<?

include('../../inc/config.php');
include ('../../inc/jpgraph/src/jpgraph.php');
include ('../../inc/jpgraph/src/jpgraph_pie.php');

$previous_month = isset($_GET['mois_end'])		&& $_GET['mois_end']	? $_GET['mois_end']		: date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')));
$one_year_ago	= isset($_GET['mois_start'])	&& $_GET['mois_start']	? $_GET['mois_start']	: date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')-1));

$sql = <<<EOT
select COUNT(NOBON) as NB_BON, SUM(MONTBT) as CA, TYVTE
from AFAGESTCOM.AENTBOP1
where
		ETSEE=''
	and NOCLI='$_GET[numero_artisan]'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))>='${one_year_ago}01'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))<='${previous_month}31'
group by	TYVTE
order by	TYVTE asc -- EMP puis LIV
EOT;

$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res_stat	= odbc_exec($loginor,$sql) ;

$data = array();
while($row_stat	= odbc_fetch_array($res_stat)) {
	$data[] = $row_stat['NB_BON'];
}

// Set the basic parameters of the graph 
$graph = new PieGraph(400,250,'auto');
$date_split_start	= array(substr($one_year_ago,0,4),substr($one_year_ago,4,2));
$date_split_end		= array(substr($previous_month,0,4),substr($previous_month,4,2));
$graph->title->Set("Rapport EMP/LIV Nb bon du $date_split_start[1]/$date_split_start[0]\nau $date_split_end[1]/$date_split_end[0]");
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetShadow();

$pie = new PiePlot($data);
$pie->SetLegends(array('Emporté (%0.1f%%)','Livré (%0.1f%%)'));
$pie->SetCenter(0.4);

$pie->SetLabelType(PIE_VALUE_PER); 
$pie->SetLabels(array("Emporté/%0.1f/$data[0]","Livré/%0.1f/$data[1]")); 
$pie->SetSliceColors(array('#71BBE3','#FCD700'));
$pie->SetLabelPos(0.75); 

$pie->value->SetFormatCallback('pieValueFormat'); 
function pieValueFormat($aLabel) {
	$tmp = explode('/',$aLabel);
    return "$tmp[0]\n$tmp[2] bon ($tmp[1]%)";
}

$graph->Add($pie);
$graph->Stroke();

?>