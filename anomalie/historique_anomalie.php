<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$message  = '';

$employe = array();
$res = mysql_query("SELECT prenom FROM employe WHERE printer=0 AND nom<>'' ORDER BY prenom ASC") or die("ne peux pas r�cup�rer la liste des employ�s. ".mysql_error());
while ($row = mysql_fetch_array($res))
	$employe[] = $row['prenom'];

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['anomalie_filtre_date_inf']))	$_SESSION['anomalie_filtre_date_inf']				= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')-1,date('d'),date('Y')));
if (!isset($_SESSION['anomalie_filtre_date_sup']))	$_SESSION['anomalie_filtre_date_sup']				= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')  ,date('d'),date('Y')));
if (!isset($_SESSION['anomalie_filtre_adherent']))			$_SESSION['anomalie_filtre_adherent']		= '';
if (!isset($_SESSION['anomalie_filtre_fournisseur']))		$_SESSION['anomalie_filtre_fournisseur']	= '';
if (!isset($_SESSION['anomalie_filtre_createur']))			$_SESSION['anomalie_filtre_createur']		= '';
if (!isset($_SESSION['anomalie_filtre_numero']))			$_SESSION['anomalie_filtre_numero']			= '';
if (!isset($_SESSION['anomalie_filtre_num_retour']))		$_SESSION['anomalie_filtre_num_retour']		= '';
if (!isset($_SESSION['anomalie_filtre_evolution']))			$_SESSION['anomalie_filtre_evolution']		= '';
if (!isset($_SESSION['anomalie_filtre_classement']))		$_SESSION['anomalie_filtre_classement'] 	= 'date_creation DESC';
if (!isset($_SESSION['anomalie_filtre_logistique']))		$_SESSION['anomalie_filtre_logistique']		= TRUE;
if (!isset($_SESSION['anomalie_filtre_commerce']))			$_SESSION['anomalie_filtre_commerce']		= TRUE;
if (!isset($_SESSION['anomalie_filtre_exposition']))		$_SESSION['anomalie_filtre_exposition']		= TRUE;
if (!isset($_SESSION['anomalie_filtre_administratif']))		$_SESSION['anomalie_filtre_administratif']	= TRUE;
if (!isset($_SESSION['anomalie_filtre_informatique']))		$_SESSION['anomalie_filtre_informatique']	= TRUE;
if (!isset($_SESSION['anomalie_filtre_litige']))			$_SESSION['anomalie_filtre_litige']			= TRUE;
if (!isset($_SESSION['anomalie_filtre_autre']))				$_SESSION['anomalie_filtre_autre']			= TRUE;
if (!isset($_SESSION['anomalie_filtre_transport']))			$_SESSION['anomalie_filtre_transport']		= TRUE;
if (!isset($_SESSION['anomalie_filtre_etat_a_traiter']))	$_SESSION['anomalie_filtre_etat_a_traiter']	= TRUE;
if (!isset($_SESSION['anomalie_filtre_etat_en_cours']))		$_SESSION['anomalie_filtre_etat_en_cours']	= TRUE;
if (!isset($_SESSION['anomalie_filtre_etat_cloture']))		$_SESSION['anomalie_filtre_etat_cloture']	= FALSE;
if (!isset($_SESSION['anomalie_from_fiche_fournisseur']))	$_SESSION['anomalie_from_fiche_fournisseur']= '';


