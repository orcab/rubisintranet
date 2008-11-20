<?
include('../inc/config.php');

$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

// l'endroit ou l'on est dans l'arborescence
if (isset($_GET['chemin']) && $_GET['chemin']) {
	$chemin = $_GET['chemin'] ;
} elseif (isset($_POST['last_chemin']) && $_POST['last_chemin']) {
	$chemin = $_POST['last_chemin'] ;
} else {
	$chemin = '' ;
}

if (isset($_POST['action']) && $_POST['action']=='add' && $_POST['add_nom']) { // on ajoute une categorie a l'endroit selectionné
	$sql = "INSERT INTO tarif_categ (nom,chemin,id_style) VALUES ('".mysql_escape_string($_POST['add_nom'])."','".mysql_escape_string($chemin)."',NULL)";
	//echo $sql ;
	mysql_query($sql) or die("Ne peux pas ajouter la categorie ".mysql_error());
	$new_id_categ = mysql_insert_id();
	$message = "Categorie '$_POST[add_nom]' Ajoutée";

	// on se déplace dans la categ juste au dessous
	$chemin .= "-$new_id_categ";


} elseif (isset($_POST['action']) && $_POST['action']=='rename' && $_POST['rename_nom']) { // on renome une categorie
	$sql = "UPDATE tarif_categ SET nom='".mysql_escape_string($_POST['rename_nom'])."' WHERE id=$_POST[id_categ]";
	mysql_query($sql) or die("Ne peux pas renomer la categorie ".mysql_error());
	$message = "La catégorie correctement à été renomé";


} elseif (isset($_POST['action']) && $_POST['action']=='delete' && isset($_POST['id_categ']) && $_POST['id_categ']) { // on supprime une categorie selectionnée
	mysql_query("DELETE FROM tarif_categ WHERE id=$_POST[id_categ]") or die("Ne peux pas supprimer la categorie ".mysql_error());
	mysql_query("UPDATE tarif_article SET id_categ=NULL WHERE id_categ=$_POST[id_categ]") or die("Ne peux pas déassocier les articles de cette categorie ".mysql_error());
	$message = "Categorie $_POST[id_categ] supprimée";
	
	

} elseif (isset($_POST['action']) && $_POST['action']=='sauve_propriete') { // on sauve les modif au niveau des proprietes
	$sql =	"UPDATE tarif_categ SET".
			" image='".mysql_escape_string($_POST['image_categ'])."',".
			" page_de_garde='".mysql_escape_string($_POST['page_de_garde_categ'])."',".
			" id_style=".e(0,explode('/',$_POST['style'])).",".
			" saut_de_page=".(isset($_POST['saut_de_page']) && $_POST['saut_de_page'] ? 1:0).
			" WHERE id=$_POST[id_categ]";
	mysql_query($sql) or die("Ne peux pas sauvegarder les propriétés ".mysql_error());
	$message = "Les modifications ont éte sauvegardé";



} elseif (isset($_POST['action']) && $_POST['action']=='associe' && $chemin) { // on associe les article à la categorie en cours
	$tmp = explode('-',$chemin);
	$id_categ = array_pop($tmp);
	if ($id_categ) {
		foreach($_POST as $key=>$val) {
			if (ereg("^associe_([0-9]+)$",$key,$regs)) { // on est sur un article a associé
				mysql_query("UPDATE tarif_article SET id_categ=$id_categ WHERE id=$regs[1]") or die("Ne peux pas associé les articles à cette categories ".mysql_error());
				//echo "UPDATE tarif_article SET id_categ=$id_categ WHERE id=$regs[1]<br>";
			}
		}
		$message = "Les associations ont éte faite";
	} else {
		$message = "Erreur : Aucune categorie spécifiée";
	}




} elseif (isset($_POST['action']) && $_POST['action']=='desassocie' && $chemin) { // on desassocie les article de leur categorie
	foreach($_POST as $key=>$val) {
		if (ereg("^desassocie_([0-9]+)$",$key,$regs)) { // on est sur un article a associé
			mysql_query("UPDATE tarif_article SET id_categ=NULL WHERE id=$regs[1]") or die("Ne peux pas associé les articles à cette categories ".mysql_error());
			//echo "UPDATE tarif_article SET id_categ=$id_categ WHERE id=$regs[1]<br>";
		}
	}
	$message = "Les désassociations ont éte faite";
}

