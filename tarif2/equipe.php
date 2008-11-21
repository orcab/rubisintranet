<?
// Page supplémentaire avec l'equipe MCS !!!

if (TARIF_EQUIPE) {
	$pdf->AddPage();
	$pdf->Image('images/page_de_garde/'.TARIF_EQUIPE,0,0,PAGE_WIDTH); // taille a 200 de l'image
}

if (TARIF_ORGANIGRAMME) {
	$pdf->AddPage();
	$PRINT_PAGE_NUMBER  = false;
	$PRINT_EDITION_DATE = false;
	$pdf->Image('images/page_de_garde/'.TARIF_ORGANIGRAMME,0,0,PAGE_WIDTH); // taille a 200 de l'image
	$PRINT_PAGE_NUMBER  = true;
	$PRINT_EDITION_DATE = true;
}
?>