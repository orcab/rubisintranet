<?

include('../../inc/config.php');


// CHERCHE LES CDE FOURNISSEURS ASSOCIE AUX CDE ADHERENTS
if (isset($_POST['what']) && $_POST['what'] == 'associe_cde_adherent_cde_fournisseur' &&
	isset($_POST['cde_adherent']) && $_POST['cde_adherent']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,"select DISTINCT(NBOFO) from AFAGESTCOM.ADETBOP1 where NOBON='".strtoupper(mysql_escape_string($_POST['cde_adherent']))."'")  or die("Impossible de lancer la requete de recherche des cde fournisseurs");
	$cde_fournisseur = array();
	while($row = odbc_fetch_array($res)) {
		$cde_fournisseur[] = $row['NBOFO'] ;
	}
}


?>
<html>
<head>
<title>Association des cde adhérents et cde fournisseurs</title>

<style>

body,td {
	font-family:verdana;
	font-size:0.8em;
}

table#assoc {
	width:50%;
	border-collapse:collapse;
	border:solid 1px grey;
}

table#assoc td {
	padding:1px;
	padding-left:3px;
	padding-right:3px;
}	

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--

function associe_cde_adherent_cde_fournisseur() {
	if (document.association.cde_adherent.value) {
		document.association.what.value='associe_cde_adherent_cde_fournisseur';
		document.association.submit();
	} else {
		alert("Aucun n° de commande adhérent");
	}
}

//-->
</script>

</head>
<body>

<form name="association" method="POST" action="index.php">
<input type="hidden" name="what" value="" />

<table id="assoc" align="center">
<tr>
	<td style="width:10%;" nowrap>N° cde adhérent :</td>
	<td style="width:10%;"><input name="cde_adherent" value="<?=isset($_POST['cde_adherent']) ? $_POST['cde_adherent']:'' ?>" size="6" /></td>
	<td style="text-align:left;"><input type="button" class="button valider" value="Chercher les cde fournisseurs" onclick="associe_cde_adherent_cde_fournisseur();" /></td>
	<td style="text-align:right;">
<?		if (isset($cde_fournisseur)) { 
			foreach ($cde_fournisseur as $nocde) { ?>
				<strong><?=$nocde?></strong><br/>
<?			}
		}
?>
	</td>
</tr>
</table>
</form>

</body>
</html>