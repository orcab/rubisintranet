<? include('../../../inc/config.php');

if (isset($_GET['code_artisan']) && $_GET['code_artisan'])
	$_POST['code_artisan'] = $_GET['code_artisan'];
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Taux de service Rubis</title>

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
	margin-top:5px;
	border:solid 1px grey;
	padding:20px;
	width:80%;
}

#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    width:100%;
    margin:auto;
    margin-top: 1em;
    font-size: 1em;
}
#lignes th, #lignes td {
    border: 1px solid #999;
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

.legende {
    margin: auto;
    margin-top: 2em;
    font-size: 0.7em;
    width: 55%;
}

tr.end-of-day {
    border-bottom: solid 2px #777;
}

tfoot {
    background-color: #CCC;
    border: solid 2px #555;
}

.pourcent-good {
	background-color:#33cc66;
}

.pourcent-medium {
	background-color:#ff950e;
}

.pourcent-bad {
	background-color:#ff0000;
}

#color-legend {
	width:15%;
	margin:auto;
	margin-top:1em;
}

#color-legend > div {
	height:2em;
	margin:auto;
}

.bon_cpt {
	border-width: 2px;
}

.euro {
	display:none;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../../js/jquery.js"></script>
<script type="text/javascript" src="../../../js/jquery-ui-1.10.3.custom.min.js"></script>

<!-- pour le chosen -->
<script language="javascript" src="../../../js/chosen/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="../../../js/chosen/chosen.css" />

<script language="javascript">
<!--

//initialise les date picker
$.datepicker.setDefaults({
 	dateFormat:'dd/mm/yy',
 	beforeShowDay: $.datepicker.noWeekends,
	changeMonth: true,
	changeYear:true,
	firstDay: 1,
	dayNamesShort: 		[ 'Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam' ],
	dayNames: 			[ 'Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi' ],
	dayNamesMin: 		[ 'Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa' ],
	monthNamesShort: 	['Jan','Fev','Mar','Avr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Déc'],
	monthNames: 		['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre']
});

var pourcent_good 	= <?= isset($_POST['pourcent-good']) 	? $_POST['pourcent-good']	:'95' ?>;
var pourcent_medium = <?= isset($_POST['pourcent-medium']) 	? $_POST['pourcent-medium']	:'90' ?>;

$(document).ready(function(){
	
	// plugin chosen
	$(".chzn-select").chosen();

	$('#date_from').datepicker({
		onClose: function( selectedDate ) {
			$( '#date_to' ).datepicker( 'option', 'minDate', selectedDate );
		}
	});

	$('#date_to').datepicker({
	 	onClose: function( selectedDate ) {
			$( '#date_from' ).datepicker( 'option', 'maxDate', selectedDate );
		}
	});
	
	$('#pourcent-good').val(pourcent_good);
	$('#pourcent-medium').val(pourcent_medium);
	$('#pourcent-bad').text(pourcent_medium);
	update_color();		

	$('#pourcent-good').keyup(function(){
		pourcent_good = $(this).val();
		update_color();
	});

	$('#pourcent-medium').keyup(function(){
		pourcent_medium = $(this).val();
		$('#pourcent-bad').text(pourcent_medium);
		update_color();
	});

	$('input:radio[name=choix_unite]').click(function(){
		//console.log($(this).val());
		var valeur = $(this).val();
		if (valeur == 'pourcentage') {
			$('.euro').css('display','none');
			$('.pourcentage').css('display','table-cell');
		} else if (valeur == 'euro') {
			$('.pourcentage').css('display','none');
			$('.euro').css('display','table-cell');
		} else if (valeur == 'les_deux') {
			$('.pourcentage').css('display','table-cell');
			$('.euro').css('display','table-cell');
		} else {
			$('.pourcentage').css('display','none');
			$('.euro').css('display','none');
		}
	});
});


function update_color() {
	$('.pourcent').each(function(){
	 	var pourcent = parseInt($(this).text());
	 	if 		(pourcent >= pourcent_good) 	$(this).removeClass('pourcent-bad pourcent-medium').addClass('pourcent-good');
	 	else if (pourcent >= pourcent_medium) 	$(this).removeClass('pourcent-bad pourcent-good').addClass('pourcent-medium');
	 	else 									$(this).removeClass('pourcent-medium pourcent-good').addClass('pourcent-bad');
	 });
}

function verif_form(){
	var form = document.cde;
	var erreur = false;

	if (!form.date_from.value) {
		alert("Veuillez préciser une date de départ");
		erreur = true;
	}
	if (!form.date_to.value) {
		alert("Veuillez préciser une date de fin");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<form name="cde" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="action" value="taux_service" />
<div id="recherche">
	<h1>Voir les taux de service Rubis</h1>
	Des commandes du  <input type="text" id="date_from" name="date_from" value="<?= isset($_POST['date_from']) ? $_POST['date_from']:''?>" size="10" maxlength="10"/>
	au commandes du <input type="text" id="date_to" name="date_to" value="<?= isset($_POST['date_to']) ? $_POST['date_to']:''?>" size="10" maxlength="10"/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Voir les taux de service Rubis</a><br/>
	<label for="code_artisan">Limiter au code artisan</label> <input type="text" id="code_artisan" name="code_artisan" value="<?= isset($_POST['code_artisan']) ? $_POST['code_artisan']:''?>" size="6" maxlength="6"/> (exemple : 056032)<br/>
	<input type="checkbox" name="reservation" 				id="reservation" 			<?= isset($_POST['reservation'])			? 'checked="checked"':'' ?> /> <label for="reservation">Inclure les SPE</label><br/>
	<input type="checkbox" name="cession" 					id="cession" 				<?= isset($_POST['cession']) 				? 'checked="checked"':'' ?> /> 	<label for="cession">Inclure les cessions</label><br/>
	<input type="checkbox" name="all_client" 				id="all_client" 			<?= isset($_POST['all_client']) 			? 'checked="checked"':'' ?> /> 	<label for="all_client">Inclure tous les types de clients (coop, employés, perso, ...)</label><br/>
	<span style="position:relative;top:-10px;">Class produit : </span><select name="class[]" id="class" data-placeholder="Class" style="width:300px;" multiple="multiple" class="chzn-select">
 		<? foreach (array('A','B','C','D','E','vide') as $c) { ?>
 			<option value="<?=$c?>"<?=isset($_POST['class']) && is_array($_POST['class']) && in_array($c,$_POST['class']) ? ' selected="selected"':''?>><?=$c?></option>
		<? } ?>
	</select>
</div>

<?
//var_dump($_POST);
if (	isset($_POST['action']) && $_POST['action'] == 'taux_service'
	&&	isset($_POST['date_from']) && $_POST['date_from'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_from'])
	&&	isset($_POST['date_to']) && $_POST['date_to'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_to'])
	) {	

$table = '';

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_from'],$regs);
$date_from = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_to'],$regs);
$date_to = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

// on inclu les autre type de clients (coop, employes, perso...)
$all_client = " and ENTETE.NOCLI like '056%' ";
if(isset($_POST['all_client']))
	$all_client = '';

// on inclu les autre type de clients (coop, employes, perso...)
$client = '';
if(isset($_POST['code_artisan']) && $_POST['code_artisan'])
	$client = " and ENTETE.NOCLI = '".mysql_escape_string($_POST['code_artisan'])."' ";

// on inclu les cessions si la case n'est pas cochée
$cession = " and ENTETE.NOCLI not like 'CES%' ";
if(isset($_POST['cession']))
	$cession = '';
 
// on rajoute les produit réservé dans les calculs
$reservation = " and TYCDD='STO' ";
if(isset($_POST['reservation']))
	$reservation = '' ;

// on restreint sur les class
$class = '';
if (isset($_POST['class']) && is_array($_POST['class'])) {
	if (sizeof($_POST['class']) == 0 || sizeof($_POST['class']) == 6) {
		// ne rien faire, on veut toutes les class
	} else {
		// faire une restriction sur les class demandées
		$class_r = array();
		$table .= " left join AFAGESTCOM.ASTOFIP1 FICHE_STOCK on DETAIL.CODAR=FICHE_STOCK.NOART and FICHE_STOCK.DEPOT='AFA' ";
		foreach ($_POST['class'] as $c) {
			if ($c == 'vide') $c = '';
			$class_r[] = " FICHE_STOCK.STCLA='".mysql_escape_string($c)."' ";
		}
		$class = '('.join(" OR ",$class_r).')';
	}
}
if (strlen($class)>0)
	$class = " and $class ";

//var_dump($_POST);


$sql = <<<EOT
select 
--ENTETE.NOCLI,
--ENTETE.NOBON,
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='ANN' and PROFI='1' $reservation $class) as LIGNES_ANNULEES,
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='R' $reservation $class) as LIGNES_RELIQUATS,
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' and CONCAT(DETAIL.DTLIS,CONCAT(DETAIL.DTLIA,CONCAT(DETAIL.DTLIM,DETAIL.DTLIJ))) > CONCAT(ENTETE.DLSSB,CONCAT(ENTETE.DLASB,CONCAT(ENTETE.DLMSB,ENTETE.DLJSB))) $reservation $class) as LIGNES_LIVREES_EN_RETARD
--CONCAT(DLSSB,CONCAT(DLASB,CONCAT(DLMSB,DLJSB))) as DATE_LIV,
(DTBOS || DTBOA || '-' || DTBOM || '-' || DTBOJ) as DATE_BON,
count(*) as NB_CDE,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' $reservation $class)) as LIGNES_COMMANDEES,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' $reservation $class)) as LIGNES_LIVREES,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL $table where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' and CONCAT(DETAIL.DTLIS,CONCAT(DETAIL.DTLIA,CONCAT(DETAIL.DTLIM,DETAIL.DTLIJ))) <= CONCAT(ENTETE.DLSSB,CONCAT(ENTETE.DLASB,CONCAT(ENTETE.DLMSB,ENTETE.DLJSB))) $reservation $class)) as LIGNES_LIVREES_A_TEMPS

from
	AFAGESTCOM.AENTBOP1 ENTETE

where
		(DTBOS || DTBOA || DTBOM || DTBOJ) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and	(DTBOS || DTBOA || DTBOM || DTBOJ) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'
	and ETSEE=''
	$cession
	$all_client
	$client

group by 
	DTBOS,DTBOA,DTBOM,DTBOJ

order by
	DTBOS ASC,DTBOA ASC,DTBOM ASC,DTBOJ ASC
EOT;

//echo "<pre>$sql</pre>";

$sql2 = <<<EOT
select
	(DTBOS || DTBOA || '-' || DTBOM || '-' || DTBOJ) as DATE_BON,
	count(*) as NB_CDE,
	CDCAM as TYPE_CDE,
	LIVSB as VENDEUR,
	SUM(MONTBT) as MONTANT

from
	AFAGESTCOM.AENTBOP1 ENTETE

where
		(DTBOS || DTBOA || DTBOM || DTBOJ) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and	(DTBOS || DTBOA || DTBOM || DTBOJ) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'
	and ETSEE=''
	$cession
	$all_client
	$client

group by 
	DTBOS,DTBOA,DTBOM,DTBOJ,CDCAM,LIVSB

order by
	DTBOS ASC,DTBOA ASC,DTBOM ASC,DTBOJ ASC
EOT;

//echo "<pre>$sql2</pre>";

$loginor  	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res 		= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
$res2 		= odbc_exec($loginor,$sql2)  or die("Impossible de lancer la requete 2 : $sql2");
?>

<table id="lignes">
	<caption>
		Taux de service Rubis du <?=$_POST['date_from']?> au <?=$_POST['date_to']?>
		&nbsp;&nbsp;&nbsp;&nbsp;
		Voir les statistiques
		en %<input type="radio" name="choix_unite" value="pourcentage" checked="checked"/>&nbsp;&nbsp;
		en &euro;<input type="radio" name="choix_unite" value="euro"/>&nbsp;&nbsp;
		% &euro;<input type="radio" name="choix_unite" value="les_deux"/>&nbsp;&nbsp;
		rien<input type="radio" name="choix_unite" value="non"/>
	</caption>
	<thead>
	<tr>
		<th>Date bon</th>
		<th>Jour</th>
		<th>Nb cde</th>
		<th>Lignes / cde</th>
		<th>Lignes commandées</th>
		<th>Lignes livrés</th>
		<th>Lignes en reliquats</th>
		<th>% de livrées</th>
		<th>Lignes livrées à temps</th>
		<th>Lignes livrées en retard</th>
		<th>% de livrées à temps / livrées</th>
		<th>% de livrées à temps / commandées</th>
		<th style="border-left-width:3px;" class="bon_cpt pourcentage">% CDE CPT</th>
		<th class="montant_cpt pourcentage">% CA CPT</th>
		<th style="border-left-width:3px;" class="bon_dis pourcentage">% CDE DIS</th>
		<th class="montant_dis pourcentage">% CA DIS</th>
		<th style="border-left-width:3px;" class="bon_exp pourcentage">% CDE EXP</th>
		<th class="montant_exp pourcentage">% CA EXP</th>
		<th style="border-left-width:3px;" class="bon_ldp pourcentage">% CDE LDP</th>
		<th class="montant_ldp pourcentage">% CA LDP</th>
		<th style="border-left-width:3px;" class="bon_lso pourcentage">% CDE LSO</th>
		<th class="montant_lso pourcentage">% CA LSO</th>
		<th style="border-left-width:3px;" class="bon_web pourcentage">% CDE WEB</th>
		<th class="montant_web pourcentage">% CA WEB</th>

		<th style="border-left-width:3px;" class="bon_cpt euro">CDE CPT</th>
		<th class="montant_cpt euro">&euro; CA CPT</th>
		<th style="border-left-width:3px;" class="bon_dis euro">CDE DIS</th>
		<th class="montant_dis euro">&euro; CA DIS</th>
		<th style="border-left-width:3px;" class="bon_exp euro">CDE EXP</th>
		<th class="montant_exp euro">&euro; CA EXP</th>
		<th style="border-left-width:3px;" class="bon_ldp euro">CDE LDP</th>
		<th class="montant_ldp euro">&euro; CA LDP</th>
		<th style="border-left-width:3px;" class="bon_lso euro">CDE LSO</th>
		<th class="montant_lso euro">&euro; CA LSO</th>
		<th style="border-left-width:3px;" class="bon_web euro">CDE WEB</th>
		<th class="montant_web euro">&euro; CA WEB</th>
	</tr>
	</thead>
	<tbody>
<?
	$nb_cde			 	= 0;
	$total_cde		 	= 0;

	$order_day			= 0;
	$cancel_day 		= 0;
	$deliver_day		= 0;
	$reliquat_day		= 0;
	$in_time_day 		= 0;
	$out_time_day 		= 0;

	$total_order 		= 0;
	$total_cancel 		= 0;
	$total_deliver		= 0;
	$total_reliquat 	= 0;
	$total_in_time 		= 0;
	$total_out_time 	= 0;
	$old_day = '';

	// regarde les pourcentage de type de cde
	$type_cde_stats = array('TOTAL_BON_   '=>0,'TOTAL_BON_CPT'=>0,'TOTAL_BON_DIS'=>0,'TOTAL_BON_EXP'=>0,'TOTAL_BON_LDP'=>0,'TOTAL_BON_LSO'=>0,'TOTAL_BON_WEB'=>0,
							'TOTAL_MONTANT_   '=>0,'TOTAL_MONTANT_CPT'=>0,'TOTAL_MONTANT_DIS'=>0,'TOTAL_MONTANT_EXP'=>0,'TOTAL_MONTANT_LDP'=>0,'TOTAL_MONTANT_LSO'=>0,'TOTAL_MONTANT_WEB'=>0,'TOTAL_MONTANT'=>0);
	while($row = odbc_fetch_array($res2)) {
		// initialise les compteurs
		if (!isset($type_cde_stats[$row['DATE_BON']])) // nouvelle date, on cree
			$type_cde_stats[$row['DATE_BON']] = array('BON_   '=>0,'BON_CPT'=>0,'BON_DIS'=>0,'BON_EXP'=>0,'BON_LDP'=>0,'BON_LSO'=>0,'BON_WEB'=>0,'MONTANT_   '=>0,'MONTANT_CPT'=>0,'MONTANT_DIS'=>0,'MONTANT_EXP'=>0,'MONTANT_LDP'=>0,'MONTANT_LSO'=>0,'MONTANT_WEB'=>0,'TOTAL_MONTANT'=>0);

		// compte les type de cde
		$type_cde_stats[$row['DATE_BON']]['BON_'.$row['TYPE_CDE']] += $row['NB_CDE'];
		$type_cde_stats[$row['DATE_BON']]['MONTANT_'.$row['TYPE_CDE']] += $row['MONTANT'];
		$type_cde_stats[$row['DATE_BON']]['TOTAL_MONTANT'] += $row['MONTANT'];
		
		// compte les cde web
		if ($row['VENDEUR'] == 'WEB') {
			$type_cde_stats[$row['DATE_BON']]['BON_WEB'] += $row['NB_CDE'];
			$type_cde_stats['TOTAL_BON_WEB'] += $row['NB_CDE'];
			$type_cde_stats[$row['DATE_BON']]['MONTANT_WEB'] += $row['MONTANT'];
			$type_cde_stats['TOTAL_MONTANT_WEB'] += $row['MONTANT'];
		}

		// cumul des totaux
		$type_cde_stats['TOTAL_BON_'.$row['TYPE_CDE']] += $row['NB_CDE'];
		$type_cde_stats['TOTAL_MONTANT_'.$row['TYPE_CDE']] += $row['MONTANT'];
		$type_cde_stats['TOTAL_MONTANT'] += $row['MONTANT'];
	}

	while($row = odbc_fetch_array($res)) {
		if ($old_day == '') { // premier record
			$old_day = $row['DATE_BON'];
			$order_day = $cancel_day = $deliver_day = $reliquat_day = $in_time_day = $out_time_day = 0; // on reset les compteurs
		}

		if ($old_day != $row['DATE_BON']) { // changemnt de journée --> on affiche les infos récoltée
			afficheInfo();
			$order_day = $cancel_day = $deliver_day = $reliquat_day = $in_time_day = $out_time_day = 0; // on reset les compteurs
		} // fin new day

		
		$total_cde += $row['NB_CDE'];

		$nb_cde 			 = $row['NB_CDE'];
		$order_day 			+= $row['LIGNES_COMMANDEES'];
//		$cancel_day 		+= $row['LIGNES_ANNULEES'];
		$deliver_day 		+= $row['LIGNES_LIVREES'];
//		$reliquat_day 		+= $row['LIGNES_RELIQUATS'];
		$reliquat_day 		+= $row['LIGNES_COMMANDEES'] - $row['LIGNES_LIVREES'];
		$in_time_day 		+= $row['LIGNES_LIVREES_A_TEMPS'];
//		$out_time_day 		+= $row['LIGNES_LIVREES_EN_RETARD'];
		$out_time_day 		+= $row['LIGNES_LIVREES'] - $row['LIGNES_LIVREES_A_TEMPS'];

		$total_order 		+= $row['LIGNES_COMMANDEES'];
//		$total_cancel		+= $row['LIGNES_ANNULEES'];
		$total_deliver		+= $row['LIGNES_LIVREES'];
//		$total_reliquat  	+= $row['LIGNES_RELIQUATS'];
		$total_reliquat  	+= $row['LIGNES_COMMANDEES'] - $row['LIGNES_LIVREES'];
		$total_in_time		+= $row['LIGNES_LIVREES_A_TEMPS'];
//		$total_out_time		+= $row['LIGNES_LIVREES_EN_RETARD'];
		$total_out_time		+= $row['LIGNES_LIVREES'] - $row['LIGNES_LIVREES_A_TEMPS'];

		$old_day = $row['DATE_BON'];
	} // fin while
	odbc_close($loginor);
	afficheInfo();
?>
	</tobdy>
	<tfoot>
	<tr class="start-of-total">
		<td colspan="2">Total période</td>
		<td rowspan="1"><?=$total_cde?></td>
		<td><?=sprintf('%0.2f',$total_order / $total_cde)?></td>
		<td><?=$total_order?></td>
		<td><?=$total_deliver?></td>
		<td><?=$total_reliquat?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100*$total_deliver / $total_order)?></td>
		<td><?=$total_in_time?></td>
		<td><?=$total_out_time?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100*$total_in_time / $total_deliver)?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100*$total_in_time / $total_order)?></td>

		<td style="border-left-width:3px;" class="bon_cpt pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_CPT'] / $total_cde)?></td>
		<td class="montant_cpt pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_CPT'] / $type_cde_stats['TOTAL_MONTANT'])?></td>
		<td style="border-left-width:3px;" class="bon_dis pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_DIS'] / $total_cde)?></td>
		<td class="montant_dis pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_DIS'] / $type_cde_stats['TOTAL_MONTANT'])?></td>
		<td style="border-left-width:3px;" class="bon_exp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_EXP'] / $total_cde)?></td>
		<td class="montant_exp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_EXP'] / $type_cde_stats['TOTAL_MONTANT'])?></td>
		<td style="border-left-width:3px;" class="bon_ldp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_LDP'] / $total_cde)?></td>
		<td class="montant_ldp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_LDP'] / $type_cde_stats['TOTAL_MONTANT'])?></td>
		<td style="border-left-width:3px;" class="bon_lso pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_LSO'] / $total_cde)?></td>
		<td class="montant_lso pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_LSO'] / $type_cde_stats['TOTAL_MONTANT'])?></td>
		<td style="border-left-width:3px;" class="bon_web pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_BON_WEB'] / $total_cde)?></td>
		<td class="montant_web pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats['TOTAL_MONTANT_WEB'] / $type_cde_stats['TOTAL_MONTANT'])?></td>

		<td style="border-left-width:3px;" class="bon_cpt euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_CPT']?></td>
		<td class="montant_cpt euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_CPT']?></td>
		<td style="border-left-width:3px;" class="bon_dis euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_DIS']?></td>
		<td class="montant_dis euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_DIS']?></td>
		<td style="border-left-width:3px;" class="bon_exp euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_EXP']?></td>
		<td class="montant_exp euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_EXP']?></td>
		<td style="border-left-width:3px;" class="bon_ldp euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_LDP']?></td>
		<td class="montant_ldp euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_LDP']?></td>
		<td style="border-left-width:3px;" class="bon_lso euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_LSO']?></td>
		<td class="montant_lso euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_LSO']?></td>
		<td style="border-left-width:3px;" class="bon_web euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_BON_WEB']?></td>
		<td class="montant_web euro" rowspan="1"><?=(int)$type_cde_stats['TOTAL_MONTANT_WEB']?></td>
	</tr>				
	</tfoot>
