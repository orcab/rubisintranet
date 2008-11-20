<?

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$erreur = '';

if (isset($_POST['action']) && $_POST['action']=='code_article' && $_POST['code_article']) {
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$sql = "select DESI1,DESI2,STFOU,LOCAL,STOMI,STALE,STOMA,STGES from ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK, ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE where STSTS<>'S' and STOCK.NOART='".mysql_escape_string($_POST['code_article'])."' and STOCK.DEPOT='$LOGINOR_DEPOT' and ARTICLE.NOART=STOCK.NOART";
	$res = odbc_exec($loginor,$sql) ;
	
	$row = array();
	$nb_result = 0 ;
	while($tmp = odbc_fetch_array($res)) {
		$row = $tmp;
		$row['STGES'] = trim($row['STGES']);
		$row['LOCAL'] = trim($row['LOCAL']);
		$row['STOMI'] = ceil($row['STOMI']);
		$row['STALE'] = ceil($row['STALE']);
		$row['STOMA'] = ceil($row['STOMA']);
		$nb_result++;
	}
	
	if ($nb_result == 0) { // code article pas trouvé
		$erreur = "Code article non trouvé";
	} elseif ($nb_result > 1) { // plusieurs code article trouvé
		$erreur = "Erreur interne (code multi article)";
	}
	
	odbc_close($loginor);
} elseif (isset($_POST['action']) && $_POST['action']=='enregistre' && $_POST['code_article']) {
	
	// validation des modifcations
	$STMSS = substr(date('Y'),0,2);
	$STMAA = substr(date('Y'),2,2);
	$STMMM = date('m');
	$STMJJ = date('d');
	$STMID = e('loginor',mysql_fetch_array(mysql_query("SELECT UPPER(loginor) AS loginor FROM employe WHERE ip='$_SERVER[REMOTE_ADDR]' LIMIT 0,1")));
	$table_ip = explode('.',$_SERVER['REMOTE_ADDR']) ;
	$STMWS = LOGINOR_PREFIX_SOCIETE.$table_ip[2].'.'.$table_ip[3];

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STMID='$STMID', STMWS='$STMWS', STMSS='$STMSS', STMAA='$STMAA', STMMM='$STMMM', STMJJ='$STMJJ' ,LOCAL='".mysql_escape_string($_POST['LOCAL'])."',STOMI='".mysql_escape_string($_POST['STOMI'])."',STALE='".mysql_escape_string($_POST['STALE'])."',STOMA='".mysql_escape_string($_POST['STOMA'])."',STGES='".mysql_escape_string($_POST['STGES'])."' where NOART='".mysql_escape_string($_POST['code_article'])."' and DEPOT='$LOGINOR_DEPOT'";
	$res = odbc_exec($loginor,$sql) or die("ERREUR SQL<br>\n$sql");

	#echo $sql;
	odbc_close($loginor);

	unset($_POST);
}

?>
<html>
<head>
<title>Edition des articles</title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_article.ico" />
</head>
<body>

<?	if ($erreur) { ?>
		<div style="color:red;"><?=$erreur?></div>
<?		exit ;
	} ?>

<form method="post" action="edition_article.php" name="edition">


<? if (!(isset($_POST['action']) && $_POST['action']=='code_article')) { // mode demande de code article ?>
	<input type="hidden" name="action" value="code_article">
	Code article : <input type="text" name="code_article" value="" size="15">


<? } elseif (isset($row)) { // on a deja le code artilce, il nous faut les infos ?>
	<input type="hidden" name="code_article" value="<?=$_POST['code_article']?>">
	<input type="hidden" name="action" value="enregistre">

	<b>Code article : <?=$_POST['code_article']?></b><br>
	Fournisseur : <span style="color:green;"><?=$row['STFOU']?><br>
	<?=$row['DESI1']?><br>	<?=$row['DESI2']?></span>
	<br><br>
	<div>Gestionnaire <select name="STGES" size="1">
						<option value="BT"<?= $row['STGES']=='BT' ? ' selected':'' ?>>Bernard</option>
						<option value="CG"<?= $row['STGES']=='CG' ? ' selected':'' ?>>Charles</option>
						<option value="JM"<?= $row['STGES']=='JM' ? ' selected':'' ?>>Jérémy</option>
						<option value="LG"<?= $row['STGES']=='LG' ? ' selected':'' ?>>Laurent</option>
					</select><br>
		Localisation <input type="text" name="LOCAL" value="<?=$row['LOCAL']?>" size="4" maxlength="5"><br>
		Stock mini <input type="text" name="STOMI" value="<?=$row['STOMI']?>" size="6"><br>
		Stock alerte <input type="text" name="STALE" value="<?=$row['STALE']?>" size="6"><br>
		Stock maxi <input type="text" name="STOMA" value="<?=$row['STOMA']?>" size="6"><br>
	</div><br>
	<input type="submit" value="Enregistrer">  <input type="reset" value="Annuler les modifications"><br><br>
	<a href="edition_article.php">Autre code article</a>

<? } ?>


</form>
<SCRIPT LANGUAGE="JavaScript">
<!--
<?	if (!(isset($_POST['action']) && $_POST['action']=='code_article')) { ?>
		document.edition.code_article.focus(); // met le focus sur la case code article
<? } ?>
//-->
</SCRIPT>

</body>
</html>
<?
mysql_close($mysql);
?>