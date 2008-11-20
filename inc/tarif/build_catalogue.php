<?

include('../inc/config.php');

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$style = array(
	'PLOMBERIE' => array('id_style' => 1, 'page_de_garde' => 'plomberie.png'),
	'OUTILS'	=> array('id_style' => 7, 'page_de_garde' => 'outils.png'),
	'CHAUFFAGE'	=> array('id_style' => 3, 'page_de_garde' => 'chauffage.png'),
	'SANITAIRE'	=> array('id_style' => 2, 'page_de_garde' => 'sanitaire.png'),
	'ELECTRICITE'=> array('id_style' => 9, 'page_de_garde' => 'electricite.png')
);

?>
<html>
<head><title>Création des catégories</title></head>
<body>
<h1>Création des catégories</h1>


Suppression de l'ancien catalogue<br>
<? 

	//mysql_query("DELETE FROM tarif_article WHERE electromenager=0") or die("Impossible de supprimer les anciens articles");
	//mysql_query("DELETE FROM tarif_categ   WHERE electromenager=0") or die("Impossible de supprimer les anciens articles");

	$res = mysql_query("SELECT * FROM pdvente ORDER BY chemin ASC") or die("Impossible de récupérer le plan de vente. ".mysql_error());
	$chemin = array();
	while($row = mysql_fetch_array($res)) {
		$chemin[$row['chemin']] = '';
	}


	$current_style = '';
	mysql_data_seek($res,0);
	while($row = mysql_fetch_array($res)) {

		if (isset($style[$row['libelle']])) $current_style = $style[$row['libelle']]['id_style'] ;

		$tmp = explode('.',$row['chemin']);
		$new_chemin = array();
		while(isset($tmp[1])) {
			array_pop($tmp); // on supprime le dernier code
			$chemin_sans_code = join('.',$tmp);
			//echo "'".$chemin[$chemin_sans_code]."' '".$row['chemin']."'<br>\n";
			$new_chemin[] = $chemin[$chemin_sans_code] ;
		}
		//echo join('-',array_reverse($new_chemin))."<br><br>";

		if ($row['niveau']==1)
				$page_de_garde = isset($style[$row['libelle']]) ? $style[$row['libelle']]['page_de_garde'] : 'a_definir.png';
		else
				$page_de_garde = 'NULL' ;

		mysql_query("INSERT INTO tarif_categ (nom,chemin,image,saut_de_page,id_style,page_de_garde,electromenager) VALUES ('".mysql_escape_string($row['libelle'])."','".
			mysql_escape_string(join('-',array_reverse($new_chemin)))."',NULL,'".
			mysql_escape_string($row['niveau']==1 ? 1 : 0)."','".
			mysql_escape_string($current_style)."','".
			mysql_escape_string($page_de_garde)."',0)"
		) or die("Impossible d'insérer la catégorie. ".mysql_error());

		$chemin[$row['chemin']] = mysql_insert_id();
	}


?>




Ouverture du fichier catalogue<br>
<? 

	$res = mysql_query("SELECT code_article,designation,ref_fournisseur,prix_net,chemin FROM article");
	while($row = mysql_fetch_array($res)) {
		// insertion de l'article dans la base

		if (isset($chemin[$row['chemin']])) {
			$sql = "INSERT INTO tarif_article (code_article,designation,id_categ,electromenager,ref_fournisseur,px_adh_ht) VALUES ('".mysql_escape_string($row['code_article'])."','".
				mysql_escape_string($row['designation'])."','".$chemin[$row['chemin']]."',0,'".
				mysql_escape_string($row['ref_fournisseur'])."','".
				mysql_escape_string($row['prix_net'])."')";
			mysql_query($sql) or die("Ne peux pas insérer l'article '$row[code_article]' ".mysql_error());
		}
		else {
			echo "ERREUR : Chemin '$row[chemin]' non trouvé (code '$row[code_article]')<br>\n";
		}
	}

?>



<br><br>
<A HREF="index.html">Revenir à l'accueil</A>
</body>
</html>
<?

function affiche_erreur($msg) {
	echo "<font color='#ff0000'>$msg</font><br>\n";
}


?>