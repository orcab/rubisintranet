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
if		 ($_POST['provenance'] == 'fournisseur' && $_POST['fournisseur'] != '>> Global <<')
	array_push($where,"NOMFO='$_POST[fournisseur]'");
elseif ($_POST['provenance'] == 'pdv' && $_POST['pdv'] != '>> Global <<')
	array_push($where,"CONCAT(ACTIV,CONCAT('.',CONCAT(FAMI1,CONCAT('.',CONCAT(SFAM1,CONCAT('.',CONCAT(ART04,CONCAT('.',ART05)))))))) like '$_POST[pdv]%'");

$where = ($where) ? ' and '.join(' and ',$where) : '';

$sql = <<<EOT
select
	ARTICLE.NOART as NO_ARTICLE, ARTICLE.DESI1 as DESIGNATION1, ARTICLE.DESI2 as DESIGNATION2, ARTICLE.DESI3 as DESIGNATION3,
	NOMFO as NOM_FOURNISSEUR,
	REFFO as REF_FOURNISSEUR,
	CONCAT(PRVDJ,CONCAT('/',CONCAT(PRVDM,CONCAT('/',CONCAT(PRVDS,PRVDA))))) as DATE_APPLICATION_PR,
	CONCAT(PVEDJ,CONCAT('/',CONCAT(PVEDM,CONCAT('/',CONCAT(PVEDS,PVEDA))))) as DATE_APPLICATION_PV,
	RMRV1 as REMISE1,RMRV2 as REMISE2,RMRV3 as REMISE3, PNRVT as PRIX_REVIENT,
	COEF1 as COEF, PVEN1 as PRIX_VENTE,
	PR.PRV03 as TYPE_PR,
	PV.PVT09 as TYPE_PV
