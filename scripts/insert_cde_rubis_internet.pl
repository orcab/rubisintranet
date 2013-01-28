#!/usr/bin/perl
my $VERSION = 3.0;

use constant ;
use Data::Dumper;					# pour les test
use Win32::ODBC;					# pour se connecter à Loginor en ODBC
use Mysql;							# pour se connecter à Mysql
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

my %skip	= qw/init 0 bon 0 devis 0 vendeurs 0 chantiers 0 devis_expo 0 compress 0 upload 0 vaccum 0/;
my %options = ();
GetOptions (\%options, 'skip=s','all','help|?','version','dbname=s','from=s','to=s','bon=s','client=s') or die ;

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
if (exists $options{'bon'} && length($options{'bon'}) > 6) { #le format de num de bon n'est pas bon --> erreur
	print "Usage --bon=xxxxxx ('".$options{'bon'}."' est trop long)"; exit;
}
if (exists $options{'client'} && length($options{'client'}) > 6) { #le format de num de client n'est pas client --> erreur
	print "Usage --client=xxxxxx ('".$options{'client'}."' est trop long)"; exit;
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
my $mysql					= Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
   $mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

# creation de la base SQLite
goto END_INIT if $skip{'init'};
init_sqlite();
END_INIT: ;


#goto DEVIS_EXPO;

goto END_BON if $skip{'bon'};
print print_time()."Select des lignes de bon ...";
my $where = '';
if (!exists $options{'all'}) { # on n'indexe que le dernier mois
	if (exists $options{'from'} && exists $options{'to'}) {
		my $tmp = $options{'from'}; $tmp =~ s/-//g;
		$where .= " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) >= '$tmp' " ;
		$tmp = $options{'to'}; $tmp =~ s/-//g;
		$where .= " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) <= '$tmp' " ;
	} else {
		$where .= " and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) >= '$thirty_days_ago_rubis' " ;
	}
}

if (exists $options{'bon'}) {
	$where  .= " and ENTETE_BON.NOBON >= '$options{bon}' " ;
}
if (exists $options{'client'}) {
	$where  .= " and ENTETE_BON.NOCLI >= '$options{client}' " ;
}

my $sql = <<EOT ;
select	NOLIG,ARCOM,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,QTREC,UNICD,PRINE,MONHT,NOMFO,ARTICLE_FOURNISSEUR.REFFO,DET97,
		ENTETE_BON.NOBON,ENTETE_BON.NOCLI,
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
		CONCAT(DSEMS,CONCAT(DSEMA,CONCAT('-',CONCAT(DSEMM,CONCAT('-',DSEMJ))))) as DATE_MAJ,
		CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB))))) as DATE_LIV,
		LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,RFCSB,MONTBT,TELCL,TLCCL,
		NBLIG,
		AGENCE.AGELI,
		DSEMS,DSEMA,DSEMM,DSEMJ,			-- date de derniere MAJ du bon
		TYCDD,TRAIT,DET21 as PREPA,PROFI,	-- etat de la ligne du bon : special/livrée/preparée/commentaire
		DDISS,DDISA,DDISM,DDISJ,			-- date de disponibilités
		CHANTIER.CHAD1,						-- nom du chantier
		CONCAT(CDE_FOURNISSEUR.CFDLS,CONCAT(CDE_FOURNISSEUR.CFDLA,CONCAT('-',CONCAT(CDE_FOURNISSEUR.CFDLM,CONCAT('-',CDE_FOURNISSEUR.CFDLJ))))) as DATE_LIV_FOURNISSEUR,
		CDE_FOURNISSEUR.CFCOD as DATE_LIV_FOURNISSEUR_CONFIRM
