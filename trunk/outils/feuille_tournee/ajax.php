<?

include('../../inc/config.php');

if (isset($_GET['what'])			&& $_GET['what'] == 'modifie_date_liv' &&
	isset($_GET['nobon'])			&& $_GET['nobon'] &&
	isset($_GET['nocli'])			&& $_GET['nocli'] &&
	isset($_GET['date_ddmmyyyy'])	&& $_GET['date_ddmmyyyy']) {

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	// requete qui modifie la date de livraison d'un bon
	$jour	= substr($_GET['date_ddmmyyyy'],0,2);
	$mois	= substr($_GET['date_ddmmyyyy'],3,2);
	$siecle = substr($_GET['date_ddmmyyyy'],6,2);
	$annee  = substr($_GET['date_ddmmyyyy'],8,2);
	$sql = <<<EOT
update	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1
set		DLSSB='$siecle', DLASB='$annee', DLMSB='$mois', DLJSB='$jour'
where		NOBON='$_GET[nobon]'
		and NOCLI='$_GET[nocli]'
EOT;
	odbc_exec($loginor,$sql);

	echo "{debug:'nobon=$_GET[nobon] / nocli=$_GET[nocli] / jour=$jour / mois=$mois / siecle=$siecle / annee=$annee'}";
	//echo "{debug:\"$sql\"}";
	//echo "{test:'ok'}";
	//echo $sql;

	odbc_close($loginor);
}


// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>