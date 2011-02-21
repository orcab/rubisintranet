#!/usr/bin/perl
my $VERSION = 3.0;

use constant ;
use Data::Dumper;					# pour les test
use Win32::ODBC;					# pour se connecter à Loginor en ODBC
use Net::FTP;						# pour l'upload de fichier
use File::Copy;						# pour le déplacement de fichier
use strict ;						# pour la clareté du script
use POSIX qw(strftime);				# pour faire des print propre
use DBI qw(:sql_types);				# pour gérer SQLite
require 'Phpconst2perlconst.pm';	# lib perso pour récupérer les constantes d'un script PHP
use Phpconst2perlconst ;
use Getopt::Long ;					# pour parser les parametre du script
use Config::IniFiles;
use constant {
	ETAT_SPECIAL		=> 1<<0,
	ETAT_LIVRE			=> 1<<1,
	ETAT_PREPARE		=> 1<<2,
	ETAT_COMMENTAIRE	=> 1<<3
};

$|=1;								# pour ne pas flusher directement les sortie print

my %skip	= qw/init 0 delete 0 bon 0 devis 0 compress 0 upload 0/;
my %options = ();
GetOptions (\%options, 'skip=s','all','help|?','version','dbname=s','from=s','to=s') or die ;

print_help()	if exists $options{'help'} ; # affiche le message avec les options dispo
print_version() if exists $options{'version'} ; # affiche le message avec les options dispo

$options{'dbname'} = 'cde_rubis.db' if !exists $options{'dbname'} ;

if (exists $options{'skip'}) { # on doit sauter des étapes
	foreach (split(/,/,$options{'skip'})) { # on regarde lesquelles
		if (exists $skip{lc $_}) { # valeur de skip autorisée
			$skip{lc $_} = 1;
		} else { # erreur --> die
			die("Option --skip=$_ inconnu");
		}
	}
}
if (exists $options{'from'} && $options{'from'} !~ /^\d{4}-\d{2}-\d{2}$/) { #le format de date n'est pas bon --> erreur
	print "Usage --from=yyyy-mm-dd ('".$options{'from'}."' n'est pas un format valide)"; exit;
}
if (exists $options{'to'} && $options{'to'} !~ /^\d{4}-\d{2}-\d{2}$/) { #le format de date n'est pas bon --> erreur
	print "Usage --to=yyyy-mm-dd ('".$options{'to'}."' n'est pas un format valide)"; exit;
}
if (exists $options{'from'} xor exists $options{'to'}) {
	print "Usage --from ne peut pas etre utilise sans --to et vice versa"; exit;
}


print print_time()."START\n";

my $ini						= new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );
my $cfg						= new Phpconst2perlconst(-file => '../../intranet/inc/config.php');
my $prefix_base_rubis		= $cfg->{LOGINOR_PREFIX_BASE};
my $loginor_agence			= $cfg->{LOGINOR_AGENCE};
my $thirty_days_ago			= strftime('%Y-%m-%d'	,0,0,0,	strftime('%d', localtime) - 7, strftime('%m', localtime) - 1, strftime('%Y', localtime) - 1900) ;
my $thirty_days_ago_rubis	= strftime('%Y%m%d'		,0,0,0,	strftime('%d', localtime) - 7, strftime('%m', localtime) - 1, strftime('%Y', localtime) - 1900) ;
my $loginor					= new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
my $sqlite					= DBI->connect('dbi:SQLite:'.$options{'dbname'},'','',{ RaiseError => 0, AutoCommit => 0 }) or die("Pas de DB");


# creation de la base SQLite
goto END_INIT if $skip{'init'};
init_sqlite();
END_INIT: ;




goto END_BON if $skip{'bon'};
# bon de commande ###########################################################################################################"
# suppression des anciens bon
#if (!$skip{'delete'}) {
#	print print_time()."Suppression des lignes de bon ...";
#	if (exists $options{'from'} && exists $options{'to'}) {
#		$sqlite->do("DELETE FROM cde_rubis WHERE date_bon >= '$options{from}' and date_bon <= '$options{to}'") ;
#	} else {
#		$sqlite->do("DELETE FROM cde_rubis WHERE date_maj >= '$thirty_days_ago'") ;
#	}
#	print "OK\n";
#}