if (isset($_POST['filtre_date_inf']))	$_SESSION['anomalie_filtre_date_inf']	= $_POST['filtre_date_inf'];
if (isset($_GET['filtre_date_inf']))	$_SESSION['anomalie_filtre_date_inf']	= $_GET['filtre_date_inf']; // pour pouvoir y acceder via une url
if (isset($_POST['filtre_date_sup']))	$_SESSION['anomalie_filtre_date_sup']	= $_POST['filtre_date_sup'];
if (isset($_GET['filtre_date_sup']))	$_SESSION['anomalie_filtre_date_sup']	= $_GET['filtre_date_sup']; // pour pouvoir y acceder via une url
if (isset($_GET['filtre_adherent']))	$_SESSION['anomalie_filtre_adherent']	= $_GET['filtre_adherent'];
if (isset($_POST['filtre_adherent']))	$_SESSION['anomalie_filtre_adherent']	= $_POST['filtre_adherent'];
if (isset($_POST['filtre_fournisseur']))$_SESSION['anomalie_filtre_fournisseur']= $_POST['filtre_fournisseur'];
if (isset($_GET['filtre_fournisseur'])) $_SESSION['anomalie_filtre_fournisseur']= $_GET['filtre_fournisseur']; // pour pouvoir y acceder via une url
if (isset($_POST['filtre_createur']))	$_SESSION['anomalie_filtre_createur']	= $_POST['filtre_createur'];
if (isset($_POST['filtre_numero']))		$_SESSION['anomalie_filtre_numero']		= $_POST['filtre_numero'];
if (isset($_POST['filtre_num_retour']))	$_SESSION['anomalie_filtre_num_retour']	= $_POST['filtre_num_retour']; // pour les litiges
if (isset($_POST['filtre_evolution']))	$_SESSION['anomalie_filtre_evolution']	= $_POST['filtre_evolution'];
if (isset($_GET['filtre_classement']))	$_SESSION['anomalie_filtre_classement'] = $_GET['filtre_classement'];
if (isset($_SERVER['HTTP_REFERER']) && preg_match('/detail_fournisseur\.php/i',$_SERVER['HTTP_REFERER']))  $_SESSION['anomalie_from_fiche_fournisseur'] = $_GET['filtre_fournisseur']; // on arrive des fiches fournisseurs

