<?

include('../../inc/config.php');
require_once('prepa_overload.php');

// read commande line argument
$arguments = array();
$arguments['copy']		= 1 ;
$arguments['ged']		= 0 ;
$arguments['duplicata'] = false ;
$arguments['user']		= '' ;
$arguments['titre']		= 'preparation' ;
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
	} elseif (preg_match('/^--titre=(.+)$/',$a,$matches)) {
		$arguments['titre'] = $matches[1];
	} elseif (preg_match('/^--duplicata=(.+)$/',$a,$matches)) {
		$arguments['duplicata'] = $matches[1];
	} elseif (preg_match('/^--user=(.+)$/',$a,$matches)) {
		$arguments['user'] = $matches[1];
	} elseif (preg_match('/^--copy=(\d+)$/',$a,$matches)) {
		$arguments['copy'] = $matches[1];
	} elseif (preg_match('/^--ged=(\d+)$/',$a,$matches)) {
		$arguments['ged'] = $matches[1];
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
select	NOBON,DSECS,DSECA,DSECM,DSECJ,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,MONTBT,TELCL,TLCCL,TOUCL,TELCC,TLXCL,COMC1,BON.NOCLI,
		--LIVSB, -- code vendeur du bon
		TABLE_PARAM.LIRPR as NOM_VENDEUR -- libelle réduit (pour le nom du vendeur)
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
		array_push($tmp,"BON.NOLIG='$nolig'");		// ajout des n° de lignes que l'on a trouvé dans le spool
	array_push($tmp,"BON.DET21='O'");				// ajout des lignes déjà préparées
	array_push($tmp,"PROFI='9'");	// ajout des commentaires
	$lignes_a_imprimer = ' and ('.join(' or ',$tmp).') ';
}

$sql_detail = <<<EOT
select	BON.NOLIG,ARCOM,PROFI,TYCDD,CODAR, --ligne,com,profil,spe,code
		DS1DB,DS2DB,DS3DB, --designation
		CONSA,QTESA,UNICD, --conditionnement, quantité, unité
		NOMFO,REFFO,DET97, --info fournisseur + kit
		LOCAL,LOCA2,LOCA3, --localisation
		DET21 as ETAT_PREPA,DET45 as PREPARATEUR,CONCAT(DET1J,CONCAT('/',CONCAT(DET1M,CONCAT('/',CONCAT(DET1S,DET1A))))) as DATE_PREPA,DET94 as LOCAL_PREPA, --preparation
		COM_PREPA.DESCO as COMMENTAIRE_PREPA,	-- commentaire de prepa
		ARTICLE.GENCO,	-- gencode
		BON.DETQ1 as CONDITIONNEMENT, BON.DETQ6 as NB_CONDITIONNEMENT,
		DET26 as CDE_SPE_RECEPTIONNEE
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
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMBOP1 COM_PREPA
			on		COM_PREPA.NOCLI	= BON.NOCLI
				and COM_PREPA.NOBON	= BON.NOBON
				and COM_PREPA.NOLIG = BON.NOLIG
				and COM_PREPA.COMBO_TYPE = 'PRE'
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			on		BON.CODAR	= ARTICLE.NOART
where	BON.NOBON='$arguments[nobon]'
	and BON.NOCLI='$arguments[nocli]'
	and ETSBE=''		-- pas de lignes annulées
	and TRAIT<>'F'		-- pas de lignes déjà livrées
	$lignes_a_imprimer	-- uniquement les lignes données dans le fichier spool + lignes deja préparées + commentaires
order by DET21 ASC, ARCOM DESC, PROFI ASC, TYCDD DESC, LOCAL ASC, NOLIG ASC --ligne non préparé avant ligne préparée, ligne puis commentaire, stock avant spé, par ordre de local, puis par ordre de n° de ligne
EOT;

