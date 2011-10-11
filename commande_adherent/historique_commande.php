<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$erreur   = FALSE ;
$message  = '' ;

$today_Ymd = date('Ymd') ;
$vendeurs = select_vendeur();

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['cde_adh_filtre_date_inf']))	$_SESSION['cde_adh_filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d')-0,date('Y')));
if (!isset($_SESSION['cde_adh_filtre_date_sup']))	$_SESSION['cde_adh_filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['cde_adh_filtre_adherent']))	$_SESSION['cde_adh_filtre_adherent']	= '';
if (!isset($_SESSION['cde_adh_filtre_reference']))	$_SESSION['cde_adh_filtre_reference']	= '';
if (!isset($_SESSION['cde_adh_filtre_vendeur']))	$_SESSION['cde_adh_filtre_vendeur']		= e('code',mysql_fetch_array(mysql_query("SELECT UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL and ip='$_SERVER[REMOTE_ADDR]' ORDER BY prenom ASC")));
if (!isset($_SESSION['cde_adh_filtre_type_vente']))	$_SESSION['cde_adh_filtre_type_vente']	= '';
if (!isset($_SESSION['cde_adh_filtre_numero']))		$_SESSION['cde_adh_filtre_numero']		= '';
if (!isset($_SESSION['cde_adh_filtre_montant']))	$_SESSION['cde_adh_filtre_montant']		= 0;
if (!isset($_SESSION['cde_adh_filtre_signe_montant']))	$_SESSION['cde_adh_filtre_signe_montant'] = '>=';
if (!isset($_SESSION['cde_adh_filtre_classement'])) $_SESSION['cde_adh_filtre_classement']	= 'NOBON DESC';
if (!isset($_SESSION['cde_adh_filtre_article']))	$_SESSION['cde_adh_filtre_article']		= '';
if (!isset($_SESSION['cde_adh_filtre_type_cde']))	$_SESSION['cde_adh_filtre_type_cde']	= '';
if (!isset($_SESSION['cde_adh_filtre_agence']))		$_SESSION['cde_adh_filtre_agence']	    = LOGINOR_AGENCE;

if (isset($_POST['filtre_date_inf']))	$_SESSION['cde_adh_filtre_date_inf']	= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))	$_SESSION['cde_adh_filtre_date_sup']	= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_adherent']))	$_SESSION['cde_adh_filtre_adherent']	= $_POST['filtre_adherent'];
if (isset($_POST['filtre_reference']))	$_SESSION['cde_adh_filtre_reference']	= $_POST['filtre_reference'];
if (isset($_POST['filtre_vendeur']))	$_SESSION['cde_adh_filtre_vendeur']		= $_POST['filtre_vendeur'];
if (isset($_POST['filtre_type_vente']))	$_SESSION['cde_adh_filtre_type_vente']	= $_POST['filtre_type_vente'];
if (isset($_POST['filtre_numero']))		$_SESSION['cde_adh_filtre_numero']		= $_POST['filtre_numero'];
if (isset($_POST['filtre_montant']))	$_SESSION['cde_adh_filtre_montant']		= $_POST['filtre_montant'];
if (isset($_POST['filtre_signe_montant']))	$_SESSION['cde_adh_filtre_signe_montant'] = $_POST['filtre_signe_montant'];
if (isset($_GET['filtre_classement']))	$_SESSION['cde_adh_filtre_classement']  = $_GET['filtre_classement'];
if (isset($_POST['filtre_article']))	$_SESSION['cde_adh_filtre_article']		= $_POST['filtre_article'];
if (isset($_POST['filtre_type_cde']))	$_SESSION['cde_adh_filtre_type_cde']	= $_POST['filtre_type_cde'];
if (isset($_POST['filtre_agence']))		$_SESSION['cde_adh_filtre_agence']	    = $_POST['filtre_agence'];


