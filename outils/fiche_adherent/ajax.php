<?
include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if ($_GET['what'] == 'toogle_star' && isset($_GET['id']) && $_GET['id']) { ////// passe un adhérent en favoris ou l'enleve
	$res = mysql_query("SELECT favoris FROM artisan_info WHERE numero='".mysql_escape_string($_GET['id'])."' LIMIT 0,1");
	$old_etat = e('favoris',mysql_fetch_array($res));
	if (mysql_num_rows($res) >= 1) // si l'enregistrement existait deja
		mysql_query("UPDATE artisan_info SET favoris=not favoris WHERE numero='".mysql_escape_string($_GET['id'])."'"); // toogle l'état favoris d'un artisan
	else // si l'on creer l'enregistrement
		$res = mysql_query("INSERT INTO artisan_info (numero,favoris) VALUES ('".mysql_escape_string($_GET['id'])."',1)"); // toogle l'état favoris d'un artisan
	
	echo json_encode(array('favoris' => !$old_etat));
	//echo "{'favoris' : '".(!$old_etat)."' }"; // renvoi du json avec l'état inversé
}
?>