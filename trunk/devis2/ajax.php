<?

include('../inc/config.php');
$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$catalfou	= "../scripts/$CATALFOU";


////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR
if (isset($_GET['what']) && $_GET['what'] == 'complette_via_ref' && isset($_GET['val'])) {
	$val = mysql_escape_string(strtoupper($_GET['val'])) ;
	
	// requete de selection des articles qui corresponde à aux caracteres de recherche
	$sql = <<<EOT
SELECT	a.rowid,nom_fournisseur,reference,designation1,(prix1 * $COEF_EXPO) as px_avec_coef,prix6,code_mcs,ecotaxe,activite
FROM	articles a
		left join fournisseurs f     
          on a.code_fournisseur=f.code_fournisseur
WHERE	reference LIKE '$val%'
		OR cle1 LIKE '$val%'
		OR cle2 LIKE '$val%'
	-- OR reference_propre LIKE '$val%'
ORDER BY reference ASC
EOT;
	
	if (file_exists($catalfou)) {
		try {
			$sqlite = new PDO("sqlite:$catalfou"); // success
			//$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
		} catch (PDOException $exception) {
			echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
			die ($exception->getMessage());
		}
	} else {
		die("Fichier '$catalfou' non présent");
	}

	
	$json = array();
	$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des articles : ".array_pop($sqlite->errorInfo()));
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		$row['px_public']	= 0 ;
		$row['px_from']		= '';

		if		($row['prix6'] <= 0) {					// prix public vide, on prend le prix adh * coef
			$row['px_public']	= $row['px_avec_coef'];
			$row['px_from']		= 'adh';

		} elseif	($row['px_avec_coef'] < $row['prix6'] && $row['activite'] != '00D')	{	// prix adh inférieur au prix public, on prend le prix adh * coef. ne marche pas pour les articles elec
			$row['px_public'] = $row['px_avec_coef'];
			$row['px_from']		= 'adh';

		} else {										// prix public inférieur au prix adh, on prend le prix public
			$row['px_public'] = $row['prix6'];
			$row['px_from']		= 'pp';
		}

		$row['designation1'] = utf8_encode($row['designation1']);
		$row['px_public'] += $row['ecotaxe'];	 // on rajoute l'ecotaxe
		array_push($json,$row);
	}
	echo json_encode($json); // on envoi la réponse au navigateur

} // fin RECHERCHE DES INFO VIA LA REF FOURNISSEUR


////// RECHERCHE LE DETAIL D'UN ARTICLE VIA SON ID
elseif (isset($_GET['what']) && $_GET['what'] == 'get_detail' && isset($_GET['val'])) { 
	$id = mysql_escape_string(strtoupper($_GET['val'])) ;
	$sql = <<<EOT
SELECT	nom_fournisseur,reference,designation1,designation2,code_mcs,ecotaxe,activite,
		prix6,
		(prix1 * $COEF_EXPO) as px_avec_coef,
		prix1 as px_adh,
		strftime('%d/%m/%Y',date_application) AS date_application_format
FROM	articles a
		left join fournisseurs f     
          on a.code_fournisseur=f.code_fournisseur
WHERE	a.rowid='$id'
EOT;

	if (file_exists($catalfou)) {
		try {
			$sqlite = new PDO("sqlite:$catalfou"); // success
			//$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
		} catch (PDOException $exception) {
			echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
			die ($exception->getMessage());
		}
	} else {
		die("Fichier '$catalfou' non présent");
	}


	$json = array();
	$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des articles : ".array_pop($sqlite->errorInfo()));
	$row = $res->fetch(PDO::FETCH_ASSOC);
	$row['designation1'] = utf8_encode($row['designation1']);
	$row['designation2'] = utf8_encode($row['designation2']);
	$row['px_public']	= 0 ;
	if		($row['prix6'] <= 0)						// prix public vide, on prend le prix adh * coef
		$row['px_public']	= $row['px_avec_coef'];
	elseif	($row['px_avec_coef'] < $row['prix6'] && $row['activite'] != '00D')		// prix adh inférieur au prix public, on prend le prix adh * coef. ne marche pas pour les article elec
		$row['px_public'] = $row['px_avec_coef'];
	else												// prix public inférieur au prix adh, on prend le prix public
		$row['px_public'] = $row['prix6'];

	$row['px_public'] += $row['ecotaxe']; // on rajoute l'ecotaxe
	$row['px_avec_coef_ecotaxe'] = $row['px_avec_coef'] + $row['ecotaxe']; // on rajoute l'ecotaxe

	header('Content-type: application/json');
	echo json_encode($row); // on envoi la réponse au navigateur
	
} // fin RECHERCHE LE DETAIL D'UN ARTICLE VIA SON ID



