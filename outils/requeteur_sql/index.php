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

<link rel="stylesheet" href="../../js/highlight/styles/arta.css">
<script src="../../js/highlight/highlight.pack.js"></script>
<script>hljs.initHighlightingOnLoad();</script>

<script language="javascript">
<!--

function verif_form() {
	var selected_request = $('#requeteur input[type=radio]:checked').val()

	if 			(		selected_request == 'montant_honorable_rubis'
					||	selected_request == 'code_taille_emplacement_article_lie_reflex'
				) {
		// do nothing (no param)

	} else if 	(		selected_request == 'manquant_a_la_preparation_reflex'
					||	selected_request == 'remise_zero_gei_reflex'
					||	selected_request == 'livraison_entre_5h_et_5h30_rubis'
				) {
		// ask for a date (dd/mm/yyyy)
		var input_user = '';
		while (!input_user) {
			input_user = prompt("Pour quelle date ? (jj/mm/aaaa)");
		}
		$('#param1').val(input_user);

	} else if 	(		selected_request == 'livraison_en_double_rubis' ) {
		// ask for a year (yyyy)
		var input_user = '';
		while (!input_user) {
			input_user = prompt("Pour quelle année ? (aaaa)");
		}
		$('#param1').val(input_user);

	} else if 	(		selected_request == 'mouvement_gei_inventaire' ) {
		// ask for a code (INVddmmyyxxx)
		var input_user = '';
		while (!input_user) {
			input_user = prompt("Date inventaire ? (jj/mm/aaaa)");
			$('#param1').val(input_user);
		}
		input_user = '';
		while (!input_user) {
			input_user = prompt("Code inventaire ? (xxx)");
			$('#param2').val(input_user);
		}
		
	}

	console.log($('#param1').val());
	console.log($('#param2').val());
	requeteur.submit();
}
//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils</a>

<form id="requeteur" name="requeteur" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" id="param1" name="param1" value=""/>
<input type="hidden" id="param2" name="param2" value=""/>
<div id="recherche">
	<h1>Choix de la requete</h1>
	<input type="radio" id="montant_honorable_rubis" 					name="requete" value="montant_honorable_rubis"/><label 						for="montant_honorable_rubis">Montant honorable à l'instant T (Rubis)</label><br/>
	<input type="radio" id="manquant_a_la_preparation_reflex" 			name="requete" value="manquant_a_la_preparation_reflex"/><label 			for="manquant_a_la_preparation_reflex">Manquant à la préparation à une date (Reflex)</label><br/>
	<input type="radio" id="mouvement_gei_inventaire" 					name="requete" value="mouvement_gei_inventaire"/><label 					for="mouvement_gei_inventaire">Mouvement GEI suite à un inventaire (Reflex)</label><br/>
	<!--<input type="radio" id="remise_zero_gei_reflex" 				name="requete" value="remise_zero_gei_reflex"/><label 						for="remise_zero_gei_reflex">Remise à 0 des GEI pour une date (Reflex)</label><br/>-->
	<input type="radio" id="code_taille_emplacement_article_lie_reflex" name="requete" value="code_taille_emplacement_article_lie_reflex"/><label 	for="code_taille_emplacement_article_lie_reflex">Code taille emplacement et articles liés (Reflex)</label><br/>
	<input type="radio" id="livraison_entre_5h_et_5h30_rubis" 			name="requete" value="livraison_entre_5h_et_5h30_rubis"/><label 			for="livraison_entre_5h_et_5h30_rubis">Livraison entre 5h et 5h30 via STRACC (Rubis)</label><br/>
	<input type="radio" id="livraison_en_double_rubis" 					name="requete" value="livraison_en_double_rubis"/><label 					for="livraison_en_double_rubis">Test (Rubis)</label>
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
<pre><code><?=htmlentities($sql)?></code></pre>
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
SELECT 	P1CART as CODE_ARTICLE,
		ARLART as DESIGNATION, ARMDAR as DESIGNATION2, 
		P1QAPR as QTE_A_PREPARER, P1QPRE as QTE_PREPAREE, P1NANP as ANNEE_PREPA, P1NPRE as NUM_PREPA ,
		OERODP as REFERENCE_OPD,
		P1CDES as CODE_DEST,
		DSLDES as DESTINATAIRE
FROM 	RFXPRODDTA.reflex.HLPRPLP PREPA_DETAIL
		left join RFXPRODDTA.reflex.HLARTIP ARTICLE
			on PREPA_DETAIL.P1CART=ARTICLE.ARCART
		left join RFXPRODDTA.reflex.HLDESTP DEST
			on PREPA_DETAIL.P1CDES=DEST.DSCDES
		left join RFXPRODDTA.reflex.HLODPEP ODP_ENTETE
			on PREPA_DETAIL.P1NANO=ODP_ENTETE.OENANN and PREPA_DETAIL.P1NODP=ODP_ENTETE.OENODP
WHERE 	
		P1SSCA='$date[siecle]' and P1ANCA='$date[annee]' and P1MOCA='$date[mois]' and P1JOCA='$date[jour]'
	and P1TVLP=1 	--prepa validée
	and (	(P1NNSL>0 and P1RRSO='')	-- avec des manquant au lancement sans réservation
			OR 
		 	(P1QPRE<P1QAPR AND P1NNSL=0)	-- quantité préparée inférieur a quantité demandée
		)
ORDER BY CODE_ARTICLE ASC
EOT;
}


