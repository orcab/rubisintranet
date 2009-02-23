<?
include('../inc/config.php');
require_once('overload.php');
set_time_limit(0);

define('DEBUG',TRUE);
if (DEBUG)
	$debug_file = fopen("debug.log", "w+") or die("Ne peux pas créer de fichier de debug"); 

$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$PRINT_PAGE_NUMBER=TRUE; // pas une constante car modifié dans 'equipe.php'
$PRINT_EDITION_DATE=TRUE; // pas une constante car modifié dans 'equipe.php'

define('FONT_SIZE_CATEG',12);
define('FONT_SIZE_CODE',8);
define('FONT_SIZE_REF',8);
define('FONT_SIZE_PRIX',8);
define('FONT_SIZE_ECOTAXE',8);
define('FONT_SIZE_DESIGNATION',7);
define('WIDTH_CODE',20);
define('WIDTH_DESIGNATION',70);
define('WIDTH_REF',20);
define('WIDTH_PRIX',15);
define('WIDTH_ECOTAXE',10);

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('IMAGE_WIDTH',40);

define('IMAGE_PATH','images/' );
define('PAGE_DE_GARDE_PATH',IMAGE_PATH.'page_de_garde/' );

$section_deja_dans_toc = array();
$TOC = array(); // pour la table des matieres
$REFERENCE = array(); // pour la table d'index des reference fabriquant
$CODE_MCS = array(); // pour la table d'index des reference mcs
$ECOTAXE = array(); // relation code->ecotaxe

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



/////////////////////////////// CHARGEMENT DU PLAN DE VENTE EN MÉMOIRE ////////////////////////
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



/////////////////////////////// CHARGEMENT DES ECOTAXES EN MÉMOIRE ////////////////////////
	$sql = <<<EOT
