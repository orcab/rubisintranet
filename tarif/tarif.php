<?
include('../inc/config.php');
require_once('overload.php');

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


define('FONT_SIZE_CODE',8);
define('FONT_SIZE_REF',8);
define('FONT_SIZE_PRIX',8);
define('FONT_SIZE_DESIGNATION',7);
define('WIDTH_CODE',20);
define('WIDTH_DESIGNATION',70);
define('WIDTH_REF',20);
define('WIDTH_PRIX',15);
define('HEIGHT_ARTICLE',6);

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('IMAGE_WIDTH',40);

// ne traite que l'électromenager
$electromenager = 1;

define('IMAGE_PATH','image/electromenager/' );
define('PAGE_DE_GARDE_PATH','image/page_de_garde/' );

$section_deja_dans_toc = array();
$TOC = array(); // pour la table des matieres
$REFERENCE = array(); // pour la table d'index des reference



// creation de l'arbre des categorie
$sql = <<<EOT
SELECT	tc.id,tc.nom,valeur,chemin,page_de_garde
FROM	tarif_categ tc,tarif_style ts
WHERE		tc.id_style=ts.id
		AND tc.electromenager=1
ORDER BY chemin ASC
EOT;

$res = mysql_query($sql) or die("ne peux pas recupérer les categorie ".mysql_error());
$categorie = array();
while($row = mysql_fetch_array($res)) {
	$categorie["nom_$row[id]"]		= $row['nom'];
	$categorie["style_$row[id]"]	= $row['valeur'];
	$categorie["chemin_$row[id]"]	= $row['chemin'];
	$categorie["pagegarde_$row[id]"]= $row['page_de_garde'];
}

// recherche des article a exporté pour le tarif
	$sql = <<<EOT
SELECT	ta.code_article,ta.designation AS article_designation,ta.px_pub_ttc AS prix_net,ta.ref_fournisseur,ta.px_coop_ht,ta.px_adh_ht,ta.px_eco_ttc,
		ta.designation AS tarif_designation,ta.image AS tarif_image,
		tc.chemin,tc.id AS categ_id, TRIM(BOTH '-' FROM CONCAT( tc.chemin ,'-',tc.id) ) AS chemin_complet,
		TRIM(BOTH '-' FROM CONCAT( (SELECT nom FROM tarif_categ WHERE id=tc.chemin) ,'-',tc.nom) ) AS chemin_complet_nom,
		tc.saut_de_page, tc.image AS categ_image,
		(SELECT valeur FROM tarif_style WHERE id=tc.id_style) AS style_categ_valeur,
		(SELECT valeur FROM tarif_style WHERE id=ta.id_style) AS style_article_valeur
FROM	tarif_article ta,
		tarif_categ tc
WHERE		ta.id_categ = tc.id
		AND ta.electromenager=1
		AND tc.electromenager=1
--		AND ta.code_article = '90314154'
--		AND tc.id=3538
--		AND tc.nom LIKE 'Brun %'
ORDER BY	chemin_complet_nom ASC,nom ASC
EOT;


$res = mysql_query($sql) or die("ne peux pas envoyé la requete à mysql ".mysql_error());

// creation de l'objet
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');


