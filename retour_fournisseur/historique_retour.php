<?
include('../inc/config.php');
session_start();

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$erreur   = FALSE ;
$message  = '' ;

$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['filtre_date_inf']))	$_SESSION['filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d')-10,date('Y')));
if (!isset($_SESSION['filtre_date_sup']))	$_SESSION['filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['filtre_vendeur']))	$_SESSION['filtre_vendeur']		= e('code',mysql_fetch_array(mysql_query("SELECT UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL and ip='$_SERVER[REMOTE_ADDR]' ORDER BY prenom ASC")));
if (!isset($_SESSION['filtre_numero']))		$_SESSION['filtre_numero']		= '';
if (!isset($_SESSION['filtre_reception']))	$_SESSION['filtre_reception']	= '';
if (!isset($_SESSION['filtre_fact_fourn']))	$_SESSION['filtre_fact_fourn']	= '';
if (!isset($_SESSION['filtre_type']))		$_SESSION['filtre_type']		= '';
if (!isset($_SESSION['filtre_montant']))	$_SESSION['filtre_montant']		= 0;
if (!isset($_SESSION['filtre_signe_montant']))	$_SESSION['filtre_signe_montant'] = '<';
if (!isset($_SESSION['filtre_classement'])) $_SESSION['filtre_classement'] = 'HIBON DESC';

if (isset($_POST['filtre_date_inf']))	$_SESSION['filtre_date_inf']	= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))	$_SESSION['filtre_date_sup']	= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_vendeur']))	$_SESSION['filtre_vendeur']		= $_POST['filtre_vendeur'];
if (isset($_POST['filtre_numero']))		$_SESSION['filtre_numero']		= $_POST['filtre_numero'];
if (isset($_POST['filtre_reception']))	$_SESSION['filtre_reception']	= $_POST['filtre_reception'];
if (isset($_POST['filtre_fact_fourn']))	$_SESSION['filtre_fact_fourn']	= $_POST['filtre_fact_fourn'];
if (isset($_POST['filtre_type']))		$_SESSION['filtre_type']		= $_POST['filtre_type'];
if (isset($_POST['filtre_montant']))	$_SESSION['filtre_montant']		= $_POST['filtre_montant'];
if (isset($_POST['filtre_signe_montant']))	$_SESSION['filtre_signe_montant'] = $_POST['filtre_signe_montant'];
if (isset($_GET['filtre_classement']))	$_SESSION['filtre_classement']  = $_GET['filtre_classement'];



// ACTION A FAIRE
if(isset($_GET['action']) && $_GET['action']=='delete_relance' && isset($_GET['id']) && $_GET['id']) { // mode delete relance
	mysql_query("DELETE FROM retour_rubis_relance WHERE id=$_GET[id]") or die("Ne peux pas supprimer la relance ".mysql_error());
	$message = "La relance a été correctement supprimée";
}


if(isset($_POST['action']) && $_POST['action']=='saisie_relance' && isset($_POST['HIBON']) && $_POST['HIBON']) { // mode saisie de relance
	$date = implode('-',array_reverse(explode('/',$_POST['relance_date']))).' '.$_POST['relance_heure'].':00'; //2007-09-10 14:16:59;
	$res = mysql_query("INSERT INTO retour_rubis_relance (HIBON,`date`,representant,`type`,humeur,commentaire) VALUES ('$_POST[HIBON]','$date','$_POST[relance_representant]','$_POST[relance_type]',$_POST[relance_humeur],'".mysql_escape_string($_POST['relance_commentaire'])."')") or die("Ne peux pas enregistrer la relance fournisseur ".mysql_error());
	$message = "La relance fournisseur du bon n° $_POST[HIBON] a été enregistrée";
}

?>
<html>
<head>
<title>Historique des retours fournisseurs</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-retour th { border:solid 1px grey; background:#DDD;font-size:0.8em; }

table#historique-retour { border-collapse:collapse; }

table#historique-retour td { border:solid 1px grey; padding:3px;font-size:0.8em;}

table#historique-retour th.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
	border-top:solid 2px black;
}

table#historique-retour th.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?>,  table#historique-retour td.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#historique-retour td.<?=e(0,explode(' ',$_SESSION['filtre_classement']))?> {
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

function relance_retour(numero) {

	var maDate = new Date() ;

	document.historique_retour.HIBON.value = numero ;
	document.historique_retour.relance_date.value  = maDate.getDate() + '/' + (maDate.getMonth() + 1) + '/' + maDate.getFullYear();
	document.historique_retour.relance_heure.value = maDate.getHours() + ':' + maDate.getMinutes() ;

	$('#relance_numero').text(numero) ;
	$('#relance').css('top',document.body.scrollTop +100);
	$('#relance').css('left',screen.availWidth / 2 - 300);
	$('#relance').show();

	document.historique_retour.relance_commentaire.focus();
}

