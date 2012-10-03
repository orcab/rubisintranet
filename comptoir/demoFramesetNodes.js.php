<?php

session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
?>

// Configures whether the names of the nodes are links (or whether only the icons are links).
USETEXTLINKS = 1;

// Configures whether the tree is fully open upon loading of the page, or whether only the root node is visible.
STARTALLOPEN = 0;

// Specify if the images are in a subdirectory;
ICONPATH = '../js/treeview/images/';

// DECLARATION DE LA RACINE
foldersTree = gFld("<i>ROOT</i>", "")
  foldersTree.treeID = "Frameset";

// ne rien changer au dessus d'ici

<?
$hide_family = array('00Q'=>true,'00R'=>true,'100'=>true,'101'=>true,'102'=>true,'103'=>true,'104'=>true,'105'=>true,'106'=>true,'00P.P98'=>true,'00P.P97'=>true,'00N.N97'=>true,'00N.N98'=>true,'00N.N99'=>true,'00H.H96'=>true,'00H.H97'=>true,'00D.D98'=>true,'00A.A97'=>true,'00T'=>true,'T'=>true,'99M'=>true,'00W'=>true,'00S'=>true,'00U'=>true,'100.100'=>true,'104.104'=>true,'00A.A96'=>true,'00A.A98'=>true,'00A.A99'=>true,'00E.E97'=>true,'00Q.Q00'=>true,'00R.R03'=>true,'00T.T00'=>true,'00W.W01'=>true);


// stock toutes les familles
$pdv = array();
$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());
while($row = mysql_fetch_array($res))
	$pdv[$row['chemin']]=$row;

// recupération du nombre d'article par categ
$nb_article_cumul_by_categ = array();

$sql = <<<EOT
SELECT		chemin,
			COUNT(*) as nb
--			(SELECT qte	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as stock_afa,
--			(SELECT qte	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as stock_afl
FROM		article A
GROUP BY	chemin
EOT;

$res = mysql_query($sql) or die("Ne peux pas récupérer les chemin de la table article : ".mysql_error());
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
//$res = mysql_query("SELECT * from pdvente ORDER BY chemin ASC") or die("Ne peux pas récupérer les infos de la table pdvente : ".mysql_error());

foreach($pdv as $row) {
		if (isset($nb_article_cumul_by_categ[$row['chemin']]) && $nb_article_cumul_by_categ[$row['chemin']]>=0 && !array_key_exists($row['chemin'],$hide_family)) {
			$libelle = "<div class=\\\"menu \\\"><b>$row[libelle]</b> ";
			$libelle .= isset($nb_article_cumul_by_categ[$row['chemin']])	? '('.$nb_article_cumul_by_categ[$row['chemin']].')'	:'' ;
			$libelle .= "</div>";
?>
			aux<?=$row['niveau']?> = insFld(<?= $row['niveau'] == 1 ? 'foldersTree' : 'aux'.($row['niveau'] - 1) ?>, gFld("<?=$libelle?>","affiche_article.php?chemin=<?=$row['chemin']?>"));
<?		}
} ?>