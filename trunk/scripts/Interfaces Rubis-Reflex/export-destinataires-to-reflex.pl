#!/usr/bin/perl
use strict;

use Data::Dumper;
use Win32::ODBC;
use File::Path qw(make_path);
use File::Copy;
use File::Basename;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;

my ($debug,$test);
GetOptions('debug!'=> \$debug, 'test!' => \$test) ;

use constant OUTPUT_FILENAME => 'output/interface-destinataires.txt';

use constant {
	CODE_APPLICATION									=>	'HL',
	CODE_INTERFACE										=>	'08',
	CODE_RUBRIQUE_DESTINATAIRE							=>	'110',
	CODE_RUBRIQUE_DESTINATAIRE_SUITE					=>	'111',
	CODE_RUBRIQUE_DESTINATAIRE_ACTIVATION				=>	'116',
	CODE_INTERFACE_DEPOT_PHYSIQUE_DESTINATAIRE			=>	'30',
	CODE_RUBRIQUE_DEPOT_PHYSIQUE_DESTINATAIRE			=>	'110',
	CODE_RUBRIQUE_FAMILLE_IC							=>	'117',
	CODE_RUBRIQUE_VALEUR_IC								=>	'118',
	CODE_RUBRIQUE_COMMENTAIRE							=>	'119',
	CODE_RUBRIQUE_SUPPRESSION_COMMENTAIRE				=>	'819',

	CODE_ACTIVITE								=>	'MCS',
	CODE_DEPOT_PHYSIQUE							=>	'AFA',

	# destinataire
	TOP_TRANSFERT_POSSIBLES						=>	0,
	CODE_CIRCUIT_DISTRIBUTION					=>	'STD',
	CODE_REGION									=>	'56',
	CODE_FAMILLE_JOURS_FERIES					=>	'FRA',
	CODE_LANGUE_ETRANGERE						=>	'',
	TOP_GESTION_DATE_ORDONNACEMENT_MINI			=>	0,
	TOP_GESTION_DATE_ORDONNACEMENT_SUPERIEUR	=>	0,
	TOP_RELIQUAT_POSSIBLE						=>	0,
	TOP_RELIQUAT_AUTOMATIQUE					=>	0,
	TOP_SOLDE_POSSIBLE							=>	1,
	TOP_SOLDE_AUTOMATIQUE						=>	1,
	TOP_INTERMEDIAIRE							=>	0,
	TOP_LIVRAISON_GLOBALISEE					=>	1,
	TOP_EDITION_BORDEREAU_ECLATEMENT			=>	0,
	TOP_EDITION_BL_FINAUX						=>	1,
	TOP_EDITION_FICHE_PALETTE					=>	1,
	TOP_INTERFACE								=>	0,
	TOP_AGRES_CONSIGNES							=>	0,
	CODE_DEPOT_PHYSIQUE_CORRESPONDANT			=>	'',
	CODE_TYPE_SUPPORT							=>	'',
	POIDS_MAXIMUM_SUPPORT						=>	0,
	VOLUME_MAXIMUM_SUPPORT						=>	0,
	VOLUME_STANDARD_SUPPORT						=>	0,
	TOP_SCINDER_PREVELEMENT						=>	0,
	TOP_INTERFACE_AVIS_EXPEDITION_A_GENERER		=>	1,
	CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION => '',
	TOP_AVIS_EXPEDITION_DETAILLE				=>	0,
	TOP_AVIS_EXPEDITION_ALLOTI					=>	0,

	# depot physique/destinataire
	TOP_DESTINATAIRE_INTERNE					=>	0,
	CODE_CIRCUIT_PRELEVEMENT					=>	'',
	CODE_1_EMPLACEMENT_MISE_A_DISPO				=>	'',
	CODE_2_EMPLACEMENT_MISE_A_DISPO				=>	'',
	CODE_3_EMPLACEMENT_MISE_A_DISPO				=>	'',
	CODE_4_EMPLACEMENT_MISE_A_DISPO				=>	'',
	CODE_5_EMPLACEMENT_MISE_A_DISPO				=>	'',
	CODE_DESTINATAIRE_INTERMEDIAIRE				=>	'',
	CODE_REGROUPEMENT_CHARGEMENT				=>	'',
	NON_UTILISE									=>	'',
	POURCENTAGE_TOLERANCE_QTE_PLUS				=>	0,
	POURCENTAGE_TOLERANCE_QTE_MOINS				=>	0,
	TOP_CHAINAGE_POSSIBLE						=>	0,
	TOP_SUBSTITUTION_POSSIBLE					=>	0,
	TOP_SORTIE_STOCK_VALIDATION_PREPARATION		=>	1,
	TOP_PREPARATION_A_FAIRE_COMPLETE			=>	0,
	CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT	=>	'STD'
};

