<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message ='' ;

// SAISIR UN COMMENTAIRE
if(isset($_POST['action']) && $_POST['action']=='saisie_intervention' && isset($_POST['id']) && $_POST['id']) { // mode saisie de commentaire fournisseur
	$date = implode('-',array_reverse(explode('/',$_POST['commentaire_date']))).' '.$_POST['commentaire_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO fournisseur_commentaire (code_fournisseur,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ('".mysql_escape_string($_POST['id'])."','$date','".mysql_escape_string($_POST['commentaire_createur'])."','$_POST[commentaire_type]',$_POST[commentaire_humeur],'".mysql_escape_string($_POST['commentaire_commentaire'])."',0)") or die("Ne peux pas enregistrer le commentaire ".mysql_error());
	$message = "L'intervention a été enregistrée";
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Fiches Fournisseur</title>
<style>
body { font-family:verdana; font-size:0.9em; }
a img { border:none; }
input,textarea { border:solid 2px #AAA; }
fieldset { -moz-border-radius:6px; border:solid 1px grey; }

table#liste-fournisseur {
	border-spacing: 0px;
	border-collapse: collapse;
}

tr.fournisseur > td:first-child { /* premiere case qui contient le nom du fournisseur */
	border:solid 1px grey;
	border-top:none;
	font-weight:bold;
	background:url(gfx/arrow-mini.png) no-repeat 10px center;
	padding:3px;
	padding-left:23px;
}

tr.fournisseur > td:nth-child(2) { /* deuxieme case qui contient l'ajout de com' */
	border:solid 1px grey;
	border-top:none;
	background:url(gfx/add-mini.png) no-repeat center -2px;
	width:30px;
}

tr.fournisseur:hover { background-color:#e8f6f8; }
tr.fournisseur > td:nth-child(2):hover { background-color:#FFA; } /* deuxieme case qui contient l'ajout de com' */
table#liste-fournisseur td:first-child, table#liste-fournisseur td:nth-child(2) { border-top:solid 1px grey; } /* premiere et 2eme case fournisseur */
table#liste-fournisseur { cursor:pointer; }

.fournisseur-annule {
	text-decoration:strike-through;
	color:#999;
	font-weight:normal;
	background:url(gfx/hachure.gif);
}

div#intervention {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	-moz-box-shadow: 5px 5px 20px 0px grey;
}


div.date, div.humeur, div.createur, div.type {
	margin-left:10px;
	margin-right:10px;
	float:left;
	text-align:left;
	font-size:0.9em;
}

</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript">

// va chercher dans la liste des fournisseurs, les valeurs qui commence par ce qu'a tapé l'utilisateur
function affiche_fournisseur() {
	$('#liste-fournisseur').html('');
	var query = document.selecteur.recherche.value.toLowerCase() ;
	if (query.length >= 1) // si au moins deux caractères de renseigné
		for(i=0; i<fournisseurs.length ; i++) // pour chaque fournisseur
			//$('#debug').append("'"+fournisseurs[i][0].substr(0,query.length)+"'    '"+query+"'<br>");
			if (	fournisseurs[i][0].substr(0,query.length) == query
				||	fournisseurs[i][1].substr(0,query.length) == query) { // code fournisseur trouvé
				$('#liste-fournisseur').append(
							'<tr class="fournisseur' + (!fournisseurs[i][2] ? ' fournisseur-annule':'') + '">' +
								'<td onclick="goTo(\''+fournisseurs[i][0]+'\')">' +fournisseurs[i][1].toUpperCase() +
							(fournisseurs[i][3] > 0 ? '&nbsp;&nbsp;('+fournisseurs[i][3]+')' : '') + '</td>' +
								'<td onclick="intervention_fournisseur(\'' + fournisseurs[i][0].toUpperCase() + '\');" title="Ajouter une intervention">&nbsp;</td>' +
							'</tr>'
					);
			} // fin if
}	



/* va voir le detail d'un fournisseur */
function goTo(id) {
	document.location.href='detail_fournisseur.php?id='+escape(id);
}



/* affiche la boite de saisie des intervention */
function intervention_fournisseur(code_rubis) {
	$('#fournisseur').text(code_rubis);
	document.selecteur.id.value = code_rubis;

	var maDate = new Date() ;
	document.selecteur.commentaire_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.selecteur.commentaire_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#intervention').css('top',document.body.scrollTop +100);
	$('#intervention').css('left',screen.availWidth / 2 - 300);
	$('#intervention').show();
	document.selecteur.commentaire_commentaire.focus();
}



/* enregsitre l'intervention */
function sauve_intervention() {
	document.selecteur.action.value="saisie_intervention";
	document.selecteur.submit();
}



// stock la liste des fournisseurs
var fournisseurs = new Array();
<?
// récupère la liste des fournisseurs
$res = mysql_query("SELECT id,nom,code_rubis,affiche,(SELECT COUNT(id) FROM fournisseur_commentaire WHERE fournisseur_commentaire.code_fournisseur=fournisseur.code_rubis AND fournisseur_commentaire.supprime=0) AS nb_com FROM fournisseur") or die("ne peux pas retrouver la liste des fournisseurs ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	fournisseurs.push(['<?=addslashes(strtolower($row['code_rubis']))?>','<?=addslashes(strtolower($row['nom']))?>',<?=$row['affiche']?>,<?=$row['nb_com']?>]);
<? } ?>

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<form action="index.php" method="post" name="selecteur" style="margin:auto;width:20%;margin-top:10px;">
<input type="hidden" name="id" value="" />
<input type="hidden" name="action" value="" />

<!-- boite de dialogue pour la intervention fournisseur -->
<div id="intervention">
<table style="">
	<caption style="font-weight:bold;">Saisie d'intervention</caption>
	<tr>
		<td colspan="3">Fournisseur : <span id="fournisseur"></span></td>
		<td><input type="text" name="commentaire_date" size="8" maxlength="10" /> <input type="text" name="commentaire_heure" size="5" maxlength="5" /></td>
	</tr>
	<tr>
		<td>Type</td>
		<td>
			<select name="commentaire_type">
				<option value="visite_mcs">Visite chez MCS</option>
				<option value="visite_fournisseur">Visite chez fournisseur</option>
				<option value="telephone">Téléphone</option>
				<option value="fax">Fax</option>
				<option value="courrier">Courrier</option>
				<option value="email">Email</option>
			</select>
		</td>
		<td>Représentant</td>
		<td>
			<select name="commentaire_createur">
<?			$res2  = mysql_query("SELECT * FROM employe WHERE printer=0 ORDER BY prenom ASC");
			while ($row2 = mysql_fetch_array($res2)) { ?>
					<option value="<?=$row2['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row2['ip'] ? ' selected':''?>><?=$row2['prenom']?></option>
<?			} ?>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="2"></td>
		<td>Humeur</td>
		<td>
			<select name="commentaire_humeur" size="1">
				<option style="padding-left:30px;height:20px;" value="0" selected>Indifférent</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-clear.png) no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-storm.png) no-repeat left;" value="3">Enervé</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4"><textarea id="commentaire_commentaire" name="commentaire_commentaire" rows="6" cols="50" style="width:100%"></textarea></td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="button" class="button valider" onclick="sauve_intervention();" value="Enregistrer" />
			<input type="button"  class="button annuler" onclick="$('#intervention').hide();" value="Annuler" />
		</td>
	</tr>
</table>
</div>


<? if ($message) { ?>
	<div style="color:red;margin-top:10px;text-align:center;font-weight:bold;"><?=$message?></div>
<? } ?>


<fieldset><legend>Rechercher un fournisseur</legend>
<input type="text" name="recherche" size="15" onkeyup="affiche_fournisseur()" value="" />
</fieldset>
</form>

<table id="liste-fournisseur" style="margin:auto;width:40%;margin-top:10px;"></div>

<div id="debug"></div>

</body>
</html>
<?
mysql_close($mysql);
?>