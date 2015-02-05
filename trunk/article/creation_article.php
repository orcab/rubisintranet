<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (isset($_POST['action']) && $_POST['action'] == 'creation_article') { ///////// ENVOI DU MAIL DE CREATION DES ARTICLES
	require_once '../inc/xpm2/smtp.php';
	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR,SMTP_USER,SMTP_PASS,(int)SMTP_PORT,'autodetect',SMTP_TLS_SLL ? SMTP_TLS_SLL:false);
	//$to = explode('/',$_POST['to']);
	//$mail->AddTo($to[0], $to[1]) or die("Erreur d'ajour de destinataire");
	//ajout des differents destinataires du tableau $CREATION_ARTICLE
	foreach ($CREATION_ARTICLE as $t) {
		//$to = explode('/',$t);
		$mail->AddTo($t['email'], $t['nom']) or die("Erreur d'ajour de destinataire");
	}
	$mail->From($_POST['from']);

	$designation		= str_replace('"',"''",$_POST['designation']);		$designation	= str_replace(';',",",$designation);
	$designation2		= str_replace('"',"''",$_POST['designation2']);		$designation2	= str_replace(';',",",$designation2);
	$designation3		= str_replace('"',"''",$_POST['designation3']);		$designation3	= str_replace(';',",",$designation3);
	$ref_fournisseur	= str_replace('"',"''",$_POST['ref_fournisseur']);	$ref_fournisseur= str_replace(';',",",$ref_fournisseur);
	
	$coef			= str_replace('.',',',sprintf("%.5f",(1/(100 - (int)$_POST['marge']))*100));
	$px_achat		= str_replace('.',',',$_POST['px_achat']);
	$px_achat_venir	= str_replace('.',',',$_POST['px_achat_venir']);
	$px_vente		= str_replace('.',',',$_POST['px_vente']);
	$eco_taxe		= str_replace('.',',',$_POST['eco_taxe']);

	$html = <<<EOT
<table>
<tr><td>De la part de</td><td>$_POST[from]<br/><br/></td></tr>

<tr><td>Designation 1</td><td>$designation</td></tr>
<tr><td>Designation 2</td><td>$designation2</td></tr>
<tr><td>Designation 3</td><td>$designation3</td></tr>
<tr><td>Fournisseur</td><td>$_POST[fournisseur]</td></tr>
<tr><td>Code fournisseur</td><td>$_POST[code_fournisseur]</td></tr>
<tr><td>Ref Fournisseur</td><td>$ref_fournisseur</td></tr>
<tr><td>Gencode</td><td>$_POST[gencode]<br/><br/></td></tr>

<tr><td>Px d'achat/public</td><td>$px_achat</td></tr>
<tr><td>Px d'achat à venir</td><td>$px_achat_venir</td></tr>
<tr><td>Date prix à venir</td><td>$_POST[date_achat_venir]</td></tr>
<tr><td>Remise1</td><td>$_POST[remise1]</td></tr>
<tr><td>Remise2</td><td>$_POST[remise2]</td></tr>
<tr><td>Remise3</td><td>$_POST[remise3]</td></tr>
<tr><td>Coef</td><td>$coef</td></tr>
<tr><td>Px de vente</td><td>$px_vente</td></tr>
<tr><td>Eco Taxe</td><td>$eco_taxe<br/><br/></td></tr>

<tr><td>Unité de vente/achat</td><td>$_POST[unite_vente]</td></tr>

<tr><td>Conditionnement d'achat</td><td>$_POST[conditionnement_achat]</td></tr>
<tr><td>Sur conditionnement d'achat</td><td>$_POST[sur_conditionnement_achat]</td></tr>
<tr><td>Type de conditionnement d'achat</td><td>$_POST[type_conditionnement_achat]</td></tr>

<tr><td>Stocké au dépôt</td><td>$_POST[stock]</td></tr>
<tr><td>Divisible</td><td>$_POST[divisible]</td></tr>
EOT;

	if ($_POST['divisible']=='non') { 	// vl10
		$html .= $_POST['conditionnement_vl10']			? "<tr><td>Contionnement : $_POST[conditionnement_vl10]</td></tr>":'';
		$html .= $_POST['unite_conditionnement_vl10']	? "<tr><td>Unité contionnement : $_POST[unite_conditionnement_vl10]</td></tr>":'';
	} else {							// vl20
		$html .= $_POST['conditionnement_vl20']			? "<tr><td>Contionnement : $_POST[conditionnement_vl20]</td></tr>":'';
		$html .= $_POST['unite_conditionnement_vl20']	? "<tr><td>Unité contionnement : $_POST[unite_conditionnement_vl20]</td></tr>":'';
	}

	$html .= <<<EOT
