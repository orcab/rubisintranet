<?
include('../inc/config.php');

if ($_GET['what'] == 'get_detail_box' && isset($_GET['val']) && $_GET['val']) { ////// RECHERCHE DES INFO VIA LA REF FOURNISSEUR DANS SQLITE
	
	// va r�cup�rer la liste articles (et des infos) pr�sent dans le box
	$sql = <<<EOT
select
	A.NOART,A.DESI1,
	AF.NOFOU,AF.REFFO,
	F.NOMFO,
	S.LOCAL, S.LOCA2, S.LOCA3,
	(PV.PVEN1 * $COEF_EXPO) as PX_AVEC_COEF,PV.PVEN6 as PX_PUBLIC
from			${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 AF
		on  A.NOART=AF.NOART and A.FOUR1=AF.NOFOU
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
		on  A.NOART=S.NOART and S.DEPOT='EXP'
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 F
		on  A.FOUR1=F.NOFOU
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOCKP1 QTE
		on QTE.NOART=A.NOART and QTE.DEPOT='EXP' 
	left join AFAGESTCOM.ATARPVP1 PV
		on A.NOART=PV.NOART
where
			(LOCAL like '%$_GET[val]%' or LOCA2 like '%$_GET[val]%' or LOCA3 like '%$_GET[val]%')
		and PV.AGENC ='AFA' and PV.PVT09='E'
--		and QTE.QTINV>0
EOT;

	//echo "\n$sql"; exit;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");

	// va r�cup�rer la liste des prix des articles du box
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
					$qte += isset($matches[3]) ? $matches[3] : 1; // on test la quantit� "X25a-3"
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
														'code_mcs'			=> $row['NOART']	
													);

		$mode	= '';
		if	($row['PX_PUBLIC'] > 0) {
			if ($row['PX_PUBLIC'] <= $row['PX_AVEC_COEF']) { // si un prix public est renseign�, on prend le moins cher des deux
				$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_PUBLIC'],2) ;
				$mode = 'pp';
			} else {
				$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_AVEC_COEF'],2) ;
				$mode = 'adh';
			}
		} else {
			$articles["$row[NOFOU];$row[REFFO]"]['px_public'] = round($row['PX_AVEC_COEF'],2);	// sinon on prend le prix calcul� avec la formule
			$mode = 'adh';
		}
		$articles["$row[NOFOU];$row[REFFO]"]['mode'] = $mode;

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
	echo "Aucune procedure selectionn�e";
}
?>