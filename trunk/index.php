<?

include('inc/config.php');

session_start();
$_SESSION = array();
session_destroy();

?><html>
<head>
<title>Intranet</title>
<style>

body,td{
	font-family:verdana;
	font-size:0.8em;
}

a img { 
	border:none;
}

a {
	text-decoration:none;
}

a:hover {
	text-decoration:underline;
}

img:hover {
	-moz-transform: scale(1.1);
}


</style>

<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/infobulle/infobulle.js"></script>
<style type="text/css">@import url(js/infobulle/infobulle.css);</style>

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Intranet <?=SOCIETE?></div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="article/creation_article.php"><img src="article/gfx/creation_article.png"><br>Création d'article</a><br></td>
	<td><a href="commande_fournisseur/historique_commande.php"><img src="commande_fournisseur/gfx/commande_fournisseur.png"><br>Commandes Fournisseur</a><br></td>
	<td style="width:50%;padding-bottom:20px;"><a href="devis2/index.html"><img src="devis/gfx/creation_devis.png"><br>Devis Exposition</a><br></td>
</tr>
<tr>
	<td><a href="article/historique_creation_article.php"><img src="article/gfx/historique_creation_article.png"><br>Historique article</a><br></td>
	<td><a href="devis_rubis/historique_devis.php"><img src="devis_rubis/gfx/devis_rubis.png"><br>Devis Rubis</a><br></td>
	<td><a href="anomalie/index.html"><img src="anomalie/gfx/anomalie.png"><br>Anomalies</a><br><br></td>
</tr>
<tr>
	<td><a href="outils/index.php"><img src="outils/gfx/icon_tools.png"><br>Outils</a></td>
	<td><a href="commande_adherent/historique_commande.php"><img src="commande_adherent/gfx/commande_adherent.png"><br>Commande Adhérents</a><br></td>
	<td><a href="tarif2/"><img src="tarif2/gfx/catalogue.png"><br>Catalogue papier</a><br></td>
	<td></td>
</tr>
</table>
</center>



<div style="width:100%;background-color:#DDD;margin-top:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">
	<a href="wiki/">[Wiki]</b></a>&nbsp;&nbsp;&nbsp;&nbsp;
</div>

</body>
</html>