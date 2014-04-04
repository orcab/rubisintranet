<?
include('../inc/config.php');

////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR DANS SQLITE
if (isset($_GET['what']) && $_GET['what'] == 'get_detail_box' && isset($_GET['box']) && $_GET['box']) {
	
	$box_escape = mysql_escape_string($_GET['box']);

	// va récupérer la liste articles (et des infos) présent dans le box
	$sql = <<<EOT
select
	A.NOART,A.DESI1,A.ACTIV,A.FAMI1,A.SFAM1,ART04,ART05,
	AF.NOFOU,AF.REFFO,
	F.NOMFO,
	S.LOCAL, S.LOCA2, S.LOCA3,
	(PV.PVEN1 * $COEF_EXPO) as PX_AVEC_COEF,
	PV.PVEN6 as PX_PUBLIC,
	TANU0 as ECOTAXE												-- ecotaxe dans la table ATABLEP1
from			${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 AF
		on  A.NOART=AF.NOART and A.FOUR1=AF.NOFOU
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
		on  A.NOART=S.NOART and S.DEPOT='EXP'
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 F
		on  A.FOUR1=F.NOFOU
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOCKP1 QTE
		on QTE.NOART=A.NOART and QTE.DEPOT='EXP' 
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARPVP1 PV
		on A.NOART=PV.NOART
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1 TAXE
				on A.TPFAR=TAXE.CODPR and TAXE.TYPPR='TPF'
where
			(LOCAL like '%$box_escape%' or LOCA2 like '%$box_escape%' or LOCA3 like '%$box_escape%')	-- la localisation correspond au critere de recherche
		and PV.AGENC ='AFA' and PV.PVT09='E'	-- prix en cours
--		and QTE.QTINV>0							-- au moins 1 dans le stock
EOT;

	//echo "\n$sql"; exit;

	$loginor  	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res 		= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");

	// va récupérer la liste des prix des articles du box
	$articles = array();
	$localisations = array();
	while($row = odbc_fetch_array($res)) {
		$row = array_map('trim',$row);
		//$row['DESI1'] = base64_encode($row['DESI1']);
		//$cle_article = "$row[NOFOU];$row[REFFO]";
		$cle_article = $row['NOART'];

		$sousbox = 'commun';
		foreach (split('/',join('/',array($row['LOCAL'],$row['LOCA2'],$row['LOCA3']))) as $local) { // pour chaque localisation
			$qte = 0;
			if ($local) {
				if (preg_match("/$_GET[box]([a-z]*)(-(\\d+))?/i",$local,$matches)) {
					$qte += isset($matches[3]) ? $matches[3] : 1; // on test la quantité "X25a-3"
					$sousbox = $matches[1] ? $matches[1] : 'commun';

					// on renseigne les localisations articles
					if (!isset($localisations[$sousbox]))
						$localisations[$sousbox] = array();

					array_push($localisations[$sousbox], array(	'article'=>$cle_article,'qte'=>$qte) );
				}
			}
		}

		// on renseigne les infos articles
		$articles[$cle_article] = array(	'designation'		=> utf8_encode($row['DESI1']),
											'code_fournisseur'	=> $row['NOFOU'],
											'fournisseur'		=> $row['NOMFO'],
											'reference'			=> $row['REFFO'],
											'code_mcs'			=> $row['NOART'],
											'ecotaxe'			=> $row['ECOTAXE'],
											'activite'			=> $row['ACTIV'],
											'famille'			=> $row['FAMI1'],
											'sousfamille'		=> $row['SFAM1'],
											'chapitre'			=> $row['ART04'],
											'souschapitre'		=> $row['ART05']
										);

		$articles[$cle_article]['px_public'] 	= 0;
		$articles[$cle_article]['mode'] 		= '';

		if		($row['PX_PUBLIC'] <= 0) {					// prix public vide, on prend le prix adh * coef
			$articles[$cle_article]['px_public']	= $row['PX_AVEC_COEF'];
			$articles[$cle_article]['mode']			= 'adh';

		} elseif	($row['PX_AVEC_COEF'] < $row['PX_PUBLIC'] && $row['ACTIV'] != '00D')	{	// prix adh inférieur au prix public, on prend le prix adh * coef. ne marche pas pour les articles elec
			$articles[$cle_article]['px_public'] 	= $row['PX_AVEC_COEF'];
			$articles[$cle_article]['mode']			= 'adh';

		} else {										// prix public inférieur au prix adh, on prend le prix public
			$articles[$cle_article]['px_public'] 	= $row['PX_PUBLIC'];
			$articles[$cle_article]['mode']			= 'pp';
		}

		$articles[$cle_article]['px_public'] += $row['ECOTAXE'] ; // on rajoute l'écotaxe

	} // while chaque article

//	print_r($articles);
//	print_r($localisations);
//	exit;

	header('Content-type: text/json');
	header('Content-type: application/json');

	echo json_encode(array(	'articles'	=>	$articles,
							'sousboxs'	=>	$localisations
							)
					);
} // fin 'get_detail_box'



////// ENREGISTRE LES TITRES DES BOX ET SOUS BOX
elseif (isset($_POST['what']) && $_POST['what'] == 'save_box_titles' && isset($_POST['box']) && $_POST['box']) {
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
	// efface les précédents titres
	mysql_query("DELETE FROM libelle_box_expo WHERE box='".strtolower(mysql_escape_string($_POST['box']))."'") ;

	// enregistre les nouveaux titres
	$sql = "INSERT INTO libelle_box_expo (libelle,box,ordre,creation_ip,creation_date) VALUES ";
	$values = array();
	for($i=0 ; $i<sizeof($_POST['titles']) ; $i++)
		$values[] = "('".utf8_decode(mysql_escape_string($_POST['titles'][$i]))."','".strtolower(mysql_escape_string($_POST['box']))."','".($i+1)."','$_SERVER[REMOTE_ADDR]',NOW())";
	$sql .= join(',',$values); // (values), (values), (values)...
	if (!mysql_query($sql)) // insertion des données
		echo "Erreur dans l'insertion des titres : ".mysql_error(); // en cas d'erreur on remonte l'info
}



////// RECUPERE LES TITRES DES BOX ET SOUS BOX
elseif (isset($_GET['what']) && $_GET['what'] == 'get_box_titles' && isset($_GET['box']) && $_GET['box']) {
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
	
	// select les titres du box
	$sql = "SELECT libelle FROM libelle_box_expo WHERE box='".strtolower(mysql_escape_string($_GET['box']))."' ORDER BY ordre ASC";
	$res = mysql_query($sql);
	if (!$res) // select des données
		echo "Erreur dans la selection des titres : ".mysql_error(); // en cas d'erreur on remonte l'info

	$titles = array();
	while($row = mysql_fetch_array($res))
		$titles[] = utf8_encode($row['libelle']);
	echo json_encode($titles);
}



// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
	//var_dump($_GET);
	//var_dump($_POST);
}
?>