#$sqlite->commit;
#$sqlite->disconnect();
#goto END;

print print_time()."Select des lignes de bon ...";
my $where_date_bon = '';
if (!exists $options{'all'}) { # on n'indexe que le dernier mois
	if (exists $options{'from'} && exists $options{'to'}) {
		my $tmp = $options{'from'}; $tmp =~ s/-//g;
		$where_date_bon  = " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) >= '$tmp' " ;
		$tmp = $options{'to'}; $tmp =~ s/-//g;
		$where_date_bon .= " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) <= '$tmp' " ;
	} else {
		$where_date_bon = " and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) >= '$thirty_days_ago_rubis' " ;
	}
}

my $sql = <<EOT ;
select	NOLIG,ARCOM,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,QTREC,UNICD,PRINE,MONHT,NOMFO,REFFO,DET97,
		ENTETE_BON.NOBON,ENTETE_BON.NOCLI,
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
		CONCAT(DSEMS,CONCAT(DSEMA,CONCAT('-',CONCAT(DSEMM,CONCAT('-',DSEMJ))))) as DATE_MAJ,
		CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB))))) as DATE_LIV,
		LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,RFCSB,MONTBT,TELCL,TLCCL,
		NBLIG,
		AGENCE.AGELI,
		DSEMS,DSEMA,DSEMM,DSEMJ,			-- date de derniere MAJ du bon
		TYCDD,TRAIT,DET21 as PREPA,PROFI,	-- etat de la ligne du bon : special/livrée/preparée/commentaire
		DDISS,DDISA,DDISM,DDISJ				-- date de disponibilités
