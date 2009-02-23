<?
require_once('../inc/fpdf/fpdf.php');

class PDF extends FPDF
{
	///////////////////////// ENTETE DE PAGE ///////////////////////////////
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



	///////////////////////// PIED DE PAGE ///////////////////////////////
	function Footer()
	{	global $old_style,$last_img_bottom,$PRINT_PAGE_NUMBER,$PRINT_EDITION_DATE ;
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
			$this->SetXY(20,-17);
			$this->Cell(0,10,"Date d'édition : ".date('d/m/Y').".      Les articles ayant une * ne sont pas stockés à la coopérative.",0,0,'');
		}
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


	///////////////////////// AFFICHE LA OU LES IMAGES ASSOCIÉES AUX CATEGORIES ///////////////////////////////
	function DrawImagesCateg() {
		global $IMAGE,$row,$last_img_bottom;
		
		if (isset($IMAGE[$row['CHEMIN']])) {
			//debug("   Debut image de categ : GetY=".$this->GetY()."\n");
			$last_img_bottom = $this->GetY() ;
			for ($i=0; $i<sizeof($IMAGE[$row['CHEMIN']]) ; $i++) {
					$img_info		= getimagesize($IMAGE[$row['CHEMIN']][$i]);
					$hauteur_image	= $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

					if ($hauteur_image + $last_img_bottom > PAGE_HEIGHT - 28) { // si l'image dépasse en bas, on ignore les autres images
						continue;
					} else {
						$this->Image($IMAGE[$row['CHEMIN']][$i],PAGE_WIDTH - 60,$last_img_bottom + 2*$i,IMAGE_WIDTH); // taille a 200 de l'image
						$last_img_bottom += $hauteur_image ;
					}
			}
			//$last_img_bottom += $this->GetY();
			//debug("   Fin image de categ : GetY=".$this->GetY()."   \$last_img_bottom=$last_img_bottom\n");
		}
	}



	///////////////////////// AFFICHE LA OU LES IMAGES ASSOCIÉES AUX ARTICLES ///////////////////////////////
	function DrawImagesArticle() {
		global $IMAGE,$row;
		
		if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on l'affiche
			//debug("image(s) associé(s) à $row[NOART] GetY=".$this->GetY()."\n");
			$max_height = 0;
			$nb_image_pour_la_ligne = 0;
			for ($i=0 , $j=0; $i<sizeof($IMAGE[$row['NOART']]) ; $i++ , $j++) { // toutes les 5 images, on passe une ligne

				if (($i % 5)==0 || $i==0) { // toutes les 5 image, on calcule la nouvelle hauteur de la rangé
					$nb_image_pour_la_ligne = 0;
					for ($z=$i ; $z<sizeof($IMAGE[$row['NOART']]) && $z < $i+5 ; $z++) { // on essai de trouver la plus hautes des 5 image en ligne
						$img_info = getimagesize($IMAGE[$row['NOART']][$z]);
						$hauteur_image = $img_info[1] * IMAGE_WIDTH / $img_info[0];
						$max_height = max($max_height,$hauteur_image) ;
						$nb_image_pour_la_ligne++;
						//debug("\$i=$i,\$z=$z   \$hauteur_image=$hauteur_image\n");
					}
					//debug("\$i=$i   \$max_height=$max_height\n");
				}

				$ecart_x = (PAGE_WIDTH - IMAGE_WIDTH * $nb_image_pour_la_ligne) / ($nb_image_pour_la_ligne + 1) ;
				$this->Image($IMAGE[$row['NOART']][$i],$ecart_x + (IMAGE_WIDTH+2) * $j ,$this->GetY() + 3,IMAGE_WIDTH); // taille a 200 de l'image
				
				if (intval(($j+1) / 5) > 0) { // on saute une ligne

					//debug("Image n°".($i+1)." $max_height + ".$this->GetY()." (".($max_height + $this->GetY()).")    PAGE_HEIGHT - 27=".(PAGE_HEIGHT - 27)."\n");

					if ($max_height + $this->GetY() + $max_height > PAGE_HEIGHT - 27) // on doit changer de page
						$this->AddPage();
					else // on peut rester sur la meme page, on saut une grosse ligne
						$this->Ln($max_height + 3);

					$j=-1;
					$max_height = 0 ;
				}
			}
			$this->Ln($max_height + 5);
		}
	}


	///////////////////////// AJOUTE LA CATEGORIE AU SOMMAIRE ///////////////////////////////
	function AddCategToSummary($lien_vers_page) {
		global $pdvente,$section_deja_dans_toc,$TOC;

		$tmp = explode(' / ',$pdvente);
		for($i=0 ; $i<sizeof($tmp) ; $i++) {
			$niveau_en_cours = join('/',array_slice($tmp,0,$i+1));
			if (!isset($section_deja_dans_toc[$niveau_en_cours])) { // si section pas deja traité
				array_push($TOC,array(	$tmp[$i],		// ID
										$this->PageNo(),	// No DE PAGE
										$lien_vers_page,// LIEN
										$i				// décalage
									)
				);	
				$section_deja_dans_toc[$niveau_en_cours] = 1;
			}
		}
	}


	///////////////////////// POUR AFFICHER LES ENTETE DE TABLEAUX ARTICLES ///////////////////////////////
	function PrintTableHeader() {
		global $style;

		$this->SetLineWidth(0.1);
		$this->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
		$this->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
		$this->SetFont('helvetica','B',7);
		$this->Cell(WIDTH_CODE		,6,'CODE','LT',0,'L',1);
		$this->Cell(WIDTH_DESIGNATION,6,'DÉSIGNATION','T',0,'L',1);
		$this->Cell(WIDTH_REF		,6,'RÉF.','T',0,'L',1);
		$this->Cell(WIDTH_PRIX		,6,'PRIX '.EURO.' HT','T',0,'L',1);
		$this->Cell(WIDTH_ECOTAXE	,6,'ECO '.EURO,'TR',0,'L',1);
		$this->Ln();
	}



	///////////////////////// POUR AFFICHER LE TITRE DE LA CATEG ///////////////////////////////// 
	function PrintCategTitle($titre, $lien_vers_page=false) {
		global $style,$lien_vers_page;

		$this->SetLineWidth(0.5);
		$this->SetDrawColor($style[RED_BACKGROUND_TITLE],$style[GREEN_BACKGROUND_TITLE],$style[BLUE_BACKGROUND_TITLE]);

		// texte en gras de la couleur de la section
		$this->SetTextColor($style[RED_CATEG],$style[GREEN_CATEG],$style[BLUE_CATEG]);
		
		// on réduit la taille de la police si le titre est trop long pour la page
		$this->redux_font_size($titre,FONT_SIZE_CATEG,PAGE_WIDTH - 15,'B');
		
		// on ajout un lien au sommaire et on imprime le titre
		if ($lien_vers_page)
			$this->Cell(0,9,$titre ,0,1,'',0,  $this->SetLink($lien_vers_page)  );
		else
			$this->Cell(0,9,$titre ,0,1,'',0 );

		$this->Ln(2);

		$string_width = intval($this->GetStringWidth($titre));
		// rectangle arrondi autour du mini titre
		$this->RoundedRect($this->GetX()-1, $this->GetY()-10.5, $string_width + 7 , 8, 3.5);

		// ligne fillante
		if ($string_width < PAGE_WIDTH - 30)
			$this->Line($string_width + 16.5, $this->GetY()-6.5 , PAGE_WIDTH - 15, $this->GetY()-6.5);
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
