#!/usr/bin/perl
my $VERSION = 0.1;

# generation des articles
use constant ;
use Win32::ODBC;
use Data::Dumper;
use strict ;
use POSIX qw(strftime);
use DBI qw(:sql_types);				# pour gérer SQLite
require 'Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Getopt::Long ;					# pour parser les parametre du script
$| = 1; # active le flush direct
use constant {
	'COEF2' => 1.33333,
	'COEF3' => 1,
	'COEF4' => 1.05260,
	'COEF5' => 1.12020,
	'SUR_TARIF'	=> 1 << 0
};

my %STOCK_CONSTANT = (	'AFA' => 1 << 0,
						'AFL' => 1 << 1
);

print print_time()."START\n";

my %options = ();
GetOptions (\%options,'dbname=s','help|?','version') or die ;
$options{'dbname'} = 'catalfou.sqlite' if !exists $options{'dbname'} ;

print_help()	if exists $options{'help'} ; # affiche le message avec les options dispo
print_version() if exists $options{'version'} ; # affiche le message avec les options dispo

my $cfg = new Phpconst2perlconst(-file => '../inc/config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE};


###############################################################
print print_time()."Suppression de l'ancienne base ...";
unlink($options{'dbname'}) if -e $options{'dbname'};
print " ok\n";


############################################################### CONNEXION A LOGINOR POUR RECUPERER LES INFOS
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";
my $sqlite = DBI->connect('dbi:SQLite:'.$options{'dbname'},'','',{ RaiseError => 0, AutoCommit => 0 }) or die("Pas de DB");
init_sqlite();

#goto ARTICLE;

FOURNISSEUR:
print print_time()."Select des fournisseurs actifs ...";
my $sql = <<EOT;
select	NOFOU,NOMFO
FROM	${prefix_base_rubis}GESTCOM.AFOURNP1
EOT
$loginor->Sql($sql); # regarde les fournisseurs
print " ok\n";


###############################################################
print print_time()."Insertion des fournisseurs ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	$sqlite->do("INSERT OR IGNORE INTO fournisseurs (code_fournisseur,nom_fournisseur) VALUES (".
		"'".$row{'NOFOU'}."',".
		"'".$row{'NOMFO'}."'".
	")");
}
print " ok\n";


FAMILLE:
print print_time()."Select des familles actives ...";
my $sql = <<EOT;
select		AFCNI,AFCAC,AFCFA,AFCSF,AFCCH,AFCSC,ACFLI
from		${prefix_base_rubis}GESTCOM.AFAMILP1
where		AFCTY='FA1'
order by	AFCAC asc, AFCFA asc, AFCSF asc, AFCCH asc, AFCSC asc
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";

print print_time()."Insertion du plan de vente ... ";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	if		($row{'AFCNI'} eq 'ACT') { # activité
		$sqlite->do("INSERT INTO pdvente (libelle,chemin,niveau) VALUES ('$row{ACFLI}','$row{AFCAC}',1);");
	}
	elsif	($row{'AFCNI'} eq 'FAM') { # famille 
		$sqlite->do("INSERT INTO pdvente (libelle,chemin,niveau,activite_pere) VALUES ('$row{ACFLI}','$row{AFCAC}.$row{AFCFA}', 2,'$row{AFCAC}');");
	}
	elsif	($row{'AFCNI'} eq 'SFA') { # sous famille 
		$sqlite->do("INSERT INTO pdvente (libelle,chemin,niveau,activite_pere,famille_pere) VALUES ('$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}', 3,'$row{AFCAC}','$row{AFCFA}');");
	}
	elsif	($row{'AFCNI'} eq 'CHA') { # chapitre 
		$sqlite->do("INSERT INTO pdvente (libelle,chemin,niveau,activite_pere,famille_pere,sousfamille_pere) VALUES ('$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}', 4,'$row{AFCAC}','$row{AFCFA}','$row{AFCSF}');");
	}
	elsif	($row{'AFCNI'} eq 'SCH') { # sous chapitre 
		$sqlite->do("INSERT INTO pdvente (libelle,chemin,niveau,activite_pere,famille_pere,sousfamille_pere,chapitre_pere) VALUES ('$row{ACFLI}','$row{AFCAC}.$row{AFCFA}.$row{AFCSF}.$row{AFCCH}.$row{AFCSC}',5,'$row{AFCAC}','$row{AFCFA}','$row{AFCSF}','$row{AFCCH}');");
	}
}
print "ok\n";


