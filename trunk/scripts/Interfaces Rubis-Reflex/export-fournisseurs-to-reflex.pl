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


use constant OUTPUT_FILENAME => 'output/interface-fournisseurs.txt';

use constant {
	CODE_APPLICATION							=>	'HL',
	CODE_INTERFACE								=>	'12',
	CODE_RUBRIQUE_FOURNISSEUR					=>	'110',
	CODE_RUBRIQUE_FOURNISSEUR_SUITE				=>	'111',
	CODE_RUBRIQUE_FOURNISSEUR_ACTIVATION		=>	'116',

	CODE_ACTIVITE								=>	'MCS',

	# fournisseur
	CODE_IMPUTATION_AGRES						=>	1,		# 1=Fournisseur, 2=Transporteur
	DELAI_DOUANIER								=>	0,
	DEPOT_PHYSIQUE_CORRESPONDANT				=>	'',
	TOP_CONTROLE_CONDITIONNEMENT_FOURNISSEUR	=>	0
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
						CODE_IMPUTATION_AGRES								1
						DELAI_DOUANIER										3
						DEPOT_PHYSIQUE_CORRESPONDANT						3
						CODE_ADRESSE										13
						RAISON_SOCIALE										30
						ADRESSE_1											30
						ADRESSE_2											30
						ADRESSE_3											30
						ADRESSE_4											30
						TELEPHONE											15
						TELECOPIE											15
						TELEX												10
/);


#print Dumper(\%field_sizes); exit;


my $old_time = 0;
my $cfg = new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis = $cfg->{LOGINOR_PREFIX_BASE_PROD};
my $loginor = new Win32::ODBC('DSN='.$cfg->{LOGINOR_DSN}.';UID='.$cfg->{LOGINOR_USER}.';PWD='.$cfg->{LOGINOR_PASS}.';') or die "Ne peux pas se connecter à rubis";

printf "%s Select des fournisseurs\n",get_time(); $old_time=time;
my $sql = "select * from ${prefix_base_rubis}GESTCOM.AFOURNP1 order by NOFOU ASC";
if ($loginor->Sql($sql)) { # regarde les fournisseurs
	die "Erreur dans la selection des fournisseurs\n$sql";
}

