<?php
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

session_start();

if (isset($_GET['chemin'])) { // recherche par arborérence
	$_SESSION['chemin'] = $_GET['chemin'] ;
	unset($_SESSION['search_text']);
}

if (isset($_POST['search_text'])) { // recherche de code
	$_SESSION['search_text'] = trim($_POST['search_text']) ;
	unset($_SESSION['chemin']);
}

if (!isset($_SESSION['order'])) $_SESSION['order'] = 'code_article ASC';
if (isset($_GET['order']))
	$_SESSION['order']  = $_GET['order'];

?>
<html>
<head>
<style>

@font-face { font-family: SG05; src: url('../../fonts/SG05.ttf'); }

body { margin:0px; }
body,pre { font-family: verdana,helvetica; }
pre { font-size:10px; }
a img { border:none; }

img.photo { width:50px; }

table#article {
	width:100%;
	border:solid 1px black;
	border-spacing: 0px;
	border-collapse: collapse;
}

table#article caption {
	text-align:left;
	font-size:12px;
	margin-bottom:10px;
}

table#article th {
	border:solid 1px grey;
	padding:1px;
	font-size:12px;
}

.suspendu {
	text-decoration:line-through;
}

table#article td {
	border:solid 1px grey;
	padding:3px;
	font-size:11px;
}

/* affichage des stocks */
table#article td.stock {
	background-position:center 2px;
	background-repeat:no-repeat;
	width:30px;
	text-align:center;
	vertical-align: top;
}

table#article td.s0 { background-image:url('gfx/stock2-0.png'); color:#c0c0c0;}
table#article td.s1 { background-image:url('gfx/stock2-1.png'); color:#ed1f24;}
table#article td.s2 { background-image:url('gfx/stock2-2.png'); color:#f68e46;}
table#article td.s3 { background-image:url('gfx/stock2-3.png'); color:#3fac49;}

table#article td.stock img { margin-top:20px; }


div#dialogue {
	padding:20px;
	border:solid 2px black;
	border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}

div#detail-article, div#arbre-deplacement {
	padding:20px;
	border:solid 2px black;
	border-radius:15px;
	background:white;
	display:none;
	position:absolute;
	color:green;
	font-size:1.2em;
	z-index:99;
}


div#detail-article table { font-size:0.6em; }

div#detail-article table td,div#detail-article table th { vertical-align:bottom; }

table#article th { background:#C0C0C0; }

table#article th.<?=e(0,explode(' ',$_SESSION['order']))?>,  table#article td.<?=e(0,explode(' ',$_SESSION['order']))?> {
	border-left:solid 2px black;
	border-right:solid 2px black;
}

table#article td.<?=e(0,explode(' ',$_SESSION['order']))?> { background-color:#D0D0D0; }

td strong {
	font-weight:bold;
	color:green;
}

strong.condi { color:black; }

div#photo {
	padding:15px;
	border:solid 2px #555;
	background:white;
	display:none;
	position:absolute;
	color:black;
	font-size:1.2em;
	z-index:99;
}

td.prix_net {
	font-size:1.1em;
	font-weight:bold;
}

td.prix_revient {
	font-size:0.9em;
}

tr.nonstock { display:none; }

div.stock_reel {
	font-size:2em;
	font-family:SG05;
	margin-top:8px;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../js/fontawesome/css/icon-custom.css">

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/infobulle.css);</style>
<SCRIPT LANGUAGE="JavaScript" SRC="../../js/jquery.js"></SCRIPT>
<SCRIPT LANGUAGE="JavaScript">
<!--

