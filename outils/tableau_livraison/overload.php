<?
require_once('../../inc/fpdf/fpdf.php');

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('LEFT_MARGIN',9);
define('RIGHT_MARGIN',LEFT_MARGIN);
define('TOP_MARGIN',45);

define('REF_WIDTH',25);
define('FOURNISSEUR_WIDTH',25);
define('UNITE_WIDTH',9);
define('QTE_WIDTH',12);
define('PUHT_WIDTH',15);
define('PTHT_WIDTH', 20);
define('TYPE_CDE_WIDTH', 5);
define('DISPO_WIDTH', 5);
define('LIVRE_WIDTH', 5);

define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH + DISPO_WIDTH + LIVRE_WIDTH) ); // s'appadate � la largeur de la page

//echo DESIGNATION_DEVIS_WIDTH.' '.DESIGNATION_DEVIS_NET_WIDTH;

class PDF extends FPDF
{
	//EN-T�TE
	function Header()
	{	global $row_entete,$SOCIETE,$info_user ;
		
		// logo gauche et droite en haut de page
		if (PDF_CDE_ADH_LOGO_HAUT_GAUCHE)	$this->Image('gfx/'.PDF_CDE_ADH_LOGO_HAUT_GAUCHE,0,0,62);
		if (PDF_CDE_ADH_LOGO_HAUT_DROITE)	$this->Image('gfx/'.PDF_CDE_ADH_LOGO_HAUT_DROITE,PAGE_WIDTH - 50,0,50);

		// le d�pot a livr� et les coordonn�es du fournisseur
		//$this->SetXY(70,2);
		$this->SetFont('helvetica','',10);
		$this->SetTextColor(0,0,0);
		$this->SetXY(90,2);
		
		//var_dump($row_entete); exit;
					
			
		// rectangle en top de page
		$this->SetDrawColor(0,0,0);
		$this->Rect(LEFT_MARGIN,TOP_MARGIN -7,PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN, 15);

		// Le cartouche d'entete
		$this->SetXY(LEFT_MARGIN,38);

		// adh�rent
		$this->SetFont('helvetica','BU',11);
		$this->Cell(20, 5 ,"Adh�rent :");
		$this->SetFont('helvetica','B',11);
		$this->Cell(100, 5 ,/*$row_entete['NOMSB']*/ $info_user['name']);
		
		// nom Client
		$this->SetFont('helvetica','',11);
		$this->Cell(15, 5 ,"Cde du ".join('/',array_reverse(explode('-',$row_entete['date_bon']))));
		$this->Ln();

		// N� de bon
		$this->SetFont('helvetica','B',11);
		$this->Cell(16, 5 ,"N� Cde : ");
		$this->Cell(104, 5 ,$row_entete['numero_bon']);

		// representant
		$this->SetFont('helvetica','',11);
		$this->Cell(30, 5 ,"Date de livraison");
		$this->SetFont('helvetica','',11);
		$this->Cell(50, 5 , join('/',array_reverse(explode('-',$row_entete['date_liv']))));
		$this->Ln();

		// Date de cr�ation du devis
		$this->SetFont('helvetica','BI',11);
		$this->Cell(20, 5 ,"Suivi par : ");
		$this->Cell(100, 5 , $row_entete['nom_vendeur']);

		// R�f�rence
		$this->SetFont('helvetica','',11);
		$this->Cell(50, 5 ,"R�f : $row_entete[reference]");
		$this->Ln();

		$this->Ln(2);

		// titre
		$oldY = $this->GetY();
		$this->SetFont('helvetica','B',12);
		$this->SetTextColor(255,0,0);
		$this->Cell(0,5,"BON DE COMMANDE ADHERENT",0,1,'C');

		// legende
		$this->SetY($oldY);
		$this->SetFont('helvetica','B',8);
		$this->SetTextColor(0,0,0);
		$this->Cell(0,5,"S=Sp�cial, D=Dispo, L=Livr�",0,1,'R');
		$this->Ln(1);

		//Entete des articles
		$this->SetFont('helvetica','B',10);
		$this->SetTextColor(0,0,0);
		$this->SetDrawColor(0,0,0);
		$this->SetFillColor(220,220,220); // gris clair
		$this->Cell(REF_WIDTH,8,"R�f�rence",1,0,'C',1);
		$this->Cell(FOURNISSEUR_WIDTH,8,"Fournisseur",1,0,'C',1);
		$this->Cell(DESIGNATION_DEVIS_WIDTH,8,"D�signation",1,0,'C',1);
		$this->Cell(UNITE_WIDTH,8,"Unit.",1,0,'C',1);
		$this->Cell(QTE_WIDTH,8,"Qt�",1,0,'C',1);
		$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
		$this->Cell(PTHT_WIDTH,8,"TOTAL HT",1,0,'C',1);
		$this->Cell(TYPE_CDE_WIDTH,8,"S",1,0,'C',1);
		$this->Cell(DISPO_WIDTH,8,"D",1,0,'C',1);
		$this->Cell(LIVRE_WIDTH,8,"L",1,0,'C',1);
		
		$this->Ln();
	}


	//PIED DE PAGE
	function Footer()
	{	global $row_entete,$SOCIETE ;

		// texte avev la date
		$this->SetXY(LEFT_MARGIN,-20);
		$this->SetFont('helvetica','',9);
		$this->Cell(0,5,'Edition du '.date('d/m/Y H:i'),0,1,'C');

		// rectangle arrondi en page a gauche avec n� de page
		$this->SetFont('helvetica','',9);
		$this->SetFillColor(192,192,192);
		$this->RoundedRect(PAGE_WIDTH - RIGHT_MARGIN - 3, PAGE_HEIGHT - 16, 12, 6, 2, 'F');
		//Positionnement � 1,7 cm du bas
		$this->SetXY(PAGE_WIDTH - RIGHT_MARGIN,-17);
		$this->SetTextColor(255,255,255);
		$this->Cell(0,8,$this->PageNo().'/{nb}',0,1,'');
		$this->SetTextColor(0,0,0);
		$this->SetFont('helvetica','',9);
		$this->Cell(0,4,PDF_CDE_ADH_PIED1,0,1,'C');
		$this->SetFont('helvetica','',7);
		$this->Cell(0,4,PDF_CDE_ADH_PIED2,0,1,'C');
	}


// pour faire des pointill�s
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

	function Row($param)
	{
		
		$data = array();
		for($i=0 ; $i<sizeof($param) ; $i++)
			$data[] = isset($param[$i]['text']) ? $param[$i]['text'] : '';

		//Calcule la hauteur de la ligne
		$nb=0;
		for($i=0;$i<count($data);$i++)
			$nb=max($nb,$this->NbLines($this->widths[$i],$data[$i]));
		$h=5*$nb;

		//Effectue un saut de page si n�cessaire
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
			//Repositionne � droite
			$this->SetXY($x+$w,$y);
		}
		//Va � la ligne
		$this->Ln($h);
	}

	function CheckPageBreak($h)
	{
		//Si la hauteur h provoque un d�bordement, saut de page manuel
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
}
?>