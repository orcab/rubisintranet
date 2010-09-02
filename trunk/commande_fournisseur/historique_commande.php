<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$erreur   = FALSE ;
$message  = '' ;

$vendeurs = select_vendeur();

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['cde_fourn_filtre_date_inf']))			$_SESSION['cde_fourn_filtre_date_inf']		= $date_inf = date('d/m/Y',mktime(0,0,0,date('m'),date('d')-0,date('Y')));
if (!isset($_SESSION['cde_fourn_filtre_date_sup']))			$_SESSION['cde_fourn_filtre_date_sup']		= $date_inf = date('d/m/Y',mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['cde_fourn_filtre_fournisseur']))		$_SESSION['cde_fourn_filtre_fournisseur']	= '';
if (!isset($_SESSION['cde_fourn_filtre_vendeur']))			$_SESSION['cde_fourn_filtre_vendeur']		= e('code',mysql_fetch_array(mysql_query("SELECT UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL and ip='$_SERVER[REMOTE_ADDR]' ORDER BY prenom ASC")));
if (!isset($_SESSION['cde_fourn_filtre_numero']))			$_SESSION['cde_fourn_filtre_numero']		= '';
if (!isset($_SESSION['cde_fourn_filtre_montant']))			$_SESSION['cde_fourn_filtre_montant']		= 0;
if (!isset($_SESSION['cde_fourn_filtre_signe_montant']))	$_SESSION['cde_fourn_filtre_signe_montant'] = '>=';
if (!isset($_SESSION['cde_fourn_filtre_classement']))		$_SESSION['cde_fourn_filtre_classement']	= 'CFBON DESC';
if (!isset($_SESSION['cde_fourn_filtre_article']))			$_SESSION['cde_fourn_filtre_article']		= '';
if (!isset($_SESSION['cde_fourn_filtre_type_cde']))			$_SESSION['cde_fourn_filtre_type_cde']		= '';
if (!isset($_SESSION['cde_fourn_filtre_agence']))			$_SESSION['cde_fourn_filtre_agence']	    = LOGINOR_AGENCE;


if (isset($_POST['filtre_date_inf']))	$_SESSION['cde_fourn_filtre_date_inf']			= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))	$_SESSION['cde_fourn_filtre_date_sup']			= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_fournisseur']))$_SESSION['cde_fourn_filtre_fournisseur']		= $_POST['filtre_fournisseur'];
if (isset($_POST['filtre_vendeur']))	$_SESSION['cde_fourn_filtre_vendeur']			= $_POST['filtre_vendeur'];
if (isset($_POST['filtre_numero']))		$_SESSION['cde_fourn_filtre_numero']			= $_POST['filtre_numero'];
if (isset($_POST['filtre_montant']))	$_SESSION['cde_fourn_filtre_montant']			= $_POST['filtre_montant'];
if (isset($_POST['filtre_signe_montant']))	$_SESSION['cde_fourn_filtre_signe_montant'] = $_POST['filtre_signe_montant'];
if (isset($_GET['filtre_classement']))	$_SESSION['cde_fourn_filtre_classement']		= $_GET['filtre_classement'];
if (isset($_POST['filtre_article']))	$_SESSION['cde_fourn_filtre_article']			= $_POST['filtre_article'];
if (isset($_POST['filtre_type_cde']))	$_SESSION['cde_fourn_filtre_type_cde']			= $_POST['filtre_type_cde'];
if (isset($_POST['filtre_agence']))		$_SESSION['cde_fourn_filtre_agence']			= $_POST['filtre_agence'];



// ACTION A FAIRE
if(isset($_GET['action']) && $_GET['action']=='delete_relance' && isset($_GET['id']) && $_GET['id']) { // mode delete relance
	mysql_query("DELETE FROM commande_rubis_relance WHERE id=$_GET[id]") or die("Ne peux pas supprimer la relance ".mysql_error());
	$message = "La relance a été correctement supprimée";
}


