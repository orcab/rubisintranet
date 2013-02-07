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
select	ARTICLE.NOART,ARTICLE.DESI1,ARTICLE.DESI2,ARTICLE_FOURNISSEUR.NOFOU,ARTICLE_FOURNISSEUR.REFFO,ARTICLE_FOURNISSEUR.AFPCB,FICHE_STOCK.LOCAL
from				${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
						on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
		left join	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 FICHE_STOCK
						on ARTICLE.NOART=FICHE_STOCK.NOART and FICHE_STOCK.DEPOT='$LOGINOR_DEPOT'
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

		$OLD_CSV = file('old_vl_produit.csv') or die("Ne peux pas ouvrir l'ancienne base de donnée");
		foreach($OLD_CSV as $ligne) {
			$data = explode(';',$ligne);
			if ($data[0] == $_SESSION['article']['NOART']) { // on a trouvé l'article deja renseigné
				$_SESSION['article']['unite_vl_10']		= $data[5];
				$_SESSION['article']['qte_vl_20']		= $data[6];
				$_SESSION['article']['unite_vl_20']		= $data[7];
				$_SESSION['article']['qte_max_vl_10']	= $data[9];
				break;
			}
		}

	} elseif (sizeof($resultats) <= 0) {
		$_SESSION['article'] = array();
		$_SESSION['reference'] = '';
		$message = "Aucun article ne correspond au code barre";

	} else {
		$_SESSION['article'] = array();
		$_SESSION['reference'] = '';
		$message = "Il existe plusieurs codes articles pour ce code barre";
	}


// on reset et on revient à lécran de saisie de l'article
} elseif (isset($_GET['what']) && $_GET['what']=='reset') {
	$_SESSION['article'] = array();


// tout est bon, on enregistre
} elseif (isset($_POST['what']) && $_POST['what']=='enregistre') {

	if (!file_exists(DATABASE)) // le fichier n'existe pas, on cree les entetes
		$buffer = join(';',array('code_article','fournisseur','reference','designation1','designation2','localisation_reflex','etiquette_localisation_reflex_dead','localisation_rubis','unite_vl_10','qte_vl_20','unite_vl_20','code_taille_emplacement','qte_max_vl_10','code_famille_prepa','ip_terminal','ean13_flashe','date_saisie','emplacement_a_verifier'))."\n";

	$buffer .= join(';',array(
							$_SESSION['article']['NOART'],
							$_SESSION['article']['NOFOU'],
							$_SESSION['article']['REFFO'],
							$_SESSION['article']['DESI1'],
							$_SESSION['article']['DESI2'],
							$_POST['localisation_reflex'],
							isset($_POST['etiquette_localisation_reflex_dead'])?'1':'',
							$_POST['localisation_rubis'],
							$_POST['unite_vl_10'],
							$_POST['qte_vl_20'],
							$_POST['unite_vl_20'],
							$_POST['code_taille_emplacement'],
							$_POST['qte_max_vl_10'],
							$_POST['code_famille_prepa'],
							$_SERVER['REMOTE_ADDR'],
							$_SESSION['reference'],
							date('Y/m/d H:i'),
							isset($_POST['emplacement_a_verifier'])?'1':'',
						))."\n";

	// enregistre le tout dans un fichier CSV
	$CSV = fopen(DATABASE,'a') ;
	fwrite($CSV,$buffer);
	fclose($CSV);

	$message = "Article ".$_SESSION['article']['NOART']." enregistré";
	
	$_SESSION['code_taille_emplacement']	= $_POST['code_taille_emplacement'];
	$_SESSION['code_famille_prepa']			= $_POST['code_famille_prepa'];
	$_SESSION['article'] = array();

}
?>
<html>
<head>
<title>VL produit</title>
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

input#valider {
	color:black;
	background-color:yellow;
}

</style>

<script language="javascript">
<!--

// au chargement de la page
function init() {
	if		(document.getElementById('reference'))
		document.getElementById('reference').focus();

	else if (document.getElementById('localisation_reflex'))
		document.getElementById('localisation_reflex').focus();
}


function verif_form() {
	document.mon_form.submit();

	/*if (document.mon_form.qte_vl_20.value == '') {
		alert("Veuillez saisir une quantité en VL 20");

	} else if (document.mon_form.code_taille_emplacement.value == '') {
		alert("Veuillez saisir un code taille emplacement");

	} else if (document.mon_form.qte_max_vl_10.value == '')	{
		alert("Veuillez une quantité maximum en VL 10");

	} else if (document.mon_form.code_famille_prepa.value == '') {
		alert("Veuillez saisir un code famille prepa");

	// tout est bon, on envoi
	} else {
		document.mon_form.submit();
	}*/
}


//-->
</script>

</head>
<body onload="init();">

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="mon_form" action="<?=basename($_SERVER['PHP_SELF'])?>" method="POST">
<center>

<h3 id="titre3"><?=$message?></h3>

<? if (is_array($_SESSION['article']) && sizeof($_SESSION['article'])>0) { ///////////////// ON A PAS ENCORE SAISIE DE GENCODE ////////////////////////// ?>

<input type="hidden" name="what" value="enregistre"/>

<div style="text-align:left;">
	<div class="code"><?=$_SESSION['article']['NOART']?></div>
	<div class="desi1"><?=$_SESSION['article']['DESI1']?></div>
	<div class="desi2"><?=$_SESSION['article']['NOFOU']?> <?=$_SESSION['article']['REFFO']?></div>

Localisation Reflex : <input id="localisation_reflex" type="text" value="" name="localisation_reflex" size="10"/>&nbsp;&nbsp;E<input type="checkbox" name="etiquette_localisation_reflex_dead"/>&nbsp;&nbsp;F<input type="checkbox" name="emplacement_a_verifier"/><br/>
Localisation Rubis : <input type="text" value="<?=$_SESSION['article']['LOCAL']?>" name="localisation_rubis" size="7"/><br/><br/>

VL 10 / Qte : 1
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


<?	// soit on recupere l'ancienne valeur rentrée, soit on va chercher dans Rubis
	$qte_vl_20 = '';
	if		(isset($_SESSION['article']['qte_vl_20']) && $_SESSION['article']['qte_vl_20']!='')
		$qte_vl_20 = $_SESSION['article']['qte_vl_20'];
	elseif	(round($_SESSION['article']['AFPCB']))
		$qte_vl_20 = round($_SESSION['article']['AFPCB']);
?>
VL 20 / Qte : <input type="text" value="<?=$qte_vl_20?>" name="qte_vl_20" size="2"/> dans 
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
<h1>Qte max en (VL 10) <input type="text" name="qte_max_vl_10"  value="<?=isset($_SESSION['article']['qte_max_vl_10']) ? $_SESSION['article']['qte_max_vl_10']:''?>" size="2"/></h1>

<br/>
<center>
	<input id="valider" type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Valider&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onclick="verif_form();"/><br/><br/><br/>
	<input id="annuler" type="button" value="&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Annuler&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;" onclick="javascript:document.location.href='<?=basename($_SERVER['PHP_SELF'])?>?what=reset';"/>
<center>

<br/>
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