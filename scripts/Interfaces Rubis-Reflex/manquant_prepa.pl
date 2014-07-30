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

$|=1;
my ($siecle,$annee,$mois,$jour) = (	substr(strftime('%Y',localtime),0,2),
									substr(strftime('%Y',localtime),2,2),
									strftime('%m',localtime),
									strftime('%d',localtime)
								);

my ($test,$date,$noemail,$help);
GetOptions('test!'=>\$test, 'date:s'=>\$date , 'noemail!'=>\$noemail, 'help|usage!'=>\$help) ;
die <<EOT if ($help);
Liste des arguments :
--test
	Exporte et importe depuis l'environnement de test

--date=yyyy-mm-dd
	Date des manquant à la pr&eacute;paration (aujourd'hui par d&eacute;faut)

--noemail
	N'envoi pas l'email

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
########################################################################################

################# DATE DE TRAVAIL ######################################################
if (length($date)>0 && $date !~ m/^\d{4}-\d{2}-\d{2}$/)  {
	die "Le format de date '$date' n'est pas du style yyyy-mm-dd";
}

if (length($date)<=0) { # aucune date de sp&eacute;cifi&eacute; --> on prend le denrier jour ouvr&eacute;
	#print "Aucune date de specifiee. Aujourd'hui\n\t--date=$siecle$annee-$mois-$jour\n";
} else {
	($siecle,$annee,$mois,$jour) 	= ($date =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
}
########################################################################################

printf "%s Select des articles pour le $siecle$annee-$mois-$jour\n",get_time();	$old_time=time;

my $sql_reflex = <<EOT ;
SELECT 	P1CART as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE, P1NANP as ANNEE_PREPA, P1NPRE as NUM_PREPA ,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE,
		COMMENTAIRE.COTXTC as COMMENTAIRE_ZZZ

FROM 	${prefix_base_reflex}.HLPRPLP PREPA_DETAIL
		left join ${prefix_base_reflex}.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join ${prefix_base_reflex}.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join ${prefix_base_reflex}.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
		left join ${prefix_base_reflex}.HLCOMMP COMMENTAIRE
			on COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM and COMMENTAIRE.COCFCO='ZZZ'

WHERE 	P1SSCA='$siecle' and P1ANCA='$annee' and P1MOCA='$mois' and P1JOCA='$jour' --prepa du jour
	and P1TVLP=1 --prepa validée
	and P1QPRE<P1QAPR --la qte preparée est inférieur à la qte demandée
	and P1NNSL=0 --la qte non servi au lancement

ORDER BY P1CART ASC
EOT

#print $sql_reflex;exit;

my $heure = strftime('%H:%M:%S', localtime);
my $message = <<EOT ;
<html>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<body>
<h3>Voici les articles manquant &agrave; la pr&eacute;paration Reflex le $jour/$mois/$annee &agrave; $heure</h3>
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

if ($reflex->Sql($sql_reflex))  { die "SQL Reflex GEI failed : ", $reflex->Error(); }
while ($reflex->FetchRow()) {
	my 	%row_reflex = $reflex->DataHash() ;
	my 	($client,$cde,$noligne) = split(/[\/\-]/,$row_reflex{'COMMENTAIRE_ZZZ'});

	my $sql_rubis = "select ETSBE as ETAT from ${prefix_base_rubis}GESTCOM.ADETBOP1 where NOBON='$cde' and NOCLI='$client' and NOLIG='$noligne'";
	if ($rubis->Sql($sql_rubis))  { die "SQL Rubis cde failed : ", $rubis->Error(); }
	$rubis->FetchRow();
	my %row_rubis = $rubis->DataHash();
	#print STDERR "DEBUG ".$row_reflex{'COMMENTAIRE_ZZZ'}.' / etat='.$row_rubis{'ETAT'}."\n";
	if ($row_rubis{'ETAT'} eq '') { # ligne non supprimée
		$message .= "<tr><td>$row_reflex{CODE_ARTICLE}</td><td>$row_reflex{DESIGNATION}</td><td>$row_reflex{DESIGNATION2}</td><td>$row_reflex{QTE_A_PREPARER}</td><td>$row_reflex{QTE_PREPAREE}</td><td>$row_reflex{ANNEE_PREPA}</td><td>$row_reflex{NUM_PREPA}</td><td>$row_reflex{REFERENCE_OPD}</td><td>$row_reflex{CODE_DEST}</td><td>$row_reflex{DESTINATAIRE}</td></tr>\n";
	}
} # fin while reflex

$message .= "</table></body></html>\n";

if (!$noemail) {
	printf "%s Envoi email\n",get_time();	$old_time=time;
	send_mail({
		'smtp_serveur'	=> $cfg->{'SMTP_SERVEUR'},
		'smtp_user'		=> $cfg->{'SMTP_USER'},
		'smtp_password'	=> $cfg->{'SMTP_PASS'},
		'from_email' 	=> 'reflex@coopmcs.com',
		'from_name' 	=> 'Manquant prepa reflex',
		'subject'		=> "Manquant à la préparation Reflex du $jour/$mois/$annee",
		'message'		=> $message,
		'html'			=> 1,
		'to'			=> {	'bernard.taverson@coopmcs.com'	=>	'Bernard Taverson',
								'emmanuel.lemab@coopmcs.com'	=>	'Emmanuel Le Mab',
								'regis.lefloch@coopmcs.com'		=>	'Regis Le Floch',
								'jeremy.morice@coopmcs.com'		=>	'Jemery Morice',
								'claude.kergosien@coopmcs.com'	=>	'Claude Kergosien',
								'benjamin.poulain@coopmcs.com' 	=> 	'Benjamin Poulain'
							}

	}) or die "Impossible d'envoyer le mail";

 } else {
 	print $message;
 }