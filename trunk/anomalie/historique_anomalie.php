<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '';

$employe = array();
$res = mysql_query("SELECT prenom FROM employe WHERE printer=0 AND nom<>'' ORDER BY prenom ASC") or die("ne peux pas récupérer la liste des employés. ".mysql_error());
while ($row = mysql_fetch_array($res))
	$employe[] = $row['prenom'];

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['filtre_date_inf']))	$_SESSION['filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')-1,date('d'),date('Y')));
if (!isset($_SESSION['filtre_date_sup']))	$_SESSION['filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')  ,date('d'),date('Y')));
if (!isset($_SESSION['filtre_adherent']))	$_SESSION['filtre_adherent']	= '';
if (!isset($_SESSION['filtre_fournisseur']))$_SESSION['filtre_fournisseur']	= '';
if (!isset($_SESSION['filtre_createur']))	$_SESSION['filtre_createur']	= '';
if (!isset($_SESSION['filtre_numero']))		$_SESSION['filtre_numero']		= '';
if (!isset($_SESSION['filtre_evolution']))	$_SESSION['filtre_evolution']	= '';
if (!isset($_SESSION['filtre_classement'])) $_SESSION['filtre_classement'] = 'date_creation DESC';
if (!isset($_SESSION['filtre_logistique']))		$_SESSION['filtre_logistique']		= TRUE;
if (!isset($_SESSION['filtre_commerce']))		$_SESSION['filtre_commerce']		= TRUE;
if (!isset($_SESSION['filtre_exposition']))		$_SESSION['filtre_exposition']		= TRUE;
if (!isset($_SESSION['filtre_administratif']))	$_SESSION['filtre_administratif']	= TRUE;
if (!isset($_SESSION['filtre_informatique']))	$_SESSION['filtre_informatique']	= TRUE;
if (!isset($_SESSION['filtre_autre']))			$_SESSION['filtre_autre']			= TRUE;
if (!isset($_SESSION['filtre_etat_a_traiter']))	$_SESSION['filtre_etat_a_traiter']	= TRUE;
if (!isset($_SESSION['filtre_etat_en_cours']))	$_SESSION['filtre_etat_en_cours']	= TRUE;
if (!isset($_SESSION['filtre_etat_cloture']))	$_SESSION['filtre_etat_cloture']	= FALSE;


if (isset($_POST['filtre_date_inf']))	$_SESSION['filtre_date_inf']	= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))	$_SESSION['filtre_date_sup']	= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_adherent']))	$_SESSION['filtre_adherent']	= $_POST['filtre_adherent'];
if (isset($_POST['filtre_fournisseur']))$_SESSION['filtre_fournisseur']	= $_POST['filtre_fournisseur'];
if (isset($_POST['filtre_createur']))	$_SESSION['filtre_createur']	= $_POST['filtre_createur'];
if (isset($_POST['filtre_numero']))		$_SESSION['filtre_numero']		= $_POST['filtre_numero'];
if (isset($_POST['filtre_evolution']))	$_SESSION['filtre_evolution']	= $_POST['filtre_evolution'];
if (isset($_GET['filtre_classement']))	$_SESSION['filtre_classement']  = $_GET['filtre_classement'];

if (isset($_SERVER['HTTP_REFERER']) && eregi('historique_anomalie.php',$_SERVER['HTTP_REFERER'])) { // si on vient d'une recherche, on modifie les coches
	if (isset($_POST['action']) && $_POST['action']=='saisie_commentaire'	||	// ne rien faire si l'on vient de saisir un commentaire
		isset($_GET['action']) && $_GET['action']=='delete_commentaire'		||	// ne rien faire si l'on vient de supprimer un commentaire
		isset($_GET['filtre_classement']))										// ne rien faire si l'on vient de changer le classement
		{ }
	else {
		$_SESSION['filtre_logistique']		= isset($_POST['filtre_logistique']);
		$_SESSION['filtre_commerce']		= isset($_POST['filtre_commerce']);
		$_SESSION['filtre_exposition']		= isset($_POST['filtre_exposition']);
		$_SESSION['filtre_administratif']	= isset($_POST['filtre_administratif']);
		$_SESSION['filtre_informatique']	= isset($_POST['filtre_informatique']);
		$_SESSION['filtre_autre']			= isset($_POST['filtre_autre']);
		$_SESSION['filtre_etat_a_traiter']	= isset($_POST['filtre_etat_a_traiter']);
		$_SESSION['filtre_etat_en_cours']	= isset($_POST['filtre_etat_en_cours']);
		$_SESSION['filtre_etat_cloture']	= isset($_POST['filtre_etat_cloture']);
	}
}

