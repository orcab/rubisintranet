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

<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../js/fontawesome/css/bootstrap.css">
<link rel="stylesheet" href="../js/fontawesome/css/font-awesome.min.css">
<!--[if IE 7]>
<link rel="stylesheet" href="../js/fontawesome/css/font-awesome-ie7.min.css">
<![endif]-->
<link rel="stylesheet" href="../js/fontawesome/css/icon-custom.css">

<!--<style type="text/css">@import url(../js/boutton.css);</style>
<style type="text/css">@import url(../js/tactile.css);</style>-->

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

span.ref_fournisseur { font-weight:bold; }

span.designation { font-style:italic; }

div#panier-favori ol a {
	display:block;
	margin-bottom:5px;
}

input#deconnexion {
	margin-top:1em;
	margin-bottom:1em;
}

input.supprime-article {
	background-image:url(gfx/delete_32.png);
	background-position: center center;
	width:50px;
}

/* une ligne (icon + libelleé) */
#domRoot > div { height:32px; }
#domRoot .menu { font-size:1.1em;margin-left:5px; }


a.btn {
	display:block;
	width:50%;
	margin:auto;
	margin-top:5px;
}

a.btn:last-child { margin-bottom:5px; }

div#panier { border-bottom:dotted 1px #0C3A6D; }

td.ajout {
	width:75px;
}

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


function vider_panier() {
	if (confirm("Voulez vous vraiment supprimer votre panier ?")) {
		$('#panier').html('<img src="gfx/loading4.gif"/>');
		$.ajax({ // on supprime tout le panier
				url: 'ajax.php',
				type: 'GET',
				data: 'what=delete_panier_all',
				success: function(result) { 
					$.ajax({ //  On va chercher le panier actuel
						url: 'ajax.php',
						type: 'GET',
						data: 'what=get_panier',
						success: function(result){ $('#panier').html(result); }	
					});
				}
		});
	}
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
	if (document.rechercher.search_text_box.value.length >= 2) {
		document.rechercher.search_text.value = document.rechercher.search_text_box.value;
		document.rechercher.search_text_box.value = '';
		return true;
	} else {
		return false;
	}
}


// une fois la page chargé.
$(document).ready(function() {
	$.ajax({ //  On va chercher le panier actuel
			url: 'ajax.php',
			type: 'GET',
			data: 'what=get_panier',
			success: function(result){ $('#panier').html(result); }	
	});


	$('body').delegate('a.affiche_article','click',function(){
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

<style>
#search_text {
	margin-left:5px;
	margin-right:10px;
	display:inline;
	width:10em;
	height: 2em;
	font-size: 1.2em;
}
</style>

</head>

<body>
<form name="rechercher" method="POST" action="affiche_article.php" style="margin-bottom:5px;" target="basefrm" onsubmit="return verif_champs();">
<input type="text" name="search_text" id="search_text"/>
<a class="btn" href="#" onclick="document.rechercher.submit();" style="width:100px;display:inline;white-space:nowrap;">
<i class="icon-search"></i> Rechercher</a>
<div style="font-size:0.7em;margin-left:5px;margin-top:5px;">(code, référence, désignation)</div>
</form>

<!--<div id="cadre-panier">
	<h1>Votre panier</h1>
	<div id="panier"></div>
	<h1><a href="validation_panier.php" target="_top">Valider le panier <img src="gfx/down-arrow-32.png" style="vertical-align:bottom;"/></a></h1>
</div>-->

<div id="cadre-panier">
	<h1>Votre panier</h1>
	<div id="panier"></div>
	<div id="bouton-action">
		<a class="btn btn-success" href="validation_panier.php" target="_top"><i class="icon-circle-arrow-right icon-large"></i> Valider le panier</a>
		<a class="btn btn-danger" href="#" onclick="vider_panier();"><i class="icon-trash icon-large"></i> Vider le panier</a>
	</div>
</div>


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
<!--  <div style="position:absolute;top:0;left:0;"><table border="0"><tr><td><font size="-2"><a href="http://www.treemenu.net/" target="_blank"></a></font></td></tr></table></div> -->

  <!-- Build the browser's objects and display default view  -->
  <!-- of the tree.                                          -->
 <!--
  <script>initializeDocument()</script>
  <noscript>
   A tree for site navigation will open here if you enable JavaScript in your browser.
  </noscript>

-->

<!-- GESTION DE L'ARBORESCENCE DES ARTICLES -->
<link rel="stylesheet" href="css/accordeon.css">
<script language="javascript" src="accordeon.js"></script>
<div class="accordion">
<?
$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());
while($row = mysql_fetch_array($res)) { ?>
	<div class="lvl-<?=$row['niveau']?>" data="<?=$row['chemin']?>"><?=$row['libelle']?></div>
<? } ?>
</div>

<div id="cadre-bestof">
	<h1>Vos 10 achats courant</h1>
	<table id="bestof">
		<tr><td>Chargement <img src="gfx/loading4.gif"/></td></tr>
	</table>
</div>
</body>
</html>