function detail_article(code_article) {
	$('#detail-article').css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300});

	$('#detail_article_designation1').html('');
	$('#detail_article_designation2').html('');
	$('#detail_article_designation3').html('');
	document.article.detail_article_gestionnaire.selectedIndex=0;
	document.article.detail_article_localisation.value='';
	document.article.detail_article_localisation2.value='';
	document.article.detail_article_localisation3.value='';
	document.article.detail_article_mini.value='';
	document.article.detail_article_maxi.value='';
	document.article.detail_article_alerte.value='';
	document.article.detail_article_edition_tarif.checked=false;

	$('#detail_article_code_article').text(code_article);
	$('#detail_article_gestionnaire').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_localisation').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_localisation2').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_localisation3').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_mini').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_alerte').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail_article_maxi').css('background','url(gfx/loading5.gif) no-repeat center center');
	$('#detail-article').show();

	$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'what=detail_article&code_article='+code_article,
			success: function(result){
						var json = eval('(' + result + ')') ;
						
						$('#detail_article_designation1').html($.trim(json['desi1']));
						$('#detail_article_designation2').html($.trim(json['desi2']));
						$('#detail_article_designation3').html($.trim(json['desi3']));

						$('#detail_article_gestionnaire').css('background','white');
						for(i=0 ; i<document.article.detail_article_gestionnaire.options.length ; i++) {
							if (document.article.detail_article_gestionnaire.options[i].value == $.trim(json['gestionnaire']))
								document.article.detail_article_gestionnaire.options[i].selected = true;
							else
								document.article.detail_article_gestionnaire.options[i].selected = false;
						}

						$('#detail_article_localisation').css('background','white');
						document.article.detail_article_localisation.value=$.trim(json['localisation']);
						$('#detail_article_localisation2').css('background','white');
						document.article.detail_article_localisation2.value=$.trim(json['localisation2']);
						$('#detail_article_localisation3').css('background','white');
						document.article.detail_article_localisation3.value=$.trim(json['localisation3']);
						$('#detail_article_mini').css('background','white');
						document.article.detail_article_mini.value=$.trim(json['mini']);
						$('#detail_article_alerte').css('background','white');
						document.article.detail_article_alerte.value=$.trim(json['alerte']);
						$('#detail_article_maxi').css('background','white');
						document.article.detail_article_maxi.value=$.trim(json['maxi']);
						if ($.trim(json['edition_tarif']) == 'OUI')
							document.article.detail_article_edition_tarif.checked=true;
							
						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}


<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
function inverse_servi_article(obj_img,code_article) {

	$('#dialogue').html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.");
	$('#dialogue').css('top',document.body.scrollTop +100);
	$('#dialogue').css('left',screen.availWidth / 2 - 300);
	$('#dialogue').show();

	$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'what=inverse_servi_article&code_article='+code_article,
			success: function(result){
						var json = eval('(' + result + ')') ;
						if (json['stock'])	obj_img.src = 'gfx/yes.png';
						else				obj_img.src = 'gfx/cancel.png';

						$('#dialogue').html('OK');
						$('#dialogue').fadeOut(2000);
						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}

function inverse_tarif_article(obj_img,code_article) {

	$('#dialogue')	.html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.")
					.css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300})
					.show();

	$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'what=inverse_tarif_article&code_article='+code_article,
			success: function(result){
						var json = eval('(' + result + ')') ;
						if (json['stock'])	obj_img.src = 'gfx/catalogue_yes.png';
						else				obj_img.src = 'gfx/catalogue_no.png';

						$('#dialogue').html('OK').fadeOut(2000);
						if (json['debug']) $('#debug').html(json['debug']);
					}
	});
}


function inverse_etat_article(obj_img,code_article,chemin) {

	$('#dialogue')	.html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.")
					.css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300})
					.show();

	$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'what=inverse_etat_article&code_article='+code_article+'&chemin='+chemin,
			success: function(result){
						var json = eval('(' + result + ')') ;
						if (json['stock']) {
							obj_img.src = 'gfx/suspendre.png';
							$('#'+code_article).removeClass('suspendu');
						}
						else {				
							obj_img.src = 'gfx/wake_up.png';
							$('#'+code_article).addClass('suspendu');
						}

						$('#dialogue').html('OK').fadeOut(2000);
						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}


function valider_detail_article() {
	$('#detail-article').hide();
	$('#dialogue')	.html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.")
					.css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300})
					.show();

	$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data:	'what=valider_detail_article&code_article='+ $.trim($('#detail_article_code_article').text()) +
					'&gestionnaire='+$.trim(document.article.detail_article_gestionnaire.options[document.article.detail_article_gestionnaire.selectedIndex].value)+
					'&localisation='+$.trim(document.article.detail_article_localisation.value)+
					'&localisation2='+$.trim(document.article.detail_article_localisation2.value)+
					'&localisation3='+$.trim(document.article.detail_article_localisation3.value)+
					'&mini='+$.trim(document.article.detail_article_mini.value)+
					'&maxi='+$.trim(document.article.detail_article_maxi.value)+
					'&alerte='+$.trim(document.article.detail_article_alerte.value)+
					'&edition_tarif='+(document.article.detail_article_edition_tarif.checked ? 'OUI':'NON'),
			success: function(result){
						var json = eval('(' + result + ')') ;
						$('#dialogue').html('OK').fadeOut(2000);
						if (json['debug']) $('#debug').html(json['debug']);
					}	
	});
}


