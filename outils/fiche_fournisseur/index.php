<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

?>
<html>
<head>
<title>Fiches Fournisseur</title>
<style>
body { font-family:verdana; font-size:0.9em; }
a img { border:none; }
input,textarea { border:solid 2px #AAA; }
fieldset { -moz-border-radius:6px; border:solid 1px grey; }

div.fournisseur {
	padding:5px;
	padding-left:20px;
	border:solid 1px grey;
	border-top:none;
	text-align:left;
	font-weight:bold;
	background:url(gfx/arrow-mini.png) no-repeat 10px center;
	cursor:pointer;
}

div.fournisseur:hover {
	background-color:#e8f6f8;
}

div#liste-fournisseur div:first-child{ /* premiere case fournisseur */
	border-top:solid 1px grey;
}


div.fournisseur-annule {
	text-decoration:strike-through;
	color:#999;
	font-weight:normal;
	background:url(gfx/hachure.gif);
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript">

// va chercher dans la liste des fournisseurs, les valeurs qui commence par ce qu'a tapé l'utilisateur
function affiche_fournisseur() {
	$('#liste-fournisseur').text('');
	var query = document.selecteur.recherche.value.toLowerCase() ;
	if (query.length >= 1) // si au moins deux caractères de renseigné
		for(i=0; i<fournisseurs.length ; i++) // pour chaque fournisseur
			//$('#debug').append("'"+fournisseurs[i][1].substr(0,query.length)+"'    '"+query+"'<br>");
			if (	fournisseurs[i][1].substr(0,query.length).toLowerCase() == query
				||	fournisseurs[i][2].substr(0,query.length).toLowerCase() == query) // code fournisseur trouvé
				$('#liste-fournisseur').append('<div onclick="goTo(\''+fournisseurs[i][1]+'\')" class="fournisseur' + (!fournisseurs[i][3] ? ' fournisseur-annule':'') + '">' + fournisseurs[i][2] + '</div>');
}

function goTo(id) {
	document.location.href='detail_fournisseur.php?id='+escape(id);
}

// stock la liste des fournisseurs
var fournisseurs = new Array();
<?
// récupère la liste des fournisseurs
$res = mysql_query("SELECT id,nom,code_rubis,affiche FROM fournisseur") or die("ne peux pas retrouver la liste des fournisseurs");
while($row = mysql_fetch_array($res)) { ?>
	fournisseurs.push([<?=$row['id']?>,'<?=addslashes($row['code_rubis'])?>','<?=addslashes($row['nom'])?>',<?=$row['affiche']?>]);
<? } ?>

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<form name="selecteur" style="margin:auto;width:20%;margin-top:10px;">
<fieldset><legend>Rechercher un fournisseur</legend>
<input type="text" name="recherche" size="15" onkeyup="affiche_fournisseur()" value="" />
</fieldset>
</form>

<div id="liste-fournisseur" style="margin:auto;width:40%;margin-top:10px;"></div>

<div id="debug"></div>

</body>
</html>
<?
mysql_close($mysql);
?>