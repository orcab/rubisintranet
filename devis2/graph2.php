<?

include('../inc/config.php');
include('../inc/iCalParser/ical-parser-class.php');
include('../inc/jpgraph/src/jpgraph.php');
include('../inc/jpgraph/src/jpgraph_line.php');
include('../inc/jpgraph/src/jpgraph_bar.php');


$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$mois = array('Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec');

define('COMMANDE',0);
define('MONTANT_COMMANDE',1);
define('DEVIS',2);
define('TAUX',3);
define('RDV',4);
define('VISITE',5);
define('PROSPECT',6);

//chargement des données
// calcul du taux de devis/cmd de la salle
$sql = <<<EOT
SELECT  DISTINCT ( DATE_FORMAT( `date` , '%b %Y' )) AS date_formater,
		DATE_FORMAT( `date` , '%Y%m' ) AS date_formater_ical,
		DATE_FORMAT( `date` , '%Y' ) AS annee,
        COUNT(id) AS nb_devis
FROM  devis
WHERE artisan<>'EDITION'
GROUP BY date_formater
ORDER BY `date` ASC
EOT;

$res = mysql_query($sql) or die("Ne peux pas trouver le nombre de devis ".mysql_error());

$sql2 = <<<EOT
SELECT  DISTINCT ( DATE_FORMAT( `date` , '%b %Y' )) AS date_formater,
        COUNT(num_cmd_rubis) AS nb_cmd_rubis,
		SUM(mtht_cmd_rubis) AS montant_cmd
FROM devis
WHERE     num_cmd_rubis NOT LIKE 'ANNULE'
      AND num_cmd_rubis NOT LIKE 'SUSPENDU'
      AND num_cmd_rubis IS NOT NULL
      AND num_cmd_rubis <> ''
	  AND artisan<>'EDITION'
GROUP BY date_formater
ORDER BY `date` ASC
EOT;

$res2 = mysql_query($sql2) or die("Ne peux pas trouver le nombre de cmd ".mysql_error());
$cmd_rubis = array();
while($row = mysql_fetch_array($res2)) {
	$cmd_rubis[$row['date_formater']] = array($row['nb_cmd_rubis'],$row['montant_cmd']);
}

while($row = mysql_fetch_array($res)) {
	if(isset($cmd_rubis[$row['date_formater']])) {
		$cmd_rubis[$row['date_formater']][] = $row['nb_devis'];
		
	} else {
		$cmd_rubis[$row['date_formater']] = array(0,0,$row['nb_devis'],0);
	}
}

//print_r($cmd_rubis);

// Create the Pie Graph. 
$data_COMMANDE = array();
foreach($cmd_rubis as $vals)
	$data_COMMANDE[] = $vals[COMMANDE] ;

$data_MONTANT_COMMANDE = array();
foreach($cmd_rubis as $vals)
	$data_MONTANT_COMMANDE[] = $vals[MONTANT_COMMANDE] ;

$data_DEVIS = array();
foreach($cmd_rubis as $vals)
	$data_DEVIS[] = $vals[DEVIS] ;

$data_TAUX = array();
foreach($cmd_rubis as $vals)
	$data_TAUX[] = sprintf('%0.1f',$vals[COMMANDE]*100 / $vals[DEVIS]) ;





// chargement des données rdv et visite
$cumul = array('RDV' => array() , 'VISITE' => array() , 'PROSPECT' => array() );
$ftp = ftp_connect(FTP_RDV_HOST);

// Identification avec un nom d'utilisateur et un mot de passe
$login_result = ftp_login($ftp, FTP_RDV_USER, FTP_RDV_PASS);
// Vérification de la connexion
if ((!$ftp) || (!$login_result)) die("La connexion FTP a échoué !");


