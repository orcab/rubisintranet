<?
include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");
$droit = recuperer_droit();

if (!($droit & PEUT_EDITER_ARTICLE_EN_MASSE)) { // n'a pas le droit de faire des devis
	die("Vos droits ne vous permettent pas d'accéder à cette partie de l'intranet");
}

?>
<html>
<head>
<title>Modification des articles</title>

<style>

* {
	font-family:verdana;
	font-size:0.95em;
}

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

form {
	width:50%;
	margin:auto;
}

fieldset {
	border:solid 1px #6290B3;
	border-radius:5px;
/*	margin-bottom:em;*/
	
}

legend {
	border:solid 1px #6290B3;
	background:#e7eef3;
	color:#325066;
	font-weight:bold;
	padding:3px;
	text-align:center;
	border-radius:5px;
	box-shadow: 0 0 5px #6290B3;
}

fieldset div {
	margin-top:0.5em;
	margin-bottom:1em;
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

div#gauche {
    width: 50%;
    float: left;
}

input#valider {
    margin-top: 1em;
}

div#droite {
    width: 50%;
    float: right;
    text-align: left;
}

textarea#articles {
    width: 10em;
    height: 30em;
}

div#droite {
    float: right;
    text-align: left;
    width: 50%;
}

div#progress-bar {
    margin-top: 1em;
    margin-bottom: 1em;
    border: solid 1px #BBB;
	visibility:hidden;
	height:1.2em;
	width:100%;
	text-align:center;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="javascript" src="../../js/ProgressBar.js"></script>

<script language="javascript">
<!--

var nb_articles			= 0 ; // nombre d'article total dans le textarea
var nb_article_traite	= 0 ; // nombre d'article traités

// renvoi un tableau des articles a traiter
function get_code_articles() {
	return $('textarea#articles').val().split(/[\n\r]+/);
}

// fonction qui calcul le nombre de code article a traiter
function update_nb_article() {
	var articles = get_code_articles() ;
	nb_article = articles.length;
	$('#info').html(nb_article + " article"+(nb_article > 1 ? 's':'')+" à traiter");
	progressBar.max_value = nb_article ; // met a jour la valeur max de la progressbar
}


// fonction qui lance le traitement : un appel ajax par article pour le mettre a jour selon l'action choisit
function lance_traitement() {
	var action = $('select#what').val();

	if (action) {
		nb_article_traite = 0;
		progressBar.update(0);
		var articles = get_code_articles();
		$('input#valider').css({'background-image':'url(gfx/loading5.gif)','color':'grey'}).val('Traitement en cours').attr('disabled','disabled');
		$('#progress-bar').css('visibility','visible');

		//progressBar.update(3); // dessine la progressbar
		for(var i=0 ; i<articles.length ; i++) {
			$.ajax({
				type:		'GET',
				url:		'ajax.php',
				async:		false,
				dataType:	'json',
				data:		'what='+action+'&code_article='+articles[i],
				//data: 'what=test&code_article='+articles[i],
				success: function(json){
					// le traitement est effectué, on met a jour la progressbar
					progressBar.update(++nb_article_traite);

					if (nb_article_traite >= articles.length) { // le traitement est fini, on remet le bouton comme au debut
						$('input#valider').css({'background-image':'url(../../js/boutton_images/accept.png)','color':'#529214'}).val('Lancer le traitement').removeAttr('disabled');
					}
				} // fin sucess

			});	// fin appel ajax

		} // pour chaque article

	} else {
		alert("Veuillez choisir une action à effectuer");
	}
}


var progressBar ;

$(document).ready(function(){
	// et le focus sur le textarea
	$('textarea#articles').focus();

	// crée la progress bar
	progressBar = new ProgressBar({ id : 'progress-bar', color:'lightgreen'	}) ;

	// BINDINGS
	// en cas de changement des codes articles
	$('body').delegate('textarea#articles','keyup',function(){
		update_nb_article();
	});

	
	// on lance le traitement
	$('body').delegate('input.valider','click',function(){
		lance_traitement();
	});
});

//-->
</script>

</head>
<body>
<form name="modif" method="POST">

<div id="gauche">
	Code articles<br/>
	<textarea id="articles"></textarea>
</div>

<div id="droite">
	<div id="info"></div>
	<div id="progress-bar"></div>
	<select name="what" id="what">
		<option value="">--Choississez une option--</option>
		<optgroup label="Suspension">
			<option value="suspendre">Supendre les codes</option>
			<option value="activer">Activer les codes</option>
		</optgroup>
		<optgroup label="Achats">
			<option value="achat-interdit">Achats interdit</option>
			<option value="achat-autorise">Achats autorisé</option>
		</optgroup>
	</select><br/>
	<input type="button" id="valider" class="valider button" value="Lancer le traitement"/>
</div>

</form>
</body>
</html>