ARTICLE:
print print_time()."Select des articles crees ...";
my $sql = <<EOT;
select	A.NOART,											-- code article
		DESI1,DESI2,DESI3,									-- deisgnations
		GENCO,												-- gencode
		CONDI,SURCO,LUNTA,									-- conditionnement + unité d'achat
		ACTIV,FAMI1,SFAM1,ART04,ART05,						-- plan de vente
		NOFOU,REFFO,										-- fournisseur + reference
		T.PVEN1,T.PVEN2,T.PVEN3,T.PVEN4,T.PVEN5,T.PVEN6,	-- prix de vente
		PARVT, RMRV1, RMRV2, RMRV3,							-- prix de revient + remise
		DIAA1,												-- sur le catalogue 1|0
		DAPCS,DAPCA,DAPCM,DAPCJ,							-- date d'application du tarif de vente
		DARCS,DARCA,DARCM,DARCJ,							-- date de creation
		DARMS,DARMA,DARMM,DARMJ								-- date de MAJ
from	${prefix_base_rubis}GESTCOM.AARTICP1 A
			left outer join ${prefix_base_rubis}GESTCOM.AARFOUP1 A_F
				on A.NOART=A_F.NOART and A.FOUR1=A_F.NOFOU
			left join ${prefix_base_rubis}GESTCOM.ATARPVP1 T
				on A.NOART=T.NOART
			left join ${prefix_base_rubis}GESTCOM.ATARPAP1 PR
				on A.NOART=PR.NOART
where
		T.AGENC ='AFA'
	and T.PVT09	='E'	-- tarif de vente en cours
	and PR.AGENC='AFA'
	and PR.PRV03='E'	-- tarif de revient en cours
	and A.ARDIV='NON'
--	and A.NOART='15001323'
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";

print print_time()."Insertion des articles crees ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	#print Dumper(\%row);
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;
	
	my $reference_propre = $row{'REFFO'};
	$reference_propre =~ s/[^A-Z0-9]//ig;

	$sqlite->do("INSERT INTO articles (code_fournisseur,reference,code_mcs,designation1,designation2,designation3,gencode,conditionnement,sur_conditionnement,unite,activite,famille,sousfamille,chapitre,souschapitre,prix_achat_brut,remise1,remise2,remise3,prix1,prix2,prix3,prix4,prix5,prix6,divers,reference_propre,date_application,date_creation,date_maj) VALUES (".
		"'".$row{'NOFOU'}."',".
		"'".$row{'REFFO'}."',".
		"'".$row{'NOART'}."',".
		"'$row{DESI1}','$row{DESI2}','$row{DESI3}',".
		"'".$row{'GENCO'}."',".
		"'$row{CONDI}','$row{SURCO}',".
		"'".$row{'LUNTA'}."',".
		"'$row{ACTIV}','$row{FAMI1}','$row{SFAM1}','$row{ART04}','$row{ART05}',".
		"'".$row{'PARVT'}."',".
		"'$row{RMRV1}','$row{RMRV2}','$row{RMRV3}',".
		"'$row{PVEN1}','$row{PVEN2}','$row{PVEN3}','$row{PVEN4}','$row{PVEN5}','$row{PVEN6}',".
		"'".($row{'DIAA1'} eq 'OUI' ? SUR_TARIF : 0)."',".													# divers
		"'".$reference_propre."',".
		"'".($row{'DAPCS'} ? join('-',$row{'DAPCS'}.$row{'DAPCA'},$row{'DAPCM'},$row{'DAPCJ'}) : '')."',".	# date creation
		"'".($row{'DARCS'} ? join('-',$row{'DARCS'}.$row{'DARCA'},$row{'DARCM'},$row{'DARCJ'}) : '')."',".	# date creation
		"'".($row{'DARMS'} ? join('-',$row{'DARMS'}.$row{'DARMA'},$row{'DARMM'},$row{'DARMJ'}) : '')."'".	# date maj
	")");
	if ($sqlite->err()) { warn "$DBI::errstr\n"; }
}
print " ok\n";



STOCK:
# rajouter la notion de stock sur caudan et plescop
print print_time()."Select des stocks ...";
my $sql = <<EOT;
select	NOART,STSER,DEPOT
from	${prefix_base_rubis}GESTCOM.ASTOFIP1
--	where
--		NOART='15001323'
EOT
$loginor->Sql($sql); # regarde les stock par agence
print " ok\n";

my %stocks ;
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	if (exists $stocks{$row{'NOART'}}) { # agence deja trouvé
		$stocks{$row{'NOART'}} |= $STOCK_CONSTANT{$row{'DEPOT'}} if $row{'STSER'} eq 'OUI';
	} else {
		$stocks{$row{'NOART'}} = $STOCK_CONSTANT{$row{'DEPOT'}} if $row{'STSER'} eq 'OUI';
	}
}

print print_time()."Insertion des stocks ...";
while(my ($noart, $stock) = each(%stocks)) {
	$sqlite->do("UPDATE articles SET stock_agence='$stock' WHERE code_mcs='$noart'");
	if ($sqlite->err()) { warn "$DBI::errstr\n"; }
}
print " ok\n";




