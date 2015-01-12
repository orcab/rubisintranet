#!/usr/bin/perl
use strict;

use Data::Dumper;
use Math::Round;
use Win32::ODBC;
use Win32API::File qw/getLogicalDrives/;
use File::Path;
use File::Copy;
use File::Basename;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;

# class d'article autorisé
my @valid_class = ('A'..'E');

# gestion des arguments
my (@articles,$debug,$all,$stock_only,$class,$test,$days,$help);
GetOptions('articles=s'=>\@articles, 'debug!'=>\$debug, 'all!'=>\$all, 'stock-only'=>\$stock_only,'class=s'=>\$class , 'test!'=>\$test, 'days=i'=>\$days, 'help|usage!'=>\$help) ;
die "Erreur : On ne peut pas utiliser les arguments --days et --all en meme temps" if ($all && $days);
die <<EOT if ($help);
Liste des arguments :
--articles=xxx[,xxx,...]
	N'importe que les articles xxx
--all
	Importe tous les articles
--stock-only
	N'importe que les produits servis sur stock
--class=A|B|C|D|E
	Restrein l'importation sur une famille de class de stock
--days=x
	Importe les articles modifies depuis x jour
--test
	Exporte et importe depuis l'environnement de test
--usage ou --help
	Affiche ce message
--debug
	Affiche les requetes SQL
EOT

use constant OUTPUT_FILENAME => 'output/interface-articles.txt';

use constant {
	# entete
	CODE_APPLICATION									=>	'HL',
	CODE_INTERFACE										=>	'03',
	CODE_INTERFACE_ASSOCIATION_FAMILLE					=>	'20',
	CODE_INTERFACE_IDENTIFIANT_VL						=>	'22',
	CODE_INTERFACE_CONDITIONNEMENT_ARTICLE_FOURNISSEUR	=>	'13',
	CODE_RUBRIQUE_ARTICLE								=>	'110',
	CODE_RUBRIQUE_ARTICLE_ACTIVATION					=>	'116',
	CODE_RUBRIQUE_ARTICLE_COMMENTAIRE					=>	'119',
	CODE_RUBRIQUE_ARTICLE_SUPPRESSION_COMMENTAIRE		=>	'819',
	CODE_RUBRIQUE_ARTICLE_VL							=>	'120',
	CODE_RUBRIQUE_SUPPRESSION_ARTICLE_VL				=>	'820',
	CODE_RUBRIQUE_SUPPRESSION_ARTICLE_VL				=>	'820',
	CODE_RUBRIQUE_FAMILLE_IC							=>	'117',
	CODE_RUBRIQUE_VALEUR_IC								=>	'118',
	CODE_RUBRIQUE_ASSOCIATION_FAMILLE					=>	'110',
	CODE_RUBRIQUE_IDENTIFIANT_VL						=>	'110',
	CODE_RUBRIQUE_CONDITIONNEMENT_ARTICLE_FOURNISSEUR	=>	'110',

	CODE_ACTIVITE										=>	'MCS',

	# article
	CODE_USAGE									=> '',
	MARQUAGE									=> '',
	TOP_POIDS_VARIABLE							=>	0,
	TOP_CONSIGNE								=>	0,
	TOP_ALCOOL									=>	0,
	TOP_DANGEREUX								=>	0,
	NOMBRE_JOURS_STABILISATION					=> '',
	NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT		=> '',
	FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE	=> 360,
	FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION => 360,
	CODE_FAMILLE_PEREMPTION						=> 'REC',
	CODE_REFERENCE_BASE							=> '',
	CODE_TYPE_VL								=> '',
	TOP_POIDS_DETAILLE_RECEPTION				=>	0,
	TOP_POIDS_DETAILLE_PREPARATION				=>	0,
	TOP_POSE_A_PLAT_PRE_COLLISAGE				=>	0,
	TOP_NOUVEAU									=>	0,

	# VL
	CODE_USAGE_VL								=>	'',
	REFERENCE_COMMANDE_VL						=>	'',
	TOP_CONTROLE_RECEPTION						=>	0,
	TOP_RECONDIONNEMENT_A_RECEPTION				=>	0,
	STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT	=>	1,
	TOP_ASSOCIATION_AUTOMATIQUE_SUPPORT			=>	0,
	CODE_FAMILLE_STOCKAGE_MASSE					=>	'',
	NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE		=>	'',
	HAUTEUR_COUCHE								=>	'',
	DATE_DEBUT_SERVICE_CONDIONNEMENT_SIECLE		=>	'',
	DATE_DEBUT_SERVICE_CONDIONNEMENT_ANNEE		=>	'',
	DATE_DEBUT_SERVICE_CONDIONNEMENT_MOIS		=>	'',
	DATE_DEBUT_SERVICE_CONDIONNEMENT_JOUR		=>	'',
	DATE_FIN_SERVICE_CONDIONNEMENT_SIECLE		=>	'',
	DATE_FIN_SERVICE_CONDIONNEMENT_ANNEE		=>	'',
	DATE_FIN_SERVICE_CONDIONNEMENT_MOIS			=>	'',
	DATE_FIN_SERVICE_CONDIONNEMENT_JOUR			=>	'',
	TOP_KIT										=>	'',

	# Identifiant VL
	CODE_VL_IDENTIFIANT_VL						=>	10,

	# Conditionnement article fournisseur
	CODE_VL_CONDITIONNEMENT_ARTICLE_FOURNISSEUR	=>	30,

	# association famille article
	CODE_VL_ASSOCIATION_FAMILLE					=>	30,
	TOP_DISSOCIATION_PREALBALE_EVENTUELLE		=>	1,
};