function mouvement_gei_inventaire() {
	//print_r($_POST);
	$date = extract_days_from_date_ddmmyyyy($_POST['param1']);
	$code_inventaire = $_POST['param2'];

	return <<<EOT
select
	VGNGEI NUM_GEI,VGNSUP NUM_SUPPORT,VGCART CODE_ARTICLE,
	ARTICLE.ARLART as DESIGNATION1,
	ARTICLE.ARMDAR as REF_FOURNISSEUR,
	(EMC1EM + ' ' + EMC2EM + ' '+ EMC3EM + ' ' + EMC4EM + ' ' + EMC5EM) as EMPLACEMENT,
	VGSMVG,VGQMVG as QTE_MOUVEMENT,VGQGAM QTE_AVANT,
	(select TVLTVL from RFXPRODDTA.reflex.HLARVLP,RFXPRODDTA.reflex.HLTYVLP where VLCART=MOUVEMENT_GEI.VGCART and VLCVLA=10 and VLCTVL=TVCTVL) as LIBELLE_UNITE
from
	RFXPRODDTA.reflex.HLMVTGP MOUVEMENT_GEI
	left join
 		RFXPRODDTA.reflex.HLARTIP ARTICLE
			on MOUVEMENT_GEI.VGCART=ARTICLE.ARCART
	left join	RFXPRODDTA.reflex.HLSUPPP SUPPORT
		on MOUVEMENT_GEI.VGNSUP=SUPPORT.SUNSUP
	left join  	RFXPRODDTA.reflex.HLEMPLP EMPLACEMENT
		on SUPPORT.SUNEMP=EMPLACEMENT.EMNEMP
where
VGCTVG='410' -- type inventaire
and VGRMVS='INV$date[jour]$date[mois]$date[annee]$code_inventaire' -- 'INV230714001'
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


function livraison_entre_5h_et_5h30_rubis() {
	$date = extract_days_from_date_ddmmyyyy($_POST['param1']);
	return <<<EOT
select 	TCE_K1 as CLIENT, CLIENT.NOMCL as NOM_CLIENT, TCE_K3 as NO_BON, TCE_K4 as NO_LIGNE, TCE_ART as ARTICLE, ARTICLE.DESI1 as DESIGNATION1,ARTICLE.DESI2 as DESIGNATION2,
		(TCE_DCS || TCE_DCA ||  '-' || TCE_DCM || '-' || TCE_DCJ || ' ' || substr(TCE_DCH,1,2) || 'h' || substr(TCE_DCH,3,2) || 'm' || substr(TCE_DCH,5,2)) as DATE_HEURE
from 	AFAGESTCOM.ATRACEP1 TRACE1
		left join AFAGESTCOM.AARTICP1 ARTICLE
		 	on TRACE1.TCE_ART=ARTICLE.NOART
		 left join AFAGESTCOM.ACLIENP1 CLIENT
		 	on TRACE1.TCE_K1=CLIENT.NOCLI
where
		TCE_DCH>50000 and TCE_DCH<53000
	and TCE_T1='DBO' and TCE_T2='VTE' and TCE_USR='AFBP' and TCE_TPH='LVC'
	and TCE_DCS='$date[siecle]' and TCE_DCA='$date[annee]' and TCE_DCM='$date[mois]' and TCE_DCJ='$date[jour]'
EOT;
}


function livraison_en_double_rubis() {
	$date = extract_year_from_date($_POST['param1']);
	return <<<EOT
select 	 count(*) as NB_LIV, TCE_K3 as NUM_CDE,TCE_K1 as CODE_CLIENT, CLIENT.NOMCL as NOM_CLIENT,TCE_K4 as NUM_LIGNE, TCE_ART as CODE_ARTICLE,ARTICLE.DESI1 as DESIGNATION1,ARTICLE.DESI2 as DESIGNATION2,

(
	select 		max(TCE_DCS || TCE_DCA ||  '-' || TCE_DCM || '-' || TCE_DCJ || ' ' || substr(TCE_DCH,1,2) || 'h' || substr(TCE_DCH,3,2) || 'm' || substr(TCE_DCH,5,2))
	from 		AFAGESTCOM.ATRACEP1 TRACE2
	where  		TCE_T1='DBO' and TCE_T2='VTE' and TCE_USR='AFBP' and TCE_TPH='LVC'
			and TRACE1.TCE_K3=TRACE2.TCE_K3 and TRACE1.TCE_K1=TRACE2.TCE_K1 and TRACE1.TCE_K4=TRACE2.TCE_K4
) as DERNIERE_LIV

from 	 AFAGESTCOM.ATRACEP1 TRACE1
		 left join AFAGESTCOM.AARTICP1 ARTICLE
		 	on TRACE1.TCE_ART=ARTICLE.NOART
		 left join AFAGESTCOM.ACLIENP1 CLIENT
		 	on TRACE1.TCE_K1=CLIENT.NOCLI
where
		 TCE_T1='DBO' and TCE_T2='VTE' and TCE_USR='AFBP' and TCE_TPH='LVC'
	and  TCE_DCS='$date[siecle]' and TCE_DCA='$date[annee]'
group by TCE_K3,TCE_K1,TCE_K4, TCE_ART, DESI1, DESI2, NOMCL
having 	 count(*)>1
order by DERNIERE_LIV
EOT;
//--TCE_K3='Z4066V' and TCE_K1='056096' and TCE_K4='013'
}

///////////////////////////////////////////////////////////////////////////////////////////////////////////
function extract_days_from_date_ddmmyyyy($var) {
	if (strlen($var) > 0 && preg_match('|^\d{2}/\d{2}/\d{4}$|',$var)) {
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


function extract_year_from_date($var) {
	if (strlen($var) > 0 && preg_match('|^\d{4}$|',$var)) {
		$siecle = substr($var,0,2);
		$annee = substr($var,2,2);
		return array('siecle'=>$siecle, 'annee'=>$annee);
	} else {
		die("Format d'annee non reconnu");
	}
}

?>