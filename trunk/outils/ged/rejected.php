<?
include('../../inc/config.php');
session_start();

define('DEBUG',FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '' ;

if (DEBUG) { echo "<pre>" ; print_r($_POST) ; echo "</pre>\n"; }

// sauvegarde les infos renseignées à la main
if (isset($_POST['what']) && $_POST['what'] == 'saveInfos' && $_POST['document_filename']) {
	mysql_query("INSERT IGNORE INTO ged_document (id,filename,code_type_document,print_by,date_print,date_scan,page,key1,key2,manual,deleted) VALUES ('','".
			mysql_escape_string($_POST['document_filename'])."','".
			mysql_escape_string(strtoupper($_POST['document_type']))."','".
			mysql_escape_string(strtoupper($_POST['document_print_by']))."','".
			mysql_escape_string( join('-',array_reverse(explode('/',$_POST['document_date_print_input']))) )."',NOW(),'".
			mysql_escape_string($_POST['document_page'])."','".
			mysql_escape_string(strtoupper($_POST['document_key1']))."','".
			mysql_escape_string(strtoupper($_POST['document_key2'])).
			"',1,0)") or die("Erreur dans l'enregistrement du documents : ".mysql_error());

	if (mysql_affected_rows($mysql) > 0) { // si une modification faites --> on déplace le fichier et sa miniature
		$store_directory = date('Y/m/d');
		if (!is_dir("documents/$store_directory"))
			mkdir("documents/$store_directory", 0777, true); // mkpath for document
		rename("rejected/$_POST[document_filename]","documents/$store_directory/$_POST[document_filename]"); // move document

		if (!is_dir("thumbs/$store_directory"))
			mkdir("thumbs/$store_directory", 0777, true); // mkpath for thumbs
		$thumb = preg_replace('/\.jpg$/i','_thumb.jpg',$_POST['document_filename']);
		rename("thumbs/$thumb","thumbs/$store_directory/$thumb"); // move thumbs

		$message = "Document enregistré";
	}
}

?>
<html>
<head>
<title>Gestion des documents rejetés du traitement automatique</title>
<style>

body {
	font-family:verdana;
	font-size:0.8em;
	margin:0;
}

a > img { border:none; }
div#main { margin:5px; }

#message {
	width:100%;
	background-color:green;
	color:white;
	font-weight:bold;
	text-align:center;
}

fieldset {
	border:solid 1px #6290B3;
	-moz-border-radius:5px;
	margin-bottom:2em;
}

fieldset legend {
	border:solid 1px #6290B3;
	background:#e7eef3;
	color:#325066;
	font-weight:bold;
	padding:3px;
	text-align:center;
	-moz-border-radius:5px;
	-moz-box-shadow: 0 0 5px #6290B3;
}

div#type_document { display:none; }

ul#resultats {
	list-style-type:none;
	padding:0px;
	margin:0px;
}

ul#resultats li { margin-right:19px; }

div.document {
	border:solid 1px #efefbb;
	float:left;
	text-align:center;
	margin:10px;
	padding:3px;
	font-size:0.7em;
}

div.document:hover {
	border-color:yellow;
	background-color:#fafaea;
	-moz-box-shadow: 0 0 5px yellow;
}

div.document img {
	width:100px;
	border:solid 1px #DDD;
}

div.document div.action {
	/*position:relative;
	top:-140px;
	text-align:left;*/
	opacity:0;
}

#message_printer {
	color:red;
	font-weight:bold;
	visibility:hidden;
}

div#footer {
	border-top:solid 1px #6290B3;
	position:fixed;
	bottom:0;
	width:100%;
	height: 30px; 
	background-color:white;
	padding-left:15px;
	padding-top:5px;
}

/* style pour la boite de dialogue pour la saisie d'intervention */
div#getInfos {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	top:35%;
	left:7%;
	width:70%;
	height:50%;
	-moz-box-shadow: 0px 0px 10px 0px white;
	z-index:200;
}

div#getInfos th, div#getInfos td {
	font-weight:normal;
	text-align:right;
	font-size:0.8em;
}

div#getInfos td { text-align:left; }

/* grand cadre avec toutes les propositions */
#liste-tiers {
	border:solid 1px black;
	background:white;
}

/* une ligne de proposition */
.tiers {
	cursor:pointer;
}

/* une ligne de proposition suspendu */
.tiers-suspendu {
	text-decoration:line-through;
	color:#999;
	font-weight:normal;
	background:url(gfx/hachure.gif);
}

