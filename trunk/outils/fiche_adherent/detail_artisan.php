<?
include('../../inc/config.php');

define('DEBUG',false);
if (DEBUG) {
	echo '<pre>$_POST '; print_r($_POST); echo '</pre>';
	echo '<pre>$_GET '; print_r($_GET); echo '</pre>';
}

$message  = '';
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$createur = '';

define('PLOMBIER',		1<<0);
define('ELECTRICIEN',	1<<1);

$droit = recuperer_droit() ;

$id = isset($_GET['id']) ? $_GET['id'] : (isset($_POST['id']) ? $_POST['id'] : '') ;


// SAUVE LE COMPLEMENT
if (isset($_POST['action']) && $_POST['action']=='sauve_complement' && ($droit & PEUT_MODIFIER_FICHE_ARTISAN)) { // on a modifié le complement de texte --> on sauve
	$res = mysql_query("SELECT * FROM artisan_info WHERE numero='".mysql_escape_string($id)."'") or die("ne peux pas retrouver les infos de l'artisan ".mysql_error());
	if (mysql_num_rows($res) < 1) { // on n'a pas trouvé de artisan avec ce code -> on fait une insertion
		//echo "INSERT INTO artisan_info (numero,info) VALUES ('".mysql_escape_string($id)."','".mysql_escape_string($_POST['textarea_complement'])."')";
		$res = mysql_query("INSERT INTO artisan_info (numero,info) VALUES ('".mysql_escape_string($id)."','".mysql_escape_string($_POST['textarea_complement'])."')") or die("Ne peux pas inserer le complément ".mysql_error());
	} else {	// on a trouvé une précédent info --> on met a jour
		$res = mysql_query("UPDATE artisan_info SET info='".mysql_escape_string($_POST['textarea_complement'])."' WHERE numero='".mysql_escape_string($id)."'") or die("Ne peux pas updater le complément ".mysql_error());
	}
	$message = "Le complément d'information a été enregistré";
}

// SUPPRIME UNE INTERVENTION
elseif(isset($_GET['action']) && $_GET['action']=='delete_intervention' && isset($_GET['id_intervention']) && $_GET['id_intervention'] && ($droit & PEUT_MODIFIER_FICHE_ARTISAN)) { // mode delete intervention
	mysql_query("UPDATE artisan_commentaire SET supprime=1 WHERE id=$_GET[id_intervention]") or die("Ne peux pas supprimer l'intervention ".mysql_error());
	$message = "L'intervention a été correctement supprimée";
}

// SAISIR UNE INTERVENTION
elseif(isset($_POST['action']) && $_POST['action']=='saisie_intervention' && isset($_POST['id']) && $_POST['id']) { // mode saisie de commentaire artisan
	$date = implode('-',array_reverse(explode('/',$_POST['commentaire_date']))).' '.$_POST['commentaire_heure'].':00'; //2007-09-10 14:16:59;
	$participants = $_POST['commentaire_participants'];
	if ($_POST['commentaire_participants_autres']) array_push($participants,$_POST['commentaire_participants_autres']);

	$res = mysql_query("INSERT INTO artisan_commentaire (code_artisan,date_creation,createur,participants,`type`,humeur,commentaire,supprime) VALUES ('".mysql_escape_string($_POST['id'])."','$date','".
		mysql_escape_string($_POST['commentaire_createur'])."','".
		mysql_escape_string(join(', ',$participants)).
		"','$_POST[commentaire_type]',$_POST[commentaire_humeur],'".
		mysql_escape_string($_POST['commentaire_commentaire'])."',0)") or die("Ne peux pas enregistrer le commentaire ".mysql_error());
	$message = "L'intervention a été enregistrée";
}

// SUPPRIME UN FICHIER
elseif(isset($_GET['action']) && $_GET['action']=='delete_fichier' && isset($_GET['path']) && $_GET['path']) { // mode saisie de commentaire artisan
	$true_path = "files/$_GET[path]" ;
	if (file_exists($true_path) && is_file($true_path) && unlink($true_path))
		$message = "Le fichier '".basename($true_path)."' a été correctement supprimé";
	else
		$message = "Impossible de supprimer le fichier '".basename($true_path)."'";
}

