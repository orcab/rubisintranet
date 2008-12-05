<?
include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

//////////////////////// AJOUT DE LIGNE EN DIFF /////////////////////////////
if (isset($_POST['what']) && $_POST['what']=='add_fact' && 
	isset($_POST['no_fact']) && $_POST['no_fact']) {

	// on va chercher les infos dans RUBIS
	$nofact_escape = trim(strtoupper(mysql_escape_string($_POST['no_fact'])));
	$commentaire_escape = trim(strtoupper(mysql_escape_string($_POST['commentaire'])));

	// on regarde si on a plusieur fournisseur qui ont le meme n° de facture
	$sql = <<<EOT
select DISTINCT(CFAFOU) as CODE_FOURNISSEUR,NOMFO
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFAENP1 CONTROLE_FACTURE, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
where	
		CEFNU='$nofact_escape'
		and CONTROLE_FACTURE.CFAFOU=FOURNISSEUR.NOFOU
EOT;
	//echo "<br>\n$sql\n<br>";
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des fournisseurs sur cette facture ($sql)");
	$fournisseurs = array();
	while($row = odbc_fetch_array($res)) {
		array_push($fournisseurs, array($row['CODE_FOURNISSEUR'],$row['NOMFO']));
	}
	//print_r($fournisseurs);

	if (sizeof($fournisseurs) == 1) { // si un seul fournisseur
		// insertion de la nouvelle ligne a surveiller dans MYSQL
		mysql_query("INSERT INTO diff_cde_fourn (code_fournisseur,no_fact,commentaire) VALUES ('".trim($fournisseurs[0][0])."','$nofact_escape','$commentaire_escape')") or die("Ne peux pas inserer la facture en surveillance : ".mysql_error());
	}

} // fin ajout

?>
<html>
<head>
<title>Différence de facturation des commandes fournisseurs</title>

<style>

body,td {
	font-family:verdana;
	font-size:0.8em;
}

table {
	border:solid 1px grey;
	border-collapse:collapse;
}

table#diff { width:95%; }
table#activ { width:20%; }

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

.date, .fournisseur,.cde,.facture,.qui { text-align:center; }
.prix { text-align:right; }
.commentaire { width:25%; }

tr.positif { background-color:#CFC; }
tr.negatif { background-color:#FCC; }
td.positif { color:green; }
td.negatif { color:red; }

fieldset {
	border:solid 1px grey;
	color:grey;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--
function verif_champs(mon_form) {
	if (!mon_form.no_fact.value) {
		alert("Champs n° de facture fournisseur vide");
		return false;
	} else {
		mon_form.commentaire.value = prompt("Commentaire");
		return true;
	}
}

//-->
</script>

</head>
<body>

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>
<br/>
<center>
<form name="add_cde" method="POST" action="index.php" onsubmit="return verif_champs(this);">
	<input type="hidden" name="what" value="add_fact" />
	<input type="hidden" name="commentaire" value="" />
	<fieldset style="width:50%;text-align:center;">
		<legend>Ajouter une différence de facturation fournisseur</legend>
		N° de facture fournisseur : <input type="text" name="no_fact" value="" size="8" />
		<input type="submit" value="Valider" class="button valider" />
	</fieldset>
</form>


<!-- AFFICHAGE DES SURVEILLANCES FACTURES -->
<table id="diff">
		<caption>Diff&eacute;rence de factures fournisseur</caption>
		<tr>
			<th>Date ctrl.</th>
			<th>Fournisseur</th>
			<th>N° Cde</th>
			<th>N° Fact</th>
			<th>Mt prévu</th>
			<th>Mt r&eacute;el</th>
			<th>Diff</th>
			<th>Activ.</th>
			<th>Commentaire</th>
			<th>Qui</th>
		</tr>
<?
		$res = mysql_query("SELECT * FROM diff_cde_fourn ORDER BY id DESC") or die("Peux pas retrouver les lignes a surveiller : ".mysql_error());
		$ligne_a_surveiller = array();
		$ligne = array();
		while($row = mysql_fetch_array($res)) {
			$ligne[] = "(CONTROLE_FACTURE_ENTETE.CEFNU='$row[no_fact]' AND CONTROLE_FACTURE_ENTETE.CFAFOU='$row[code_fournisseur]')";
			$ligne_a_surveiller["$row[code_fournisseur]/$row[no_fact]"] = $row['commentaire'];
		}
		if ($ligne)
			$ligne = '('.join(" OR ",$ligne).')';
		else
			$ligne = '';

		$sql = <<<EOT
select 	CENID,CONCAT(CENCJ,CONCAT('/',CONCAT(CENCM,CONCAT('/',CONCAT(CENCS,CENCA))))) as DATE_CONTROLE,
		CONTROLE_FACTURE_ENTETE.CFAFOU,NOMFO,CEFNU,CEMON
from	
		${LOGINOR_PREFIX_BASE}GESTCOM.ACFAENP1 CONTROLE_FACTURE_ENTETE
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
				on CONTROLE_FACTURE_ENTETE.CFAFOU=FOURNISSEUR.NOFOU
where
		$ligne
EOT;

//echo "<pre style='text-align:left;font-size:1.2em;'>$sql</pre>";
		if ($ligne) { // s'il existe des lignes a surveiller

			$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des factures ($sql)");
			while($row = odbc_fetch_array($res)) {
				$row['CEFNU']  = trim($row['CEFNU']);
				$row['CFAFOU'] = trim($row['CFAFOU']);

				$sql = <<<EOT
select	HIBON,HIPRI,ACFLI
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFADEP1 CONTROLE_FACTURE_DETAIL
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFAMILP1 FAMILLE
				on CONTROLE_FACTURE_DETAIL.CFVC6=FAMILLE.AFCAC and AFCNI='ACT'
where		CFFNU='$row[CEFNU]'
		and CFAFOU='$row[CFAFOU]'
EOT;

//echo "<pre style='text-align:left;font-size:1.2em;'>$sql</pre>";
				$res_detail = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des détails factures ($sql)");
				$no_bon = array(); $montant_reel = 0; $activite = array();
				while($row_detail = odbc_fetch_array($res_detail)) {
					$no_bon[$row_detail['HIBON']] = 1;
					$activite[$row_detail['ACFLI']] = 1;
					$montant_reel += $row_detail['HIPRI'];
				}
				$diff = $row['CEMON'] - $montant_reel;
?>
				<tr class="<?=$diff >= 0 ? 'positif':'negatif'?>">
					<td class="date"><?=$row['DATE_CONTROLE']?></td>
					<td class="fournisseur"><?=$row['NOMFO']?></td>
					<td class="cde"><?=join(", ",array_keys($no_bon))?></td>
					<td class="facture"><?=$row['CEFNU']?></td>
					<td class="prix"><?=sprintf('%0.2f',$row['CEMON'])?>&euro;</td>
					<td class="prix"><?=sprintf('%0.2f',$montant_reel)?>&euro;</td>
					<td class="prix <?=$diff >= 0 ? 'positif':'negatif'?>" style="font-weight:bold;"><?=sprintf('%0.2f',$diff)?>&euro;</td>
					<td class="activite"><?=join("<br/>",array_keys($activite))?></td>
					<td class="commentaire"><?=isset($ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"]) ? $ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"]:'' ?></td>
					<td class="qui"><?=$row['CENID']?></td>
				</tr>
<?			}
		} ?>

</center>

</body>
</html>
<?
odbc_close($loginor);
mysql_close($mysql);
?>