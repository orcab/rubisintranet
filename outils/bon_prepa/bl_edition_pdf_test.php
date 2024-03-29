<?

include('../../inc/config.php');
require_once('bl_overload.php');

// read commande line argument
$arguments = array();
$arguments['copy']		= 1 ;
$arguments['duplicata'] = false ;
$arguments['user']		= '' ;
foreach($argv as $a) {
	if		 (preg_match('/^--nocli=(.+)$/',$a,$matches)) {
		$arguments['nocli'] = strtoupper($matches[1]);
	} elseif (preg_match('/^--nobon=(.+)$/',$a,$matches)) {
		$arguments['nobon'] = strtoupper($matches[1]);
	} elseif (preg_match('/^--printer=(.+)$/',$a,$matches)) {
		$arguments['printer'] = $matches[1];
	} elseif (preg_match('/^--foxit_path=(.+)$/',$a,$matches)) {
		$arguments['foxit_path'] = $matches[1];
	} elseif (preg_match('/^--lignes=(.+)$/',$a,$matches)) {
		$arguments['lignes'] = $matches[1];
	} elseif (preg_match('/^--duplicata=(.+)$/',$a,$matches)) {
		$arguments['duplicata'] = $matches[1];
	} elseif (preg_match('/^--user=(.+)$/',$a,$matches)) {
		$arguments['user'] = $matches[1];
	} elseif (preg_match('/^--copy=(\d+)$/',$a,$matches)) {
		$arguments['copy'] = $matches[1];
	}

}

if (!(isset($arguments['nobon']) && $arguments['nobon'])) {
	fwrite(STDERR , "ERREUR : parametre --nobon vide ou manquant");
	exit;
}
if (!(isset($arguments['nocli']) && $arguments['nocli'])) {
	fwrite(STDERR , "ERREUR : parametre --nocli vide ou manquant");
	exit;
}

define('DEBUG',false);

$sql_entete = <<<EOT
select	NOBON,DSECS,DSECA,DSECM,DSECJ,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,MONTBT,TELCL,TLCCL,TOUCL,TELCC,TLXCL,COMC1,BON.NOCLI,TYVTE,
		TABLE_PARAM.LIRPR as NOM_VENDEUR, -- libelle r�duit (pour le nom du vendeur)
		CLIENT.BLVAL,	-- BL valoris� ou pas
		NINT1 as NB_COLIS, NINT2 as NB_PALETTE, NINT3 as NB_COURONNE, NINT4 as NB_PAROI, NINT5 as NB_PVC, NINT6 as NB_CUIVRE
