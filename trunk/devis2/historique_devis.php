<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_GET['debug']) ? 1:0);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$droit = recuperer_droit() ;

if (!($droit & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}

$message  = '' ;


// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['devis_expo_filtre_date_inf']))			$_SESSION['devis_expo_filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')-2,date('d'),date('Y')));
if (!isset($_SESSION['devis_expo_filtre_date_sup']))			$_SESSION['devis_expo_filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['devis_expo_filtre_ville']))				$_SESSION['devis_expo_filtre_ville']		= '';
if (!isset($_SESSION['devis_expo_filtre_montant_devis']))		$_SESSION['devis_expo_filtre_montant_devis']	= 0;
if (!isset($_SESSION['devis_expo_filtre_montant_cmd']))			$_SESSION['devis_expo_filtre_montant_cmd']		= 0;
if (!isset($_SESSION['devis_expo_filtre_signe_montant_cmd']))	$_SESSION['devis_expo_filtre_signe_montant_cmd']	= '>=';
if (!isset($_SESSION['devis_expo_filtre_classement']))			$_SESSION['devis_expo_filtre_classement']	= 'date DESC';
if (!isset($_SESSION['devis_expo_filtre_representant']))		$_SESSION['devis_expo_filtre_representant'] = '';
if (!isset($_SESSION['devis_expo_filtre_numero_devis']))		$_SESSION['devis_expo_filtre_numero_devis'] = '';
if (!isset($_SESSION['devis_expo_filtre_numero_cmd']))			$_SESSION['devis_expo_filtre_numero_cmd'] = '';
if (!isset($_SESSION['devis_expo_filtre_client']))				$_SESSION['devis_expo_filtre_client'] = '';
if (!isset($_SESSION['devis_expo_filtre_artisan']))				$_SESSION['devis_expo_filtre_artisan'] = '';
if (!isset($_SESSION['devis_expo_filtre_jour_relance']))		$_SESSION['devis_expo_filtre_jour_relance'] = JOUR_MAX_RELANCE_DEVIS ;
if (!isset($_SESSION['devis_expo_filtre_relance']))				$_SESSION['devis_expo_filtre_relance'] = FALSE ;
if (!isset($_SESSION['devis_expo_filtre_commande']))			$_SESSION['devis_expo_filtre_commande'] = 'devis_cde' ;
if (!isset($_SESSION['devis_expo_filtre_article']))				$_SESSION['devis_expo_filtre_article'] = '' ;

if (isset($_POST['filtre_date_inf']))			$_SESSION['devis_expo_filtre_date_inf']				= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))			$_SESSION['devis_expo_filtre_date_sup']				= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_ville']))				$_SESSION['devis_expo_filtre_ville']				= $_POST['filtre_ville'];
if (isset($_POST['filtre_montant_cmd']))		$_SESSION['devis_expo_filtre_montant_cmd']			= $_POST['filtre_montant_cmd'];
if (isset($_POST['filtre_signe_montant_devis']))$_SESSION['devis_expo_filtre_signe_montant_devis']	= $_POST['filtre_signe_montant_devis'];
if (isset($_POST['filtre_signe_montant_cmd']))	$_SESSION['devis_expo_filtre_signe_montant_cmd']	= $_POST['filtre_signe_montant_cmd'];
if (isset($_POST['filtre_numero_devis']))		$_SESSION['devis_expo_filtre_numero_devis']			= $_POST['filtre_numero_devis'];
if (isset($_POST['filtre_numero_cmd']))			$_SESSION['devis_expo_filtre_numero_cmd']			= $_POST['filtre_numero_cmd'];
if (isset($_GET['filtre_classement']))			$_SESSION['devis_expo_filtre_classement']			= $_GET['filtre_classement'];
if (isset($_POST['filtre_representant']))		$_SESSION['devis_expo_filtre_representant']			= $_POST['filtre_representant'];
if (isset($_POST['filtre_client']))				$_SESSION['devis_expo_filtre_client']				= $_POST['filtre_client'];
if (isset($_POST['filtre_artisan']))			$_SESSION['devis_expo_filtre_artisan']				= $_POST['filtre_artisan'];
if (isset($_POST['filtre_jour_relance']))		$_SESSION['devis_expo_filtre_jour_relance']			= $_POST['filtre_jour_relance'];
if (isset($_POST['filtre_commande']))			$_SESSION['devis_expo_filtre_commande']				= $_POST['filtre_commande'];
if (isset($_POST['filtre_article']))			$_SESSION['devis_expo_filtre_article']				= $_POST['filtre_article'];

