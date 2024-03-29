<?php
include('../inc/config.php');
session_start();
if (!isset($_SESSION['info_user']['username'])) pas_identifie();
$info_user = $_SESSION['info_user'];
//print_r($info_user);


$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


// GESTION des images
// pour la prod chez OVH
if ($_SERVER['HTTP_HOST'] == 'www.coopmcs.com') {
	define('PREFIX_IMAGE_PATH','images/');
} else {
	// pour les tests en local
	define('PREFIX_IMAGE_PATH','../../intranet/tarif2/miniatures/');
}

$IMAGES = array();
if (file_exists('../outils/plan_de_vente/images_data.php'))
	include '../outils/plan_de_vente/images_data.php';
else
	echo ("Impossible de charger le fichier de cache des images");
//print_r($IMAGES);exit;


$page_accueil = false; // savoir si l'on est ou pas sur la page d'accueil


if (isset($_GET['chemin'])) { // recherche par arbor�rence
	$_SESSION['chemin'] = $_GET['chemin'] ;
	unset($_SESSION['search_text']);
}

if (isset($_POST['search_text'])) { // recherche de code
	$_SESSION['search_text'] = $_POST['search_text'] ;
	unset($_SESSION['chemin']);
}

if (isset($_GET['search_text'])) { // recherche de code
	$_SESSION['search_text'] = $_GET['search_text'] ;
	unset($_SESSION['chemin']);
}


if (isset($_GET['reset_critere'])) {
	unset($_SESSION['chemin']);
	unset($_SESSION['search_text']);
}

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

<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="javascript" src="../js/jquery.tablesorter.min.js"></script>
<style type="text/css">@import url(../js/tablesortable/style.css);</style>
<!--<style type="text/css">@import url(../js/tactile.css);</style>-->
<style>
body { margin:0px; }
body,pre {
	font-family: verdana,helvetica;
}
a img { border:none; }

div#debug {
	color:white;
	font-weight:bold;
	background-color:red;
	text-align:center;
	width:80%;
}

table#article {
	width:99%;
	border-spacing: 0px;
	border-collapse: collapse;
}

table#article caption {
	text-align:left;
	margin-bottom:10px;
}

table#article th {
	border:solid 1px grey;
	padding:1px;
}

table#article td {
	border:solid 1px grey;
	padding:3px;
	font-size:1.5em;
}


.designation pre {  margin:0; }
.photo { text-align:center; }
table#article td.qte { border-right:none; vertical-align:middle;}
table#article td.panier { border-left:none; vertical-align:middle; }
.prix_net { text-align:right; font-weight:bold; display:none; }
.prix_public { text-align:right; }
.stock {
	width:30px;
	text-align:center;
}

td strong {
	font-weight:bold;
	color:green;
}

strong.condi { color:black; }
input.qte {
	text-align: right;
    font-size: 2.5em;
    width: 1.5em;
}

td.qte,td.panier {
    width: 70px;
}

div#overlay {
	display:none;
	background:black;
	color:white;
	width:20em;
	font-weight:bold;
	text-align:center;
	position:absolute;
	top:0px;
	right:3px;
}

div#legend { margin-left:1em; }
a.similaire			{ text-decoration:none; color:grey;}
a.similaire:hover	{ color:black; }
a.home				{ text-decoration:none; color:white; font-weight:bold;}
a.home:hover		{ color:yellow; }

img.photo { width:50px; }

div#photo {
	padding:15px;
	border:solid 2px #555;
	background:white;
	display:none;
	position:absolute;
	color:black;
	z-index:99;
}

/* change le background si pas de stock */
tr.stock_a_0 { background:url(gfx/no-stock.png) repeat-x center center; }

/* cache les element de commande s'il n'y a pas de stock */
tr.stock_a_0 .panier ,tr.stock_a_0 .qte  { visibility:hidden; }

/* cache complement si AFL ou non stock� */
tr.nonstock { display:none; }
.stock_afl { display:none; }