my %field_sizes = (qw/	CODE_ACTIVITE										3
						CODE												13
						LIBELLE												30
						LIBELLE_REDUIT										15
						MOT_DIRECTEUR										15
						CODE_USAGE											13
						INTERLOCUTEUR										30
						TELEPHONE_INTERLOCUTEUR								15
						TELECOPIE_INTERLOCUTEUR								15
						CODE_CIRCUIT_DISTRIBUTION							3
						CODE_REGION											6
						CODE_FAMILLE_JOURS_FERIES							3
						CODE_LANGUE_ETRANGERE								3
						CODE_DEPOT_PHYSIQUE_CORRESPONDANT					3
						CODE_TYPE_SUPPORT									3
						POIDS_MAXIMUM_SUPPORT								11
						VOLUME_MAXIMUM_SUPPORT								11
						VOLUME_STANDARD_SUPPORT								11
						CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION	15
						CODE_ADRESSE										13
						RAISON_SOCIALE										30
						ADRESSE_1											30
						ADRESSE_2											30
						ADRESSE_3											30
						ADRESSE_4											30
						TELEPHONE											15
						TELECOPIE											15
						TELEX												10
						CODE_DEPOT_PHYSIQUE									3
						CODE_PRIORITE_SERVICE								1
						CODE_CIRCUIT_PRELEVEMENT							10
						CODE_1_EMPLACEMENT_MISE_A_DISPO						4
						CODE_2_EMPLACEMENT_MISE_A_DISPO						1
						CODE_3_EMPLACEMENT_MISE_A_DISPO						3
						CODE_4_EMPLACEMENT_MISE_A_DISPO						2
						CODE_5_EMPLACEMENT_MISE_A_DISPO						2
						CODE_DESTINATAIRE_INTERMEDIAIRE						13
						CODE_SECTEUR_DISTRIBUTION							10
						CODE_REGROUPEMENT_CHARGEMENT						10
						NON_UTILISE											12
						POURCENTAGE_TOLERANCE_QTE_PLUS						5
						POURCENTAGE_TOLERANCE_QTE_MOINS						5
						CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT			10
						CODE_FAMILLE_IC										10
						CODE_IC												10
						VALEUR_IC_ARTICLE									40
						COMMENTAIRE											70
						FAMILLE_COMMENTAIRE									3
/);


my $old_time = 0;
my $cfg = new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE_PROD};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

my @destinataires = ();
my %code_destinataire_deja_vu = ();

printf "%s Select des clients\n",get_time(); $old_time=time;
my $sql = "select * from ${prefix_base_rubis}GESTCOM.ACLIENP1 where NOMCL<>'ADHERENT' ORDER BY CATCL ASC, NOCLI ASC"; # regarde les artisans actif
if ($loginor->Sql($sql)) { # regarde les clients
	die "Erreur dans la selection des clients\n$sql";
}
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash();
	
	next if ($row{'NOCLI'} == 'ATLANT'); # patch pour éviter le CLIENT<>FOURNISSEUR ATLANT
	
	$code_destinataire_deja_vu{$row{'NOCLI'}} = 1;
	push @destinataires,\%row ;
}

