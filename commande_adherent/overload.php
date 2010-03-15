<?

//require_once('../inc/constant.php');
require_once('../inc/fpdf/fpdf.php');

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('LEFT_MARGIN',9);
define('RIGHT_MARGIN',LEFT_MARGIN);
define('TOP_MARGIN',45);

define('REF_WIDTH',25);
define('FOURNISSEUR_WIDTH',25);
define('LOCAL_WIDTH', 20);
define('UNITE_WIDTH',9);
define('QTE_WIDTH',12);
define('PUHT_WIDTH',15);
define('PTHT_WIDTH', 20);
define('TYPE_CDE_WIDTH', 5);


if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis demandé sans prix
	if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) // uniquement les lignes R
		define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + LOCAL_WIDTH + UNITE_WIDTH + QTE_WIDTH + TYPE_CDE_WIDTH) ); // s'appadate à la largeur de la page
	else
		define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + UNITE_WIDTH + QTE_WIDTH + TYPE_CDE_WIDTH) ); // s'appadate à la largeur de la page
} else {
	define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH) ); // s'appadate à la largeur de la page
}


//echo DESIGNATION_DEVIS_WIDTH.' '.DESIGNATION_DEVIS_NET_WIDTH;

class PDF extends FPDF
{
	//EN-TÊTE
	function Header()
	{	global $row_entete,$vendeurs,$SOCIETE,$jours_mini,$tournee_chauffeur ;
		
		// logo gauche et droite en haut de page
		if (PDF_CDE_ADH_LOGO_HAUT_GAUCHE)	$this->Image('gfx/'.PDF_CDE_ADH_LOGO_HAUT_GAUCHE,0,0,62);
		if (PDF_CDE_ADH_LOGO_HAUT_DROITE)	$this->Image('gfx/'.PDF_CDE_ADH_LOGO_HAUT_DROITE,PAGE_WIDTH - 50,0,50);

		// le dépot a livré et les coordonnées du fournisseur
		//$this->SetXY(70,2);
		$this->SetFont('helvetica','',10);
		$this->SetTextColor(0,0,0);
		$this->SetXY(70,2);
		$this->MultiCell(100,5,"Coordonnées adhérent :\n".
				$row_entete['NOMSB'].
				($row_entete['AD1SB']?"\n$row_entete[AD1SB]":'').
				($row_entete['AD2SB']?"\n$row_entete[AD2SB]":'').
				($row_entete['CPOSB']?"\n$row_entete[CPOSB]":'')." ".
				($row_entete['BUDSB']?$row_entete['BUDSB']:'').
				($row_entete['TELCL']?"\nTél : $row_entete[TELCL]":''). ($row_entete['TELCC']?"    Tél 2 : $row_entete[TELCC]":'').
				($row_entete['TLCCL']?"\nFax : $row_entete[TLCCL]":''). ($row_entete['TLXCL']?"    Tél 3 : $row_entete[TLXCL]":'').
				($row_entete['COMC1']?"    Email : ".strtolower($row_entete['COMC1']):'')
			);

		//var_dump($row_entete); exit;
					
			
		// rectangle en top de page
		$this->SetDrawColor(0,0,0);
		$this->Rect(LEFT_MARGIN,TOP_MARGIN -7,PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN, 15);

		// Le cartouche d'entete
		$this->SetXY(LEFT_MARGIN,38);

		// adhérent
		$this->SetFont('helvetica','BU',11);
		$this->Cell(20, 5 ,"Adhérent :");
		$this->SetFont('helvetica','B',11);
		$this->Cell(100, 5 ,$row_entete['NOMSB']);
		

		// nom Client
		$this->SetFont('helvetica','',11);
		$this->Cell(15, 5 ,"Cde du $row_entete[DSECJ]/$row_entete[DSECM]/$row_entete[DSECS]$row_entete[DSECA]");
		$this->Ln();

		// N° de bon
		$this->SetFont('helvetica','B',11);
		$this->Cell(16, 5 ,"N° Cde : ");
		$this->Cell(104, 5 ,$row_entete['NOBON']);

		// representant
		$this->SetFont('helvetica','',11);
		$this->Cell(30, 5 ,"Date de livraison");
		$this->SetFont('helvetica','',11);
		$this->Cell(50, 5 , "$row_entete[DLJSB]/$row_entete[DLMSB]/$row_entete[DLSSB]$row_entete[DLASB]");
		$this->Ln();

		// Date de création du devis
		$this->SetFont('helvetica','BI',11);
		$this->Cell(20, 5 ,"Suivi par : ");
		$vendeur = '';
		if (trim($row_entete['LIVSB']))
			$vendeur = isset($vendeurs[trim($row_entete['LIVSB'])]) ? $vendeurs[trim($row_entete['LIVSB'])] : trim($row_entete['LIVSB']);
		$this->Cell(100, 5 , $vendeur);
	
		// Référence
		$this->SetFont('helvetica','',11);
		$this->Cell(50, 5 ,"Réf : $row_entete[RFCSB]");
		$this->Ln();

		$this->Ln(1.5);

		// A LIVRER LE
		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options']) && $row_entete['TOUCL']) { // on affiche les tournée du client
			$tournee = array();
			foreach (str_split($row_entete['TOUCL']) as $id)
				array_push($tournee,$jours_mini[$id].'('.substr($tournee_chauffeur[$row_entete['TOUCL']][$id],0,3).')');

			$this->SetFont('helvetica','B',12);
			$this->SetTextColor(0,0,255);
			$this->Cell(0,5,"A LIVRER LE :                                 ".strtoupper(join('     -     ',$tournee)),0,1,'L');
			$this->Ln(0.2);
		}


