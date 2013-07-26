<? include('../../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<META http-equiv="refresh" content="10">
<title>Stock des produits</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
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
    width: 70%;
    margin: auto;
    margin-top:1em;
}
#preparations td {
    border: solid 1px grey;
    padding: 5px;
}
.avancement {
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

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

$(document).ready(function(){
	$('#code_article').focus();
});


function verif_form(){
	var form = document.cde;
	//var value_type_cde = form.type_cde[form.type_cde.selectedIndex].value;
	var erreur = false;

	if (!form.code_article.value) {
		alert("Veuillez pr�ciser un n� de commande");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>
<!--<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>-->

<table id="preparations">
	<thead>
		<tr>
			<th class="num_artisan">Artisan</th>
			<th class="num_commande">Commande</th>
			<th class="avancement">Avancement</th>
			<th class="manquant">Manq.</th>
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

		$sql = <<<EOT
select
	*,
	P1QAPR as QTE_A_PREPARER,P1QPRE as QTE_PREPARER,
	P1TVLP as LIGNE_VALIDEE,
	PEHVPP as HEURE_VALIDATION,
	PEHCRE as HEURE_CREATION,
	DSLDES as LIBELLE_DESTINATAIRE
from
				${REFLEX_BASE}.HLPRENP PREPA_ENTETE
	left join 	${REFLEX_BASE}.HLPRPLP PREPA_DETAIL
		on PREPA_ENTETE.PENANN=PREPA_DETAIL.P1NANP and PREPA_ENTETE.PENPRE=PREPA_DETAIL.P1NPRE
	left join ${REFLEX_BASE}.HLDESTP DESTINATAIRE
		on PREPA_ENTETE.PECDES=DESTINATAIRE.DSCDES
where
		PREPA_ENTETE.PECCPL='CPT'
	and PREPA_ENTETE.PESCRE='$date[siecle]' and PREPA_ENTETE.PEACRE='$date[annee]' and PREPA_ENTETE.PEMCRE='$date[mois]' and PREPA_ENTETE.PEJCRE='$date[jour]'
order by HEURE_CREATION DESC
EOT;

//echo "<pre>$sql</pre><br/>\n";

	$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter � Reflex via ODBC ($REFLEX_DSN)");
	$res = odbc_exec($reflex,$sql)  or die("Impossible de rechercher les prepa CPT du jour : <br/>$sql");


	$old_prepa = '';
	$old_row = array();
	$total_mission = $total_mission_validee = $pourcentage_avancement = 0;
	while($row = odbc_fetch_array($res)) {
		
		if ($old_prepa != "$row[PENANN].$row[PENPRE]" && $old_prepa != '') { // si on change de num de prepa --> on reset les compteur et on cree une nouvelle ligne 
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
			<tr class="<?= $delay['hours']>=1 ? 'more-than-one-hour':''?>">
				<td class="num_artisan"><?=$old_row['LIBELLE_DESTINATAIRE']?></td>
				<td class="num_commande"><?=$old_row['PENANN']?>-<?=$old_row['PENPRE']?></td>
				<td class="avancement" style="background: linear-gradient(to right,#5F5 0%,#CFC <?=$pourcentage_avancement?>%, #FAA <?=$pourcentage_avancement?>%, #F55 100%);">Lignes <?=str_pad($total_mission_validee,2,' ',STR_PAD_LEFT);?>/<?=str_pad($total_mission,2,' ',STR_PAD_LEFT);?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<?=str_pad($pourcentage_avancement,3,' ',STR_PAD_LEFT);?>%</td>
				<td class="manquant <?=$manquant ? 'has_manquant':''?>"><?=$manquant?></td>
				<td class="heure_debut"><?=reflex_hour_to_hhmmss($old_row['HEURE_CREATION'])?></td>
				<td class="heure_fin"><?=reflex_hour_to_hhmmss($old_row['HEURE_VALIDATION'])?></td>
				<td class="realise">
					<?	if ($old_row['HEURE_VALIDATION']) {
							//echo $date->diff(new DateTime('2000-01-20 '.reflex_hour_to_hhmmss($old_row['HEURE_VALIDATION'])),true)->format('%hh %mm %ss %U');
							$delay = dateDiff($date_valid->format('U') , (int)$date_crea->format('U'));
							echo 	($delay['days'] ? $delay['days'].'J ':'').
									($delay['hours'] ? $delay['hours'].'h ':'').
									($delay['minutes'] ? $delay['minutes'].'m ':'').
									($delay['seconds'] ? $delay['seconds'].'s ':'')
								;
						}
					?>
				</td>
			</tr>
<?				
			$total_mission = $total_mission_validee = $pourcentage_avancement = 0;
		}

		if ($row['LIGNE_VALIDEE'])
			$total_mission_validee++;

		$total_mission++;
		$old_prepa = "$row[PENANN].$row[PENPRE]";
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

	return 	array(
					'days' 		=> $delay[0],
					'hours' 	=> $delay[1],
					'minutes' 	=> $delay[2],
					'seconds' 	=> $delay[3],
			);
}

?>