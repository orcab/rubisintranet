<?

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

if (isset($_POST['action']) && $_POST['action'] == 'creation_article') { ///////// ENVOI DU MAIL DE CREATION DES ARTICLES
	require_once '../inc/xpm2/smtp.php';
	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR);
	$to = explode('/',$_POST['to']);
	$mail->AddTo($to[0], $to[1]) or die("Erreur d'ajour de destinataire");
	$mail->From($_POST['from']);

	$designation		= str_replace('"',"''",$_POST['designation']);		$designation	= str_replace(';',",",$designation);
	$designation2		= str_replace('"',"''",$_POST['designation2']);		$designation2	= str_replace(';',",",$designation2);
	$designation3		= str_replace('"',"''",$_POST['designation3']);		$designation3	= str_replace(';',",",$designation3);
	$ref_fournisseur	= str_replace('"',"''",$_POST['ref_fournisseur']);	$ref_fournisseur= str_replace(';',",",$ref_fournisseur);
	
	$html = <<<EOT
<table>
<tr><td>De la part de</td><td>$_POST[from]</td></tr>
<tr><td>Designation 1</td><td>$designation</td></tr>
<tr><td>Designation 2</td><td>$designation2</td></tr>
<tr><td>Designation 3</td><td>$designation3</td></tr>
<tr><td>Fournisseur</td><td>$_POST[fournisseur]</td></tr>
<tr><td>Ref Fournisseur</td><td>$ref_fournisseur</td></tr>
<tr><td>Gencode</td><td>$_POST[gencode]</td></tr>
<tr><td>Apparait sur le tarif</td><td>$_POST[on_tarif]</td></tr>
<tr><td>Px d'achat/public</td><td>$_POST[px_achat]</td></tr>
<tr><td>Remise</td><td>$_POST[remise]</td></tr>
<tr><td>Marge</td><td>$_POST[marge]</td></tr>
<tr><td>Px de vente</td><td>$_POST[px_vente]</td></tr>
<tr><td>Eco Taxe</td><td>$_POST[eco_taxe]</td></tr>
<tr><td>Servi sur stock</td><td>$_POST[stock]</td></tr>
<tr><td>Stock mini</td><td>$_POST[stock_mini]</td></tr>
<tr><td>Stock alerte</td><td>$_POST[stock_alerte]</td></tr>
<tr><td>Stock maxi</td><td>$_POST[stock_maxi]</td></tr>
<tr><td>Conditionnement</td><td>$_POST[conditionnement]</td></tr>
<tr><td>Divisible</td><td>$_POST[divisible]</td></tr>
<tr><td>Unité</td><td>$_POST[unite]</td></tr>
<tr><td>Zone de Prépa</td><td>$_POST[zone_prepa]</td></tr>
<tr><td>Activite</td><td>$_POST[activite]</td></tr>
<tr><td>Famille</td><td>$_POST[famille]</td></tr>
<tr><td>Sous famille</td><td>$_POST[sousfamille]</td></tr>
<tr><td>Chapitre</td><td>$_POST[chapitre]</td></tr>
<tr><td>Sous chapitre</td><td>$_POST[souschapitre]</td></tr>
<tr><td>Commentaire</td><td>$_POST[commentaire]</td></tr>
EOT;
	$mail->Html($html);
	$sent = $mail->Send("Creation article : $_POST[designation]");

	$titre = mysql_escape_string($_POST['designation']);
	$description = <<<EOT
$designation
$designation2
$designation3
Fournisseur : $_POST[fournisseur]
Reférence : $ref_fournisseur
Gencode : $_POST[gencode]
Sur Tarif ? : $_POST[on_tarif]
Px d'achat/public : $_POST[px_achat]
Remise : $_POST[remise]
Marge : $_POST[marge]
Px de vente : $_POST[px_vente]
Eco Taxe : $_POST[eco_taxe]
Servi sur stock : $_POST[stock]
Stock mini : $_POST[stock_mini]
Stock maxi : $_POST[stock_maxi]
Stock alerte : $_POST[stock_alerte]
Conditionnement : $_POST[conditionnement]
Divisible : $_POST[divisible]
Unité : $_POST[unite]
Zone de Prépa : $_POST[zone_prepa]
Activite : $_POST[activite]
Famille : $_POST[famille]
Sous famille : $_POST[sousfamille]
Chapitre : $_POST[chapitre]
Sous chapitre : $_POST[souschapitre]

