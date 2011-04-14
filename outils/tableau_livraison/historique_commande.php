<?
include('../../inc/config.php');
require_once('etat.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
$artisans = array();
$res = mysql_query("SELECT numero,nom FROM artisan") or die("Ne peux pas supprimer la relance ".mysql_error());
while($row = mysql_fetch_array($res)) {
	$artisans[$row['numero']] = $row['nom'];
}


session_start();

//print_r($_SESSION);
//print_r($_GET);

define('DEBUG',isset($_POST['debug'])?TRUE:FALSE);

$pageno = isset($_GET['pageno']) ? $_GET['pageno'] : 1;

if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
try {
	$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
	$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
} catch (PDOException $exception) {
	echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
	die ($exception->getMessage());
}

// GESTION DU CLASSEMENT ET DES FILTRES DE RECHERCHE
if (!isset($_SESSION['cde_adh_filtre_date_inf']))		$_SESSION['cde_adh_filtre_date_inf']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m')-1,date('d'),date('Y')));
if (!isset($_SESSION['cde_adh_filtre_date_sup']))		$_SESSION['cde_adh_filtre_date_sup']	= $date_inf = date('d/m/Y' , mktime(0,0,0,date('m'),date('d'),date('Y')));
if (!isset($_SESSION['cde_adh_filtre_reference']))		$_SESSION['cde_adh_filtre_reference']	= '';
if (!isset($_SESSION['cde_adh_filtre_vendeur']))		$_SESSION['cde_adh_filtre_vendeur']		= '';
if (!isset($_SESSION['cde_adh_filtre_numero']))			$_SESSION['cde_adh_filtre_numero']		= '';
if (!isset($_SESSION['cde_adh_filtre_montant']))		$_SESSION['cde_adh_filtre_montant']		= 0;
if (!isset($_SESSION['cde_adh_filtre_signe_montant']))	$_SESSION['cde_adh_filtre_signe_montant'] = '>=';
if (!isset($_SESSION['cde_adh_filtre_classement']))		$_SESSION['cde_adh_filtre_classement']	= 'date_bon DESC, numero_bon DESC';
if (!isset($_SESSION['cde_adh_filtre_article']))		$_SESSION['cde_adh_filtre_article']		= '';
if (!isset($_SESSION['cde_adh_filtre_pagesize']))		$_SESSION['cde_adh_filtre_pagesize']	= 10;
if (!isset($_SESSION['cde_adh_filtre_reliquat']))		$_SESSION['cde_adh_filtre_reliquat']	= '';
if (!isset($_SESSION['cde_adh_filtre_artisan']))		$_SESSION['cde_adh_filtre_artisan']		= '';

if (isset($_POST['filtre_date_inf']))		$_SESSION['cde_adh_filtre_date_inf']		= $_POST['filtre_date_inf'];
if (isset($_POST['filtre_date_sup']))		$_SESSION['cde_adh_filtre_date_sup']		= $_POST['filtre_date_sup'];
if (isset($_POST['filtre_reference']))		$_SESSION['cde_adh_filtre_reference']		= $_POST['filtre_reference'];
if (isset($_POST['filtre_vendeur']))		$_SESSION['cde_adh_filtre_vendeur']			= $_POST['filtre_vendeur'];
if (isset($_POST['filtre_numero']))			$_SESSION['cde_adh_filtre_numero']			= $_POST['filtre_numero'];
if (isset($_POST['filtre_montant']))		$_SESSION['cde_adh_filtre_montant']			= $_POST['filtre_montant'];
if (isset($_POST['filtre_signe_montant']))	$_SESSION['cde_adh_filtre_signe_montant']	= $_POST['filtre_signe_montant'];
if (isset($_GET['filtre_classement']))		$_SESSION['cde_adh_filtre_classement']		= $_GET['filtre_classement'];
if (isset($_POST['filtre_article']))		$_SESSION['cde_adh_filtre_article']			= $_POST['filtre_article'];
if (isset($_POST['filtre_pagesize']))		$_SESSION['cde_adh_filtre_pagesize']		= $_POST['filtre_pagesize'];
if (isset($_POST['filtre_reliquat']))		$_SESSION['cde_adh_filtre_reliquat']		= $_POST['filtre_reliquat'];
if (isset($_POST['filtre_artisan']))		$_SESSION['cde_adh_filtre_artisan']			= $_POST['filtre_artisan'];

?>
<html>
<head>
<title>Historique des commandes adhérent</title>
<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#historique-commande th { border:solid 1px grey; background:#DDD;font-size:0.8em;white-space: nowrap; }

table#historique-commande { border-collapse:collapse; }

table#historique-commande td { border:solid 1px grey; padding:3px;font-size:0.8em;white-space: nowrap; }

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

div#relance {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:10px;
	background:white;
	display:none;
	position:absolute;
}

