<?
// Page supplémentaire avec l'equipe MCS !!!

$pdf->AddPage();
$pdf->Image("gfx/equipe.png",0,0,PAGE_WIDTH); // taille a 200 de l'image

$pdf->AddPage();

$PRINT_PAGE_NUMBER  = false;
$PRINT_EDITION_DATE = false;
$pdf->Image("gfx/organigramme.png",0,0,PAGE_WIDTH); // taille a 200 de l'image
?>