if (DEBUG) {
	echo "<pre>_POST "; echo print_r($_POST); echo "</pre>";
	echo "<pre>_SESSION "; echo print_r($_SESSION); echo "</pre>";
}

// ACTION A FAIRE
// SUPPRIMER UN COMMENTAIRE
if(isset($_GET['action']) && $_GET['action']=='delete_commentaire' && isset($_GET['id']) && $_GET['id']) { // mode delete commentaire
	mysql_query("UPDATE anomalie_commentaire SET supprime=1 WHERE id=$_GET[id]") or die("Ne peux pas supprimer le commentaire ".mysql_error());
	$message = "Le commentaire a été correctement supprimée";
	//print_r($_SERVER);
}

// SUPPRIMER UNE ANOMALIE
elseif(isset($_GET['action']) && $_GET['action']=='delete_anomalie' && isset($_GET['id']) && $_GET['id']) { // mode delete anomalie
	mysql_query("UPDATE anomalie SET supprime=1 WHERE id=$_GET[id]") or die("Ne peux pas supprimer l'anomalie ".mysql_error());
	$message = "L'anomalie n°$_GET[id] a été correctement supprimé";
	//print_r($_SERVER);
}

// SAISIR UN COMMENTAIRE
elseif(isset($_POST['action']) && $_POST['action']=='saisie_commentaire' && isset($_POST['id']) && $_POST['id']) { // mode saisie de commentaire client
	$date = implode('-',array_reverse(explode('/',$_POST['commentaire_date']))).' '.$_POST['commentaire_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO anomalie_commentaire (id_anomalie,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ($_POST[id],'$date','$_POST[commentaire_createur]','$_POST[commentaire_type]',$_POST[commentaire_humeur],'".mysql_escape_string($_POST['commentaire_commentaire'])."',0)") or die("Ne peux pas enregistrer le commentaire ".mysql_error());
	$message = "La commentaire de l'anomalie n° $_POST[id] a été enregistrée";



	// faire un envoi de mail au chef de pole
	$res = mysql_query("SELECT * FROM anomalie WHERE id=$_POST[id]") or die ("Ne peux pas récupérer les infos de l'anomalie n°$id. ".mysql_error());
	$row_anomalie = mysql_fetch_array($res);

	$html = <<<EOT
	Nouveau commentaire sur l'anomalie n°$_POST[id] concernant l'artisant $row_anomalie[artisan]<br>
	Créé par <b>$_POST[commentaire_createur]</b> le $_POST[commentaire_date] à $_POST[commentaire_heure]<br><br>
	
	<u>Commentaire</u> :<br>
	$_POST[commentaire_commentaire]
EOT;

	
	require_once '../inc/xpm2/smtp.php';
	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR);
	foreach ($CHEFS_DE_POLE as $p=>$chef)
		if ($row_anomalie['pole'] & $p)	$mail->AddTo($chef['email'],$chef['nom']) or die("Erreur d'ajour de destinataire");
	$mail->From(e('email',mysql_fetch_array(mysql_query("SELECT email FROM employe WHERE prenom='$_POST[commentaire_createur]'"))));
	$mail->Html($html);
	$sent = $mail->Send("Nouveau commentaire sur anomalie n.$_POST[id]");
}

?>
<html>
<head>
<title>Historique des anomalies</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-anomalie th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-anomalie { border-collapse:collapse; }

table#historique-anomalie td { border:solid 1px grey; padding:3px;font-size:0.8em; vertical-align:top;}

