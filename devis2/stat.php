<html>
<head>
<title>Stats des devis</title>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { border:none; }
a { text-decoration:none; }
a:hover { text-decoration:underline; }

#date_start,#date_end {
    font-family: lucida console;
}

</style>

<script language="javascript">

function reload_graph_cde() {
	var date_start	= $('#date_cde_start').val();
	var date_end	= $('#date_cde_end').val();

	if (parseInt(date_start.replace(/-/,'')) > parseInt(date_end.replace(/-/,''))) {
		alert("Attention, la date de départ est supérieur à la date de fin");
		return;
	}

	$('#graph_cde').attr('src','graph_cde.php?'+
		'representant='+$('#choix_representant').val()+
		'&date_start='+date_start+
		'&date_end='+date_end
	);
}



function reload_graph_visite() {
	var date_start	= $('#date_visite_start').val();
	var date_end	= $('#date_visite_end').val();

	if (parseInt(date_start.replace(/-/,'')) > parseInt(date_end.replace(/-/,''))) {
		alert("Attention, la date de départ est supérieur à la date de fin");
		return;
	}

	$('#graph_visite').attr('src','graph_visite.php?'+
		'&date_start='+date_start+
		'&date_end='+date_end
	);
}

</script>

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Stats des devis</div>

<center>
<form name="cde">


<!-- Graphique des devis réalisés -->
	<fieldset>
		<legend>
			<select id="choix_representant" name="choix_representant">
				<option value="tous">Tous les conseillés</option>
<?
				// POUR SELECTIONNER UN REPRÉSENTANT
				include('../inc/config.php');

				$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
				$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT LOWER(representant) as representant, count(*) AS nb
FROM devis
WHERE supprime =0
GROUP BY representant
ORDER BY representant ASC
EOT;

				$res = mysql_query($sql) or die("Ne peux pas trouver les noms des représentants ".mysql_error());
				while($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['representant']?>"><?=ucfirst(strtolower($row['representant']))?> (<?=$row['nb']?>)</option>
<?				} ?>
			</select>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
Entre le <select id="date_cde_start" name="date_cde_start">
<?
// POUR SELECTIONNER UNE DATE DE DEPART
$sql = <<<EOT
SELECT DATE_FORMAT(`date`,'%b %Y') as date_affichage, DATE_FORMAT(`date`,'%Y-%m') as date_param, count(*) AS nb
FROM devis
WHERE supprime=0 and `date`>0
GROUP BY DATE_FORMAT(`date`,'%b %Y')
ORDER BY `date` ASC
EOT;
				$i=0;
				$res = mysql_query($sql) or die("Ne peux pas trouver les dates de devis ".mysql_error());
				while($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['date_param']?>"<?= $i++ == 0 ? ' selected="selected"':'' ?>><?=$row['date_affichage']?> (<?=$row['nb']?>)</option>
<?				} ?>
			</select>


et le <select id="date_cde_end" name="date_cde_end">
<?				// POUR SELECTIONNER UNE DATE DE FIN
				$nb_element = $i;
				$i=0;
				mysql_data_seek($res,0); // reset le curseur
				while($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['date_param']?>" <?= $i++ == $nb_element-1 ? ' selected="selected"':'' ?>><?=$row['date_affichage']?> (<?=$row['nb']?>)</option>
<?				} ?>
			</select>

			<input type="button" class="valider button" value="OK" onclick="reload_graph_cde();"/>
		</legend>
		<img id="graph_cde" src="graph_cde.php" style="margin-bottom:20px;" />
	</fieldset>
<br/>


<!-- Graphique des visites en salle expo -->
<fieldset>
	<legend>
		Entre le <select id="date_visite_start" name="date_visite_start">
<?			// POUR SELECTIONNER UNE DATE DE DEPART
			$i=0;
			mysql_data_seek($res,0); // reset le curseur
			while($row = mysql_fetch_array($res)) { ?>
				<option value="<?=$row['date_param']?>" <?= $i++ == 0 ? ' selected="selected"':'' ?>><?=$row['date_affichage']?></option>
<?			} ?>
			</select>

			et le <select id="date_visite_end" name="date_visite_end">
<?			// POUR SELECTIONNER UNE DATE DE FIN
			$i=0;
			mysql_data_seek($res,0); // reset le curseur
			while($row = mysql_fetch_array($res)) { ?>
				<option value="<?=$row['date_param']?>" <?= $i++ == $nb_element-1 ? ' selected="selected"':'' ?>><?=$row['date_affichage']?></option>
<?			} ?>
			</select>
		<input type="button" class="valider button" value="OK" onclick="reload_graph_visite();"/>
	</legend>
	<img id="graph_visite" src="graph_visite.php" style="margin-bottom:20px;" />
</fieldset>
<br/>

</form>

<!-- Graphique qui mixte les visites et les devis -->
<img src="graph2.php" style="margin-bottom:20px;" />



<div style="margin:auto;width:50%;border:solid 1px grey;padding:10px;"><a href="stats_devis.php">Télécharger les stats au format Excel</div>

<br/>
<!-- Rendez-vous par adhérent -->
<img src="graph_adh.php" style="margin-bottom:20px;" />

<br/>
<!-- Camembert par activité -->
<img src="graph_act.php" style="margin-bottom:20px;" />

</center>

</body>
</html>