// on passe sur chaque article
$last_img_bottom = 0;
$old_pdvente = ''; $old_style = array();
$r_old_pdvente = array(0);
$lien = 1 ;
while($row = mysql_fetch_array($res)) {

	// on definit son style
	if($row['style_article_valeur']) { // un style est renseigné pour l'article
		$style = html2rgb($row['style_article_valeur']);
	} elseif($row['style_categ_valeur']) {	// pas de style pour l'article, on utilise celui de la categorie
		$style = html2rgb($row['style_categ_valeur']);
	} else { // pas de style pour la categ, on recherche celui du pere
		// on remonte le chemin a l'envers jusqu'a trouvé un style
		foreach (array_reverse(explode('-',$row['chemin_complet'])) as $id_categ) {
			if ($categorie["style_{$id_categ}"]) { // si la categ existe, on la prend
				$style = html2rgb($categorie["style_{$id_categ}"]);
				break ;
			}
		}
	}

	// on construit sa categorie avec le chemin complet
	$pdvente = ''; $tmp = array();
	$r_pdvente = explode('-',$row['chemin_complet']);
	foreach ($r_pdvente as $id_categ)
		if ($id_categ)
			array_push($tmp,$categorie["nom_{$id_categ}"]);

	//if ($electromenager)
	array_shift($tmp);
	$pdvente = join(' / ',$tmp) ;


	// s'il change de categorie par rapport au prececent, on créer le changement de categorie
	if ($old_pdvente != $pdvente) {
		// chamgement de super section, on gere une page de garde
		if ($r_pdvente[0] != $r_old_pdvente[0] && $categorie['pagegarde_'.$r_pdvente[0]]) { // la categorie demande un saut de page
			$pdf->AddPage();
			$pdf->Image(PAGE_DE_GARDE_PATH.$categorie['pagegarde_'.$r_pdvente[0]],0,0,PAGE_WIDTH); // taille a 200 de l'image
			$old_style = $style;
			$pdf->AddPage();
		}

		// au changement de catégorie, on gere un saut de texte
		if ($row['saut_de_page'] || $pdf->PageNo() == 0) { // la categorie demande un saut de page
			$pdf->AddPage();
			$last_img_bottom = 0;
		} else {
			if($pdf->GetY() > PAGE_HEIGHT - 53) { // check le saut de page
				$pdf->AddPage();
				$last_img_bottom = 0;
			}
			else
				$pdf->Ln(10); // pas de saut de page mais juste un décalage de 1cm
		}


		// permet de gérer les eventuel saut de page si l'image dépasse
		if ($row['categ_image']) { // s'il y a une image de spécifié, on l'affiche
			$row['categ_image'] = ereg_replace('^,+|,+$','',$row['categ_image']);
			$imgs = explode(',',$row['categ_image']);
			if (sizeof($imgs) == 1) { // la premiere image
				$img_info = getimagesize(IMAGE_PATH.$imgs[0]);
				$img_height = $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

				if (0 && $imgs[0] == 'fagor frigo top.jpg') {
					//print_r($img_info);
					echo "DEBUG img_height='$img_height'<br>\n";
					echo "BAS DE PAGE sans image '".($pdf->GetY())."'<br>\n";
					echo "BAS DE PAGE avec image '".($pdf->GetY() + $img_height)."'<br>\n";
					echo "DEBUG last_img_bottom='$last_img_bottom'<br>\n";
					echo "HAUTEUR DE PAGE '".PAGE_HEIGHT."'<br>\n";
				}

				//if ($pdf->GetY() + $img_height > PAGE_HEIGHT) {
				if ($last_img_bottom + $img_height + 5> PAGE_HEIGHT) {
					$pdf->AddPage();
					$last_img_bottom = 0;
				}
			}
		}

		if ($last_img_bottom) { // on vérifie que la nouvelle categ soit bien en dessous de la photo de l'ancienne categ
			//echo "Y=".$pdf->GetY()."   \$last_img_bottom=$last_img_bottom<br>\n";
			if ($pdf->GetY() < $last_img_bottom)
				$pdf->SetY($last_img_bottom);
			$last_img_bottom = 0;
		}

		// dessin du titre de la categorie	
		$pdf->SetLineWidth(0.5);
		$pdf->SetFont('helvetica','B',12);
		$pdf->SetDrawColor($style[RED_BACKGROUND_TITLE],$style[GREEN_BACKGROUND_TITLE],$style[BLUE_BACKGROUND_TITLE]);

		// texte en gras de la couleur de la section
		$pdf->SetTextColor($style[RED_CATEG],$style[GREEN_CATEG],$style[BLUE_CATEG]);
		// Cell(float w [, float h [, string txt [, mixed border [, int ln [, string align [, int fill [, mixed link]]]]]]])
		$lien_vers_page = $pdf->AddLink();
		$pdf->Cell(0,9,$pdvente ,0,1,'',0,         $pdf->SetLink($lien_vers_page)       );
		$pdf->Ln(2);

		// rectangle arrondi autour du mini titre + ligne fillante
		$pdf->RoundedRect($pdf->GetX()-1, $pdf->GetY()-10.5, intval($pdf->GetStringWidth($pdvente)) + 7 , 8, 3.5);
		$pdf->Line(intval($pdf->GetStringWidth($pdvente)) + 16.5, $pdf->GetY()-6.5 , PAGE_WIDTH - 15, $pdf->GetY()-6.5);



		// mise a jour de la table des matière
		$r_pdvente = explode('-',$categorie['chemin_'.$id_categ]);
		if ($r_pdvente[0]) { // bug sur les super categ
			$r_pdvente[] = $id_categ;
		} else {
			$r_pdvente[0] = $id_categ;
		}
		$tmp = array();
		for($i=0 ; $i<sizeof($r_pdvente) ; $i++) {
			$tmp[] = $r_pdvente[$i];
			if (!isset($section_deja_dans_toc[ join('-' , array_slice( $r_pdvente ,0,$i+1) ) ])) { // si section pas deja traité
				array_push($TOC, array( $r_pdvente[$i],					// ID
										$pdf->PageNo(),					// No DE PAGE
										$lien_vers_page					// LIEN
										)
							);	
				$section_deja_dans_toc[ join('-' , array_slice( $r_pdvente ,0,$i+1) ) ] = 1;
			}
		}
		
	
		// entete du tableau
		// on image une ou des images simages si spécifié
		if ($row['categ_image']) { // s'il y a une image de spécifié, on l'affiche
			$imgs = explode(',',$row['categ_image']);
			//echo "Je vais afficher des images ".$pdf->GetY()."<br>\n";
			$last_img_bottom = 0 ;
			if (sizeof($imgs) == 1) { // une seul image
				$pdf->Image(IMAGE_PATH.$imgs[0],PAGE_WIDTH - 60,$pdf->GetY(),IMAGE_WIDTH); // taille a 200 de l'image
				$img_info = getimagesize(IMAGE_PATH.$imgs[0]);
				$last_img_bottom += $img_info[1] * IMAGE_WIDTH / $img_info[0] ;
			} elseif (sizeof($imgs) > 1) { // plusieur image
				for ($i=0; $i<sizeof($imgs) ; $i++) {
					$pdf->Image(IMAGE_PATH.$imgs[$i],PAGE_WIDTH - 60,$pdf->GetY() + IMAGE_WIDTH * $i,IMAGE_WIDTH); // taille a 200 de l'image
					$img_info = getimagesize(IMAGE_PATH.$imgs[0]);
					$last_img_bottom += $img_info[1] * IMAGE_WIDTH / $img_info[0] ;
				}
			}
			$last_img_bottom += $pdf->GetY();
		}


		// on dessine l'entete avec les colonnes
		$pdf->SetLineWidth(0.1);
		$pdf->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
		$pdf->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
		$pdf->SetFont('helvetica','B',7);
		$pdf->Cell(WIDTH_CODE			,6,'CODE','LT',0,'L',1);
		$pdf->Cell(WIDTH_DESIGNATION	,6,'DÉSIGNATION','T',0,'L',1);
		$pdf->Cell(WIDTH_REF			,6,'RÉF.','T',0,'L',1);

		$pdf->Cell(WIDTH_PRIX			,6,'PUBLIC '.EURO,'TR',0,'L',1);
		$pdf->Ln();

	} else { // fin on a changer de categ
		// on n'a pas changer de categ mais on vérifie si l'on ne doit pas réimprimer le titre

		if ($pdf->GetY() > PAGE_HEIGHT - 27) { // on est sur une nouvelle page
			$last_img_bottom = 0;

			$pdf->SetLineWidth(0.5);
			$pdf->SetFont('helvetica','B',12);
			$pdf->SetDrawColor($style[RED_BACKGROUND_TITLE],$style[GREEN_BACKGROUND_TITLE],$style[BLUE_BACKGROUND_TITLE]);

			// texte en gras de la couleur de la section
			$pdf->SetTextColor($style[RED_CATEG],$style[GREEN_CATEG],$style[BLUE_CATEG]);
			$pdf->Cell(0,9,"$pdvente (suite)" ,0,1,'',0);
			$pdf->Ln(2);

			// rectangle arrondi autour du mini titre + ligne fillante
			$string_width = intval($pdf->GetStringWidth("$pdvente (suite)")) ;
			$pdf->RoundedRect($pdf->GetX()-1, $pdf->GetY()-10.5, $string_width + 7 , 8, 3.5);
			$pdf->Line($string_width + 16.5, $pdf->GetY()-6.5 , PAGE_WIDTH - 15, $pdf->GetY()-6.5);


			// on dessine l'entete avec les colonnes
			$pdf->SetLineWidth(0.1);
			$pdf->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
			$pdf->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
			$pdf->SetFont('helvetica','B',7);
			$pdf->Cell(WIDTH_CODE			,6,'CODE','LT',0,'L',1);
			$pdf->Cell(WIDTH_DESIGNATION	,6,'DÉSIGNATION','T',0,'L',1);
			$pdf->Cell(WIDTH_REF			,6,'RÉF.','T',0,'L',1);

			$pdf->Cell(WIDTH_PRIX			,6,'PUBLIC '.EURO,'TR',0,'L',1);
			$pdf->Ln();
		}
	}
	
	
	// ecriture de l'article
	if ($row['tarif_image']) // s'il y a une image de spécifié, on l'affiche
		$bordure = 'B';
	else
		$bordure = '' ;
	
	$designation = preg_replace("/[\n\r].+?$/",'',trim($row['tarif_designation'] ? $row['tarif_designation'] : $row['article_designation'])) ;
	$designation = preg_replace("/ +/"," ",trim($designation)) ;

	// couleur de fond pour les articles
	$pdf->SetFillColor($style[RED_BACKGROUND_ARTICLE],$style[GREEN_BACKGROUND_ARTICLE],$style[BLUE_BACKGROUND_ARTICLE]);

	// CODE ARTICLE
	$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
	$pdf->SetFont('helvetica','B',FONT_SIZE_CODE - 1);
	$pdf->Cell(WIDTH_CODE,HEIGHT_ARTICLE,trim($row['code_article']).'.'.sprintf('%05s',round(isset($_GET['px_coop']) && $_GET['px_coop']==1 ? $row['px_coop_ht']:$row['px_adh_ht'])),"L$bordure",0,'L',1);
		
	// DESIGNATION
	$pdf->SetFont('helvetica','',FONT_SIZE_DESIGNATION);
	$font_redux = 0;
	while ($pdf->GetStringWidth($designation) > WIDTH_DESIGNATION && (FONT_SIZE_DESIGNATION - $font_redux) > 0) {
		$pdf->SetFont('helvetica','',FONT_SIZE_DESIGNATION - ++$font_redux);
	}

	$pdf->Cell(WIDTH_DESIGNATION,HEIGHT_ARTICLE,$designation,$bordure,0,'L',1);
	
	// on revient à la police precédente
	if ($font_redux) {
		$pdf->SetFont('helvetica','',FONT_SIZE_DESIGNATION); $font_redux = 0;
	}


	// REFERENCE
	$pdf->SetFont('helvetica','',FONT_SIZE_REF - 1);
	
	$lien_vers_ref = $pdf->AddLink();
	$pdf->Cell(WIDTH_REF,HEIGHT_ARTICLE,$row['ref_fournisseur'],$bordure,0,'L',1,    $pdf->SetLink($lien_vers_ref) );
	$REFERENCE[$row['ref_fournisseur']] = array($pdf->PageNo(), $lien_vers_ref);

	// PRIX
	$pdf->SetTextColor($style[RED_PRICE],$style[GREEN_PRICE],$style[BLUE_PRICE]);
	$eco_taxe = '';
	$pdf->SetFont('helvetica','B',FONT_SIZE_PRIX - 1);
	$tmp = str_replace('.00','',$row['px_eco_ttc']);
	$eco_taxe = $tmp > 0 ? "($tmp)" : '';
	$pdf->Cell(WIDTH_PRIX,HEIGHT_ARTICLE,$row['prix_net'].$eco_taxe,"R$bordure",0,'R',1);

	
	// GESTION DES IMAGES
	if ($row['tarif_image']) { // s'il y a une image de spécifié, on l'affiche
		$imgs = explode(',',$row['tarif_image']);
		//print_r($imgs); exit;
		if (sizeof($imgs) == 1) { // une seul image
			$pdf->Image(IMAGE_PATH.$imgs[0],PAGE_WIDTH - 60,$pdf->GetY(),IMAGE_WIDTH); // taille a 200 de l'image
			$img_info = getimagesize(IMAGE_PATH.$row['tarif_image']);
			$pdf->Ln($img_info[1] * IMAGE_WIDTH / $img_info[0]);
		} elseif (sizeof($imgs) > 1) { // plusieur image
			$last_max_height = 0;
			for ($i=0 , $j=0; $i<sizeof($imgs) ; $i++ , $j++) { // toutes les 5 images, on passe une ligne
				$pdf->Image(IMAGE_PATH.$imgs[$i],8 + IMAGE_WIDTH * $j,$pdf->GetY() + 7,IMAGE_WIDTH); // taille a 200 de l'image
				$img_info = getimagesize(IMAGE_PATH.$imgs[$i]);
				$last_max_height = max($last_max_height,$img_info[1] * IMAGE_WIDTH / $img_info[0]) ;

				if (intval(($j+1) / 5) > 0) { // on saute une ligne
					$pdf->Ln($last_max_height + 5);
					$j=-1;
					$last_max_height = 0 ;
				}
			}
			$pdf->Ln($last_max_height + 5);
		}
	}
	$pdf->Ln();

	// trace une ligne sous l'article
	$pdf->Line($pdf->GetX(),$pdf->GetY(),$pdf->GetX() + WIDTH_CODE + WIDTH_DESIGNATION + WIDTH_REF + WIDTH_PRIX,$pdf->GetY());

	// on affect la categ en cours de traitement
	$old_pdvente = $pdvente ;
	$r_old_pdvente = $r_pdvente;
	$old_style   = $style ; // pour pallier à l'application des footer
}

$titre_page = '';

// PAGE SUPPLEMENTAIRE
include('table_des_matieres_electromenager.php');

include('index_des_references.php');


// envoi du fichier au client
$pdf->Output();

?>