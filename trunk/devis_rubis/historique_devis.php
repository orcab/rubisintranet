<?
include('../inc/config.php');
session_start();

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$erreur   = FALSE ;
$message  = '' ;

$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}
$vendeurs['LN'] = 'Jean René';
$vendeurs['MAR'] = 'Marc';

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['devis_rubis_filtre_date_inf']))	$_SESSION['devis_rubis_filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d')-7,date('Y')));
if (!isset($_SESSION['devis_rubis_filtre_date_sup']))	$_SESSION['devis_rubis_filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['devis_rubis_filtre_client']))		$_SESSION['devis_rubis_filtre_client']		= '';
if (!isset($_SESSION['devis_rubis_filtre_artisan']))	$_SESSION['devis_rubis_filtre_artisan']		= '';
if (!isset($_SESSION['devis_rubis_filtre_vendeur']))	$_SESSION['devis_rubis_filtre_vendeur']		= e('code',mysql_fetch_array(mysql_query("SELECT UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL and ip='$_SERVER[REMOTE_ADDR]' ORDER BY prenom ASC")));
if (!isset($_SESSION['devis_rubis_filtre_numero']))		$_SESSION['devis_rubis_filtre_numero']		= '';
if (!isset($_SESSION['devis_rubis_filtre_montant']))	$_SESSION['devis_rubis_filtre_montant']		= 0;
if (!isset($_SESSION['devis_rubis_filtre_signe_montant']))	$_SESSION['devis_rubis_filtre_signe_montant'] = '>=';
if (!isset($_SESSION['devis_rubis_filtre_transfere']))	$_SESSION['devis_rubis_filtre_transfere'] = FALSE;
if (!isset($_SESSION['devis_rubis_filtre_classement'])) $_SESSION['devis_rubis_filtre_classement'] = 'NOBON DESC';
if (!isset($_SESSION['devis_rubis_filtre_article']))	$_SESSION['devis_rubis_filtre_article']		= '';

if (isset($_POST['filtre_date_inf']))	$_SESSION['devis_rubis_filtre_date_inf']	= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))	$_SESSION['devis_rubis_filtre_date_sup']	= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_client']))		$_SESSION['devis_rubis_filtre_client']		= $_POST['filtre_client'];
if (isset($_POST['filtre_artisan']))	$_SESSION['devis_rubis_filtre_artisan']		= $_POST['filtre_artisan'];
if (isset($_POST['filtre_vendeur']))	$_SESSION['devis_rubis_filtre_vendeur']		= $_POST['filtre_vendeur'];
if (isset($_POST['filtre_numero']))		$_SESSION['devis_rubis_filtre_numero']		= $_POST['filtre_numero'];
if (isset($_POST['filtre_montant']))	$_SESSION['devis_rubis_filtre_montant']		= $_POST['filtre_montant'];
if (isset($_POST['filtre_signe_montant']))	$_SESSION['devis_rubis_filtre_signe_montant'] = $_POST['filtre_signe_montant'];
$_SESSION['devis_rubis_filtre_transfere'] = isset($_POST['filtre_transfere']) ? TRUE : FALSE;
if (isset($_GET['filtre_classement']))	$_SESSION['devis_rubis_filtre_classement']  = $_GET['filtre_classement'];
if (isset($_POST['filtre_article']))	$_SESSION['devis_rubis_filtre_article']		= $_POST['filtre_article'];



// ACTION A FAIRE
if(isset($_GET['action']) && $_GET['action']=='delete_relance' && isset($_GET['id']) && $_GET['id']) { // mode delete relance
	mysql_query("DELETE FROM devis_rubis_relance WHERE id=$_GET[id]") or die("Ne peux pas supprimer la relance ".mysql_error());
	$message = "La relance a été correctement supprimée";
}