?>
<html>
<head><title>Tarif : Administration des catégories</title>
<style>
body {
	font-family:Verdana;
	font-size:0.8em;
}

td,th {
	font-size:0.8em;
}

.open {
	padding-left:33px;
	height:20px;
	background:white url('gfx/folder-opened.png') no-repeat left center;
}

.close {
	padding-left:33px;
	height:20px;
	background:white url('gfx/folder-closed.png') no-repeat left center;
}

.active {
	background-color:navy;
	color:white;
}

a {
	text-decoration:none;
	color:inherit;
}

a:hover {
	text-decoration:underline;
}

table#interface td , table#interface th {
	border:solid 1px black;
}

table#arborescence td , table#arborescence th {
	border:none;
}

table#liste-article {
	margin-top:20px;
	border:solid 1px black;
	border-spacing: 0px;
	border-collapse: collapse;
}

table#liste-article td {
	border:none;
}

 table#liste-article th {
	border:solid 1px black;
	background-color:#DDDDDD;
 }

</style>


<SCRIPT LANGUAGE="JavaScript" src="base64.js"></SCRIPT>
<script language="javascript">
<!--

function affiche_ajouter_categ() {
	document.getElementById('btn_ajouter_categ').style.display='none';
	document.getElementById('form_ajouter_categ').style.display='block';
	document.admin_categ.add_nom.focus();
}

function affiche_renomer_categ() {
	document.getElementById('btn_renomer_categ').style.display='none';
	document.getElementById('form_renomer_categ').style.display='block';
	document.admin_categ.rename_nom.focus();
}

function affiche_propriete_categ() {
	document.location.href = '<?=$_SERVER['PHP_SELF']?>?<?=$_SERVER['QUERY_STRING']?>&edit_categ=1' ;
}

function affiche_image(monSelect) {
	document.getElementById('affiche_image').innerHTML = '<img src="image/electromenager/' + monSelect.options[monSelect.selectedIndex].value + '" width="150">' ;
}

function associe_image() {
	var monSelect = document.admin_categ.image_dir ;
	var monText   = document.admin_categ.image_categ ;

	var tmp = monText.value.split(/[,;]+/);
	tmp.push(monSelect.options[monSelect.selectedIndex].value); // on charge l'image à la suite des autre
	monText.value = tmp.join(',').replace(/^,+|,+$/,'');
}

function affiche_page_de_garde(monSelect) {
	document.getElementById('affiche_page_de_garde').innerHTML = '<img src="image/page_de_garde/' + monSelect.options[monSelect.selectedIndex].value + '" width="150">' ;
}

