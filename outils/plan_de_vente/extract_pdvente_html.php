<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT * FROM pdvente order by id ASC
EOT;

$res = mysql_query($sql);
?>
<html>
<head>
<title>Arboréscence du plan de vente</title>
<style>
h1 { margin-left:00px; }
h2 { margin-left:10px; }
h3 { margin-left:20px; }
h4 { margin-left:30px; }
h5 { margin-left:40px; }
h6 { margin-left:50px; }
</style>
</head>
<body>
<?
$historique_libelle = array();
while($row = mysql_fetch_array($res)) {
	//if ($row['activite_pere'] == 'T') { continue ; }
	

	$chemin = '';
	$chemin .= $row['activite_pere']		? $row['activite_pere'].'.' : '';
	$chemin .= $row['famille_pere']			? $row['famille_pere'].'.' : '';
	$chemin .= $row['sousfamille_pere']		? $row['sousfamille_pere'].'.' : '';
	$chemin .= $row['chapitre_pere']		? $row['chapitre_pere'].'.' : '';

	$niveau = sizeof(explode('.',$chemin)) ;
	$chemin_sans_code = ereg_replace('(^\.+)','',$chemin);
	$chemin .= $row['code'].'.';
	
	$historique_libelle[$niveau - 1] = $row['libelle'];
	//echo $row['code'].';'.$row['libelle'].";$niveau;$chemin;$chemin_sans_code;".join(' / ',$historique_libelle)."\n";

	if ($niveau >= 1 && $niveau <= 6) {
		echo "<h$niveau>[$chemin] $row[libelle]</h$niveau>\n";
	}

}
?>
</body>
</html>