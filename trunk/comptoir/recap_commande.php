<?
include('../inc/config.php');


session_start();
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if (isset($_POST['password']) && $_POST['password'])
	$_SESSION['password'] = $_POST['password'];


// on check si le mot de passe est rentré
$salt = 'sA2rJEwb14x7LM6u8Xn1e6b63Gx8u6H0';
$identifier = (isset($_SESSION['password']) && md5($_SESSION['password'].$salt) == 'c980c4e70f9daf19568b34c45e5f8b65') ? TRUE : FALSE ;


// on a demander une édition excel du tableau
if (isset($_GET['xls']) && $_GET['xls']==1 && $identifier === TRUE) {
	// on charge la lib excel
	set_include_path(get_include_path().PATH_SEPARATOR.'../inc'); // ajoute le chemin d'acces a Spreadsheet/Excel
	require_once '../inc/Spreadsheet/Excel/Writer.php';

	// on recupere la requette SQL
	$sql = isset($_GET['sql']) ? base64_decode(urldecode($_GET['sql'])) : '';
	
	$i=0;
	define('NUM_CDE',$i++);
	define('CODE_ADH',$i++);
	define('NOM_ADH',$i++);
	define('DATE_CDE',$i++);
	define('DATE_LIV',$i++);
	define('REFERENCE',$i++);
	define('ADRESSE',$i++);
	define('NOTE',$i++);
	define('MONTANT',$i++);
	define('NB_LIGNE',$i++);

	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send('recap_commande_web.xls');

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Commandes Web MCS');
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
	$worksheet->write(0,NUM_CDE,	'N°',$format_title);			$worksheet->setColumn(NUM_CDE,NUM_CDE,5);
	$worksheet->write(0,CODE_ADH, 	'Code Adh',$format_title);		
	$worksheet->write(0,NOM_ADH, 	'Nom Adh',$format_title);		$worksheet->setColumn(NOM_ADH,NOM_ADH,20);
	$worksheet->write(0,DATE_CDE,	'Date',$format_title);			$worksheet->setColumn(DATE_CDE,DATE_CDE,20);
	$worksheet->write(0,DATE_LIV,	'Date Liv.',$format_title);		$worksheet->setColumn(DATE_LIV,DATE_LIV,11);
	$worksheet->write(0,REFERENCE,	'Référence',$format_title);		$worksheet->setColumn(REFERENCE,REFERENCE,25);
	$worksheet->write(0,ADRESSE,	'Adresse',$format_title);		$worksheet->setColumn(ADRESSE,ADRESSE,30);
	$worksheet->write(0,NOTE,		'Note',$format_title);			$worksheet->setColumn(NOTE,NOTE,30);
	$worksheet->write(0,MONTANT,	'Montant',$format_title);		$worksheet->setColumn(MONTANT,MONTANT,10);
	$worksheet->write(0,NB_LIGNE,	'Nb Lig',$format_title);		$worksheet->setColumn(NB_LIGNE,NB_LIGNE,5);

	$res = mysql_query($sql) or die("Erreur dans la requette SQL (".mysql_error().") $sql") ; // selectionne les panier favori
	$i = 1;
	while ($row = mysql_fetch_array($res)) {
		$worksheet->write( $i, NUM_CDE,		$row['N°']	,$format_cell);
		$worksheet->write( $i, CODE_ADH ,	$row['Code Adh']	,$format_cell); 
		$worksheet->write( $i, NOM_ADH ,	$row['Nom Adh']		,$format_cell); 
		$worksheet->write( $i, DATE_CDE,	$row['Date']		,$format_cell); 
		$worksheet->write( $i, DATE_LIV,	$row['Date Liv.']	,$format_cell);
		$worksheet->write( $i, REFERENCE,	$row['Réf.']		,$format_cell);
		$worksheet->write( $i, ADRESSE,		$row['Adresse']		,$format_cell);
		$worksheet->write( $i, NOTE,		$row['Note']		,$format_cell);
		$worksheet->write( $i, MONTANT,		$row['Montant']		,$format_prix);
		$worksheet->write( $i, NB_LIGNE,	$row['Nb Lig']		,$format_cell);
		$i++;
	}
	mysql_close($mysql);

	// on rajoute les différences global
	$worksheet->write($i, NOTE, "Total"  ,$format_title);
	$worksheet->writeFormula($i, MONTANT,	'=SUM('.excel_column(MONTANT).'2:'.excel_column(MONTANT).$i.')' ,$format_prix);

	// Let's send the file
	$workbook->close();
	exit;
}



?>
<html>
<head>
<title>Récapitulatif des commandes adhérent Web</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#recap_commande {
	border:solid 1px grey;
	width:99%;
	border-spacing: 0px;
	border-collapse: collapse;
}

table#recap_commande th, table#recap_commande td {
	border:solid 1px grey;
}

