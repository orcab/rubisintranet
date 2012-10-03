<?
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1" />
<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/tactile.css);</style>
<style>
body {
	background-color: white;
	font-family: verdana,helvetica;
	margin:0px;
	margin-top:5px;
}
a img { border:none; }
td { text-decoration: none; }
a {	text-decoration: none;
	color: black;
}

div#cadre-panier,div#cadre-panier-favori,div#cadre-bestof  {
	margin:5px;
	margin-top:10px;
	margin-bottom:10px;
	padding:0px;
	border-style:solid;
	border-width:1px;
}
div#cadre-panier,div#cadre-bestof		{ border-color:#0C3A6D; }
div#cadre-panier-favori { border-color:#6E610C; }

div#cadre-panier h1, div#cadre-panier-favori h1, div#cadre-bestof h1 {	
	color:white;
	text-align:center;
	padding:2px;
	margin:0px;
	font-size:1.5em;
}
div#cadre-panier h1, div#cadre-bestof h1			{ background-color:#0C3A6D; }
div#cadre-panier-favori h1	{ background-color:#6E610C; }
div#cadre-panier h1 a, div#cadre-panier-favori h1 a { color:white; }
div#cadre-panier h1 span:hover,div#cadre-panier h1 a:hover { color:yellow; }

div#panier, div#panier-favori {
	margin-left:7px;
	margin-right:7px;
	margin-bottom:5px;
}

div#panier table, div#panier-favori table {
	width:100%;
	margin:0px;	
	border-spacing: 0px;
	border-collapse: collapse;
}

div#panier td, div#panier-favori td {
	padding:2px;
	vertical-align:top;
}

div#panier table tr { border-bottom:dotted 1px grey; }
div#panier table tr:last-child, table#bestof tr:last-child { border-bottom:none; }


table#bestof { width:100% ;}
table#bestof tr td { border-bottom:dotted 1px grey; }
table#bestof .code_article a { color:#529214; }

img.supprime-article { cursor:pointer; }

td.code_article {
	color:#529214;
	font-weight:bold;
	vertical-align:top;
}

td.qte { font-weight:bold; }

div#panier-favori ul {
	cursor:pointer;
	list-style-image:url(gfx/arrow_down.gif);
	color:#6E610C;
	margin:0px;
	margin-top:3px;
	padding-left:15px;
}

li.cde_favori {
	margin-top:3px;
	margin-bottom:3px;
}

div#panier-favori ol {
	list-style-image:url(gfx/puce.png);
	display:none;
	color:grey;
	padding-left:10px;
}

div#panier-favori ol li { margin-bottom:10px; }

span.ref_fournisseur {
	font-weight:bold;
}

span.designation {
	font-style:italic;
}

div#panier-favori ol a {
	display:block;
	margin-bottom:5px;
}

input#deconnexion {
	margin-top:1em;
	margin-bottom:1em;
}

input.affiche_article {
	background-image:url(gfx/arrow_right_blue_32.png);
	background-position: center center;
	width:50px;
}

input.supprime-article {
	background-image:url(gfx/delete_32.png);
	background-position: center center;
	width:50px;
}

/* une ligne (icon + libelleé) */
#domRoot > div { height:32px; }
#domRoot .menu { font-size:1.1em;margin-left:5px; }


</style>

<!--[if IE]>
<style>
div#panier table { width:90%; }
div#panier-favori ol { margin:0; }
div#panier table td { border-bottom:dotted 1px grey; }
</style>
<![endif]-->

<!-- Code for browser detection. DO NOT REMOVE.              -->
<script src="../js/treeview/ua.js"></script>
<!-- Infrastructure code for the TreeView. DO NOT REMOVE.    -->
<script src="../js/treeview/ftiens4x2.js"></script>
<!-- Scripts that define the tree. DO NOT REMOVE.            -->
<script src="demoFramesetNodes.js.php"></script>
<script language="javascript" src="../js/jquery.js"></script>

<script language="javascript">
<!--

function delete_panier(no_ligne) {
	if (confirm("Voulez vous vraiment supprimer cet article ?")) {
		$.ajax({
				url: 'ajax.php',
				type: 'GET',
				data: 'what=delete_panier&no_ligne='+escape(no_ligne),
				success: function(result){ $('#panier').html(result); }	
		});
	}
}


function ajoute_panier(tab_article) { // format : code:qte[, code:qte ...]
	$.ajax({url: 'ajax.php',
			type: 'GET',
			data: 'what=ajout_panier&code_article='+escape(tab_article),
			success: function(result){ $('#panier').html(result); }	
	});
}

function remplace_panier(tab_article) { // format : code:qte[, code:qte ...]
	$.ajax({ // on supprime tout le panier
			url: 'ajax.php',
			type: 'GET',
			data: 'what=delete_panier_all',
			success: function(result){ 
				$.ajax({ // on ajoute la commande favorite
						url: 'ajax.php',
						type: 'GET',
						data: 'what=ajout_panier&code_article='+escape(tab_article),
						success: function(result){ $('#panier').html(result); }	
				});
			
			}	
	});
}

