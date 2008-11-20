<?

include('../inc/config.php');
include ('../inc/jpgraph/src/jpgraph.php');
include ('../inc/jpgraph/src/jpgraph_bar.php');
include ('../inc/jpgraph/src/jpgraph_line.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT count(id) as nb_anomalie,pole,DATE_FORMAT(date_creation,'%b %Y') as mois_creation
FROM anomalie
WHERE supprime=0
GROUP BY mois_creation ASC,pole ASC
ORDER BY date_creation ASC
EOT;

$res = mysql_query($sql) or die("Ne peux pas trouver le nombre de d'anomalie ".mysql_error());

$mois			= array();	$cumul			= array();
$logistique		= array();	$commerce		= array(); $exposition		= array();
$administratif	= array();	$informatique	= array();	$autre			= array();

$old_mois = '';
while($row = mysql_fetch_array($res)) {
	if ($old_mois != $row['mois_creation']) { // si le mois n'a pas encore été rencontré, on le rajoute
		$mois[]									= $row['mois_creation'];
		$logistique[$row['mois_creation']]		= 0;	$commerce[$row['mois_creation']]	= 0;	$exposition[$row['mois_creation']]	= 0;
		$administratif[$row['mois_creation']]	= 0;	$informatique[$row['mois_creation']]= 0;	$autre[$row['mois_creation']]		= 0;
		$cumul[$row['mois_creation']]			= 0;
	}

	if	($row['pole']&POLE_LOGISTIQUE)
		$logistique[$row['mois_creation']] += $row['nb_anomalie'];
	
	if	($row['pole']&POLE_COMMERCE)
		$commerce[$row['mois_creation']] += $row['nb_anomalie'];

	if	($row['pole']&POLE_EXPOSITION)
		$exposition[$row['mois_creation']] += $row['nb_anomalie'];

	if	($row['pole']&POLE_ADMINISTRATIF)
		$administratif[$row['mois_creation']] += $row['nb_anomalie'];

	if	($row['pole']&POLE_INFORMATIQUE)
		$informatique[$row['mois_creation']] += $row['nb_anomalie'];

	if	($row['pole']&POLE_AUTRE)
		$autre[$row['mois_creation']] += $row['nb_anomalie'];

	$cumul[$row['mois_creation']] += $row['nb_anomalie'];
	$old_mois = $row['mois_creation'];
}


//print_r($mois);//print_r($data_en_cours);print_r($data_open);
//exit;


// Create the graph. These two calls are always required
$graph = new Graph(800,400);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,30,30);
$graph->title->Set("Evolution des anomalies");

$graph->SetScale('textlin');
$graph->yaxis->SetColor('blue');
$graph->yaxis->title->Set("Nombre d'anomalie");
$graph->yaxis->SetTitlemargin(50);

$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
$graph->xgrid->Show();
$graph->xaxis->SetTickLabels(array_values($mois));

$graph->legend->SetShadow('gray@0.4',5);
$graph->legend->SetPos(0.1,0,'right','top');


// create the line cumul
$line_cumul	= new LinePlot(array_values($cumul));
$line_cumul->SetColor('navy');$line_cumul->SetLegend('Cumul');$line_cumul->value->SetColor('navy'); $line_cumul->value->SetFormat('%d');$line_cumul->value->SetFont( FF_FONT1, FS_BOLD);$line_cumul->value->Show();$line_cumul->SetBarCenter();$line_cumul->mark->SetTYPE(MARK_SQUARE);$line_cumul->mark->SetColor('navy');$line_cumul->mark->SetFillColor('yellow');


// Create the bar plots
$bar_log = new BarPlot(array_values($logistique));$bar_log->SetFillColor('green');$bar_log->SetLegend('Logistique');
$bar_log->value->SetColor('green');$bar_log->value->SetFormat('%d');$bar_log->value->SetFont( FF_FONT1, FS_BOLD);$bar_log->value->Show();

$bar_com = new BarPlot(array_values($commerce));$bar_com->SetFillColor('blue');$bar_com->SetLegend('Commerce');
$bar_com->value->SetColor('blue');$bar_com->value->SetFormat('%d');$bar_com->value->SetFont( FF_FONT1, FS_BOLD);$bar_com->value->Show();

$bar_exp = new BarPlot(array_values($exposition));$bar_exp->SetFillColor('purple');$bar_exp->SetLegend('Exposition');
$bar_exp->value->SetColor('purple');$bar_exp->value->SetFormat('%d');$bar_exp->value->SetFont( FF_FONT1, FS_BOLD);$bar_exp->value->Show();

$bar_adm = new BarPlot(array_values($administratif));$bar_adm->SetFillColor('orange');$bar_adm->SetLegend('Administratif');
$bar_adm->value->SetColor('orange');$bar_adm->value->SetFormat('%d');$bar_adm->value->SetFont( FF_FONT1, FS_BOLD);$bar_adm->value->Show();

$bar_inf = new BarPlot(array_values($informatique));$bar_inf->SetFillColor('red');$bar_inf->SetLegend('Informatique');
$bar_inf->value->SetColor('red');$bar_inf->value->SetFormat('%d');$bar_inf->value->SetFont( FF_FONT1, FS_BOLD);$bar_inf->value->Show();

$bar_aut = new BarPlot(array_values($autre));$bar_aut->SetFillColor('black');$bar_aut->SetLegend('Autre');
$bar_aut->value->SetColor('black');$bar_aut->value->SetFormat('%d');$bar_aut->value->SetFont( FF_FONT1, FS_BOLD);$bar_aut->value->Show();

// Create the grouped bar plot
$bar_group = new GroupBarPlot (array($bar_log,$bar_com,$bar_exp,$bar_adm,$bar_inf,$bar_aut));

// ...and add it to the graPH
$graph->Add($line_cumul);
$graph->Add($bar_group);
$graph->Stroke();

?>