from	${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_BON
		left join ${prefix_base_rubis}GESTCOM.AENTBOP1 ENTETE_BON
			on		DETAIL_BON.NOBON=ENTETE_BON.NOBON
				and	DETAIL_BON.NOCLI=ENTETE_BON.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.NOFOU=FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		DETAIL_BON.CODAR = ARTICLE_FOURNISSEUR.NOART
				and	DETAIL_BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
			on		ENTETE_BON.NOCLI=CLIENT.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AGENCEP1 AGENCE
			on		ENTETE_BON.AGENC=AGENCE.AGECO
where
		ENTETE_BON.ETSEE = ''
	and DETAIL_BON.ETSBE = ''
		$where_date_bon
order by DETAIL_BON.NOBON asc, DETAIL_BON.NOCLI asc, DETAIL_BON.NOLIG asc
EOT
$loginor->Sql($sql); # regarde les bon du mois actif
print "OK\n";

# construction du fichier SQL pour la base internet
my $old_bon = ''; my $i=0;
my $nb_ligne = 0;
my $nb_livre = 0;
my $nb_prepa = 0;
my $nb_dispo = 0;
my $montant_dispo = 0;
print print_time()."Insertion des cde dans la base SQLite ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	my $date_dispo = '';
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	my $designation		= $row{'DS1DB'} ;
	$designation		.= $row{'DS2DB'} ? "\\n$row{DS2DB}":'';
	$designation		.= $row{'DS3DB'} ? "\\n$row{DS3DB}":'';
	$designation		= $row{'CONSA'} ? "$row{CONSA}":$designation;

	if ($old_bon ne "$row{NOBON}.$row{NOCLI}") { #nouveau
		# met a jour le nomber de ligne preparées et livrées
		$sqlite->do("UPDATE OR IGNORE cde_rubis SET nb_ligne='$nb_ligne', nb_livre='$nb_livre', nb_prepa='$nb_prepa', nb_dispo='$nb_dispo', montant_dispo='$montant_dispo' WHERE id_bon='$old_bon'");
		$nb_ligne = 0;
		$nb_livre = 0;
		$nb_prepa = 0;
		$nb_dispo = 0;
		$montant_dispo = 0;

		# supprime l'ancien bon et le détail grace au trigger
		$sqlite->do("DELETE FROM cde_rubis WHERE numero_bon='$row{NOBON}' and numero_artisan='$row{NOCLI}'");
		if ($sqlite->err()) { die "$DBI::errstr\n"; }

		# insert la nouvelle entete de commande
		$sqlite->do("INSERT OR IGNORE INTO cde_rubis (id_bon,numero_bon,numero_artisan,date_bon,date_maj,date_liv,vendeur,nb_ligne,montant,montant_dispo,reference,agence,nb_livre,nb_prepa,nb_dispo) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_MAJ}','$row{DATE_LIV}','$row{LIVSB}',0,$row{MONTBT},0,'$row{RFCSB}','$row{AGELI}','0','0','0')");
		if ($sqlite->err()) { die "$DBI::errstr\n"; }
	}

	if ($row{'QTREC'} == $row{'QTESA'} && $row{'TYCDD'} eq 'SPE') { # si quantié receptionnée == quantité commandée --> matos dispo
		$date_dispo = "$row{DDISS}$row{DDISA}-$row{DDISM}-$row{DDISJ}";
		$nb_dispo++;
		$montant_dispo += $row{'MONHT'};
	}	

	#insertion des différentes ligne du bon
	$sqlite->do("INSERT OR IGNORE INTO cde_rubis_detail (id_bon,no_ligne,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat,date_dispo) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOLIG}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".
		(		($row{'TYCDD'} eq 'SPE' ? ETAT_SPECIAL:0)
			|	($row{'TRAIT'} eq 'F'	? ETAT_LIVRE:0)
			|	($row{'PREPA'} eq 'O'	? ETAT_PREPARE:0)
			|	($row{'PROFI'} eq '9'	? ETAT_COMMENTAIRE:0)
		)
		.",'$date_dispo')");
	if ($sqlite->err()) { die "$DBI::errstr\n"; }



	if ($row{'PROFI'} eq '1') { # article et non pas com'
		$nb_ligne++;

		if ($row{'TRAIT'} eq 'F') {
			$nb_livre++ ;
		} else {
			$nb_prepa++ if ($row{'PREPA'} eq 'O');
		}

		if ($row{'TYCDD'} eq 'STO') { # matos en stock, donc forcement recu
			$nb_dispo++;
			$montant_dispo += $row{'MONHT'};
		}
	}

	$old_bon = "$row{NOBON}.$row{NOCLI}";
}

# met a jour le nomber de ligne preparées et livrées
$sqlite->do("UPDATE OR IGNORE cde_rubis SET nb_ligne='$nb_ligne', nb_livre='$nb_livre', nb_prepa='$nb_prepa' WHERE id_bon='$old_bon'");
print "OK\n";
END_BON: ;





goto END_DEVIS if $skip{'devis'};
# devis ###########################################################################################################"
# suppression des anciens devis
#if (!$skip{'delete'}) {
#	print print_time()."Suppression des devis ...";
#	if (exists $options{'from'} && exists $options{'to'}) {
#		$sqlite->do("DELETE FROM devis_rubis WHERE date_bon >= '$options{from}' and date_bon <= '$options{to}'") ;
#	} else {
#		$sqlite->do("DELETE FROM devis_rubis WHERE date_maj >= '$thirty_days_ago'");
#	}
#	print "OK\n";
#}

print print_time()."Select des devis ...";
my $where_date_devis = '';
if (!exists $options{'all'}) { # on n'indexe que le dernier mois
	if (exists $options{'from'} && exists $options{'to'}) {
		my $tmp = $options{'from'}; $tmp =~ s/-//g;
		$where_date_devis  = " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) >= '$tmp' " ;
		$tmp = $options{'to'}; $tmp =~ s/-//g;
		$where_date_devis .= " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) <= '$tmp' " ;
	} else {
		$where_date_devis = " and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) >= '$thirty_days_ago_rubis' " ;
	}
}

