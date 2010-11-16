<?

include('../inc/config.php');
require_once('overload_etiquette.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

if (!(isset($_GET['NOBON']) && $_GET['NOBON'])) { ?>
	ERREUR : Aucun N° de cde précisé.
<? 	exit;
}

$NOBON_escape = mysql_escape_string($_GET['NOBON']);
$NOCLI_escape = mysql_escape_string($_GET['NOCLI']);

$sql_entete = <<<EOT
select NOMSB,RFCSB
from ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON, ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
where	BON.NOBON='$NOBON_escape'
	and BON.NOCLI='$NOCLI_escape'
	and BON.NOCLI = CLIENT.NOCLI
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
$row_entete		= array_map('trim',$row_entete);


// génération du doc PDF
$pdf=new PDF('P','mm',array(PAGE_WIDTH,PAGE_HEIGHT));
$pdf->SetDisplayMode('fullpage','single');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute
$pdf->AddPage();
$pdf->SetTextColor(0);
$pdf->SetFont('helvetica','B',36);


$pdf->MultiCell(0,10,$row_entete['NOMSB']);
$pdf->SetY(113);

$pdf->MultiCell(0,10,$row_entete['RFCSB']);
$pdf->SetY(150);

$pdf->SetFont('helvetica','B',70);
$pdf->Cell(0,10,strtoupper($NOBON_escape));

$pdf->Output('etiquette_'.$NOBON_escape.'('.crc32(uniqid()).').pdf','I');

odbc_close($loginor);
?>