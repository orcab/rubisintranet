<?
session_start();
include('../../../inc/config.php'); 
?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Taux de service Reflex</title>

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
	dayNamesShort: 		['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
	dayNames: 			['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
	dayNamesMin: 		['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
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
	<h1>Voir les taux de service Reflex</h1>
	Du chargement du <input type="text" id="date_from" name="date_from" value="<?= isset($_POST['date_from']) ? $_POST['date_from']:''?>" size="10" maxlength="10"/>
	au chargement du <input type="text" id="date_to" name="date_to" value="<?= isset($_POST['date_to']) ? $_POST['date_to']:''?>" size="10" maxlength="10"/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Voir les taux de service Reflex</a><br/>
	<input type="checkbox" name="reservation" 				id="reservation" 			<?= isset($_POST['reservation'])			? 'checked="checked"':'' ?> /> <label for="reservation">Inclure les réservations</label><br/>
	<input type="checkbox" name="cession" 					id="cession" 				<?= isset($_POST['cession']) 				? 'checked="checked"':'' ?> /> 	<label for="cession">Inclure les cessions</label><br/>
	<input type="checkbox" name="all_client" 				id="all_client" 			<?= isset($_POST['all_client']) 			? 'checked="checked"':'' ?> /> 	<label for="all_client">Inclure tous les types de clients (coop, employés, perso, ...)</label><br/>
	<input type="checkbox" name="only_first_preparation" 	id="only_first_preparation" <?= isset($_POST['only_first_preparation']) ? 'checked="checked"':'' ?> /> 	<label for="only_first_preparation">Inclure seulement les premières descente en préparation (-001)</label>
</div>


<?
//var_dump($_POST);

// on met a jour les état envoyée a reflex dans Rubis
if (	isset($_POST['action']) && $_POST['action'] == 'taux_service'
	&&	isset($_POST['date_from']) && $_POST['date_from'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_from'])
	&&	isset($_POST['date_to']) && $_POST['date_to'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_to'])
	) {	

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_from'],$regs);
$date_from = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_to'],$regs);
$date_to = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

// on inclu les autre type de clients (coop, employes, perso...)
$all_client = " and OECDES like '056%' ";
if(isset($_POST['all_client']))
	$all_client = '';

// on inclu les cessions si la case n'est pas cochée
$cession = " and OECDES not like 'CES%' ";
if(isset($_POST['cession']))
	$cession = '';
 
// on rajoute les produit réservé dans les calculs
$reservation = " and P1RRSO='' ";
if(isset($_POST['reservation']))
	$reservation = '';

// on ne tient compte que des premieres descentes en preparation
$only_first_preparation = '';
if(isset($_POST['only_first_preparation']))
	$only_first_preparation = " and OERODP like '%-001' ";

$sql = <<<EOT
select
	OENANN as ANN_ODP, OENODP as NUM_ODP,
	OERODP as REF_DONNEUR_ORDRE,
	OERODD as REF_COMMANDE, 
	(RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0' +CONVERT(VARCHAR,OEACHG ),2)+'-'+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+'-'+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG),2)) as DATE_CHARGEMENT,
	
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENANN=P1NANO and OENODP=P1NODP                   $reservation) 	as LIGNES_COMMANDEES,
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENANN=P1NANO and OENODP=P1NODP and P1NNSL=0      $reservation) 	as LIGNES_PREPARABLES,
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENANN=P1NANO and OENODP=P1NODP and P1QAPR=P1QPRE $reservation)	as LIGNES_PREPAREES,
	(select SUM(P1QAPR) from RFXPRODDTA.reflex.HLPRPLP where OENANN=P1NANO and OENODP=P1NODP                   $reservation) 	as QTE_COMMANDER,
	(select SUM(P1QAPR) from RFXPRODDTA.reflex.HLPRPLP where OENANN=P1NANO and OENODP=P1NODP and P1NNSL=0      $reservation) 	as QTE_PREPARABLE,
	(select SUM(P1QPRE) from RFXPRODDTA.reflex.HLPRPLP where OENANN=P1NANO and OENODP=P1NODP                   $reservation) 	as QTE_PREPAREE

from
	${REFLEX_BASE}.HLODPEP ODP_ENTETE
	left join ${REFLEX_BASE}.HLPRPLP PREPA_DETAIL
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
	
where
		RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG ),2) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG),2) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'
	$cession
	$all_client
	$only_first_preparation

group by OESCHG ,OEACHG , OEMCHG, OEJCHG, OENANN, OENODP, OERODD, OERODP
order by OESCHG ASC ,OEACHG ASC, OEMCHG ASC, OEJCHG ASC,OENODP  ASC
EOT;

//echo "<pre>$sql</pre>";

