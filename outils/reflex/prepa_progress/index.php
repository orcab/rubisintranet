
<? include('../../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>Stock des produits</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
}

td {
	font-size:0.8em;
}

h1 {
    font-size: 1.2em;
}

.message {
    color: red;
    font-weight: bold;
    text-align: center;
}

#recherche {
	margin:auto;
	border:solid 1px grey;
	padding:20px;
	width:50%;
}

#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    margin-top: 1em;
    width:55%;
    margin:auto;
}
#lignes th, #lignes td {
    border: 1px solid #CCCCCC;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #CCCCCC;
    text-decoration: line-through;
}
caption {
    background-color: #DDD;
    padding: 2px;
}

.qualite-afz {
	background-color:#FAA
}

.qualite-afz .qualite {
	color:#F00;
}

.support .significient {
	font-size:1.2em;
}

#legende {
    margin: auto;
    margin-top: 2em;
    font-size: 0.7em;
    width: 55%;
}

#preparations {
    border-collapse: collapse;
    border: solid 1px black;
    width: 100%;
    margin: auto;
    margin-top:1em;
}

th {
	text-align:center;
	white-space: nowrap;
}

#preparations td {
    border: solid 1px grey;
    padding: 5px;
	white-space:nowrap;
}

td.avancement {
    text-align: left;
}

td.avancement {
	white-space:pre;
	font-family:courier new;
}

td.manquant {
	text-align: center;
	color:red;
}

.has_manquant {
	background-color:#FAA;
}

.more-than-one-hour {
	display:none;
}

