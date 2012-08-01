<?

include('../../inc/config.php');
require_once('overload_etiquette.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

if (!isset($_GET['code_article'])) { ?>
	ERREUR : Aucun code article précisé.
<? 	exit;
}

$code_article_escape	= mysql_escape_string($_GET['code_article']);
$qte_escape = 0 ;
if (isset($_GET['qte']))
	$qte_escape	= mysql_escape_string($_GET['qte']);

$sql_entete = <<<EOT
select	ARTICLE.NOART, ARTICLE.DESI1, ARTICLE.DESI2, ARTICLE.DESI3,
		FOURNISSEUR.NOMFO,
		ART_FOU.REFFO, ART_FOU.AFOG3 as EAN13,
		STOCK.LOCAL,STOCK.LOCA2,STOCK.LOCA3
from			${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
					on ARTICLE.FOUR1=FOURNISSEUR.NOFOU
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ART_FOU
					on ARTICLE.NOART=ART_FOU.NOART and FOURNISSEUR.NOFOU=ART_FOU.NOFOU
	left join	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK
					on ARTICLE.NOART=STOCK.NOART and STOCK.DEPOT='${LOGINOR_DEPOT}'
where	ARTICLE.NOART='$code_article_escape'
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
$row_entete		= array_map('trim',$row_entete);


// génération du doc PDF
$pdf=new PDF('L','mm','A4');
$pdf->SetDisplayMode('fullpage','single');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute
$pdf->AddPage();
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica','B',38);

$x_origine  = 100;
$y_origine  = 36;

$interligne = 14;
//$oldY = 25;

// fournisseur
$pdf->SetXY($x_origine,$y_origine);
$pdf->MultiCell(0,10,$row_entete['NOMFO'],0,'L');

// reference
$pdf->SetY($y_origine + 20);
$pdf->MultiCell(0,10,$row_entete['REFFO'],0);

// designation
$pdf->SetY($y_origine + 34);
$pdf->MultiCell(0,10,$row_entete['DESI1'],0);

// noart + qte
$pdf->SetFont('helvetica','B',60);
$pdf->SetY($y_origine + 59);
$pdf->MultiCell(0,10,$row_entete['NOART']."QTE: $qte_escape",0);

// code barre
$pdf->SetY($y_origine + 75);
if (is_ean13($row_entete['EAN13']))
	$pdf->EAN13(LEFT_MARGIN, $y_origine + 75 , $row_entete['EAN13'] , 40 , 2 );

// localisation
$localisations = array();
if ($row_entete['LOCAL'])	$localisations[] = $row_entete['LOCAL'];
if ($row_entete['LOCA2'])	$localisations[] = $row_entete['LOCA2'];
if ($row_entete['LOCA3'])	$localisations[] = $row_entete['LOCA3'];
$pdf->SetFont('helvetica','B',25);
$pdf->SetY($y_origine + 117);
$pdf->MultiCell(0,10,join(' / ',$localisations),0);


$pdf->Output('etiquette_'.$row_entete['NOART'].'('.crc32(uniqid()).').pdf','I');

odbc_close($loginor);
?>