CATALOGUE:
print print_time()."Select des articles au catalogue ...";
my $sql = <<EOT;
select
	ACBDCS,ACBDCA,ACBDCM,ACBDCJ,		-- date creation
	ACBDMS,ACBDMA,ACBDMM,ACBDMJ,		-- date modif
	ACBEMM,								-- code fournisseur
	ACBRFF,								-- ref fournisseur
	ACBDE1,ACBDE2,ACBDE3,				-- designation
	ACBGEN,								-- gencode
	ACBCF1,								-- coef 1
	ACBCF6,								-- prix public
	ACBTPA,								-- prix d'achat brut
	ACBRE1,ACBRE2,ACBRE3,				-- remise
	ACBSPR,ACBAPR,ACBMPR,ACBJPR,		-- date d'application
	ACBC09								-- code MCS
FROM ${prefix_base_rubis}GESTCOM.ACBARTP1
where
		ACBPRO='CATALFOU'	-- du catalogue fournisseur
	and	ACBEMM<>''			-- pas de fournisseur vide
--	and ACBEMM='HANSGR' and ACBRFF='11042000'
EOT
$loginor->Sql($sql); # regarde les articles actifs
print " ok\n";


print print_time()."Insertion des articles ...";
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash() ;
	#print Dumper(\%row);
	map { $row{$_} = trim(quotify($row{$_})) ; } keys %row ;

	if (!$row{'ACBRE1'}) { $row{'ACBRE1'} = 0; }
	if (!$row{'ACBRE2'}) { $row{'ACBRE2'} = 0; }
	if (!$row{'ACBRE3'}) { $row{'ACBRE3'} = 0; }

	# calcul du prix d'achat net
	my ($pa_net,$prix6) = (0,0);
	$pa_net = $row{'ACBTPA'} ;
	if ($row{'ACBRE1'}) {
		$pa_net -= $pa_net * $row{'ACBRE1'}/100;
	}
	if ($row{'ACBRE2'}) {
		$pa_net -= $pa_net * $row{'ACBRE2'}/100;
	}
	if ($row{'ACBRE3'}) {
		$pa_net -= $pa_net * $row{'ACBRE3'}/100;
	}

	# prix public
	$prix6 = $row{'ACBCF6'} ? $row{'ACBCF6'} : $row{'ACBTPA'};
	
	my $reference_propre = $row{'ACBRFF'};
	$reference_propre =~ s/[^A-Z0-9]//ig;

	$sqlite->do("INSERT OR IGNORE INTO articles (code_fournisseur,reference,code_mcs,designation1,designation2,designation3,gencode,prix_achat_brut,remise1,remise2,remise3,prix1,prix2,prix3,prix4,prix5,prix6,reference_propre,date_application,date_creation,date_maj) VALUES (".
		"'".$row{'ACBEMM'}."',".
		"'".$row{'ACBRFF'}."',".
		"'".$row{'ACBC09'}."',".
		"'".$row{'ACBDE1'}."',".
		"'".$row{'ACBDE2'}."',".
		"'".$row{'ACBDE3'}."',".
		"'".$row{'ACBGEN'}."',".
		"'".$row{'ACBTPA'}."',".
		"'".$row{'ACBRE1'}."',".
		"'".$row{'ACBRE2'}."',".
		"'".$row{'ACBRE3'}."',".
		"'".($pa_net * $row{'ACBCF1'})."',".	# prix adh
		"'".($pa_net * COEF2)."',".
		"'".($pa_net * COEF3)."',".
		"'".($pa_net * COEF4)."',".
		"'".($pa_net * COEF5)."',".
		"'".$prix6."',".
		"'".$reference_propre."',".
		"'".($row{'ACBSPR'} ? join('-',$row{'ACBSPR'}.$row{'ACBAPR'},$row{'ACBMPR'},$row{'ACBJPR'}) : '')."',".	# date application
		"'".($row{'ACBDCS'} ? join('-',$row{'ACBDCS'}.$row{'ACBDCA'},$row{'ACBDCM'},$row{'ACBDCJ'}) : '')."',".	# date creation
		"'".($row{'ACBDMS'} ? join('-',$row{'ACBDMS'}.$row{'ACBDMA'},$row{'ACBDMM'},$row{'ACBDMJ'}) : '')."'".	# date maj
	")");

	# met a jour le prix d'un article expo s'il existe deja (les prix expo sont faux, il faut tenir compte du prix catalogue fournisseur)
	$sqlite->do("UPDATE articles set ".
		"prix_achat_brut='".$row{'ACBTPA'}."',".
		"remise1='".$row{'ACBRE1'}."',".
		"remise2='".$row{'ACBRE2'}."',".
		"remise3='".$row{'ACBRE3'}."',".
		"prix1='".($pa_net * $row{'ACBCF1'})."',".	# prix adh
		"prix2='".($pa_net * COEF2)."',".
		"prix3='".($pa_net * COEF3)."',".
		"prix4='".($pa_net * COEF4)."',".
		"prix5='".($pa_net * COEF5)."',".
		"prix6='".$prix6."' ".
		"WHERE code_fournisseur='".$row{'ACBEMM'}."' ".
		"AND reference='".$row{'ACBRFF'}."' ".
		"AND code_mcs LIKE '15%'"  # article expo
	);

	if ($sqlite->err()) { warn "$DBI::errstr\n"; }
}
print " ok\n";