my %field_sizes = (qw/	CODE_ACTIVITE										3
						CODE												16
						LIBELLE												30
						LIBELLE_REDUIT										15
						MOT_DIRECTEUR										15
						CODE_USAGE											16
						MARQUAGE											16
						NOMBRE_JOURS_STABILISATION							3
						NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT				3
						FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE	5
						FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION	5
						CODE_FAMILLE_PEREMPTION								3
						CODE_REFERENCE_BASE									16
						CODE_TYPE_VL										3
						COMMENTAIRE											70
						FAMILLE_COMMENTAIRE									3

						CODE_VL												2
						MOT_DIRECTEUR_VL									15
						CODE_USAGE_VL										16
						CODE_TYPE_VL_VL										3
						CODE_VL_SOUS_CONDITIONNEMENT						2
						QUANTITE_VL_SOUS_CONDITIONNEMENT					7
						REFERENCE_COMMANDE_VL								16
						POIDS_NET											9
						POIDS_BRUT											9
						HAUTEUR												7
						LARGEUR												7
						PROFONDEUR											7
						VOLUME												7
						PRIX_STANDARD										11
						CODE_TYPE_SUPPORT									3
						CODE_TAILLE_EMPLACEMENT								3
						STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT			2
						CODE_FAMILLE_STOCKAGE								3
						CODE_FAMILLE_STOCKAGE_MASSE							6
						NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE				7
						HAUTEUR_COUCHE										7
						CODE_FAMILLE_PREPARATION							3
						DATE_SIECLE											2
						DATE_ANNEE											2
						DATE_MOIS											2
						DATE_JOUR											2
						CODE_FAMILLE_IC										10
						CODE_IC												10
						VALEUR_IC_ARTICLE									40
						CODE_TYPE_IDENTIFIANT_VL							6
						IDENTIFIANT_VL										35
						CODE_FOURNISSEUR									13
						CODE_FAMILLE_ARTICLE								15
/);

#print Dumper(\%field_sizes); exit;

my $old_time = 0;
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis 	= $cfg->{'LOGINOR_PREFIX_BASE_'.($test ? 'TEST':'PROD')};
my $loginor 			= new Win32::ODBC('DSN='.$cfg->{'LOGINOR_DSN'}.';UID='.$cfg->{'LOGINOR_USER'}.';PWD='.$cfg->{'LOGINOR_PASS'}.';') or die "Ne peux pas se connecter à rubis";
my $loginor2 			= new Win32::ODBC('DSN='.$cfg->{'LOGINOR_DSN'}.';UID='.$cfg->{'LOGINOR_USER'}.';PWD='.$cfg->{'LOGINOR_PASS'}.';') or die "Ne peux pas se connecter à rubis";
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";


# CREATION DE LA REQUETE SQL
my @articles_to_export;
foreach (@articles) { #pour chaque argument --articles
	foreach my $article (split / *, */) { #on coupe sur la virgule (cas 05658998,5646987354,876,43436)
		push @articles_to_export, "A.NOART='$article'";
	}
}

#regarde depuis combien de jours on export les articles
my 	$days_until_last_article_modif = $cfg->{'DAYS_UNTIL_LAST_ARTICLE_MODIF'};
	$days_until_last_article_modif = $days if ($days); # si l'argument --days est donnée, il est prioritaire

if ($all) {
	printf "%s Select de tous les articles\n",get_time();	$old_time=time;
} else {
	if ($#articles < 0) { # si pas d'argument, on import en fonction du delta de modification
		printf "%s Select des articles modifie depuis %d jour(s)\n",get_time(),$days_until_last_article_modif; 	$old_time=time;
	} else {
		printf "%s Select de(s) article(s) %s\n",get_time(),join(',',@articles); 								$old_time=time;
	}
}

my $sql = <<EOT ;
select *
	from		${prefix_base_rubis}GESTCOM.AARTICP1 A
	left join	${prefix_base_rubis}GESTCOM.AARFOUP1 AF
		on A.NOART=AF.NOART and AF.NOFOU=A.FOUR1
	left join	${prefix_base_rubis}GESTCOM.ATARPAP1 PR
				on A.NOART=PR.NOART and PR.PRV03='E'
	left join	${prefix_base_rubis}GESTCOM.ASTOFIP1 S
				on A.NOART=S.NOART and S.DEPOT='AFA'
where	1=1
EOT

my @where = ();

# on importe tout
if ($all) {

} else { # on importe que quelques articles ou des dates de modif
	if ($#articles < 0) { # si pas d'argument, on import en fonction du delta de modification
		push @where, " and (	(DATE(A.DARMS || A.DARMA || '-' || A.DARMM || '-' || A.DARMJ) + $days_until_last_article_modif DAYS) > CURRENT DATE
								or
								(DATE(S.STMSS || S.STMAA || '-' || S.STMMM || '-' || S.STMJJ) + $days_until_last_article_modif DAYS) > CURRENT DATE
							)";
	} else {
		push @where, " and (".join(' or ',@articles_to_export).") ";
	}
}

# on restrient au article  aune class de stock (a,b,c,d)
if ($class) {
	$class = uc($class);
	printf "%s Select de la Class '$class'\n",get_time();	$old_time=time;
	if (!in_array($class,\@valid_class)) {
		die "Erreur : --class non reconnue. Class valident (".join(',',@valid_class).")";
	}
	push @where, " and (S.STCLA='$class') ";
}

# on restrient au article servis sur stock
if ($stock_only) {
	push @where, " and (A.SERST='OUI') ";
}

$sql .= join(' and ',@where)." ORDER BY A.NOART ASC";

if ($debug) { print STDERR "\n$sql\n" ; exit ;}

# on envoi la requete
$loginor->Sql($sql);

printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);
printf "%s Generation du fichier\n",get_time(); $old_time=time;