if (isset($_SERVER['HTTP_REFERER']) && preg_match('/historique_anomalie\.php/i',$_SERVER['HTTP_REFERER'])) { // si on vient d'une recherche, on modifie les coches
	if (isset($_POST['action']) && $_POST['action']=='saisie_commentaire'	||	// ne rien faire si l'on vient de saisir un commentaire
		isset($_GET['action']) && $_GET['action']=='delete_commentaire'		||	// ne rien faire si l'on vient de supprimer un commentaire
		isset($_GET['filtre_classement']))										// ne rien faire si l'on vient de changer le classement
	{ }
	else {
		$_SESSION['anomalie_filtre_logistique']		= isset($_POST['filtre_logistique']);
		$_SESSION['anomalie_filtre_commerce']		= isset($_POST['filtre_commerce']);
		$_SESSION['anomalie_filtre_exposition']		= isset($_POST['filtre_exposition']);
		$_SESSION['anomalie_filtre_administratif']	= isset($_POST['filtre_administratif']);
		$_SESSION['anomalie_filtre_informatique']	= isset($_POST['filtre_informatique']);
		$_SESSION['anomalie_filtre_litige']			= isset($_POST['filtre_litige']);
		$_SESSION['anomalie_filtre_autre']			= isset($_POST['filtre_autre']);
		$_SESSION['anomalie_filtre_transport']		= isset($_POST['filtre_transport']);
		$_SESSION['anomalie_filtre_etat_a_traiter']	= isset($_POST['filtre_etat_a_traiter']);
		$_SESSION['anomalie_filtre_etat_en_cours']	= isset($_POST['filtre_etat_en_cours']);
		$_SESSION['anomalie_filtre_etat_cloture']	= isset($_POST['filtre_etat_cloture']);
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
	$message = "Le commentaire a �t� correctement supprim�e";
	//print_r($_SERVER);
}

// SUPPRIMER UNE ANOMALIE
elseif(isset($_GET['action']) && $_GET['action']=='delete_anomalie' && isset($_GET['id']) && $_GET['id']) { // mode delete anomalie
	mysql_query("UPDATE anomalie SET supprime=1 WHERE id=$_GET[id]") or die("Ne peux pas supprimer l'anomalie ".mysql_error());
	$message = "L'anomalie n�$_GET[id] a �t� correctement supprim�";
	//print_r($_SERVER);
}

// SAISIR UN COMMENTAIRE
elseif(isset($_POST['action']) && $_POST['action']=='saisie_commentaire' && isset($_POST['id']) && $_POST['id']) { // mode saisie de commentaire client
	$date = implode('-',array_reverse(explode('/',$_POST['commentaire_date']))).' '.$_POST['commentaire_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO anomalie_commentaire (id_anomalie,date_creation,createur,`type`,humeur,commentaire,supprime) VALUES ($_POST[id],'$date','$_POST[commentaire_createur]','$_POST[commentaire_type]',$_POST[commentaire_humeur],'".mysql_escape_string($_POST['commentaire_commentaire'])."',0)") or die("Ne peux pas enregistrer le commentaire ".mysql_error());
	$message = "La commentaire de l'anomalie n� $_POST[id] a �t� enregistr�e";



	// faire un envoi de mail au chef de pole
	$res = mysql_query("SELECT * FROM anomalie WHERE id=$_POST[id]") or die ("Ne peux pas r�cup�rer les infos de l'anomalie n�$id. ".mysql_error());
	$row_anomalie = mysql_fetch_array($res);

	$html = <<<EOT
	Nouveau commentaire sur l'anomalie n�$_POST[id] concernant l'artisant $row_anomalie[artisan]<br>
	Cr�� par <b>$_POST[commentaire_createur]</b> le $_POST[commentaire_date] � $_POST[commentaire_heure]<br><br>
	
	<u>Commentaire</u> :<br>
	$_POST[commentaire_commentaire]
EOT;

	
	require_once '../inc/xpm2/smtp.php';
	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR,SMTP_USER,SMTP_PASS,(int)SMTP_PORT,'autodetect',SMTP_TLS_SLL ? SMTP_TLS_SLL:false);
	$emails_deja_envoye = array();
	foreach ($CHEFS_DE_POLE as $p=>$chef) {
		if (($row_anomalie['pole'] & $p) && !in_array($chef['email'],$emails_deja_envoye)) {
			$mail->AddTo($chef['email'],$chef['nom']) or die("Erreur d'ajout de destinataire");
			array_push($emails_deja_envoye,$chef['email']); // on enregistre l'email pour ne pas lui envoyer d'autre mail
		}
	}
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

div#blackscreen {
	position:absolute;
	top:0px;
	left:0px;
	width:100%;
	height:100%;
	background-color:rgba(0,0,0,0.6);
	display:none;
}

table#historique-anomalie th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-anomalie { border-collapse:collapse; }

table#historique-anomalie td { border:solid 1px grey; padding:3px;font-size:0.8em; vertical-align:top;}

table#historique-anomalie th.<?=e(0,explode(' ',$_SESSION['anomalie_filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-anomalie th.<?=e(0,explode(' ',$_SESSION['anomalie_filtre_classement']))?>,  table#historique-anomalie td.<?=e(0,explode(' ',$_SESSION['anomalie_filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-anomalie td.<?=e(0,explode(' ',$_SESSION['anomalie_filtre_classement']))?> {
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
	border-radius:10px;
	background:white;
	display:none;
	position:absolute;
	z-index:99;
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
<script language="JavaScript" SRC="../js/jquery.js"></script>
<script language="JavaScript" SRC="../js/mobile.style.js"></script>
<script language="JavaScript" SRC="../js/data_dumper.js"></script>
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

	$('#blackscreen').fadeIn( 500 ,function() {
		$('#commentaire').show();
	});

	document.historique_anomalie.commentaire_commentaire.focus();
}

