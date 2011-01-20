#!/usr/bin/perl


# ce sript permet de fetcher tous les mails d'une boite mail et de créer un fichier de commande au format RUBIS (structure d'accueil de commande)
# il depose ensuite ce fichier sur le disque partagé pour une intégration dans Rubis

use Data::Dumper;
use POSIX qw(strftime);
use Net::POP3;
use Config::IniFiles;
use Email::Simple;
use File::Copy;
use File::Basename;
use Text::Wrap;
use Getopt::Long;
my $debug; GetOptions ('debug' => \$debug);  # flag
$|=1;
print print_time()."START\n";

# charge la config en mémoire
my $cfg  = new Config::IniFiles( -file => 'pop2rubis.ini' , -nocase=>1 ) or die "Impossible de charger le fichier de config 'pop2rubis.ini'";
my $data = {} ;


# on tente d'aller sur le disqie partagé --> erreur
print print_time()."Testing access to disk ... ";
open(TEST,'+>'.$cfg->val('file','path_temporary_file')) or die "Ne peux pas creer le fichier TEST '".$cfg->val('file','path_temporary_file')."' ($!)";
close(TEST);
unlink($cfg->val('file','path_temporary_file')) or die "Ne peux pas supprimer le fichier TEST '".$cfg->val('file','path_temporary_file')."' ($!)";
print "ok\n";


# début du script
POP3_FETCH:
print print_time()."POP3 connection ... ";
my $pop3 = Net::POP3->new($cfg->val('pop3','host'), ResvPort=>$cfg->val('pop3','port') , Timeout => 30) or die "Impossible de se connecter au serveur POP3";
print "ok\n";

print print_time()."POP3 authentification ... ";
my $authentification = $pop3->login($cfg->val('pop3','user'), $cfg->val('pop3','pass'));
if (defined($authentification) && $authentification > 0) {
	print "ok\n";
	foreach my $msgnum (keys %{$pop3->list}) {
		my $email= Email::Simple->new(join('',@{$pop3->get($msgnum)}));
		#print $email->header('From')."\n";
		#print $email->header('Subject')."\n";
		#print $email->header('To')."\n";
		my ($messageId) = ($email->header('Message-Id') =~ m/^<(.+?)\@/i);
		$messageId = substr($messageId, 0 , 15);
		my $subject   = $cfg->val('pop3','valid_subject');

		# procedure de la validation que l'email est bien une commande
		if ($email->header('To') eq $cfg->val('pop3','valid_to') && $email->header('Subject') =~ m/^$subject/i) { # ok l'email est valide, on l'examine
			my ($code_client) = ($email->header('Subject') =~ m/\((.+?)\)$/i);
			my $code_cab = '';
			if ($code_client =~ /^CAB(\d+)$/i) {
				$code_client = '056039';
				$code_cab = $1;
			}


			my $ligne = 1;
			print print_time()."Commande found from $code_client Parsing,";
			$data->{$messageId} = {	'SNOCLI'=>$code_client,
									'SNTBOS'=>'', 'SNTBOA'=>'', 'SNTBOM'=>'', 'SNTBOJ'=>'', # date du bon
									'SNTLIS'=>'', 'SNTLIA'=>'', 'SNTLIM'=>'', 'SNTLIJ'=>'', # date de livraison
									'SNTRFC'=>'',											# reference
									#'SNTRFS'=>'', 'SNTRFA'=>'', 'SNTRFM'=>'', 'SNTRFJ'=>'',# date de la cde client
									#'SNTNAL'=>'', 'SNTAL1'=>'','SNTAL2'=>'','SNTRUL'=>'','SNTVIL'=>'','SNTBDL'=>'','SNTBDL'=>'', # adresse de livraison
									'articles' => [],
									'commentaires' => []
								  };
			#format info article : {'SEOLIG'=>'','SENART'=>'','SENROF'=>'','SENTYP'=>'','SENQTE'=>''} # info article

			$data->{$messageId}->{'SNTCHA'} = $code_cab ? sprintf('%03d',$code_cab) : 'SANS'; # code chantier CAB ou 'SANS'

			my @body = split /\n/, $email->body;
			foreach (@body) {
				chomp;
				if		(/^\s*date\s*=(\d{2})\/(\d{2})\/(\d{2})(\d{2})/i) { # date
					$data->{$messageId}->{'SNTBOS'} = $3;
					$data->{$messageId}->{'SNTBOA'} = $4;
					$data->{$messageId}->{'SNTBOM'} = $2;
					$data->{$messageId}->{'SNTBOJ'} = $1;


				} elsif (/^\s*livraison sur\s*=(.*)/i) { # livraison sur (chantier ou depot)
					push @{$data->{$messageId}->{'commentaires'}} , {'SEOLIG'=>sprintf('%03d',$ligne),  'SENART'=>'',  'SENROF'=>'R',  'SENTYP'=>'COM',  'SENQTE'=>'', 'SENCSA'=>"Livraison sur $1"} ;
					$ligne += 2;


				} elsif (/^\s*adresse\s*de livraison\s*=(.*)/i) { # adresse de livraison
					my $adr_liv = $1;
					$adr_liv =~ s/< *br *\/? *>//ig; # supprime les <br> dans le texte
					$Text::Wrap::columns	= 55;
					$Text::Wrap::separator	= "\x0D" ;
					foreach my $tmp (split(/\x0D/,  wrap('','',$adr_liv)  )) { # pour chaque ligne de commentaire
						$tmp =~ s/\\//g;
						push @{$data->{$messageId}->{'commentaires'}} , {'SEOLIG'=>sprintf('%03d',$ligne),  'SENART'=>'',  'SENTYP'=>'COM',  'SENQTE'=>'', 'SENCSA'=> substr($tmp,0,60)} ;
						$ligne += 2;
					}


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


				} elsif (/^\s*commentaire\s*=(.*)/i) { # commentaire sur la commande
					#on vérifie que le com ne fait pas plus de 60 caractere de long --> sinon on coupe (on rajoute un \x0D)
					my $com = $1;
					$com =~ s/< *br *\/? *>//ig; # supprime les <br> dans le texte
					$Text::Wrap::columns	= 55;
					$Text::Wrap::separator	= "\x0D" ;
					foreach my $tmp (split(/\x0D/,  wrap('','',$com)  )) { # pour chaque ligne de commentaire
						$tmp =~ s/\\//g;
						push @{$data->{$messageId}->{'commentaires'}} , {'SEOLIG'=>sprintf('%03d',$ligne),  'SENART'=>'',  'SENTYP'=>'COM',  'SENQTE'=>'', 'SENCSA'=> substr($tmp,0,60)} ;
						$ligne += 2;
					}


				} elsif (/^\s*article_(.+?)\s*=(.*)/i) { # article avec sa qte
					push @{$data->{$messageId}->{'articles'}} , {'SEOLIG'=>sprintf('%03d',$ligne),  'SENART'=>$1,  'SENTYP'=>'',  'SENQTE'=>$2, 'SENCSA'=>''} ;
					$ligne += 2;
				}
			}

			print " deleting ... ";
			$pop3->delete($msgnum);
			print "ok\n";

		} else {
			print print_time()."Malformed email found ($msgnum). Deleting ... ";
			$pop3->delete($msgnum);
			print "ok\n";
		}
	}
} elsif ($authentification == '0E0') {
	print "ok, but no message\n";
	$pop3->quit;
	goto END;
} else {
	die "Impossible de s'identifier sur le serveur POP3";
}
$pop3->quit;

