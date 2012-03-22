<?
include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$droit = recuperer_droit();

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

</head>
<body style="margin:0px;padding:0px;">
<div style="width:100%;background-color:#DDD;margin-bottom:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">Intranet <?=SOCIETE?> &gt;&gt; OUTILS</div>

<center>
<table style="width:70%;text-align:center;border:solid 1px #AAA;">
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="ordinateur/index.php"><img src="ordinateur/gfx/computer.png"><br>Ordinateur</a><br></td>
	<td><a href="plan_de_vente/index.html"><img src="plan_de_vente/gfx/plan_de_vente.png"><br>Plan de vente</a></td>
	<td style="width:50%;padding-bottom:20px;"><a href="cde_client_cde_fournisseur/index.php"><img src="cde_client_cde_fournisseur/gfx/assoc.png"><br>Relier cde adhérents et cde fournisseurs</a></td>
</tr>
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="edi/index.php"><img src="edi/gfx/edi3.png"><br>EDI</a><br></td>
	<td nowrap>
		<img src="feuille_tournee/gfx/feuille_tournee.png"><br>
		<a href="feuille_tournee/feuille_tournee.php">Feuille de tournée</a><br/>
		<a href="feuille_tournee/carte_tournee.php">Carte de tournée</a>
	</td>
	<td style="width:50%;padding-bottom:20px;">
		<a href="http://10.211.14.10/intranet/outils/ged/"><img src="ged/gfx/ged_icon.png"><br>GED</a>
	</td>
</tr>
<tr>
	<td style="width:50%;padding-bottom:20px;"><a href="date_liv/index.php"><img src="date_liv/gfx/date2mail.png"><br>Prévenir adh des dates de livraison<br/>ou de mise à dispo</a></td>
	<td>
		<img src="tarif_a_venir/gfx/tarif_a_venir.png"><br/>Comparaison de tarif<br/>
		<a href="tarif_a_venir/tarif_a_venir_v7.php">A venir</a><br/>
<!--		<a href="tarif_a_venir/time_machine.php"><del>Anciens tarifs</del></a>  -->
	</td>
	<td style="width:50%;padding-bottom:20px;">
		<!--<a href="cde_spe_non_livre/index.php"><img src="cde_spe_non_livre/gfx/cde_spe_non_livre.png"><br>Suivi des cde sp&eacute;cial</a>-->
		<a href="fiche_adherent/index.php"><img src="fiche_adherent/gfx/fiche_adherent.png"><br>Fiches adhérents</a>
	</td>
</tr>

<tr>
	<td><!--<a href="reliquat_a_livrer/index.php"><img src="reliquat_a_livrer/gfx/reliquat_a_livrer.png"><br>Reliquats à préparer et livrer</a>-->
		<a href="tableau_livraison/historique_commande.php"><img src="tableau_livraison/gfx/icon_tableau.png"><br>Tableau de bords des livraisons</a>
	</td>
	<td><a href="bon_mise_en_stock/index.php"><img src="bon_mise_en_stock/gfx/bon_mise_en_stock.png"><br>Bon de mise en stock</a></td>
	<td><a href="fiche_fournisseur/index.php"><img src="fiche_fournisseur/gfx/fiche_fournisseur.png"><br>Fiches Fournisseurs</a></td>
</tr>
<tr>
	<td><a href="feuille_temps/index.php"><img src="feuille_temps/gfx/icon.jpg"><br>Feuille de temps</a></td>
	<td><a href="diff_facture_fournisseur/index.php"><img src="diff_facture_fournisseur/gfx/diff_facture_fournisseur.png"><br>Différence facturation fournisseur</a></td>
	<td>
<?		if ($droit & PEUT_EDITER_ARTICLE_EN_MASSE) { ?>
			<a href="edition_article_en_masse/index.php"><img src="edition_article_en_masse/gfx/edition_article_en_masse.png"><br>Edition d'article en masse</a>
<?		} ?>
	</td>
</tr>

</table>
</center>



<div style="width:100%;background-color:#DDD;margin-top:10px;height:30px;padding-left:50px;font-weight:bold;padding-top:10px;">
	<a href="wiki/">[Wiki]</b></a>&nbsp;&nbsp;&nbsp;&nbsp;
</div>

</body>
</html>