<?

include('../../inc/config.php');
include ('../../inc/jpgraph/src/jpgraph.php');
include ('../../inc/jpgraph/src/jpgraph_pie.php');

$sql = <<<EOT
select COUNT(NOBON) as NB_BON, SUM(MONTBT) as CA, TYVTE
from AFAGESTCOM.AENTBOP1
where
		ETSEE=''
	and NOCLI='$_GET[numero_artisan]'
group by
		TYVTE
order by
		TYVTE asc -- EMP puis LIV
EOT;

$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");
$res_stat	= odbc_exec($loginor,$sql) ;

$data = array();
while($row_stat	= odbc_fetch_array($res_stat)) {
	$data[] = round($row_stat['CA']/1000,2);
}

// Set the basic parameters of the graph 
$graph = new PieGraph(400,250,'auto');
$graph->title->Set('Rapport EMP/LIV CA');
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetShadow();

$pie = new PiePlot($data);
$pie->SetLegends(array('Emport� (%0.1f%%)','Livr� (%0.1f%%)'));
$pie->SetCenter(0.4);

$pie->SetLabelType(PIE_VALUE_PER); 
$pie->SetLabels(array("Emport�/%0.1f/$data[0]","Livr�/%0.1f/$data[1]")); 
$pie->SetSliceColors(array('#71BBE3','#FCD700'));
$pie->SetLabelPos(0.75); 

$pie->value->SetFormatCallback('pieValueFormat'); 
function pieValueFormat($aLabel) {
	$tmp = explode('/',$aLabel);
    return "$tmp[0]\n$tmp[2] Ke ($tmp[1]%)";
}

$graph->Add($pie);
$graph->Stroke();

?>