Commentaire : $_POST[commentaire]
EOT;
	$description = mysql_escape_string($description);
	$res = mysql_query("INSERT INTO historique_article (titre,description,de_la_part,status,date_demande) VALUES ('$titre','$description','$_POST[from]',FALSE,now())") or die(mysql_error());

} // fin d'envoi du mail
?>
<html>
<head>
<title>Demande de création d'article</title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_article.ico" />
<style>
th.label {
	vertical-align:top;
	text-align:left;
}

td.valeur {
	width:70%;
}

select#completion_fourn {
	border:solid 1px #000080;
	display:none;
}
</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="JavaScript">
<!--

// transforme les champs de saisie en majuscule
function majusculize(champ) {
	document.creation_article.elements[champ].value = document.creation_article.elements[champ].value.toUpperCase();
}

// si article stocké --> affiche les champs de stock
function affiche_stock_mini_maxi() {
	if(document.creation_article.stock[0].checked) // stock a oui
		$('#stock_mini_maxi').show;
	else
		$('#stock_mini_maxi').hide;
}


// permet le passage de champs en champs dans la saisie des familles/sous familles
function compte_car(mon_objet) {
	if (mon_objet.value.length >= 3) {
		if			(mon_objet.name=='activite') // on passe au champs famille
			document.creation_article.famille.focus();
		else if	(mon_objet.name=='famille') // on passe au champs sousfamille
			document.creation_article.sousfamille.focus();
		else if	(mon_objet.name=='sousfamille') // on passe au champs chapitre
			document.creation_article.chapitre.focus();
		else if	(mon_objet.name=='chapitre') // on passe au champs sous chapitre
			document.creation_article.souschapitre.focus();
	}
}

// vérifie que les donnée obligatoire sont bien présentes
function envoi_formulaire() {
	var erreur = 0 ;
	if      (!document.creation_article.fournisseur.value) {
		alert("Veuillez saisir un FOURNISSEUR"); erreur = 1;
	}
	else if      (!document.creation_article.ref_fournisseur.value) {
		alert("Veuillez saisir une REFERENCE FOURNISSEUR"); erreur = 1;
	}
	else if      (document.creation_article.eco_taxe.value.length <= 0) {
		alert("Veuillez saisir une ECO TAXE (0 si aucune éco taxe sur le produit)"); erreur = 1;
	}
	else if      (!document.creation_article.marge.options[document.creation_article.marge.selectedIndex].value) {
		alert("Veuillez saisir une MARGE"); erreur = 1;
	}
	else if (!document.creation_article.zone_prepa.options[document.creation_article.zone_prepa.selectedIndex].value) {
		alert("Veuillez saisir une ZONE DE PREPA\n(même \"je ne sais pas\" s'il faut)"); erreur = 1;
	}

	if (!erreur) document.creation_article.submit();
}

///// AJAX ///////////////////////////////////


var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non supporté par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");


function affiche_aide(type) { // activite, famille, sousfamille, chapitre
	with (document.creation_article) {
		if		(type=='activite')
			$('#completion').load('ajax.php?what='+type);
		else if (type=='famille')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value));
		else if (type=='sousfamille')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value));
		else if (type=='chapitre')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value)+'/'+escape(sousfamille.value));
		else if (type=='souschapitre')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value)+'/'+escape(sousfamille.value)+'/'+escape(chapitre.value));
	}
}



// completion pour les fournisseurs
function complette_fourn(e) {
	var sel = document.creation_article.completion_fourn ;
	var nb_el = sel.options.length ;
	var selIndex = sel.selectedIndex ;

	if (!document.creation_article.fournisseur.value) {
		sel.style.display = 'none';
	}
	else if (e.keyCode == 40 && nb_el) { // fleche bas
		if (selIndex < sel.options.length - 1)
			sel.selectedIndex = selIndex + 1 ;
	}
	else if (e.keyCode == 38 && nb_el) { // fleche haut
		if (selIndex > 0)
			sel.selectedIndex = selIndex - 1 ;
	}
	else if (e.keyCode == 13 && nb_el) { // entrée
		document.creation_article.fournisseur.value = sel.options[selIndex].value ;
		sel.style.display = 'none';
	}
	else { // autre touche --> on recherche les fournisseurs
		val = document.creation_article.fournisseur.value ;
		if (val.length > 0) {
			http.open("GET", "ajax.php?what=complette_fourn&val="+escape(val), true);
			http.onreadystatechange = handleHttpResponse_complette_fourn;
			http.send(null);
		}
	}
}

