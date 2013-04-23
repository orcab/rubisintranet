<?

include('../../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$droit = recuperer_droit();

if (!isset($_POST['type_cde']) || !$_POST['type_cde'])
	die("Erreur type de commande non précisé");

if (!isset($_POST['num_cde']) || !$_POST['num_cde'])
	die("Erreur numéro de commande non précisé");

$num_cde = strtoupper($_POST['num_cde']);
?>
<html>
<head>
<title>Lignes envoyées à Reflex du bon <?=$num_cde?></title>

<style>

body {
    font-family: verdana;
    font-size: 0.8em;
}
h1 {
    font-size: 1.2em;
}
#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    margin-top: 1em;
}
#lignes th, #lignes td {
    border: 1px solid #CCCCCC;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #CCCCCC;
    text-decoration: line-through;
}
caption {
    background-color: #DDD;
    padding: 2px;
}

.reflex_envoyee {
    color: green;
}
.reflex_non_envoyee,.rubis_non_livre {
    color: red;
}

.rubis_livre {
	color:green;
	background-color:#CFC;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

function tout_selectionner() {
	$('input[type=checkbox][name^=etat_reflex_]').each(function(){
		$(this).attr('checked','checked');
	});
}

function inverser_selection() {
	$('input[type=checkbox][name^=etat_reflex_]').each(function(){
		if ($(this).attr('checked') == 'checked')
			$(this).removeAttr('checked');
		else
			$(this).attr('checked','checked');
	});
}

function update_etat_reflex() {
	document.lignes.submit();
}
//-->
</script>

</head>
<body>

<a class="btn" href="index.php"><i class="icon-arrow-left"></i> Revenir au choix de bon</a>

<form name="lignes" method="POST" action="index.php">
<input type="hidden" name="action" value="update_etat_reflex"/>
<input type="hidden" name="type_cde" value="<?=$_POST['type_cde']?>"/>
<input type="hidden" name="num_cde" value="<?=$num_cde?>"/>


<?	
	$num_cde_escape = mysql_escape_string($num_cde);
	$sql = '';
	if 		($_POST['type_cde'] == 'client') {
		$sql = <<<EOT
select
	DETAIL.NOCLI as NUM_TIER,
	DETAIL.USSBE as LAST_USER,	(CONCAT(DSBMJ,CONCAT('/',CONCAT(DSBMM,CONCAT('/',CONCAT(DSBMS,DSBMA)))))) as LAST_MODIFICATION_DATE,
	DETAIL.NOLIG as NUM_LIGNE,
	DETAIL.ETSBE as ETAT_RUBIS,
	DETAIL.DET06 as ETAT_REFLEX,
	DETAIL.CODAR as CODE_ARTICLE,
	DETAIL.TRAIT as R_F,
	DETAIL.TYCDD as TYPE,DS1DB as DESIGNATION1,DS2DB as DESIGNATION2,
	DETAIL.QTESA as QTE,
	DETAIL.PREDI as LIGNE_EDITE,
	ENTETE.CDCAM as MOTIF,
	ENTETE.LIVSB as SUIVI_PAR,
	(CONCAT(DLJSB,CONCAT('/',CONCAT(DLMSB,CONCAT('/',CONCAT(DLSSB,DLASB)))))) as DATE_LIVRAISON_ENTETE
from 
				${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 as DETAIL
	left join 	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 as ENTETE
		on DETAIL.NOBON=ENTETE.NOBON and DETAIL.NOCLI=ENTETE.NOCLI
where
		DETAIL.NOBON='$num_cde_escape'
	and DETAIL.PROFI='1'
EOT;

	} elseif($_POST['type_cde'] == 'fournisseur') {
		$sql = <<<EOT
select
	DETAIL.NOFOU as NUM_TIER,
	DETAIL.CFLIG as NUM_LIGNE,
	DETAIL.CFDID as LAST_USER, (CONCAT(CFDMJ,CONCAT('/',CONCAT(CFDMM,CONCAT('/',CONCAT(CFDMS,CFDMA)))))) as LAST_MODIFICATION_DATE,
	DETAIL.CFDET as ETAT_RUBIS,
	DETAIL.CFD31 as ETAT_REFLEX,
	DETAIL.CFART as CODE_ARTICLE,
	DETAIL.CDDE1 as R_F,
	DETAIL.CFDPA as TYPE,
	DETAIL.CFDE1 as DESIGNATION1,CFDE2 as DESIGNATION2,
	DETAIL.CFQTE as QTE,
	DETAIL.CDDE2 as LIGNE_EDITE,
	ENTETE.CFSER as SUIVI_PAR,
	(CONCAT(CFELJ,CONCAT('/',CONCAT(CFELM,CONCAT('/',CONCAT(CFELS,CFELA)))))) as DATE_LIVRAISON_ENTETE
from
				${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 as DETAIL
	left join 	${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 as ENTETE
		on DETAIL.CFBON=ENTETE.CFBON and DETAIL.NOFOU=ENTETE.NOFOU
where
		DETAIL.CFBON='$num_cde_escape'
	and DETAIL.CFPRF='1'
EOT;
	}

	$rubis  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Rubis via ODBC ($LOGINOR_DSN)");
	$res 	= odbc_exec($rubis,$sql)  or die("Impossible de lancer la requete de recherche des lignes : <br/>$sql");
	$row 	= odbc_fetch_array($res,1); // lit la premiere ligne pour récupérer les entetes de bons
?>


<table id="lignes" style="width:100%;">
	<caption>
		Lignes de commande <strong><?=$_POST['type_cde']?></strong> du bon <strong><?=$num_cde?></strong>
		<? if ($_POST['type_cde'] == 'client') { ?>
			&nbsp;&nbsp;&nbsp;Motif : <?=$row['MOTIF']?>
		<? } ?>
		&nbsp;&nbsp;&nbsp;Date liv : <?=$row['DATE_LIVRAISON_ENTETE']?>
		&nbsp;&nbsp;&nbsp;Suivi par : <?=$row['SUIVI_PAR']?>
	</caption>
	<theader>
	<tr>
		<th class="num_tier">N° tier</th>
		<th class="num_ligne">N° ligne</th>
		<th class="code_article">Code</th>
		<th class="last_action">Dernière action</th>
		<th class="ligne_editee"><?= $_POST['type_cde']=='client'?'BP<br/>édité':'Ligne<br/>éditée ?' ?></th>
		<th class="r_f"><?= $_POST['type_cde']=='client'?'R/F':'RECEP ?' ?></th>
		<th class="type">Type</th>
		<th class="etat_rubis">Etat rubis</th>
		<th class="etat_reflex">
			Etat Reflex<br/>
			<a class="btn btn-small" onclick="tout_selectionner();" title="Tout sélectionner"><i class="icon-check"></i></a>
			<a class="btn btn-small" onclick="inverser_selection();" title="Inverser la sélection"><i class="icon-refresh"></i></a>
		</th>
		<th class="designation">Designation</th>
		<th class="qte">Qte</th>
	</tr>
	</theader>
	<tbody>
<?
	$i=1; // on recommence a lire depuis la première lignes
	while($row = odbc_fetch_array($res,$i++)) {
		$etat_rubis 	= trim($row['ETAT_RUBIS']) 	? true:false;
		$rubis_livree	= trim($row['R_F'])=='F'||trim($row['R_F'])=='OUI' 	? true:false;
?>
		<tr class="<?=$etat_rubis ? ' annule':''?> <?=$rubis_livree ? ' rubis_livre':'rubis_non_livre'?>">
			<td class="num_tier"><?=$row['NUM_TIER']?></td>
			<td class="num_ligne"><?=$row['NUM_LIGNE']?></td>
			<td class="code_article"><?=$row['CODE_ARTICLE']?></td>
			<td class="last_action"><?=$row['LAST_USER']?><br/><?=$row['LAST_MODIFICATION_DATE']?></td>
			<td class="ligne_editee">
			<? if ($_POST['type_cde']=='client') { ?>
				<?=$row['LIGNE_EDITE']=='O'?'OUI':'NON'?>
			<? } else { ?>
				<?=$row['LIGNE_EDITE']=='OUI'?'OUI':'NON'?>
			<? } ?>
			</td>
			<td class="r_f <?= $rubis_livree ? 'rubis_livre':'rubis_non_livre' ?>"><?=$rubis_livree ? ($_POST['type_cde']=='client'?'Livrée':'Receptionnée'):'Reliquat' ?></td>
			<td class="type"><?=$row['TYPE']?></td>
			<td class="etat_rubis"><?=$row['ETAT_RUBIS']?></td>
			<? $etat_reflex = trim($row['ETAT_REFLEX']) ? true:false; ?>
			<td class="etat_reflex <?= $etat_reflex ? 'reflex_envoyee':'reflex_non_envoyee' ?>" style="text-align:right;padding-right:1em;">
			<?	if (!$rubis_livree)
					if ($etat_reflex)
						echo 'Envoyée';
					else
						echo 'Non envoyé';
					
				if (!$etat_rubis && !$rubis_livree) { // ligne non annulée  ?>
					<input type="checkbox" 	name="etat_reflex_<?=trim($row['NUM_TIER'])?>_<?=trim($row['NUM_LIGNE'])?>" <?=$etat_reflex ? 'checked="checked"':''?>/>
					<input type="hidden" 	name="ligne_<?=trim($row['NUM_TIER'])?>_<?=trim($row['NUM_LIGNE'])?>" value="<?=$etat_reflex?>"/>
			<?	} ?>
			</td>
			<td class="designation" style="text-align:left;"><?=$row['DESIGNATION1']?><br/><?=$row['DESIGNATION2']?></td>
			<td class="qte"><?=$row['QTE']?></td>
		</tr>
<?	} ?>
	</tbody>
	<tfooter>
	<tr>
		<td colspan="8"></td>
		<td>
			<? if ($droit & PEUT_ENVOYER_LIGNE_A_REFLEX) { ?>
				<a class="btn btn-success" onclick="update_etat_reflex();" title="Mettre à jour l'état Reflex dans Rubis"><i class="icon-ok"></i> Valider</a>
			<? } ?>
		</td>
		<td colspan="2"></td>
	</tr>
	<tfooter>
</table>
</form>
</body>
</html>