<?php

include('../inc/config.php');
include('../inc/iCalParser/ical-parser-class.php');
require_once 'Spreadsheet/Excel/Writer.php';

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$i=0;
define('COL_DATE',$i++);
define('COL_RDV',$i++);
define('COL_VISITE',$i++);
define('COL_PROSPECT',$i++);
define('COL_DEVIS_REALISE',$i++);
define('COL_CMD_REALISE',$i++);
define('COL_RATIO',$i++);
define('COL_MT_CMD',$i++);

$cumul = array('RDV' => array() , 'VISITE' => array(),'PROSPECT' => array() );
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

				if (isset($e[$nom_cle_start])) {
					$date = substr($e[$nom_cle_start],0,6) ;
					
					if (isset($cumul[$type][$date]))
						$cumul[$type][$date] += 1;
					else
						$cumul[$type][$date] = 1;
				}
			}
		}
	} else {
		die("Impossible de récupérer le fichier des calendrier");
	}
} // for each fichier

// Fermeture du flux FTP
ftp_close($ftp);


// Creating a workbook
$workbook = new Spreadsheet_Excel_Writer();

// sending HTTP headers
$workbook->send('stat-expo.xls');

// Creating a worksheet
$worksheet =& $workbook->addWorksheet('Statistique de la salle expo');
$workbook->setCustomColor(12, 220, 220, 220);

$format_title =& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>12 , 'bordercolor'=>'black' ));
$format_cell  =& $workbook->addFormat(array('bordercolor'=>'black'));
$format_pourcentage  =& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>12 , 'bordercolor'=>'black' ));
$format_pourcentage->setNumFormat('0.0%');

// La premiere ligne
$worksheet->write(0,COL_RDV,			'RDV',$format_title);
$worksheet->write(0,COL_VISITE, 		'VISITE',$format_title);
$worksheet->write(0,COL_PROSPECT, 		'PROSPECT',$format_title);
$worksheet->write(0,COL_DEVIS_REALISE,	'Devis réalisé',$format_title);
$worksheet->write(0,COL_CMD_REALISE,	'Cmd passée',$format_title);
$worksheet->write(0,COL_RATIO,			'Ratio devis/cmd %',$format_title);
$worksheet->write(0,COL_MT_CMD,			'Montant des cmd',$format_title);
$worksheet->setColumn(0,COL_MT_CMD,17);


// calcul du taux de devis/cmd de la salle
$sql = <<<EOT
SELECT  DISTINCT ( DATE_FORMAT( `date` , '%b %Y' )) AS date_formater,
		DATE_FORMAT( `date` , '%Y%m' ) AS date_formater_ical,
		DATE_FORMAT( `date` , '%Y' ) AS annee,
        COUNT(id) AS nb_devis
FROM devis
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
GROUP BY date_formater
ORDER BY `date` ASC
EOT;

$res2 = mysql_query($sql2) or die("Ne peux pas trouver le nombre de cmd ".mysql_error());
$cmd_rubis = array();
while($row = mysql_fetch_array($res2)) {
	$cmd_rubis[$row['date_formater']] = array($row['nb_cmd_rubis'],$row['montant_cmd']);
}

