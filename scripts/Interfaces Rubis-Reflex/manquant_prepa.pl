#!perl

use strict;
use Data::Dumper;
use Win32::ODBC;
use POSIX qw(strftime);
use DateTime;
require 'Phpconst2perlconst.pm';
require 'useful.pl'; # load get_time / second2hms
use Phpconst2perlconst ;
use Getopt::Long;
use Net::SMTP;
use constant FROM_EMAIL	=> 'reflex@coopmcs.com';
use constant FROM_NAME	=> 'Manquant prepa reflex';
my @TO_EMAIL	= ('bernard.taverson@coopmcs.com','regis.lefloch@coopmcs.com','jeremy.morice@coopmcs.com','claude.kergosien@coopmcs.com');
my @TO_NAME		= ('Bernard Taverson','Regis Le Floch','Jemery Morice','Claude Kergosien');
#my @TO_EMAIL	= ('benjamin.poulain@coopmcs.com');
#my @TO_NAME		= ('Benjamin Poulain');

$|=1;
my ($siecle,$annee,$mois,$jour) = (	substr(strftime('%Y',localtime),0,2),
									substr(strftime('%Y',localtime),2,2),
									strftime('%m',localtime),
									strftime('%d',localtime)
								);

my ($test,$date,$help);
GetOptions('test!'=>\$test, 'date:s'=>\$date , 'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--test
	Exporte et importe depuis l'environnement de test

--date=yyyy-mm-dd
	Date des manquant à la pr&eacute;paration (aujourd'hui par d&eacute;faut)

--usage ou --help
	Affiche ce message
EOT


########################################################################################
my $cfg 				= new Phpconst2perlconst(-file => 'config.php');
my $prefix_base_reflex 	= $test ? $cfg->{'REFLEX_PREFIX_BASE_TEST'} : $cfg->{'REFLEX_PREFIX_BASE'};
my $reflex 				= new Win32::ODBC('DSN='.$cfg->{'REFLEX_DSN'}.';UID='.$cfg->{'REFLEX_USER'}.';PWD='.$cfg->{'REFLEX_PASS'}.';') or die "Ne peux pas se connecter à REFLEX";
########################################################################################

################# DATE DE TRAVAIL ######################################################
if (length($date)>0 && $date !~ m/^\d{4}-\d{2}-\d{2}$/)  {
	die "Le format de date '$date' n'est pas du style yyyy-mm-dd";
}

if (length($date)<=0) { # aucune date de sp&eacute;cifi&eacute; --> on prend le denrier jour ouvr&eacute;
	print "Aucune date de specifiee. Aujourd'hui\n\t--date=$siecle$annee-$mois-$jour\n";
} else {
	($siecle,$annee,$mois,$jour) 	= ($date =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
}
########################################################################################


my $sql = <<EOT ;
SELECT 	P1CART as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE, P1NANP as ANNEE_PREPA, P1NPRE as NUM_PREPA ,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE
FROM 	${prefix_base_reflex}.HLPRPLP PREPA_DETAIL
		left join ${prefix_base_reflex}.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join ${prefix_base_reflex}.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join ${prefix_base_reflex}.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
WHERE 	P1QPRE<P1QAPR AND P1NNSL=0
	and P1SSCA='$siecle' and P1ANCA='$annee' and P1MOCA='$mois' and P1JOCA='$jour'
	and P1TVLP=1 --prepa validée
ORDER BY P1CART ASC
EOT

#print $sql;

my $message = <<EOT ;
<html>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<body>
<table border="1" cellspacing="0" cellpadding="0">
	<tr>
		<th>Code article</th>
		<th>Designation 1</th>
		<th>Designation 2</th>
		<th>Qt&eacute; &agrave; pr&eacute;parer</th>
		<th>Qt&eacute; pr&eacute;par&eacute;e</th>
		<th>Ann&eacute;e pr&eacute;pa</th>
		<th>No pr&eacute;pa</th>
		<th>R&eacute;f&egrave;rence ODP</th>
		<th>Code client</th>
		<th>Client</th>
	</tr>
EOT

if ($reflex->Sql($sql))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }
while ($reflex->FetchRow()) {
	my 	%row = $reflex->DataHash() ;
	#my 	($client,$cde) = split(/[\/\-]/,$row_reflex{'REFERENCE_OPD'});
	$message .= "<tr><td>$row{CODE_ARTICLE}</td><td>$row{DESIGNATION}</td><td>$row{DESIGNATION2}</td><td>$row{QTE_A_PREPARER}</td><td>$row{QTE_PREPAREE}</td><td>$row{ANNEE_PREPA}</td><td>$row{NUM_PREPA}</td><td>$row{REFERENCE_OPD}</td><td>$row{CODE_DEST}</td><td>$row{DESTINATAIRE}</td></tr>\n";
} # fin while reflex

$message .= "</table></body></html>\n";

my 	$smtp = Net::SMTP->new($cfg->{'SMTP_SERVEUR'}) or die "Pas de connexion SMTP a ".$cfg->{'SMTP_SERVEUR'}.": $!\n";
	$smtp->auth($cfg->{'SMTP_USER'},$cfg->{'SMTP_PASS'} );
 	$smtp->mail(FROM_EMAIL);
 	$smtp->to(@TO_EMAIL);

 	$smtp->data();
 	$smtp->datasend('To: '.$TO_NAME[0].' <'.$TO_EMAIL[0].">\n");
 	$smtp->datasend('From: '.FROM_NAME.' <'.FROM_EMAIL.">\n");
 	$smtp->datasend("Subject: Manquant à la préparation Reflex du $jour/$mois/$annee\n");
 	$smtp->datasend("MIME-Version: 1.0\n");
 	$smtp->datasend("Content-Type: multipart/mixed; boundary=\"frontier\"\n");
 	$smtp->datasend("\n--frontier\n");
 	$smtp->datasend("Content-Type: text/html; charset=\"iso-8859-1\" \n");
 	$smtp->datasend("\n");
 	$smtp->datasend("<h3>Voici les articles manquant &agrave; la pr&eacute;paration Reflex le $jour/$mois/$annee &agrave; ".strftime('%H:%M:%S', localtime)."</h3>\n\n");
 	$smtp->datasend($message);
 	$smtp->datasend("--frontier--\n");
 	$smtp->dataend();

 	$smtp->quit;

#print $message;