<? include('../../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Taux de service</title>

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
    margin-top: 1em;
    width:55%;
    margin:auto;
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

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../../js/jquery.js"></script>
<script type="text/javascript" src="../../../js/jquery-ui-1.10.3.custom.min.js"></script>
<script language="javascript">
<!--

$(document).ready(function(){
	 $( '#date_from' ).datepicker({
	 	dateFormat:'dd/mm/yy',
		changeMonth: true, changeYear:true,
		onClose: function( selectedDate ) {
				$( '#date_to' ).datepicker( 'option', 'minDate', selectedDate );
		}
	});

	 $( '#date_to' ).datepicker({
	 	dateFormat:'dd/mm/yy',
		changeMonth: true, changeYear:true,
		onClose: function( selectedDate ) {
				$( '#date_from' ).datepicker( 'option', 'maxDate', selectedDate );
		}
	});
});

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
	<h1>Voir les taux de service</h1>
	Du chargement du <input type="text" id="date_from" name="date_from" value="<?= isset($_POST['date_from']) ? $_POST['date_from']:''?>" size="10" maxlength="10"/>
	au chargement du <input type="text" id="date_to" name="date_to" value="<?= isset($_POST['date_to']) ? $_POST['date_to']:''?>" size="10" maxlength="10"/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Voir les taux de service</a><br/>
	<input type="checkbox" name="reservation" id="reservation" <?= isset($_POST['reservation']) ? 'checked="checked"':'' ?> /> <label for="reservation">Inclure les réservations</label>
</div>
</form>


<?
// on met a jour les état envoyée a reflex dans Rubis
if (	isset($_POST['action']) && $_POST['action'] == 'taux_service'
	&&	isset($_POST['date_from']) && $_POST['date_from'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_from'])
	&&	isset($_POST['date_to']) && $_POST['date_to'] && preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $_POST['date_to'])
	) {	

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_from'],$regs);
$date_from = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

preg_match('/^(\d{2})\/(\d{2})\/(\d{2})(\d{2})$/', $_POST['date_to'],$regs);
$date_to = array('jour'=>$regs[1],'mois'=>$regs[2],'siecle'=>$regs[3],'annee'=>$regs[4]);

// on rajoute les produit résvervé dans les calculs
$reservation = " and P1RRSO='' ";
if(isset($_POST['reservation']))
	$reservation = '';

$sql = <<<EOT
select
	OENODP as NUM_ODP,
	OERODP as REF_DONNEUR_ORDRE,
	OERODD as REF_COMMANDE, 
	(RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0' +CONVERT(VARCHAR,OEACHG ),2)+'-'+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+'-'+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG),2)) as DATE_CHARGEMENT,
	
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENODP=P1NODP                   $reservation) 	as LIGNES_COMMANDEES,
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENODP=P1NODP and P1NNSL=0      $reservation) 	as LIGNES_PREPARABLES,
	(select COUNT(*) from RFXPRODDTA.reflex.HLPRPLP    where OENODP=P1NODP and P1QAPR=P1QPRE $reservation)	as LIGNES_PREPAREES,
	(select SUM(P1QAPR) from RFXPRODDTA.reflex.HLPRPLP where OENODP=P1NODP                   $reservation) 	as QTE_COMMANDER,
	(select SUM(P1QAPR) from RFXPRODDTA.reflex.HLPRPLP where OENODP=P1NODP and P1NNSL=0      $reservation) 	as QTE_PREPARABLE,
	(select SUM(P1QPRE) from RFXPRODDTA.reflex.HLPRPLP where OENODP=P1NODP                   $reservation) 	as QTE_PREPAREE

from
	${REFLEX_BASE}.HLODPEP ODP_ENTETE
	left join ${REFLEX_BASE}.HLPRPLP PREPA_DETAIL
		on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
	
where
		RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG ),2) >= '$date_from[siecle]$date_from[annee]$date_from[mois]$date_from[jour]'
	and RIGHT('0'+ CONVERT(VARCHAR,OESCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEACHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEMCHG ),2)+RIGHT('0'+ CONVERT(VARCHAR,OEJCHG),2) <= '$date_to[siecle]$date_to[annee]$date_to[mois]$date_to[jour]'

group by OESCHG ,OEACHG , OEMCHG, OEJCHG, OENODP, OERODD, OERODP
order by OESCHG ASC ,OEACHG ASC, OEMCHG ASC, OEJCHG ASC,OENODP  ASC
EOT;

//echo $sql;

	$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
	$res = odbc_exec($reflex,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
?>

<table id="lignes">
	<caption>
		Taux de service du <?=$_POST['date_from']?> au <?=$_POST['date_to']?>
	</caption>
	<thead>
	<tr>
		<th>Date chargement</th>
		<th>Jour</th>
		<th>Nb cde</th>
		<th>Type</th>
		<th>Commandé</th>
		<th>Préparable</th>
		<th>% préparable</th>
		<th>Préparé</th>
		<th>% préparé</th>
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
						<td>Lignes</td>
						<td><?=$total_ligne?></td>
						<td><?=$total_ligne_doable?></td>
						<td><?=(int)(100*$total_ligne_doable / $total_ligne)?></td>
						<td><?=$total_ligne_done?></td>
						<td><?=(int)(100*$total_ligne_done / $total_ligne)?></td>
					</tr>
					<!--<tr class="end-of-total">
						<td>Qte</td>
						<td><?=$total_qte?></td>
						<td><?=$total_qte_doable?></td>
						<td><?=(int)(100*$total_qte_doable / $total_qte)?></td>
						<td><?=$total_qte_done?></td>
						<td><?=(int)(100*$total_qte_done / $total_qte)?></td>
					</tr>-->
				</tfoot>
			</table>
<? } ?>

<!--<pre><?=$sql?></pre>-->
</body>
</html>

<?

function afficheInfo() {
	global $nb_cde,$old_day,$total_ligne_day,$done_ligne_day,$doable_ligne_day,$total_qte_day,$done_qte_day,$doable_qte_day,$jours_mini;
?>
	<tr class="start-of-day">
		<td rowspan="1"><?=$old_day?></td>
		<td rowspan="1"><?=$jours_mini[date('w',strtotime($old_day))]?></td>
		<td rowspan="1"><?=$nb_cde?></td>
		<td>Lignes</td>
		<td><?=$total_ligne_day?></td>
		<td><?=$doable_ligne_day?></td>
		<td><?=(int)(100*$doable_ligne_day / $total_ligne_day)?></td>
		<td><?=$done_ligne_day?></td>
		<td><?=(int)(100*$done_ligne_day / $total_ligne_day)?></td>
	</tr>
	<!--<tr class="end-of-day">
		<td>Qte</td>
		<td><?=$total_qte_day?></td>
		<td><?=$doable_qte_day?></td>
		<td><?=(int)(100*$doable_qte_day / $total_qte_day)?></td>
		<td><?=$done_qte_day?></td>
		<td><?=(int)(100*$done_qte_day / $total_qte_day)?></td>
	</tr>-->
<? } ?>