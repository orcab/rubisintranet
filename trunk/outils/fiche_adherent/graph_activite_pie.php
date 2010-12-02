<?

include('../../inc/config.php');
require_once ('../../inc/jpgraph-3.5.0b1/src/jpgraph.php');
require_once ('../../inc/jpgraph-3.5.0b1/src/jpgraph_pie.php');

$previous_month = date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')));
$one_year_ago	= date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')-1));

$sql = <<<EOT
select	SUM(MONHT) as CA, FAMILLE.ACFLI as LIBELLE_FAMILLE,FAMILLE.AFCAC as CODE_FAMILLE
from	AFAGESTCOM.ADETBOP1 DETAIL_BON
			left join AFAGESTCOM.AFAMILP1 FAMILLE
				on DETAIL_BON.ACTBO=FAMILLE.AFCAC and FAMILLE.AFCNI='ACT'	-- join les libéllé familles et les lignes de bons
where
		ETSBE=''	-- ligne non supprimées
	and ACTBO<>''	-- activité non vide
	and NOCLI='$_GET[numero_artisan]'
	and CONCAT(DSBCS,CONCAT(DSBCA,CONCAT(DSBCM,DSBCJ)))>='${one_year_ago}01'
	and CONCAT(DSBCS,CONCAT(DSBCA,CONCAT(DSBCM,DSBCJ)))<='${previous_month}31'
group by	FAMILLE.ACFLI,FAMILLE.AFCAC
order by	CA DESC
EOT;

$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res_stat	= odbc_exec($loginor,$sql) or die("Impossible d'executer la requette") ;

$i=0;
$data	= array();
$libelle= array();
while(($row_stat	= odbc_fetch_array($res_stat)) && ($i++<7)) {
	$data[]		= round($row_stat['CA']/1000,2); // charge les données
	$libelle[$row_stat['CODE_FAMILLE']]	= $row_stat['LIBELLE_FAMILLE'];
}

// Set the basic parameters of the graph 
$graph = new PieGraph(500,510);
//$graph->SetMargin(100,100,100,100);
$graph->title->Set('CA 7 premieres activités sur 12 derniers mois');
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetShadow();
$graph->SetBox(true,array(0,0,0),1);
$graph->SetFrame();

// Create the titles for the axis
//$graph->SetTitles(array_keys($libelle));
//$graph->SetCenter(0.45,0.55);

// Add grid lines
//$graph->grid->Show();
//$graph->grid->SetLineStyle('dotted');

$p1 = new PiePlotC($data);
$p1->SetSize(0.35);
$p1->value->SetFont(FF_ARIAL,FS_NORMAL,9);
$p1->value->SetColor('black');
$p1->value->Show();

// Setup the title on the center circle
$p1->midtitle->Set("CA par activité\nen Keuros\nsur 12 derniers mois");
$p1->midtitle->SetFont(FF_ARIAL,FS_NORMAL,13);

// Set color for mid circle
$p1->SetMidColor('yellow');
 
// Use percentage values in the legends values (This is also the default)
$p1->SetLabelType(PIE_VALUE_PER);
$p1->SetCenter(0.5,0.415);

$legends = array();
foreach(array_values($libelle) as $lib) {
	$legends[] = substr($lib,0,5);
}
$p1->SetLegends($legends);
//$graph->legend->Pos(0.05,0.1);

$i=0;
$label = array();
foreach($libelle as $code=>$lib) {
	$label[] = "%.1f%%\n".round($data[$i++])."Ke";
}
$p1->SetLabels($label);
$p1->SetLabelPos(0.85); 
 
// Uncomment this line to remove the borders around the slices
// $p1->ShowBorder(false);
 
// Add drop shadow to slices
$p1->SetShadow();
 
// Explode all slices 15 pixels
$p1->ExplodeAll(15);
 
// Add plot to pie graph
$graph->Add($p1);
$graph->Stroke();
?>