from	${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_BON
		left join ${prefix_base_rubis}GESTCOM.AENTBOP1 ENTETE_BON
			on		DETAIL_BON.NOBON=ENTETE_BON.NOBON and DETAIL_BON.NOCLI=ENTETE_BON.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.NOFOU=FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		DETAIL_BON.CODAR = ARTICLE_FOURNISSEUR.NOART and DETAIL_BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
		left join ${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
			on		ENTETE_BON.NOCLI=CLIENT.NOCLI
		left join ${prefix_base_rubis}GESTCOM.AGENCEP1 AGENCE
			on		ENTETE_BON.AGENC=AGENCE.AGECO
		left join ${prefix_base_rubis}GESTCOM.AENTCHP1 CHANTIER
			on		ENTETE_BON.NOCHA=CHANTIER.CHCHA and ENTETE_BON.NOCLI=CHANTIER.CHCLI 
		left join ${prefix_base_rubis}GESTCOM.ACFDETP1 CDE_FOURNISSEUR
			on		ENTETE_BON.NOCLI=CDE_FOURNISSEUR.CFCLI and ENTETE_BON.NOBON=CDE_FOURNISSEUR.CFCLB and DETAIL_BON.NOLIG=CDE_FOURNISSEUR.CFCLL
where
		ENTETE_BON.ETSEE = ''
	and DETAIL_BON.ETSBE = ''
		$where
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
my $montant_livre = 0;
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
		# met a jour le nombre de lignes preparées et livrées
		$sqlite->do("UPDATE OR IGNORE cde_rubis SET nb_ligne='$nb_ligne', nb_livre='$nb_livre', nb_prepa='$nb_prepa', nb_dispo='$nb_dispo', montant_dispo='$montant_dispo', montant_livre='$montant_livre' WHERE id_bon='$old_bon'");
		$nb_ligne = 0;
		$nb_livre = 0;
		$nb_prepa = 0;
		$nb_dispo = 0;
		$montant_dispo = 0;
		$montant_livre = 0;

		# supprime l'ancien bon et le détail grace au trigger
		$sqlite->do("DELETE FROM cde_rubis WHERE numero_bon='$row{NOBON}' and numero_artisan='$row{NOCLI}'");
		die "$DBI::errstr\n" if $sqlite->err();

		# insert la nouvelle entete de commande
		$sqlite->do("INSERT OR IGNORE INTO cde_rubis (id_bon,numero_bon,numero_artisan,date_bon,date_maj,date_liv,vendeur,nb_ligne,montant,montant_dispo,montant_livre,reference,chantier,agence,nb_livre,nb_prepa,nb_dispo) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_MAJ}','$row{DATE_LIV}','$row{LIVSB}',0,$row{MONTBT},0,0,'$row{RFCSB}','$row{CHAD1}','$row{AGELI}','0','0','0')");
		die "$DBI::errstr\n" if $sqlite->err();
	}

	# calcul du nombre de dispo, montant dispo, nombre de prepa
	if ($row{'PROFI'} eq '1') { # article et non pas com'
		$nb_ligne++;
	
		if ($row{'TRAIT'} eq 'F') {					# ligne deja livré
			$nb_livre++ ;
			$montant_livre += $row{'MONHT'};

		} else {									# ligne pas encore livré
			$nb_prepa++ if ($row{'PREPA'} eq 'O');	# ligne préparée

			if ($row{'TYCDD'} eq 'STO') {			# matos en stock
				$nb_dispo++;						# donc forcement recu
				$montant_dispo += $row{'MONHT'};

			} elsif ($row{'TYCDD'} eq 'SPE') {	# matos special
				if ($row{'QTREC'} == $row{'QTESA'}) { # si quantié receptionnée == quantité commandée --> matos dispo
					$date_dispo = "$row{DDISS}$row{DDISA}-$row{DDISM}-$row{DDISJ}";
					$nb_dispo++;
					$montant_dispo += $row{'MONHT'};
				}
			}
		}
	}

	#insertion des différentes ligne du bon
	$sqlite->do("INSERT OR IGNORE INTO cde_rubis_detail (id_bon,no_ligne,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat,date_liv_four,date_liv_four_confirm,date_dispo) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOLIG}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".
		(		($row{'TYCDD'} eq 'SPE' ? ETAT_SPECIAL:0)
			|	($row{'TRAIT'} eq 'F'	? ETAT_LIVRE:0)
			|	($row{'PREPA'} eq 'O'	? ETAT_PREPARE:0)
			|	($row{'PROFI'} eq '9'	? ETAT_COMMENTAIRE:0)
		)
		.",'$row{DATE_LIV_FOURNISSEUR}','".($row{'DATE_LIV_FOURNISSEUR_CONFIRM'} eq 'OUI'?1:0)."','".($date_dispo eq '--' ? '':$date_dispo)."')");
	die "$DBI::errstr\n" if $sqlite->err();

	$old_bon = "$row{NOBON}.$row{NOCLI}";
} # fin while cde