my $sql = <<EOT ;
select	NOLIG,ARCOM,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,PRINE,MONHT,NOMFO,REFFO,DET97,
		ENTETE_BON.NOBON,ENTETE_BON.NOCLI,
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
		CONCAT(DSEMS,CONCAT(DSEMA,CONCAT('-',CONCAT(DSEMM,CONCAT('-',DSEMJ))))) as DATE_MAJ,
		CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB))))) as DATE_LIV,
		LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,RFCSB,MONTBT,TELCL,TLCCL,
		NBLIG,
		AGENCE.AGELI,
		DSEMS,DSEMA,DSEMM,DSEMJ,	-- date de derniere MAJ du bon
		TYCDD,PROFI					-- etat de la ligne du bon : special/livrée/preparée/commentaire
from	${prefix_base_rubis}GESTCOM.ADETBVP1 DETAIL_BON
		left join ${prefix_base_rubis}GESTCOM.AENTBVP1 ENTETE_BON
			on		DETAIL_BON.NOBON=ENTETE_BON.NOBON
				and	DETAIL_BON.NOCLI=ENTETE_BON.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.NOFOU=FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		DETAIL_BON.CODAR = ARTICLE_FOURNISSEUR.NOART
				and	DETAIL_BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
			on		ENTETE_BON.NOCLI=CLIENT.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AGENCEP1 AGENCE
			on		ENTETE_BON.AGENC=AGENCE.AGECO
where	
		ENTETE_BON.ETSEE = ''
	and DETAIL_BON.ETSBE = ''
		$where_date_devis
order by DETAIL_BON.NOBON asc, DETAIL_BON.NOCLI asc, DETAIL_BON.NOLIG asc
EOT
$loginor->Sql($sql);
print "OK\n";

# construction du fichier SQL pour la base internet
my $old_bon = ''; my $i=0;
print print_time()."Insertion des devis dans la base SQLite ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	my $designation		= $row{'DS1DB'} ;
	$designation		.= $row{'DS2DB'} ? "\\n$row{DS2DB}":'';
	$designation		.= $row{'DS3DB'} ? "\\n$row{DS3DB}":'';
	$designation		= $row{'CONSA'} ? "$row{CONSA}":$designation;

	if ($old_bon ne "$row{NOBON}.$row{NOCLI}") { #nouveau
		# supprime l'ancien bon et le détail grace au trigger
		$sqlite->do("DELETE FROM cde_rubis WHERE numero_bon='$row{NOBON}' and numero_artisan='$row{NOCLI}'");
		if ($sqlite->err()) { die "$DBI::errstr\n"; }

		# insert le nouveau
		$sqlite->do("INSERT OR IGNORE INTO devis_rubis (id_bon,numero_bon,numero_artisan,date_bon,date_maj,date_liv,vendeur,nb_ligne,montant,reference,agence) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_MAJ}','$row{DATE_LIV}','$row{LIVSB}',$row{NBLIG},$row{MONTBT},'$row{RFCSB}','$row{AGELI}')");
		if ($sqlite->err()) { die "$DBI::errstr\n"; }
	}

	#insertion des différente ligne du bon
	$sqlite->do("INSERT OR IGNORE INTO devis_rubis_detail (id_bon,no_ligne,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOLIG}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".
			($row{'TYCDD'} eq 'SPE' ? ETAT_SPECIAL:0)
		.")");
	if ($sqlite->err()) { die "$DBI::errstr\n"; }

	$old_bon = "$row{NOBON}.$row{NOCLI}";
}
print "OK\n";
END_DEVIS: ;






