#!/usr/bin/perl


# ce sript permet de fetcher tous les mails d'une boite mail et de cr�er un fichier de commande au format RUBIS (structure d'accueil de commande)
# il depose ensuite ce fichier sur le disque partag� pour une int�gration dans Rubis

use Data::Dumper;
use POSIX qw(strftime ceil);
use Net::POP3;
use Net::FTP;
use File::Path;
use Config::IniFiles;
use Email::Simple;
use File::Copy;
use File::Basename;
use Text::Wrap;
use Getopt::Long;
use Win32::ODBC;
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;

my $debug; GetOptions ('debug' => \$debug);  # flag
$|=1;
print get_time()."START\n";

# charge la config en m�moire
my $cfg  = new Config::IniFiles( -file => 'pop2rubis.ini' , -nocase=>1 ) or die "Impossible de charger le fichier de config 'pop2rubis.ini'";

# conection � la base Loginor
my $cfg2 = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg2->{LOGINOR_PREFIX_BASE};
print get_time()."Connecting to ODBC '".$cfg2->{LOGINOR_DSN}."' ... ";
my $loginor = new Win32::ODBC('DSN='.$cfg2->{LOGINOR_DSN}.';UID='.$cfg2->{LOGINOR_USER}.';PWD='.$cfg2->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter � rubis";
print "ok\n";

# on tente d'aller sur le disqie partag� --> erreur
print get_time()."Testing access to disk ... ";
open(TEST,'+>TEST.TMP') or die "Ne peux pas creer le fichier TEST 'TEST.TMP' ($!)"; close(TEST);
unlink('TEST.TMP') or die "Ne peux pas supprimer le fichier TEST 'TEST.TMP' ($!)";
print "ok\n";

if (!-d 'temp') { mkpath('temp'); } # cree un repertoire temporaire pour la r�cup�ration des fichiers s'il n'existe pas

my $nb_mail = 0;
my $nb_file = 0;

######################################################################################################################
# recuperation des commandes via FTP
######################################################################################################################
FTP_FETCH:
print get_time()."FTP connection ... ";
my 	$ftp = Net::FTP->new($cfg->val(qw/FTP host/), Debug => 0) or die "Cannot connect to ".$cfg->val(qw/FTP host/).": $@";
print "ok, authentification ... ";
	$ftp->login($cfg->val(qw/FTP user/), $cfg->val(qw/FTP pass/)) or die "Cannot login ", $ftp->message;
print "ok\n";
	$ftp->cwd($cfg->val(qw/FTP directory/)) or die "Cannot change working directory ", $ftp->message;

foreach ($ftp->ls) {
	if (/^\.{1,2}$/) { # si repertoire . ou .. on saute
		next;
	}

	if (/\.txt$/) { # si c'est une commande
		$ftp->get($_,"temp/$_"); # on rappatrie
		$nb_file++;
	}

	$ftp->delete($_); # on supprime dans tous les cas
}
$ftp->quit;
print get_time()."FTP get $nb_file file(s)\n";
goto FILE_FETCH;


######################################################################################################################
# recuperation des commandes via EMAIL
######################################################################################################################
# POP3_FETCH:
# print get_time()."POP3 connection ... ";
# my $pop3 = Net::POP3->new($cfg->val('pop3','host'), ResvPort=>$cfg->val('pop3','port') , Timeout => 3) or die "Impossible de se connecter au serveur POP3";
# print "ok\n";

# print get_time()."POP3 authentification ... ";
# my $authentification = $pop3->login($cfg->val('pop3','user'), $cfg->val('pop3','pass'));
# if (defined($authentification) && $authentification > 0) {
# 	print "ok\n";
# 	foreach my $msgnum (keys %{$pop3->list}) {
# 		my $email= Email::Simple->new(join('',@{$pop3->get($msgnum)}));
# 		my ($messageId) = ($email->header('Message-Id') =~ m/^<(.+?)\@/i);
# 		$messageId = substr($messageId, 0 , 15);
# 		my $valid_subject   = $cfg->val('pop3','valid_subject');
# 		my $valid_to   		= $cfg->val('pop3','valid_to');

# 		# procedure de la validation que l'email est bien une commande
# 		if ($email->header('To') =~ m/$valid_to/i  && $email->header('Subject') =~ m/^$valid_subject/i) { # ok l'email est valide, on l'examine
# 			my ($code_client) = ($email->header('Subject') =~ m/\((.+?)\)$/i);
			
# 			# stock le mail en local pour analyse plus tard
# 			open(F,"temp/$messageId.txt") or die ("Ne peux pas creer le fichier temp $messageId.txt ($!)");
# 			print F "from=$code_client\n";
# 			print F $email->body;
# 			close(F);

# 			# deleting message from pop3
# 			$pop3->delete($_);
# 			$nb_mail++;
		
# 		} else {
# 			print get_time()."Malformed email found ($msgnum). To:'".$email->header('To')."', Subject:'".$email->header('Subject')."'. Deleting ... ";
# 			$pop3->delete($msgnum);
# 			print "ok\n";
# 		}
# 	}

# } elsif ($authentification == '0E0') {
# 	print "ok, but no message\n";
# 	$pop3->quit;
# }
# print get_time()."POP3 get $nb_mail mail(s)\n";


######################################################################################################################
# lit les fichiers pr�sent sur le disque local
######################################################################################################################
FILE_FETCH:

my $data = {} ;
my @files_to_delete ;
opendir(D,'temp') or die "Impossible d'ouvrir le r�ertoire local pour lecture ($!)";
foreach my $filename (readdir(D)) { # pour chaque fichier pr�sent dans le r�pertoire

	# si ce n'est pas un .txt --> pas une commande, on saute
	if ($filename !~ /\.txt$/i) { next ; }

	my 	$messageId = $filename;
		$messageId =~ s/\.txt$//i; #  remove .txt at the end of file name

	open(F, "temp/$filename") or die "Ne peux pas ouvrir $filename ($!)";
	my @body = <F>;
	close F;
	push @files_to_delete, "temp/$filename"; # on note qu'il faudra supprimer ce fichier plus tard (quand tout ce sera bien pass�)

	my $code_client = '';
	foreach (@body) {
		if (/^\s*from\s*=(.*)/i) { # code du client
			$code_client = $1;
		}
	}

	my $code_cab = '';
	if ($code_client =~ /^CAB(\d+)$/i) {
		$code_client = '056039';
		$code_cab = $1;
	}

	if ($code_client eq '') {
		print get_time()."Malformed commande in $filename (no code_client). Skip\n";
		next ;
	}

	my $ligne = 1;
	print get_time()."Commande found from $code_client\n";
	$data->{$messageId} = {	'SNOCLI'=>$code_client,
							'SNTBOS'=>'', 'SNTBOA'=>'', 'SNTBOM'=>'', 'SNTBOJ'=>'', # date du bon
							'SNTLIS'=>'', 'SNTLIA'=>'', 'SNTLIM'=>'', 'SNTLIJ'=>'', # date de livraison
							'SNTRFC'=>'',											# reference
							'SNTNOM'=>'','SNTCA1'=>'','SNTCA2'=>'','SNTCRU'=>'','SNTCVI'=>'','SNTCCP'=>'','SNTCBD'=>'', # adr de livraison
							#'SNTRFS'=>'', 'SNTRFA'=>'', 'SNTRFM'=>'', 'SNTRFJ'=>'',# date de la cde client
							#'SNTNAL'=>'', 'SNTAL1'=>'','SNTAL2'=>'','SNTRUL'=>'','SNTVIL'=>'','SNTBDL'=>'','SNTBDL'=>'', # adresse de livraison
							'SNTAL1' => '',
							'SNTCAM'=>'', # code camion
							'articles' => [],
							'commentaires' => []
						  };
	#format info article : {'SEOLIG'=>'','SENART'=>'','SENROF'=>'','SENTYP'=>'','SENQTE'=>''} # info article


	foreach (@body) {
		chomp;
		if		(/^\s*date\s*=(\d{2})\/(\d{2})\/(\d{2})(\d{2})/i) { # date
			$data->{$messageId}->{'SNTBOS'} = $3;
			$data->{$messageId}->{'SNTBOA'} = $4;
			$data->{$messageId}->{'SNTBOM'} = $2;
			$data->{$messageId}->{'SNTBOJ'} = $1;


		} elsif (/^\s*livraison sur\s*=(.*)/i) { # livraison sur (chantier ou depot)
			$data->{$messageId}->{'SNTCAM'} = $1 eq 'livraison' ? 'LDP':'DIS';


		} elsif (/^\s*adresse\s*de livraison\s*=(.*)/i) { # adresse de livraison
			my $adr_liv = $1;
			#''SNTCA1'=>'','SNTCA2'=>'','SNTCRU'=>'','SNTCVI'=>'','SNTCCP'=>'','SNTCBD'=>'', # adr de livraison
			my @adresses = split(/\\n/,$adr_liv);
			$data->{$messageId}->{'SNTCA1'} = $adresses[0] if exists $adresses[0];
			$data->{$messageId}->{'SNTCA2'} = $adresses[1] if exists $adresses[1];
			$data->{$messageId}->{'SNTCRU'} = $adresses[2] if exists $adresses[2];
			$data->{$messageId}->{'SNTCVI'} = $adresses[3] if exists $adresses[3];
			$data->{$messageId}->{'SNTCCP'} = $adresses[4] if exists $adresses[4];
			$data->{$messageId}->{'SNTCBD'} = $adresses[5] if exists $adresses[5];


		} elsif (/^\s*nom\s*=(.*)/i) { # nom du client
			$data->{$messageId}->{'SNTNOM'} = $1;


		} elsif (/^\s*date de livraison\s*=(\d{2})\/(\d{2})\/(\d{2})(\d{2})/i) { # date de livraison
			$data->{$messageId}->{'SNTLIS'} = $3;
			$data->{$messageId}->{'SNTLIA'} = $4;
			$data->{$messageId}->{'SNTLIM'} = $2;
			$data->{$messageId}->{'SNTLIJ'} = $1;


		} elsif (/^\s*reference\s*=(.*)/i) { # reference de la commande
			my $ref = $1;
			if ($code_cab && $code_client eq '056039') { # commande de la CAB56 --> on met le code adh CAB dans la ref client
				$ref = sprintf('%03d',$code_cab)."/$ref";
			}
			$data->{$messageId}->{'SNTRFC'} = substr($ref,0,20);


		} elsif (/^\s*chantier\s*=(.*)/i) { # chantier de la commande
			my $chantier = $1 || 'SANS';	# code chantier a 'SANS' par d�faut
			$data->{$messageId}->{'SNTCHA'} = $code_cab ? sprintf('%03d',$code_cab) : $chantier; # code chantier CAB ou 'SANS'


		} elsif (/^\s*commentaire\s*=(.*)/i) { # commentaire sur la commande
			#on v�rifie que le com ne fait pas plus de 60 caractere de long --> sinon on coupe (on rajoute un \x0D)
			my $com = $1;
			$com =~ s/< *br *\/? *>//ig; # supprime les <br> dans le texte
			$Text::Wrap::columns	= 55;
			$Text::Wrap::separator	= "\x0D" ;
			foreach my $tmp (split(/\x0D/,  wrap('','',$com)  )) { # pour chaque ligne de commentaire
				$tmp =~ s/\\//g;
				push @{$data->{$messageId}->{'commentaires'}}, {'SEOLIG'=>sprintf('%03d',$ligne), 'SENART'=>'', 'SENTYP'=>'COM', 'SENQTE'=>'', 'SENCSA'=> substr($tmp,0,60)} ;
				$ligne += 2;
			}


		} elsif (/^\s*article_(.+?)\s*=([\d\.\,]*)=([\d\.\,]*)/i) { # article avec sa qte et sa remise
			my ($code,$qte,$remise) = ($1,$2,$3);
			$qte 	=~ s/\./,/g; # pour les chiffre flotant, transforme les "." en ",".
			$remise =~ s/\./,/g; # pour les chiffre flotant, transforme les "." en ",".
			push @{$data->{$messageId}->{'articles'}}, {'SEOLIG'=>sprintf('%03d',$ligne), 'SENART'=>$code, 'SENTYP'=>'', 'SENQTE'=>$qte, 'SENCSA'=>'', 'SENRP1'=>$remise} ;
			$ligne += 2;
		}
	}
} # fin pour chaque fichier

closedir(D);

if ($debug) {
	print Dumper($data);
	#exit;
}


######################################################################################################################
# GENERATION DU FICHIER CSV
######################################################################################################################
CSV:

if (keys %$data <= 0) { # si aucune donn�e trait�, on va � la fin
	goto END;
}

print get_time()."Generating CSV file ... ";
open(CSV,'>>'.$cfg->val('file','path_temporary_file')) or die "Ne peux pas creer le fichier CSV temporaire '".$cfg->val('file','path_temporary_file')."' ($!)";
# print header
print CSV join(';',qw/SNOCLI SNOBON SNTROF SNTCHA SNTBOS SNTBOA SNTBOM SNTBOJ SNTLIS SNTLIA SNTLIM SNTLIJ
						SNTRFC SNTRFS SNTRFA SNTRFM SNTRFJ SNTVTE SENTCD SNTTTR SNTPRO SNTGAL SEOLIG SENART SENROF
						SENTYP SENNBR SENQTE SENCSA SNTCAM SNTNOM SNTCA1 SNTCA2 SNTCRU SNTCVI SNTCCP SNTCBD SENRP1/)."\n";

foreach my $uniqid (keys %$data) {
	foreach my $com ((@{$data->{$uniqid}->{'commentaires'}},@{$data->{$uniqid}->{'articles'}})) {

		# transforme mon code web en vrai code rubis
		if ($data->{$uniqid}->{'SNOCLI'} eq 'benjamin' || $data->{$uniqid}->{'SNOCLI'} eq 'benjamin2') {
			$data->{$uniqid}->{'SNOCLI'} = 'POULAI'; # patch pour le code client de benjamin
		}

		# code chantier par defaut
		if (!exists $data->{$uniqid}->{'SNTCHA'}) { # code chantier par d�faut a SANS
			$data->{$uniqid}->{'SNTCHA'}='SANS';
		}

		# v�rifie si le produit est commandable par nombre de conditionnment ou par unit�
		my $nombre = '';
		if ($loginor->Sql("select CONDI as CONDITIONNEMENT, CDCON as CONDITIONNEMENT_DIVISBLE from ${prefix_base_rubis}GESTCOM.AARTICP1 where NOART='".$com->{'SENART'}."'")) { # regarde le conditionnement de l'article
			die "Erreur dans la recquete de recuperation des conditionnement article (".$loginor->Error().")";
		}
		while($loginor->FetchRow()) {
			my %row = $loginor->DataHash() ;
			if ($row{'CONDITIONNEMENT_DIVISBLE'} eq 'NON' && $row{'CONDITIONNEMENT'} && $row{'CONDITIONNEMENT'}>1) { # si un condi non divible est renseign�, on doit le command� par nombre
				$nombre = ceil($com->{'SENQTE'} / $row{'CONDITIONNEMENT'});
			}
		}
		%row = {};

		# genere la ligne
		print CSV join(';',
					$data->{$uniqid}->{'SNOCLI'}, 	# n� client
					$uniqid,					  	# numero unique
					'R',                          	# ligne en reliquat
					$data->{$uniqid}->{'SNTCHA'}, 	# code chantier
					$data->{$uniqid}->{'SNTBOS'}, 	# date bon SS
					$data->{$uniqid}->{'SNTBOA'}, 	# date bon AA
					$data->{$uniqid}->{'SNTBOM'}, 	# date bon MM
					$data->{$uniqid}->{'SNTBOJ'}, 	# date bon JJ
					$data->{$uniqid}->{'SNTLIS'}, 	# date de liv SS
					$data->{$uniqid}->{'SNTLIA'}, 	# date de liv AA
					$data->{$uniqid}->{'SNTLIM'}, 	# date de liv MM
					$data->{$uniqid}->{'SNTLIJ'}, 	# date de liv JJ
					$data->{$uniqid}->{'SNTRFC'}, 	# reference
					$data->{$uniqid}->{'SNTBOS'}, 	# date cde client SS
					$data->{$uniqid}->{'SNTBOA'}, 	# date cde client AA
					$data->{$uniqid}->{'SNTBOM'}, 	# date cde client MM
					$data->{$uniqid}->{'SNTBOJ'}, 	# date cde client JJ
					'LIV', 							# type de vente
					#($data->{$uniqid}->{'SNTCAM'} eq 'DIS' ? 'STO':''), # on ne cree pas de cde fournisseur dans le cas d'une DIS
					'', # on cree une code fournisseur si il n'y a pas de dispo
					'NON', 							# livraison partiel
					'CDC', 							# provenance STRACC
					'O',
					$com->{'SEOLIG'},				# n� de ligne
					$com->{'SENART'},				# code article
					'R',				
					$com->{'SENTYP'},
					$nombre,						# nombre (pour les article a conditionnement)
					$nombre ? '':$com->{'SENQTE'},	# quantit� si le nombre n'est pas deja renseign�
					$com->{'SENCSA'},				# commentaire
					$data->{$uniqid}->{'SNTCAM'}, 	# code camion
					$data->{$uniqid}->{'SNTNOM'}, 	# nom client
					$data->{$uniqid}->{'SNTCA1'}, 	# ligne adr1
					$data->{$uniqid}->{'SNTCA2'}, 	# ligne adr2
					$data->{$uniqid}->{'SNTCRU'}, 	# ligne adr3
					$data->{$uniqid}->{'SNTCVI'}, 	# ligne adr4
					$data->{$uniqid}->{'SNTCCP'}, 	# ligne adr5
					$data->{$uniqid}->{'SNTCBD'},  	# ligne adr6
					$com->{'SENRP1'}				# remise web
			  )."\n";
	}	
}
close CSV;
print "ok\n";

# si le fichier final est deja pr�sent mais qu'il ne comporte que les entetes --> bug. Le fichier s'est bloqu� pour une raison inconnu
# il faut alors supprimer le fichier pour que le fichier temp puisse etre trait�
if (-e $cfg->val('file','path_file')) {
	open(F,'<'.$cfg->val('file','path_file'));
	my @lines = <F>;
	close F;

	if (scalar @lines <= 1) { # le fichier n'a qu'une seul ligne --> bug, on le supprime.
		unlink($cfg->val('file','path_file')) ;
	}
}

# copie du fichier temporaire a l'emplacement finale sur le disque partag�
if (!-e $cfg->val('file','path_file') && -e $cfg->val('file','path_temporary_file')) { # si le fichier finale n'existe pas alors on move le fichier temporaire
	move($cfg->val('file','path_temporary_file'),$cfg->val('file','path_file')) or die "Ne peux pas deplacer le fichier temporaire en fichier finale '".$cfg->val('file','path_file')."' ($!)";
} else {
	 # si le fichier existe deja alors on ne le copie pas, on attend patiement.
}

# on supprime les fichiers du r�pertoire temp si tout c'est bien pass�
print get_time()."Removing files ... ";
# sauvegarde les fichiers
my $save_path = 'cde_web_historique/'.strftime('%Y/%m/%d',localtime);
mkpath($save_path); # cree le r�pertoire de sauvegarde des cde web
foreach (@files_to_delete) {
	#unlink or die "Ne peux pas supprimer le fichier $_ ($!)";
	move($_,"$save_path/".basename($_));
}
print "ok\n";

END:
$loginor->Close(); #close la connection a Rubis
print get_time()."END\n\n";



#########################################################################################################################################
sub get_time {
	return strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
}