function handleHttpResponse_complette_fourn()
{
	if (http.readyState == 4)
	{	fournisseurs = eval('(' + http.responseText + ')'); // [id1,id2, ...]
		document.getElementById('completion_fourn').attributes['size'].value = fournisseurs.length;
		sel = document.creation_article.completion_fourn ;

		// on vide le select
		while(sel.options.length > 0)
			sel.options[0] = null

		// on rempli avec les nouveaux fournisseurs
		for(i=0 ; i<fournisseurs.length ; i++)
			sel.options[sel.options.length] = new Option(fournisseurs[i],fournisseurs[i]);

		if (sel.options.length) {
			sel.selectedIndex = 0 ; // on selection le premier element de la liste
			sel.style.display = 'block';
		}
		else
			sel.style.display = 'none';
	}	
}


function complette_fourn_click() {
	sel = document.creation_article.completion_fourn ;
	document.creation_article.fournisseur.value = sel.options[sel.selectedIndex].value ;
	sel.style.display = 'none';
}

// vérifie si la référence fournisseur n'existe pas déjà
function check_ref_fournisseur() {
	with (document.creation_article) {
		if (fournisseur.value && ref_fournisseur.value)
			http.open('GET', 'ajax.php?what=check_ref_fournisseur&val='+escape(fournisseur.value)+'/'+escape(ref_fournisseur.value), true);
	}

	http.onreadystatechange = function() {
		if (http.readyState == 4 && http.responseText) {
			alert("Attention, cette référence fournisseur existe déjà.\n"+http.responseText);
		}
	};
	http.send(null);
}


//-->
</script>

</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<table>
<tr>
<td style="width:65%;vertical-align:top;">

<form method="post" action="creation_article.php" name="creation_article">
<input type="hidden" name="action" value="creation_article">
<table style="width:100%;border:solid 2px black;padding:5px;">

<tr><th class="label" style="color:red;font-weight:bold;">Envoyer à :</th>
<td class="valeur">
<select name="to" style="color:red;">
<?		foreach ($CREATION_ARTICLE as $tmp) { ?>
			<option value="<?=$tmp['email']?>/<?=$tmp['nom']?>"><?=$tmp['nom']?></option>
<?		} ?>
</select><br><br></td></tr>

<tr><th class="label">De la part de :</th>
<td class="valeur">
<select name="from">
	<? $res  = mysql_query("SELECT * FROM employe WHERE printer=0 and prenom NOT LIKE '%serveur%' ORDER BY prenom ASC");
		while ($row = mysql_fetch_array($res)) { ?>
		<option value="<?=$row['email']?>"<?= $_SERVER['REMOTE_ADDR']==$row['ip'] ? ' selected':''?>><?=$row['prenom']?></option>
	<? } ?>
</select></td></tr>

<tr><th class="label">Designation :</th><td class="valeur"><input type="text" name="designation" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label">Designation 2 :</th><td class="valeur"><input type="text" name="designation2" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label">Designation 3 :</th><td class="valeur"><input type="text" name="designation3" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label">Fournisseur :</th><td class="valeur"><input type="text" name="fournisseur" value="" onkeyup="complette_fourn(event);" autocomplete="off" onblur="majusculize(this.name);"><br/>
<select id="completion_fourn" name="completion_fourn" size="1" onclick="complette_fourn_click();"></select>
</td></tr>
<tr><th class="label">Ref Fournisseur :</th><td class="valeur"><input type="text" name="ref_fournisseur" value="" onblur="majusculize(this.name);check_ref_fournisseur();"></td></tr>
<tr><th class="label">Gencode :</th><td class="valeur"><input type="text" name="gencode" value="" size="13" maxlength="13" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label">Apparait sur tarif :</th><td class="valeur">Oui<input type="radio" name="on_tarif" value="oui">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Non<input type="radio" name="on_tarif" value="non" checked><br><br></td></tr>
<tr><th class="label">Px d'achat/public :</th>
	<td class="valeur"><input type="text" name="px_achat" value="">&nbsp;&nbsp;&nbsp;&nbsp;
	Eco Taxe <input type="text" name="eco_taxe" value="" size="3">
	</td>
</tr>
<tr><th class="label">Remise :</th><td class="valeur"><input type="text" name="remise" value=""></td></tr>
<tr><th class="label"></th><td class="valeur">
	Marge : 
	<select name="marge">
		<option value=""></option>
		<option value="10% (coef 1.11111)">10% (Electromenager)</option>
		<option value="12% (coef 1.13636)">12%</option>
		<option value="15% (coef 1.17647)">15%</option>
		<option value="17% (coef 1.20482)">17% (Elec)</option>
		<option value="18% (coef 1.21951)">18%</option>
		<option value="20% (coef 1.25000)">20% (Outilage)</option>
		<option value="25% (coef 1.33000)">25%</option>
		<option value="Autre (voir Px de Vente)">Autre (préciser)</option>
	</select> PV : <input type="text" name="px_vente" value="" size="6"><br><br></td></tr>