if (DEBUG) {
	fwrite(STDERR , "SQL_ENTETE : $sql_entete\n\n\n-------------------------------------------------------------\n\n");
	fwrite(STDERR , "SQL_DETAIL : $sql_detail");
	exit;
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
if (!is_array($row_entete)) {
	fwrite(STDERR , "ERREUR : Aucun bon de retrouve avec les parametres --nobon=$arguments[nobon] --nocli=$arguments[nocli]");
	exit;
}
$row_entete		= array_map('trim',$row_entete);
$detail_commande= odbc_exec($loginor,$sql_detail) ; 

// détermine le type de document "bon de prepa/bon de controle/bon de retour"
if ($row_entete['MONTBT'] >= 0) {
	if		($arguments['titre'] == 'preparation')
		define('TYPE_DOCUMENT','preparation');
	elseif	($arguments['titre'] == 'controle')
		define('TYPE_DOCUMENT','controle');
} else {
	define('TYPE_DOCUMENT','retour');
}


$row = array();

// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage(); // $nouvelle_page=true;
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

$flag_header_prepare = false;	// pour voir s'il l'on a deja affiché la section des lignes deja preparées

// largeur des colonnes
$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,LOCAL_WIDTH,UNITE_WIDTH,QTE_WIDTH,CODEBARRE_WIDTH,NOLIG_WIDTH,TYPE_CDE_WIDTH));

$kit = array();
while($row = odbc_fetch_array($detail_commande)) {
	
	$row_original = $row ;
	$row =array_map('trim',$row);

	// affiche les header si l'on change de section "a prepare" -> "deja preparée" --> "deja livrée"
	if ($row['ETAT_PREPA'] == 'O' && !$flag_header_prepare) { // 1ere ligne deja preparée
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
			$pdf->AddPage();

		/*
		$pdf->SetTextColor(255); // sur fond noir
		$pdf->SetFillColor(0);
		*/
		$pdf->SetTextColor(0);
		$pdf->SetFillColor(255);
		$pdf->Cell(0,7,"LES LIGNES SUIVANTES SONT DEJA PREPARÉES",1,1,'C',1);
		$flag_header_prepare = true; // pour en pas le réafficher deux fois
	}

	if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (ereg('^ +',$row_original['CONSA'])) { // un espace devant le commentaire défini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				//$pdf->SetFillColor(240); // pas d'espace définit un TITRE
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
		if ($row['DS2DB'])	$designation .= "\n$row[DS2DB]";
		if ($row['DS3DB'])	$designation .= "\n$row[DS3DB]";
		if ($row['CONSA'])	$designation .= "\n$row[CONSA]";
		
		// gestion des kits
		if ($row['ARCOM']=='OUI') { // attention article d'un kit, il faut l'enregistré pour le resortir sur le kit
			if (!isset($kit[$row['DET97']])) // premier article du kit
				$kit[$row['DET97']] = array();

			$kit[$row['DET97']][] = array(	$row['CODAR'],
											$row['NOMFO'],
											$row['REFFO'],
											$designation,
											$row['LOCAL'].($row['LOCA2'] ? "\n$row[LOCA2]":'').($row['LOCA3'] ? "\n$row[LOCA3]":''),
											$row['UNICD'],
											floatval($row['QTESA']),
											'', // pour un eventuel prix
											$row['NOLIG'],
											$row['TYCDD']=='SPE' ? 'S'.($row['CDE_SPE_RECEPTIONNEE']=='O'?"\nE":'') :'' // 'E' et 'S'
									);// on rajoute la piece au kit
			continue;
		}
		//print_r($kit);exit;


		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBOP1' and CDETA='' and CDCOD LIKE '%$row_entete[NOBON]$row[NOLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])
				$designation .= "\n".trim($commentaire_row['CDLIB']);

		$info_prepa = '';
		if ($row['ETAT_PREPA'] == 'O') {
			$info_prepa .= "\nLoc : ";
			$info_prepa .= $row['LOCAL_PREPA'] ? "En $row[LOCAL_PREPA]":'';
			$info_prepa .= $row['DATE_PREPA']  ? " le $row[DATE_PREPA]":'';
			$info_prepa .= $row['PREPARATEUR'] ? " par $row[PREPARATEUR]":'';
			$info_prepa .= $row['COMMENTAIRE_PREPA'] ? "\n$row[COMMENTAIRE_PREPA]":'';
		}

		$info_conditionnement = '';
		if ($row['CONDITIONNEMENT'] > 1) {
			$info_conditionnement = "\nConditionnement : ".floatval($row['NB_CONDITIONNEMENT']).' x '.floatval($row['CONDITIONNEMENT']).' '.$row['UNICD'];
		}


		$y_up_rect = $pdf->GetY();
		$y_up_before_page_chage = $y_up_rect;
		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => $row['CODAR']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
						array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 7 ),
						array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation.$info_prepa.$info_conditionnement.
										(isset($kit[$row['DET97']])?"\nKIT composé de :":''), 'text-align' => 'L', 'font-size' => 8),
						array('text' => $row['LOCAL'].( $row['LOCA2'] ? "\n$row[LOCA2]":'' ).( $row['LOCA3'] ? "\n$row[LOCA3]":'' )	,'text-align' => 'C', 'font-size' => 10), //localisation
						array('text' => $row['UNICD'],				'text-align' => 'C'), // unité
						array('text' => floatval($row['QTESA']),	'text-align' => 'C'), // quantité
						array('text' => '',							'text-align' => 'C'), // code barre
						array('text' => $row['NOLIG'],				'text-align' => 'C'), // type ligne R,P,F
						array('text' => $row['TYCDD']=='SPE' ? 'S'.($row['CDE_SPE_RECEPTIONNEE']=='O'?"\nE":'') :'' , 'text-align' => 'C') // spécial ou pas
					)
		);

		// le code barre du produit
		if ($y_up_rect > $y_up_before_page_chage)
			$y_up_rect = $y_up_before_page_chage;
		
		/*
		if ($row['GENCO'] && preg_match('/^\d{13}$/',$row['GENCO']) && $row['ARCOM'] != 'OUI' && is_ean13($row['GENCO'])) { // s'il a un gencode et que ce n'est pas un kit
			$pdf->SetFillColor(0,0,0); // noir
			$pdf->EAN13(LEFT_MARGIN + REF_WIDTH + FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH + LOCAL_WIDTH + UNITE_WIDTH + QTE_WIDTH + 1.5, $y_up_rect + 1, $row['GENCO'] , 5 , .20 );
		}
		*/

		//print_r($kit);exit;
		if (isset($kit[$row['DET97']])) { // on doit afficher les info du kit
			foreach ($kit[$row['DET97']] as $data)
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => $data[0]	,'text-align'=>'R','font-size'=>'8'),
								array('text' => $data[1]."\n".$data[2],'text-align'=>'R','font-size'=>'8'),
								array('text' => "Composante kit : ".$data[3],'text-align'=>'R','font-size'=>'8'),
								array('text' => $data[4],'text-align'=>'R','font-size'=>'8'),
								array('text' => $data[5],'text-align'=>'R','font-size'=>'8'),
								array('text' => $data[6],'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $data[8],'text-align'=>'R','font-size'=>'8'),
								array('text' => $data[9],'text-align'=>'R','font-size'=>'8')
							)
						);
			
			unset($kit[$row['DET97']]);
		}
	}
}