if(isset($_POST['action']) && $_POST['action']=='saisie_relance' && isset($_POST['CFBON']) && $_POST['CFBON']) { // mode saisie de relance client
	$date = implode('-',array_reverse(explode('/',$_POST['relance_date']))).' '.$_POST['relance_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO commande_rubis_relance (CFBON,`date`,representant,`type`,humeur,commentaire) VALUES ('$_POST[CFBON]','$date','$_POST[relance_representant]','$_POST[relance_type]',$_POST[relance_humeur],'".mysql_escape_string($_POST['relance_commentaire'])."')") or die("Ne peux pas enregistrer la relance fournisseur ".mysql_error());
	$message = "La relance client du bon n° $_POST[CFBON] a été enregistrée";
}

?>
<html>
<head>
<title>Historique des commandes fournisseurs</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-commande th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-commande { border-collapse:collapse; }

table#historique-commande td { border:solid 1px grey; padding:3px;font-size:0.8em;}

table#historique-commande th.<?=e(0,explode(' ',$_SESSION['cde_fourn_filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-commande th.<?=e(0,explode(' ',$_SESSION['cde_fourn_filtre_classement']))?>,  table#historique-commande td.<?=e(0,explode(' ',$_SESSION['cde_fourn_filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-commande td.<?=e(0,explode(' ',$_SESSION['cde_fourn_filtre_classement']))?> {
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

	document.historique_commande.CFBON.value = numero ;
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
		document.location.href = 'historique_commande.php?action=delete_relance&id=' + id ;
}

function liste_relance(id) {
	var el = $('#relance_commande_' + id);
	el.css('display', el.css('display') == 'table-row' ? 'none' : 'table-row') ;
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

	$('tr[id^=relance_commande_]').css('display',what); // afficher ou cacher les lignes
}

function envoi_formulaire(l_action) {
	document.historique_commande.action.value = l_action ;
	document.historique_commande.submit();
	return true;
}


//-->
</SCRIPT>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_commande" action="historique_commande.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="CFBON" value="">


<!-- boite de dialogue pour la relance client -->
<div id="relance">
<table style="border:solid 2px grey;">
	<caption style="font-weight:bold;">Saisie des relances fournisseur</caption>
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
		<td colspan="4" align="center"><input type="button" class="button valider" onclick="envoi_formulaire('saisie_relance');" value="Enregistrer"> <input type="button"  class="button annuler" onclick="$('#relance').hide();" value="Annuler"></td>
	</tr>
</table>
</div>




<!-- TABLEAU AVEC LES CDE ET LE MOTEUR DE RECHERCHE -->
<table id="historique-commande" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des commandes fournisseur <input type="checkbox" name="debug"<?=DEBUG?' checked':''?> class="hide_when_print"/>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
		<span class="agence">Agence</span>		
		<select name="filtre_agence">
			<option value=""<?= $_SESSION['cde_fourn_filtre_agence']=='' ? ' selected':''?>>Toutes agences</option>	