$res = mysql_query("SELECT * FROM artisan LEFT JOIN artisan_info ON artisan.numero=artisan_info.numero WHERE artisan.numero='".mysql_escape_string($id)."'") or die("ne peux pas retrouver les détails de l'artisan 1");
if (mysql_num_rows($res) < 1) { // on n'a pas trouvé de artisan avec ce code -> on tente une recherche sur le nom
	$res = mysql_query("SELECT * FROM artisan LEFT JOIN artisan_info ON artisan.numero=artisan_info.numero WHERE artisan.nom LIKE '".mysql_escape_string($id)."%'") or die("ne peux pas retrouver les détails de l'artisan 2");
}
$row = mysql_fetch_array($res);

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1">
<title>Fiches Artisan : <?=$row['nom']?></title>
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
	position: absolute;
	top:35%;
	left:30%;
	width:35%;
	height:35%;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	-moz-box-shadow: 0px 0px 10px 0px white;
	z-index:200;
}

table.intervention td {
	padding:2px;
	font-size:0.9em;
	text-align:left;
	/*border:solid 1px red;*/
}

td.date					{ width:12em; white-space:nowrap;}
td.humeur				{ width:20px; white-space:nowrap;}
td.createur				{ width:6em; white-space:nowrap;}
td.type					{ width:12em; white-space:nowrap;}
td.participants			{ font-size:0.8em; white-space:nowrap; background: url(gfx/participants.png) no-repeat center left; }
td.delete_intervention	{ width:20px; text-align:right; white-space:nowrap;}

table.intervention tr:first-child { background:#DDD; }

table.intervention { /* premiere case intervention */
	border:dotted 1px #444;
	margin:auto;
	margin-bottom:20px;
	width:95%;
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
	position: absolute;
	top:20%;
	left:30%;
	width:35%;
	height:50%;
	/* (inset ?)    x-offset  y-offset  blur-raduis  spread-radius   color  --> for opactiy : rgba(0, 0, 0, 0.5)    */
	-moz-box-shadow: 0px 0px 10px 0px white;
	z-index:200;
}
div#upload-file h2 { font-size:0.8em; }

div#interventions {
	-moz-border-radius:6px;
	border:solid 1px grey;
	width:99%;
	margin:auto;
	margin-top:20px;
}

div#statistiques {
	-moz-border-radius:6px;
	border:solid 1px grey;
	width:99%;
	margin:auto;
	margin-top:20px;
	text-align:center;
}

select#commentaire_type option {
	padding-left:20px;
	background-repeat:no-repeat;
	background-position:left center;
}

table.coordonnees-rubis {
	width:100%;
	font-size:0.8em;
}

table.coordonnees-rubis tr td:first-child {
	text-align:right;
	vertical-align:top;
}

table.coordonnees-rubis tr td:nth-child(2) {
	text-align:left;
	vertical-align:top;
}

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

/*form { margin: 0 30px;}*/
fieldset#slider { border:0; margin-top: 1em;}
.ui-slider {clear: both; top: 15px;}

input#commentaire_participants_autres {
	color:grey;
    width: 200px;
    vertical-align: top;
}

img.qrcode { display:none; }

@media print {
	.hide_when_print { display:none; }
	img.qrcode { display:block; }
	div#fiche { width:100%; }
	
}
</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/date-format.js"></script>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript" src="../../js/mobile.style.js"></script>
<script type="text/javascript" src="../../js/tiny_mce/tiny_mce.js"></script>
<link href="../../js/uploadify/uploadify.css" rel="stylesheet" type="text/css" />
<script type="text/javascript" src="../../js/uploadify/swfobject.js"></script>
<script type="text/javascript" src="../../js/uploadify/jquery.uploadify.v2.1.0.min.js"></script>
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>

<!-- pour le slider -->
<script type="text/javascript" src="../../js/jquery.ui.all.js"></script>
<script type="text/javascript" src="../../js/slider2/selectToUISlider.jQuery.js"></script>
<link type="text/css" href="../../js/theme/ui.core.css" rel="Stylesheet" />	
<link rel="stylesheet" href="../../js/slider2/ui.theme.css" type="text/css" title="ui-theme" />
<link rel="stylesheet" href="../../js/theme/ui.slider.css" type="text/css" />
<link rel="Stylesheet" href="../../js/slider2/ui.slider.extras.css" type="text/css" />