<tr><td>Sur conditionnement</td><td>$_POST[sur_conditionnement]</td></tr>
<tr><td>Unité sur conditionnement</td><td>$_POST[unite_sur_conditionnement]<br/><br/></td></tr>

<tr><td>Activite</td><td>$_POST[activite]</td></tr>
<tr><td>Famille</td><td>$_POST[famille]</td></tr>
<tr><td>Sous famille</td><td>$_POST[sousfamille]</td></tr>
<tr><td>Chapitre</td><td>$_POST[chapitre]</td></tr>
<tr><td>Sous chapitre</td><td>$_POST[souschapitre]<br/><br/></td></tr>

<tr><td>Commentaire</td><td>$_POST[commentaire]</td></tr>
EOT;

	$mail->Html($html);
	$sent = $mail->Send("Creation article : $_POST[designation]");

	$titre = mysql_escape_string($_POST['designation']);
	$description = '';
	$description .= $designation				? $designation."\n":'';
	$description .= $designation2				? $designation2."\n":'';
	$description .= $designation3				? $designation3."\n":'';
	$description .= $_POST['fournisseur']		? "Fournisseur : $_POST[fournisseur]\n":'';
	$description .= $_POST['code_fournisseur']	? "Code fournisseur : $_POST[code_fournisseur]\n":'';
	$description .= $ref_fournisseur			? "Reference : $ref_fournisseur\n":'';
	$description .= $_POST['gencode']			? "Gencode : $_POST[gencode]\n":'';
		
	$description .= $px_achat					? "Px d'achat/public : $px_achat\n":'';
	$description .= $_POST['remise1']			? "Remise1 : $_POST[remise1]\n":'';
	$description .= $_POST['remise2']			? "Remise2 : $_POST[remise2]\n":'';
	$description .= $_POST['remise3']			? "Remise3 : $_POST[remise3]\n":'';
	$description .= $coef						? "Coef : $coef\n":'';
	$description .= $px_vente					? "Px de vente : $px_vente\n":'';
	$description .= $eco_taxe					? "Eco Taxe : $eco_taxe\n":'';
	$description .= $px_achat_venir				? "Px d'achat a venir : $px_achat_venir\n":'';
	$description .= $_POST['date_achat_venir']	? "Date prix à venir : $_POST[date_achat_venir]\n":'';

	$description .= $_POST['unite_vente']		? "Unité de vente : $_POST[unite_vente]\n":'';

	$description .= $_POST['conditionnement_achat']		? "Conditionnement d'achat : $_POST[conditionnement_achat]\n":'';
	$description .= $_POST['sur_conditionnement_achat']	? "Sur conditionnement d'achat : $_POST[sur_conditionnement_achat]\n":'';
	$description .= $_POST['type_conditionnement_achat']? "Type de conditionnement d'achat : $_POST[type_conditionnement_achat]\n":'';

	$description .= $_POST['stock']				? "Stocké au dépôt : $_POST[stock]\n":'';
	$description .= $_POST['divisible']			? "Divisible : $_POST[divisible]\n":'';

	if ($_POST['divisible']=='non') { 	// vl10
		$description .= $_POST['conditionnement_vl10']			? "Contionnement : $_POST[conditionnement_vl10]\n":'';
		$description .= $_POST['unite_conditionnement_vl10']	? "Unité contionnement : $_POST[unite_conditionnement_vl10]\n":'';
	} else {							// vl20
		$description .= $_POST['conditionnement_vl20']			? "Contionnement : $_POST[conditionnement_vl20]\n":'';
		$description .= $_POST['unite_conditionnement_vl20']	? "Unité contionnement : $_POST[unite_conditionnement_vl20]\n":'';
	}

	$description .= $_POST['sur_conditionnement']		? "Sur conditionnement : $_POST[sur_conditionnement]\n":'999';
	$description .= $_POST['unite_sur_conditionnement']	? "Unité sur conditionnement : $_POST[unite_sur_conditionnement]\n":'PAL';

	$description .= $_POST['activite']			? "Activite : $_POST[activite]\n":'';
	$description .= $_POST['famille']			? "Famille : $_POST[famille]\n":'';
	$description .= $_POST['sousfamille']		? "Sous famille : $_POST[sousfamille]\n":'';
	$description .= $_POST['chapitre']			? "Chapitre : $_POST[chapitre]\n":'';
	$description .= $_POST['souschapitre']		? "Sous chapitre : $_POST[souschapitre]\n":'';
	$description .=  $_POST['commentaire']		? "Commentaire : $_POST[commentaire]\n":'';
	

	$description = mysql_escape_string($description);
	$res = mysql_query("INSERT INTO historique_article (titre,activite,description,de_la_part,status,date_demande) VALUES ('$titre','$_POST[activite]','$description','$_POST[from]',FALSE,now())") or die(mysql_error());

} // fin d'envoi du mail
?>
<html>
<head>
<title>Demande de création d'article</title>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_article.ico" />
<style>
a		{ text-decoration:none; }
a:hover { text-decoration:underline; }

