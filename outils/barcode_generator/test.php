<?php
require_once('../../inc/fpdf/fpdf.php');
require_once('php-barcode-2.0.1.php');

class PDF extends FPDF
{
	//EN-TÊTE
	function Header()
	{	
	}


	//PIED DE PAGE
	function Footer()
	{	
	}

	function Polygon($points, $style='D')
	{
		//Draw a polygon
		if($style=='F')
			$op = 'f';
		elseif($style=='FD' || $style=='DF')
			$op = 'b';
		else
			$op = 's';

		$h = $this->h;
		$k = $this->k;

		$points_string = '';
		for($i=0; $i<count($points); $i+=2){
			$points_string .= sprintf('%.2F %.2F', $points[$i]*$k, ($h-$points[$i+1])*$k);
			if($i==0)
				$points_string .= ' m ';
			else
				$points_string .= ' l ';
		}
		$this->_out($points_string . $op);
	}


} // fin class PDF


$pdf=new PDF('L','pt','A4');
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(0,0,0); // marge gauche et haute (droite = gauche)
$pdf->AddPage();
//print_r(

// seul le 39 marche

$format_lisse = array('x'=>mm2pt(90),'y'=>mm2pt(80)); // en mm
$color_sticker_theme	= array('000000',	'F0ED00',		'95D5FF',	'FF341F',	'C4FF1F',		'00A60A');
								// noir     jaune			bleu		rouge		vertclair		vert
$color_text_theme		= array('FFFFFF',	'000000',		'000000',	'FFFFFF',	'000000',		'FFFFFF');
								// white	// black		// black	//white		//black			//white

$textes = array('M23 I 005 80 D','D21 P 002 20 A','P12 I 001 10 A','D01 I 003 30 B','M01 I 005 60 C','D16 P 002 10 A');

$bar_height = 100;
$angle = 32 ;
define('CODE39','code39');
$font_size = 40;

$page_origine = array('x'=>10,'y'=>30);



for($i=0 ; $i<=5 ;$i++) {
	$colonne	= $i%3;
	$ligne		= intval($i/3);
	//$texte = ; //'M23 I 005 80 D';
	$texte_split = explode(' ',$textes[$i]);
	$origine = array(	'x'=>$page_origine['x'] + $format_lisse['x'] * $colonne,
						'y'=>$page_origine['y'] + $format_lisse['y'] * $ligne
					);	

	$color=htlmColor2fpdfColor($color_sticker_theme[$i]); $pdf->SetFillColor($color['red'],$color['green'],$color['blue']);
	$pdf->Rect($origine['x'],$origine['y'],$format_lisse['x'],$format_lisse['y'],'FD');
	$pdf->SetFillColor(255,255,255);
	$pdf->Polygon(array(
							$origine['x'] + mm2pt( 76)				, $origine['y'],						//p1
							$origine['x'] + $format_lisse['x']		, $origine['y'] ,						//p2
							$origine['x'] + $format_lisse['x']		, $origine['y'] + mm2pt(33),			//p3
							$origine['x'] + mm2pt(14)				, $origine['y'] +  $format_lisse['y'],	//p4
							$origine['x']							, $origine['y'] +  $format_lisse['y'],	//p5
							$origine['x']							, $origine['y'] + mm2pt(47.5),			//p6
						),'FD');

	Barcode::fpdf($pdf,'000000', $origine['x'] + $format_lisse['x'] / 2,  $origine['y'] + $format_lisse['y'] / 2, $angle,  CODE39, $textes[$i], $width = null, $bar_height);

	
	$color=htlmColor2fpdfColor($color_text_theme[$i]); $pdf->SetTextColor($color['red'],$color['green'],$color['blue']);
	$pdf->SetFont('Arial','B',$font_size);
	$pdf->Text($origine['x'] + mm2pt( 2),$origine['y'] + $font_size,				$texte_split[0].' '.$texte_split[1]);
	$pdf->Text($origine['x'] + mm2pt( 2),$origine['y'] + $font_size * 2,			$texte_split[2]);
	$pdf->Text($origine['x'] + mm2pt(57),$origine['y'] + mm2pt(62) + $font_size ,	$texte_split[3].' '.$texte_split[4]);


}


$pdf->Output();


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


/*$pdf=new PDF_Code128();
$pdf->AddPage();
$pdf->SetFont('Arial','',10);

//Jeu A
$code='B';
$pdf->Code128(50,20,$code,115,20);
//$pdf->SetXY(50,45);
//$pdf->Write(5,'Jeu A : "'.$code.'"');

//Jeu B
$code='Code a barres 128';
$pdf->Code128(50,70,$code,115,20);
$pdf->SetXY(50,95);
$pdf->Write(5,'Jeu B : "'.$code.'"');

//Jeu C
$code='12345678901234567890';
$pdf->Code128(50,120,$code,120,20);
$pdf->SetXY(50,145);
$pdf->Write(5,'Jeu C : "'.$code.'"');

//Jeux A,C,B
$code='ABCDEFG1234567890AbCdEf';
$pdf->Code128(50,170,$code,125,20);
$pdf->SetXY(50,195);
$pdf->Write(5,'Jeux ABC commutés : "'.$code.'"');
*/
$pdf->Output();

?>