/* test type jQuery mobile */
label.mobile {
	border:solid 1px #ccc;
	border-radius:5px;
	padding:5px;
	text-shadow:none;
	color:#111;
	font-weight:normal;
	text-align:left;
	background-image:-moz-linear-gradient( top , #fdfdfd, #eee );
}

label.mobile-block {
	margin-bottom:3px;
	display:block;
	width:11em;
}

label.mobile:not(.mobile-checked):hover {
	color:#000;
	border-color:#aaa;
	text-shadow:none;
	background-image:-moz-linear-gradient( top, #fefefe, #f5f5f5 );
	box-shadow: 0 0 2px #aaa;
	cursor:pointer;
}

label.mobile-checked {
	border-color:#155678;
	background-image:-moz-linear-gradient( top, #83b8e2, #5393c5 );
	color:white;
	font-weight:bold;
	cursor:pointer;
	text-shadow:grey 0px -1px;
	box-shadow: 0 0 5px #6a9dca;
}

label.mobile > input[type="checkbox"] {
	position:relative;
	top:2px;
}
</style>
<script language="javascript">
<!--

function ajout_panier(code_article,conditionnement) {

	var qte = document.article.elements['qte_'+code_article].value ;

	if (conditionnement > 1) { // v�rifie le conditionnement
		if ((qte % conditionnement) != 0) {
			var multiple_sup = Math.ceil(qte / conditionnement) * conditionnement ;
			if (confirm("La quantit� command�e ("+qte+") n'est pas un multiple de "+conditionnement+"\nVoulez vous arrondir � "+multiple_sup+" ?")) {
				document.article.elements['qte_'+code_article].value = multiple_sup;
				qte = multiple_sup;
			} else {
				return;
			}
		}
	}

	if (qte > 0) {
		$.ajax({url: 'ajax.php',
				type: 'GET',
				data: 'what=ajout_panier&code_article='+escape(code_article)+':'+qte,
				success: function(result){ top.frames[0].document.getElementById('panier').innerHTML=result; }	
		});
		document.article.elements['qte_'+code_article].value = '';
	} else {
		alert("Quantit� � 0. Saisissez une quantit�");
	}
}



$(document).ready(function() {
	
	$('#article').tablesorter( {sortList: [[7,1]]} ); // sort sur le code article par d�faut
	
	$('#article').bind('sortStart',function() {
		$('#overlay').show();
	}).bind('sortEnd',function() {
		$('#overlay').hide();
	});


	$('img.photo').bind('mouseover',function(){
		//alert($(this).offset().top + 'px    '+$(this).offset().left+'px');
		var offset = $(this).offset();
		$('#photo')	.html('<img src="'+$(this).attr('src')+'"/>')
					.css({'top':offset.top+'px','left':offset.left+'px'})
					.bind('mouseout',function(){
						$('#photo').hide();
					})
					.show();
	});// fin mouseover


	// on clique sur ajout panier
//	$('.ajout_panier').click(function(){
//		ajout_panier($(this));
//	});


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


	// click sur affiche les produits non stock�
	$('#show_produit_stock').click(function(){
		if(this.checked)
			$('.nonstock').show();
		else
			$('.nonstock').hide();
	});


	// click sur un champs texte, on selectionne tout le texte par d�faut
	$('input[type=text]').focusin(function(){
		$(this).select();
	});


	// clique sur le bouton deconnexion
	$('body').delegate('#deconnexion','click',function(){
		parent.document.location.href='index.php?deconnexion=1';
	});

}); // document.ready

//-->
</script>
</head>
<body>

<div id="photo"></div>

<form name="article" method="POST">

<!-- entete + bouton de selection -->
<table style="width:100%;">
<tr>
	<!-- photo -->
	<td rowspan="2" class="photo" style="text-align:left;vertical-align:middle;">
<?			if (isset($_SESSION['chemin']) && array_key_exists($_SESSION['chemin'],$IMAGES)) { // il y a une photo ?>
				<img class="photo" src="<?=PREFIX_IMAGE_PATH.$IMAGES[$_SESSION['chemin']][0]?>"/>
<?			} ?>
	</td>
	<td style="text-align:left;vertical-align:top;border:solid 1px #0C3A6D;color:white;background-color:#0C3A6D;">
		<div style="width:69%;text-align:left;float:left;">&nbsp;
<?		if (isset($_SESSION['chemin'])) {
				
			// d�cortique le chemin pour retrouver les lib�ll�s
			$codes_chemin = explode('.',$_SESSION['chemin']);
			$condition = array();
			for($i=0 ; $i<sizeof($codes_chemin); $i++) {
				$condition[] = "chemin='".join('.',array_slice($codes_chemin,0,$i+1))."'" ;
			}

			$res = mysql_query("SELECT chemin,libelle FROM pdvente WHERE ".join(" OR ",$condition)." ORDER BY chemin ASC") or die("Ne peux pas retrouver les lib�ll�s des familles ".mysql_error()); // recupere tous les libell�s
			$chemin_libelle = array();
			while($row = mysql_fetch_array($res)) {
				$chemin_libelle[] = $row['libelle'];
			}// fin while
			echo "<b>".join(" &rArr; ",$chemin_libelle)."</b>";
?>
<?		} 
	
		if (isset($_SESSION['search_text'])) { ?>
			Recherche de [<b><?=$_SESSION['search_text']?></b>]
<?		}  ?>
		</div>
		<div style="width:30%;text-align:right;float:left;">
			<a href="affiche_article.php?reset_critere=1" class="home"><i class="icon-home"></i> Page d'accueil</a>
		</div>
</td>


</tr>
</table>

<center><div id="overlay">Un moment s'il vous plait...</div></center>

<div id="legend">
<img src="gfx/stock2-0.png" /> Non stock�&nbsp;&nbsp;&nbsp;
<img src="gfx/stock2-1.png" /> En rupture&nbsp;&nbsp;&nbsp;
<img src="gfx/stock2-2.png" /> Stock limit�&nbsp;&nbsp;&nbsp;
<img src="gfx/stock2-3.png" /> Stock suffisant&nbsp;&nbsp;&nbsp;
<i class="icon-truck icon-large"></i> R�appro en cours

<label for="show_produit_stock" class="mobile mobile-block" style="width:20em;"><input id="show_produit_stock" type="checkbox">Afficher les produits sans stock</label>
</div>

<table id="article" class="tablesorter">
	<thead>
	<tr>
		<th class="photo">Photo</th>
		<th class="code_article">Code</th>
		<th class="fournisseur">Fourn</th>
		<th class="ref_fournisseur">Ref</th>
		<th class="designation">D�signation</th>
		<th class="prix_net no-sort">Px Adh</th>
		<th class="prix_public no-sort">Px Pub</th>
		<th class="stock_afa no-sort">Plescop</th>
		<th class="stock_afl no-sort">Caudan</th>
		<th colspan="2" class="qte no-sort">&nbsp;</th>
	</tr>
	</thead>
	<tbody>
<?
	$sql = '';

	if (isset($_SESSION['chemin'])) { // recherche par chemin
		$sql .= "chemin='".mysql_escape_string($_SESSION['chemin'])."'";
	} elseif (isset($_SESSION['search_text'])) { // recherche par text

		$phrase = split(' +',$_SESSION['search_text']); // on s�prare les mots
		$et  = array();
		// on v�rifie que chaque mot est pr�sent dans la d�signation (ET naturel)
		foreach ($phrase as $mot) {
			if ($mot) array_push($et,"designation LIKE '%$mot%'");
		}
		$et = join($et," AND ");

		$search_text = mysql_escape_string($_SESSION['search_text']);
		$sql .=		"(A.code_article LIKE '%$search_text%' OR ".
					" ($et) OR ".
					" ref_fournisseur LIKE '%$search_text%' OR ".
					" ref_fournisseur_condensee LIKE '%$search_text%')";

	} else { // pas de recherche --> on affiche la page d'accueil
		// on va rechercher les articles a mettre en page d'accueil
		$sql .= "1=1";
		$page_accueil = true ;
	}

	$sql  .= " ORDER BY designation ASC";
	$table = "article A";
	if ($page_accueil)
		$table .= " INNER JOIN page_accueil PA ON A.code_article = PA.code_article";

	$sql = <<<EOT
SELECT	A.code_article,fournisseur,code_fournisseur,ref_fournisseur,designation,prix_net,prix_public,conditionnement,unite,chemin,date_creation,DATEDIFF(NOW(),date_creation) as days_since_creation,
		(SELECT qte		FROM qte_article WHERE code_article=A.code_article and depot='AFA') as stock_afa,
		(SELECT mini	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as mini_afa,
		(SELECT qte_cde	FROM qte_article WHERE code_article=A.code_article and depot='AFA') as reappro_afa,
		(SELECT qte		FROM qte_article WHERE code_article=A.code_article and depot='AFL') as stock_afl,
		(SELECT mini	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as mini_afl,
		(SELECT qte_cde	FROM qte_article WHERE code_article=A.code_article and depot='AFL') as reappro_afl
FROM	$table
WHERE	1=1
		and
$sql
EOT;

	//echo $sql;
	$res = mysql_query($sql) or die("Ne peux pas r�cup�rer les infos de la table article : ".mysql_error());

	$i=0;
	$nb_stock = 0 ;
	while($row = mysql_fetch_array($res)) {
			$stocker	= $row['stock_afa'] != ''	? true : false;
			$stock_a_0	= $row['stock_afa'] <= 0	? true : false;
			if ($stocker)
				$nb_stock++;

			$row['code_article'] = trim($row['code_article']);
?>
		<tr id="ligne_<?=$row['code_article']?>" class="<?=$stocker?'stock':'nonstock'?> <?=$stock_a_0?'stock_a_0':''?>">
			<!-- photo -->
			<td class="photo">
<?				if (strlen($row['fournisseur']) > 0 && strlen($row['ref_fournisseur']) > 0) { ?>
					<img class="photo" src="http://www.coopmcs.com/hydra/getfile.php?fournisseur=<?=$row['code_fournisseur']?>&ref=<?=$row['ref_fournisseur']?>&largeur=500&hauteur=500"/>
<?				} ?>
<?				if (array_key_exists($row['code_article'],$IMAGES)) { // il y a une photo ?>
					<!-- <img class="photo" src="<?=PREFIX_IMAGE_PATH.$IMAGES[$row['code_article']][0]?>"/> -->
<?				} ?>
			</td>

			<td class="code_article"><?=isset($_SESSION['search_text']) ? preg_replace("/(".trim($_SESSION['search_text']).")/i","<strong>$1</strong>",$row['code_article']) : $row['code_article']?></td>

			<td class="fournisseur"><?=wordwrap($row['fournisseur'], 20, "<br />\n")?></td>

			<td class="ref_fournisseur"><?=isset($_SESSION['search_text']) ? preg_replace("/(".trim($_SESSION['search_text']).")/i","<strong>$1</strong>",$row['ref_fournisseur']) : $row['ref_fournisseur']?></td>

			<td class="designation">
				<pre><?
					// si l'article a moins de deux mois, on affiche un logo nouveau
					if ($row['days_since_creation'] < 60) { // article de mions de deux mois ?>
<i class="icon-tag icon-large" style="vertical-align:middle;" title="Article de moins de 2 mois cr�e le <?=join('/',array_reverse(explode('-',$row['date_creation'])))?>"></i> <?
					}

					if (isset($_SESSION['search_text'])) { // si un mot cl� de recherch�
						$designation = $row['designation'];
						foreach ($phrase as $mot) {
							if ($mot) $designation = preg_replace("/(".$mot.")/i","<strong>$1</strong>",$designation);
						}
						echo trim($designation);
					} else {
							echo trim($row['designation']);
					}	?></pre>

				<!-- condtionnement et unit� -->
<?				if ($row['conditionnement'] > 1) { ?>
					<br/><strong class="condi">Vendu par <?=$row['conditionnement']?><?=$row['unite']?></strong>
<?				} ?>

				<!-- article similaire -->
				<div style="text-align:right;"><a class="btn btn-small" href="<?=$_SERVER['PHP_SELF']?>?chemin=<?=$row['chemin']?>"><i class="icon-list"></i> Articles similiares</a></div>
			</td>

			<td class="prix_net" nowrap><?
				if ($row['conditionnement'] > 1) {
					printf('%d%s x %0.2f&euro;', $row['conditionnement'], $row['unite'], $row['prix_net'] );
					printf('<br/><small>%0.2f&euro;</small>',$row['conditionnement']*$row['prix_net']);
				} else {
					printf('%0.2f&euro;',$row['prix_net']);
				}
			?>
			</td>

			<td class="prix_public" nowrap><?
				if ($row['prix_public']!='0.00') {
					if ($row['conditionnement'] > 1) {
						printf('%d%s x %0.2f&euro;', $row['conditionnement'], $row['unite'], $row['prix_public'] );
						printf('<br/><small>%0.2f&euro;</small>',$row['conditionnement']*$row['prix_public']);
					} else {
						printf('%0.2f&euro;',$row['prix_public']);
					}
				} else {
					echo 'NC';
				}
			?>
			</td>

			<td class="stock stock_afa">
				<? if		($row['stock_afa'] == '') { // pas stock� ?>
						<img src="gfx/stock2-0.png"/>
				<? } elseif  ($row['stock_afa'] <= 0) { // en rupture ?>
						<img src="gfx/stock2-1.png"/>
				<? } elseif  ($row['stock_afa'] > 0 && $row['stock_afa'] <= $row['mini_afa']) { // en dessous du mini ?>
						<img src="gfx/stock2-2.png"/>
				<? } else { // au dessus du mini ?>
						<img src="gfx/stock2-3.png"/>	
				<? } ?>
				<? if ($row['reappro_afa'] > 0) { // reappro de stock en cours ?>
					<br/><br/><img src="gfx/reappro.png"/>
				<? } ?>
			</td>

			<td class="stock stock_afl">
				<? if		($row['stock_afl'] == '') { // pas stock� ?>
						<img src="gfx/stock2-0.png"/>
				<? } elseif  ($row['stock_afl'] <= 0) { // en rupture ?>
						<img src="gfx/stock2-1.png"/>
				<? } elseif  ($row['stock_afl'] > 0 && $row['stock_afl'] <= $row['mini_afl']) { // en dessous du mini ?>
						<img src="gfx/stock2-2.png"/>
				<? } else { // au dessus du mini ?>
						<img src="gfx/stock2-3.png"/>	
				<? } ?>
				<? if ($row['reappro_afl'] > 0) { // reappro de stock en cours ?>
					<br/><br/><img src="gfx/reappro.png"/>
				<? } ?>
			</td>

			<td class="qte" nowrap="nowrap">
				<input class="qte" type="text" name="qte_<?=$row['code_article']?>" value="" size="6" placeholder=""/>
				<?= $row['conditionnement'] > 1 ? $row['unite'] :'' ?>
			</td>

			<td class="panier">
				<a class="btn btn-success icon-2x ajout_panier" onclick="ajout_panier('<?=$row['code_article']?>','<?=$row['conditionnement']?>');"><i class="icon-download-alt" title="Ajouter au panier"></i></a>
				<img src="gfx/loading4.gif"		class="loading"			style="display:none;"/><!-- loading cach� � la base -->
				<!--<input type="button" value="Ajouter au panier" onclick="ajout_panier('<?=$row['code_article']?>','<?=$row['conditionnement']?>');"/>-->
			</td>
		</tr>
<?		$i++;
	} ?>
	</tbody>
	<tfoot>
	</tfoot>
</table>
</form>
<br/><br/><br/>
<? if ($nb_stock <= 0) { ?>
	<script type="text/javascript">
	<!--
	$(document).ready(function(){
		// si aucun l'article affich� est stock�, on affiche les non stock�
		$('#show_produit_stock').attr('checked','checked');
		$('#show_produit_stock').parents('label').addClass('mobile-checked');
		$('.nonstock').show();
	});
	//-->
	</script>
<? } ?>

<style>
#footer {
    background-color: #0C3A6D;
    color: white;
    font-size: 1em;
    width: 50%;
    border-radius: 20px 0 0 0;
    text-align: right;
    padding: 5px;
    position: fixed;
    right: 0;
    bottom: 0;
}
</style>
<div id="footer">
	Utilisateur connect� : <?=$info_user['name']?>&nbsp;
	<a id="deconnexion" class="btn btn-danger" href="#"><i class="icon-signout icon-large"></i> D�connexion</a>
</div>

</body>
</html>
<?

// recursive listing directory
function listdir($dir='.') {
    if (!is_dir($dir)) {
        return false;
    }
   
    $files = array();
    listdiraux($dir, $files);

    return $files;
}

function listdiraux($dir, &$files) {
    $handle = opendir($dir);
    while (($file = readdir($handle)) !== false) {
        if ($file == '.' || $file == '..') {
            continue;
        }
        $filepath = $dir == '.' ? $file : $dir . '/' . $file;
        if (is_link($filepath))
            continue;
        if (is_file($filepath))
            $files[] = $filepath;
        else if (is_dir($filepath))
            listdiraux($filepath, $files);
    }
    closedir($handle);
} 


?>