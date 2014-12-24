<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


if (isset($_GET['what']) && $_GET['what'] == 'inverse_servi_article' &&
	isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$servi_avant_modif = e('SERST',odbc_fetch_array(odbc_exec($loginor,"select SERST from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='$_GET[code_article]'")));
	if ($servi_avant_modif == 'OUI') { // passer l'article en non servi
		mysql_query("UPDATE article SET servi_sur_stock=0 WHERE code_article='$_GET[code_article]'"); // mysql
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 SET STSER='NON' WHERE NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET SERST='NON' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
		echo "{stock:0}";
	} else { // passer l'article en servi
		mysql_query("UPDATE article SET servi_sur_stock=1 WHERE code_article='$_GET[code_article]'"); // mysql
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 SET STSER='OUI' WHERE NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET SERST='OUI' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
		echo "{stock:1}";
	}
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'inverse_tarif_article' &&
		isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$servi_avant_modif = e('DIAA1',odbc_fetch_array(odbc_exec($loginor,"select DIAA1 from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='$_GET[code_article]'")));
	if ($servi_avant_modif == 'OUI') { // passer l'article en non sur tarif
		mysql_query("UPDATE article SET sur_tarif=0 WHERE code_article='$_GET[code_article]'"); // mysql
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET DIAA1='NON' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
		echo "{stock:0}";
	} else { // passer l'article en sur tarif
		mysql_query("UPDATE article SET sur_tarif=1 WHERE code_article='$_GET[code_article]'"); // mysql
		odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET DIAA1='OUI' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
		echo "{stock:1}";
	}
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'inverse_etat_article' &&
		isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$etat_avant_modif = e('ETARE',odbc_fetch_array(odbc_exec($loginor,"select ETARE from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='$_GET[code_article]'")));
	if (trim($etat_avant_modif) == '') { // passer l'article en suspendu
		$sql = "UPDATE article SET suspendu='1' WHERE code_article='$_GET[code_article]'";
		if (!mysql_query($sql)) { // mysql
			echo "{stock:1,debug:'Impossible de supprimer : ".ereg_replace("'","",mysql_error()."dans<br/>$sql")."'}";
		} else {
			if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
				odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 SET STSTS='S' WHERE NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
				odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET ETARE='S' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
				odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 SET ETAFE='S' WHERE NOART='$_GET[code_article]'"); // loginor fiche article fournisseur
			}
			echo "{stock:0}";
		}
	} else { // passer l'article activé
		if (!mysql_query("UPDATE article SET suspendu=0 WHERE code_article='$_GET[code_article]'")) { // mysql
			echo "{stock:0,debug:'Impossible de creer : ".ereg_replace("'","",mysql_error())."'}";
		} else {
			if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
				odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 SET STSTS='' WHERE NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
				odbc_exec($loginor,"UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET ETARE='' WHERE NOART='$_GET[code_article]'"); // loginor fiche article
				// on ne reveil pas la fiche ARTICLE FOURNISSEUR pour éviter les erreurs de référence en double pour un meme fournisseur
			}
			echo "{stock:1}";
		}
	}
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'detail_article' &&
		isset($_GET['code_article']) && trim($_GET['code_article'])) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$sql = "SELECT DESI1,DESI2,DESI3,LOCAL,LOCA2,LOCA3,STOMI,STALE,STOMA,STGES,DIAA1 FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK,${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE WHERE ARTICLE.NOART='".mysql_escape_string($_GET['code_article'])."' AND STOCK.DEPOT='$LOGINOR_DEPOT' AND ARTICLE.NOART=STOCK.NOART";
	$res = odbc_exec($loginor,$sql) ;
	$row = odbc_fetch_array($res);
	foreach ($row as $key=>$val)
		$row[$key] = trim(ereg_replace("'","\\'",$val));
	echo "{desi1:'$row[DESI1]',desi2:'$row[DESI2]',desi3:'$row[DESI3]',mini:'$row[STOMI]',maxi:'$row[STOMA]',alerte:'$row[STALE]',localisation:'$row[LOCAL]',localisation2:'$row[LOCA2]',localisation3:'$row[LOCA3]',gestionnaire:'".trim($row['STGES'])."',edition_tarif:'".trim($row['DIAA1'])."'}";
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'valider_detail_article' &&
		isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	// mise a jour de la fiche de stock
	$sql = "UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 SET ".
												"LOCAL='".mysql_escape_string($_GET['localisation'])."',".
												"LOCA2='".mysql_escape_string($_GET['localisation2'])."',".
												"LOCA3='".mysql_escape_string($_GET['localisation3'])."',".
												"STOMI='".mysql_escape_string($_GET['mini'])."',".
												"STOMA='".mysql_escape_string($_GET['maxi'])."',".
												"STALE='".mysql_escape_string($_GET['alerte'])."',".
												"STGES='".mysql_escape_string($_GET['gestionnaire'])."'".
			" WHERE NOART='".mysql_escape_string($_GET['code_article'])."' AND DEPOT='$LOGINOR_DEPOT'";
	odbc_exec($loginor,$sql) ;

	// mise a jour de la fiche article
	$sql = "UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET DIAA1='".mysql_escape_string($_GET['edition_tarif'])."' WHERE NOART='".mysql_escape_string($_GET['code_article'])."'";
	odbc_exec($loginor,$sql) ;

	echo "{}";
	odbc_close($loginor);
}


