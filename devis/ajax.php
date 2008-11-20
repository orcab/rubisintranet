<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if ($_GET['what'] == 'complette_via_ref' && isset($_GET['val'])) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR
	$res = mysql_query("SELECT * FROM devis_article WHERE ref_fournisseur LIKE '".trim(mysql_escape_string(strtoupper($_GET['val'])))."' LIMIT 0,1");
	
//	$from_loginor = FALSE ;
//	if (!mysql_num_rows($res)) { // l'article n'a pas été en devis, on va chercher dans loginor
//		$res = mysql_query("SELECT * FROM article WHERE ref_fournisseur LIKE '".trim(mysql_escape_string(strtoupper($_GET['val'])))."' OR ref_fournisseur_condensee LIKE '%".trim(mysql_escape_string(strtoupper($_GET['val'])))."%' LIMIT 0,1");
//		$from_loginor = TRUE ;
//	}

	//echo print_r(mysql_fetch_array($res));
	
	if (mysql_num_rows($res)) {
		$row = mysql_fetch_array($res);
		
		$row['designation'] = ereg_replace("[\n\r]","{CR}",$row['designation']);
//		if ($from_loginor)	$prix = $row['prix_net'] * COEF_PRIX_PUBLIC ;
//		else				$prix = $row['puht'] ;
		$prix = $row['prix_public_ht'] - ($row['prix_public_ht'] * $row['remise'] / 100) ;

		$json  = "[" ;
		$json .= "\"$row[code_article]\"," ;
		$json .= "\"$row[fournisseur]\"," ;
		//$json .= '"'.ereg_replace("[\\n\r]+","\\\\n",$row['designation']).'",' ;
		//$json .= '"'.ereg_replace("[\\n\r]+","n",$row['designation']).'",' ;
		$json .= '"'.$row['designation'].'",' ;
		//echo base64_encode(str_replace("\\n","",$row['designation']));
		$json .= "\"$prix\"" ;
		$json .= "]" ;
		echo base64_encode($json) ;
		//echo $json;
	} else { // aucun resultat
		$json  = '[' ;
		$json .= '"",' ;
		$json .= '"",' ;
		$json .= '"",' ;
		$json .= '""' ;
		$json .= ']' ;
		echo base64_encode($json) ;
		//echo $json;
	}
	
	
} // fin RECHERCHE DES INFO VIA LA REF FOURNISSEUR




elseif ($_GET['what'] == 'calcul_cmd_rubis' && isset($_GET['id']) && $_GET['id']) { // CALCUL LES MONTANT GENERER DANS RUBIS
	$json = Array() ;
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	
	foreach ($_GET['id'] as $id) { // pour chaque ID de devis
		$res = mysql_query("SELECT (SELECT numero FROM artisan WHERE devis.artisan = artisan.nom) AS num_artisan,num_cmd_rubis FROM devis WHERE id=$id") or die("Impossible de trouver le devis $id ".mysql_error());
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

		array_push($json,"[$id,$prix_cmd_rubis]");

		// mise a jour de la base avec le montant pour éviter une recherche future
		mysql_query("UPDATE devis SET mtht_cmd_rubis=$prix_cmd_rubis WHERE id=$id");
	
	}

	odbc_close($loginor);
	echo "[".join(',',$json)."]";
}


// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}
?>