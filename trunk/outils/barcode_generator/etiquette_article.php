<?php
require_once('../../inc/config.php');
require_once('../../inc/fpdf/fpdf.php');
require_once('php-barcode-2.0.1.php');
require_once('pdf.php');

if (!is_dir('tmp')) mkdir('tmp');

$page_size = array(	'A4' => array('L' => array(842,595),	'P' => array(595,842)),
					'A3' => array('L' => array(1191,842),	'P' => array(842,1191))
				);

$themes	=	array(	'00A'=>array('background'=>'ffff00', 'text'=>'000000'),			// jaune
					'00B'=>array('background'=>'95D5FF', 'text'=>'000000'),			// bleu par defaut
					'00C'=>array('background'=>'0002b0', 'text'=>'FFFFFF'),			// bleu foncé
					'00D'=>array('background'=>'FF341F', 'text'=>'FFFFFF'),			// rouge
					'00E'=>array('background'=>'a1d400', 'text'=>'000000'),			// vert clair
					'00F'=>array('background'=>'ffa11b', 'text'=>'000000'),			// orange
					'00G'=>array('background'=>'000000', 'text'=>'FFFFFF'),			// noir
					'00H'=>array('background'=>'af3ee2', 'text'=>'FFFFFF'),			// violet
					'00J'=>array('background'=>'8b421b', 'text'=>'FFFFFF'),			// marron
					'00K'=>array('background'=>'006a06', 'text'=>'FFFFFF'),			// vert
					'00M'=>array('background'=>'b3b3b3', 'text'=>'000000'),			// gris
					'00N'=>array('background'=>'ff8080', 'text'=>'000000'),			// saumon
					'00P'=>array('background'=>'ff8080', 'text'=>'000000'),			// saumon
					'default'=>array('background'=>'b3e1ff', 'text'=>'000000')			// bleu
			);

/*if ($_POST['format_etiquette'] == 'L6009') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(45.4),'y'=>mm2pt(21.07)) ; // en mm
	$bar_height = mm2pt(8);
	$bar_width  = 1 ;
	$angle = 0 ;
	$font_size = 11;
	$page_origine = array('x'=>mm2pt(10.5),'y'=>mm2pt(21));
	$max_etiquette_on_ligne = 4;
	$max_ligne_on_page = 12 ;


} else
*/

if ($_POST['format_etiquette'] == 'L6011') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(63.5),'y'=>mm2pt(29.6)) ; // en mm
	$bar_height = mm2pt(8);
	$bar_width  = 1 ;
	$angle = 0 ;
	$font_size = 11;
	$page_origine = array('x'=>mm2pt(13.5),'y'=>mm2pt(21));
	$max_etiquette_on_ligne = 3;
	$max_ligne_on_page = 9 ;


}

/* elseif ($_POST['format_etiquette'] == 'L7993') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(99.1),'y'=>mm2pt(67.7)) ; // en mm
	$bar_height = mm2pt(30);
	$bar_width  = 1 ;
	$angle = 32 ;
	$font_size = 35;
	$page_origine = array('x'=>mm2pt(6),'y'=>mm2pt(13));
	$max_etiquette_on_ligne = 2;
	$max_ligne_on_page = 4 ;


} elseif ($_POST['format_etiquette'] == 'A4') {
	$_POST['orientation_page'] = 'L';
	$_POST['format_page'] = 'A4';
	$marge_x = 0;
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(210),'y'=>mm2pt(297)) ; // en mm
	$bar_height = mm2pt(130);
	$bar_width  = 2 ;
	$angle = 0 ;
	$font_size = 130;
	$page_origine = array('x'=>mm2pt(0),'y'=>mm2pt(0));
	$max_etiquette_on_ligne = 1;
	$max_ligne_on_page = 1;
}
*/

$max_etiquette_on_page = $max_etiquette_on_ligne*$max_ligne_on_page ;

$pdf=new PDF($_POST['orientation_page'],'pt',$page_size[$_POST['format_page']]['P']);
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(0,0,0); // marge gauche et haute (droite = gauche)
$pdf->AddPage();