h1 {
	width:95%;
	background-color:#DDD;
	margin-bottom:10px;
	height:30px;
	padding-left:50px;
	font-weight:bold;
	padding-top:10px;
	font-size:1em;
}

th.label {
	vertical-align:top;
	text-align:left;
}

td.valeur {
	width:70%;
	vertical-align:top;
}

select#completion_fourn {
	border:solid 1px #000080;
	display:none;
}

option.type-produit-global { color:grey; }
#completion div { cursor:pointer; }
#completion b { color:blue; }

.prix 		{	background-color: #CFC;	}
.descriptif {	background-color: #FCC;	}
.famille 	{	background-color: #FFC;	}
.stock 		{	background-color: #CCF; }
.achat 		{	background-color: #F5F;	}
.libelle_unite_vl10 {font-size:0.8em;}

</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="JavaScript">
<!--

// transforme les champs de saisie en majuscule
function majusculize(champ) {
	document.creation_article.elements[champ].value = document.creation_article.elements[champ].value.toUpperCase();
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
	else if ($('input:radio[name=divisible]:checked').val()=='non' && $('#conditionnement_vl10').val()=='') {
			alert("Veuillez rentrer une valeur de conditionnement de vente"); erreur = 1;
	}
	else if      (document.creation_article.marge.value.length <= 0 && document.creation_article.px_vente.value.length <= 0) {
		alert("Veuillez saisir soit une marge (ou choisir le type de produit) ou un prix de vente"); erreur = 1;
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
			$('#completion').load('ajax.php?what='+type,function() { bind_event_on_completion(); });
		else if (type=='famille')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value),function() { bind_event_on_completion(); });
		else if (type=='sousfamille')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value),function() { bind_event_on_completion(); });
		else if (type=='chapitre')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value)+'/'+escape(sousfamille.value),function() { bind_event_on_completion(); });
		else if (type=='souschapitre')
			$('#completion').load('ajax.php?what='+type+'&val='+escape(activite.value)+'/'+escape(famille.value)+'/'+escape(sousfamille.value)+'/'+escape(chapitre.value),function() { bind_event_on_completion(); });
	}
}

function bind_event_on_completion() {
	$('#completion .activite').click(function(){
		document.creation_article.activite.value = $(this).children('b').text();
		$(document.creation_article.famille).focus();
	});

	$('#completion .famille').click(function(){
		document.creation_article.famille.value = $(this).children('b').text();
		$(document.creation_article.sousfamille).focus();
	});

	$('#completion .sousfamille').click(function(){
		document.creation_article.sousfamille.value = $(this).children('b').text();
		$(document.creation_article.chapitre).focus();
	});

	$('#completion .chapitre').click(function(){
		document.creation_article.chapitre.value = $(this).children('b').text();
		$(document.creation_article.souschapitre).focus();
	});

	$('#completion .souschapitre').click(function(){
		document.creation_article.souschapitre.value = $(this).children('b').text();
	});
}


