<?

include('../../../inc/config.php');

if (!isset($_POST['type_cde']) || !$_POST['type_cde']) {
	die("Erreur type de commande non précisé");
}

// CHERCHE LES CDE FOURNISSEURS ASSOCIE AUX CDE ADHERENTS
/*if (isset($_POST['what']) && $_POST['what'] == 'associe_cde_adherent_cde_fournisseur' &&
	isset($_POST['cde_adherent']) && $_POST['cde_adherent']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,"select DISTINCT(NBOFO) from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 where NOBON='".strtoupper(mysql_escape_string($_POST['cde_adherent']))."'")  or die("Impossible de lancer la requete de recherche des cde fournisseurs");
	$cde_fournisseur = array();
	while($row = odbc_fetch_array($res)) {
		$cde_fournisseur[] = $row['NBOFO'] ;
	}
}


// CHERCHE LES CDE ADHERENTS ASSOCIE AUX CDE FOURNISSEURS
if (isset($_POST['what']) && $_POST['what'] == 'associe_cde_fournisseur_cde_adherent' &&
	isset($_POST['cde_fournisseur']) && $_POST['cde_fournisseur']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,"select DISTINCT(NOBON) from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 where NBOFO='".strtoupper(mysql_escape_string($_POST['cde_fournisseur']))."'")  or die("Impossible de lancer la requete de recherche des cde fournisseurs");
	$cde_fournisseur = array();
	while($row = odbc_fetch_array($res)) {
		$cde_adherent[] = $row['NOBON'] ;
	}
}
*/

?>
<html>
<head>
<title>Lignes envoyées à Reflex du bon <?=$_POST['num_cde']?></title>

<style>

body {
    font-family: verdana;
    font-size: 0.8em;
}
h1 {
    font-size: 1.2em;
}
#lignes {
    border: solid 1px black;
    border-collapse: collapse;
}
#lignes th, #lignes td {
    border: solid 1px #CCC;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #ccc;
    text-decoration: line-through;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--



//-->
</script>

</head>
<body>

<a class="btn" href="index.php"><i class="icon-arrow-left"></i> Revenir au choix de bon</a>

<form name="ligne" method="POST" action="index.php">
<table id="lignes" style="width:100%;">
	<caption>Lignes de commande <strong><?=$_POST['type_cde']?></strong> du bon <strong><?=strtoupper($_POST['num_cde'])?></strong></caption>
	<tr>
		<th class="num_tier">N° tier</th>
		<th class="num_ligne">N° ligne</th>
		<th class="code_article">Code</th>
		<th class="last_action">Dernière action</th>
		<th class="r_f"><?= $_POST['type_cde']=='client'?'R/F':'RECEP ?' ?></th>
		<th class="type">Type</th>
		<th class="etat_rubis">Etat rubis</th>
		<th class="etat_reflex">Etat Reflex</th>
		<th class="designation">Designation</th>
		<th class="qte">Qte</th>
	</tr>
<?	
	$sql = '';
	if 		($_POST['type_cde'] == 'client') {
		$sql = "select NOCLI as NUM_TIER,USSBE as LAST_USER,(CONCAT(DSBMJ,CONCAT('/',CONCAT(DSBMM,CONCAT('/',CONCAT(DSBMS,DSBMA)))))) as LAST_MODIFICATION_DATE,NOLIG as NUM_LIGNE,ETSBE as ETAT_RUBIS,DET06 as ETAT_REFLEX,CODAR as CODE_ARTICLE,TRAIT as R_F,TYCDD as TYPE,DS1DB as DESIGNATION1,DS2DB as DESIGNATION2,QTESA as QTE from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 where NOBON='".strtoupper(mysql_escape_string($_POST['num_cde']))."' and PROFI='1'";
	} elseif($_POST['type_cde'] == 'fournisseur') {
		$sql = "select NOFOU as NUM_TIER,CFLIG as NUM_LIGNE,CFDID as LAST_USER,(CONCAT(CFDMJ,CONCAT('/',CONCAT(CFDMM,CONCAT('/',CONCAT(CFDMS,CFDMA)))))) as LAST_MODIFICATION_DATE, CFDET as ETAT_RUBIS,CFD31 as ETAT_REFLEX,CFART as CODE_ARTICLE,CDDE1 as R_F,CFDPA as TYPE,CFDE1 as DESIGNATION1,CFDE2 as DESIGNATION2,CFQTE as QTE from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 where CFBON='".strtoupper(mysql_escape_string($_POST['num_cde']))."' and CFPRF='1'";
	}


	$rubis  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($rubis,$sql)  or die("Impossible de lancer la requete de recherche des lignes : <br/>$sql");
	while($row = odbc_fetch_array($res)) { ?>
		<tr class="<?=trim($row['ETAT_RUBIS']) ? ' annule':''?>">
			<td class="num_tier"><?=$row['NUM_TIER']?></td>
			<td class="num_ligne"><?=$row['NUM_LIGNE']?></td>
			<td class="code_article"><?=$row['CODE_ARTICLE']?></td>
			<td class="last_action"><?=$row['LAST_USER']?><br/><?=$row['LAST_MODIFICATION_DATE']?></td>
			<td class="r_f"><?=$row['R_F']?></td>
			<td class="type"><?=$row['TYPE']?></td>
			<td class="etat_rubis"><?=$row['ETAT_RUBIS']?></td>
			<td class="etat_reflex"><?=trim($row['ETAT_REFLEX']) ? 'Envoyée':''?></td>
			<td class="designation" style="text-align:left;"><?=$row['DESIGNATION1']?><br/><?=$row['DESIGNATION2']?></td>
			<td class="qte"><?=$row['QTE']?></td>
		</tr>	
<?	} ?>
</table>
</form>
</body>
</html>