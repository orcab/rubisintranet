<?
// Page supplémentaire avec l'equipe MCS !!!
$PRINT_PAGE_NUMBER  = FALSE;
$PRINT_EDITION_DATE = FALSE;

if (TARIF_EQUIPE) {
	$pdf->AddPage();
	$pdf->Image('images/page_de_garde/'.TARIF_EQUIPE,0,0,PAGE_WIDTH);
}

if (TARIF_ORGANIGRAMME) {
	$pdf->AddPage();
	$pdf->Image('images/page_de_garde/'.TARIF_ORGANIGRAMME,0,0,PAGE_WIDTH);
}

?>