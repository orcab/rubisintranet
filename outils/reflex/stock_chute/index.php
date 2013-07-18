<? include('../../../inc/config.php'); ?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Stock des produits</title>

<style>
body {
	font-family: verdana;
	font-size: 0.8em;
}

h1 {
    font-size: 1.2em;
}

.message {
    color: red;
    font-weight: bold;
    text-align: center;
}

#recherche {
	margin:auto;
	border:solid 1px grey;
	padding:20px;
	width:50%;
}

#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    margin-top: 1em;
    width:55%;
    margin:auto;
}
#lignes th, #lignes td {
    border: 1px solid #CCCCCC;
    font-size: 0.9em;
    text-align: center;
}
tr.annule {
    color: #CCCCCC;
    text-decoration: line-through;
}
caption {
    background-color: #DDD;
    padding: 2px;
}

.qualite-afz {
	background-color:#FAA
}

.qualite-afz .qualite {
	color:#F00;
}

.support .significient {
	font-size:1.2em;
}

#legende {
    margin: auto;
    margin-top: 2em;
    font-size: 0.7em;
    width: 55%;
}

</style>
<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../../js/fontawesome/css/icon-custom.css">

<script type="text/javascript" src="../../../js/jquery.js"></script>
<script language="javascript">
<!--

$(document).ready(function(){
	$('#code_article').focus();
});


function verif_form(){
	var form = document.cde;
	//var value_type_cde = form.type_cde[form.type_cde.selectedIndex].value;
	var erreur = false;

	if (!form.code_article.value) {
		alert("Veuillez préciser un n° de commande");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}

//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<form name="cde" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="action" value="stock_chute" />
<div id="recherche">
	<h1>Voir les stock des produits</h1>
	Code article
	<input type="text" id="code_article" name="code_article" value="" placeholder="code article" size="10" maxlength="15"/>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Voir les stocks</a>
</div>
</form>


<?
// on met a jour les état envoyée a reflex dans Rubis
if (	isset($_POST['action']) && $_POST['action'] == 'stock_chute'
	&&	isset($_POST['code_article']) && $_POST['code_article']
	) {	
		$sql = <<<EOT
select
GEI.GECART as CODE_ARTICLE,
ARTICLE.ARLART as DESIGNATION1,
ARTICLE.ARMDAR as REF_FOURNISSEUR,
GEI.GEQGEI as QTE_REFLEX,
(select VLCTVL from ${REFLEX_BASE}.HLARVLP where VLCART=GEI.GECART and VLCVLA=10) as CODE_UNITE,
(select TVLTVL from ${REFLEX_BASE}.HLARVLP,${REFLEX_BASE}.HLTYVLP where VLCART=GEI.GECART and VLCVLA=10 and VLCTVL=TVCTVL) as LIBELLE_UNITE,
(EMC1EM + ' ' + EMC2EM + ' '+ EMC3EM + ' ' + EMC4EM + ' ' + EMC5EM) as EMPLACEMENT,
SUPPORT.SUNSUP as NUMERO_SUPPORT,
VL.VLCFPR as FAMILLE_PREPARATION,
QUALITE.QACQAL as CODE_QUALITE,
QUALITE.QALQAL as LIBELLE_QUALITE,
ETAT_SUPPORT.ESL3ES as CODE_ETAT_SUPPORT,
ETAT_SUPPORT.ESLESU as LIBELLE_ETAT_SUPPORT,
(CAST(GEJREG as VARCHAR(2))+ '/' + CAST(GEMREG as VARCHAR(2)) + '/' + CAST(GESREG as VARCHAR(2)) + CAST(GEAREG as VARCHAR(2))) as DATE_RECEPTION
	from		${REFLEX_BASE}.HLGEINP GEI
	left join	${REFLEX_BASE}.HLSUPPP SUPPORT
		on GEI.GENSUP=SUPPORT.SUNSUP
	left join  	${REFLEX_BASE}.HLEMPLP EMPLACEMENT
		on SUPPORT.SUNEMP=EMPLACEMENT.EMNEMP
	left join  	${REFLEX_BASE}.HLARVLP VL
		on GEI.GECART=VL.VLCART
	left join  	${REFLEX_BASE}.HLARTIP ARTICLE
		on GEI.GECART=ARTICLE.ARCART
	left join  	${REFLEX_BASE}.HLETSUP ETAT_SUPPORT
		on SUPPORT.SUCESU=ETAT_SUPPORT.ESCESU
	left join  	${REFLEX_BASE}.HLQUALP QUALITE
		on GEI.GECQAL=QUALITE.QACQAL
where
		GEI.GECTST='200'		-- obligatoire pour le stock réel
	and GEI.GECART='$_POST[code_article]'			-- code article
	and VL.VLCVLA=30			-- VL 10
--	and VL.VLCFPR='DEC'			-- produit de la famille découpe
order by CODE_QUALITE ASC, EMPLACEMENT ASC
EOT;
	$reflex  = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
	$res = odbc_exec($reflex,$sql)  or die("Impossible de lancer la modification de ligne : <br/>$sql");
	$i=0;
	while($row = odbc_fetch_array($res)) {
		if ($i<=0) { // entete du tableau ?>
			<table id="lignes">
				<caption>
					Stock pour l'article <strong><?=$row['CODE_ARTICLE']?></strong><br/>
					<?=$row['DESIGNATION1']?> / <?=$row['REF_FOURNISSEUR']?><br/>
					Famille de prépa : <?=$row['FAMILLE_PREPARATION']?>
				</caption>
				<thead>
				<tr>
					<th>Qte</th><th>Unité*</th><th>Emplacement</th><th>Etat*</th><th>Support</th><th>Qualité*</th><th>Date recep</th>
				</tr>
				</thead>
				<tbody>
<?		} ?>
		
				<tr class="qualite-<?=$row['CODE_QUALITE']?>">
					<td class="qte"><?=$row['QTE_REFLEX']?></td>
					<td class="unite" title="<?=htmlentities($row['LIBELLE_UNITE'])?>"><?=$row['CODE_UNITE']?></td>
					<td class="emplacement"><?=$row['EMPLACEMENT']?></td>
					<td class="etat" title="<?=htmlentities($row['LIBELLE_ETAT_SUPPORT'])?>"><?=$row['CODE_ETAT_SUPPORT']?></td>
					<td class="support"><?=substr($row['NUMERO_SUPPORT'],0,11)?><span class="significient"><?=substr($row['NUMERO_SUPPORT'],11,strlen($row['NUMERO_SUPPORT'])-11)?></span></td>
					<td class="qualite" title="<?=htmlentities($row['LIBELLE_QUALITE'])?>"><?=$row['CODE_QUALITE']?></td>
					<td class="date_reception"><?=$row['DATE_RECEPTION']?></td>
				</tr>
<?		$i++;
	} 
	odbc_close($reflex);
?>
				</tobdy>
			</table>

			<div id="legende">* = En laissant la souris sur la valeur vous obtenez la signification du code</div>
<? } ?>

</body>
</html>