<!-- pour le chosen -->
<script language="javascript" src="../../js/chosen/chosen.jquery.min.js"></script>
<link rel="stylesheet" href="../../js/chosen/chosen.css" />

<script type="text/javascript">


$(function(){
	$('select#valueA, select#valueB').selectToUISlider({ labels: 10 });
});

tinyMCE.init({
	mode : 'textareas',
	theme : 'advanced',
	theme_advanced_buttons1_add : 'forecolor',
	theme_advanced_buttons2 : '',
	theme_advanced_buttons3 : ''
});

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

// enresgistre le completment artisan
function sauve_complement() {
	document.selecteur.action.value="sauve_complement";
	document.selecteur.submit();
}

// affiche la boite de dialogue de saisie des interventions
function intervention_artisan() {
	var maDate = new Date() ;
	document.selecteur.commentaire_date.value  = maDate.format('dd/mm/yyyy');
	document.selecteur.commentaire_heure.value = maDate.format('HH:MM');
	
	$('#blackscreen').fadeIn('slow');
	$('#intervention').show();

	document.selecteur.commentaire_commentaire.focus();
}

function cache_intervention() {
	$('#blackscreen').fadeOut('slow');
	$('#intervention').hide();
}

// supprime un fichier
function delete_fichier(path) {
	if (confirm("Voulez-vous vraiment supprimer ce fichier ?"))
		document.location.href = 'detail_artisan.php?action=delete_fichier&id=' + <?="'$id'"?> + '&path=' + escape(path)   ;
}

// supprime une intervention
function delete_intervention(id) {
	if (confirm("Voulez-vous vraiment supprimer cette intervention ?"))
		document.location.href = 'detail_artisan.php?action=delete_intervention&id=' + <?="'$id'"?> + '&id_intervention=' + id  ;
}

// enregistre une intervention
function sauve_intervention() {
	document.selecteur.action.value="saisie_intervention";
	document.selecteur.submit();
}

// affiche la boite de dialogue d'upload de fichier
function affiche_upload() {
	$('#blackscreen').fadeIn('slow');
	$('#upload-file').show();
}	

// cache la boite de dialogue d'upload de fichier
function cache_upload() {
	$('#blackscreen').fadeOut('slow');
	$('#upload-file').hide();
}

