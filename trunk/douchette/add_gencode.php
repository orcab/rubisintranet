<?
include('../inc/config.php');

$message = '';

// on vient de sasir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && $_POST['gencode'] && $_POST['reference']) {
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$reference	= strtoupper(mysql_escape_string(trim($_POST['reference'])));
	$gencode	= strtoupper(mysql_escape_string(trim($_POST['gencode'])));
	$sql = <<<EOT
select	ARTICLE.NOART,DESI1,FOUR1,REFFO,AFOG3
from				${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		left join	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
						on ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
where	(ARTICLE.NOART='$reference') or (REFFO='$reference')
EOT;
	$res = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete : $sql");
	$resultats = array();
	while($row = odbc_fetch_array($res)) {
		array_push($resultats,$row);
	}

	if (sizeof($resultats) > 1) { // si plus d'un resultat, il faut proposé le choix	
		$message = "La référence tapée correspond à plusieurs articles<br>Choisissez en un :";
	} elseif (sizeof($resultats) == 1) { // si un seul résultat, on enresgitre le tout dans le systeme
		$resultats[0]['NOART'] = trim($resultats[0]['NOART']);
		$resultats[0]['AFOG3'] = trim($resultats[0]['AFOG3']);
		//print_r($resultats);

		if (!$row['AFOG3']) // si pas de code barre MCS renseigné --> on le calcul
			odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 set AFOG3='".calcul_gencode_from_noart($resultats[0]['NOART'])."' where NOART='".$resultats[0]['NOART']."'")  or die("Impossible de lancer la requete : $sql");
		odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set GENCO='$gencode' where NOART='".$resultats[0]['NOART']."'")  or die("Impossible de lancer la requete : $sql");
		//echo "update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set GENCO='$gencode' where NOART='".$resultats[0]['NOART']."'";
		$message = "Code barre enregistré";
	} elseif (sizeof($resultats) <= 0) { // Aucune référence trouvé dans le systeme --> afficher une erreur
		$message = "La référence tapée ne correspond à aucun article";
	}
	odbc_close($loginor);



// on a fourni le code MCS a près un premier choix dans une liste
} elseif (isset($_POST['what']) && $_POST['what']=='saisie_code' && $_POST['gencode'] && $_POST['code']) {

	$loginor= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$code	= strtoupper(mysql_escape_string(trim($_POST['code'])));
	$gencode= strtoupper(mysql_escape_string(trim($_POST['gencode'])));
	odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 set AFOG3='".calcul_gencode_from_noart($code)."' where NOART='$code'")  or die("Impossible de lancer la requete : $sql");
	odbc_exec($loginor,"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set GENCO='$gencode' where NOART='$code'")  or die("Impossible de lancer la requete : $sql");
	$message = "Code barre enregistré";
	odbc_close($loginor);
}


function calcul_gencode_from_noart($code_article) {
	//echo "len='".(12 - strlen('2'.$code_article))."'" ; exit;

	$gencod_mcs	= '2'.$code_article . str_repeat('0' , 12 - strlen('2'.$code_article))  ;
	//$gencod		= explode('',$gencod_mcs) ;
	$cle		= 3*($gencod_mcs[1]+$gencod_mcs[3]+$gencod_mcs[5]+$gencod_mcs[7]+$gencod_mcs[9]+$gencod_mcs[11]) + ($gencod_mcs[0]+$gencod_mcs[2]+$gencod_mcs[4]+$gencod_mcs[6]+$gencod_mcs[8]+$gencod_mcs[10])  ;
	$gencod_mcs .= substr((10 - substr($cle,-1)),-1); # calcul de la clé de check

	return $gencod_mcs;
}
?>
<html>
<head>
<title>Saisie GENCODE</title>
<style>
a img { border:none; }

@media print {
	.hide_when_print { display:none; }
}
</style>

<!-- STYLE POUR UN TERMINAL DEPORTE -->
<style>
body {
	margin:0px;
	border:solid 1px green;
	font-family:verdana;
	width:240px;
	height:210px;
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
	font-size:0.7em;
	text-align:center;
}

