<?

include('../inc/config.php');
include('../inc/iCalParser/ical-parser-class.php');
include ('../inc/jpgraph/src/jpgraph.php');
include ('../inc/jpgraph/src/jpgraph_bar.php');


$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

// charge le nom des adhérents en mémoire
$adherent = array();  // format $adherent[056089] = 'Machin truc' ;
$res  = mysql_query('SELECT nom,numero FROM artisan WHERE suspendu=0');
while ($row = mysql_fetch_array($res))
	$adherent[$row['numero']] = $row['nom'];

$stats = array(); // format $adherent[056089] = 56 ;

if ($stream = join('',file('http://www.google.com/calendar/ical/oi3c84064vjruvmkrsbbgn69go%40group.calendar.google.com/private-b5ad0090953fcf19110ac0be6aaeb152/basic.ics'))) { // telecharge le fichier chez google
	
	$ical = new iCal();
	$events = $ical->iCalStreamDecoder($stream);
	
	foreach ($events as $e) {
		if (	array_key_exists('SUMMARY',$e)					// début d'evenement
			&&	preg_match('/^(?:RDV|VISITE|PROSPECT)/i',$e['SUMMARY'])	// RDV, VISITE ou PROSPECT
			&&	preg_match('/(0?56\d{3})/',$e['SUMMARY'],$regs)	// un adhérent est renseigné
			) {
		
			if (strlen($regs[1]) == 5) $regs[1] = '0'.$regs[1];
			//echo $e['SUMMARY']." ".$regs[1]."\n";

			if (isset($stats[$regs[1]]))
				$stats[$regs[1]]++;
			else
				$stats[$regs[1]] = 1;

		} // fin if RDV|VISITE|PROSPECT
	} 
} else {
	die("Impossible de récupérer le fichier des calendrier");
}

arsort($stats); // classe les RDV par ordre croissant

//print_r($stats);exit;

// Setup the graph
$datay = array(); //array(2,3,5,8,12,6,3);
$datax = array(); //array("Jan","Feb","Mar","Apr","May","Jun","Jul");
foreach ($stats as $key => $val) {
	$datay[] = $val;
	if (isset($adherent[$key]))
		$datax[] = $adherent[$key]." ($key)";
	else
		$datax[] = "Inconnu ($key)";
}
//print_r($datax);
//print_r($datay);

// Set the basic parameters of the graph 
$graph = new Graph(1200,1000,'auto');
$graph->SetScale('textlin');
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->Set90AndMargin(0,10,30,10); // Rotate graph 90 degrees and set margin
$graph->title->Set('Rapport par adhérent');

$graph->xaxis->SetTickLabels($datax);
//$graph->xaxis->SetFont(FF_ARIAL,FS_NORMAL,7);
$graph->xaxis->SetLabelMargin(5);
$graph->xaxis->SetLabelAlign('right','center');

$graph->yaxis->SetLabelAlign('center','bottom');
$graph->yaxis->Hide();

$bplot = new BarPlot($datay);
$bplot->SetFillColor('orange');
$bplot->SetWidth(0.5);

$bplot->value->Show();
$bplot->value->SetFont(FF_ARIAL,FS_NORMAL,9);
$bplot->value->SetAlign('left','center');
$bplot->value->SetColor("black","darkred");
$bplot->value->SetFormat('%d rdv');

// Add the bar to the graph
$graph->Add($bplot);

// Output line
$graph->Stroke();
?>