table#historique-anomalie th.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-anomalie th.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?>,  table#historique-anomalie td.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-anomalie td.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
	background-color:#D0D0D0;
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

div#commentaire {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
}

table#historique-anomalie table.commentaire td {
	border-width:1px 0 0 0;
}

table#historique-anomalie table.commentaire caption {
	font-weight:bold;
	text-decoration:underline;
	text-align:left;
	font-size:0.8em;
}

@media print {
	.hide_when_print { display:none; }
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
		mode : 'textareas',
		theme : 'advanced',
		theme_advanced_buttons1_add : 'forecolor',
		theme_advanced_buttons2 : '',
		theme_advanced_buttons3 : ''
	});
</script>
<SCRIPT LANGUAGE="JavaScript">
<!--

function commentaire_anomalie(numero) {

	var maDate = new Date() ;

	document.historique_anomalie.id.value = numero ;
	document.historique_anomalie.commentaire_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.historique_anomalie.commentaire_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#commentaire_numero').text(numero) ;
	$('#commentaire').css('top',document.body.scrollTop +100);
	$('#commentaire').css('left',screen.availWidth / 2 - 300);
	$('#commentaire').show();

	document.historique_anomalie.commentaire_commentaire.focus();
}

function delete_commentaire(id) {
	if (confirm("Voulez-vous vraiment supprimer ce commentaire ?"))
		document.location.href = 'historique_anomalie.php?action=delete_commentaire&id=' + id ;
}

function liste_commentaire(id) {
	document.getElementById('commentaire_anomalie_' + id).style.display = document.getElementById('commentaire_anomalie_' + id).style.display == 'table-row' ? 'none' : 'table-row' ;
}

function liste_toute_commentaire() {
	var tr_elements = document.getElementsByTagName('tr');
	var what = '';
	if (document.historique_anomalie.button_affiche_commentaire.value == 'Afficher') { // on doit cacher les commentaires
		document.historique_anomalie.button_affiche_commentaire.value = 'Cache';
		what = 'table-row';
	} else { // on doit afficher les commentaires
		document.historique_anomalie.button_affiche_commentaire.value = 'Afficher';
		what = 'none';
	}

	for(i=0 ; i<tr_elements.length ; i++) {
		if (tr_elements[i]['id'].match(/^commentaire_anomalie_\w+$/))
			tr_elements[i].style.display = what ;
	}
}

function cache(id) {
	$('#'+id).hide();
}

function envoi_formulaire(l_action) {
	document.historique_anomalie.action.value = l_action ;
	document.historique_anomalie.submit();
	return true;
}


//-->
</SCRIPT>
</head>
<body>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_anomalie" action="historique_anomalie.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="id" value="">


<!-- boite de dialogue pour la commentaire client -->
<div id="commentaire">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des commentaires</caption>
	<tr>
		<td>Anomalie n°</td>
		<td id="commentaire_numero"></td>
		<td></td>
		<td><input type="text" name="commentaire_date" size="8" maxlength="10"> <input type="text" name="commentaire_heure" size="5" maxlength="5"></td>
	</tr>
	<tr>
		<td>Type</td>
		<td>
			<select name="commentaire_type">
				<option value="telephone">Téléphone</option>
				<option value="fax">Fax</option>
				<option value="visite">Visite en salle</option>
				<option value="courrier">Courrier</option>
				<option value="email">Email</option>
			</select>
		</td>
		<td>Représentant</td>
		<td>
			<select name="commentaire_createur">
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
			<select name="commentaire_humeur" size="1">
				<option style="padding-left:30px;height:20px;" value="0" selected>Indifférent</option>
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-clear.png) no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-storm.png) no-repeat left;" value="3">Enervé</option>
			</select>
		</td>
	</tr>
	<tr>
		<td colspan="4"><textarea id="commentaire_commentaire" name="commentaire_commentaire" rows="6" cols="50" style="width:100%"></textarea></td>
	</tr>
	<tr>
		<td colspan="4" align="center"><input type="button" class="button valider" onclick="envoi_formulaire('saisie_commentaire');" value="Enregistrer"> <input type="button"  class="button annuler" onclick="cache('commentaire');" value="Annuler"></td>
	</tr>