function delete_relance(id) {
	if (confirm("Voulez-vous vraiment supprimer cette relance ?"))
		document.location.href = 'historique_retour.php?action=delete_relance&id=' + id ;
}

function liste_relance(id) {
	document.getElementById('relance_retour_' + id).style.display = document.getElementById('relance_retour_' + id).style.display == 'table-row' ? 'none' : 'table-row' ;
}

function liste_toute_relance() {
	var tr_elements = document.getElementsByTagName('tr');
	var what = '';
	if (document.historique_retour.button_affiche_relance.value == 'Afficher') { // on doit cacher les relances
		document.historique_retour.button_affiche_relance.value = 'Cacher';
		what = 'table-row';
	} else { // on doit afficher les relances
		document.historique_retour.button_affiche_relance.value = 'Afficher';
		what = 'none';
	}

	for(i=0 ; i<tr_elements.length ; i++) {
		if (tr_elements[i]['id'].match(/^relance_retour_\w+$/))
			tr_elements[i].style.display = what ;
	}
}

function cache(id) {
	$('#'+id).hide();
}

function envoi_formulaire(l_action) {
	document.historique_retour.action.value = l_action ;
	document.historique_retour.submit();
	return true;
}


//-->
</SCRIPT>
</head>
<body>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_retour" action="historique_retour.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="HIBON" value="">


<!-- boite de dialogue pour la relance fournisseur -->
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
				<option value="email">Email</option>
				<option value="visite">Visite</option>
				<option value="courrier">Courrier</option>
			</select>
		</td>
		<td>Représentant</td>
		<td>
			<select name="relance_representant">
<?			$res  = mysql_query("SELECT * FROM employe WHERE printer = 0 ORDER BY prenom ASC");
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
<table id="historique-retour" style="width:100%;border:solid 1px black;">
	<caption style="padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		Historique des retours fournisseur <a style="font-size:0.8em" href="/intranet/index.php">(Retour Intranet)</a>
		<div style="color:red;"><?= $message ? $message : ''?></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date cde de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['filtre_date_inf']?>" size="8">
					<button id="trigger_inf" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.historique_retour.filtre_date_inf.value='';">
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
				<td style="padding-left:2em;">Montant
					<select name="filtre_signe_montant">
						<option value=">="<?=$_SESSION['filtre_signe_montant']=='>=' ? ' selected':''?>>supérieur à</option>
						<option selected value="<="<?=$_SESSION['filtre_signe_montant']=='<=' ? ' selected':''?>>inférieur à</option>
					</select></td>
				<td><input type="text" name="filtre_montant" value="<?=$_SESSION['filtre_montant'] ? $_SESSION['filtre_montant']:'0' ?>" size="3">&euro;</td>
				<td><input type="submit" class="button divers" style="background-image:url(/intranet/gfx/magnify.png);" value="Filtrer"></td>
			</tr>
			<tr>
				<td>Date cde de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['filtre_date_sup']?>" size="8">
					<button id="trigger_sup" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.historique_retour.filtre_date_sup.value='';">
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
				<td style="text-align:center;">Acheteur
					<select name="filtre_vendeur">
						<option value=""<?=$_SESSION['filtre_vendeur']==''?' selected':''?>>TOUS</option>
						<option value="P1"<?=$_SESSION['filtre_vendeur']=='P1'?' selected':''?>>P1</option>
						<option value="P2"<?=$_SESSION['filtre_vendeur']=='P2'?' selected':''?>>P2</option>
						<option value="P3"<?=$_SESSION['filtre_vendeur']=='P3'?' selected':''?>>P3</option>
						<option value="ADM"<?=$_SESSION['filtre_vendeur']=='ADM'?' selected':''?>>ADM</option>
					</select>
				</td>
				<td style="text-align:right;">N° Cde <input type="text" name="filtre_numero" value="<?=$_SESSION['filtre_numero']?>" size="7"></td>
				
				<td style="text-align:right;">Type cde <input type="text" name="filtre_type" value="<?=$_SESSION['filtre_type']?>" size="3" maxlength="3"></td>
				<td></td>
				<td style="text-align:right;">Réceptionné <input type="text" name="filtre_reception" value="<?=$_SESSION['filtre_reception']?>" size="3" maxlength="3"></td>	
				
				<td></td>
				<td style="padding-left:2em;">Facture fournisseur
					<select name="filtre_fact_fourn">
						<option value="tous"<?=$_SESSION['filtre_fact_fourn']=='tous' ? ' selected':''?>>Tous</option>					
						<option value="existant"<?=$_SESSION['filtre_fact_fourn']=='existant' ? ' selected':''?>>Existant</option>
					</select>
				</td>
			</tr>
		</table>

	</caption>
	<tr>
		<th class="HIBON">N°<br><a href="historique_retour.php?filtre_classement=HIBON ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HIBON DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="HIZA2">Type cde<br><a href="historique_retour.php?filtre_classement=HIZA2 ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HIZA2 DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="DATE">Date du bon<br><a href="historique_retour.php?filtre_classement=DATE ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=DATE DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="HISER">Achat<br><a href="historique_retour.php?filtre_classement=HISER ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HISER DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="NOMFO">Fournisseur<br></th>
		<th class="NOART">Code article<br><a href="historique_retour.php?filtre_classement=NOART ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=NOART DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="HISDE">Désignation<br><a href="historique_retour.php?filtre_classement=HISDE ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HISDE DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="HIQTE">Quantité<br><a href="historique_retour.php?filtre_classement=HIQTE ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HIQTE DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="HIMTH">Montant HT<br><a href="historique_retour.php?filtre_classement=HIMTH ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=HIMTH DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="TYPHI">Receptionnée<br><a href="historique_retour.php?filtre_classement=TYPHI ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=TYPHI DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="CFFNU">N° de factures fourn<br><a href="historique_retour.php?filtre_classement=CFFNU ASC"><img src="/intranet/gfx/asc.png"></a><a href="historique_retour.php?filtre_classement=CFFNU DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th>Relances<br><input name="button_affiche_relance" type="button" class="button divers" style="background-image:url(/intranet/gfx/comments.png);" value="Afficher" onclick="liste_toute_relance();"></th>
		<th>PDF (ne marche que si cde existante)</th>
	</tr>
