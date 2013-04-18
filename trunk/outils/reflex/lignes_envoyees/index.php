<?

include('../../../inc/config.php');


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
<title>Voir les lignes envoyées à Reflex</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
}

h1 {
    font-size: 1.2em;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

function verif_form(){
	var form = document.cde;
	var value_type_cde = form.type_cde[form.type_cde.selectedIndex].value;
	var erreur = false;

	if (!form.num_cde.value) {
		alert("Veuillez préciser un n° de commande");
		erreur = true;
		
	} else if (form.num_cde.value.length != 6) {
		alert("Le n° de commande doit faire 6 caractères");
		erreur = true;
	}

	

	if (!value_type_cde) {
		alert("Veuillez préciser un type de commande");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>

<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<form name="cde" method="POST" action="lignes.php">
<div style="margin:auto;border:solid 1px grey;padding:20px;width:50%;">
	<h1>Voir les lignes qui ont été envoyés à Reflex</h1>
	N° de cde Rubis
	<input type="text" name="num_cde" value="" placeholder="N° de cde" size="10" maxlength="6"/>
	<select name="type_cde">
		<option value="">Type de commande</option>
		<option value="fournisseur">Fournisseur</option>
		<option value="client">Client</option>
	</select>
	<a class="btn btn-success" href="#" onclick="verif_form();"><i class="icon-ok"></i> Voir les lignes</a>
</div>
</form>
</body>
</html>