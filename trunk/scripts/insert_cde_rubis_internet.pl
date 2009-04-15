#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Net::FTP;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$|=1;

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../../intranet/inc/config.php');
my $prefix_base_rubis	= $cfg->{LOGINOR_PREFIX_BASE};
my $loginor_agence		= $cfg->{LOGINOR_AGENCE};
my $mois_en_cours		= strftime('%Y-%m', localtime);

my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
use DBI qw(:sql_types);
my $sqlite = DBI->connect('dbi:SQLite:cde_rubis.db') or die("Pas de DB");

init_sqlite();

print print_time()."Suppression des lignes de bon de ce mois ...";
$sqlite->do("DELETE FROM cde_rubis WHERE date_bon >= '${mois_en_cours}-01'");
print "OK\n";

print print_time()."Nettoyage de l'espace vide ...";
$sqlite->do("VACUUM;"); # flush white space
print "OK\n";

print print_time()."Select des lignes de bon de ce mois ...";
my $sql = <<EOT ;
select	NOLIG,ARCOM,PROFI,TYCDD,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,PRINE,MONHT,NOMFO,REFFO,DET97,
		ENTETE_BON.NOBON,ENTETE_BON.NOCLI,
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
		CONCAT(DSECS,CONCAT(DSECA,CONCAT('-',CONCAT(DSECM,CONCAT('-',DSECJ))))) as DATE_LIV,
		LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,MONTBT,TELCL,TLCCL,
		NBLIG
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
where	
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) >= '${mois_en_cours}-01'
	and CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) <= '${mois_en_cours}-31'
	and ENTETE_BON.ETSEE = ''
	and DETAIL_BON.ETSBE = ''
	and DETAIL_BON.AGENC='$loginor_agence'
order by DETAIL_BON.NOBON asc, DETAIL_BON.NOCLI asc, DETAIL_BON.NOLIG asc
EOT
$loginor->Sql($sql); # regarde les bon du mois actif
print "OK\n";

# construction du fichier SQL pour la base internet
my $old_bon = ''; my $i=0;
print print_time()."Insertion dans la base SQLite ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_}=trim(quotify($row{$_})); } keys %row ; # nettoyage et prepa sql des valeur
	my $designation		= $row{'DS1DB'} ;
	$designation		.= $row{'DS2DB'} ? "\\n$row{DS2DB}":'';
	$designation		.= $row{'DS3DB'} ? "\\n$row{DS3DB}":'';
	$designation		= $row{'CONSA'} ? "$row{CONSA}":$designation;

	if ($old_bon ne "$row{NOBON}.$row{NOCLI}") { #nouveau
		$sqlite->do("INSERT INTO cde_rubis (id_bon,numero_bon,numero_artisan,date_bon,date_liv,vendeur,nb_ligne,montant,reference) VALUES ('$row{NOBON}.$row{NOCLI}','$row{NOBON}','$row{NOCLI}','$row{DATE_BON}','$row{DATE_LIV}','$row{LIVSB}',$row{NBLIG},$row{MONTBT},'$row{RFCSB}')");
		if ($sqlite->err()) { die "$DBI::errstr\n"; }
	}

	#insertion des différente ligne du bon
	$sqlite->do("INSERT INTO cde_rubis_detail (id_bon,code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,spe) VALUES ('$row{NOBON}.$row{NOCLI}','$row{CODAR}','$row{NOMFO}','$row{REFFO}','$designation','$row{UNICD}',$row{QTESA},$row{PRINE},".($row{'TYCDD'} eq 'SPE' ? 1:0).")");
	if ($sqlite->err()) { die "$DBI::errstr\n"; }

	$old_bon = "$row{NOBON}.$row{NOCLI}";
	#if (($i++ % 50) == 0) { print '.'; }
}

print "OK\n";

#close SQL;
$sqlite->disconnect();
$loginor->Close();

# Début du transfert FTP
print print_time()."Transfert FTP ... ";
my	$ftp = Net::FTP->new('ftp.coopmcs.com', Debug => 0) or die "Cannot connect to ftp.coopmcs.com : $@";
	$ftp->login('coopmcs','9trFHEZd') or die "Cannot login ", $ftp->message;
	$ftp->binary;
    $ftp->put('cde_rubis.db') or die "put failed ", $ftp->message;
    $ftp->quit;
print "OK\n";


print print_time()."END\n\n";




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



sub init_sqlite {
	$sql = <<EOT ;
CREATE TABLE IF NOT EXISTS "cde_rubis" (
	"id" INTEGER PRIMARY KEY  AUTOINCREMENT  NOT NULL ,
	"id_bon" VARCHAR(31)  NOT NULL   UNIQUE  ,
	"numero_bon" VARCHAR(7) NOT NULL ,
	"numero_artisan" VARCHAR(15) NOT NULL ,
	"date_bon" DATE NOT NULL ,
	"date_liv" DATE NOT NULL ,
	"vendeur" VARCHAR(3),
	"nb_ligne" INTEGER NOT NULL ,
	"montant" FLOAT NOT NULL ,
	"reference" VARCHAR(20),
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
	"code_article" VARCHAR(15),
	"fournisseur" VARCHAR(40),
	"ref_fournisseur" VARCHAR(40),
	"designation" VARCHAR(124) NOT NULL ,
	"unit" VARCHAR(3),
	"qte" FLOAT NOT NULL ,
	"prix" FLOAT NOT NULL ,
	"spe" INTEGER DEFAULT 0
)
EOT
$sqlite->do($sql);

#index
$sqlite->do('CREATE INDEX IF NOT EXISTS "id_bon_detail" ON "cde_rubis_detail" ("id_bon" ASC)');

$sql = <<EOT ;
CREATE TRIGGER IF NOT EXISTS "cle_etrangere_id_bon"
	BEFORE DELETE ON cde_rubis
	BEGIN
		DELETE FROM cde_rubis_detail WHERE id_bon=old.id_bon;
	END
EOT
$sqlite->do($sql);
}