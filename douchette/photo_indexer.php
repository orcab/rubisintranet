<?
include('../inc/config.php');

$message = '';
$resultats = array();

// on vient de saisir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && isset($_POST['reference']) && $_POST['reference']) {
	$loginor    = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$reference	= strtoupper(mysql_escape_string(trim($_POST['reference'])));
	$sql = <<<EOT
select	ARTICLE.NOART,ARTICLE.DESI1,ARTICLE_FOURNISSEUR.REFFO,ARTICLE_FOURNISSEUR.NOFOU
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
	while($row = odbc_fetch_array($res)) {
		array_push($resultats,$row);
		break; // on ne sauve qu'un article (le premier)
	}

	if (sizeof($resultats) <= 0) { // Aucune référence trouvé dans le systeme --> afficher une erreur
		$message = "La référence tapée ne correspond à aucun article";
	}
	odbc_close($loginor);
} elseif (isset($_POST['what']) && $_POST['what']=='valide_gencode' && isset($_POST['noart']) && $_POST['noart']) {
	// sauve le noart quelque part (dans une table mysql par exemple)
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

	$noart = mysql_escape_string($_POST['noart']);
	$res = mysql_query("INSERT INTO photo_indexer (code_article,`date`,renamed) VALUES ('$noart',NOW(),0)") or die(mysql_error());
	$nb_article_a_traiter = e('nb_article_a_traiter',mysql_query("SELECT count(code_article) as nb_article_a_traiter FROM photo_indexer where renamed=0"));
	
	mysql_close($mysql);

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
		document.mon_form.submit();

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

	<h3 id="titre3"><?=$message?></h3>

<? if (sizeof($resultats) >= 1) { // on a deja saisie un code, on propose de la valider l'article ?>
	<table>
		<tr>
			<th>Code</th>
			<th>Four.</th>
			<th>Réf</th>
		</tr>
		<tr>
			<td id="code_<?=$i+1?>"><?=trim($resultats[0]['NOART'])?></td>
			<td><br/><?=trim($resultats[0]['NOFOU'])?></td>
			<td><?=trim($resultats[0]['REFFO'])?></td>
		</tr>
		<tr>
			<td colspan="3" style="margin-bottom:10px;border-bottom:dotted 1px green;color:#BB0;"><?=trim($resultats[0]['DESI1'])?></td>
		</tr>
	</table>
	<br/>
	<table>
		<tr>
			<td><input type="submit" height="30" value="    OK    "/></td>
			<td><input type="button" height="30" value="  ANNULER  "/></td>
		</tr>
	</table>

	<input type="hidden" name="what" value="valide_gencode" />
	<input type="hidden" name="noart" value="<?=trim($resultats[0]['NOART'])?>" />

<?	} else { // fin if resultat
		// 	aucun code barre de saisie, on propose la saisie ?>
		
	<input type="hidden" name="what" value="saisie_gencode" />
	<h1 id="titre1">Scanner le code</h1>
	<div style="text-align:center;border:none;">
		<div id="reference" style="width:80%;height:20px;display:block;" contenteditable="true" onkeyup="check_enter_on_gencode(event)" autocomplete="off"></div>
		<input type="hidden" id="reference_hidden" name="reference" value="" />
	</div>
	
<? } ?>
</form>
</body>
</html>