select CODPR,TANU0
from ${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1
where TYPPR='TPF'
EOT;
$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
while($row = odbc_fetch_array($res))
	$ECOTAXE[$row['CODPR']]=sprintf('%0.2f',$row['TANU0']);
//print_r($ECOTAXE);exit;



/////////////////////////////// CHARGEMENT DES NOM D'IMAGE EN MÉMOIRE ////////////////////////
$IMAGE = rscandir(IMAGE_PATH);
//print_r($IMAGE);exit;



////////////////////////////// CONSTRUCTION DE LA REQUETE SQL ///////////////////////////////
$condition	= array();
$condition[]= "ETARE=''"; // non suspendu
$condition[]= "DIAA1='OUI'"; // la case édité sur tarif est cochée
$condition[]= "ARDIV='NON'"; // ce n'est pas un article divers

$pdv = '' ;
if	(isset($_GET['pdv']) && $_GET['pdv'])
	$pdv = $_GET['pdv'] ;
if	(isset($_POST['pdv']) && $_POST['pdv'])
	$pdv = $_POST['pdv'] ;

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
		CDKIT,
		XPVE1 as PRIX_VENTE_VENIR,
		TPFAR as CODE_ECOTAXE
from	
		${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			left outer join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
				on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIFP1 TARIF
				on ARTICLE.NOART=TARIF.NOART
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIXP1 TARIF_VENIR
				on ARTICLE.NOART=TARIF_VENIR.NOART
where $condition
order by
	ACTIV ASC,FAMI1 ASC,SFAM1 ASC,ART04 ASC,ART05 ASC,DESI1 ASC,DESI2 ASC,DESI3 ASC
EOT;

//echo $sql ; exit;

$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");


// creation de l'objet PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetWidths(array(WIDTH_CODE,WIDTH_DESIGNATION,WIDTH_REF,WIDTH_PRIX,WIDTH_ECOTAXE)); // a sortir de la boucle quand tout marchera bien

// on passe sur chaque article
$coef_multiplicateur = 1;
$last_img_bottom = 0;
$old_pdvente = '';
$old_activite= '';
$lien = 1 ;
while($row = odbc_fetch_array($res)) {
	$prix_de_base	= $row['PRIX_VENTE_VENIR'] && isset($_POST['prix_a_venir']) && $_POST['prix_a_venir'] ? $row['PRIX_VENTE_VENIR'] : $row['PVEN1'];
	$row['CHEMIN']	= ereg_replace('[ \.]*$','',$row['CHEMIN']);
	$row['NOART']	= trim($row['NOART']);
	$row['DESI1']	= trim($row['DESI1']);
	$row['REFFO']	= trim($row['REFFO']);
	$coef_multiplicateur = defined('TARIF_COEF_'.$row['ACTIV']) ? constant('TARIF_COEF_'.$row['ACTIV']) : 1;

	//debug("\nDebut $row[NOART] GetY=".$pdf->GetY()."\n");

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
				$PRINT_EDITION_DATE = FALSE ;	$PRINT_PAGE_NUMBER  = FALSE;
				$pdf->Image(PAGE_DE_GARDE_PATH.'/'.$PAGE_DE_GARDE[$row['ACTIV']][FICHIER],0,0,PAGE_WIDTH); // taille a 200 de l'image
				$pdf->AddPage();
				$PRINT_EDITION_DATE = TRUE ;	$PRINT_PAGE_NUMBER  = TRUE;
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

		//debug("debug2 $row[CHEMIN] GetY=".$pdf->GetY()."\n"); // good

		// permet de gérer les eventuels saut de page si l'image dépasse
		if (isset($IMAGE[$row['NOART']])) { // s'il y a une image de spécifié, on vérifie que l'on a pas besoin d'un saut de page
			$img_info = getimagesize($IMAGE[$row['NOART']][0]);
			$img_height = $img_info[1] * IMAGE_WIDTH / $img_info[0] ;

			if ($last_img_bottom + $img_height + 5 > PAGE_HEIGHT) {
				$pdf->AddPage();
				//debug("  Saut de page forcé car l'image suivante dépassait ".($last_img_bottom + $img_height + 5)." > ".PAGE_HEIGHT."\n");
				$last_img_bottom = 0;
			}
		}

		//debug("debug3 $row[CHEMIN] GetY=".$pdf->GetY()." \$last_img_bottom=$last_img_bottom\n");
		// on vérifie que la nouvelle categ soit bien en dessous de la photo de l'ancienne categ
		if ($last_img_bottom) {
			if ($pdf->GetY() < $last_img_bottom)
				$pdf->SetY($last_img_bottom + 3);
			$last_img_bottom = 0;
		}
		//debug("debug4 $row[CHEMIN] GetY=".$pdf->GetY()."\n");

		// dessin du titre de la categorie
		debug("debug5 $row[CHEMIN] GetY=".$pdf->GetY()." PAGE_HEIGHT=".PAGE_HEIGHT."\n");
		if ($pdf->GetY() > PAGE_HEIGHT - 49) $pdf->AddPage(); // on vérifie que l'on a la place pour affiche un article

		$lien_vers_page = $pdf->AddLink();
		$pdf->PrintCategTitle($pdvente_sans_activite,$lien_vers_page) ;

		// mise a jour de la table des matière
		$pdf->AddCategToSummary($lien_vers_page);
		//print_r($TOC);print_r($section_deja_dans_toc);exit;

		// images associé à la categ
		$pdf->DrawImagesCateg();

		// on dessine l'entete avec les colonnes
		$pdf->PrintTableHeader();

	} else { // fin on a changer de categ
		// on n'a pas changer de categ mais on vérifie si l'on ne doit pas réimprimer le titre

		//debug("$row[NOART] : GetY=".$pdf->GetY()." > PAGE_HEIGHT - 27 (".(PAGE_HEIGHT - 27).") ?\n");
		if ($pdf->GetY() > PAGE_HEIGHT - 32) { // on vérifie que l'on a la place pour affiche un article
			//debug("  AddPage car l'article $row[NOART] suivant va sur la page suivante\n");
			$pdf->AddPage();
			$last_img_bottom = 0;

			// on réimprime le titre de la categ car nouvelle page
			$pdf->PrintCategTitle("$pdvente_sans_activite (suite)") ;

			// on dessine l'entete avec les colonnes
			$pdf->PrintTableHeader();
		}
	}
	
	// ecriture de l'article
	$bordure = isset($IMAGE[$row['NOART']]) ? 'B':''; // s'il y a une image de spécifié, on l'affiche

	// couleur de fond pour les articles
	$pdf->SetFillColor($style[RED_BACKGROUND_ARTICLE],$style[GREEN_BACKGROUND_ARTICLE],$style[BLUE_BACKGROUND_ARTICLE]);
	$pdf->SetTextColor($style[RED_ARTICLE],$style[GREEN_ARTICLE],$style[BLUE_ARTICLE]);
	
	// REFERENCE
	$lien_vers_ref = $pdf->AddLink();
	$REFERENCE[$row['REFFO'] ? $row['REFFO'] : $row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$prix_de_base), $lien_vers_ref,(isset($ECOTAXE[$row['CODE_ECOTAXE']]) ? $ECOTAXE[$row['CODE_ECOTAXE']]:0));
	$CODE_MCS[$row['NOART']] = array($pdf->PageNo(),sprintf('%01.2f',$prix_de_base), $lien_vers_ref,(isset($ECOTAXE[$row['CODE_ECOTAXE']]) ? $ECOTAXE[$row['CODE_ECOTAXE']]:0));
	
	$kit				= 0 ;
	$prix_cumul_kit		= 0 ;
	$ecotaxe_cumul_kit	= 0 ;
	$noart				= '' ;
	$designation		= '' ;
	$ref				= '' ;
	$prix				= '' ;
	$ecotaxe			= '' ;

	$font_size_max = FONT_SIZE_REF;
	if ($row['CDKIT'] == 'OUI') { // il s'agit d'un article en kit. On doit afficher les composants avec les prix
		// on va chercher le détail des articles composants
$sql = <<<EOT
select		DETAIL_KIT.NOART,NUCOM,REFFO,DESI1,PVEN1,SERST,XPVE1 as PRIX_VENTE_VENIR,ARTICLE.TPFAR as CODE_ECOTAXE
from		${LOGINOR_PREFIX_BASE}GESTCOM.AKITDEP1 DETAIL_KIT
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
					on DETAIL_KIT.NOART=ARTICLE.NOART
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
					on DETAIL_KIT.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIFP1 TARIF
					on ARTICLE.NOART=TARIF.NOART
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIXP1 TARIF_VENIR
					on ARTICLE.NOART=TARIF_VENIR.NOART
where		NOKIT='$row[NOART]'
EOT;
		$res_kit = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete kit : $sql");
		while($row_kit = odbc_fetch_array($res_kit)) { // on parcours les articles du kit et on les enregistre pour plus tard
			$prix_de_base	 = $row_kit['PRIX_VENTE_VENIR'] && isset($_POST['prix_a_venir']) && $_POST['prix_a_venir'] ? $row_kit['PRIX_VENTE_VENIR'] : $row_kit['PVEN1'];
			$noart			.= "\n   ".trim($row_kit['NOART']).( $row_kit['SERST']=='NON' ? ' *' :'' );
			$designation	.= "\n".trim($row_kit['DESI1']).' (x'.sprintf('%d',$row_kit['NUCOM']).')';

			// calcul de la taille max de la font de la référence
			$ref			.= "\n".trim($row_kit['REFFO']);
			$font_size_max = min($pdf->redux_font_size($row_kit['REFFO'],FONT_SIZE_REF,WIDTH_REF),$font_size_max); // on prend la plus petite des deux

			$prix			.= "\n".sprintf('%0.2f',$prix_de_base *	$coef_multiplicateur);
			$ecotaxe		.= "\n".(isset($ECOTAXE[$row_kit['CODE_ECOTAXE']]) ? $ECOTAXE[$row_kit['CODE_ECOTAXE']]:0);

			$prix_cumul_kit		+= sprintf('%0.2f',$prix_de_base * $coef_multiplicateur) ;
			$ecotaxe_cumul_kit	+= (isset($ECOTAXE[$row_kit['CODE_ECOTAXE']]) ? $ECOTAXE[$row_kit['CODE_ECOTAXE']]:0) ;
			$kit++;
		}
	}

	$noart			= $row['NOART'].( $row['SERST']=='NON' ? ' *' :'' ). ($kit ? "\n$noart" : '');
	$designation	= $row['DESI1'] . ($kit ? "\nKit composé de $kit éléments :$designation" : '');
	$ref			= $row['REFFO'] . ($kit ? "\n$ref" : '');
	$prix			= $kit ? "$prix_cumul_kit\n$prix" : sprintf('%0.2f',$prix_de_base * $coef_multiplicateur) ;
	$ecotaxe		= $kit ? "$ecotaxe_cumul_kit\n$ecotaxe" : (isset($ECOTAXE[$row['CODE_ECOTAXE']]) ? $ECOTAXE[$row['CODE_ECOTAXE']]:0);

	// redux de font pour la référence
	$font_size_max = min($pdf->redux_font_size($row['REFFO'],FONT_SIZE_REF,WIDTH_REF),$font_size_max); // on prend la plus petite des deux
	
	//debug("avant $row[NOART] GetY=".$pdf->GetY()."\n");
	// on imprime la ligne
	// code_article,designation,ref,prix
	$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $noart			, 'font-style' => 'B'	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_CODE ),
							array('text' => $designation	, 'font-style' => ''	, 'text-align' => 'L'	,	'font-size' => FONT_SIZE_DESIGNATION),
							array('text' => $ref			,													'font-size' => $font_size_max),
							array('text' => $prix			, 'font-color' => array($style[RED_PRICE],$style[GREEN_PRICE],$style[BLUE_PRICE]), 'text-align' => 'R', 'font-size' => FONT_SIZE_PRIX), // le prix est multiplié par une valeur de config.php en fonction de l'activité
							array('text' => $ecotaxe ? $ecotaxe:''		, 'font-color' => array($style[RED_PRICE],$style[GREEN_PRICE],$style[BLUE_PRICE]), 'text-align' => 'R', 'font-size' => FONT_SIZE_ECOTAXE)
				),
			$kit ? $kit+2 : 1 // nombre de ligne
	);

	//debug("après $row[NOART] GetY=".$pdf->GetY()."\n");

	// affiche la ou les images associées aux articles
	$pdf->DrawImagesArticle();

	//debug("Fin $row[NOART] GetY=".$pdf->GetY()."\n");

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
				(preg_match("/^(\d+)(?:-\d+)?\.(?:jpe?g|png)$/i",$value,$regs) ||
				 preg_match("/^((?:[\d\w]{3}\.?)+)(?:-\d+)?\.(?:jpe?g|png)$/i",$value,$regs) )
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