<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if (isset($_POST['what'])		&& $_POST['what'] == 'save_frequence'	&&
	isset($_POST['numero'])		&& trim($_POST['numero'])				&&
	isset($_POST['type_doc'])	&& trim($_POST['type_doc'])
	) {

		$ancienne_valeurs = array();
		$row = mysql_fetch_array(mysql_query("SELECT AR,BL,RELIQUAT,RELIQUATSMS,AVOIR FROM send_document WHERE numero_artisan='".mysql_escape_string($_POST['numero'])."'"));
		foreach(array('AR','BL','RELIQUAT','RELIQUATSMS','AVOIR') as $key)
			$ancienne_valeurs[$key] = isset($row[$key]) ? $row[$key] : '';

		$ancienne_valeurs[$_POST['type_doc']] = mysql_escape_string($_POST['val'] ? $_POST['val']:'0'); // on met la nouvelle valeur

		$sql = "REPLACE INTO send_document (AR,BL,RELIQUAT,RELIQUATSMS,AVOIR,numero_artisan) VALUES ('$ancienne_valeurs[AR]','$ancienne_valeurs[BL]','$ancienne_valeurs[RELIQUAT]','$ancienne_valeurs[RELIQUATSMS]','$ancienne_valeurs[AVOIR]','".mysql_escape_string($_POST['numero'])."')";
		if (!mysql_query($sql)) {
			echo '{debug:"'.$sql.' '.mysql_error().'"}';
		}
}

// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>