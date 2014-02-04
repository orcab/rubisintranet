<? include('../../../inc/config.php'); ?>
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
    width:80%;
    margin:auto;
    margin-top: 1em;
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

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../../js/jquery.js"></script>
<script type="text/javascript" src="../../../js/jquery-ui-1.10.3.custom.min.js"></script>
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
	
	$( '#date_from' ).datepicker({
		onClose: function( selectedDate ) {
			$( '#date_to' ).datepicker( 'option', 'minDate', selectedDate );
		}
	});

	$( '#date_to' ).datepicker({
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
	<input type="checkbox" name="reservation" 				id="reservation" 			<?= isset($_POST['reservation'])			? 'checked="checked"':'' ?> /> <label for="reservation">Inclure les SPE</label><br/>
	<input type="checkbox" name="cession" 					id="cession" 				<?= isset($_POST['cession']) 				? 'checked="checked"':'' ?> /> 	<label for="cession">Inclure les cessions</label><br/>
	<input type="checkbox" name="all_client" 				id="all_client" 			<?= isset($_POST['all_client']) 			? 'checked="checked"':'' ?> /> 	<label for="all_client">Inclure tous les types de clients (coop, employés, perso, ...)</label><br/>
<!--	<input type="checkbox" name="only_first_preparation" 	id="only_first_preparation" <?= isset($_POST['only_first_preparation']) ? 'checked="checked"':'' ?> /> 	<label for="only_first_preparation">Inclure seulement les premières descente en préparation (-001)</label>
-->
</div>


<?
//var_dump($_POST);

if (	isset($_POST['action']) && $_POST['action'] == 'taux_service'
	&&	isset($_POST['date_from']) && $_POST['date_from'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_from'])
	&&	isset($_POST['date_to']) && $_POST['date_to'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_to'])
	) {	

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_from'],$regs);
$date_from = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_to'],$regs);
$date_to = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

// on inclu les autre type de clients (coop, employes, perso...)
$all_client = " and ENTETE.NOCLI like '056%' ";
if(isset($_POST['all_client']))
	$all_client = '';

// on inclu les cessions si la case n'est pas cochée
$cession = " and ENTETE.NOCLI not like 'CES%' ";
if(isset($_POST['cession']))
	$cession = '';
 
// on rajoute les produit réservé dans les calculs
$reservation = " and TYCDD='STO' ";
if(isset($_POST['reservation']))
	$reservation = '' ;

// on ne tient compte que des premieres descentes en preparation
/*
$only_first_preparation = '';
if(isset($_POST['only_first_preparation']))
	$only_first_preparation = " and OERODP like '%-001' ";
*/

$sql = <<<EOT
select 
--ENTETE.NOCLI,
--ENTETE.NOBON,
CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) as DATE_BON,
count(*) as NB_CDE,
--CONCAT(DLSSB,CONCAT(DLASB,CONCAT(DLMSB,DLJSB))) as DATE_LIV,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' $reservation)) as LIGNES_COMMANDEES,
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='ANN' and PROFI='1' $reservation) as LIGNES_ANNULEES,
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='R' $reservation) as LIGNES_RELIQUATS,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' $reservation)) as LIGNES_LIVREES,
SUM((select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' and CONCAT(DETAIL.DTLIS,CONCAT(DETAIL.DTLIA,CONCAT(DETAIL.DTLIM,DETAIL.DTLIJ))) <= CONCAT(ENTETE.DLSSB,CONCAT(ENTETE.DLASB,CONCAT(ENTETE.DLMSB,ENTETE.DLJSB))) $reservation)) as LIGNES_LIVREES_A_TEMPS
--(select count(*) from AFAGESTCOM.ADETBOP1 DETAIL where ENTETE.NOBON=DETAIL.NOBON and ENTETE.NOCLI=DETAIL.NOCLI and ETSBE='' and PROFI='1' and TRAIT='F' and CONCAT(DETAIL.DTLIS,CONCAT(DETAIL.DTLIA,CONCAT(DETAIL.DTLIM,DETAIL.DTLIJ))) > CONCAT(ENTETE.DLSSB,CONCAT(ENTETE.DLASB,CONCAT(ENTETE.DLMSB,ENTETE.DLJSB))) $reservation) as LIGNES_LIVREES_EN_RETARD

from AFAGESTCOM.AENTBOP1 ENTETE

where
		CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and	CONCAT(DTBOS,CONCAT(DTBOA,CONCAT(DTBOM,DTBOJ))) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'
	$cession
	$all_client

group by 
	DTBOS,DTBOA,DTBOM,DTBOJ

order by
	DTBOS ASC,DTBOA ASC,DTBOM ASC,DTBOJ ASC
EOT;

//echo "<pre>$sql</pre>";

$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
?>

<table id="lignes">
	<caption>
		Taux de service Rubis du <?=$_POST['date_from']?> au <?=$_POST['date_to']?>
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

	while($row = odbc_fetch_array($res)) {
		if ($old_day == '') { // premier record
			$old_day = $row['DATE_BON'];
			$order_day = $cancel_day = $deliver_day = $reliquat_day = $in_time_day = $out_time_day = 0; // on reset les compteurs
		}

		if ($old_day != $row['DATE_BON']) { // changemnt de journée --> on affiche les infos récoltée
			afficheInfo();
			$order_day = $cancel_day = $deliver_day = $reliquat_day = $in_time_day = $out_time_day = 0; // on reset les compteurs
		} // fin new day

		
		$total_cde++;

		$nb_cde 			= $row['NB_CDE'];
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
						<td class="pourcent"><?=(int)(100*$total_deliver / $total_order)?></td>
						<td><?=$total_in_time?></td>
						<td><?=$total_out_time?></td>
						<td class="pourcent"><?=(int)(100*$total_in_time / $total_deliver)?></td>
						<td class="pourcent"><?=(int)(100*$total_in_time / $total_order)?></td>
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
	global $nb_cde,$old_day,$order_day,$order_day,$cancel_day,$deliver_day,$reliquat_day,$in_time_day,$out_time_day,$jours_mini;
?>
	<tr class="start-of-day">
		<td rowspan="1"><?=$old_day?></td>
		<td rowspan="1"><?=$jours_mini[date('w',strtotime($old_day))]?></td>
		<td rowspan="1"><?=$nb_cde?></td>
		<td><?=sprintf('%0.2f',$order_day / $nb_cde)?></td>
		<td><?=$order_day?></td>
		<td><?=$deliver_day?></td>
		<td><?=$reliquat_day?></td>
		<td class="pourcent"><?=(int)(100*$deliver_day / $order_day)?></td>
		<td><?=$in_time_day?></td>
		<td><?=$out_time_day?></td>
		<td class="pourcent"><?=(int)(100*$in_time_day / $deliver_day)?></td>
		<td class="pourcent"><?=(int)(100*$in_time_day / $order_day)?></td>
	</tr>
<? } ?>