div.pagination {
	margin-top:5px;
	text-align:center;
	font-size:0.9em;
	color:#FF3B00;
}

span.nombre {
	font-weight:bold;
}

div.pagination select {
	font-size:0.8em;
	color:red;
}

div.pagination a {
	color:#4078E0;
	text-decoration:none;
	font-size:0.8em;
	font-weight:bold;
}

.info, .pdf, .xls {
	width:32px;
	text-align:center;
}

td.info {
	cursor:pointer;
}

span.groupe_vendeur {
	color:gray;
	font-size:0.8em;
	text-transform:lowercase;
}

option.suspendu {
	color:gray;
	font-style:italic;
	font-size:0.9em;
}

th.numero_bon,th.nb_ligne,th.nb_livre,th.nb_dispo { width:5em; }

th.date_bon, th.date_liv,th.montant { width:10em; }
td.numero_bon,td.date_bon, td.date_liv { text-align:center; }

span.montant_dispo {
	color:grey;
	font-size:0.8em;
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
<SCRIPT LANGUAGE="JavaScript" SRC="../../js/jquery.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript" SRC="../../js/data_dumper.js"></SCRIPT>
<script type="text/javascript" src="../../js/tiny_mce/tiny_mce.js"></script>
<script type="text/javascript">
	tinyMCE.init({
		mode : "textareas",
		theme : "simple"
	});
</script>
<SCRIPT LANGUAGE="JavaScript">
<!--

function change_page(select_obj) {
	document.location.href='historique_commande.php?pageno=' + select_obj[select_obj.selectedIndex].value;
}


function envoi_formulaire(l_action) {
	document.historique_commande.action.value = l_action ;
	document.historique_commande.submit();
	return true;
}

function telecharger_excel(sql) {
	document.location.href='historique_commande_excel.php?sql='+sql;
}

//-->
</SCRIPT>
</head>
<body>

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="historique_commande" action="historique_commande.php" method="POST">
<input type="hidden" name="action" value="">
<input type="hidden" name="NOBON" value="">


<!-- TABLEAU AVEC LES CDE ET LE MOTEUR DE RECHERCHE -->
<table id="historique-commande" style="width:100%;">
	<caption style="text-align:left;padding:3px;margin-bottom:15px;border:solid 2px black;font-weight:bold;font-size:1.2em;background:#DDD;">
		<div style="text-align:center;">Historique des commandes<input type="checkbox" name="debug"<?=DEBUG?' checked':''?>/></div>

		<!-- choix pour les recherches -->
		<table id="recherche">
			<tr>
				<td>Date de départ</td>
				<td>
					<input type="text" id="filtre_date_inf" name="filtre_date_inf" value="<?=$_SESSION['cde_adh_filtre_date_inf']?>" size="8">
					<img src="../../js/jscalendar/calendar.gif" id="trigger_inf" style="vertical-align:middle;cursor:pointer;" title="Date selector" />
					<img src="gfx/delete_micro.gif" onclick="document.historique_commande.filtre_date_inf.value='';">
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
				<td style="padding-left:1em;text-align:right;">Référence cde <input type="text" name="filtre_reference" value="<?=$_SESSION['cde_adh_filtre_reference']?>" size="8"></td>
				<td style="text-align:right;padding-left:1em;">N° Cde <input type="text" name="filtre_numero" value="<?=$_SESSION['cde_adh_filtre_numero']?>" size="8"></td>
				<td style="padding-left:15px;">
					Code artisan&nbsp;<input type="text" name="filtre_artisan" size="6" value="<?=$_SESSION['cde_adh_filtre_artisan']?>"/>	
				</td>
				<td style="padding-left:1em;">Montant
					<select name="filtre_signe_montant">
						<option value=">="<?=$_SESSION['cde_adh_filtre_signe_montant']=='>=' ? ' selected':''?>>supérieur à</option>
						<option value="<="<?=$_SESSION['cde_adh_filtre_signe_montant']=='<=' ? ' selected':''?>>inférieur à</option>
					</select>
				</td>
				<td><input type="text" name="filtre_montant" value="<?=$_SESSION['cde_adh_filtre_montant'] ? $_SESSION['cde_adh_filtre_montant']:'0' ?>" size="3">&euro;</td>
			</tr>
			<tr>
				<td>Date de fin</td>
				<td>
					<input type="text" id="filtre_date_sup" name="filtre_date_sup" value="<?=$_SESSION['cde_adh_filtre_date_sup']?>" size="8">
					<img src="../../js/jscalendar/calendar.gif" id="trigger_sup" style="vertical-align:middle;cursor:pointer;" title="Date selector" />
					<img src="gfx/delete_micro.gif" onclick="document.historique_commande.filtre_date_sup.value='';">
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
				<td style="text-align:right;">Code ou référence article&nbsp;<input type="text" name="filtre_article" value="<?=$_SESSION['cde_adh_filtre_article']?>" size="8"></td>
				<td>
					<!-- vendeurs -->
					<select name="filtre_vendeur">
						<option value="">Tous vendeur</option>
<?							$res		= $sqlite->query("SELECT * FROM vendeurs WHERE nom <> '???' ORDER BY nom ASC") or die("Impossible de lancer la requete de selection des vendeurs : ".array_pop($sqlite->errorInfo()));
							while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
								<option value="<?=$row['code']?>" class="<?=$row['suspendu'] ? 'suspendu':''?>" <?=$_SESSION['cde_adh_filtre_vendeur']==$row['code'] ? ' selected':''?>><?=$row['nom']?></option>
<?							} ?>
					</select>
				</td>
				<td style="text-align:right;">
					<select name="filtre_pagesize">
						<option value=""<?=$_SESSION['cde_adh_filtre_pagesize']=='' ? ' selected':''?>>Une seule page</option>
						<option value="10"<?=$_SESSION['cde_adh_filtre_pagesize']=='10' ? ' selected':''?>>10 cde par page</option>
						<option value="15"<?=$_SESSION['cde_adh_filtre_pagesize']=='15' ? ' selected':''?>>15 cde par page</option>
						<option value="20"<?=$_SESSION['cde_adh_filtre_pagesize']=='20' ? ' selected':''?>>20 cde par page</option>
						<option value="50"<?=$_SESSION['cde_adh_filtre_pagesize']=='50' ? ' selected':''?>>50 cde par page</option>
						<option value="100"<?=$_SESSION['cde_adh_filtre_pagesize']=='100' ? ' selected':''?>>100 cde par page</option>
					</select>
				</td>
				<td>
					<select name="filtre_reliquat">
						<option value=""<?=$_SESSION['cde_adh_filtre_reliquat']			==''		? ' selected':''?> style="background-color:white;">Toutes les commandes</option>
						<option value="reliquat"<?=$_SESSION['cde_adh_filtre_reliquat']	=='reliquat'? ' selected':''?> style="background-color:orange;">Uniquement les reliquats</option>
						<option value="livre"<?=$_SESSION['cde_adh_filtre_reliquat']	=='livre'	? ' selected':''?> style="background-color:green;color:white;">Uniquement les livrées</option>
					</select>
				</td>
				<td style="text-align:right;">
					<input type="submit" class="button divers" style="background-image:url(gfx/magnify.png);" value="Filtrer">
				</td>
			</tr>
		</table>
	</caption>

	<thead>
	<!--[if IE]>
	<tr>
		<td colspan="7" style="border:none;">&nbsp;</td>
	</tr>
	<![endif]-->
	
	<tr>
		<th class="numero_bon">N°<br><a href="historique_commande.php?filtre_classement=numero_bon ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=numero_bon DESC"><img src="gfx/desc.png"></a></th>
		<th class="date_bon">Date<br><a href="historique_commande.php?filtre_classement=date_bon ASC,numero_bon ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=date_bon DESC,numero_bon DESC"><img src="gfx/desc.png"></a></th>
		<th class="date_liv">Date Liv.<br><a href="historique_commande.php?filtre_classement=date_liv ASC,numero_bon ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=date_liv DESC,numero_bon DESC"><img src="gfx/desc.png"></a></th>
		<th class="numero_artisan">Artisan<br><a href="historique_commande.php?filtre_classement=numero_artisan ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=numero_artisan DESC"><img src="gfx/desc.png"></a></th>
		<th class="vendeur">Vendeur<br><a href="historique_commande.php?filtre_classement=vendeur ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=vendeur DESC"><img src="gfx/desc.png"></a></th>
		<th class="reference">Référence<br><a href="historique_commande.php?filtre_classement=reference ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=reference DESC"><img src="gfx/desc.png"></a></th>
		<th class="nb_ligne">Nb ligne<br><a href="historique_commande.php?filtre_classement=nb_ligne ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=nb_ligne DESC"><img src="gfx/desc.png"></a></th>
		<th class="nb_livre">Nb livrées<br><a href="historique_commande.php?filtre_classement=nb_livre ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=nb_dispo DESC"><img src="gfx/desc.png"></a></th>
		<th class="nb_dispo">Dispo Coop<br><a href="historique_commande.php?filtre_classement=nb_dispo ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=nb_dispo DESC"><img src="gfx/desc.png"></a></th>
		<th class="montant">Mt HT Cde<br><a href="historique_commande.php?filtre_classement=montant ASC"><img src="gfx/asc.png"></a><a href="historique_commande.php?filtre_classement=montant DESC"><img src="gfx/desc.png"></a></th>
		<th class="pdf" style="border-right:none;border-left:none;">PDF</th>
		<th class="xls" style="border-left:none;">XLS</th>
	</tr>
	</thead>
	<tbody>