from
	${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE,
	${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR,
	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 REF_FOURNISSEUR,
	${LOGINOR_PREFIX_BASE}GESTCOM.ATARPAP1 PR,
	${LOGINOR_PREFIX_BASE}GESTCOM.ATARPVP1 PV
where
		ARTICLE.ETARE=''					-- article non suspendu
	and ARTICLE.FOUR1=FOURNISSEUR.NOFOU
	and ARTICLE.NOART=REF_FOURNISSEUR.NOART
	and REF_FOURNISSEUR.NOFOU=FOURNISSEUR.NOFOU
		and REF_FOURNISSEUR.AGENC='$LOGINOR_AGENCE'
	and PV.NOART=ARTICLE.NOART
		and PV.AGENC='$LOGINOR_AGENCE'
	and PR.NOART=ARTICLE.NOART
		and PR.AGENC='$LOGINOR_AGENCE'
--and ARTICLE.NOART='02023140'
and NOMFO='AIRPAC'
	and ((PR.PRV03='E' AND PV.PVT09='E') -- tarif encours
			OR
		(PR.PRV03='A' AND PV.PVT09='A')) -- tarif a venir
	$where
ORDER BY
	ARTICLE.NOART ASC, TYPE_PR DESC, PRVDS DESC, PRVDA DESC, PRVDM DESC, PRVDJ DESC
EOT;
	
//	echo $sql;exit;

	$i=0;
	define('NO_ARTICLE',$i++);
	define('DESIGNATION',$i++);
	define('NOM_FOURNISSEUR',$i++);
	define('REF_FOURNISSEUR',$i++);
	define('DATE_APPLICATION',$i++);
	define('REMISE',$i++);
	define('REMISE_VENIR',$i++);
	define('PRIX_REVIENT',$i++);
	define('PRIX_REVIENT_VENIR',$i++);
	define('DELTA_REVIENT',$i++);
	define('COEF',$i++);
	define('COEF_VENIR',$i++);
	define('PRIX_VENTE',$i++);
	define('PRIX_VENTE_VENIR',$i++);
	define('DELTA_VENTE',$i++);
	
	// Creating a workbook
	$workbook = new Spreadsheet_Excel_Writer();

	// sending HTTP headers
	$workbook->send('comparaison_prix.xls');

	// Creating a worksheet
	$worksheet =& $workbook->addWorksheet('Comparaison de prix Rubis');
	$workbook->setCustomColor(12, 220, 220, 220);

	$format_title		=& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>'12' , 'bordercolor'=>'black' ));
	$format_cell		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_cell_futur	=& $workbook->addFormat(array('bordercolor'=>'black', 'fgcolor'=>'26'));
	$format_article		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_article->setNumFormat('00000000');
	$format_pourcentage =& $workbook->addFormat(array('bold'=>1 , 'fgcolor'=>'12' , 'bordercolor'=>'black' ));
	$format_pourcentage->setNumFormat('0.0%');
	$format_prix		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_prix->setNumFormat('0.00€');
	$format_prix_futur		=& $workbook->addFormat(array('bordercolor'=>'black','fgcolor'=>'26'));
	$format_prix_futur->setNumFormat('0.00€');
	$format_coef		=& $workbook->addFormat(array('bordercolor'=>'black'));
	$format_coef->setNumFormat('0.00000');
	$format_coef_futur	=& $workbook->addFormat(array('fgcolor'=>'26')); // beige
	$format_coef_futur->setNumFormat('0.00000');

	// La premiere ligne
	$worksheet->write(0,NO_ARTICLE,			'Code article',$format_title);
	$worksheet->write(0,DESIGNATION, 		'Désignation',$format_title); $worksheet->setColumn(DESIGNATION,DESIGNATION,30);
	$worksheet->write(0,NOM_FOURNISSEUR, 	'Fournisseur',$format_title); $worksheet->setColumn(NOM_FOURNISSEUR,NOM_FOURNISSEUR,12);
	$worksheet->write(0,REF_FOURNISSEUR,	'Référence',$format_title);
	$worksheet->write(0,DATE_APPLICATION,	"Date d'application",$format_title); $worksheet->setColumn(DATE_APPLICATION,DATE_APPLICATION,12);
	$worksheet->write(0,REMISE,				'Remise',$format_title);
	$worksheet->write(0,REMISE_VENIR,		'Remise F',$format_title);
	$worksheet->write(0,PRIX_REVIENT,		'Px R',$format_title);
	$worksheet->write(0,PRIX_REVIENT_VENIR,	'Px RF',$format_title);
	$worksheet->write(0,DELTA_REVIENT,		'Diff R',$format_title);
	$worksheet->write(0,COEF,				'Coef V',$format_title);
	$worksheet->write(0,COEF_VENIR,			'Coef VF',$format_title);
	$worksheet->write(0,PRIX_VENTE,			'Px V',$format_title);
	$worksheet->write(0,PRIX_VENTE_VENIR,	'Px VF',$format_title);
	$worksheet->write(0,DELTA_VENTE,		'Diff V',$format_title);
	
	
	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;
	$i = 1;
	$tarif_encours = array('remise1','remise2','remise3','prix_revient','coef','prix_vente');
	while($row = odbc_fetch_array($res)) {
		if ($row['TYPE_PR'] == 'E' && $row['TYPE_PV'] == 'E') { // tarif encours --> on stock pour l'afficher ensuite
			$tarif_encours['remise1']		= $row['REMISE1'];
			$tarif_encours['remise2']		= $row['REMISE2'];
			$tarif_encours['remise3']		= $row['REMISE3'];
			$tarif_encours['prix_revient']	= $row['PRIX_REVIENT'];
			$tarif_encours['coef']			= $row['COEF'];
			$tarif_encours['prix_vente']	= $row['PRIX_VENTE'];
			continue;

		} elseif ($row['TYPE_PR'] == 'A' && $row['TYPE_PV'] == 'A') { // tarif a venir --> on affiche
		
			$worksheet->write( $i, NO_ARTICLE,			trim($row['NO_ARTICLE'])  ,$format_article);
			$worksheet->write( $i, DESIGNATION ,		trim(trim($row['DESIGNATION1']).' '.trim($row['DESIGNATION2']).' '.trim($row['DESIGNATION3'])),$format_cell);
			$worksheet->write( $i, NOM_FOURNISSEUR ,	trim($row['NOM_FOURNISSEUR'])  ,$format_cell);
			$worksheet->write( $i, REF_FOURNISSEUR,		trim($row['REF_FOURNISSEUR'])  ,$format_cell);
			$worksheet->write( $i, DATE_APPLICATION,	$row['DATE_APPLICATION_PR']  ,$format_cell);
			$worksheet->write( $i, REMISE,				ereg_replace('.0000$','',$tarif_encours['remise1']).' / '.ereg_replace('.0000$','',$tarif_encours['remise2']).' / '.ereg_replace('.0000$','',$tarif_encours['remise3'])  ,$format_cell);
			$worksheet->write( $i, REMISE_VENIR,		ereg_replace('.0000$','',$row['REMISE1']).' / '.ereg_replace('.0000$','',$row['REMISE2']).' / '.ereg_replace('.0000$','',$row['REMISE3'])  ,$format_cell_futur);
			$worksheet->write( $i, PRIX_REVIENT,		$tarif_encours['prix_revient']  ,$format_prix);
			$worksheet->write( $i, PRIX_REVIENT_VENIR,	$row['PRIX_REVIENT']  ,$format_prix_futur);
			$worksheet->writeFormula($i, DELTA_REVIENT, '=('.excel_column(PRIX_REVIENT_VENIR).($i+1).'/'.excel_column(PRIX_REVIENT).($i+1).')-1' ,$format_pourcentage);
			$worksheet->write( $i, COEF,				$tarif_encours['coef']  ,$format_coef);
			$worksheet->write( $i, COEF_VENIR,			$row['COEF']  ,$format_coef_futur);		
			$worksheet->write( $i, PRIX_VENTE,			$tarif_encours['prix_vente']  ,$format_prix);
			$worksheet->write( $i, PRIX_VENTE_VENIR,	$row['PRIX_VENTE']  ,$format_prix_futur);
			$worksheet->writeFormula($i, DELTA_VENTE,	'=('.excel_column(PRIX_VENTE_VENIR).($i+1).'/'.excel_column(PRIX_VENTE).($i+1).')-1' ,$format_pourcentage);
			$i++;
		}
	}

	// on rajoute les différences global
