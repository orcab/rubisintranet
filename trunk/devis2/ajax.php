<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$search_car  =	array('à','ä','â','é','ê','è','ë','ê','ò','ö','ô','ì','ï','î','ù','ü','û','ÿ','{CR}');
$replace_car =	array('a','a','e','e','e','e','e','e','o','o','o','i','i','i','u','u','u','y',"\n");

//$F = fopen('debug.txt','w');
//fwrite($F,serialize($_GET));

if ($_GET['what'] == 'complette_via_ref' && isset($_GET['val'])) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR
	$val = mysql_escape_string(strtoupper($_GET['val'])) ;
	$sql = <<<EOT
SELECT	id,reference,designation,remise1,remise2,remise3,remise4,
		px_public,
		px_coop as px_expo_force,
		px_achat_coop as px_achat_coop_force,
		fournisseur,couleur,taille
FROM	devis_article2
WHERE	reference LIKE '$val%' OR
		reference_simple LIKE '$val%'
ORDER BY designation ASC
EOT;
	$res = mysql_query($sql);

	$json = array();
	while($row = mysql_fetch_array($res)) {
		foreach ($row as $key => $val) $row[$key] = my_utf8_decode(stripslashes($val));

		if ($row['px_achat_coop_force'] > 0) // on a forcé un prix d'achat (prix net)
			$row['px_achat_coop'] = $row['px_achat_coop_force'];
		else	// pas de prix d'achat renseigné, on calcul a partir des remise sur le prix public
			$row['px_achat_coop'] = $row['px_public'] * ((100-$row['remise1'])/100) * ((100-$row['remise2'])/100) * ((100-$row['remise3'])/100) * ((100-$row['remise4'])/100);

		$row['px_adh']  = $row['px_achat_coop'] / (1-(MARGE_COOP/100)); // on calcul le prix adh a partir du prix d'achat de la coop

		if ($row['px_expo_force'] > 0) // on a forcé un prix d'expo (rentré par les filles)
			$row['px_expo'] = $row['px_expo_force'];
		else
			$row['px_expo'] = min($row['px_adh'] * COEF_EXPO, $row['px_public'] > 0 ? $row['px_public'] : $row['px_adh'] * COEF_EXPO); // on calcul le prix expo a partir du prix d'adh. Si plus grand que px_public --> on prend le prix public

		$row['designation'] = str_replace($search_car,$replace_car,$row['designation']);
		array_push($json,$row);
	}
	//fwrite($F,json_encode($json));
	echo json_encode($json);
} // fin RECHERCHE DES INFO VIA LA REF FOURNISSEUR


 
elseif ($_GET['what'] == 'get_detail' && isset($_GET['val'])) { ////// RECHERCHE LE DETAIL D'UN ARTICLE VIA SON ID
	$id = mysql_escape_string(strtoupper($_GET['val'])) ;
	$sql = <<<EOT
SELECT	id,reference,designation,remise1,remise2,remise3,remise4,
		px_public,
		px_coop as px_expo_force,
		px_achat_coop as px_achat_coop_force,
		fournisseur,couleur,taille
FROM	devis_article2
WHERE	id='$id'
EOT;
	$res = mysql_query($sql);

	$row = mysql_fetch_array($res);
	foreach ($row as $key => $val) $row[$key] = my_utf8_decode(stripslashes($val));
	$row['designation'] = str_replace($search_car,$replace_car,$row['designation']);
	
	if ($row['px_achat_coop_force'] > 0) // on a forcé un prix d'achat (prix net)
		$row['px_achat_coop'] = $row['px_achat_coop_force'];
	else	// pas de prix d'achat renseigné, on calcul a partir des remise sur le prix public
		$row['px_achat_coop'] = $row['px_public'] * ((100-$row['remise1'])/100) * ((100-$row['remise2'])/100) * ((100-$row['remise3'])/100) * ((100-$row['remise4'])/100);


	$row['px_adh']  = $row['px_achat_coop'] / (1-(MARGE_COOP/100)); // on calcul le prix adh a partir du prix d'achat de la coop

	if ($row['px_expo_force'] > 0) // on a forcé un prix d'expo (rentré par les filles)
		$row['px_expo'] = $row['px_expo_force'];
	else
		$row['px_expo'] = min($row['px_adh'] * COEF_EXPO, $row['px_public'] > 0 ? $row['px_public'] : $row['px_adh'] * COEF_EXPO); // on calcul le prix expo a partir du prix d'adh. Si plus grand que px_public --> on prend le prix public

	$row['COEF_EXPO'] = COEF_EXPO;

	//fwrite($F,"\n\n".print_r($row,TRUE));

	//$row['prix'] = $row['px_expo']>0 ? min($row['px_public'],$row['px_expo']) : $row['px_public'];
	//$row['prix'] = $row['marge_coop'] <= 0 ? $row['px_public'] : $row['prix'];
	echo json_encode($row);
} // fin RECHERCHE LE DETAIL D'UN ARTICLE VIA SON ID


elseif ($_GET['what'] == 'calcul_cmd_rubis' && isset($_GET['id']) && $_GET['id']) { // CALCUL LES MONTANT GENERER DANS RUBIS
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
	devis_log("update_montant_cmd_rubis",$_GET['id'],$sql);

	odbc_close($loginor);
	echo json_encode($prix_cmd_rubis);
}




elseif ($_POST['what'] == 'sauvegarde_auto') { ////// SAUVEGARDE_AUTO
	/*ob_start();
	print_r($_POST);
	fwrite($F,ob_get_contents());
	ob_end_clean();*/

	require_once('save_data_into_database.php');
	// enregistre les données dans la base
	save_data_into_database();
} // fin sauvegarde_auto





// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}


//fclose($F);
?>