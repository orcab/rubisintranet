<?
// TABLE DES MATIERES !!!

define('NOM',0);
define('NO_PAGE',1);
define('LIEN',2);
define('DECALAGE',3);

// construction de la table des matières
// a ce niveau $TOC = [ ['nom',page] , ['nom',page] , ['nom',page] , ... ]
$style = html2rgb('#C0C0C0,#404040,#FFFFFF,#404040,#FFFFFF,#000000,#404040,#FFFFFF'); // noir et gris
$titre_page = "Table des matières";
$pdf->AddPage(); // ajour de la table
$old_style = $style;
$pdf->SetFont('helvetica','B',11);
$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
$pdf->SetDrawColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);

//print_r($TOC);exit;
$pdf->SetDash(0.5,1);
for($i=0 ; $i<sizeof($TOC) ; $i++) {
	
	if		($TOC[$i][DECALAGE] == 0) // si c'est une sous section
		$pdf->SetFont('helvetica','B',12);
	elseif	($TOC[$i][DECALAGE] == 1)
		$pdf->SetFont('helvetica','',11);
	else
		$pdf->SetFont('helvetica','',9);

		$pdf->SetX(10 + 10 * $TOC[$i][DECALAGE]);
		$pdf->Cell(150 - 10 * $TOC[$i][DECALAGE],8, $TOC[$i][NOM] ,0,0,'L',0, $TOC[$i][LIEN] ); // titre de la sous section

		// trace des pointillés pour aller chercher le n° de page
		$pdf->Line(
			$pdf->GetStringWidth($TOC[$i][NOM]) + 10 * $TOC[$i][DECALAGE] + 13,
			$pdf->GetY()+5,
			190 - $pdf->GetStringWidth('page '.$TOC[$i][NO_PAGE]) - 4,
			$pdf->GetY()+5
		);
		
		$pdf->Cell(30,8,'page '.$TOC[$i][NO_PAGE],0,0,'R',0, $TOC[$i][LIEN]); // page de la section
		$pdf->Ln(4);
}
$pdf->SetDash();
?>