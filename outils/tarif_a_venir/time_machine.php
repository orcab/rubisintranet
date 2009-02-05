<?php
include('../../inc/config.php');
set_time_limit(0);

set_include_path(get_include_path().PATH_SEPARATOR.'../../inc'); // ajoute le chemin d'acces a Spreadsheet/Excel
require_once '../../inc/Spreadsheet/Excel/Writer.php';

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");


//////////////////////////////////// GENERATION DU FICHIER EXCEL ////////////////////////:
if (isset($_POST['provenance']) && $_POST['provenance']) {

$where = array();
if		($_POST['provenance'] == 'fournisseur' && $_POST['fournisseur'] != '>> Global <<')
	array_push($where,"FOURNISSEUR.NOMFO='$_POST[fournisseur]'");
elseif	($_POST['provenance'] == 'pdv' && $_POST['pdv'] != '>> Global <<')
	array_push($where,"CONCAT(ARTICLE.ACTIV,CONCAT('.',CONCAT(ARTICLE.FAMI1,CONCAT('.',CONCAT(ARTICLE.SFAM1,CONCAT('.',CONCAT(ARTICLE.ART04,CONCAT('.',ARTICLE.ART05)))))))) like '$_POST[pdv]%'");
elseif	($_POST['provenance'] == 'code_article' && $_POST['code_article'])
	array_push($where,"(ARTICLE.NOART='".mysql_escape_string($_POST['code_article'])."' OR REF_FOURNISSEUR.REFFO='".mysql_escape_string($_POST['code_article'])."')");


if	(isset($_POST['valueA']) &&	isset($_POST['valueB'])) { // une date est spécifiée
	array_push($where,"CONCAT(OLD_PRIX_REVIENT.PRVDS,CONCAT(OLD_PRIX_REVIENT.PRVDA,OLD_PRIX_REVIENT.PRVDM)) >= '".join(array_reverse(explode('/',$_POST['valueA'])))."'");
	array_push($where,"CONCAT(OLD_PRIX_REVIENT.PRVDS,CONCAT(OLD_PRIX_REVIENT.PRVDA,OLD_PRIX_REVIENT.PRVDM)) <= '".join(array_reverse(explode('/',$_POST['valueB'])))."'");
}

if	(isset($_POST['tarif_papier']) && $_POST['tarif_papier']) { // un critere de tarif papier OUI ou NON
	array_push($where,"ARTICLE.DIAA1='".mysql_escape_string($_POST['tarif_papier'])."'");
}

if	(isset($_POST['servi_stock']) && $_POST['servi_stock']) { // un critere de servi sur stock OUI ou NON
	array_push($where,"ARTICLE.SERST='".mysql_escape_string($_POST['servi_stock'])."'");
}


$where = ($where) ? ' and '.join(' and ',$where) : '';

$sql = <<<EOT
select
	ARTICLE.NOART as NO_ARTICLE,
	DESI1 as DESIGNATION1,DESI2 as DESIGNATION2,DESI3 as DESIGNATION3,
	NOMFO as NOM_FOURNISSEUR,REFFO as REF_FOURNISSEUR,
	REVIENT.PNRVT as PRIX_REVIENT, REVIENT.RMRV1 as REMISE1, REVIENT.RMRV2 as REMISE2, REVIENT.RMRV3 as REMISE3,
	VENTE.PVEN1 as PRIX_VENTE, VENTE.COEF1 as COEF,
	OLD_PRIX_REVIENT.PNRVT as OLD_PRIX_REVIENT, OLD_PRIX_REVIENT.RMRV1 as OLD_REMISE1, OLD_PRIX_REVIENT.RMRV2 as OLD_REMISE2, OLD_PRIX_REVIENT.RMRV3 as OLD_REMISE3,
	OLD_PRIX_VENTE.PVEN1 as OLD_PRIX_VENTE, OLD_PRIX_VENTE.COEF1 as OLD_COEF,
	CONCAT(OLD_PRIX_REVIENT.PRVDJ,CONCAT('/',CONCAT(OLD_PRIX_REVIENT.PRVDM,CONCAT('/',CONCAT(OLD_PRIX_REVIENT.PRVDS,OLD_PRIX_REVIENT.PRVDA))))) as DATE_APPLICATION_FORMATEE
--	,
--	PNXVT as PRIX_REVIENT_VENIR,
--	XPVE1 as PRIX_VENTE_VENIR,
--	CONCAT(XAPCJ,CONCAT('/',CONCAT(XAPCM,CONCAT('/',CONCAT(XAPCS,XAPCA))))) as DATE_APPLICATION_VENIR
from
	${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on ARTICLE.FOUR1=FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 REF_FOURNISSEUR
			on REF_FOURNISSEUR.NOFOU=FOURNISSEUR.NOFOU and REF_FOURNISSEUR.NOART=ARTICLE.NOART
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARVTP1 REVIENT
			on ARTICLE.NOART=REVIENT.NOART
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIFP1 VENTE
			on ARTICLE.NOART=VENTE.NOART
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AOLDPRP1 OLD_PRIX_REVIENT
			on ARTICLE.NOART=OLD_PRIX_REVIENT.NOART
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AOLDPVP1 OLD_PRIX_VENTE
			on		ARTICLE.NOART=OLD_PRIX_VENTE.NOART
				and OLD_PRIX_REVIENT.PRVDS=OLD_PRIX_VENTE.PVEDS
				and OLD_PRIX_REVIENT.PRVDA=OLD_PRIX_VENTE.PVEDA
				and OLD_PRIX_REVIENT.PRVDM=OLD_PRIX_VENTE.PVEDM
				and OLD_PRIX_REVIENT.PRVDJ=OLD_PRIX_VENTE.PVEDJ
--		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ANEWPRP1 REVIENT_VENIR
--			on ARTICLE.NOART=REVIENT_VENIR.NOART
--		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARIXP1 VENTE_VENIR
--			on ARTICLE.NOART=VENTE_VENIR.NOART
where
		ARTICLE.ETARE=''
	and REF_FOURNISSEUR.AGENC='$LOGINOR_AGENCE'
	and VENTE.AGENC='$LOGINOR_AGENCE'
	and OLD_PRIX_REVIENT.AGENC='$LOGINOR_AGENCE'
	and OLD_PRIX_VENTE.AGENC='$LOGINOR_AGENCE'
	$where
order by
	ARTICLE.NOART ASC, CONCAT(OLD_PRIX_REVIENT.PRVDS,CONCAT(OLD_PRIX_REVIENT.PRVDA,CONCAT(OLD_PRIX_REVIENT.PRVDM,OLD_PRIX_REVIENT.PRVDJ))) DESC
EOT;

	//echo $sql;exit;

	$i=0;
	define('NO_ARTICLE',$i++);
	define('DESIGNATION',$i++);
	define('NOM_FOURNISSEUR',$i++);
	define('REF_FOURNISSEUR',$i++);

	define('DATE_APPLICATION',$i++);

	define('PRIX_VENTE',$i++);
	define('OLD_PRIX_VENTE',$i++);
	define('DELTA_VENTE',$i++);

	define('PRIX_REVIENT',$i++);
	define('OLD_PRIX_REVIENT',$i++);
	define('DELTA_REVIENT',$i++);

	define('REMISE',$i++);
	define('OLD_REMISE',$i++);
	define('COEF',$i++);
	define('OLD_COEF',$i++);


	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send('time_machine_prix.xls');

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Time machine prix Rubis');
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
	$worksheet->write(0,NO_ARTICLE,			'Code article',$format_title);
	$worksheet->write(0,DESIGNATION, 		'Désignation',$format_title); $worksheet->setColumn(DESIGNATION,DESIGNATION,30);
	$worksheet->write(0,NOM_FOURNISSEUR, 	'Fournisseur',$format_title); $worksheet->setColumn(NOM_FOURNISSEUR,NOM_FOURNISSEUR,12);
	$worksheet->write(0,REF_FOURNISSEUR,	'Référence',$format_title);

	$worksheet->write(0,DATE_APPLICATION,	"Date d'application",$format_title); $worksheet->setColumn(DATE_APPLICATION,DATE_APPLICATION,12);

	$worksheet->write(0,PRIX_VENTE,			'Px V',$format_title);
	$worksheet->write(0,OLD_PRIX_VENTE,		'Px V avant',$format_title);
	$worksheet->write(0,DELTA_VENTE,		'Diff V',$format_title);

	$worksheet->write(0,PRIX_REVIENT,		'Px R',$format_title);
	$worksheet->write(0,OLD_PRIX_REVIENT,	'Px R avant',$format_title);
	$worksheet->write(0,DELTA_REVIENT,		'Diff R',$format_title);

	$worksheet->write(0,REMISE,				'Remise',$format_title);
	$worksheet->write(0,OLD_REMISE,			'Remise avant',$format_title);
	$worksheet->write(0,COEF,				'Coef',$format_title);
	$worksheet->write(0,OLD_COEF,			'Coef avant',$format_title);

	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;
	$i = 1; $j=0;
	$old_prix_vente = 0; $old_prix_revient = 0; // pour voir si il est bien necessaire d'afficher la ligne si les prix non pas changés
	$old_code_article = ''; // on s'en sert pour aéré le tableau et sauté une ligne quand on change de code
	while($row = odbc_fetch_array($res)) {
		if ($old_code_article != $row['NO_ARTICLE']) {
				$old_prix_vente = $row['PRIX_VENTE'];
				$old_prix_revient = $row['PRIX_REVIENT'];
				$j = 0;
				
				if ($i > 1) $i++; // on passe une ligne pour aéré le code (sauf la premiere)
		}


		if (	$row['OLD_PRIX_VENTE']   != $old_prix_vente
			||	$row['OLD_PRIX_REVIENT'] != $old_prix_revient) { // si les prix on changer ou si on est sur la premiere ligne

			
			$worksheet->write( $i, NO_ARTICLE,			trim($row['NO_ARTICLE'])  ,$format_article);
			$worksheet->write( $i, DESIGNATION ,		trim($row['DESIGNATION1']).' '.trim($row['DESIGNATION2']).' '.trim($row['DESIGNATION3']),$format_cell);
			$worksheet->write( $i, NOM_FOURNISSEUR ,	trim($row['NOM_FOURNISSEUR'])  ,$format_cell);
			$worksheet->write( $i, REF_FOURNISSEUR,		trim($row['REF_FOURNISSEUR'])  ,$format_cell);
			
			$worksheet->write( $i, DATE_APPLICATION,	$row['DATE_APPLICATION_FORMATEE']  ,$format_cell);

			$worksheet->write( $i, PRIX_VENTE,			$row['PRIX_VENTE']  ,$format_prix);
			$worksheet->write( $i, OLD_PRIX_VENTE,		$row['OLD_PRIX_VENTE']  ,$format_prix);
			$worksheet->writeFormula($i, DELTA_VENTE,	'=('.excel_column(PRIX_VENTE).($i+1).'/'.excel_column(OLD_PRIX_VENTE).($i+1).')-1' ,$format_pourcentage);

			$worksheet->write( $i, PRIX_REVIENT,		$row['PRIX_REVIENT']  ,$format_prix);
			$worksheet->write( $i, OLD_PRIX_REVIENT,	$row['OLD_PRIX_REVIENT']  ,$format_prix);
			$worksheet->writeFormula($i, DELTA_REVIENT, '=('.excel_column(PRIX_REVIENT).($i+1).'/'.excel_column(OLD_PRIX_REVIENT).($i+1).')-1' ,$format_pourcentage);

			$worksheet->write( $i, REMISE,				ereg_replace('.0000$','',$row['REMISE1']).' / '.ereg_replace('.0000$','',$row['REMISE2']).' / '.ereg_replace('.0000$','',$row['REMISE3'])  ,$format_cell);
			$worksheet->write( $i, OLD_REMISE,			ereg_replace('.0000$','',$row['OLD_REMISE1']).' / '.ereg_replace('.0000$','',$row['OLD_REMISE2']).' / '.ereg_replace('.0000$','',$row['OLD_REMISE3'])  ,$format_cell);
			$worksheet->write( $i, COEF,				$row['COEF']  ,$format_coef);
			$worksheet->write( $i, OLD_COEF,			$row['OLD_COEF']  ,$format_coef);

			$i++;
			$j++;
		}
	
		$old_prix_vente = $row['OLD_PRIX_VENTE']; $old_prix_revient = $row['OLD_PRIX_REVIENT'];
		$old_code_article = $row['NO_ARTICLE'];
	}

	// Let's send the file
	$workbook->close();
	exit;

} // fin génération du fichier excel

?>
<html>
<head>
	<title>Historique des anciens tarifs</title>

<link type="text/css" href="../../js/slider2/demoPages.css" media="screen" rel="Stylesheet" />
<script type="text/javascript" src="../../js/slider2/jquery-1.2.6.min.js"></script>
<script type="text/javascript" src="../../js/slider2/jquery-ui-personalized-1.6rc4.min.js"></script>
<script type="text/javascript" src="../../js/slider2/selectToUISlider.jQuery.js"></script>
<link type="text/css" href="../../js/slider2/ui.core.css" rel="Stylesheet" />	
<link rel="stylesheet" href="../../js/slider2/ui.theme.css" type="text/css" title="ui-theme" />
<link rel="stylesheet" href="../../js/slider2/ui.slider.css" type="text/css" />
<link rel="Stylesheet" href="../../js/slider2/ui.slider.extras.css" type="text/css" />

<style type="text/css">
	form { margin: 0 30px;}
	fieldset { border:0; margin-top: 1em;}
	.ui-slider {clear: both; top: 15px;}

</style>
<script type="text/javascript">
$(function(){
	$('select#valueA, select#valueB').selectToUISlider({
		labels: 15
	});
});
</script>


<style type="text/css">@import url(../../js/activite.css);</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--
function update_path(selecteur) {
	document.getElementById('path').innerHTML = selecteur[selecteur.selectedIndex].value;
}

function valid_form(quoi) {
	document.tarif.provenance.value=quoi;
	document.tarif.submit();
}

//-->
</script>

<style>
body {
	font-family:verdana;
	margin-left:0px;
	margin-right:0px;
	padding:0px;
}

h1 {
	text-align:center;
	font-size:1.5em;
	color:green;
}

h2 {
	text-align:center;
	font-size:1.5em;
	font-weight:bold;
}

div.col {
	float:left; width:50%;
	marging-left:50%; width:50%;
	margin-bottom:30px;
	text-align:center;
}

option { font-size:0.7em; }

option.n1 {
	padding-left:0px;
	font-size:0.8em;
	font-weight:bold;
	color:white;
	background-color:#A00;
}

option.n2 { padding-left:10px; }
option.n3 { padding-left:20px;color:#666; }
option.n4 { padding-left:30px;color:#999; }
option.n5 { padding-left:40px;color:#BBB; }

@media print {
	.hide_when_print { display:none; }
}

</style>


</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<h1>Comparaison des anciens prix de vente et de revient</h1>


<form name="tarif" method="post" action="time_machine.php" style="margin-top:10px;">
	<input type="hidden" name="provenance" value=""/>
	<center>

		<fieldset style="margin-bottom:30px;">
		<label for="valueA" class="sentence" style="display:none;">Depuis :</label>

		<select name="valueA" id="valueA" style="display:none;">
<?			$mois_mini = array('Jan','Fev','Mar','Avr','Mai','Jui','Jul','Aou','Sep','Oct','Nov','Dec');
			for($i=2006 ; $i <= date('Y') ; $i++) {
				for($j=0 ; $j<sizeof($mois_mini) ; $j++) {	?>
					<option value="<?=sprintf('%02d',$j+1)."/$i"?>" <?=($i+1 == date('Y') && $j+1 == date('m')) ? 'selected="selected"':''?>><?=$mois_mini[$j]." $i"?></option>
<?						if ($i == date('Y') && $j+1 == date('m')) break; // pour ne pas afficher les mois de l'année en cours qui ne sont pas passé
				}
			}
?>		</select>

		<label for="valueB" class="sentence" style="display:none;">Jusqu'à :</label>
		<select name="valueB" id="valueB" style="display:none;">
<?			for($i=2006 ; $i <= date('Y') ; $i++) {
				for($j=0 ; $j<sizeof($mois_mini) ; $j++) {	?>
					<option value="<?=sprintf('%02d',$j+1)."/$i"?>" <?=($i == date('Y') && $j+1 == date('m')) ? 'selected="selected"':''?>><?=$mois_mini[$j]." $i"?></option>
<?						if ($i == date('Y') && $j+1 == date('m')) break; // pour ne pas afficher les mois de l'année en cours qui ne sont pas passé
				}
			}
?>		</select>
	</fieldset>


	<div>
		<div class="col">
			<h2>Tarif papier</h2>
			<select name="tarif_papier" style="height:20px;">
				<option value="" selected="selected">Tous les produits</option>
				<option value="OUI">Les produits SUR le tarif papier</option>
				<option value="NON">Les produits HORS du tarif papier</option>
			</select>
		</div>
		<div class="col">
			<h2>Servi sur stock</h2>
			<select name="servi_stock" style="height:20px;">
				<option value="" selected="selected">Tous les produits</option>
				<option value="OUI">Les produits SERVI sur stock</option>
				<option value="NON">Les produits NON SERVI sur stock</option>
			</select>
		</div>
	</div>

	<div class="col">
		<h2>Choix par Plan de vente</h2>
		<select name="pdv" size="20" onchange="update_path(this);">
			<option style="text-align:center;font-size:1.2em;">&gt;&gt; Global &lt;&lt;</option>
<?
				$sql = <<<EOT
SELECT	chemin,libelle,niveau
FROM	pdvente
ORDER BY chemin ASC
EOT;
				$res = mysql_query($sql) or die("ne peux pas recupérer le plan de vente ".mysql_error());
				while($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['chemin']?>" class="n<?=$row['niveau']?> act_<?=array_shift(explode('.',$row['chemin']))?>"><?=$row['libelle']?></option>
<?				} ?>
		</select><br/>
		<div id="path">&nbsp;</div>
		<input type="button" class="button valider excel" value="Télécharger le fichier Excel" onclick="valid_form('pdv');"/>
		</div>


		<div class="col">
			<h2>Choix par Fournisseur</h2>
			<select name="fournisseur" size="20">
			<option style="text-align:center;font-size:1.2em;">&gt;&gt; Global &lt;&lt;</option>
<?
				$sql = <<<EOT
SELECT	DISTINCT(fournisseur)
FROM	article
ORDER BY fournisseur ASC
EOT;
				$res = mysql_query($sql) or die("ne peux pas recupérer le plan de vente ".mysql_error());
				while($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['fournisseur']?>"><?=$row['fournisseur']?></option>
<?				} ?>
		</select><br/>
		<div id="path">&nbsp;</div>
		<input type="button" class="button valider excel" value="Télécharger le fichier Excel" onclick="valid_form('fournisseur');"/>
		</div>

		<div>
			<h2>Choix par code article ou Référence fournisseur</h2>
			<input type="text" value="" name="code_article" size="13"/>
			<input type="button" class="button valider excel" value="Télécharger le fichier Excel" onclick="valid_form('code_article');"/>
		</div>


	</center>
</form>

</body>
</html>
<?
mysql_close($mysql);


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