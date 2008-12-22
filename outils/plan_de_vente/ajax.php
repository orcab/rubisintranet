<?

include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


if (isset($_GET['what']) && $_GET['what'] == 'inverse_status_article' &&
	isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$servi_avant_modif = e('SERST',odbc_fetch_array(odbc_exec($loginor,"select SERST from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='$_GET[code_article]'")));
	if ($servi_avant_modif == 'OUI') { // passer l'article en non servi
		mysql_query("UPDATE article SET servi_sur_stock=0 WHERE code_article='$_GET[code_article]'"); // mysql
		if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
			odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSER='NON' where NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
			odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set SERST='NON' where NOART='$_GET[code_article]'"); // loginor fiche article
		}
		echo "{stock:0}";
	} else { // passer l'article en servi
		mysql_query("UPDATE article SET servi_sur_stock=1 WHERE code_article='$_GET[code_article]'"); // mysql
		if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
			odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSER='OUI' where NOART='$_GET[code_article]' AND DEPOT='$LOGINOR_DEPOT'"); // loginor fiche de stock
			odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set SERST='OUI' where NOART='$_GET[code_article]'"); // loginor fiche article
		}
		echo "{stock:1}";
	}
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'inverse_etat_article' &&
		isset($_GET['code_article']) && $_GET['code_article'] &&
		isset($_GET['chemin']) && $_GET['chemin']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$etat_avant_modif = e('ETARE',odbc_fetch_array(odbc_exec($loginor,"select ETARE from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where NOART='$_GET[code_article]'")));
	if (trim($etat_avant_modif) == '') { // passer l'article en suspendu
		if (!mysql_query("DELETE FROM article WHERE code_article='$_GET[code_article]'")) { // mysql
			echo "{stock:1,debug:'Impossible de supprimer : ".ereg_replace("'","",mysql_error())."'}";
		} else {
			if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
				odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSTS='S' where NOART='$_GET[code_article]'"); // loginor fiche de stock
				odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ETARE='S' where NOART='$_GET[code_article]'"); // loginor fiche article
			}
			echo "{stock:0}";
		}
	} else { // passer l'article activé
		if (!mysql_query("INSERT INTO article (code_article,designation,chemin) VALUES ('$_GET[code_article]','Visible demain matin','$_GET[chemin]')")) { // mysql
			echo "{stock:0,debug:'Impossible de creer : ".ereg_replace("'","",mysql_error())."'}";
		} else {
			if ($_SERVER['SERVER_ADDR'] == '10.211.14.6') { // que en prod
				odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSTS='' where NOART='$_GET[code_article]'"); // loginor fiche de stock
				odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ETARE='' where NOART='$_GET[code_article]'"); // loginor fiche article
			}
			echo "{stock:1}";
		}
	}
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'detail_article' &&
		isset($_GET['code_article']) && trim($_GET['code_article'])) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$sql = "select DESI1,DESI2,DESI3,LOCAL,STOMI,STALE,STOMA,STGES,DIAA1 from ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK,${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE where ARTICLE.NOART='".mysql_escape_string($_GET['code_article'])."' and STOCK.DEPOT='$LOGINOR_DEPOT' and ARTICLE.NOART=STOCK.NOART";
	$res = odbc_exec($loginor,$sql) ;
	$row = odbc_fetch_array($res);
	foreach ($row as $key=>$val)
		$row[$key] = trim(ereg_replace("'","\\'",$val));
	echo "{desi1:'$row[DESI1]',desi2:'$row[DESI2]',desi3:'$row[DESI3]',mini:'$row[STOMI]',maxi:'$row[STOMA]',alerte:'$row[STALE]',localisation:'$row[LOCAL]',gestionnaire:'".trim($row['STGES'])."',edition_tarif:'".trim($row['DIAA1'])."'}";
	odbc_close($loginor);
}


elseif (isset($_GET['what']) && $_GET['what'] == 'valider_detail_article' &&
		isset($_GET['code_article']) && $_GET['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	// mise a jour de la fiche de stock
	$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set ".	"LOCAL='".mysql_escape_string($_GET['localisation'])."',".
												"STOMI='".mysql_escape_string($_GET['mini'])."',".
												"STOMA='".mysql_escape_string($_GET['maxi'])."',".
												"STALE='".mysql_escape_string($_GET['alerte'])."',".
												"STGES='".mysql_escape_string($_GET['gestionnaire'])."'".
			" where NOART='".mysql_escape_string($_GET['code_article'])."' and DEPOT='$LOGINOR_DEPOT'";
	odbc_exec($loginor,$sql) ;

	// mise a jour de la fiche article
	$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set DESI1='".mysql_escape_string($_GET['desi1'])."',DESI2='".mysql_escape_string($_GET['desi2'])."',DESI3='".mysql_escape_string($_GET['desi3'])."',DIAA1='".mysql_escape_string($_GET['edition_tarif'])."' where NOART='".mysql_escape_string($_GET['code_article'])."'";
	odbc_exec($loginor,$sql) ;

	mysql_query("UPDATE article SET designation='".mysql_escape_string($_GET['desi1'])."\n".mysql_escape_string($_GET['desi2'])."\n".mysql_escape_string($_GET['desi3'])."' WHERE code_article='".mysql_escape_string($_GET['code_article'])."'"); // mysql

	echo "{}";
	odbc_close($loginor);
}


elseif (isset($_POST['what']) && $_POST['what'] == 'valider_nouveau_chemin' &&
		isset($_POST['chemin']) && $_POST['chemin'] &&
		isset($_POST['code_article']) && $_POST['code_article']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	for($i=0 ; $i<sizeof($_POST['code_article']) ; $i++) {
		$tmp = explode('.',$_POST['chemin']);

		$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ".
					"ACTIV='".mysql_escape_string(isset($tmp[0])?$tmp[0]:'')."',".
					"FAMI1='".mysql_escape_string(isset($tmp[1])?$tmp[1]:'')."',".
					"SFAM1='".mysql_escape_string(isset($tmp[2])?$tmp[2]:'')."',".
					"ART04='".mysql_escape_string(isset($tmp[3])?$tmp[3]:'')."',".
					"ART05='".mysql_escape_string(isset($tmp[4])?$tmp[4]:'')."'".
				" where NOART='".mysql_escape_string($_POST['code_article'][$i])."'";
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


// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>