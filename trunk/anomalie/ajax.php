<?
include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if ($_GET['what'] == 'complette_fourn' && $_GET['val']) { ////// RECHERCHE DES FOURNISSEURS
	$res = mysql_query("SELECT DISTINCT(fournisseur) AS fournisseur FROM article WHERE fournisseur LIKE '".strtoupper($_GET['val'])."%'");
	if ($res) {
		$fournisseurs = array();
		while($row = mysql_fetch_array($res))
			array_push($fournisseurs,"'$row[fournisseur]'");

		echo '['.join(',',$fournisseurs).']' ; // renvoi au format JSON
	} else { // erreur sql
		echo "['ERREUR SQL : ".ereg_replace("'","\\'",mysql_error())."']";
	}


}
?>