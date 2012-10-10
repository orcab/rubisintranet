<?
include('../inc/config.php');

$message = '';
$resultats = array();

// on vient de saisir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && isset($_POST['reference']) && $_POST['reference']) {
	
	$message = "Article enregistré dans la liste<br/>$nb_article_a_traiter article".($nb_article_a_traiter > 1 ? 's':'');
}
?>
<html>
<head>
<title>Association de photo</title>
<style>
a img { border:none; }
</style>

<!-- STYLE POUR UN TERMINAL DEPORTE -->
<style>
body {
	margin:0px;
	border:solid 1px green;
	font-family:verdana;
	width:240px;
	height:210px;
	font-size:0.7em;
}

body, input {
	color:yellow;
	background:black;
}

table {
	 width:100%;
	 border-collapse:collapse;
}

td {
	font-size:0.6em;
	padding:0px;
	vertical-align:bottom;
}

th {
	font-size:0.7em;
	color:white;
	text-align:left;
}

div,input {
	border:solid 1px green;
	font-size:0.9em;
	font-family:verdana;
}


h1,h2,h3 {
	margin:0px;
	text-align:center;
	font-size:1em;
}

h2,h3 { color:red; }

table#articles td.ligne3 {
	border:none;
	border-bottom:dotted 1px lightgreen;
	font-family:smalls font;
}

td.code {
	vertical-align:top;
	color:lightgreen;
	font-size:10px;
	padding-right:2px;
}

td.designation {
	font-size:10px;
	padding-right:2px;
}

td.fournisseur { color:lightgreen; }
td.reference { color:lightgreen; }
.desi1 { border:none; }
.desi2 {
	color: #990;/*#6E6F00;*/
	border:none;
}

.prix {
	font-weight:bold;
	font-size:0.9em;
	color:red;
	text-align:right;
}

table#articles td.mini_maxi { text-align:center; }
table#articles td.localisation { text-align:right; font-size:10px ;}

select { 
	background-color:black;
	color:yellow;
	font-family:"Courier New";
}

option { color:yellow; }

</style>

<script language="javascript">
<!--

// au chargement de la page
function init() {
	if (document.getElementById('reference'))
		document.getElementById('reference').focus();
}

function check_enter_on_gencode(e) {
	if (e.keyCode == 13) { // on a valider la saisie
		document.mon_form.reference.value=document.getElementById('reference').innerHTML.replace(/<br *>/gi,'');
		//document.mon_form.submit();

		/*console.log(document.getElementById('reference').innerHTML);
		console.log(document.getElementById('reference').innerHTML.replace(/<br *>/ig,''));
		console.log(document.mon_form.reference.value);*/
	}
}

//-->
</script>


</head>
<body onload="init();">

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="mon_form" action="<?=basename($_SERVER['PHP_SELF'])?>" method="POST">
<center>

<h3 id="titre3"><?=$message?></h3>
	
<input type="hidden" name="what" value="saisie_gencode" />
<h1>Scanner le code barre</h1>
<div style="text-align:center;border:none;">
	<div id="reference" style="width:80%;height:20px;display:block;margin:auto;" contenteditable="true" onkeyup="check_enter_on_gencode(event)" autocomplete="off"></div>
	<input type="hidden" id="reference_hidden" name="reference" value="" />
</div>
<br/>

<h1>VL de base</h1>
Qte : 1
<select name="vl_base">
	<option value="BTE">BTE - Boite</option>
	<option value="CAD">CAD - Cadre</option>
	<option value="CAR">CAR - Carton</option>
	<option value="COU">COU - Couronne</option>
	<option value="ML" >ML &nbsp;- Mètre L.</option>
	<option value="PAL">PAL - Palette</option>
	<option value="PCE">PCE - Pièce</option>
	<option value="PQT">PQT - Paquet</option>
	<option value="RLX">RLX - Rouleur</option>
	<option value="SAC">SAC - Sac</option>
	<option value="TOR">TOR - Touret</option>
	<option value="UN" selected="selected">UN &nbsp;- Unité</option>
	<option value="TUB">TUB - Tube</option>
</select>
<br/><br/>

<h1>VL 20</h1>
Qte : <input type="text" value="" name="qte_vl_20" size="2"/>
<select name="vl_base">
	<option value="BTE">BTE - Boite</option>
	<option value="CAD">CAD - Cadre</option>
	<option value="CAR">CAR - Carton</option>
	<option value="COU">COU - Couronne</option>
	<option value="ML" >ML &nbsp;- Mètre L.</option>
	<option value="PAL">PAL - Palette</option>
	<option value="PCE">PCE - Pièce</option>
	<option value="PQT">PQT - Paquet</option>
	<option value="RLX">RLX - Rouleur</option>
	<option value="SAC">SAC - Sac</option>
	<option value="TOR">TOR - Touret</option>
	<option value="UN" selected="selected">UN &nbsp;- Unité</option>
	<option value="TUB">TUB - Tube</option>
</select>
<br/><br/>

<h1>Code taille emplacement <input type="text" value="" name="code_taille_emp" size="2"/></h1>
<h1>Qte max en (VL 10)<input type="text" value="" name="qte_max_vl_10" size="2"/></h1>

<input type="submit" value="Valider"/>

</center>
</form>
</body>
</html>