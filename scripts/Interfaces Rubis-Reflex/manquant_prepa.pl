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

if (length($date)>0) { # aucune date de spécifié --> on prend le dernier jour ouvré
	($siecle,$annee,$mois,$jour) 	= ($date =~ m/^(\d{2})(\d{2})-(\d{2})-(\d{2})$/);
}
########################################################################################

printf "%s Select des articles pour le $siecle$annee-$mois-$jour\n",get_time();	$old_time=time;

=begin
# methode qui se base sur les ligne de prépa validé
# parfois les prépa sont validé apres que le programme soit lancé, du coup les lignes n'apparaissent pas
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
	and (	(P1NNSL>0 and P1RRSO='')	-- avec des manquant au lancement sans réservation
			OR 
		 	(P1QPRE<P1QAPR AND P1NNSL=0)	-- quantité préparée inférieur a quantité demandée
		)
ORDER BY CODE_ARTICLE ASC
EOT
=cut

=begin
# methode qui se base sur les missions validées
my $sql_reflex = <<EOT ;
SELECT 	DISTINCT(P1CART) as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE, P1NANP as ANNEE_PREPA, P1NPRE as NUM_PREPA ,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE,
		COMMENTAIRE.COTXTC as COMMENTAIRE_ZZZ

FROM 	${prefix_base_reflex}.HLPRPLP PREPA_DETAIL

		left join ${prefix_base_reflex}.HLPLLPP PRELEVEMENT
 			on PREPA_DETAIL.P1NANN=PRELEVEMENT.PPNANP and PREPA_DETAIL.P1NLPR=PRELEVEMENT.PPNLPR
		left join ${prefix_base_reflex}.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join ${prefix_base_reflex}.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join ${prefix_base_reflex}.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
		left join ${prefix_base_reflex}.HLCOMMP COMMENTAIRE
			on COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM and COMMENTAIRE.COCFCO='ZZZ'

where	P1SSCA='$siecle' and P1ANCA='$annee' and P1MOCA='$mois' and P1JOCA='$jour' --prepa du jour
	and(	
		(P1NNSL>0 and P1RRSO='')	-- avec des manquant au lancement sans réservation
		or
		(
			P1QPRE<P1QAPR AND P1NNSL=0    	-- quantité préparée inférieur a quantité demandée
			and
			PRELEVEMENT.PPTTVM=1 and PRELEVEMENT.PPQPPL<PRELEVEMENT.PPQAPL --mission validé et prevelement inferieur a qte demandée
		)
	)

ORDER BY CODE_ARTICLE ASC
EOT
=cut

# header du message
my $heure = strftime('%H:%M:%S', localtime);
my $message = <<EOT ;
<html>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<head>
<style>
.qte,.resa,.client,.class { text-align:center; }
.not-important { color:B5B5B5; }
</style>
</head>
<body>
<h3>Voici les articles manquant &agrave; la pr&eacute;paration Reflex le $jour/$mois/$annee &agrave; $heure</h3>
EOT


# article ayant du stock mais dont le prélévément était insuffisant
my $sql_reflex = <<EOT ;
SELECT  DISTINCT(P1CART) as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE,
		(cast(P1NANP as varchar) + '-' + cast(P1NPRE as varchar)) as NUM_PREPA,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE,
		COMMENTAIRE.COTXTC as COMMENTAIRE_ZZZ,
		PREPA_DETAIL.P1RRSO as RESERVATION,
		(select SUM(RECEPTION_LIGNE_DETAIL.R1Q1SL) from 
			${prefix_base_reflex}.HLRECPP RECEPTION_ENTETE			
			left join ${prefix_base_reflex}.HLRECLP RECEPTION_LIGNE_DETAIL
				on 	RECEPTION_LIGNE_DETAIL.R1NANN=RECEPTION_ENTETE.RENANN and RECEPTION_LIGNE_DETAIL.R1NREC=RECEPTION_ENTETE.RENREC 
					and RECEPTION_LIGNE_DETAIL.R1CART=PREPA_DETAIL.P1CART and RECEPTION_LIGNE_DETAIL.R1TVLR=0
			where RECEPTION_ENTETE.RECTRC = '010' and RECEPTION_ENTETE.RETRVA=0
		) as QTE_EN_RECEPTION