.prepa-non-demarrer {
	background: linear-gradient(to right,#FAA 0%, #F55 100%);
}

.prepa-encours {
	/*background: linear-gradient(to right,#F7CA42 0%,#FFD460 100%);*/
	background: linear-gradient(to right,#F7CA42 0%,#FFF571 100%);
}

.prepa-fini {
	background: linear-gradient(to right,#5F5 0%,#CFC 100%);
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

$(document).ready(function(){
	$('#code_article').focus();

	setTimeout( "reload()", 10000 );
});


function reload() {
	console.log("reload");
	document.choix_prepa.submit();
}

//-->
</script>

</head>
<body>
<!--<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>-->

<form name="choix_prepa" method="GET" action="<?=$_SERVER['PHP_SELF']?>">
	<fieldset><legend>Type de pr�pa</legend>
		CPT <input type="checkbox" name="CPT" <?= isset($_GET['CPT']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		DIS <input type="checkbox" name="DIS" <?= isset($_GET['DIS']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		EXP <input type="checkbox" name="EXP" <?= isset($_GET['EXP']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		LDP <input type="checkbox" name="LDP" <?= isset($_GET['LDP']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		LSO <input type="checkbox" name="LSO" <?= isset($_GET['LSO']) ?'checked="checked"':'' ?>/>
	</fieldset>
</form>

<table id="preparations">
	<thead>
		<tr>
			<th class="num_artisan">Artisan</th>
			<th class="type">Type</th>
			<th class="num_commande">Commande</th>
			<th class="avancement">Avancement</th>
			<!--<th class="manquant">Manq.</th>-->
			<th class="heure_debut">Commenc� �</th>
			<th class="heure_fin">Fini �</th>
			<th class="realise">Fait en</th>
		</tr>
	</thead>
	<tbody>
<?
	$date_yyymmdd 	= date('Y-m-d');
	$date['siecle'] = substr($date_yyymmdd,0,2);
	$date['annee'] 	= substr($date_yyymmdd,2,2);
	$date['mois'] 	= substr($date_yyymmdd,5,2);
	$date['jour'] 	= substr($date_yyymmdd,8,2);

/*	$where_type_prepa = array();
	if (isset($_GET['type'])) { // un type de prepa est sp�cifi�, on choisit ce type
		foreach (explode(',',$_GET['type']) as $type) {
			$where_type_prepa[] = " ODP_ENTETE.OECMOP='".trim(strtoupper(mysql_escape_string($type)))."' ";
		}
	}
*/
	$where_type_prepa = array();
	if (isset($_GET['CPT']))
		$where_type_prepa[] = " ODP_ENTETE.OECMOP='CPT' ";
	if (isset($_GET['DIS']))
		$where_type_prepa[] = " ODP_ENTETE.OECMOP='DIS' ";
	if (isset($_GET['EXP']))
		$where_type_prepa[] = " ODP_ENTETE.OECMOP='EXP' ";
	if (isset($_GET['LDP']))
		$where_type_prepa[] = " ODP_ENTETE.OECMOP='LDP' ";
	if (isset($_GET['LSO']))
		$where_type_prepa[] = " ODP_ENTETE.OECMOP='LSO' ";

	if (sizeof($where_type_prepa)) // si au moins un type de prepa
		$where_type_prepa = ' and ('.join(' OR ',$where_type_prepa).')';
	else 
		$where_type_prepa = '';

		$sql = <<<EOT
select
--	*,
	PENANN as PREPA_ANNEE,
	PENPRE as PREPA_NUMERO,
	P1QAPR as QTE_A_PREPARER,P1QPRE as QTE_PREPARER,
	P1TVLP as LIGNE_VALIDEE,
	PEHVPP as HEURE_VALIDATION,
	PEHCRE as HEURE_CREATION,
	DSLDES as LIBELLE_DESTINATAIRE,
	OERODP as REFERENCE_OPD,
	ODP_ENTETE.OECMOP as TYPE
from
				${REFLEX_BASE}.HLPRENP PREPA_ENTETE
	left join 	${REFLEX_BASE}.HLPRPLP PREPA_DETAIL
		on PREPA_ENTETE.PENANN=PREPA_DETAIL.P1NANP and PREPA_ENTETE.PENPRE=PREPA_DETAIL.P1NPRE
	left join ${REFLEX_BASE}.HLODPEP ODP_ENTETE
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
	left join ${REFLEX_BASE}.HLDESTP DESTINATAIRE
		on PREPA_ENTETE.PECDES=DESTINATAIRE.DSCDES
where
		PREPA_ENTETE.PESCRE='$date[siecle]' and PREPA_ENTETE.PEACRE='$date[annee]' and PREPA_ENTETE.PEMCRE='$date[mois]' and PREPA_ENTETE.PEJCRE='$date[jour]'
		$where_type_prepa
order by HEURE_CREATION DESC
EOT;

//echo "<pre>$sql</pre><br/>\n";

	$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter � Reflex via ODBC ($REFLEX_DSN)");
	$res = odbc_exec($reflex,$sql)  or die("Impossible de rechercher les prepa du jour : <br/>$sql");


	$old_prepa = '';
	$old_row = array();
	$total_mission = $total_mission_validee = $pourcentage_avancement = 0;
	while($row = odbc_fetch_array($res)) {
		
		if ($old_prepa != "$row[PREPA_ANNEE].$row[PREPA_NUMERO]" && $old_prepa != '') { // si on change de num de prepa --> on reset les compteur et on cree une nouvelle ligne 
			$pourcentage_avancement = (int)($total_mission_validee * 100 / $total_mission);
			if ($old_row['HEURE_VALIDATION'])
				$pourcentage_avancement = 100;

			$manquant = '';
			if ($old_row['HEURE_VALIDATION'])
				$manquant = $total_mission - $total_mission_validee > 0 ? $total_mission - $total_mission_validee : '';

			$delay = array();
			if ($old_row['HEURE_VALIDATION']) {
				$date_crea 	= new DateTime($date_yyymmdd.' '.reflex_hour_to_hhmmss($old_row['HEURE_CREATION']));
				$date_valid = new DateTime($date_yyymmdd.' '.reflex_hour_to_hhmmss($old_row['HEURE_VALIDATION']));
				$now = new DateTime('now');
				$delay = dateDiff($now->format('U') , (int)$date_valid->format('U'));
			}
?>
			<tr class="<?	echo $delay['hours']>=1 ? ' more-than-one-hour':''; // plus d'une heure depuis la validation ?>">
				<td class="num_artisan"><?=$old_row['LIBELLE_DESTINATAIRE']?></td>
				<td class="type"><?=$old_row['TYPE']?></td>
				<td class="num_commande">
					<?=$old_row['PREPA_ANNEE']?>-<?=$old_row['PREPA_NUMERO']?>
					/
					<?	$reference_odp = split('/|-',$old_row['REFERENCE_OPD']);
						echo $reference_odp[1];
				?></td>
				<td class="avancement" style="background: linear-gradient(to right,#5F5 0%,#CFC <?=$pourcentage_avancement?>%, #FAA <?=$pourcentage_avancement?>%, #F55 100%);">Lignes <?=str_pad($total_mission_validee,2,' ',STR_PAD_LEFT);?>/<?=str_pad($total_mission,2,' ',STR_PAD_LEFT);?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=str_pad($pourcentage_avancement,3,' ',STR_PAD_LEFT);?>%</td>
				<!--<td class="manquant <?=$manquant ? 'has_manquant':''?>"><?=$manquant?></td>-->
				<td class="heure_debut <?
						if ($total_mission_validee <= 0)
							echo ' prepa-non-demarrer';
						elseif ($total_mission_validee > 0 && $total_mission_validee < $total_mission)
							echo ' prepa-encours';
						elseif ($total_mission_validee >= $total_mission && $old_row['HEURE_VALIDATION'])
							echo ' prepa-fini';
					?>"><?=reflex_hour_to_hhmmss($old_row['HEURE_CREATION'])?></td>
				<td class="heure_fin"><?=reflex_hour_to_hhmmss($old_row['HEURE_VALIDATION'])?></td>
				<td class="realise">
					<?	if ($old_row['HEURE_VALIDATION']) {
							echo getHumanReadableDelay($date_valid->format('U') , (int)$date_crea->format('U'));
						} ?>
				</td>
			</tr>
<?				
			$total_mission = $total_mission_validee = $pourcentage_avancement = 0;
		}

		if ($row['LIGNE_VALIDEE'])
			$total_mission_validee++;

		$total_mission++;
		$old_prepa = "$row[PREPA_ANNEE].$row[PREPA_NUMERO]";
		$old_row = $row;
	} 
	odbc_close($reflex);
?>
	</tbody>	
</table>
</body>
</html>
<?

function reflex_hour_to_hhmmss($heure) {
	if (strlen($heure) == 6)
		preg_match('/^(\d{2})(\d{2})(\d{2})$/',$heure,$regs);
	elseif (strlen($heure) == 5)
		preg_match('/^(\d{1})(\d{2})(\d{2})$/',$heure,$regs);
	else
		$regs = array(0,0,0,0);

	$heure_mmhhss = sprintf('%02d:%02d:%02d',$regs[1],$regs[2],$regs[3]);
	if ($heure_mmhhss == '00:00:00') $heure_mmhhss = '';

	return $heure_mmhhss;
}


function dateDiff($date1, $date2) {
	$seconds = abs($date1 - $date2);

	$delay = array();
	foreach( array( 86400, 3600, 60, 1) as $increment) {
		$difference = abs(floor($seconds / $increment));
		$seconds %= $increment;
		$delay[] = $difference;
	}

	return 	array(	'days' 		=> $delay[0],
					'hours' 	=> $delay[1],
					'minutes' 	=> $delay[2],
					'seconds' 	=> $delay[3],
			);
}


function getHumanReadableDelay($second1,$second2) {
	$delay = dateDiff($second1,$second2);
	return 	($delay['days'] ? $delay['days'].'d ':'').
			($delay['hours'] ? $delay['hours'].'h ':'').
			($delay['minutes'] ? $delay['minutes'].'m ':'').
			($delay['seconds'] ? $delay['seconds'].'s ':'')
		;
}
?>