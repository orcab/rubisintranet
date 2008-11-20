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
		$this->Ln(10);
	}



	//PIED DE PAGE
	function Footer()
	{	global $old_style,$PRINT_PAGE_NUMBER,$PRINT_EDITION_DATE ;


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
			$this->SetXY(PAGE_WIDTH/2 - 15,-17);
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

}

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
