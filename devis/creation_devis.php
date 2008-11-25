<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if (!(recuperer_droit() & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}


// mode creation ou modification ?
$id = isset($_GET['id']) ? $_GET['id'] : '' ;


if (!$id) {
	// on demande un nouveau devis. On crée un dummy dans la base et on renvoi le numero
	$representant = e('prenom',mysql_fetch_array(mysql_query("SELECT * FROM employe WHERE droit & $PEUT_CREER_DEVIS AND ip='$_SERVER[REMOTE_ADDR]'")));
	$res = mysql_query("INSERT INTO devis (id,`date`,representant,theme) VALUES (id,NOW(),'".mysql_escape_string($representant)."','devis')");
	$id = mysql_insert_id($mysql) ;
}


// recherche du l'entete du devis si en modification
if($id) { // modif
	$res_devis = mysql_query("SELECT *,DATE_FORMAT(`date`,'%d/%m/%Y') AS date_formater,DATE_FORMAT(`date`,'%H:%i') AS heure,DATE_FORMAT(date_maj,'%d/%m/%Y') AS date_maj_formater,CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero FROM devis WHERE id=$id LIMIT 0,1") or die("Requete impossible ".mysql_error()) ;
	$row_devis = mysql_fetch_array($res_devis);
}

// DEMANDE DE MAJ DES PRIX DU DEVIS VIA LA BASE ARTICLE
if (isset($_GET['action']) && $_GET['action']=='update_price') {
	$res_ligne_devis = mysql_query("SELECT id,ref_fournisseur,fournisseur,puht FROM devis_ligne WHERE id_devis=$id") or die("Impossible de récupérer les lignes du devis pour la MAJ des prix");

	while($row = mysql_fetch_array($res_ligne_devis)) { // pour chaque ligne du devis, on regarde si le prix n'a pas changé
		if ($row['ref_fournisseur'] && $row['fournisseur']) {
			$res_article = mysql_query("SELECT (prix_public_ht - (prix_public_ht * (remise / 100))) AS prix_remise FROM devis_article WHERE ref_fournisseur='".mysql_escape_string($row['ref_fournisseur'])."' AND fournisseur='".mysql_escape_string($row['fournisseur'])."' LIMIT 0,1") or die("Impossible de récupérer l'article dans la base de connaissance (".mysql_error().")");

			if (mysql_num_rows($res_article) >= 1) { // si l'on a un article
				$prix_remise = e('prix_remise',mysql_fetch_array($res_article));
				if ($prix_remise != $row['puht']) { // le prix est différent, on fait une MAJ du prix
					mysql_query("UPDATE devis_ligne SET puht='$prix_remise' WHERE id=$row[id]") or die("Impossible de mettre à jour le prix pour une ligne de devis (".mysql_error().") SQL :<br>\nUPDATE devis_ligne SET puht='$prix_remise' WHERE id=$row[id]");
				}
			}
		}
	}
}

?>
<html>
<head>
<title><?= $id ? "Modification du $row_devis[numero]" : "Création de devis" ?></title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_devis.ico" />

<style>
a img { border:none; }

input,textarea { border:solid 2px #AAA; }

table#article { border-collapse:collapse; }

table#article td { border-bottom:solid 1px black; border-left:solid 1px grey; }

table#article th { border:solid 1px black;background-color:#DDD; }
table#article th.numero { width:3%; }
table#article th.reference { width:10%; }
table#article th.fournisseur { width:10%; }
table#article th.qte { <?= $id && substr($row_devis['theme'],0,5)=='gamme' ? 'visibility:hidden;' : '' ?> width:5%; }
table#article th.pu { width:10%; }
table#article th.pt { <?= $id && substr($row_devis['theme'],0,5)=='gamme' ? 'visibility:hidden;' : '' ?> width:10%; }
table#article th.stock { <?= !($id && substr($row_devis['theme'],0,5)=='gamme') ? 'visibility:hidden;' : '' ?> width:5%; }
table#article th.expo { <?= !($id && substr($row_devis['theme'],0,5)=='gamme') ? 'visibility:hidden;' : '' ?> width:5%; }

table#article td.numero { width:3%; text-align:center; font-weight:bold; }
table#article td.reference { width:10%; vertical-align:top; }
table#article td.fournisseur { width:10%; vertical-align:top; }
table#article td.designation { vertical-align:top; }
table#article td.qte { <?= $id && substr($row_devis['theme'],0,5)=='gamme' ? 'visibility:hidden;' : '' ?> width:5%; text-align:center; vertical-align:top; }
table#article td.pu { width:10%; text-align:right; vertical-align:top; }
table#article td.pt { <?= $id && substr($row_devis['theme'],0,5)=='gamme' ? 'visibility:hidden;' : '' ?> width:10%; text-align:right; vertical-align:top;}
table#article td.maj { text-align:center; vertical-align:top;}
table#article td.stock { <?= !($id && substr($row_devis['theme'],0,5)=='gamme') ? 'visibility:hidden;' : '' ?> width:5%; text-align:center; vertical-align:top; }
table#article td.expo {  <?= !($id && substr($row_devis['theme'],0,5)=='gamme') ? 'visibility:hidden;' : '' ?> width:5%; text-align:center; vertical-align:top; }

table#article td.total { width:10%; text-align:right; vertical-align:top; border-right:solid 1px black;}
</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../js/jscalendar/calendar-setup.js"></script>
<SCRIPT LANGUAGE="JavaScript" src="base64.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">
<!--

function update_price() {
	if (confirm("Voulez-vous vraiment mettre à jour les prix pour ce devis ?"))
		document.location.href = 'creation_devis.php?id=<?=$id?>&action=update_price' ;
}

function confirm_delete(id,numero) {
	if (confirm("Voulez-vous vraiment supprimer le devis "+numero+" et tous ses articles ?"))
		document.location.href = 'historique_devis.php?action=delete&id=' + id ;
}

function envoi_formulaire(option) {
	document.creation_devis.les_options.value=option ;
	document.creation_devis.submit();
}

function zone_active(champs) {
	champs.style.backgroundColor='#FFFFAA';
}
function zone_inactive(champs) {
	champs.style.backgroundColor='white';
}

function affiche_adherent() {
	if (document.creation_devis.artisan_nom.options[document.creation_devis.artisan_nom.selectedIndex].value != 'NON Adherent')
		document.getElementById('artisan_nom_libre').style.visibility = 'hidden';
	else
		document.getElementById('artisan_nom_libre').style.visibility = 'visible';
}

function switch_devis_gamme() {
	if (document.creation_devis.devis_theme.options[document.creation_devis.devis_theme.selectedIndex].value.substr(0,5) == 'gamme') {
		// on doit afficher les block expo et stock
		for (i=0; i<document.getElementsByTagName('*').length; i++) {
			if (document.getElementsByTagName('*').item(i).className == 'expo' || document.getElementsByTagName('*').item(i).className == 'stock'){
				document.getElementsByTagName('*').item(i).style.visibility='visible';
			} else if (document.getElementsByTagName('*').item(i).className == 'qte' || document.getElementsByTagName('*').item(i).className == 'pt') {
				document.getElementsByTagName('*').item(i).style.visibility='hidden';
			}
		}
	} else if (document.creation_devis.devis_theme.options[document.creation_devis.devis_theme.selectedIndex].value.substr(0,5) == 'devis') {
		// on doit afficher les qte et pt
		for (i=0; i<document.getElementsByTagName('*').length; i++) {
			if (document.getElementsByTagName('*').item(i).className == 'qte' || document.getElementsByTagName('*').item(i).className == 'pt'){
				document.getElementsByTagName('*').item(i).style.visibility='visible';
			} else if (document.getElementsByTagName('*').item(i).className == 'expo' || document.getElementsByTagName('*').item(i).className == 'stock') {
				document.getElementsByTagName('*').item(i).style.visibility='hidden';
			}
		}
	}
}

function ajoute_ligne(ligne) {
	with(document.creation_devis) {
		if (elements['a<?=NOMBRE_DE_LIGNE?>_reference'].value) {
			alert("Trop de ligne dans le devis. On ne peux pas en rajouter");
			return 0;
		}

		for(var j=<?=NOMBRE_DE_LIGNE?> ; j>ligne ; j--) {
			if (j > 1) {
				elements['a'+j+'_reference'].value		= elements['a'+(j-1)+'_reference'].value;
				elements['a'+j+'_code'].value			= elements['a'+(j-1)+'_code'].value;
				elements['a'+j+'_fournisseur'].value	= elements['a'+(j-1)+'_fournisseur'].value;
				elements['a'+j+'_designation'].value	= elements['a'+(j-1)+'_designation'].value;
				elements['a'+j+'_puht'].value			= elements['a'+(j-1)+'_puht'].value;
				elements['a'+j+'_qte'].value			= elements['a'+(j-1)+'_qte'].value;
				if (elements['a'+j+'_reference'].value) calcul_ptht(j);				
			}
		}

		elements['a'+parseInt(ligne+1)+'_reference'].value		= '';
		elements['a'+parseInt(ligne+1)+'_code'].value			= '';
		elements['a'+parseInt(ligne+1)+'_fournisseur'].value	= '';
		elements['a'+parseInt(ligne+1)+'_designation'].value	= '';
		elements['a'+parseInt(ligne+1)+'_puht'].value			= '';
		elements['a'+parseInt(ligne+1)+'_qte'].value			= '';
		calcul_ptht(parseInt(ligne+1));
	}
}


function calcul_ptht(idx) {
	// REMPLACE LES ',' par des '.' pour le calcule
	document.creation_devis.elements['a'+idx+'_puht'].value = document.creation_devis.elements['a'+idx+'_puht'].value.replace(',','.');
	
	// CALCUL LE PRIX TOTAL DE LA LIGNE
	var puht  = document.creation_devis.elements['a'+idx+'_puht'].value ;
	var qte  = document.creation_devis.elements['a'+idx+'_qte'].value ;
	
	document.getElementById('a'+idx+'_ptht').innerHTML = qte ? Math.round(qte * puht * 100)/100 : '';

	// CALCULE LE PRIX TOTAL DU DEVIS
	var prix_total_ht = 0 ;
	for(i=1 ; i<=<?=NOMBRE_DE_LIGNE?> ; i++) {
		if (document.creation_devis.elements['a'+i+'_qte'].value)
			prix_total_ht += document.creation_devis.elements['a'+i+'_qte'].value * document.creation_devis.elements['a'+i+'_puht'].value ;
	}
	document.getElementById('devis_ptht').innerHTML   = Math.round(prix_total_ht * 100)/100;
	document.getElementById('devis_ptttc1').innerHTML = Math.round((prix_total_ht + prix_total_ht * <?=TTC1?> / 100)*100)/100;
	document.getElementById('devis_ptttc2').innerHTML = Math.round((prix_total_ht + prix_total_ht * <?=TTC2?> / 100)*100)/100;

	//window.status = "traite "+idx ;
}

function check_si_prix_null(idx) {
		if (document.creation_devis.elements['a'+idx+'_puht'].value <= 0 && document.creation_devis.elements['a'+idx+'_reference'].value)
			document.getElementById('tr'+idx).style.backgroundColor = 'red';
		else
			document.getElementById('tr'+idx).style.backgroundColor = 'white';
}

///// AJAX ///////////////////////////////////

var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non supporté par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

var old_reference = new Array(<?=NOMBRE_DE_LIGNE?>);

function cherche_reference(idx) {
	with(document.creation_devis) {
		reference = elements['a'+idx+'_reference'].value;

		if (reference != old_reference[idx]) { // si'il y a eu un changement dans la valeur de la référence
			if (reference) {
				http.open('GET','ajax.php?what=complette_via_ref&val='+escape(reference), true);
			} else {
				with(document.creation_devis) {
					elements['a'+idx+'_code'].value			= '';
					elements['a'+idx+'_fournisseur'].value	= '';
					elements['a'+idx+'_designation'].value	= '';
					elements['a'+idx+'_puht'].value			= '';
					elements['a'+idx+'_qte'].value			= '' ;

					calcul_ptht(idx); // initialisation
					old_reference[idx] = elements['a'+idx+'_reference'].value;
				}
			}
			
			http.onreadystatechange = function() {
				if (http.readyState == 4 && http.responseText) {
					var result = eval('(' + decode64(http.responseText) + ')') ;
					//alert(decode64(http.responseText));
					//alert(http.responseText);
					with(document.creation_devis) {
						elements['a'+idx+'_code'].value			= result[0];
						elements['a'+idx+'_fournisseur'].value	= result[1];
						elements['a'+idx+'_designation'].value	= result[2].replace(/(\{CR\})+/ig,"\n").replace(/&#[0-9]{1,3};/,"");
						elements['a'+idx+'_puht'].value			= result[3];
						
						if (!elements['a'+idx+'_qte'].value) { // mettre la qte a 1 par defaut
							elements['a'+idx+'_qte'].value = '1' ;
						}

						check_si_prix_null(idx);
						calcul_ptht(idx); // initialisation
						old_reference[idx] = elements['a'+idx+'_reference'].value;
					}
				}
			};
			http.send(null);
		} // fin old_reference != reference
	} // fin with
	return 1;
} // fin function


function affiche_hausse() {
	document.getElementById('hausse').innerHTML = '<input type="text" size="5" name="hausse">% <input type="button" onclick="applique_hausse();" value="Appliquer">';
}

function applique_hausse() {
	document.creation_devis.action='creation_devis.php?<?=$_SERVER['QUERY_STRING']?>' ;
	document.creation_devis.submit();
}

//-->
</SCRIPT>

</head>
<body>

<form method="post" action="generation_devis_pdf.php" name="creation_devis">
<input type="hidden" name="les_options" value="" />

<table style="margin-bottom:10px;width:100%;"><tr>
	<td style="width:20%;">
		<input type="button" class="button divers hide_when_print" style="background-image:url(/intranet/gfx/list.gif);margin-bottom:4px;" onclick="document.location.href='historique_devis.php';" value="Voir l'historique des devis">
	</td>
	<td>
		<input type="button" class="button divers hide_when_print" style="background-image:url(gfx/update.gif);margin-bottom:4px;" onclick="update_price();" value="Mettre à jour les prix du devis">
	</td>
</tr></table>

<input type="hidden" name="id" value="<?= $id ? $id : '' ?>"><!-- mode modification -->

<table style="width:100%;border:solid 1px black;">
<tr>
	<td style="width:10%;">Représentant</td>
	<td>
		<select name="artisan_representant" TABINDEX="1">
<?			$res  = mysql_query("SELECT * FROM employe WHERE droit & $PEUT_CREER_DEVIS ORDER BY prenom ASC");
			while ($row = mysql_fetch_array($res)) {
				if ($id) { //modif ?>
					<option value="<?=$row['prenom']?>"<?= $row_devis['representant']==$row['prenom'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['prenom']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?></option>
<?				}	
			} ?>
		</select>
	</td>
	<td>Client</td>
	<td>Nom</td>
	<td><input type="text" name="client_nom" size="45" TABINDEX="6" value="<?= $id ? $row_devis['nom_client']: ''?>"></td>
</tr>
<tr>
	<td>Artisan</td>
	<td>
		<select name="artisan_nom" onchange="affiche_adherent();" TABINDEX="2">
			<option value="NON Adherent">Artisan NON ADHERENT</option>
<?			$res  = mysql_query("SELECT nom FROM artisan WHERE suspendu=0 ORDER BY nom ASC");
			$a_trouve_artisan = FALSE ;
			while ($row = mysql_fetch_array($res)) {
				if ($id) { //modif ?>
					<option value="<?=$row['nom']?>"<? if ($row_devis['artisan']==$row['nom']) { echo ' selected'; $a_trouve_artisan = TRUE ; } ?>><?=$row['nom']?></option>
<?				} else { // creation ?>
					<option value="<?=$row['nom']?>"><?=$row['nom']?></option>
<?				}	
			} ?>
		</select><input id="artisan_nom_libre" <?= $a_trouve_artisan ? 'style="visibility:hidden;"' : ''?> type="text" name="artisan_nom_libre" value="<?= $id && !$a_trouve_artisan ? $row_devis['artisan']:''; ?>">
	</td>
	<td></td>
	<td>Adresse (ligne 1)</td>
	<td><input type="text" name="client_adresse" size="45" TABINDEX="7" value="<?= $id ? $row_devis['adresse_client']: ''?>"></td>
</tr>
<tr>
	<td>Modèle de devis</td>
	<td>
		<select name="devis_theme" TABINDEX="3" onchange="switch_devis_gamme();">
			<option value="devis"<?=isset($row_devis['theme']) && $row_devis['theme']=='devis'? ' selected':''?>>Devis</option>
			<option value="devis_avec_entete"<?= isset($row_devis['theme']) && $row_devis['theme']=='devis_avec_entete'? ' selected':''?>>Devis (avec entête)</option>
			<option value="gamme_avec_entete"<?= isset($row_devis['theme']) && $row_devis['theme']=='gamme_avec_entete'? ' selected':''?>>Gamme (avec entête)</option>
			<option value="gamme"<?= isset($row_devis['theme']) && $row_devis['theme']=='gamme'? ' selected':''?>>Gamme</option>
		</select>
	</td>
	<td></td>
	<td>Adresse (ligne 2)</td>
	<td><input type="text" name="client_adresse2" size="45" TABINDEX="8" value="<?= $id ? $row_devis['adresse_client2']: ''?>"></td>
</tr>
<tr>
	<td>Date</td>
	<td nowrap>

		<input type="text" id="devis_date" name="devis_date" value="<?= $id ? $row_devis['date_formater'] : date('d/m/Y')?>" size="8">
		<button id="trigger" style="background:url('../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button>
		<script type="text/javascript">
		  Calendar.setup(
			{
			  inputField	: 'devis_date',         // ID of the input field
			  ifFormat		: '%d/%m/%Y',    // the date format
			  button		: 'trigger',       // ID of the button
			  date			: '<?= $id ? $row_devis['date_formater'] : date('d/m/Y')?>',
			  firstDay 	: 1
			}
		  );
		</script>

	Heure <input type="text" name="devis_heure" size="5" maxlength="5" value="<?= $id ? $row_devis['heure'] : date('G:i')?>" TABINDEX="5"></td>
	<td></td>
	<td>Code Postal / Ville</td>
	<td>
		<input type="text" name="client_codepostal" size="5" maxsize="5" TABINDEX="9" value="<?= $id ? $row_devis['codepostal_client']: ''?>">
		<input type="text" name="client_ville" size="35" TABINDEX="10" value="<?= $id ? $row_devis['ville_client']:''?>">
	</td>
</tr>
<tr>
	<td><?= $id ? 'Devis N°' :'' ?></td>
	<td><? if($id) { ?>
			<?=$row_devis['numero']?> <a href="javascript:confirm_delete('<?=$id?>','<?=$row_devis['numero']?>');" style="border:none;"><img src="/intranet/gfx/delete_micro.gif" alt="Suppression" align="absmiddle"></a>
<?		} ?>
	</td>
	<td></td>
	<td>Tél / Mobile</td>
	<td>
		<input type="text" name="client_telephone" TABINDEX="11" value="<?= $id ? $row_devis['tel_client']: ''?>">
		<input type="text" name="client_telephone2" TABINDEX="12" value="<?= $id ? $row_devis['tel_client2']: ''?>">
	</td>
</tr>
<tr>
	<td></td>
	<td></td>
	<td></td>
	<td>Email</td>
	<td>
		<input type="text" name="client_email" TABINDEX="13" value="<?= $id ? $row_devis['email_client']: ''?>" size="45">
	</td>
</tr>
</table>

<!-- LISTE DES ARTICLE DU DEVIS -->

<br>
<table id="article" style="width:100%;border:solid 1px black;">
<tr>
	<th class="numero">N°
		<br><img src="gfx/plus.gif" onclick="ajoute_ligne('0');" style="margin-top:0px;margin-right:4px;" title="Ajoute une ligne">
	</th>
	<th class="reference">Référence</th>
	<th class="fournisseur">Fournisseur</th>
	<th class="designation">Désignation</th>
	<th class="qte">Qte</th>
	<th class="pu">P.U.<sup style="font-size:10px;">ht</sup></th>
	<th class="pt">P.T.<sup style="font-size:10px;">ht</sup></th>
	<th class="maj">MAJ</th>
	<th class="stock">Stock</th>
	<th class="expo">Expo</th>
</tr>
<? 
if ($id) { // modification
	$res_ligne_devis = mysql_query("SELECT * FROM devis_ligne WHERE id_devis=$id ORDER BY id ASC");
	$nb_ligne_devis  = mysql_num_rows($res_ligne_devis);
}
for($i=1;$i<=NOMBRE_DE_LIGNE;$i++) {
	$row_ligne_devis = array() ;
	if ($id && $i<=$nb_ligne_devis	) { // en modif on a deja une ligne de renseignée
		$row_ligne_devis = mysql_fetch_array($res_ligne_devis);
		$row_ligne_devis['ptht'] = $row_ligne_devis['qte'] * $row_ligne_devis['puht'];
	}
?>
<tr id="tr<?=$i?>" <?=isset($row_ligne_devis['ref_fournisseur']) && $row_ligne_devis['puht'] <= 0 ? " style='background-color:red;'":''?>>
	<td class="numero" valign="top">
		<?=$i?>
		<br><img src="gfx/plus.gif" onclick="ajoute_ligne(<?=$i?>);" style="margin-top:30px;margin-right:4px;" title="Ajoute une ligne">
		<input type="hidden" name="a<?=$i?>_code" value="<?= $id ? $row_ligne_devis['code_article'] : ''?>">
	</td>
	<td class="reference">
		<input type="text" name="a<?=$i?>_reference"	onblur="zone_inactive(this);cherche_reference('<?=$i?>');"
														onfocus="zone_active(this);"
														value="<?= isset($row_ligne_devis['ref_fournisseur']) ? $row_ligne_devis['ref_fournisseur'] : ''?>">

	</td>
	<td class="fournisseur">
		<input type="text" name="a<?=$i?>_fournisseur"	onblur="zone_inactive(this);" 
														onfocus="zone_active(this);"
														value="<?= isset($row_ligne_devis['fournisseur']) ? $row_ligne_devis['fournisseur'] : ''?>">
	</td>
	<td class="designation">
		<textarea  name="a<?=$i?>_designation"   rows="3" cols="40" onblur="zone_inactive(this);"
																	onfocus="zone_active(this);"><?= isset($row_ligne_devis['designation']) ? eregi_replace("(\{CR\})+","\n",$row_ligne_devis['designation']) : ''?></textarea>
	</td>
	<td class="qte">
		<input type="text" name="a<?=$i?>_qte"  size="2"	onkeyup="calcul_ptht('<?=$i?>',0);"
															onblur="zone_inactive(this);"
															onfocus="zone_active(this);"
															value="<?= isset($row_ligne_devis['qte']) ? $row_ligne_devis['qte'] : ''?>">
	</td>
	<td class="pu">
		<input  type="text" name="a<?=$i?>_puht" size="7"	onkeyup="calcul_ptht('<?=$i?>',0);"
															onblur="zone_inactive(this);check_si_prix_null(<?=$i?>);"
															onfocus="zone_active(this);"
															value="<?= isset($row_ligne_devis['puht']) ? $row_ligne_devis['puht'] : ''?>"> &euro;&nbsp;
<!--sanitaire-->
	<? if (PEUX_AFFICHER_PRIX_NET_EXPO) { ?>
		<br>Adh <input  type="text" name="a<?=$i?>_pu_adh_ht" size="7"
															onfocus="zone_active(this);"
															value="<?= isset($row_ligne_devis['pu_adh_ht']) ? $row_ligne_devis['pu_adh_ht'] : ''?>">
	<? } else { // fin sanitaire ?>
			<input type="hidden" name="a<?=$i?>_pu_adh_ht" value="<?= isset($row_ligne_devis['pu_adh_ht']) ? $row_ligne_devis['pu_adh_ht'] : ''?>">
	<? } ?>
	</td>
	<td class="pt">
		<div id="a<?=$i?>_ptht" style="display:inline;"><?= isset($row_ligne_devis['ptht']) ? sprintf('%0.2f',$row_ligne_devis['ptht']) : ''?></div> &euro;&nbsp;
	</td>
	<td class="maj">
		<input type="checkbox" name="a<?=$i?>_maj">
	</td>
	<td class="stock">
		<input type="checkbox" name="a<?=$i?>_stock"<?= isset($row_ligne_devis['stock']) && $row_ligne_devis['stock'] ? ' checked':'' ?>>
	</td>
	<td class="expo">
		<input type="checkbox" name="a<?=$i?>_expo"<?= isset($row_ligne_devis['expo']) && $row_ligne_devis['expo'] ? ' checked':'' ?>>
	</td>
</tr>
<? }

if ($id) { // modif
	$res_montant_devis = mysql_query("SELECT SUM(puht * qte) AS ptht,(SUM(puht * qte) + SUM(puht * qte) * ".TTC1."/100) AS ptttc1,(SUM(puht * qte) + SUM(puht * qte) * ".TTC2."/100) AS ptttc2  FROM devis_ligne WHERE id_devis=$id") or die("Requete impossible ".mysql_error()) ;
	$row_montant_devis = mysql_fetch_array($res_montant_devis);
}
?>

<tr>
	<td colspan="2"></td>
	<td colspan="3">Montant total HT</td>
	<td colspan="2" class="total"><div id="devis_ptht" style="display:inline;"><?= $id ? sprintf('%0.2f',$row_montant_devis['ptht']) : ''?></div> &euro;</td>
</tr>
<tr>
	<td colspan="2"></td>
	<td colspan="3">Montant total TTC (<?=TTC1?>%)</td>
	<td colspan="2" class="total"><div id="devis_ptttc1" style="display:inline;"><?= $id ? sprintf('%0.2f',$row_montant_devis['ptttc1']) : ''?></div> &euro;</td>
</tr>
<tr>
	<td colspan="2"></td>
	<td colspan="3">Montant total TTC (<?=TTC2?>%)</td>
	<td colspan="2" class="total"><div id="devis_ptttc2" style="display:inline;"><?= $id ? sprintf('%0.2f',$row_montant_devis['ptttc2']) : ''?></div> &euro;</td>
</tr>
</table>

<div style="text-align:center;margin-top:4px;"><input type="button" class="button valider pdf" value="Générer le devis" onclick="envoi_formulaire('');">&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button valider pdf" value="Générer le devis non chiffré" onclick="envoi_formulaire('non_chiffre');">
<!-- sanitaire-->
<? if (PEUX_AFFICHER_PRIX_NET_EXPO) { ?>
&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="button valider pdf" value="Générer prix ADH" onclick="envoi_formulaire('prix_adh');">
<? } //fin sanitaire ?>
</div>

</form>
</body>
</html>
<?
mysql_close($mysql);
?>