// quand le document est chargé
$(document).ready(function() {
	// plugin chosen
	$(".chzn-select").chosen();

	//plhgin upload
	$('#uploadify').uploadify({
		'scriptData'	 : {'artisan':'<?=strtoupper($id)?>'},
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

	// autre participants
	$('#commentaire_participants_autres').focus(function() {
		$(this).val('').css('color','black');
	});

<?
	// définition des options de la carte
	if ($row['geo_coords']) { // s'il a des coordonées géographique
		list($lat,$lng) = explode(',',ereg_replace('[^0-9\.\,\-]','',$row['geo_coords']));// on nettoi et éclate les coords géo
?>
		var map	= new google.maps.Map(document.getElementById('map_canvas'), {
			zoom: 10,											// Pour afficher le Morbihan
			center: new google.maps.LatLng(47.694974, -3),		// centré sur VANNES
			mapTypeId: google.maps.MapTypeId.ROADMAP
		});

		// marker adhérent
		var m_adh = new google.maps.Marker({
			position: new google.maps.LatLng(<?=$lat?>, <?=$lng?>),
			map: map,
			title:"<?=htmlentities($row['nom'])?>"
		});

		// marker MCS
		var m_mcs = new google.maps.Marker({
			position: new google.maps.LatLng(47.683087, -2.801085), // MCS coords
			map: map,
			title:"MCS",
			icon: new google.maps.MarkerImage('gfx/mcs-rouge.png',
						new google.maps.Size(26,14),// taille de l'image
						new google.maps.Point(0,0),// Origine de l'image
						new google.maps.Point(13,7)// center de l'image
					)
		});

		var bounds = new google.maps.LatLngBounds();
		bounds.extend(m_adh.getPosition());
		bounds.extend(m_mcs.getPosition());
		map.fitBounds(bounds);

		// trace la route entre l'adh et MCS
		draw_roads(m_mcs.getPosition() , m_adh.getPosition());

		function draw_roads(from,to) {
			var directionsDisplay	= new google.maps.DirectionsRenderer( { 'map':map, 'suppressMarkers': true , 'preserveViewport':true , 'polylineOptions':{ 'strokeOpacity':0.4, 'strokeColor':'#00F' } } );
			var directionsService	= new google.maps.DirectionsService();

			var request = {
				origin:			from, // LatLng or string
				destination:	to , // LatLng or string
				travelMode:		google.maps.DirectionsTravelMode.DRIVING
			};

			directionsService.route(request, function(response, status) {
				if (status == google.maps.DirectionsStatus.OK) {
					directionsDisplay.setDirections(response);
					//distance += response.routes[0].legs[0].distance.value ;
					$('#distance_from_mcs').text(Math.round(response.routes[0].legs[0].distance.value/1000) + ' Km') ;
				}
				else if (status == google.maps.DirectionsStatus.INVALID_REQUEST)			{	alert('INVALID_REQUEST'); }
				else if (status == google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED)		{	alert('MAX_WAYPOINTS_EXCEEDED'); }
				else if (status == google.maps.DirectionsStatus.NOT_FOUND)					{	alert('NOT_FOUND'); }
				else if (status == google.maps.DirectionsStatus.OVER_QUERY_LIMIT)			{	alert('OVER_QUERY_LIMIT'); }
				else if (status == google.maps.DirectionsStatus.REQUEST_DENIED)				{	alert('REQUEST_DENIED'); }
				else if (status == google.maps.DirectionsStatus.UNKNOWN_ERROR)				{	alert('UNKNOWN_ERROR'); }
				else if (status == google.maps.DirectionsStatus.ZERO_RESULTS)				{	alert('ZERO_RESULTS'); }
			});
		} // fin draw_roads
<?	} // fin si coords_geo ?>

});

</script>
</head>
<body>

<div id="blackscreen"></div>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<!-- formulaire géénral à la page -->
<form action="detail_artisan.php" enctype="multipart/form-data" method="post" name="selecteur" style="margin-top:10px;">
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

<!-- boite de dialogue pour la intervention artisan -->
<div id="intervention">
<table style="">
	<caption style="font-weight:bold;">Saisie d'intervention</caption>
	<tr>
		<td style="text-align:right;">Créateur</td>
		<td>
			<select name="commentaire_createur">
<?			$res_employes  = mysql_query("SELECT * FROM employe WHERE printer=0 and nom NOT LIKE '%3G' ORDER BY prenom ASC");
			while ($row2 = mysql_fetch_array($res_employes)) { ?>
					<option value="<?=$row2['prenom']?>"<?
						if ($_SERVER['REMOTE_ADDR']==$row2['ip']) {
							echo ' selected' ;
							$createur = $row2['prenom'];
						}
					?>><?=$row2['prenom']?></option>
<?			} ?>
			</select>
		</td>
		<td style="text-align:right;">Date</td>
		<td style="white-space:nowrap;"><input type="text" name="commentaire_date" size="8" maxlength="10"> <input type="text" name="commentaire_heure" size="5" maxlength="5"></td>
	</tr>
	<tr>
		<td style="text-align:right;">Type</td>
		<td style="text-align:left;">
			<select name="commentaire_type" id="commentaire_type">
				<option value="visite_mcs" style="background-image:url('gfx/mcs-icon.png');">Visite de l'adh à MCS</option>
				<option value="visite_artisan" style="background-image:url('gfx/artisan.png');">Visite chez artisan</option>
				<option value="telephone" style="background-image:url('gfx/telephone.png');">Téléphone</option>
				<option value="fax" style="background-image:url('gfx/fax.png');">Fax</option>
				<option value="courrier" style="background-image:url('gfx/courrier.png');">Courrier</option>
				<option value="email" style="background-image:url('gfx/mail.png');">Email</option>
				<option value="autre" style="background-image:url('gfx/autre.png');">Autre</option>
			</select>
		</td>
		<td>Humeur</td>
		<td>
			<select name="commentaire_humeur" size="1">
				<option style="padding-left:30px;height:20px;" value="0" selected>Indifférent</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-clear.png)		no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-storm.png)		no-repeat left;" value="3">Enervé</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4" style="text-align:left;font-size:0.8em;vertical-align:top;">
			<select name="commentaire_participants[]" data-placeholder="Participants..." style="width:200px;" multiple class="chzn-select">
				<?	mysql_data_seek($res_employes,0);
					while ($row2 = mysql_fetch_array($res_employes)) { ?>
						<option value="<?=$row2['prenom']?>"><?=$row2['prenom']?></option>
				<?	} ?>
			</select>
			<input type="text" id="commentaire_participants_autres" name="commentaire_participants_autres" value="Autres ..." />
		</td>
	</tr>
	<tr>
		<td colspan="4"><textarea id="commentaire_commentaire" name="commentaire_commentaire" rows="6" cols="50" style="width:100%"></textarea></td>
	</tr>
	<tr>
		<td colspan="4" align="center">
			<input type="button" class="button valider" onclick="sauve_intervention();" value="Enregistrer">
			<input type="button"  class="button annuler" onclick="cache_intervention();" value="Annuler">
		</td>
	</tr>