<?
// récupere la liste des fournisseurs de MySQL
$res = mysql_query("SELECT nom AS nom_fournisseur,code_rubis as code_fournisseur FROM fournisseur ORDER BY nom ASC") or mysql_error("Impossible de récupérer la liste des fournisseurs");
$fournisseurs = array();
while($row = mysql_fetch_array($res)) {
	array_push($fournisseurs,array($row['code_fournisseur'],strtoupper($row['nom_fournisseur'])));
}
echo "var fournisseurs = ".json_encode($fournisseurs).";";
?>


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
		document.creation_article.fournisseur.value = sel.options[selIndex].text ;
		document.creation_article.code_fournisseur.value = sel.options[selIndex].value ;
		$('#code_fournisseur').text(sel.options[selIndex].value);
		sel.style.display = 'none';

		show_type_produit();
	}
	else { // autre touche --> on recherche et affiche les fournisseurs valident
		val = document.creation_article.fournisseur.value.toUpperCase() ;
		if (val.length > 0) {
						
			var valid_fournisseurs = new Array();
			for(var i=0 ; i<fournisseurs.length ; i++) {
				if (fournisseurs[i][1] != null && fournisseurs[i][1].substr(0,val.length) == val) // ca match --> on garde le fournisseur (on a deja converti en majuscule avant)
					valid_fournisseurs.push(i); // on garde l'indice du fournisseur valide
			}

			document.getElementById('completion_fourn').attributes['size'].value = valid_fournisseurs.length;

			// on vide le select
			while(sel.options.length > 0)
				sel.options[0] = null

			// on rempli avec les fournisseurs valident
			for(var i=0 ; i<valid_fournisseurs.length ; i++) {
				var indice = valid_fournisseurs[i];
				sel.options[sel.options.length] = new Option(fournisseurs[indice][1],fournisseurs[indice][0]);
			}

			if (sel.options.length) {
				sel.selectedIndex = 0 ; // on selection le premier element de la liste
				$('#completion_fourn').show();
			}
			else {
				$('#completion_fourn').hide();		
			}
		}
	}
}


// action lorsque l'on choisit un fournisseur dans la liste
function complette_fourn_click() {
	var sel = document.creation_article.completion_fourn ;

	document.creation_article.fournisseur.value = sel.options[sel.selectedIndex].text ;
	document.creation_article.code_fournisseur.value = sel.options[sel.selectedIndex].value ;
	$('#code_fournisseur').text(sel.options[sel.selectedIndex].value) ;
	sel.style.display = 'none';

	show_type_produit();
}

// affiche les différentes famille produit et les marges une fois le fournisseur choisit
function show_type_produit() {
	// récupere le code fournisseur
	var code_fournisseur = $('#code_fournisseur').text();
	var select_famille_produit = document.creation_article.famille_produit;
	// on vide le select et on bloque les cases marge et PV
	$('#marge').attr('readonly','readonly').css('color','grey').val('');
	$('#pv').css('visibility','hidden').children('input').val('');
	while(select_famille_produit.options.length > 0)
		select_famille_produit.options[0] = null

	$.ajax({
		type: 'GET',
		url:  'ajax.php',
		data: 'what=get_type_produit_fournisseur&code_fournisseur='+code_fournisseur,
		dataType: 'json',
		success: function(json){
			
			if (json.length > 0) {
				// on a trouvé des familles, on ne laisse pas le choix à l'utilisateur
				for(tmp in json) {
					var opt = new Option(json[tmp]['famille_produit'], json[tmp]['marge']);
					if (json[tmp]['famille_produit'] == 'Global')
						opt.className = 'type-produit-global';
					select_famille_produit.options[select_famille_produit.options.length] = opt;
				}
			} else {
				// on a pas trouvé de famille, on laisse l'utilisateur rentré sa marge
				$('#marge').attr('readonly','').css('color','black');
				$('#pv').css('visibility','visible');
			}
		}
	});
}


// action lorsque l'on choisit une famille de produit dans liste
function change_famille_produit() {
	$('#marge').val( document.creation_article.famille_produit.options[document.creation_article.famille_produit.selectedIndex].value );
}


function input_only_float(obj) {
	if(obj.value.match(/[^0-9\.,]/)) {
		alert("Seul les nombres sont autorisés");
		obj.value = obj.value.substr(0,obj.value.length-1);
	}
}


// vérifie si la référence fournisseur n'existe pas déjà
function check_ref_fournisseur() {
	with (document.creation_article) {
		if (code_fournisseur.value && ref_fournisseur.value)
			http.open('GET', 'ajax.php?what=check_ref_fournisseur&val='+escape(code_fournisseur.value)+'/'+escape(ref_fournisseur.value), true);
	}

	http.onreadystatechange = function() {
		if (http.readyState == 4 && http.responseText) {
			alert("Attention, cette référence fournisseur existe déjà.\n"+http.responseText);
		}
	};
	http.send(null);
}


$(document).ready(function(){
    var p = $("input[name=fournisseur]");
	var offset = p.offset();
	$('#completion_fourn').css({'top':offset.top + 22,'left':offset.left,'position':'absolute'});
});

//-->
</script>

</head>
<body style="margin-left:0px;">

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<h1>Demande de création d'article</h1> 

