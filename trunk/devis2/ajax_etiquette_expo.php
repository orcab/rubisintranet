<?
include('../inc/config.php');
define('SQLITE_DATABASE','../scripts/catalfou.sqlite');

if ($_GET['what'] == 'get_detail_box' && isset($_GET['val']) && $_GET['val']) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR DANS SQLITE
	
	// va récupérer la liste articles (et des infos) présent dans le box
	$sql = <<<EOT
select
	A.NOART,A.DESI1,
	AF.NOFOU,AF.REFFO,
	F.NOMFO,
	S.LOCAL, S.LOCA2, S.LOCA3
from			${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 AF
		on  A.NOART=AF.NOART and A.FOUR1=AF.NOFOU
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
		on  A.NOART=S.NOART and S.DEPOT='${LOGINOR_DEPOT}'
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 F
		on  A.FOUR1=F.NOFOU
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOCKP1 QTE
		on QTE.NOART=A.NOART and QTE.DEPOT='${LOGINOR_DEPOT}' 
where
			(LOCAL like '%$_GET[val]%' or LOCA2 like '%$_GET[val]%' or LOCA3 like '%$_GET[val]%')
		and QTE.QTINV>0
EOT;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");

	// va récupérer la liste des prix des articles du box
	$articles = array();
	while($row = odbc_fetch_array($res)) {
		$row = array_map('trim',$row);
		$qte = 0;
		foreach (split('/',join('/',array($row['LOCAL'],$row['LOCA2'],$row['LOCA3']))) as $local) { // pour chaque localisation
			if ($local) {
				if (preg_match("/$_GET[val](-(\\d+))?/",$local,$matches)) {
					$qte += isset($matches[2]) ? $matches[2] : 1; // on test la quantité "X25-3"
				}
			}
		}

		$articles["$row[NOFOU].$row[REFFO]"] = array(	'designation'		=> $row['DESI1'],
														'code_fournisseur'	=> $row['NOFOU'],
														'fournisseur'		=> $row['NOMFO'],
														'reference'			=> $row['REFFO'],
														'qte'				=> $qte,
														'code_expo'			=> $row['NOART']
													);
	}

	//print_r($articles);


	if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
	try {
		$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
		$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
	} catch (PDOException $exception) {
		echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
		die ($exception->getMessage());
	}


	$tmp = array();
	foreach ($articles as $article => $data) {
		array_push($tmp,"(code_fournisseur='$data[code_fournisseur]' AND reference='$data[reference]')");
	}
	$tmp = join(' OR ',$tmp);
	$sql = <<<EOT
SELECT
	code_fournisseur,reference,
	prix6 AS px_public, (prix1 * 1.5) AS px_public_calcule
from	articles
where
	$tmp
EOT;

	//echo "\n$sql"; exit;
	$res = $sqlite->query($sql) or die("Impossible de lancer la requete des prix : ".array_pop($sqlite->errorInfo()));
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if	($row['px_public'] > 0)
			$articles["$row[code_fournisseur].$row[reference]"]['px_public'] = round(min($row['px_public'],$row['px_public_calcule']),2); // si un prix public est renseigné, on prend le moins cher des deux
		else
			$articles["$row[code_fournisseur].$row[reference]"]['px_public'] = round($row['px_public_calcule'],2);	// sinon on prend le prix calculé avec la formule
	}

	echo json_encode($articles);
} // fin 'get_detail_box'




// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}


?>