# met a jour le nomber de ligne preparées et livrées
$sqlite->do("UPDATE OR IGNORE cde_rubis SET nb_ligne='$nb_ligne', nb_livre='$nb_livre', nb_prepa='$nb_prepa' WHERE id_bon='$old_bon'");
print "OK\n";
END_BON: ;




goto END_DEVIS if $skip{'devis'};
print print_time()."Select des devis ...";
my $where = '';
if (!exists $options{'all'}) { # on n'indexe que le dernier mois
	if (exists $options{'from'} && exists $options{'to'}) {
		my $tmp = $options{'from'}; $tmp =~ s/-//g;
		$where  = " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) >= '$tmp' " ;
		$tmp = $options{'to'}; $tmp =~ s/-//g;
		$where .= " and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) <= '$tmp' " ;
	} else {
		$where = " and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) >= '$thirty_days_ago_rubis' " ;
	}
}

if (exists $options{'bon'}) {
	$where  .= " and ENTETE_BON.NOBON >= '$options{bon}' " ;
}
if (exists $options{'client'}) {
	$where  .= " and ENTETE_BON.NOCLI >= '$options{client}' " ;
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
		$where
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
		$sqlite->do("DELETE FROM devis_rubis WHERE numero_bon='$row{NOBON}' and numero_artisan='$row{NOCLI}'");
		die "$DBI::errstr\n" if $sqlite->err();

		# insert le nouveau
		$sqlite->do("INSERT OR IGNORE INTO devis_rubis (id_bon,numero_bon,numero_artisan,date_bon,date_maj,date_liv,vendeur,nb_ligne,montant,reference,agence) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_MAJ}','$row{DATE_LIV}','$row{LIVSB}',$row{NBLIG},$row{MONTBT},'$row{RFCSB}','$row{AGELI}')");
		die "$DBI::errstr\n" if $sqlite->err();
	}

	#insertion des différente ligne du bon
	$sqlite->do("INSERT OR IGNORE INTO devis_rubis_detail (id_bon,no_ligne,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOLIG}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".
			($row{'TYCDD'} eq 'SPE' ? ETAT_SPECIAL:0)
		.")");
	die "$DBI::errstr\n" if $sqlite->err();

	$old_bon = "$row{NOBON}.$row{NOCLI}";
}
print "OK\n";
END_DEVIS: ;




goto END_VENDEURS if $skip{'vendeurs'};
print print_time()."Suppression des anciens vendeurs ...";
$sqlite->do("DELETE FROM vendeurs");	die "$DBI::errstr\n" if $sqlite->err();
print "OK\n";

print print_time()."Select des vendeurs ...";
my $sql = <<EOT ;
select CODPR,LIBPR,DIAP2,DIAP1 from ${prefix_base_rubis}GESTCOM.ATABLEP1 where TYPPR='LIV'
EOT
$loginor->Sql($sql);
print "OK\n";

print print_time()."Insertion des vendeurs dans la base SQLite ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	#insertion des vendeurs
	$sqlite->do("INSERT OR IGNORE INTO vendeurs (code,nom,groupe_principal,suspendu) VALUES ('$row{CODPR}','$row{LIBPR}','$row{DIAP2}','$row{DIAP1}')");	die "$DBI::errstr\n" if $sqlite->err();
}
print "OK\n";
END_VENDEURS: ;




goto END_CHANTIERS if $skip{'chantiers'};
print print_time()."Suppression des anciens chantiers ...";
$sqlite->do("DELETE FROM chantiers");	die "$DBI::errstr\n" if $sqlite->err();
print "OK\n";

print print_time()."Select des chantiers ...";
my $sql = <<EOT ;
select CHCLI,CHCHA,CHAD1 from ${prefix_base_rubis}GESTCOM.AENTCHP1 where CHEET=''
EOT
$loginor->Sql($sql);
print "OK\n";