<tr><th class="label">Stock :</th><td class="valeur">Oui<input type="radio" name="stock" value="oui" onclick="affiche_stock_mini_maxi();">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Non<input type="radio" name="stock" value="non" onclick="affiche_stock_mini_maxi();" checked>
<div id="stock_mini_maxi" style="display:none;">
Stock mini : <input type="text" name="stock_mini" value="" size="5"><br>
Stock maxi : <input type="text" name="stock_maxi" value="" size="5"><br>
Stock alerte : <input type="text" name="stock_alerte" value="" size="5">
</div>
</td></tr>
<tr><th class="label">Conditionnement :</th><td class="valeur"><input type="text" name="conditionnement" value="1" size="5"></td></tr>
<tr><th class="label">Divisible :</th><td class="valeur">Oui<input type="radio" name="divisible" value="oui">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Non<input type="radio" name="divisible" value="non" checked></td></tr>
<tr><th class="label">Unité de vente :</th><td class="valeur">
	<select name="unite">
		<option value="Boite (BTE)">Boite</option>
		<option value="Cent (CEN)">Cent</option>
		<option value="Colis (COL)">Colis</option>
		<option value="Heures (HEU)">Heures</option>
		<option value="Kilo (KG)">Kilo</option>
		<option value="Litre (L)">Litre</option>
		<option value="Mille (MIL)">Mille</option>
		<option value="Mètre linéaire (ML)">Mètre linéaire</option>
		<option value="Mètre carée (M2)">Mètre carée</option>
		<option value="Mètre cube (M3)">Mètre cube</option>
		<option value="Pièce (PCE)">Pièce</option>
		<option value="Plaque (PLA)">Plaque</option>
		<option value="Sac (SAC)">Sac</option>
		<option value="Tonne (TON)">Tonne</option>
		<option value="Unité (UN)" selected>Unité</option>
	</select>
<br><br></td></tr>

<tr>
	<th class="label">Zone de Prépa :</th>
	<td class="valeur">
		<select name="zone_prepa">
			<option value=""> --- </option>
			<option value="saispas">Je ne sais pas</option>
			<optgroup label="Magasin">
				<option value="MCH">Chauffage</option>
				<option value="MEL">Electricité</option>
				<option value="MSA">Sanitaire</option>
				<option value="MPL">Plomberie</option>
			</optgroup>
			<optgroup label="Dépot">
				<option value="DCH">Chauffage</option>
				<option value="DEL">Electricité</option>
				<option value="DSA">Sanitaire</option>
				<option value="DPL">Plomberie</option>
			</optgroup>
			<optgroup label="Mezanine">
				<option value="PCH">Chauffage</option>
				<option value="PEL">Electricité</option>
				<option value="PSA">Sanitaire</option>
				<option value="PPL">Plomberie</option>
			</optgroup>
			<option value="EXT">Extérieur</option>
			<option value="TOU">Touret</option>
		</select>
	</td>
</tr>

<tr>
	<th class="label">Activité :</th>
	<td class="valeur"><input type="text" name="activite" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('activite');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label">Famille :</th>
	<td class="valeur"><input type="text" name="famille" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('famille');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label">Sous famille :</th>
	<td class="valeur"><input type="text" name="sousfamille" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('sousfamille');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label">Chapître :</th>
	<td class="valeur"><input type="text" name="chapitre" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('chapitre');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label">Sous chapître :</th>
	<td class="valeur"><input type="text" name="souschapitre" value="" size="3" maxlength="3" onfocus="affiche_aide('souschapitre');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th colspan="2"><input type="button" class="button valider" style="background-image:url(../js/boutton_images/email.gif);" onclick="envoi_formulaire();" value="Envoyer la demande"></td>
</tr>
<tr>
	<th class="label">Commentaire :<br><small>("urgent" par exemple)</small></th>
	<td class="valeur"><textarea type="text" name="commentaire" rows="3" cols="40"></textarea></td>
</tr>
</table>
</td>

<td id="completion" style="vertical-align:top;border:solid 2px black;padding:5px;">
<?
	if (isset($sent)) { // un a tente d'envoyé un mail
		if ($sent) { // le mail est bien parti
?>
<div style="background-color:#0000BB;color:white;text-align:center;padding:15px;">Votre demande a été envoyé</div>
<?		} else { // erreur dans l'envoi
?>
<div style="background-color:#BB0000;color:white;text-align:center;padding:15px;">Une erreur est survenu lors de l'envoi de la demande</div>
<?		}
	}
?>
</td>
</tr>
</table>

</form>
</body>
</html>
<?
mysql_close($mysql);
?>