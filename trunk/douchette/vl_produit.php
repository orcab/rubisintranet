<?
include('../inc/config.php');
define('DATABASE','vl_produit.csv');
$message = '';
$resultats = array();

// on vient de saisir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && isset($_POST['reference']) && $_POST['reference']) {
	//echo var_dump($_POST);

	$loginor    = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$reference	= strtoupper(mysql_escape_string(trim($_POST['reference'])));
	$gencode	= strtoupper(mysql_escape_string(trim($_POST['gencode'])));
	$sql = <<<EOT
select	ARTICLE.NOART
from				${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
						on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
where	   (ARTICLE.NOART='$reference')					-- saisie d'un code article MCS
		or (ARTICLE_FOURNISSEUR.REFFO='$reference')		-- saisie d'une référence fournisseur
		or (ARTICLE.GENCO='$reference')					-- saisie d'un code barre MCS
		or (ARTICLE_FOURNISSEUR.AFOGE='$reference')		-- saisie d'un code barre fournisseur
		or (ARTICLE_FOURNISSEUR.AFOG2='$reference')
		or (ARTICLE_FOURNISSEUR.AFOG3='$reference')	
EOT;

//echo $sql;

	$res = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete : $sql");
	$resultats = array();
	while($row = odbc_fetch_array($res)) {
		array_push($resultats,$row);
	}

	if (sizeof($resultats) == 1) { // si un seul résultat, on enregistre le tout dans le systeme
		if (!file_exists(DATABASE)) // le fichier n'existe pas, on cree les entetes
			$buffer = join(';',array('code_article','unite_vl_10','qte_vl_20','unite_vl_20','code_taille_emplacement','qte_max_vl_10','ip_terminal','ean13_flashe','date_saisie'))."\n";
	
		$buffer .= join(';',array(
								trim($resultats[0]['NOART']),
								$_POST['unite_vl_10'],
								$_POST['qte_vl_20'],
								$_POST['unite_vl_20'],
								$_POST['code_taille_emplacement'],
								$_POST['qte_max_vl_10'],
								$_SERVER['REMOTE_ADDR'],
								$_POST['reference'],
								date('Y/m/d H:i')
							))."\n";

		// enregistre le tout dans un fichier CSV
		$TEMP = fopen(DATABASE,'a') ;
		fwrite($TEMP,$buffer);
		fclose($TEMP);

		$message = "Article ".$resultats[0]['NOART']." enregistré";


	} elseif (sizeof($resultats) <= 0) {
		$message = "Aucun article ne correspond au code barre";


	} else {
		$message = "Il existe plusieurs codes articles pour ce code barre";
	}	
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
	font-size:0.9em;
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


function verif_form() {
	if(document.getElementById('reference').innerHTML == '') {
		alert("Veuillez scanner un code ou entrer une référence");
	} else if (document.mon_form.qte_vl_20.value == '')	{
		alert("Veuillez saisir une quantité en VL 20");
	} else if (document.mon_form.code_taille_emplacement.value == '')	{
		alert("Veuillez saisir un code taille emplacement");
	} else if (document.mon_form.qte_max_vl_10.value == '')	{
		alert("Veuillez une quantité maximum en VL 10");

	// tout est bon, on envoi
	} else {
		document.mon_form.reference.value=document.getElementById('reference').innerHTML.replace(/<br *>/gi,'');
		document.mon_form.submit();
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
	<div id="reference" style="width:80%;height:20px;display:block;margin:auto;" contenteditable="true" autocomplete="off"></div>
	<input type="hidden" id="reference_hidden" name="reference" value="" />
</div>
<br/>

<h1>VL de base</h1>
Qte : 1
<select name="unite_vl_10">
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
Qte : <input type="text" value="" name="qte_vl_20" size="2"/> dans 
<select name="unite_vl_20">
	<option value="BTE">BTE - Boite</option>
	<option value="CAD">CAD - Cadre</option>
	<option value="CAR" selected="selected">CAR - Carton</option>
	<option value="COU">COU - Couronne</option>
	<option value="ML" >ML &nbsp;- Mètre L.</option>
	<option value="PAL">PAL - Palette</option>
	<option value="PCE">PCE - Pièce</option>
	<option value="PQT">PQT - Paquet</option>
	<option value="RLX">RLX - Rouleur</option>
	<option value="SAC">SAC - Sac</option>
	<option value="TOR">TOR - Touret</option>
	<option value="UN">UN &nbsp;- Unité</option>
	<option value="TUB">TUB - Tube</option>
</select>
<br/><br/>

<h1>Code taille emplacement <input type="text" value="" name="code_taille_emplacement" size="2"/></h1>
<h1>Qte max en (VL 10)<input type="text" value="" name="qte_max_vl_10" size="2"/></h1>

<input type="button" value="Valider" onclick="verif_form();"/>

</center>
</form>
</body>
</html>