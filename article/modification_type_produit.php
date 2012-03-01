<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (!($droit & PEUT_MODIFIER_TYPE_PRODUIT)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}

?>
<html>
<head>
<title>Modification des types de produits</title>
<style>
a		{ text-decoration:none; }
a:hover { text-decoration:underline; }

h1 {
	width:95%;
	background-color:#DDD;
	margin-bottom:10px;
	height:30px;
	padding-left:50px;
	font-weight:bold;
	padding-top:10px;
	font-size:1em;
}

select#completion_fourn {
	border:solid 1px #000080;
	display:none;
}

.label {
	vertical-align:top;
	width:15%;
}

input.marge {
	width:3em;
}

input[readonly=readonly] {
	color:grey;
}

input.valider {
	visibility:hidden;
}

</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="JavaScript">
<!--

// transforme les champs de saisie en majuscule
function majusculize(champ) {
	document.creation_article.elements[champ].value = document.creation_article.elements[champ].value.toUpperCase();
}

// permet le passage de champs en champs dans la saisie des familles/sous familles
function compte_car(mon_objet) {
	if (mon_objet.value.length >= 3) {
		if			(mon_objet.name=='activite') // on passe au champs famille
			document.creation_article.famille.focus();
		else if	(mon_objet.name=='famille') // on passe au champs sousfamille
			document.creation_article.sousfamille.focus();
		else if	(mon_objet.name=='sousfamille') // on passe au champs chapitre
			document.creation_article.chapitre.focus();
		else if	(mon_objet.name=='chapitre') // on passe au champs sous chapitre
			document.creation_article.souschapitre.focus();
	}
}

///// AJAX ///////////////////////////////////
var http = null;
if		(window.XMLHttpRequest) // Firefox 
	   http = new XMLHttpRequest(); 
else if	(window.ActiveXObject) // Internet Explorer 
	   http = new ActiveXObject("Microsoft.XMLHTTP");
else	// XMLHttpRequest non supporté par le navigateur 
   alert("Votre navigateur ne supporte pas les objets XMLHTTPRequest...");

<?
// récupere la liste des fournisseurs de MySQL
$res = mysql_query("SELECT nom AS nom_fournisseur,code_rubis as code_fournisseur FROM fournisseur ORDER BY nom ASC") or mysql_error("Impossible de récupérer la liste des fournisseurs");
$fournisseurs = array();
while($row = mysql_fetch_array($res)) {
	array_push($fournisseurs,array($row['code_fournisseur'],strtoupper($row['nom_fournisseur'])));
}
echo "var fournisseurs = ".json_encode($fournisseurs).";";
?>


// completion pour les fournisseurs
function complette_fourn(e) {
	var sel = document.creation_article.completion_fourn ;
	var nb_el = sel.options.length ;
	var selIndex = sel.selectedIndex ;

	if (!document.creation_article.fournisseur.value) {
		sel.style.display = 'none';
	}
	else if (e.keyCode == 40 && nb_el) { // fleche bas
		if (selIndex < sel.options.length - 1)
			sel.selectedIndex = selIndex + 1 ;
	}
	else if (e.keyCode == 38 && nb_el) { // fleche haut
		if (selIndex > 0)
			sel.selectedIndex = selIndex - 1 ;
	}
	else if (e.keyCode == 13 && nb_el) { // entrée
		document.creation_article.fournisseur.value = sel.options[selIndex].text ;
		document.creation_article.code_fournisseur.value = sel.options[selIndex].value ;
		$('#code_fournisseur').text(sel.options[selIndex].value);
		sel.style.display = 'none';

		show_type_produit();
	}
	else { // autre touche --> on recherche et affiche les fournisseurs valident
		val = document.creation_article.fournisseur.value.toUpperCase() ;
		if (val.length > 0) {
						
			var valid_fournisseurs = new Array();
			for(var i=0 ; i<fournisseurs.length ; i++) {
				if (fournisseurs[i][1] != null && fournisseurs[i][1].substr(0,val.length) == val) // ca match --> on garde le fournisseur (on a deja converti en majuscule avant)
					valid_fournisseurs.push(i); // on garde l'indice du fournisseur valide
			}

			document.getElementById('completion_fourn').attributes['size'].value = valid_fournisseurs.length;

			// on vide le select
			while(sel.options.length > 0)
				sel.options[0] = null

			// on rempli avec les fournisseurs valident
			for(var i=0 ; i<valid_fournisseurs.length ; i++) {
				var indice = valid_fournisseurs[i];
				sel.options[sel.options.length] = new Option(fournisseurs[indice][1],fournisseurs[indice][0]);
			}

			if (sel.options.length) {
				sel.selectedIndex = 0 ; // on selection le premier element de la liste
				$('#completion_fourn').show();
			}
			else {
				$('#completion_fourn').hide();		
			}
		}
	}
}


// action lorsque l'on choisit un fournisseur dans la liste
function complette_fourn_click() {
	var sel = document.creation_article.completion_fourn ;

	document.creation_article.fournisseur.value = sel.options[sel.selectedIndex].text ;
	document.creation_article.code_fournisseur.value = sel.options[sel.selectedIndex].value ;
	$('#code_fournisseur').text(sel.options[sel.selectedIndex].value) ;
	sel.style.display = 'none';

	show_type_produit();
}

