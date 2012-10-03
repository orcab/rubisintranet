<?php
require_once('../../inc/fpdf/fpdf.php');
require_once('php-barcode-2.0.1.php');
require_once('pdf.php');

$page_size = array(	'A4' => array('L' => array(842,595),	'P' => array(595,842)),
					'A3' => array('L' => array(1191,842),	'P' => array(842,1191))
				);

$themes	=					array(	'00'=>array('background'=>'000055', 'text'=>'FFFFFF'),				// bleu foncé
									'10'=>array('background'=>'000000', 'text'=>'FFFFFF'),				// noir
									'20'=>array('background'=>'F0ED00', 'text'=>'000000'),				// jaune
									'30'=>array('background'=>'95D5FF', 'text'=>'000000'),				// bleu
									'40'=>array('background'=>'FF341F', 'text'=>'FFFFFF'),				// rouge
									'50'=>array('background'=>'C4FF1F', 'text'=>'000000'),				// vert clair
									'60'=>array('background'=>'00A60A', 'text'=>'FFFFFF'),				// vert
									'default'=>array('background'=>'95D5FF', 'text'=>'000000')			// bleu par defaut
							);

$textes = preg_split("/[\n\r]+/",$_POST['emplacements']);

define('CODE39','code39');

if ($_POST['format_etiquette'] == 'L6009') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(45.4),'y'=>mm2pt(21.07)) ; // en mm
	$bar_height = mm2pt(8);
	$bar_width  = 1 ;
	$angle = 15 ;
	$font_size = 11;
	$page_origine = array('x'=>mm2pt(14),'y'=>mm2pt(25.4));



} elseif ($_POST['format_etiquette'] == 'L7993') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(99.1),'y'=>mm2pt(67.7)) ; // en mm
	$bar_height = mm2pt(30);
	$bar_width  = 1.5 ;
	$angle = 32 ;
	$font_size = 35;
	$page_origine = array('x'=>mm2pt(6),'y'=>mm2pt(13));
}


$pdf=new PDF($_POST['orientation_page'],'pt',$page_size[$_POST['format_page']]['P']);
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(0,0,0); // marge gauche et haute (droite = gauche)
$pdf->AddPage();

$page_size_x = $page_size[$_POST['format_page']][$_POST['orientation_page']][0];
$page_size_y = $page_size[$_POST['format_page']][$_POST['orientation_page']][1];


if ($_POST['format_etiquette'] == 'L6009') {
	$max_etiquette_on_ligne = 4;
	$max_ligne_on_page = 12 ;
	$max_etiquette_on_page = $max_etiquette_on_ligne*$max_ligne_on_page ;


} elseif ($_POST['format_etiquette'] == 'L7993') {
	$max_etiquette_on_ligne = 2;
	$max_ligne_on_page = 4 ;
	$max_etiquette_on_page = $max_etiquette_on_ligne*$max_ligne_on_page ;
}


$etiquette_position = 0;
$page = 1;

for($i=0 ; $i<sizeof($textes) ; $i++) {

	if ($etiquette_position + 1 > $max_etiquette_on_page) {
		$pdf->AddPage();
		$page++;
		$etiquette_position = 0;
	}

	$colonne	= $etiquette_position % $max_etiquette_on_ligne;
	$ligne		= intval($etiquette_position / $max_etiquette_on_ligne);

	$texte_split = explode(' ',$textes[$i]);
	$origine = array(	'x'=>$page_origine['x'] + $format_etiquette['x'] * $colonne + $marge_x * $colonne,
						'y'=>$page_origine['y'] + $format_etiquette['y'] * $ligne	+ $marge_y * $ligne,
					);

	$theme = $texte_split[3];
	//echo $theme." ";

	if (!array_key_exists($theme,$themes))
		$theme = 'default';

	$cle_pose = rand(100,999);

	$pdf->SetFillColor(255,255,255);
	$color=htlmColor2fpdfColor($themes[$theme]['background']);
	$pdf->SetFillColor($color['red'],$color['green'],$color['blue']);

	if ($_POST['format_etiquette'] == 'L6009') {
	
		Barcode::fpdf($pdf,'000000',
						$origine['x'] + $format_etiquette['x'] / 2,
						$origine['y'] + $bar_height / 2 + mm2pt(2.5),
						$angle,  CODE39, $cle_pose, $bar_width, $bar_height);

		// rectangle coloré
		$pdf->RoundedRect(	$origine['x'] + mm2pt(1) ,
							$origine['y'] + $bar_height + mm2pt(4),
							$format_etiquette['x'] - mm2pt(3),
							$font_size  + mm2pt(2) ,
							4,'F');
		$pdf->SetFillColor(255,255,255);

		$color=htlmColor2fpdfColor($themes[$theme]['text']);
		$pdf->SetTextColor($color['red'],$color['green'],$color['blue']);

		// texte
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text(	$origine['x'] + mm2pt(2),
					$origine['y'] + $font_size + $bar_height + mm2pt(4.5),
					join(' ',$texte_split). "   [$cle_pose]");
	

	} elseif  ($_POST['format_etiquette'] == 'L7993') {
		$pdf->RoundedRect(	$origine['x'] + mm2pt(1) ,
							$origine['y'],
							$format_etiquette['x'] - mm2pt(4),
							$format_etiquette['y'] - mm2pt(4) ,
							4,'F');
		$pdf->SetFillColor(255,255,255);

		$pdf->Polygon(array( // losange
							$origine['x'] + mm2pt( 70.25)			,	$origine['y'],						//p1
							$origine['x'] + $format_etiquette['x']	,	$origine['y'] ,						//p2
							$origine['x'] + $format_etiquette['x']	,	$origine['y'] + mm2pt(18),			//p3
							$origine['x'] + mm2pt(19.25)			,	$origine['y'] +  $format_etiquette['y'],	//p4
							$origine['x']							,	$origine['y'] +  $format_etiquette['y'],	//p5
							$origine['x']							,	$origine['y'] + mm2pt(43.75),			//p6
						),'F');

		Barcode::fpdf($pdf,'000000', $origine['x'] + $format_etiquette['x'] / 2,  $origine['y'] + $format_etiquette['y'] / 2 - mm2pt(3), $angle,  CODE39, $cle_pose, $bar_width, $bar_height);

		$color=htlmColor2fpdfColor($themes[$theme]['text']);
		$pdf->SetTextColor($color['red'],$color['green'],$color['blue']);

		// texte	
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size,				$texte_split[0].' '.$texte_split[1]);	# allée + face
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size * 2,			$texte_split[2]);						# colonne
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size ,	$texte_split[3].' '.$texte_split[4]);	# hauteur + emplacement
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size*2 ,	"[$cle_pose]");	# clé


	}

	// on fait courrir les compteur
	$etiquette_position++;
}


$pdf->Output();



////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
function mm2pt($mm) {
	return 2.85714285 * $mm ;
}

function htlmColor2fpdfColor($htmlColor) {
	preg_match('/^ *([a-f0-9]{2})([a-f0-9]{2})([a-f0-9]{2}) *$/i',$htmlColor,$matches);
	return array(	'red'	=>	hexdec($matches[1]),
					'green'	=>	hexdec($matches[2]),
					'blue'	=>	hexdec($matches[3])
				);
}
?>