if (isset($_POST['filtre_relance']))
	$_SESSION['devis_expo_filtre_relance'] = TRUE ;
elseif (isset($_POST['action']) && !$_POST['action'])
	$_SESSION['devis_expo_filtre_relance'] = FALSE ;


if(isset($_GET['action']) && $_GET['action']=='delete' && isset($_GET['id']) && $_GET['id']) { // mode delete
	$res = mysql_query("SELECT CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis WHERE id=$_GET[id] LIMIT 0,1");
	$row = mysql_fetch_array($res);
	#mysql_query("DELETE FROM devis WHERE id=$_GET[id]");
	mysql_query("UPDATE devis SET supprime=1 WHERE id='$_GET[id]'");
	devis_log("delete_devis",$_GET['id'],"UPDATE devis SET supprime=1 WHERE id='$_GET[id]'");
	$message = "Le devis n° $row[numero] a été correctement supprimé";
}


if(isset($_GET['action']) && $_GET['action']=='delete_relance' && isset($_GET['id']) && $_GET['id']) { // mode delete relance
	$res = mysql_query("SELECT CONCAT(DATE_FORMAT(devis.`date`,'%b%y-'),devis.id) AS numero FROM devis_relance,devis WHERE devis_relance.id=$_GET[id] AND devis_relance.id_devis = devis.id LIMIT 0,1") or die("Ne peux pas trouver le n° du devis ".mysql_error());
	$row = mysql_fetch_array($res);
	#mysql_query("DELETE FROM devis_relance WHERE id=$_GET[id]") ;
	mysql_query("UPDATE devis_relance SET supprime=1 WHERE id='$_GET[id]'") or die("Ne peux pas supprimer la relance ".mysql_error());
	devis_log("delete_relance",$_GET['id'],"UPDATE devis_relance SET supprime=1 WHERE id='$_GET[id]'");
	$message = "La relance du devis n° $row[numero] a été correctement supprimée";
}


if(isset($_POST['action']) && $_POST['action']=='saisie_relance' && isset($_POST['id']) && $_POST['id']) { // mode saisie de relance client
	$date = implode('-',array_reverse(explode('/',$_POST['relance_date']))).' '.$_POST['relance_heure'].':00'; //2007-09-10 14:16:59;
	$sql = "INSERT INTO devis_relance (id_devis,`date`,representant,`type`,humeur,commentaire) VALUES ($_POST[id],'$date','$_POST[relance_representant]','$_POST[relance_type]',$_POST[relance_humeur],'".mysql_escape_string($_POST['relance_commentaire'])."')";
	$res = mysql_query($sql) or die("Ne peux pas enregistrer la relance client ".mysql_error());
	devis_log("insert_relance",mysql_insert_id(),$sql);
	$message = "La relance client a été enregistrée";
}

if(isset($_POST['action']) && $_POST['action']=='saisie_cmd' && isset($_POST['id']) && $_POST['id']) { // mode saisie de cmd client
	$sql = "UPDATE devis SET mtht_cmd_rubis=NULL, num_cmd_rubis='".strtoupper(ereg_replace("[^A-Za-z0-9]+",",",trim($_POST['cmd'])))."' WHERE id=$_POST[id]";
	$res = mysql_query($sql) or die("Ne peux pas enregistrer la commande client ".mysql_error());
	devis_log("update_num_cmd_rubis",$_POST['id'],$sql);
	$message = "La commande client a été enregistrée";
}

?>
<html>
<head>
<title>Historique des devis</title>
<link rel="shortcut icon" type="image/x-icon" href="../gfx/creation_devis.ico" />
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-devis th { border:solid 1px red; background:#DDD;font-size:0.8em; }

table#historique-devis { border-collapse:collapse; }

table#historique-devis td { border:solid 1px red; padding:3px;font-size:0.8em;}

div#relance,div#cmd {
	padding:20px;
	border:solid 2px black;
	background:white;
	display:none;
	position:absolute;
}

