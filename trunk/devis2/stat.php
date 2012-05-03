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

</style>

<script language="javascript">

function reload_cde() {
	$('#graph_cde').attr('src','graph_cde.php?representant='+$('#choix_representant').val());
	//alert($('#choix_representant').val());
}

</script>

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Stats des devis</div>

<center>
<form name="cde">
	<fieldset>
		<legend>
			<select id="choix_representant" name="choix_representant">
				<option value="tous">Tous les conseillés</option>
<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$sql = <<<EOT
SELECT LOWER(representant) as representant, count( * ) AS nb
FROM devis
WHERE supprime =0
GROUP BY representant
ORDER BY representant ASC
EOT;

$res = mysql_query($sql) or die("Ne peux pas trouver les noms des représentants ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
				<option value="<?=$row['representant']?>"><?=ucfirst(strtolower($row['representant']))?> (<?=$row['nb']?>)</option>
<? } ?>
			</select>
			<input type="button" class="valider button" value="OK" onclick="reload_cde();"/>
		</legend>
		<img id="graph_cde" src="graph_cde.php" style="margin-bottom:20px;" /><!-- STATS DES COMMANDES / DEVIS REALISES -->
	</fieldset>
</form>

<br/>
<img src="graph_visite.php" style="margin-bottom:20px;" />
<br/>
<img src="graph2.php" style="margin-bottom:20px;" />

<div style="margin:auto;width:50%;border:solid 1px grey;padding:10px;"><a href="stats_devis.php">Télécharger les stats au format Excel</div>

<br/>
<img src="graph_adh.php" style="margin-bottom:20px;" />

<br/>
<img src="graph_act.php" style="margin-bottom:20px;" />

</center>

</body>
</html>