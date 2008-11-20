<?
// PAGE DES INDEX DES REFERENCE !!!


// construction de l'index des references
ksort($REFERENCE,SORT_STRING);
$titre_page = "Index des r�f�rences";
$style = html2rgb('#C0C0C0,#404040,#FFFFFF,#404040,#FFFFFF,#000000,#404040,#FFFFFF'); // noir et gris
$old_style = $style;
$pdf->AddPage(); // ajout des references
// on peut mettre 63 + entete ref sur une colonne
$colonne = 0 ;
$ligne=0;
$page_actuel = 0;
$restaure_font_size = 0 ;
define('INDEX_CELL_HEIGHT',4);
define('INDEX_FONT_SIZE',8);
define('INDEX_REF_WIDTH',20);
define('INDEX_PAGE_WIDTH',8);
define('INDEX_CELL_SPACING',9);
$pdf->SetFont('helvetica','',INDEX_FONT_SIZE);
$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
$pdf->SetDrawColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);

foreach($REFERENCE as $ref=>$data) {
	if ($ref) {
		if ($ligne >= 63) { // en bout de colonne
			if ($colonne >= 4) { // on arrive en bout de page, on creer une nouvelle page
				$colonne = 0 ; $ligne=0;
				$pdf->AddPage();
			} else {
				$colonne++; // on avance d'une colonne
				$ligne=0; // on remonte en haut
			}
		}

		if ($ligne == 0) { // sur un entete
			$pdf->SetY(15 + ($ligne * INDEX_CELL_HEIGHT)); // mettre avant le SetY car Y agit sur X et cr�er des incoh�rences
			$pdf->SetX(15 + ($colonne * (INDEX_REF_WIDTH + INDEX_PAGE_WIDTH + INDEX_CELL_SPACING)));

			$pdf->Cell(INDEX_REF_WIDTH,INDEX_CELL_HEIGHT,'R�f�rence','BR',0,'L'); // ref
			$pdf->Cell(INDEX_PAGE_WIDTH,INDEX_CELL_HEIGHT,'Page','BL',0,'R'); // sa page
			$ligne++;
		}

		$pdf->SetY(15 + ($ligne * INDEX_CELL_HEIGHT)); // mettre avant le SetY car Y agit sur X et cr�er des incoh�rences
		$pdf->SetX(15 + ($colonne * (INDEX_REF_WIDTH + INDEX_PAGE_WIDTH + INDEX_CELL_SPACING)));

		// si le texte depasse de la case, on diminu la police
		$font_redux = 0;
		while ($pdf->GetStringWidth($ref) > INDEX_REF_WIDTH && (INDEX_FONT_SIZE - $font_redux) > 0) {
			$pdf->SetFont('helvetica','',INDEX_FONT_SIZE - ++$font_redux);
		}


	//	echo $data[1]." ";


		$pdf->Cell(INDEX_REF_WIDTH,INDEX_CELL_HEIGHT,$ref,'BTR',0,'L',0,$data[1]); // ref

		// on revient � la police prec�dente
		if ($font_redux) {
			$pdf->SetFont('helvetica','',INDEX_FONT_SIZE); $font_redux =0;
		}

		$pdf->Cell(INDEX_PAGE_WIDTH,INDEX_CELL_HEIGHT,$data[0],'BTL',0,'R',0,$data[1]); // sa page

		$ligne++;
	}
}

?>