@media print {
	.hide_when_print { display:none; }
}
</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<SCRIPT LANGUAGE="JavaScript" SRC="../js/jquery.js"></SCRIPT>
<script language="javascript" src="../js/jquery.tablesorter.min.js"></script>
<style type="text/css">@import url(../js/tablesortable/style.css);</style>
<SCRIPT LANGUAGE="JavaScript">
<!--

function telecharger_excel(sql) {
	document.location.href='recap_commande.php?xls=1&sql='+sql;
}


$(document).ready(function() {
        $('#recap_commande').tablesorter( {sortList: [[0,0]]} ); // sort sur le code article par défaut
		$('#recap_commande').bind('sortStart',function() {
			$('#overlay').show();
		}).bind('sortEnd',function() {
			$('#overlay').hide();
		});
	}
);

//-->
</SCRIPT>
</head>
<body>
<form name="ask_password" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
<!-- TABLEAU AVEC LE RECAP DES CDE -->
<?
// requete de recap des commandes web
$sql = <<<EOT
SELECT	id AS 'N°',
		code_user AS 'Code Adh',
		nom_user AS 'Nom Adh',
		date_cde AS 'Date',
		date_liv AS 'Date Liv.',
		reference AS 'Réf.',
		adr_liv	AS 'Adresse',
		note AS 'Note',
		(SELECT SUM(qte*prix) FROM mcs_cde_ligne WHERE mcs_cde.id=mcs_cde_ligne.id_cde)	 AS 'Montant',
		(SELECT COUNT(id) FROM mcs_cde_ligne WHERE mcs_cde.id=mcs_cde_ligne.id_cde) AS 'Nb Lig'
FROM mcs_cde
EOT;

if ($identifier === TRUE) { // on affiche le tableau ?>
	<?=mysql_result_to_html_table($sql,array('id'=>'recap_commande','class'=>'tablesorter'))?>
	
	<div style="text-align:center;"><input type="button" class="button valider excel" value="Télécharger au format Excel" onclick="telecharger_excel('<?=urlencode(urlencode(base64_encode($sql)))?>');"/></div>
	
<? } else { // on affiche le formulaire de demande de mot de passe ?>
	<div style="width:80%;text-align:center;">Mot de passe ? <input type="password" name="password" value="" /> <input type="submit" value="Se connecter" /></div>
<? } ?>

</form>

<?php if ($_SERVER['HTTP_HOST'] == 'www.coopmcs.com') { ?>
	<script type="text/javascript" src="http://www.coopmcs.com/php-stats/php-stats.js.php"></script>
	<noscript><img src="http://www.coopmcs.com/php-stats/php-stats.php" border="0" alt=""></noscript>
<?php } ?>

<!--[if IE]>
<div style="text-align:center;color:grey;font-size:0.8em;">
Si vous constatez des problèmes d'affichage, cela est du à votre naviguateur Internet Explorer. Merci d'utiliser un navigateur récent qui respecte les standards du Web.<br>
<a href="http://www.mozilla-europe.org/fr/firefox/" target="_blank"><img src="../templates/MCS/images/firefox_bar.png" /></a>
<a href="http://www.google.com/chrome" target="_blank"><img src="../templates/MCS/images/chrome_bar.gif" /></a>
<a href="http://www.opera.com/download/" target="_blank"><img src="../templates/MCS/images/opera_bar.gif" /></a>
<a href="http://www.apple.com/fr/safari/download/" target="_blank"><img src="../templates/MCS/images/safari_bar.png" /></a>
</div>
<![endif]-->

</body>
</html>
<?

///////////////// FUNCTION QUI TRANSFORME UNE REQUETTE SQL EN TABLEAU HTML //////////////////////////:
function mysql_result_to_html_table($sql, $table_attr = array()) {
	$return = '';
	$res = mysql_query($sql) or die("Erreur dans la requette SQL (".mysql_error().") $sql") ; // selectionne les panier favori

	// déclaration du table
	$return .= "<table";
	if (sizeof($table_attr) > 0)
		foreach ($table_attr as $key => $val)
			$return .= " $key=\"$val\"";
	$return .= ">\n";

	// déclaration des HEADERS --> nom des champs SQL
	$return .= "<thead><tr>\n";
	for($i=0 ; $i<mysql_num_fields($res) ; $i++) {
		$return .= "<th>".mysql_field_name($res,$i)."</th>";
	}
	$return .= "\n</tr></thead>\n";
	
	// déclaration du Corps du tableau --> valeur des champs SQL
	$return .= "<tbody>\n";
	while ($row = mysql_fetch_row($res)) {
		$return .= "<tr>";
		for($i=0 ; $i<sizeof($row) ; $i++)
			$return .= "<td>$row[$i]</td>";
		$return .= "</tr>\n";
	}
	$return .= "</tbody>\n";

	// déclaration des FOOTERS --> rien pour le moment
	$return .= "<tfoot></tfoot>\n";
	$return .= "</table>\n";
	return $return ;
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