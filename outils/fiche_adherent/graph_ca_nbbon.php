<?
include('../../inc/config.php');
include('../../inc/jpgraph/src/jpgraph.php');
include('../../inc/jpgraph/src/jpgraph_line.php');
include('../../inc/jpgraph/src/jpgraph_bar.php');

$mois = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

$previous_month = isset($_GET['mois_end'])		&& $_GET['mois_end']	? $_GET['mois_end']		: date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')));
$one_year_ago	= isset($_GET['mois_start'])	&& $_GET['mois_start']	? $_GET['mois_start']	: date('Ym',mktime(0, 0, 0, date('m')-1,date('d'),date('Y')-1));

$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

$sql = <<<EOT
select COUNT(NOBON) as NB_BON, SUM(MONTBT) as CA, CONCAT(DSECS,CONCAT(DSECA,DSECM)) as GROUPE
from AFAGESTCOM.AENTBOP1
where
		ETSEE=''
	and NOCLI='$_GET[numero_artisan]'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))>='${one_year_ago}01'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))<='${previous_month}31'
	and LIVSB='WEB'	-- uniquement les commandes WEB
group by	CONCAT(DSECS,CONCAT(DSECA,DSECM))
order by	GROUPE asc
EOT;

$infos_WEB	= array();
$res_stat	= odbc_exec($loginor,$sql) ;
while($row_stat	= odbc_fetch_array($res_stat)) {
	$infos_WEB[$row_stat['GROUPE']] = $row_stat['NB_BON'] ;
}

$sql = <<<EOT
select COUNT(NOBON) as NB_BON, SUM(MONTBT) as CA, CONCAT(DSECS,CONCAT(DSECA,DSECM)) as GROUPE
from AFAGESTCOM.AENTBOP1
where
		ETSEE=''
	and NOCLI='$_GET[numero_artisan]'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))>='${one_year_ago}01'
	and CONCAT(DSECS,CONCAT(DSECA,CONCAT(DSECM,DSECJ)))<='${previous_month}31'
group by
		CONCAT(DSECS,CONCAT(DSECA,DSECM))
order by
		GROUPE asc
EOT;

//echo $sql;

// rempli les libéllé pour les mois
$mois_rubis = array();
$data_NBBON	= array();
$data_WEB	= array(); // nombre de bon fait par le web
$data_CA	= array();
$res_stat		= odbc_exec($loginor,$sql) ;
while($row_stat	= odbc_fetch_array($res_stat)) {
	preg_match('/^(\d{4})(\d{2})$/',$row_stat['GROUPE'],$regs);
	array_push($mois_rubis,$mois[$regs[2] - 1].' '.$regs[1]);
	array_push($data_NBBON,$row_stat['NB_BON']);
	array_push($data_CA,$row_stat['CA'] / 1000);
	if (isset($infos_WEB[$row_stat['GROUPE']])) // commande web renseigné
		array_push($data_WEB,$infos_WEB[$row_stat['GROUPE']]);
	else
		array_push($data_WEB,0);
}

// Setup the graph
$graph = new Graph(800,300);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,50,60);
$date_split_start	= array(substr($one_year_ago,0,4),substr($one_year_ago,4,2));
$date_split_end		= array(substr($previous_month,0,4),substr($previous_month,4,2));
$graph->title->Set("Evolution du $date_split_start[1]/$date_split_start[0]\nau $date_split_end[1]/$date_split_end[0]");

$graph->SetScale('textlin');
$graph->SetY2Scale('lin');
$graph->yaxis->SetColor('blue');
$graph->y2axis->SetColor('red');
$graph->yaxis->title->Set('Nb Bon');
$graph->yaxis->SetTitlemargin(50);
$graph->yaxis->title->SetColor('blue');
$graph->y2axis->title->Set('CA en Ke');
$graph->y2axis->title->SetColor('red');

$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
$graph->xgrid->Show();
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetTickLabels($mois_rubis);

$graph->legend->SetShadow('gray@0.4',5);
$graph->legend->SetPos(0.21,0,'right','top');

// Create the bar plots
$line_nbbon	= new LinePlot($data_NBBON);
$line_web	= new LinePlot($data_WEB);
$line_ca	= new LinePlot($data_CA);

$graph->Add($line_nbbon);
$graph->Add($line_web);
$graph->AddY2($line_ca);

$line_nbbon->SetColor('red');
$line_nbbon->SetLegend('Nb Bon');
$line_nbbon->value->SetColor('red'); 
$line_nbbon->value->SetFormat('%d');
$line_nbbon->value->SetFont( FF_FONT1, FS_BOLD);
$line_nbbon->mark->SetTYPE( MARK_SQUARE);
$line_nbbon->mark->SetColor('red');
$line_nbbon->mark->SetFillColor('red');
$line_nbbon->value->Show();

$line_web->SetColor('darkgreen');
$line_web->SetLegend('Nb bon web');
$line_web->value->SetColor('darkgreen'); 
$line_web->value->SetFormat('%d');
$line_web->value->SetFont( FF_FONT1, FS_BOLD);
$line_web->mark->SetTYPE( MARK_X);
$line_web->mark->SetColor('darkgreen');
$line_web->mark->SetFillColor('darkgreen');
$line_web->value->Show();

$line_ca->SetColor('blue');
$line_ca->SetLegend('CA en Ke');
$line_ca->value->SetColor('blue'); 
$line_ca->value->SetFormat('%d Ke');
$line_ca->value->SetFont( FF_FONT1, FS_BOLD); 
$line_ca->value->Show();
$line_ca->mark->SetTYPE( MARK_DIAMOND);
$line_ca->mark->SetColor('blue');
$line_ca->mark->SetFillColor('blue');

// Output line
$graph->Stroke();
?>