<?

//require_once('../inc/constant.php');
require_once('../../inc/fpdf/fpdf.php');
require_once('../../inc/qrcode/qrcode.class.php');

class PDF extends FPDF
{
	//EN-TÊTE
	function Header() {	
		// fond de page
		$this->Image('gfx/fond_page.png',0,0,PAGE_WIDTH,PAGE_HEIGHT);
		
	}



	//PIED DE PAGE
	function Footer() {	
		
		// qrcode du fichier
		//$json = array('t'=>'cde_client','b'=>$row_entete['NOBON'],'c'=>$row_entete['NOCLI'],'d'=>time(),'p'=>$this->PageNo());
		//$qrcode = new QRcode(json_encode($json), 'L'); // error level : L, M, Q, H
		//$qrcode = new QRcode("t=cdecli,c=$row_entete[NOBON]/$row_entete[NOCLI],d=".time(), 'H'); // error level : L, M, Q, H
		//$qrcode->displayFPDF($this, RIGHT_MARGIN -7, PAGE_HEIGHT-22, 20);


		// texte avev la date
		if ($this->PageNo() > 1) {
			$this->SetXY(LEFT_MARGIN,-20);
			$this->SetFont('helvetica','',9);
			$this->Cell(0,5,'Edition du '.date('d/m/Y H:i'),0,1,'C');

			// rectangle arrondi en page a gauche avec n° de page
			$this->SetFont('helvetica','',9);
			$this->SetFillColor(192,192,192);
			$this->RoundedRect(PAGE_WIDTH - RIGHT_MARGIN - 3, PAGE_HEIGHT - 16, 12, 6, 2, 'F');
			//Positionnement à 1,7 cm du bas
			$this->SetXY(PAGE_WIDTH - RIGHT_MARGIN - 2,-17);
			$this->SetTextColor(255,255,255);
			$this->Cell(0,8,$this->PageNo().'/{nb}',0,1,'');
		}
	}



// pour faire des pointillés
	function SetDash($black=false,$white=false) {
        if($black and $white)
            $s=sprintf('[%.3f %.3f] 0 d',$black*$this->k,$white*$this->k);
        else
            $s='[] 0 d';
        $this->_out($s);
    }


///////////////////////// REDUIT LA TAILLE DE LA FONT ///////////////////////////////
	function redux_font_size($texte,$initial_font_size,$max_width,$modifier='') {
		$redux=0;
		do {
			$this->SetFont('helvetica',$modifier,$initial_font_size - $redux);
			$redux++;
		} while($this->GetStringWidth($texte) > $max_width);
		return $initial_font_size - $redux;
	}



// GESTION DES RECTANGLE ARRONDI
	function RoundedRect($x, $y, $w, $h,$r, $style = '') {
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

    function _Arc($x1, $y1, $x2, $y2, $x3, $y3) {
        $h = $this->h;
        $this->_out(sprintf('%.2f %.2f %.2f %.2f %.2f %.2f c ', $x1*$this->k, ($h-$y1)*$this->k,
            $x2*$this->k, ($h-$y2)*$this->k, $x3*$this->k, ($h-$y3)*$this->k));
    }



	var $widths;
	var $aligns;

	function SetWidths($w) {
		//Tableau des largeurs de colonnes
		$this->widths=$w;
	}

	function SetAligns($a) {
		//Tableau des alignements de colonnes
		$this->aligns=$a;
	}

	function Row($param) {
		
		$data = array();
		for($i=0 ; $i<sizeof($param) ; $i++)
			$data[] = isset($param[$i]['text']) ? $param[$i]['text'] : '';

		//Calcule la hauteur de la ligne
		$nb=0;
		for($i=0;$i<count($data);$i++)
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
		$h=5*$nb;

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
			$this->Rect($x,$y,$w,$h);
			//Imprime le texte
			$this->MultiCell($w,5,$data[$i],0,$a,0);
			//Repositionne à droite
			$this->SetXY($x+$w,$y);
		}
		//Va à la ligne
		$this->Ln($h);
	}

	function CheckPageBreak($h) {
		//Si la hauteur h provoque un débordement, saut de page manuel
		if($this->GetY() + $h + 8 > $this->PageBreakTrigger)
			$this->AddPage($this->CurOrientation);
	}

	function NbLines($w,$txt) {
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


}

?>