<?			foreach ($AGENCES as $code_agence => $info_agence) { ?>
				<option value="<?=$code_agence?>"<?= $_SESSION['cde_fourn_filtre_agence']==$code_agence ? ' selected':''?>><?=$info_agence[0]?></option>
<?			} ?>
		</select>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['cde_fourn_filtre_date_inf']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_inf" style="vertical-align:middle;cursor: pointer;" title="Date selector" class="hide_when_print" />
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_commande.filtre_date_inf.value='';" class="hide_when_print" />
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['cde_fourn_filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;">Fournisseur</td>
				<td><input type="text" name="filtre_fournisseur" value="<?=$_SESSION['cde_fourn_filtre_fournisseur']?>" size="8"></td>
				<td style="padding-left:2em;">Montant
					<select name="filtre_signe_montant">
						<option value=">="<?=$_SESSION['cde_fourn_filtre_signe_montant']=='>=' ? ' selected':''?>>supérieur à</option>
						<option value="<="<?=$_SESSION['cde_fourn_filtre_signe_montant']=='<=' ? ' selected':''?>>inférieur à</option>
					</select></td>
				<td><input type="text" name="filtre_montant" value="<?=$_SESSION['cde_fourn_filtre_montant'] ? $_SESSION['cde_fourn_filtre_montant']:'0' ?>" size="3">&euro;</td>

				<td style="text-align:right;"><input type="submit" class="button divers hide_when_print" style="background-image:url(/intranet/gfx/magnify.png);" value="Filtrer"/></td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['cde_fourn_filtre_date_sup']?>" size="8">
					<img src="../js/jscalendar/calendar.gif" id="trigger_sup" style="vertical-align:middle;cursor: pointer;" title="Date selector" class="hide_when_print" />
					<img src="/intranet/gfx/delete_micro.gif" style="vertical-align:middle;" onclick="document.historique_commande.filtre_date_sup.value='';" class="hide_when_print" />
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['cde_fourn_filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="text-align:right;">Acheteur</td>
				<td>
					<select name="filtre_vendeur">
							<option value=""<?=$_SESSION['cde_fourn_filtre_vendeur']==''?' selected':''?>>TOUS</option>
<?						while (list($key, $val) = each($vendeurs)) { ?>
							<option value="<?=$key?>" <?=strrpos($key,',') === false ? '':'style="font-weight:bold;background-color:grey;color:white;"' ?> <?=$_SESSION['cde_fourn_filtre_vendeur']==$key ? ' selected':''?>><?=$val?></option>
<?						} ?>
					</select>
				</td>
				<td style="text-align:right;">N° Cde <input type="text" name="filtre_numero" value="<?=$_SESSION['cde_fourn_filtre_numero']?>" size="8"></td>
				<td>Code article <input type="text" name="filtre_article" value="<?=$_SESSION['cde_fourn_filtre_article']?>" size="8"></td>
				<td>
					<select name="filtre_type_cde">
						<option value=""<?=$_SESSION['cde_fourn_filtre_type_cde']==''									?' selected':''?>>Tous types de cde</option>
						<option value="cde_en_cours"<?=$_SESSION['cde_fourn_filtre_type_cde']=='cde_en_cours'			?' selected':''?>>Cde en cours</option>
					</select>
				</td>
			</tr>
		</table>

	</caption>
	<tr>
		<th class="CFBON">N°<br><a href="historique_commande.php?filtre_classement=CFBON ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=CFBON DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="DATE">Date<br><a href="historique_commande.php?filtre_classement=DATE ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=DATE DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="DATE_CONFIRMATION">Confirmée<br><a href="historique_commande.php?filtre_classement=DATE_CONFIRMATION ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=DATE_CONFIRMATION DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="CFSER">Achat<br><a href="historique_commande.php?filtre_classement=CFSER ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=CFSER DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="NOFOU">Fournisseur<br><a href="historique_commande.php?filtre_classement=NOFOU ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=NOFOU DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="CFAGE">Agence<br><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=CFAGE ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="<?=$_SERVER['PHP_SELF']?>?filtre_classement=CFAGE DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="CUMLI">Nb ligne<br><a href="historique_commande.php?filtre_classement=CUMLI ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=CUMLI DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th class="CFMON">Mt HT Cde<br><a href="historique_commande.php?filtre_classement=CFMON ASC"><img src="/intranet/gfx/asc.png" class="hide_when_print"></a><a href="historique_commande.php?filtre_classement=CFMON DESC"><img src="/intranet/gfx/desc.png" class="hide_when_print"></a></th>
		<th>Relances<br><input name="button_affiche_relance" type="button" class="button divers hide_when_print" style="background-image:url(/intranet/gfx/comments.png);" value="Afficher" onclick="liste_toute_relance();"></th>
		<th class="hide_when_print">PDF</th>
	</tr>
<?
	$where  = array() ;
	$tables = array("${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 CDE_ENTETE",
					"${LOGINOR_PREFIX_BASE}GESTCOM.AGENCEP1 AGENCE");

	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['cde_fourn_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['cde_fourn_filtre_date_sup'])));
	
	if ($_SESSION['cde_fourn_filtre_date_inf'] && $_SESSION['cde_fourn_filtre_date_inf'] != 'Aucune') $where[] = "CONCAT(CFEDS,CONCAT(CFEDA,CONCAT('-',CONCAT(CFEDM,CONCAT('-',CFEDJ))))) >= '$date_inf_formater'" ;
	if ($_SESSION['cde_fourn_filtre_date_sup'] && $_SESSION['cde_fourn_filtre_date_sup'] != 'Aucune') $where[] = "CONCAT(CFEDS,CONCAT(CFEDA,CONCAT('-',CONCAT(CFEDM,CONCAT('-',CFEDJ))))) <= '$date_sup_formater'" ;
	if ($_SESSION['cde_fourn_filtre_fournisseur'])	$where[] = "FNOMF like '%".strtoupper(mysql_escape_string($_SESSION['cde_fourn_filtre_fournisseur']))."%'" ;
	if ($_SESSION['cde_fourn_filtre_vendeur'])	{
		$tmp = explode(',',$_SESSION['cde_fourn_filtre_vendeur']);
		for($i=0 ; $i<sizeof($tmp) ; $i++)
			$tmp[$i] = "CFSER='".strtoupper(mysql_escape_string($tmp[$i]))."'" ;
		$where[] = "(".join(' or ',$tmp).")";
	}
	if ($_SESSION['cde_fourn_filtre_numero'])		$where[] = "CDE_ENTETE.CFBON like '".strtoupper(trim(mysql_escape_string($_SESSION['cde_fourn_filtre_numero'])))."%'" ;

	$where[] = "CFMON $_SESSION[cde_fourn_filtre_signe_montant] $_SESSION[cde_fourn_filtre_montant]" ;
	$where[] = 'CUMLI > 0' ;
	$where[] = "CFEET = ''" ; // commande non annulée
	$where[] = "CDFE5 = 'CDE'" ; // on n'affiche pas les préco
	$where[] = "CDE_ENTETE.CFAGE=AGENCE.AGECO" ; // jointure bon<->agence

	if ($_SESSION['cde_fourn_filtre_agence']) // si une agence de spécifié
		$where[] = "CFAGE = '$_SESSION[cde_fourn_filtre_agence]'" ; // uniquement pour l'agence en cours
	

	// gere les recherche sur article et type de commande
	if ($_SESSION['cde_fourn_filtre_article'] || $_SESSION['cde_fourn_filtre_type_cde']) {
		$tables[] = "${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 CDE_DETAIL"; // on rajoute la table détail
		$where[]  = "CDE_ENTETE.CFBON=CDE_DETAIL.CFBON"; // liaison naturel entre detail et entete

		// code article présent dans la cde
		if ($_SESSION['cde_fourn_filtre_article'])
			$where[]  = "CDE_DETAIL.CFART='".strtoupper(trim(mysql_escape_string($_SESSION['cde_fourn_filtre_article'])))."'";

		// reliquat ou livrées
		if ($_SESSION['cde_fourn_filtre_type_cde'])
			$where[]  = "(CDE_DETAIL.CDDE1='".($_SESSION['cde_fourn_filtre_type_cde']=='cde_en_cours'?'NON':'OUI')."' AND ". // article non receptioné
						"CDE_DETAIL.CFPRF='1' AND ". // un article et pas un commentaire
						"CDE_DETAIL.CFDET='')" ; // Une ligne non annulée
	}


	$where = $where ? $where = ' where '.join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	if		($_SESSION['cde_fourn_filtre_classement'] == 'DATE DESC')
		$ordre = 'CFEDS DESC, CFEDA DESC, CFEDM DESC, CFEDJ DESC';
	elseif	($_SESSION['cde_fourn_filtre_classement'] == 'DATE ASC')
		$ordre = 'CFEDS ASC, CFEDA ASC, CFEDM ASC, CFEDJ ASC';
	elseif	($_SESSION['cde_fourn_filtre_classement'] == 'DATE_CONFIRMATION ASC')
		$ordre = 'CFCON DESC, CFELS ASC, CFELA ASC, CFELM ASC, CFELJ ASC';
	elseif	($_SESSION['cde_fourn_filtre_classement'] == 'DATE_CONFIRMATION DESC')
		$ordre = 'CFCON DESC, CFELS DESC, CFELA DESC, CFELM DESC, CFELJ DESC';
	else
		$ordre = $_SESSION['cde_fourn_filtre_classement'];

	$tables = join(',',$tables);

	$sql = <<<EOT
select DISTINCT(CDE_ENTETE.CFBON),CFEDM,CFEDJ,CFEDS,CFEDA,CFSER,CUMLI,CFMON,FNOMF,AGELI,
CFCON as CONFIRMATION, CFELJ,CFELM,CFELS,CFELA
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
		<td class="CFBON"><?=$row['CFBON']?></td>
		<td class="DATE"><?
			$date_commande = mktime(0,0,0,$row['CFEDM'],$row['CFEDJ'],$row['CFEDS'].$row['CFEDA']) ;
			$date_formater = date('d M Y',$date_commande);
			$jour_commande = $jours_mini[date('w',$date_commande)];		
		?><?=$jour_commande?> <?=$date_formater?></td><!-- date -->
		<td class="DATE_CONFIRMATION">
<?			if ($row['CONFIRMATION']=='OUI') { // commande confirmée ?>
<?				$date_commande = mktime(0,0,0,$row['CFELM'],$row['CFELJ'],$row['CFELS'].$row['CFELA']) ;
				$date_formater = date('d M Y',$date_commande);
				$jour_commande = $jours_mini[date('w',$date_commande)];		
				?><img src="gfx/asterisk.gif" /> <?=$jour_commande?> <?=$date_formater?>
<?			} ?>
		</td><!-- date -->
		<td class="CFSER"><?=isset($vendeurs[trim($row['CFSER'])]) ? $vendeurs[trim($row['CFSER'])] : trim($row['CFSER'])?></td><!-- représentant -->
		<td class="NFOUN" style="text-align:left;"><?=$row['FNOMF']?></td><!-- fournisseur -->
		<td class="CFAGE" style="text-align:center;"><?= ucfirst(strtolower($row['AGELI'])) ?></td><!-- agence -->
		<td class="CUMLI" style="text-align:center;"><?=(int)$row['CUMLI']?></td><!-- nombre de ligne -->
		<td class="CFMON" style="text-align:right;" nowrap><?=$row['CFMON']?> &euro;</td><!-- Mt commande -->
		<td style="text-align:center;"><!-- relance -->
<?			
			$nb_relance = e('nb_relance',mysql_fetch_array(mysql_query("SELECT count(id) as nb_relance FROM commande_rubis_relance WHERE CFBON='$row[CFBON]'")));
			if ($nb_relance) { ?>
				<a class="hide_when_print" href="javascript:liste_relance('<?=$row['CFBON']?>');" style="border:none;"><img src="/intranet/gfx/list.gif" alt="Liste des relances" title="Liste des relances fournisseur" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_relance?></span>
<?			} ?>
			<br><a href="javascript:relance_commande('<?=$row['CFBON']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;"><a href="edition_pdf.php?CFBON=<?=$row['CFBON']?>"><img src="/intranet/gfx/pdf-icon.png" alt="Edition PDF" class="hide_when_print"/></a></td>
	</tr>


<?		// ON AFFICHE LES RELANCE CLIENTS POUR CE commande
		if ($nb_relance) {
			$res_relance = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,DATE_FORMAT(`date`,'%w') AS date_jour,DATE_FORMAT(`date`,'%H:%i') AS heure_formater FROM commande_rubis_relance WHERE CFBON='$row[CFBON]' ORDER BY `date` DESC") or die("Ne peux pas afficher les relances fournisseur ".mysql_error()); 
?>
			<tr style="display:none;" id="relance_commande_<?=$row['CFBON']?>">
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
		$total_montant += $row['CFMON'] ;
	} // while commande ?>

<tr>
	<td colspan="4">
		Nombre de lignes : <?=$total_ligne?>
	</td>
	<td colspan="4">
		Total des montants : <?=$total_montant?> &euro;
	</td>
</tr>

</table>
</form>
</body>
</html>
<?
odbc_close($loginor);
mysql_close($mysql);
?>