function delete_commentaire(id) {
	if (confirm("Voulez-vous vraiment supprimer ce commentaire ?"))
		document.location.href = 'historique_anomalie.php?action=delete_commentaire&id=' + id ;
}

function liste_commentaire(id) {
	$('#commentaire_anomalie_' + id).css('display',$('#commentaire_anomalie_' + id).css('display') == 'table-row' ? 'none' : 'table-row') ;
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
	$('#blackscreen').fadeOut(500);
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

<div id="blackscreen"></div>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_anomalie" action="historique_anomalie.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="id" value="">


<!-- boite de dialogue pour la commentaire client -->
<div id="commentaire">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des commentaires</caption>
	<tr>
		<td>Anomalie n�</td>
		<td id="commentaire_numero"></td>
		<td></td>
		<td><input type="text" name="commentaire_date" size="8" maxlength="10"> <input type="text" name="commentaire_heure" size="5" maxlength="5"></td>
	</tr>
	<tr>
		<td>Type</td>
		<td>
			<select name="commentaire_type">
				<option value="telephone">T�l�phone</option>
				<option value="fax">Fax</option>
				<option value="visite">Visite en salle</option>
				<option value="courrier">Courrier</option>
				<option value="email">Email</option>
			</select>
		</td>
		<td>Repr�sentant</td>
		<td>
			<select name="commentaire_createur">
<?			$res  = mysql_query("SELECT * FROM employe WHERE printer=0 ORDER BY prenom ASC");
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
				<option style="padding-left:30px;height:20px;" value="0" selected>Indiff�rent</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-clear.png) no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(/intranet/gfx/weather-storm.png) no-repeat left;" value="3">Enerv�</option>
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
<div style="text-align:left;margin-bottom:5px;margin-top:5px;">
	<input type="button" class="button divers" style="background-image:url(gfx/anomalie_small.png);" onclick="javascript:document.location.href='creation_anomalie.php';" value="Cr�ation d'anomalie" />
	<? if ($_SESSION['anomalie_from_fiche_fournisseur']) { ?>
			<input type="button" class="button divers" style="background-image:url(gfx/fiche_fournisseur_mini.png);margin-left:10px;" onclick="javascript:document.location.href='../outils/fiche_fournisseur/detail_fournisseur.php?id=<?=$_SESSION['anomalie_from_fiche_fournisseur']?>';" value="Retour � la fiche fournisseur" />
	<? } ?>
</div>