function affiche_style(monSelect) {
	var valeur = monSelect.options[monSelect.selectedIndex].value.split(/\//); // on sépare l'id des couleurs du style selectionné
	document.getElementById('colorDark').style.backgroundColor = valeur[1];
	document.getElementById('colorLight').style.backgroundColor = valeur[2];
}

function affiche_style_article(monSelect,id) {
	var valeur = monSelect.options[monSelect.selectedIndex].value.split(/\//); // on sépare l'id des couleurs du style selectionné
	document.getElementById('colorDark-article-'+id).style.backgroundColor = valeur[1];
	document.getElementById('colorLight-article-'+id).style.backgroundColor = valeur[2];
}

function suppr_categ() {
	if (confirm("Voulez-vous vraiment supprimer cette catégorie ?")) {
		envoi_formulaire('delete');
	}
}


function filtre(e) {
	if (e.keyCode == 13) { // entrée
		envoi_formulaire('filtrer');
	}
}

function inverse_selection(liste) {
	for(var i=0 ; i<=document.admin_categ.elements.length ; i++)
		if ((liste == 'associe'		&& document.admin_categ.elements[i].name.match(/^associe_\d+$/)) || 
			(liste == 'desassocie'	&& document.admin_categ.elements[i].name.match(/^desassocie_\d+$/))) // case a cocher pour l'association des articles
			document.admin_categ.elements[i].checked = document.admin_categ.elements[i].checked ? false : true;
}


function envoi_formulaire(what) {
	document.admin_categ.action.value = what;
	document.admin_categ.submit();
}

//-->
</script>
</head>
<body>
<?=isset($message) ? $message : ''?>
<form name="admin_categ" method="POST" action="admin_categ.php<?= isset($_GET['chemin']) && $_GET['chemin'] ? "?chemin=$_GET[chemin]":'' ?>">
<input type="hidden" name="action" value="">

<form name="admin_categ" method="POST" action="admin_categ.php">

<table id="interface">
	<tr>
	<td valign="top" rowspan="2"><!-- CASE DE GAUCHE -->

	<table id="arborescence"><!-- ARBORESCENCE -->
		<tr><td class="open" style="font-style:italic;"><a href="<?=$_SERVER['PHP_SELF']?>">ROOT</a></td></tr>
<?
	$id_categ=0;
	$nom_categ= '';
	$images_categ= '';
	$style_categ= '';
	$saut_page_categ = 0;

	if ($chemin) {
		$tmp = array("chemin=''");

		$arbre_des_pere = explode('-',$chemin);
		$id_categ=$arbre_des_pere[sizeof($arbre_des_pere)-1];

		for($i=0 ; $i<sizeof($arbre_des_pere) ; $i++) { //$arbre_des_pere as $id_pere) {
			$tmp[] = "chemin = '".join('-',array_slice($arbre_des_pere,0,$i+1))."'";
		}
		$where = '('.join(' OR ',$tmp).')' ; // on categorie de cliqué
	}
	else
		$where = "chemin=''"; // sur root

	$sql = <<<EOT
SELECT	id, id_style, saut_de_page, page_de_garde, image, nom, chemin,
		TRIM(BOTH '-' FROM CONCAT(chemin,'-',id)) AS chemin_complet,
		TRIM(BOTH '-' FROM CONCAT( (SELECT nom FROM tarif_categ WHERE id=chemin) ,'-',nom) ) AS chemin_complet_nom,
		(SELECT count(id) FROM tarif_article WHERE id_categ = tc.id) AS nb_article
FROM	tarif_categ tc
WHERE	$where
ORDER BY chemin_complet ASC,nom ASC
EOT;

	//echo $sql;
	$res = mysql_query($sql) or die("ne peux pas recupérer les categorie ".mysql_error());
	while($row = mysql_fetch_array($res)) { ?>
		<tr>
			<td class="<?= $row['id']==$id_categ ? 'active':''?> <?= in_array($row['id'],$arbre_des_pere) ? 'open':'close'?>"
				style="background-position:<?= 16 * sizeof(explode('-',$row['chemin_complet'])) ?>px center;padding-left:<?= 16 * (sizeof(explode('-',$row['chemin_complet'])) + 2) ?>px;" nowrap>
<?					if ($row['image']) {?>
						<img src="gfx/gfx.png" style="margin-left:2px;">
<?					}
					if ($row['nb_article']) {?>
						<img src="gfx/list.png" style="margin-left:2px;">
<?					}
					if (!$row['id_style']) {?>
						<img src="gfx/caution.png" style="margin-left:2px;">
<?					} ?>
					<a href="<?=$_SERVER['PHP_SELF']?>?chemin=<?=$row['chemin_complet']?>"><?=$row['nom']?></a>
			</td>
		</tr>
<?	
		if ($row['id']==$id_categ) {
			$nom_categ		= $row['nom'];
			$images_categ	= ereg_replace('^,+|,+$','',$row['image']);
			$style_categ	= $row['id_style'];
			$saut_page_categ= $row['saut_de_page'];
			$page_de_garde_categ = $row['page_de_garde'];
		}
	
	}  ?>
	</table><!-- FIN ARBORESCENCE -->

	
	</td>
	<td style="height:12px;" valign="top" align="center"><!-- CASE DE DROITE -->
		<div id="btn_ajouter_categ"><a href="javascript:affiche_ajouter_categ();">Ajouter Sous-Catégorie</a></div>
		<div id="form_ajouter_categ" style="display:none;"><input type="text" name="add_nom" value=""> <input type="button" value="Enregistrer" onclick="envoi_formulaire('add');"></div>
	</td>

	<td style="height:12px;" valign="top" align="center">
		<div id="btn_renomer_categ"><a href="javascript:affiche_renomer_categ();">Renomer</a></div>
		<div id="form_renomer_categ" style="display:none;"><input type="text" name="rename_nom" value="<?=$nom_categ?>"> <input type="button" value="Enregistrer" onclick="envoi_formulaire('rename');"></div>
	</td>

	<td style="height:12px;" valign="top" align="center">
		<div id="btn_propriete_categ"><a href="javascript:affiche_propriete_categ();">Propriété</a></div>
	</td>


	<td style="height:12px;" valign="top" align="center">
<?	// n'affiche le boutton supprimé que si l'on est sur la derniere categ
		if (e('nb_fils',mysql_fetch_array(mysql_query("SELECT count(id) AS nb_fils FROM tarif_categ WHERE chemin LIKE '%$chemin'"))) == 0) { ?>
			<div id="btn_supprimer_categ"><a href="javascript:suppr_categ();">Supprimer</a></div>
<?		} else { ?>
			<div id="btn_supprimer_categ" style="text-decoration:line-through;">Supprimer</div>
<?		} ?>
	</td>

	</tr>
	<tr>
	<td colspan="10" valign="top">

<?	if (isset($_GET['edit_categ']) && $_GET['edit_categ']) { // edition des propriete de la categ ?>

		<div style="font-weight:bold;">Edition des propriétées</div>

		<!-- GETION DES L'IMAGES ASSOCIEES A LA SECTION-->
		<fieldset style="margin-top:20px;"><legend>Images</legend>
		<input type="text" name="image_categ" value="<?=$images_categ?>" size="60"><br>
			<span style="font-size:0.7em;">(séparé par des virgules)</span><br>
			<select name="image_dir" size="10" onchange="affiche_image(this);">
<?				if ($handle = opendir('image/electromenager')) {
					while (false !== ($file = readdir($handle))) {
						if ($file != "." && $file != ".." && eregi('\.(jpe?g|png)$',$file)) { // image jpeg ou png ?>
							<option value="<?=$file?>"><?=$file?></option>
<?						}
					}
					closedir($handle);
				}	?>
			</select><span id="affiche_image"></span><br>
			<input type="button" value="Associer l'image" onclick="associe_image();">
		</fieldset>


		<!-- GETION DU STYLE -->
		<fieldset style="margin-top:20px;"><legend>Style</legend>
			<select name="style" onchange="affiche_style(this);">
				<option value="NULL/#FFFFFF/#FFFFFF">Pas de style</option>
<?				$res = mysql_query("SELECT * FROM tarif_style") or die("Ne peux pas recupérér les styles ".mysql_error());
				$styleDark='';
				$styleLight='';
				while($row = mysql_fetch_array($res)) {
					$valeur = explode(',',$row['valeur']); ?>
					<option value="<?=join('/',array($row['id'],$valeur[0],$valeur[1]))?>"<?= $row['id']==$style_categ ? ' selected':''?>><?=$row['nom']?></option>
<?					
					if ($row['id']==$style_categ) {
						$styleDark=$valeur[0];
						$styleLight=$valeur[1];
					}
			
				} ?>
			</select>
			<div style="width:20%;">
				<div id="colorDark"  style="height:10px;background:<?=$styleDark?>;">&nbsp;</div>
				<div id="colorLight" style="height:10px;background:<?=$styleLight?>;">&nbsp;</div>
			</div>
		</fieldset>
		

		<!-- GETION DU SAUT DE PAGE -->
		<fieldset style="margin-top:20px;"><legend>Saut de page</legend>
			<label for="saut_de_page">Saut de page</label> : <input type="checkbox" name="saut_de_page" id="saut_de_page"<?= $saut_page_categ ? ' checked':'' ?>>
		</fieldset>
		

		<!-- GETION DE LA PAGE DE GARDE -->
		<fieldset style="margin-top:20px;"><legend>Page de garde</legend>
			<span style="font-size:0.7em;">(affecter une page de garde produit un saut de page automatique)</span><br>
			<select name="page_de_garde_categ" size="1" onchange="affiche_page_de_garde(this);">
				<option value="">Pas de page de garde</option>
<?				if ($handle = opendir('image/page_de_garde')) {
					while (false !== ($file = readdir($handle))) {
						if ($file != "." && $file != ".." && eregi('\.(jpe?g|png)$',$file)) { // image jpeg ou png ?>
							<option value="<?=$file?>"<?= isset($page_de_garde_categ) && $page_de_garde_categ==$file ? ' selected':''?>><?=$file?></option>
<?						}
					}
					closedir($handle);
				}	?>
			</select><span id="affiche_page_de_garde"></span>
		</fieldset>


		<!-- BOUTTON DE VALIDATION -->
		<div style="margin-top:20px;text-align:center;">
			<input type="button" value="Enregistrer" onclick="envoi_formulaire('sauve_propriete');">
			<input type="reset" value="Annuler">
		</div>


<?	} else { // fin edition des propriétés ?>


		<!-- TABLEAU DES ARTICLES -->
		<table id="liste-article">
			<tr>
				<th nowrap>Code article</th>
				<th nowrap>Fournisseur</th>
				<th nowrap>Designation</th>
				<th nowrap>image</th>
				<th nowrap>style</th>
				<th nowrap>&nbsp;</th>
				<th><input type="button" value="Désassocier" onclick="envoi_formulaire('desassocie');"><br>
					<input type="button" value="Inverser<?="\n"?>selection" onclick="inverse_selection('desassocie');"></th>
			</tr>
<?	
	$res_style = mysql_query("SELECT * FROM tarif_style") or die("Ne peux pas recupérér les styles ".mysql_error());

	$sql = <<<EOT
SELECT	code_article,id,
		(SELECT designation FROM tarif_article	WHERE code_article=ta.code_article) AS tarif_designation,
		(SELECT designation FROM		article WHERE code_article=ta.code_article) AS article_designation,
		(SELECT fournisseur FROM		article WHERE code_article=ta.code_article) AS fournisseur,
		image,
		id_style
FROM	tarif_article ta
WHERE	id_categ=$id_categ
ORDER BY designation
EOT;
	
	$res = mysql_query($sql) or die("ne peux pas recupérer les articles ".mysql_error());
	while($row = mysql_fetch_array($res)) { ?>
		<tr>
			<td nowrap><?=$row['code_article']?></td>
			<td><?=$row['fournisseur']?></td>
			<td id="td_article_designation_<?=$row['id']?>" onclick="edit_article_designation('<?=$row['id']?>');">
<?					if ($row['tarif_designation']) { ?>
						<?=$row['tarif_designation']?><br><span style="font-size:0.7em;color:grey;"><?=$row['article_designation']?></span>
<?					} else { ?>
						<?=$row['article_designation']?>
<?					} ?>
			</td>
			<td nowrap><?=$row['image']?></td>


			<td nowrap><!-- GESTION DU STYLE ARTICLE -->
				<select name="style" onchange="affiche_style_article(this,<?=$row['id']?>);">
					<option value="NULL/#FFFFFF/#FFFFFF">Pas de style</option>
<?					
					$styleDark='';
					$styleLight='';
					while($row_style = mysql_fetch_array($res_style)) {
						$valeur = explode(',',$row_style['valeur']);
?>						<option value="<?=join('/',array($row_style['id'],$valeur[0],$valeur[1]))?>"<?= $row['id_style']==$row_style['id'] ? ' selected':''?>><?=$row_style['nom']?></option>
<?					
						if ($row['id_style']==$row_style['id']) {
							$styleDark=$valeur[0];
							$styleLight=$valeur[1];
						}
				
					}
					mysql_data_seek($res_style,0); ?>
				</select>
			</td>
			<td style="width:20px;">
				<div id="colorDark-article-<?=$row['id']?>"  style="height:10px;background:<?=$styleDark?>;">&nbsp;</div>
				<div id="colorLight-article-<?=$row['id']?>" style="height:10px;background:<?=$styleLight?>;">&nbsp;</div>
			</td>
			<td style="text-align:center;">
				<input type="checkbox" name="desassocie_<?=$row['id']?>">
			</td>
		</tr>
<?	}  ?>
		</table>
		<!-- FIN TABLEAU DES ARTICLES -->

<?	}	// fin affichage des article?>
	</td>
	</tr>
	<tr>
		


		<!-- SELECTION Des articles qui ne sont associé à aucune catégorie -->
		<td colspan="10">

<style>
table#liste_article_sans_categ caption {
	font-size:0.8em;
	font-weight:bold;
	padding-bottom:5px;
	padding-top:15px;
}
table#liste_article_sans_categ th {
	text-align:center;
	border:solid 1px grey;
	background-color:#DDD;
}
table#liste_article_sans_categ td {
	text-align:center;
	border:solid 1px grey;
}

