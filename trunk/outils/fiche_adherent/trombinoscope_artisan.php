<?

include('../../inc/config.php');
require_once('overload.php');

//error_reporting(E_ALL ^ E_NOTICE);

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);


$sql = <<<EOT
select	CLIENT.NOCLI as NUMERO,
		CLIENT.NOMCL as NOM,
		CLIENT.CPCLF as CODE_POSTAL,
		CLIENT.BURCL as VILLE,
		CLIENT.TELCL as TELEPHONE1,
		CLIENT.TELCC as TELEPHONE2,
		CLIENT.CONTA as DIRIGEANT,
		CLIENT.CLID8 as CONSEIL_ADMIN,
		CLIENT.DICN2 as ELECTRICIEN,
		CLIENT.DICN1 as PLOMBIER
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
where
			CLIENT.ETCLE=''		--client actif
		and CLIENT.CATCL='1' 	-- type adhérent
		and CLIENT.NOMCL<>'ADHERENT'
		and CLIENT.NOCLI<>'056039'
order by CLIENT.NOMCL asc
EOT;



$rubis = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res   = odbc_exec($rubis,$sql) ; 

$conseil_admins	= array(); // array
//$clients_order 	= array(); // array
$clients		= array(); // hash
while($row = odbc_fetch_array($res)) {
	$tmp = split(' / ',$row['DIRIGEANT']);
	$row['DIRIGEANT_PRENOM']= isset($tmp[1]) ? $tmp[1]:'';
	$row['DIRIGEANT_NOM']	= $tmp[0];

	// save datas
	$clients[$row['NUMERO']] = $row;

	// save order
//	array_push($clients_order,$row['NUMERO']);

	// save admin
	if ($row['CONSEIL_ADMIN'] == 'ADM')
		array_push($conseil_admins,$row['NUMERO']);
}

//var_dump($clients);
//var_dump($clients_order);
//var_dump($conseil_admins);

define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);
define('LEFT_MARGIN',9);
define('RIGHT_MARGIN',LEFT_MARGIN);
define('TOP_MARGIN',45);

define('PHOTO_WIDTH',24);
define('PHOTO_HEIGHT',32);
define('NB_PHOTO_PER_ROW',3);
define('NB_ROW_PER_PAGE',4);
define('NB_PHOTO_PER_PAGE',NB_PHOTO_PER_ROW * NB_ROW_PER_PAGE);

define('HORINZONTAL_SPACE_BETWEEN_PHOTO',(PAGE_WIDTH - NB_PHOTO_PER_ROW*PHOTO_WIDTH) / (NB_PHOTO_PER_ROW+1));
define('VERTICAL_SPACE_BETWEEN_PHOTO',(PAGE_HEIGHT - NB_ROW_PER_PAGE*PHOTO_HEIGHT) / (NB_ROW_PER_PAGE+1));

// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();

/*
init_debug();
debug("PAGE_WIDTH=".PAGE_WIDTH."\n");
debug("PAGE_HEIGHT=".PAGE_HEIGHT."\n");
debug("PHOTO_WIDTH=".PHOTO_WIDTH."\n");
debug("PHOTO_HEIGHT=".PHOTO_HEIGHT."\n");
debug("NB_PHOTO_PER_ROW=".NB_PHOTO_PER_ROW."\n");
debug("NB_ROW_PER_PAGE=".NB_ROW_PER_PAGE."\n");
debug("NB_PHOTO_PER_PAGE=".NB_PHOTO_PER_PAGE."\n");
debug("HORINZONTAL_SPACE_BETWEEN_PHOTO=".HORINZONTAL_SPACE_BETWEEN_PHOTO."\n");
debug("VERTICAL_SPACE_BETWEEN_PHOTO=".VERTICAL_SPACE_BETWEEN_PHOTO."\n\n");
*/

//1ere de couverture
$pdf->Image('gfx/trombinoscope_artisan.png',0,0,PAGE_WIDTH,PAGE_HEIGHT);

// conseil d'administration
$pdf->AddPage();
$pdf->SetFont('helvetica','B',30);
$pdf->SetTextColor(0,0,0);
$pdf->SetY(15);
$pdf->Cell(0,0,"Conseil d'administration",0,0,'C');

$pdf->SetFont('helvetica','B',11);
for ($i=0 ; $i<sizeof($conseil_admins) ; $i++) {
	$numero = $conseil_admins[$i];

	$col = $i % NB_PHOTO_PER_ROW;
	$row = (int)($i / NB_PHOTO_PER_ROW);

	$x = $col * (PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO) + HORINZONTAL_SPACE_BETWEEN_PHOTO;
	$y = $row * (PHOTO_HEIGHT + VERTICAL_SPACE_BETWEEN_PHOTO) + VERTICAL_SPACE_BETWEEN_PHOTO - 10;

	// photo de l'administrateur
	$pdf->Image(get_photo_artisan($numero), $x,	$y,	0,PHOTO_HEIGHT); // auto adapt width

	// info sous l'image
	$pdf->SetXY($x,$y + PHOTO_HEIGHT + 5);
	$pdf->Cell(0,0,trim($clients[$numero]['NOM']));
}