<!-- TABLEAU AVEC LES CDE ET LE MOTEUR DE RECHERCHE -->
<table id="historique-anomalie" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des Anomalies <input type="checkbox" name="debug"<?=DEBUG?' checked':''?>/>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de d�part</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['anomalie_filtre_date_inf']?>" size="8">
					<button id="trigger_inf" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.historique_anomalie.filtre_date_inf.value='';">
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['anomalie_filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;">Adh�rent</td>
				<td><input type="text" name="filtre_adherent" value="<?=$_SESSION['anomalie_filtre_adherent']?>" size="8"></td>
				<td style="padding-left:2em;">Cr�ateur</td>
				<td>
					<select name="filtre_createur">
							<option value=""<?=$_SESSION['anomalie_filtre_createur']==''?' selected':''?>>TOUS</option>
<?						foreach ($employe as $val) { ?>
							<option value="<?=$val?>"<?=$_SESSION['anomalie_filtre_createur']==$val ? ' selected':''?>><?=$val?></option>
<?						} ?>
					</select>
				</td>
				<td>&nbsp;</td>
				<td style="text-align:right;"><input type="submit" class="button divers" style="background-image:url(/intranet/gfx/magnify.png);" value="Filtrer"></td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['anomalie_filtre_date_sup']?>" size="8">
					<button id="trigger_sup" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.historique_anomalie.filtre_date_sup.value='';">
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['anomalie_filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="text-align:right;">Fournisseur</td>
				<td><input type="text" name="filtre_fournisseur" value="<?=$_SESSION['anomalie_filtre_fournisseur']?>" size="8"></td>
				<td style="text-align:right;">Num�ro</td>
				<td style="text-align:left;"><input type="text" name="filtre_numero" value="<?=$_SESSION['anomalie_filtre_numero']?>" size="8"></td>
				<td style="text-align:right;">N� retour</td>
				<td style="text-align:left;"><input type="text" name="filtre_num_retour" value="<?=$_SESSION['anomalie_filtre_num_retour']?>" size="11" maxlength="6"></td>
			</tr>
			<tr>
				<td colspan="7" style="padding-top:5px;">
					<label class="mobile<?=$_SESSION['anomalie_filtre_logistique']		? ' mobile-checked':''?>" for="filtre_logistique"	><input type="checkbox" id="filtre_logistique"		name="filtre_logistique"	<?=$_SESSION['anomalie_filtre_logistique']		? 'checked="on"':''?>/>Logistique</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_commerce']		? ' mobile-checked':''?>" for="filtre_commerce"		><input type="checkbox" id="filtre_commerce"		name="filtre_commerce"		<?=$_SESSION['anomalie_filtre_commerce']		? 'checked="on"':''?>/>Commerce</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_exposition']		? ' mobile-checked':''?>" for="filtre_exposition"	><input type="checkbox" id="filtre_exposition"		name="filtre_exposition"	<?=$_SESSION['anomalie_filtre_exposition']		? 'checked="on"':''?>/>Exposition</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_administratif']	? ' mobile-checked':''?>" for="filtre_administratif"><input type="checkbox" id="filtre_administratif"	name="filtre_administratif" <?=$_SESSION['anomalie_filtre_administratif']	? 'checked="on"':''?>/>Administratif</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_informatique']	? ' mobile-checked':''?>" for="filtre_informatique"	><input type="checkbox" id="filtre_informatique"	name="filtre_informatique"	<?=$_SESSION['anomalie_filtre_informatique']	? 'checked="on"':''?>/>Informatique</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_litige']			? ' mobile-checked':''?>" for="filtre_litige"		><input type="checkbox" id="filtre_litige"			name="filtre_litige"		<?=$_SESSION['anomalie_filtre_litige']			? 'checked="on"':''?>/>Litige</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_autre']			? ' mobile-checked':''?>" for="filtre_autre"		><input type="checkbox" id="filtre_autre"			name="filtre_autre"			<?=$_SESSION['anomalie_filtre_autre']			? 'checked="on"':''?>/>Autre</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_transport']		? ' mobile-checked':''?>" for="filtre_transport"	><input type="checkbox" id="filtre_transport"		name="filtre_transport"		<?=$_SESSION['anomalie_filtre_transport']		? 'checked="on"':''?>/>Transport</label>
				</td>
			<tr>
				<td colspan="7" style="padding-top:10px;padding-bottom:10px;">
					<label class="mobile<?=$_SESSION['anomalie_filtre_etat_a_traiter']	? ' mobile-checked':''?>" for="filtre_etat_a_traiter"	><input type="checkbox" id="filtre_etat_a_traiter"	name="filtre_etat_a_traiter"	<?=$_SESSION['anomalie_filtre_etat_a_traiter'] ? 'checked="on"':''?>/>A traiter</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_etat_en_cours']	? ' mobile-checked':''?>" for="filtre_etat_en_cours"	><input type="checkbox" id="filtre_etat_en_cours"	name="filtre_etat_en_cours"		<?=$_SESSION['anomalie_filtre_etat_en_cours'] ? 'checked="on"':''?>	/>En cours</label>
					<label class="mobile<?=$_SESSION['anomalie_filtre_etat_cloture']	? ' mobile-checked':''?>" for="filtre_etat_cloture"		><input type="checkbox" id="filtre_etat_cloture"	name="filtre_etat_cloture"		<?=$_SESSION['anomalie_filtre_etat_cloture'] ? 'checked="on"':''?>	/>Clotur�</label>
				</td>
			</tr>
		</table>

	</caption>
	<tr>
		<th class="id">N�<br><a href="historique_anomalie.php?filtre_classement=id ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=id DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="date_creation">Date cr�ation<br><a href="historique_anomalie.php?filtre_classement=date_creation ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=date_creation DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="createur">Createur<br><a href="historique_anomalie.php?filtre_classement=createur ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=createur DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="artisan">Adh�rent<br><a href="historique_anomalie.php?filtre_classement=artisan ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=artisan DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="fournisseur">Fournisseur<br><a href="historique_anomalie.php?filtre_classement=fournisseur ASC, evolution ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=fournisseur DESC, evolution DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="pole">Pole<br><a href="historique_anomalie.php?filtre_classement=pole ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=pole DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="evolution">Etat<br><a href="historique_anomalie.php?filtre_classement=evolution ASC, fournisseur ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_anomalie.php?filtre_classement=evolution DESC, fournisseur DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="responsabilite">Responsabilit�</th>
		<th class="num_retour">N� Retour</th>
		<th>Commentaire<br><input name="button_affiche_commentaire" type="button" class="button divers" style="background-image:url(/intranet/gfx/comments.png);" value="Afficher" onclick="liste_toute_commentaire();"></th>
		<th>Edit</th>
	</tr>
