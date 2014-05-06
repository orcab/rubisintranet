<?

include('../inc/config.php');
require_once('overload.php');

//error_reporting(E_ALL ^ E_NOTICE);

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$vendeurs = select_vendeur();

if (!(isset($_GET['NOBON']) && $_GET['NOBON'])) { ?>
	ERREUR : Aucun N° de cde précisé.
<? 	exit;
}

$NOBON_escape = mysql_escape_string($_GET['NOBON']);
$NOCLI_escape = mysql_escape_string($_GET['NOCLI']);

$ligne_R = '';
if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) // uniquement les lignes R
	$ligne_R = "and TRAIT='R'" ;

//${'LOGINOR_PREFIX_BASE'} = 'AFZ'; // pour les tests uniquement

$sql_entete = <<<EOT
select	BON.NOCLI,NOBON,DSECS,DSECA,DSECM,DSECJ,LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,
		TELCL,TLCCL,TOUCL,TELCC,TLXCL,COMC1,
		MONTBT as MONTANT_HT,
		MTTCBT as MONTANT_TTC,
		FTRAB as FRAIS_TRANSPORT,
		ENT02 as FRAIS_TRANSPORT_GRATUIT,
		CHANTIER.CHAD1						-- nom du chantier
from	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
			on		BON.NOCLI=CLIENT.NOCLI
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AENTCHP1 CHANTIER
			on		BON.NOCHA=CHANTIER.CHCHA and BON.NOCLI=CHANTIER.CHCLI
where	NOBON='$NOBON_escape'
	and BON.NOCLI='$NOCLI_escape'
EOT;

$sql_detail = <<<EOT
select	BON.NOLIG,ARCOM,PROFI,TYCDD,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,PRINE,MONHT,NOMFO,REFFO,DET97,TANU0 as ECOTAXE,DET26,LOCAL,LOCA2,LOCA3,
		DDISS,DDISA,DDISM,DDISJ,
		DET21 as ETAT_PREPA,DET45 as PREPARATEUR,CONCAT(DET1J,CONCAT('/',CONCAT(DET1M,CONCAT('/',CONCAT(DET1S,DET1A))))) as DATE_PREPA,DET94 as LOCAL_PREPA, --preparation
		COM_PREPA.DESCO as COMMENTAIRE_PREPA	-- commentaire de prepa
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
where	BON.NOBON='$NOBON_escape'
	and BON.NOCLI='$NOCLI_escape'
	and BON.ETSBE<>'ANN'
	$ligne_R
order by BON.NOLIG
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
$row_entete		= array_map('trim',$row_entete);
$detail_commande= odbc_exec($loginor,$sql_detail) ; 


// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair


// largeur des colonnes
if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis demandé sans prix
	if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) // uniquement les lignes R
		$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,LOCAL_WIDTH,UNITE_WIDTH,QTE_WIDTH,TYPE_CDE_WIDTH));
	else
		$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,TYPE_CDE_WIDTH));
} else {
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH,TYPE_CDE_WIDTH));
}