$sqlite->commit;
$sqlite->disconnect();
$loginor->Close();

END:
print print_time()."END\n\n";





##################### AFFICHE L'AIDE DU PROGRAMME #######################
sub print_help {
	print <<EOT ;
aide a rediger
EOT
	exit;
}

sub print_version {
	print <<EOT ;
$VERSION
EOT
	exit;
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

sub print_time {
	print strftime "[%Y-%m-%d %H:%M:%S] ", localtime;
	return '';
}


sub init_sqlite {
# creation des tables articles + fournisseur + index + triggers #####################################################################################""
	$sql = <<EOT ;
CREATE TABLE [articles] (
  [code_fournisseur] CHAR(6) NOT NULL,
  [reference] CHAR(15) NOT NULL,
  [code_mcs] CHAR(15),
  [designation1] CHAR(40),
  [designation2] CHAR(40),
  [designation3] CHAR(40),
  [gencode] CHAR(13),
  [stock_agence] INTEGER(2) DEFAULT 0,	-- 2^0 = AFA, 2^1 = AFL
  [conditionnement] DECIMAL(10, 4) DEFAULT 0,
  [sur_conditionnement] DECIMAL(10, 4) DEFAULT 0,
  [unite] CHAR(3) DEFAULT NULL,
  [activite] CHAR(3) DEFAULT NULL,
  [famille] CHAR(3) DEFAULT NULL,
  [sousfamille] CHAR(3) DEFAULT NULL,
  [chapitre] CHAR(3) DEFAULT NULL,
  [souschapitre] CHAR(3) DEFAULT NULL,
  [prix_achat_brut] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [remise1] DECIMAL(2, 2) NOT NULL DEFAULT 0,
  [remise2] DECIMAL(2, 2) NOT NULL DEFAULT 0,
  [remise3] DECIMAL(2, 2) NOT NULL DEFAULT 0,
  [prix1] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [prix2] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [prix3] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [prix4] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [prix5] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [prix6] DECIMAL(10, 4) NOT NULL DEFAULT 0,
  [divers] INTEGER(1) NOT NULL DEFAULT 0,		-- catalogue ou pas
  [reference_propre] CHAR(15) NOT NULL,
  [date_application] DATE DEFAULT NULL,
  [date_creation] DATE NOT NULL,
  [date_maj] DATE DEFAULT NULL,
  CONSTRAINT [] PRIMARY KEY ([code_fournisseur], [reference])
);
EOT
$sqlite->do($sql);

#index sur article
$sqlite->do('CREATE INDEX [code_fournisseur]	ON [articles] ([code_fournisseur]);');
$sqlite->do('CREATE INDEX [code_mcs]			ON [articles] ([code_mcs]);');
$sqlite->do('CREATE INDEX [reference]			ON [articles] ([reference]);');
$sqlite->do('CREATE INDEX [reference_propre]	ON [articles] ([reference_propre]);');

$sql = <<EOT ;
CREATE TABLE [fournisseurs] (
  [code_fournisseur] CHAR(6) NOT NULL, 
  [nom_fournisseur] CHAR(35) NOT NULL, 
  CONSTRAINT [] PRIMARY KEY ([code_fournisseur])
);
EOT
$sqlite->do($sql);


$sql = <<EOT ;
CREATE TABLE [pdvente] (
  [libelle] CHAR(60) NOT NULL, 
  [activite_pere] CHAR(3), 
  [famille_pere] CHAR(3), 
  [sousfamille_pere] CHAR(3), 
  [chapitre_pere] CHAR(3), 
  [chemin] CHAR(19) NOT NULL, 
  [niveau] INT(3) NOT NULL,
  CONSTRAINT [] PRIMARY KEY ([chemin])
);
EOT
$sqlite->do($sql);

#index sur pdvente
$sqlite->do('CREATE UNIQUE INDEX [chemin_unique] ON [pdvente] ([chemin]);');

$sqlite->commit; # valide les table et les trigger
}