<?php
	include('../../inc/config.php');

	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
?>

// Configures whether the names of the nodes are links (or whether only the icons are links).
USETEXTLINKS = 1;

// Configures whether the tree is fully open upon loading of the page, or whether only the root node is visible.
STARTALLOPEN = 0;

// Specify if the images are in a subdirectory;
ICONPATH = '../../js/treeview/images/';

// DECLARATION DE LA RACINE
foldersTree = gFld("<i>ROOT</i>", "")
  foldersTree.treeID = "Frameset";

// ne rien changer au dessus d'ici

<?
// recupération du nombre d'article par categ
$nb_article_by_categ = array();
$res = mysql_query("SELECT chemin,COUNT(*) as nb FROM article GROUP BY chemin") or die("Ne peux pas récupérer les chemin de la table article : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	$nb_article_by_categ[$row['chemin']] = $row['nb'] ;
}

// construction de l'arbre
$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());

while($row = mysql_fetch_array($res)) { ?>
	aux<?=$row['niveau']?> = insFld(<?= $row['niveau'] == 1 ? 'foldersTree' : 'aux'.($row['niveau'] - 1) ?>, gFld("<div class=\"menu\"><b><?=$row['libelle']?></b> <?=isset($nb_article_by_categ[$row['chemin']]) ? '('.$nb_article_by_categ[$row['chemin']].')' : ''?> [<?=$row['chemin']?>]</div>", "affiche_article.php?chemin=<?=$row['chemin']?>"));
<? } ?>