// autre artisans
$pdf->AddPage();
$pdf->SetFont('helvetica','B',11);
$i=0;
foreach ($clients as $numero => $values) {
	$col = $i % NB_PHOTO_PER_ROW;
	$row = (int)($i / NB_PHOTO_PER_ROW);

	$x = $col * (PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO) + HORINZONTAL_SPACE_BETWEEN_PHOTO;
	$y = $row * (PHOTO_HEIGHT + VERTICAL_SPACE_BETWEEN_PHOTO) + VERTICAL_SPACE_BETWEEN_PHOTO - 20;

	//debug("photo n°$i (".trim($clients[$numero]['NOM']).")\ncol=$col\nrow=$row\nx=$x\ny=$y\n\n");

	// photo de l'administrateur
	$pdf->Image(get_photo_artisan($numero), $x,	$y,	0,PHOTO_HEIGHT); // auto adapt width

	// icon plombier a coté de la photo
	if (trim($clients[$numero]['PLOMBIER']))
		$pdf->Image('../../photos/adherents/plombier.png', $x - 10, $y + PHOTO_HEIGHT / 2, 10); // auto adapt height

	// icon electricien a coté de la photo
	if (trim($clients[$numero]['ELECTRICIEN']))
		$pdf->Image('../../photos/adherents/electricien.png', $x - 10, $y + PHOTO_HEIGHT / 2 + 12, 10); // auto adapt height

	// info sous l'image
	$pdf->SetXY($x,$y + PHOTO_HEIGHT + 5);

	// redux de font pour la référence
	$font_size_max = min($pdf->redux_font_size(trim($clients[$numero]['NOM']),11,PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO),11); // on prend la plus petite des deux
	$pdf->SetFont('helvetica','B',$font_size_max);

	$pdf->Cell(PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO,0,trim($clients[$numero]['NOM']));
	$pdf->SetXY($x,$y + PHOTO_HEIGHT + 10);

	$font_size_max = min($pdf->redux_font_size(trim($clients[$numero]['DIRIGEANT_NOM']).' '.trim($clients[$numero]['DIRIGEANT_PRENOM']),11,PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO),11); // on prend la plus petite des deux
	$pdf->SetFont('helvetica','B',$font_size_max);
	$pdf->Cell(PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO,0,trim($clients[$numero]['DIRIGEANT_NOM']).' '.trim($clients[$numero]['DIRIGEANT_PRENOM']));

	$font_size_max = min($pdf->redux_font_size(trim($clients[$numero]['CODE_POSTAL']).' '.trim($clients[$numero]['VILLE']),11,PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO),11); // on prend la plus petite des deux
	$pdf->SetXY($x,$y + PHOTO_HEIGHT + 15);
	$pdf->Cell(PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO,0,trim($clients[$numero]['CODE_POSTAL']).' '.trim($clients[$numero]['VILLE']));

	$pdf->SetXY($x,$y + PHOTO_HEIGHT + 20);
	$pdf->Cell(PHOTO_WIDTH + HORINZONTAL_SPACE_BETWEEN_PHOTO,0,trim($clients[$numero]['TELEPHONE1']) ? trim($clients[$numero]['TELEPHONE1']) : trim($clients[$numero]['TELEPHONE2']));


	if ((++$i % NB_PHOTO_PER_PAGE) == 0) { // max photo per page
		$i=0;
		$pdf->AddPage();
	}
}



// generation du pdf avec un numero unique pour que les navigateur gere bien le cache
$pdf->Output('trombinoscope_artisan_('.crc32(uniqid()).').pdf','I');


odbc_close($rubis);

///////////////////////////////////////////////////////////////////////////////////////////////////////////////
function get_photo_artisan($numero) {
	// default
	$photo = '../../photos/adherents/no_photo.png';

	// png
	if (file_exists('../../photos/adherents/'.$numero.'.png'))
		$photo = '../../photos/adherents/'.$numero.'.png';

	// jpg
	else if (file_exists('../../photos/adherents/'.$numero.'.jpg'))
		$photo = '../../photos/adherents/'.$numero.'.jpg';

	return $photo;
}


function debug($text) {
	$F = fopen('debug.txt','a');
	fwrite($F,$text);
	fclose($F);
}


function init_debug() {
	unlink('debug.txt');
}

?>