<?
include('../inc/config.php');
require_once('overload.php');
set_time_limit(0);

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$electromenager=0;

$PRINT_PAGE_NUMBER  = true;
$PRINT_EDITION_DATE = true;

define('FONT_SIZE_CODE',8);
define('FONT_SIZE_REF',7);
define('FONT_SIZE_PRIX',8);
define('FONT_SIZE_DESIGNATION',7);
define('WIDTH_CODE',20);
define('WIDTH_DESIGNATION',70);
define('WIDTH_REF',20);
define('WIDTH_PRIX',15);

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('IMAGE_WIDTH',40);

define('IMAGE_PATH','images/' );
define('PAGE_DE_GARDE_PATH',IMAGE_PATH.'page_de_garde/' );

$section_deja_dans_toc = array();
$TOC = array(); // pour la table des matieres
$REFERENCE = array(); // pour la table d'index des reference fabriquant
$CODE_MCS = array(); // pour la table d'index des reference mcs

define('FICHIER',0);
define('STYLE',1);
$PAGE_DE_GARDE = array(
	'00A'=>array('chauffage.png','#D17779,#B23F3F,#FFFFFF,#B23F3F,#FFFFFF,#000000,#B23F3F,#FFFFFF'),
	'00B'=>array('sanitaire.png','#A6AFC8,#5D6B97,#FFFFFF,#5D6B97,#FFFFFF,#000000,#5D6B97,#FFFFFF'),
	'00C'=>array(),
	'00D'=>array('electricite.png','#C397BF,#693C65,#FFFFFF,#693C65,#FFFFFF,#000000,#693C65,#FFFFFF'),
	'OOE'=>array(),
	'OOF'=>array(),
	'OOG'=>array('plomberie.png','#9DC6B8,#798778,#FFFFFF,#798778,#FFFFFF,#000000,#798778,#FFFFFF'),
	'OOH'=>array(),
	'OOI'=>array(),
	'OOJ'=>array(),
	'00K'=>array('outils.png','#9B6E59,#523C31,#FFFFFF,#523C31,#FFFFFF,#000000,#523C31,#FFFFFF'),
	'00L'=>array(),'OOM'=>array(),'OON'=>array(),'OOP'=>array(),'OOQ'=>array(),'OOR'=>array(),
	'00S'=>array()
);
// remplissage des valeurs non renseignées

foreach ($PAGE_DE_GARDE as $act=>$tab)
	if (sizeof($PAGE_DE_GARDE[$act]) <= 0)
		$PAGE_DE_GARDE[$act] = array('a_definir.png','#9B6E59,#523C31,#FFFFFF,#523C31,#FFFFFF,#000000,#523C31,#FFFFFF');
//print_r($PAGE_DE_GARDE);exit;

// creation de l'arbre des categorie
$sql = <<<EOT
SELECT	chemin,libelle
FROM	pdvente
ORDER BY chemin ASC
EOT;
$res = mysql_query($sql) or die("ne peux pas recupérer le plan de vente ".mysql_error());
$PLAN_DE_VENTE = array();
while($row = mysql_fetch_array($res))
	$PLAN_DE_VENTE[$row['chemin']] 	= $row['libelle'];
//print_r($PLAN_DE_VENTE);exit;

// Chargement des nom d'image en mémoire
$IMAGE = rscandir(IMAGE_PATH);
//print_r($IMAGE);exit;



$pdv = '' ;
if	(isset($_GET['pdv']) && $_GET['pdv'])
	$pdv = $_GET['pdv'] ;
if	(isset($_POST['pdv']) && $_POST['pdv'])
	$pdv = $_POST['pdv'] ;


$condition	= array();
$condition[]= "ETARE=''"; // non suspendu
$condition[]= "DIAA1='OUI'"; // la case édité sur tarif est cochée

//$condition[]= "ARTICLE.NOART='01001298'"; // pour les test sur les kits


