<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT * FROM pdvente order by id ASC
EOT;

$res = mysql_query($sql);

//echo "code;libelle;niveau;chemin;chemin_sans_code_finale\n";
while($row = mysql_fetch_array($res)) {
	$chemin = '';
	//$chemin .= $row['activite_pere']		? $row['activite_pere'].'.' : '';
	$chemin .= $row['famille_pere']			? $row['famille_pere'].'.' : '';
	$chemin .= $row['sousfamille_pere']		? $row['sousfamille_pere'].'.' : '';
	$chemin .= $row['chapitre_pere']		? $row['chapitre_pere'].'.' : '';
	//echo "\n'$chemin'\n";
	$niveau = sizeof(explode('.',$chemin)) ;
	$chemin_sans_code = ereg_replace('(^\.+)','',$chemin);
	$chemin .= $row['code'].'.';
	
	
	

	echo $row['code'].';'.$row['libelle'].";$niveau;$chemin;$chemin_sans_code\n";
}
?>