FROM 	${prefix_base_reflex}.HLPRPLP PREPA_DETAIL

		left join ${prefix_base_reflex}.HLPLLPP PRELEVEMENT
			on PREPA_DETAIL.P1NANN=PRELEVEMENT.PPNANP and PREPA_DETAIL.P1NLPR=PRELEVEMENT.PPNLPR
		left join ${prefix_base_reflex}.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join ${prefix_base_reflex}.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join ${prefix_base_reflex}.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
		left join ${prefix_base_reflex}.HLCOMMP COMMENTAIRE
			on COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM and COMMENTAIRE.COCFCO='ZZZ'
where	
		P1SSCA='$siecle' and P1ANCA='$annee' and P1MOCA='$mois' and P1JOCA='$jour' --prepa du jour
	and P1QPRE<P1QAPR AND P1NNSL=0    									-- quantité préparée inférieur a quantité demandée
	and	PRELEVEMENT.PPTTVM=1 and PRELEVEMENT.PPQPPL<PRELEVEMENT.PPQAPL 	--mission validé et prevelement inferieur a qte demandée

order by RESERVATION asc, CODE_ARTICLE asc
EOT
$message .= draw_table("Article ayant fait l'objet d'une mission incompl&egrave;te",$sql_reflex);


# article n'ayant pas de stock au moment de la mission (vrai manquant)
$sql_reflex = <<EOT ;
SELECT 	DISTINCT(P1CART) as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE,
		(cast(P1NANP as varchar) + '-' + cast(P1NPRE as varchar)) as NUM_PREPA,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE,
		COMMENTAIRE.COTXTC as COMMENTAIRE_ZZZ,
		PREPA_DETAIL.P1RRSO as RESERVATION,

		(select SUM(RECEPTION_LIGNE_DETAIL.R1Q1SL) from 
			${prefix_base_reflex}.HLRECPP RECEPTION_ENTETE			
			left join ${prefix_base_reflex}.HLRECLP RECEPTION_LIGNE_DETAIL
				on 	RECEPTION_LIGNE_DETAIL.R1NANN=RECEPTION_ENTETE.RENANN and RECEPTION_LIGNE_DETAIL.R1NREC=RECEPTION_ENTETE.RENREC 
					and RECEPTION_LIGNE_DETAIL.R1CART=PREPA_DETAIL.P1CART and RECEPTION_LIGNE_DETAIL.R1TVLR=0
			where RECEPTION_ENTETE.RECTRC = '010' and RECEPTION_ENTETE.RETRVA=0
		) as QTE_EN_RECEPTION

FROM 	${prefix_base_reflex}.HLPRPLP PREPA_DETAIL

		left join ${prefix_base_reflex}.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join ${prefix_base_reflex}.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join ${prefix_base_reflex}.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
		left join ${prefix_base_reflex}.HLCOMMP COMMENTAIRE
			on COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM and COMMENTAIRE.COCFCO='ZZZ'
where	
		P1SSCA='$siecle' and P1ANCA='$annee' and P1MOCA='$mois' and P1JOCA='$jour' --prepa du jour
	and P1NNSL>0 					-- avec des manquant au lancement 
	and P1RRSO=''					-- sans réservation

order by RESERVATION asc, CODE_ARTICLE asc
EOT
$message .= draw_table("Article n'ayant pas de stock au moment du lancement",$sql_reflex);

#print $sql_reflex;exit;

# footer du message
$message .= "</body></html>\n";

