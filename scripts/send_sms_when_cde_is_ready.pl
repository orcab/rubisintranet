#!/usr/bin/perl
use strict;

use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
use File::Path;
use File::Copy;
use File::Basename;
require 'Interfaces Rubis-Reflex/useful.pl'; # load get_time / second2hms / dot2comma
require 'Interfaces Rubis-Reflex/Phpconst2perlconst.pm';
use Phpconst2perlconst ;
use Getopt::Long;

use constant 'SEND_SMS' => 1;
use constant 'SEND_MAIL' => 0;

my ($test,$help);
GetOptions('test!'=>\$test,'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--test
	Exporte et importe depuis l'environnement de test
--usage ou --help
	Affiche ce message
EOT

########################################################################################
my $old_time = 0;
our $cfg 				= new Phpconst2perlconst(-file => 'Interfaces Rubis-Reflex/config.php');
my $prefix_base_rubis 	= $cfg->{'LOGINOR_PREFIX_BASE_'.($test ? 'TEST':'PROD')};
my $rubis 				= new Win32::ODBC('DSN='.$cfg->{'LOGINOR_DSN'}.';UID='.$cfg->{'LOGINOR_USER'}.';PWD='.$cfg->{'LOGINOR_PASS'}.';') or die "Ne peux pas se connecter à rubis";
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";
########################################################################################

printf "%s Select des prepa reflex\n",get_time(); $old_time=time;

my $today_yyyymmdd = strftime('%Y%m%d', localtime);
my %today = (	'siecle'=>substr($today_yyyymmdd,0,2),
				'annee'	=>substr($today_yyyymmdd,2,2),
				'mois'	=>substr($today_yyyymmdd,4,2),
				'jour'	=>substr($today_yyyymmdd,6,2)
	);

# liste des commande "DIS" valider depuis moins d'une x minutes
my $sql_reflex = <<EOT ;
select
RIGHT('0'+ CONVERT(VARCHAR,PESVPP),2) + RIGHT('0'+ CONVERT(VARCHAR,PEAVPP),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEMVPP),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEJVPP),2)+' '+STR(FLOOR( PEHVPP/10000), 2, 0)  + ':' + RIGHT(STR(FLOOR( PEHVPP/100), 6, 0), 2) + ':' + RIGHT(STR( PEHVPP), 2) as VALIDATION_DATETIME,
RIGHT('0'+ CONVERT(VARCHAR,PESCRE),2) + RIGHT('0'+ CONVERT(VARCHAR,PEACRE),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEMCRE),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEJCRE),2)+' '+STR(FLOOR( PEHCRE/10000), 2, 0)  + ':' + RIGHT(STR(FLOOR( PEHCRE/100), 6, 0), 2) + ':' + RIGHT(STR( PEHCRE), 2) as CREATION_DATETIME,
	ODP_ENTETE.OECMOP as TYPE_CDE,
--	PREPA_ENTETE.PENANN as PREPA_ANNEE,
--	PREPA_ENTETE.PENPRE as PREPA_NUMERO,
	PREPA_ENTETE.PECDES as CODE_DESTINATAIRE,
	COMMENTAIRE.COTXTC as NUM_PORTABLE,
	ODP_ENTETE.OERODP as REFERENCE_ODP,
	ODP_ENTETE.OERODD as REFERENCE_DESTINATAIRE,
	(select TOP 1 (EMC1EM+' '+EMC2EM+' '+EMC3EM+' '+EMC4EM+' '+EMC5EM)
		from RFXPRODDTA.reflex.HLGESOP GEI_SORTIS
			left join RFXPRODDTA.reflex.HLSUSOP SUPPORT_SORTIS
				on GEI_SORTIS.GSNSUP=SUPPORT_SORTIS.SXNSUP
			left join RFXPRODDTA.reflex.HLEMPLP EMPLACEMENTS
				on EMPLACEMENTS.EMNEMP=SUPPORT_SORTIS.SXNEMP
		where PREPA_ENTETE.PENANN=GEI_SORTIS.GSNAPP and PREPA_ENTETE.PENPRE=GEI_SORTIS.GSNPRE
	) as EMPLACEMENT_SUPPORT_SORTIS
from
				RFXPRODDTA.reflex.HLPRENP PREPA_ENTETE
	left join 	RFXPRODDTA.reflex.HLPRPLP PREPA_DETAIL
		on PREPA_ENTETE.PENANN=PREPA_DETAIL.P1NANP and PREPA_ENTETE.PENPRE=PREPA_DETAIL.P1NPRE
	left join RFXPRODDTA.reflex.HLODPEP ODP_ENTETE
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
	left join RFXPRODDTA.reflex.HLDESTP DESTINATAIRE
		on PREPA_ENTETE.PECDES=DESTINATAIRE.DSCDES
	left join RFXPRODDTA.reflex.HLCOMMP COMMENTAIRE
		on COMMENTAIRE.COCFIC='HLDESTP' and COMMENTAIRE.COCFCO='TE1' and COMMENTAIRE.CONCOM=DESTINATAIRE.DSNCOM

where
(select SUM(P1QAPR - P1NQAM) from RFXPRODDTA.reflex.HLPRPLP where PENPRE=P1NPRE)>0
and ODP_ENTETE.OECMOP='DIS'	-- un dispo comptoir
and PESVPP='$today{siecle}' and PEAVPP='$today{annee}' and PEMVPP='$today{mois}' and PEJVPP='$today{jour}'	-- validation dans la journée
and PECDES<>'056039'		-- pas de la CAB56
and DATEDIFF(MINUTE,
		RIGHT('0'+ CONVERT(VARCHAR,PESVPP),2) + RIGHT('0'+ CONVERT(VARCHAR,PEAVPP),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEMVPP),2) +'-'+ RIGHT('0'+ CONVERT(VARCHAR,PEJVPP),2)+
		' '+
		STR(FLOOR( PEHVPP/10000), 2, 0)  + ':' + RIGHT(STR(FLOOR( PEHVPP/100), 6, 0), 2) + ':' + RIGHT(STR( PEHVPP), 2)
, GETDATE()) < 10	-- validée depuis moins de x minutes

group by PESVPP,PEAVPP,PEMVPP,PEJVPP,PEHVPP,PENANN,PENPRE,PEHCRE,PECDES,OERODP,ODP_ENTETE.OECMOP,PESCRE,PEACRE,PEMCRE,PEJCRE,COTXTC,OERODD
EOT

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }
while ($reflex->FetchRow()) {
	my %row = $reflex->DataHash() ;

	#print Dumper(\%row);

	my @reference_odp = split(/[\/\-]/,$row{'REFERENCE_ODP'});
	#print Dumper(\@reference_odp);
	my $text = "Votre commande $reference_odp[1]\nReference : $row{REFERENCE_DESTINATAIRE} est prete.\nVous pouvez venir la retirer a Plescop.\nL'equipe MCS";
	
	#print $text."\n";

	# here we can't send info to customers about a finish prepa
	if (SEND_SMS && $row{'NUM_PORTABLE'} && sendSMS($row{'NUM_PORTABLE'},$text,$cfg->{'SMS_GATEWAY'})) {
		printf "%s SMS send to %s (%s)\n",get_time(),$row{'CODE_DESTINATAIRE'},$row{'NUM_PORTABLE'}; $old_time=time;
	}
}

print "END\n";