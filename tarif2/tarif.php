<?
include('../inc/config.php');
require_once('overload.php');
set_time_limit(0);

define('DEBUG',FALSE);
if (DEBUG)
	$debug_file = fopen("debug.log", "w+") or die("Ne peux pas créer de fichier de debug"); 

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$PRINT_PAGE_NUMBER=TRUE; // pas une constante car modifié dans 'equipe.php'
$PRINT_EDITION_DATE=TRUE; // pas une constante car modifié dans 'equipe.php'

define('FONT_SIZE_CODE',8);
define('FONT_SIZE_REF',6);
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
	'00E'=>array(),
	'00F'=>array(),
	'00G'=>array('plomberie.png','#9DC6B8,#798778,#FFFFFF,#798778,#FFFFFF,#000000,#798778,#FFFFFF'),
	'00H'=>array(),
	'00I'=>array(),
	'00J'=>array(),
	'00K'=>array('outils.png','#9B6E59,#523C31,#FFFFFF,#523C31,#FFFFFF,#000000,#523C31,#FFFFFF'),
	'00L'=>array(),
	'00M'=>array('gaz.png','#DB8931,#814D17,#FFFFFF,#814D17,#FFFFFF,#000000,#814D17,#FFFFFF'),
	'00N'=>array(),'00P'=>array(),'00Q'=>array(),'00R'=>array(),
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
$condition[]= "ARDIV='NON'"; // ce n'est pas un article divers

//$condition[]= "ARTICLE.NOART='01001298'"; // pour les test sur les kits
//$pdv = "00B.B00.002" ; // pour les tests

if ($pdv)
	$condition[] = "CONCAT(ACTIV,CONCAT('.',CONCAT(FAMI1,CONCAT('.',CONCAT(SFAM1,CONCAT('.',CONCAT(ART04,CONCAT('.',ART05)))))))) like '$pdv%'";

$condition = join(' and ',$condition);

// recherche des articles à exporter pour le tarif
	$sql = <<<EOT
select	
		ARTICLE.NOART,DESI1,ACTIV,FAMI1,SFAM1,ART04,ART05,SERST,
		CONCAT(ACTIV,CONCAT('.',CONCAT(FAMI1,CONCAT('.',CONCAT(SFAM1,CONCAT('.',CONCAT(ART04,CONCAT('.',ART05)))))))) as CHEMIN,
		REFFO,PVEN1,
		CDKIT
