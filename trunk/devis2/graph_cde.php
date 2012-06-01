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

$where = array();
$where[] = "artisan<>'EDITION'";

// PARAMETRE "REPRESENTANT"
if (isset($_GET['representant']) && $_GET['representant'] != 'tous')
	$where[] = "representant='".mysql_escape_string($_GET['representant'])."'";

// PARAMETRE "DATE_START"
if (isset($_GET['date_start']))
	$where[] = "`date`>='".mysql_escape_string($_GET['date_start'])."-01'";

// PARAMETRE "DATE_END"
if (isset($_GET['date_end']))
	$where[] = "`date`<='".mysql_escape_string($_GET['date_end'])."-31'";


$where = join(' AND ',$where);

//chargement des données
// calcul du taux de devis/cmd de la salle
$sql = <<<EOT
SELECT  DISTINCT ( DATE_FORMAT( `date` , '%b %Y' )) AS date_formater,
		DATE_FORMAT( `date` , '%Y%m' ) AS date_formater_ical,
		DATE_FORMAT( `date` , '%Y' ) AS annee,
        COUNT(id) AS nb_devis
FROM  devis
WHERE $where
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
	  AND $where
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


// Setup the graph
$graph = new Graph(1200,650);
$graph->SetMarginColor('white');
$graph->SetFrame(false);
$graph->SetMargin(70,50,100,60);
$graph->title->Set('Evolution mois par mois'.(isset($_GET['representant']) && $_GET['representant'] != 'tous' ? ' de '.ucfirst($_GET['representant']):''));

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
$graph->legend->SetPos(0,0,'right','top');



// Create the bar plots
$bar_cmd	= new BarPlot($data_COMMANDE);
$bar_devis	= new BarPlot($data_DEVIS);
$line_ca	= new LinePlot($data_MONTANT_COMMANDE);
$line_taux	= new LinePlot($data_TAUX);


// Create the grouped bar plot
$acc_cmd_devis		= new AccBarPlot(array($bar_devis,$bar_cmd));
$acc_cmd_devis->SetWidth(0.75);

$graph->Add($line_ca);
$graph->AddY2($acc_cmd_devis);
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