// affiche les différentes famille produit et les marges une fois le fournisseur choisit
function show_type_produit() {
	// nettoi l'ancien case type produit
	$('#type_produit').html('');

	// récupere le code fournisseur
	var code_fournisseur = $('#code_fournisseur').text();
	$('#type_produit').css({'background-image':'url(gfx/loading4.gif)',
							'background-repeat':'no-repeat',
							'background-position':'center middle'
							});

	$.ajax({
		type: 'GET',
		url:  'ajax.php',
		data: 'what=get_type_produit_fournisseur&code_fournisseur='+code_fournisseur,
		dataType: 'json',
		success: function(json){
			$('#type_produit').css('background','none');
			var found_global = false;
			if (json.length > 0) {
				// on a trouvé des familles, on ne laisse pas le choix à l'utilisateur

				for(tmp in json) {
					draw_type(json[tmp]['id'], json[tmp]['famille_produit'], json[tmp]['marge']);
					if (json[tmp]['famille_produit'] == 'Global') found_global = true;
				}
			}

			if (!found_global) // pas de marge "global" --> on l'affiche
				draw_type('', 'Global', '');

			$('#type_produit').append(
				'<input type="button" id="add_button" class="add button" style="background-image:url(../js/boutton_images/add.png)" value="Ajouter un type" onclick="draw_type(\'\',\'\',\'\');"/>'
			);
		}
	});
}


function draw_type(id,type,marge) {
	if (type) { // si un type deja renseigné
		$('#type_produit').append(
			'<div><input type="hidden" name="id" value="'+id+'"/>'+
			'<div><input type="text" name="libelle" value="'+type+'" class="libelle" '+
			(type == 'Global' ? 'readonly="readonly"':'')+'/> '+
			'<input type="text" name="marge" value="'+marge+'" class="marge only_float" title="Valider"/>%&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="valider button" value=""/>'+
			(type != 'Global' ? '&nbsp;&nbsp;<input type="button" class="annuler button" value="" title="Supprimer"/>':'')+
			'</div>'
		);
	} else { // si une nouvelle ligne
		$('#add_button').before(
			'<div><input type="hidden" name="id" value=""/>'+
			'<input type="text" name="libelle" value="" class="libelle"/> '+
			'<input type="text" name="marge" value="" class="marge only_float" title="Valider"/>%&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;<input type="button" class="valider button" value=""/>&nbsp;&nbsp;<input type="button" class="annuler button" value="" title="Supprimer"/></div>'
		);
	}
}



$(document).ready(function(){
    var p = $("input[name=fournisseur]");
	var offset = p.offset();
	$('#completion_fourn').css({'top':offset.top + 22,'left':offset.left,'position':'absolute'});


	// binding
	// bouton supprimer
	$('body').delegate('input.annuler','click',function(){
		if (confirm("Voulez vous vraiment supprimer ce type de produit ?")) {
			$this = $(this);
			$this	.attr('disabled','disabled')
					.css('background-image','url(gfx/loading5.gif)');

			$.ajax({
				type: 'GET',
				url:  'ajax.php',
				data: 'what=delete_type_produit&code_fournisseur='+$('#code_fournisseur').text()+
											'&type='+$this.parent('div').children('.libelle').val(),
				success: function(result){
					// ok ca c'est bien passé
					$this.parent('div').remove();
				}
			});
			
		}
	});

	// bouton valider
	$('body').delegate('input.valider','click',function(){
		var $this	= $(this);
		var libelle = $this.parent('div').children('.libelle').val();
		var marge	= $this.parent('div').children('.marge').val();

		// vérifie les saisies pour ne pas valider n'importe quoi
		if ($.trim(libelle) == '') {
			alert("Le libellé n'est pas renseigné"); return;
		}
		if ($.trim(marge) == '') {
			alert("La marge n'est pas renseignée"); return;
		}

		$this	.attr('disabled','disabled')
				.css('background-image','url(gfx/loading5.gif)');

		$.ajax({
			type: 'GET',
			url:  'ajax.php',
			data: 'what=save_type_produit&code_fournisseur='+$('#code_fournisseur').text()+
										'&type='+libelle+
										'&marge='+marge,
			success: function(result){
				// ok ca c'est bien passé
				$this	.attr('disabled','')
						.css({	'background-image':'url(../js/boutton_images/accept.png)',
								'visibility':'hidden'});
			}
		});
	});


	//case pour des nombres
	$('body').delegate('input.only_float','keyup',function(){
		var val = $(this).val();
		if(val.match(/[^0-9\.,]/)) {
			alert("Seul les nombres à virgules sont autorisés");
			$(this).val(val.substr(0,val.length-1));
		}
	});


	//en cas de changement de la valeur des libelle ou de la marge --> on affiche le bnouton valider
	$('body').delegate('input[type=text]','keyup',function(){
		$(this).parent('div').children('input.valider').css('visibility','visible');
	});
	


	// pour un debug plus rapide, supprimer ensuite
	$('#select_fournisseur').val('FINIMETAL'); 	$('#code_fournisseur').text('FINIME'); 	show_type_produit();
});

//-->
</script>

</head>
<body style="margin-left:0px;">

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<h1>Modification des types de produit</h1> 

<form method="post" name="creation_article">
<input type="hidden" name="code_fournisseur" value=""/>

<table style="width:100%;padding:5px;">
<tr>
	<th class="label">Fournisseur :</th>
	<td class="valeur">
		<input type="text" id="select_fournisseur" name="fournisseur" value="" onkeyup="complette_fourn(event);" autocomplete="off" onblur="majusculize(this.name);"> <span id="code_fournisseur"></span>
		<br/>
		<select id="completion_fourn" name="completion_fourn" size="1" onclick="complette_fourn_click();"></select>
	</td>
</tr>
<tr>
	<th class="label">Type de produit</th>
	<td class="valeur" id="type_produit"></td>
</tr>

</table>

</form>
</body>
</html>
<?
mysql_close($mysql);
?>