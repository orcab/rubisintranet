<?
include('../../inc/config.php');
require_once('etat.php');

set_include_path(get_include_path().PATH_SEPARATOR.'../../inc'); // ajoute le chemin d'acces a Spreadsheet/Excel
require_once '../../inc/Spreadsheet/Excel/Writer.php';

if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
try {
	$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
	$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
} catch (PDOException $exception) {
	die ($exception->getMessage());
}

$sql = isset($_GET['sql']) ? base64_decode(urldecode($_GET['sql'])) : '';

//echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$res = $sqlite->query($sql) or die("Impossible de lancer la requete de selection des bons : ".array_pop($sqlite->errorInfo()));
	
	$i=0;
	define('NUM_BON',$i++);
	define('DATE_BON',$i++);
	define('DATE_LIV',$i++);
	define('REFERENCE',$i++);
	define('NB_LIGNE',$i++);
	define('NB_LIVRE',$i++);
	define('NB_DISPO',$i++);
	define('MONTANT',$i++);
	define('MONTANT_DISPO',$i++);

	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send('historique_commande_mcs.xls');

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Historique commande MCS');
	$workbook->setCustomColor(12, 220, 220, 220);

	$format_title						=& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>12 , 'bordercolor'=>'black' ));
	$format_cell						=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_cell_pas_dispo				=& $workbook->addFormat(array('bordercolor'=>'black','fgcolor'=>'red','color'=>'white' ));
	$format_cell_partiellement_dispo	=& $workbook->addFormat(array('bordercolor'=>'black','fgcolor'=>'orange','color'=>'white' ));
	$format_cell_dispo					=& $workbook->addFormat(array('bordercolor'=>'black','fgcolor'=>'green','color'=>'white' ));
	$format_article						=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_article->setNumFormat('00000000');
	$format_pourcentage					=& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>'12' , 'bordercolor'=>'black' ));
	$format_pourcentage->setNumFormat('0.0%');
	$format_prix						=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_prix->setNumFormat('0.00€');
	$format_prix_title					=& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>12 , 'bordercolor'=>'black' ));
	$format_prix_title->setNumFormat('0.00€');
	$format_coef						=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_coef->setNumFormat('0.00000');

	// La premiere ligne
	$worksheet->write(0,NUM_BON,		'N° du bon',$format_title);
	$worksheet->write(0,DATE_BON, 		'Date du bon',$format_title);		$worksheet->setColumn(DATE_BON,DATE_BON,20);
	$worksheet->write(0,DATE_LIV, 		'Date de livraison',$format_title); $worksheet->setColumn(DATE_LIV,DATE_LIV,20);
	$worksheet->write(0,REFERENCE,		'Référence',$format_title);			$worksheet->setColumn(REFERENCE,REFERENCE,25);
	$worksheet->write(0,NB_LIGNE,		'Nombre de ligne',$format_title);
	$worksheet->write(0,NB_LIVRE,		'Livrées',$format_title);
	$worksheet->write(0,NB_DISPO,		'Dispo',$format_title);
	$worksheet->write(0,MONTANT,		'Montant',$format_title);			$worksheet->setColumn(MONTANT,MONTANT,20);
	$worksheet->write(0,MONTANT_DISPO,	'Montant disponible',$format_title);$worksheet->setColumn(MONTANT_DISPO,MONTANT_DISPO,20);

	$i = 1;
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		$worksheet->write( $i, NUM_BON,			$row['numero_bon']		,$format_cell);
		$worksheet->write( $i, DATE_BON ,		$row['date_bon']		,$format_cell); 
		$worksheet->write( $i, DATE_LIV ,		$row['date_liv']		,$format_cell); 
		$worksheet->write( $i, REFERENCE,		$row['reference']		,$format_cell); 
		$worksheet->write( $i, NB_LIGNE,		$row['nb_ligne']		,$format_cell);

		$format = '';
		if		($row['nb_livre'] <= 0)					$format = $format_cell_pas_dispo;
		elseif	($row['nb_livre'] <  $row['nb_ligne'])	$format = $format_cell_partiellement_dispo;
		elseif	($row['nb_livre'] >= $row['nb_ligne'])	$format = $format_cell_dispo;
		$worksheet->write( $i, NB_LIVRE,		$row['nb_livre']		,$format);

		$format = '';
		if		($row['nb_dispo'] <= 0)					$format = $format_cell_pas_dispo;
		elseif	($row['nb_dispo'] <  $row['nb_ligne'])	$format = $format_cell_partiellement_dispo;
		elseif	($row['nb_dispo'] >= $row['nb_ligne'])	$format = $format_cell_dispo;
		$worksheet->write( $i, NB_DISPO,		$row['nb_dispo']		,$format);

		$worksheet->write( $i, MONTANT,			$row['montant']			,$format_prix);
		$worksheet->write( $i, MONTANT_DISPO,	$row['montant_dispo']	,$format_prix);	

		$i++;
	}

	// on rajoute les différences global
	$worksheet->write(		 $i, REFERENCE,		"Total"  ,$format_title);
	$worksheet->writeFormula($i, NB_LIGNE,		'=SUM('.excel_column(NB_LIGNE).		'2:'.excel_column(NB_LIGNE).$i.')'		,$format_title);
	$worksheet->writeFormula($i, NB_LIVRE,		'=SUM('.excel_column(NB_LIVRE).		'2:'.excel_column(NB_LIVRE).$i.')'		,$format_title);
	$worksheet->writeFormula($i, NB_DISPO,		'=SUM('.excel_column(NB_DISPO).		'2:'.excel_column(NB_DISPO).$i.')'		,$format_title);
	$worksheet->writeFormula($i, MONTANT,		'=SUM('.excel_column(MONTANT).		'2:'.excel_column(MONTANT).$i.')'		,$format_prix_title);
	$worksheet->writeFormula($i, MONTANT_DISPO,	'=SUM('.excel_column(MONTANT_DISPO).'2:'.excel_column(MONTANT_DISPO).$i.')'	,$format_prix_title);

	// Let's send the file
	$workbook->close();
	unset($sqlite);
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