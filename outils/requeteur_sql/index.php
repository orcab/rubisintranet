<? include('../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Requeteur SQL</title>

<style>

body {
    font-family: verdana;
    font-size: 0.8em;
}
h1 {
    font-size: 1.2em;
}
.message {
    color: #FF0000;
    font-weight: bold;
    text-align: center;
}
#recherche {
    border: 1px solid #808080;
    margin: 5px auto auto;
    padding: 20px;
    width: 80%;
}
#lignes {
    border: 1px solid #000000;
    border-collapse: collapse;
    margin: 1em auto auto;
    width: 80%;
}
#lignes th, #lignes td {
    border: 1px solid #999999;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #CCCCCC;
    text-decoration: line-through;
}
caption {
    background-color: #DDDDDD;
    padding: 2px;
}
span.field_type {
    color: grey;
    font-size: 0.8em;
    font-weight: normal;
}
.legende {
    font-size: 0.7em;
    margin: 2em auto auto;
    width: 55%;
}
#resultats td {
    border: solid 1px grey;
    text-align: right;
    font-size: 0.9em;
}
#resultats th {
    text-align: center;
    border: solid 1px grey;
    padding: 5px;
    font-size: 0.8em;
}
table#resultats {
    margin: auto;
    cellspacing: 0;
    border-collapse: collapse;
}
tfoot {
    background-color: #CCCCCC;
    border: 2px solid #555555;
}

pre#requete {
    border: solid 1px grey;
    margin-top: 2em;
    color: grey;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../js/jquery.js"></script>
<script type="text/javascript" src="../../js/jquery-ui-1.10.3.custom.min.js"></script>

<script language="javascript">
<!--

$(document).ready(function(){
	
});

function verif_form() {
	var selected_request = $('#requeteur input[type=radio]:checked').val()

	if 			(		selected_request == 'montant_honorable_rubis'
					||	selected_request == 'code_taille_emplacement_article_lie_reflex'
				) {
		// do nothing (no param)

	} else if 	(		selected_request == 'manquant_a_la_preparation_reflex'
					||	selected_request == 'remise_zero_gei_reflex'
				) {
		// ask for a date (dd/mm/yyyy)
		var input_user = '';
		while (!input_user) {
			input_user = prompt("Pour quelle date ? (jj/mm/aaaa)");
		}
		$('#param1').val(input_user);
	}

	console.log($('#param1').val());
	requeteur.submit();
}
//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils</a>

<form id="requeteur" name="requeteur" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" id="param1" name="param1" value=""/>
<div id="recherche">
	<h1>Choix de la requete</h1>
	<input type="radio" id="montant_honorable_rubis" name="requete" value="montant_honorable_rubis"/><label for="montant_honorable_rubis">Montant honorable à l'instant T (Rubis)</label><br/>
	<input type="radio" id="manquant_a_la_preparation_reflex" name="requete" value="manquant_a_la_preparation_reflex"/><label for="manquant_a_la_preparation_reflex">Manquant à la préparation à une date (Reflex)</label><br/>
	<!--<input type="radio" id="remise_zero_gei_reflex" name="requete" value="remise_zero_gei_reflex"/><label for="remise_zero_gei_reflex">Remise à 0 des GEI pour une date (Reflex)</label><br/>-->
	<input type="radio" id="code_taille_emplacement_article_lie_reflex" name="requete" value="code_taille_emplacement_article_lie_reflex"/><label for="code_taille_emplacement_article_lie_reflex">Code taille emplacement et articles liés (Reflex)</label>
	<br/><br/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Afficher les résultats</a><br/>
</div>
</form>

