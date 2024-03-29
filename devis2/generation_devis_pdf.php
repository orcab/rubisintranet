<?
//print_r($_POST);exit;

// les options sont 'px_adh'	pour sortir un devis avec les prix artisans,
//					'no_header' pour sortir un devis sans les logos a imprimer sur du papier entete

$options = array();
if (isset($_POST['les_options'])) {
	$options = explode(',',$_POST['les_options']);
} elseif (isset($_GET['les_options'])) {
	$options = explode(',',$_GET['les_options']);
}

//print_r($options);exit;

include('../inc/config.php');
require_once('overload.php');
require_once('save_data_into_database.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

// enregistre les donn�es dans la base
save_data_into_database();

// GENERATION DU DOCUMENT PDF
// creation de l'objet
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

if (in_array('px_adh',$options))	// si le bon est destin� � l'artisan, on met toutes les infos
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH));
else								// si le bon est destin� au client, on n'affiche ni la r�f�rence, ni le fournisseur
	$pdf->SetWidths(array(FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH));

$sous_total = 0 ;
$sous_total_option = 0 ;
// on genere les lignes les une apres les autres
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {

	// si le bon est destin� au client, on affiche la designation1
	// si le bon est destin� a l'artisan, on affiche  la designation1 et la designation2
	$designation = trim($_POST['a_designation'][$i]);
	if (in_array('px_adh',$options)) // bon destin� a l'artisan
		$designation .= "\n".trim($_POST['a_2designation'][$i]);

	$designation .= $_POST['a_hid_opt'][$i] ? "\n(option)":'';

	$count = preg_match_all('/\n/',trim($designation),$regs);
	if ($count > 0 || $_POST['a_hid_opt'][$i])
		$hauteur_max =  str_repeat("\n ", $count);
	else
		$hauteur_max = '';

	if ($_POST['a_hid_opt'][$i])
		$hauteur_max_prix = str_repeat("\n ", max($count - 1,0));
	else
		$hauteur_max_prix =  str_repeat("\n ", $count);;
		

	if ($_POST['a_reference'][$i] && $_POST['a_qte'][$i]) { // cas d'un article
		$prix = in_array('px_adh',$options) ? $_POST['a_adh_pu'][$i] : $_POST['a_pu'][$i];

		if ($prix <= 0)
			$pdf->SetFillColor(255,0,0);

		setCustomColor();

		// si le bon est destin� � l'artisan, on met toutes les infos
		if (in_array('px_adh',$options)) {
			$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => utf8_decode($_POST['a_reference'][$i]).$hauteur_max	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($_POST['a_reference'][$i])>11 ? 9:10 ),
						array('text' => utf8_decode($_POST['a_fournisseur'][$i]).$hauteur_max	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
						array('text' => my_utf8_decode(stripslashes($designation)), 'text-align' => 'L'),
						array('text' => $_POST['a_qte'][$i].$hauteur_max			, 'text-align' => 'C'),
						array('text' => str_replace('.',',',sprintf("%0.2f",$prix)).EURO.($_POST['a_hid_opt'][$i] ? "\n(option)":'').$hauteur_max_prix	, 'text-align' => 'R'),
						array('text' => str_replace('.',',',sprintf("%0.2f",$_POST['a_qte'][$i]*$prix)).EURO.($_POST['a_hid_opt'][$i] ? "\n(option)":'').$hauteur_max_prix	, 'text-align' => 'R'),
						)
					);

		} else { // si le bon est destin� au client, on n'affiche ni la r�f�rence, ni le fournisseur
			$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => utf8_decode($_POST['a_fournisseur'][$i]).$hauteur_max	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
						array('text' => my_utf8_decode(stripslashes($designation)) , 'text-align' => 'L'),
						array('text' => $_POST['a_qte'][$i].$hauteur_max			, 'text-align' => 'C'),
						array('text' => str_replace('.',',',sprintf("%0.2f",$prix)).EURO.($_POST['a_hid_opt'][$i] ? "\n(option)":'').$hauteur_max_prix			, 'text-align' => 'R'),
						array('text' => str_replace('.',',',sprintf("%0.2f",$_POST['a_qte'][$i]*$prix)).EURO.($_POST['a_hid_opt'][$i] ? "\n(option)":'').$hauteur_max_prix		, 'text-align' => 'R'),
						)
					);
		}

		$pdf->SetFillColor(255);

	} elseif(!$_POST['a_reference'][$i] && $_POST['a_designation'][$i]) { // cas d'un commentaire
		$pdf->SetFillColor(230);

		setCustomColor();

		$pdf->SetFont('','B');
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
			$pdf->AddPage();

		// on doit aller cherche le prochain commentaire pour trouver le sous total
		for($j=$i+1 ; $j < sizeof($_POST['a_reference']) ; $j++) {
			if ($_POST['a_reference'][$j] && $_POST['a_qte'][$j]) { // cas d'un article
				if ($_POST['a_hid_opt'][$j]) // c'est une option
					$sous_total_option++;
				else
					$sous_total += ($_POST['a_qte'][$j] * (in_array('px_adh',$options) ? $_POST['a_adh_pu'][$j] : $_POST['a_pu'][$j])) ;
			} else {
				break ; // on tombe sur un autre commentaire, on s'arrete
			}
		}

		if ($sous_total_option)
			$option_phrase = "\nLe sous total ne tient pas compte " . ($sous_total_option > 1 ? "des $sous_total_option options choisies" : "de l'option choisie");
		else
			$option_phrase='';

		if ($sous_total)
			$pdf->MultiCell(0,7,my_utf8_decode(stripslashes($designation)).' ('.str_replace('.',',',sprintf("%0.2f",$sous_total)).EURO.')'.$option_phrase,1,'C',1);
		else
			$pdf->MultiCell(0,7,my_utf8_decode(stripslashes($designation)) ,1,'C',1);

		$sous_total = 0 ;
		$sous_total_option = 0;
	}

	$pdf->SetFillColor(255);
	$pdf->SetTextColor(0);
}

