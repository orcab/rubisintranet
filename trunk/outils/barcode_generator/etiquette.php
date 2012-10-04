<?php
require_once('../../inc/fpdf/fpdf.php');
require_once('php-barcode-2.0.1.php');
require_once('pdf.php');

$page_size = array(	'A4' => array('L' => array(842,595),	'P' => array(595,842)),
					'A3' => array('L' => array(1191,842),	'P' => array(842,1191))
				);

$themes	=					array(	'00'=>array('background'=>'ffff00', 'text'=>'000000'),				// jaune
									'10'=>array('background'=>'000000', 'text'=>'FFFFFF'),				// noir
									'20'=>array('background'=>'FF341F', 'text'=>'FFFFFF'),				// rouge
									'30'=>array('background'=>'0002b0', 'text'=>'FFFFFF'),				// bleu foncé
									'40'=>array('background'=>'a1d400', 'text'=>'000000'),				// vert clair
									'50'=>array('background'=>'ffa11b', 'text'=>'000000'),				// orange
									'60'=>array('background'=>'b3e1ff', 'text'=>'000000'),				// bleu
									'70'=>array('background'=>'af3ee2', 'text'=>'FFFFFF'),				// violet
									'80'=>array('background'=>'8b421b', 'text'=>'FFFFFF'),				// marron
									'90'=>array('background'=>'006a06', 'text'=>'FFFFFF'),				// vert
									'default'=>array('background'=>'95D5FF', 'text'=>'000000')			// bleu par defaut
							);

$textes = preg_split("/[\n\r]+/",$_POST['emplacements']);


