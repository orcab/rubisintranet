<?
include('../inc/config.php');
define('DATABASE','vl_produit.csv');
$message = '';
$resultats = array();
session_start();


// on vient de saisir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && isset($_POST['reference']) && $_POST['reference']) {
	//echo var_dump($_POST);

	$loginor    = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$reference	= strtoupper(mysql_escape_string(trim($_POST['reference'])));
	$gencode	= strtoupper(mysql_escape_string(trim($_POST['gencode'])));

	$sql = <<<EOT
select	ARTICLE.NOART,ARTICLE.DESI1,ARTICLE.DESI2,ARTICLE_FOURNISSEUR.NOFOU,ARTICLE_FOURNISSEUR.REFFO,ARTICLE_FOURNISSEUR.AFPCB
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
	while($row = odbc_fetch_array($res))
		array_push($resultats,array_map('trim',$row));

	if (sizeof($resultats) == 1) { // si un seul résultat, on enregistre le tout dans le systeme
		// on stock le resultat pour un réeaffichage
		$_SESSION['article'] = $resultats[0];
		$_SESSION['reference'] = $_POST['reference'];

	} elseif (sizeof($resultats) <= 0) {
		$_SESSION['article'] = array();
		$_SESSION['reference'] = '';
		$message = "Aucun article ne correspond au code barre";

	} else {
		$_SESSION['article'] = array();
		$_SESSION['reference'] = '';
		$message = "Il existe plusieurs codes articles pour ce code barre";
	}


// tout est bon, on enregistre
} elseif (isset($_POST['what']) && $_POST['what']=='enregistre') {

	if (!file_exists(DATABASE)) // le fichier n'existe pas, on cree les entetes
		$buffer = join(';',array('code_article','fournisseur','reference','designation1','designation2','unite_vl_10','qte_vl_20','unite_vl_20','code_taille_emplacement','qte_max_vl_10','code_famille_prepa','ip_terminal','ean13_flashe','date_saisie'))."\n";

	$buffer .= join(';',array(
							$_SESSION['article']['NOART'],
							$_SESSION['article']['NOFOU'],
							$_SESSION['article']['REFFO'],
							$_SESSION['article']['DESI1'],
							$_SESSION['article']['DESI2'],
							$_POST['unite_vl_10'],
							$_POST['qte_vl_20'],
							$_POST['unite_vl_20'],
							$_POST['code_taille_emplacement'],
							$_POST['qte_max_vl_10'],
							$_POST['code_famille_prepa'],
							$_SERVER['REMOTE_ADDR'],
							$_SESSION['reference'],
							date('Y/m/d H:i')
						))."\n";

	// enregistre le tout dans un fichier CSV
	$TEMP = fopen(DATABASE,'a') ;
	fwrite($TEMP,$buffer);
	fclose($TEMP);

	$message = "Article ".$_SESSION['article']['NOART']." enregistré";
	
	$_SESSION['code_taille_emplacement']	= $_POST['code_taille_emplacement'];
	$_SESSION['code_famille_prepa']			= $_POST['code_famille_prepa'];
	$_SESSION['article'] = array();

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

div,input,select {
	border:solid 1px green;
	font-size:0.9em;
	font-family:verdana;
}

div { 	border:none; }


h1,h2,h3 {
	margin:0px;
	text-align:center;
	font-size:1em;
}

h2,h3 { color:red; }

.code {
	vertical-align:top;
	color:lightgreen;
	font-size:10px;
	padding-right:2px;
	font-weight:bold;
}

.fournisseur { color:lightgreen; }
.reference { color:lightgreen; }
.desi2 { color: #990;/*#6E6F00;*/ }


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
	if (document.mon_form.qte_vl_20.value == '')	{
		alert("Veuillez saisir une quantité en VL 20");

	} else if (document.mon_form.code_taille_emplacement.value == '')	{
		alert("Veuillez saisir un code taille emplacement");

	} else if (document.mon_form.qte_max_vl_10.value == '')	{
		alert("Veuillez une quantité maximum en VL 10");

	} else if (document.mon_form.code_famille_prepa.value == '')	{
		alert("Veuillez saisir un code famille prepa");

	// tout est bon, on envoi
	} else {
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

<? if (is_array($_SESSION['article']) && sizeof($_SESSION['article'])>0) { ///////////////// ON A PAS ENCORE SAISIE DE GENCODE //////////////////////////: ?>

<input type="hidden" name="what" value="enregistre"/>

<div style="text-align:left;">
	<div class="code"><?=$_SESSION['article']['NOART']?></div>
	<div class="desi1"><?=$_SESSION['article']['DESI1']?></div>
	<div class="desi2"><?=$_SESSION['article']['NOFOU']?> <?=$_SESSION['article']['REFFO']?></div>
	
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
Qte : <input type="text" value="<?=round($_SESSION['article']['AFPCB']) ? round($_SESSION['article']['AFPCB']):''?>" name="qte_vl_20" size="2"/> dans 
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

<h1>Code taille emplacement <input type="text" value="<?=isset($_SESSION['code_taille_emplacement']) ? $_SESSION['code_taille_emplacement']:'' ?>" name="code_taille_emplacement" size="2"/></h1>
<h1>Qte max en (VL 10) <input type="text" value="" name="qte_max_vl_10" size="2"/></h1>

<input type="button" value="Valider" onclick="verif_form();"/>

<br/><br/><br/><br/>
<h1>Code famille prépa <input type="text" value="<?=isset($_SESSION['code_famille_prepa']) ? $_SESSION['code_famille_prepa']:'' ?>" name="code_famille_prepa" size="2"/></h1>

<? } else { /////////// ON A DEJA SAISIE UN GENCODE //////////////////////////////////////////////////////////////////////////////////////////////////// ?>

<input type="hidden" name="what" value="saisie_gencode" />
<h1>Scanner le code barre</h1>
	<input type="text" id="reference" name="reference" value="" style="width:80%;margin:auto;"/>
</div>

<? } // fin on a deja saisie un gencode ?>

</center>
</form>
</body>
</html>