elseif (isset($_POST['what']) && $_POST['what'] == 'valider_nouveau_chemin' &&
		isset($_POST['chemin']) && $_POST['chemin'] &&
		isset($_POST['code_article']) && $_POST['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	for($i=0 ; $i<sizeof($_POST['code_article']) ; $i++) {
		$tmp = explode('.',$_POST['chemin']);

		$sql = "UPDATE ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 SET ".
					"ACTIV='".mysql_escape_string(isset($tmp[0])?$tmp[0]:'')."',".
					"FAMI1='".mysql_escape_string(isset($tmp[1])?$tmp[1]:'')."',".
					"SFAM1='".mysql_escape_string(isset($tmp[2])?$tmp[2]:'')."',".
					"ART04='".mysql_escape_string(isset($tmp[3])?$tmp[3]:'')."',".
					"ART05='".mysql_escape_string(isset($tmp[4])?$tmp[4]:'')."'".
				" WHERE NOART='".mysql_escape_string($_POST['code_article'][$i])."'";
		odbc_exec($loginor,$sql) ;

		mysql_query("UPDATE article SET ".
						"activite='".	mysql_escape_string(isset($tmp[0])?$tmp[0]:'')."',".
						"famille='".	mysql_escape_string(isset($tmp[1])?$tmp[1]:'')."',".
						"sousfamille='".mysql_escape_string(isset($tmp[2])?$tmp[2]:'')."',".
						"chapitre='".	mysql_escape_string(isset($tmp[3])?$tmp[3]:'')."',".
						"souschapitre='".mysql_escape_string(isset($tmp[4])?$tmp[4]:'')."',".
						"chemin='".		mysql_escape_string($_POST['chemin'])."' ".
					" WHERE code_article='".mysql_escape_string($_POST['code_article'][$i])."'");
	}

	//echo "{debug:\"$sql\"}";
	echo "{}";
	odbc_close($loginor);
}


//////////////////////// CHERCHE SI DES DONNEE HYDRA EXISTE //////////////////////////////////////////:
elseif (	isset($_GET['what'])		&& $_GET['what'] == 'hydra_exists'
		&&	isset($_GET['fournisseur'])	&& $_GET['fournisseur']
		&&	isset($_GET['ref'])			&& $_GET['ref']
		&&	isset($_GET['type'])		&& $_GET['type']) {
	
		$tmp = trim(join('',file("http://www.coopmcs.com/hydra/getfile.php?fournisseur=".urlencode($_GET['fournisseur'])."&ref=".urlencode($_GET['ref'])."&type=".urlencode($_GET['type'])."&exists=1")));
		echo json_encode(array(	'response' 	=> array(
														'exists' => $tmp),
								'request'	=> array(
														'fournisseur'	=> $_GET['fournisseur'],
														'ref'			=> $_GET['ref'],
														'type'			=> $_GET['type']
													)
								)
						);
}



//////////////////////// CHERCHE SI DES DONNEE HYDRA INFO //////////////////////////////////////////:
elseif (	isset($_GET['what'])		&& $_GET['what'] == 'hydra_info'
		&&	isset($_GET['fournisseur'])	&& $_GET['fournisseur']
		&&	isset($_GET['ref'])			&& $_GET['ref']) {
	
		$tmp = join('',file("http://www.coopmcs.com/hydra/getfile.php?fournisseur=".urlencode($_GET['fournisseur'])."&ref=".urlencode($_GET['ref'])."&info=1&json=1"));
		$json = json_decode($tmp,true);
		echo json_encode(array(	'response' 	=> array($json['response']),
								'request'	=> array(	'fournisseur'	=> $_GET['fournisseur'],
														'ref'			=> $_GET['ref']
													)
								)
						);
					
}




// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>