if($pdf->GetY() +  3*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

$total = in_array('px_adh',$options) ? $total_devis_adh : $total_devis;
$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(230); // gris clair

if (in_array('px_adh',$options))
	$pdf->Cell(REF_WIDTH,7,'',1,0,'',1);

$pdf->Cell(FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,str_replace('.',',',sprintf("%0.2f",$total)).EURO,1,0,'R',1);
$pdf->Ln();

if (in_array('px_adh',$options))
	$pdf->Cell(REF_WIDTH,7,'',1,0,'',1);

$pdf->Cell(FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH ,7,"MONTANT TOTAL TTC (TVA ".TTC1."%)",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,str_replace('.',',',sprintf("%0.2f",$total + $total * TTC1 / 100)).EURO,1,0,'R',1);
$pdf->Ln();

if (in_array('px_adh',$options))
	$pdf->Cell(REF_WIDTH,7,'',1,0,'',1);

$pdf->Cell(FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH ,7,"MONTANT TOTAL TTC (TVA ".TTC2."%)",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,str_replace('.',',',sprintf("%0.2f",$total + $total * TTC2 / 100)).EURO,1,0,'R',1);
$pdf->Ln();

if ($option > 0) { // il y a des options, on balance un disclaimer
	if (in_array('px_adh',$options))
		$pdf->Cell(	REF_WIDTH + FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH + QTE_WIDTH + PUHT_WIDTH + PUHT_WIDTH,7,
					"Le total ne tient pas compte " . ($option > 1 ? "des $option options choisies" : "de l'option choisie"),
					1,0,'R',1);
	else
		$pdf->Cell(	FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH + QTE_WIDTH + PUHT_WIDTH + PUHT_WIDTH,7,
					"Le total ne tient pas compte " . ($option > 1 ? "des $option options choisies" : "de l'option choisie"),
					1,0,'R',1);

	$pdf->Ln();
}

$pdf->Output('devis_'.$id_devis.'('.crc32(uniqid()).').pdf','I');



//////////////////////////////////////////////////////////////////////////////////////////////////////
function is_rgb_color($str) {
	return preg_match('/^\d{1,3},\d{1,3},\d{1,3}$/', $str);
}


function setCustomColor() {
	global $pdf,$i;
	if (is_rgb_color($_POST['a_designation_background-color'][$i])) {
		$tmp = explode(',',$_POST['a_designation_background-color'][$i]);
		$pdf->SetFillColor($tmp[0],$tmp[1],$tmp[2]);
	}

	if (is_rgb_color($_POST['a_designation_color'][$i])) {
		$tmp = explode(',',$_POST['a_designation_color'][$i]);
		$pdf->SetTextColor($tmp[0],$tmp[1],$tmp[2]);
	}
}
?>