		// titre
		$this->SetFont('helvetica','B',12);
		$this->SetTextColor(255,0,0);
		$this->Cell(0,5,"BON DE COMMANDE ADHERENT",0,1,'C');
		$this->Ln(0.5);

		if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) { // uniquement les lignes R
			$this->SetFont('helvetica','B',10);
			$this->Cell(0,5,"ATTENTION LES LIGNES DEJA LIVRES N'APPARAISSENT PAS",0,1,'C');
			$this->Ln(1);
		}

		//Entete des articles
		$this->SetFont('helvetica','B',10);
		$this->SetTextColor(0,0,0);
		$this->SetDrawColor(0,0,0);
		$this->SetFillColor(220,220,220); // gris clair
		$this->Cell(REF_WIDTH,8,"Référence",1,0,'C',1);
		$this->Cell(FOURNISSEUR_WIDTH,8,"Fournisseur",1,0,'C',1);
		$this->Cell(DESIGNATION_DEVIS_WIDTH,8,"Désignation",1,0,'C',1);
		if (isset($_GET['options']) && in_array('ligne_R',$_GET['options'])) // uniquement les lignes R
			$this->Cell(LOCAL_WIDTH,8,"Local.",1,0,'C',1);
		$this->Cell(UNITE_WIDTH,8,"Unit.",1,0,'C',1);
		$this->Cell(QTE_WIDTH,8,"Qté",1,0,'C',1);

		if (isset($_GET['options']) && in_array('sans_prix',$_GET['options'])) { // devis demandé sans prix

		} else {
			$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
			$this->Cell(PTHT_WIDTH,8,"TOTAL HT",1,0,'C',1);
		}

		$this->Cell(TYPE_CDE_WIDTH,8,"S",1,0,'C',1);
		$this->Ln();
	}



	//PIED DE PAGE
	function Footer()
	{	global $row_entete,$SOCIETE ;
		
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
		$this->SetFont('helvetica','',9);
		$this->Cell(0,4,PDF_CDE_ADH_PIED1,0,1,'C');
		$this->SetFont('helvetica','',7);
		$this->Cell(0,4,PDF_CDE_ADH_PIED2,0,1,'C');
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