printf "%s Select des fournisseurs\n",get_time(); $old_time=time;
my $sql = "select * from ${prefix_base_rubis}GESTCOM.AFOURNP1 ORDER BY NOFOU ASC"; # regarde les fournisseurs actif
if ($loginor->Sql($sql)) { # regarde les clients
	die "Erreur dans la selection des fournisseurs\n$sql";
}
while($loginor->FetchRow()) {
	my %row = $loginor->DataHash();
	if (exists $code_destinataire_deja_vu{$row{'NOFOU'}}) { next ; }

	$row{'NOCLI'} = $row{'NOFOU'};
	$row{'NOMCL'} = $row{'NOMFO'};
	$row{'TELCL'} = $row{'TELFO'};
	$row{'TLCCL'} = $row{'TLCFO'};
	$row{'AD1CL'} = $row{'RUEFO'};
	$row{'AD2CL'} = $row{'VILFO'};
	$row{'RUECL'} = '';
	$row{'CPCLF'} = $row{'CPFOU'};
	$row{'BURCL'} = $row{'BURFO'};
	$row{'ETCLE'} = $row{'ETFOE'};
	$row{'TELCC'} = $row{'TLXFO'};
	$row{'ETCLE'} = $row{'ETFOE'};
	$row{'ETCLE'} = $row{'ETFOE'};
	$row{'ETCLE'} = $row{'ETFOE'};
	$row{'ETCLE'} = $row{'ETFOE'};
	$row{'CATCL'} = 0;
	$row{'TOUCL'} = 0;
	$row{'COFIN'} = '';
	$row{'COMC1'} = '';
	push @destinataires,\%row ;
}

