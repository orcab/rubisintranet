<?
include "../inc/phpqrcode/qrlib.php";
$texte = isset($_GET['text']) && $_GET['text'] ? $_GET['text']:''; 
QRcode::png($texte);
?>