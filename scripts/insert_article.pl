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
		AFOG3 as GENCOD,SERST as SERVI_SUR_STOCK,CONDI as CONDITIONNEMENT,SURCO as SURCONDITIONNEMENT,CDCON as CONDI_DIVISIBLE,LUNTA as UNITE,
		ACTIV as ACTIVITE,FAMI1 as FAMILLE,SFAM1 as SOUSFAMILLE,ART04 as CHAPITRE,ART05 as SOUSCHAPITRE,
		NOMFO as FOURNISSEUR,REFFO as REF_FOURNISSEUR,AFOGE as REF_FOURNISSEUR_CONDENSEE,
		FOUR1 as CODE_FOURNISSEUR,
		ROUND(PV.PVEN1,3) as PRIX_VENTE_ADH,
		ROUND(PV.PVEN6,3) as PRIX_VENTE_PUBLIC,
		PARVT as PRIX_ACHAT_BRUT, RMRV1 as REMISE1, RMRV2 as REMISE2, RMRV3 as REMISE3,PRVT2 as PRIX_ACHAT_NET,
		DIAA1 as SUR_TARIF,
		TANU0 as ECOTAXE,									-- L'ecotaxe dans la table ATABLEP1
		DARCS as SIECLE_CREATION,DARCA as ANNEE_CREATION,DARCM as MOIS_CREATION,DARCJ as JOUR_CREATION
from	${prefix_base_rubis}GESTCOM.AARTICP1 A
			left outer join ${prefix_base_rubis}GESTCOM.AARFOUP1 A_F
				on A.NOART=A_F.NOART and A.FOUR1=A_F.NOFOU
			left join ${prefix_base_rubis}GESTCOM.AFOURNP1 F
				on A_F.NOFOU=F.NOFOU
			left join ${prefix_base_rubis}GESTCOM.ATARPVP1 PV
				on A.NOART=PV.NOART
			left join ${prefix_base_rubis}GESTCOM.ATARPAP1 PR
				on A.NOART=PR.NOART
			left join ${prefix_base_rubis}GESTCOM.ATABLEP1 TAXE
				on A.TPFAR=TAXE.CODPR and TAXE.TYPPR='TPF'
where	ETARE=''
	and PV.AGENC ='AFA'
	and PV.PVT09	='E' -- tarif de vente en cours
	and PR.AGENC='AFA'
	and PR.PRV03='E' -- tarif de revient en cours
	and A.ARDIV<>'OUI'
--	and A.NOART='05002245'
EOT

$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";

my $mysql = Mysql->connect($cfg->{MYSQL_HOST},$cfg->{MYSQL_BASE},$cfg->{MYSQL_USER},$cfg->{MYSQL_PASS}) or die "Peux pas se connecter a mysql";
$mysql->selectdb($cfg->{MYSQL_BASE}) or die "Peux pas selectionner la base mysql";

print print_time()."Suppression de la base ...";
$mysql->query("DROP TABLE IF EXISTS article;");
$mysql->query(join('',<DATA>)); # construction de la table si elle n'existe pas
print " ok\n";


print print_time()."Insertion des articles ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	my $servi_sur_stock = $row{'SERVI_SUR_STOCK'}	eq 'OUI' ? 1:0;
	my $sur_tarif		= $row{'SUR_TARIF'}			eq 'OUI' ? 1:0;

	#calcul du prix public
	$row{'TARIF_PUBLIC'} = $row{'PRIX_VENTE_PUBLIC'}; # on prend le champ réservé en premier lieu

	if ($row{'TARIF_PUBLIC'} < $row{'PRIX_ACHAT_BRUT'}) { # si le tarif public est inférieur au prix d'achat sans remise, on prend le plus haut
		$row{'TARIF_PUBLIC'} = $row{'PRIX_ACHAT_BRUT'};
	}

	if ($row{'TARIF_PUBLIC'} < $row{'PRIX_VENTE_ADH'}) {
		$row{'TARIF_PUBLIC'} = 0 ; # si le prix public est inférieur au prix adh --> prix négocier donc prix public NC
	}

	# date de creation de l'article
	$row{'DATE_CREATION'}		= "$row{SIECLE_CREATION}$row{ANNEE_CREATION}-$row{MOIS_CREATION}-$row{JOUR_CREATION}";

	# si conditionnement divisible, on efface la notion de conditonnement
	$row{'CONDITIONNEMENT'} = 1 if $row{'CONDI_DIVISIBLE'} eq 'OUI';

	my @chemin = ();
	push @chemin, $row{'ACTIVITE'}		if $row{'ACTIVITE'} ;
	push @chemin, $row{'FAMILLE'}		if $row{'FAMILLE'} ;
	push @chemin, $row{'SOUSFAMILLE'}	if $row{'SOUSFAMILLE'} ;
	push @chemin, $row{'CHAPITRE'}		if $row{'CHAPITRE'} ;
	push @chemin, $row{'SOUSCHAPITRE'}	if $row{'SOUSCHAPITRE'} ;
	my $chemin = join('.',@chemin);
	
	$mysql->query("INSERT IGNORE INTO article (code_article,designation,gencod,servi_sur_stock,conditionnement,surconditionnement,unite,activite,famille,sousfamille,chapitre,souschapitre,chemin,fournisseur,ref_fournisseur,ref_fournisseur_condensee,prix_achat_brut,prix_revient,prix_net,prix_public,remise1,remise2,remise3,sur_tarif,ecotaxe,date_creation,code_fournisseur) VALUES ('$row{CODE_ARTICLE}','".join("\n",($row{'DESIGNATION1'},$row{'DESIGNATION2'},$row{'DESIGNATION3'}))."','$row{GENCOD}',$servi_sur_stock,'$row{CONDITIONNEMENT}','$row{SURCONDITIONNEMENT}','$row{UNITE}','$row{ACTIVITE}','$row{FAMILLE}','$row{SOUSFAMILLE}','$row{CHAPITRE}','$row{SOUSCHAPITRE}','$chemin','$row{FOURNISSEUR}','$row{REF_FOURNISSEUR}','$row{REF_FOURNISSEUR_CONDENSEE}','$row{PRIX_ACHAT_BRUT}','$row{PRIX_ACHAT_NET}','$row{PRIX_VENTE_ADH}','$row{PRIX_PUBLIC}','$row{REMISE1}','$row{REMISE2}','$row{REMISE3}','$sur_tarif','$row{ECOTAXE}','$row{DATE_CREATION}','$row{CODE_FOURNISSEUR}');") or warn( Dumper(\%row) );
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
	`prix_achat_brut` decimal(10,2) default NULL,
	`prix_revient` decimal(10,2) default NULL,
	`prix_net` decimal(10,2) default NULL,
	`prix_public` decimal(10,2) default NULL,
	`remise1` int(11) default NULL,
	`remise2` int(11) default NULL,
	`remise3` int(11) default NULL,
	`sur_tarif` tinyint(1) NOT NULL,
	`ecotaxe` decimal(10,2) default NULL,
	`date_creation` date NOT NULL,
	`suspendu` tinyint(1) default '0',
	`code_fournisseur` varchar(6) default NULL,
	PRIMARY KEY  (`id`),
	UNIQUE KEY `code_article` (`code_article`),
	KEY `fourn` (`fournisseur`),
	KEY `suspendu` (`suspendu`)
) ENGINE=MyISAM  DEFAULT CHARSET=latin1;