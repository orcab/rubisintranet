<?

include('../inc/config.php');
include('google_calendar.php');
include('../inc/iCalParser/ical-parser-class.php');
include ('../inc/jpgraph/src/jpgraph.php');
include ('../inc/jpgraph/src/jpgraph_pie.php');

define('PLOMBIER',   1 << 0);
define('ELECTRICIEN',1 << 1);


// PARAMETRE "DATE_START"
$date_start = isset($_GET['date_start'])	? (int)str_replace('-','',$_GET['date_start'])	: '' ; // $date_start = 200805 (int)

// PARAMETRE "DATE_END"
$date_end	= isset($_GET['date_end'])		? (int)str_replace('-','',$_GET['date_end'])	: '' ;// $date_end = 201106 (int)


$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

// charge le nom des adhérents en mémoire
$adherent = array();  // format $adherent[056089] = array('Nom adhérent',activite adhérent) ;
$res  = mysql_query('SELECT numero,activite FROM artisan');
while ($row = mysql_fetch_array($res))
	$adherent[$row['numero']] = $row['activite'];

$stats = array(0,0,0,0); // unknow, nb plom, nb elect, nb both

if ($stream = join('',file($google_calendar_expo))) { // telecharge le fichier chez google
//if ($stream = join('',file('expo.ics'))) { // telecharge le fichier chez google
	
	$ical = new iCal();
	$events = $ical->iCalStreamDecoder($stream);
	
	foreach ($events as $e) {
		if (	array_key_exists('SUMMARY',$e)					// début d'evenement
			&&	preg_match('/^(?:RDV|VISITE?|PROSPECT)/i',$e['SUMMARY'])	// RDV, VISITE ou PROSPECT
			&&	preg_match('/(0?56\d{3})/',$e['SUMMARY'],$regs)	// un adhérent est renseigné
			) {
		
			$adh = $regs[1];


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

			if (strlen($adh) == 5) $adh = '0'.$adh;
			//echo $e['SUMMARY']." ".$regs[1]."\n";

			if (isset($adherent[$adh]))
				$stats[$adherent[$adh]]++; // on rajoute 1 à l'activité concernée

		} // fin if RDV|VISITE|PROSPECT
	} 
} else {
	die("Impossible de récupérer le fichier des calendrier");
}


// Set the basic parameters of the graph 
$graph = new PieGraph(600,600,'auto');
$graph->title->Set('Rendez-vous par activité');
$graph->title->SetFont(FF_FONT1,FS_BOLD);
$graph->SetShadow();

$pie = new PiePlot($stats);
$pie->SetLegends(array('Inconnu (%0.1f%%)','Plombier (%0.1f%%)','Electricien (%0.1f%%)','Multi-activitées (%0.1f%%)'));
$pie->SetCenter(0.4);

$pie->SetLabelType(PIE_VALUE_PER); 
$pie->SetLabels(array("Inconnu/%0.1f/$stats[0]","Plombier/%0.1f/$stats[1]","Electricien/%0.1f/$stats[2]","Multi-activitées/%0.1f/$stats[3]")); 
$pie->SetSliceColors(array('white','#71BBE3','#FCD700','#255E7D'));
$pie->SetLabelPos(0.75); 

$pie->value->SetFormatCallback('pieValueFormat'); 
function pieValueFormat($aLabel) {
	$tmp = explode('/',$aLabel);
    return "$tmp[0]\n$tmp[2] rdv ($tmp[1]%)";
}

$graph->Add($pie);
$graph->Stroke();

?>