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

$res = mysql_query("SELECT * FROM fournisseur WHERE code_rubis='".mysql_escape_string($id)."'") or die("ne peux pas retrouver les détails du fournisseur 1");
if (mysql_num_rows($res) < 1) { // on n'a pas trouvé de fournisseur avec ce code -> on tente une recherche sur le nom
	$res = mysql_query("SELECT * FROM fournisseur WHERE nom LIKE '".mysql_escape_string($id)."%'") or die("ne peux pas retrouver les détails du fournisseur 2");
}
$row = mysql_fetch_array($res);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
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

img.icon { cursor:pointer; }
div#edit-complement { display:none; }

/* style pour la boite de dialogue pour la saisie d'intervention */
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

table.intervention td {
	padding:2px;
	font-size:0.9em;
	text-align:left;
}

td.date					{ width:25%; }
td.humeur				{ width:5%; }
td.createur				{ width:5%; }
td.type					{ width:25%; }
td.delete_intervention	{ width:5%; }

table.intervention tr:first-child { background:#DDD; }

table.intervention { /* premiere case intervention */
	border:dotted 1px #444;
	margin-bottom:20px;
	width:100%;
	border-spacing: 0px;
    border-collapse: collapse;
}

td.commentaire {
	margin-top:25px;
	margin-left:50px;
	text-align:left;
}

/* style pour les fichier uploader */
ul.file { list-style-type:none; padding-left:0px; }
ul.file li { margin:5px 5px 5px 10px; }
ul.file a { text-decoration:none; color:DarkCyan ; }
ul.file a:hover { text-decoration:underline; }
img.icon { margin-right:2px; }
span.size {
	color:grey;
	font-size:0.7em;
	margin-left:5px;
}


/* style pour la boite de dialogue pour l'upload de fichier */
div#upload-file {
	padding:20px;
	padding-top:5px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	-moz-box-shadow: 5px 5px 20px 0px grey;
}
div#upload-file h2 { font-size:0.8em; }


@media print {
	.hide_when_print { display:none; }
	div#fiche { width:100%; }
	
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script type="text/javascript" src="../../js/tiny_mce/tiny_mce.js"></script>
<link href="../../js/uploadify/uploadify.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../../js/uploadify/swfobject.js"></script>
<script type="text/javascript" src="../../js/uploadify/jquery.uploadify.v2.1.0.min.js"></script>
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

// affiche a boite de saisie du completment
function affiche_complement() {
	$('div#div-complement').hide();
	$('div#edit-complement').show();
}

// cache la boite de saisie du completment
function cache_complement() {
	$('div#div-complement').show();
	$('div#edit-complement').hide();
}

// enresgistre le completment fournisseur
function sauve_complement() {
	//alert(document.selecteur.textarea_complement.value);
	document.selecteur.action.value="sauve_complement";
	document.selecteur.submit();
}

// affiche la boite de dialogue de saisie des interventions
function intervention_fournisseur() {
	var maDate = new Date() ;
	document.selecteur.commentaire_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.selecteur.commentaire_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#intervention').css('top',document.body.scrollTop +100);
	$('#intervention').css('left',screen.availWidth / 2 - 300);
	$('#intervention').show();

	document.selecteur.commentaire_commentaire.focus();
}

// supprime une intervention
function delete_intervention(id) {
	if (confirm("Voulez-vous vraiment supprimer cette intervention ?"))
		document.location.href = 'detail_fournisseur.php?action=delete_intervention&id=' + <?="'$id'"?> + '&id_intervention=' + id  ;
}

// enregistre une intervention
function sauve_intervention() {
	document.selecteur.action.value="saisie_intervention";
	document.selecteur.submit();
}

// affiche la boite de dialogue d'upload de fichier
function affiche_upload() {
	$('#upload-file').css('top',document.body.scrollTop +100);
	$('#upload-file').css('left',screen.availWidth / 2 - 300);
	$('#upload-file').show();
}

// cache la boite de dialogue d'upload de fichier
function cache_upload() {
	$('#upload-file').hide();
}


// upload
$(document).ready(function() {
	$('#uploadify').uploadify({
		'scriptData'	 : {'fournisseur':'<?=strtoupper($id)?>'},
		'uploader'       : '../../js/uploadify/uploadify.swf',
		'script'         : 'uploadify.php',
		'cancelImg'      : '../../js/uploadify/cancel.png',
		'queueID'        : 'fileQueue',
		'auto'           : true,
		'multi'          : true,
		'onAllComplete'  : function() {
				window.location.reload();
		}
	});
});

</script>
</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<!-- formulaire géénral à la page -->
<form action="detail_fournisseur.php" enctype="multipart/form-data" method="post" name="selecteur" style="margin-top:10px;">
<input type="hidden" name="MAX_FILE_SIZE" value="5000000" />
<input type="hidden" name="id" value="<?=$id?>"/>
<input type="hidden" name="action" value=""/>

<!-- boite de dialogue pour l'upload d'un fichier -->
<div id="upload-file">
	<h2>Choisissez le(s) fichier(s) à associer</h2>
	<div id="fileQueue"></div>
	<input type="file" name="uploadify" id="uploadify" />
	<p><input type="button" class="button annuler" value="Annuler" onclick="javascript:jQuery('#uploadify').uploadifyClearQueue();cache_upload();" /></p>
</div>


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
			<img class="icon hide_when_print" src="gfx/edit-mini.png" onclick="affiche_complement();" title="Edite le texte" align="absbottom"/>
<?		}	?>
	</legend>
		<div id="div-complement"><?=stripslashes($row['info3'])?></div>
		<div id="edit-complement">
			<textarea id="textarea_complement" name="textarea_complement" rows="10" cols="50" style="width:100%;"><?=stripslashes($row['info3'])?></textarea>
			<input type="button" class="button valider" onclick="sauve_complement();" value="Enregistrer">
			<input type="button"  class="button annuler" onclick="cache_complement('complement');" value="Annuler">
		</div>
	</fieldset>


	<fieldset style="margin-top:10px;width:84%;display:inline;floating:left;text-align:left;"><legend>Fichiers attachés
