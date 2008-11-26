<?
require_once('../inc/fpdf/fpdf.php');

class PDF extends FPDF
{
	//EN-TÊTE
	function Header()
	{	global $row,$style,$PLAN_DE_VENTE,$titre_page ;
		
		// rectangle arrondi en haut de page	
		$this->SetFillColor($style[RED_BACKGROUND_TITLE],$style[GREEN_BACKGROUND_TITLE],$style[BLUE_BACKGROUND_TITLE]);
		$this->RoundedRect(6, 5, 100, 8, 3.5, 'F');

		// ligne en haut de page
		$this->Rect(0,7,PAGE_WIDTH,1,'F');

		// rectangle en top de page
		$this->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
		$this->Rect(0,0,PAGE_WIDTH,7,'F');

		// activite dans le rectangle arrondi
		$this->SetFont('helvetica','',11);
		$this->SetTextColor($style[RED_TITLE],$style[GREEN_TITLE],$style[BLUE_TITLE]);

		// on trouve le super pere pour le titre de la page
		$this->Cell(0,1,isset($PLAN_DE_VENTE[$row['ACTIV']]) ? $PLAN_DE_VENTE[$row['ACTIV']] : $titre_page  ,0,0,'L');

		//Saut de ligne
		$this->Ln(8);
	}



	//PIED DE PAGE
	function Footer()
	{	global $old_style,$PRINT_PAGE_NUMBER,$PRINT_EDITION_DATE,$last_img_bottom ;
		$last_img_bottom = 0;

		if ($PRINT_PAGE_NUMBER) {
			// rectangle arrondi en page a gauche avec n° de page
			$this->SetFillColor($old_style[RED_BACKGROUND_TITLE],$old_style[GREEN_BACKGROUND_TITLE],$old_style[BLUE_BACKGROUND_TITLE]);
			$this->SetFont('helvetica','',9);
			$this->SetTextColor(255,255,255);

			if ($this->PageNo() & 1) { // page impaire a droite
				$this->RoundedRect(PAGE_WIDTH - 11, PAGE_HEIGHT - 15, 17, 6, 2, 'F');
				//Positionnement à 1,7 cm du bas
				$this->SetXY(PAGE_WIDTH - 8,-17);
				$this->Cell(0,10,$this->PageNo(),0,0,'');
			} else {					// page paire a gauche
				$this->RoundedRect(-6, PAGE_HEIGHT - 15, 17, 6, 2, 'F');
				//Positionnement à 1,7 cm du bas
				$this->SetXY(4,-17);
				$this->Cell(0,10,$this->PageNo(),0,0,'');
			}
		}

		if ($PRINT_EDITION_DATE) {
			// date d'édition
			$this->SetTextColor(0);
			if ($this->PageNo() & 1) // page impaire a droite
				$this->SetXY(10,-17);
			else
				$this->SetXY(PAGE_WIDTH - 50,-17);

			$this->Cell(0,10,"Date d'édition : ".date('d/m/Y'),0,0,'');
		}
	}


// pour faire des pointillés
	function SetDash($black=false,$white=false)
    {
        if($black and $white)
            $s=sprintf('[%.3f %.3f] 0 d',$black*$this->k,$white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }



// GESTION DES RECTANGLE ARRONDI
	function RoundedRect($x, $y, $w, $h,$r, $style = '')
    {
        $k = $this->k;
        $hp = $this->h;
        if($style=='F')
            $op='f';
        elseif($style=='FD' or $style=='DF')
            $op='B';
        else
            $op='S';
        $MyArc = 4/3 * (sqrt(2) - 1);
        $this->_out(sprintf('%.2f %.2f m',($x+$r)*$k,($hp-$y)*$k ));
        $xc = $x+$w-$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l', $xc*$k,($hp-$y)*$k ));

        $this->_Arc($xc + $r*$MyArc, $yc - $r, $xc + $r, $yc - $r*$MyArc, $xc + $r, $yc);
        $xc = $x+$w-$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',($x+$w)*$k,($hp-$yc)*$k));
        $this->_Arc($xc + $r, $yc + $r*$MyArc, $xc + $r*$MyArc, $yc + $r, $xc, $yc + $r);
        $xc = $x+$r ;
        $yc = $y+$h-$r;
        $this->_out(sprintf('%.2f %.2f l',$xc*$k,($hp-($y+$h))*$k));
        $this->_Arc($xc - $r*$MyArc, $yc + $r, $xc - $r, $yc + $r*$MyArc, $xc - $r, $yc);
        $xc = $x+$r ;
        $yc = $y+$r;
        $this->_out(sprintf('%.2f %.2f l',($x)*$k,($hp-$yc)*$k ));
        $this->_Arc($xc - $r, $yc - $r*$MyArc, $xc - $r*$MyArc, $yc - $r, $xc, $yc - $r);
        $this->_out($op);
    }

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3)
    {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }


	var $widths;
	var $aligns;

	function SetWidths($w)
	{
		//Tableau des largeurs de colonnes
		$this->widths=$w;
	}

	function SetAligns($a)
	{
		//Tableau des alignements de colonnes
		$this->aligns=$a;
	}