<?
	$where = array() ;
	$tables = array('cde_rubis');
	
	$date_inf_formater = join('-',array_reverse(explode('/',$_SESSION['cde_adh_filtre_date_inf'])));
	$date_sup_formater = join('-',array_reverse(explode('/',$_SESSION['cde_adh_filtre_date_sup'])));
	
	if ($_SESSION['cde_adh_filtre_date_inf'] && $_SESSION['cde_adh_filtre_date_inf'] != 'Aucune') $where[] = "date_bon >= '$date_inf_formater'" ;
	if ($_SESSION['cde_adh_filtre_date_sup'] && $_SESSION['cde_adh_filtre_date_sup'] != 'Aucune') $where[] = "date_bon <= '$date_sup_formater'" ;
	if ($_SESSION['cde_adh_filtre_reference'])	$where[] = "reference LIKE '%".strtoupper(mysql_escape_string($_SESSION['cde_adh_filtre_reference']))."%'" ;
	if ($_SESSION['cde_adh_filtre_vendeur'])	$where[] = "vendeur = '".strtoupper(mysql_escape_string($_SESSION['cde_adh_filtre_vendeur']))."'" ;
	if ($_SESSION['cde_adh_filtre_numero'])		$where[] = "cde_rubis.numero_bon LIKE '".strtoupper(trim(mysql_escape_string($_SESSION['cde_adh_filtre_numero'])))."%'" ;
	
	$where[] = "montant $_SESSION[cde_adh_filtre_signe_montant] $_SESSION[cde_adh_filtre_montant]" ;
	$where[] = 'nb_ligne > 0' ;

	if ($_SESSION['cde_adh_filtre_artisan'])
		$where[] = "cde_rubis.numero_artisan='".trim(mysql_escape_string($_SESSION['cde_adh_filtre_artisan']))."'"; // un artisan précis


	// code article présent dans la cde
	if ($_SESSION['cde_adh_filtre_article']) {
		array_push($tables,'cde_rubis_detail'); // on rajoute la table détail
		$where[]  = "cde_rubis.id_bon=cde_rubis_detail.id_bon"; // liaison naturel entre detail et entete
		$where[]  = "(cde_rubis_detail.code_article='".strtoupper(trim(mysql_escape_string($_SESSION['cde_adh_filtre_article'])))."' OR cde_rubis_detail.ref_fournisseur='".strtoupper(trim(mysql_escape_string($_SESSION['cde_adh_filtre_article'])))."')";
	}

	// cde en reliquat uniquement
	if			($_SESSION['cde_adh_filtre_reliquat'] == 'reliquat') {
		$where[] = "cde_rubis.nb_livre < cde_rubis.nb_ligne";
	} elseif	($_SESSION['cde_adh_filtre_reliquat'] == 'livre') { // cde en livrées uniquement
		$where[] = "cde_rubis.nb_livre >= cde_rubis.nb_ligne";
	}

	$where = $where ? $where = ' WHERE '.join(' AND ',$where) : '';

	$ordre = mysql_escape_string($_SESSION['cde_adh_filtre_classement']);
	$tables = join(',',$tables);
	$rows_per_page = $_SESSION['cde_adh_filtre_pagesize'];
	if (!$rows_per_page) $rows_per_page = 1000000;
	$limit = ($pageno - 1) * $rows_per_page .',' .$rows_per_page;

	$sql = <<<EOT
