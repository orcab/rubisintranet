<?php
include('../../../inc/config.php');
session_start();
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Ligne en &eacute;cart</title>

<style>

body {
    font-family: verdana;
    font-size: 0.8em;
}
table {
    width: 100%;
    border-collapse: collapse;
    border-spacing: 0;
}
td,th {
    border: solid 1px #ccc;
    text-align:center;
}
th {
    background-color: black;
    color: white;
}

td.designation,td.destinataire {
	text-align: left;
}
</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../../js/jquery.js"></script>
<script type="text/javascript" src="../../../js/jquery-ui-1.10.3.custom.min.js"></script>
</head>
<body>

<?php
if(!isset($_GET['type']) || strlen($_GET['type'])<1)
	die("Impossible de trouver le type");

if 		($_GET['type']==1)
	$where_type = "and P1NNSL > 0";
elseif 	($_GET['type']==2)
	$where_type = "and P1QPRE < P1QAPR and P1NNSL=0";

if(!isset($_SESSION['where']) || strlen($_SESSION['where'])<1)
	die("Impossible de trouver la session where");

if(!isset($_GET['date']) || strlen($_GET['date'])<1)
	die("Impossible de trouver la date");

$where = str_replace('!date-chargement!', $_GET['date'], $_SESSION['where']);
//$where = str_replace('date-chargement', $_GET['date'], $_SESSION['where']);

$sql = <<<EOT
select
	(cast(P1NANP as varchar) + '-' + cast(P1NPRE as varchar)) as NUM_PREPA,
	P1CART as CODE_ARTICLE,
	P1QAPR as QTE_A_PREPARER,
	P1QPRE as QTE_PREPAREE,
	ARLART as DESIGNATION,
	ARMDAR as DESIGNATION2,
--	OERODP as REFERENCE_OPD,
	P1CDES as CODE_DEST,
	DSLDES as DESTINATAIRE,
	COMMENTAIRE.COTXTC as COMMENTAIRE_ZZZ,
	PREPA_DETAIL.P1RRSO as RESERVATION,
	ARTICLE_FAMILLE.A2CFAR as CLASS
from
	RFXPRODDTA.reflex.HLPRPLP PREPA_DETAIL
	left join ${REFLEX_BASE}.HLODPEP ODP_ENTETE
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
	left join ${REFLEX_BASE}.HLARTIP ARTICLE
		on PREPA_DETAIL.P1CART=ARTICLE.ARCART
	left join ${REFLEX_BASE}.HLDESTP DEST
		on PREPA_DETAIL.P1CDES=DEST.DSCDES
	left join ${REFLEX_BASE}.HLCOMMP COMMENTAIRE
		on COMMENTAIRE.CONCOM=PREPA_DETAIL.P1NCOM and COMMENTAIRE.COCFCO='ZZZ'
	left join ${REFLEX_BASE}.HLPRENP PREPA_ENTETE
		on PREPA_DETAIL.P1NANP=PREPA_ENTETE.PENANN and PREPA_DETAIL.P1NPRE=PREPA_ENTETE.PENPRE
	left join ${REFLEX_BASE}.HLCDFAP ARTICLE_FAMILLE
		on PREPA_DETAIL.P1CART=ARTICLE_FAMILLE.A2CART and ARTICLE_FAMILLE.A2CFAN='CLASSE'
where
--start session where
	$where
--end session where
	$where_type
	and PREPA_DETAIL.P1TOPD=0
	and PREPA_ENTETE.PETOPD=0
order by CODE_ARTICLE ASC
EOT;

//echo "<pre>$sql</pre>";

$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
$res = odbc_exec($reflex,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
?>

<table id="diff">
	<thead>
		<tr>
			<th class="indice">#</th>
			<th class="num_prepa">NUM PREPA</th>
			<th class="code_article">CODE_ARTICLE</th>
			<th class="designation">DESIGNATION</th>
			<th class="qte_a_preparer">QTE A PREPARER</th>
			<th class="qte_preparee">QTE PREPAREE</th>
<!--			<th class="ref_odp">REF ODP</th> -->
			<th class="destinataire">CLIENT</th>
			<th class="commande">COMMANDE</th>
			<th class="reservation">RESA</th>
			<th class="class">CLASS</th>
		<tr>
	</thead>
	<tbody>
<?
//select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENANN=P1NANO and OENODP=P1NODP and P1NNSL=0      $reservation
$i=0;
while($row = odbc_fetch_array($res)) { ?>
	<tr>
		<td class="indice"><?=++$i?></td>
		<td class="num_prepa"><?=$row['NUM_PREPA']?></td>
		<td class="code_article"><?=$row['CODE_ARTICLE']?></td>
		<td class="designation"><?=$row['DESIGNATION']?><br/><?=$row['DESIGNATION2']?></td>
		<td class="qte_a_preparer"><?=$row['QTE_A_PREPARER']?></td>
		<td class="qte_preparee"><?=$row['QTE_PREPAREE']?></td>
<!--		<td class="ref_odp"><?=$row['REFERENCE_OPD']?></td> -->
		<td class="destinataire"><?=$row['CODE_DEST']?><br/><?=$row['DESTINATAIRE']?></td>
		<td class="commande"><?=$row['COMMENTAIRE_ZZZ']?></td>
		<td class="reservation"><?=$row['RESERVATION'] ? 'OUI':''?></td>
		<td class="class"><?=$row['CLASS']?></td>
	</tr>
<? }
odbc_close($reflex);
?>
	</tbody>
</table>
<!--
$sql
<pre><?=$sql?></pre>
-->
</body>
</html>