	function Row($param,$nb_ligne = 1)
	{
		
		$data = array();
		for($i=0 ; $i<sizeof($param) ; $i++)
			$data[] = isset($param[$i]['text']) ? $param[$i]['text'] : '';

		// la hauteur est spécifié dans l'appel de la fonction. Defaut=1
		$h = 5 * $nb_ligne ;

		//Effectue un saut de page si nécessaire
		$this->CheckPageBreak($h);

		//Dessine les cellules
		for($i=0;$i<count($data);$i++) {
			$a=isset($this->aligns[$i]) ? $this->aligns[$i] : 'L'; // gere l'align

			$this->SetFont( isset($param[$i]['font-family'])?$param[$i]['font-family']:'',
							isset($param[$i]['font-style'])?$param[$i]['font-style']:'',
							isset($param[$i]['font-size'])?$param[$i]['font-size']:'');
			if (isset($param[$i]['font-color']))	$this->SetTextColor($param[$i]['font-color'][0],$param[$i]['font-color'][1],$param[$i]['font-color'][2]);
			if (isset($param[$i]['text-align']))	$a = $param[$i]['text-align'];
			
			$w=$this->widths[$i];
			
			//Sauve la position courante
			$x=$this->GetX();
			$y=$this->GetY();
			//Dessine le cadre
			//$this->Rect($x,$y,$w,$h);

			// dessine le cadre extérieur
			if ($i==0)	// uniquement pour la premiere cellule trace la ligne gauche
				$this->Line($x,$y,$x,$y+$h);

			// pour toutes les cellules, on trace la ligne dessu et dessous
			$this->Line($x,$y,$x+$w,$y); // dessus
			$this->Line($x,$y+$h,$x+$w,$y+$h); // dessous

			if ($i==count($data)-1) // pour la derniere case, on trace la bordure droite
				$this->Line($x+$w,$y,$x+$w,$y+$h);
		

			//Imprime le texte
			$this->MultiCell($w,5,$data[$i],0,$a,0);
			//Repositionne à droite
			$this->SetXY($x+$w,$y);
		}
		//Va à la ligne
		$this->Ln($h);
	}

	function CheckPageBreak($h)
	{
		//Si la hauteur h provoque un débordement, saut de page manuel
		if($this->GetY() + $h + 8 > $this->PageBreakTrigger)
			$this->AddPage($this->CurOrientation);
	}

	function NbLines($w,$txt)
	{
		//Calcule le nombre de lignes qu'occupe un MultiCell de largeur w
		$cw=&$this->CurrentFont['cw'];
		if($w==0) $w=$this->w-$this->rMargin-$this->x;
		$wmax=($w-2*$this->cMargin)*1000/$this->FontSize;
		$s=str_replace("\r",'',$txt);
		$nb=strlen($s);
		if($nb>0 and $s[$nb-1]=="\n") $nb--;
		$sep=-1; $i=0; $j=0; $l=0; $nl=1;
		while($i<$nb)
		{
			$c=$s[$i];
			if($c=="\n") {
				$i++; $sep=-1; $j=$i; $l=0; $nl++;
				continue;
			}
			if($c==' ') $sep=$i;
			$l+=$cw[$c];
			if($l>$wmax) {
				if($sep==-1) {
					if($i==$j) $i++;
				} else $i=$sep+1;
				$sep=-1; $j=$i; $l=0; $nl++;
			}
			else $i++;
		}
		return $nl;
	}
} // fin overload


$c = 0 ;
define('RED_PAGE',$c++);				define('GREEN_PAGE',$c++);				define('BLUE_PAGE',$c++); // haut de page
define('RED_BACKGROUND_TITLE',$c++);	define('GREEN_BACKGROUND_TITLE',$c++);	define('BLUE_BACKGROUND_TITLE',$c++); // fond du titre de la page
define('RED_TITLE',$c++);				define('GREEN_TITLE',$c++);				define('BLUE_TITLE',$c++); // font du titre
define('RED_CATEG',$c++);				define('GREEN_CATEG',$c++);				define('BLUE_CATEG',$c++); // font de la categorie
define('RED_HEADER',$c++);				define('GREEN_HEADER',$c++);			define('BLUE_HEADER',$c++); // font de l'entete de tableau
define('RED_ARTICLE',$c++);				define('GREEN_ARTICLE',$c++);			define('BLUE_ARTICLE',$c++); // font des articles
define('RED_PRICE',$c++);				define('GREEN_PRICE',$c++);				define('BLUE_PRICE',$c++); // font du prix
define('RED_BACKGROUND_ARTICLE',$c++);	define('GREEN_BACKGROUND_ARTICLE',$c++);define('BLUE_BACKGROUND_ARTICLE',$c++); // fond des articles

function html2rgb($rgb) {
	// on recoit une chaine comme suit : #xxxxxx, ... x8
	$style = array();

	foreach (explode(',',$rgb) as $html_color) { // pour chaque style, on tranforme la valeur HTML en valeur r,g,b
		if (eregi('^#([A-F0-9][A-F0-9])([A-F0-9][A-F0-9])([A-F0-9][A-F0-9])$',$html_color,$regs)) { // s'il la couleur html est bien formée
			$style[] = hexdec($regs[1]);
			$style[] = hexdec($regs[2]);
			$style[] = hexdec($regs[3]);
		} else {
			$style[] = 0;
			$style[] = 0;
			$style[] = 0;
		}
	}
	return $style;
}
?>