foreach (array('expo_archive.ics','expo.ics') as $fichier) {
	if (ftp_get($ftp, $fichier, $fichier, FTP_BINARY)) {
	   // le fichier est bien la, on le traite
		
		$ical = new iCal();
		$events = $ical->iCalDecoder($fichier);

		//print_r($events);

		foreach ($events as $e) {
			if (array_key_exists('SUMMARY',$e) && eregi('^(RDV|VISITE|PROSPECT)',$e['SUMMARY'],$regs)) { //SUMMARY,DTSTART
				//on traite le rdv ou visite
				$type = strtoupper($regs[1]);

				$nom_cle_start = '';
				foreach($e as $key=>$val) {
						if (substr($key,0,8) == 'DTSTART;') {
							$nom_cle_start = $key;
							break;
						}
				}

				$date_annee = substr($e[$nom_cle_start],0,4) ;
				$date_mois = substr($e[$nom_cle_start],4,2) ;

				$date = $mois[$date_mois - 1].' '.$date_annee ;
				
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
} // for each fichier

// Fermeture du flux FTP
ftp_close($ftp);



//$bar_rdv_commande = array($cumul['RDV'],$cumul['VISITE']);

//print_r($cmd_rubis);
//array_multisort($cumul['RDV'], SORT_ASC, SORT_STRING);
//print_r($cumul);

$data_RDV = array();
foreach($cmd_rubis as $vals)
	$data_RDV[] = isset($vals[RDV]) ? $vals[RDV] : 0;

$data_VISITE = array();
foreach($cmd_rubis as $vals)
	$data_VISITE[] = isset($vals[VISITE]) ? $vals[VISITE] : 0;

$data_PROSPECT = array();
foreach($cmd_rubis as $vals)
	$data_PROSPECT[] = isset($vals[PROSPECT]) ? $vals[PROSPECT] : 0;



// Setup the graph
$graph = new Graph(1200,650);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,100,60);
$graph->title->Set('Evolution mois par mois');

$graph->SetScale('textlin');
$graph->SetY2Scale('lin');
$graph->yaxis->SetColor('blue');
$graph->y2axis->SetColor('red');
$graph->yaxis->title->Set("Montant des commandes");
$graph->yaxis->SetTitlemargin(50);
$graph->yaxis->title->SetColor('navy');
$graph->y2axis->title->Set("Devis/Commande");
$graph->y2axis->title->SetColor('red');

$graph->yaxis->HideZeroLabel();
$graph->ygrid->SetFill(true,'#EFEFEF@0.5','#BBCCFF@0.5');
$graph->xgrid->Show();
$graph->xaxis->SetLabelAngle(90);
$graph->xaxis->SetTickLabels(array_keys($cmd_rubis));

$graph->legend->SetShadow('gray@0.4',5);
$graph->legend->SetPos(0.21,0,'right','top');



// Create the bar plots
$bar_cmd	= new BarPlot($data_COMMANDE);
$bar_devis	= new BarPlot($data_DEVIS);
$bar_rdv	= new BarPlot($data_RDV);
$bar_vis	= new BarPlot($data_VISITE);
$bar_pro	= new BarPlot($data_PROSPECT);
$line_ca	= new LinePlot($data_MONTANT_COMMANDE);
$line_taux	= new LinePlot($data_TAUX);


// Create the grouped bar plot
$acc_cmd_devis		= new AccBarPlot(array($bar_devis,$bar_cmd));
$acc_rdv_vis		= new AccBarPlot(array($bar_rdv,$bar_vis,$bar_pro));
$group_bar_cmd_rdv  = new GroupBarPlot (array($acc_cmd_devis ,$acc_rdv_vis));

$group_bar_cmd_rdv->SetWidth(0.75);


$graph->Add($line_ca);
$graph->AddY2($group_bar_cmd_rdv);
$graph->AddY2($line_taux);


// Create the commande bar
$bar_cmd->SetFillColor("red");
$bar_cmd->SetLegend('Commandes réalisées');
$bar_cmd->value->SetColor('#55000'); 
$bar_cmd->value->SetFormat('%d');
$bar_cmd->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_cmd->value->Show();


// Create the devis bar;
$bar_devis->SetFillColor("orange");
$bar_devis->SetLegend('Devis réalisés');
$bar_devis->value->SetColor('red'); 
$bar_devis->value->SetFormat('%d');
$bar_devis->value->SetFont( FF_FONT1, FS_BOLD); 
$bar_devis->value->Show();

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

// Create the CA line
$line_ca->SetColor('blue');
$line_ca->SetLegend('Montant des commandes');
$line_ca->value->SetColor('navy'); 
$line_ca->value->SetFormat('%d');
$line_ca->value->SetFont( FF_FONT1, FS_BOLD); 
$line_ca->value->Show();
$line_ca->SetBarCenter();
$line_ca->mark->SetTYPE( MARK_SQUARE);
$line_ca->mark->SetColor("navy");


// create TAUX line 
$line_taux->SetColor('purple');
$line_taux->SetLegend('Taux de tranformation devis/cmd');
$line_taux->value->SetColor('purple'); 
$line_taux->value->SetFormat('%d%%');
$line_taux->value->SetFont( FF_FONT1, FS_BOLD); 
$line_taux->value->Show();
$line_taux->SetBarCenter();
$line_taux->mark->SetTYPE( MARK_DIAMOND);
$line_taux->mark->SetColor('purple');
$line_taux->mark->SetFillColor('purple');

// Output line
$graph->Stroke();


?>