<?

$feries = array();

// FERIE 2009
$feries['2009'] = array(
	'0101',		// 1er de l'an
	'0413',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0521',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1111',		// 11 novembre
	'1225'		// noel
);

// FERIE 2010
$feries['2010'] = array(
	'0101',		// 1er de l'an
	'0405',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0513',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1101',		// toussain
	'1111',		// 11 novembre
	'1225'		// noel
);

// FERIE 2011
$feries['2011'] = array(
	'0101',		// 1er de l'an
	'0425',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0602',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1101',		// toussain
	'1111',		// 11 novembre
	'1225'		// noel
);

// FERIE 2012
$feries['2012'] = array(
	'0101',		// 1er de l'an
	'0409',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0517',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1101',		// toussain
	'1111',		// 11 novembre
	'1225'		// noel
);

// FERIE 2013
$feries['2013'] = array(
	'0101',		// 1er de l'an
	'0401',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0509',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1101',		// toussain
	'1111',		// 11 novembre
	'1225'		// noel
);

// FERIE 2014
$feries['2014'] = array(
	'0101',		// 1er de l'an
	'0421',		// lundi de paques
	'0501',		// 1er mai
	'0508',		// 8 mai
	'0529',		// jeudi de l'ascension
	'0714',		// 14 juillet
	'0815',		// 15 aout
	'1101',		// toussain
	'1111',		// 11 novembre
	'1225'		// noel
);


// vérifie que l'on a bien choisit une année
$year = '';
if (isset($_POST['annee']) && array_key_exists($_POST['annee'],$feries)) {
	$year = $_POST['annee'];
} else {
	echo "Les jours fériés pour l'année '$_POST[annee]' n'ont pas encore été renseigné";
	exit;
}

// vérifie que l'on a bien choisit une personne
$personne = '';
if (isset($_POST['who']) && $_POST['who']) {
	$personne = $_POST['who'];
} else {
	echo "Personne n'a été renseigné";
	exit;
}



//echo "<pre>"; print_r($_POST); echo "</pre>";



$holidays_payed_ranges = array();
$holidays_not_payed_ranges = array();
$illness_ranges = array();

if (isset($_POST['mois_start_paye']) && isset($_POST['mois_end_paye']))
	for($i=0 ; $i<sizeof($_POST['mois_start_paye']) ; $i++) {
		$holidays_payed_ranges[] = array(	sprintf('%02d%02d',$_POST['mois_start_paye'][$i],$_POST['jour_start_paye'][$i]),
											sprintf('%02d%02d',$_POST['mois_end_paye'][$i],$_POST['jour_end_paye'][$i]));
	}

if (isset($_POST['mois_start_sans']) && isset($_POST['mois_end_sans']))
	for($i=0 ; $i<sizeof($_POST['mois_start_sans']) ; $i++) {
		$holidays_not_payed_ranges[] = array(	sprintf('%02d%02d',$_POST['mois_start_sans'][$i],$_POST['jour_start_sans'][$i]),
												sprintf('%02d%02d',$_POST['mois_end_sans'][$i],$_POST['jour_end_sans'][$i]));
	}

if (isset($_POST['mois_start_malade']) && isset($_POST['mois_end_malade']))
	for($i=0 ; $i<sizeof($_POST['mois_start_malade']) ; $i++) {
		$illness_ranges[] = array(	sprintf('%02d%02d',$_POST['mois_start_malade'][$i],$_POST['jour_start_malade'][$i]),
									sprintf('%02d%02d',$_POST['mois_end_malade'][$i],$_POST['jour_end_malade'][$i]));
	}

//echo "<pre>"; print_r($holidays_payed_ranges); echo "</pre>";
//echo "<pre>"; print_r($holidays_not_payed_ranges); echo "</pre>";
//echo "<pre>"; print_r($illness_ranges); echo "</pre>";



require_once('../../inc/fpdf/fpdf.php');
define('PAGE_WIDTH',210);
define('PAGE_HEIGHT',297);

$holidays_payed = array();
foreach ($holidays_payed_ranges as $range) {
	for($start=$range[0] ; $start<=$range[1] ; $start++) {
		$holidays_payed[] = $year.sprintf('%04d',$start) ; // ajoute la date dans la période de congé
	}
}

$holidays_not_payed = array();
foreach ($holidays_not_payed_ranges as $range) {
	for($start=$range[0] ; $start<=$range[1] ; $start++) {
		$holidays_not_payed[] = $year.sprintf('%04d',$start) ; // ajoute la date dans la période de congé
	}
}

$illness = array();
foreach ($illness_ranges as $range) {
	for($start=$range[0] ; $start<=$range[1] ; $start++) {
		$illness[] = $year.sprintf('%04d',$start) ; // ajoute la date dans la période de congé
	}
}

