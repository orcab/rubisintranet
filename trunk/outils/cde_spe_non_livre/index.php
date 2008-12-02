<?
include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


//////////////////////// AJOUT DE LIGNE A SURVEILLER /////////////////////////////
if (isset($_POST['what']) && $_POST['what']='cde_a_suivre') {
	foreach($_POST as $cle=>$val) {
		if (eregi("^check_([^\/]+)\/([^\/]+)\/([^\/]+)$",$cle,$regs)) { // si l'objet est a enregistrer
			$no_cli = $regs[1]; $no_bon = $regs[2]; $no_ligne = $regs[3];
			// on ajoute les surveillances
			mysql_query("INSERT INTO suivi_cde_spe (no_client,no_bon,no_ligne,date_saisie) VALUES ('$no_cli','$no_bon','$no_ligne',NOW())") ;
		}
	}
}


// CHARGE LES SURVEILLANCES EN COURS
$ligne_en_surveillance = array();
$res = mysql_query("SELECT CONCAT(no_client,'/',no_bon,'/',no_ligne) AS id_ligne,date_saisie,DATE_FORMAT(date_saisie,'%d/%m/%Y') AS date_saisie_formatee FROM suivi_cde_spe") or die("Ne peux pas récupérer la liste des lignes en cours de surveillance : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	$ligne_en_surveillance[$row['id_ligne']] = array($row['date_saisie'],$row['date_saisie_formatee']) ;
}

?>
<html>
<head>
<title>Commandes spéciales restants à livrer</title>

<style>

body,td {
	font-family:verdana;
	font-size:0.8em;
}

table {
	border:solid 1px grey;
	border-collapse:collapse;
	width:60%;
}

th {
	background-color:#EEE;
	font-family:verdana;
	font-size:0.7em;
	border:solid 1px grey;
}

td {
	padding:3px;
	border:solid 1px grey;
	vertical-align:top;
}