</table>
</div>


<? if ($message) { ?>
	<div style="color:red;margin:10px;text-align:center;font-weight:bold;"><?=$message?></div>
<? } ?>

<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/fiche_artisan_mini.png);" value="Choisir un autre artisan" onclick="document.location.href='index.php';" />
<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/anomalie_small.png);margin-left:10px;" value="Voir la liste des anomalies du artisan <?=$row['nom']?>" onclick="document.location.href='/intranet/anomalie/historique_anomalie.php?filtre_adherent='+escape('<?=$row['nom']?>')+'&filtre_date_inf=&filtre_date_sup=';" />


<h1>Artisan : <?=$row['nom']?></h1>

<div id="fiche" style="margin:auto;width:90%;text-align:center;">
<?	// creer une connextion Rubis pour récupérer d'autres infos non stocké dans MySQL

	// truc stocké dans les tables de listing
	$sql = <<<EOT
select	TABLE_PARAM.TYPPR as TYPE_LIBELLE, TABLE_PARAM.LIBPR as LIBELLE
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATABLEP1 TABLE_PARAM
				on (
						(CLIENT.TOUCL=TABLE_PARAM.CODPR and TABLE_PARAM.TYPPR='TOU') or	-- pour récupérer le libéllé du code tournée
						(CLIENT.REGCL=TABLE_PARAM.CODPR and TABLE_PARAM.TYPPR='REG')	-- pour récupérer le type de reglement
					)
where
		CLIENT.NOCLI='$id'
EOT;
	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) or die("Impossible lancer la requete de récupration des infos adh");
	$info_rubis = array();
	while($row_rubis = odbc_fetch_array($res)) {
		$info_rubis[$row_rubis['TYPE_LIBELLE']] = $row_rubis['LIBELLE'];
	}
	//echo "<pre>$sql</pre>";


	// truc stocké dans des tables
	$sql = <<<EOT
select	ECHEANCE.LIECH as LIBELLE_ECHEANCE,
		CLIENT.RENDI as MDP_SITE_WEB,
		CLIENT.PROFE as DATE_ADHESION
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AECHEAP1 ECHEANCE
				on CLIENT.NUECH=ECHEANCE.NUECH
where
		CLIENT.NOCLI='$id'
EOT;
	$res		= odbc_exec($loginor,$sql) ;
	//echo "<pre>$sql</pre>";
	$row_rubis	= odbc_fetch_array($res) or die("Impossible lancer la requete de récupration échéance");
	