</table>
</div>



<!-- LIEN POUR LA CREATION DE NOUVELLE ANOMALIE-->
<div style="text-align:left;margin-bottom:5px;"><input type="button" class="button divers" style="background-image:url(gfx/anomalie_small.png);" onclick="javascript:document.location.href='creation_anomalie.php';" value="Creation d'anomalie" /></div>

<!-- TABLEAU AVEC LES CDE ET LE MOTEUR DE RECHERCHE -->
<table id="historique-anomalie" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des Anomalies <input type="checkbox" name="debug"<?=DEBUG?' checked':''?>/>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['filtre_date_inf']?>" size="8">
					<button id="trigger_inf" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="gfx/delete_micro.gif" onclick="document.historique_anomalie.filtre_date_inf.value='';">
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;">Adhérent</td>
				<td><input type="text" name="filtre_adherent" value="<?=$_SESSION['filtre_adherent']?>" size="8"></td>
				<td style="padding-left:2em;">Créateur</td>
				<td>
					<select name="filtre_createur">
							<option value=""<?=$_SESSION['filtre_createur']==''?' selected':''?>>TOUS</option>
<?						foreach ($employe as $val) { ?>
							<option value="<?=$val?>"<?=$_SESSION['filtre_createur']==$val ? ' selected':''?>><?=$val?></option>
<?						} ?>
					</select>
				</td>
				<td style="text-align:right;"><input type="submit" class="button divers" style="background-image:url(gfx/application_form_magnify.png);" value="Filtrer"></td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['filtre_date_sup']?>" size="8">
					<button id="trigger_sup" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="gfx/delete_micro.gif" onclick="document.historique_anomalie.filtre_date_sup.value='';">
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="text-align:right;">Fournisseur</td>
				<td><input type="text" name="filtre_fournisseur" value="<?=$_SESSION['filtre_fournisseur']?>" size="8"></td>
				<td style="text-align:right;">Numéro</td>
				<td style="text-align:left;"><input type="text" name="filtre_numero" value="<?=$_SESSION['filtre_numero']?>" size="8"></td>
			</tr>
			<tr>
				<td colspan="7">
					<input type="checkbox" id="filtre_logistique" name="filtre_logistique" <?=$_SESSION['filtre_logistique'] ? 'checked="on"':''?>/><label for="filtre_logistique">Logistique</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_commerce" name="filtre_commerce" <?=$_SESSION['filtre_commerce'] ? 'checked="on"':''?>/><label for="filtre_commerce">Commerce</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_exposition" name="filtre_exposition" <?=$_SESSION['filtre_exposition'] ? 'checked="on"':''?>/><label for="filtre_exposition">Exposition</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_administratif" name="filtre_administratif" <?=$_SESSION['filtre_administratif'] ? 'checked="on"':''?>/><label for="filtre_administratif">Administratif</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_informatique" name="filtre_informatique" <?=$_SESSION['filtre_informatique'] ? 'checked="on"':''?>/><label for="filtre_informatique">Informatique</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_autre" name="filtre_autre" <?=$_SESSION['filtre_autre'] ? 'checked="on"':''?>/><label for="filtre_autre">Autre</label>
				</td>
			<tr>
				<td colspan="7">
					<input type="checkbox" id="filtre_etat_a_traiter" name="filtre_etat_a_traiter" <?=$_SESSION['filtre_etat_a_traiter'] ? 'checked="on"':''?>/><label for="filtre_etat_a_traiter">A traiter</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_etat_en_cours" name="filtre_etat_en_cours" <?=$_SESSION['filtre_etat_en_cours'] ? 'checked="on"':''?>/><label for="filtre_etat_en_cours">En cours</label>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="checkbox" id="filtre_etat_cloture" name="filtre_etat_cloture" <?=$_SESSION['filtre_etat_cloture'] ? 'checked="on"':''?>/><label for="filtre_etat_cloture">Cloturé</label>
				</td>
			</tr>
		</table>

	</caption>
	<tr>
		<th class="id">N°<br><a href="historique_anomalie.php?filtre_classement=id ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=id DESC"><img src="gfx/desc.png"></a></th>
		<th class="date_creation">Date création<br><a href="historique_anomalie.php?filtre_classement=date_creation ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=date_creation DESC"><img src="gfx/desc.png"></a></th>
		<th class="createur">Createur<br><a href="historique_anomalie.php?filtre_classement=createur ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=createur DESC"><img src="gfx/desc.png"></a></th>
		<th class="artisan">Adhérent<br><a href="historique_anomalie.php?filtre_classement=artisan ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=artisan DESC"><img src="gfx/desc.png"></a></th>
		<th class="fournisseur">Fournisseur<br><a href="historique_anomalie.php?filtre_classement=fournisseur ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=fournisseur DESC"><img src="gfx/desc.png"></a></th>
		<th class="pole">Pole<br><a href="historique_anomalie.php?filtre_classement=pole ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=pole DESC"><img src="gfx/desc.png"></a></th>
		<th class="evolution">Etat<br><a href="historique_anomalie.php?filtre_classement=evolution ASC"><img src="gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=evolution DESC"><img src="gfx/desc.png"></a></th>
		<th class="responsabilite">Responsabilité</th>
		<th>Commentaire<br><input name="button_affiche_commentaire" type="button" class="button divers" style="background-image:url(gfx/comments.png);" value="Afficher" onclick="liste_toute_commentaire();"></th>
		<th>Edit</th>
	</tr>