<table>
<tr>
<td style="width:80%;vertical-align:top;">

<form method="post" action="creation_article.php" name="creation_article">
<input type="hidden" name="action" value="creation_article"/>
<input type="hidden" name="code_fournisseur" value=""/>

<table style="width:100%;padding:5px;">
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

<tr><th class="label descriptif">Designation :</th><td class="valeur"><input type="text" name="designation" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label descriptif">Designation 2 :</th><td class="valeur"><input type="text" name="designation2" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label descriptif">Designation 3 :</th><td class="valeur"><input type="text" name="designation3" value="" size="58" maxlength="40" onblur="majusculize(this.name);"></td></tr>
<tr><th class="label descriptif">Fournisseur :</th><td class="valeur"><input type="text" name="fournisseur" value="" onkeyup="complette_fourn(event);" autocomplete="off" onblur="majusculize(this.name);"> <span id="code_fournisseur"></span><br/>
<select id="completion_fourn" name="completion_fourn" size="1" onclick="complette_fourn_click();"></select>
</td></tr>
<tr><th class="label descriptif">Ref Fournisseur :</th><td class="valeur"><input type="text" name="ref_fournisseur" value="" onblur="majusculize(this.name);check_ref_fournisseur();"></td></tr>
<tr><th class="label descriptif">Gencode :</th><td class="valeur"><input type="text" name="gencode" value="" size="13" maxlength="13" onblur="majusculize(this.name);"></td></tr>
<!--<tr><th class="label">Apparait sur tarif :</th><td class="valeur">Oui<input type="radio" name="on_tarif" value="oui">&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Non<input type="radio" name="on_tarif" value="non" checked><br><br></td></tr>-->

<tr><th class="label prix">Px d'achat/public</th>
	<td class="valeur"><input type="text" name="px_achat" value="">&nbsp;&nbsp;&nbsp;&nbsp;
	Eco Taxe <input type="text" name="eco_taxe" value="" size="3">
	</td>
</tr>

<tr>
	<th class="label prix">Px d'achat à venir</th>
	<td class="valeur">
		<input type="text" name="px_achat_venir" value=""/>&nbsp;&nbsp;&nbsp;&nbsp;
		Date <input type="text" name="date_achat_venir" value="" size="8"/>
	</td>
</tr>
<tr>
	<th class="label prix">Remises (3 max) :</th>
	<td class="valeur">
		<input type="text" name="remise1" value="" size="2" maxlength="5"/>%&nbsp;<input type="text" name="remise2" value="" size="2" maxlength="5"/>%&nbsp;<input type="text" name="remise3" value="" size="2" maxlength="5"/>%
	</td>
</tr>
<tr>
	<th class="label prix">Type de produit
<?		if ($droit & PEUT_MODIFIER_TYPE_PRODUIT) { ?>
		<br/><a href="modification_type_produit.php" style="font-size:0.8em;">Edition des types <img src="gfx/ext_arrow.png" style="vertical-align:bottom;"/></a>
<?		} ?>
	</th>
	<td class="valeur">
		<select name="famille_produit" id="famille_produit" size="5" onchange="change_famille_produit();"></select>
		Marge <input type="text" id="marge" name="marge" value="" size="2" readonly="readonly" style="color:grey;" onkeyup="input_only_float(this);"/>%<br/>
		<div id="pv" style="visibility:hidden;">PV : <input type="text" name="px_vente" value="" size="6" onkeyup="input_only_float(this);"/></div>
	</td>
</tr>


<tr>
	<th class="label stock">Unité de vente/achat :</th>
	<td class="valeur">
		<select name="unite_vente" onchange="$('.libelle_unite_vl10').text($(this).val());">
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
	</td>
</tr>

<tr>
	<th class="label achat">Achat</th>
	<td class="valeur">
		Conditionnement achat
		<input type="text" name="conditionnement_achat" id="conditionnement_achat" value="" size="5" placeholder="nb unité"
			onkeyup="
				if ($('input[name=divisible]:checked').val() == 'non')
					$('#conditionnement_vl10').val($(this).val());
		"/>
		<span class="libelle_unite_vl10">Unité (UN)</span>
		<br/>
		Sur contionnement achat
		<input type="text" name="sur_conditionnement_achat" id="sur_conditionnement_achat" value="" size="5" placeholder="nb unité"/>
		<span class="libelle_unite_vl10">Unité (UN)</span>
		<br/>
		Acheté par <input type="radio" name="type_conditionnement_achat" value="conditionnement" checked="checked"/> Conditionnement&nbsp;&nbsp;&nbsp;&nbsp;<input type="radio" name="type_conditionnement_achat" value="sur_conditionnement" /> Sur contionnement
	</td>
