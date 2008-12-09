<?
include('../../inc/config.php');
$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

 ////// RECHERCHE S'IL Y A PLUSIEURS FOURNISSEUR POUR CETTE FACTURE
if ($_GET['what'] == 'check_nb_fournisseur' && isset($_GET['no_fact']) && $_GET['no_fact']) {
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
}

?>