$kit = array();
while($row = odbc_fetch_array($detail_commande)) {
	
	$row_original = $row ;
	$row =array_map('trim',$row);

	//echo $row['CODAR'];

	if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (preg_match('/^ +/',$row_original['CONSA'])) { // un espace devant le commentaire défini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				$pdf->SetFillColor(240); // pas d'espace définit un titre
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

			$kit[$row['DET97']][] = $designation." x".str_replace('.000','',$row['QTESA'])." (".str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO.")";// on rajoute la piece au kit
			continue;
		}
		
		//print_r($kit);exit;

		// INFO de PREPA
		$info_prepa = '';
		if ($row['ETAT_PREPA'] == 'O') {
			$info_prepa .= "\nInfo prépa Loc : ";
			$info_prepa .= $row['LOCAL_PREPA'] ? "En $row[LOCAL_PREPA]":'';
			$info_prepa .= $row['DATE_PREPA']  ? " le $row[DATE_PREPA]":'';
			$info_prepa .= $row['PREPARATEUR'] ? " par $row[PREPARATEUR]":'';
			$info_prepa .= $row['COMMENTAIRE_PREPA'] ? "\n$row[COMMENTAIRE_PREPA]":'';
		}


		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBOP1' and CDETA='' and CDCOD LIKE '%$row_entete[NOBON]$row[NOLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])
				$designation .= "\n".trim($commentaire_row['CDLIB']);


		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // cde demandé sans prix

			if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) { // uniquement les lignes R
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => $row['CODAR']."\n#".$row['NOLIG']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
					array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 7 ),
					array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation.$info_prepa		, 'text-align' => 'L', 'font-size' => 8),
					array('text' => $row['LOCAL'].( $row['LOCA2'] ? "\n$row[LOCA2]":'' ).( $row['LOCA3'] ? "\n$row[LOCA3]":'' )	,'text-align' => 'C', 'font-size' => 10), //localisation
					array('text' => $row['UNICD']		, 'text-align' => 'C'), // unité
					array('text' => str_replace('.000','',$row['QTESA']).($row['DET26']=='O' && $row['TYCDD']=='SPE'?"\n$row[DDISJ]/$row[DDISM]/$row[DDISS]$row[DDISA]":'')		, 'text-align' => 'C'), // quantité
					array('text' => $row['TYCDD']=='SPE' ? 'S'.($row['DET26']=='O'?"\nE":'') : ''	, 'text-align' => 'R') // spécial ou pas
					));
			} else {
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => $row['CODAR']."\n#".$row['NOLIG']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
					array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 7 ),
					array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation		, 'text-align' => 'L', 'font-size' => 8),
					array('text' => $row['UNICD']		, 'text-align' => 'C'), // unité
					array('text' => str_replace('.000','',$row['QTESA']).($row['DET26']=='O' && $row['TYCDD']=='SPE'?"\n$row[DDISJ]/$row[DDISM]/$row[DDISS]$row[DDISA]":'')		, 'text-align' => 'C'), // quantité
					array('text' => $row['TYCDD']=='SPE' ? 'S'.($row['DET26']=='O'?"\nE":'') : ''	, 'text-align' => 'R') // spécial ou pas
					));
			}

		} else { // demandé AVEC prix

			$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
				array('text' => $row['CODAR']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
				array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 7 ),
				array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation		, 'text-align' => 'L', 'font-size' => 8),
				array('text' => $row['UNICD']		, 'text-align' => 'C'), // unité
				array('text' => str_replace('.000','',$row['QTESA'])		, 'text-align' => 'C'), // quantité
				array('text' => sprintf('%0.2f',round($row['PRINE'],2)).EURO	, 'text-align' => 'R'), // prix unitaire après remise
				array('text' => $row['MONHT'].EURO	, 'text-align' => 'R'), // total après remise
				array('text' => $row['TYCDD']=='SPE' ? 'S' :''	, 'text-align' => 'R') // spécial ou pas
				));

		} // fin avec ou sans prix
		
		//print_r($kit);exit;
		if (isset($kit[$row['DET97']])) { // on doit afficher les info du kit
			foreach ($kit[$row['DET97']] as $ligne) {
				if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis demandé sans prix
					if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) { // uniquement les lignes R
						$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8')
							));
					} else {
						$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
							));
					}
				} else {
					$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8')
							));
				}
			} // fin kit
			unset($kit[$row['DET97']]);
		}

		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // pas d'eco taxe a afficher

		} else {
			if ($row['ECOTAXE']) // l'article contient de l'écotaxe
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => '','text-align'=>'R','font-size'=>'8'),
						array('text' => '','text-align'=>'R','font-size'=>'8'),
						array('text' => "Ecotaxe sur l'article $row[CODAR]",'text-align'=>'R','font-size'=>'8'),
						array('text' => '','text-align'=>'R','font-size'=>'8'),
						array('text' => str_replace('.000','',$row['QTESA']),'text-align'=>'C','font-size'=>'8'),
						array('text' => sprintf('%0.2f',$row['ECOTAXE']),'text-align'=>'R','font-size'=>'8'),
						array('text' => sprintf('%0.2f',$row['ECOTAXE']*$row['QTESA']).EURO,'text-align'=>'R','font-size'=>'8'),
						array('text' => '','text-align'=>'R','font-size'=>'8')
					)
				);
		} // fin si options sans prix
	}
}


// fin de la cde
if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // cde sans prix

} else {

	if($pdf->GetY() +  3*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

	$pdf->SetFont('helvetica','B',10);
	$pdf->SetFillColor(240); // gris clair

	// affichage des eventuels frais de port
	if ($row_entete['FRAIS_TRANSPORT'] && $row_entete['FRAIS_TRANSPORT_GRATUIT'] != 'O') {
		$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
		$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"Frais de port",1,0,'L',1);
		$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$row_entete['FRAIS_TRANSPORT'])).EURO,1,0,'R',1);
		$pdf->Ln();
	}

	// affichage du total de la commande HT
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
	$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$row_entete['MONTANT_HT'])).EURO,1,0,'R',1);
	$pdf->Ln();

	// affichage du total de la commande TTC
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL TTC",1,0,'L',1);
	$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$row_entete['MONTANT_TTC'])).EURO,1,0,'R',1);
}

// generation du pdf avec un numero unique pour que les navigateur gere bien le cache
$pdf->Output('cde_adh_'.$NOBON_escape.'('.crc32(uniqid()).').pdf','I');

odbc_close($loginor);
?>