<? } // fin peut modifier article ?>


/* imprime une etiquette en PDF destinée a une palette pour le dépot */
function generate_etiquette_article() {
	var code_article = $.trim($('#detail_article_code_article').text());
	document.location.href="edition_etiquette.php?code_article="+code_article+"&qte="+prompt("Quelle quantité ?");
	//alert($.trim($('#detail_article_code_article').text()));
}

<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
function tout_selectionner() {
	for(i=0 ; i<document.article.elements.length ; i++)
		if (document.article.elements[i].type == 'checkbox' && document.article.elements[i].name.match(/^checkbox_/)) // si une checkbox
			document.article.elements[i].checked = true ;
}

function inverser_selection() {
	for(i=0 ; i<document.article.elements.length ; i++)
		if (document.article.elements[i].type == 'checkbox' && document.article.elements[i].name.match(/^checkbox_/)) // si une checkbox
			document.article.elements[i].checked = !document.article.elements[i].checked  ;
}

function affiche_arbre_deplacement() {
	
	nb_article_coche = 0 ;
	for(i=0 ; i<document.article.elements.length ; i++)
		if (document.article.elements[i].type == 'checkbox' && document.article.elements[i].checked && document.article.elements[i].name.match(/^checkbox_/)) // si une checkbox
			nb_article_coche++;
	
	if (nb_article_coche) { // si un moins un article est coché
		$('#arbre-deplacement')	.css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300})
								.show();
	} else
		alert("Veuillez sélectionner au moins un article avant");
}

function maj_nouveau_chemin(chemin) {
	document.article.nouveau_chemin.value=chemin;
}

function valider_nouveau_chemin() {
	if (document.article.nouveau_chemin.value) {

		$('#arbre-deplacement').hide();
		$('#dialogue')	.html("<img src=\"gfx/loading3.gif\" align=\"absmiddle\"> En cours de modification.")
						.css({'top':document.body.scrollTop +100,'left':screen.availWidth / 2 - 300})
						.show();

		code_article = '' ;
		for(i=0 ; i<document.article.elements.length ; i++) {
			if (document.article.elements[i].type == 'checkbox' && document.article.elements[i].checked) { // si une checkbox
				tmp = document.article.elements[i].name.split(/_/) ;
				if (tmp[0] == 'checkbox') // si une des cases a coché avec un code article
					code_article += '&code_article[]='+tmp[1] ;
			}
		}

		$.ajax({
				url:	'ajax.php',
				type:	'POST',
				data:	'what=valider_nouveau_chemin&chemin=' + document.article.nouveau_chemin.value + code_article,
				success: function(result){
							var json = eval('(' + result + ')') ;
							$('#dialogue').html('OK').fadeOut(2000);
							if (json['debug']) $('#debug').html(json['debug']); // on affiche le debug
							document.location.reload(false); // on recharge la page
						}	
		});
	} else
		alert("Veuillez sélectionner une nouvelle destination avant");
}
<? } // fin de peut déplacer article ?>


function searchAllContenuHydra(type_stock) {
	// pour chaque article, cherche le contenu
	$('tr[id^=ligne_].nonstock').each(function(){
		console.log($(this).attr('data-article'));
		searchContenuHydra(
			$(this).attr('data-article'),
			$(this).attr('data-fournisseur'),
			$(this).attr('data-ref')
		);
	});
}


function searchContenuHydra(code_article,fournisseur,ref) {
	if (fournisseur == 'LEGRA') fournisseur = 'LEGRAN'; // patch pour MCS

	$.ajax({url: 'ajax.php',
				type: 'GET',
				dataType:'json',
				data: 'what=hydra_info&fournisseur='+escape(fournisseur)+'&ref='+escape(ref),
				success: function(result) {
					console.log(result);
					
					if (result.response[0]['T'] != undefined) { // la fiche technique exists
						$('tr#ligne_'+code_article+' .fiche_technique').html(
							'<a class="btn btn-small" href="http://www.coopmcs.com/hydra/getfile.php?fournisseur='+escape(result.request['fournisseur'])+'&ref='+escape(result.request['ref'])+'&type=T"><i class="icon-download"></i> F. Technique</a>'
						);
					}

					if (result.response[0]['M'] != undefined) { // photo metré
						$('tr#ligne_'+code_article+' .photo_mettre').html(
								'<img class="photo" src="http://www.coopmcs.com/hydra/getfile.php?fournisseur='+escape(result.request['fournisseur'])+'&ref='+escape(result.request['ref'])+'&type=M&largeur=500&hauteur=500"/>'
						);
					}
					
				}
		});
}