#goto END_RELIQUAT if $skip{'reliquat'};
## reliquat ###########################################################################################################"
## suppression des anciens reliquats
#if (!$skip{'delete'}) {
#	print print_time()."Suppression des reliquats ...";
#	$sqlite->do("DELETE FROM reliquat");
#	$sqlite->do("DELETE FROM reliquat_detail");
#	print "OK\n";
#}
#
#print print_time()."Select des reliquats ...";
#my $sql = <<EOT ;
#select	NOLIG,ARCOM,PROFI,TYCDD,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,PRINE,MONHT,NOMFO,REFFO,DET97,
#		ENTETE_BON.NOBON,ENTETE_BON.NOCLI,
#		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
#		CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB))))) as DATE_LIV,
#		LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,RFCSB,TELCL,TLCCL,
#		AGENCE.AGELI,
#		DETAIL_BON.QTREC,DETAIL_BON.DDISS,DETAIL_BON.DDISA,DETAIL_BON.DDISM,DETAIL_BON.DDISJ
#from	${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_BON
#		left join ${prefix_base_rubis}GESTCOM.AENTBOP1 ENTETE_BON
#			on		DETAIL_BON.NOBON=ENTETE_BON.NOBON
#				and	DETAIL_BON.NOCLI=ENTETE_BON.NOCLI
#		left join ${prefix_base_rubis}GESTCOM.AFOURNP1 FOURNISSEUR
#			on		DETAIL_BON.NOFOU=FOURNISSEUR.NOFOU
#		left join ${prefix_base_rubis}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
#			on		DETAIL_BON.CODAR = ARTICLE_FOURNISSEUR.NOART
#				and	DETAIL_BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
#		left join ${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
#			on		ENTETE_BON.NOCLI=CLIENT.NOCLI
#		left join ${prefix_base_rubis}GESTCOM.AGENCEP1 AGENCE
#			on		ENTETE_BON.AGENC=AGENCE.AGECO
#where	
#	ENTETE_BON.ETSEE = ''
#	and DETAIL_BON.ETSBE = ''
#	and DETAIL_BON.TRAIT='R' -- les reliquat
#	and DETAIL_BON.PROFI='1' -- qui ne sont pas des commentaires
#order by DETAIL_BON.NOBON asc,
#		 DETAIL_BON.NOCLI asc,
#		 DETAIL_BON.NOLIG asc
#EOT
#$loginor->Sql($sql); # regarde les bon du mois actif
#print "OK\n";
#
#
## construction du fichier SQL pour la base internet
#my $old_bon = ''; my $i=0;
#my $somme		= 0;
#my $nb_reliquat = 0 ;
#my $lignes_recu = 0;
#print print_time()."Insertion des reliquats dans la base SQLite ...";
#while($loginor->FetchRow()) {
#	my %row = $loginor->DataHash() ;
#	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
#	my  $designation	 = $row{'DS1DB'} ;
#		$designation	.= $row{'DS2DB'} ? "\\n$row{DS2DB}":'';
#		$designation	.= $row{'DS3DB'} ? "\\n$row{DS3DB}":'';
#		$designation	 = $row{'CONSA'} ? "$row{CONSA}"   :$designation;
#
#	if ($old_bon ne "$row{NOBON}.$row{NOCLI}") { #nouveau
#		$sqlite->do("UPDATE OR IGNORE reliquat SET nb_ligne=$nb_reliquat, montant=$somme, dispo=$lignes_recu WHERE id_bon='$old_bon'"); # met a jour le nb de reliquat
#		$nb_reliquat=0; $somme=0; $lignes_recu=0;
#		$sqlite->do("INSERT OR IGNORE INTO reliquat (id_bon,numero_bon,numero_artisan,date_bon,date_liv,nb_ligne,vendeur,montant,reference,agence) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_LIV}',0,'$row{LIVSB}',0,'$row{RFCSB}','$row{AGELI}')");
#		if ($sqlite->err()) { die "$DBI::errstr\n"; }
#	}
#
#	my $date_dispo = '';
#	if ($row{'QTREC'} == $row{'QTESA'} && $row{'TYCDD'} eq 'SPE') { # si quantié receptionnée == quantité commandée --> matos dispo
#		$date_dispo = "$row{DDISS}$row{DDISA}-$row{DDISM}-$row{DDISJ}";
#		$lignes_recu++;
#	}
#
#	if ($row{'TYCDD'} eq 'STO') { # matos en stock, donc forcement recu
#		$lignes_recu++;
#	}
#
#	#insertion des différente ligne du bon
#	$sqlite->do("INSERT OR IGNORE INTO reliquat_detail (id_bon,no_ligne,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,spe,date_dispo) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOLIG}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".($row{'TYCDD'} eq 'SPE' ? 1:0).",'$date_dispo')");
#	if ($sqlite->err()) { die "$DBI::errstr\n"; }
#
#	$old_bon = "$row{NOBON}.$row{NOCLI}";
#	$nb_reliquat++;
#	$somme += $row{'MONHT'};
#}
#print "OK\n";
#END_RELIQUAT: ;