$page_size_x = $page_size[$_POST['format_page']][$_POST['orientation_page']][0];
$page_size_y = $page_size[$_POST['format_page']][$_POST['orientation_page']][1];


$where = array();
$code_articles = preg_split("/[\n\r]+/",$_POST['articles']);
foreach ($code_articles as $tmp)
	$where[] = "ARTICLE.NOART='".trim($tmp)."'";

// debug
$where = array(	"ARTICLE.NOART='02001843'",
				"ARTICLE.NOART='01000100'",
				"ARTICLE.NOART='01000206'",
				"ARTICLE.NOART='01021720'",
				"ARTICLE.NOART='05000007'",
				"ARTICLE.NOART='06000055'",
				"ARTICLE.NOART='01010418'",
				"ARTICLE.NOART='08000299'",
				"ARTICLE.NOART='09000006'",
				"ARTICLE.NOART='10000006'",
				"ARTICLE.NOART='01003337'",
				"ARTICLE.NOART='12000006'",
				"ARTICLE.NOART='13000007'",
				"ARTICLE.NOART='02007624'",
				"ARTICLE.NOART='02024555'",
				"ARTICLE.NOART='02003642'",
				"ARTICLE.NOART='02016269'",
				"ARTICLE.NOART='02016601'",
				"ARTICLE.NOART='02016928'",
				"ARTICLE.NOART='01010757'",
				"ARTICLE.NOART='04004508'",
				"ARTICLE.NOART='04013005'",
				"ARTICLE.NOART='12000066'",
				"ARTICLE.NOART='12000224'",
				"ARTICLE.NOART='10000849'",
				"ARTICLE.NOART='11001434'",
				"ARTICLE.NOART='01010759'"
		);

$where = join(' OR ',$where);

// construction de la requete SQL
$sql = <<<EOT
select	ARTICLE.DESI1 				as DESIGNATION1,
		ARTICLE.NOART 				as CODE_ARTICLE,
		ARTICLE.GENCO 				as EAN13,
		ARTICLE.ACTIV 				as CODE_ACTIVITE,
		ARTICLE_FOURNISSEUR.REFFO 	as REFERENCE_FOURNISSEUR,
		FOURNISSEUR.NOFOU 			as CODE_FOURNISSEUR,
		FOURNISSEUR.NOMFO 			as NOM_FOURNISSEUR
