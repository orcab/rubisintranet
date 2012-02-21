<html>
<head>
<title>Feuille de temps</title>
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
	-moz-border-radius:5px;
/*	margin-bottom:em;*/
	
}

legend {
	border:solid 1px #6290B3;
	background:#e7eef3;
	color:#325066;
	font-weight:bold;
	padding:3px;
	text-align:center;
	-moz-border-radius:5px;
	-moz-box-shadow: 0 0 5px #6290B3;
}

fieldset div {
	margin-top:0.5em;
	margin-bottom:1em;
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

</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar-setup.js"></script>
<script language="javascript" src="../../js/jquery.js"></script>
<script language="JavaScript">
<!--

function remove_date(elm) {
	$(elm).parent('div').hide();
}

function draw_date(elm,t) {
	$(elm).parent('fieldset').children('div').append(
		'<div>'+
			'Du <input type="text" value="" size="2" maxlength="2" name="jour_start_'+t+'[]"/> '+
			'<select name="mois_start_'+t+'[]">'+
				'<option value="01">janvier</option>'+
				'<option value="02">février</option>'+
				'<option value="03">mars</option>'+
				'<option value="04">avril</option>'+
				'<option value="05">mai</option>'+
				'<option value="06">juin</option>'+
				'<option value="07">juillet</option>'+
				'<option value="08">août</option>'+
				'<option value="09">septembre</option>'+
				'<option value="10">octobre</option>'+
				'<option value="11">novembre</option>'+
				'<option value="12">décembre</option>'+
			'</select>'+
			'&nbsp;au&nbsp;&nbsp;'+
			'<input type="text" value="" size="2" maxlength="2" name="jour_end_'+t+'[]"/> '+
			'<select name="mois_end_'+t+'[]">'+
				'<option value="01">janvier</option>'+
				'<option value="02">février</option>'+
				'<option value="03">mars</option>'+
				'<option value="04">avril</option>'+
				'<option value="05">mai</option>'+
				'<option value="06">juin</option>'+
				'<option value="07">juillet</option>'+
				'<option value="08">août</option>'+
				'<option value="09">septembre</option>'+
				'<option value="10">octobre</option>'+
				'<option value="11">novembre</option>'+
				'<option value="12">décembre</option>'+
			'</select>'+
			'&nbsp;&nbsp;<input type="button" class="annuler button" value="" title="Supprimer" onclick="remove_date(this);"/>'+
		'</div>'
	);
}


// vérifie le formulaire et envoi la demande
function valid_form() {
	var erreur = false;
	var form = document.feuille_temps ;

	// choix de l'année
	if (form.annee.options[form.annee.selectedIndex].value == '') {
		alert("Veuillez choisir une année");
		erreur = true;
	}

	// choix de la personne
	if (form.who.options[form.who.selectedIndex].value == '') {
		alert("Veuillez choisir une personne");
		erreur = true;
	}

	
	$('input[name^=jour]').each(function(){
		// on test si le jour est érroné
		var jour = $(this).val();
		if (jour < 0 || jour > 31) {
			$(this).css('background','yellow');
			alert("Le numéro du jour '"+jour+"' est bizarre");
			erreur = true;
		}
	})

	if (!erreur)
		form.submit();
	//alert("toutou");

}



// au démarrage
$(document).ready(function(){
	
});

//-->
</script>

</head>
<body style="margin-left:0px;">

<!-- menu de naviguation -->
<? include('../../inc/naviguation.php'); ?>

<h1>Feuille de temps</h1> 

<form method="post" name="feuille_temps" action="img2pdf.php">

Année <select name="annee">
	<option value=""></option>
	<option value="2009">2009</option>
	<option value="2010">2010</option>
	<option value="2011">2011</option>
	<option value="2012">2012</option>
	<option value="2013">2013</option>
	<option value="2014">2014</option>
</select>
&nbsp;pour&nbsp;&nbsp;
<select name="who">
	<option value=""></option>
<?	$dir = opendir('background') or die("ne peux pas ouvrir le répertoire de background");
	while($f = readdir($dir)) {
		if (substr($f,strlen($f)-4,4) == '.jpg') { ?>
			<option value="<?=substr($f,0,strlen($f)-4)?>"><?=substr($f,0,strlen($f)-4)?></option>
<?		}
	}
	closedir($dir);
?>
</select> <span style="font-size:0.8em;">&lt;-- Pour faire apparaitre votre nom, contactez Benjamin</span>
<br/><br/>
<fieldset><legend>Congés payés</legend>
	<div></div>

	<input type="button" id="add_button" class="add button divers" style="background-image:url(../../js/boutton_images/add.png)" value="Ajouter une date" onclick="draw_date(this,'paye');"/>
</fieldset>
<br/>
<fieldset><legend>Congés sans solde</legend>
	<div></div>

	<input type="button" id="add_button" class="add button divers" style="background-image:url(../../js/boutton_images/add.png)" value="Ajouter une date" onclick="draw_date(this,'sans');"/>
</fieldset>
<br/>
<fieldset><legend>Maladie</legend>
	<div></div>

	<input type="button" id="add_button" class="add button divers" style="background-image:url(../../js/boutton_images/add.png)" value="Ajouter une date" onclick="draw_date(this,'malade');"/>
</fieldset>

<br/>
<input type="button" id="send_form" class="valider button" value="Générer les feuilles de temps" onclick="valid_form();"/>
</form>


</body>
</html>