odbc_close($loginor);

$filename = 'bon_'.TYPE_DOCUMENT.'_'.$arguments['nobon'].'('.crc32(uniqid()).').pdf';	# defnit un nom de fchier unique
$pdf->Output($filename,'F');												# creer le fichier PDF
fwrite(STDERR , print_time()."File '$filename' generated\n");

if (isset($arguments['ged']) && $arguments['ged']) { // envoi une copie du document dans la GED
	fwrite(STDERR , print_time()."Sending to GED\n");
	copy($filename,"../ged/temp/$filename");
}

if (isset($arguments['printer'])	&& $arguments['printer'] &&
	isset($arguments['foxit_path']) && $arguments['foxit_path']) { // imprimante et foxit de spécifier	

	// imprime le nombre de copy spécifier
	for($i=1 ; $i<=$arguments['copy'] ; $i++) {
		fwrite(STDERR , print_time()."Sending to printer copy $i '$arguments[printer]'\n");
		system('"'.$arguments['foxit_path'].'" -t '.$filename.' '.$arguments['printer']);# envoie le fichier PDF vers l'imprimante
	}
	
	// supprime le fichier PDF
	fwrite(STDERR , print_time()."Deleting PDF file\n");
	unlink($filename) or die("Impossible de supprimer le fichier PDF $filename");# supprime le fichier PDF
}



/////////////////////////////////////////////////////////////////////////////////////////
function print_time() {
	fwrite(STDERR , date('[Y-m-d H:i:s] '));
	return '';
}
?>