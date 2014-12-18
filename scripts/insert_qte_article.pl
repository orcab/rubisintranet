#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Config::IniFiles;
require 'Interfaces Rubis-Reflex/useful.pl';

print get_time()." START\n";

my $ini = new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

open(SQL,'+>qte_article.sql') or die "ne peux pas creer le fichier SQL ($!)" ;
# SQL de creation de la table
print SQL <<EOT ;
DROP TABLE IF EXISTS `qte_article`;

CREATE TABLE IF NOT EXISTS `qte_article` (
  `code_article`	varchar(15) NOT NULL,
  `depot`			varchar(3) NOT NULL,
  `qte`				float(10,3) default 0,
  `mini`			float(10,3) default 0,
  `qte_cde`			float(10,3) default 0,
  `servi`			bit default 0,
  PRIMARY KEY (`code_article`,`depot`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
EOT

print get_time()." Select des quantites ...";
my $sql = <<EOT;
select
	STOCK.NOART as CODE_ARTICLE,
	STOCK.QTATC as QTE_CDE_CLIENT_STOCK,
	STOCK.QTINV as QTE_REEL,
	STOCK.QFSTO as QTE_CDE_FOURN,
	FICHE_STOCK.STSER SERVI,
	FICHE_STOCK.STOMI as MINI,
	FICHE_STOCK.DEPOT
from
	${prefix_base_rubis}GESTCOM.ASTOCKP1 STOCK
	left join ${prefix_base_rubis}GESTCOM.ASTOFIP1 FICHE_STOCK
		on STOCK.NOART=FICHE_STOCK.NOART and STOCK.DEPOT=FICHE_STOCK.DEPOT
where
		(STOCK.DEPOT='AFA' or STOCK.DEPOT='AFL')
	and STOCK.QTINV>0 								-- a du stock
	and STOCK.ETSOE=''								-- non supsendu
	and FICHE_STOCK.STSTS=''						-- non suspendu
EOT

$loginor->Sql($sql);
print "OK\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;
	#$row{'QTE_DISPO'} = $row{'QTE_REEL'} - $row{'QTE_CDE_CLIENT_STOCK'} ;
	$row{'QTE_DISPO'} = $row{'QTE_REEL'};
	$row{'SERVI'} = $row{'SERVI'} eq 'OUI' ? 1:0;

	$row{'QTE_DISPO'} 		=~ s/\.000$//;
	$row{'MINI'} 			=~ s/\.000$//;
	$row{'QTE_CDE_FOURN'} 	=~ s/\.000$//;

	print SQL "REPLACE INTO qte_article (code_article,depot,qte,mini,qte_cde,servi) VALUES ('$row{CODE_ARTICLE}','$row{DEPOT}','$row{QTE_DISPO}','$row{MINI}','$row{QTE_CDE_FOURN}',$row{SERVI});\n";
}

############################################## ARTICLE SUSPENDUS ################################################""
print get_time()." Select des suspendus ...";
my $sql = <<EOT;
select
	ARTICLE.NOART as CODE_ARTICLE
from
	${prefix_base_rubis}GESTCOM.AARTICP1 ARTICLE
	left join ${prefix_base_rubis}GESTCOM.ASTOFIP1 FICHE_STOCK
		on FICHE_STOCK.NOART=ARTICLE.NOART
where
	ARTICLE.ETARE='S'
EOT

$loginor->Sql($sql);
print "OK\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;
	print SQL "UPDATE article SET suspendu='1' WHERE code_article='$row{CODE_ARTICLE}' and suspendu='0';\n";
}

############################################## ARTICLE EN ACHAT INTERDIT ################################################""
print get_time()." Select des achats interdits ...";
my $sql = <<EOT;
select
	ARTICLE.NOART as CODE_ARTICLE
from
	${prefix_base_rubis}GESTCOM.AARTICP1 ARTICLE
	left join ${prefix_base_rubis}GESTCOM.ASTOFIP1 FICHE_STOCK
		on FICHE_STOCK.NOART=ARTICLE.NOART and FICHE_STOCK.DEPOT='AFA'
	left join ${prefix_base_rubis}GESTCOM.ASTOCKP1 FICHE_QTE
		on FICHE_QTE.NOART=ARTICLE.NOART and FICHE_QTE.DEPOT='AFA'
where
		FICHE_STOCK.STO11='O'		-- achat interdit
	and FICHE_QTE.QTINV<=0 			-- pas de stock
EOT

$loginor->Sql($sql);
print "OK\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;
	print SQL "UPDATE article SET suspendu='1' WHERE code_article='$row{CODE_ARTICLE}' and suspendu='0';\n";
}


$loginor->Close();
close SQL;


# on compress la base pour l'envoyé sur le serveur FTP
print get_time()." Compression du fichier SQL ... ";
system("bzip2 -zkf8 qte_article.sql");
print "OK\n";


print get_time()." Transfert ... ";
my $cmd = join(' ',	'pscp',
					'-scp',
					'-pw',
					$ini->val(qw/SSH pass/),
					'qte_article.sql.bz2',
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/).':qte_article.sql.bz2'
			);
`$cmd`;
print "OK\n";

print get_time()." Decompression ... ";
my $cmd = join(' ',	'plink',
					'-pw',
					$ini->val(qw/SSH pass/),
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/),
					'./insert-qte-article.sh'
			);
`$cmd`;
print "OK\n";
END_FTP: ;

END: ;

print get_time()." END\n\n";