<?

include('../inc/config.php');
require_once('overload.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$vendeurs = select_vendeur();

if (!(isset($_GET['NOBON']) && $_GET['NOBON'])) { ?>
	ERREUR : Aucun N° de devis précisé.
<? 	exit;
}

$NOBON_escape = mysql_escape_string($_GET['NOBON']);
$NOCLI_escape = mysql_escape_string($_GET['NOCLI']);

$sql_entete = <<<EOT
select NOBON,ENTETE.NOCLI,DSECM,DSECJ,DSECS,DSECA,LIVSB,RFCSB,BUDSB,AD1SB,AD2SB,CPOSB,NOMSB,VALFS,VALFA,VALFM,VALFJ,CATCL
from ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBVP1 ENTETE, ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
where	NOBON='$NOBON_escape'
	and ENTETE.NOCLI='$NOCLI_escape'
	and ENTETE.NOCLI=CLIENT.NOCLI
EOT;

$sql_detail = <<<EOT
select NOLIG,ARCOM,CODAR,DS1DB,DS2DB,DS3DB,CONSA,FOUR1,QTESA,UNICD,PRINE,EDIT1,REFFO,DET97,TANU0 as ECOTAXE
from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBVP1 DEVIS
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
		on DEVIS.CODAR=ARTICLE_FOURNISSEUR.NOART
			and DEVIS.NOFOU=ARTICLE_FOURNISSEUR.NOFOU
			and ARTICLE_FOURNISSEUR.AGENC='$LOGINOR_AGENCE'
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1 TAXE
		on DEVIS.TPFAR=TAXE.CODPR and TAXE.TYPPR='TPF'
where	NOBON='$NOBON_escape'
	and DEVIS.NOCLI='$NOCLI_escape'
	and ETSBE<>'ANN'
-- 	and CODAR<>'' -- suite a MAJ rubis v6
order by NOLIG
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_devis	= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_devis);
$row_entete		= array_map('trim',$row_entete);
$detail_devis	= odbc_exec($loginor,$sql_detail) ; 


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
if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) // devis demandé sans prix
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH));
else
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH));

