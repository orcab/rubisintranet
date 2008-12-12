<?
include('../../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");

$message = '' ;

//////////////////////// AJOUT DE LIGNE EN DIFF /////////////////////////////
if (isset($_POST['what']) && $_POST['what']=='add_fact' && 
	isset($_POST['no_fact']) && $_POST['no_fact'] && 
	isset($_POST['montant_cde']) && $_POST['montant_cde'] &&
	isset($_POST['fournisseur']) && $_POST['fournisseur']) {

	// on va chercher les infos dans RUBIS
	$nofact_escape		= trim(strtoupper(mysql_escape_string($_POST['no_fact'])));
	$montant_cde_escape = trim(strtoupper(mysql_escape_string($_POST['montant_cde'])));
	$commentaire_escape = trim(strtoupper(mysql_escape_string($_POST['commentaire'])));
	$fournisseur_escape = trim(strtoupper(mysql_escape_string($_POST['fournisseur'])));

	// insertion de la nouvelle ligne a surveiller dans MYSQL
	mysql_query("INSERT INTO diff_cde_fourn (code_fournisseur,no_fact,montant_cde,commentaire) VALUES ('$fournisseur_escape','$nofact_escape','$montant_cde_escape','$commentaire_escape')") or die("Ne peux pas inserer la facture en surveillance : ".mysql_error());
	$message = (mysql_affected_rows($mysql) == 1) ? "La facture n�$_POST[no_fact] a �t� correctement ajout�" : "Une erreur est survenu, impossible d'ajouter la facture";

} // fin ajout


//////////////////////// SUPPRESION DE LIGNE EN DIFF /////////////////////////////
elseif (isset($_GET['what']) && $_GET['what']=='del_fact' && 
		isset($_GET['id']) && $_GET['id']) {

		mysql_query("DELETE FROM diff_cde_fourn WHERE id=".mysql_escape_string($_GET['id'])) or die("Ne peux pas supprimer la facture : ".mysql_error());
		$message = (mysql_affected_rows($mysql) == 1) ? "La facture a �t� correctement supprim�e" : "Une erreur est survenu, impossible de supprimer la facture";
}

//print_r($_POST);

?>
<html>
<head>
<title>Diff�rence de facturation des commandes fournisseurs</title>

<style>

a > img { border:none; }

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

fieldset {
	border:solid 1px grey;
	color:grey;
}

.date, .fournisseur,.cde,.facture,.qui { text-align:center; }
.prix { text-align:right; }
.commentaire { width:25%; }
.edit { text-align:center; border-right:none; }
.sup { text-align:center; border-left:none; }

tr.positif { background-color:#CFC; }
tr.negatif { background-color:#FCC; }
td.positif { color:green; }
td.negatif { color:red; }
td.manuelle { color:grey; }

div#choix-fournisseur {
	padding:20px;
	border:solid 2px black;
	-moz-border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>

<script language="javascript">
<!--

///// AJAX ///////////////////////////////////
var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non support� par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");


function verif_champs() {
	mon_form = document.add_cde ;

	if (!mon_form.no_fact.value) {
		alert("Champs n� de facture fournisseur vide");
		return false;
	} else {
		while(mon_form.montant_cde.value == '') {
			mon_form.montant_cde.value	= prompt("Montant de la cde fournisseur");
		}
		mon_form.commentaire.value	= prompt("Commentaire");
		mon_form.what.value			='add_fact'; // action d'ajout� une facture

		// on va checker la base pour v�rifi� combien il y a de fournisseur pour cette facture
		http.open('GET', 'ajax.php?what=check_nb_fournisseur&no_fact='+escape(mon_form.no_fact.value), true);
		http.onreadystatechange = function() {
			$('#loading').css('visibility','visible');

			if (http.readyState == 4 && http.responseText) {
				fournisseurs = eval('(' + http.responseText + ')'); // structure JSON [ [code1,nom1],[code2,nom2], ... ]
				if (fournisseurs.length == 1)  { // un seul fournisseur
					document.add_cde.fournisseur.value=fournisseurs[0][0];
					document.add_cde.submit(); // on submit le formulaire

				} else if (fournisseurs.length > 1) { // cas d'une facture avec plusieurs fournisseur
					$('#choix-fournisseur').css('top',document.body.scrollTop +100);
					$('#choix-fournisseur').css('left',screen.availWidth / 2 - 300);

					for(i=0 ; i<fournisseurs.length ; i++)
						$('#choix-fournisseur').html($('#choix-fournisseur').html() + '<a href="javascript:valider_choix_fournisseur(\''+fournisseurs[i][0]+'\')">'+fournisseurs[i][1]+'</a><br>') ;

					$('#loading').css('visibility','hidden');
					$('#choix-fournisseur').show();					

				} else { // pas de fournisseur trouv�
					alert("N� de facture inconnu");
				}
			}
		};
		http.send(null);
		return false;
	}
}

function valider_choix_fournisseur(code_fournisseur) {
	$('#choix-fournisseur').hide();
	document.add_cde.fournisseur.value=code_fournisseur;
	document.add_cde.submit();
}


function del_fact(id) {
	if (confirm("Voulez-vous vraiment supprimer cette facture ?"))
		document.location.href='index.php?what=del_fact&id='+id;
}

function edit_fact(id) {
	var diff_element= document.getElementById('diff_'+id);
	var com_element	= document.getElementById('com_'+id);
	var edit_element= document.getElementById('edit_'+id);
	var old_diff	= trim(diff_element.innerHTML);
	var old_com		= trim(com_element.innerHTML);
	old_diff = old_diff.substring(0,old_diff.length - 1); // on supprime le caractere �

	diff_element.innerHTML	= '<input type="text" name="new_diff" size="10" value="'+old_diff+'" class="prix" />&euro;';
	com_element.innerHTML	= '<input type="text" name="new_com" size="40" value="'+old_com+'" />';
	edit_element.innerHTML	= '<img src="../../js/boutton_images/accept.png" title="Valider les modifications" onclick="save_diff('+id+');"/>';
}

function save_diff(id) {
	var diff_element= document.getElementById('diff_'+id);
	var com_element	= document.getElementById('com_'+id);
	var edit_element= document.getElementById('edit_'+id);

	var nouveau_diff= document.add_cde.new_diff.value ;
	var nouveau_com	= document.add_cde.new_com.value ;

	// on sauvegarde le tout dans la base mysql
	$.ajax({
		type: "POST", url: "ajax.php",
		data: "what=save_diff&id="+id+"&diff="+nouveau_diff+"&com="+nouveau_com,
		success: function(msg){ if (msg) alert(msg); }
	});

	//on r�affiche les valeurs
	diff_element.innerHTML	= nouveau_diff+'&euro;';
	com_element.innerHTML	= nouveau_com;
	edit_element.innerHTML	= '<img src="../../gfx/edit_mini.gif" onclick="edit_fact('+id+');" title="Modifier la ligne" />';

	// on met la ligne de la bonne couleur en fonction de la diff�rence
	$('#ligne_'+id).removeClass('positif negatif');
	$('#ligne_'+id).addClass( nouveau_diff >= 0 ? 'positif':'negatif');
}


function trim (myString) {
	return myString.replace(/^\s+/g,'').replace(/\s+$/g,'');
}
//-->
</script>

</head>
<body>
<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>
<br/>

<!-- dialog pour faire le choix entre plusieurs fournisseur -->
<div id="choix-fournisseur">
	<strong>Plusieurs fournisseurs correspondent � cette facture.<br>
	Choisissez le bon :</strong><br><br>
</div>

<center>

<? if ($message) { // affichage d'un message de traitement ?>
	<div style="background:red;color:white;font-weight:bold;width:50%;"><?=$message?></div>
<? } ?>

<form name="add_cde" method="POST" action="index.php" onsubmit="return verif_champs();">
	<input type="hidden" name="what" value="" />
	<input type="hidden" name="montant_cde" value="" />
	<input type="hidden" name="commentaire" value="" />
	<input type="hidden" name="fournisseur" value="" />

	<fieldset style="width:50%;text-align:center;">
		<legend>Ajouter une diff�rence de facturation fournisseur</legend>
		N� de facture fournisseur : <input type="text" name="no_fact" value="" size="8" />
		<input type="button" value="Valider" class="button valider" onclick="verif_champs();"/>
		<img id="loading" src="gfx/loading4.gif" style="visibility:hidden;"/>
	</fieldset>



<!-- AFFICHAGE DES SURVEILLANCES FACTURES -->
<table id="diff">
		<caption>Diff&eacute;rence de factures fournisseur</caption>
		<tr>
			<th>Date ctrl.</th>
			<th>Fournisseur</th>
			<th>N� Cde</th>
			<th>N� Fact</th>
			<th>Mt pr�vu</th>
			<th>Mt r&eacute;el</th>
			<th>Diff</th>
			<th>Activ.</th>
			<th>Commentaire</th>
			<th>Qui</th>
			<th class="edit">&nbsp;</th>
			<th class="sup">&nbsp;</th>
		</tr>
<?
		$res = mysql_query("SELECT * FROM diff_cde_fourn ORDER BY id DESC") or die("Peux pas retrouver les lignes a surveiller : ".mysql_error());
		$ligne_a_surveiller = array();
		$ligne = array();
		while($row = mysql_fetch_array($res)) {
			$ligne[] = "(CONTROLE_FACTURE_ENTETE.CEFNU='$row[no_fact]' AND CONTROLE_FACTURE_ENTETE.CFAFOU='$row[code_fournisseur]')";
			$ligne_a_surveiller["$row[code_fournisseur]/$row[no_fact]"] = array($row['montant_cde'], $row['commentaire'], $row['id'] , $row['diff']);
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
select	HIBON,ACFLI
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFADEP1 CONTROLE_FACTURE_DETAIL
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFAMILP1 FAMILLE
				on CONTROLE_FACTURE_DETAIL.CFVC6=FAMILLE.AFCAC and AFCNI='ACT'
where		CFFNU='$row[CEFNU]'
		and CFAFOU='$row[CFAFOU]'
EOT;

//echo "<pre style='text-align:left;font-size:1.2em;'>$sql</pre>";
				$res_detail = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des d�tails factures ($sql)");
				$no_bon = array(); $montant_reel = 0; $activite = array();
				while($row_detail = odbc_fetch_array($res_detail)) {
					$no_bon[$row_detail['HIBON']] = 1;
					$activite[$row_detail['ACFLI']] = 1;
				}

				// on r�cupere les infos venant de MYSQL
				$mysql_mon  = $ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"][0];
				$mysql_com  = $ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"][1];
				$mysql_id	= $ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"][2];
				$mysql_diff = $ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"][3];

				 // on diff�rence a �t� enresgitr�e manuellement dans la base
				if ($mysql_diff)
					$diff = $mysql_diff;
				else
					$diff = (isset($ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"]) ? $mysql_mon:0) - $row['CEMON'] ;
?>
				<tr class="<?=$diff >= 0 ? 'positif':'negatif'?>" id="ligne_<?=$mysql_id?>">
					<td class="date"><?=$row['DATE_CONTROLE']?></td>
					<td class="fournisseur"><?=$row['NOMFO']?></td>
					<td class="cde"><?=join(", ",array_keys($no_bon))?></td>
					<td class="facture"><?=$row['CEFNU']?></td>
					<td class="prix"><?=isset($ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"]) ? $mysql_mon:'non saisie' ?></td>
					<td class="prix"><?=sprintf('%0.2f',$row['CEMON'])?>&euro;</td>
					<td class="prix <?=$diff >= 0 ? 'positif':'negatif'?> <?=$mysql_diff ? 'manuelle':''?>" id="diff_<?=$mysql_id?>" style="font-weight:bold;">
						<?=sprintf('%0.2f',$diff)?>&euro;
					</td>
					<td class="activite"><?=join("<br/>",array_keys($activite))?></td>
					<td class="commentaire" id="com_<?=$mysql_id?>">
						<?=isset($ligne_a_surveiller["$row[CFAFOU]/$row[CEFNU]"]) ? $mysql_com:'' ?>
					</td>
					<td class="qui"><?=$row['CENID']?></td>
					<td class="edit" id="edit_<?=$mysql_id?>">
						<img src="../../gfx/edit_mini.gif" onclick="edit_fact(<?=$mysql_id?>);" title="Modifier la ligne" />
					</td>
					<td class="sup"><img src="../../gfx/delete_micro.gif" onclick="del_fact(<?=$mysql_id?>);" title="Supprimer la ligne" /></td>
				</tr>
<?			}
		} ?>

</form>
</center>

</body>
</html>
<?
odbc_close($loginor);
mysql_close($mysql);
?>