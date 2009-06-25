<?
require_once('../inc/fpdf/fpdf.php');

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('LEFT_MARGIN',9);
define('RIGHT_MARGIN',LEFT_MARGIN);
define('TOP_MARGIN',45);

define('REF_WIDTH',25);
define('FOURNISSEUR_WIDTH',27);
define('QTE_WIDTH',10);
define('STOCK_WIDTH',10);
define('EXPO_WIDTH',10);
define('PNHT_WIDTH',20);

/*if (in_array('non_chiffre',$options)) { // non chiffré
	define('PUHT_WIDTH',0);
	define('PTHT_WIDTH',0);
//} else { // chiffré */
	define('PUHT_WIDTH',20);
	define('PTHT_WIDTH',20);
//} 


define('DESIGNATION_DEVIS_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH) ); // s'appadate à la largeur de la page
/*
define('DESIGNATION_DEVIS_NET_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + QTE_WIDTH + PNHT_WIDTH + PUHT_WIDTH + PTHT_WIDTH) ); // s'appadate à la largeur de la page
define('DESIGNATION_GAMME_WIDTH',PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN - (REF_WIDTH + FOURNISSEUR_WIDTH + STOCK_WIDTH + EXPO_WIDTH + PUHT_WIDTH) ); // s'appadate à la largeur de la page
*/

//echo DESIGNATION_DEVIS_WIDTH.' '.DESIGNATION_DEVIS_NET_WIDTH;

class PDF extends FPDF
{
	//EN-TÊTE
	function Header()
	{	global $values,$SOCIETE,$options,$_POST,$id_devis,$date_devis ;
		
		// logo gauche et droite en haut de page si le theme le demande
		//if (eregi('_avec_entete$',$values['devis.theme'])) {
		if (!in_array('no_header',$options)) {
			if (PDF_DEVIS_LOGO_HAUT_GAUCHE)	$this->Image('gfx/'.PDF_DEVIS_LOGO_HAUT_GAUCHE,0,0,62);
			if (PDF_DEVIS_LOGO_HAUT_DROITE)	$this->Image('gfx/'.PDF_DEVIS_LOGO_HAUT_DROITE,PAGE_WIDTH - 50,0,50);
		}

		// rectangle en top de page
		$this->SetDrawColor(0,0,0);
		$this->Rect(LEFT_MARGIN,TOP_MARGIN - 8,PAGE_WIDTH - LEFT_MARGIN - RIGHT_MARGIN, 35);

		// Le cartouche d'entete
		$this->SetTextColor(0,0,0);
		$this->SetXY(LEFT_MARGIN, TOP_MARGIN - 7);

		// Artisan
		$this->SetFont('helvetica','BU',11);
		$this->Cell(21, 5 ,"ARTISAN :");
		$this->SetFont('helvetica','B',11);
		$this->Cell(90, 5 , $_POST['artisan_nom'] == 'NON Adherent' ? $_POST['artisan_nom_libre'] : $_POST['artisan_nom'] );
		
		// nom Client
		$this->SetFont('helvetica','',11);
		$this->Cell(60, 5 ,$_POST['client_nom']);
		$this->Ln();

		// representant
		$this->SetFont('helvetica','BI',11);
		$this->Cell(21, 5 ,"Suivi par :");
		$this->SetFont('helvetica','',11);
		$this->Cell(90, 5 ,$_POST['artisan_representant']);

		// client adresse 1
		$this->SetFont('helvetica','I',11);
		$this->Cell(90, 5 ,$_POST['client_adresse']);
		$this->Ln();

		// client adresse 2
		$this->Cell(111);
		$this->Cell(60, 5 ,$_POST['client_adresse2']);
		$this->Ln();

		// client CP
		$this->SetFont('helvetica','',11);
		$this->Cell(111);
		$this->Cell(13, 5 ,$_POST['client_codepostal']);

		// client ville
		$this->Cell(60, 5 ,$_POST['client_ville']);
		$this->Ln();

		// N° de bon
		$this->SetFont('helvetica','B',11);
		$this->Cell(34, 5 ,"BON DE CHOIX n°");
		$this->Cell(77, 5 , $id_devis );

		// client tel 1
		$this->Cell(60, 5 ,$_POST['client_telephone']);
		$this->Ln();

		// Date de création du devis
		$this->SetFont('helvetica','',10);
		$this->Cell(15, 5 ,"Devis du ");
		$this->Cell(77, 5 ,  $date_devis );

		// client tel 2
		$this->Cell(19);
		$this->Cell(60, 5 ,   $_POST['client_telephone2']   );
		$this->Ln();

		// si on est avec un devis prix ADH
		if (in_array('px_adh',$options)) {
			$this->SetTextColor(255,0,0);
			$this->SetFont('helvetica','B',12);
			$this->Cell(90, 5 ,"Les prix affichés sont NET HT Adhérents");
			$this->SetTextColor(0,0,0);
		} else {
			$this->Cell(90);
		}

		// client email
		$this->SetFont('helvetica','',10);
		$this->Cell(21);
		$this->Cell(60, 5 ,$_POST['client_email']);
		$this->Ln(6);

		// heure d'ouverture
		$this->SetFont('helvetica','',9);
		$this->SetTextColor(255,0,0);
		$this->Cell(0,5,PDF_DEVIS_ENTETE1,0,1,'C');
		$this->Cell(0,5,PDF_DEVIS_ENTETE2,0,1,'C');
		$this->Ln(2);

		//Entete des articles
		$this->SetFont('helvetica','B',10);
		$this->SetTextColor(0,0,0);
		$this->SetDrawColor(0,0,0);
		$this->SetFillColor(220,220,220); // gris clair
		$this->Cell(REF_WIDTH,8,"Référence",1,0,'C',1);

		$this->Cell(FOURNISSEUR_WIDTH,8,"Fournisseur",1,0,'C',1);

		/*if (eregi('^devis_net',$values['devis.theme'])) {
			$this->Cell(DESIGNATION_DEVIS_NET_WIDTH,8,"Désignation",1,0,'C',1);
			$this->Cell(QTE_WIDTH,8,"Qté",1,0,'C',1);
		//	if (in_array('non_chiffre',$options)) { // non chiffré

		//	} else { // chiffré
				$this->Cell(PNHT_WIDTH,8,"P.U Net",1,0,'C',1);
				$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
				$this->Cell(PTHT_WIDTH,8,"TOTAL HT",1,0,'C',1);
		//	}
		} elseif (eregi('^devis',$values['devis.theme'])) { */
			$this->Cell(DESIGNATION_DEVIS_WIDTH,8,"Désignation",1,0,'C',1);
			$this->Cell(QTE_WIDTH,8,"Qté",1,0,'C',1);
			/*if (in_array('non_chiffre',$options)) { // non chiffré

			} else { // chiffré */
				$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
				$this->Cell(PTHT_WIDTH,8,"TOTAL HT",1,0,'C',1);
//			}
		/*} elseif (eregi('^gamme',$values['devis.theme'])) {
			$this->Cell(DESIGNATION_GAMME_WIDTH,8,"Désignation",1,0,'C',1);
			$this->Cell(STOCK_WIDTH,8,"stock",1,0,'C',1);
			$this->Cell(EXPO_WIDTH,8,"expo",1,0,'C',1);
			if (in_array('non_chiffre',$options)) { // non chiffré

			} else { // chiffré
				$this->Cell(PUHT_WIDTH,8,"P.U HT",1,0,'C',1);
			}
		}*/
		$this->Ln();
	}



	//PIED DE PAGE
	function Footer()
	{	global $values,$SOCIETE,$options ;

		// texte avev la date
		$this->SetXY(LEFT_MARGIN,-30);
		$this->SetFont('helvetica','',9);
		$this->Cell(0,4,'Edition du '.date('d/m/Y').' à '.date('H:i'),0,1,'C');

		$this->SetFont('helvetica','BI',8);
//		if (eregi('^devis_net',$values['devis.theme'])) {
			//$this->MultiCell(0,3,PDF_DEVIS_PRIX_NET1,0,'C');
			//$this->SetFillColor(200,200,200); // gris clair
			//$this->MultiCell(0,3,PDF_DEVIS_PRIX_NET2,0,'C',1);	
//		} elseif (eregi('^devis',$values['devis.theme'])) {
			$this->SetXY(PAGE_WIDTH - RIGHT_MARGIN - 65,-30);
			$this->MultiCell(65,3,PDF_DEVIS_PRIX_PUBLIC1,0,'C');
/*		} elseif (eregi('^gamme',$values['devis.theme'])) {
			$this->MultiCell(0,3,PDF_DEVIS_GAMME1,0,'C');
		}*/

		// rectangle arrondi en page a gauche avec n° de page
		$this->SetFont('helvetica','',9);
		$this->SetFillColor(192,192,192);
		$this->RoundedRect(PAGE_WIDTH - RIGHT_MARGIN - 3, PAGE_HEIGHT - 7, 12, 6, 2, 'F');
		//Positionnement à 1,7 cm du bas
		$this->SetXY(PAGE_WIDTH - RIGHT_MARGIN,-8);
		$this->SetTextColor(255,255,255);
		$this->Cell(0,8,$this->PageNo().'/{nb}',0,1,'');
		if (!in_array('no_header',$options)) {
			$this->SetY(-20);
			$this->SetTextColor(0,0,0);
			$this->SetFont('helvetica','',9);
			$this->Cell(120,4,PDF_DEVIS_PIED1,0,1,'L');
			$this->SetFont('helvetica','',7);
			$this->MultiCell(100,4,PDF_DEVIS_PIED2);
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

			$this->SetFont(isset($param[$i]['font-family'])?$param[$i]['font-family']:'',isset($param[$i]['font-style'])?$param[$i]['font-style']:'',isset($param[$i]['font-size'])?$param[$i]['font-size']:'');
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