<?
	$where = array() ;
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['filtre_date_sup'])));
	
	if ($_SESSION['filtre_date_inf'] && $_SESSION['filtre_date_inf'] != 'Aucune') $where[] = "CONCAT(HISCS,CONCAT(HISCA,CONCAT('-',CONCAT(HISCM,CONCAT('-',HISCJ))))) >= '$date_inf_formater'" ;
	if ($_SESSION['filtre_date_sup'] && $_SESSION['filtre_date_sup'] != 'Aucune') $where[] = "CONCAT(HISCS,CONCAT(HISCA,CONCAT('-',CONCAT(HISCM,CONCAT('-',HISCJ))))) <= '$date_sup_formater'" ;
	if ($_SESSION['filtre_vendeur'])	$where[] = "HISER='".strtoupper($_SESSION['filtre_vendeur'])."'" ;
	if ($_SESSION['filtre_numero'])		$where[] = "HIBON like '".strtoupper(trim($_SESSION['filtre_numero']))."%'" ;
	if ($_SESSION['filtre_reception'])	$where[] = "TYPHI='".strtoupper($_SESSION['filtre_reception'])."'" ;
	
	if ($_SESSION['filtre_fact_fourn']=='vide')	
		$where[] = "CFFNU = false" ;
	else if	($_SESSION['filtre_fact_fourn']=='existant')
		$where[] = "CFFNU <> ''" ;
	
	$where[] = "t1.HIMTH $_SESSION[filtre_signe_montant] $_SESSION[filtre_montant]" ;
	
	$where = $where ? $where = join(' and ',$where) : '';

//print_r($_SESSION);
//print_r($_GET);

	if		($_SESSION['filtre_classement'] == 'DATE DESC')
		$ordre = 'HISCS DESC, HISCA DESC, HISCM DESC, HISCJ DESC';
	elseif	($_SESSION['filtre_classement'] == 'DATE ASC')
		$ordre = 'HISCS ASC, HISCA ASC, HISCM ASC, HISCJ ASC';
	elseif		($_SESSION['filtre_classement'] == 'DATE_LIV DESC')
		$ordre = 'CFDLS DESC, CFDLA DESC, CFDLM DESC, CFDLJ DESC';
	elseif	($_SESSION['filtre_classement'] == 'DATE_LIV ASC')
		$ordre = 'CFDLS ASC, CFDLA ASC, CFDLM ASC, CFDLJ ASC';
	else
		$ordre = $_SESSION['filtre_classement'];

	
	$sql = <<<EOT