<tr>
	<th class="label stock">Stockage</th>
	<td class="valeur">
		
		<div>Stocké au dépôt ?
			<label for="stock_oui">Oui</label><input type="radio" name="stock" value="oui" id="stock_oui"/>&nbsp;&nbsp;&nbsp;
			<label for="stock_non">Non</label><input type="radio" name="stock" value="non" checked="checked" id="stock_non"/>
		</div>

		<div>Divisible à la vente ? 
			<label for="divisible_oui">Oui</label><input type="radio" name="divisible" value="oui" id="divisible_oui"
				onclick="
					$('#block_divisible_oui').show('fast');
					$('#block_divisible_non').hide('fast');
					$('#conditionnement_achat').val('1').attr('disabled','disabled');
			"/>&nbsp;&nbsp;&nbsp;
			<label for="divisible_non">Non</label><input type="radio" name="divisible" value="non" checked="checked" id="divisible_non"
				onclick="
					$('#block_divisible_non').show('fast');
					$('#block_divisible_oui').hide('fast');
					$('#conditionnement_achat').removeAttr('disabled');
			"/>
		</div>

		<div id="block_divisible_non">
			<div>Vendu en
				<select name="unite_conditionnement_vl10">
					<option value="BAR">Barre (BAR)</option>
					<option value="BTE" selected="selected">Boite (BTE)</option>
					<option value="COU">Couronne (COU)</option>
					<option value="M2">M² (M2)</option>
					<option value="ML">Mètre (ML)</option>
					<option value="PQT">Paquet (PQT)</option>
					<option value="RLX">Rouleaux (RLX)</option>
					<option value="SAC">Sac (SAC)</option>
					<option value="TOU">Touret (TOU)</option>
				</select>
				de : <input type="text" name="conditionnement_vl10" id="conditionnement_vl10" value="" size="5" placeholder="nb unité"
						onkeyup="
							if ($('input[name=divisible]:checked').val() == 'non')
								$('#conditionnement_achat').val($(this).val());
					"/>
				<span class="libelle_unite_vl10">Unité (UN)</span>
			</div>
		</div>


		<div id="block_divisible_oui" style="display:none;">
			<div>Conditionné en
				<select name="unite_conditionnement_vl20">
					<option value="" selected="selected">Ne sais pas</option>
					<option value="BA2">Barre (BA2)</option>
					<option value="BT2">Boite (BT2)</option>
					<option value="CA2">Carton (CA2)</option>
					<option value="CO2">Couronne (CO2)</option>
					<option value="M22">M² (M22)</option>
					<option value="ML2">Mètre (ML2)</option>
					<option value="PQ2">Paquet (PQ2)</option>
					<option value="RL2">Rouleaux (RL2)</option>
					<option value="SA2">Sac (SA2)</option>
					<option value="TO2">Touret (TO2)</option>
				</select>
				de : <input type="text" name="conditionnement_vl20" value="" size="5" placeholder="nb unité"/>
				<span class="libelle_unite_vl10">Unité (UN)</span>
			</div>
		</div>


		<div>Sur conditionné en 
			<select name="unite_sur_conditionnement">
				<option value="PAL" selected="selected">Palette (PAL)</option>
				<option value="CA3">Gros carton (CA3)</option>
				<option value="BAC">Ba bleu (BAC)</option>
				<option value="CAD">Cadre (CAD)</option>
				<option value="TOR">Touret (TOR)</option>
			</select>
			de : <input type="text" name="sur_conditionnement" value="" size="5" value="999"/>
			<span class="libelle_unite_vl10">Unité (UN)</span>
		</div>
	</td>
</tr>

<tr>
	<th class="label famille">Activité :</th>
	<td class="valeur"><input type="text" name="activite" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('activite');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label famille">Famille :</th>
	<td class="valeur"><input type="text" name="famille" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('famille');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label famille">Sous famille :</th>
	<td class="valeur"><input type="text" name="sousfamille" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('sousfamille');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label famille">Chapître :</th>
	<td class="valeur"><input type="text" name="chapitre" value="" size="3" maxlength="3" onkeyup="compte_car(this);" onfocus="affiche_aide('chapitre');" onblur="majusculize(this.name);"></td>
</tr>
<tr>
	<th class="label famille">Sous chapître :</th>
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