<? if (isset($_POST['requete']) && $_POST['requete']) { // si une requete est demandée
	$time = microtime(1);

	$reflex = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
	$rubis	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	
	$tmp = split('_',$_POST['requete']);
	$source = (end($tmp) == 'rubis' ? $rubis:$reflex) ;

	$sql = '';
	if (function_exists($_POST['requete'])) {
		$sql = call_user_func($_POST['requete']);
	} else {
		die("La requete $_POST[requete] n'existe pas");
	}

	// lance la requete
	$res 		= odbc_exec($source,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
	$errormsg 	= '';
	if (!$res) {
		$errormsg = odbc_errormsg($source);
	}
	$nb_field 	= odbc_num_fields($res);
	$nb_rows	= odbc_num_rows($res);

	if ($errormsg) { ?>
		<div class="message">ERREUR : <?=$errormsg?></div>
<?	} ?>

<table id="resultats">
<caption><?=$nb_rows?> résultats</caption>

<!-- entete -->
<thead>
<tr>
	<th>#</th>
<? for($i=1 ; $i<=$nb_field ; $i++) { ?>
		<th><span class="field_name"><?=odbc_field_name($res,$i)?></span><br/><span class="field_type">(<?=strtolower(odbc_field_type($res,$i))?>)</span></th>
<? } ?>
</tr>
</thead>

<!-- results -->
<tbody>
<? for($i=1 ; $row=odbc_fetch_row($res,$i) ; $i++) { ?>
	<tr>
		<td><?=$i?></td>
		<? for($j=1 ; $j<=$nb_field ; $j++) { ?>
			<td><?=odbc_result($res,$j);?></td>
  		<? } ?>
	</tr>
<?} ?>
</tbody>


<!-- footer -->
<tfoot>
<tr><td colspan="<?=$nb_field + 1?>">Temps d'execution : <?= sprintf('%0.4f',microtime(1) - $time)?> sec</td></tr>
</tfoot>

</table>

<pre id="requete"><?=htmlentities($sql)?></pre>
<? } // fin if requete ?>

</body>
</html>
<?

// permet de voirl e stock des commande honorable a l'instant T saisie dans Rubis
function montant_honorable_rubis() {
	return <<<EOT
select
	SUM(MONPR) as MONTANT_PR_HT,
	SUM(MONHT) as MONTANT_CA_HT,
	TYCDD as TYPE_LIGNE,
	ACTBO as ACTIVITE
from AFAGESTCOM.ADETBOP1 DETAIL_CDE
	left join AFAGESTCOM.ASTOCKP1 STOCK
	 on STOCK.DEPOT='AFA' and STOCK.NOART=DETAIL_CDE.CODAR
where
	ETSBE='' -- ligne active
	and PROFI='1' -- pas un commentaire
	and TRAIT='R' -- encore a livrer
	and DETAIL_CDE.AGENC='AFA' and DETAIL_CDE.DEPOT='AFA' -- uniquement le depot AFA
	and STOCK.QTINV>=DETAIL_CDE.QTESA -- le stock en cours est supérieur à la qte commandée
group by TYCDD, ACTBO
order by ACTBO asc,TYCDD asc
EOT;
}

function manquant_a_la_preparation_reflex() {
	$date = extract_days_from_date_ddmmyyyy($_POST['param1']);

	return <<<EOT
SELECT 	P1CART as CODE_ARTICLE,P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE, P1NANP as ANNEE_PREPA, P1NPRE as NUM_PREPA , P1CDES as DESTINATAIRE
FROM 	RFXPRODDTA.reflex.HLPRPLP
WHERE 	P1QPRE<P1QAPR AND P1NNSL=0
	and P1SSCA='$date[siecle]' and P1ANCA='$date[annee]' and P1MOCA='$date[mois]' and P1JOCA='$date[jour]'
EOT;
}

function remise_zero_gei_reflex() {
	$date = extract_days_from_date_ddmmyyyy($_POST['param1']);

	return <<<EOT
-- remise a zero des article par GEI
select
	VGCART as CODE_ARTICLE,ARLART as  LIBELLE_1,ARMDAR as LIBELLE_2,VGCUCR as QUI,
	VGSCRE as SIECLE,VGACRE as ANNEE,VGMCRE as MOIS,VGJCRE as JOUR,VGHCRE as HEURE,
	VGQMVG as QUANTITE,
	VGNSUP as NUM_SUPPORT,
	EMC1EM,EMC2EM,EMC3EM,EMC4EM,EMC5EM,
	VGCMES as CODE_MOUVEMENT,
	VGRMVS as COMMENTAIRE
from RFXPRODDTA.reflex.HLMVTGP MVT_GEI
	left join RFXPRODDTA.reflex.HLARTIP ARTICLES
		on MVT_GEI.VGCART = ARTICLES.ARCART
	left join RFXPRODDTA.reflex.HLSUPPP SUPPORTS
		on MVT_GEI.VGNSUP = SUPPORTS.SUNSUP
	left  join RFXPRODDTA.reflex.HLEMPLP EMPLACEMENTS
		on SUPPORTS.SUNEMP = EMPLACEMENTS.EMNEMP
where
	VGSMVG='-'
	and (VGCTMS='340' or VGCTMS='250') -- inventaire et modif qte/poids
	and VGCTST='200'
	and VGQGAM=VGQMVG 
	and VGSCRE='$date[siecle]' and VGACRE='$date[annee]' and VGMCRE='$date[mois]' and VGJCRE='$date[jour]'
EOT;
}

function code_taille_emplacement_article_lie_reflex() {
	return <<<EOT
select
GECART as CODE_ARTICLE,
EMC1EM,EMC2EM,EMC3EM,EMC4EM,EMC5EM,
EMCTAI as CODE_TAILLE_EMPLACEMENT,
EMTEPI as EMPLACEMENT_PICKING
from RFXPRODDTA.reflex.HLGEINP GEIS
      left join RFXPRODDTA.reflex.HLSUPPP SUPPORTS
         on GEIS.GENSUP=SUPPORTS.SUNSUP
      left join  RFXPRODDTA.reflex.HLEMPLP EMPLACEMENTS
         on SUPPORTS.SUNEMP=EMPLACEMENTS.EMNEMP

order by GECART ASC
EOT;
}

function extract_days_from_date_ddmmyyyy($var) {
	if (strlen($var) > 0) {
		$tmp = split('/',$var); // format dd/mm/yyyy
		$jour = $tmp[0];
		$mois = $tmp[1];
		$siecle = substr($tmp[2],0,2);
		$annee = substr($tmp[2],2,2);
		return array('siecle'=>$siecle, 'annee'=>$annee, 'mois'=>$mois, 'jour'=>$jour);
	} else {
		die("Format de date non reconnu");
	}
}
?>