</style>

<script language="javascript">

var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non supporté par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");


function edit_article_designation(id) {
	var monTd = document.getElementById('td_article_designation_'+id);
	if (!monTd.innerHTML.match(/^<input /)) // si pas deja transformé
		monTd.innerHTML = '<input type="text" size="75" value="'+monTd.innerHTML.replace(/<br><span [\S\s]+<\/span>/g,'')+'" onkeyup="envoi_article_designation(\''+id+'\',this.value,event);">';

}

function envoi_article_designation(id,designation,e) {
	if (e.keyCode == 13 && id) { // entrée
		http.open("GET", "ajax.php?what=edit_designation&id="+id+"&val="+escape(designation), true);
		http.onreadystatechange = function () { 
			if (http.readyState == 4) { // envoi fini
				if (http.responseText) { // ca c'est bien passé
					json = eval('(' + decode64(http.responseText) + ')'); // [id,designation]

					if (json[1]) // une designation spécial
						document.getElementById('td_article_designation_'+json[0]).innerHTML = json[1] + '<br><span style="font-size:0.7em;color:grey;">'+json[2]+'</span>';
					else // on reprend la designation de RUBIS
						document.getElementById('td_article_designation_'+json[0]).innerHTML = json[2];

				} else { // l'update n'a pas été fait
					alert("Erreur lors de la mise à jour de la designation");
				}
			}
		};
		http.send(null);
	}
}