SELECT	cde_rubis.id_bon,cde_rubis.numero_bon,cde_rubis.numero_artisan,date_bon,date_liv,nb_ligne,nb_livre,nb_prepa,nb_dispo,montant,montant_dispo,reference,
		vendeurs.code AS code_vendeur, vendeurs.nom AS nom_vendeur, vendeurs.groupe_principal AS groupe_vendeur
FROM $tables
	LEFT JOIN vendeurs
		ON cde_rubis.vendeur=vendeurs.code
$where
ORDER BY $ordre
LIMIT $limit
EOT;

$sql_sans_limit = <<<EOT
SELECT	cde_rubis.id_bon,cde_rubis.numero_bon,cde_rubis.numero_artisan,date_bon,date_liv,nb_ligne,nb_livre,nb_prepa,nb_dispo,montant,montant_dispo,reference,
		vendeurs.code AS code_vendeur, vendeurs.nom AS nom_vendeur, vendeurs.groupe_principal AS groupe_vendeur
FROM $tables
	LEFT JOIN vendeurs
		ON cde_rubis.vendeur=vendeurs.code
$where
ORDER BY $ordre
EOT;

$sql_count = <<<EOT
select count(*)
from $tables
$where
EOT;

if (DEBUG) echo "<div style='color:red;'><pre>$sql</pre></div>" ;

	$res		= $sqlite->query($sql) or die("Impossible de lancer la requete de selection des bons : ".array_pop($sqlite->errorInfo()));
	$res_count	= $sqlite->query($sql_count) or die("Impossible de lancer la requete de selection du nombre bons : ".array_pop($sqlite->errorInfo()));
	$row_count  = $res_count->fetchColumn();
	$lastpage   = ceil($row_count / $rows_per_page);
	$total_montant = 0;
	
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) { ?>
		<tr style="background:<?= $i++ & 1 ? '#F5F5F5':'white' ?>" id="<?=$row['numero_bon']?>">
			<td class="numero_bon"><?=$row['numero_bon']?></td>
			<td class="date_bon"><?
				$tmp = explode('-',$row['date_bon']);
				$date_commande = mktime(0,0,0,$tmp[1],$tmp[2],$tmp[0]);
				$date_formater = date('d M Y',$date_commande);
				$jour_commande = $jours_mini[date('w',$date_commande)];
			?><?=$jour_commande?> <?=$date_formater?></td><!-- date -->
			<td class="date_liv"><?
				$tmp = explode('-',$row['date_liv']);
				$date_commande = mktime(0,0,0,$tmp[1],$tmp[2],$tmp[0]);
				$date_formater = date('d M Y',$date_commande);
				$jour_commande = $jours_mini[date('w',$date_commande)];
			?><?=$jour_commande?> <?=$date_formater?></td><!-- date -->
			<td class="numero_artisan"><?=
				isset($artisans[$row['numero_artisan']]) ? $artisans[$row['numero_artisan']] : $row['numero_artisan']
			?></td>
			<td class="vendeur" style="text-align:left;"><?=$row['nom_vendeur']?>
				<? if ($row['groupe_vendeur']) { ?>
					<span class="groupe_vendeur">(<?=$row['groupe_vendeur']?>)</span>
				<? } ?>
			</td><!-- vendeur -->
			<td class="reference" style="text-align:left;"><?=$row['reference']?></td><!-- réference -->
			<td class="nb_ligne" style="text-align:center;"><?=$row['nb_ligne']?></td><!-- nombre de ligne -->
			<!-- nombre de ligne livrées -->
			<td class="nb_livre" style="text-align:center;">
<?				if ($row['nb_livre'] == $row['nb_ligne']) { // tout est livré ?>
					<img src="gfx/ok.png" title="Votre commande est entièrement livrée"/>
<?				} else { ?>
					<?=$row['nb_livre']?>
<?			} ?>
			</td>
			<!-- nombre de ligne dispo -->
			<td class="nb_dispo" style="text-align:center;">
<?				if ($row['nb_livre'] != $row['nb_ligne']) {
					if ($row['nb_dispo'] <= 0) { // matos pas recu ?>
						<?=$row['nb_dispo']?><br/><img src="gfx/stock2-1.png" title="Aucune pièce de votre commande n'a été reçu"/>
<?					} elseif ($row['nb_dispo'] + $row['nb_livre'] < $row['nb_ligne']) {  // matos partiellement dispo ?>
						<?=$row['nb_dispo']?><br/><img src="gfx/stock2-2.png" title="Votre commande est partiellement disponible à la coopérative"/>
<?					} elseif ($row['nb_dispo'] + $row['nb_livre'] >= $row['nb_ligne']) {  // matos dispo ?>
						<?=$row['nb_dispo']?><br/><img src="gfx/stock2-3.png" title="Votre commande est entièrement disponible à la coopérative"/>
<?					}
				} ?>
			</td><!-- nombre de ligne livrées -->
			<td class="montant" style="text-align:right;" nowrap>
				<? printf('%0.2f',$row['montant']) ?> &euro;
				<? if ($row['montant_dispo'] > 0 && $row['nb_livre'] != $row['nb_ligne']) { ?>
						<br/><span class="montant_dispo"><?=$row['montant_dispo']?> &euro; dispo ou livré</span>
				<? } ?>
			</td><!-- Mt commande -->
			<td class="pdf" style="border-right:none;border-left:none;"><a href="edition_pdf.php?id=<?=$row['id_bon']?>"><img src="gfx/icon-pdf.png" alt="Edition PDF" /></a></td>
			<td class="xls" style="border-left:none;"><a href="edition_excel.php?id=<?=$row['id_bon']?>"><img src="gfx/icon-excel.png" alt="Edition Excel" /></a></td>
		</tr>
<?	
		$total_montant += $row['montant'] ;
	} // while commande ?>
	</tbody>
	<tfoot>
		<tr>
			<td colspan="8" style="text-align:center;">
			<input type="button" class="button valider excel" value="Télécharger le fichier Excel" onclick="telecharger_excel('<?=urlencode(urlencode(base64_encode($sql_sans_limit)))?>');"/><!-- double urlencode pour éviter le probleme du décoage automatique par php --></td>
			<td style="text-align:right;">Total : <? printf('%0.2f',$total_montant) ?> &euro;</td>
			<td colspan="3">&nbsp;</td>
		</tr>
	</tfoot>