.tiers-type-1 { background:white;	} /* artisan */
.tiers-type-2 { background:lightgrey;	} 
.tiers-type-3 { background:orange;	} /* employe */
.tiers-type-4 { background:lime;	} /* coop */
.tiers-type-5 { background:pink;	} 
.tiers-type-6 { background:yellow;	} /* fournisseur */

.tiers-code { color:green; font-weight:bold;}
.tiers-nom	{ color:black; }

div.calendar { z-index:201; }

div#blackscreen {
	position:absolute;
	top:0px;
	left:0px;
	width:100%;
	height:100%;
	background-color:rgba(0,0,0,0.7);
	display:none;
	z-index:199;
}

@media print {
	.hide_when_print { display:none; }
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar-setup.js"></script>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript" src="../../js/mobile.style.js"></script>

<script language="javascript">
<!--

function document_search() {
	top.preview.location.href='preview.php';
	document.location.href='search.php';
}

function show_getInfos(file,id) {
	// rempli les champs d'avance
	
	top.preview.location.href='rejected/'+file;
	document.rejected.document_filename.value = file;
	$('#document_filename').text(file);

	$('#blackscreen').fadeIn('slow');
	$('#getInfos').show();
}

function hide_getInfos() {
	$('#getInfos').hide();
	$('#blackscreen').fadeOut('slow');
}

function delete_file(file,id) {
	if (confirm("Voulez-vous vraiment supprimer ce fichier ?")) {
		// appel ajax de suppression
		$.get('ajax.php', {'what':'delete_image',  'filename':file},
			  function(data){ // succes
					$('#image-'+id).hide('slow');
		});
	}
}

function rotate(file,id) {
	// appel ajax de suppression
	$.get('ajax.php', {'what':'rotate_image',  'filename':file},
		  function(data){ // succes
				$('#image-'+id).hide('slow');
				alert("L'image a été tournée à 180° et réinjectée dans le scanner");
	});
}

function verif_formulaire() {
	top.preview.location.href='preview.php'; // on efface la frame de preview
	return 1;
}


// va chercher dans la liste des tiers, les valeurs qui commence par ce qu'a tapé l'utilisateur
function affiche_tiers(texte) {
	//alert(texte);
	$('#liste-tiers').html('').show();	// efface le div de résultat
	var query = texte.toUpperCase().replace(/[^0-9a-z ]/i,'') ;
	if (query.length >= 2) { // si au moins un caractère de renseigné
		for(i=0; i<tiers.length ; i++) { // pour chaque tiers
			if (	tiers[i][2].indexOf(query) > -1 // nom artisan trouvé
				||	tiers[i][1].indexOf(query) > -1
				||	tiers[i][0].indexOf(query) > -1) { // code artisan trouvé
						//draw_row_artisan('liste-artisan',artisans[i]); // ajoute une ligne aux tableaux de résultat

				$('#liste-tiers').append('<div class="tiers tiers-type-' + tiers[i][4] + (tiers[i][3] ? ' tiers-suspendu':'') + '" onclick="valide_tiers(\''+tiers[i][0]+'\')"><span class="tiers-code">'+tiers[i][0]+'</span> <span class="tiers-nom">'+tiers[i][1]+'</span></div>');
			}
		}
	}
}

function valide_tiers(tiers) {
	$('#liste-tiers').hide();
	document.rejected.document_key2.value=tiers;
}

// stock la liste des artisans
var tiers = new Array();
<?
// récupère la liste des tiers
$res = mysql_query("SELECT	* FROM tiers") or die("ne peux pas retrouver la liste des tiers ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	tiers.push(['<?=addslashes(strtoupper($row['code']))?>','<?=addslashes(strtoupper($row['nom']))?>','<?=preg_replace('/[^0-9a-z ]/i','',strtoupper($row['nom']))?>',<?=$row['suspendu']?>,<?=$row['type']?>]);
<? } ?>


$(document).ready(function(){
	// binding
	$('.document').bind('mouseenter', function(event) {
		//$(this).children('.action').css({'left':'100px'}).animate({opacity: 1},400);
		$(this).children('.action').animate({opacity: 1},400);
	});

	$('.document').bind('mouseleave', function(event) {
		//$(this).children('.action').animate({opacity: 0},1000,function() { $(this).css({'left':'0px'}); });
		$(this).children('.action').animate({opacity: 0},1000);
	});

	// supprimer le message au bout de deux sec
	<? if ($message) { ?>
		setTimeout(function(){
			$('#message').hide('slow');
		},3000);
	<? } ?>
});

//-->
</script>
</head>
<body>

<div id="message"><?=$message?></div>

<form name="rejected" method="post" action="<?=$_SERVER['PHP_SELF']?>" onsubmit="return verif_formulaire();">
<input type="hidden" name="what" value="saveInfos" />
<input type="hidden" name="document_filename" value="" />

<!-- pour faire une fenetre modal et cacher le fond -->
<div id="blackscreen"></div>

<!-- fenetre pour renseigner les infos -->
<div id="getInfos">
	<table>
		<tr><th>Fichier</th>			<td id="document_filename"></td></tr>
		<tr><th>Type</th>				<td id="document_type">
				<select name="document_type">
					<? $res_type_document = mysql_query("SELECT * FROM ged_type_document ORDER BY libelle ASC") or die("Erreur dans la récupération des type de documents : ".mysql_error());
						while ($row = mysql_fetch_array($res_type_document)) { ?>
						<option value="<?=$row['code']?>"><?=$row['libelle']?></option>
					<? } ?>
				</select>
			</td></tr>
		<tr><th>Imprimer par</th>		<td id="document_print_by"><input type="text" name="document_print_by" size="6" value="AF"/></td></tr>
		<?				// calcul la veille. Si on est lundi, calcul le vendredi précedent
						$eve = date('d/m/Y' , mktime(0,0,0,date('m'),date('d')-(date('w') == 1 ? 3:1),date('Y')));
		?>
		<tr><th>Date d'impression</th>	<td id="document_date_print">
			<input type="text" id="document_date_print_input" name="document_date_print_input" value="<?=$eve?>" size="8">
					<img src="../../js/jscalendar/calendar.gif" id="push_date" style="vertical-align:middle;cursor:pointer;" title="Choisir une date" class="hide_when_print"/>
			<script type="text/javascript">
				Calendar.setup({
					inputField	: 'document_date_print_input',         // ID of the input field
					ifFormat	: '%d/%m/%Y',    // the date format
					button		: 'push_date',       // ID of the button
					date		: '<?=$eve?>',	// eve (friday for monday)
					firstDay 	: 1
				});
			</script>
		</td></tr>
		<tr><th>Page</th>				<td id="document_page"><input type="text" name="document_page" size="2" value=""/></td></tr>
		<tr><th>N° de document</th>		<td id="document_key1"><input type="text" name="document_key1" size="6" value=""/></td></tr>
		<tr><th style="vertical-align:top;">N° du tier</th>			<td id="document_key2">
			<input type="text" name="document_key2" onkeyup="affiche_tiers(this.value)" size="6" value="" autocomplete="off"/>
			<span style="background:orange;color:black;">Emplo</span>
			<span style="background:yellow;color:black;">Fourn</span>
			<span style="background:pink;color:black;">Perso</span>
			<div id="liste-tiers" style="display:none;"></div>
		</td></tr>
		<tr>
			<td colspan="2" style="text-align:center;">
				<input type="submit" class="button valider" onclick="save_getInfos();" value="Enregistrer">
				<input type="button"  class="button annuler" onclick="hide_getInfos();" value="Annuler">
			</td>
		</tr>
	</table>
</div>


<div id="main">
<fieldset><legend>Document à trier</legend>
<ul id="resultats">
<?	$i=1;
	$dir = opendir('rejected') or die("Ne peux pas ouvrir le répertoire des documents rejetés");
	while($file = readdir($dir)) { // pour chaque résultat
		if (preg_match('/\.(jpe?g|png|gif)$/i',$file)) { ?>
			<li id="image-<?=$i?>">
				<div class="document">
					<a href="rejected/<?=$file?>" target="preview"><img src="thumbs/<?=basename($file,'.jpg')?>_thumb.jpg" alt="<?=$file?>"/></a><br/>
					<div class="action">
						<input type="button" value="Val." class="button valider" onclick="show_getInfos('<?=$file?>',<?=$i?>)"/><br/>
						<input type="button" value="Sup." class="button annuler" onclick="delete_file('<?=$file?>',<?=$i?>)"/><br/>
						<input type="button" value="180&deg;" class="button rotate-right" onclick="rotate('<?=$file?>',<?=$i?>)"/>
					</div>
				</div>
			</li>
<?			$i++;
		}
	} ?>
</ul>
</fieldset>
</div><!-- fin main -->

<div id="footer">
	<input type="button" value="Recherche" class="button search divers" onclick="document_search();"/>
	<span id="message_printer"></span>
</div>

</form>
</body>
</html>
<?
mysql_close($mysql);
?>