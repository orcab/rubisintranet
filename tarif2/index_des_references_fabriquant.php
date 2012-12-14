<?
// PAGE DES INDEX DES REFERENCE !!!

// construction de l'index des references
ksort($REFERENCE,SORT_STRING);
$titre_page = "Index des références fabriquant";
$style = html2rgb('#C0C0C0,#404040,#FFFFFF,#404040,#FFFFFF,#000000,#404040,#FFFFFF'); // noir et gris
$old_style = $style;
$pdf->AddPage(); // ajout des references
// on peut mettre 63 + entete ref sur une colonne
$colonne = 0 ;
$ligne=0;
$page_actuel = 0;
$restaure_font_size = 0 ;

$pdf->SetFont('helvetica','',INDEX_FONT_SIZE);
$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
$pdf->SetDrawColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);

foreach($REFERENCE as $ref=>$data) {
	if ($ref) {
		if ($ligne >= 65) { // en bout de colonne
			if ($colonne >= 3) { // on arrive en bout de page, on creer une nouvelle page
				$colonne = 0 ; $ligne=0;
				$pdf->AddPage();
			} else {
				$colonne++; // on avance d'une colonne
				$ligne=0; // on remonte en haut
			}
		}

		if ($ligne == 0) { // sur un entete
			$pdf->SetY(INDEX_TOP_MARGIN + ($ligne * INDEX_CELL_HEIGHT)); // mettre avant le SetY car Y agit sur X et créer des incohérences
			$pdf->SetX(INDEX_LEFT_MARGIN + ($colonne * (INDEX_REF_WIDTH + INDEX_PRIX_WIDTH + INDEX_PAGE_WIDTH + INDEX_CELL_SPACING)));

			$pdf->Cell(INDEX_REF_WIDTH,INDEX_CELL_HEIGHT,'Référence','BR',0,'L'); // ref
			$pdf->Cell(INDEX_PRIX_WIDTH,INDEX_CELL_HEIGHT,'Prix '.EURO,'BC',0,'RL'); // prix
			$pdf->Cell(INDEX_PAGE_WIDTH,INDEX_CELL_HEIGHT,'Page','BL',0,'R'); // page
			$ligne++;
		}

		$pdf->SetY(INDEX_TOP_MARGIN + ($ligne * INDEX_CELL_HEIGHT)); // mettre avant le SetY car Y agit sur X et créer des incohérences
		$pdf->SetX(INDEX_LEFT_MARGIN + ($colonne * (INDEX_REF_WIDTH + INDEX_PRIX_WIDTH + INDEX_PAGE_WIDTH + INDEX_CELL_SPACING)));


		////////////////// REFERENCE ////////////////
		// si le texte depasse de la case, on diminu la police
		$font_redux = 0;
		while ($pdf->GetStringWidth($ref) > INDEX_REF_WIDTH && (INDEX_FONT_SIZE - $font_redux) > 0)
			$pdf->SetFont('helvetica','',INDEX_FONT_SIZE - ++$font_redux);
		$pdf->Cell(INDEX_REF_WIDTH,INDEX_CELL_HEIGHT,$ref,'BTR',0,'L',0,$data[2]); // ref
		if ($font_redux) { // on revient à la police precédente
			$pdf->SetFont('helvetica','',INDEX_FONT_SIZE); $font_redux =0;
		}

		if (!isset($_POST['prix'])) { // si on ne doit pas afficher les prix
			$data[1]='';
			$data[3]='';
		}

		////////////////// PRIX + ECOTAXE ////////////////
		if ($data[3]) { // une écotaxe, donc on diminue la police
			$font_redux = 0;
			while ($pdf->GetStringWidth("$data[1] ($data[3])") > INDEX_PRIX_WIDTH && (INDEX_FONT_SIZE - $font_redux) > 0)
				$pdf->SetFont('helvetica','',INDEX_FONT_SIZE - ++$font_redux);
		}
		$pdf->Cell(INDEX_PRIX_WIDTH,INDEX_CELL_HEIGHT,$data[1].( $data[3] ? " ($data[3])":''),'BTR',0,'R',0,$data[2]); // prix + ecotaxe
		if ($font_redux) { // on revient à la police precédente
			$pdf->SetFont('helvetica','',INDEX_FONT_SIZE); $font_redux =0;
		}

		////////////////// PAGE ////////////////
		$pdf->Cell(INDEX_PAGE_WIDTH,INDEX_CELL_HEIGHT,$data[0],'BTL',0,'R',0,$data[2]); // page

		$ligne++;
	}
}

?>