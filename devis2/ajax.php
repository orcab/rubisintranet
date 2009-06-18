<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


//$F = fopen('debug.txt','w');
//fwrite($F,serialize($_GET));

if ($_GET['what'] == 'complette_via_ref' && isset($_GET['val'])) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR
	$val = mysql_escape_string(strtoupper($_GET['val'])) ;
	$res = mysql_query("SELECT id,reference,designation,px_public,px_coop,(px_coop / (1-(marge_coop/100))) AS px_adh,(px_coop * 1.5 / (1-(marge_coop/100))) AS px_expo,fournisseur,couleur,taille FROM devis_article2 WHERE reference LIKE '$val%' OR reference_simple LIKE '$val%' ORDER BY designation ASC");

	$json = array();
	while($row = mysql_fetch_array($res)) {
		$row['prix'] = min($row['px_public'],$row['px_expo']); // on prend le plus petit prix entre le prix expo et le prix public
		array_push($json,$row);
	}
	//fwrite($F,json_encode($json));
	echo json_encode($json);
	
} // fin RECHERCHE DES INFO VIA LA REF FOURNISSEUR


 
elseif ($_GET['what'] == 'get_detail' && isset($_GET['val'])) { ////// RECHERCHE LE DETAIL D'UN ARTICLE VIA SON ID
	$id = mysql_escape_string(strtoupper($_GET['val'])) ;
	$res = mysql_query("SELECT id,reference,designation,px_public,px_coop,(px_coop / (1-(marge_coop/100))) AS px_adh,(px_coop * 1.5 / (1-(marge_coop/100))) AS px_expo,fournisseur,couleur,taille FROM devis_article2 WHERE id='$id'");

	//fwrite($F,"\n\nSELECT id,reference,designation,px_public,px_coop,(px_coop / (1-(marge_coop/100))) AS px_adh,(px_coop * 1.5 / (1-(marge_coop/100))) AS px_expo,fournisseur,couleur,taille FROM devis_article2 WHERE id='$id'");
	$row = mysql_fetch_array($res);
	//fwrite($F,"\n\n".serialize($row));

	$row['prix'] = min($row['px_public'],$row['px_expo']);
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


// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}


//fclose($F);
?>