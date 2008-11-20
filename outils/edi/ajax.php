<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");



if (isset($_POST['what']) && $_POST['what'] == 'save_frequence' &&
	isset($_POST['numero']) && trim($_POST['numero'])	&&
	isset($_POST['type_doc']) && trim($_POST['type_doc'])
	) {

	$res = mysql_query("UPDATE send_document SET $_POST[type_doc]='".mysql_escape_string($_POST['val'] ? $_POST['val']:'0')."' WHERE numero_artisan='".mysql_escape_string($_POST['numero'])."'") ;

//	echo "{debug:'$_POST[val]'}";

}

// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>