$total_ht = 0;
$sous_total_ht = 0;
$kit = array();
while($row = odbc_fetch_array($detail_devis)) {

	$row_original = $row ;
	$row =array_map('trim',$row);

	$row['REFFO'] = $row['REFFO'] ? $row['REFFO'] : 'Divers';
	
	$row['QTESA'] = preg_replace('/\.?0+$/','',$row['QTESA']);
	$row['PRINE'] = sprintf('%0.2f',$row['PRINE']);

	if ($row['CONSA'] && $row['PRINE'] <= 0) { // cas d'un commentaire
		if (preg_match('/^ +/',$row_original['CONSA'])) { // un espace devant le commentaire défini un COMMENTAIRE
			$pdf->SetFillColor(255);
		} else {
			$pdf->SetFillColor(230); // pas d'espace définit un titre
		}
		
		$pdf->SetFont('','B');
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
			$pdf->AddPage();

		if ($row['EDIT1'] == 'STO') { // sous total demandé
			$pdf->Cell(0,7,$row['CONSA']. " : $sous_total_ht ".EURO ,1,1,'C',1);
			$sous_total_ht = 0;
		} else { // pas de sous total
			$pdf->Cell(0,7,$row['CONSA'],1,1,'C',1);
		}
		$pdf->SetFillColor(255);


	} else { // cas d'un article
	
		$designation = $row['DS1DB'] ;
		if ($row['DS2DB']) $designation .= "\n$row[DS2DB]";
		if ($row['DS3DB']) $designation .= "\n$row[DS3DB]";
		if ($row['CONSA']) $designation .= "\n$row[CONSA]";

		if ($row['ARCOM']=='OUI') { // attention article d'un kit, il faut l'enregistré pour le resortir sur le kit
			if (!isset($kit[$row['DET97']])) // premier article du kit
				$kit[$row['DET97']] = array();

			$kit[$row['DET97']][] = $designation." x$row[QTESA] (".str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO.")";// on rajoute la piece au kit
			continue;
		}
		
		//print_r($kit);exit;


		/// on cherche les commentaires associé à la ligne de commande (saisie sur un devis client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBVP1' and CDETA='' and CDCOD='${NOCLI_escape}${NOBON_escape}$row[NOLIG]' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n".trim($commentaire_row['CDLIB']);


		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) // devis demandé sans prix
			$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $row['REFFO'].($row['CODAR'] && $row['CODAR']!='DIVERS' ? "\n$row[CODAR]":'')	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10 ),
							array('text' => $row['FOUR1']	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
							array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation 	, 'text-align' => 'L'),
							array('text' => $row['QTESA']				, 'text-align' => 'C')
						)
					);
		else
			$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $row['REFFO'].($row['CODAR'] && $row['CODAR']!='DIVERS' ? "\n$row[CODAR]":'')	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10 ),
							array('text' => $row['FOUR1']	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
							array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation	, 'text-align' => 'L'),
							array('text' => $row['QTESA']				, 'text-align' => 'C'),
							array('text' => $row['PRINE']				, 'text-align' => 'R'),
							array('text' => str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO	, 'text-align' => 'R'),
						)
					);

		//print_r($kit);exit;

		if (isset($kit[$row['DET97']])) { // on doit afficher les info du kit
			if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) {
				foreach ($kit[$row['DET97']] as $ligne)
					$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
									array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8'),
									array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8')
								)
							);
			} else {
				foreach ($kit[$row['DET97']] as $ligne)
					$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
									array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8'),
									array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8'),
									array('text' => '','text-align'=>'R','font-size'=>'8')
								)
							);
			}
			unset($kit[$row['DET97']]);
		}

		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // pas d'eco taxe a afficher

		} else {
			if ($row['ECOTAXE']) { // l'article contient de l'écotaxe
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
							array('text' => '','text-align'=>'R','font-size'=>'8'),
							array('text' => "Ecotaxe sur l'article $row[CODAR]",'text-align'=>'R','font-size'=>'8'),
							array('text' => $row['QTESA'],'text-align'=>'C','font-size'=>'8'),
							array('text' => sprintf('%0.2f',$row['ECOTAXE']),'text-align'=>'R','font-size'=>'8'),
							array('text' => sprintf('%0.2f',$row['ECOTAXE']*$row['QTESA']).EURO,'text-align'=>'R','font-size'=>'8'),
						)
				);
			}
		}

		$total_ht		+= $row['QTESA']*$row['PRINE'] + $row['ECOTAXE']*$row['QTESA']; // on rajoute la somme au total
		$sous_total_ht	+= $row['QTESA']*$row['PRINE'] + $row['ECOTAXE']*$row['QTESA']; // on rajoute la somme au sous total
	}
}



// fin du devis
if($pdf->GetY() +  3*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(230); // gris clair

if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis sans prix
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH + PAGE_WIDTH/10,7,"MONTANT TOTAL HT",1,0,'L',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH + QTE_WIDTH - PAGE_WIDTH/10,7,str_replace('.',',',sprintf('%0.2f',$total_ht)).EURO,1,0,'R',1);
} else {
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$total_ht)).EURO,1,0,'R',1);
}
$pdf->Ln();

if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis sans prix
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH + PAGE_WIDTH/10,7,"MONTANT TOTAL TTC (TVA ".TTC1."%)",1,0,'L',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH + QTE_WIDTH - PAGE_WIDTH/10,7,str_replace('.',',',sprintf('%0.2f',$total_ht * TTC1 / 100) + $total_ht).EURO,1,0,'R',1);
} else {
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL TTC (TVA ".TTC1."%)",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$total_ht * TTC1 / 100) + $total_ht).EURO,1,0,'R',1);
}
$pdf->Ln();

if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis sans prix
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH + PAGE_WIDTH/10,7,"MONTANT TOTAL TTC (TVA ".TTC2."%)",1,0,'L',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH + QTE_WIDTH - PAGE_WIDTH/10,7,str_replace('.',',',sprintf('%0.2f',$total_ht * TTC2 / 100) + $total_ht).EURO,1,0,'R',1);
} else {
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL TTC (TVA ".TTC2."%)",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$total_ht * TTC2 / 100) + $total_ht).EURO,1,0,'R',1);
}
$pdf->Ln();

$pdf->Output('devis_'.$NOBON_escape.'('.crc32(uniqid()).').pdf','I');

odbc_close($loginor);
?>