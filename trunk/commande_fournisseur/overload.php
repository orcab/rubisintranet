<?
require_once('../inc/fpdf/fpdf.php');
require_once('../inc/qrcode/qrcode.class.php');

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('LEFT_MARGIN',9);
define('RIGHT_MARGIN',LEFT_MARGIN);
define('TOP_MARGIN',45);

define('REF_WIDTH',25);
define('UNITE_WIDTH',9);
define('QTE_WIDTH',12);
define('PUHT_WIDTH',15);
define('REMISE_WIDTH',10);
define('PUNETHT_WIDTH',20);
define('PTHT_WIDTH', 20);

define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + REMISE_WIDTH + PUNETHT_WIDTH + PTHT_WIDTH) ); // s'appadate à la largeur de la page

//echo DESIGNATION_DEVIS_WIDTH.' '.DESIGNATION_DEVIS_NET_WIDTH;

class PDF extends FPDF
{
	//EN-TÊTE
	function Header()
	{	global $row_entete,$vendeurs,$SOCIETE ;
		
		// logo gauche et droite en haut de page
		if (PDF_CDE_FOURNISSEUR_LOGO_HAUT_GAUCHE)	$this->Image('gfx/'.PDF_CDE_FOURNISSEUR_LOGO_HAUT_GAUCHE,0,0,62);
		if (PDF_CDE_FOURNISSEUR_LOGO_HAUT_DROITE)	$this->Image('gfx/'.PDF_CDE_FOURNISSEUR_LOGO_HAUT_DROITE,PAGE_WIDTH - 20,0,20);

		// le dépot a livré et les coordonnées du fournisseur
		$this->SetXY(63,2);
		$this->SetFont('helvetica','',10);
		$this->SetTextColor(0,0,0);
		//$this->SetWidths(array(30,60));

		//Coordonnées de l'agence
		//$this->MultiCell(40,5,PDF_CDE_FOURNISSEUR_ENTETE1);
		$coordonnees_agence = "Dépot à livrer :\n$row_entete[AGENCE_NOM]\n$row_entete[AGENCE_ADR1]\n$row_entete[AGENCE_ADR2]\n$row_entete[AGENCE_ADR3]\nTél : $row_entete[AGENCE_TEL]\nFax : $row_entete[AGENCE_FAX]";
		$this->MultiCell(40,5,$coordonnees_agence);
		$this->SetXY(110,2);
		$this->MultiCell(75,5,"Coordonnées fournisseur :\n".$row_entete['FNOMF'].($row_entete['RUEFO']?"\n$row_entete[RUEFO]":'').($row_entete['VILFO']?"\n$row_entete[VILFO]":'')."\n".($row_entete['CPFOU']?$row_entete['CPFOU']:'')." ".($row_entete['BURFO']?$row_entete['BURFO']:'')."\nTél : ".($row_entete['TELFO']?$row_entete['TELFO']:'')."\nFax : ".($row_entete['TLCFO']?$row_entete['TLCFO']:''));

		//var_dump($row_entete); exit;
					
			
		// rectangle en top de page
		$this->SetDrawColor(0,0,0);
		$this->Rect(LEFT_MARGIN,TOP_MARGIN - 2,PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN, 15);

		// Le cartouche d'entete
		$this->SetXY(LEFT_MARGIN,43);

		// Fournisseur
		$this->SetFont('helvetica','BU',11);
		$this->Cell(26, 5 ,"Fournisseur :");
		$this->SetFont('helvetica','B',11);
		$this->Cell(100, 5 ,$row_entete['FNOMF']);
		
		// nom Client
		$this->SetFont('helvetica','',11);
		$this->Cell(15, 5 ,"Cde du $row_entete[CFEDJ]/$row_entete[CFEDM]/$row_entete[CFEDS]$row_entete[CFEDA]");
		$this->Ln();

		// N° de bon
		$this->SetFont('helvetica','B',11);
		$this->Cell(16, 5 ,"N° Cde : ");
		$this->Cell(110, 5 ,$row_entete['CFBON']);

		// representant
		$this->SetFont('helvetica','',11);
		$this->Cell(30, 5 ,"Date de livraison");
		$this->SetFont('helvetica','',11);
		$this->Cell(50, 5 , "$row_entete[CFELJ]/$row_entete[CFELM]/$row_entete[CFELS]$row_entete[CFELA]");
		$this->Ln();


		// Date de création du devis
		$this->SetFont('helvetica','BI',11);
		$this->Cell(20, 5 ,"Suivi par : ");
		if (trim($row_entete['CFSER']))
			$this->Cell(87, 5 , isset($vendeurs[trim($row_entete['CFSER'])]) ? $vendeurs[trim($row_entete['CFSER'])] : trim($row_entete['CFSER']));
		else
			$this->Cell(87, 5 , '');
		
		$this->Ln();
		$this->Ln(2);

		// heure d'ouverture
		$this->SetFont('helvetica','B',12);
		$this->SetTextColor(255,0,0);
		$this->Cell(0,5,"BON DE COMMANDE FOURNISSEUR",0,1,'C');
		$this->Ln(0.2);

		// validation impérative
		$this->SetFont('helvetica','',9);
		$this->SetTextColor(0,0,0);
		$this->MultiCell(0,4,"Pour la validation de cette commande, merci de retourner impérativement par fax ou courrier un accusé de réception en précisant vos prix et détails de livraison\nRéception marchandise : lundi -> jeudi 8h à 11h45, 13h30 à 15h\n         Vendredi 8h à 11h45",0,'C');
		$this->SetFont('helvetica','B',14);
		//$this->MultiCell(0,4,"MCS sera fermé pour inventaire les 30 et 31 mars 2009",0,'C');
		//$this->Ln(1);

		//Entete des articles
		$this->SetFont('helvetica','B',10);
		$this->SetTextColor(0,0,0);
		$this->SetDrawColor(0,0,0);
		$this->SetFillColor(220,220,220); // gris clair
		$this->Cell(REF_WIDTH,8,"Référence",1,0,'C',1);
		$this->Cell(DESIGNATION_DEVIS_WIDTH,8,"Désignation",1,0,'C',1);
		$this->Cell(UNITE_WIDTH,8,"Unit.",1,0,'C',1);
		$this->Cell(QTE_WIDTH,8,"Qté",1,0,'C',1);
		$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
		$this->Cell(REMISE_WIDTH,8,"Rem.",1,0,'C',1);
		$this->Cell(PUNETHT_WIDTH,8,"Prix Net",1,0,'C',1);
		$this->Cell(PTHT_WIDTH,8,"TOTAL HT",1,0,'C',1);
		
		$this->Ln();
	}



