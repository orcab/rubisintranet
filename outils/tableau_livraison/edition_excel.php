<?
include('../../inc/config.php');
require_once('etat.php');

set_include_path(get_include_path().PATH_SEPARATOR.'../../inc'); // ajoute le chemin d'acces a Spreadsheet/Excel
require_once '../../inc/Spreadsheet/Excel/Writer.php';

if (!(isset($_GET['id']) && $_GET['id'])) {
	echo "ERREUR : Aucun N° de cde précisé.";
 	exit;
}

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
try {
	$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
} catch (PDOException $exception) {
	die ($exception->getMessage());
}

$id_escape = mysql_escape_string($_GET['id']);

$sql_entete = <<<EOT
SELECT	id,numero_bon,numero_artisan,date_bon,date_liv,vendeur,reference,montant,
		vendeurs.nom AS nom_vendeur
FROM				cde_rubis
		LEFT JOIN	vendeurs
			ON cde_rubis.vendeur=vendeurs.code
WHERE	id_bon='$id_escape'
LIMIT	0,1
EOT;

$sql_detail = <<<EOT
SELECT	code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat,date_dispo
FROM	cde_rubis_detail
WHERE	id_bon='$id_escape'
ORDER BY id ASC
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$entete_commande= $sqlite->query($sql_entete) or die("Impossible de recuperer l'entete du bon : ".array_pop($sqlite->errorInfo()));
$row_entete		= $entete_commande->fetch(PDO::FETCH_ASSOC);
$row_entete		= array_map('utf8_decode',$row_entete);
$detail_commande= $sqlite->query($sql_detail) or die("Impossible de recuperer le détail du bon : ".array_pop($sqlite->errorInfo()));


// création du fichier excel
$i=0;
define('BON_CODE_MCS',$i++);
define('BON_FOURNISSEUR',$i++);
define('BON_REF_FOURNISSEUR',$i++);
define('BON_DESIGNATION',$i++);
define('BON_UNITE',$i++);
define('BON_QTE',$i++);
define('BON_PUHT',$i++);
define('BON_TOTALHT',$i++);
define('BON_SPECIAL',$i++);
define('BON_DISPO',$i++);
define('BON_LIVRE',$i++);
define('BON_DATE_DISPO',$i++);
define('BON_NUM_BON',$i++);
define('BON_DATE_BON',$i++);
define('BON_DATE_LIV',$i++);
define('BON_SUIVI_PAR',$i++);
define('BON_REF_BON',$i++);

$workbook = new Spreadsheet_Excel_Writer();

// sending HTTP headers
$workbook->send("commande_mcs_${id_escape}.xls");

// Creating a worksheet
$worksheet =& $workbook->addWorksheet($id_escape);
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

// entete
$worksheet->write(0,BON_CODE_MCS, 		'Code MCS',$format_title);			$worksheet->setColumn(BON_CODE_MCS,BON_CODE_MCS,10);
$worksheet->write(0,BON_FOURNISSEUR,	'Fournisseur',$format_title);		$worksheet->setColumn(BON_FOURNISSEUR,BON_FOURNISSEUR,20);
$worksheet->write(0,BON_REF_FOURNISSEUR,'Ref fournisseur',$format_title);	$worksheet->setColumn(BON_REF_FOURNISSEUR,BON_REF_FOURNISSEUR,15);
$worksheet->write(0,BON_DESIGNATION, 	'Désignation',$format_title);		$worksheet->setColumn(BON_DESIGNATION,BON_DESIGNATION,30);
$worksheet->write(0,BON_UNITE, 			'Un',$format_title);				$worksheet->setColumn(BON_UNITE,BON_UNITE,5);
$worksheet->write(0,BON_QTE, 			'Qte',$format_title);				$worksheet->setColumn(BON_QTE,BON_QTE,5);
$worksheet->write(0,BON_PUHT, 			'P.U. HT',$format_title);			$worksheet->setColumn(BON_PUHT,BON_PUHT,10);
$worksheet->write(0,BON_TOTALHT, 		'Total HT',$format_title);			$worksheet->setColumn(BON_TOTALHT,BON_TOTALHT,10);
$worksheet->write(0,BON_SPECIAL, 		'S',$format_title);					$worksheet->setColumn(BON_SPECIAL,BON_SPECIAL,5);
$worksheet->write(0,BON_DISPO, 			'D',$format_title);					$worksheet->setColumn(BON_DISPO,BON_DISPO,5);
$worksheet->write(0,BON_LIVRE, 			'L',$format_title);					$worksheet->setColumn(BON_LIVRE,BON_LIVRE,5);
$worksheet->write(0,BON_DATE_DISPO, 	'Date dispo',$format_title);		$worksheet->setColumn(BON_DATE_DISPO,BON_DATE_DISPO,10);
$worksheet->write(0,BON_NUM_BON, 		'N° bon',$format_title);			$worksheet->setColumn(BON_NUM_BON,BON_NUM_BON,10);
$worksheet->write(0,BON_DATE_BON, 		'Date',$format_title);				$worksheet->setColumn(BON_DATE_BON,BON_DATE_BON,10);
$worksheet->write(0,BON_DATE_LIV, 		'Date livraison',$format_title);	$worksheet->setColumn(BON_DATE_LIV,BON_DATE_LIV,10);
$worksheet->write(0,BON_SUIVI_PAR, 		'Suivi par',$format_title);			$worksheet->setColumn(BON_SUIVI_PAR,BON_SUIVI_PAR,15);
$worksheet->write(0,BON_REF_BON, 		'Référence bon',$format_title);		$worksheet->setColumn(BON_REF_BON,BON_REF_BON,40);

