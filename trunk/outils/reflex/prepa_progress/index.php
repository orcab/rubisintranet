
<? include('../../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<title>Avancement des prépas</title>

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
	background: linear-gradient(to right,#F7CA42 0%,#FFF571 100%);
}

.prepa-fini {
	background: linear-gradient(to right,#5F5 0%,#CFC 100%);
}

.emplacement {
    color: darkgreen;
    font-weight: normal;
}
.num_artisan {
    font-weight: bold;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

var timeout = 61;

$(document).ready(function(){
	$('#code_article').focus();
<? if ($_SERVER['REMOTE_ADDR'] == '10.211.14.63') { // on recharge la page uniquement si c'est l'écran d'accueil du comptoir ?>
	refresh();
<? } ?>
});

function refresh() {
	timeout--;
	$('#timeout').text(timeout);
	setTimeout( "refresh()", 1000 );

	if (timeout<1)
		reload();
}

function reload() {
	document.choix_prepa.submit();
}
//-->
</script>
</head>
<body>

<form name="choix_prepa" method="GET" action="<?=$_SERVER['PHP_SELF']?>">
	<fieldset><legend>Type de prépa</legend>
		CPT <input type="checkbox" name="CPT" <?= isset($_GET['CPT']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		DIS <input type="checkbox" name="DIS" <?= isset($_GET['DIS']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		EXP <input type="checkbox" name="EXP" <?= isset($_GET['EXP']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		LDP <input type="checkbox" name="LDP" <?= isset($_GET['LDP']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;
		LSO <input type="checkbox" name="LSO" <?= isset($_GET['LSO']) ?'checked="checked"':'' ?>/>&nbsp;&nbsp;&nbsp;&nbsp;

		<a class="btn btn-success" onclick="reload();" id="rafraichir"><i class="icon-ok"></i> Rafraichir <span id="timeout"></span></a>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>
	</fieldset>
</form>

<table id="preparations">
	<thead>
		<tr>
			<th class="num_artisan">Artisan</th>
			<th class="type">Type</th>
			<th class="preparable">%</th>
			<th class="num_commande">Commande</th>
			<th class="avancement">Avancement</th>
			<th class="heure_debut">Commencé à</th>
			<th class="heure_fin">Fini à</th>
			<th class="realise">Fait en</th>
		</tr>
	</thead>
	<tbody>
<?
	$reflex = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");

	# recuperation de la liste des destinataire
	$destinataires = array();
	$sql = <<<EOT
select DSCDES as CODE_DESTINATAIRE,DSLDES as LIBELLE_DESTINATAIRE from ${REFLEX_BASE}.HLDESTP DESTINATAIRE
EOT;
	$res 	= odbc_exec($reflex,$sql) or die("Impossible de rechercher les prepa du jour : <br/>$sql");
	while($row = odbc_fetch_array($res)) {
		$destinataires[$row['CODE_DESTINATAIRE']] = $row['LIBELLE_DESTINATAIRE'];
	}

	$now_hhmmss 	= date('Gis');

	$today_yyymmdd 	= date('Y-m-d');
	$today['siecle']= substr($today_yyymmdd,0,2);
	$today['annee'] = substr($today_yyymmdd,2,2);
	$today['mois'] 	= substr($today_yyymmdd,5,2);
	$today['jour'] 	= substr($today_yyymmdd,8,2);

	$next_open_day_yyymmdd 	= get_next_open_day('Y-m-d');

	$next_open_day['siecle']= substr($next_open_day_yyymmdd,0,2);
	$next_open_day['annee'] = substr($next_open_day_yyymmdd,2,2);
	$next_open_day['mois'] 	= substr($next_open_day_yyymmdd,5,2);
	$next_open_day['jour'] 	= substr($next_open_day_yyymmdd,8,2);

	$where_type_prepa = array();
	if (isset($_GET['CPT']))
		$where_type_prepa[] = " (ODP_ENTETE.OECMOP='CPT' and PREPA_ENTETE.PESSCA='$today[siecle]' and PREPA_ENTETE.PEANCA='$today[annee]' and PREPA_ENTETE.PEMOCA='$today[mois]' and PREPA_ENTETE.PEJOCA='$today[jour]')\n";
	if (isset($_GET['DIS']))
		$where_type_prepa[] = " (ODP_ENTETE.OECMOP='DIS' and PREPA_ENTETE.PESSCA='$today[siecle]' and PREPA_ENTETE.PEANCA='$today[annee]' and PREPA_ENTETE.PEMOCA='$today[mois]' and PREPA_ENTETE.PEJOCA='$today[jour]')\n";
	if (isset($_GET['EXP']))
		$where_type_prepa[] = " (ODP_ENTETE.OECMOP='EXP' and PREPA_ENTETE.PESSCA='$today[siecle]' and PREPA_ENTETE.PEANCA='$today[annee]' and PREPA_ENTETE.PEMOCA='$today[mois]' and PREPA_ENTETE.PEJOCA='$today[jour]')\n";
	if (isset($_GET['LDP']))
		$where_type_prepa[] = " (ODP_ENTETE.OECMOP='LDP' and PREPA_ENTETE.PESSCA='$next_open_day[siecle]' and PREPA_ENTETE.PEANCA='$next_open_day[annee]' and PREPA_ENTETE.PEMOCA='$next_open_day[mois]' and PREPA_ENTETE.PEJOCA='$next_open_day[jour]')\n";
	if (isset($_GET['LSO']))
		$where_type_prepa[] = " (ODP_ENTETE.OECMOP='LSO' and PREPA_ENTETE.PESSCA='$next_open_day[siecle]' and PREPA_ENTETE.PEANCA='$next_open_day[annee]' and PREPA_ENTETE.PEMOCA='$next_open_day[mois]' and PREPA_ENTETE.PEJOCA='$next_open_day[jour]')\n";

	if (sizeof($where_type_prepa)) // si au moins un type de prepa
		$where_type_prepa = join(' OR ',$where_type_prepa);
	else 
		$where_type_prepa = '';

		$sql = <<<EOT
select
	PENANN as PREPA_ANNEE,
	PENPRE as PREPA_NUMERO,
	PEHVPP as HEURE_VALIDATION,
	PEHCRE as HEURE_CREATION,
	PECDES as CODE_DESTINATAIRE,
	OERODP as REFERENCE_OPD,
	ODP_ENTETE.OECMOP as TYPE,
	PESCRE as CREATION_SIECLE, PEACRE as CREATION_ANNEE, PEMCRE as CREATION_MOIS, PEJCRE as CREATION_JOUR,
	(select COUNT(*) 				from RFXPRODDTA.reflex.HLPRPLP where P1NANP=PENANN and PENPRE=P1NPRE and P1TVLP='1') 	as LIGNES_PREPAREES,
	(select COUNT(*) 				from RFXPRODDTA.reflex.HLPRPLP where P1NANP=PENANN and PENPRE=P1NPRE) 					as LIGNES_A_PREPARER,
	(select SUM(P1QAPR - P1NQAM) 	from RFXPRODDTA.reflex.HLPRPLP where P1NANP=PENANN and PENPRE=P1NPRE) 					as PEUT_PREPARER,
	(select SUM(P1QAPR) 			from RFXPRODDTA.reflex.HLPRPLP where P1NANP=PENANN and PENPRE=P1NPRE) 					as A_PREPARER,
	(select TOP 1 (EMC1EM+' '+EMC2EM+' '+EMC3EM+' '+EMC4EM+' '+EMC5EM)
		from ${REFLEX_BASE}.HLGESOP GEI_SORTIS
			left join ${REFLEX_BASE}.HLSUSOP SUPPORT_SORTIS
				on GEI_SORTIS.GSNSUP=SUPPORT_SORTIS.SXNSUP
			left join ${REFLEX_BASE}.HLEMPLP EMPLACEMENTS
				on EMPLACEMENTS.EMNEMP=SUPPORT_SORTIS.SXNEMP

		where PREPA_ENTETE.PENANN=GEI_SORTIS.GSNAPP and PREPA_ENTETE.PENPRE=GEI_SORTIS.GSNPRE
	) as EMPLACEMENT_SUPPORT_SORTIS
from
				${REFLEX_BASE}.HLPRENP PREPA_ENTETE
	left join 	${REFLEX_BASE}.HLPRPLP PREPA_DETAIL
		on PREPA_ENTETE.PENANN=PREPA_DETAIL.P1NANP and PREPA_ENTETE.PENPRE=PREPA_DETAIL.P1NPRE
	left join ${REFLEX_BASE}.HLODPEP ODP_ENTETE
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP

where
		($where_type_prepa)
		and (select SUM(P1QAPR - P1NQAM) from RFXPRODDTA.reflex.HLPRPLP where PENPRE=P1NPRE)>0
		and ($now_hhmmss - PEHCRE) <= 10000	-- pas de commande validé il y a plus d'une heure
group by PENANN,PENPRE,PEHVPP,PEHCRE,PECDES,OERODP,ODP_ENTETE.OECMOP,PESCRE,PEACRE,PEMCRE,PEJCRE
order by PESCRE DESC, PEACRE DESC, PEMCRE DESC, PEJCRE DESC, HEURE_CREATION DESC
EOT;

//echo "Coupé temporairement pour un test de performance. Benjamin";
//echo "<pre>$sql</pre><br/>\n";
//exit;

	$res 	= odbc_exec($reflex,$sql) or die("Impossible de rechercher les prepa du jour : <br/>$sql");
	while($row = odbc_fetch_array($res)) {
			$row['LIBELLE_DESTINATAIRE'] = $destinataires[$row['CODE_DESTINATAIRE']];
			$pourcentage_avancement = (int)($row['LIGNES_PREPAREES'] * 100 / $row['LIGNES_A_PREPARER']);
			if ($row['HEURE_VALIDATION'])
				$pourcentage_avancement = 100;

			$delay = array();
			if ($row['HEURE_VALIDATION']) {
				$date_crea 	= new DateTime($today_yyymmdd.' '.reflex_hour_to_hhmmss($row['HEURE_CREATION']));
				$date_valid = new DateTime($today_yyymmdd.' '.reflex_hour_to_hhmmss($row['HEURE_VALIDATION']));
				$now = new DateTime('now');
				$delay = dateDiff($now->format('U') , (int)$date_valid->format('U'));
			}
?>
			<tr class="<?	echo $delay['hours']>=1 ? ' more-than-one-hour':''; // plus d'une heure depuis la validation ?>">
				<td class="num_artisan"><?=$row['LIBELLE_DESTINATAIRE']?><br/>
					<?= $row['EMPLACEMENT_SUPPORT_SORTIS'] ? "<span class='emplacement'>Emplacement de dépose : $row[EMPLACEMENT_SUPPORT_SORTIS]</span>":''?>
				</td>
				<td class="type"><?=$row['TYPE']?></td>
				<td class="type"><?=(int)($row['PEUT_PREPARER'] * 100 / $row['A_PREPARER'])?>%</td>
				<td class="num_commande">
					<?=$row['PREPA_ANNEE']?>-<?=$row['PREPA_NUMERO']?>
					/
					<?	$reference_odp = split('/|-',$row['REFERENCE_OPD']);
						echo $reference_odp[1];
				?></td>
				<td class="avancement" style="background: linear-gradient(to right,#5F5 0%,#CFC <?=$pourcentage_avancement?>%, #FAA <?=$pourcentage_avancement?>%, #F55 100%);">Lignes <?=str_pad($row['LIGNES_PREPAREES'],2,' ',STR_PAD_LEFT);?>/<?=str_pad($row['LIGNES_A_PREPARER'],2,' ',STR_PAD_LEFT);?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=str_pad($pourcentage_avancement,3,' ',STR_PAD_LEFT);?>%</td>
				<td class="heure_debut <?
						if ($row['LIGNES_PREPAREES'] <= 0)
							echo ' prepa-non-demarrer';
						elseif ($row['LIGNES_PREPAREES'] > 0 && $row['LIGNES_PREPAREES'] < $row['LIGNES_A_PREPARER'])
							echo ' prepa-encours';
						elseif ($row['LIGNES_PREPAREES'] >= $row['LIGNES_A_PREPARER'] && $row['HEURE_VALIDATION'])
							echo ' prepa-fini';
					?>">
					<?=reflex_hour_to_hhmmss($row['HEURE_CREATION'])?>
					(<?=$row['CREATION_JOUR']?>/<?=$row['CREATION_MOIS']?>/<?=$row['CREATION_SIECLE'].$row['CREATION_ANNEE']?>)
				</td>
				<td class="heure_fin"><?=reflex_hour_to_hhmmss($row['HEURE_VALIDATION'])?></td>
				<td class="realise">
					<?	if ($row['HEURE_VALIDATION']) {
							echo get_human_readable_delay($date_valid->format('U') , (int)$date_crea->format('U'));
						} ?>
				</td>
			</tr>
<?	} 
	odbc_close($reflex);
?>
	</tbody>	
</table>

<? if ($_SERVER['REMOTE_ADDR'] == '10.211.14.63') { // meteo si c'est l'écran d'accueil du comptoir ?>

<h2>Météo à 14 jours</h2>
<?
// recuperation de la page meteo
$html_global = join('',file('http://france.meteoconsult.fr/meteo/plescop/france/prevision_meteo_plescop_france_ville__33052_0.php?vue=simple#ancre_chemin_fer'));

// on extrait le cadre qui nous interesse
preg_match('/(<div id="menu_quatorze_jours_ville".*?>.+?)<div class="clear" style="height:0px;">/is', $html_global,$regs);
$html_meteo = $regs[1];

// remove publicité pour abonnement et autre fioriture
$html_meteo = preg_replace('/<div style="position:absolute; left:181px; top:0;">.+?<\/div>/is','', $html_meteo);
$html_meteo = preg_replace('/width:9[96]0px/is','', $html_meteo);
$html_meteo = preg_replace('/position:relative/is','', $html_meteo);
$html_meteo = preg_replace('/border-right:0px/is','', $html_meteo);

// affiche le cadre
echo $html_meteo;
?>

<style type="text/css">
#menu_quatorze_jours_ville li {
	border: 1px solid grey;
    display: block;
    float: left;
    margin: 2px;
}
</style>

<? } ?>

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

	$heure_hhmmss = sprintf('%02d:%02d:%02d',$regs[1],$regs[2],$regs[3]);
	if ($heure_hhmmss == '00:00:00') $heure_hhmmss = '';

	return $heure_hhmmss;
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


function get_human_readable_delay($second1,$second2) {
	$delay = dateDiff($second1,$second2);
	return 	($delay['days'] ? $delay['days'].'d ':'').
			($delay['hours'] ? $delay['hours'].'h ':'').
			($delay['minutes'] ? $delay['minutes'].'m ':'').
			($delay['seconds'] ? $delay['seconds'].'s ':'')
		;
}

function get_next_open_day($format) {
	$add_day = 0;

	$today_week_day = date('w');
	if ($today_week_day <= 4) // du dimanche au jeudi, on ajoute un jour
		$add_day = 1;
	elseif ($today_week_day == 5) // le vendredi on ajoute 3 jours
		$add_day = 3;
	elseif ($today_week_day == 6) // le samedi on ajoute 2 jours
		$add_day = 2;

	return date($format,strtotime("+$add_day day"));
}
?>