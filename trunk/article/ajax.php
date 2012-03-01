<?
include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if ($_GET['what'] == 'activite') { ////// RECHERCHE DES ACTIVITES
	$res = mysql_query("SELECT code,libelle FROM pdvente WHERE activite_pere IS NULL;");
	if ($res) {
		$html = "<b>Activit&eacute;</b><br/><br/>";
		while($row = mysql_fetch_array($res))
			$html .= "<div class=\"activite\"><b>$row[code]</b> $row[libelle]</div>";

		echo $html ;
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}


} elseif ($_GET['what'] == 'famille' && $_GET['val']) { ////// RECHERCHE DES FAMILLES
	$vals = explode('/',$_GET['val']); // separation des valeurs pour la recherche
	$res = mysql_query("SELECT code,libelle FROM pdvente WHERE activite_pere='".strtoupper($vals[0])."' AND famille_pere IS NULL ORDER BY code");
	if ($res) {
		$html = "<b>$vals[0] =&gt; Famille</b><br><br>";
		while($row = mysql_fetch_array($res))
			$html .= "<div class=\"famille\"><b>$row[code]</b> $row[libelle]</div>";

		echo $html ;
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}


} elseif ($_GET['what'] == 'sousfamille' && $_GET['val']) { ////// RECHERCHE DES SOUS FAMILLES
	$vals = explode('/',$_GET['val']); // separation des valeurs pour la recherche
	$res = mysql_query("SELECT code,libelle FROM pdvente WHERE activite_pere='".strtoupper($vals[0])."' AND famille_pere='".strtoupper($vals[1])."' AND sousfamille_pere IS NULL ORDER BY code");
	if ($res) {
		$html = "<b>$vals[0] =&gt; $vals[1] =&gt; Sous famille</b><br><br>";
		while($row = mysql_fetch_array($res))
			$html .= "<div class=\"sousfamille\"><b>$row[code]</b> $row[libelle]</div>";

		echo $html ;
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}


} elseif ($_GET['what'] == 'chapitre' && $_GET['val']) { ////// RECHERCHE DES CHAPITRES
	$vals = explode('/',$_GET['val']); // separation des valeurs pour la recherche
	$res = mysql_query("SELECT code,libelle FROM pdvente WHERE activite_pere='".strtoupper($vals[0])."' AND famille_pere='".strtoupper($vals[1])."' AND sousfamille_pere='".strtoupper($vals[2])."' AND chapitre_pere IS NULL ORDER BY code");
	if ($res) {
		$html = "<b>$vals[0] =&gt; $vals[1] =&gt; $vals[2] =&gt; Chapitre</b><br><br>";
		while($row = mysql_fetch_array($res))
			$html .= "<div class=\"chapitre\"><b>$row[code]</b> $row[libelle]</div>";

		echo $html ;
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}


} elseif ($_GET['what'] == 'souschapitre' && $_GET['val']) { ////// RECHERCHE DES SOUS CHAPITRES
	$vals = explode('/',$_GET['val']); // separation des valeurs pour la recherche
	$res = mysql_query("SELECT code,libelle FROM pdvente WHERE activite_pere='".strtoupper($vals[0])."' AND famille_pere='".strtoupper($vals[1])."' AND sousfamille_pere='".strtoupper($vals[2])."' AND chapitre_pere='".strtoupper($vals[3])."' ORDER BY code");
	if ($res) {
		$html = "<b>$vals[0] =&gt; $vals[1] =&gt; $vals[2] =&gt; $vals[3] =&gt; Sous chapitre</b><br><br>";
		while($row = mysql_fetch_array($res))
			$html .= "<div class=\"souschapitre\"><b>$row[code]</b> $row[libelle]</div>";

		echo $html ;
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}


} elseif ($_GET['what'] == 'complette_fourn' && $_GET['val']) { ////// RECHERCHE DES FOURNISSEURS
	$res = mysql_query("SELECT DISTINCT(fournisseur) AS fournisseur FROM article WHERE fournisseur LIKE '".strtoupper($_GET['val'])."%'");
	if ($res) {
		$fournisseurs = array();
		while($row = mysql_fetch_array($res))
			array_push($fournisseurs,"'$row[fournisseur]'");

		echo '['.join(',',$fournisseurs).']' ; // renvoi au format JSON
	} else { // erreur sql
		echo "['ERREUR SQL : ".ereg_replace("'","\\'",mysql_error())."']";
	}


} elseif ($_GET['what'] == 'check_ref_fournisseur' && $_GET['val']) { ////// RECHERCHE SI LA REFERENCE FOURNISSEUR N'EXISTE PAS DEJA
	$vals = explode('/',$_GET['val']); // separation des valeurs pour la recherche
	$res = mysql_query("SELECT code_article,designation FROM article WHERE fournisseur='".strtoupper($vals[0])."' AND (ref_fournisseur='".strtoupper($vals[1])."' OR ref_fournisseur_condensee='".strtoupper($vals[1])."' OR ref_fournisseur='".strtoupper(preg_replace('/[^a-z0-9]/i','',$vals[1]))."' OR ref_fournisseur_condensee='".strtoupper(preg_replace('/[^a-z0-9]/i','',$vals[1]))."')");
	
	if ($res) {
		if (mysql_num_rows($res)) {
			$row = mysql_fetch_array($res);
			echo "Code $row[code_article]\n$row[designation]";
		}
	} else { // erreur sql
		echo 'ERREUR SQL : '.mysql_error();
	}
}
	
		
elseif ($_GET['what'] == 'get_type_produit_fournisseur' && isset($_GET['code_fournisseur']) && $_GET['code_fournisseur']) { ////// RECHERCHE DES TYPE DE PRODUIT CHEZ CE FOURNISSEUR
	$res = mysql_query("SELECT famille_produit,marge FROM fournisseur_marge WHERE code_fournisseur='".strtoupper($_GET['code_fournisseur'])."' ORDER BY famille_produit ASC") or die("Ne peux pas récupérer la liste des type de produit ".mysql_error());
	$type_produit = array();
	while($row = mysql_fetch_array($res)) {
		//print_r(array_map('htmlize',$row));
		array_push($type_produit,$row);
	}
	echo json_encode($type_produit);



} elseif ($_GET['what'] == 'save_type_produit' &&
		isset($_GET['code_fournisseur']) && $_GET['code_fournisseur'] &&
		isset($_GET['type']) && $_GET['type'] &&
		isset($_GET['marge']) && $_GET['marge']) { ////// AJOUTE UN TYPE PRODUIT
	
	$marge = str_replace(',','.',$_GET['marge']);

	mysql_query("REPLACE INTO fournisseur_marge (code_fournisseur,famille_produit,marge,last_editor,last_modification_date) VALUES (".
				"'".strtoupper(mysql_escape_string($_GET['code_fournisseur']))."',".
				"'".mysql_escape_string($_GET['type'])."',".
				"'".mysql_escape_string($marge)."',".
				"'".mysql_escape_string($_SERVER['REMOTE_ADDR'])."',".
				"NOW()".
			")") or die("Ne peux pas insérer le type produit ".mysql_error());
	echo '1';



} elseif ($_GET['what'] == 'delete_type_produit' &&
		isset($_GET['code_fournisseur']) && $_GET['code_fournisseur'] &&
		isset($_GET['type']) && $_GET['type']) { ////// SUPPRIME UN TYPE PRODUIT
	
	mysql_query("DELETE FROM fournisseur_marge WHERE code_fournisseur='".strtoupper(mysql_escape_string($_GET['code_fournisseur'])).
					"' AND famille_produit='".mysql_escape_string($_GET['type'])."'") or die("Ne peux pas supprimer le type produit ".mysql_error());

	echo '1';



} else {
	echo "Procedure '$_GET[what]' inconnu";
}


?>