mkpath(dirname(OUTPUT_FILENAME)) if !-d dirname(OUTPUT_FILENAME) ;
open(REFLEX,'+>'.OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".OUTPUT_FILENAME."' ($!)";

my $i=1;
# recupere les données de Rubis
while($loginor->FetchRow()) {
	my %data = ();
	my %row = $loginor->DataHash() ;
	
	$data{'CODE_ACTIVITE'}					= fill_with_blank(CODE_ACTIVITE,$field_sizes{'CODE_ACTIVITE'});
	$data{'CODE'}							= fill_with_blank($row{'NOFOU'},$field_sizes{'CODE'});
	$data{'LIBELLE'}						= fill_with_blank($row{'NOMFO'},$field_sizes{'LIBELLE'});
	$data{'LIBELLE_REDUIT'}					= fill_with_blank($row{'NOMFO'},$field_sizes{'LIBELLE_REDUIT'});
	$data{'MOT_DIRECTEUR'}					= fill_with_blank($row{'NOMFO'},$field_sizes{'MOT_DIRECTEUR'});
	$data{'CODE_USAGE'}						= fill_with_blank('',$field_sizes{'CODE_USAGE'});
	$data{'INTERLOCUTEUR'}					= fill_with_blank($row{'NOMFO'},$field_sizes{'INTERLOCUTEUR'});
	$data{'TELEPHONE_INTERLOCUTEUR'}		= fill_with_blank($row{'TELFO'},$field_sizes{'TELEPHONE_INTERLOCUTEUR'});
	$data{'TELECOPIE_INTERLOCUTEUR'}		= fill_with_blank($row{'TLCFO'},$field_sizes{'TELECOPIE_INTERLOCUTEUR'});
	$data{'CODE_IMPUTATION_AGRES'}			= fill_with_blank(CODE_IMPUTATION_AGRES,$field_sizes{'CODE_IMPUTATION_AGRES'});
	$data{'DELAI_DOUANIER'}					= fill_with_zero(DELAI_DOUANIER,$field_sizes{'DELAI_DOUANIER'});
	$data{'DEPOT_PHYSIQUE_CORRESPONDANT'}	= fill_with_blank(DEPOT_PHYSIQUE_CORRESPONDANT,$field_sizes{'DEPOT_PHYSIQUE_CORRESPONDANT'});
	$data{'TOP_CONTROLE_CONDITIONNEMENT_FOURNISSEUR'} = binary(TOP_CONTROLE_CONDITIONNEMENT_FOURNISSEUR);
	
	# destinataire suite
	$data{'CODE_ADRESSE'}					= fill_with_blank('',$field_sizes{'CODE_ADRESSE'});
	$data{'RAISON_SOCIALE'}					= fill_with_blank($row{'NOMFO'},$field_sizes{'RAISON_SOCIALE'});
	$data{'ADRESSE_1'}						= fill_with_blank($row{'RUEFO'} eq '' ? "champs adresse 1 a renseigner" : $row{'RUEFO'},$field_sizes{'ADRESSE_1'});
	$data{'ADRESSE_2'}						= fill_with_blank($row{'VILFO'},$field_sizes{'ADRESSE_2'});
	$data{'ADRESSE_3'}						= fill_with_blank('',$field_sizes{'ADRESSE_3'});
	$data{'ADRESSE_4'}						= fill_with_blank($row{'CPFOU'}.' '.$row{'BURFO'},$field_sizes{'ADRESSE_4'});
	$row{'TLXFO'} =~ s/ +//g;
	$data{'TELEX'}							= fill_with_blank($row{'TLXFO'},$field_sizes{'TELEX'});
	
	# activation
	$data{'TOP_ACTIVATION'}					= binary($row{'ETFOE'} eq 'S' ? 1:0);

	#print Dumper(\%data); exit;

	# Activation du fournisseur pour enregistrer ses modifications
	print REFLEX	fill_with_zero($i,7).join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FOURNISSEUR_ACTIVATION)).
					join('',@data{qw/CODE_ACTIVITE CODE/})."0\n";

	# fournisseur
	print REFLEX	fill_with_zero($i,7).join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FOURNISSEUR)).
					join('',@data{qw/
						CODE_ACTIVITE
						CODE
						LIBELLE
						LIBELLE_REDUIT
						MOT_DIRECTEUR
						CODE_USAGE
						INTERLOCUTEUR
						TELEPHONE_INTERLOCUTEUR
						TELECOPIE_INTERLOCUTEUR
						CODE_IMPUTATION_AGRES
						DELAI_DOUANIER
						DEPOT_PHYSIQUE_CORRESPONDANT
						TOP_CONTROLE_CONDITIONNEMENT_FOURNISSEUR
					/})."\n";

	# fournisseur suite
	print REFLEX	fill_with_zero($i,7).join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FOURNISSEUR_SUITE)).
					join('',@data{qw/
						CODE_ACTIVITE
						CODE
						CODE_ADRESSE
						RAISON_SOCIALE
						ADRESSE_1
						ADRESSE_2
						ADRESSE_3
						ADRESSE_4
						TELEPHONE_INTERLOCUTEUR
						TELECOPIE_INTERLOCUTEUR
						TELEX
					/})."\n";

	# les fournisseurs sont toujours activé dans Reflex
	# activation
	#print REFLEX	fill_with_zero($i,7).join('',(CODE_APPLICATION,CODE_INTERFACE,CODE_RUBRIQUE_FOURNISSEUR_ACTIVATION)).
	#				join('',@data{qw/CODE_ACTIVITE CODE TOP_ACTIVATION/})."\n";

	$i++;
}

close(REFLEX);
printf "%s OK. Delay %s\n",get_time(),second2hms(time - $old_time);

if ($test) {
	printf "%s Copying to test directory\n",get_time();
	copy(OUTPUT_FILENAME,$cfg->{PRODUCTION_DIRECTORY_TEST}.'/FOU_rubis.txt') or warn "Impossible de copier le fichier vers le repertoire de test ($!)";
} else {
	printf "%s Copying to production directory\n",get_time();
	copy(OUTPUT_FILENAME,$cfg->{PRODUCTION_DIRECTORY_PROD}.'/FOU_rubis.txt') or warn "Impossible de copier le fichier vers le repertoire de production ($!)";
}

require 'save-file-to-zip.pl';