$(document).ready(function(){
	$('body').delegate('img.photo','mouseover',function(){
		//alert($(this).offset().top + 'px    '+$(this).offset().left+'px');
		var offset = $(this).offset();
		$('#photo')	.html('<img src="'+$(this).attr('src')+'"/>')
					.css({'top':offset.top+'px','left':offset.left+'px'})
					.show();
	}); // fin mouseover

	$('body').delegate('#photo','mouseout',function(){
						$('#photo').hide();
	}); // fin mouseout


	$('label.mobile > input[type=checkbox]').click(function(){
		if(this.checked)
			$(this).parents('label').addClass('mobile-checked');
		else
			$(this).parents('label').removeClass('mobile-checked');
	});

	$('label.mobile > input[type=checkbox]').each(function(){
		if(this.checked)
			$(this).parents('label').addClass('mobile-checked');
	});


	// click sur affiche les produits non stocké
	$('#show_produit_stock').click(function(){
		if(this.checked) {
			$('.nonstock').show();
			searchAllContenuHydra('nonstock');
		} else {
			$('.nonstock').hide();
		}
	});

}); // document.ready


//-->
</SCRIPT>
</head>

<body>

<form name="article" method="POST">

<div id="debug"></div>

<div id="photo"></div>

<!-- boite de dialogue pour faire patienté pendant l'ajax -->
<div id="dialogue"></div>


<!-- boite qui affiche les détails de l'article -->
<div id="detail-article">
<table>
	<caption>Edition du détail pour <strong id="detail_article_code_article"></strong></caption>
	<tr>
		<th>Designation 1</th>
		<td id="detail_article_designation1"></td>
	</tr>
	<tr>
		<th>Designation 2</th>
		<td id="detail_article_designation2"></td>
	</tr>
	<tr>
		<th>Designation 3</th>
		<td id="detail_article_designation3"></td>
	</tr>
	<tr>
		<th>Gestionnaire</th>
		<td><select name="detail_article_gestionnaire" size="1" id="detail_article_gestionnaire">
				<option value="" selected></option>
				<option value="BT">Bernard</option>
				<option value="RLF">Régis</option>
				<option value="JM">Jérémy</option>
				<option value="CK">Claude K.</option>
			</select>
		</td>
	</tr>
	<tr>
		<th>Localisation</th>
		<td><input type="text" name="detail_article_localisation" id="detail_article_localisation" size="8" maxlength="12"></td>
	</tr>
	<tr>
		<th>Localisation 2</th>
		<td><input type="text" name="detail_article_localisation2" id="detail_article_localisation2" size="8" maxlength="12"></td>
	</tr>
	<tr>
		<th>Localisation 3</th>
		<td><input type="text" name="detail_article_localisation3" id="detail_article_localisation3" size="8" maxlength="12"></td>
	</tr>
	<tr>
		<th>Stock mini</th>
		<td><input type="text" name="detail_article_mini" id="detail_article_mini" size="8"></td>
	</tr>
	<tr>
		<th>Stock alerte</th>
		<td><input type="text" name="detail_article_alerte" id="detail_article_alerte" size="8"></td>
	</tr>
	<tr>
		<th>Stock maxi</th>
		<td><input type="text" name="detail_article_maxi" id="detail_article_maxi" size="8"></td>
	</tr>
	
	<tr>
		<th>Edition sur tarif</th>
		<td><input type="checkbox" name="detail_article_edition_tarif" id="detail_article_edition_tarif"></td>
	</tr>

	<tr>
		<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
			<td><input value="Valider" class="button valider" type="button" onclick="valider_detail_article();"></td>
		<? } ?>
		<td>
			<input value="Annuler" class="button annuler" type="button" onclick="$('#detail-article').hide();">
			<input value="Imprimer etiquette" class="button printer divers" type="button" onclick="generate_etiquette_article();">
		</td>
	</tr>
	
</table>
</div>