	//PIED DE PAGE
	function Footer()
	{	global $row_entete,$SOCIETE ;
		
		// qrcode du fichier
		$json = array('t'=>'cdefour','b'=>$row_entete['CFBON'],'c'=>$row_entete['NOFOU'],'d'=>time(),'p'=>$this->PageNo());
		$qrcode = new QRcode(json_encode($json), 'H'); // error level : L, M, Q, H
		//$qrcode = new QRcode("t=cdecli,c=$row_entete[NOBON]/$row_entete[NOCLI],d=".time(), 'H'); // error level : L, M, Q, H
		$qrcode->displayFPDF($this, RIGHT_MARGIN -7, PAGE_HEIGHT-22, 20);

		// texte avev la date
		$this->SetXY(LEFT_MARGIN,-20);
		$this->SetFont('helvetica','',9);
		$this->Cell(0,5,'Edition du '.date('d/m/Y H:i'),0,1,'C');

		// rectangle arrondi en page a gauche avec n° de page
		$this->SetFont('helvetica','',9);
		$this->SetFillColor(192,192,192);
		$this->RoundedRect(PAGE_WIDTH - RIGHT_MARGIN - 3, PAGE_HEIGHT - 16, 12, 6, 2, 'F');
		//Positionnement à 1,7 cm du bas
		$this->SetXY(PAGE_WIDTH - RIGHT_MARGIN,-17);
		$this->SetTextColor(255,255,255);
		$this->Cell(0,8,$this->PageNo().'/{nb}',0,1,'');
		$this->SetTextColor(0,0,0);
		$this->SetFont('helvetica','',8);
		$this->Cell(0,4,PDF_CDE_FOURNISSEUR_PIED1,0,1,'C');
		$this->SetFont('helvetica','',7);
		$this->Cell(0,4,PDF_CDE_FOURNISSEUR_PIED2,0,1,'C');
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


}

?>