<?
	$where = array() ;
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['filtre_date_sup'])));

	if ($_SESSION['filtre_date_inf'] && $_SESSION['filtre_date_inf'] != 'Aucune') $where[] = "date_creation >= '$date_inf_formater 00:00:00'" ;
	if ($_SESSION['filtre_date_sup'] && $_SESSION['filtre_date_sup'] != 'Aucune') $where[] = "date_creation <= '$date_sup_formater 23:59:59'" ;
	if ($_SESSION['filtre_adherent'])	$where[] = "artisan like '%".strtoupper(mysql_escape_string($_SESSION['filtre_adherent']))."%'" ;
	if ($_SESSION['filtre_createur'])	$where[] = "createur='"	.strtoupper(mysql_escape_string($_SESSION['filtre_createur']))."'" ;
	if ($_SESSION['filtre_fournisseur'])$where[] = "fournisseur like '%".strtoupper(mysql_escape_string($_SESSION['filtre_fournisseur']))."%'" ;
	if ($_SESSION['filtre_numero'])		$where[] = "id='".strtoupper(trim(mysql_escape_string($_SESSION['filtre_numero'])))."'" ;

	$pole = array();
	if ($_SESSION['filtre_logistique'])		$pole[] = '(pole & '.POLE_LOGISTIQUE.	' = '.POLE_LOGISTIQUE.')';
	if ($_SESSION['filtre_commerce'])		$pole[] = '(pole & '.POLE_COMMERCE.		' = '.POLE_COMMERCE.')';
	if ($_SESSION['filtre_exposition'])		$pole[] = '(pole & '.POLE_EXPOSITION.	' = '.POLE_EXPOSITION.')';
	if ($_SESSION['filtre_administratif'])	$pole[] = '(pole & '.POLE_ADMINISTRATIF.' = '.POLE_ADMINISTRATIF.')';
	if ($_SESSION['filtre_informatique'])	$pole[] = '(pole & '.POLE_INFORMATIQUE.	' = '.POLE_INFORMATIQUE.')';
	if ($_SESSION['filtre_autre'])			$pole[] = '(pole & '.POLE_AUTRE.		' = '.POLE_AUTRE.')';

	if (sizeof($pole)>0) // au moins un pole de coché
		$where[] = '('.join(' or ',$pole).')';
	else
		$where[] = "pole<0";

	$etat = array();
	if ($_SESSION['filtre_etat_a_traiter'])	$etat[] = 'evolution=0';
	if ($_SESSION['filtre_etat_en_cours'])	$etat[] = 'evolution=1';
	if ($_SESSION['filtre_etat_cloture'])	$etat[] = 'evolution=2';

	if (sizeof($etat)>0) // au moins un pole de coché
		$where[] = '('.join(' or ',$etat).')';
	else 
		$where[] = "evolution<0";

	$where[] = "supprime=0";
	
	$where = $where ? $where = ' where '.join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	$ordre = $_SESSION['filtre_classement'];

	$sql = <<<EOT