function edit_designation(id) {
	var monTd = document.getElementById('td_designation_'+id);
	if (!monTd.innerHTML.match(/^<input /)) // si pas deja transformé
		monTd.innerHTML = '<input type="text" size="75" value="'+monTd.innerHTML.replace(/<br><span [\S\s]+<\/span>/g,'')+'" onkeyup="envoi_designation(\''+id+'\',this.value,event);">';

}

function envoi_designation(id,designation,e) {
	if (e.keyCode == 13 && id) { // entrée
		http.open("GET", "ajax.php?what=edit_designation&id="+id+"&val="+escape(designation), true);
		http.onreadystatechange = function () { 
			if (http.readyState == 4) { // envoi fini
				if (http.responseText) { // ca c'est bien passé
					json = eval('(' + decode64(http.responseText) + ')'); // [id,designation]

					if (json[1]) // une designation spécial
						document.getElementById('td_designation_'+json[0]).innerHTML = json[1] + '<br><span style="font-size:0.7em;color:grey;">'+json[2]+'</span>';
					else // on reprend la designation de RUBIS
						document.getElementById('td_designation_'+json[0]).innerHTML = json[2];

				} else { // l'update n'a pas été fait
					alert("Erreur lors de la mise à jour de la designation");
				}
			}
		};
		http.send(null);
	}
}



