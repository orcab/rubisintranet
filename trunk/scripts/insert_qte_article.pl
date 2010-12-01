#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Mysql ;
use strict ;
use POSIX qw(strftime);
use Net::FTP;						# pour l'upload de fichier
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Config::IniFiles;

print print_time()."START\n";

my $ini = new Config::IniFiles( -file => 'insert_cde_rubis_internet.ini' );
my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

open(SQL,'+>qte_article.sql') or die "ne peux pas creer le fichier SQL ($!)" ;
# SQL de creation de la table
print SQL <<EOT ;
CREATE TABLE IF NOT EXISTS `qte_article` (
  `code_article`	varchar(15) NOT NULL,
  `depot`			varchar(3) NOT NULL,
  `qte`				float(10,3) default '0.000',
  `mini`			float(10,3) default '0.000',
  `qte_cde`			float(10,3) default '0.000',
  PRIMARY KEY (`code_article`,`depot`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;
EOT

print print_time()."Select des quantites ...";
my $sql = <<EOT;
select
	STOCK.NOART as CODE_ARTICLE,
	STOCK.QCSTO as QTE_CDE_CLIENT_STOCK,
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
	and FICHE_STOCK.STSER='OUI'						-- est servi sur stock
	and STOCK.ETSOE=''								-- non supsendu
	and FICHE_STOCK.STSTS=''						-- non suspendu
EOT

$loginor->Sql($sql);
print "OK\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;
	$row{'QTE_DISPO'} = $row{'QTE_REEL'} - $row{'QTE_CDE_CLIENT_STOCK'} ;
	$row{'SERVI'} = $row{'SERVI'} eq 'OUI' ? 1:0;
	print SQL "REPLACE INTO qte_article (code_article,depot,qte,mini,qte_cde) VALUES ('$row{CODE_ARTICLE}','$row{DEPOT}','$row{QTE_DISPO}','$row{MINI}','$row{QTE_CDE_FOURN}');\n";
}

$loginor->Close();
close SQL;


# on compress la base pour l'envoyé sur le serveur FTP
print print_time()."Compression du fichier SQL ... ";
system("bzip2 -zkf8 qte_article.sql");
print "OK\n";


# Début du transfert FTP
#print print_time()."Transfert FTP ... ";
#my	$ftp = Net::FTP->new('', Debug => 0) or die "Cannot connect to  : $@";
#	$ftp->login('','') or die "Cannot login ", $ftp->message;
#	$ftp->binary;
#	$ftp->put('qte_article.sql.bz2') or die "put failed ", $ftp->message;
#	$ftp->quit;
#
#	$ftp = Net::FTP->new('', Debug => 0) or die "Cannot connect to : $@";
#	$ftp->login('','') or die "Cannot login ", $ftp->message;
#	open(F,'>switch_db_qte.txt') && close F; # fichier qui permet de dire au script distant d'inverser les BD (insérer la nouvelle)
#	$ftp->put('switch_db_qte.txt') or die "put failed ", $ftp->message;
#    $ftp->quit;
#print "OK\n";

print print_time()."Transfert ... ";
my $cmd = join(' ',	'pscp',
					'-scp',
					'-pw',
					$ini->val(qw/SSH pass/),
					'qte_article.sql.bz2',
					$ini->val(qw/SSH user/).'@'.$ini->val(qw/SSH host/).':qte_article.sql.bz2'
			);
`$cmd`;
print "OK\n";

print print_time()."Decompression ... ";
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

print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
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