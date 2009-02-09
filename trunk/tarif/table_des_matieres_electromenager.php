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

$deja_afficher = array();
for($i=0 ; $i<sizeof($TOC) ; $i++) {
	
	$nom_categ		 = $categorie['nom_'.$TOC[$i][ID]] ;
	$hierachie_categ = split(' *\| *',$nom_categ); // on split le plan de vente

	if (sizeof($hierachie_categ) > 1 && $hierachie_categ[0]) { // sous section
		for ($j=0 ; $j<sizeof($hierachie_categ) ; $j++) {
			if (isset($deja_afficher[join(' | ',array_slice($hierachie_categ,0,$j+1))])) continue ;

//			echo sizeof($hierachie_categ)." \$j=$j ".$hierachie_categ[$j]."<br>" ;
//			if ($j==0 && $i>1) $pdf->AddPage();
//			$pdf->SetFont('helvetica',($j==0 ? 'BU':''),16 - 2*$j);
			$pdf->SetFont('helvetica',($j==0 ? 'BU':''),12 - $j);
			$pdf->SetX($pdf->GetX() + 10 * ($j + 1));
		
			$pdf->Cell(150 - 10 * $j,8, $hierachie_categ[$j] ,0,0,'L',0, $TOC[$i][LIEN] ); // titre de la sous section

			$deja_afficher[join(' | ',array_slice($hierachie_categ,0,$j+1))] = 1;
		
			// trace des pointillés pour aller chercher le n° de page
			$pdf->SetDash(0.5,1);
			$pdf->Line(($pdf->GetStringWidth($hierachie_categ[$j])) + 10 * ($j+1) + 13,$pdf->GetY()+5,PAGE_WIDTH - $pdf->GetStringWidth('page '.$TOC[$i][NO_PAGE]) - 14,$pdf->GetY()+5);
			$pdf->SetDash();
			$pdf->Cell(30,8,'page '.$TOC[$i][NO_PAGE],0,0,'R',0, $TOC[$i][LIEN]); // page de la section
			$pdf->Ln(4);
		}

	} else { // super section
			$pdf->SetFont('helvetica','B',11);
			$pdf->Cell(150,8,$nom_categ,0,0,'L' ,0 , $TOC[$i][LIEN]); // titre de la section
			$pdf->Cell(30,8,'page '.$TOC[$i][NO_PAGE],0,0,'R',0, $TOC[$i][LIEN] ); // page de la section

			$pdf->SetLineWidth(0.1);
			$pdf->Ln();
			$pdf->Line($pdf->GetX(),$pdf->GetY(),190,$pdf->GetY());
	}
}

?>