?>
	<fieldset style="width:47%;display:inline;floating:left;"><legend>Coordonnées (Rubis)</legend>
		<table class="coordonnees-rubis" style="width:100%;">
			<tr><td>Code Rubis :</td><td><?=$id?></td>
				<td rowspan="15"><img src="../../gfx/qrcode.php?text=<?
					echo urlencode(json_encode(array('t'=>'fiche_adherent','c'=>$id,'d'=>time())));
				?>" class="qrcode"/></td>
			</tr>
			<tr><td>Email :</td><td><?=$row['email']?></td></tr>
			<tr><td>Tél bureau :</td><td><?=$row['tel1']?></td></tr>
			<tr><td>Fax bureau :</td><td><?=$row['tel2']?></td></tr>
			<tr><td>Portable 1 :</td><td><?=$row['tel3']?></td></tr>
			<tr><td>Portable 2 :</td><td><?=$row['tel4']?></td></tr>
			<tr><td style="white-space:nowrap;">Adresse bureau :</td><td><?
				if ($row['adr1'])	echo $row['adr1']."<br/>";
				if ($row['adr2'])	echo $row['adr2']."<br/>";
				if ($row['adr3'])	echo $row['adr3']."<br/>";
				if ($row['cp'])		echo $row['cp']." ";
				if ($row['ville'])	echo $row['ville'];
			?></td></tr>
			<tr><td style="white-space:nowrap;">Activité :</td><td>
			<?	if ($row['activite'] & PLOMBIER) { ?>
					<img src="gfx/plombier-mini.png" alt="Plombier/Chauffagiste" title="Plombier/Chauffagiste"/>
			<?	} ?>
			<?	if ($row['activite'] & ELECTRICIEN) { ?>
					<img src="gfx/electricien-mini.png" alt="Electricien" title="Electricien"/>
			<?	} ?>
			</td></tr>
			<tr><td>Tournée le :</td><td><?=trim($info_rubis['TOU'])?></td></tr>
			<tr><td>Echéance :</td><td><?=trim($row_rubis['LIBELLE_ECHEANCE'])?></td></tr>
			<tr><td>Réglement :</td><td><?=trim($info_rubis['REG'])?></td></tr>
			<tr><td>Date d'adhésion :</td><td><?=trim($row_rubis['DATE_ADHESION'])?></td></tr>
			<tr><td>MDP Site Web :</td><td><?=trim($row_rubis['MDP_SITE_WEB'])?></td></tr>
			<tr><td>Distance de MCS :</td><td id="distance_from_mcs"></td></tr>
			<tr>
				<td>3 dernières visites :</td><td id="dernieres_visites">
<?					$res_visite  = mysql_query("SELECT DATE_FORMAT(date_creation,'%d/%m/%Y') AS date_formater FROM artisan_commentaire WHERE supprime<>1 and `type`='visite_artisan' and code_artisan='$id' ORDER BY date_creation DESC LIMIT 0,3") or die("Erreur dans la récupération des dernières visite : ".mysql_error());
					while ($row_visite = mysql_fetch_array($res_visite)) { ?>
							<?=$row_visite['date_formater'];?><br/>
<?					} ?>				
				</td>
			</tr>
		</table>
	</fieldset>

	<fieldset style="margin-top:10px;width:47%;display:inline;floating:left;"><legend>Carte</legend>
		<div id="map_canvas" style="width:100%;height:300px;z-index:0;"></div>
	</fieldset>


	<fieldset style="margin:auto;margin-top:10px;width:97%;"><legend>Complément
<?		if ($droit & PEUT_MODIFIER_FICHE_ARTISAN) { ?>
			<img class="icon hide_when_print" src="gfx/edit-mini.png" onclick="affiche_complement();" alt="Edite le texte" title="Edite le texte" align="absbottom"/>
<?		}	?>
	</legend>
		<div id="div-complement"><?=stripslashes($row['info'])?></div>
		<div id="edit-complement">
			<textarea id="textarea_complement" name="textarea_complement" rows="10" cols="50" style="width:100%;"><?=stripslashes($row['info'])?></textarea>
			<input type="button" class="button valider" onclick="sauve_complement();" value="Enregistrer">
			<input type="button"  class="button annuler" onclick="cache_complement('complement');" value="Annuler">
		</div>
	</fieldset>


	<fieldset style="margin-top:10px;width:97%;display:inline;floating:left;text-align:left;"><legend>Fichiers attachés