if ($pdv)
	$condition[] = "CONCAT(ACTIV,CONCAT('.',CONCAT(FAMI1,CONCAT('.',CONCAT(SFAM1,CONCAT('.',CONCAT(ART04,CONCAT('.',ART05)))))))) like '$pdv%'";

$condition = join(' and ',$condition);

// recherche des article a exporté pour le tarif
	$sql = <<<EOT
select	
		ARTICLE.NOART,DESI1,ACTIV,FAMI1,SFAM1,ART04,ART05,
		CONCAT(ACTIV,CONCAT('.',CONCAT(FAMI1,CONCAT('.',CONCAT(SFAM1,CONCAT('.',CONCAT(ART04,CONCAT('.',ART05)))))))) as CHEMIN,
		REFFO,PVEN1,
		CDKIT
from	
		${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			left outer join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
				on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIFP1 TARIF
				on ARTICLE.NOART=TARIF.NOART
where $condition
order by
	ACTIV ASC,FAMI1 ASC,SFAM1 ASC,ART04 ASC,ART05 ASC,DESI1 ASC,DESI2 ASC,DESI3 ASC
EOT;

//echo $sql ; exit;

$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");


// creation de l'objet
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetWidths(array(WIDTH_CODE,WIDTH_DESIGNATION,WIDTH_REF,WIDTH_PRIX)); // a sortir de la boucle quand tout marchera bien

// on passe sur chaque article
$last_img_bottom = 0;
$old_pdvente = '';
$old_activite= '';
$lien = 1 ;
while($row = odbc_fetch_array($res)) {
	$row['CHEMIN']	= ereg_replace('[ \.]*$','',$row['CHEMIN']);
	$row['NOART']	= trim($row['NOART']);
	$row['DESI1']	= trim($row['DESI1']);
	$$row['REFFO']	= trim($row['REFFO']);

	$style = html2rgb($PAGE_DE_GARDE[$row['ACTIV']][STYLE]);

	$pdvente = array(
		isset($PLAN_DE_VENTE[$row['ACTIV']]) ? $PLAN_DE_VENTE[$row['ACTIV']] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1]"]) ?
			$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1]"]) ?
			$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04]"]) ?
			$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04].$row[ART05]"]) ?
			$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04].$row[ART05]"] : '',
	);
	$pdvente_sans_activite = ereg_replace('[/ ]*$','',join(' / ',array_slice($pdvente,1,sizeof($pdvente)-1)));
	$pdvente = ereg_replace('[/ ]*$','',join(' / ',$pdvente));
	
	//print_r($row);echo $pdvente;exit;

	// s'il change de categorie par rapport au prececent, on créer le changement de categorie
	if ($old_pdvente != $pdvente) {
		// chamgement d'activité, on gere une page de garde + saut de page
		if ($old_activite != $row['ACTIV']) {
			$pdf->AddPage();
			$pdf->Image(PAGE_DE_GARDE_PATH.'/'.$PAGE_DE_GARDE[$row['ACTIV']][FICHIER],0,0,PAGE_WIDTH); // taille a 200 de l'image
			$pdf->AddPage();
		}

		if($pdf->GetY() > PAGE_HEIGHT - 53) { // check le saut de page
			$pdf->AddPage();
			$last_img_bottom = 0;
		}
		else
			if ($old_activite) // pour evité de faire un décalage trop grand la premiere fois
				$pdf->Ln(8); // pas de saut de page mais juste un décalage de 1cm


		// permet de gérer les eventuel saut de page si l'image dépasse
		if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on l'affiche
			$img_info = getimagesize($IMAGE[$row['NOART']][0]);
			$img_height = $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

			/*	if (0 && $imgs[0] == 'fagor frigo top.jpg') {
					//print_r($img_info);
					echo "DEBUG img_height='$img_height'<br>\n";
					echo "BAS DE PAGE sans image '".($pdf->GetY())."'<br>\n";
					echo "BAS DE PAGE avec image '".($pdf->GetY() + $img_height)."'<br>\n";
					echo "DEBUG last_img_bottom='$last_img_bottom'<br>\n";
					echo "HAUTEUR DE PAGE '".PAGE_HEIGHT."'<br>\n";
				}
			*/

				//if ($pdf->GetY() + $img_height > PAGE_HEIGHT) {
				if ($last_img_bottom + $img_height + 5 > PAGE_HEIGHT) {
					$pdf->AddPage();
					$last_img_bottom = 0;
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
		$pdf->Cell(0,9,$pdvente_sans_activite ,0,1,'',0,  $pdf->SetLink($lien_vers_page)  );
		$pdf->Ln(2);

		// rectangle arrondi autour du mini titre + ligne fillante
		$pdf->RoundedRect($pdf->GetX()-1, $pdf->GetY()-10.5, intval($pdf->GetStringWidth($pdvente_sans_activite)) + 7 , 8, 3.5);
		$pdf->Line(intval($pdf->GetStringWidth($pdvente_sans_activite)) + 16.5, $pdf->GetY()-6.5 , PAGE_WIDTH - 15, $pdf->GetY()-6.5);


		// mise a jour de la table des matière
		$tmp = explode(' / ',$pdvente);
		for($i=0 ; $i<sizeof($tmp) ; $i++) {
			$niveau_en_cours = join('/',array_slice($tmp,0,$i+1));
			if (!isset($section_deja_dans_toc[$niveau_en_cours])) { // si section pas deja traité
				array_push($TOC,array(
									$tmp[$i],		// ID
									$pdf->PageNo(),	// No DE PAGE
									$lien_vers_page,// LIEN
									$i				// décalage
								)
				);	
				$section_deja_dans_toc[$niveau_en_cours] = 1;
			}
		}
		//print_r($TOC);print_r($section_deja_dans_toc);exit;


		// entete du tableau
		//echo "'".$row['CHEMIN']."'\n";
		if (isset($IMAGE[$row['CHEMIN']])) { // s'il y a une image de spécifié, on l'affiche
			//echo "Je vais afficher des images ".$pdf->GetY()."<br>\n";
			$last_img_bottom = 0 ;
			if (sizeof($IMAGE[$row['CHEMIN']]) == 1) { // une seul image
				$pdf->Image($IMAGE[$row['CHEMIN']][0],PAGE_WIDTH - 60,$pdf->GetY(),IMAGE_WIDTH); // taille a 200 de l'image
				$img_info = getimagesize($IMAGE[$row['CHEMIN']][0]);
				$last_img_bottom += $img_info[1] * IMAGE_WIDTH / $img_info[0] ;
			} elseif (sizeof($IMAGE[$row['CHEMIN']]) > 1) { // plusieur image
				for ($i=0; $i<sizeof($IMAGE[$row['CHEMIN']]) ; $i++) {
					$pdf->Image($IMAGE[$row['CHEMIN']][$i],PAGE_WIDTH - 60,$pdf->GetY() + IMAGE_WIDTH * $i,IMAGE_WIDTH); // taille a 200 de l'image
					$img_info = getimagesize($IMAGE[$row['CHEMIN']][0]);
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
		$pdf->Cell(WIDTH_CODE		,6,'CODE','LT',0,'L',1);
		$pdf->Cell(WIDTH_DESIGNATION,6,'DÉSIGNATION','T',0,'L',1);
		$pdf->Cell(WIDTH_REF		,6,'RÉF.','T',0,'L',1);
		$pdf->Cell(WIDTH_PRIX		,6,($electromenager ? 'PUBLIC '.EURO :'PRIX '.EURO.' HT'),'TR',0,'L',1);
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
			$pdf->Cell(0,9,"$pdvente_sans_activite (suite)" ,0,1,'',0);
			$pdf->Ln(2);

			// rectangle arrondi autour du mini titre + ligne fillante
			$string_width = intval($pdf->GetStringWidth("$pdvente_sans_activite (suite)")) ;
			$pdf->RoundedRect($pdf->GetX()-1, $pdf->GetY()-10.5, $string_width + 7 , 8, 3.5);
			$pdf->Line($string_width + 16.5, $pdf->GetY()-6.5 , PAGE_WIDTH - 15, $pdf->GetY()-6.5);

			// on dessine l'entete avec les colonnes
			$pdf->SetLineWidth(0.1);
			$pdf->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
			$pdf->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
			$pdf->SetFont('helvetica','B',7);
			$pdf->Cell(WIDTH_CODE		,6,'CODE','LT',0,'L',1);
			$pdf->Cell(WIDTH_DESIGNATION,6,'DÉSIGNATION','T',0,'L',1);
			$pdf->Cell(WIDTH_REF		,6,'RÉF.','T',0,'L',1);
			$pdf->Cell(WIDTH_PRIX		,6,($electromenager ? 'PUBLIC '.EURO :'PRIX '.EURO.' HT'),'TR',0,'L',1);
			$pdf->Ln();
		}
	}
	
	// ecriture de l'article
	$bordure = isset($IMAGE[$row['NOART']]) ? 'B':''; // s'il y a une image de spécifié, on l'affiche

	// couleur de fond pour les articles
	$pdf->SetFillColor($style[RED_BACKGROUND_ARTICLE],$style[GREEN_BACKGROUND_ARTICLE],$style[BLUE_BACKGROUND_ARTICLE]);
	$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
		
	// CODE ARTICLE
	if ($electromenager) {
		$pdf->SetFont('helvetica','B',FONT_SIZE_CODE - 1);
		$pdf->Cell(WIDTH_CODE,6,trim($row['code_article']).'.'.sprintf('%05s',round(isset($_GET['px_coop']) && $_GET['px_coop']==1 ? $row['px_coop_ht']:$row['px_adh_ht'])),"L$bordure",0,'L',1);
	}
	
	// REFERENCE
	if ($electromenager) {
		$pdf->SetFont('helvetica','',FONT_SIZE_REF - 1);
	}
	$lien_vers_ref = $pdf->AddLink();
	$REFERENCE[$row['REFFO'] ? $row['REFFO'] : $row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$row['PVEN1']), $lien_vers_ref);
	$CODE_MCS[$row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$row['PVEN1']), $lien_vers_ref);

	// PRIX
	$eco_taxe = '';
	if ($electromenager) {
		$pdf->SetFont('helvetica','B',FONT_SIZE_PRIX - 1);
		$tmp = str_replace('.00','',$row['px_eco_ttc']);
		$eco_taxe = $tmp > 0 ? "($tmp)" : '';
	}


	
	$noart			= $row['NOART']; // deja trimé avant
	$designation	= $row['DESI1'];
	$ref			= $row['REFFO'];
	$prix			= sprintf('%01.2f',$row['PVEN1']);
	$kit			= 0 ;

	if ($row['CDKIT'] == 'OUI') { // il s'agit d'un article en kit. On doit afficher les composants avec les prix
		// on va chercher le détail des article composants
$sql = <<<EOT
select		DETAIL_KIT.NOART,NUCOM,REFFO,DESI1
from		${LOGINOR_PREFIX_BASE}GESTCOM.AKITDEP1 DETAIL_KIT
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
					on DETAIL_KIT.NOART=ARTICLE_FOURNISSEUR.NOART
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
					on DETAIL_KIT.NOART=ARTICLE.NOART
where		NOKIT='$row[NOART]'
EOT;

		$noart			.= "\n";
		$designation	.= "\nKit composé de :";
		$ref			.= "\n";
		$prix			.= "\n";
		
		$res_kit = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete kit : $sql");
		while($row_kit = odbc_fetch_array($res_kit)) { // on parcours les articles du kit et on les enregistre pour plus tard
			$noart			.= "\n".trim($row_kit['NOART']);
			$designation	.= "\n".trim($row_kit['DESI1']).' x'.sprintf('%d',$row_kit['NUCOM']);
			$ref			.= "\n".trim($row_kit['REFFO']);
			$kit++;
		}
	}

	// on imprime la ligne
	// code_article,designation,ref,prix
	$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $noart			, 'font-style' => 'B'	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_CODE ),
							array('text' => $designation	, 'font-style' => ''	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_DESIGNATION),
							array('text' => $ref			,													'font-size' => FONT_SIZE_REF),
							array('text' => $prix			, 'font-color' => array($style[RED_PRICE],$style[GREEN_PRICE],$style[BLUE_PRICE]), 'text-align' => 'R')
				),
			$kit ? $kit+2 : 1 // nombre de ligne
	);

	$font_redux = 0; // on réinitilise la taille de la police pour la désignation




	// GESTION DES IMAGES
	if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on l'affiche
		if (sizeof($IMAGE[$row['NOART']]) == 1) { // une seul image
			$pdf->Image($IMAGE[$row['NOART']][0],PAGE_WIDTH - 60,$pdf->GetY(),IMAGE_WIDTH); // taille a 200 de l'image
			$img_info = getimagesize($IMAGE[$row['NOART']][0]);
			$pdf->Ln($img_info[1] * IMAGE_WIDTH / $img_info[0]);
		} elseif (sizeof($IMAGE[$row['NOART']]) > 1) { // plusieur image
			$last_max_height = 0;
			for ($i=0 , $j=0; $i<sizeof($IMAGE[$row['NOART']]) ; $i++ , $j++) { // toutes les 5 images, on passe une ligne
				$pdf->Image($IMAGE[$row['NOART']][$i],8 + IMAGE_WIDTH * $j,$pdf->GetY() + 7,IMAGE_WIDTH); // taille a 200 de l'image
				$img_info = getimagesize($IMAGE[$row['NOART']][$i]);
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


	// on affect la categ en cours de traitement
	$old_pdvente = $pdvente ;
	$old_activite = $row['ACTIV'];
	$old_style = $style;
	$kit = 0; // supression des kit en mémoire
}

$titre_page = '';



// PAGE SUPPLEMENTAIRE
if (isset($_POST['index_ref']) && $_POST['index_ref']) // index des références fabriquant
	include('index_des_references_fabriquant.php');

if (isset($_POST['index_code']) && $_POST['index_code']) // index des code interne de la société
	include('index_des_codes.php');

if (isset($_POST['sommaire']) && $_POST['sommaire']) // le sommaire
	if ($electromenager)
		include('table_des_matieres_electromenager.php');
	else
		include('table_des_matieres.php');


// EQUIPE + ORGANIGRAMME
if (isset($_POST['equipe']) && $_POST['equipe'])  // on rajoute les page contactes et organigramme à la fin du tarif (pour une édition complette par exemple)
	include('equipe.php');


// envoi du fichier au client
$pdf->Output();




function rscandir($base='', &$data=array()) {
  $array = array_diff(scandir($base), array('.', '..')); # remove ' and .. from the array */
  foreach($array as $value) { /* loop through the array at the level of the supplied $base */
 
    if (is_dir($base.$value)) { /* if this is a directory */
    //  $data[] = $base.$value.'/'; /* add it to the $data array */
      $data = rscandir($base.$value.'/', $data); /* then make a recursive call with the
      current $value as the $base supplying the $data array to carry into the recursion */
    }  elseif (is_file($base.$value) &&
				(preg_match("/^(\d+)(?:-\d+)?\.(?:jpe?g|png)$/",$value,$regs) ||
				 preg_match("/^((?:[\d\w]{3}\.?)+)(?:-\d+)?\.(?:jpe?g|png)$/",$value,$regs) )
			) { /* else if the current $value is a file */
      $data[$regs[1]][] = $base.$value; /* just add the current $value to the $data array */
    }
  }
  return $data; // return the $data array
}
?>