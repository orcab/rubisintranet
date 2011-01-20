#!/usr/bin/perl

use Data::Dumper;
use Win32::ODBC;
use Mysql ;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use constant {
	PLOMBIER	=> 1 << 0,
	ELECTRICIEN => 1 << 1
};

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');

init_kml();

my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

print print_time()."Select des artisans ...";
$loginor->Sql("select ETCLE,NOCLI,NOMCL,COMC1,AD1CL,AD2CL,RUECL,COFIN,CPCLF,BURCL,TELCL,TELCC,TLCCL,TLXCL,DICN1,DICN2 from ${prefix_base_rubis}GESTCOM.ACLIENP1 where CATCL='1' and NOMCL<>'ADHERENT'"); # regarde les artisans actif
print "OK\n";


my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query(join('',<DATA>)); # construction de la table si elle n'existe pas
$mysql->query("TRUNCATE TABLE artisan;");
print " ok\n";


print print_time()."MAJ des artisans dans la base\n";

while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;

	#if ($mysql->quote($row{'NOCLI'}) == '056077') { # patch pour email trop long
	#	$mysql->quote($row{'COMC1'}) = 'bretagne-plomberie-chauffage@aliceadsl.fr';
	#}

	my	$activite  = 0 ;
		$activite |= $row{'DICN1'} ? PLOMBIER    : 0 ;
		$activite |= $row{'DICN2'} ? ELECTRICIEN : 0 ;

	$mysql->query("INSERT INTO artisan (numero,nom,suspendu,email,tel1,tel2,tel3,tel4,adr1,adr2,adr3,cp,ville,activite,geo_coords) VALUES (".
					$mysql->quote($row{'NOCLI'}).",".
					$mysql->quote($row{'NOMCL'}).",".
					($row{'ETCLE'} eq 'S' ? 1 : 0).",".					# artisan suspendu
					lc($mysql->quote($row{'COMC1'})).",".
					$mysql->quote($row{'TELCL'}).",".
					$mysql->quote($row{'TELCC'}).",".
					$mysql->quote($row{'TLCCL'}).",".
					$mysql->quote($row{'TLXCL'}).",".
					$mysql->quote($row{'AD1CL'}).",".
					$mysql->quote($row{'AD2CL'}).",".
					$mysql->quote($row{'RUECL'}).",".
					$mysql->quote($row{'CPCLF'}).",".
					$mysql->quote($row{'BURCL'}).",".
					$mysql->quote($activite).",".
					$mysql->quote($row{'COFIN'}).
					")")
		or warn "Ne peux pas inserer le client ".$row{'NOMCL'};

	my ($lat,$lon)	= split(/,/,$row{'COFIN'});
	my $description = '';
	print KML <<EOT ;
<Placemark>
			<name>$row{NOMCL}</name>
			<styleUrl>#transBluePoly</styleUrl>
			<description> <![CDATA[
$description
]]>
</description>
			<Point>
				<coordinates>$lon,$lat,0</coordinates>
			</Point>
</Placemark>
EOT
}

$loginor->Close();
close_kml();


print print_time()."END\n\n";

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

sub init_kml {
	open(KML,'+>adherents_'.$cfg->{SOCIETE}.'.kml') or die "Ne peux pas creer le fichier .kml $!";
	print KML <<EOT ;
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2" xmlns:gx="http://www.google.com/kml/ext/2.2" xmlns:kml="http://www.opengis.net/kml/2.2" xmlns:atom="http://www.w3.org/2005/Atom">
	<Folder>
		<name>Adhérents MCS</name>
		<open>1</open>
		<Style id="transBluePoly">
			<LineStyle>
				<width>1.5</width>
			</LineStyle>
			<PolyStyle>
				<color>7dff0000</color>
			</PolyStyle>
		</Style>
EOT
}

sub close_kml {
	print KML <<EOT ;
</Folder>
</kml>
EOT
	close(KML);
}

__DATA__
CREATE TABLE IF NOT EXISTS `artisan` (
  `id` int(11) NOT NULL auto_increment,
  `numero` varchar(6) NOT NULL,
  `nom` varchar(255) NOT NULL,
  `suspendu` tinyint(1) NOT NULL default '0',
  `email` varchar(255) default NULL,
  `tel1` text,
  `tel2` text,
  `tel3` text,
  `tel4` text,
  `adr1` text,
  `adr2` text,
  `adr3` text,
  `cp` varchar(5) default NULL,
  `ville` text,
  `activite` tinyint(4) NOT NULL default '0',
  `geo_coords` varchar(22) default NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `nom` (`nom`),
  UNIQUE KEY `numero` (`numero`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1 ;