print print_time()."Insertion des chantiers dans la base SQLite ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	#insertion des vendeurs
	$sqlite->do("INSERT OR IGNORE INTO chantiers (code_client,code_chantier,nom_chantier) VALUES ('$row{CHCLI}','$row{CHCHA}','$row{CHAD1}')");	die "$DBI::errstr\n" if $sqlite->err();
}
print "OK\n";
END_CHANTIERS: ;





DEVIS_EXPO: ;
goto END_DEVIS_EXPO if $skip{'devis_expo'};
# supprime les anciens devis expo
print print_time()."Suppression des anciens devis expo ...";
$sqlite->do("DELETE FROM devis_expo_detail");	die "$DBI::errstr\n" if $sqlite->err();
$sqlite->do("DELETE FROM devis_expo");			die "$DBI::errstr\n" if $sqlite->err();
print "OK\n";

print print_time()."Select des devis expo ...";
my $res = $mysql->query("SELECT * FROM devis WHERE supprime=0 and code_artisan<>'' and code_artisan is not null and code_artisan<>'EDITIO'");	# selection des devis expo actif
print "OK\n";

print print_time()."Insertion des devis expo dans la base SQLite ...";
while(my %row = $res->fetchhash) {
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	#insertion des devis expo
	$sql = <<EOT ;
INSERT INTO devis_expo (
	[id],[date],[date_maj],[representant],[code_artisan],[artisan],[nom_client],[adresse_client],[adresse_client2],
	[codepostal_client],[ville_client],[tel_client],[tel_client2],[email_client],[num_devis_rubis],[num_cmd_rubis],[mtht_cmd_rubis]
) VALUES (
	'$row{id}','$row{date}','$row{date_maj}','$row{representant}','$row{code_artisan}','$row{artisan}','$row{nom_client}','$row{adresse_client}','$row{adresse_client2}',
	'$row{codepostal_client}','$row{ville_client}','$row{tel_client}','$row{tel_client2}','$row{email_client}','$row{num_devis_rubis}','$row{num_cmd_rubis}','$row{mtht_cmd_rubis}'
)
EOT
	$sqlite->do($sql);
	die "$DBI::errstr\n" if $sqlite->err();
}

my $res = $mysql->query("SELECT * FROM devis_ligne"); # selection le détails des devis expo
print "OK\n";

while(my %row = $res->fetchhash) {
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	#insertion des lignes de devis expo
	$sql = <<EOT ;
INSERT INTO devis_expo_detail (
	[id],[id_devis],[code_article],[ref_fournisseur],[fournisseur],[designation],[qte],[puht],[pu_adh_ht],[stock],[expo],[option]
) VALUES (
	'$row{id}','$row{id_devis}','$row{code_article}','$row{ref_fournisseur}','$row{fournisseur}','$row{designation}','$row{qte}','$row{puht}','$row{pu_adh_ht}',
	'$row{stock}','$row{expo}','$row{option}'
)
EOT
	$sqlite->do($sql);	die "$DBI::errstr\n" if $sqlite->err();
}
print "OK\n";
END_DEVIS_EXPO: ;

$sqlite->commit;



goto END_VACCUM if $skip{'vaccum'};
print print_time()."Nettoyage de l'espace vide ...";
do_vacuum($sqlite);
print "OK\n";
END_VACCUM: ;


undef $mysql;			# close the mysql connection
$sqlite->disconnect();	# close the sqlite connection
$loginor->Close();		# close the loginor connection



goto END_COMPRESS if $skip{'compress'};
# on compress la base pour l'envoyé sur le serveur SSH
print print_time()."Compression de la base SQLite ... ";
system("bzip2 -zkf8 ".$options{'dbname'});
print "OK\n";
END_COMPRESS: ;



goto END_UPLOAD if $skip{'upload'};
# Début du transfert SSH
print print_time()."Transfert ... ";
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
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL,
	"id_bon" VARCHAR(31)  NOT NULL   UNIQUE,
	"numero_bon" VARCHAR(7) NOT NULL,
	"numero_artisan" VARCHAR(15) NOT NULL,
	"date_bon" DATE NOT NULL,
	"date_maj" DATE NOT NULL,
	"date_liv" DATE NOT NULL,
	"vendeur" VARCHAR(3),
	"nb_ligne" INTEGER NOT NULL,
	"montant" FLOAT NOT NULL,
	"montant_dispo" FLOAT NOT NULL,
	"montant_livre" FLOAT NOT NULL,
	"reference" VARCHAR(20),
	"chantier" VARCHAR(35),
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
	"date_liv_four" DATE DEFAULT NULL,
	"date_liv_four_confirm"  BOOL NOT NULL DEFAULT (0),
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