</script>


			<table id="liste_article_sans_categ" cellspacing="0" cellpadding="2" style="width:100%;">
				<caption>Article sans affectation</caption>
				<tr>
					<th><input type="button" value="Filtrer" onclick="envoi_formulaire('filtrer');"></th>
					<th><input type="text" name="filtre_fournisseur"		value="<?=isset($_POST['filtre_fournisseur'])?strtoupper($_POST['filtre_fournisseur']):''?>"	onkeyup="filtre(event);"></th>
					<th>Contenant <input type="text" name="filtre_designation" value="<?=isset($_POST['filtre_designation'])?$_POST['filtre_designation']:''?>"
					size="40" onkeyup="filtre(event);"></th>
					<th><input type="text" name="filtre_activite"		value="<?=isset($_POST['filtre_activite'])?strtoupper($_POST['filtre_activite']):''?>"			size="3" maxlength="3" onkeyup="filtre(event);"></th>
					<th><input type="text" name="filtre_famille"		value="<?=isset($_POST['filtre_famille'])?strtoupper($_POST['filtre_famille']):''?>"			size="3" maxlength="3" onkeyup="filtre(event);"></th>
					<th><input type="text" name="filtre_sousfamille"	value="<?=isset($_POST['filtre_sousfamille'])?strtoupper($_POST['filtre_sousfamille']):''?>"	size="3" maxlength="3" onkeyup="filtre(event);"></th>
					<th><input type="text" name="filtre_chapitre"		value="<?=isset($_POST['filtre_chapitre'])?strtoupper($_POST['filtre_chapitre']):''?>"			size="3" maxlength="3" onkeyup="filtre(event);"></th>
					<th><input type="text" name="filtre_souschapitre"	value="<?=isset($_POST['filtre_souschapitre'])?strtoupper($_POST['filtre_souschapitre']):''?>" size="3" maxlength="3" onkeyup="filtre(event);"></th>
					<th><input type="button" value="Associer" onclick="envoi_formulaire('associe');"></th>
				</tr>
				<tr>
					<th>Code</th>
					<th>Fournisseur</th>
					<th>Designation</th>
					<th>Act</th>
					<th>Fam</th>
					<th>Ss fam</th>
					<th>Chap</th>
					<th>Ss chap</th>
					<th><input type="button" value="Inverser<?="\n"?>selection" onclick="inverse_selection('associe');"></th>
				</tr>
