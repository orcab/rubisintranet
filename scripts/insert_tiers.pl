#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Mysql ;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query(join('',<DATA>)); # construction de la table si elle n'existe pas
$mysql->query("TRUNCATE TABLE tiers;");
print " ok\n";

print print_time()."MAJ des Tiers dans la base\n";

print print_time()."Select des clients ...";
$loginor->Sql("select ETCLE,NOCLI,NOMCL,CATCL from ${prefix_base_rubis}GESTCOM.ACLIENP1 where NOMCL<>'ADHERENT' and NOMCL<>'MCS UTILISATION SOCIETE'"); # regarde les artisans actif
print "OK\n";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;

	$mysql->query("INSERT IGNORE INTO tiers (code,nom,`type`,suspendu) VALUES (".
						$mysql->quote($row{'NOCLI'}).",".
						$mysql->quote($row{'NOMCL'}).",".
						$mysql->quote($row{'CATCL'}).",".
						($row{'ETCLE'} eq 'S' ? 1 : 0).					# tiers suspendu
					")")
		or warn "Ne peux pas inserer le tiers '$row{NOMCL}' ($row{NOCLI})";
}

print print_time()."Select des fournisseurs ...";
$loginor->Sql("select ETFOE,NOFOU,NOMFO from ${prefix_base_rubis}GESTCOM.AFOURNP1"); # regarde les artisans actif
print "OK\n";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;

	$mysql->query("INSERT IGNORE INTO tiers (code,nom,`type`,suspendu) VALUES (".
						$mysql->quote($row{'NOFOU'}).",".
						$mysql->quote($row{'NOMFO'}).",".
						"6,".											# type 6 pour les fournisseurs
						($row{'ETFOE'} eq 'S' ? 1 : 0).					# tiers suspendu
					")")
		or warn "Ne peux pas inserer le tiers '$row{NOMFO}' ($row{NOFOU})";
}

$loginor->Close();
print print_time()."END\n\n";

########################################################################################################################################################
sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

__DATA__
CREATE TABLE IF NOT EXISTS `tiers` (
  `id` int(11) NOT NULL auto_increment,
  `code` varchar(6) NOT NULL,
  `nom` varchar(35) NOT NULL,
  `type` tinyint(4) NOT NULL COMMENT 'CATCL',
  `suspendu` tinyint(1) NOT NULL default '0',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code` (`code`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1 ;