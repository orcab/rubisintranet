<?

include('../inc/config.php');
include('google_calendar.php');
include('../inc/iCalParser/ical-parser-class.php');
include('../inc/jpgraph/src/jpgraph.php');
include('../inc/jpgraph/src/jpgraph_line.php');
include('../inc/jpgraph/src/jpgraph_bar.php');


$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$mois = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

define('RDV',4);
define('VISITE',5);
define('PROSPECT',6);


// PARAMETRE "DATE_START"
$date_start = isset($_GET['date_start'])	? (int)str_replace('-','',$_GET['date_start'])	: '' ; // $date_start = 200805 (int)

// PARAMETRE "DATE_END"
$date_end	= isset($_GET['date_end'])		? (int)str_replace('-','',$_GET['date_end'])	: '' ;// $date_end = 201106 (int)


// chargement des données rdv et visite
	//if ($stream = join('',file('basic.ics'))) { // telecharge le fichier chez google
	if ($stream = join('',file($google_calendar_expo))) { // telecharge le fichier chez google
		
		$ical = new iCal();
		$events = $ical->iCalStreamDecoder($stream);

		//print_r($events);

		foreach ($events as $e) {
			if (array_key_exists('SUMMARY',$e) && preg_match('/^(RDV|VISITE|PROSPECT)/i',$e['SUMMARY'],$regs)) { //SUMMARY,DTSTART
				//on traite le rdv ou visite
				$type = strtoupper($regs[1]);

				$nom_cle_start = '';
				foreach($e as $key=>$val) {
						if (substr($key,0,7) == 'DTSTART') {
							$nom_cle_start = $key;
							break;
						}
				}

				$date_annee = substr($e[$nom_cle_start],0,4) ;
				$date_mois = substr($e[$nom_cle_start],4,2) ;
				
				$date_event = (int)($date_annee.$date_mois);
				//echo "EVENT date='$date_event' start=($date_start) end=($date_end)\n<br>";
				if ($date_start && $date_end && ($date_event < $date_start || $date_event > $date_end)) { // on rejette
					//echo "Date d'event hors limit --> rejette\n<br>";
					continue;
				}


				//$date = $mois[$date_mois - 1].' '.$date_annee ;
				$date = $date_annee.'-'.$date_mois;
				
				if ($type == 'RDV') {
					if (isset($cmd_rubis[$date][RDV]))
						$cmd_rubis[$date][RDV] += 1;
					else
						$cmd_rubis[$date][RDV] = 1;
				} elseif ($type == 'VISITE') {
					if (isset($cmd_rubis[$date][VISITE]))
						$cmd_rubis[$date][VISITE] += 1;
					else
						$cmd_rubis[$date][VISITE] = 1;
				} elseif ($type == 'PROSPECT') {
					if (isset($cmd_rubis[$date][PROSPECT]))
						$cmd_rubis[$date][PROSPECT] += 1;
					else
						$cmd_rubis[$date][PROSPECT] = 1;
				}

			}
		}
	} else {
		die("Impossible de récupérer le fichier des calendrier");
	}



ksort($cmd_rubis); // classe les RDV par ordre croissant

$data_RDV = array();
foreach($cmd_rubis as $vals)
	$data_RDV[] = isset($vals[RDV]) ? $vals[RDV] : 0;

$data_VISITE = array();
foreach($cmd_rubis as $vals)
	$data_VISITE[] = isset($vals[VISITE]) ? $vals[VISITE] : 0;

$data_PROSPECT = array();
foreach($cmd_rubis as $vals)
	$data_PROSPECT[] = isset($vals[PROSPECT]) ? $vals[PROSPECT] : 0;


// transforme les valeur numrique des mois en string
$cmd_rubis_string = array();
foreach($cmd_rubis as $key => $val) {
	list($date_annee,$date_mois) = explode('-',$key);
	$cmd_rubis_string[] = $mois[$date_mois - 1].' '.$date_annee ;
}

//print_r($cmd_rubis);
//print_r($data_RDV);exit;


// Setup the graph
$graph = new Graph(1200,650);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,100,60);
$graph->title->Set('Evolution mois par mois');

$graph->SetScale('textlin');
$graph->yaxis->SetColor('darkgreen');
$graph->yaxis->title->Set("Visite / RDV /Prospect");
$graph->yaxis->SetTitlemargin(50);
$graph->yaxis->title->SetColor('darkgreen');

$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
$graph->xgrid->Show();
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetTickLabels($cmd_rubis_string);

$graph->legend->SetShadow('gray@0.4',5);
$graph->legend->SetPos(0.21,0,'right','top');


// Create the bar plots
$bar_rdv	= new BarPlot($data_RDV);
$bar_vis	= new BarPlot($data_VISITE);
$bar_pro	= new BarPlot($data_PROSPECT);

// Create the grouped bar plot
$acc_rdv_vis		= new AccBarPlot(array($bar_rdv,$bar_vis,$bar_pro));
$acc_rdv_vis->SetWidth(0.75);

$graph->Add($acc_rdv_vis);

// Create the rdv bar
$bar_rdv->SetFillColor('darkgreen');
$bar_rdv->SetLegend('RDV');
$bar_rdv->value->SetColor('#CCFFCC'); 
$bar_rdv->value->SetFormat('%d');
$bar_rdv->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_rdv->value->Show();

// Create the visite bar
$bar_vis->SetFillColor('green');
$bar_vis->SetLegend('VISITE');
$bar_vis->value->SetColor('#003300'); 
$bar_vis->value->SetFormat('%d');
$bar_vis->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_vis->value->Show();

// Create the prospect bar
$bar_pro->SetFillColor('lightgreen');
$bar_pro->SetLegend('PROSPECT');
$bar_pro->value->SetColor('#006600'); 
$bar_pro->value->SetFormat('%d');
$bar_pro->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_pro->value->Show();


// Output line
$graph->Stroke();


?>