<?
include('../../inc/config.php');

define('DEBUG',FALSE);
if (DEBUG) {
	echo '<pre>$_POST '; print_r($_POST); echo '</pre>';
	echo '<pre>$_GET '; print_r($_GET); echo '</pre>';
}

$message  = '';
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$droit = recuperer_droit() ;

$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : '') ;


// SAUVE LE COMPLEMENT
if (isset($_POST['action']) && $_POST['action']=='sauve_complement' && ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR)) { // on a modifié le complement de texte --> on sauve
	$res = mysql_query("UPDATE fournisseur SET info3='".mysql_escape_string($_POST['textarea_complement'])."' WHERE code_rubis='".mysql_escape_string($id)."'") or die("Ne peux pas enregistrer le complément ".mysql_error());
	//$message = "Le complément d'information a été enregistré";
}

// SUPPRIME UNE INTERVENTION
elseif(isset($_GET['action']) && $_GET['action']=='delete_intervention' && isset($_GET['id_intervention']) && $_GET['id_intervention'] && ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR)) { // mode delete intervention
	mysql_query("UPDATE fournisseur_commentaire SET supprime=1 WHERE id=$_GET[id_intervention]") or die("Ne peux pas supprimer l'intervention ".mysql_error());
	$message = "L'intervention a été correctement supprimée";
}

// SAISIR UN COMMENTAIRE
elseif(isset($_POST['action']) && $_POST['action']=='saisie_intervention' && isset($_POST['id']) && $_POST['id']) { // mode saisie de commentaire fournisseur
	$date = implode('-',array_reverse(explode('/',$_POST['commentaire_date']))).' '.$_POST['commentaire_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO fournisseur_commentaire (code_fournisseur,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ('".mysql_escape_string($_POST['id'])."','$date','".mysql_escape_string($_POST['commentaire_createur'])."','$_POST[commentaire_type]',$_POST[commentaire_humeur],'".mysql_escape_string($_POST['commentaire_commentaire'])."',0)") or die("Ne peux pas enregistrer le commentaire ".mysql_error());
	$message = "L'intervention a été enregistrée";
}


$res = mysql_query("SELECT * FROM fournisseur WHERE code_rubis='".mysql_escape_string($id)."'") or die("ne peux pas retrouver les détails du fournisseur");
$row = mysql_fetch_array($res);



?>
<html>
<head>
<title>Fiches Fournisseur : <?=$row['nom']?></title>
<style>
body { font-family:verdana; font-size:0.9em; }
a img { border:none; }
input,textarea { border:solid 2px #AAA; }
fieldset { -moz-border-radius:6px; border:solid 1px grey; }
legend { font-weight:bold; font-size:0.9em; padding-right:5px;}


h1 {
	text-transform:uppercase;
	text-align:center;
	font-size:1.5em;
}

img.icon {
	cursor:pointer;
}

div#edit-complement { 
	display:none;
}

div#intervention {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
}

div.date, div.humeur, div.createur, div.type {
	margin-left:10px;
	margin-right:10px;
	float:left;
	text-align:left;
	font-size:0.9em;
}

div.delete_intervention {
	float:right;
}

div.intervention { /* premiere case intervention */
	border-bottom:solid 1px grey;
}

div.commentaire {
	margin-left:10px;
	text-align:left;
}

@media print {
	.hide_when_print { display:none; }
	div#fiche { width:100%; } 
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript" src="../../js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : 'textareas',
		theme : 'advanced',
		theme_advanced_buttons1_add : 'forecolor',
		theme_advanced_buttons2 : '',
		theme_advanced_buttons3 : ''
	});
</script>
<script type="text/javascript">

function complement_fournisseur() {
	$('div#div-complement').hide();
	$('div#edit-complement').show();
}

function cache(id) {
	$('div#div-complement').show();
	$('div#edit-complement').hide();
}

function sauve_complement() {
	//alert(document.selecteur.textarea_complement.value);
	document.selecteur.action.value="sauve_complement";
	document.selecteur.submit();
}

function intervention_fournisseur() {

	var maDate = new Date() ;
	document.selecteur.commentaire_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.selecteur.commentaire_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#intervention').css('top',document.body.scrollTop +100);
	$('#intervention').css('left',screen.availWidth / 2 - 300);
	$('#intervention').show();

	document.selecteur.commentaire_commentaire.focus();
}

function delete_intervention(id) {
	if (confirm("Voulez-vous vraiment supprimer cette intervention ?"))
		document.location.href = 'detail_fournisseur.php?action=delete_intervention&id=' + <?="'$id'"?> + '&id_intervention=' + id  ;
}

function sauve_intervention() {
	document.selecteur.action.value="saisie_intervention";
	document.selecteur.submit();
}

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<!-- formulaire géénral à la page -->
<form action="detail_fournisseur.php" method="post" name="selecteur" style="margin-top:10px;">
<input type="hidden" name="id" value="<?=$id?>"/>
<input type="hidden" name="action" value=""/>


