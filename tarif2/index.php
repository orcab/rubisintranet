<?php

include('../inc/config.php');
$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

?>
<html>
<head>
<title>Edition du catalogue papier</title>
<style type="text/css">@import url(../js/activite.css);</style>
<style type="text/css">@import url(../js/boutton.css);</style>
<script type="text/javascript" src="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>

<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { border:none; }
a {	text-decoration:none; }
a:hover { text-decoration:underline; }

option { font-size:0.7em; }

option.n1 {
	font-size:0.8em;
	font-weight:bold;
	color:white;
	background-color:#A00;
}

option.n2 { padding-left:10px; }
option.n3 { padding-left:20px;color:#666; }
option.n4 { padding-left:30px;color:#999; }
option.n5 { padding-left:40px;color:#BBB; }

</style>

<script language="javascript">
<!--
function update_path(selecteur) {
//	alert(selecteur.selectedIndex);
	document.getElementById('path').innerHTML = selecteur[selecteur.selectedIndex].value;
}
//-->
</script>

</head>
<body style="margin-left:0px;margin-right:0px;padding:0px;">

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Edition du catalogue papier</div>


<div style="float:right;border:solid 1px black;margin-right:3px;padding:5px;font-family:fixedsys;">
Coef<br/>
<?	foreach (get_defined_constants() as $constante => $valeur) {
		if (eregi('^TARIF_COEF_(.+)',$constante,$regs)) { // une constante de coef tarif ?>
			<?=$regs[1]?> <?=$valeur?><br>
<?		}
	}
?>
</div>

<center>
	<form method="post" name="tarif" action="tarif.php">
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
		<label for="page_de_garde"	class="mobile mobile-block mobile-checked"	style="width:25em;"><input id="page_de_garde"	type="checkbox" name="page_de_garde" checked="checked"	/>Ajouter les pages de garde</label>
		<label for="index_ref"		class="mobile mobile-block"					style="width:25em;"><input id="index_ref"		type="checkbox" name="index_ref"						/>Ajouter l'index des références fabriquant</label>
		<label for="index_code"		class="mobile mobile-block"					style="width:25em;"><input id="index_code"		type="checkbox" name="index_code"						/>Ajouter l'index des codes <?=SOCIETE?></label>
		<label for="sommaire"		class="mobile mobile-block"					style="width:25em;"><input id="sommaire"		type="checkbox" name="sommaire"							/>Ajouter le sommaire</label>
		<label for="equipe"			class="mobile mobile-block"					style="width:25em;"><input id="equipe"			type="checkbox" name="equipe"							/>Ajouter l'&eacute;quipe</label>
<!--		<label for="prix_a_venir"	class="mobile mobile-block mobile-checked"	style="width:25em;"><input id="prix_a_venir"	type="checkbox" name="prix_a_venir"  checked="checked"	/>Prix à venir (si disponible)</label>-->
		<br/>
		<input type="submit" class="button valider pdf" value="Editer en PDF"/>
		<div style="font-size:0.8em;">(attention certaine activit&eacute; peuvent demander un temps de traitement tr&egrave;s long)</div>
	</form>
</center>

</body>
</html>