<?		
		// s'il aucun filtre n'est appliqué, on affiche aucun article
		if (	(isset($_POST['filtre_fournisseur'])	&& $_POST['filtre_fournisseur']) ||
				(isset($_POST['filtre_designation'])	&& $_POST['filtre_designation']) ||
				(isset($_POST['filtre_activite'])		&& $_POST['filtre_activite']) ||
				(isset($_POST['filtre_famille'])		&& $_POST['filtre_famille']) ||
				(isset($_POST['filtre_sousfamille'])	&& $_POST['filtre_sousfamille']) ||
				(isset($_POST['filtre_chapitre'])		&& $_POST['filtre_chapitre'])  ||
				(isset($_POST['filtre_souschapitre']) && $_POST['filtre_souschapitre'])
			) {

			$where = array() ;
			if (isset($_POST['filtre_fournisseur']) && $_POST['filtre_fournisseur']) 
				$where[] = "a.fournisseur='".strtoupper($_POST['filtre_fournisseur'])."'";
			if (isset($_POST['filtre_designation']) && $_POST['filtre_designation']) 
				$where[] = "(a.designation LIKE '%".mysql_escape_string($_POST['filtre_designation'])."%' OR ta.designation LIKE '%".mysql_escape_string($_POST['filtre_designation'])."%')";
			if (isset($_POST['filtre_activite']) && $_POST['filtre_activite']) 
				$where[] = "a.activite='".strtoupper($_POST['filtre_activite'])."'";
			if (isset($_POST['filtre_famille']) && $_POST['filtre_famille']) 
				$where[] = "a.famille='".strtoupper($_POST['filtre_famille'])."'";
			if (isset($_POST['filtre_sousfamille']) && $_POST['filtre_sousfamille']) 
				$where[] = "a.sousfamille='".strtoupper($_POST['filtre_sousfamille'])."'";
			if (isset($_POST['filtre_chapitre']) && $_POST['filtre_chapitre']) 
				$where[] = "a.chapitre='".strtoupper($_POST['filtre_chapitre'])."'";
			if (isset($_POST['filtre_souschapitre']) && $_POST['filtre_souschapitre']) 
				$where[] = "a.souschapitre='".strtoupper($_POST['filtre_souschapitre'])."'";
			
			$where = join(' AND ',$where);
			if ($where) $where = " AND $where";

			$sql = <<<EOT
SELECT	ta.code_article,
		ta.id,
		a.designation AS article_designation,
		ta.designation AS tarif_designation,
		a.activite, a.famille, a.sousfamille, a.chapitre, a.souschapitre , a.fournisseur
FROM	tarif_article ta, article a
WHERE	ta.code_article=a.code_article AND
		(ta.id_categ=0 or ta.id_categ IS NULL)
		$where
ORDER BY ta.designation
EOT;
			$res = mysql_query($sql) or die("Ne peux pas selectionner les articles sans categorie ".mysql_error());
			while($row = mysql_fetch_array($res)) { ?>
					<tr>
						<td style="text-align:left;"><?=$row['code_article']?></td>
						<td style="text-align:left;"><?=$row['fournisseur']?></td>
						<td style="text-align:left;" id="td_designation_<?=$row['id']?>" onclick="edit_designation('<?=$row['id']?>');">
							<?	if ($row['tarif_designation']) { ?>
									<?=$row['tarif_designation']?><br><span style="font-size:0.7em;color:grey;"><?=$row['article_designation']?></span>
<?								} else { ?>
									<?=$row['article_designation']?>
<?								} ?>
						</td>
						<td><?=$row['activite']?>&nbsp;</td>
						<td><?=$row['famille']?>&nbsp;</td>
						<td><?=$row['sousfamille']?>&nbsp;</td>
						<td><?=$row['chapitre']?>&nbsp;</td>
						<td><?=$row['souschapitre']?>&nbsp;</td>
						<td><input type="checkbox" name="associe_<?=$row['id']?>" value=""></td>
					</tr>
<?			} // fin while article
		} // fin si aucun filtre n'est applique
?>
			</table>
		</td>
	</tr>




	<!-- LEGENDE -->
	<tr>
		<td colspan="10">
			Légende :<br>
			<img src="gfx/gfx.png"> La catégorie a une image associée<br>
			<img src="gfx/list.png"> La catégorie a des articles associés<br>
			<img src="gfx/caution.png"> La catégorie n'a pas de style associé<br>
		</td>
	</tr>
</table>

<input type="hidden" name="id_categ" value="<?=$id_categ?>">
<input type="hidden" name="last_chemin" value="<?=$chemin?>">
</form>

<br><br>
<A HREF="index.html">Revenir à l'accueil</A>
</body>
</html>