if(isset($_POST['action']) && $_POST['action']=='saisie_relance' && isset($_POST['NOBON']) && $_POST['NOBON']) { // mode saisie de relance client
	$date = implode('-',array_reverse(explode('/',$_POST['relance_date']))).' '.$_POST['relance_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO devis_rubis_relance (NOBON,`date`,representant,`type`,humeur,commentaire) VALUES ('$_POST[NOBON]','$date','$_POST[relance_representant]','$_POST[relance_type]',$_POST[relance_humeur],'".mysql_escape_string($_POST['relance_commentaire'])."')") or die("Ne peux pas enregistrer la relance client ".mysql_error());
	$message = "La relance client du bon n° $_POST[NOBON] a été enregistrée";
}

?>
<html>
<head>
<title>Historique des devis rubis</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-devis th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-devis { border-collapse:collapse; }

table#historique-devis td { border:solid 1px grey; padding:3px;font-size:0.8em;}

table#historique-devis th.<?=e(0,explode(' ',$_SESSION['devis_rubis_filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-devis th.<?=e(0,explode(' ',$_SESSION['devis_rubis_filtre_classement']))?>,  table#historique-devis td.<?=e(0,explode(' ',$_SESSION['devis_rubis_filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-devis td.<?=e(0,explode(' ',$_SESSION['devis_rubis_filtre_classement']))?> {
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

div#relance {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
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

function relance_devis(numero) {

	var maDate = new Date() ;

	document.historique_devis.NOBON.value = numero ;
	document.historique_devis.relance_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.historique_devis.relance_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#relance_numero').text(numero) ;
	$('#relance').css('top',document.body.scrollTop +100);
	$('#relance').css('left',screen.availWidth / 2 - 300);
	$('#relance').show();

	document.historique_devis.relance_commentaire.focus();
}

function delete_relance(id) {
	if (confirm("Voulez-vous vraiment supprimer cette relance ?"))
		document.location.href = 'historique_devis.php?action=delete_relance&id=' + id ;
}

function liste_relance(id) {
	document.getElementById('relance_devis_' + id).style.display = document.getElementById('relance_devis_' + id).style.display == 'table-row' ? 'none' : 'table-row' ;
}

function liste_toute_relance() {
	var tr_elements = document.getElementsByTagName('tr');
	var what = '';
	if (document.historique_devis.button_affiche_relance.value == 'Afficher') { // on doit cacher les relances
		document.historique_devis.button_affiche_relance.value = 'Cache';
		what = 'table-row';
	} else { // on doit afficher les relances
		document.historique_devis.button_affiche_relance.value = 'Afficher';
		what = 'none';
	}

	for(i=0 ; i<tr_elements.length ; i++) {
		if (tr_elements[i]['id'].match(/^relance_devis_\w+$/))
			tr_elements[i].style.display = what ;
	}
}

function cache(id) {
	$('#'+id).hide();
}

function envoi_formulaire(l_action) {
	document.historique_devis.action.value = l_action ;
	document.historique_devis.submit();
	return true;
}


//-->
</SCRIPT>
</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_devis" action="historique_devis.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="NOBON" value="">

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
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-clear.png) no-repeat left;" value="1">Content</option>
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-few-clouds.png) no-repeat left;" value="2">Mausade</option>
				<option style="padding-left:30px;height:20px;background:white url(gfx/weather-storm.png) no-repeat left;" value="3">Enervé</option>
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