$sqlite->commit;
if (!$skip{'delete'}) {
	print print_time()."Nettoyage de l'espace vide ...";
	$sqlite->do("VACUUM;"); # flush white space
	print "OK\n";
}
$sqlite->disconnect();
$loginor->Close();




goto END_COMPRESS if $skip{'compress'};
# on compress la base pour l'envoyé sur le serveur FTP
print print_time()."Compression de la base SQLite ... ";
system("bzip2 -zkf8 ".$options{'dbname'});
print "OK\n";
END_COMPRESS: ;




goto END_UPLOAD if $skip{'upload'};
# Début du transfert FTP
print print_time()."Transfert ... ";
#my	$ftp = Net::FTP->new($ini->val(qw/FTP host/), Debug => 1) or die "Cannot connect to ".$ini->val(qw/FTP host/)." : $@";
#	$ftp->login($ini->val(qw/FTP user/),$ini->val(qw/FTP pass/)) or die "Cannot login ", $ftp->message;
#	$ftp->binary;
#	$ftp->put($options{'dbname'}.'.bz2') or die "put failed ", $ftp->message;
#	$ftp->quit;
my $cmd = join(' ',	'pscp',
					'-scp',
					'-pw',
					$ini->val(qw/SSH pass/),
					$options{'dbname'}.'.bz2',
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/).':'.$options{'dbname'}.'.bz2'
			);
`$cmd`;
print "OK\n";

print print_time()."Decompression ... ";
my $cmd = join(' ',	'plink',
					'-pw',
					$ini->val(qw/SSH pass/),
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/),
					'"bzip2 -t '.$options{'dbname'}.'.bz2 && bzip2 -fd '.$options{'dbname'}.'.bz2"'
			);
`$cmd`;

#	$ftp = Net::FTP->new('ftp.coopmcs.com', Debug => 0) or die "Cannot connect to ftp.coopmcs.com : $@";
#	$ftp->login('coopmcs','') or die "Cannot login ", $ftp->message;
#	open(F,'>swicth_db.txt') && close F; # fichier qui permet de dire au script distant d'inverser les BD (supprimer l'ancienne et renomer la nouvelle)
#	$ftp->put('swicth_db.txt') or die "put failed ", $ftp->message;
#   $ftp->quit;
print "OK\n";
END_UPLOAD: ;


END: ;
print print_time()."END\n\n";


####################### METHODE USEFUL ############################""""

sub print_time {
	print strftime('[%Y-%m-%d %H:%M:%S] ', localtime);
	return '';
}

sub trim {
	my $t = shift;
	$t =~ s/^\s+//g;
	$t =~ s/\s+$//g;
	$t =~ s/\n/ /g;
	return $t ;
}

sub quotify {
	my $t = shift;
	$t =~ s/'/''/g;
	return $t ;
}


####################### CREATION DES TABLES SQL ############################""""