if ($debug) {
	print Dumper($data);
	exit;
}

# génération du fichier CSV
CSV:
print print_time()."Generating CSV file ... ";
open(CSV,'>>'.$cfg->val('file','path_temporary_file')) or die "Ne peux pas creer le fichier CSV temporaire '".$cfg->val('file','path_temporary_file')."' ($!)";
# print header
print CSV join(';',qw/SNOCLI SNOBON SNTROF SNTCHA SNTBOS SNTBOA SNTBOM SNTBOJ SNTLIS SNTLIA SNTLIM SNTLIJ
						SNTRFC SNTRFS SNTRFA SNTRFM SNTRFJ SNTVTE SNTTTR SNTPRO SNTGAL SEOLIG SENART SENROF SENTYP SENQTE SENCSA/)."\n";
foreach my $uniqid (keys %$data) {
	foreach my $com ((@{$data->{$uniqid}->{'commentaires'}},@{$data->{$uniqid}->{'articles'}})) {
		if ($data->{$uniqid}->{'SNOCLI'} eq 'benjamin') {
			$data->{$uniqid}->{'SNOCLI'} = 'POULAI'; # patch pour le code client de benjamin
		}

		print CSV join(';',
					$data->{$uniqid}->{'SNOCLI'}, # n° client
					$uniqid,					  # numero unique
					'R',                          # R
					$data->{$uniqid}->{'SNTCHA'}, # code chantier
					$data->{$uniqid}->{'SNTBOS'}, # date bon
					$data->{$uniqid}->{'SNTBOA'},
					$data->{$uniqid}->{'SNTBOM'},
					$data->{$uniqid}->{'SNTBOJ'},
					$data->{$uniqid}->{'SNTLIS'}, # date de liv
					$data->{$uniqid}->{'SNTLIA'},
					$data->{$uniqid}->{'SNTLIM'},
					$data->{$uniqid}->{'SNTLIJ'},
					$data->{$uniqid}->{'SNTRFC'}, # reference
					$data->{$uniqid}->{'SNTBOS'}, # date cde client
					$data->{$uniqid}->{'SNTBOA'},
					$data->{$uniqid}->{'SNTBOM'},
					$data->{$uniqid}->{'SNTBOJ'},
					'LIV',
					'NON',
					'CDC',
					'O',
					$com->{'SEOLIG'},
					$com->{'SENART'},
					'R',
					$com->{'SENTYP'},
					$com->{'SENQTE'},
					$com->{'SENCSA'}
			  )."\n";
	}	
}
close CSV;
print "ok\n";

# copie du fichier temporaire a l'emplacement finale sur le disque partagé
if (!-e $cfg->val('file','path_file')) { # si le fichier finale n'existe pas alors on move le fichier temporaire
	move($cfg->val('file','path_temporary_file'),$cfg->val('file','path_file')) or die "Ne peux pas deplacer le fichier temporaire en fichier finale '".$cfg->val('file','path_file')."' ($!)";
} else {
	 # si le fichier existe deja alors on ne le copie pas, on attend patiement.
}

END:

print print_time()."END\n\n";
print STDERR Dumper($data) if $debug;

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}