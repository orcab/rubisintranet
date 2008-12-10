<?
include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if ($_GET['what'] == 'edit_designation' && isset($_GET['id']) && $_GET['id'] && isset($_GET['val'])) { ////// EDIT LES DESIGNATIONS
	
	if ($_GET['val']) // on met a jour la desigantion
		$res = mysql_query("UPDATE tarif_article SET designation='".mysql_escape_string($_GET['val'])."' WHERE id=$_GET[id]") ;
	else // on la remet a NULL
		$res = mysql_query("UPDATE tarif_article SET designation=NULL WHERE id=$_GET[id]") ;

	if (mysql_affected_rows()) { // l'update c'est bien passé
		$sql = <<<EOT
SELECT a.designation
FROM article a, tarif_article ta
WHERE		ta.id=$_GET[id]
		AND ta.code_article = a.code_article
EOT;
		echo base64_encode("[$_GET[id],'".mysql_escape_string($_GET['val'])."','".mysql_escape_string(e('designation',mysql_fetch_array(mysql_query($sql))))."']");
		//echo base64_encode("[$_GET[id],'".mysql_escape_string(mysql_error())."','".mysql_escape_string(e('designation',mysql_fetch_array(mysql_query($sql))))."']");
	}
	
	
}
?>