<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
<!-- boite qui affiche le nouvel arbre pour le déplacement -->
<div id="arbre-deplacement">
	<input value="Valider" class="button valider" type="button" onclick="valider_nouveau_chemin();" />
	<input value="Annuler" class="button annuler" type="button" onclick="$('#arbre-deplacement').hide();" />
	<input type="hidden" name="nouveau_chemin" value="" />

	<script src="../../js/treeview/ua.js"></script>
	<script src="../../js/treeview/ftiens4.js"></script>
	<script src="arbre-deplacement.js.php"></script>
	<style>
		td {
			font-size: 10pt; 
			text-decoration: none;
			white-space:nowrap;
		}

		a {
			text-decoration: none;
			color: black;
		}

		.menu {
			font-size:7pt;
		}

	</style>
	<div style="position:absolute; top:0; left:0;"><table border="0"><tr><td><font size="-2"><a href="http://www.treemenu.net/" target="_blank"></a></font></td></tr></table></div>

	<script>initializeDocument()</script>
	<noscript>
		A tree for site navigation will open here if you enable JavaScript in your browser.
	</noscript>

	<input value="Valider" class="button valider" type="button" onclick="valider_nouveau_chemin();" />
	<input value="Annuler" class="button annuler" type="button" onclick="$('#arbre-deplacement').hide();" />
</div>
<? } ?>

<style>

table#entete { width:100%; }
table#entete #chemin {
	text-align:left;
	vertical-align:top;
	border:solid 1px #0C3A6D;
	color:white;
	background-color:#0C3A6D;
	font-size:0.8em;
}
table#entete #chemin div {
	width:69%;
	text-align:left;
	float:left;
}

table#entete #chemin a {
	color:white;
	font-weight:bold;
}

a:hover {
	text-decoration:underline;
}
</style>

<!-- entete + bouton de selection -->
	<table id="entete">
	<tr>
		<!-- photo -->
		<td rowspan="2" class="photo" style="text-align:left;vertical-align:middle;"></td>
		<td id="chemin">
			<div>&nbsp;
<?			if (isset($_SESSION['chemin'])) {
				
				// décortique le chemin pour retrouver les libéllés
				$codes_chemin = explode('.',$_SESSION['chemin']);
				$condition = array();
				for($i=0 ; $i<sizeof($codes_chemin); $i++) {
					$condition[] = "chemin='".join('.',array_slice($codes_chemin,0,$i+1))."'" ;
				}

				$res = mysql_query("SELECT chemin,libelle FROM pdvente WHERE ".join(" OR ",$condition)." ORDER BY chemin ASC") or die("Ne peux pas retrouver les libéllés des familles ".mysql_error()); // recupere tous les libellés
				$i=0;
				while($row = mysql_fetch_array($res)) { //$chemin_libelle[] = $row['libelle']; ?>
					<?= $i>0 ? ' &rArr; ':'' ?><a href="<?=$_SERVER['PHP_SELF']?>?chemin=<?=join('.',array_slice($codes_chemin,0,$i+1))?>"><?=$row['libelle']?></a>
<?					$i++;
				} ?>
<?			}

			if (isset($_SESSION['search_text'])) { ?>
				Recherche de [<b><?=$_SESSION['search_text']?></b>]
<?			}  ?>
		</div>
</td>
	</tr>
	<tr>
		<td style="text-align:right;border:none;">
			<label for="show_produit_stock" class="mobile" style="width:20em;margin-right:3em;"><input id="show_produit_stock" type="checkbox">Afficher les produits sans stock</label>
<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
			<input value="Tout sélectionner" class="button divers" style="background-image:url(gfx/basket_add.png);" type="button" onclick="tout_selectionner();">
			<input value="Inverser la sélection" class="button divers" style="background-image:url(gfx/basket_invert.png);" type="button" onclick="inverser_selection();">
			<input value="Déplacer la sélection" class="button divers" style="margin-top:4px;background-image:url(gfx/arrow_switch.png);" type="button" onclick="affiche_arbre_deplacement();">
<? } ?>
		</td>
	</tr>
</table>


<table id="article">
	<tr>
		<th class="photo" nowrap>photo</th>
		<th class="code_article" nowrap>code<a href="affiche_article.php?order=code_article ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=code_article DESC"><img src="/intranet/gfx/desc.png"></a></th>
		<th class="fournisseur">Fournisseur<a href="affiche_article.php?order=fournisseur ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=fournisseur DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="ref_fournisseur" nowrap>Ref<a href="affiche_article.php?order=ref_fournisseur ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=ref_fournisseur DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="designation">Désignation<a href="affiche_article.php?order=designation ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=designation DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="stock_afa">Plescop</th>
		<th class="stock_afl">Caudan</th>
		<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
			<th></th>
		<? } ?>
		<th class="servi_sur_stock" nowrap>S<a href="affiche_article.php?order=servi_sur_stock ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=servi_sur_stock DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="sur_tarif" nowrap>T<a href="affiche_article.php?order=sur_tarif ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=sur_tarif DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="prix_revient" nowrap>PR<a href="affiche_article.php?order=prix_revient ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=prix_revient DESC"><img src="/intranet/gfx/desc.png"></th>
		<th class="prix_net" nowrap>PV<a href="affiche_article.php?order=prix_net ASC"><img src="/intranet/gfx/asc.png"></a><a href="affiche_article.php?order=prix_net DESC"><img src="/intranet/gfx/desc.png"></th>
		<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
			<th nowrap>SUS</th>
		<? } ?>
	</tr>
