<?
// TABLE DES MATIERES !!!

define('ID',0);
define('NO_PAGE',1);
define('LIEN',2);

// construction de la table des matières
// a ce niveau $TOC = [ ['nom',page] , ['nom',page] , ['nom',page] , ... ]
$style = html2rgb('#C0C0C0,#404040,#FFFFFF,#404040,#FFFFFF,#000000,#404040,#FFFFFF'); // noir et gris
$titre_page = "Table des matières";
$pdf->AddPage(); // ajour de la table
$old_style = $style;
$pdf->SetFont('helvetica','B',11);
$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
$pdf->SetDrawColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);

//print_r($TOC);

for($i=0 ; $i<sizeof($TOC) ; $i++) {
	//echo $TOC[$i][NOM].' '.$categorie['chemin_'.$TOC[$i][ID]]."<br>\n";
	$sections = explode('-',$categorie['chemin_'.$TOC[$i][ID]]);
	
	if (sizeof($sections) > 0 && $sections[0]) { // si c'est une sous section
		
		$noms = explode(' / ',$categorie['nom_'.$TOC[$i][ID]]); // on split le plan de vente
		$pdf->SetFont('helvetica','',10);
		$pdf->SetX($pdf->GetX() + 10 * sizeof($sections));
		$pdf->Cell(150 - 10 * sizeof($sections),8, $noms[sizeof($noms)-1] ,0,0,'L',0, $TOC[$i][LIEN] ); // titre de la sous section

		// trace des pointillés pour aller chercher le n° de page
		$pdf->SetDash(0.5,1);
		$pdf->Line(($pdf->GetStringWidth($noms[sizeof($noms)-1])) + 10 * sizeof($sections) + 13,$pdf->GetY()+5,190 - $pdf->GetStringWidth('page '.$TOC[$i][NO_PAGE]) - 4,$pdf->GetY()+5);
		$pdf->SetDash();
		
		$pdf->Cell(30,8,'page '.$TOC[$i][NO_PAGE],0,0,'R',0, $TOC[$i][LIEN]); // page de la section
		$pdf->Ln();
	} else {
		$pdf->SetFont('helvetica','B',11);
		$pdf->Cell(150,8,$categorie['nom_'.$TOC[$i][ID]],0,0,'L' ,0 , $TOC[$i][LIEN]); // titre de la section
		$pdf->Cell(30,8,'page '.$TOC[$i][NO_PAGE],0,0,'R',0, $TOC[$i][LIEN] ); // page de la section

		$pdf->SetLineWidth(0.1);
		$pdf->Ln();
		$pdf->Line($pdf->GetX(),$pdf->GetY(),190,$pdf->GetY());
	}		
}

?>