/*	$worksheet->write( $i, REF_FOURNISSEUR,			"Total"  ,$format_title);
	$worksheet->writeFormula($i, PRIX_VENTE,		'=SUM('.excel_column(PRIX_VENTE).'2:'.excel_column(PRIX_VENTE).$i.')' ,$format_prix);
	$worksheet->writeFormula($i, PRIX_VENTE_VENIR,	'=SUM('.excel_column(PRIX_VENTE_VENIR).'2:'.excel_column(PRIX_VENTE_VENIR).$i.')' ,$format_prix);
	$worksheet->writeFormula($i, DELTA_VENTE,		'=('.excel_column(PRIX_VENTE_VENIR).($i+1).'/'.excel_column(PRIX_VENTE).($i+1).')-1' ,$format_pourcentage);
	$worksheet->writeFormula($i, PRIX_REVIENT,		'=SUM('.excel_column(PRIX_REVIENT).'2:'.excel_column(PRIX_REVIENT).$i.')' ,$format_prix);
	$worksheet->writeFormula($i, PRIX_REVIENT_VENIR,'=SUM('.excel_column(PRIX_REVIENT_VENIR).'2:'.excel_column(PRIX_REVIENT_VENIR).$i.')' ,$format_prix);
	$worksheet->writeFormula($i, DELTA_REVIENT,		'=('.excel_column(PRIX_REVIENT_VENIR).($i+1).'/'.excel_column(PRIX_REVIENT).($i+1).')-1' ,$format_pourcentage);
*/
	// Let's send the file
	$workbook->close();
	exit;

} // fin génération du fichier excel

?>
<html>
<head>
	<title>Tarif à venir v7</title>
	<style type="text/css">@import url(../../js/activite.css);</style>
<style>
body {
	font-family:verdana;
	margin-left:0px;
	margin-right:0px;
	padding:0px;
}

h1 {
	text-align:center;
	font-size:1.1em;
	color:green;
}

div.col {
	float:left; width:50%;
	marging-left:50%; width:50%;
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

</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<h1>Comparaison des prix a venir v7</h1>

<form name="tarif" method="post" action="<?=$_SERVER['PHP_SELF']?>" style="margin-top:10px;">
	<input type="hidden" name="provenance" value=""/>
	<center>
	<div class="col">
		<strong>Choix par Plan de vente</strong><br/>
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
			<strong>Choix par Fournisseur</strong><br/>
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