<?		if ($droit & PEUT_MODIFIER_FICHE_ARTISAN) { ?>
			<img class="icon hide_when_print" src="gfx/add-file-mini.png" onclick="affiche_upload();" alt="Associer un fichier" title="Associer un fichier" align="absbottom"/>
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
						case 'doc': case 'docx': case 'odt': case 'txt':										echo 'doc-docx-odt.png'; break;
						case 'xls': case 'xlsx': case 'csv': case 'ods':										echo 'xls-xlsx-csv-ods.png';  break;
						case 'pdf':																				echo 'pdf.png';  break;
						case 'jpg': case 'jpeg': case 'gif': case 'png': case 'tiff': case 'tif': case 'bmp':	echo 'jpg-jpeg-gif-png-tiff-bmp.png';  break;
						case 'zip': case 'rar': case '7z':														echo 'zip-rar-7z.png';  break;
						default:																				echo 'file.png'; break;
					}
					?>" class="icon" />
					<a href="files/<?="$id/$file"?>" target="_blank"><?=$file?></a> 
					<span class="size">(<?=formatBytes(filesize(dirname($_SERVER['SCRIPT_FILENAME'])."/files/$id/$file"))?>)</span>
					<img src="gfx/delete.png" alt="Supprimer le fichier" title="Supprimer ce fichier" style="cursor:pointer;" onclick="delete_fichier('<?=str_replace("'","\\'","$id/$file")?>');" />
					</li>
<?				} // fin foreach $file
				$d->close(); // on ferme le répertoire
			} // fin if file_exists
?>
		</ul>
	</fieldset>

	<div id="interventions">
		<div style="font-weight:bold; font-size:0.9em; margin-left:10px;margin-bottom:5px;text-align:left;">
		Interventions <img class="icon hide_when_print" src="gfx/add-mini.png" onclick="intervention_artisan();" alt="Ajoute une intervention" title="Ajoute une intervention" align="absbottom"/>
		</div>
<?
		// récupère la liste des interventions
		$res_commentaire = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d %b %Y') AS date_formater,DATE_FORMAT(date_creation,'%w') AS date_jour,DATE_FORMAT(date_creation,'%H:%i') AS heure_formater,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM artisan_commentaire WHERE code_artisan='$id' AND supprime=0 ORDER BY date_creation ASC") or die("Ne peux pas afficher les commentaires anomalies ".mysql_error()); 
		while($row_commentaire = mysql_fetch_array($res_commentaire)) { ?>
			<table class="intervention">
				<tr>
					<td class="date"><?=$jours_mini[$row_commentaire['date_jour']]?> <?=$row_commentaire['date_formater']?> <?=$row_commentaire['heure_formater']?></td>
					<td class="humeur">
<?						switch ($row_commentaire['humeur']) {
							case 0: ?>&nbsp;<?																				break;
							case 1: ?><img src="/intranet/gfx/weather-clear.png" alt="Content" title="Content" /><?			break;
							case 2: ?><img src="/intranet/gfx/weather-few-clouds.png" alt="Mausade" title="Mausade" /><?	break;
							case 3: ?><img src="/intranet/gfx/weather-storm.png" alt="Enervé" title="Enervé" /><?			break;
						} ?>
					</td>
					<td class="createur"><?=$row_commentaire['createur']?></td>
					<td class="type"><?
						switch ($row_commentaire['type']) {
							case 'visite_mcs': ?><img src="gfx/mcs-icon.png"/> Visite de l'adh à MCS<?		break;
							case 'visite_artisan': ?><img src="gfx/artisan.png"/> Visite chez l'artisan<?	break;
							case 'telephone': ?><img src="gfx/telephone.png"/> Téléphone<?					break;
							case 'fax': ?><img src="gfx/fax.png"/> Fax<?									break;
							case 'courrier': ?><img src="gfx/courrier.png"/> Courrier<?						break;
							case 'email': ?><img src="gfx/mail.png"/> Email<?								break;
							case 'autre': ?><img src="gfx/autre.png"/> Autre<?								break;
						}
					?></td>
					<td class="participants"><?=$row_commentaire['participants']?></td>
<?						if (	($droit & PEUT_MODIFIER_FICHE_ARTISAN) ||
								($row_commentaire['createur'] == $createur && $row_commentaire['temps_ecoule'] <= 3600) // si on est le créateur du com' et que moins d'une heure s'est écoulée
							) { ?>
							<td class="delete_intervention"><img src="/intranet/gfx/comment_delete.png" style="cursor:pointer;"  onclick="delete_intervention(<?=$row_commentaire['id']?>);" class="hide_when_print" alt="Supprimer cette intervention" title="Supprimer cette intervention"/></td>
<?						}	?>
				</tr>
				<tr>
					<td class="commentaire" colspan="6"><?=stripslashes($row_commentaire['commentaire'])?></td>
				</tr>
			</table>
<?		} ?>
		</div>
</div>


