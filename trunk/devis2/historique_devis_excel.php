<?
include('../inc/config.php');

error_reporting(E_ALL & ~E_DEPRECATED);
set_include_path(get_include_path().PATH_SEPARATOR.'../../inc'.PATH_SEPARATOR.'c:/EasyPHP/php/pear/'); // ajoute le chemin d'acces a Spreadsheet/Excel

$sql = isset($_GET['sql']) ? base64_decode(urldecode($_GET['sql'])) : '';

//echo "<div style='color:red;'><pre>$sql</pre></div>" ; exit;
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

	$res		= mysql_query($sql) or die("Ne peux pas trouver la liste des devis ".mysql_error()."<br>\n$sql");
	
	/*$fields = array();
	for($i=0 ; $i<mysql_num_fields($res) ; $i++) {
		$fields[] = mysql_field_name($res,$i);
	}
	print_r($fields);exit;*/

	$i=0;
	define('ID',$i++);
	define('NUM_DEVIS',$i++);
	define('DATE_DEVIS',$i++);
	define('REPRESENTANT',$i++);
	define('CLIENT',$i++);
	define('VILLE',$i++);
	define('TELEPHONE',$i++);
	define('ARTISAN',$i++);
	define('MT_HT_DEVIS',$i++);
	define('MT_HT_CMD',$i++);
	define('CMD_RUBIS',$i++);
	define('NB_RELANCE',$i++);

	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send('historique_devis_mcs.xls');

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Historique devis expo MCS');
	$workbook->setCustomColor(12, 220, 220, 220);

	$format_title		=& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>12 , 'bordercolor'=>'black' ));
	$format_cell		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_article		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_article->setNumFormat('00000000');
	$format_pourcentage =& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>'12' , 'bordercolor'=>'black' ));
	$format_pourcentage->setNumFormat('0.0%');
	$format_prix		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_prix->setNumFormat('0.00€');
	$format_coef		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_coef->setNumFormat('0.00000');

	// La premiere ligne
	$worksheet->write(0,ID,				'Id',$format_title);							$worksheet->setColumn(ID,ID,5);
	$worksheet->write(0,NUM_DEVIS,		'N° du devis',$format_title);					$worksheet->setColumn(NUM_DEVIS,NUM_DEVIS,10);
	$worksheet->write(0,DATE_DEVIS,		'Date du devis',$format_title);					$worksheet->setColumn(DATE_DEVIS,DATE_DEVIS,12);
	$worksheet->write(0,REPRESENTANT, 	'Représentant',$format_title);					$worksheet->setColumn(REPRESENTANT,REPRESENTANT,10);
	$worksheet->write(0,CLIENT,			'Client',$format_title);						$worksheet->setColumn(CLIENT,CLIENT,25);
	$worksheet->write(0,VILLE,			'Ville',$format_title);							$worksheet->setColumn(VILLE,VILLE,15);
	$worksheet->write(0,TELEPHONE,		'Coordonnées',$format_title);					$worksheet->setColumn(TELEPHONE,TELEPHONE,20);
	$worksheet->write(0,ARTISAN,		'Artisan',$format_title);						$worksheet->setColumn(ARTISAN,ARTISAN,20);
	$worksheet->write(0,MT_HT_DEVIS,	'Mt Devis',$format_title);						$worksheet->setColumn(MT_HT_DEVIS,MT_HT_DEVIS,10);
	$worksheet->write(0,MT_HT_CMD,		'Mt Cmd',$format_title);						$worksheet->setColumn(MT_HT_CMD,MT_HT_CMD,10);
	$worksheet->write(0,CMD_RUBIS,		'Cmd Rubis',$format_title);						$worksheet->setColumn(CMD_RUBIS,CMD_RUBIS,10);
	$worksheet->write(0,NB_RELANCE,		'Nb Relances',$format_title);					$worksheet->setColumn(NB_RELANCE,NB_RELANCE,4);

	$i = 1;
	while($row = mysql_fetch_array($res)) {
		$worksheet->write( $i, ID,				$row['id']									,$format_cell);
		$worksheet->write( $i, NUM_DEVIS,		$row['numero']								,$format_cell);
		$worksheet->write( $i, DATE_DEVIS ,		$row['date_formater']						,$format_cell); 
		$worksheet->write( $i, REPRESENTANT ,	$row['representant']						,$format_cell); 
		$worksheet->write( $i, CLIENT,			my_utf8_decode($row['nom_client'])			,$format_cell); 
		$worksheet->write( $i, VILLE,			my_utf8_decode($row['ville_client'])		,$format_cell);
		$worksheet->write( $i, TELEPHONE,		$row['tel_client']."\n".$row['tel_client2']."\n".$row['email_client']	,$format_cell);
		$worksheet->write( $i, ARTISAN,			my_utf8_decode($row['artisan'])				,$format_cell);	
		$worksheet->write( $i, MT_HT_DEVIS,		$row['ptht']								,$format_prix);	
		$worksheet->write( $i, MT_HT_CMD,		$row['mtht_cmd_rubis']						,$format_prix);	
		$worksheet->write( $i, CMD_RUBIS,		$row['num_cmd_rubis']						,$format_cell);	
		$worksheet->write( $i, NB_RELANCE,		$row['nb_relance']							,$format_cell);	

		$i++;
	}

	// on rajoute les différences global
	$worksheet->write( $i, ARTISAN,		"Total"  ,$format_title);
	$worksheet->writeFormula($i, MT_HT_DEVIS,	'=SUM('.excel_column(MT_HT_DEVIS).'2:'.excel_column(MT_HT_DEVIS).$i.')' ,$format_prix);
	$worksheet->writeFormula($i, MT_HT_CMD,		'=SUM('.excel_column(MT_HT_CMD).'2:'.excel_column(MT_HT_CMD).$i.')' ,$format_prix);

	// Let's send the file
	$workbook->close();
	exit;


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