sub init_sqlite {
	my @rows ;
# creation des table BON DE COMMANDE #####################################################################################""
	$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "cde_rubis" (
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
	"id_bon" VARCHAR(31)  NOT NULL   UNIQUE  ,
	"numero_bon" VARCHAR(7) NOT NULL ,
	"numero_artisan" VARCHAR(15) NOT NULL ,
	"date_bon" DATE NOT NULL ,
	"date_maj" DATE NOT NULL ,
	"date_liv" DATE NOT NULL ,
	"vendeur" VARCHAR(3),
	"nb_ligne" INTEGER NOT NULL ,
	"montant" FLOAT NOT NULL ,
	"montant_dispo" FLOAT NOT NULL ,
	"reference" VARCHAR(20),
	"agence" VARCHAR(20),
	"nb_livre" INTEGER NOT NULL,
	"nb_prepa" INTEGER NOT NULL,
	"nb_dispo" INTEGER NOT NULL,
	 UNIQUE (numero_bon,numero_artisan) 
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS "date_bon_cde" ON "cde_rubis" ("date_bon" ASC)');

$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "cde_rubis_detail"	(
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
	"id_bon" VARCHAR(31) NOT NULL REFERENCES cde_rubis (id_bon) ON DELETE CASCADE,
	"no_ligne" VARCHAR(3) NOT NULL,
	"code_article" VARCHAR(15),
	"fournisseur" VARCHAR(40),
	"ref_fournisseur" VARCHAR(40),
	"designation" VARCHAR(124) NOT NULL ,
	"unit" VARCHAR(3),
	"qte" FLOAT NOT NULL ,
	"prix" FLOAT NOT NULL ,
	"etat" INTEGER DEFAULT 0,			-- spe=2^0, livre=2^1, prepa=2^2
	"date_dispo" DATE DEFAULT NULL
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS "id_bon_detail" ON "cde_rubis_detail" ("id_bon" ASC)');

# comme le CREATE TRIGGER IF NOT EXISTS ne marche pas cette version, on est obligé de tester à la main si le trigger existe ou pas.
@rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='cle_etrangere_id_bon' AND tbl_name='cde_rubis'") or die $sqlite->errstr;
if ($rows[0] == 0) { # si aucun trigger --> on le créé
	$sql = <<EOT ;
CREATE TRIGGER "cle_etrangere_id_bon"
	BEFORE DELETE ON cde_rubis
	BEGIN
		DELETE FROM cde_rubis_detail WHERE id_bon=old.id_bon;
	END
EOT
	$sqlite->do($sql);
}


# creation des tables DEVIS #####################################################################################""
$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "devis_rubis" (
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
	"id_bon" VARCHAR(31)  NOT NULL   UNIQUE  ,
	"numero_bon" VARCHAR(7) NOT NULL ,
	"numero_artisan" VARCHAR(15) NOT NULL ,
	"date_bon" DATE NOT NULL ,
	"date_maj" DATE NOT NULL ,
	"date_liv" DATE NOT NULL ,
	"vendeur" VARCHAR(3),
	"nb_ligne" INTEGER NOT NULL ,
	"montant" FLOAT NOT NULL ,
	"reference" VARCHAR(20),
	"agence" VARCHAR(20),
	 UNIQUE (numero_bon,numero_artisan) 
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS "date_devis_rubis" ON "devis_rubis" ("date_bon" ASC)');

$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "devis_rubis_detail"	(
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
	"id_bon" VARCHAR(31) NOT NULL REFERENCES devis_rubis (id_bon) ON DELETE CASCADE,
	"no_ligne" VARCHAR(3) NOT NULL,
	"code_article" VARCHAR(15),
	"fournisseur" VARCHAR(40),
	"ref_fournisseur" VARCHAR(40),
	"designation" VARCHAR(124) NOT NULL ,
	"unit" VARCHAR(3),
	"qte" FLOAT NOT NULL ,
	"prix" FLOAT NOT NULL ,
	"etat" INTEGER DEFAULT 0 -- spe=2^0
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS "id_devis_detail" ON "devis_rubis_detail" ("id_bon" ASC)');

# comme le CREATE TRIGGER IF NOT EXISTS ne marche pas cette version, on est obligé de tester à la main si le trigger existe ou pas.
@rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='cle_etrangere_devis' AND tbl_name='devis_rubis'") or die $sqlite->errstr;
if ($rows[0] == 0) { # si aucun trigger --> on le créé
	$sql = <<EOT ;
CREATE TRIGGER "cle_etrangere_devis"
	BEFORE DELETE ON devis_rubis
	BEGIN
		DELETE FROM devis_rubis_detail WHERE id_bon=old.id_bon;
	END
EOT
	$sqlite->do($sql);
}




# Creation des table RELIQUAT #####################################################################################""
#$sql = <<EOT ;
#CREATE TABLE IF NOT EXISTS "reliquat" (
#	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
#	"id_bon" VARCHAR(31)  NOT NULL   UNIQUE  ,
#	"numero_bon" VARCHAR(7) NOT NULL ,
#	"numero_artisan" VARCHAR(15) NOT NULL ,
#	"date_bon" DATE NOT NULL ,
#	"date_liv" DATE NOT NULL ,
#	"vendeur" VARCHAR(3),
#	"nb_ligne" INTEGER NOT NULL ,
#	"montant" FLOAT NOT NULL ,
#	"reference" VARCHAR(20),
#	"agence" VARCHAR(20),
#	"dispo" INTEGER DEFAULT 0,
#	 UNIQUE (numero_bon,numero_artisan) 
#)
#EOT
#$sqlite->do($sql);
#
##index
#$sqlite->do('CREATE INDEX IF NOT EXISTS "reliquat_date_bon_cde" ON "reliquat" ("date_bon" ASC)');
#
#$sql = <<EOT ;
#CREATE TABLE IF NOT EXISTS "reliquat_detail"	(
#	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
#	"id_bon" VARCHAR(31) NOT NULL REFERENCES cde_rubis (id_bon) ON DELETE CASCADE,
#	"no_ligne" VARCHAR(3) NOT NULL,
#	"code_article" VARCHAR(15),
#	"fournisseur" VARCHAR(40),
#	"ref_fournisseur" VARCHAR(40),
#	"designation" VARCHAR(124) NOT NULL ,
#	"unit" VARCHAR(3),
#	"qte" FLOAT NOT NULL ,
#	"prix" FLOAT NOT NULL ,
#	"spe" INTEGER DEFAULT 0,
#	"date_dispo" DATE DEFAULT NULL
#)
#EOT
#$sqlite->do($sql);
#
##index
#$sqlite->do('CREATE INDEX IF NOT EXISTS "reliquat_id_bon_detail" ON "reliquat_detail" ("id_bon" ASC)');
#
## comme le CREATE TRIGGER IF NOT EXISTS ne marche pas cette version, on est obligé de tester à la main si le trigger existe ou pas.
#@rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='cle_etrangere_reliquat_id_bon' AND tbl_name='reliquat'") or die $sqlite->errstr;
#if ($rows[0] == 0) { # si aucun trigger --> on le créé
#	$sql = <<EOT ;
#CREATE TRIGGER "cle_etrangere_reliquat_id_bon"
#	BEFORE DELETE ON reliquat
#	BEGIN
#		DELETE FROM reliquat_detail WHERE id_bon=old.id_bon;
#	END
#EOT
#	$sqlite->do($sql);
#}


# Creation de la table EMPLOYE #####################################################################################""
$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "employe" (
  "id" integer PRIMARY KEY AUTOINCREMENT NOT NULL ,
  "prenom" varchar(255) NOT NULL,
  "nom" varchar(255) NOT NULL,
  "email" varchar(255) default NULL,
  "loginor" varchar(6) default NULL,
  "code_vendeur" varchar(3) default NULL,
  "tel" varchar(255) default NULL,
  "ip" varchar(255) default NULL,
  "machine" varchar(255) default NULL,
  "printer" INTEGER NOT NULL default 0,
  "droit" INTEGER NOT NULL
)
EOT
$sqlite->do($sql);

$sqlite->commit; # valide les table et les trigger

} #fin init_sqlite







##################### AFFICHE L'AIDE DU PROGRAMME #######################
sub print_help {
	my $tmp = join(',',keys %skip);
	print <<EOT ;
Les options possibles sont :

Pour sauter une etape
	--skip=$tmp

Pour indexer toutes les donnees sans critere de date
	--all

Pour specifier un nom a la base SQLite (defaut : 'cde_rubis.db')
	--dbname=<nom du fichier SQLite>

Pour specifier une date de depart d'indexation (pour les bons et les devis)
	--from=yyyy-mm-dd

Pour specifier une date de fin d'indexation (pour les bons et les devis)
	--to=yyyy-mm-dd
EOT
	exit;
}

sub print_version {
	print <<EOT ;
$VERSION
EOT
	exit;
}