// ACTION A FAIRE
if(isset($_GET['action']) && $_GET['action']=='delete_relance' && isset($_GET['id']) && $_GET['id']) { // mode delete relance
	mysql_query("DELETE FROM commande_adherent_relance WHERE id=$_GET[id]") or die("Ne peux pas supprimer la relance ".mysql_error());
	$message = "La relance a été correctement supprimée";
}


if(isset($_POST['action']) && $_POST['action']=='saisie_relance' && isset($_POST['NOBON']) && $_POST['NOBON']) { // mode saisie de relance client
	$date = implode('-',array_reverse(explode('/',$_POST['relance_date']))).' '.$_POST['relance_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO commande_adherent_relance (NOBON,`date`,representant,`type`,humeur,commentaire) VALUES ('$_POST[NOBON]','$date','$_POST[relance_representant]','$_POST[relance_type]',$_POST[relance_humeur],'".mysql_escape_string($_POST['relance_commentaire'])."')") or die("Ne peux pas enregistrer la relance adhérent ".mysql_error());
	$message = "La relance client du bon n° $_POST[NOBON] a été enregistrée";
}

?>
<html>
<head>
<title>Historique des commandes adhérent</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-commande th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-commande { border-collapse:collapse; }

table#historique-commande td { border:solid 1px grey; padding:3px;font-size:0.8em;}

table#historique-commande th.<?=e(0,explode(' ',$_SESSION['cde_adh_filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-commande th.<?=e(0,explode(' ',$_SESSION['cde_adh_filtre_classement']))?>,  table#historique-commande td.<?=e(0,explode(' ',$_SESSION['cde_adh_filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-commande td.<?=e(0,explode(' ',$_SESSION['cde_adh_filtre_classement']))?> {
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

table#historique-commande tr.ligne:nth-of-type(even) {
	background:#F5F5F5;
}

div#relance {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
}

span.agence  {
	font-size:1em;
	font-weight:normal;
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
		mode : "textareas",
		theme : "simple"
	});
</script>
<SCRIPT LANGUAGE="JavaScript">
<!--

function relance_commande(numero) {

	var maDate = new Date() ;

	document.historique_commande.NOBON.value = numero ;
	document.historique_commande.relance_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.historique_commande.relance_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#relance_numero').text(numero) ;
	$('#relance').css('top',document.body.scrollTop +100);
	$('#relance').css('left',screen.availWidth / 2 - 300);
	$('#relance').show();

	document.historique_commande.relance_commentaire.focus();
}

function delete_relance(id) {
	if (confirm("Voulez-vous vraiment supprimer cette relance ?"))
		document.location.href = '<?=$_SERVER['PHP_SELF']?>?action=delete_relance&id=' + id ;
}

function liste_relance(id) {
	document.getElementById('relance_commande_' + id).style.display = document.getElementById('relance_commande_' + id).style.display == 'table-row' ? 'none' : 'table-row' ;
}

function liste_toute_relance() {
	var tr_elements = document.getElementsByTagName('tr');
	var what = '';
	if (document.historique_commande.button_affiche_relance.value == 'Afficher') { // on doit cacher les relances
		document.historique_commande.button_affiche_relance.value = 'Cache';
		what = 'table-row';
	} else { // on doit afficher les relances
		document.historique_commande.button_affiche_relance.value = 'Afficher';
		what = 'none';
	}

	for(i=0 ; i<tr_elements.length ; i++) {
		if (tr_elements[i]['id'].match(/^relance_commande_\w+$/))
			tr_elements[i].style.display = what ;
	}
}

function cache(id) {
	$('#'+id).hide();
}

function envoi_formulaire(l_action) {
	document.historique_commande.action.value = l_action ;
	document.historique_commande.submit();
	return true;
}

$(document).ready(function() {
	$("#historique-commande").tablesorter();
});


//-->
</SCRIPT>

</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_commande" action="<?=$_SERVER['PHP_SELF']?>" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="NOBON" value="">


<!-- boite de dialogue pour la relance client -->
<div id="relance">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des relances adhérents</caption>
	<tr>
		<td>Cde n°</td>
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




