<?
include('../../inc/config.php');

$num_cde	= '';
$select_fou = '';
$message	= '';

// CHERCHE L'EMAIL DE L'ADHRENT POUR LE PREVENIR DE SES DATES DE LIVRAISON
if (	isset($_POST['what'])
	&& $_POST['what'] == 'bon_mise_en_stock'
	&& isset($_POST['cde_fournisseur']) && $_POST['cde_fournisseur']) {

	$num_cde = strtoupper(mysql_escape_string($_POST['cde_fournisseur']));
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$sql = <<<EOT
select NOFOU
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 ENTETE_BON
where		CFBON='$num_cde'
EOT;

	//echo $sql;exit;
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde fournisseurs : <br/>\n$sql");
	$four			= array();

	while($row = odbc_fetch_array($res))
		if (!in_array($row['NOFOU'],$four)) // regarde combien de fournisseur correspond à la commande
			array_push($four,$row['NOFOU']);

	//si la commande ne correspond qu'a un seul fourn --> on affiche le bon de mise a dispo
	if (sizeof($four) == 1) {
				
		include('edition_pdf.php');


	}
	elseif (sizeof($four) > 1) { // cas ou plusieurs fournisseur ont le meme n° de cde
		$select_fou = "<select name=\"no_fou\">";
		foreach ($four as $val)
			$select_fou .= "<option value=\"$val\">".$four_nom[$val]."</option>";
		$select_fou .= "</select>";
	}
}

?>
<html>
<head>
<title>Bon de mise en stock</title>

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
	vertical-align:top;
	padding:5px;
}	

div.message {
	text-align:center;
	font-weight:bold;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--

function bon_mise_en_stock() {
	if (document.association2.cde_fournisseur.value) {
		document.association2.what.value = 'bon_mise_en_stock';
		document.association2.submit();
		return 1;
	} else {
		alert("Aucun n° de commande fournisseur");
		return 0;
	}
}

function init_focus() {
	document.association2.cde_fournisseur.focus();
} // fin init_focus

//-->
</script>

</head>
<body onload="init_focus();">

<table id="assoc" align="center">

<form name="association2" method="POST" action="index.php" onsubmit="return bon_mise_en_stock();">
<tr>
	<td style="width:10%;" nowrap>N° cde fournisseur :</td>
	<td style="width:10%;">
		<input type="hidden" name="what" value="" />
		<input name="cde_fournisseur" value="" size="8" />
	</td>
	<td style="text-align:left;">
		<input type="button" class="button valider" value="Bon de mise en stock" onclick="bon_mise_en_stock();" />
	</td>
</tr>
<? if ($select_fou) { // plusieur fournisseur corresponde, on propose d'en selectionné un ?>
<tr>	
	<td colspan="3" style="text-align:center;"><img src="../../gfx/attention.png" /> Plusieurs fournisseur ont ce n° de bon, selectionné le bon : <?=$select_fou?></td>
</tr>
<?  } ?>
</form>


</table>


<?= $message ? $message:'' ?>
</body>
</html>