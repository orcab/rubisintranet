#!/usr/bin/perl

# generation des articles
use Mysql;
use Win32::ODBC;
use Data::Dumper;
use strict ;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
$| = 1; # active le flush direct

print print_time()."START\n";

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};

#connexion a loginor pour recuperer les infos
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
print print_time()."Select des articles actifs ...";
my $sql = <<EOT;
select	A.NOART as CODE_ARTICLE,
		DESI1 as DESIGNATION1,DESI2 as DESIGNATION2,DESI3 as DESIGNATION3,
		GENCO as GENCOD,SERST as SERVI_SUR_STOCK,CONDI as CONDITIONNEMENT,SURCO as SURCONDITIONNEMENT,LUNTA as UNITE,
		ACTIV as ACTIVITE,FAMI1 as FAMILLE,SFAM1 as SOUSFAMILLE,ART04 as CHAPITRE,ART05 as SOUSCHAPITRE,
		NOMFO as FOURNISSEUR,REFFO as REF_FOURNISSEUR,AFOGE as REF_FOURNISSEUR_CONDENSEE,
		PVEN1 as PRIX_NET,
		DIAA1 as SUR_TARIF
from	${prefix_base_rubis}GESTCOM.AARTICP1 A
			left outer join ${prefix_base_rubis}GESTCOM.AARFOUP1 A_F
				on A.NOART=A_F.NOART and A.FOUR1=A_F.NOFOU
			left join ${prefix_base_rubis}GESTCOM.AFOURNP1 F
				on A_F.NOFOU=F.NOFOU
			left join ${prefix_base_rubis}GESTCOM.ATARIFP1 T
				on A.NOART=T.NOART
where	ETARE=''
	and T.AGENC ='AFA'
EOT

$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";

my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query(join('',<DATA>)); # construction de la table si elle n'existe pas
$mysql->query("TRUNCATE TABLE article;");
print " ok\n";


print print_time()."Insertion des articles ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	my $servi_sur_stock = $row{'SERVI_SUR_STOCK'}	eq 'OUI' ? 1:0;
	my $sur_tarif		= $row{'SUR_TARIF'}			eq 'OUI' ? 1:0;

	my @chemin = ();
	push @chemin, $row{'ACTIVITE'}		if $row{'ACTIVITE'} ;
	push @chemin, $row{'FAMILLE'}		if $row{'FAMILLE'} ;
	push @chemin, $row{'SOUSFAMILLE'}	if $row{'SOUSFAMILLE'} ;
	push @chemin, $row{'CHAPITRE'}		if $row{'CHAPITRE'} ;
	push @chemin, $row{'SOUSCHAPITRE'}	if $row{'SOUSCHAPITRE'} ;
	my $chemin = join('.',@chemin);
	
	$mysql->query("INSERT INTO article (code_article,designation,gencod,servi_sur_stock,conditionnement,surconditionnement,unite,activite,famille,sousfamille,chapitre,souschapitre,chemin,fournisseur,ref_fournisseur,ref_fournisseur_condensee,prix_brut,prix_net,sur_tarif) VALUES ('$row{CODE_ARTICLE}','".join("\n",($row{'DESIGNATION1'},$row{'DESIGNATION2'},$row{'DESIGNATION3'}))."','$row{GENCOD}',$servi_sur_stock,'$row{CONDITIONNEMENT}','$row{SURCONDITIONNEMENT}','$row{UNITE}','$row{ACTIVITE}','$row{FAMILLE}','$row{SOUSFAMILLE}','$row{CHAPITRE}','$row{SOUSCHAPITRE}','$chemin','$row{FOURNISSEUR}','$row{REF_FOURNISSEUR}','$row{REF_FOURNISSEUR_CONDENSEE}','$row{PRIX_NET}','$row{PRIX_NET}',$sur_tarif);") or warn( Dumper(\%row) );
}
close F ;
print " ok\n";


END:
print print_time()."END\n\n";


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

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}

__DATA__
CREATE TABLE IF NOT EXISTS `article` (
  `id` int(11) NOT NULL auto_increment,
  `code_article` varchar(15) NOT NULL,
  `designation` varchar(122) default NULL COMMENT 'trois fois 40 car + 2CR',
  `gencod` varchar(13) default NULL COMMENT 'code barre',
  `servi_sur_stock` tinyint(1) NOT NULL,
  `conditionnement` int(11) default NULL,
  `surconditionnement` int(11) default NULL,
  `unite` enum('BTE','CEN','COL','HEU','KG','L','MIL','ML','M2','M3','PCE','PLA','SAC','TON','UN') NOT NULL,
  `activite` varchar(3) default NULL,
  `famille` varchar(3) default NULL,
  `sousfamille` varchar(3) default NULL,
  `chapitre` varchar(3) default NULL,
  `souschapitre` varchar(3) default NULL,
  `chemin` varchar(19) NOT NULL,
  `fournisseur` varchar(35) default NULL,
  `ref_fournisseur` varchar(255) default NULL,
  `ref_fournisseur_condensee` varchar(255) default NULL,
  `prix_brut` decimal(10,2) default NULL,
  `prix_net` decimal(10,2) default NULL,
  `sur_tarif` tinyint(1) NOT NULL,
  PRIMARY KEY  (`id`),
  UNIQUE KEY `code_article` (`code_article`),
  KEY `fourn` (`fournisseur`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;