if (!$noemail) {
	printf "%s Envoi email\n",get_time();	$old_time=time;

	my $to_list = {};

	if (strftime('%H',localtime) eq '13') { #liste de 13h30
		$to_list = {	'benjamin.poulain@coopmcs.com' 	=> 	'Benjamin Poulain',
						'francois.dore@coopmcs.com' 	=> 	'Francois Dore',
						'emmanuel.cheriaux@coopmcs.com' => 	'Emmanuel Cheriaux',
						'bernard.taverson@coopmcs.com'	=>	'Bernard Taverson',
						'regis.lefloch@coopmcs.com'		=>	'Regis Le Floch',
						'jeremy.morice@coopmcs.com'		=>	'Jemery Morice',
						'pierrick.boillet@coopmcs.com'	=>	'Pierrick Boillet',
						'emmanuel.lemab@coopmcs.com'	=>	'Emmanuel Le Mab',
						'aymeric.merigot@coopmcs.com' 	=> 	'Aymeric Merigot',
						'stephane.leneveu@coopmcs.com' 	=> 	'Stephane Le Neveu'
					};
	} else {		#liste de 16h30
		$to_list = {	'bernard.taverson@coopmcs.com'	=>	'Bernard Taverson',
						'emmanuel.lemab@coopmcs.com'	=>	'Emmanuel Le Mab',
						'regis.lefloch@coopmcs.com'		=>	'Regis Le Floch',
						'jeremy.morice@coopmcs.com'		=>	'Jemery Morice',
						'pierrick.boillet@coopmcs.com'	=>	'Pierrick Boillet',
						'benjamin.poulain@coopmcs.com' 	=> 	'Benjamin Poulain',
						'aymeric.merigot@coopmcs.com' 	=> 	'Aymeric Merigot',
						'francois.dore@coopmcs.com' 	=> 	'Francois Dore',
						'emmanuel.cheriaux@coopmcs.com' => 	'Emmanuel Cheriaux',
						'xavier.ledoussal@coopmcs.com' 	=> 	'Xavier Le Doussal',
						'stephane.leneveu@coopmcs.com' 	=> 	'Stephane Le Neveu'
					};
	}

	send_mail({
		'smtp_serveur'	=> $cfg->{'SMTP_SERVEUR'},
		'smtp_user'		=> $cfg->{'SMTP_USER'},
		'smtp_password'	=> $cfg->{'SMTP_PASS'},
		'smtp_port'		=> $cfg->{'SMTP_PORT'},
		'from_email' 	=> 'reflex@coopmcs.com',
		'from_name' 	=> 'Manquant prepa reflex',
		'subject'		=> "Manquant a la preparation Reflex du $jour/$mois/$annee",
		'message'		=> $message,
		'html'			=> 1,
		'to'			=> $to_list
	}) or die "Impossible d'envoyer le mail";

 } else {
 	print $message;
 }



 ############################################################################################

 sub draw_table($$) {
 	my ($titre,$sql_reflex) = @_;

 	my $message .= <<EOT ;
<table border="1" cellspacing="0" cellpadding="0" style="margin-bottom:2em;">
	<caption style="font-size:1.2em;font-weight:bold;">$titre</caption>
	<tr>
		<th>Code article</th>
		<th>Designation 1</th>
		<th>Designation 2</th>
		<th>Qt&eacute; &agrave; pr&eacute;parer</th>
		<th>Qt&eacute; pr&eacute;par&eacute;e</th>
		<th>No pr&eacute;pa</th>
		<th>R&eacute;f&egrave;rence ODP</th>
		<th>Code client</th>
		<th>Client</th>
		<th nowrap="nowrap">R&eacute;sa ?</th>
		<th nowrap="nowrap">Qte en<br/>Recep</th>
		<th nowrap="nowrap">Class</th>
	</tr>
EOT
 	die "SQL Reflex GEI failed : ".$reflex->Error() if $reflex->Sql($sql_reflex) ;

	while ($reflex->FetchRow()) {
		my 	%row_reflex = $reflex->DataHash() ;
		my 	($client,$cde,$noligne) = split(/[\/\-]/,$row_reflex{'COMMENTAIRE_ZZZ'});

		# regarde si la ligne est toujours active dans rubis
		my $sql_rubis = <<EOT ;
select
	ETSBE as ETAT,TRAIT as LIVRAISON, CLIENT.CATCL as CATGEORIE_CLIENT,QTESA as QTE_DEMANDEE,STCLA as CLASS
from
	${prefix_base_rubis}GESTCOM.ADETBOP1 DETAIL_PREPA
	left join ${prefix_base_rubis}GESTCOM.ACLIENP1 CLIENT
		on DETAIL_PREPA.NOCLI=CLIENT.NOCLI
	left join ${prefix_base_rubis}GESTCOM.ASTOFIP1 FICHE_STOCK
		on DETAIL_PREPA.CODAR=FICHE_STOCK.NOART and FICHE_STOCK.DEPOT='AFA'
where
	NOBON='$cde' and DETAIL_PREPA.NOCLI='$client' and NOLIG='$noligne'
EOT
		die "SQL Rubis cde failed : ".$rubis->Error() if $rubis->Sql($sql_rubis) ;
		$rubis->FetchRow();
		my %row_rubis = $rubis->DataHash();
		#print STDERR "DEBUG ".$row_reflex{'COMMENTAIRE_ZZZ'}.' / etat='.$row_rubis{'ETAT'}."\n";

		if ($row_rubis{'ETAT'} ne '') { next ; }
		if ($row_rubis{'LIVRAISON'} eq 'F' && $row_reflex{'QTE_PREPAREE'} >= $row_rubis{'QTE_DEMANDEE'}) { next ; }

		#if ($row_rubis{'ETAT'} eq '' && ($row_rubis{'LIVRAISON'} eq 'R')) { # ligne non supprimée et non livrée
		$row_reflex{'DESIGNATION'} =~ s/[^A-Z0-9 \n\r,\-\+\?_:<>\[\]\{\}\(\)=\.\/\\*%\^\~\#°\'¨³²\$&µÖÜÏËÉÊÈÙÛÄÀÂÎÔÒÇØ¼½öüïëéêèùûäàâîôòç]+/ /ig;
		$row_reflex{'DESIGNATION2'} =~ s/[^A-Z0-9 \n\r,\-\+\?_:<>\[\]\{\}\(\)=\.\/\\*%\^\~\#°\'¨³²\$&µÖÜÏËÉÊÈÙÛÄÀÂÎÔÒÇØ¼½öüïëéêèùûäàâîôòç]+/ /ig;
		$message .= "<tr class='".( $row_rubis{'CATGEORIE_CLIENT'} ne '1' ? 'not-important':'')."'><td>$row_reflex{CODE_ARTICLE}</td>\n<td>$row_reflex{DESIGNATION}</td>\n<td>$row_reflex{DESIGNATION2}</td>\n<td class='qte'>$row_reflex{QTE_A_PREPARER}</td>\n<td class='qte'>$row_reflex{QTE_PREPAREE}</td>\n<td>$row_reflex{NUM_PREPA}</td>\n<td>$row_reflex{REFERENCE_OPD}</td>\n<td class='client'>$row_reflex{CODE_DEST}</td>\n<td>$row_reflex{DESTINATAIRE}</td>\n<td class='resa'>".($row_reflex{'RESERVATION'} ? 'OUI':'&nbsp;')."</td><td>".($row_reflex{'QTE_EN_RECEPTION'} ? $row_reflex{'QTE_EN_RECEPTION'}:'&nbsp;')."</td>\n<td class='class'>$row_rubis{CLASS}</td></tr>\n";
		#}
	} # fin while reflex

	$message .= "</table>";
	return $message;
 }