<?php
include 'config.php'; 

//Connexion � la base de donn�es
function connexion() {
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
}

?>