--		,PLAN_DE_VENTE.AFCAC		as LIBELLE_ACTIVITE
from	
					${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		left join 	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART
				and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
				and ARTICLE_FOURNISSEUR.AGENC='$LOGINOR_AGENCE'
		left join 	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on		ARTICLE.FOUR1=FOURNISSEUR.NOFOU
--		left join 	${LOGINOR_PREFIX_BASE}GESTCOM.AFAMILP1 PLAN_DE_VENTE
--			on 		PLAN_DE_VENTE.AFCTY='FA1' and PLAN_DE_VENTE.AFC01='' and ARTICLE.ACTIV=PLAN_DE_VENTE.AFCAC
where
		$where
ORDER BY
		ARTICLE.FOUR1 ASC,
		ARTICLE_FOURNISSEUR.REFFO ASC
EOT;


// connexion à la base DL Negoce et récupération des articles
$articles 	= array();
$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res 		= odbc_exec($loginor,$sql) ;
while($row = odbc_fetch_array($res)) {
	$row =array_map('trim',$row);
	$row =array_map('strtoupper',$row);
	if ($row['CODE_FOURNISSEUR'] == 'LEGRA') $row['CODE_FOURNISSEUR'] == 'LEGRAN'; // patch pour article legrand sur Hydra
	$articles[] = $row;
}


 // si un jump d'étiquette est préciser, on démarre avec des étiquettes blanches
if (isset($_POST['jump']) && preg_match('/\d/',$_POST['jump']))
	$etiquette_position = intval($_POST['jump']);
else
	$etiquette_position = 0;


// pour chaque article
for($i=0 ; $i<sizeof($articles) ; $i++) {
	if ($articles[$i] == '') continue; // on saute les lignes vide

	// choix du theme de couleur
	$theme = $articles[$i]['CODE_ACTIVITE'];
	if (!array_key_exists($theme,$themes))
		$theme = 'default';

	// on cree une nouvelle page si l'on depasse le nombre d'étiquette par page
	if ($etiquette_position + 1 > $max_etiquette_on_page) {
		$pdf->AddPage();
		$etiquette_position = 0;
	}

	// on détermine l'emplacement de l'étiquette en cours
	$colonne	= $etiquette_position % $max_etiquette_on_ligne;
	$ligne		= intval($etiquette_position / $max_etiquette_on_ligne);

	// et son point d'origine
	$origine = array(	'x'=>$page_origine['x'] + $format_etiquette['x'] * $colonne + $marge_x * $colonne,
						'y'=>$page_origine['y'] + $format_etiquette['y'] * $ligne	+ $marge_y * $ligne,
					);

	// détermine la couleur de l'étiquette en fonction du theme
	$bgcolor=htlmColor2fpdfColor($themes[$theme]['background']);
	
	// détermine la couleur du texte en fonction du theme
	$txtcolor=htlmColor2fpdfColor($themes[$theme]['text']);
	$pdf->SetTextColor($txtcolor['red'],$txtcolor['green'],$txtcolor['blue']);

	//echo var_dump(htlmColor2fpdfColor($themes[$theme]['background']));


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////// FORMAT L6009 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*	if ($_POST['format_etiquette'] == 'L6009') {
		// génération d'une image
		$image_width = 160;
		$image_height= 40;
	    $im   = imagecreatetruecolor($image_width, $image_height);
		imagefilledrectangle($im, 0, 0, $image_width, $image_height ,ImageColorAllocate($im,0xff,0xff,0xff));   // fond blanc
	    $data = Barcode::gd($im, ImageColorAllocate($im,0x00,0x00,0x00), $image_width / 2, $image_height / 2, $angle, 'code93', $emplacement['code_barre'] , 1, $image_height);
		$filename = 'tmp/'.$emplacement['code_barre'].' (code93).png'; // génération d'une image sur le disque
		imagepng($im,$filename);

		$pdf->Image($filename,
					$origine['x']  - mm2pt(1.5),
					$origine['y'] + mm2pt(2.5),
					$format_etiquette['x'] + mm2pt(2)
		);

		// rectangle coloré
		$pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
		$pdf->RoundedRect(	$origine['x'] + mm2pt(1) ,
							$origine['y'] + $bar_height + mm2pt(4),
							$format_etiquette['x'] - mm2pt(3),
							$font_size  + mm2pt(2) ,
							4,'F');

		$pdf->SetFillColor(255,255,255);
		
		// texte
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text(	$origine['x'] + mm2pt(2),
					$origine['y'] + $font_size + $bar_height + mm2pt(4.5),"$emplacement[allee] $emplacement[face] $emplacement[colonne] $emplacement[niveau] $emplacement[emplacement]   [$emplacement[cle_pose]]");
	
	}
*/
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////// FORMAT L6011 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//	 else


if ($_POST['format_etiquette'] == 'L6011') {
		// vérifie que le répertoire d'accueil des images existe
		
		// génération d'une image code barre
		$code_barre_format  = 'code128';
		$image_width = 160;
		$image_height= 15;
	    $im   = imagecreatetruecolor($image_width, $image_height);
		imagefilledrectangle($im, 0, 0, $image_width, $image_height ,ImageColorAllocate($im,0xff,0xff,0xff));   // fond blanc
	    $data = Barcode::gd($im, ImageColorAllocate($im,0x00,0x00,0x00), $image_width / 2, $image_height / 2, $angle, $code_barre_format, $articles[$i]['CODE_ARTICLE'] , 1, $image_height);
		$filename = 'tmp/'.$articles[$i]['CODE_ARTICLE']." ($code_barre_format).png"; // génération d'une image sur le disque
		imagepng($im,$filename);
		$pdf->Image($filename,
					$origine['x'] - mm2pt(4.5),
					$origine['y'] - mm2pt(2),
					$format_etiquette['x'] + mm2pt(2)
		);

		// rectangle coloré
		$pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
		$pdf->RoundedRect(	$origine['x'] - mm2pt(2) ,
							$origine['y'] + $bar_height - mm2pt(3),
							$format_etiquette['x'] / 4 * 3 - mm2pt(10),
							mm2pt(21) ,
							4,'F');

		// couleur du texte
		$pdf->SetFillColor(255,255,255);
		$pdf->SetFont('Arial','B',$font_size);

		// activité + code article
		$pdf->Text(	$origine['x'],
					$origine['y'] + mm2pt(1) + $bar_height,$articles[$i]['CODE_ACTIVITE'].'     '.$articles[$i]['CODE_ARTICLE']);

		// designation
		$pdf->SetFont('Arial','',$font_size - 3);
		$designations = split("\n",wordwrap($articles[$i]['DESIGNATION1'],15)); // on coupe la designation sur deux lignes
		$pdf->Text(	$origine['x'],
					$origine['y'] + mm2pt(1) + $font_size + $bar_height, $designations[0]); // ligne 1
		$pdf->Text(	$origine['x'],
					$origine['y'] + mm2pt(1) + $font_size * 2 + $bar_height, $designations[1]); // ligne 2
		
		// fournisseur
		$pdf->Text(	$origine['x'],
					$origine['y'] + mm2pt(1) + $font_size * 3 + $bar_height,$articles[$i]['NOM_FOURNISSEUR']);

		// reference
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text(	$origine['x'],
					$origine['y'] + mm2pt(1) + $font_size * 4 + $bar_height,$articles[$i]['REFERENCE_FOURNISSEUR']);

		// image produit
		if ($filename = get_image($articles[$i]['CODE_FOURNISSEUR'],$articles[$i]['REFERENCE_FOURNISSEUR']))
			$pdf->Image($filename,	$origine['x'] + $format_etiquette['x'] / 2 + mm2pt(5),
									$origine['y'] + $bar_height - mm2pt(3),
									mm2pt(20),mm2pt(20)
			);

		// rectangle autour de l'étiquette
		$pdf->SetDrawColor(0,0,0);
		/*$pdf->Rect(	$origine['x'] - mm2pt(4.5),
					$origine['y'] - mm2pt(2),
					$format_etiquette['x'] + mm2pt(2),
					$format_etiquette['y'],
					'D');
		*/
	}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////// FORMAT L7993 //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
/*	elseif  ($_POST['format_etiquette'] == 'L7993') {
		$pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
		$pdf->RoundedRect(	$origine['x'] + mm2pt(1) ,
							$origine['y'],
							$format_etiquette['x'] - mm2pt(4),
							$format_etiquette['y'] - mm2pt(4) ,
							4,'F');
		$pdf->SetFillColor(255,255,255);

		$pdf->Polygon(array( // losange
							$origine['x'] + mm2pt( 71)				,	$origine['y'] - mm2pt(0.5),						//p1
							$origine['x'] + $format_etiquette['x']	,	$origine['y'] ,									//p2
							$origine['x'] + $format_etiquette['x']	,	$origine['y'] + mm2pt(18),						//p3
							$origine['x'] + mm2pt(19.25)			,	$origine['y'] +  $format_etiquette['y'],		//p4
							$origine['x']							,	$origine['y'] +  $format_etiquette['y'],		//p5
							$origine['x']							,	$origine['y'] + mm2pt(43.75),					//p6
						),'F');

		Barcode::fpdf($pdf,'000000', $origine['x'] + $format_etiquette['x'] / 2,  $origine['y'] + $format_etiquette['y'] / 2 - mm2pt(3), $angle,  'code39', $emplacement['code_barre'], $bar_width, $bar_height);

		// texte
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size,				"$emplacement[allee] $emplacement[face]");			# allée + face
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size * 2,			"$emplacement[colonne]");							# colonne
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size ,	"$emplacement[niveau] $emplacement[emplacement]");	# hauteur + emplacement
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size*2 ,	"[$emplacement[cle_pose]]");						# clé

		if (isset($_POST['arrow']) && $_POST['arrow'] && in_array($emplacement['niveau'],array('00','10','20','30','40','50','60','70','80','90','91','92'))) // on veut que les fleche soit affichées
			$pdf->Image("gfx/arrow_$emplacement[niveau]_".$themes[$theme]['text'].".png",
						$origine['x'] + mm2pt(55),
						$origine['y'] + mm2pt(48) );
	}


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////// FORMAT A4 /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
	elseif ($_POST['format_etiquette'] == 'A4') {

		// génération d'une image
		$image_width = 480;
		$image_height= 200;
	    $im   = imagecreatetruecolor($image_width, $image_height);
		imagefilledrectangle($im, 0, 0, $image_width, $image_height ,ImageColorAllocate($im,0xff,0xff,0xff));   // fond blanc
	    $data = Barcode::gd($im, ImageColorAllocate($im,0x00,0x00,0x00), $image_width / 2, $image_height / 2, $angle, 'code93', $emplacement['code_barre'] , 3, $image_height);
		$filename = 'tmp/'.$emplacement['code_barre'].' (code93).png'; // génération d'une image sur le disque
		imagepng($im,$filename);

		$pdf->Image($filename,
					$origine['x'] + mm2pt(40),
					$origine['y'] + mm2pt(60),
					$format_etiquette['x']
		);

		// rectangle coloré
		$pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
		$pdf->RoundedRect(	$origine['x'] + mm2pt(10) ,
							$origine['y'] + mm2pt(10),
							$format_etiquette['x'] + mm2pt(65),
							$font_size + mm2pt(10),
							30,'F');

		$pdf->RoundedRect(	$origine['x'] + mm2pt(10) ,
							$origine['y'] + $bar_height + mm2pt(15),
							$format_etiquette['x'] + mm2pt(65),
							$font_size + mm2pt(10),
							30,'F');

		$pdf->SetFillColor(255,255,255);
		
		// texte
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text(	$origine['x'] + mm2pt(30),
					$origine['y'] + $font_size + mm2pt(7),"$emplacement[allee] $emplacement[face] $emplacement[colonne]");
		$pdf->Text(	$origine['x'] + mm2pt(30),
					$origine['y'] + $font_size + $bar_height + mm2pt(10),"$emplacement[niveau] $emplacement[emplacement]   [$emplacement[cle_pose]]");
	}
*/

	// on fait courrir les compteur
	$etiquette_position++;
}