from			${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
		on		BON.NOCLI = CLIENT.NOCLI
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1 TABLE_PARAM
		on		TABLE_PARAM.TYPPR='LIV'		-- liste des vendeur
			and	TABLE_PARAM.CODPR=BON.LIVSB	-- code vendeur du bon
where		NOBON='$arguments[nobon]'
	and BON.NOCLI='$arguments[nocli]'
EOT;

$lignes_a_imprimer = '';
if (isset($arguments['lignes']) && $arguments['lignes']) {
	$tmp = array();
	foreach (explode(',',$arguments['lignes']) as $nolig)
		array_push($tmp,"BON.CODAR='$nolig'");		// ajout des n� de lignes que l'on a trouv� dans le spool
	array_push($tmp,"BON.PROFI='9'");				// avec les commentaires
	$lignes_a_imprimer = ' and ('.join(' or ',$tmp).') ';
}

$sql_detail = <<<EOT
select	NOLIG,ARCOM,PROFI,TYCDD,CODAR, --ligne,com,profil,spe,code
		DS1DB,DS2DB,DS3DB, --designation
		CONSA,QTESA,UNICD, --conditionnement, quantit�, unit�
		NOMFO,REFFO,DET97, --info fournisseur + kit
		BON.DETQ1 as CONDITIONNEMENT, BON.DETQ6 as NB_CONDITIONNEMENT,
		BON.PRINE,BON.MONHT,
		TRAIT
from	${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 BON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on	BON.NOFOU=FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		BON.CODAR = ARTICLE_FOURNISSEUR.NOART
				and	BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1 TAXE
			on BON.TPFAR=TAXE.CODPR and TAXE.TYPPR='TPF'
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK
			on		BON.CODAR   = STOCK.NOART
				and	STOCK.DEPOT = BON.AGENC
				and	STOCK.STSTS = ''
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			on		BON.CODAR	= ARTICLE.NOART
where	BON.NOBON='$arguments[nobon]'
	and BON.NOCLI='$arguments[nocli]'
	and ETSBE=''		-- pas de lignes annul�es
	--and TRAIT='F'		-- lignes d�j� livr�es
	$lignes_a_imprimer	-- uniquement les lignes donn�es dans le fichier spool
order by TRAIT ASC, BON.NOLIG ASC -- par ordre de n� de ligne
EOT;

if (DEBUG) {
	fwrite(STDERR , "SQL_ENTETE : $sql_entete");
	fwrite(STDERR , "SQL_DETAIL : $sql_detail");
	exit;
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
if (!is_array($row_entete)) {
	fwrite(STDERR , "ERREUR : Aucun bon de retrouve avec les parametres --nobon=$arguments[nobon] --nocli=$arguments[nocli]");
	exit;
}
$row_entete		= array_map('trim',$row_entete);
$detail_commande= odbc_exec($loginor,$sql_detail) ; 


$row = array();

// g�n�ration du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage(); // $nouvelle_page=true;
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

$flag_header_prepare = false;	// pour voir s'il l'on a deja affich� la section des lignes deja prepar�es

// largeur des colonnes
$pdf->SetWidths(array(CODAR_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_WIDTH,UNITE_WIDTH,QTE_WIDTH,PU_WIDTH,TOTAL_WIDTH,TYPE_CDE_WIDTH));

$total_bon = 0 ;
$kit = array();
while($row = odbc_fetch_array($detail_commande)) {
	
	$row_original = $row ;
	$row =array_map('trim',$row);

	if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (preg_match('/^ +/',$row_original['CONSA'])) { // un espace devant le commentaire d�fini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				//$pdf->SetFillColor(240); // pas d'espace d�finit un TITRE
				$pdf->SetFillColor(255);
			}
			
			$pdf->SetFont('','B');
			if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
				$pdf->AddPage();

			$pdf->Cell(0,7,$row['CONSA'],1,1,'C',1);
			$pdf->SetFillColor(255);
		}
	} else { // cas d'un article
	
		$designation = $row['DS1DB'] ;
		if ($row['DS2DB'])			$designation .= "\n$row[DS2DB]";
		if ($row['DS3DB'])			$designation .= "\n$row[DS3DB]";
		if ($row['CONSA'])			$designation .= "\n$row[CONSA]";
		if ($row['TRAIT'] == 'R')	$designation .= "\n                                    /!\ TOUJOURS EN COMMANDE /!\\" ;
		
		// gestion des kits
		if ($row['ARCOM']=='OUI') { // attention article d'un kit, il faut l'enregistrer pour le resortir sur le kit
			if (!isset($kit[$row['DET97']])) // premier article du kit
				$kit[$row['DET97']] = array();

			$kit[$row['DET97']][] = $designation." x".str_replace('.000','',$row['QTESA'])." (".str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO.")";// on rajoute la piece au kit
			continue;
		}
		
		// on cherche les commentaires associ� � la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBOP1' and CDETA='' and CDCOD LIKE '%$row_entete[NOBON]$row[NOLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])
				$designation .= "\n".trim($commentaire_row['CDLIB']);

		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => $row['CODAR']	, 'font-style' => 'B',					'text-align' => 'C', 'font-size' => 10 ),
						array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":''),		'font-style' => 'B', 'text-align' => 'C', 'font-size' => 7 ),
						array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation,	'text-align' => 'L', 'font-size' => 8),
						array('text' => $row['UNICD'],											'text-align' => 'C'), // unit�
						array('text' => floatval($row['QTESA']),								'text-align' => 'C'), // quantit�
						array('text' => $row_entete['BLVAL']=='OUI' ? sprintf('%0.2f',round($row['PRINE'],2)).EURO : '',			'text-align' => 'R'), // PU
						array('text' => $row_entete['BLVAL']=='OUI' ? sprintf('%0.2f',round($row['MONHT'],2)).EURO : '',			'text-align' => 'R'), // Total
						array('text' => ($row['TYCDD']=='SPE' && $row['TRAIT']=='R'? 'S':''),'text-align' => 'C') // sp�cial ou pas
					)
		);

		$total_bon += $row['MONHT'];


		if (isset($kit[$row['DET97']])) { // on doit afficher les info du kit
			foreach ($kit[$row['DET97']] as $ligne)
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8')
							)
						);
			
			unset($kit[$row['DET97']]);
		}
	}
} // fin while article