<!-- TABLEAU AVEC LES DEVIS ET LE MOTEUR DE RECHERCHE -->
<table id="historique-devis" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des devis rubis <input type="checkbox" name="debug"<?=DEBUG?' checked':''?>/>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['devis_rubis_filtre_date_inf']?>" size="8">
					<button id="trigger_inf" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="gfx/delete_micro.gif" onclick="document.historique_devis.filtre_date_inf.value='';">
					<script type="text/javascript">
					  Calendar.setup(
						{
						  inputField	: 'filtre_date_inf',         // ID of the input field
						  ifFormat		: '%d/%m/%Y',    // the date format
						  button		: 'trigger_inf',       // ID of the button
						  date			: '<?=$_SESSION['devis_rubis_filtre_date_inf']?>',
						  firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;">Client</td>
				<td><input type="text" name="filtre_client" value="<?=$_SESSION['devis_rubis_filtre_client']?>" size="8"></td>
				<td style="padding-left:2em;">Vendeur</td>
				<td>
					<select name="filtre_vendeur">
							<option value=""<?=$_SESSION['devis_rubis_filtre_vendeur']==''?' selected':''?>>TOUS</option>
<?						while (list($key, $val) = each($vendeurs)) { ?>
							<option value="<?=$key?>"<?=$_SESSION['devis_rubis_filtre_vendeur']==$key ? ' selected':''?>><?=$val?></option>
<?						} ?>
					</select>
				</td>
				<td style="padding-left:2em;">Montant
					<select name="filtre_signe_montant">
						<option value=">="<?=$_SESSION['devis_rubis_filtre_signe_montant']=='>=' ? ' selected':''?>>supérieur à</option>
						<option value="<="<?=$_SESSION['devis_rubis_filtre_signe_montant']=='<=' ? ' selected':''?>>inférieur à</option>
					</select></td>
				<td>
					<input type="text" name="filtre_montant" value="<?=$_SESSION['devis_rubis_filtre_montant'] ? $_SESSION['devis_rubis_filtre_montant']:'0' ?>" size="3">&euro;
					&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
					<input type="submit" class="button divers" style="background-image:url(gfx/application_form_magnify.png);" value="Filtrer">
				</td>

			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['devis_rubis_filtre_date_sup']?>" size="8">
					<button id="trigger_sup" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="gfx/delete_micro.gif" onclick="document.historique_devis.filtre_date_sup.value='';">
					<script type="text/javascript">
						Calendar.setup(
						{
							inputField	: 'filtre_date_sup',         // ID of the input field
							ifFormat	: '%d/%m/%Y',    // the date format
							button		: 'trigger_sup',       // ID of the button
							date		: '<?=$_SESSION['devis_rubis_filtre_date_sup']?>',
							firstDay 	: 1
						}
					  );
					</script>
				</td>
				<td style="padding-left:2em;">Artisan</td>
				<td><input type="text" name="filtre_artisan" value="<?=$_SESSION['devis_rubis_filtre_artisan']?>" size="8"></td>
				<td style="padding-left:2em;">N° Devis</td>
				<td><input type="text" name="filtre_numero" value="<?=$_SESSION['devis_rubis_filtre_numero']?>" size="8"></td>
				<td style="padding-left:2em;">Devis déjà transférés <input type="checkbox" name="filtre_transfere"<?=$_SESSION['devis_rubis_filtre_transfere'] ? ' checked':''?>></td>
				<td>Code Article <input type="text" name="filtre_article" value="<?=$_SESSION['devis_rubis_filtre_article']?>" size="8"></td>
			</tr>
		</table>

	</caption>
	<tr>
		<th class="NOBON">N°<br><a href="historique_devis.php?filtre_classement=NOBON ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NOBON DESC"><img src="gfx/desc.png"></a></th>
		<th class="DATE">Date<br><a href="historique_devis.php?filtre_classement=DATE ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=DATE DESC"><img src="gfx/desc.png"></a></th>
		<th class="LIVSB">Rep<br><a href="historique_devis.php?filtre_classement=LIVSB ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=LIVSB DESC"><img src="gfx/desc.png"></a></th>
		<th class="RFCSB">Client<br><a href="historique_devis.php?filtre_classement=RFCSB ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=RFCSB DESC"><img src="gfx/desc.png"></a></th>
		<th class="NOMSB">Artisan<br><a href="historique_devis.php?filtre_classement=NOMSB ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NOMSB DESC"><img src="gfx/desc.png"></a></th>
		<th class="NBLIG">Nb ligne<br><a href="historique_devis.php?filtre_classement=NBLIG ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=NBLIG DESC"><img src="gfx/desc.png"></a></th>
		<th class="MONTBR">Mt HT Devis<br><a href="historique_devis.php?filtre_classement=MONTBR ASC"><img src="gfx/asc.png"></a><a href="historique_devis.php?filtre_classement=MONTBR DESC"><img src="gfx/desc.png"></a></th>
		<th>Relances<br><input name="button_affiche_relance" type="button" class="button divers" style="background-image:url(gfx/comments.png);" value="Afficher" onclick="liste_toute_relance();"></th>
		<th>PDF</th>
		<th>PDF</th>
	</tr>
<?
	$where = array() ;
	$tables = array("${LOGINOR_PREFIX_BASE}GESTCOM.AENTBVP1 DEVIS_ENTETE");
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['devis_rubis_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['devis_rubis_filtre_date_sup'])));
	
	if ($_SESSION['devis_rubis_filtre_date_inf'] && $_SESSION['devis_rubis_filtre_date_inf'] != 'Aucune') $where[] = "CONCAT(DSECS,CONCAT(DSECA,CONCAT('-',CONCAT(DSECM,CONCAT('-',DSECJ))))) >= '$date_inf_formater'" ;
	if ($_SESSION['devis_rubis_filtre_date_sup'] && $_SESSION['devis_rubis_filtre_date_sup'] != 'Aucune') $where[] = "CONCAT(DSECS,CONCAT(DSECA,CONCAT('-',CONCAT(DSECM,CONCAT('-',DSECJ))))) <= '$date_sup_formater'" ;
	if ($_SESSION['devis_rubis_filtre_client'])		$where[] = "RFCSB like '%".strtoupper(mysql_escape_string($_SESSION['devis_rubis_filtre_client']))."%'" ;
	if ($_SESSION['devis_rubis_filtre_artisan'])	$where[] = "NOMSB like '%".strtoupper(mysql_escape_string($_SESSION['devis_rubis_filtre_artisan']))."%'" ;
	if ($_SESSION['devis_rubis_filtre_vendeur'])	$where[] = "LIVSB='".strtoupper(mysql_escape_string($_SESSION['devis_rubis_filtre_vendeur']))."'" ;
	if ($_SESSION['devis_rubis_filtre_numero'])		$where[] = "NOBON like '".strtoupper(trim(mysql_escape_string($_SESSION['devis_rubis_filtre_numero'])))."%'" ;

	$where[] = "MONTBR $_SESSION[devis_rubis_filtre_signe_montant] $_SESSION[devis_rubis_filtre_montant]" ;
	$where[] = 'NBLIG > 0' ;
	if (!$_SESSION['devis_rubis_filtre_transfere']) $where[] = "DVTCD = 'NON'" ; // devis non passé en commande

	if ($_SESSION['devis_rubis_filtre_article']) {
		$tables[] = "${LOGINOR_PREFIX_BASE}GESTCOM.ADETBVP1 DEVIS_DETAIL"; // on rajoute la table détail
		$where[]  = "DEVIS_DETAIL.CODAR='".strtoupper(trim(mysql_escape_string($_SESSION['devis_rubis_filtre_article'])))."'";
		$where[]  = "DEVIS_ENTETE.NOBON=DEVIS_DETAIL.NOBON";
	}

	$where = $where ? $where = ' where '.join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	if		($_SESSION['devis_rubis_filtre_classement'] == 'DATE DESC')
		$ordre = "DSECS DESC, DSECA DESC, DSECM DESC, DSECJ DESC";
	elseif	($_SESSION['devis_rubis_filtre_classement'] == 'DATE ASC')
		$ordre = "DSECS ASC, DSECA ASC, DSECM ASC, DSECJ ASC";
	else
		$ordre = $_SESSION['devis_rubis_filtre_classement'];

	$tables = join(',',$tables);

	$sql = <<<EOT
select DISTINCT(DEVIS_ENTETE.NOBON),DEVIS_ENTETE.NOCLI,DSECM,DSECJ,DSECS,DSECA,LIVSB,RFCSB,BUDSB,AD1SB,AD2SB,NOMSB,NBLIG,MONTBR
from $tables
$where
order by $ordre
EOT;

if (DEBUG) echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$total_ligne = 0 ;
	$total_montant = 0 ;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql) ; 
	while($row = odbc_fetch_array($res)) {
//	$row = odbc_fetch_array($res)
?>

	<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>">
		<td class="NOBON"><?=$row['NOBON']?></td>
		<td class="DATE"><?
			$date_devis = mktime(0,0,0,$row['DSECM'],$row['DSECJ'],$row['DSECS'].$row['DSECA']) ;
			$date_formater = date('d M Y',$date_devis);
			$jour_devis = $jours_mini[date('w',$date_devis)];		
		?><?=$jour_devis?> <?=$date_formater?></td><!-- date -->
		<td class="LIVSB"><?=isset($vendeurs[trim($row['LIVSB'])]) ? $vendeurs[trim($row['LIVSB'])] : trim($row['LIVSB'])?></td><!-- représentant -->
		<td class="RFCSB"><?=$row['RFCSB']?></td><!-- chantier -->
		<td class="NOMSB"><?=$row['NOMSB']?></td><!-- artisan -->
		<td class="NBLIG" style="text-align:center;"><?=(int)$row['NBLIG']?></td><!-- nombre de ligne -->
		<td class="MONTBR" style="text-align:right;" nowrap><?=$row['MONTBR']?> &euro;</td><!-- Mt devis -->
		<td style="text-align:center;"><!-- relance -->
<?			
			$nb_relance = e('nb_relance',mysql_fetch_array(mysql_query("SELECT count(id) as nb_relance FROM devis_rubis_relance WHERE NOBON='$row[NOBON]'")));
			if ($nb_relance) { ?>
				<a class="hide_when_print" href="javascript:liste_relance('<?=$row['NOBON']?>');" style="border:none;"><img src="gfx/list.gif" alt="Liste des relances" title="Liste des relances client" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_relance?></span>
<?			} ?>
			<br><a href="javascript:relance_devis('<?=$row['NOBON']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;"><a href="edition_pdf.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>"><img src="gfx/pdf-icon_avec_prix.png" alt="Edition PDF" /></a></td>
		<td style="text-align:center;"><a href="edition_pdf.php?NOBON=<?=$row['NOBON']?>&NOCLI=<?=$row['NOCLI']?>&options[]=sans_prix"><img src="gfx/pdf-icon_sans_prix.png" alt="Edition PDF Sans prix" /></a></td>
	</tr>


<?		// ON AFFICHE LES RELANCE CLIENTS POUR CE DEVIS
		if ($nb_relance) {
			$res_relance = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,DATE_FORMAT(`date`,'%w') AS date_jour,DATE_FORMAT(`date`,'%H:%i') AS heure_formater FROM devis_rubis_relance WHERE NOBON='$row[NOBON]' ORDER BY `date` DESC") or die("Ne peux pas afficher les relances clients ".mysql_error()); 
?>
			<tr style="display:none;" id="relance_devis_<?=$row['NOBON']?>">
				<td><img src="gfx/return.jpg"></td>
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
										case 1: ?><img src="gfx/weather-clear.png"><?
											break;
										case 2: ?><img src="gfx/weather-few-clouds.png"><?
											break;
										case 3: ?><img src="gfx/weather-storm.png"><?
											break;
									}	?>									
								</td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="10%"><?=$row_relance['representant']?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="10%"><?=$row_relance['type']?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="60%"><?=stripslashes($row_relance['commentaire'])?></td>
								<td style="border:none;border-bottom:solid 1px grey;" valign="top" width="5%"><a href="javascript:delete_relance(<?=$row_relance['id']?>);"><img src="gfx/comment_delete.png"></a></td>
							</tr>
<?							} ?>
					</table>
				</td>
			</tr>
<?		} // fin affiche les relances devis

		$total_ligne++;
		$total_montant += $row['MONTBR'] ;
	} // while devis ?>

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