// on affiche le PDF
$pdf->Output();


// nettoyage du répertoire tmp
$file_in_tmp_dir = glob("tmp/* (code128).png");
foreach ($file_in_tmp_dir as $file)
	unlink($file);




///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function mm2pt($mm) {
	return 2.85714285 * $mm ;
}

function htlmColor2fpdfColor($htmlColor) {
	preg_match('/^ *#? *([a-f0-9]{2}) *([a-f0-9]{2}) *([a-f0-9]{2}) *$/i',$htmlColor,$matches);
	return array(	'red'	=>	hexdec($matches[1]),
					'green'	=>	hexdec($matches[2]),
					'blue'	=>	hexdec($matches[3])
				);
}


function wget($filename,$url) {
	file_put_contents($filename, file_get_contents($url));
}


function get_image($code_fournisseur,$reference_fournisseur) {

	// on va regarder dans le dossier temp si l'image est présente
	$file_in_tmp_dir = glob("tmp/$code_fournisseur#$reference_fournisseur.*");
	if (sizeof($file_in_tmp_dir) > 0) {
		return $file_in_tmp_dir[0];

	} else {
		// on va regarder sur le site de partage si l'image est présente
		$url_info  = sprintf("http://www.coopmcs.com/hydra/getfile.php?fournisseur=%s&ref=%s&info=1&json=1",
							$code_fournisseur,
							$reference_fournisseur
						);
		$infos = json_decode(join('',file($url_info)),true);

		//print_r($infos);exit;
		if (isset($infos['response']['C'])) { // si une image de type 'C'

			$url = sprintf("http://www.coopmcs.com/hydra/getfile.php?fournisseur=%s&ref=%s&largeur=%d&hauteur=%d",
						$code_fournisseur,
						$reference_fournisseur,
						300,300
					);

			$filename = sprintf("tmp/%s#%s.%s",
									$code_fournisseur,
									$reference_fournisseur,
									strtolower($infos['response']['C']['extension'])
						);
			wget($filename,$url);
			
			return $filename;
		} // if image exists
	}

	return '';
}

?>