for($start=0 ; $start < sizeof($feries[$year]) ; $start++) {
	$feries[$year][$start] = $year.$feries[$year][$start];
}

//print_r($feries); exit;

//print_r($_GET); exit;

// génération du doc PDF
$pdf=new FPDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->AliasNbPages();

$last_week = 0;
$number_of_hour_worked_in_week = 0;
for($day=1 ; $day<=365 ; $day++) {

	$date_day = mktime(0 , 0, 0, 1, 1 + $day -1 , $year); # h,m,s,M,J,Y

	$day_in_week	= date('w',$date_day);
	if ($day_in_week == 0 || $day_in_week == 6) { continue; } // samedi ou dimanche

	$week_in_year	= date('W',$date_day);

	if ($last_week != $week_in_year) {
		end_of_week();

		// nouvelle semaine
		$number_of_hour_worked_in_week = 0 ;
		$pdf->AddPage();

		// ajout de la trame de fond pré rempli
		$pdf->Image('background/'.$personne.'.jpg',0 ,0, PAGE_WIDTH, PAGE_HEIGHT); // image en pleine page
		$last_week = $week_in_year;
	}

	

	// ajout des infos variables
	// n° des jours
	$pdf->SetFont('helvetica','',8);
	$pdf->SetX(1); // x

	// pour chaque jour de la semaine ouvrable
	$pdf->SetY(87 + 8.45 * ($day_in_week - 1)); // y 
	$pdf->Cell(0,10,date('d/m/Y',$date_day),0); // w, h, text, border

	// gestion des congés payés
	if (in_array(date('Ymd',$date_day),$holidays_payed) ||
		in_array(date('Ymd',$date_day),$holidays_not_payed) ||
		in_array(date('Ymd',$date_day),$illness) ||
		in_array(date('Ymd',$date_day),$feries[$year])
		) { // si c'est un jour de congés
		// on efface les cases d'heure pré-rempli sur le background
		$pdf->SetFillColor(255,255,255); // white
		$pdf->Rect(	26,										// x
					86 + 8.45 * ($day_in_week - 1),			// y
					27,										// w
					7.4,										// h
					'F'										// fill
			); // w, h, text, border

		$pdf->Rect(	82,										// x
					86 + 8.45 * ($day_in_week - 1),			// y
					27,										// w
					7.4,										// h
					'F'										// fill
			); // w, h, text, border

		$pdf->Rect(	111,										// x
					86 + 8.45 * ($day_in_week - 1),			// y
					27,										// w
					7.4,										// h
					'F'										// fill
			); // w, h, text, border

		// on coche la case congés payés
		if			(in_array(date('Ymd',$date_day),$holidays_payed)) {		// congés payé
			$pdf->SetXY(160, 83 + 8.45 * ($day_in_week - 1) ); // x,y
		} elseif	(in_array(date('Ymd',$date_day),$holidays_not_payed)) {	// congés sans solde
			$pdf->SetXY(178.5, 83 + 8.45 * ($day_in_week - 1) ); // x,y
		} elseif	(in_array(date('Ymd',$date_day),$illness)) {	// congés sans solde
			$pdf->SetXY(169.25, 83 + 8.45 * ($day_in_week - 1) ); // x,y
		} elseif	(in_array(date('Ymd',$date_day),$feries[$year])) {	// ferié
			$pdf->SetXY(141, 83 + 8.45 * ($day_in_week - 1) ); // x,y
		}

		$pdf->SetFont('helvetica','',12);
		$pdf->Cell(0,10,'X',0); // w, h, text, border

	} else {
		// jour travaillé
		$number_of_hour_worked_in_week += ($day_in_week==5 ? 7 : 8) ; // vendredi 7h uniquement
	}

} // for week

end_of_week();

$pdf->Output('feuille_temps('.crc32(uniqid()).').pdf','I');





function end_of_week() {
	// marque le nomber d'heure total travaillé
	global $pdf,$number_of_hour_worked_in_week,$date_day;

	// n° de la semaine
	$pdf->SetFont('helvetica','B',16);
	$pdf->SetXY(160,49); // x, y 
	$pdf->Cell(0,10,date('W (Y)',$date_day),0); // w, h, text, border

	$pdf->SetFillColor(255,255,255); // white
	$pdf->Rect(	111,									// x
				86 + 8.45 * 7,							// y
				27,										// w
				7,									// h
				'F'										// fill
		); // w, h, text, border
	$pdf->SetFont('helvetica','B',14);
	$pdf->SetXY(116,86 + 8.45 * 7); // x, y 
	$pdf->Cell(0,10,$number_of_hour_worked_in_week.'h',0); // w, h, text, border
}

?>