table#recherche {
	border-collapse:collapse;
	border-spacing: 0px;
}

table#recherche td {
	font-weight:bold;
	border:none;
	padding:2px;
}


@media print {
	.hide_when_print { display:none; }
}

table#historique-devis th.<?=e(0,explode(' ',$_SESSION['devis_expo_filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-devis th.<?=e(0,explode(' ',$_SESSION['devis_expo_filtre_classement']))?>,  table#historique-devis td.<?=e(0,explode(' ',$_SESSION['devis_expo_filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-devis td.<?=e(0,explode(' ',$_SESSION['devis_expo_filtre_classement']))?> {
	background-color:#D0D0D0;
}

</style>
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<SCRIPT LANGUAGE="JavaScript" SRC="../js/jquery.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" SRC="../js/data_dumper.js"></SCRIPT>
<script type="text/javascript" src="../js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "simple"
	});
</script>
<SCRIPT LANGUAGE="JavaScript">
<!--
function confirm_delete(id,numero) {
	if (confirm("Voulez-vous vraiment supprimer le devis "+numero+" et tous ses articles ?"))
		document.location.href = 'historique_devis.php?action=delete&id=' + id ;
}

function delete_relance(id,numero) {
	if (confirm("Voulez-vous vraiment supprimer cette relance ?"))
		document.location.href = 'historique_devis.php?action=delete_relance&id=' + id ;
}

function liste_relance(id) {
	document.getElementById('relance_devis_' + id).style.display = document.getElementById('relance_devis_' + id).style.display == 'table-row' ? 'none' : 'table-row' ;
}

function liste_toute_relance() {
	var what = '';
	if (document.historique_devis.button_affiche_relance.value == 'Afficher') { // on doit cacher les relances
		document.historique_devis.button_affiche_relance.value = 'Cache';
		what = 'table-row';
	} else { // on doit afficher les relances
		document.historique_devis.button_affiche_relance.value = 'Afficher';
		what = 'none';
	}

	// affiche ou cache tous les com' d'un coup.
	$('tr[id^=relance_devis_]').each(function (i) {
		$(this).css('display',what);
	});
}

function relance_devis(id,numero) {
	var maDate = new Date() ;

	document.historique_devis.id.value = id ;
	document.historique_devis.relance_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.historique_devis.relance_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#relance_numero').text(numero) ;
	$('#relance').css('top',document.body.scrollTop +100);
	$('#relance').css('left',screen.availWidth / 2 - 300);
	$('#relance').show('normal');

	document.historique_devis.relance_commentaire.focus();
}

function associe_cmd_devis(id,numero,cmds) {
	document.historique_devis.id.value = id ;
	document.historique_devis.cmd.value = cmds;

	$('#cmd_numero').text(numero) ;
	$('#cmd').css('top',document.body.scrollTop +100);
	$('#cmd').css('left',screen.availWidth / 2 - 300);
	$('#cmd').show('normal');
	
	document.historique_devis.cmd.focus();
}

function cache(id) {
	$('#'+id).hide('normal');
}

function envoi_formulaire(l_action) {
	document.historique_devis.action.value = l_action ;
	document.historique_devis.submit();
	return true;
}


// VA CHERCHER LE MONTANT DE LA CMD DANS RUBIS
function calcul_cmd_rubis(id_devis) {
	$('#cmd_rubis_'+id_devis).html('<img src="gfx/loading.gif">');
	$.getJSON('ajax.php', { what:'calcul_cmd_rubis', id: id_devis  } ,
		function(data){
			$('#cmd_rubis_'+id_devis).html(data + ' &euro;');
		} // fin fonction
	); // fin getJson
}

//-->
</SCRIPT>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<form name="historique_devis" action="historique_devis.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="id" value="">

<!-- boite de dialogue pour la relance client -->
<div id="relance">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des relances client</caption>
	<tr>
		<td>Devis n°</td>
		<td id="relance_numero"></td>
		<td></td>
		<td><input type="text" name="relance_date" size="8" maxlength="10"> <input type="text" name="relance_heure" size="5" maxlength="5"></td>
	</tr>
	<tr>
		<td>Type</td>
		<td>
			<select name="relance_type">
				<option value="telephone">Téléphone</option>
				<option value="fax">Fax</option>
				<option value="visite">Visite en salle</option>
				<option value="courrier">Courrier</option>
				<option value="email">Email</option>
			</select>
		</td>
		<td>Représentant</td>
		<td>
			<select name="relance_representant">