$_SESSION['where'] = <<<EOT
RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG ),2) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG),2) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'
$cession
$all_client
$only_first_preparation
$reservation
EOT;

	$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
	$res = odbc_exec($reflex,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
?>

<table id="lignes">
	<caption>
		Taux de service Reflex du <?=$_POST['date_from']?> au <?=$_POST['date_to']?>
	</caption>
	<thead>
	<tr>
		<th>Date chargement</th>
		<th>Jour</th>
		<th>Nb cde</th>
		<th>Lignes / cde</th>
		<th>Lignes commandées</th>
		<td></td>
		<th>Lignes préparables</th>
		<th>% préparable / commandé</th>
		<td></td>
		<th>Lignes préparées</th>
		<th>% préparé / préparable</th>
		<th>% préparé / commandé</th>
	</tr>
	</thead>
	<tbody>
<?
	$nb_cde			 	= 0;
	$total_cde		 	= 0;

	$total_ligne_day 	= 0;
	$doable_ligne_day 	= 0;
	$done_ligne_day 	= 0;
	$total_qte_day 		= 0;
	$doable_qte_day 	= 0;
	$done_qte_day 		= 0;

	$total_ligne 		= 0;
	$total_ligne_doable	= 0;
	$total_ligne_done	= 0;
	$total_qte 			= 0;
	$total_qte_doable 	= 0;
	$total_qte_done 	= 0;
	$old_day = '';

	while($row = odbc_fetch_array($res)) {
		if ($old_day == '') { // premier record
			$old_day = $row['DATE_CHARGEMENT'];
			$nb_cde = $total_ligne_day = $done_ligne_day = $total_qte_day = $done_qte_day = $doable_qte_day = $doable_ligne_day = 0; // on reset les compteurs
		}

		if ($old_day != $row['DATE_CHARGEMENT']) { // changemnt de journée --> on affiche les infos récoltée			
			afficheInfo();
			$nb_cde = $total_ligne_day = $done_ligne_day = $total_qte_day = $done_qte_day = $doable_qte_day = $doable_ligne_day = 0; // on reset les compteurs
		} // fin new day

		$nb_cde++;
		$total_cde++;

		$total_ligne_day 	+= $row['LIGNES_COMMANDEES'];
		$doable_ligne_day 	+= $row['LIGNES_PREPARABLES'];
		$done_ligne_day 	+= $row['LIGNES_PREPAREES'];
		$total_qte_day 		+= $row['QTE_COMMANDER'];
		$doable_qte_day 	+= $row['QTE_PREPARABLE'];
		$done_qte_day 		+= $row['QTE_PREPAREE'];

		$total_ligne 		+= $row['LIGNES_COMMANDEES'];
		$total_ligne_doable	+= $row['LIGNES_PREPARABLES'];
		$total_ligne_done	+= $row['LIGNES_PREPAREES'];
		$total_qte  		+= $row['QTE_COMMANDER'];
		$total_qte_doable	+= $row['QTE_PREPARABLE'];
		$total_qte_done		+= $row['QTE_PREPAREE'];

		$old_day = $row['DATE_CHARGEMENT'];
	} // fin while
	odbc_close($reflex);
	afficheInfo();
?>
				</tobdy>
				<tfoot>
					<tr class="start-of-total">
						<td colspan="2">Total période</td>
						<td rowspan="1"><?=$total_cde?></td>
						<td><?=sprintf('%0.2f',$total_ligne / $total_cde)?></td>
						<td><?=$total_ligne?></td>
						<td></td>
						<td><?=$total_ligne_doable?></td>
						<td class="pourcent"><?=sprintf('%0.2f',100*$total_ligne_doable / $total_ligne)?></td>
						<td></td>
						<td><?=$total_ligne_done?></td>
						<td class="pourcent"><?=sprintf('%0.2f',100*$total_ligne_done / $total_ligne_doable)?></td>
						<td class="pourcent"><?=sprintf('%0.2f',100*$total_ligne_done / $total_ligne)?></td>
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
	global $nb_cde,$old_day,$total_ligne_day,$done_ligne_day,$doable_ligne_day,$total_qte_day,$done_qte_day,$doable_qte_day,$jours_mini,$sql;
?>
	<tr class="start-of-day">
		<td rowspan="1"><?=$old_day?></td>
		<td rowspan="1"><?=$jours_mini[date('w',strtotime($old_day))]?></td>
		<td rowspan="1"><?=$nb_cde?></td>
		<td><?=sprintf('%0.2f',$total_ligne_day / $nb_cde)?></td>
		<td><?=$total_ligne_day?></td>
		<td><a class="btn" href="diff.php?type=1" target="_blank"><i class="icon-list"></i></a></td>
		<td><?=$doable_ligne_day?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100*$doable_ligne_day / $total_ligne_day)?></td>
		<td><a class="btn" href="diff.php?type=2" target="_blank"><i class="icon-list"></i></a></td>
		<td><?=$done_ligne_day?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100* $done_ligne_day / $doable_ligne_day)?></td>
		<td class="pourcent"><?=sprintf('%0.2f',100*$done_ligne_day / $total_ligne_day)?></td>
	</tr>
<? } ?>