from	
		${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			left outer join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
				on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
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
$coef_multiplicateur = 1;
$last_img_bottom = 0;
$old_pdvente = '';
$old_activite= '';
$lien = 1 ;
while($row = odbc_fetch_array($res)) {
	$row['CHEMIN']	= ereg_replace('[ \.]*$','',$row['CHEMIN']);
	$row['NOART']	= trim($row['NOART']);
	$row['DESI1']	= trim($row['DESI1']);
	$row['REFFO']	= trim($row['REFFO']);
	$coef_multiplicateur = defined('TARIF_COEF_'.$row['ACTIV']) ? constant('TARIF_COEF_'.$row['ACTIV']) : 1;

	debug("\nDebut $row[NOART] GetY=".$pdf->GetY()."\n");

	$style = html2rgb($PAGE_DE_GARDE[$row['ACTIV']][STYLE]);

	$pdvente = array(
		isset($PLAN_DE_VENTE[$row['ACTIV']]) ? $PLAN_DE_VENTE[$row['ACTIV']] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1]"]) ?	$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1]"]) ?	$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04]"]) ?	$PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04]"] : '',
		isset($PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04].$row[ART05]"]) ? $PLAN_DE_VENTE["$row[ACTIV].$row[FAMI1].$row[SFAM1].$row[ART04].$row[ART05]"] : '',
	);
	$pdvente_sans_activite = ereg_replace('[/ ]*$','',join(' / ',array_slice($pdvente,1,sizeof($pdvente)-1)));
	$pdvente = ereg_replace('[/ ]*$','',join(' / ',$pdvente));
	
	//print_r($row);echo $pdvente;exit;

	// s'il change de categorie par rapport au prececent, on créer le changement de categorie
	if ($old_pdvente != $pdvente) {
		// chamgement d'activité, on gere une page de garde + saut de page
		if ($old_activite != $row['ACTIV']) {
			$pdf->AddPage();

			if (isset($_POST['page_de_garde']) && $_POST['page_de_garde']) { // la page de garde
				$PRINT_EDITION_DATE = FALSE ;
				$PRINT_PAGE_NUMBER  = FALSE;
				$pdf->Image(PAGE_DE_GARDE_PATH.'/'.$PAGE_DE_GARDE[$row['ACTIV']][FICHIER],0,0,PAGE_WIDTH); // taille a 200 de l'image
				$pdf->AddPage();
				$PRINT_EDITION_DATE = TRUE ;
				$PRINT_PAGE_NUMBER  = TRUE;
			}
		}


		//debug("$row[CHEMIN] Vérifie saut de page : GetY=".$pdf->GetY()." max=".(PAGE_HEIGHT - 53)."\n");
		//debug("$row[CHEMIN] Vérifie saut de page : \$last_img_bottom=$last_img_bottom max=".(PAGE_HEIGHT - 53)."\n");
		//if(($pdf->GetY() > PAGE_HEIGHT - 53) || ($last_img_bottom > PAGE_HEIGHT - 53)) { // check le saut de page
		if($pdf->GetY() > PAGE_HEIGHT - 53) { // check le saut de page
			//debug(" besoin d'un saut\n");
			$pdf->AddPage();
			$last_img_bottom = 0;
		}
		else {
			if ($old_activite) // pour evité de faire un décalage trop grand la premiere fois
				$pdf->Ln(8); // pas de saut de page mais juste un décalage de 1cm
		}

		debug("debug2 $row[CHEMIN] GetY=".$pdf->GetY()."\n"); // good

		// permet de gérer les eventuel saut de page si l'image dépasse
		if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on vérifie que l'on a pas besoin d'un saut de page
			$img_info = getimagesize($IMAGE[$row['NOART']][0]);
			$img_height = $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

			if ($last_img_bottom + $img_height + 5 > PAGE_HEIGHT) {
				$pdf->AddPage();
				//debug("  Saut de page forcé car l'image suivante dépassait ".($last_img_bottom + $img_height + 5)." > ".PAGE_HEIGHT."\n");
				$last_img_bottom = 0;
			}
		}

		debug("debug3 $row[CHEMIN] GetY=".$pdf->GetY()." \$last_img_bottom=$last_img_bottom\n");

		// on vérifie que la nouvelle categ soit bien en dessous de la photo de l'ancienne categ
		if ($last_img_bottom) {
			if ($pdf->GetY() < $last_img_bottom)
				$pdf->SetY($last_img_bottom + 3);
			$last_img_bottom = 0;
		}

		debug("debug4 $row[CHEMIN] GetY=".$pdf->GetY()."\n");

		// dessin du titre de la categorie + le lien dans le sommaire
		$lien_vers_page = $pdf->AddLink();
		$pdf->PrintCategTitle($pdvente_sans_activite,$lien_vers_page) ;


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


		// images associé à la categ
		if (isset($IMAGE[$row['CHEMIN']])) {
			//debug("   Debut image de categ : GetY=".$pdf->GetY()."\n");
			$last_img_bottom = $pdf->GetY() ;
			for ($i=0; $i<sizeof($IMAGE[$row['CHEMIN']]) ; $i++) {
					$img_info		= getimagesize($IMAGE[$row['CHEMIN']][$i]);
					$hauteur_image	= $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

					if ($hauteur_image + $last_img_bottom > PAGE_HEIGHT - 28) { // si l'image dépasse en bas, on ignore les autres images
						continue;
					} else {
						$pdf->Image($IMAGE[$row['CHEMIN']][$i],PAGE_WIDTH - 60,$last_img_bottom + 2*$i,IMAGE_WIDTH); // taille a 200 de l'image
						$last_img_bottom += $hauteur_image ;
					}
			}
			//$last_img_bottom += $pdf->GetY();
			//debug("   Fin image de categ : GetY=".$pdf->GetY()."   \$last_img_bottom=$last_img_bottom\n");
		}

		// on dessine l'entete avec les colonnes
		$pdf->SetLineWidth(0.1);
		$pdf->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
		$pdf->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
		$pdf->SetFont('helvetica','B',7);
		$pdf->Cell(WIDTH_CODE		,6,'CODE','LT',0,'L',1);
		$pdf->Cell(WIDTH_DESIGNATION,6,'DÉSIGNATION','T',0,'L',1);
		$pdf->Cell(WIDTH_REF		,6,'RÉF.','T',0,'L',1);
		$pdf->Cell(WIDTH_PRIX		,6,'PRIX '.EURO.' HT','TR',0,'L',1);
		$pdf->Ln();

	} else { // fin on a changer de categ
		// on n'a pas changer de categ mais on vérifie si l'on ne doit pas réimprimer le titre

		//debug("$row[NOART] : GetY=".$pdf->GetY()." > PAGE_HEIGHT - 27 (".(PAGE_HEIGHT - 27).") ?\n");
		//if ($pdf->GetY() > PAGE_HEIGHT - 27) { // on est sur une nouvelle page
		if ($pdf->GetY() + 5 > PAGE_HEIGHT - 27) {
			//debug("  AddPage car l'article $row[NOART] suivant va sur la page suivante\n");
			$pdf->AddPage();

			$last_img_bottom = 0;

			// on réimprime le titre de la categ car nouvelle page (mais on ne l'ajoute pas au sommaire)
			$pdf->PrintCategTitle("$pdvente_sans_activite (suite)") ;

			// on dessine l'entete avec les colonnes
			$pdf->SetLineWidth(0.1);
			$pdf->SetFillColor($style[RED_PAGE],$style[GREEN_PAGE],$style[BLUE_PAGE]);
			$pdf->SetTextColor($style[RED_HEADER],$style[GREEN_HEADER],$style[BLUE_HEADER]);
			$pdf->SetFont('helvetica','B',7);
			$pdf->Cell(WIDTH_CODE		,6,'CODE','LT',0,'L',1);
			$pdf->Cell(WIDTH_DESIGNATION,6,'DÉSIGNATION','T',0,'L',1);
			$pdf->Cell(WIDTH_REF		,6,'RÉF.','T',0,'L',1);
			$pdf->Cell(WIDTH_PRIX		,6,'PRIX '.EURO.' HT','TR',0,'L',1);
			$pdf->Ln();
		}
	}
	
	// ecriture de l'article
	$bordure = isset($IMAGE[$row['NOART']]) ? 'B':''; // s'il y a une image de spécifié, on l'affiche

	// couleur de fond pour les articles
	$pdf->SetFillColor($style[RED_BACKGROUND_ARTICLE],$style[GREEN_BACKGROUND_ARTICLE],$style[BLUE_BACKGROUND_ARTICLE]);
	$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
	
	// REFERENCE
	$lien_vers_ref = $pdf->AddLink();
	$REFERENCE[$row['REFFO'] ? $row['REFFO'] : $row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$row['PVEN1']), $lien_vers_ref);
	$CODE_MCS[$row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$row['PVEN1']), $lien_vers_ref);
	
	$kit			= 0 ;
	$prix_cumul_kit = 0;
	$noart			= '' ;
	$designation	= '' ;
	$ref			= '' ;
	$prix			= '' ;

	if ($row['CDKIT'] == 'OUI') { // il s'agit d'un article en kit. On doit afficher les composants avec les prix
		// on va chercher le détail des articles composants
$sql = <<<EOT
select		DETAIL_KIT.NOART,NUCOM,REFFO,DESI1,PVEN1,SERST
from		${LOGINOR_PREFIX_BASE}GESTCOM.AKITDEP1 DETAIL_KIT
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
					on DETAIL_KIT.NOART=ARTICLE.NOART
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
					on DETAIL_KIT.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIFP1 TARIF
					on ARTICLE.NOART=TARIF.NOART
where		NOKIT='$row[NOART]'
EOT;
		
		$res_kit = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete kit : $sql");
		while($row_kit = odbc_fetch_array($res_kit)) { // on parcours les articles du kit et on les enregistre pour plus tard
			$noart			.= "\n   ".trim($row_kit['NOART']).( $row_kit['SERST']=='NON' ? ' *' :'' );
			$designation	.= "\n".trim($row_kit['DESI1']).' (x'.sprintf('%d',$row_kit['NUCOM']).')';
			$ref			.= "\n".trim($row_kit['REFFO']);
			$prix			.= "\n".sprintf('%0.2f',$row_kit['PVEN1'] *	$coef_multiplicateur);
			$prix_cumul_kit += sprintf('%0.2f',$row_kit['PVEN1'] * $coef_multiplicateur) ;
			$kit++;
		}
	}

	$noart			= $row['NOART'].( $row['SERST']=='NON' ? ' *' :'' ). ($kit ? "\n$noart" : '');
	$designation	= $row['DESI1'] . ($kit ? "\nKit composé de $kit éléments :$designation" : '');
	$ref			= $row['REFFO'] . ($kit ? "\n$ref" : '');
	$prix			= $kit ? "$prix_cumul_kit\n$prix" : sprintf('%0.2f',$row['PVEN1'] * $coef_multiplicateur) ;

	debug("avant $row[NOART] GetY=".$pdf->GetY()."\n");
	// on imprime la ligne
	// code_article,designation,ref,prix
	$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $noart			, 'font-style' => 'B'	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_CODE ),
							array('text' => $designation	, 'font-style' => ''	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_DESIGNATION),
							array('text' => $ref			,													'font-size' => FONT_SIZE_REF),
							array('text' => $prix			, 'font-color' => array($style[RED_PRICE],$style[GREEN_PRICE],$style[BLUE_PRICE]), 'text-align' => 'R', 'font-size' => FONT_SIZE_PRIX) // le prix est multiplié par une valeur de config.php en fonction de l'activité
				),
			$kit ? $kit+2 : 1 // nombre de ligne
	);

	debug("après $row[NOART] GetY=".$pdf->GetY()."\n");

	$font_redux = 0; // on réinitilise la taille de la police pour la désignation


	// GESTION DES IMAGES
	if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on l'affiche
		//debug("image(s) associé(s) à $row[NOART] GetY=".$pdf->GetY()."\n");
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
			$pdf->Image($IMAGE[$row['NOART']][$i],$ecart_x + (IMAGE_WIDTH+2) * $j ,$pdf->GetY() + 3,IMAGE_WIDTH); // taille a 200 de l'image
			
			if (intval(($j+1) / 5) > 0) { // on saute une ligne

				//debug("Image n°".($i+1)." $max_height + ".$pdf->GetY()." (".($max_height + $pdf->GetY()).")    PAGE_HEIGHT - 27=".(PAGE_HEIGHT - 27)."\n");

				if ($max_height + $pdf->GetY() + $max_height > PAGE_HEIGHT - 27) // on doit changer de page
					$pdf->AddPage();
				else // on peut rester sur la meme page, on saut une grosse ligne
					$pdf->Ln($max_height + 3);

				$j=-1;
				$max_height = 0 ;
			}
		}
		$pdf->Ln($max_height + 5);
	}

	debug("Fin $row[NOART] GetY=".$pdf->GetY()."\n");

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
	include('table_des_matieres.php');


// EQUIPE + ORGANIGRAMME
if (isset($_POST['equipe']) && $_POST['equipe'])  // on rajoute les page contactes et organigramme à la fin du tarif (pour une édition complette par exemple)
	include('equipe.php');


// envoi du fichier au client
$pdf->Output();


if (DEBUG) // fermeture du fichier de debug
	fclose($debug_file);


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


function debug($msg) {
	global $debug_file;
	if (DEBUG)
		fwrite($debug_file,$msg) or die("Ne peux pas écrire dans le fichier de debug");
}

?>