function delete_cde(id_panier) {
	if (confirm("Voulez vous vraiment supprimer cette commande de vos favoris ?")) {
		$.ajax({ // on supprime tout le panier
				url: 'ajax.php',
				type: 'GET',
				data: 'what=delete_cde_favori&id='+escape(id_panier),
				success: function(result){  
					$('#cde_'+id_panier).remove(); // on supprime la cde favorite.
				}	
		});
	}
}

function verif_champs() {
	return document.rechercher.search_text.value.length >= 2 ? true : false ;
}

/*
function save_panier_as_favori() {
	var nom_panier=prompt("Donnez un nom à cette commande favorite :");
	if (nom_panier) {
		$.ajax({
				url: 'ajax.php',
				type: 'GET',
				data: 'what=save_panier_as_favori&code_user=<?=$info_user['username']?>&nom_panier='+nom_panier,
				success: function(result){
					if (result) // erreur
						alert(result);
					else // pas d'erreur, on recharge la page pour avoir la nouvelle cde favorite en visuel
						$.ajax({ //  On va chercher les cde favorites
								url: 'ajax.php',
								type: 'GET',
								data: 'what=get_favori&code_user=<?=$info_user['username']?>',
								success: function(result){ $('#panier-favori').html(result); }	
						});
				}
		});
	} // si nom_panier
}
*/

// une fois la page chargé.
$(document).ready(function() {
	$.ajax({ //  On va chercher le panier actuel
			url: 'ajax.php',
			type: 'GET',
			data: 'what=get_panier',
			success: function(result){ $('#panier').html(result); }	
	});

	/*
	$.ajax({ //  On va chercher les cde favorites
			url: 'ajax.php',
			type: 'GET',
			data: 'what=get_favori&code_user=<?=$info_user['username']?>',
			success: function(result){ $('#panier-favori').html(result); }	
	});
	*/

	// clique sur le bouton deconnexion
	$('body').delegate('#deconnexion','click',function(){
		parent.document.location.href='index.php?deconnexion=1';
	});

	
	$('body').delegate('input.affiche_article','click',function(){
			parent.basefrm.document.location.href='affiche_article.php?search_text=' + $(this).attr('title') ;
	});

	
	$.ajax({ //  charge les best of
			url: 'ajax.php',
			type: 'GET',
			data: 'what=get_bestof',
			success: function(result){ $('#bestof').html(result); }	
	});

	// click sur un champs texte, on selectionne tout le texte par défaut
	$('input[type=text]').focusin(function(){
		$(this).select();
	});

});

//-->
</script>

</head>

<body>
<form name="rechercher" method="POST" action="affiche_article.php" style="margin-bottom:5px;" target="basefrm" onsubmit="return verif_champs();">
<input type="text" name="search_text" size="14" style="margin-left:5px;"> <input type="submit" class="button valider" style="background-image:url(gfx/find.png);" value="Rechercher"><br>
<span style="margin-left:5px;">(code, référence, désignation)</span>
</form>

<div id="cadre-panier">
	<h1>Votre panier</h1>
	<div id="panier"></div>
	<h1><a href="validation_panier.php" target="_top">Valider le panier <img src="gfx/down-arrow-32.png" style="vertical-align:bottom;"/></a></h1>
	<!--<h1 style="border-top:dotted 1px white;"><span style="cursor:pointer;" onclick="save_panier_as_favori();">Enregistrer en favoris <img src="gfx/arrow_white_orange.gif" align="absbottom"/></span></h1>-->
</div>

<!--
<div id="cadre-panier-favori">
	<h1>Vos paniers favoris</h1>
	<div id="panier-favori"></div>
</div>
-->
  <!------------------------------------------------------------->
  <!-- IMPORTANT NOTICE:                                       -->
  <!-- Removing the following link will prevent this script    -->
  <!-- from working.  Unless you purchase the registered       -->
  <!-- version of TreeView, you must include this link.        -->
  <!-- If you make any unauthorized changes to the following   -->
  <!-- code, you will violate the user agreement.  If you want -->
  <!-- to remove the link, see the online FAQ for instructions -->
  <!-- on how to obtain a version without the link.            -->
  <!------------------------------------------------------------->
  <div style="position:absolute;top:0;left:0;"><table border="0"><tr><td><font size="-2"><a href="http://www.treemenu.net/" target="_blank"></a></font></td></tr></table></div>

  <!-- Build the browser's objects and display default view  -->
  <!-- of the tree.                                          -->
  <script>initializeDocument()</script>
  <noscript>
   A tree for site navigation will open here if you enable JavaScript in your browser.
  </noscript>

<div style="text-align:center;">
	<input id="deconnexion" type="button" value="Déconnexion" class="button annuler" style="background-image:url(gfx/delete_32.png);padding-left:40px;" />
</div>

<div id="cadre-bestof">
	<h1>Vos 10 achats courant</h1>
	<table id="bestof">
		<tr><td>Chargement <img src="gfx/loading4.gif"/></td></tr>
	</table>
</div>
</body>
</html>