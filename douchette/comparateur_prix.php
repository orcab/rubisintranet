<?
include('../inc/config.php');
session_start();

$message = '';
$delete_panier = TRUE;


// on vient de saisir un gencode et une référence --> on regarde dans la base si un truc correspond
if (isset($_POST['what']) && $_POST['what']=='saisie_gencode' && $_POST['reference']) {
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

	if (sizeof($resultats) > 1) { // si plus d'un resultat, il faut proposé le choix	
		$message = "La référence tapée correspond à plusieurs articles<br>Choisissez en un :";
	} elseif (sizeof($resultats) == 1) { // si un seul résultat, on enregistre le tout dans le systeme
		
		// inscrire le produit
		if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
			if (!in_array($resultats[0]['NOART'],$_SESSION['panier']))
				array_push($_SESSION['panier'],$resultats[0]['NOART']);
		} else {
			$_SESSION['panier'] = array($resultats[0]['NOART']);
		}

		$message = "Choix validé";
	} elseif (sizeof($resultats) <= 0) { // Aucune référence trouvé dans le systeme --> afficher une erreur
		$message = "La référence tapée ne correspond à aucun article";
	}
	odbc_close($loginor);
	$delete_panier = FALSE;
	

// on a fourni le code MCS après un premier choix dans une liste
} elseif (isset($_POST['what']) && $_POST['what']=='saisie_code' && $_POST['code']) {

	$loginor= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$code	= strtoupper(mysql_escape_string(trim($_POST['code'])));
	
	// inscrire le produit
	if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
		if (!in_array($resultats[0]['NOART'],$_SESSION['panier']))
			array_push($_SESSION['panier'],$resultats[0]['NOART']);
	} else {
		$_SESSION['panier'] = array($resultats[0]['NOART']);
	}

	$message = "Choix validé";
	odbc_close($loginor);
	$delete_panier = FALSE;
}

if ($delete_panier)
	unset($_SESSION['panier']);

?>
<html>
<head>
<title>Comparateur de prix</title>
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

h2,h3 {
	color:red;
}

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

td.fournisseur {
	color:lightgreen;
}

td.reference {
	color:lightgreen;
}

.desi1 {
	border:none;
}

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
	else if (document.getElementById('choix'));
		document.getElementById('choix').focus()
}

function check_enter_on_gencode(e) {
	if (e.keyCode == 13) { // on a valider la saisie
		document.mon_form.reference.value=document.getElementById('reference').innerHTML.replace(/<br *>/i,'');
		document.mon_form.submit();

		//alert(document.mon_form.reference.value);
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


<? if (	$message != "La référence tapée correspond à plusieurs articles<br>Choisissez en un :" ) { // on vient d'enregistré un code ou on vient d'appeller la page ?>

	<input type="hidden" name="what" value="saisie_gencode" />
	<input type="hidden" name="gencode" value="" />
	<h3 id="titre3"><?=$message?></h3>
	<h2 id="titre2"></h2>
	<h1 id="titre1">Scanner le code</h1>
	<div style="text-align:center;border:none;">
		<div id="reference" style="width:80%;height:20px;display:block;" contenteditable="true" onkeyup="check_enter_on_gencode(event)" autocomplete="off"></div>
		<input type="hidden" id="reference_hidden" name="reference" value="" />
	</div>



<? } else { ?>
	
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


<?	// affichage du panier de comparaison de prix
	if (isset($_SESSION['panier']) && is_array($_SESSION['panier'])) {
		//print_r($_SESSION['panier']);
	
		$where = array();
		foreach ($_SESSION['panier'] as $key) {
			$where[] = " (A.NOART='".mysql_escape_string($key)."') ";
		}
		$where = join(' or ',$where);

		// requete de recherche des infos produits
		$sql = <<<EOT
select	A.NOART as CODE_ARTICLE,
		DESI1 as DESIGNATION1,
		DESI2 as DESIGNATION2,
		NOMFO as FOURNISSEUR,
		REFFO as REF_FOURNISSEUR,
		ROUND(T.PVEN1,2) as PRIX_NET,
		STSER as SERVI,
		STOMI as STOCK_MINI,
		STOMA as STOCK_MAXI,
		STGES as GESTIONNAIRE,
		LOCAL as LOCALISATION
from	${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 A
			left outer join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 A_F
				on A.NOART=A_F.NOART and A.FOUR1=A_F.NOFOU
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 F
				on A_F.NOFOU=F.NOFOU
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ATARPVP1 T
				on A.NOART=T.NOART and T.PVT09='E'						-- tarif en cours
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 S
				on A.NOART=S.NOART
where	
			T.AGENC ='${LOGINOR_AGENCE}'
		and S.DEPOT='${LOGINOR_DEPOT}'
		and ($where)
EOT;

	//echo $sql;
		
		// on execuite la requete et on stock les résultat en mémoire pour les réafficher dans l'ordre inverse des demandes. (le plus récent en haut)
		$resultats = array();
		$res = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete : $sql");
		while($row = odbc_fetch_array($res))
			$resultats[$row['CODE_ARTICLE']] = $row ;

		//print_r($resultats);
?>
		<table id="articles">

<?		$panier = array_reverse($_SESSION['panier']);
		//print_r($resultats);
		foreach($panier as $code_article) {
			$row = $resultats[$code_article] ;
?>
			<tr>
				<td class="code"><?=$row['CODE_ARTICLE']?></td>
				<td class="fournisseur"><?=$row['FOURNISSEUR']?></td>
				<td class="reference"><?=$row['REF_FOURNISSEUR']?></td>
			</tr>
			<tr>
				<td class="designation ligne2" colspan="2">
					<div class="desi1"><?=$row['DESIGNATION1']?></div>
					<div class="desi2"><?=$row['DESIGNATION2']?></div>
				</td>
				<td class="prix ligne2" nowrap><?=sprintf('%0.2f',$row['PRIX_NET'])?>&nbsp;&euro;</td>
			</tr>
			<tr>
				<td class="ligne3 servi"><?=$row['GESTIONNAIRE']?> <img src="gfx/<?= $row['SERVI']=='OUI' ? 'accept':'cancel2' ?>.gif"></td>
				<td class="ligne3 mini_maxi">mini:<?=ceil($row['STOCK_MINI'])?> maxi:<?=ceil($row['STOCK_MAXI'])?></td>
				<td class="ligne3 localisation"><?=$row['LOCALISATION']?></td>
			</tr>
<?		} ?>
		</table>
<?	} ?>
</form>
</body>
</html>
<?

?>