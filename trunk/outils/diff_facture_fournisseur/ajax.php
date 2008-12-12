<?
include('../../inc/config.php');


 ////// RECHERCHE S'IL Y A PLUSIEURS FOURNISSEUR POUR CETTE FACTURE
if (isset($_GET['what']) && $_GET['what'] == 'check_nb_fournisseur' &&
	isset($_GET['no_fact']) && $_GET['no_fact']) {
		$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

		$nofact_escape = trim(strtoupper(mysql_escape_string($_GET['no_fact'])));

		// on regarde si on a plusieur fournisseur qui ont le meme n° de facture
		$sql = <<<EOT
select DISTINCT(CFAFOU) as CODE_FOURNISSEUR,NOMFO
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFAENP1 CONTROLE_FACTURE, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
where	
		CEFNU='$nofact_escape'
		and CONTROLE_FACTURE.CFAFOU=FOURNISSEUR.NOFOU
EOT;
		//echo "<br>\n$sql\n<br>";
		$res = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete de recherche des fournisseurs sur cette facture ($sql)");
		$fournisseurs = array();
		while($row = odbc_fetch_array($res)) {
			array_push($fournisseurs, "['".trim($row['CODE_FOURNISSEUR'])."','".trim($row['NOMFO'])."']"); // structure JSON [ [code1,nom1],[code2,nom2], ... ]
		}
		echo '['.join(',',$fournisseurs).']';
		//print_r($fournisseurs);

		odbc_close($loginor);
}


// sauevgarde des modifications manuelle des différences
elseif (isset($_POST['what']) && $_POST['what'] == 'save_diff' &&
		isset($_POST['id']) && $_POST['id'] &&
		isset($_POST['diff']) && isset($_POST['com'])) {
	
		$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
		$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

		$id_escape	= trim(strtoupper(mysql_escape_string($_POST['id'])));
		$diff_escape	= trim(strtoupper(mysql_escape_string($_POST['diff'])));
		$com_escape		= trim(strtoupper(mysql_escape_string($_POST['com'])));

		mysql_query("UPDATE diff_cde_fourn SET diff=$diff_escape, commentaire='$com_escape' WHERE id=$id_escape") ;
		echo (mysql_affected_rows($mysql) == 1) ? '' : "Une erreur est survenu, impossible d'enregistrer les modifications ou aucune modification effectée";

		mysql_close($mysql);
}
?>