select DISTINCT t1.HIBON,t1.NOART,HISER,HISDE,t1.HIQTE,t1.HIMTH,TYPHI,HIZA2,HISCS,HISCA,HISCM,HISCJ, t2.CFFNU, NOMFO
from ${LOGINOR_PREFIX_BASE}GESTCOM.AHISTOP1 t1 LEFT JOIN ${LOGINOR_PREFIX_BASE}GESTCOM.ACFADEP1 t2 ON t1.HIBON=t2.HIBON, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1
WHERE HISET<>'ANN' and (TYPHI='ENT' or TYPHI='CDF') and (t1.HIQTE<0 or t1.HIMTH<0)
	and t1.NCLFO=${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1.NOFOU
	and $where
order by $ordre
EOT;


//echo "<div style='color:red;'>$sql</div>" ;

	$total_ligne = 0 ;
	$total_montant = 0 ;

	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : $sql");
	while($row = odbc_fetch_array($res)) {
//	$row = odbc_fetch_array($res)


	if ($row['TYPHI'] == 'ENT')
		$color="red";
	else 
		$color="black";
?>	
	<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>; color:<? echo "$color"; ?>">
		<td class="HIBON" style="text-align:center;"><?=$row['HIBON']?></td>
		<?$bonf=$row['HIBON']?>
		<td class="HIZA2" style="text-align:center;"><?=$row['HIZA2']?></td>
		<td class="DATE" style="text-align:center;"><?
			$date_commande = mktime(0,0,0,$row['HISCM'],$row['HISCJ'],$row['HISCS'].$row['HISCA']) ;
			$date_formater = date('d M Y',$date_commande);
			$jour_commande = $jours_mini[date('w',$date_commande)];		
		?><?=$jour_commande?> <?=$date_formater?></td><!-- date -->
		<td class="HISER"><?=isset($vendeurs[trim($row['HISER'])]) ? $vendeurs[trim($row['HISER'])] : trim($row['HISER'])?></td><!-- représentant -->		
		<td class="NOMFO" style="text-align:center;"><?=$row['NOMFO']?></td>
		<td class="NOART" style="text-align:center;"><?=$row['NOART']?></td>
		<td class="HISDE" style="text-align:center;"><?=$row['HISDE']?></td>
		<td class="HIQTE" style="text-align:center;"><?=$row['HIQTE']?></td>
		<td class="HIMTH" style="text-align:center;"><?=$row['HIMTH']?></td>
		<td class="TYPHI" style="text-align:center;"><?=$row['TYPHI']?></td>
		<td class="CFFNU" style="text-align:center;"><?=$row['CFFNU']?></td>
		
		<td style="text-align:center;"><!-- relance -->
		<?			
			$nb_relance = e('nb_relance',mysql_fetch_array(mysql_query("SELECT count(id) as nb_relance FROM retour_rubis_relance WHERE HIBON='$row[HIBON]'")));
			if ($nb_relance) { ?>
				<a class="hide_when_print" href="javascript:liste_relance('<?=$row['HIBON']?>');" style="border:none;"><img src="/intranet/gfx/list.gif" alt="Liste des relances" title="Liste des relances fournisseur" align="top"></a><span style="font-size:1.2em;color:green;font-weight:bold;"><?=$nb_relance?></span>
<?			} ?>
			<br><a href="javascript:relance_retour('<?=$row['HIBON']?>');" style="border:none;color:black;" class="hide_when_print">Ajouter</a>
		</td>
		<td style="text-align:center;"><a href="edition_pdf.php?HIBON=<?=$row['HIBON']?>" target="_blank"><img src="/intranet/gfx/pdf-icon.png" alt="Edition PDF" /></a></td>
	</tr>

<?		// ON AFFICHE LES RELANCE CLIENTS POUR CE commande
		if ($nb_relance) {
			$res_relance = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d %b %Y') AS date_formater,DATE_FORMAT(`date`,'%w') AS date_jour,DATE_FORMAT(`date`,'%H:%i') AS heure_formater FROM retour_rubis_relance WHERE HIBON='$row[HIBON]' ORDER BY `date` DESC") or die("Ne peux pas afficher les relances fournisseur ".mysql_error()); 
?>
			<tr style="display:none;" id="relance_retour_<?=$row['HIBON']?>">
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
		$total_montant += $row['HIMTH'] ;

	} // while commande ?>

<tr>
	<td colspan="4">
		<B>Nombre de lignes :</B> <?=$total_ligne?>
	</td>
	<td colspan="4">
		<B>Total des montants :</B> <?=$total_montant?> &euro;
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