fieldset {
	border:solid 1px grey;
	color:grey;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--
function verif_champs(mon_form) {
	if (!mon_form.NOBON.value) {
		alert("Champs n° de cde adhérent vide");
		return false;
	} else {
		return true;
	}
}

function select_all() {
	for(i=0 ; i<document.cde_a_suivre.elements.length ; i++)
		if (document.cde_a_suivre.elements[i].type == 'checkbox')
			document.cde_a_suivre.elements[i].checked = true;
}

function invert_select() {
	for(i=0 ; i<document.cde_a_suivre.elements.length ; i++)
		if (document.cde_a_suivre.elements[i].type == 'checkbox')
			document.cde_a_suivre.elements[i].checked = !document.cde_a_suivre.elements[i].checked;
}

//-->
</script>

</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<center>
<form name="cde_special" method="POST" action="index.php" onsubmit="return verif_champs(this);">
	<fieldset style="width:50%;text-align:center;">
		<legend>Ajouter une cde à surveiller</legend>
		N° de cde adhérent : <input type="text" name="NOBON" value="" size="8" />
		<input type="submit" value="Valider" class="button valider" />
	</fieldset>
</form>



<?	if (isset($_POST['NOBON'])) { ///////////////// MODE AJOUT ////////////////////////////// ?>
		
		<form name="cde_a_suivre" method="POST" action="index.php">
		<input type="hidden" name="what" value="cde_a_suivre" />
		<table>
<?
		$nobon_escape = strtoupper(mysql_escape_string($_POST['NOBON']));
		$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$sql = <<<EOT
select NOMCL,CODAR,NOLIG,DS1DB,DS2DB,DS3DB,CONSA,CLIENT.NOCLI,QTESA,MONPR
from AFAGESTCOM.ADETBOP1 DETAIL_BON, AFAGESTCOM.ACLIENP1 CLIENT
where	DETAIL_BON.NOCLI=CLIENT.NOCLI
	and	NOBON='$nobon_escape'
	and TRAIT='R'
	and PROFI=1
	and TYCDD='SPE'
	and ETSBE=''
order by NOCLI ASC, NOBON ASC,NOLIG ASC
EOT;
		$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde adhérents ($sql)");
		$old_nocli = '';
		while($row = odbc_fetch_array($res)) {
			if($row['NOCLI'] != $old_nocli) { // nouveau n° de client ?>
				<tr><th colspan="5"><?=$row['NOMCL']?> cde n°<?=$_POST['NOBON']?></th></tr>
				<tr><td colspan="5">&nbsp;
					<img src="gfx/fleche.png"/>
					<input type="button" value="Tout selectionner" onclick="select_all();" class="button divers" style="background-image:url(../../js/boutton_images/select.png)"/>&nbsp;&nbsp;
					<input type="button" value="Inverser la sel." onclick="invert_select();" class="button divers" style="background-image:url(../../js/boutton_images/invert.png)"/>&nbsp;&nbsp;
					<input type="submit" value="Enregistrer" class="button valider"/>
					</td>
				</tr>
<?			} ?>
			<tr>
				<td><input type="checkbox" name="check_<?="$row[NOCLI]/$_POST[NOBON]/$row[NOLIG]"?>" <?= isset($ligne_en_surveillance["$row[NOCLI]/$_POST[NOBON]/$row[NOLIG]"]) ?'checked="checked" ':'' ?>/></td>
				<td><?=$row['NOLIG']?></td>
				<td><?=$row['CODAR']?></td>
				<td><?=$row['DS1DB']?><br/><?=$row['DS2DB']?><br /><?=$row['DS3DB']?><?= trim($row['CONSA'])?"<br/>($row[CONSA])":'' ?></td>
				<td>x<?=ereg_replace('\.000$','',$row['QTESA'])?></td>
				<td><?=$row['MONPR']?></td>
			</tr>
<?			$old_nocli = $row['NOCLI'];
		}
?>
	</table>
	</form>




<?	} else { ///////////////// MODE AFFICHAGE ////////////////////////////// ?>
	
	<table>
		<caption>Cde adhérent à livrer</caption>
		<tr>
			<th>Adhérent</th>
			<th>N° de cde</th>
			<th>n° ligne</th>
			<th>Article</th>
			<th>Désignation</th>
			<th>qte</th>
			<th>Px four</th>
			<th>Date ctrl.</th>
			<th>Date récep.</th>
			<th>Date liv.</th>
		</tr>
<?		
		$res = mysql_query("SELECT * FROM suivi_cde_spe") or die("Peux pas retrouver les lignes a surveiller : ".mysql_error());
		$ligne = array();
		while($row = mysql_fetch_array($res)) {
			$ligne[] = "(DETAIL_BON.NOCLI='$row[no_client]' AND NOBON='$row[no_bon]' AND NOLIG='$row[no_ligne]')";
		}
		if ($ligne)
			$ligne = ' AND ('.join(" OR ",$ligne).')';
		else
			$ligne = '';
		
		$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$sql = <<<EOT
select	NOMCL,CODAR,NOLIG,DS1DB,DS2DB,DS3DB,CONSA,CLIENT.NOCLI,QTESA,TRAIT,NOBON,MONPR,
		CONCAT(DTLIJ,CONCAT('/',CONCAT(DTLIM,CONCAT('/',CONCAT(DTLIS,DTLIA))))) as DATE_LIVRAISON,
		CONCAT(DDISJ,CONCAT('/',CONCAT(DDISM,CONCAT('/',CONCAT(DDISS,DDISA))))) as DATE_RECEPTION,
		QTREC
from AFAGESTCOM.ADETBOP1 DETAIL_BON, AFAGESTCOM.ACLIENP1 CLIENT
where	DETAIL_BON.NOCLI=CLIENT.NOCLI
	and PROFI=1
	and TYCDD='SPE'
	and ETSBE=''
	$ligne
order by NOCLI ASC, NOBON ASC,NOLIG ASC
EOT;

		if ($ligne) { // s'il existe des lignes a surveiller

			$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde adhérents ($sql)");
			while($row = odbc_fetch_array($res)) {	?>
				<tr>
					<td><?=$row['NOMCL']?></td>
					<td><?=$row['NOBON']?></td>
					<td><?=$row['NOLIG']?></td>
					<td><?=$row['CODAR']?></td>
					<td><?=$row['DS1DB']?><br /><?=$row['DS2DB']?><br /><?=$row['DS3DB']?><?= trim($row['CONSA'])?"<br/>($row[CONSA])":'' ?></td>
					<td>x<?=ereg_replace('\.000$','',$row['QTESA'])?></td>
					<td><?=$row['MONPR']?>&euro;</td>
					<td><?=$ligne_en_surveillance["$row[NOCLI]/$row[NOBON]/$row[NOLIG]"][1]?></td>
					<td style="text-align:center;"><?= $row['QTREC'] ? $row['DATE_RECEPTION']:"<img src='/intranet/js/boutton_images/cancel.png'>"?></td>
					<td><?=$row['DATE_LIVRAISON']?></td>
				</tr>
<?			}
		} ?>
	</table>
<?	} ?>



</center>

</body>
</html>