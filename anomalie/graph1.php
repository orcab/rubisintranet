<?

include('../inc/config.php');
include ('../inc/jpgraph/src/jpgraph.php');
include ('../inc/jpgraph/src/jpgraph_bar.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT pole,evolution
FROM anomalie
WHERE supprime=0
EOT;

$res = mysql_query($sql) or die("Ne peux pas trouver le nombre de d'anomalie ".mysql_error());

$data_clo = array(0,0,0,0,0,0);
$data_en_cours = array(0,0,0,0,0,0);
$data_open = array(0,0,0,0,0,0);
while($row = mysql_fetch_array($res)) {
	if		($row['pole']&POLE_LOGISTIQUE)
		switch($row['evolution']) {
			case 0 : $data_open[0]++ ;break ;
			case 1 : $data_en_cours[0]++ ;break ;
			case 2 : $data_clo[0]++ ;break ;
		}
	
	if	($row['pole']&POLE_COMMERCE)
		switch($row['evolution']) {
			case 0 : $data_open[1]++ ;break ;
			case 1 : $data_en_cours[1]++ ;break ;
			case 2 : $data_clo[1]++ ;break ;
		}

	if	($row['pole']&POLE_EXPOSITION)
		switch($row['evolution']) {
			case 0 : $data_open[2]++ ;break ;
			case 1 : $data_en_cours[2]++ ;break ;
			case 2 : $data_clo[2]++ ;break ;
		}

	if	($row['pole']&POLE_ADMINISTRATIF)
		switch($row['evolution']) {
			case 0 : $data_open[3]++ ;break ;
			case 1 : $data_en_cours[3]++ ;break ;
			case 2 : $data_clo[3]++ ;break ;
		}

	if	($row['pole']&POLE_INFORMATIQUE)
		switch($row['evolution']) {
			case 0 : $data_open[4]++ ;break ;
			case 1 : $data_en_cours[4]++ ;break ;
			case 2 : $data_clo[4]++ ;break ;
		}

	if	($row['pole']&POLE_LITIGE)
		switch($row['evolution']) {
			case 0 : $data_open[5]++ ;break ;
			case 1 : $data_en_cours[5]++ ;break ;
			case 2 : $data_clo[5]++ ;break ;
		}

	if	($row['pole']&POLE_AUTRE)
		switch($row['evolution']) {
			case 0 : $data_open[6]++ ;break ;
			case 1 : $data_en_cours[6]++ ;break ;
			case 2 : $data_clo[6]++ ;break ;
		}
}


//print_r($data_clo);print_r($data_en_cours);print_r($data_open);
//exit;

//$data = array(40,60,21,33);


// Create the graph. These two calls are always required
$graph = new Graph(800,300);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,30,30);
$graph->title->Set("Anomalies déclarées");

$graph->SetScale('textlin');
$graph->yaxis->SetColor('blue');
$graph->yaxis->title->Set("Nombre d'anomalie");
$graph->yaxis->SetTitlemargin(50);

$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
$graph->xgrid->Show();
$graph->xaxis->SetTickLabels(array('Logistique','Commerce','Expo','Administratif','Informatique','Litige','Autre'));

$graph->legend->SetShadow('gray@0.4',5);
$graph->legend->SetPos(0.1,0,'right','top');


// Create the bar plots
$bar_clo = new BarPlot($data_clo);
$bar_clo->SetFillColor('green');
$bar_clo->SetLegend('Cloturé');
$bar_en_cours = new BarPlot($data_en_cours);
$bar_en_cours->SetFillColor('orange');
$bar_en_cours->SetLegend('En cours');
$bar_open = new BarPlot($data_open);
$bar_open->SetFillColor('red');
$bar_open->SetLegend('Ouvert');	

$bar_clo->value->SetColor('#003300'); 
$bar_clo->value->SetFormat('%d');
$bar_clo->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_clo->value->Show();

$bar_en_cours->value->SetColor('red'); 
$bar_en_cours->value->SetFormat('%d');
$bar_en_cours->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_en_cours->value->Show();

$bar_open->value->SetColor('white'); 
$bar_open->value->SetFormat('%d');
$bar_open->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_open->value->Show();

// Create the grouped bar plot
$bar_group = new AccBarPlot(array($bar_clo,$bar_en_cours,$bar_open));
$bar_group->SetWidth(0.75);

// ...and add it to the graPH
$graph->Add($bar_group);
$graph->Stroke();

?>