<div id="statistiques">
	<div style="font-weight:bold; font-size:0.9em; margin-left:10px;margin-bottom:5px;text-align:left;">
	Statisitiques <img src="gfx/stats.png"/>
	</div>
	<fieldset id="slider" style="margin-bottom:30px;font-size:0.7em;">
		<label for="valueA" class="sentence" style="display:none;">Depuis :</label>

		<select name="valueA" id="valueA" style="display:none;">
<?			$mois_mini = array('Jan','Fev','Mar','Avr','Mai','Jui','Jul','Aou','Sep','Oct','Nov','Dec');
			$mois_start = '';
			for($i=2006 ; $i <= date('Y') ; $i++) {
				for($j=0 ; $j<sizeof($mois_mini) ; $j++) {	?>
					<option value="<?=sprintf('%02d',$j+1)."/$i"?>" <?
						if ($i+1 == date('Y') && $j+1 == date('m')) {
							echo 'selected="selected"';
							$mois_start = $i.sprintf('%02d',$j+1);
						} else {
							echo '';
						}
					?>><?=$mois_mini[$j]." $i"?></option>
<?						if ($i == date('Y') && $j+1 == date('m')) break; // pour ne pas afficher les mois de l'année en cours qui ne sont pas passé
				}
			}
?>		</select>

		<label for="valueB" class="sentence" style="display:none;">Jusqu'à :</label>
		<select name="valueB" id="valueB" style="display:none;">
<?			$mois_end	= '';
			for($i=2006 ; $i <= date('Y') ; $i++) {
				for($j=0 ; $j<sizeof($mois_mini) ; $j++) {	?>
					<option value="<?=sprintf('%02d',$j+1)."/$i"?>" <?
						if ($i == date('Y') && $j+1 == date('m')) {
							echo 'selected="selected"';
							$mois_end	= $i.sprintf('%02d',$j+1);
						} else {
							echo '';
						}
					?>><?=$mois_mini[$j]." $i"?></option>
<?						if ($i == date('Y') && $j+1 == date('m')) break; // pour ne pas afficher les mois de l'année en cours qui ne sont pas passé
				}
			}
?>		</select>
	
		<input type="button" class="button valider" value="Afficher" style="margin-top:40px;" onclick="reload_stats();"/>
	</fieldset>

<script>

function reload_stats() {
	var tmp	= document.selecteur.valueA.options[document.selecteur.valueA.selectedIndex].value.split(/\//);
	var mois_start	= tmp[1].toString() + tmp[0].toString();
	var tmp	= document.selecteur.valueB.options[document.selecteur.valueB.selectedIndex].value.split(/\//);
	var mois_end	= tmp[1].toString() + tmp[0].toString();
	//alert(mois_start);
	
	$('#graph_ca_nbbon')	.attr('src','graph_ca_nbbon.php?numero_artisan=<?=$id?>&mois_start='+mois_start+'&mois_end='+mois_end);
	$('#graph_emp_liv')		.attr('src','graph_emp_liv.php?numero_artisan=<?=$id?>&mois_start='+mois_start+'&mois_end='+mois_end);
	$('#graph_emp_liv_ca')	.attr('src','graph_emp_liv_ca.php?numero_artisan=<?=$id?>&mois_start='+mois_start+'&mois_end='+mois_end);
	$('#graph_activite_pie').attr('src','graph_activite_pie.php?numero_artisan=<?=$id?>&mois_start='+mois_start+'&mois_end='+mois_end);
}

</script>
	

<img id="graph_ca_nbbon" src="graph_ca_nbbon.php?numero_artisan=<?=$id?>&mois_start=<?=$mois_start?>&mois_end=<?=$mois_end?>"/><br/><br/>
<img id="graph_emp_liv" src="graph_emp_liv.php?numero_artisan=<?=$id?>&mois_start=<?=$mois_start?>&mois_end=<?=$mois_end?>"/>
<img id="graph_emp_liv_ca" src="graph_emp_liv_ca.php?numero_artisan=<?=$id?>&mois_start=<?=$mois_start?>&mois_end=<?=$mois_end?>"/>
<img id="graph_activite_pie" src="graph_activite_pie.php?numero_artisan=<?=$id?>&mois_start=<?=$mois_start?>&mois_end=<?=$mois_end?>"/>
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