select *,DATE_FORMAT(date_creation,'%d %M %Y') AS date_creation_formatee, DATE_FORMAT(date_creation,'%w') AS jour_creation, DATE_FORMAT(date_cloture,'%d %M %Y') AS date_cloture_formatee
from anomalie
$where
order by $ordre
EOT;

if (DEBUG)
	echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$total_ligne = 0 ;	$i=0;
	$res = mysql_query($sql) or die("Ne peux pas rechercher les anomalies. ".mysql_error());
	while($row = mysql_fetch_array($res)) {
?>

	<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>">
		<td class="id"><?=$row['id']?></td>
		<td class="date_creation"><?=$jours_mini[$row['jour_creation']]?> <?=$row['date_creation_formatee']?></td><!-- date -->
		<td class="createur"><?=$row['createur']?></td><!-- createur -->
		<td class="artisan" style="text-align:left;"><?=$row['artisan']?></td><!-- adhérent -->
		<td class="fournisseur" style="text-align:left;"><?=$row['fournisseur']?></td><!-- fournisseur -->
		<td class="pole" style="text-align:left;"><?
			if ($row['pole'] & POLE_LOGISTIQUE)		echo "Logitique<br>";
			if ($row['pole'] & POLE_COMMERCE)		echo "Commerce<br>";
			if ($row['pole'] & POLE_EXPOSITION)		echo "Exposition<br>";
			if ($row['pole'] & POLE_ADMINISTRATIF)	echo "Administratif<br>";
			if ($row['pole'] & POLE_INFORMATIQUE)	echo "Informatique<br>";
			if ($row['pole'] & POLE_AUTRE)			echo "Autre<br>";
		?></td><!-- pole -->
		<td class="evolution" style="text-align:left;" nowrap><?
															switch($row['evolution']) {
																case 0: echo '<img src="gfx/feu_red.png" /> A traiter'; break ;
																case 1: echo '<img src="gfx/feu_yellow.png" /> En cours'; break ;
																case 2: echo "<img src=\"gfx/feu_green.png\" /> Cloturée<br/>Le $row[date_cloture_formatee]"; break ;
															} ?></td><!-- etat -->
		<td class="responsabilite" style="text-align:left;"><?
				switch($row['resp_coop']) {	case 0: break ;
											case 1: echo '<img src="gfx/feu_yellow.png" /> <strong>Coop</strong> partiellement<br>'; break ;
											case 2: echo '<img src="gfx/feu_red.png" /> <strong>Coop complétement</strong><br>'; break ;
										}
				switch($row['resp_adh']) {	case 0: break ;
											case 1: echo '<img src="gfx/feu_yellow.png" /> <strong>Adhérent</strong> partiellement<br>'; break ;
											case 2: echo '<img src="gfx/feu_red.png" /> <strong>Adhérent</strong> complétement<br>'; break ;
										}
				switch($row['resp_four']) {	case 0: break ;
											case 1: echo '<img src="gfx/feu_yellow.png" /> <strong>Fournisseur</strong> partiellement<br>'; break ;
											case 2: echo '<img src="gfx/feu_red.png" /> <strong>Fournisseur</strong> complétement<br>'; break ;
										}
			
		?></td><!-- responsabilité -->
		<td style="text-align:center;"><!-- commentaire -->
<?			
			$nb_commentaire = e('nb_commentaire',mysql_fetch_array(mysql_query("SELECT count(id) as nb_commentaire FROM anomalie_commentaire WHERE id_anomalie='$row[id]'  AND supprime=0"))); ?>
			
				<a class="hide_when_print" href="javascript:liste_commentaire('<?=$row['id']?>');" style="border:none;"><img src="gfx/list.gif" alt="Liste des commentaires" title="Liste des commentaires" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_commentaire ? $nb_commentaire : '' ?></span>

			<br><a href="javascript:commentaire_anomalie('<?=$row['id']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;"><a href="creation_anomalie.php?id=<?=$row['id']?>"><img src="gfx/edit.gif" alt="Modification" /></a></td>
	</tr>

			<tr style="display:none;" id="commentaire_anomalie_<?=$row['id']?>">
				<td><img src="gfx/return.jpg"></td>
				<td colspan="13" valign="top" style="padding:0px;">
					<div style="background:#F0F0F0;padding:3px;"><strong style="text-decoration:underline;">Problème rencontré :</strong><br><?=$row['probleme']?></div>
<?		// ON AFFICHE LES commentaire POUR CETTE anomalie
		if ($nb_commentaire) {
			$res_commentaire = mysql_query("SELECT *,DATE_FORMAT(date_creation,'%d %b %Y') AS date_formater,DATE_FORMAT(date_creation,'%w') AS date_jour,DATE_FORMAT(date_creation,'%H:%i') AS heure_formater,TIME_TO_SEC(TIMEDIFF(NOW(),date_creation)) AS temps_ecoule FROM anomalie_commentaire WHERE id_anomalie='$row[id]' AND supprime=0 ORDER BY date_creation ASC") or die("Ne peux pas afficher les commentaires anomalies ".mysql_error()); 
?>
					<table class="commentaire" width="100%" cellspacing="0">
							<caption>Liste des commentaires</caption>
<?							while($row_commentaire = mysql_fetch_array($res_commentaire)) {
								$is_createur_commentaire = e('ip',mysql_fetch_array(mysql_query("SELECT ip FROM employe WHERE prenom='$row_commentaire[createur]'"))) == $_SERVER['REMOTE_ADDR'] ? TRUE:FALSE ;
?>
							<tr>
								<td width="15%"><?=$jours_mini[$row_commentaire['date_jour']]?> <?=$row_commentaire['date_formater']?> <?=$row_commentaire['heure_formater']?></td>
								<td width="5%">
	<?								switch ($row_commentaire['humeur']) {
										case 0: ?>&nbsp;<?
											break;
										case 1: ?><img src="gfx/weather-clear.png"><?
											break;
										case 2: ?><img src="gfx/weather-few-clouds.png"><?
											break;
										case 3: ?><img src="gfx/weather-storm.png"><?
											break;
									}
									
									if		($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie cloturée') { ?>
										<img src="gfx/link.png">
<?									} elseif($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie réouverte') { ?>
										<img src="gfx/link_break.png">
<?									} ?>
								</td>
								<td width="10%"><?=$row_commentaire['createur']?>&nbsp;</td>
								<td width="10%"><?=$row_commentaire['type']?>&nbsp;</td>
								<td width="60%"><?=stripslashes($row_commentaire['commentaire'])?></td>
								<td width="5%">
<?									if		($is_createur_commentaire && $row_commentaire['type'] != 'autre' && ($row_commentaire['temps_ecoule'] < MAX_TIME_ANOMALIE_DELETION)) { ?>
										<a href="javascript:delete_commentaire(<?=$row_commentaire['id']?>);"><img src="gfx/comment_delete.png"></a>
<?									} else { ?>&nbsp;<? } ?>
								</td>
							</tr>
<?							}  ?>
							<tr>	
								<td colspan="6">&nbsp;</td><!-- un blanc pour aéré -->
							</tr>
					</table>
<?		} // fin affiche les commentaires anomalie ?>
				</td>
			</tr>

<?		$total_ligne++;
	} // while anomalie ?>


<tr>
	<td colspan="10">
		Nombre de lignes : <?=$total_ligne?>
	</td>
</tr>

</table>
</form>
</body>
</html>
<?
mysql_close($mysql);
?>