<?
	$where = array() ;
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['anomalie_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['anomalie_filtre_date_sup'])));

	if ($_SESSION['anomalie_filtre_date_inf'] && $_SESSION['anomalie_filtre_date_inf'] != 'Aucune') $where[] = "date_creation >= '$date_inf_formater 00:00:00'" ;
	if ($_SESSION['anomalie_filtre_date_sup'] && $_SESSION['anomalie_filtre_date_sup'] != 'Aucune') $where[] = "date_creation <= '$date_sup_formater 23:59:59'" ;
	if ($_SESSION['anomalie_filtre_adherent'])		$where[] = "artisan like '%".strtoupper(mysql_escape_string($_SESSION['anomalie_filtre_adherent']))."%'" ;
	if ($_SESSION['anomalie_filtre_createur'])		$where[] = "createur='"	.strtoupper(mysql_escape_string($_SESSION['anomalie_filtre_createur']))."'" ;
	if ($_SESSION['anomalie_filtre_fournisseur'])	$where[] = "fournisseur like '%".strtoupper(mysql_escape_string($_SESSION['anomalie_filtre_fournisseur']))."%'" ;
	if ($_SESSION['anomalie_filtre_numero'])		$where[] = "id='".strtoupper(trim(mysql_escape_string($_SESSION['anomalie_filtre_numero'])))."'" ;
	if ($_SESSION['anomalie_filtre_num_retour'])	$where[] = "num_retour='".strtoupper(trim(mysql_escape_string($_SESSION['anomalie_filtre_num_retour'])))."'" ;

	$pole = array();
	if ($_SESSION['anomalie_filtre_logistique'])	$pole[] = '(pole & '.POLE_LOGISTIQUE.	'='.POLE_LOGISTIQUE.')';
	if ($_SESSION['anomalie_filtre_commerce'])		$pole[] = '(pole & '.POLE_COMMERCE.		'='.POLE_COMMERCE.')';
	if ($_SESSION['anomalie_filtre_exposition'])	$pole[] = '(pole & '.POLE_EXPOSITION.	'='.POLE_EXPOSITION.')';
	if ($_SESSION['anomalie_filtre_administratif'])	$pole[] = '(pole & '.POLE_ADMINISTRATIF.'='.POLE_ADMINISTRATIF.')';
	if ($_SESSION['anomalie_filtre_informatique'])	$pole[] = '(pole & '.POLE_INFORMATIQUE.	'='.POLE_INFORMATIQUE.')';
	if ($_SESSION['anomalie_filtre_litige'])		$pole[] = '(pole & '.POLE_LITIGE.		'='.POLE_LITIGE.')';
	if ($_SESSION['anomalie_filtre_autre'])			$pole[] = '(pole & '.POLE_AUTRE.		'='.POLE_AUTRE.')';
	if ($_SESSION['anomalie_filtre_transport'])		$pole[] = '(pole & '.POLE_TRANSPORT.	'='.POLE_TRANSPORT.')';

	if (sizeof($pole)>0) // au moins un pole de coch�
		$where[] = '('.join(' or ',$pole).')';
	else
		$where[] = "pole<0";

	$etat = array();
	if ($_SESSION['anomalie_filtre_etat_a_traiter'])$etat[] = 'evolution=0';
	if ($_SESSION['anomalie_filtre_etat_en_cours'])	$etat[] = 'evolution=1';
	if ($_SESSION['anomalie_filtre_etat_cloture'])	$etat[] = 'evolution=2';

	if (sizeof($etat)>0) // au moins un pole de coch�
		$where[] = '('.join(' or ',$etat).')';
	else 
		$where[] = "evolution<0";

	$where[] = "supprime=0";
	
	$where = $where ? $where = ' where '.join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	$ordre = $_SESSION['anomalie_filtre_classement'];

	$sql = <<<EOT
SELECT
	*,
	DATE_FORMAT(date_creation,'%d %b %Y') AS date_creation_formatee,
	DATE_FORMAT(date_creation,'%w') AS jour_creation,
	DATE_FORMAT(date_cloture,'%d %b %Y') AS date_cloture_formatee
FROM anomalie
$where
ORDER BY $ordre
EOT;

if (DEBUG) echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$total_ligne = 0 ;	$i=0;
	$res = mysql_query($sql) or die("Ne peux pas rechercher les anomalies. ".mysql_error());
	while($row = mysql_fetch_array($res)) {
?>

	<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>">
		<td class="id"><?=$row['id']?></td>
		<td class="date_creation"><?=$jours_mini[$row['jour_creation']]?> <?=$row['date_creation_formatee']?></td><!-- date -->
		<td class="createur"><?=$row['createur']?></td><!-- createur -->
		<td class="artisan" style="text-align:left;"><?=$row['artisan']?></td><!-- adh�rent -->
		<td class="fournisseur" style="text-align:left;"><?=$row['fournisseur']?></td><!-- fournisseur -->
		<td class="pole" style="text-align:left;"><?
			if ($row['pole'] & POLE_LOGISTIQUE)		echo "Logitique<br>";
			if ($row['pole'] & POLE_COMMERCE)		echo "Commerce<br>";
			if ($row['pole'] & POLE_EXPOSITION)		echo "Exposition<br>";
			if ($row['pole'] & POLE_ADMINISTRATIF)	echo "Administratif<br>";
			if ($row['pole'] & POLE_INFORMATIQUE)	echo "Informatique<br>";
			if ($row['pole'] & POLE_LITIGE)			echo "Litige<br>";
			if ($row['pole'] & POLE_AUTRE)			echo "Autre<br>";
			if ($row['pole'] & POLE_TRANSPORT)		echo "Transport<br>";
		?></td><!-- pole -->
		<td class="evolution" style="text-align:left;" nowrap><?
															switch($row['evolution']) {
																case 0: echo '<img src="/intranet/gfx/feu_red.png" /> A traiter'; break ;
																case 1: echo '<img src="/intranet/gfx/feu_yellow.png" /> En cours'; break ;
																case 2: echo "<img src=\"/intranet/gfx/feu_green.png\" /> Clotur�e<br/>Le $row[date_cloture_formatee]"; break ;
															} ?></td><!-- etat -->
		<td class="responsabilite" style="text-align:left;"><?
				switch($row['resp_coop']) {	case 0: break ;
											case 1: echo '<img src="/intranet/gfx/feu_yellow.png" /> <strong>Coop</strong> partiellement<br>'; break ;
											case 2: echo '<img src="/intranet/gfx/feu_red.png" /> <strong>Coop compl�tement</strong><br>'; break ;
										}
				switch($row['resp_adh']) {	case 0: break ;
											case 1: echo '<img src="/intranet/gfx/feu_yellow.png" /> <strong>Adh�rent</strong> partiellement<br>'; break ;
											case 2: echo '<img src="/intranet/gfx/feu_red.png" /> <strong>Adh�rent</strong> compl�tement<br>'; break ;
										}
				switch($row['resp_four']) {	case 0: break ;
											case 1: echo '<img src="/intranet/gfx/feu_yellow.png" /> <strong>Fournisseur</strong> partiellement<br>'; break ;
											case 2: echo '<img src="/intranet/gfx/feu_red.png" /> <strong>Fournisseur</strong> compl�tement<br>'; break ;
										}
			
		?></td><!-- responsabilit� -->
		<td class="fournisseur" style="text-align:center;"><?=$row['num_retour']?></td><!-- num_retour -->
		<td style="text-align:center;"><!-- commentaire -->
<?			
			$nb_commentaire = e('nb_commentaire',mysql_fetch_array(mysql_query("SELECT count(id) as nb_commentaire FROM anomalie_commentaire WHERE id_anomalie='$row[id]'  AND supprime=0"))); ?>
			
				<a class="hide_when_print" href="javascript:liste_commentaire('<?=$row['id']?>');" style="border:none;"><img src="/intranet/gfx/list.gif" alt="Liste des commentaires" title="Liste des commentaires" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_commentaire ? $nb_commentaire : '' ?></span>

			<br><a href="javascript:commentaire_anomalie('<?=$row['id']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;"><a href="creation_anomalie.php?id=<?=$row['id']?>"><img src="/intranet/gfx/edit.gif" alt="Modification" /></a></td>
	</tr>

			<tr style="display:none;" id="commentaire_anomalie_<?=$row['id']?>">
				<td><img src="/intranet/gfx/return.jpg"></td>
				<td colspan="13" valign="top" style="padding:0px;">
					<div style="background:#F0F0F0;padding:3px;"><strong style="text-decoration:underline;">Probl�me rencontr� :</strong><br><?=$row['probleme']?></div>
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
										case 1: ?><img src="/intranet/gfx/weather-clear.png"><?
											break;
										case 2: ?><img src="/intranet/gfx/weather-few-clouds.png"><?
											break;
										case 3: ?><img src="/intranet/gfx/weather-storm.png"><?
											break;
									}
									
									if		($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie clotur�e') { ?>
										<img src="/intranet/gfx/link.png">
<?									} elseif($row_commentaire['type'] == 'autre' && $row_commentaire['commentaire'] == 'Anomalie r�ouverte') { ?>
										<img src="/intranet/gfx/link_break.png">
<?									} ?>
								</td>
								<td width="10%"><?=$row_commentaire['createur']?>&nbsp;</td>
								<td width="10%"><?=$row_commentaire['type']?>&nbsp;</td>
								<td width="60%"><?=stripslashes($row_commentaire['commentaire'])?></td>
								<td width="5%">
<?									if		($is_createur_commentaire && $row_commentaire['type'] != 'autre' && ($row_commentaire['temps_ecoule'] < MAX_TIME_ANOMALIE_DELETION)) { ?>
										<a href="javascript:delete_commentaire(<?=$row_commentaire['id']?>);"><img src="/intranet/gfx/comment_delete.png"></a>
<?									} else { ?>&nbsp;<? } ?>
								</td>
							</tr>
<?							}  ?>
							<tr>	
								<td colspan="6">&nbsp;</td><!-- un blanc pour a�r� -->
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