// CALCUL LES MONTANT GENERER DANS RUBIS
elseif (isset($_GET['what']) && $_GET['what'] == 'calcul_cmd_rubis' && isset($_GET['id']) && $_GET['id']) { 
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$res = mysql_query("SELECT (SELECT numero FROM artisan WHERE devis.artisan = artisan.nom) AS num_artisan,num_cmd_rubis FROM devis WHERE id=$_GET[id]") or die("Impossible de trouver le devis $_GET[id] ".mysql_error());
	$row = mysql_fetch_array($res);
	$cmds_rubis = split("[^A-Za-z0-9]",$row['num_cmd_rubis']); // coupe les cmd rubis renseignée via l'interface
	for ($i=0 ; $i<sizeof($cmds_rubis) ; $i++) {
		$cmds_rubis[$i] = "NOBON='".$cmds_rubis[$i]."'" ;
	}
	$cmds = join(" OR ",$cmds_rubis);
	//select	NOCLI,NOBON,CODAR,DS1DB,DS2DB,DS3DB,FOUR1,QTESA,MONHT,SUM(MONHT) as PTHT
	if ($row['num_artisan']) { // si artisan spécifié
		$sql = <<<EOT
			select	SUM(MONHT) as PTHT
			from	${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1
			where	NOCLI = '$row[num_artisan]' and
					($cmds) and
					PROFI = 1
EOT;
	} else { // si pas d'artisan
		$sql = <<<EOT
			select	SUM(MONHT) as PTHT
			from	${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1
			where	($cmds) and
					PROFI = 1
EOT;
	}
	$prix_cmd_rubis = sprintf('%0.2f',e('PTHT',odbc_fetch_array(odbc_exec($loginor,$sql)))) ;

	// mise a jour de la base avec le montant pour éviter une recherche future
	mysql_query("UPDATE devis SET mtht_cmd_rubis='$prix_cmd_rubis' WHERE id=$_GET[id]");

	odbc_close($loginor);
	echo json_encode($prix_cmd_rubis);
}



////// SAUVEGARDE_AUTO
elseif (isset($_POST['what']) && $_POST['what'] == 'sauvegarde_auto') {
	require_once('save_data_into_database.php');
	// enregistre les données dans la base
	save_data_into_database(TRUE); // sauvegarde en brouillon
} // fin sauvegarde_auto




////// RECHERCHE DES PHRASES PRE-ENREGSITREES
elseif (isset($_GET['what']) && $_GET['what'] == 'get_phrase') { 
	$res = mysql_query("SELECT * FROM devis_phrase ORDER BY mot_cle ASC") or die("Ne peux pas récupérer la liste des phrases ".mysql_error());
	$rows = array();
	while($row = mysql_fetch_array($res)) {
		array_push($rows,array_map('utf8_encode',$row)); // encodage utf8
	}
	echo json_encode($rows);
}


////// AJOUTE UNE PHRASE
elseif (isset($_GET['what']) && $_GET['what'] == 'save_phrase' &&
		isset($_GET['mot_cle']) && $_GET['mot_cle'] &&
		isset($_GET['phrase']) && $_GET['phrase']) { 
	
	mysql_query("REPLACE INTO devis_phrase (mot_cle,phrase,last_editor,last_modification_date) VALUES (".
				"'".mysql_escape_string($_GET['mot_cle'])."',".
				"'".mysql_escape_string($_GET['phrase'])."',".
				"'".mysql_escape_string($_SERVER['REMOTE_ADDR'])."',".
				"NOW()".
			")") or die("Ne peux pas insérer la phrase ".mysql_error());
	echo '1';
}


////// SUPPRIME UNe PHRASE
elseif (isset($_GET['what']) && $_GET['what'] == 'delete_phrase' &&
		isset($_GET['mot_cle']) && $_GET['mot_cle']) { 
	
	mysql_query("DELETE FROM devis_phrase WHERE mot_cle='".mysql_escape_string($_GET['mot_cle'])."'") or die("Ne peux pas supprimer la phrase ".mysql_error());
	echo '1';
}




// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}
?>