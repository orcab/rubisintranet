<?

include('../inc/config.php');
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$droit = recuperer_droit();

if (!($droit & PEUT_CREER_DEVIS)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}

if (!isset($_GET['app'])) { // n'a pas le droit de faire des devis
	die("Aucune application de spécifier. Veuillez passer le paramètre 'app' en URL");
}

?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=UTF-8"/> 
<title>Modification des phrases <?=$_GET['app']?></title>
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

input.valider {	visibility:hidden; }

div.row {
	margin-bottom: 1em;
	margin:auto;
	vertical-align:top;
}

input[type=text] { vertical-align: top; }

textarea {
    width: 30em;
    height: 5em;
}

form {
    margin: auto;
    width: 50%;
}

input#add_button {
	margin-top: 1em;
}

input.annuler,input.valider {
	vertical-align: top;
    margin-top: 1.5em;
}

</style>

<style type="text/css">@import url(../js/boutton.css);</style>
<script language="javascript" src="../js/jquery.js"></script>
<script language="javascript" src="../js/utf8.js"></script>
<script language="JavaScript">
<!--

// affiche les différentes famille produit et les marges une fois le fournisseur choisit
function show_phrase() {
	// on charge les données
	$('#phrase').html('&nbsp;')
				.css({	'background-image':'url(gfx/loading4.gif)',
						'background-repeat':'no-repeat',
						'background-position':'center middle'
					});

	$.ajax({
		type: 'GET',
		url:  'ajax.php',
		data: 'what=get_phrase&app=<?=$_GET['app']?>',
		dataType: 'json',
		success: function(json){
			$('#phrase').css('background','none');
			if (json.length > 0) {
				for(tmp in json)
					draw_phrase(json[tmp]['id'], json[tmp]['mot_cle'], json[tmp]['phrase']);
			}
		}
	});
}


function draw_phrase(id,mot_cle,phrase) {
	var html = '<div class="row">'

	if (mot_cle) { // si un mot clé deja renseigné
		html +=	'<input type="hidden" name="id" value="' + id + '"/>'+
				'<input type="text" name="mot_cle" value="' + mot_cle.utf8_decode() + '" class="mot_cle"/> '+
				'<textarea name="phrase" class="phrase">' + phrase.utf8_decode() + '</textarea>';
	} else { // si une nouvelle ligne
		html +=	'<input type="hidden" name="id" value=""/>'+
				'<input type="text" name="mot_cle" value="" class="mot_cle"/> '+
				'<textarea name="phrase" class="phrase"></textarea>';
	}

	// on ajoute les bouton de controle (enresgitrer et supprimer)
	html += '&nbsp;&nbsp;<input type="button" class="annuler button" value="" title="Supprimer"/>&nbsp;<input type="button" class="valider button" value=""/></div>';

	$('#phrase').append(html);
}



$(document).ready(function(){

	show_phrase(); // on charge les données

	// binding
	// bouton supprimer
	$('body').delegate('input.annuler','click',function(){
		if (confirm("Voulez vous vraiment supprimer cette phrase ?")) {
			$this = $(this);
			$this	.attr('disabled','disabled')
					.css('background-image','url(gfx/loading4.gif)');

			$.ajax({
				type: 'GET',
				url:  'ajax.php',
				data: 'what=delete_phrase'+	'&app=<?=$_GET['app']?>&mot_cle='+$this.parent('div').children('.mot_cle').val(),
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
		var mot_cle = $this.parent('div').children('.mot_cle').val();
		var phrase	= $this.parent('div').children('.phrase').val();

		// vérifie les saisies pour ne pas valider n'importe quoi
		if ($.trim(mot_cle) == '') {
			alert("Le mot clé n'est pas renseigné"); return;
		}
		if ($.trim(phrase) == '') {
			alert("La phrase est vide"); return;
		}

		$this	.attr('disabled','disabled')
				.css('background-image','url(gfx/loading4.gif)');

		$.ajax({
			type: 'GET',
			url:  'ajax.php',
			data: 'what=save_phrase'+	'&mot_cle='+mot_cle+
										'&app=<?=$_GET['app']?>'+
										'&phrase='+phrase,
			success: function(result){
				// ok ca c'est bien passé
				$this	.attr('disabled','')
						.css({	'background-image':'url(../js/boutton_images/accept.png)',
								'visibility':'hidden'});
			}
		});
	});


	//en cas de changement de la valeur des libelle ou de la marge --> on affiche le bnouton valider
	$('body').delegate('input[type=text], textarea','keyup',function(){
		$(this).parent('div').children('input.valider').css('visibility','visible');
		//console.log($(this).attr('class'));
	});

	
	// click sur le bouton d'ajout
	$('body').delegate('input#add_button','click',function(){
		draw_phrase('','','');
	});
	
});

//-->
</script>

</head>
<body style="margin-left:0px;">

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<h1>Modification des phrases <?=$_GET['app']?></h1> 

<form method="post" name="creation_article">
<div id="phrase"></div>
<input type="button" id="add_button" class="add button" style="background-image:url(../js/boutton_images/add.png)" value="Ajouter une phrase"/>
</form>
</body>
</html>
<? mysql_close($mysql); ?>