<?			$res  = mysql_query("SELECT * FROM employe ORDER BY prenom ASC");
			while ($row = mysql_fetch_array($res)) { ?>
					<option value="<?=$row['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?></option>
<?			} ?>
		</select>
		</td>
	</tr>
	<tr>
		<td colspan="2"></td>
		<td>Humeur</td>
		<td>
			<select name="relance_humeur" size="1">
				<option style="padding-left:30px;height:20px;" value="0" selected>Indifférent</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-clear.png) no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-storm.png) no-repeat left;" value="3">Enervé</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4"><textarea id="relance_commentaire" name="relance_commentaire" rows="6" cols="50" style="width:100%"></textarea></td>
	</tr>
	<tr>
		<td colspan="4" align="center"><input type="button" class="button valider" onclick="envoi_formulaire('saisie_relance');" value="Enregistrer"> <input type="button"  class="button annuler" onclick="cache('relance');" value="Annuler"></td>
	</tr>
</table>
</div>


<!-- boite de dialogue pour la saisie de la relantion entre devis et cmd client -->
<div id="cmd">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des n° de commande client</caption>
	<tr><td>Devis n°</td>
		<td id="cmd_numero"></td>
	</tr>
	<tr><td>N° des commandes<br><span style="font-size:0.7em;">(séparées par une virgule)<br>Saisir "ANNULE" si le devis n'aboutira pas</span><br>Saisir "SUSPENDU" pour un devis en attente</td>
		<td><input type="text" name="cmd"></td>
	</tr>
	<tr><td colspan="2" align="center"><input type="button" class="button valider" onclick="envoi_formulaire('saisie_cmd');" value="Enregistrer"> <input type="button"  class="button annuler" onclick="cache('cmd');" value="Annuler"></td>
	</tr>
</table>
</div>



<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/page_add.png);margin-bottom:4px;" onclick="document.location.href='creation_devis.php';" value="Créer un nouveau devis">