mkpath(dirname(OUTPUT_FILENAME)) if !-d dirname(OUTPUT_FILENAME) ;
open(REFLEX,'+>'.OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".OUTPUT_FILENAME."' ($!)";

my %articles_deja_vu = ();
my $i=0;
# recupere les données de Rubis
while($loginor->FetchRow()) {
	my %data = ();
	my %row = $loginor->DataHash() ;

	# pour éviter le traitement des doublons
	next if exists $articles_deja_vu{$row{'NOART'}};  # on a deja traité l'article, on passe au suivant
	$articles_deja_vu{$row{'NOART'}} = 1 ;

	# recupere les commentaires article avec les dimmensions.
	 my %dimensions = ('L20'=>0, 'L30'=>0, 'H20'=>0, 'H30'=>0, 'P20'=>0, 'P30'=>0, 'PB20'=>0, 'PB30'=>0);
	 my $sql2 = "select CDLIB from ${prefix_base_rubis}GESTCOM.ADETCOP1 where CDFIC='AARTICP1' and CDCOD='$row{NOART}'";
	 $loginor2->Sql($sql2);
	 while($loginor2->FetchRow()) {
	 	my %row2 = $loginor2->DataHash() ;
	 	# regarde si des valeurs correspondent au dimension
	 	my @values = split( /\s*,\s*/ , $row2{'CDLIB'} );
	 	foreach (@values) {
	 		my @key_val = split(/\s*=\s*/);
	 		$dimensions{$key_val[0]} = $key_val[1];
	 	}
	 }

	#print Dumper(\%dimensions);
	#exit;

	# nettoyage de caracteres qui fait planter l'importation dans reflex
	$row{'DESI1'} =~ s///g;
	$row{'DESI2'} =~ s///g;
	$row{'DESI3'} =~ s///g;
	
	$data{'CODE_ACTIVITE'}								= fill_with_blank(CODE_ACTIVITE,$field_sizes{'CODE_ACTIVITE'});
	$data{'CODE'}										= fill_with_blank($row{'NOART'},$field_sizes{'CODE'});
	$data{'LIBELLE'}									= fill_with_blank($row{'DESI1'},$field_sizes{'LIBELLE'});
	$data{'LIBELLE_REDUIT'}								= fill_with_blank($row{'DESI1'},$field_sizes{'LIBELLE_REDUIT'});
	$data{'MOT_DIRECTEUR'}								= fill_with_blank("$row{NOFOU} $row{REFFO}",$field_sizes{'MOT_DIRECTEUR'});
	$data{'CODE_USAGE'}									= fill_with_blank(CODE_USAGE,$field_sizes{'CODE_USAGE'});
	$data{'MARQUAGE'}									= fill_with_blank(MARQUAGE,$field_sizes{'MARQUAGE'});
	$data{'TOP_POIDS_VARIABLE'}							= binary(TOP_POIDS_VARIABLE,$field_sizes{'TOP_POIDS_VARIABLE'});
	$data{'TOP_CONSIGNE'}								= binary(TOP_CONSIGNE);
	$data{'TOP_ALCOOL'}									= binary(TOP_ALCOOL);
	$data{'TOP_DANGEREUX'}								= binary(TOP_DANGEREUX);
	$data{'NOMBRE_JOURS_STABILISATION'}					= fill_with_blank(NOMBRE_JOURS_STABILISATION,$field_sizes{'NOMBRE_JOURS_STABILISATION'});
	$data{'NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT'}					= fill_with_blank(NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT,$field_sizes{'NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT'});
	$data{'FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE'}	= fill_with_zero(FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE,$field_sizes{'FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE'});
	$data{'FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION'}= fill_with_zero(FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION,$field_sizes{'FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION'});
	$data{'CODE_FAMILLE_PEREMPTION'}					= fill_with_blank(CODE_FAMILLE_PEREMPTION,$field_sizes{'CODE_FAMILLE_PEREMPTION'});
	$data{'CODE_REFERENCE_BASE'}						= fill_with_blank(CODE_REFERENCE_BASE,$field_sizes{'CODE_REFERENCE_BASE'});
	$data{'CODE_TYPE_VL'}								= fill_with_blank(CODE_TYPE_VL,$field_sizes{'CODE_TYPE_VL'});
	$data{'TOP_POIDS_DETAILLE_RECEPTION'}				= binary(TOP_POIDS_DETAILLE_RECEPTION);
	$data{'TOP_POIDS_DETAILLE_PREPARATION'}				= binary(TOP_POIDS_DETAILLE_PREPARATION);
	$data{'TOP_POSE_A_PLAT_PRE_COLLISAGE'}				= binary(TOP_POSE_A_PLAT_PRE_COLLISAGE);
	$data{'TOP_NOUVEAU'}								= binary(TOP_NOUVEAU);

	# commentaire
	$data{'COMMENTAIRE1'}								= fill_with_blank(($row{'DESI3'} ? $row{'DESI3'}.' / ':'')."$row{NOFOU} $row{REFFO}",$field_sizes{'COMMENTAIRE'});
	$data{'FAMILLE_COMMENTAIRE1'}						= fill_with_blank('BR',$field_sizes{'FAMILLE_COMMENTAIRE'});
	$data{'COMMENTAIRE2'}								= fill_with_blank($data{'MOT_DIRECTEUR'},$field_sizes{'COMMENTAIRE'});
	$data{'FAMILLE_COMMENTAIRE2'}						= fill_with_blank('REF',$field_sizes{'FAMILLE_COMMENTAIRE'});
	
	# activation
	$data{'TOP_DESACTIVATION'}							= binary($row{'ETARE'} eq 'S' ? 1:0);

	# VL
	$data{'MOT_DIRECTEUR_VL'}							= fill_with_blank($row{'DESI1'},$field_sizes{'MOT_DIRECTEUR_VL'});
	$data{'CODE_USAGE_VL'}								= fill_with_blank(CODE_USAGE_VL,$field_sizes{'CODE_USAGE_VL'});

	# si les conditionnement ne sont pas  renseigné, on met des truc pas défaut
	if(!trim($row{'CONDI'})) {
		$row{'CONDI'} = 1;
		$row{'ARTD4'} = $row{'TAUAR'};
		$row{'CDCON'} = 'OUI'
	}

	if(!trim($row{'SURCO'})) {
		$row{'SURCO'} = 999;
		$row{'ARTD5'} = 'PAL';
	}

	#unité de preparation
	my $unite_prepa_vl10 	= '';
	my $unite_prepa_vl20 	= '';
	my $qte_vl10_dans_vl20	= 0;
	if ($row{'CDCON'} eq 'OUI') { # si l'on peut vendre le produit en division, on prend l'unité de vente
		$unite_prepa_vl10 = $row{'TAUAR'};
		
		# on va également créer une VL20 pour information
		if (	$row{'CONDI'} > 1 							# si le conditionnement de vente est supérieur a 1
			&&	$row{'TAUAR'} ne $row{'ARTD4'}				# si l'unite de vente n'est pas la meme que l'unité de prépa
			&&  $row{'ARTD4'} ne ''							# les unité sont correctement rempli
			&&  $row{'ARTD4'} ne 'UN')	{					# si l'unité de VL n'est pas 'UN'
				$unite_prepa_vl20 	= substr($row{'ARTD4'},0,2) . '2';
				$qte_vl10_dans_vl20 = $row{'CONDI'};
		}

	} else { # si le produit est vendu par paquet (couronne, sachet, bte), on prend l'unite dans ARTD4
		$unite_prepa_vl10 = $row{'ARTD4'};
		if ($unite_prepa_vl10 eq '' && $row{'TAUAR'} eq 'ML') 	{	$unite_prepa_vl10 = 'COU'; } # courronne par défaut
		if ($unite_prepa_vl10 eq '') 							{	$unite_prepa_vl10 = 'BTE'; } # boite par défaut
	}
	
	#print Dumper($unite_prepa_vl10,\@row{qw/NOART TAUAR ARTD4 CDCON/}); exit;
	$data{'CODE_TYPE_VL_VL_10'}							= fill_with_blank($unite_prepa_vl10,$field_sizes{'CODE_TYPE_VL_VL'});
	$data{'CODE_TYPE_VL_VL_20'}							= fill_with_blank($unite_prepa_vl20,$field_sizes{'CODE_TYPE_VL_VL'});
	$data{'CODE_TYPE_VL_VL_30'}							= fill_with_blank($row{'ARTD5'}?$row{'ARTD5'}:'PAL',$field_sizes{'CODE_TYPE_VL_VL'});

	# l'unité de la VL30 doit appartenir à la liste suivante
	if (!in_array($data{'CODE_TYPE_VL_VL_30'},[qw/BAC CA3 CAD PAL TOR/])) {
		$data{'CODE_TYPE_VL_VL_30'} = 'PAL';
	}

	$data{'TOP_VL_BASE_10'}								= binary(1);
	$data{'TOP_VL_BASE_20'}								= binary(0);
	$data{'TOP_VL_BASE_30'}								= binary(0);
	$data{'TOP_VL_CONDIONNEMENT_10'}					= binary(0);
	$data{'TOP_VL_CONDIONNEMENT_20'}					= binary(0);
	$data{'TOP_VL_CONDIONNEMENT_30'}					= binary(1);
	$data{'CODE_VL_SOUS_CONDITIONNEMENT_10'}			= fill_with_blank('',$field_sizes{'CODE_VL_SOUS_CONDITIONNEMENT'});
	$data{'CODE_VL_SOUS_CONDITIONNEMENT_20'}			= fill_with_blank(10,$field_sizes{'CODE_VL_SOUS_CONDITIONNEMENT'});
	$data{'CODE_VL_SOUS_CONDITIONNEMENT_30'}			= fill_with_blank(10,$field_sizes{'CODE_VL_SOUS_CONDITIONNEMENT'});
	$data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_10'}		= fill_with_zero(1,$field_sizes{'QUANTITE_VL_SOUS_CONDITIONNEMENT'});
	$data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_20'}		= fill_with_zero($qte_vl10_dans_vl20,$field_sizes{'QUANTITE_VL_SOUS_CONDITIONNEMENT'});

	my $surco = 0;
	if (!$row{'SURCO'} || !$row{'CONDI'}) 	{	$surco = 999;	}
	else {
		if ($row{'CDCON'} eq 'OUI') {
			$surco = $row{'SURCO'} * $row{'CONDI'};
		} else {
			$surco = $row{'SURCO'};
		}
	}
	
	$data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_30'}		= fill_with_zero($surco,$field_sizes{'QUANTITE_VL_SOUS_CONDITIONNEMENT'});
	$data{'REFERENCE_COMMANDE_VL'}						= fill_with_blank(REFERENCE_COMMANDE_VL,$field_sizes{'REFERENCE_COMMANDE_VL'});
	
	$data{'POIDS_BRUT_10'}								= fill_with_zero($row{'POIDB'}?$row{'POIDB'} * 1000 : 0,$field_sizes{'POIDS_BRUT'});
	$data{'POIDS_NET_10'}								= fill_with_zero($data{'POIDS_BRUT_10'},$field_sizes{'POIDS_NET'});
	$data{'HAUTEUR_10'}									= fill_with_zero($row{'HAUTA'} ? $row{'HAUTA'} * 10 : 0,$field_sizes{'HAUTEUR'});
	$data{'LARGEUR_10'}									= fill_with_zero($row{'LARGA'} ? $row{'LARGA'} * 10 : 0,$field_sizes{'LARGEUR'});
	$data{'PROFONDEUR_10'}								= fill_with_zero($row{'LONGA'} ? $row{'LONGA'} * 10 : 0,$field_sizes{'PROFONDEUR'});
	my $volume = $data{'HAUTEUR_10'} * $data{'LARGEUR_10'} * $data{'PROFONDEUR_10'};
	$data{'VOLUME_10'}									= fill_with_zero($volume?$volume:0,$field_sizes{'VOLUME'});

	# le poids brut de la vl 20 est soit renseigné, soit un multiple du poids de la vl 10
	my $poids_brut_20 = ($dimensions{'PB20'} ? $dimensions{'PB20'} : $row{'POIDB'} * $data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_20'}) * 1000;
	$data{'POIDS_BRUT_20'}								= fill_with_zero($poids_brut_20,$field_sizes{'POIDS_BRUT'});
	$data{'POIDS_NET_20'}								= fill_with_zero($poids_brut_20,$field_sizes{'POIDS_NET'});
	$data{'HAUTEUR_20'}									= fill_with_zero($dimensions{'H20'} * 10,$field_sizes{'HAUTEUR'});
	$data{'LARGEUR_20'}									= fill_with_zero($dimensions{'L20'} * 10,$field_sizes{'LARGEUR'});
	$data{'PROFONDEUR_20'}								= fill_with_zero($dimensions{'P20'} * 10,$field_sizes{'PROFONDEUR'});
	$volume = $data{'HAUTEUR_20'} * $data{'LARGEUR_20'} * $data{'PROFONDEUR_20'};
	$data{'VOLUME_20'}									= fill_with_zero($volume,$field_sizes{'VOLUME'});

	# le poids brut de la vl 30 est soit renseigné, soit un multiple du poids de la vl 10
	my $poids_brut_30 = ($dimensions{'PB30'} ? $dimensions{'PB30'} : $row{'POIDB'} * $data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_30'}) * 1000;
	$data{'POIDS_BRUT_30'}								= fill_with_zero($poids_brut_30,$field_sizes{'POIDS_BRUT'});
	$data{'POIDS_NET_30'}								= fill_with_zero($poids_brut_30,$field_sizes{'POIDS_NET'});
	$data{'HAUTEUR_30'}									= fill_with_zero($dimensions{'H30'} * 10,$field_sizes{'HAUTEUR'});
	$data{'LARGEUR_30'}									= fill_with_zero($dimensions{'L30'} * 10,$field_sizes{'LARGEUR'});
	$data{'PROFONDEUR_30'}								= fill_with_zero($dimensions{'P30'} * 10,$field_sizes{'PROFONDEUR'});
	$volume = $data{'HAUTEUR_30'} * $data{'LARGEUR_30'} * $data{'PROFONDEUR_30'};
	$data{'VOLUME_30'}									= fill_with_zero($volume,$field_sizes{'VOLUME'});

	# calcul du prix
	my $prix_vl_10 = $row{'PRVT2'};
	if ($row{'CDCON'} eq 'NON') { # si conditionnement non divisible
		$prix_vl_10 = $row{'PRVT2'}*$row{'CONDI'};
	}
	$prix_vl_10 *= 1000;

	$data{'PRIX_STANDARD_10'}							= fill_with_zero(round($prix_vl_10)													,$field_sizes{'PRIX_STANDARD'});
	$data{'PRIX_STANDARD_20'}							= fill_with_zero(round($prix_vl_10 * $data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_20'})	,$field_sizes{'PRIX_STANDARD'});
	$data{'PRIX_STANDARD_30'}							= fill_with_zero(round($prix_vl_10 * $data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_30'})	,$field_sizes{'PRIX_STANDARD'});

	$data{'TOP_CONTROLE_RECEPTION'}						= binary(TOP_CONTROLE_RECEPTION);
	$data{'TOP_RECONDIONNEMENT_A_RECEPTION'}			= binary(TOP_RECONDIONNEMENT_A_RECEPTION);
	$data{'CODE_TYPE_SUPPORT'}							= fill_with_blank($row{'ART30'}?$row{'ART30'}:'P80'						,$field_sizes{'CODE_TYPE_SUPPORT'});
	$data{'CODE_TAILLE_EMPLACEMENT'}					= fill_with_blank($row{'ART31'}?$row{'ART31'}:'R31'						,$field_sizes{'CODE_TAILLE_EMPLACEMENT'});
	if ($data{'CODE_TAILLE_EMPLACEMENT'} =~ /^S/i) { # si le code taille emplacement commance par 'S', exemple : S04, S20...   on met emplacement 'SOL'
		$data{'CODE_TAILLE_EMPLACEMENT'} = 'SOL';
	}
	$data{'STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT'}	= fill_with_zero(STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT, $field_sizes{'STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT'});
	$data{'TOP_ASSOCIATION_AUTOMATIQUE_SUPPORT'}		= binary(TOP_ASSOCIATION_AUTOMATIQUE_SUPPORT);
	$data{'CODE_FAMILLE_STOCKAGE'}						= fill_with_blank($row{'ART33'}?$row{'ART33'}:'PAL',		$field_sizes{'CODE_FAMILLE_STOCKAGE'});
	$data{'CODE_FAMILLE_STOCKAGE_MASSE'}				= fill_with_blank(CODE_FAMILLE_STOCKAGE_MASSE,				$field_sizes{'CODE_FAMILLE_STOCKAGE_MASSE'});
	$data{'NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE'}		= fill_with_blank(NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE,		$field_sizes{'NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE'});
	$data{'HAUTEUR_COUCHE'}								= fill_with_blank(HAUTEUR_COUCHE,							$field_sizes{'HAUTEUR_COUCHE'});
	$data{'CODE_FAMILLE_PREPARATION'}					= fill_with_blank($row{'ART34'}?$row{'ART34'}:'DEP',		$field_sizes{'CODE_FAMILLE_PREPARATION'});
	$data{'DATE_DEBUT_SERVICE_CONDIONNEMENT_SIECLE'}	= fill_with_blank(DATE_DEBUT_SERVICE_CONDIONNEMENT_SIECLE,	$field_sizes{'DATE_SIECLE'});
	$data{'DATE_DEBUT_SERVICE_CONDIONNEMENT_ANNEE'}		= fill_with_blank(DATE_DEBUT_SERVICE_CONDIONNEMENT_ANNEE,	$field_sizes{'DATE_ANNEE'});
	$data{'DATE_DEBUT_SERVICE_CONDIONNEMENT_MOIS'}		= fill_with_blank(DATE_DEBUT_SERVICE_CONDIONNEMENT_MOIS,	$field_sizes{'DATE_MOIS'});
	$data{'DATE_DEBUT_SERVICE_CONDIONNEMENT_JOUR'}		= fill_with_blank(DATE_DEBUT_SERVICE_CONDIONNEMENT_JOUR,	$field_sizes{'DATE_JOUR'});
	$data{'DATE_FIN_SERVICE_CONDIONNEMENT_SIECLE'}		= fill_with_blank(DATE_FIN_SERVICE_CONDIONNEMENT_SIECLE,	$field_sizes{'DATE_SIECLE'});
	$data{'DATE_FIN_SERVICE_CONDIONNEMENT_ANNEE'}		= fill_with_blank(DATE_FIN_SERVICE_CONDIONNEMENT_ANNEE,		$field_sizes{'DATE_ANNEE'});
	$data{'DATE_FIN_SERVICE_CONDIONNEMENT_MOIS'}		= fill_with_blank(DATE_FIN_SERVICE_CONDIONNEMENT_MOIS,		$field_sizes{'DATE_MOIS'});
	$data{'DATE_FIN_SERVICE_CONDIONNEMENT_JOUR'}		= fill_with_blank(DATE_FIN_SERVICE_CONDIONNEMENT_JOUR,		$field_sizes{'DATE_JOUR'});
	$data{'TOP_VL_GESTION_VL_10'}						= binary(1);
	$data{'TOP_VL_GESTION_VL_20'}						= binary(0);
	$data{'TOP_VL_GESTION_VL_30'}						= binary(0);
	$data{'TOP_KIT'}									= binary(TOP_KIT);

	# IC
	$data{'CODE_FAMILLE_IC'}							= fill_with_blank('RUBIS',$field_sizes{'CODE_FAMILLE_IC'});
	$data{'CODE_IC_1'}									= fill_with_blank('LIBELLE_1',$field_sizes{'CODE_IC'});
	$data{'CODE_IC_2'}									= fill_with_blank('LIBELLE_2',$field_sizes{'CODE_IC'});
	$data{'IC_1'}										= fill_with_blank($row{'DESI1'},$field_sizes{'VALEUR_IC_ARTICLE'});
	$data{'IC_2'}										= fill_with_blank($row{'DESI2'},$field_sizes{'VALEUR_IC_ARTICLE'});

	# Identifiant VL
	$data{'CODE_VL_IDENTIFIANT_VL'}						= fill_with_blank(CODE_VL_IDENTIFIANT_VL,$field_sizes{'CODE_VL'});
	$data{'CODE_IDENTIFIANT_VL_CB_FOURNISSEUR'}			= fill_with_blank('FEAN13',$field_sizes{'CODE_TYPE_IDENTIFIANT_VL'});
	$data{'CODE_IDENTIFIANT_VL_CB_INTERNE'}				= fill_with_blank('IEAN13',$field_sizes{'CODE_TYPE_IDENTIFIANT_VL'});
	$data{'CODE_IDENTIFIANT_VL_REF_FOURNISSEUR'}		= fill_with_blank('REF',$field_sizes{'CODE_TYPE_IDENTIFIANT_VL'});
	$data{'CODE_IDENTIFIANT_VL_CODE_ARTICLE'}			= fill_with_blank('CODE',$field_sizes{'CODE_TYPE_IDENTIFIANT_VL'});
	$data{'IDENTIFIANT_VL_CB_FOURNISSEUR'}				= fill_with_blank($row{'AFOG3'},$field_sizes{'IDENTIFIANT_VL'});
	$data{'IDENTIFIANT_VL_CB_INTERNE'}					= fill_with_blank($row{'GENCO'},$field_sizes{'IDENTIFIANT_VL'});
	$data{'IDENTIFIANT_VL_REF_FOURNISSEUR'}				= fill_with_blank($row{'REFFO'},$field_sizes{'IDENTIFIANT_VL'});
	$data{'IDENTIFIANT_VL_CODE_ARTICLE'}				= fill_with_blank($row{'NOART'},$field_sizes{'IDENTIFIANT_VL'});

	# Conditionnement article fournisseur
	$data{'CODE_VL_CONDITIONNEMENT_ARTICLE_FOURNISSEUR'}= fill_with_blank(CODE_VL_CONDITIONNEMENT_ARTICLE_FOURNISSEUR,$field_sizes{'CODE_VL'});
	$data{'CODE_FOURNISSEUR'}							= fill_with_blank($row{'FOUR1'},$field_sizes{'CODE_FOURNISSEUR'});

######### correction des erreurs de saisie #########################################################################################
	$data{'CODE_FAMILLE_STOCKAGE'} = fill_with_blank('PAL',$field_sizes{'CODE_FAMILLE_STOCKAGE'}) if $data{'CODE_FAMILLE_STOCKAGE'} eq 'DEP' ;

####################################################################################################################################

######### association famille article #########################################################################################
	if (!in_array($row{'STCLA'},\@valid_class)) {
		$row{'STCLA'} = 'F';
	}
	$data{'CODE_FAMILLE_ARTICLE'} 						= fill_with_blank($row{'STCLA'},$field_sizes{'CODE_FAMILLE_ARTICLE'});
	
####################################################################################################################################

	my $num_sequence = fill_with_zero($i+1,7);

	#vérifie si l'article est activé dans Reflex
	if ($reflex->Sql("select ARTOPD from ${prefix_base_reflex}.HLARTIP where ARCART='$data{CODE}'")) { print "Erreur dans SQL ".$reflex->Error()."\n";}
	$reflex->FetchRow(); %row = $reflex->DataHash();
	my $desactiver_dans_reflex = $row{'ARTOPD'};

	if ($reflex->Sql("select count(*) as NB_VL20 from ${prefix_base_reflex}.HLARVLP where VLCART='$data{CODE}' and VLCVLA='20'")) { print "Erreur dans SQL ".$reflex->Error()."\n";}
	$reflex->FetchRow(); %row = $reflex->DataHash();
	my $vl20_dans_reflex = $row{'NB_VL20'};
	#print Dumper(\%row);exit;

	if ($desactiver_dans_reflex) {
		# Activation l'article pour enregistrer ses modifications
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_ACTIVATION)).
						join('',@data{qw/CODE_ACTIVITE CODE/})."0\n";
	}

	# acticle
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE)).
					join('',@data{qw/
						CODE_ACTIVITE							
						CODE								
						LIBELLE							
						LIBELLE_REDUIT						
						MOT_DIRECTEUR						
						CODE_USAGE
						MARQUAGE
						TOP_POIDS_VARIABLE
						TOP_CONSIGNE
						TOP_ALCOOL
						TOP_DANGEREUX
						NOMBRE_JOURS_STABILISATION
						NOMBRE_JOURS_MINI_DATA_ORDONNANCEMENT
						FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_STOCKAGE
						FOURCHETTE_BANALISATION_DATA_ORDONNANCEMENT_PREPARATION
						CODE_FAMILLE_PEREMPTION
						CODE_REFERENCE_BASE
						CODE_TYPE_VL
						TOP_POIDS_DETAILLE_RECEPTION
						TOP_POIDS_DETAILLE_PREPARATION
						TOP_POSE_A_PLAT_PRE_COLLISAGE
						TOP_NOUVEAU
		/})."\n";


	# Information complementaire (IC)
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FAMILLE_IC)).join('',@data{qw/CODE_ACTIVITE CODE CODE_FAMILLE_IC/})."\n";
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_VALEUR_IC)).join('',@data{qw/CODE_ACTIVITE CODE CODE_IC_1 IC_1/})."\n";
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_VALEUR_IC)).join('',@data{qw/CODE_ACTIVITE CODE CODE_IC_2 IC_2/})."\n";

	# supprime les commentaires precedents
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_SUPPRESSION_COMMENTAIRE)).join('',@data{qw/CODE_ACTIVITE CODE/})."\n";

	my $j=1;
	# commentaire 1
	if (trim($data{'COMMENTAIRE1'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_COMMENTAIRE)).
						join('',@data{qw/CODE_ACTIVITE CODE/}).
						fill_with_zero($j++,3).
						join('',@data{qw/FAMILLE_COMMENTAIRE1 COMMENTAIRE1/})."\n";
	}
	if (trim($data{'COMMENTAIRE2'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_COMMENTAIRE)).
						join('',@data{qw/CODE_ACTIVITE CODE/}).
						fill_with_zero($j++,3).
						join('',@data{qw/FAMILLE_COMMENTAIRE2 COMMENTAIRE2/})."\n";
	}

	# suppression des VL precedentes
	#print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_SUPPRESSION_ARTICLE_VL)).join('',@data{qw/CODE_ACTIVITE CODE/})."10\n";
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_SUPPRESSION_ARTICLE_VL)).join('',@data{qw/CODE_ACTIVITE CODE/})."20\n" if $vl20_dans_reflex;
	#print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_SUPPRESSION_ARTICLE_VL)).join('',@data{qw/CODE_ACTIVITE CODE/})."30\n";

	# VL 10
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE/}).
					fill_with_zero(10,$field_sizes{'CODE_VL'}).
					join('',@data{qw/	MOT_DIRECTEUR_VL CODE_USAGE_VL
										CODE_TYPE_VL_VL_10 TOP_VL_BASE_10 TOP_VL_CONDIONNEMENT_10 CODE_VL_SOUS_CONDITIONNEMENT_10 QUANTITE_VL_SOUS_CONDITIONNEMENT_10 REFERENCE_COMMANDE_VL
										POIDS_NET_10 POIDS_BRUT_10 HAUTEUR_10 LARGEUR_10 PROFONDEUR_10 VOLUME_10 PRIX_STANDARD_10/}).
					fill_with_blank('',	$field_sizes{'TOP_CONTROLE_RECEPTION'}+$field_sizes{'TOP_RECONDIONNEMENT_A_RECEPTION'}+
										$field_sizes{'CODE_TYPE_SUPPORT'}+$field_sizes{'CODE_TAILLE_EMPLACEMENT'}+$field_sizes{'STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT'}+
										1+$field_sizes{'CODE_FAMILLE_STOCKAGE'}+$field_sizes{'CODE_FAMILLE_STOCKAGE_MASSE'}+$field_sizes{'NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE'}+
										$field_sizes{'HAUTEUR_COUCHE'}+$field_sizes{'CODE_FAMILLE_PREPARATION'}+$field_sizes{'DATE_SIECLE'}+
										$field_sizes{'DATE_ANNEE'}+$field_sizes{'DATE_MOIS'}+$field_sizes{'DATE_JOUR'}+
										$field_sizes{'DATE_SIECLE'}+$field_sizes{'DATE_ANNEE'}+$field_sizes{'DATE_MOIS'}+
										$field_sizes{'DATE_JOUR'}+2   # aucune idée du +2 mais on est obligé de décaler les valeurs pour que l'interface comprenne
									).
					join('',@data{qw/	TOP_VL_GESTION_VL_10 TOP_KIT/})."\n";
	
	# VL 20
	#print "DEBUG '".$data{'CODE'}."' '".$data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_20'}."'\n";
	if ($data{'CODE_TYPE_VL_VL_20'} && $data{'QUANTITE_VL_SOUS_CONDITIONNEMENT_20'} > 1) { # si une VL 20 est précisée, on la renseigne
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE/}).
					fill_with_zero(20,$field_sizes{'CODE_VL'}).
					join('',@data{qw/	MOT_DIRECTEUR_VL CODE_USAGE_VL
										CODE_TYPE_VL_VL_20 TOP_VL_BASE_20 TOP_VL_CONDIONNEMENT_20 CODE_VL_SOUS_CONDITIONNEMENT_20 QUANTITE_VL_SOUS_CONDITIONNEMENT_20 REFERENCE_COMMANDE_VL
										POIDS_NET_20 POIDS_BRUT_20 HAUTEUR_20 LARGEUR_20 PROFONDEUR_20 VOLUME_20 PRIX_STANDARD_20
									/}).															      
					join('',@data{qw/	TOP_CONTROLE_RECEPTION TOP_RECONDIONNEMENT_A_RECEPTION CODE_TYPE_SUPPORT CODE_TAILLE_EMPLACEMENT STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT
										TOP_ASSOCIATION_AUTOMATIQUE_SUPPORT CODE_FAMILLE_STOCKAGE CODE_FAMILLE_STOCKAGE_MASSE NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE
										HAUTEUR_COUCHE CODE_FAMILLE_PREPARATION
										DATE_DEBUT_SERVICE_CONDIONNEMENT_SIECLE DATE_DEBUT_SERVICE_CONDIONNEMENT_ANNEE DATE_DEBUT_SERVICE_CONDIONNEMENT_MOIS DATE_DEBUT_SERVICE_CONDIONNEMENT_JOUR
										DATE_FIN_SERVICE_CONDIONNEMENT_SIECLE DATE_FIN_SERVICE_CONDIONNEMENT_ANNEE DATE_FIN_SERVICE_CONDIONNEMENT_MOIS DATE_FIN_SERVICE_CONDIONNEMENT_JOUR
									/}).
					join('',@data{qw/	TOP_VL_GESTION_VL_20 TOP_KIT/})."\n";
	} # fin if VL 20

	# VL 30
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE/}).
					fill_with_zero(30,$field_sizes{'CODE_VL'}).
					join('',@data{qw/	MOT_DIRECTEUR_VL CODE_USAGE_VL
										CODE_TYPE_VL_VL_30 TOP_VL_BASE_30 TOP_VL_CONDIONNEMENT_30 CODE_VL_SOUS_CONDITIONNEMENT_30 QUANTITE_VL_SOUS_CONDITIONNEMENT_30 REFERENCE_COMMANDE_VL
										POIDS_NET_30 POIDS_BRUT_30 HAUTEUR_30 LARGEUR_30 PROFONDEUR_30 VOLUME_30 PRIX_STANDARD_30
									/}).
					join('',@data{qw/	TOP_CONTROLE_RECEPTION TOP_RECONDIONNEMENT_A_RECEPTION CODE_TYPE_SUPPORT CODE_TAILLE_EMPLACEMENT STOCKAGE_STANDARD_NOMBRE_CONDIOTIONNEMENT
										TOP_ASSOCIATION_AUTOMATIQUE_SUPPORT CODE_FAMILLE_STOCKAGE CODE_FAMILLE_STOCKAGE_MASSE NB_VL_SOUS_CONDIONNEMENT_POUR_COUCHE
										HAUTEUR_COUCHE CODE_FAMILLE_PREPARATION
										DATE_DEBUT_SERVICE_CONDIONNEMENT_SIECLE DATE_DEBUT_SERVICE_CONDIONNEMENT_ANNEE DATE_DEBUT_SERVICE_CONDIONNEMENT_MOIS DATE_DEBUT_SERVICE_CONDIONNEMENT_JOUR
										DATE_FIN_SERVICE_CONDIONNEMENT_SIECLE DATE_FIN_SERVICE_CONDIONNEMENT_ANNEE DATE_FIN_SERVICE_CONDIONNEMENT_MOIS DATE_FIN_SERVICE_CONDIONNEMENT_JOUR
									/}).
					join('',@data{qw/	TOP_VL_GESTION_VL_30 TOP_KIT/})."\n";


	#Identifiant VL
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_IDENTIFIANT_VL,CODE_RUBRIQUE_IDENTIFIANT_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE CODE_VL_IDENTIFIANT_VL CODE_IDENTIFIANT_VL_CB_FOURNISSEUR IDENTIFIANT_VL_CB_FOURNISSEUR/})."\n" if trim($data{IDENTIFIANT_VL_CB_FOURNISSEUR});
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_IDENTIFIANT_VL,CODE_RUBRIQUE_IDENTIFIANT_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE CODE_VL_IDENTIFIANT_VL CODE_IDENTIFIANT_VL_CB_INTERNE IDENTIFIANT_VL_CB_INTERNE/})."\n" if trim($data{IDENTIFIANT_VL_CB_INTERNE});
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_IDENTIFIANT_VL,CODE_RUBRIQUE_IDENTIFIANT_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE CODE_VL_IDENTIFIANT_VL CODE_IDENTIFIANT_VL_REF_FOURNISSEUR IDENTIFIANT_VL_REF_FOURNISSEUR/})."\n" if trim($data{IDENTIFIANT_VL_REF_FOURNISSEUR});
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_IDENTIFIANT_VL,CODE_RUBRIQUE_IDENTIFIANT_VL)).
					join('',@data{qw/CODE_ACTIVITE CODE CODE_VL_IDENTIFIANT_VL CODE_IDENTIFIANT_VL_CODE_ARTICLE IDENTIFIANT_VL_CODE_ARTICLE/})."\n";

	
	# Conditionnement article fournisseur
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_CONDITIONNEMENT_ARTICLE_FOURNISSEUR,CODE_RUBRIQUE_CONDITIONNEMENT_ARTICLE_FOURNISSEUR)).
					join('',@data{qw/CODE_ACTIVITE CODE CODE_VL_CONDITIONNEMENT_ARTICLE_FOURNISSEUR CODE_FOURNISSEUR/})."\n";


	# association famille article
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_ASSOCIATION_FAMILLE,CODE_RUBRIQUE_ASSOCIATION_FAMILLE)).
					join('',@data{qw/CODE_ACTIVITE CODE/}).
					CODE_VL_ASSOCIATION_FAMILLE.$data{'CODE_FAMILLE_ARTICLE'}.TOP_DISSOCIATION_PREALBALE_EVENTUELLE."\n";