$i = 1;
while($row = $detail_commande->fetch(PDO::FETCH_ASSOC)) {
	
	$dispo = '';
	if (!($row['etat'] & ETAT_COMMENTAIRE)) {	// si pas un com'
		if ($row['etat'] & ETAT_SPECIAL) {		// si un spécial
			if ($row['date_dispo']) {			// si receptionné
				$dispo = 'D';
			}
		} else { // matériel stocké
			$dispo = 'D';
		}
	}

	$livre = '';
	if ($row['etat'] & ETAT_LIVRE) { // produit deja livré
		$dispo = '';
		$livre = 'L';
	}

	$worksheet->write( $i, BON_CODE_MCS,		$row['code_article']	,$format_article);
	$worksheet->write( $i, BON_FOURNISSEUR,		$row['fournisseur']	,$format_cell);
	$worksheet->write( $i, BON_REF_FOURNISSEUR,	$row['ref_fournisseur'] ,$format_cell);
	$worksheet->write( $i, BON_DESIGNATION,		str_replace('\n',' ',$row['designation'])	,$format_cell);
	$worksheet->write( $i, BON_UNITE,			$row['unit']	,$format_cell);
	$worksheet->write( $i, BON_QTE,				$row['qte'] > 0 ? ereg_replace('\.0$','',$row['qte']) : ''	,$format_cell);
	$worksheet->write( $i, BON_PUHT,			$row['qte'] > 0 ? sprintf('%0.2f',round($row['prix'],2)):''	,$format_prix);
	$worksheet->writeFormula($i, BON_TOTALHT,	'='.excel_column(BON_QTE).($i+1)." * ".excel_column(BON_PUHT).($i+1).")" ,$format_prix);
	$worksheet->write( $i, BON_SPECIAL,			$row['etat'] & ETAT_SPECIAL ? 'S':''	,$format_cell);
	$worksheet->write( $i, BON_DISPO,			$dispo	,$format_cell);
	$worksheet->write( $i, BON_LIVRE,			$livre	,$format_cell);
	$worksheet->write( $i, BON_DATE_DISPO,		join('/',array_reverse(explode('-',$row['date_dispo'])))	,$format_cell);
	$worksheet->write( $i, BON_NUM_BON,			$row_entete['numero_bon']	,$format_cell);
	$worksheet->write( $i, BON_DATE_BON,		join('/',array_reverse(explode('-',$row_entete['date_bon'])))	,$format_cell);
	$worksheet->write( $i, BON_DATE_LIV,		join('/',array_reverse(explode('-',$row_entete['date_liv'])))	,$format_cell);
	$worksheet->write( $i, BON_SUIVI_PAR,		$row_entete['nom_vendeur'],$format_cell);
	$worksheet->write( $i, BON_REF_BON,			$row_entete['reference']	,$format_cell);

	$i++;
}

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