mkpath(dirname(OUTPUT_FILENAME)) if !-d dirname(OUTPUT_FILENAME) ;
open(REFLEX,'+>'.OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".OUTPUT_FILENAME."' ($!)";

if ($debug) {
	print Dumper(\@destinataires);
	exit;
}

my $i=0;
# recupere les données de Rubis
#while($loginor->FetchRow()) {
foreach (@destinataires) {
	my %data = ();
	#my %row = $loginor->DataHash() ;
	my %row = %$_;
	
	$data{'CODE_ACTIVITE'}								= fill_with_blank(CODE_ACTIVITE,$field_sizes{'CODE_ACTIVITE'});
	$data{'CODE'}										= fill_with_blank($row{'NOCLI'},$field_sizes{'CODE'});
	$data{'LIBELLE'}									= fill_with_blank($row{'NOMCL'},$field_sizes{'LIBELLE'});
	$data{'LIBELLE_REDUIT'}								= fill_with_blank($row{'NOMCL'},$field_sizes{'LIBELLE_REDUIT'});
	$data{'MOT_DIRECTEUR'}								= fill_with_blank($row{'NOMCL'},$field_sizes{'MOT_DIRECTEUR'});
	$data{'CODE_USAGE'}									= fill_with_blank('',$field_sizes{'CODE_USAGE'});
	$data{'INTERLOCUTEUR'}								= fill_with_blank($row{'NOMCL'},$field_sizes{'INTERLOCUTEUR'});
	$data{'TELEPHONE_INTERLOCUTEUR'}					= fill_with_blank($row{'TELCL'},$field_sizes{'TELEPHONE_INTERLOCUTEUR'});
	$data{'TELECOPIE_INTERLOCUTEUR'}					= fill_with_blank($row{'TLCCL'},$field_sizes{'TELECOPIE_INTERLOCUTEUR'});
	$data{'TOP_TRANSFERT_POSSIBLES'}					= binary(TOP_TRANSFERT_POSSIBLES);
	$data{'CODE_CIRCUIT_DISTRIBUTION'}					= fill_with_blank(CODE_CIRCUIT_DISTRIBUTION,$field_sizes{'CODE_CIRCUIT_DISTRIBUTION'});
	$data{'CODE_REGION'}								= fill_with_blank(CODE_REGION,$field_sizes{'CODE_REGION'});
	$data{'CODE_FAMILLE_JOURS_FERIES'}					= fill_with_blank(CODE_FAMILLE_JOURS_FERIES,$field_sizes{'CODE_FAMILLE_JOURS_FERIES'});
	$data{'CODE_LANGUE_ETRANGERE'}						= fill_with_blank(CODE_LANGUE_ETRANGERE,$field_sizes{'CODE_LANGUE_ETRANGERE'});
	$data{'TOP_GESTION_DATE_ORDONNACEMENT_MINI'}		= binary(TOP_GESTION_DATE_ORDONNACEMENT_MINI);
	$data{'TOP_GESTION_DATE_ORDONNACEMENT_SUPERIEUR'}	= binary(TOP_GESTION_DATE_ORDONNACEMENT_SUPERIEUR);
	$data{'TOP_RELIQUAT_POSSIBLE'}						= binary(TOP_RELIQUAT_POSSIBLE);
	$data{'TOP_RELIQUAT_AUTOMATIQUE'}					= binary(TOP_RELIQUAT_AUTOMATIQUE);
	$data{'TOP_SOLDE_POSSIBLE'}							= binary(TOP_SOLDE_POSSIBLE);
	$data{'TOP_SOLDE_AUTOMATIQUE'}						= binary(TOP_SOLDE_AUTOMATIQUE);
	$data{'TOP_INTERMEDIAIRE'}							= binary(TOP_INTERMEDIAIRE);
	$data{'TOP_LIVRAISON_GLOBALISEE'}					= binary(TOP_LIVRAISON_GLOBALISEE);
	$data{'TOP_EDITION_BORDEREAU_ECLATEMENT'}			= binary(TOP_EDITION_BORDEREAU_ECLATEMENT);
	$data{'TOP_EDITION_BL_FINAUX'}						= binary(TOP_EDITION_BL_FINAUX);
	$data{'TOP_EDITION_FICHE_PALETTE'}					= binary(TOP_EDITION_FICHE_PALETTE);
	$data{'TOP_INTERFACE'}								= binary(TOP_INTERFACE);
	$data{'TOP_AGRES_CONSIGNES'}						= binary(TOP_AGRES_CONSIGNES);
	$data{'CODE_DEPOT_PHYSIQUE_CORRESPONDANT'}			= fill_with_blank(CODE_DEPOT_PHYSIQUE_CORRESPONDANT,$field_sizes{'CODE_DEPOT_PHYSIQUE_CORRESPONDANT'});
	$data{'CODE_TYPE_SUPPORT'}							= fill_with_blank(CODE_TYPE_SUPPORT,$field_sizes{'CODE_TYPE_SUPPORT'});
	$data{'POIDS_MAXIMUM_SUPPORT'}						= fill_with_zero(POIDS_MAXIMUM_SUPPORT,$field_sizes{'POIDS_MAXIMUM_SUPPORT'});
	$data{'VOLUME_MAXIMUM_SUPPORT'}						= fill_with_zero(VOLUME_MAXIMUM_SUPPORT,$field_sizes{'VOLUME_MAXIMUM_SUPPORT'});
	$data{'VOLUME_STANDARD_SUPPORT'}					= fill_with_zero(VOLUME_STANDARD_SUPPORT,$field_sizes{'VOLUME_STANDARD_SUPPORT'});
	$data{'TOP_SCINDER_PREVELEMENT'}					= binary(TOP_SCINDER_PREVELEMENT);
	$data{'TOP_INTERFACE_AVIS_EXPEDITION_A_GENERER'}	= binary(TOP_INTERFACE_AVIS_EXPEDITION_A_GENERER);
	$data{'CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION'}	= fill_with_blank(CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION,$field_sizes{'CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION'});
	$data{'TOP_AVIS_EXPEDITION_DETAILLE'}				= binary(TOP_AVIS_EXPEDITION_DETAILLE);
	$data{'TOP_AVIS_EXPEDITION_ALLOTI'}					= binary(TOP_AVIS_EXPEDITION_ALLOTI);

	# destinataire suite
	$data{'CODE_ADRESSE'}								= fill_with_blank('',$field_sizes{'CODE_ADRESSE'});
	$data{'RAISON_SOCIALE'}								= fill_with_blank($row{'NOMCL'},$field_sizes{'RAISON_SOCIALE'});
	$data{'ADRESSE_1'}									= fill_with_blank($row{'AD1CL'} eq '' ? 'champs adresse 1 a renseigner':$row{'AD1CL'},$field_sizes{'ADRESSE_1'});
	$data{'ADRESSE_2'}									= fill_with_blank($row{'AD2CL'},$field_sizes{'ADRESSE_2'});
	$data{'ADRESSE_3'}									= fill_with_blank($row{'RUECL'},$field_sizes{'ADRESSE_3'});
	$data{'ADRESSE_4'}									= fill_with_blank($row{'CPCLF'}.' '.$row{'BURCL'},$field_sizes{'ADRESSE_4'});
	$row{'TELCC'} =~ s/ +//g;
	$data{'TELEX'}										= fill_with_blank($row{'TELCC'},$field_sizes{'TELEX'});
	
	# activation
	$data{'TOP_ACTIVATION'}								= binary($row{'ETCLE'} eq 'S' ? 1:0);

	# depot physique / destinataire
	$data{'CODE_DEPOT_PHYSIQUE'}						= fill_with_blank(CODE_DEPOT_PHYSIQUE,$field_sizes{'CODE_DEPOT_PHYSIQUE'});
	$data{'TOP_DESTINATAIRE_INTERNE'}					= binary(TOP_DESTINATAIRE_INTERNE);
	$data{'CODE_PRIORITE_SERVICE'}						= fill_with_blank($row{'CATCL'} eq '1' ? 1:2,$field_sizes{'CODE_PRIORITE_SERVICE'});
	$data{'CODE_CIRCUIT_PRELEVEMENT'}					= fill_with_blank(CODE_CIRCUIT_PRELEVEMENT,$field_sizes{'CODE_CIRCUIT_PRELEVEMENT'});
	$data{'CODE_1_EMPLACEMENT_MISE_A_DISPO'}			= fill_with_blank(CODE_1_EMPLACEMENT_MISE_A_DISPO,$field_sizes{'CODE_1_EMPLACEMENT_MISE_A_DISPO'});
	$data{'CODE_2_EMPLACEMENT_MISE_A_DISPO'}			= fill_with_blank(CODE_2_EMPLACEMENT_MISE_A_DISPO,$field_sizes{'CODE_2_EMPLACEMENT_MISE_A_DISPO'});
	$data{'CODE_3_EMPLACEMENT_MISE_A_DISPO'}			= fill_with_blank(CODE_3_EMPLACEMENT_MISE_A_DISPO,$field_sizes{'CODE_3_EMPLACEMENT_MISE_A_DISPO'});
	$data{'CODE_4_EMPLACEMENT_MISE_A_DISPO'}			= fill_with_blank(CODE_4_EMPLACEMENT_MISE_A_DISPO,$field_sizes{'CODE_4_EMPLACEMENT_MISE_A_DISPO'});
	$data{'CODE_5_EMPLACEMENT_MISE_A_DISPO'}			= fill_with_blank(CODE_5_EMPLACEMENT_MISE_A_DISPO,$field_sizes{'CODE_5_EMPLACEMENT_MISE_A_DISPO'});
	$data{'CODE_DESTINATAIRE_INTERMEDIAIRE'}			= fill_with_blank(CODE_DESTINATAIRE_INTERMEDIAIRE,$field_sizes{'CODE_DESTINATAIRE_INTERMEDIAIRE'});
	$data{'CODE_SECTEUR_DISTRIBUTION'}					= fill_with_blank($row{'TOUCL'} ? $row{'TOUCL'}:'6HZ',$field_sizes{'CODE_SECTEUR_DISTRIBUTION'});
	$data{'CODE_REGROUPEMENT_CHARGEMENT'}				= fill_with_blank(CODE_REGROUPEMENT_CHARGEMENT,$field_sizes{'CODE_REGROUPEMENT_CHARGEMENT'});
	$data{'NON_UTILISE'}								= fill_with_blank(NON_UTILISE,$field_sizes{'NON_UTILISE'});
	$data{'POURCENTAGE_TOLERANCE_QTE_PLUS'}				= fill_with_zero(POURCENTAGE_TOLERANCE_QTE_PLUS,$field_sizes{'POURCENTAGE_TOLERANCE_QTE_PLUS'});
	$data{'POURCENTAGE_TOLERANCE_QTE_MOINS'}			= fill_with_zero(POURCENTAGE_TOLERANCE_QTE_MOINS,$field_sizes{'POURCENTAGE_TOLERANCE_QTE_MOINS'});
	$data{'TOP_CHAINAGE_POSSIBLE'}						= binary(TOP_CHAINAGE_POSSIBLE);
	$data{'TOP_SUBSTITUTION_POSSIBLE'}					= binary(TOP_SUBSTITUTION_POSSIBLE);
	$data{'TOP_SORTIE_STOCK_VALIDATION_PREPARATION'}	= binary(TOP_SORTIE_STOCK_VALIDATION_PREPARATION);
	$data{'TOP_PREPARATION_A_FAIRE_COMPLETE'}			= binary(TOP_PREPARATION_A_FAIRE_COMPLETE);
	$data{'CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT'}	= fill_with_blank(CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT,$field_sizes{'CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT'});																					

	# IC
	$data{'CODE_FAMILLE_IC'}							= fill_with_blank('RUBIS',$field_sizes{'CODE_FAMILLE_IC'});
	$data{'CODE_IC_1'}									= fill_with_blank('LIBELLE',$field_sizes{'CODE_IC'});
	$data{'IC_1'}										= fill_with_blank($row{'NOMCL'},$field_sizes{'VALEUR_IC_ARTICLE'});

	# commentaire
	$data{'COMMENTAIRE1'}								= fill_with_blank($row{'TELCC'},$field_sizes{'COMMENTAIRE'}); # portable 1
	$data{'FAMILLE_COMMENTAIRE1'}						= fill_with_blank('TE1',$field_sizes{'FAMILLE_COMMENTAIRE'});
	$data{'COMMENTAIRE2'}								= fill_with_blank($row{'TLXCL'},$field_sizes{'COMMENTAIRE'}); # portable 2
	$data{'FAMILLE_COMMENTAIRE2'}						= fill_with_blank('TE2',$field_sizes{'FAMILLE_COMMENTAIRE'});
	$data{'COMMENTAIRE3'}								= fill_with_blank($row{'COMC1'},$field_sizes{'COMMENTAIRE'}); # Email
	$data{'FAMILLE_COMMENTAIRE3'}						= fill_with_blank('MAI',$field_sizes{'FAMILLE_COMMENTAIRE'});
	$data{'COMMENTAIRE4'}								= fill_with_blank($row{'COFIN'},$field_sizes{'COMMENTAIRE'}); # Latitude et Longitude
	$data{'FAMILLE_COMMENTAIRE4'}						= fill_with_blank('LL' ,$field_sizes{'FAMILLE_COMMENTAIRE'});

###################################################################################################################################################################

	my $num_sequence = fill_with_zero($i+1,7);

	# Activation du destinataire pour enregistrer ses modifications
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_DESTINATAIRE_ACTIVATION)).
					join('',@data{qw/CODE_ACTIVITE CODE/})."0\n";

	# destinataire
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_DESTINATAIRE)).
					join('',@data{qw/
						CODE_ACTIVITE							
						CODE								
						LIBELLE	LIBELLE_REDUIT	MOT_DIRECTEUR	CODE_USAGE
						INTERLOCUTEUR	TELEPHONE_INTERLOCUTEUR	TELECOPIE_INTERLOCUTEUR							
						TOP_TRANSFERT_POSSIBLES							
						CODE_CIRCUIT_DISTRIBUTION	CODE_REGION	CODE_FAMILLE_JOURS_FERIES						
						CODE_LANGUE_ETRANGERE							
						TOP_GESTION_DATE_ORDONNACEMENT_MINI				
						TOP_GESTION_DATE_ORDONNACEMENT_SUPERIEUR		
						TOP_RELIQUAT_POSSIBLE	TOP_RELIQUAT_AUTOMATIQUE						
						TOP_SOLDE_POSSIBLE	TOP_SOLDE_AUTOMATIQUE							
						TOP_INTERMEDIAIRE					
						TOP_LIVRAISON_GLOBALISEE						
						TOP_EDITION_BORDEREAU_ECLATEMENT				
						TOP_EDITION_BL_FINAUX	TOP_EDITION_FICHE_PALETTE						
						TOP_INTERFACE						
						TOP_AGRES_CONSIGNES								
						CODE_DEPOT_PHYSIQUE_CORRESPONDANT				
						CODE_TYPE_SUPPORT	POIDS_MAXIMUM_SUPPORT	VOLUME_MAXIMUM_SUPPORT	VOLUME_STANDARD_SUPPORT							
						TOP_SCINDER_PREVELEMENT							
						TOP_INTERFACE_AVIS_EXPEDITION_A_GENERER			
						CODE_CHAINE_GENERATION_INTERFACE_AVIS_EXPEDITION
						TOP_AVIS_EXPEDITION_DETAILLE	TOP_AVIS_EXPEDITION_ALLOTI
					/})."\n";

	# destinataire suite
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_DESTINATAIRE_SUITE)).
					join('',@data{qw/
						CODE_ACTIVITE
						CODE
						CODE_ADRESSE	RAISON_SOCIALE
						ADRESSE_1	ADRESSE_2	ADRESSE_3	ADRESSE_4
						TELEPHONE_INTERLOCUTEUR	TELECOPIE_INTERLOCUTEUR	TELEX
					/})."\n";

	# Information complementaire (IC)
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FAMILLE_IC)).join('',@data{qw/CODE_ACTIVITE CODE CODE_FAMILLE_IC/})."\n";
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_VALEUR_IC)).join('',@data{qw/CODE_ACTIVITE CODE CODE_IC_1 IC_1/})."\n";

	# supprime les commentaires precedents
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_SUPPRESSION_COMMENTAIRE)).
					join('',@data{qw/CODE_ACTIVITE CODE/})."\n";

	my $j=1;
	# commentaire 1
	if (trim($data{'COMMENTAIRE1'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_COMMENTAIRE)).join('',@data{qw/CODE_ACTIVITE CODE/}).fill_with_zero($j++,3).join('',@data{qw/FAMILLE_COMMENTAIRE1 COMMENTAIRE1/})."\n";
	}
	if (trim($data{'COMMENTAIRE2'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_COMMENTAIRE)).join('',@data{qw/CODE_ACTIVITE CODE/}).fill_with_zero($j++,3).join('',@data{qw/FAMILLE_COMMENTAIRE2 COMMENTAIRE2/})."\n";
	}
	if (trim($data{'COMMENTAIRE3'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_COMMENTAIRE)).join('',@data{qw/CODE_ACTIVITE CODE/}).fill_with_zero($j++,3).join('',@data{qw/FAMILLE_COMMENTAIRE3 COMMENTAIRE3/})."\n";
	}
	if (trim($data{'COMMENTAIRE4'})) {
		print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_COMMENTAIRE)).join('',@data{qw/CODE_ACTIVITE CODE/}).fill_with_zero($j++,3).join('',@data{qw/FAMILLE_COMMENTAIRE4 COMMENTAIRE4/})."\n";
	}


	# depot physique / destinataire
	print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE_DEPOT_PHYSIQUE_DESTINATAIRE,CODE_RUBRIQUE_DEPOT_PHYSIQUE_DESTINATAIRE)).
					join('',@data{qw/
						CODE_DEPOT_PHYSIQUE
						CODE_ACTIVITE
						CODE
						TOP_DESTINATAIRE_INTERNE
						CODE_FAMILLE_JOURS_FERIES
						CODE_PRIORITE_SERVICE	CODE_CIRCUIT_PRELEVEMENT
						CODE_1_EMPLACEMENT_MISE_A_DISPO	CODE_2_EMPLACEMENT_MISE_A_DISPO	CODE_3_EMPLACEMENT_MISE_A_DISPO	CODE_4_EMPLACEMENT_MISE_A_DISPO	CODE_5_EMPLACEMENT_MISE_A_DISPO
						CODE_DESTINATAIRE_INTERMEDIAIRE
						CODE_SECTEUR_DISTRIBUTION
						CODE_REGROUPEMENT_CHARGEMENT
						NON_UTILISE
						POURCENTAGE_TOLERANCE_QTE_PLUS	POURCENTAGE_TOLERANCE_QTE_MOINS
						TOP_CHAINAGE_POSSIBLE	TOP_SUBSTITUTION_POSSIBLE	TOP_SORTIE_STOCK_VALIDATION_PREPARATION	TOP_PREPARATION_A_FAIRE_COMPLETE
						CODE_MODE_AFFECTATION_AUTOMATIQUE_CIRCUIT
					/})."\n";

	# activation les clients sont tous activé dans Reflex
	#print REFLEX	$num_sequence.join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_DESTINATAIRE_ACTIVATION)).
	#				join('',@data{qw/CODE_ACTIVITE CODE TOP_ACTIVATION/})."\n";

	$i++;
}

close(REFLEX);
printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);


###################################### ENVOI LE FICHIER POUR TRAITEMENT ######################################################
if ($test) {
	printf "%s Copying to test directory\n",get_time();
	copy(OUTPUT_FILENAME,$cfg->{PRODUCTION_DIRECTORY_TEST}.'/DES_rubis.txt') or warn "Impossible de copier le fichier vers le repertoire de test ($!)";
} else {
	printf "%s Copying to production directory\n",get_time();
	copy(OUTPUT_FILENAME,$cfg->{PRODUCTION_DIRECTORY_PROD}.'/DES_rubis.txt') or warn "Impossible de copier le fichier vers le repertoire de production ($!)";
}

require 'save-file-to-zip.pl';