<?		if ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR) { ?>
			<img class="icon hide_when_print" src="gfx/add-file-mini.png" onclick="affiche_upload();" title="Associer un fichier" align="absbottom"/>
<?		}	?>
	</legend>
		<ul class="file">
<?			if (file_exists(dirname($_SERVER['SCRIPT_FILENAME']).'/files/'.$id)) {
				$d = dir(dirname($_SERVER['SCRIPT_FILENAME']).'/files/'.$id); // l'endroit ou sont stocké les fichiers
				while (false !== ($file = $d->read())) { 
					if ($file == '.' || $file == '..') continue ;
?>					<li><img src="gfx/icons/<?
					eregi('\.(.+)$',$file,$regs);
					$ext = $regs[1];
					switch ($ext) {
						case 'doc': case 'docx': case 'odt': case 'txt':
							echo 'doc-docx-odt.png'; break;
						case 'xls': case 'xlsx': case 'csv': case 'ods':
							echo 'xls-xlsx-csv-ods.png';  break;
						case 'pdf':
							echo 'pdf.png';  break;
						case 'jpg': case 'jpeg': case 'gif': case 'png': case 'tiff': case 'tif': case 'bmp':
							echo 'jpg-jpeg-gif-png-tiff-bmp.png';  break;
						case 'zip': case 'rar': case '7z':
							echo 'zip-rar-7z.png';  break;
						default:
							echo 'file.png'; break;
					}
					?>" class="icon" />
					<a href="files/<?="$id/$file"?>" target="_blank"><?=$file?></a>
					<span class="size">(<?=formatBytes(filesize(dirname($_SERVER['SCRIPT_FILENAME'])."/files/$id/$file"))?>)</span></li>
<?				} // fin foreach $file
				$d->close(); // on ferme le répertoire
			} // fin if file_exists
?>
		</ul>
	</fieldset>

	<fieldset id="liste-intervention" style="margin-top:10px;width:84%;display:inline;floating:left;"><legend>Interventions <img class="icon hide_when_print" src="gfx/add-mini.png" onclick="intervention_fournisseur();" title="Ajoute une intervention" align="absbottom"/></legend>
<?
		// récupère la liste des interventions
		$res_commentaire = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d %b %Y') AS date_formater,DATE_FORMAT(date_creation,'%w') AS date_jour,DATE_FORMAT(date_creation,'%H:%i') AS heure_formater,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM fournisseur_commentaire WHERE code_fournisseur='$id' AND supprime=0 ORDER BY date_creation ASC") or die("Ne peux pas afficher les commentaires anomalies ".mysql_error()); 
		while($row_commentaire = mysql_fetch_array($res_commentaire)) { ?>
			<table class="intervention">
				<tr>
					<td class="date"><?=$jours_mini[$row_commentaire['date_jour']]?> <?=$row_commentaire['date_formater']?> <?=$row_commentaire['heure_formater']?></td>
					<td class="humeur">
<?						switch ($row_commentaire['humeur']) {
							case 0: ?>&nbsp;<?
								break;
							case 1: ?><img src="/intranet/gfx/weather-clear.png" title="Content"><?
								break;
							case 2: ?><img src="/intranet/gfx/weather-few-clouds.png" title="Mausade"><?
								break;
							case 3: ?><img src="/intranet/gfx/weather-storm.png" title="Enervé"><?
								break;
						} ?>
					</td>
					<td class="createur"><?=$row_commentaire['createur']?></td>
					<td class="type">par <?=$row_commentaire['type']?></td>
<?						if ($droit & PEUT_MODIFIER_FICHE_FOURNISSEUR) { ?>
							<td class="delete_intervention"><img src="/intranet/gfx/comment_delete.png" onclick="delete_intervention(<?=$row_commentaire['id']?>);" class="hide_when_print" title="Supprimer cette intervention"/></td>
<?						}	?>
				</tr>
				<tr>
					<td class="commentaire" colspan="5"><?=stripslashes($row_commentaire['commentaire'])?></td>
				</tr>
			</table>
<?		} ?>

	</fieldset>
</div>


</form>
</body>
</html>
<?
mysql_close($mysql);


function formatBytes($bytes, $precision = 2) {
    $units = array('B', 'KB', 'MB', 'GB', 'TB');
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, $precision) . ' ' . $units[$pow];
} 
?>