<table id="historique-devis" style="width:100%;border:solid 1px red;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px red;font-weight:bold;font-size:1.2em;background:#DDD;color:red;">
		Historique des devis clients

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['devis_expo_filtre_date_inf']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_inf" style="vertical-align:middle;cursor: pointer;"title="Date selector" />
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_devis.filtre_date_inf.value='';">
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['devis_expo_filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;text-align:right;">Artisan</td>
				<td><input type="text" name="filtre_artisan" value="<?=$_SESSION['devis_expo_filtre_artisan']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;">Représentant</td>
				<td><input type="text" name="filtre_representant" value="<?=$_SESSION['devis_expo_filtre_representant']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;">N° CMD</td>
				<td ><input type="text" name="filtre_numero_cmd" value="<?=$_SESSION['devis_expo_filtre_numero_cmd']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;" nowrap>Montant CMD
					<select name="filtre_signe_montant_cmd">
						<option value=">="<?=$_SESSION['devis_expo_filtre_signe_montant_cmd']=='>=' ? ' selected':''?>>supérieur à</option>
						<option value="<="<?=$_SESSION['devis_expo_filtre_signe_montant_cmd']=='<=' ? ' selected':''?>>inférieur à</option>
					</select></td>
				<td nowrap><input type="text" name="filtre_montant_cmd" value="<?=$_SESSION['devis_expo_filtre_montant_cmd'] ? $_SESSION['devis_expo_filtre_montant_cmd']:'0' ?>" size="3">&euro;</td>

				<td><input type="submit" class="button divers" style="background-image:url(/intranet/gfx/magnify.png);" value="Filtrer"></td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['devis_expo_filtre_date_sup']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_sup" style="vertical-align:middle;cursor: pointer;"title="Date selector" />
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_devis.filtre_date_sup.value='';">
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['devis_expo_filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;text-align:right;">Client</td>
				<td><input type="text" name="filtre_client" value="<?=$_SESSION['devis_expo_filtre_client']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;">Ville</td>
				<td><input type="text" name="filtre_ville" value="<?=$_SESSION['devis_expo_filtre_ville']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;">N° Devis</td>
				<td><input type="text" name="filtre_numero_devis" value="<?=$_SESSION['devis_expo_filtre_numero_devis']?>" size="8"></td>
				<td style="padding-left:2em;text-align:right;" nowrap>Article <input type="text" name="filtre_article" value="<?=$_SESSION['devis_expo_filtre_article']?>" size="8" /></td>
				<td nowrap></td>
			</tr>

			<tr>
				<td>Jour sans relance</td>
				<td><input type="text" name="filtre_jour_relance" value="<?=$_SESSION['devis_expo_filtre_jour_relance']?>" size="1"></td>
				<td style="text-align:right;"><label for="filtre_relance">Afficher uniquement les relances : </label></td>
				<td><input id="filtre_relance" type="checkbox" name="filtre_relance" value="on"<?=$_SESSION['devis_expo_filtre_relance'] ? ' checked':''?>></td>
				<td></td>
				<td></td>
				<td></td>
				<td></td>
				<td>
					<select name="filtre_commande">
						<option value="devis"<?=$_SESSION['devis_expo_filtre_commande']=='devis'?' selected':''?>>Afficher uniquement devis</option>
						<option value="cde"<?=$_SESSION['devis_expo_filtre_commande']=='cde'?' selected':''?>>Afficher uniquement cde</option>
						<option value="devis_cde"<?=$_SESSION['devis_expo_filtre_commande']=='devis_cde'?' selected':''?>>Afficher cde + devis</option>		
					</select>
				</td>
			</tr>
		</table>

<div style="color:red;"><?= $message ? $message : ''?></div>
	</caption>
	<tr>
		<th class="NUMERO">N°</th>
		<th class="DATE">Date<br><a href="historique_devis.php?filtre_classement=DATE ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=DATE DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="REPRESENTANT">Représentant<br><a href="historique_devis.php?filtre_classement=NUMERO ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NUMERO DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="NOM_CLIENT">Client<br><a href="historique_devis.php?filtre_classement=NOM_CLIENT ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NOM_CLIENT DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="VILLE_CLIENT">Ville<br><a href="historique_devis.php?filtre_classement=VILLE_CLIENT ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=VILLE_CLIENT DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="TEL_CLIENT">Téléphone<br><a href="historique_devis.php?filtre_classement=TEL_CLIENT ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=TEL_CLIENT DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="ARTISAN">Artisan<br><a href="historique_devis.php?filtre_classement=ARTISAN ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=ARTISAN DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="PTHT">Mt HT Devis<br><a href="historique_devis.php?filtre_classement=PTHT ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=PTHT DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="MTHT_CMD_RUBIS">Mt HT Cmd<br><a href="historique_devis.php?filtre_classement=MTHT_CMD_RUBIS ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=MTHT_CMD_RUBIS DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="NUM_CMD_RUBIS">Cmd Rubis<br><a href="historique_devis.php?filtre_classement=NUM_CMD_RUBIS ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NUM_CMD_RUBIS DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<? if ($droit & PEUT_ASSOCIER_CMD_AU_DEVIS) { // peut associer une cmd a une devis ?>
			<th class="hide_when_print" style="border-left-width:3px;">Cmd</th>
		<? } ?>
		<th>Relances<br><a href="historique_devis.php?filtre_classement=NB_RELANCE ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NB_RELANCE DESC"><img src="/intranet/gfx/desc.png"></a><br><input name="button_affiche_relance" type="button" class="button divers" style="background-image:url(/intranet/gfx/comments.png);" value="Afficher" onclick="liste_toute_relance();"></th>
		<th class="hide_when_print">Edit</th>
		<th class="hide_when_print">Supp</th>
	</tr>
<?	
	$where = array() ;
	$tables = array('devis') ;
	
	if ($_SESSION['devis_expo_filtre_article']) {
		$tables[] = 'devis_ligne'; // on rajoute la table des ligne de devis à la recherche
		$where[] = "devis.id = devis_ligne.id_devis";
		$where[] = "(ref_fournisseur LIKE '%".mysql_escape_string($_SESSION['devis_expo_filtre_article'])."%' OR designation LIKE '%".mysql_escape_string($_SESSION['devis_expo_filtre_article'])."%')";
	}


	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['devis_expo_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['devis_expo_filtre_date_sup'])));
	
	if ($_SESSION['devis_expo_filtre_date_inf'] && $_SESSION['devis_expo_filtre_date_inf'] != 'Aucune') $where[] = "`date` >= '$date_inf_formater 00:00:00'" ;
	if ($_SESSION['devis_expo_filtre_date_sup'] && $_SESSION['devis_expo_filtre_date_sup'] != 'Aucune') $where[] = "`date` <= '$date_sup_formater 23:59:59'" ;
	if ($_SESSION['devis_expo_filtre_representant'])	$where[] = "representant LIKE '%".	mysql_escape_string($_SESSION['devis_expo_filtre_representant'])."%'";
	if ($_SESSION['devis_expo_filtre_artisan'])			$where[] = "artisan LIKE '%".		mysql_escape_string($_SESSION['devis_expo_filtre_artisan'])."%'";
	if ($_SESSION['devis_expo_filtre_client'])			$where[] = "nom_client LIKE '%".	mysql_escape_string($_SESSION['devis_expo_filtre_client'])."%'";
	if ($_SESSION['devis_expo_filtre_ville'])			$where[] = "ville_client LIKE '%".	mysql_escape_string($_SESSION['devis_expo_filtre_ville'])."%'";
	if ($_SESSION['devis_expo_filtre_numero_cmd'])		$where[] = "num_cmd_rubis LIKE '%".	mysql_escape_string($_SESSION['devis_expo_filtre_numero_cmd'])."%'";
	if ($_SESSION['devis_expo_filtre_numero_devis'])	$where[] = "devis.id = '".			mysql_escape_string($_SESSION['devis_expo_filtre_numero_devis'])."'";
	if ($_SESSION['devis_expo_filtre_montant_cmd'] > 0) $where[] = "mtht_cmd_rubis $_SESSION[devis_expo_filtre_signe_montant_cmd] $_SESSION[devis_expo_filtre_montant_cmd]" ;


	if		($_SESSION['devis_expo_filtre_commande'] == 'devis')
		$where[] = "(num_cmd_rubis IS NULL OR num_cmd_rubis='')";
	elseif	($_SESSION['devis_expo_filtre_commande'] == 'cde')
		$where[] = "NOT (num_cmd_rubis IS NULL OR num_cmd_rubis='') AND num_cmd_rubis<>'ANNULE' AND num_cmd_rubis<>'SUSPENDU'";

	$where[] = "supprime=0";

	if ($where)
		$where = ' WHERE '.join(' AND ',$where);
	else
		$where = '';

	if		($_SESSION['devis_expo_filtre_classement'] == 'DATE DESC')
		$ordre = "`date` DESC";
	elseif	($_SESSION['devis_expo_filtre_classement'] == 'DATE ASC')
		$ordre = "`date` ASC";
	else
		$ordre = $_SESSION['devis_expo_filtre_classement'];

	$tables = join(',',$tables);

	$sql = <<<EOT
SELECT	devis.id as id,
		CONCAT(DATE_FORMAT(devis.`date`,'%b%y-'),devis.id) as numero,
		(SELECT numero FROM artisan WHERE devis.artisan = artisan.nom) AS num_artisan,
		DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,
		DATE_FORMAT(`date`,'%w') AS date_jour,
		representant,nom_client,ville_client,tel_client,tel_client2,email_client,artisan,UPPER(num_cmd_rubis) as num_cmd_rubis,mtht_cmd_rubis,
		(SELECT count(id) FROM devis_relance WHERE devis_relance.id_devis=devis.id AND devis_relance.supprime=0) AS nb_relance,
		(SELECT DATEDIFF(NOW(),`date`) FROM devis_relance WHERE devis_relance.id_devis=devis.id ORDER BY `date` DESC LIMIT 0,1) AS datediff_relance, -- si des relances
		DATEDIFF(NOW(),`date`) AS datediff_devis,
		(SELECT SUM(qte * puht) FROM devis_ligne WHERE devis_ligne.id_devis=devis.id) AS ptht
FROM  $tables
$where
ORDER BY $ordre
EOT;

	if (DEBUG)
		echo "<pre style='color:red;'>$sql</pre>";

	$total_ligne = 0;
	$total_devis = 0;
	$total_cmd	 = 0;

	$res = mysql_query($sql) or die("Ne peux pas trouver la liste des devis ".mysql_error()."<br>\n$sql");
	$i = 0 ;
	
	while($row = mysql_fetch_array($res)) {
		// on n'affiche pas le devis si l'on a demandé que les relances client
		if ($_SESSION['devis_expo_filtre_relance']) {
			if (!strtoupper(trim($row['num_cmd_rubis']))) {
				// le devis a été relance et a depasssé la date limite OU le devis n'a jamais été relancé et la date est dépassé
				if ($row['nb_relance'] && $row['datediff_relance'] >= $_SESSION['devis_expo_filtre_jour_relance']) { 
				} elseif (!$row['nb_relance'] && $row['datediff_devis'] >= $_SESSION['devis_expo_filtre_jour_relance']) {					
				} else {
					continue;
				}
			} else {
				continue;
			}
		}
?>

	<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>">
		<td class="NUMERO"><?=$row['numero']?></td>
		<td class="DATE"><a name="<?=$row['id']?>"></a><?=$jours_mini[$row['date_jour']]?> <?=$row['date_formater']?></td>
		<td class="REPRESENTANT"><?=$row['representant']?></td>
		<td class="NOM_CLIENT"><?=$row['nom_client']?></td>
		<td class="VILLE_CLIENT"><?=$row['ville_client']?></td>
		<td class="TEL_CLIENT">
			<?=$row['tel_client']?>
			<? if ($row['tel_client2']) { ?><br><?=$row['tel_client2']?><? } ?>
			<? if ($row['email_client']) { ?><br><?=$row['email_client']?><? } ?>
		</td>
		<td class="ARTISAN"><?=$row['artisan']?></td>
		<td class="PTHT" nowrap><?=$row['ptht']?> &euro;</td><!-- Mt devis -->
		<td class="MTHT_CMD_RUBIS" style="text-align:center;"
			<?	if ($row['num_cmd_rubis']) { // si un numero de cmd renseigné
				//echo "ok1 ";
					if (!$row['mtht_cmd_rubis']) { // montant pas encore calculé
						//echo "ok2 ";
						if (strtoupper(trim($row['num_cmd_rubis'])) != 'ANNULE' && strtoupper(trim($row['num_cmd_rubis'])) != 'SUSPENDU') { // si pas annulé ?>
							id="cmd_rubis_<?=$row['id']?>" nowrap>
							<img src="gfx/calc.gif" onclick="calcul_cmd_rubis(<?=$row['id']?>);">
<?						} // pas si pas annulé ou suspendu
					} else { // si le montant est déjà calculé, on n'affiche pas la calculette mais le montant ?>
						 id="cmd_rubis_<?=$row['id']?>" nowrap><?=$row['mtht_cmd_rubis']?> &euro;
						 <img src="gfx/mini-calc.png" align="top" onclick="calcul_cmd_rubis(<?=$row['id']?>);">
<?					}
				} else {  // fin si cmd rubis renseigné ?>
					><!-- on ferme la balise td -->
<?				} ?>
		</td><!-- Mt CMD -->
		<td class="NUM_CMD_RUBIS"><?=eregi_replace("[^a-z0-9]","<br>",$row['num_cmd_rubis'])?></td><!-- N° CMD Rubis -->
		<? if ($droit & PEUT_ASSOCIER_CMD_AU_DEVIS) { // peut associer une cmd a une devis ?>
				<td class="hide_when_print" style="border-left-width:3px;">
					<a href="javascript:associe_cmd_devis('<?=$row['id']?>','<?=$row['numero']?>','<?=ereg_replace("[^A-Za-z0-9]",",",trim($row['num_cmd_rubis']))?>');" style="border:none;"><img src="gfx/yellow-triple.png" alt="Associe une commande à un devis" title="Associe une commande à un devis"></a>
				</td>
		<? } ?>
		<td style="text-align:center;vertical-align:bottom;font-size:0.6em;background-color:<?  // le devis n'est pas annulé
										$jour_sans_relance = 0 ;
										if (!strtoupper(trim($row['num_cmd_rubis']))) {
											// le devis a été relance et a depasssé la date limite OU le devis n'a jamais été relancé et la date est dépassé
											if ($row['nb_relance'] && $row['datediff_relance'] >= $_SESSION['devis_expo_filtre_jour_relance']) {
												echo 'yellow' ; $jour_sans_relance = $row['datediff_relance'] ;
											} elseif (!$row['nb_relance'] && $row['datediff_devis'] >= $_SESSION['devis_expo_filtre_jour_relance']) {
												echo 'yellow'; $jour_sans_relance = $row['datediff_devis'] ;
											} else {
												echo 'white' ;
											}
										} else {
											echo 'white' ;
										}
									?>;">
<?			if ($row['nb_relance']) { ?>
				<a class="hide_when_print" href="javascript:liste_relance('<?=$row['id']?>');" style="border:none;"><img src="/intranet/gfx/liste-white.gif" alt="Liste des relances" title="Liste des relances client"></a><span style="font-size:1.5em;color:green;font-weight:bold;"><?=$row['nb_relance']?></span>
<?			}
			
			if ($jour_sans_relance) { ?>
				<br><?=$jour_sans_relance?> jours sans relance 
<?			} ?>

			<br><a href="javascript:relance_devis('<?=$row['id']?>','<?=$row['numero']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td class="hide_when_print"><a href="creation_devis.php?id=<?=$row['id']?>" style="border:none;"><img src="/intranet/gfx/edit.gif" alt="Modification" title="Modification du devis"></a></td>
		<td class="hide_when_print"><a href="javascript:confirm_delete('<?=$row['id']?>','<?=$row['numero']?>');" style="border:none;"><img src="gfx/delete.gif" alt="Suppression" title="Suppression du devis"></a>
		</td>
	</tr>

<?		// ON AFFICHE LES RELANCE CLIENTS POUR CE DEVIS
		if ($row['nb_relance']) {
			$res_relance = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,DATE_FORMAT(`date`,'%w') AS date_jour,DATE_FORMAT(`date`,'%H:%i') AS heure_formater FROM devis_relance WHERE id_devis=$row[id] AND devis_relance.supprime=0 ORDER BY `date` DESC") or die("Ne peux pas afficher les relances clients ".mysql_error()); 
?>
			<tr style="display:none;" id="relance_devis_<?=$row['id']?>">
				<td><img src="/intranet/gfx/return.jpg"></td>
				<td colspan="13" valign="top">
					<div style="font-weight:bold;">Liste des relances</div>
					<table width="100%" cellspacing="0" style="border:solid 1px grey;">
<?							while($row_relance = mysql_fetch_array($res_relance)) { ?>
							<tr>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="15%"><?=$jours_mini[$row_relance['date_jour']]?> <?=$row_relance['date_formater']?> <?=$row_relance['heure_formater']?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="5%">
	<?								switch ($row_relance['humeur']) {
										case 0: ?>&nbsp;<?
											break;
										case 1: ?><img src="/intranet/gfx/weather-clear.png"><?
											break;
										case 2: ?><img src="/intranet/gfx/weather-few-clouds.png"><?
											break;
										case 3: ?><img src="/intranet/gfx/weather-storm.png"><?
											break;
									}	?>									
								</td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="10%"><?=$row_relance['representant']?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="10%"><?=$row_relance['type']?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="60%"><?=stripslashes($row_relance['commentaire'])?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="5%"><a href="javascript:delete_relance(<?=$row_relance['id']?>);"><img src="/intranet/gfx/comment_delete.png"></a></td>
							</tr>
<?							} ?>
					</table>
				</td>
			</tr>
<?
		} // fin affiche les relances devis

		$total_ligne++;
		$total_devis += $row['ptht'];
		$total_cmd	 += $row['mtht_cmd_rubis'] ;
	} //fin while devis ?>

<tr>
	<td colspan="3">Nombre de ligne : <?=$total_ligne?></td>
	<td colspan="3">Montant des devis : <?=$total_devis?> &euro;</td>
	<td colspan="8">Montant des cmd : <?=$total_cmd?> &euro;</td>
</tr>

</table>
</form>
</body>
</html>
<?
mysql_close($mysql);
?>