$i = 1;
$old_year = 0;
$last_ligne = 2 ;
$ligne_annee = array();
while($row = mysql_fetch_array($res)) {
	if ($old_year != $row['annee'] && $i!=1) { # on change d'année, faire un calcul intermediaire (sauf pour le premier passage)
		array_push($ligne_annee,$i);
		write_total_ligne($old_year);
	}

	$worksheet->write( $i, COL_DATE ,			$row['date_formater'],$format_title);
	$worksheet->write( $i, COL_DEVIS_REALISE ,  $row['nb_devis'],$format_cell);
	$worksheet->write( $i, COL_CMD_REALISE , 	isset($cmd_rubis[$row['date_formater']][0]) ? $cmd_rubis[$row['date_formater']][0] : 0 ,$format_cell);
	$worksheet->write( $i, COL_MT_CMD , 		isset($cmd_rubis[$row['date_formater']][1]) ? $cmd_rubis[$row['date_formater']][1] : 0 ,$format_cell);
	$ligne = $i+1 ;
	$worksheet->writeFormula($i, COL_RATIO, '='.excel_column(COL_CMD_REALISE).$ligne.'/'.excel_column(COL_DEVIS_REALISE).$ligne ,$format_pourcentage);
	

	if (isset($cumul['RDV'][$row['date_formater_ical']])) {
		$worksheet->write( $i, COL_RDV , $cumul['RDV'][$row['date_formater_ical']],$format_cell);
	}
	if (isset($cumul['VISITE'][$row['date_formater_ical']])) {
		$worksheet->write( $i, COL_VISITE, $cumul['VISITE'][$row['date_formater_ical']],$format_cell);
	}
	if (isset($cumul['PROSPECT'][$row['date_formater_ical']])) {
		$worksheet->write( $i, COL_PROSPECT, $cumul['PROSPECT'][$row['date_formater_ical']],$format_cell);
	}

	$old_year = $row['annee'];
	$i++;
}

array_push($ligne_annee,$i);
write_total_ligne($old_year);




// ecrit le total final du cumul des années
$worksheet->write(			$i, COL_DATE,	"Total",$format_title);
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_RDV).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_RDV,	"=SUM($formula)",$format_title) ;
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_VISITE).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_VISITE,	"=SUM($formula)",$format_title) ;
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_PROSPECT).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_PROSPECT,"=SUM($formula)",$format_title) ;
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_DEVIS_REALISE).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_DEVIS_REALISE,	"=SUM($formula)",$format_title) ;
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_CMD_REALISE).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_CMD_REALISE,	"=SUM($formula)",$format_title) ;
$formula = '' ; foreach ($ligne_annee as $ligne) $formula .= excel_column(COL_MT_CMD).($ligne+1).';' ; $formula = ereg_replace(';+$','',$formula);
$worksheet->writeFormula(	$i, COL_MT_CMD,	"=SUM($formula)",$format_title) ;
//$col = excel_column($i) ;
$worksheet->writeFormula($i, COL_RATIO,		'='.excel_column(COL_CMD_REALISE).($i+1).'/'.excel_column(COL_DEVIS_REALISE).($i+1) ,$format_pourcentage);





// Let's send the file
$workbook->close();


function write_total_ligne($annee) {
	global $i,$worksheet,$last_ligne,$format_title,$format_pourcentage ;
	$ligne = $i ;
	$worksheet->write(		 $i,COL_DATE,			"Total $annee",$format_title);
	$worksheet->writeFormula($i,COL_RDV,			'=SUM('.excel_column(COL_RDV).$last_ligne.				':'.excel_column(COL_RDV).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_VISITE,			'=SUM('.excel_column(COL_VISITE).$last_ligne.			':'.excel_column(COL_VISITE).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_PROSPECT,		'=SUM('.excel_column(COL_PROSPECT).$last_ligne.			':'.excel_column(COL_PROSPECT).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_DEVIS_REALISE,	'=SUM('.excel_column(COL_DEVIS_REALISE).$last_ligne.	':'.excel_column(COL_DEVIS_REALISE).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_CMD_REALISE,	'=SUM('.excel_column(COL_CMD_REALISE).$last_ligne.		':'.excel_column(COL_CMD_REALISE).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_MT_CMD,			'=SUM('.excel_column(COL_MT_CMD).$last_ligne.			':'.excel_column(COL_MT_CMD).$i.')' ,$format_title);
	$worksheet->writeFormula($i,COL_RATIO,			'='.excel_column(COL_CMD_REALISE).($i+1).'/'.excel_column(COL_DEVIS_REALISE).($i+1) ,$format_pourcentage);

	$i++;
	$last_ligne = $i+1 ;
}


function excel_column($col_number) {
	if( ($col_number < 0) || ($col_number > 701)) die('Column must be between 0(A) and 701(ZZ)');
	if($col_number < 26) {
		return(chr(ord('A') + $col_number));
	} else {
		$remainder = floor($col_number / 26) - 1;
		return(chr(ord('A') + $remainder) . excel_column($col_number % 26));
	}
}

?>