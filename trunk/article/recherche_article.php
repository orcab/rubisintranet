<html>
<head>
<title>Recherche d'articles</title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_article.ico" />
</head>
<body>
<?

include('../inc/config.php');

$erreur = '';

if (isset($_POST['action']) && $_POST['action']=='code' && $_POST['code']) {
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$sql_AARTICP1 = "select NOART,FOUR1,DESI1,ETARE from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='".mysql_escape_string($_POST['code'])."'";
	$sql_AARFOUP1 = "select NOART,NOFOU,REFFO,AFOGE from ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 where REFFO like '%".mysql_escape_string($_POST['code'])."%' OR AFOGE like '%".mysql_escape_string($_POST['code'])."%'";
?>
	Chaine recherchée : '<?=$_POST['code']?>'<br><br>

	<table border="1" width="20%">
		<caption>Codes article retrouvés</caption>
		<tr><th>Code article</th><th>Fournisseur</th><th>Designation</th><th>Etat</th></tr>
<?	$res = odbc_exec($loginor,$sql_AARTICP1) ;
	while($row = odbc_fetch_array($res)) { ?>
		<tr>
			<td nowrap><?=str_replace($_POST['code'],'<b>'.$_POST['code'].'</b>',$row['NOART'])?></td>
			<td nowrap><?=$row['FOUR1']?></td>
			<td nowrap><?=$row['DESI1']?></td>
			<td nowrap><?=$row['ETARE']?></td>
		</tr>
<? } ?>
	</table><br><br>


	<table border="1" width="20%">
		<caption>Références/Gencodes retrouvés</caption>
		<tr><th>Code article</th><th>Fournisseur</th><th>Référence</th><th>Gencode</th></tr>
<?	$res = odbc_exec($loginor,$sql_AARFOUP1) ;
	while($row = odbc_fetch_array($res)) { ?>
		<tr>
			<td nowrap><?=$row['NOART']?></td>
			<td nowrap><?=$row['NOFOU']?></td>
			<td nowrap><?=str_replace($_POST['code'],'<b>'.$_POST['code'].'</b>',$row['REFFO'])?></td>
			<td nowrap><?=str_replace($_POST['code'],'<b>'.$_POST['code'].'</b>',$row['AFOGE'])?></td>
		</tr>
<? } ?>
	</table><br><br>


<?
	odbc_close($loginor);
}
?>


<?	if ($erreur) { ?><div style="color:red;"><?=$erreur?></div><? } ?>

<form method="post" action="recherche_article.php" name="recherche">
<input type="hidden" name="action" value="code">
Code, Ref, Gencode : <input type="text" name="code" value="" size="15">
<input type="submit" value="Rechercher">
</form>
<SCRIPT LANGUAGE="JavaScript">
<!--
<?	if (!(isset($_POST['action']) && $_POST['action']=='code')) { ?>
		document.edition.code.focus(); // met le focus sur la case code article
<? } ?>
//-->
</SCRIPT>

</body>
</html>