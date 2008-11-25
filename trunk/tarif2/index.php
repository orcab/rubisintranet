<?php

include('../inc/config.php');
$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

?>
<html>
<head>
<title>Edition du catalogue papier</title>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { 
	border:none;
}

a {
	text-decoration:none;
}

a:hover {
	text-decoration:underline;
}

option.n1 {
	font-size:0.8em;
	font-weight:bold;
	color:white;
	background-color:#A00;
}

option.n2,option.n3,option.n4,option.n5 { 	font-size:0.7em; }
option.n2 { padding-left:10px; }
option.n3 { padding-left:20px;color:#666; }
option.n4 { padding-left:30px;color:#999; }
option.n5 { padding-left:40px;color:#BBB; }


</style>
<style type="text/css">@import url(../js/boutton.css);</style>

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
					<option value="<?=$row['chemin']?>" class="n<?=$row['niveau']?>"><?=$row['libelle']?></option>
<?				} ?>
		</select><br/>
		<div id="path">&nbsp;</div>
		<div style="text-align:left;width:30%;"><label for="page_de_garde"><input type="checkbox" name="page_de_garde" checked="checked" /> Ajouter les pages de garde</label></div>
		<div style="text-align:left;width:30%;"><label for="index_ref"><input type="checkbox" name="index_ref" /> Ajouter l'index des références fabriquant</label></div>
		<div style="text-align:left;width:30%;"><label for="index_code"><input type="checkbox" name="index_code" /> Ajouter l'index des codes <?=SOCIETE?></label></div>
		<div style="text-align:left;width:30%;"><label for="sommaire"><input type="checkbox" name="sommaire" /> Ajouter le sommaire</label></div>
		<div style="text-align:left;width:30%;"><label for="equipe"><input type="checkbox" name="equipe" /> Ajouter l'&eacute;quipe</label></div> <br/>
		<input type="submit" class="button valider pdf" value="Editer en PDF"/>
		<div style="font-size:0.8em;">(attention certaine activit&eacute; peuvent demander un temps de traitement tr&egrave;s long)</div>
	</form>
</center>

</body>
</html>