<?	
	$sql = <<<EOT
SELECT	code_article,fournisseur,code_fournisseur,ref_fournisseur,chemin,designation,servi_sur_stock,prix_revient,prix_net,prix_achat_brut,remise1,remise2,remise3,sur_tarif,conditionnement,unite,date_creation,
		(SELECT qte		FROM qte_article WHERE code_article=A.code_article and depot='AFA') as stock_afa,
		(SELECT mini	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as mini_afa,
		(SELECT qte_cde	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as reappro_afa,
		(SELECT servi	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as servi_afa,
		(SELECT qte		FROM qte_article WHERE code_article=A.code_article and depot='AFL') as stock_afl,
		(SELECT mini	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as mini_afl,
		(SELECT qte_cde	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as reappro_afl,
		(SELECT servi	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as servi_afl
FROM	article A
WHERE	1=1
		and

EOT;

	if (isset($_SESSION['chemin'])) { // recherche par chemin
		$sql .= "chemin='".mysql_escape_string($_SESSION['chemin'])."'";
	} elseif (isset($_SESSION['search_text'])) { // recherche par text

		$phrase = split(' +',$_SESSION['search_text']); // on séprare les mots
		$et  = array();
		// on vérifie que chaque mot est présent dans la désignation (ET naturel)
		foreach ($phrase as $mot) {
			if ($mot) array_push($et,"designation LIKE '%$mot%'");
		}
		$et = join($et," AND ");

		$search_text = mysql_escape_string($_SESSION['search_text']);
		$sql .=		"(code_article    LIKE '%$search_text%' OR ".
					" ($et) OR ".
					" ref_fournisseur LIKE '%$search_text%' OR ".
					" ref_fournisseur_condensee LIKE '%$search_text%')";

	} else { // pas de recherche
		$sql .= "chemin=''";
	}

	$sql .= " ORDER BY ".mysql_escape_string(isset($_SESSION['order']) ? $_SESSION['order']:'code_article ASC');

	$res = mysql_query($sql) or die("Ne peux pas récupérer les infos de la table article : ".mysql_error());

	$nb_stock = 0 ;
	while($row = mysql_fetch_array($res)) {
		$stock = $row['stock_afa'].$row['stock_afl'] == '' ? false : true ;
		if ($stock)
			$nb_stock++;

		$row['code_article'] = trim(strtoupper($row['code_article']));
?>
		<tr id="ligne_<?=$row['code_article']?>" class="<?=($row['stock_afa'].$row['stock_afl'] == '' ? ' nonstock':'stock')?>" data-article="<?=$row['code_article']?>" data-fournisseur="<?=$row['code_fournisseur']?>" data-ref="<?=$row['ref_fournisseur']?>">
			<!-- photo -->
			<td class="photo">
<?				if (strlen($row['fournisseur']) > 0 && strlen($row['ref_fournisseur']) > 0) { ?>
					<img class="photo" src="http://www.coopmcs.com/hydra/getfile.php?fournisseur=<?=$row['code_fournisseur']?>&ref=<?=$row['ref_fournisseur']?>&largeur=500&hauteur=500"/>
					<div class="photo_mettre"></div>
<?				} ?>
			</td>
			
			<!-- code article -->
			<td class="code_article"><a href="javascript:detail_article('<?=$row['code_article']?>');" class="info"><?=isset($_SESSION['search_text']) ? preg_replace("/(".trim($_SESSION['search_text']).")/i","<strong>$1</strong>",$row['code_article']) : $row['code_article']?><span>Afficher les détails de l'article</span></a></td>
			
			<!-- fournisseur -->
			<td class="fournisseur" style="font-size:9px;"><?=wordwrap($row['fournisseur'], 20, "<br />\n")?><br/>(<?=$row['code_fournisseur']?>)</td>
			
			<!-- ref fournisseur -->
			<td class="ref_fournisseur" style="font-size:9px;">
				<?=isset($_SESSION['search_text']) ? preg_replace("/(".trim($_SESSION['search_text']).")/i","<strong>$1</strong>",$row['ref_fournisseur']) : $row['ref_fournisseur']?>
			</td>

			<!-- designation -->
			<td class="designation" style="font-size:9px;">
				<pre style="font-size:9px;">
<?				// si l'article a moins de deux mois, on affiche un logo nouveau
				$date_creation	= date_create($row['date_creation']);
				$now			= date_create('now');
				$interval		= date_diff($now, $date_creation)->format('%a');
				if ($interval < 60) { // article de mions de deux mois ?>
<img src="gfx/new.png" style="vertical-align:middle;" title="Article de moins de 2 mois crée le <?=join('/',array_reverse(explode('-',$row['date_creation'])))?>"/> <?
				}
				
				if (isset($_SESSION['search_text'])) { // si un mot clé de recherché
					$designation = $row['designation'];
					foreach ($phrase as $mot) {
						if ($mot) $designation = preg_replace("/(".$mot.")/i","<strong>$1</strong>",$designation);
					}
					echo trim($designation);
				} else {
						echo trim($row['designation']);
				}	?></pre>
				<!-- condtionnement et unité -->
<?				if ($row['conditionnement'] > 1) { ?>
					<strong class="condi">Vendu par <?=$row['conditionnement']?><?=$row['unite']?></strong>
<?				} ?>

				<!-- article similaire -->
				<div class="articles-similaires">
					<a href="<?=$_SERVER['PHP_SELF']?>?chemin=<?=$row['chemin']?>" class="similaire"><i class="icon-search icon-large" style="color:black;"></i> Articles similaires</a>
				</div>

				<div class="fiche_technique" style="width:48%;float:left;"></div>
			</td>
			
			<!-- gestion des stock -->
			<td class="stock <?
				if		(!$row['servi_afa'])							echo "s0";											// pas stocké
				elseif  ($row['servi_afa'] && $row['stock_afa'] <= 0)	echo "s1";											// en rupture
				elseif  ($row['servi_afa'] && $row['stock_afa'] > 0 && $row['stock_afa'] <= $row['mini_afa']) echo "s2";	// en dessous du mini
				else													echo "s3";											// au dessus du mini
			?>">
			<div class="stock_reel"><?=str_replace('.000','',$row['stock_afa'])?></div>
<?				if ($row['reappro_afa'] > 0) { // reappro de stock en cours ?>
					<i class="icon-truck icon-2x" title="Réappro en cours"></i>
<?				} ?>
			</td>

			<td class="stock <?
				if		(!$row['servi_afl'])							echo "s0";											// pas stocké
				elseif  ($row['servi_afl'] && $row['stock_afl'] <= 0)	echo "s1";											// en rupture
				elseif  ($row['servi_afl'] && $row['stock_afl'] > 0 && $row['stock_afl'] <= $row['mini_afl']) echo "s2";	// en dessous du mini
				else													echo "s3";											// au dessus du mini
			?>">
				<div class="stock_reel"><?=str_replace('.000','',$row['stock_afl'])?></div>
<?				if ($row['reappro_afl'] > 0) { // reappro de stock en cours ?>
					<i class="icon-truck icon-2x" title="Réappro en cours"></i>
<?				} ?>
			</td>

			<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
				<td><input type="checkbox" name="checkbox_<?=$row['code_article']?>" /></td>
			<? } ?>
			<!-- servi sur stock -->
			<td class="servi_sur_stock" align="center">
				<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
					<a class="info"><span>Changer "servi" de l'article</span>
				<? } ?>
				<img src="<?=$row['servi_sur_stock'] == '0' ? 'gfx/cancel.png':'gfx/yes.png'?>"<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?> onclick="inverse_servi_article(this,'<?=$row['code_article']?>');"<? } ?>>
				<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
					</a>
				<? } ?>
			</td>

			<!-- sur tarif -->
			<td class="sur_tarif" align="center">
				<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
					<a class="info"><span>Changer l'édition sur tarif papier de l'article</span>
				<? } ?>
				<img src="<?=$row['sur_tarif'] == '0' ? 'gfx/catalogue_no.png':'gfx/catalogue_yes.png'?>"<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?> onclick="inverse_tarif_article(this,'<?=$row['code_article']?>');"<? } ?>>
				<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
					</a>
				<? } ?>
			</td>

			<!-- prix revient -->
			<td class="prix_revient" nowrap="nowrap" title="Prix achat brut : <?=$row['prix_achat_brut']."\n"?>Remises : <?=$row['remise1']?> + <?=$row['remise2']?> + <?=$row['remise3']."\n"?>Prix revient : <?=$row['prix_revient']."\n"?>Marge/Coef : <? 
				$coef	= $row['prix_net'] / $row['prix_revient'];
				$marge	= 100 - (1/$coef * 100);
				printf("%0.2f/%0.5f\n",$marge,$coef);
			?>Prix vente : <?=$row['prix_net']."\n"?>"><?
				if ($row['conditionnement'] > 1) {
					printf('%d%s x %0.2f&euro;', $row['conditionnement'], $row['unite'], $row['prix_revient'] );
					printf('<br/><small>%0.2f&euro;</small>',$row['conditionnement']*$row['prix_revient']);
				} else {
					printf('%0.2f&euro;',$row['prix_revient']);
				} ?>
			</td>

			<!-- prix net -->
			<td class="prix_net" nowrap="nowrap"><?
				if ($row['conditionnement'] > 1) {
					printf('%d%s x %0.2f&euro;', $row['conditionnement'], $row['unite'], $row['prix_net'] );
					printf('<br/><small>%0.2f&euro;</small>',$row['conditionnement']*$row['prix_net']);
				} else {
					printf('%0.2f&euro;',$row['prix_net']);
				} ?>
			</td>

			<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
				<td align="center">			
					<a class="info"><span>Suspendre l'article</span><img src="gfx/suspendre.png" onclick="inverse_etat_article(this,'<?=$row['code_article']?>','<?=isset($_SESSION['chemin'])?$_SESSION['chemin']:''?>');"></a>
				</td>
			<? } ?>
		</tr>
