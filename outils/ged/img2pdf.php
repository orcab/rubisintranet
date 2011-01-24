<?
require_once('../../inc/fpdf/fpdf.php');

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);

//print_r($_GET); exit;

// génération du doc PDF
$pdf=new FPDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->AliasNbPages();
$pdf->AddPage(); // $nouvelle_page=true;

if (isset($_GET['img_name']) && $_GET['img_name']) {
	$filename_sans_ext = $_GET['img_name'];
	$filename_sans_ext = preg_replace('/\.[^\.]+$/','',$filename_sans_ext); # supprime l'extension

	$pdf->Image('documents/'.$_GET['img_name'],0 ,0, PAGE_WIDTH, PAGE_HEIGHT); // image en pleine page
	$pdf->Output($filename_sans_ext.'('.crc32(uniqid()).').pdf','I');
}
?>