=begin
	# on laisse tous les articles activé dans Reflex
	if ($data{'TOP_DESACTIVATION'}) { # on le desactive uniquement si besoin
		# Activation ou desactivation de l'article
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_ARTICLE_ACTIVATION)).
						join('',@data{qw/CODE_ACTIVITE CODE/})."1\n";
	}
=cut

	$i++;
}

close(REFLEX);

printf "%s OK. ".($i)." articles exported in %s\n",get_time(),second2hms(time - $old_time);

if ($i > 0) {
	my $export_directory = '';
	if ($test) {
		printf "%s Copying to test directory\n",get_time();
		$export_directory = $cfg->{PRODUCTION_DIRECTORY_TEST};
	} else {
		printf "%s Copying to production directory\n",get_time();
		$export_directory = $cfg->{PRODUCTION_DIRECTORY_PROD};
	}
	$export_directory = uc($export_directory);

	my $letter = substr(trim($export_directory),0,1); # on recupere la lettre a créer
	if (!isDriveMapped($letter)) { # connecte le lecteur réseau car il n'est pas mappé quand le script PHP se lance
		my $location = '\\\\reflex\\'.($test ? 'INTMC_INTMC':'INTMC_INTMC2');
		my $user = 'Administrateur';
		my $pass = 'C100manche';
		system("net use $letter: \"$location\" $pass /user:$user /persistent:no>nul 2>&1");
	}

	while (-e "$export_directory/ART_rubis.txt") {
		sleep(20);
	}

	copy(OUTPUT_FILENAME,"$export_directory/ART_rubis.txt") or warn "Impossible de déplacer le fichier dans '$export_directory/ART_rubis.txt' ($!)";
	
	require 'save-file-to-zip.pl';
}