<?	}
	
	if (isset($_SESSION['chemin']) && 0) {
		$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

		$tmp = explode('.',$_SESSION['chemin']);
		$famille = array();
		if (isset($tmp[0])) $famille[] = "ACTIV='$tmp[0]'"; // activité
		if (isset($tmp[1])) $famille[] = "FAMI1='$tmp[1]'"; // famille
		if (isset($tmp[2])) $famille[] = "SFAM1='$tmp[2]'"; // sous famille
		if (isset($tmp[3])) $famille[] = "ART04='$tmp[3]'"; // chapitre
		if (isset($tmp[4])) $famille[] = "ART05='$tmp[4]'"; // sous chapitre

		$res = odbc_exec($loginor,"select NOART,FOUR1,DESI1,SERST from ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 where ETARE='S' and ".join(' and ',$famille)) ;
		while($row = odbc_fetch_array($res)) { ?>
			<tr class="suspendu">
				<td></td><!--photo-->
				<td><?=$row['NOART']?></td>
				<td style="font-size:9px;"><?=$row['FOUR1']?></td>
				<td></td>
				<td style="font-size:9px;"><?=$row['DESI1']?></td>
				<? if ($droit & PEUT_DEPLACER_ARTICLE) { ?>
					<td></td>
				<? } ?>
				<td align="center">
					<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
						<a class="info"><span>Cliquer sur l'image pour changer le status de l'article</span>
					<? } ?>
						<img src="<?=$row['SERST'] == 'NON' ? 'gfx/cancel.png':'gfx/yes.png'?>"<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?> onclick="inverse_servi_article(this,'<?=$row['NOART']?>');"<? } ?>>
					<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
						</a>
					<? } ?>
				</td>
				<td></td>
				<? if ($droit & PEUT_MODIFIER_ARTICLE) { ?>
					<td align="center">				
						<a class="info"><span>Cliquer sur l'image pour activer l'article</span><img src="gfx/wake_up.png" onclick="inverse_etat_article(this,'<?=$row['NOART']?>','<?=$_SESSION['chemin']?>');"></a>
					</td>
				<? } ?>
			</tr>
<?		}
		odbc_close($loginor);
	} // chemin non définit ?>
</table>
</form>

<script type="text/javascript">
<!--
$(document).ready(function(){

<? if ($nb_stock <= 0) { ?>
	// si aucun l'article affiché est stocké, on affiche les non stocké
	$('#show_produit_stock').attr('checked','checked');
	$('#show_produit_stock').parents('label').addClass('mobile-checked');
	$('.nonstock').show();
	searchAllContenuHydra('nonstock');
<? } else { ?>
	// pour chaque article, cherche le contenu
	$('tr[id^=ligne_].stock').each(function(){
		console.log($(this).attr('data-article'));
		searchContenuHydra(
			$(this).attr('data-article'),
			$(this).attr('data-fournisseur'),
			$(this).attr('data-ref')
		);
	});
<? } ?>

});
//-->
</script>

</body>
</html>