if ($_POST['format_etiquette'] == 'L6009') {
	$_POST['orientation_page'] = 'P';
	$_POST['format_page'] = 'A4';
	$marge_x = mm2pt(2.5);
	$marge_y = 0;
	$format_etiquette = array('x'=>mm2pt(45.4),'y'=>mm2pt(21.07)) ; // en mm
	$bar_height = mm2pt(8);
	$bar_width  = 1 ;
	//$angle = 15 ;
	$angle = 0 ;
	$font_size = 11;
	$page_origine = array('x'=>mm2pt(14),'y'=>mm2pt(25.4));



} elseif ($_POST['format_etiquette'] == 'L7993') {
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

	// mise au propre des données
	// alle face	colonne niv	emp
	// D01	P		001		20	A
	// 4	1		1		2	2  (tout en alphanum)
	// clé pose : 3 dernier car en dec du CRC
	$emplacement['brut']		= $textes[$i];
	$emplacement['clean']		= preg_replace('/[^0-9a-z]/i','',$emplacement['brut']);

	if ($textes[$i] == '') continue; // on saute les lignes vide

	$emplacement['allee']		= substr($emplacement['clean'],0,3);
	$emplacement['face']		= substr($emplacement['clean'],3,1);
	$emplacement['colonne']		= substr($emplacement['clean'],4,3);
	$emplacement['niveau']		= substr($emplacement['clean'],7,2);
	$emplacement['emplacement']	= substr($emplacement['clean'],9,2);

	$emplacement['code_barre']	= $emplacement['allee'].' '.$emplacement['face'].$emplacement['colonne'].$emplacement['niveau'].$emplacement['emplacement'];
	$emplacement['crc']			= sprintf('%u',crc32($emplacement['code_barre']));
	$emplacement['cle_pose']	= substr($emplacement['crc'],strlen($emplacement['crc'])-3,3);

	if ($emplacement['niveau'] == '') {
		echo var_dump($emplacement); exit;
	}

	// choix du theme de couleur
	$theme = $emplacement['niveau'];
	if (!array_key_exists($theme,$themes))
		$theme = 'default';

	//echo var_dump($emplacement);

	// on cree une nouvelle page si l'on depasse le nombre d'étiquette par page
	if ($etiquette_position + 1 > $max_etiquette_on_page) {
		$pdf->AddPage();
		$page++;
		$etiquette_position = 0;
	}

	// on détermine l'emplacement de l'étiquette en cours
	$colonne	= $etiquette_position % $max_etiquette_on_ligne;
	$ligne		= intval($etiquette_position / $max_etiquette_on_ligne);

	// et son point d'origine
	$origine = array(	'x'=>$page_origine['x'] + $format_etiquette['x'] * $colonne + $marge_x * $colonne,
						'y'=>$page_origine['y'] + $format_etiquette['y'] * $ligne	+ $marge_y * $ligne,
					);

	//$pdf->SetFillColor(255,255,255);

	// détermine la couleur de l'étiquette en fonction du theme
	$bgcolor=htlmColor2fpdfColor($themes[$theme]['background']);
	
	// détermine la couleur du texte en fonction du theme
	$txtcolor=htlmColor2fpdfColor($themes[$theme]['text']);
	$pdf->SetTextColor($txtcolor['red'],$txtcolor['green'],$txtcolor['blue']);

	//echo var_dump(htlmColor2fpdfColor($themes[$theme]['background']));

	if ($_POST['format_etiquette'] == 'L6009') {
	/*	$pdf->Code128(	$origine['x'] + $format_etiquette['x'] / 2,
						$origine['y'] + $bar_height / 2 + mm2pt(2.5),
						$emplacement['code_barre'],
						115,
						$bar_height);
*/
	/*	Barcode::fpdf($pdf,'000000',
						$origine['x'] + $format_etiquette['x'] / 2,
						$origine['y'] + $bar_height / 2 + mm2pt(2.5),
						$angle,  'code128', $emplacement['code_barre'], $bar_width - $i/20, $bar_height);
	*/
		// vérifie que le répertoire d'accueil des images existe
		if (!is_dir('tmp')) mkpath('tmp');

		// génération d'une image
		$image_width = 156;
		$image_height= 40;
	    $im   = imagecreatetruecolor($image_width, $image_height);
		imagefilledrectangle($im, 0, 0, $image_width, $image_height ,ImageColorAllocate($im,0xff,0xff,0xff));   // fond blanc
	    $data = Barcode::gd($im, ImageColorAllocate($im,0x00,0x00,0x00), $image_width / 2, $image_height / 2, $angle, 'code128', $emplacement['code_barre'] , 1, $image_height);
		//echo var_dump($data);
		$filename = 'tmp/'.$emplacement['code_barre'].' (code128).png'; // génération d'une image sur le disque
		imagepng($im,$filename);

		// importation de l'image que le doc PDF
		$pdf->Image($filename,
					$origine['x'] + ($format_etiquette['x'] - mm2pt($image_height))/2 -mm2pt(1),
					$origine['y'] + mm2pt(2.5) );

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
	

	} elseif  ($_POST['format_etiquette'] == 'L7993') {
		$pdf->SetFillColor($bgcolor['red'],$bgcolor['green'],$bgcolor['blue']);
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

		Barcode::fpdf($pdf,'000000', $origine['x'] + $format_etiquette['x'] / 2,  $origine['y'] + $format_etiquette['y'] / 2 - mm2pt(3), $angle,  'code39', $emplacement['code_barre'], $bar_width, $bar_height);

		// texte
		$pdf->SetFont('Arial','B',$font_size);
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size,				"$emplacement[allee] $emplacement[face]");			# allée + face
		$pdf->Text($origine['x'] + mm2pt( 4),$origine['y'] + $font_size * 2,			"$emplacement[colonne]");							# colonne
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size ,	"$emplacement[niveau] $emplacement[emplacement]");	# hauteur + emplacement
		$pdf->Text($origine['x'] + mm2pt(67),$origine['y'] + mm2pt(35) + $font_size*2 ,	"[$emplacement[cle_pose]]");						# clé

		//echo var_dump($_POST);

		if (isset($_POST['arrow']) && $_POST['arrow'] && in_array($emplacement['niveau'],array('00','10'))) // on veut que les fleche soit affichées
			$pdf->Image("arrow_$emplacement[niveau]_".$themes[$theme]['text'].".png",
						$origine['x'] + mm2pt(55),
						$origine['y'] + mm2pt(48) );
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
	preg_match('/^ *#? *([a-f0-9]{2}) *([a-f0-9]{2}) *([a-f0-9]{2}) *$/i',$htmlColor,$matches);
	return array(	'red'	=>	hexdec($matches[1]),
					'green'	=>	hexdec($matches[2]),
					'blue'	=>	hexdec($matches[3])
				);
}
?>