h2,h3 {
	color:red;
}

</style>

<script language="javascript">
<!--

// au chargement de la page
function init() {
	if (document.getElementById('div_gencode'))
		document.getElementById('div_gencode').focus();
	else if (document.getElementById('choix'));
		document.getElementById('choix').focus()
}
	
function check_enter_on_gencode(e) {
	if (e.keyCode == 13) { // on a valider la saisie
		document.mon_form.gencode.value=document.getElementById('div_gencode').innerHTML;
		document.getElementById('div_gencode').style.display='none';
		document.getElementById('titre1').innerHTML = "Saisissez la référence ou le code MCS";
		document.getElementById('titre2').innerHTML = "Code barre : "+document.mon_form.gencode.value;
		document.getElementById('reference').style.display='block';
		document.mon_form.reference.focus();
	}
}

function check_enter_on_choix(e) {
	if (e.keyCode == 13) { // on a valider la saisie
		var code = document.getElementById('code_'+document.getElementById('choix').innerHTML).innerHTML ;
		if (code) {
			document.mon_form.code.value=code;
			document.mon_form.submit();
		} else {
			alert("Votre choix "+document.getElementById('choix').innerHTML+" ne semble pas valide");
		}
	}
}

//-->
</script>


</head>
<body onload="init();">

<!-- DECLARATION DU FORMULAIRE PRINCIPALE -->
<form name="mon_form" action="<?=basename($_SERVER['PHP_SELF'])?>" method="POST">


<? if (	$message == '' ||
		$message == "Code barre enregistré" ||
		$message == "La référence tapée ne correspond à aucun article") { // on vient d'enregistré un code ou on vient d'appeller la page ?>

	<input type="hidden" name="what" value="saisie_gencode" />
	<input type="hidden" name="gencode" value="" />
	<h3 id="titre3"><?=$message?></h3>
	<h2 id="titre2"></h2>
	<h1 id="titre1">Scanner le code barre à enregistrer dans Rubis</h1>
	<div style="text-align:center;border:none;">
		<div id="div_gencode" style="width:80%;height:20px;display:block;" contenteditable="true" onkeyup="check_enter_on_gencode(event)" autocomplete="off"></div>
		<input id="reference" name="reference" style="width:80%;height:20px;display:none;" value="" />
	</div>



<? } elseif ($message == "La référence tapée correspond à plusieurs articles<br>Choisissez en un :") { ?>
	
	<input type="hidden" name="what" value="saisie_code" />
	<input type="hidden" name="code" value="" />
	<input type="hidden" name="gencode" value="<?=$_POST['gencode']?>" />

	<h3 id="titre3"><?=$message?></h3>
	<table>
		<tr>
			<th></th>
			<th>Code</th>
			<th>Four.</th>
			<th>Réf</th>
		</tr>
<?		for($i=0 ; $i<sizeof($resultats) ; $i++) { ?>
			<tr>
				<td rowspan="2" style="width:25px;vertical-align:middle;text-align:center;color:red;font-weight:bold;font-size:1.1em;border-bottom:dotted 1px green;"><?=$i+1?></td>
				<td id="code_<?=$i+1?>"><?=trim($resultats[$i]['NOART'])?></td>
				<td><br/><?=trim($resultats[$i]['FOUR1'])?></td>
				<td><?=trim($resultats[$i]['REFFO'])?></td>
			</tr>
			<tr>
				<td colspan="3" style="margin-bottom:10px;border-bottom:dotted 1px green;color:#BB0;"><?=trim($resultats[$i]['DESI1'])?></td>
			</tr>
<?		} // fin while?>
	</table>
	
	<div style="margin-top:10px;border:none;">Votre choix : <div id="choix" style="width:20px;height:20px;border:solid 1px green;color:red;font-weight:bold;font-size:1.1em;" contenteditable="true" onkeyup="check_enter_on_choix(event)" autocomplete="off"></div></div>
	
<? } ?>

</form>
</body>
</html>
<?

?>