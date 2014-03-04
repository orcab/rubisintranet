#!/usr/bin/perl
use strict;
use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
use DateTime;
require 'Phpconst2perlconst.pm';
use File::Path;
use File::Copy;
use File::Basename;
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;
$|=1;

use constant {	'COMMANDE' 	=> 1,
				'AVOIR'		=> 2};

my ($test,$date_start,$date_end,$help);
GetOptions('test!'=>\$test, 'date-start:s'=>\$date_start , 'date-end:s'=>\$date_end, 'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--test
	Exporte et importe depuis l'environnement de test

--date-start=yyyy-mm-dd
	Date de debut de la sortie de stock (par defaut le dernier jour ouvre)

--date-end=yyyy-mm-dd
	Date de fin de la sortie de stock (par defaut le dernier jour ouvre)

--usage ou --help
	Affiche ce message
EOT

########################################################################################
my $old_time = 0;
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_rubis 	= $cfg->{'LOGINOR_PREFIX_BASE_'.($test ? 'TEST':'PROD')};
my $rubis 				= new Win32::ODBC('DSN='.$cfg->{'LOGINOR_DSN'}.';UID='.$cfg->{'LOGINOR_USER'}.';PWD='.$cfg->{'LOGINOR_PASS'}.';') or die "Ne peux pas se connecter à rubis";
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";

################# DATE DE TRAVAIL ######################################################
my($siecle_start,$annee_start,$mois_start,$jour_start);
my($siecle_end,$annee_end,$mois_end,$jour_end);
if (	(length($date_start)>0 	&& $date_start 	!~ m/^\d{4}-\d{2}-\d{2}$/)
	||	(length($date_end)>0 	&& $date_end 	!~ m/^\d{4}-\d{2}-\d{2}$/))  {
	die "Le format de date '$date_start' ou '$date_end' n'est pas du style yyyy-mm-dd";
}

if (length($date_start)<=0 && length($date_end)<=0) { # aucune date de spécifié --> on prend le denrier jour ouvré
	my $delta_day = 1; # un jour en moins
	my $weekday = strftime('%w', localtime);

	if ($weekday == 0) { # dimanche
		$delta_day = 2
	} elsif ($weekday == 1) { # lundi
		$delta_day = 3
	}

	my $last_open_day = DateTime->now()->subtract( days => $delta_day );
	($siecle_start,$annee_start,$mois_start,$jour_start) = ($last_open_day->ymd =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
	($siecle_end,$annee_end,$mois_end,$jour_end) 		 = ($siecle_start,$annee_start,$mois_start,$jour_start);

	print "Aucune date de specifiee. Using\n\t--date_start=$siecle_start$annee_start-$mois_start-$jour_start\n\t--date_end=$siecle_end$annee_end-$mois_end-$jour_end\n";

} else {
	($siecle_start,$annee_start,$mois_start,$jour_start) 	= ($date_start =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
	($siecle_end,$annee_end,$mois_end,$jour_end) 			= ($date_end =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
}


################### CREATION DU FICHIER DE SORTIE ######################################
my $OUTPUT_FILENAME = "output/check-livraison du ${siecle_start}${annee_start}-${mois_start}-${jour_start} au ${siecle_end}${annee_end}-${mois_end}-${jour_end}.csv";
mkpath(dirname($OUTPUT_FILENAME)) if !-d dirname($OUTPUT_FILENAME) ;
open(CSV,'+>'.$OUTPUT_FILENAME) or die "ne peux pas creer le fichier de sortie '".$OUTPUT_FILENAME."' ($!)";
print CSV join(';',qw/ETAT TYPE NUM_CLIENT NOM_CLIENT CDE_RUBIS LIGNE ARTICLE DESIGNATION1 DESIGNATION2 DESIGNATION3 QTE LAST_USER LAST_ACTION/)."\n";

#goto AVOIR;

COMMANDE:
################# SELECT DES COMMANDES REFLEX ########################################################
printf "%s Select des livraisons reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
select
--	PREPA_ENTETE.PESSOR,PREPA_ENTETE.PEASOR,PREPA_ENTETE.PEMSOR,PREPA_ENTETE.PEJSOR, -- date de sortie de stock
	COMMENTAIRE.COTXTC as COMMENTAIRE	-- texte commentaire
from	
				${prefix_base_reflex}.HLPRPLP PREPA_DETAIL
	left join 	${prefix_base_reflex}.HLPRENP PREPA_ENTETE
		on		PREPA_DETAIL.P1NANP=PREPA_ENTETE.PENANN
			and 	PREPA_DETAIL.P1NPRE=PREPA_ENTETE.PENPRE
	left join	${prefix_base_reflex}.HLCOMMP COMMENTAIRE
		on		COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM
			and 	COMMENTAIRE.COCFCO='ZZZ'
where	
		PREPA_DETAIL.P1TVLP='1'											-- ligne validé (préparé en totalité)
	and PREPA_ENTETE.PETSOP='1'											-- sortie de stock effectuée
	and PREPA_DETAIL.P1QODP=PREPA_DETAIL.P1QPRE							-- quantité à préparer = quantité livrée

	and RIGHT('0'+ CONVERT(VARCHAR,PESSOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEASOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEMSOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEJSOR),2) >= '$siecle_start$annee_start$mois_start$jour_start'
	and RIGHT('0'+ CONVERT(VARCHAR,PESSOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEASOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEMSOR),2)+RIGHT('0'+ CONVERT(VARCHAR,PEJSOR),2) <= '$siecle_end$annee_end$mois_end$jour_end'
EOT

#print $sql_reflex;exit;

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }

my %cde_rubis = ();
my %cde_rubis_ligne = ();
while ($reflex->FetchRow()) {
	my %row_reflex = $reflex->DataHash() ;
	my ($client,$cde,$ligne) = split(/\//,$row_reflex{'COMMENTAIRE'});

	$cde_rubis{"$client/$cde"} = COMMANDE ;
	$cde_rubis_ligne{"$client/$cde/$ligne"} = COMMANDE ;
} # fin while reflex

#print Dumper(\%cde_rubis);



AVOIR:
################# SELECT DES RECEPTIONS REFLEX ########################################################
printf "%s Select des receptions reflex\n",get_time(); $old_time=time;
my $sql_reflex = <<EOT ;
select
	RERREC as REFERENCE_COMMANDE,R1NLRR as REFERENCE_LIGNE
--	PREPA_ENTETE.PESSOR,PREPA_ENTETE.PEASOR,PREPA_ENTETE.PEMSOR,PREPA_ENTETE.PEJSOR, -- date de sortie de stock
--	COMMENTAIRE.COTXTC as COMMENTAIRE	-- texte commentaire
from	
				${prefix_base_reflex}.HLRECLP RECEPTION_DETAIL
	left join 	${prefix_base_reflex}.HLRECPP RECEPTION_ENTETE
		on			RECEPTION_DETAIL.R1NANN=RECEPTION_ENTETE.RENANN
			and 	RECEPTION_DETAIL.R1NREC=RECEPTION_ENTETE.RENREC
--	left join	${prefix_base_reflex}.HLCOMMP COMMENTAIRE
--		on		COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM
--			and 	COMMENTAIRE.COCFCO='ZZZ'
where	
		RECEPTION_DETAIL.R1TVLR='1'											-- ligne validé (receptionnée en totalité)
	and	RECEPTION_ENTETE.RETRVA='1'											-- validation de stock effectuée
	and RECEPTION_DETAIL.R1Q1SL=RECEPTION_DETAIL.R1QBSR						-- quantité à receptionner = quantité receptionné

	and RIGHT('0'+ CONVERT(VARCHAR,RESVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REAVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REMVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REJVAL),2) >= '$siecle_start$annee_start$mois_start$jour_start'
	and RIGHT('0'+ CONVERT(VARCHAR,RESVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REAVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REMVAL),2)+RIGHT('0'+ CONVERT(VARCHAR,REJVAL),2) <= '$siecle_end$annee_end$mois_end$jour_end'
EOT

#print $sql_reflex;exit;

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }

while ($reflex->FetchRow()) {
	my 	%row_reflex = $reflex->DataHash() ;
	my 	($client,$cde) = split(/[\/\-]/,$row_reflex{'REFERENCE_COMMANDE'});
	my 	$ligne = $row_reflex{'REFERENCE_LIGNE'} ;
		$ligne =~ s/000$//;	# supprime les 3 derniers '0' et 
		$ligne = sprintf('%03d', $ligne); # rajoute les leading 0

	# on ne garde que les avoirs
	if ($client =~ /^\d{6}$/) { # si le code client correspond a un code client MCS (056xxx)
		$cde_rubis{"$client/$cde"} = AVOIR ;
		$cde_rubis_ligne{"$client/$cde/$ligne"} = AVOIR ;
	}
} # fin while reflex

#print Dumper(\%cde_rubis);
#print Dumper(\%cde_rubis_ligne);



################# SELECT RUBIS ######################################################
# on recherche dans Rubis si les commandes sont bien livrées
my ($nb_error,$nb_error_annulee) = (0,0);
while(my($key,$val) = each %cde_rubis) {
	my ($client,$cde) = split(/\//,$key);

	printf "%s Select de la cde rubis %s\n",get_time(),$key; $old_time=time;
	my $sql_rubis = <<EOT ;
select
	ETSBE as ETAT,
	TRAIT as LIVRAISON,
	CLIENT.NOCLI as NUM_CLIENT,
	CLIENT.NOMCL NOM_CLIENT,
	NOBON as NUM_BON,
	QTESA as QTE,
	DS1DB as DESIGNATION1, DS2DB as DESIGNATION2, DS3DB as DESIGNATION3,
	NOLIG as LIGNE,
	CODAR as ARTICLE,
	USSBE as LAST_USER,
	CONCAT(DSBMS,CONCAT(DSBMA,CONCAT('-',CONCAT(DSBMM,CONCAT('-',DSBMJ))))) as LAST_MODIFICATION_DATE
from
				${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_BON
	left join 	${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
		on DETAIL_BON.NOCLI=CLIENT.NOCLI
where
		DETAIL_BON.PROFI='1'					-- pas les commentaires
	and DETAIL_BON.NOCLI='$client'
	and DETAIL_BON.NOBON='$cde'
EOT

	if ($rubis->Sql($sql_rubis))  { die "SQL Rubis cde failed : ", $rubis->Error(); }
	while ($rubis->FetchRow()) {
		my %row_rubis = $rubis->DataHash() ;
		
		if (	($row_rubis{'LIVRAISON'} eq 'R'		# la ligne n'est pas livré dans Rubis
			||	$row_rubis{'ETAT'} eq 'ANN')		# la ligne est suspendu dans Rubis
			&&	exists $cde_rubis_ligne{"$row_rubis{NUM_CLIENT}/$row_rubis{NUM_BON}/$row_rubis{LIGNE}"} # la ligne est marquée livrée dans Reflex
			) {
				print STDERR "\tLigne non livree dans Rubis $row_rubis{NUM_CLIENT}/$row_rubis{NUM_BON}/$row_rubis{LIGNE}\n";
				print CSV join(';',	$row_rubis{'ETAT'},
									$cde_rubis_ligne{"$row_rubis{NUM_CLIENT}/$row_rubis{NUM_BON}/$row_rubis{LIGNE}"} == COMMANDE ? 'COMMANDE':'AVOIR',
									$row_rubis{'NUM_CLIENT'},
									$row_rubis{'NOM_CLIENT'},
									$row_rubis{'NUM_BON'},
									$row_rubis{'LIGNE'},
									$row_rubis{'ARTICLE'},
									$row_rubis{'DESIGNATION1'},
									$row_rubis{'DESIGNATION2'},
									$row_rubis{'DESIGNATION3'},
									remove_useless_zero($row_rubis{'QTE'}),
									$row_rubis{'LAST_USER'},
									$row_rubis{'LAST_MODIFICATION_DATE'}
							)."\n";
				$nb_error++;
				if ($row_rubis{'ETAT'} eq 'ANN') {
					$nb_error_annulee++;
				}
		}
	} # fin while rubis
} # fin while cde_rubis


close(CSV);

if ($nb_error) {
	print 	"$nb_error erreur(s) constatee.\n".
			($nb_error - $nb_error_annulee)." erreur(s) non annulee constatee.\nDetail dans ".$OUTPUT_FILENAME."\n";
} else {
	unlink($OUTPUT_FILENAME) or die "Impossible de supprimer ".$OUTPUT_FILENAME;
}