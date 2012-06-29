<?
include('../inc/config.php');

if ($_GET['what'] == 'get_detail_box' && isset($_GET['val']) && $_GET['val']) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR DANS SQLITE
	
	// va récupérer la liste articles (et des infos) présent dans le box
	$sql = <<<EOT
select
	A.NOART,A.DESI1,A.ACTIV,
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
			(LOCAL like '%$_GET[val]%' or LOCA2 like '%$_GET[val]%' or LOCA3 like '%$_GET[val]%')	-- la localisation correspond au critere de recherche
		and PV.AGENC ='AFA' and PV.PVT09='E'	-- prix en cours
--		and QTE.QTINV>0							-- au moins 1 dans le stock
EOT;

	//echo "\n$sql"; exit;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");

	// va récupérer la liste des prix des articles du box
	$articles = array();
	$localisations = array();
	while($row = odbc_fetch_array($res)) {
		$row = array_map('trim',$row);
		//$row['DESI1'] = base64_encode($row['DESI1']);

		$sousbox = 'commun';
		foreach (split('/',join('/',array($row['LOCAL'],$row['LOCA2'],$row['LOCA3']))) as $local) { // pour chaque localisation
			$qte = 0;
			if ($local) {
				if (preg_match("/$_GET[val]([a-z]*)(-(\\d+))?/i",$local,$matches)) {
					$qte += isset($matches[3]) ? $matches[3] : 1; // on test la quantité "X25a-3"
					$sousbox = $matches[1] ? $matches[1] : 'commun';

					// on renseigne les localisations articles
					if (!isset($localisations[$sousbox]))
						$localisations[$sousbox] = array();

					array_push($localisations[$sousbox],	array(	'article'=>"$row[NOFOU];$row[REFFO]",
																	'qte'=>$qte)
								);
				}
			}
		}

		// on renseigne les infos articles
		$articles["$row[NOFOU];$row[REFFO]"] = array(	'designation'		=> utf8_encode($row['DESI1']),
														'code_fournisseur'	=> $row['NOFOU'],
														'fournisseur'		=> $row['NOMFO'],
														'reference'			=> $row['REFFO'],
														'code_mcs'			=> $row['NOART'],
														'ecotaxe'			=> $row['ECOTAXE']
													);

		/*$mode	= '';
		if	($row['PX_PUBLIC'] > 0) {

			if ($row['PX_PUBLIC'] <= $row['PX_AVEC_COEF']) { // si un prix public est renseigné, on prend le moins cher des deux
				$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_PUBLIC'],2) ;
				$mode = 'pp';
			} else {
				$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_AVEC_COEF'],2) ;
				$mode = 'adh';
			}

		} else {
			$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_AVEC_COEF'],2);	// sinon on prend le prix calculé avec la formule
			$mode = 'adh';
		}
		$articles["$row[NOFOU];$row[REFFO]"]['mode'] = $mode;*/


		
		$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = 0;
		$articles["$row[NOFOU];$row[REFFO]"]['mode'] = '';

		if		($row['PX_PUBLIC'] <= 0) {					// prix public vide, on prend le prix adh * coef
			$articles["$row[NOFOU];$row[REFFO]"]['px_public']	= $row['PX_AVEC_COEF'];
			$articles["$row[NOFOU];$row[REFFO]"]['mode']		= 'adh';

		} elseif	($row['PX_AVEC_COEF'] < $row['PX_PUBLIC'] && $row['ACTIV'] != '00D')	{	// prix adh inférieur au prix public, on prend le prix adh * coef. ne marche pas pour les articles elec
			$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = $row['PX_AVEC_COEF'];
			$articles["$row[NOFOU];$row[REFFO]"]['mode']		= 'adh';

		} else {										// prix public inférieur au prix adh, on prend le prix public
			$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = $row['PX_PUBLIC'];
			$articles["$row[NOFOU];$row[REFFO]"]['mode']		= 'pp';
		}

		$articles["$row[NOFOU];$row[REFFO]"]['px_public'] += $row['ECOTAXE'] ; // on rajoute l'écotaxe

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



// CAS PAR DEFAUT
else {
	echo "Aucune procedure selectionnée";
}
?>