</table>
<? } ?>


<div id="color-legend">
	<div class="pourcent-good">Supérieur à 		<input type="text" name="pourcent-good" 	id="pourcent-good" 	 value="" size="2"/>%</div>
	<div class="pourcent-medium">Supérieur à 	<input type="text" name="pourcent-medium" 	id="pourcent-medium" value="" size="2"/>%</div>
	<div class="pourcent-bad">Inférieur à <span id="pourcent-bad"></span> %</div>
</div>
</form>
</body>
</html>

<?

function afficheInfo() {
	global $type_cde_stats,$nb_cde,$old_day,$order_day,$order_day,$cancel_day,$deliver_day,$reliquat_day,$in_time_day,$out_time_day,$jours_mini;
?>
<tr class="start-of-day">
	<td rowspan="1"><?=$old_day?></td>
	<td rowspan="1"><?=$jours_mini[date('w',strtotime($old_day))]?></td>
	<td rowspan="1"><?=$nb_cde?></td>
	<td><?=sprintf('%0.2f',$order_day / $nb_cde)?></td>
	<td><?=$order_day?></td>
	<td><?=$deliver_day?></td>
	<td><?=$reliquat_day?></td>
	<td class="pourcent"><?=sprintf('%0.2f',100*$deliver_day / $order_day)?></td>
	<td><?=$in_time_day?></td>
	<td><?=$out_time_day?></td>
	<td class="pourcent"><?=sprintf('%0.2f',100*$in_time_day / $deliver_day)?></td>
	<td class="pourcent"><?=sprintf('%0.2f',100*$in_time_day / $order_day)?></td>

	<td style="border-left-width:3px;" class="bon_cpt pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_CPT'] / $nb_cde)?></td>
	<td class="montant_cpt pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_CPT'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>
	<td style="border-left-width:3px;" class="bon_dis pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_DIS'] / $nb_cde)?></td>
	<td class="montant_dis pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_DIS'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>
	<td style="border-left-width:3px;" class="bon_exp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_EXP'] / $nb_cde)?></td>
	<td class="montant_exp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_EXP'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>
	<td style="border-left-width:3px;" class="bon_ldp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_LDP'] / $nb_cde)?></td>
	<td class="montant_ldp pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_LDP'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>
	<td style="border-left-width:3px;" class="bon_lso pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_LSO'] / $nb_cde)?></td>
	<td class="montant_lso pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_LSO'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>
	<td style="border-left-width:3px;" class="bon_web pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['BON_WEB'] / $nb_cde)?></td>
	<td class="montant_web pourcentage" rowspan="1"><?=sprintf('%0.1f',100*$type_cde_stats[$old_day]['MONTANT_WEB'] / $type_cde_stats[$old_day]['TOTAL_MONTANT'])?></td>

	<td style="border-left-width:3px;" class="bon_cpt euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_CPT']?></td>
	<td class="montant_cpt euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_CPT']?></td>
	<td style="border-left-width:3px;" class="bon_dis euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_DIS']?></td>
	<td class="montant_dis euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_DIS']?></td>
	<td style="border-left-width:3px;" class="bon_exp euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_EXP']?></td>
	<td class="montant_exp euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_EXP']?></td>
	<td style="border-left-width:3px;" class="bon_ldp euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_LDP']?></td>
	<td class="montant_ldp euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_LDP']?></td>
	<td style="border-left-width:3px;" class="bon_lso euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_LSO']?></td>
	<td class="montant_lso euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_LSO']?></td>
	<td style="border-left-width:3px;" class="bon_web euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['BON_WEB']?></td>
	<td class="montant_web euro" rowspan="1"><?=(int)$type_cde_stats[$old_day]['MONTANT_WEB']?></td>
</tr>
<? } ?>