# Creation de la table VENDEURS #####################################################################################""
$sqlite->do('DROP TABLE IF EXISTS "vendeurs"');
$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "vendeurs" (
  "code" varchar(3) PRIMARY KEY NOT NULL ,
  "nom" varchar(255) NOT NULL,
  "groupe_principal" varchar(10) DEFAULT NULL,
  "suspendu" bool NOT NULL DEFAULT 0
)
EOT
$sqlite->do($sql);


# Creation de la table CHANTIERS #####################################################################################""
$sqlite->do('DROP TABLE IF EXISTS "chantiers"');
$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "chantiers" (
  [code_client] char(6) NOT NULL, 
  [code_chantier] char(6) NOT NULL, 
  [nom_chantier] varchar(255) DEFAULT NULL, 
  CONSTRAINT [] PRIMARY KEY ([code_client], [code_chantier])
)
EOT
$sqlite->do($sql);



# creation des table DEVIS EXPO #####################################################################################""
	$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS [devis_expo] (
  [id] INTEGER NOT NULL PRIMARY KEY, 
  [date] DATETIME NOT NULL, 
  [date_maj] DATETIME,
  [representant] VARCHAR NOT NULL,
  [code_artisan] VARCHAR(6),
  [artisan] VARCHAR, 
  [nom_client] VARCHAR, 
  [adresse_client] TEXT, 
  [adresse_client2] TEXT, 
  [codepostal_client] VARCHAR(10), 
  [ville_client] VARCHAR, 
  [tel_client] VARCHAR, 
  [tel_client2] TEXT, 
  [email_client] VARCHAR, 
  [num_devis_rubis] VARCHAR(10), 
  [num_cmd_rubis] TEXT, 
  [mtht_cmd_rubis] FLOAT(15, 2)	
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS [code_artisan] ON [devis_expo] ([code_artisan])');
$sqlite->do('CREATE INDEX IF NOT EXISTS [nom_client] ON [devis_expo] ([nom_client])');
$sqlite->do('CREATE INDEX IF NOT EXISTS [representant] ON [devis_expo] ([representant])');

$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS [devis_expo_detail]	(
	[id] INTEGER NOT NULL PRIMARY KEY, 
	[id_devis] INTEGER NOT NULL CONSTRAINT [cle_etrangere_devis_expo] REFERENCES [devis_expo]([id]) ON DELETE CASCADE,
	[code_article] VARCHAR(15), 
	[ref_fournisseur] VARCHAR, 
	[fournisseur] VARCHAR(35), 
	[designation] TEXT, 
	[qte] INTEGER, 
	[puht] FLOAT(10, 2), 
	[pu_adh_ht] FLOAT(10, 2), 
	[stock] BOOL, 
	[expo] BOOL, 
	[option] BOOL
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS [id_devis] ON [devis_expo_detail] ([id_devis])');


# comme le CREATE TRIGGER IF NOT EXISTS ne marche pas cette version, on est obligé de tester à la main si le trigger existe ou pas.
@rows = $sqlite->selectrow_array("SELECT count(*) FROM sqlite_master WHERE type='trigger' AND name='cle_etrangere_devis_expo' AND tbl_name='devis_expo'") or die $sqlite->errstr;
if ($rows[0] == 0) { # si aucun trigger --> on le créé
	$sql = <<EOT ;
CREATE TRIGGER "cle_etrangere_devis_expo"
	BEFORE DELETE ON devis_expo
	BEGIN
		DELETE FROM devis_expo_detail WHERE id_devis=old.id;
	END
EOT
	$sqlite->do($sql);
}

$sqlite->commit; # valide les table et les trigger
} #fin init_sqlite




sub do_vacuum {
    my ($dbh) = @_;
    local $dbh->{AutoCommit} = 1;
    $dbh->do('VACUUM');
    return;
}




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