</table>

<div class="pagination">
<? if ($row_count > 0) {
	if ($pageno > 1) { ?>
	<a href='<?=$_SERVER['PHP_SELF']?>?pageno=1'>&lt;&lt;PREMIERE</a>
	<a href='<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno - 1?>'>&lt;PREC</a>
<? } ?>

&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<span class="nombre"><?=$row_count?></span> commande(s)&nbsp;&nbsp;&nbsp;


<!-- menu déroulant pour accedez à la page de son choix -->
<select name="pageno" onchange="change_page(this);">
<?	for($i=1 ; $i<=$lastpage ; $i++) { ?>
		<option value="<?=$i?>"<?= $i==$pageno?' selected':'' ?>>Page <?=$i?></option>
<?	} ?>
</select>


de <span class="nombre"><?=$lastpage?></span>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;
<? if ($pageno < $lastpage) { ?>
	<a href='<?=$_SERVER['PHP_SELF']?>?pageno=<?=$pageno + 1?>'>SUIV&gt;</a>
	<a href='<?=$_SERVER['PHP_SELF']?>?pageno=<?=$lastpage?>'>DERNIERE&gt;&gt;</a>
<? } ?>

<?	} ?>
</div>

</form>

<div style="margin-top:10px;color:red;text-align:center;">La mise à jour a eu lieu ce matin à 5h</div>

</body>
</html>
<?
unset($sqlite);
?>