<!-- boite de dialogue pour la intervention fournisseur -->
<div id="intervention">
<table style="">
	<caption style="font-weight:bold;">Saisie d'intervention</caption>
	<tr>
		<td colspan="3"></td>
		<td><input type="text" name="commentaire_date" size="8" maxlength="10"> <input type="text" name="commentaire_heure" size="5" maxlength="5"></td>
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
<?			$res2  = mysql_query("SELECT * FROM employe ORDER BY prenom ASC");
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
			<input type="button" class="button valider" onclick="sauve_intervention();" value="Enregistrer">
			<input type="button"  class="button annuler" onclick="$('#intervention').hide();" value="Annuler">
		</td>
	</tr>
</table>
</div>


<? if ($message) { ?>
	<div style="color:red;margin-top:10px;text-align:center;font-weight:bold;"><?=$message?></div>
<? } ?>


<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/fiche_fournisseur_mini.png);" value="Choisir un autre fournisseur" onclick="document.location.href='index.php';" />
<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/anomalie_small.png);margin-left:10px;" value="Voir la liste des anomalies du fournisseur <?=$row['nom']?>" onclick="document.location.href='/intranet/anomalie/historique_anomalie.php?filtre_fournisseur='+escape('<?=$row['nom']?>')+'&filtre_date_inf=&filtre_date_sup=';" />


<h1>Fiche fournisseur : <?=$row['nom']?></h1>

<div id="fiche" style="margin:auto;width:80%;text-align:center;">

	<fieldset style="width:40%;display:inline;floating:left;"><legend>Coordonnées (Rubis)</legend>
		<div><?=str_replace("\n",'<br/>',$row['info_rubis1'])?></div>
	</fieldset>

	<fieldset style="margin-top:10px;width:40%;display:inline;floating:left;"><legend>Représentant (Rubis)</legend>
		<div><?=str_replace("\n",'<br/>',$row['info_rubis2'])?></div>
	</fieldset>

	<fieldset style="margin:auto;margin-top:10px;width:84%;"><legend>Complément
<?		if ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR) { ?>
			<img class="icon hide_when_print" src="gfx/edit-mini.png" onclick="complement_fournisseur();" title="Edite le texte"/>
<?		}	?>
	</legend>
		<div id="div-complement"><?=stripslashes($row['info3'])?></div>
		<div id="edit-complement">
			<textarea id="textarea_complement" name="textarea_complement" rows="10" cols="50" style="width:100%;"><?=stripslashes($row['info3'])?></textarea>
			<input type="button" class="button valider" onclick="sauve_complement();" value="Enregistrer">
			<input type="button"  class="button annuler" onclick="cache('complement');" value="Annuler">
		</div>
	</fieldset>

	<fieldset id="liste-intervention" style="margin-top:10px;width:84%;display:inline;floating:left;"><legend>Interventions <img class="icon hide_when_print" src="gfx/add-mini.png" onclick="intervention_fournisseur();" title="Ajoute une intervention"/></legend>
<?
		// récupère la liste des interventions
		$res_commentaire = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d %b %Y') AS date_formater,DATE_FORMAT(date_creation,'%w') AS date_jour,DATE_FORMAT(date_creation,'%H:%i') AS heure_formater,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM fournisseur_commentaire WHERE code_fournisseur='$id' AND supprime=0 ORDER BY date_creation ASC") or die("Ne peux pas afficher les commentaires anomalies ".mysql_error()); 
		while($row_commentaire = mysql_fetch_array($res_commentaire)) { ?>
			<div class="intervention">
				<div class="date"><?=$jours_mini[$row_commentaire['date_jour']]?> <?=$row_commentaire['date_formater']?> <?=$row_commentaire['heure_formater']?></div>
			
				<div class="humeur">
<?					switch ($row_commentaire['humeur']) {
						case 0: ?>&nbsp;<?
							break;
						case 1: ?><img src="/intranet/gfx/weather-clear.png" title="Content"><?
							break;
						case 2: ?><img src="/intranet/gfx/weather-few-clouds.png" title="Mausade"><?
							break;
						case 3: ?><img src="/intranet/gfx/weather-storm.png" title="Enervé"><?
							break;
					} ?>
				</div>
				<div class="createur"><?=$row_commentaire['createur']?></div>
				<div class="type">par <?=$row_commentaire['type']?></div>
				<?		if ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR) { ?>
					<div class="delete_intervention"><img src="/intranet/gfx/comment_delete.png" onclick="delete_intervention(<?=$row_commentaire['id']?>);" class="hide_when_print" title="Supprimer cette intervention"/></div>
				<?		}	?>
				<br/>
				<div class="commentaire"><?=stripslashes($row_commentaire['commentaire'])?></div>
			</div>
<?		} ?>

	</fieldset>
</div>


</form>
</body>
</html>
<?
mysql_close($mysql);
?>
