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
// stock toutes les familles
$pdv = array();
$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());
while($row = mysql_fetch_array($res))
	$pdv[$row['chemin']]=$row;

// recupération du nombre d'article par categ
$nb_article_cumul_by_categ = array();
$res = mysql_query("SELECT chemin,COUNT(*) as nb FROM article GROUP BY chemin") or die("Ne peux pas récupérer les chemin de la table article : ".mysql_error());
while($row = mysql_fetch_array($res)) {
	$chemin_exploded = explode('.',$row['chemin']);
	for($i=0;$i<sizeof($chemin_exploded);$i++) { // on parcours chaque parti du chemin pour faire un cumul
		$chemin_slice = join('.',array_slice($chemin_exploded,0,$i+1));

		if (!array_key_exists($chemin_slice,$nb_article_cumul_by_categ))
			$nb_article_cumul_by_categ[$chemin_slice] = 0;

		if (array_key_exists($chemin_slice,$pdv)) // la famille de l'article existe
			$nb_article_cumul_by_categ[$chemin_slice] += $row['nb'];
	}
}

// construction de l'arbre
$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());

foreach($pdv as $row) {
	$libelle = "<div class=\\\"menu ".(!isset($nb_article_cumul_by_categ[$row['chemin']]) || $nb_article_cumul_by_categ[$row['chemin']]==0 ? 'empty' : '')."\\\"><b>$row[libelle]</b> ";
	$libelle .= isset($nb_article_cumul_by_categ[$row['chemin']])	? '('.$nb_article_cumul_by_categ[$row['chemin']].')'	:'' ;
	$libelle .= " [$row[chemin]]</div>";
?>
	aux<?=$row['niveau']?> = insFld(<?= $row['niveau'] == 1 ? 'foldersTree' : 'aux'.($row['niveau'] - 1) ?>, gFld("<?=$libelle?>","affiche_article.php?chemin=<?=$row['chemin']?>"));
<? } ?>