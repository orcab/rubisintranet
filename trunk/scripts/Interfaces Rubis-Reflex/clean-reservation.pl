#!/usr/bin/perl
use strict;

use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
require 'Phpconst2perlconst.pm';
use File::Path;
use File::Copy;
use File::Basename;
require 'useful.pl'; # load get_time / second2hms / dot2comma
use Phpconst2perlconst ;
use Getopt::Long;
$|=1;

my ($test,$help);
GetOptions('test!'=>\$test,'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--test
	Exporte et importe depuis l'environnement de test
--usage ou --help
	Affiche ce message
EOT

use constant OUTPUT_FILENAME => 'output/clean-reservation.csv';

########################################################################################
my $old_time = 0;
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis 	= $cfg->{'LOGINOR_PREFIX_BASE_'.($test ? 'TEST':'PROD')};
my $rubis 				= new Win32::ODBC('DSN='.$cfg->{'LOGINOR_DSN'}.';UID='.$cfg->{'LOGINOR_USER'}.';PWD='.$cfg->{'LOGINOR_PASS'}.';') or die "Ne peux pas se connecter à rubis";
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";

################### CREATION DU FICHIER DE SORTIE ######################################
mkpath(dirname(OUTPUT_FILENAME)) if !-d dirname(OUTPUT_FILENAME) ;
open(CSV,'+>'.OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".OUTPUT_FILENAME."' ($!)";
print CSV join(';',qw/ARTICLE NUM_CLIENT NOM_CLIENT CDE_RUBIS QTE_BON QTE_GEI NUM_GEI NUM_SUPPORT LOCALISATION RESA_REF_REFLEX DESI1 DESI2 DESI3 BON_PREPA_EDITE CODE_VENDEUR MONTANT_PR_LIGNE MONTANT_CA_LIGNE/)."\n";

################# SELECT REFLEX ########################################################
printf "%s Select des stock reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
select
	GECART as CODE_ARTICLE, GECDES as RESA_DESTINATAIRE, GENGEI as NUM_GEI, GENSUP as NUM_SUPPORT, GEQGEI as QTE_GEI, GERRSO as RESA_REF_REFLEX,(EMC1EM + ' ' + EMC2EM + ' '+ EMC3EM + ' ' + EMC4EM + ' ' + EMC5EM) as LOCALISATION
from
	${prefix_base_reflex}.HLGEINP 				GEI
	left join	${prefix_base_reflex}.HLSUPPP 	SUPPORT
		on GEI.GENSUP=SUPPORT.SUNSUP
	left join  	${prefix_base_reflex}.HLEMPLP 	EMPLACEMENT
		on SUPPORT.SUNEMP=EMPLACEMENT.EMNEMP
where
		GECTST='200' and GECDPO='AFA' and GECACT='MCS' and GECPRP='MCS' and GECQAL='AFA' 	--on restreint au dépot AFA en qualité AFA
	and (GECDES<>'' or GERRSO<>'')
order by
	GECART ASC
EOT

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }
while ($reflex->FetchRow()) {
	my %row_reflex = $reflex->DataHash() ;

	my 	$code_client_resa = $row_reflex{'RESA_DESTINATAIRE'} ? $row_reflex{'RESA_DESTINATAIRE'} : $row_reflex{'RESA_REF_REFLEX'};
		$code_client_resa =~ s/^(.+?)\/.*/$1/; # si le code ressemble a code_client/num_bon, on le nettoi

	
	################# SELECT RUBIS ######################################################
	# on recherche dans Rubis les commande avec de la résa pour ce client
	printf "%s Select des cde rubis de %s pour %s\n",get_time(),$code_client_resa,$row_reflex{'CODE_ARTICLE'}; $old_time=time;
	my $sql_rubis = <<EOT ;
select
	ENTETE_BON.NOBON NUM_BON, CLIENT.NOMCL NOM_CLIENT, DS1DB as DESI1, DS2DB as DESI2, DS3DB as  DESI3, PREDI as BON_PREPA_EDITE, QTESA as QTE_BON, MONHT as MONTANT_CA_LIGNE, MONPR as MONTANT_PR_LIGNE, LIVSB as CODE_VENDEUR
from
				${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_BON
	left join 	${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
		on DETAIL_BON.NOCLI=CLIENT.NOCLI
	left join	${prefix_base_rubis}GESTCOM.AENTBOP1 ENTETE_BON
		on DETAIL_BON.NOCLI=ENTETE_BON.NOCLI and DETAIL_BON.NOBON=ENTETE_BON.NOBON
where
		ETSBE='' and TRAIT='R' and PROFI='1'					-- ligne non supprimé, non livré
--	and QTESA='$row_reflex{QTE_GEI}'
	and DETAIL_BON.NOCLI='$code_client_resa'
	and CODAR='$row_reflex{CODE_ARTICLE}'
EOT

	if ($rubis->Sql($sql_rubis))  { die "SQL Rubis RESA failed : ", $rubis->Error(); }
	my $nb_row = 0;

	if ($rubis->FetchRow()) {
		# my %row_rubis = $rubis->DataHash() ;
		
		# print CSV join(';',	$row_reflex{'CODE_ARTICLE'},
		# 					$code_client_resa,
		# 					$row_rubis{'NOM_CLIENT'},
		# 					$row_rubis{'NUM_BON'},
		# 					remove_useless_zero($row_rubis{'QTE_BON'}),
		# 					$row_reflex{'QTE_GEI'},
		# 					$row_reflex{'NUM_GEI'},
		# 					$row_reflex{'NUM_SUPPORT'},
		# 					$row_reflex{'LOCALISATION'},
		# 					$row_reflex{'RESA_REF_REFLEX'},
		# 					$row_rubis{'DESI1'},
		# 					$row_rubis{'DESI2'},
		# 					$row_rubis{'DESI3'},
		# 					$row_rubis{'BON_PREPA_EDITE'},
		# 					$row_rubis{'CODE_VENDEUR'},
		# 					dot2comma($row_rubis{'MONTANT_PR_LIGNE'}),
		# 					dot2comma($row_rubis{'MONTANT_CA_LIGNE'})
		# 			)."\n";
		$nb_row++;
	} # fin if in rubis

	if ($nb_row <= 0) { # de pas de réservation dans RUBIS
		print CSV join(';',	$row_reflex{'CODE_ARTICLE'},
							$code_client_resa,
							'',
							'',
							'',
							$row_reflex{'QTE_GEI'},
							$row_reflex{'NUM_GEI'},
							$row_reflex{'NUM_SUPPORT'},
							$row_reflex{'LOCALISATION'},
							$row_reflex{'RESA_REF_REFLEX'},
							'',
							'',
							'',
							'',
							'',
							'',
							''
					)."\n";
	} # fin pas de commande dans rubis

} # fin while reflex

close(CSV);