<!-- TABLEAU AVEC LES CDE ET LE MOTEUR DE RECHERCHE -->
<table id="historique-commande" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des commandes adhérent <input type="checkbox" name="debug"<?=DEBUG?' checked':''?> class="hide_when_print"/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<span class="agence">Agence</span>		
		<select name="filtre_agence">
			<option value=""<?= $_SESSION['cde_adh_filtre_agence']=='' ? ' selected':''?>>Toutes agences</option>	
<?			foreach ($AGENCES as $code_agence => $info_agence) { ?>
				<option value="<?=$code_agence?>"<?= $_SESSION['cde_adh_filtre_agence']==$code_agence ? ' selected':''?>><?=$info_agence[0]?></option>
<?			} ?>
		</select>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['cde_adh_filtre_date_inf']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_inf" style="vertical-align:middle;cursor:pointer;" title="Date selector" class="hide_when_print"/>
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_commande.filtre_date_inf.value='';"  class="hide_when_print">
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['cde_adh_filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:1em;">Adhérent</td>
				<td><input type="text" name="filtre_adherent" value="<?=$_SESSION['cde_adh_filtre_adherent']?>" size="8"></td>
				<td style="padding-left:1em;">Montant
					<select name="filtre_signe_montant">
						<option value=">="<?=$_SESSION['cde_adh_filtre_signe_montant']=='>=' ? ' selected':''?>>supérieur à</option>
						<option value="<="<?=$_SESSION['cde_adh_filtre_signe_montant']=='<=' ? ' selected':''?>>inférieur à</option>
					</select></td>
				<td><input type="text" name="filtre_montant" value="<?=$_SESSION['cde_adh_filtre_montant'] ? $_SESSION['cde_adh_filtre_montant']:'0' ?>" size="3">&euro;</td>
				<td>
					<select name="filtre_type_cde">
						<option value=""<?=$_SESSION['cde_adh_filtre_type_cde']==''							?' selected':''?>>Tous types de cde</option>
						<option value="cde_en_cours"<?=$_SESSION['cde_adh_filtre_type_cde']=='cde_en_cours'	?' selected':''?>>Cde en reliquats</option>
					</select>
				</td>
				<td style="text-align:right;"><input type="submit" class="button divers hide_when_print" style="background-image:url(/intranet/gfx/magnify.png);" value="Filtrer"></td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['cde_adh_filtre_date_sup']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_sup" style="vertical-align:middle;cursor: pointer;"title="Date selector" class="hide_when_print"/>
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_commande.filtre_date_sup.value='';" class="hide_when_print">
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['cde_adh_filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="text-align:right;">Vendeur</td>
				<td>
					<select name="filtre_vendeur">
							<option value=""<?=$_SESSION['cde_adh_filtre_vendeur']==''?' selected':''?>>TOUS</option>
<?						while (list($key, $val) = each($vendeurs)) { ?>
							<option value="<?=$key?>" <?=strrpos($key,',') === false ? '':'style="font-weight:bold;background-color:grey;color:white;"' ?> <?=$_SESSION['cde_adh_filtre_vendeur']==$key ? ' selected':''?>><?=$val?></option>
<?						} ?>
					</select>
				</td>
				<td style="text-align:right;">Référence <input type="text" name="filtre_reference" value="<?=$_SESSION['cde_adh_filtre_reference']?>" size="8"></td>
				<td style="text-align:right;padding-left:1em;">N° Cde <input type="text" name="filtre_numero" value="<?=$_SESSION['cde_adh_filtre_numero']?>" size="8"></td>
				<td style="padding-left:1em;">Code Article <input type="text" name="filtre_article" value="<?=$_SESSION['cde_adh_filtre_article']?>" size="8"></td>
				<td><!-- type de vente -->
					Type de vente :
					<select name="filtre_type_vente">
						<option value=""	<?=$_SESSION['cde_adh_filtre_type_vente']==''?' selected':''?>>TOUS</option>
						<option value="EMP"	<?=$_SESSION['cde_adh_filtre_type_vente']=='EMP' ? ' selected':''?>>Emportée</option>
						<option value="LIV"	<?=$_SESSION['cde_adh_filtre_type_vente']=='LIV' ? ' selected':''?>>Livrée</option>
					</select>
				</td>
			</tr>
		</table>

	</caption>
	<thead>
	<tr>
		<th class="NOBON">N°<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NOBON ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NOBON DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="DATE">Date<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=DATE ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=DATE DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="DATE">Date Liv<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=DATELIV ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=DATELIV DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="TYVTE">Type<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=TYVTE ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=TYVTE DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="LIVSB">Vendeur<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=LIVSB ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=LIVSB DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="NOMSB">Adhérent<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NOMSB ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NOMSB DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="RFCSB">Référence<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=RFCSB ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=RFCSB DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="AGENC">Agence<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=CDE_ENTETE.AGENC ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=CDE_ENTETE.AGENC DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="NBLIG">Nb ligne<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NBLIG ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=NBLIG DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="MONTBT">Mt HT Cde<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=MONTBT ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=MONTBT DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th>Relances<br><input name="button_affiche_relance" type="button" class="button divers hide_when_print" style="background-image:url(/intranet/gfx/comments.png);" value="Afficher" onclick="liste_toute_relance();"></th>
		<th style="vertical-align:top;" class="hide_when_print">PDF<br/>chiffré</th>
		<th style="vertical-align:top;" class="hide_when_print">PDF</th>
		<th style="vertical-align:top;" class="hide_when_print">Etiq</th>
	</tr>
	</thead>
	<tbody>
<?
	$where = array() ;
	$tables = array("${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 CDE_ENTETE",
					"${LOGINOR_PREFIX_BASE}GESTCOM.AGENCEP1 AGENCE");
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['cde_adh_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['cde_adh_filtre_date_sup'])));
	
	if ($_SESSION['cde_adh_filtre_date_inf'] && $_SESSION['cde_adh_filtre_date_inf'] != 'Aucune') $where[] = "CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) >= '$date_inf_formater'" ;
	if ($_SESSION['cde_adh_filtre_date_sup'] && $_SESSION['cde_adh_filtre_date_sup'] != 'Aucune') $where[] = "CONCAT(DTBOS,CONCAT(DTBOA,CONCAT('-',CONCAT(DTBOM,CONCAT('-',DTBOJ))))) <= '$date_sup_formater'" ;
	if ($_SESSION['cde_adh_filtre_adherent'])	$where[] = "NOMSB like '%".strtoupper(mysql_escape_string($_SESSION['cde_adh_filtre_adherent']))."%'" ;
	if ($_SESSION['cde_adh_filtre_vendeur'])	{
		$tmp = explode(',',$_SESSION['cde_adh_filtre_vendeur']);
		for($i=0 ; $i<sizeof($tmp) ; $i++)
			$tmp[$i] = "LIVSB='".strtoupper(mysql_escape_string($tmp[$i]))."'" ;
		$where[] = "(".join(' or ',$tmp).")";
	}
	if ($_SESSION['cde_adh_filtre_reference'])	$where[] = "RFCSB like '%".strtoupper(mysql_escape_string($_SESSION['cde_adh_filtre_reference']))."%'" ;
	if ($_SESSION['cde_adh_filtre_numero'])		$where[] = "CDE_ENTETE.NOBON like '".strtoupper(trim(mysql_escape_string($_SESSION['cde_adh_filtre_numero'])))."%'" ;

	$where[] = "MONTBT $_SESSION[cde_adh_filtre_signe_montant] '$_SESSION[cde_adh_filtre_montant]'" ;
	$where[] = "NBLIG > '0'" ;						// au moins une ligne sur le bon
	$where[] = "ETSEE = ''" ;						// commande non annulée
	$where[] = "CDE_ENTETE.AGENC = AGENCE.AGECO" ;	// jointure bon<->agence

	if ($_SESSION['cde_adh_filtre_agence']) // si une agence de spécifié
		$where[] = "CDE_ENTETE.AGENC = '$_SESSION[cde_adh_filtre_agence]'" ; // uniquement pour l'agence en cours

	if ($_SESSION['cde_adh_filtre_type_vente']) // si une agence de spécifié
		$where[] = "CDE_ENTETE.TYVTE = '$_SESSION[cde_adh_filtre_type_vente]'" ; // type de vente EMP ou LIV

	// gere les recherche sur article et type de commande
	if ($_SESSION['cde_adh_filtre_article'] || $_SESSION['cde_adh_filtre_type_cde']) {
		$tables[] = "${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 CDE_DETAIL"; // on rajoute la table détail
		$where[]  = "CDE_ENTETE.NOBON=CDE_DETAIL.NOBON"; // liaison naturel entre detail et entete

		// code article présent dans la cde
		if ($_SESSION['cde_adh_filtre_article'])
			$where[]  = "CDE_DETAIL.CODAR='".strtoupper(trim(mysql_escape_string($_SESSION['cde_adh_filtre_article'])))."'";

		// reliquat ou livrées
		if ($_SESSION['cde_adh_filtre_type_cde'])
			$where[]  = "(CDE_DETAIL.TRAIT='".($_SESSION['cde_adh_filtre_type_cde']=='cde_en_cours'?'R':'F')."' AND ". // article non receptioné
						"CDE_DETAIL.PROFI='1' AND ". // un article et pas un commentaire
						"CDE_DETAIL.ETSBE='')" ; // Une ligne non annulée
	}

	$where = $where ? $where = ' where '.join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	if		($_SESSION['cde_adh_filtre_classement'] == 'DATE DESC')
		$ordre = 'DTBOS DESC, DTBOA DESC, DTBOM DESC, DTBOJ DESC';
	elseif	($_SESSION['cde_adh_filtre_classement'] == 'DATE ASC')
		$ordre = 'DTBOS ASC, DTBOA ASC, DTBOM ASC, DTBOJ ASC';
	elseif	($_SESSION['cde_adh_filtre_classement'] == 'DATELIV DESC')
		$ordre = 'DLSSB DESC, DLASB DESC, DLMSB DESC, DLJSB DESC';
	elseif	($_SESSION['cde_adh_filtre_classement'] == 'DATELIV ASC')
		$ordre = 'DLSSB ASC, DLASB ASC, DLMSB ASC, DLJSB ASC';
	else
		$ordre = $_SESSION['cde_adh_filtre_classement'];

	$tables = join(',',$tables);

	$sql = <<<EOT
select DISTINCT(CDE_ENTETE.NOBON),CDE_ENTETE.NOCLI,DTBOM,DTBOJ,DTBOS,DTBOA,DLSSB,DLASB,DLMSB,DLJSB,LIVSB,NBLIG,MONTBT,NOMSB,RFCSB,AGELI,TYVTE
from $tables
$where
order by $ordre
EOT;

if (DEBUG) echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$total_ligne = 0 ;
	$total_montant = 0 ;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
	while($row = odbc_fetch_array($res)) {
//	$row = odbc_fetch_array($res)
?>

	<tr class="ligne">
		<td class="NOBON"><?=$row['NOBON']?></td>
		<td class="DATE"><?
			$date_commande = mktime(0,0,0,$row['DTBOM'],$row['DTBOJ'],$row['DTBOS'].$row['DTBOA']) ;
			$date_formater = date('d M Y',$date_commande);
			$jour_commande = $jours_mini[date('w',$date_commande)];		
		?><?=$jour_commande?> <?=$date_formater?></td><!-- date -->
		<td class="DATELIV"><?
			$date_livraison = mktime(0,0,0,$row['DLMSB'],$row['DLJSB'],$row['DLSSB'].$row['DLASB']) ;
			$date_formater = date('d M Y',$date_livraison);
			$jour_commande = $jours_mini[date('w',$date_livraison)];		
		?><?=$jour_commande?> <?=$date_formater?>
		<?= ($today_Ymd > $row['DLSSB'].$row['DLASB'].$row['DLMSB'].$row['DLJSB'] && $_SESSION['cde_adh_filtre_type_cde']=='cde_en_cours') ? "<img src='../gfx/attention.png'/>":''?></td><!-- date livraison -->
		<td class="TYVTE"><?=isset($vendeurs[trim($row['TYVTE'])]) ? $vendeurs[trim($row['TYVTE'])] : trim($row['TYVTE'])?></td><!-- type de vente -->
		<td class="LIVSB"><?=isset($vendeurs[trim($row['LIVSB'])]) ? $vendeurs[trim($row['LIVSB'])] : trim($row['LIVSB'])?></td><!-- représentant -->
		<td class="NOMSB" style="text-align:left;"><?=$row['NOMSB']?></td><!-- adhérent -->
		<td class="RFCSB" style="text-align:left;"><?=$row['RFCSB']?></td><!-- réference -->
		<td class="AGENC" style="text-align:center;"><?= ucfirst(strtolower($row['AGELI'])) ?></td><!-- agence -->
		<td class="NBLIG" style="text-align:center;"><?=(int)$row['NBLIG']?></td><!-- nombre de ligne -->
		<td class="MONTBT" style="text-align:right;" nowrap><?=$row['MONTBT']?> &euro;</td><!-- Mt commande -->
		<td style="text-align:center;"><!-- relance -->
<?			
			$nb_relance = e('nb_relance',mysql_fetch_array(mysql_query("SELECT count(id) as nb_relance FROM commande_adherent_relance WHERE NOBON='$row[NOBON]'")));
			if ($nb_relance) { ?>
				<a class="hide_when_print" href="javascript:liste_relance('<?=$row['NOBON']?>');" style="border:none;"><img src="/intranet/gfx/list.gif" alt="Liste des relances" title="Liste des relances fournisseur" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_relance?></span>
<?			} ?>
			<br><a href="javascript:relance_commande('<?=$row['NOBON']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;" class="hide_when_print"><a href="edition_pdf.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>"><img src="../gfx/pdf-icon_avec_prix.png" alt="Edition PDF" /></a></td>
		<td style="text-align:center;" class="hide_when_print"><a href="edition_pdf.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>&options[]=sans_prix"><img src="../gfx/pdf-icon_sans_prix_FR.png" alt="Edition PDF - Ligne F et R" /></a><br/><a href="edition_pdf.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>&options[]=sans_prix&options[]=ligne_R"><img src="../gfx/pdf-icon_sans_prix_R.png" alt="Edition PDF - Ligne R" style="margin-top:3px;"/></a></td>
		<td style="text-align:center;" class="hide_when_print"><a href="edition_etiquette.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>"><img src="gfx/icon_etiquette.png" alt="Edition Etiquette" /></a></td>
	</tr>


<?		// ON AFFICHE LES RELANCE CLIENTS POUR CETT commande
		if ($nb_relance) {
			$res_relance = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,DATE_FORMAT(`date`,'%w') AS date_jour,DATE_FORMAT(`date`,'%H:%i') AS heure_formater FROM commande_adherent_relance WHERE NOBON='$row[NOBON]' ORDER BY `date` DESC") or die("Ne peux pas afficher les relances commandes adhérent ".mysql_error()); 
?>
			<tr style="display:none;" id="relance_commande_<?=$row['NOBON']?>">
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
<?		} // fin affiche les relances commande

		$total_ligne++;
		$total_montant += $row['MONTBT'] ;
	} // while commande ?>
	</tbody>
	<tfoot>
	<tr>
		<td colspan="4">
			Nombre de lignes : <?=$total_ligne?>
		</td>
		<td colspan="9">
			Total des montants : <?=$total_montant?> &euro;
		</td>
	</tr>
	</tfoot>
</table>
</form>
</body>
</html>
<?
odbc_close($loginor);
mysql_close($mysql);
?>