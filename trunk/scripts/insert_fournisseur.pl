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

print print_time()."Select des fournisseurs ...";
$loginor->Sql("select ETFOE,NOFOU,NOMFO,RUEFO,VILFO,BURFO,CPFOU,TELFO,TLCFO,TLXFO,REPFO,CONT1,CONT2 from ${prefix_base_rubis}GESTCOM.AFOURNP1"); # regarde les fournisseurs
print "OK\n";

my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

$mysql->query(join('',<DATA>)); # construction de la table fournisseur si elle n'existe pas

print print_time()."MAJ des fournisseurs dans la base\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;

	my $info_rubis1 = join("\n",	$row{'RUEFO'},
									$row{'VILFO'},
									$row{'CPFOU'}.' '.$row{'BURFO'},
									'',
									'Tel : '.$row{'TELFO'},
									'Fax : '.$row{'TLCFO'},
									'Autre : '.$row{'TLXFO'});
	my $info_rubis2 = join("\n",	$row{'REPFO'},
									$row{'CONT1'},
									$row{'CONT2'});

	# insertion dees nouveaux fournisseurs
	$mysql->query('INSERT IGNORE INTO fournisseur (code_rubis,nom,info_rubis1,info_rubis2,affiche) VALUES ('.
					$mysql->quote($row{'NOFOU'}).','.
					$mysql->quote($row{'NOMFO'}).','.
					$mysql->quote($info_rubis1).','.
					$mysql->quote($info_rubis2).','.
					$mysql->quote($row{'ETFOE'} eq 'S' ? 0:1).')')
		or warn "Ne peux pas inserer le fournisseur ".$row{'NOMFO'};

	# modification de ceux deja enregistré
	$mysql->query('UPDATE fournisseur SET info_rubis1='.$mysql->quote($info_rubis1).
										',info_rubis2='.$mysql->quote($info_rubis2).
										',affiche='.$mysql->quote($row{'ETFOE'} eq 'S' ? 0:1).
									' WHERE code_rubis='.$mysql->quote($row{'NOFOU'}))
				or warn "Ne peux pas modifier le fournisseur ".$row{'NOMFO'};
}

$loginor->Close();


print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}


__DATA__
CREATE TABLE IF NOT EXISTS `fournisseur` (
  `id` int(11) NOT NULL,
  `code_rubis` varchar(6) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `info_rubis1` text,
  `info_rubis2` text,
  `info3` text,
  `affiche` tinyint(1) NOT NULL default '1',
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code_rubis` (`code_rubis`),
  UNIQUE KEY `nom` (`nom`)
) ENGINE=MyISAM DEFAULT CHARSET=latin1;