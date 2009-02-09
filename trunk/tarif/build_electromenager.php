<?

include('../inc/config.php');

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

define('CATALOGUE','cedil_co.cat');
define('EXCLUSION','exclure_categorie_electromenager.txt');
define('TRANSFORMATION','transformation_categorie_electromenager.csv');

$i=0;
define('CODE_ARTICLE',$i++);
define('DESIGNATION',$i++);
$i++;
define('CATEGORIE',$i++);
define('CODE_MARQUE',$i++);
define('REF_FOURNISSEUR',$i++);
define('PUHT_ACHAT',$i++);
$i++; $i++;
define('PUTTC_PUBLIC',$i++);
define('PUTTC_PUBLIC_FRANCS',$i++);
$i++; $i++; $i++; $i++;
define('DESIGNATION_MINI',$i++);
$i++;
define('ECOTAXEHT',$i++);
define('ECOTAXETTC',$i++);

define('COEF_VENTE',1.13636);

?>
<html>
<head><title>Création des catégories électromenager</title></head>
<body>
<h1>Création des catégories électromenager</h1>


Suppression de l'ancien catalogue CEDIL<br>
<? 
	#mysql_query("DELETE FROM tarif_categ WHERE electromenager=1") or die("Impossible de supprimer les anciennes catégories");


	// ajout de la super categ ELECTROMENAGER
	$res = mysql_query("SELECT id,id_style FROM tarif_categ WHERE nom='Electromenager' LIMIT 0,1") or die("Impossible de voir si la super categorie Electromenager a été créé");
	if (mysql_num_rows($res) >= 1) { // on la trouvé, on releve le n°
		$row = mysql_fetch_array($res) ;
		$id_categ_electromenager = $row['id'] ;
		$id_style_electromenager = $row['id_style'] ;
	} else { // on l'a pas trouvé, on l'a crée
		mysql_query("INSERT INTO tarif_categ (nom,chemin,id_style,electromenager) VALUES ('Electromenager','',8,1)");
		$id_style_electromenager = 8 ;
		$id_categ_electromenager = mysql_insert_id();
	}
	
	mysql_query("DELETE FROM tarif_article WHERE electromenager=1") or die("Impossible de supprimer les anciens articles");
?>

Ouverture de la liste des exclusion CEDIL <?=EXCLUSION?><br>
<?
	/*$conn_id = ftp_connect(FTP_SERVER);
	$login_result = ftp_login($conn_id, 'public','');
	if (!ftp_get($conn_id, basename(EXCLUSION), EXCLUSION, FTP_BINARY))
		die("Impossible de récupérer la liste des exlusion par FTP");
	*/

	
	$exclusion = array();
	$f = file( EXCLUSION ) or die("Impossible de trouver la liste des exclusion ".EXCLUSION);
	foreach ($f as $line) {
		$exclusion[strtolower(trim($line))] = 1;
	}
?>

Ouverture de la liste des transformations CEDIL <?=TRANSFORMATION?><br>
<?
	/*$conn_id = ftp_connect(FTP_SERVER);
	$login_result = ftp_login($conn_id, 'public','');
	if (!ftp_get($conn_id, basename(TRANSFORMATION), TRANSFORMATION, FTP_BINARY))
		die("Impossible de récupérer la liste des exlusion par FTP");
	*/
	
	$transformation = array();
	$f = file( TRANSFORMATION ) or die("Impossible de trouver la liste des exclusion ".TRANSFORMATION);
	foreach ($f as $line) {
		if (trim($line)) {
			$tmp = explode(';',$line);
			$transformation[strtolower(trim($tmp[0]))] = trim($tmp[1]);
			//echo "'".strtolower(trim($tmp[0]))."' '".trim($tmp[1])."'<br>\n";
		}
	}

	//print_r($transformation);
?>

Ouverture du fichier catalogue CEDIL <?=CATALOGUE?><br>
<? 
	$old_categ = '' ;
	$id_categ = 0;

	/*if (!ftp_get($conn_id, basename(CATALOGUE), CATALOGUE, FTP_BINARY))
		die("Impossible de récupérer le catalogue par FTP");
	ftp_close($conn_id);
	*/

	$f = file( CATALOGUE ) or die("Impossible de trouver le catalogue ".CATALOGUE);
	foreach ($f as $line) {
		$data = explode(';',$line);
		$data[CATEGORIE] = trim($data[CATEGORIE]);
		if (isset($transformation[strtolower($data[CATEGORIE])])) {
			$data[CATEGORIE] = $transformation[strtolower($data[CATEGORIE])] ;
		}

		//echo "strtolower(\$data[CATEGORIE])='".strtolower($data[CATEGORIE])."'<br>\n";

		if (isset($exclusion[strtolower($data[CATEGORIE])]) && $exclusion[strtolower($data[CATEGORIE])]==1) continue; // on saute les categ à exclure

		$data[PUHT_ACHAT]	= str_replace(',','.',$data[PUHT_ACHAT]);
		$data[PUTTC_PUBLIC] = str_replace(',','.',$data[PUTTC_PUBLIC]);
		$data[ECOTAXETTC]	= str_replace(',','.',$data[ECOTAXETTC]);

		
		 // on est sur un nouvelle categ, on l'ajoute à la base (ou la met à jour)
		if ($data[CATEGORIE] != $old_categ) {
			$res = mysql_query("SELECT id FROM tarif_categ WHERE nom='".mysql_escape_string($data[CATEGORIE])."' LIMIT 0,1") or die("Impossible de voir si categorie ".$data[CATEGORIE]." a été créé");
			if (mysql_num_rows($res) >= 1) { // on la trouvé, on releve le n°
				$id_categ = e('id',mysql_fetch_array($res)) ;
				//echo "Trouvé une categ '".$data[CATEGORIE]."' id='$id_categ'<br>\n";
			} else { // on l'a pas trouvé, on l'a crée
				mysql_query("INSERT INTO tarif_categ (nom,chemin,id_style,electromenager) VALUES ('".mysql_escape_string($data[CATEGORIE])."','$id_categ_electromenager',$id_style_electromenager,1)") ;
				$id_categ = mysql_insert_id();
				//echo "PAS Trouvé de categ '".$data[CATEGORIE]."' nouvel id='$id_categ'<br>\n";
			}
		}


		// insertion de l'article dans la base
		$sql = "INSERT INTO tarif_article (code_article,designation,id_categ,electromenager,ref_fournisseur,px_coop_ht,px_adh_ht,px_pub_ttc,px_eco_ttc) VALUES ('".				mysql_escape_string($data[CODE_ARTICLE])."','".
				mysql_escape_string($data[DESIGNATION])."','$id_categ',1,'".
				mysql_escape_string($data[REF_FOURNISSEUR])."','".
				mysql_escape_string($data[PUHT_ACHAT])."','".
				mysql_escape_string($data[PUHT_ACHAT] * COEF_VENTE)."','".
				mysql_escape_string($data[PUTTC_PUBLIC])."','".
				mysql_escape_string($data[ECOTAXETTC])."')";
		mysql_query($sql) or affiche_erreur("Ne peux pas insérer l'article '".$data[CODE_ARTICLE]."' ".mysql_error());


		$old_categ = $data[CATEGORIE];
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