// infos de colisage
//NINT1 as NB_COLIS, NINT2 as NB_PALETTE, NINT3 as NB_COURONNE, NINT4 as NB_PAROI, NINT5 as NB_PVC, NINT6 as NB_CUIVRE
$info_colisage = array();
$row_entete['NB_COLIS']		= (int)$row_entete['NB_COLIS'];
$row_entete['NB_PALETTE']	= (int)$row_entete['NB_PALETTE'];
$row_entete['NB_COURONNE']	= (int)$row_entete['NB_COURONNE'];
$row_entete['NB_PAROI']		= (int)$row_entete['NB_PAROI'];
$row_entete['NB_PVC']		= (int)$row_entete['NB_PVC'];
$row_entete['NB_CUIVRE']	= (int)$row_entete['NB_CUIVRE'];
if ($row_entete['NB_COLIS'] > 0)	array_push($info_colisage,$row_entete['NB_COLIS']." colis");
if ($row_entete['NB_PALETTE'] > 0)	array_push($info_colisage,$row_entete['NB_PALETTE']." palette".		($row_entete['NB_PALETTE']>1?'s':''));
if ($row_entete['NB_COURONNE'] > 0)	array_push($info_colisage,$row_entete['NB_COURONNE']." couronne".	($row_entete['NB_COURONNE']>1?'s':''));
if ($row_entete['NB_PAROI'] > 0)	array_push($info_colisage,$row_entete['NB_PAROI']." paroi".			($row_entete['NB_PAROI']>1?'s':''));
if ($row_entete['NB_PVC'] > 0)		array_push($info_colisage,$row_entete['NB_PVC']." barre".			($row_entete['NB_PVC']>1?'s':'')." de PVC");
if ($row_entete['NB_CUIVRE'] > 0)	array_push($info_colisage,$row_entete['NB_CUIVRE']." barre".		($row_entete['NB_CUIVRE']>1?'s':'')." de cuivre");
if (sizeof($info_colisage)) { // si des infos de colisage renseign�es
	if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 29) $pdf->AddPage(); // check le saut de page
	$pdf->SetFont('helvetica','B',11);
	$pdf->Cell(CODAR_WIDTH + FOURNISSEUR_WIDTH + DESIGNATION_WIDTH + UNITE_WIDTH + QTE_WIDTH + PU_WIDTH + TOTAL_WIDTH + TYPE_CDE_WIDTH,7,"Info de colisage : ".join(' / ',$info_colisage),1,0,'C',1);
	$pdf->SetXY(LEFT_MARGIN,$pdf->GetY() + 7);
}

// valorisation du BL
if ($row_entete['BLVAL']=='OUI') {
	// fin de la cde
	if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 29) $pdf->AddPage();// check le saut de page
	$pdf->SetFont('helvetica','B',10);
	$pdf->SetFillColor(240); // gris clair
	$pdf->Cell(CODAR_WIDTH + FOURNISSEUR_WIDTH + DESIGNATION_WIDTH + UNITE_WIDTH + QTE_WIDTH + PU_WIDTH,7,"MONTANT TOTAL HT",1,0,'R',1);
	$pdf->Cell(TOTAL_WIDTH + TYPE_CDE_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$total_bon)).EURO,1,0,'R',1);
}

odbc_close($loginor);

$filename = 'bon_livraison_'.$arguments['nobon'].'('.crc32(uniqid()).').pdf';	# defnit un nom de fchier unique
$pdf->Output($filename,'F');												# creer le fichier PDF
fwrite(STDERR , print_time()."File '$filename' generated\n");

if (isset($arguments['printer'])	&& $arguments['printer'] &&
	isset($arguments['foxit_path']) && $arguments['foxit_path']) { // imprimante et foxit de sp�cifier	

	// imprime le nombre de copy sp�cifier
	for($i=1 ; $i<=$arguments['copy'] ; $i++) {
		fwrite(STDERR , print_time()."Sending to printer copy $i '$arguments[printer]'... ");
		system('"'.$arguments['foxit_path'].'" -t '.$filename.' '.$arguments['printer']);# envoie le fichier PDF vers l'imprimante
		fwrite(STDERR , "ok\n");
	}
	
	// supprime le fichier PDF
	fwrite(STDERR , print_time()."Deleting PDF file... ");
	unlink($filename) or die("Impossible de supprimer le fichier PDF $filename");# supprime le fichier PDF
	fwrite(STDERR , "ok\n");
}



/////////////////////////////////////////////////////////////////////////////////////////
function print_time() {
	fwrite(STDERR , date('[Y-m-d H:i:s] '));
	return '';
}
?>