<?
include "../inc/phpqrcode/qrlib.php";
$texte = isset($_GET['text']) && $_GET['text'] ? $_GET['text']:'';
#png($text, $outfile = false, $level = QR_ECLEVEL_L, $size = 3, $margin = 4, $saveandprint=false) 

$texte = preg_replace('/\\\"/','"',$texte); // supprime les \" par "
#echo $texte;
QRcode::png($texte,false,'H',1);
?>