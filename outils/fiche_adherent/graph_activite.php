<?

include('../../inc/config.php');
include('../../inc/jpgraph/src/jpgraph.php');
include('../../inc/jpgraph/src/jpgraph_radar.php');

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
while(($row_stat	= odbc_fetch_array($res_stat)) && ($i++<5)) {
	//print_r($row_stat);
	$data[]		= round($row_stat['CA']/1000,2); // charge les données
	$libelle[$row_stat['CODE_FAMILLE']]	= $row_stat['LIBELLE_FAMILLE'];
}

// Set the basic parameters of the graph 
$graph = new RadarGraph(330,330,"auto");
//$graph->SetMargin(100,100,100,100);
$graph->title->Set('5 meilleurs Activités en Ke');
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetShadow();

// Create the titles for the axis
$graph->SetTitles(array_keys($libelle));
$graph->SetCenter(0.45,0.55);

// Add grid lines
$graph->grid->Show();
$graph->grid->SetLineStyle('dotted');

$plot = new RadarPlot($data);
$plot->SetFillColor('lightblue');
$legend = '';
$i=0;
foreach($libelle as $code=>$lib) {
	$legend .= "$code ".substr($lib,0,5).' '.round($data[$i++])."Ke\n";
}
$plot->SetLegend(trim($legend));

// Add the plot and display the graph
$graph->Add($plot);
$graph->Stroke();
?>