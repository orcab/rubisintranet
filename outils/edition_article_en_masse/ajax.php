<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$droit = recuperer_droit();
if (!($droit & PEUT_EDITER_ARTICLE_EN_MASSE)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}

// connexion à RUBIS
$loginor= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");


// TEST
if (	isset($_GET['what']) && $_GET['what'] == 'test'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {
	usleep(500000);
	echo json_encode(array('result'=>1));
}



// SUSPENSION DES CODE ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'suspendre'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ETARE='S' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # article
	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSTS='S' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # stock
	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 set ETAFE='S' where NOART='".mysql_escape_string($_GET['code_article'])."'"); #article_fournisseu
	echo json_encode(array('result'=>1));
}


// ACTIVATION DES CODES ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'activer'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ETARE='' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # article
	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STSTS='' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # stock
	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 set ETAFE='' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # article_fournisseu
	echo json_encode(array('result'=>1));
}



// ACHAT INTERDIT DES CODES ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'achat-interdit'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STO11='O' where NOART='".mysql_escape_string($_GET['code_article'])."' and DEPOT='${LOGINOR_DEPOT}'"); # stock
	echo json_encode(array('result'=>1));
}


// ACHAT AUTORISER DES CODES ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'achat-autorise'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set STO11='N' where NOART='".mysql_escape_string($_GET['code_article'])."' and DEPOT='${LOGINOR_DEPOT}'"); # stock
	echo json_encode(array('result'=>1));
}



// MET AU CATALOGUE DES CODES ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'catalogue-on'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set DIAA1='OUI' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # stock
	echo json_encode(array('result'=>1));
}


// SUPPRIME DU CATALOGUE DES CODES ARTICLES
elseif (isset($_GET['what']) && $_GET['what'] == 'catalogue-off'
	&&	isset($_GET['code_article']) && $_GET['code_article']) {

	$res = odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set DIAA1='NON' where NOART='".mysql_escape_string($_GET['code_article'])."'"); # stock
	echo json_encode(array('result'=>1));
}


else {
	echo "Procedure '$_GET[what]' inconnu";
}


?>