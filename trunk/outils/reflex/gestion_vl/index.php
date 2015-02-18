<? include('../../../inc/config.php');

$message = '';

// enregistre les modifications
if (	isset($_POST['action']) 		&& $_POST['action']=='validation_vl'
	&&	isset($_POST['code_article']) 	&& strlen($_POST['code_article'])>0
	&&	isset($_POST['depot']) 			&& strlen($_POST['depot'])>0
	&&	isset($_POST['fournisseur']) 	&& strlen($_POST['fournisseur'])>0) {

	//print_r($_POST);
	$_POST_ESCAPE = array_map('mysql_escape_string', $_POST);

	foreach ($_POST_ESCAPE as $key => $val) // on ne laisse pas de valeur vide
		if (strlen($val)<=0)
			$_POST_ESCAPE[$key] = 0;

	$loginor= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
	$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

	$now = date('Ymd');
	$siecle = substr($now,0,2);
	$annee 	= substr($now,2,2);
	$mois 	= substr($now,4,2);
	$jour 	= substr($now,6,2);
	$ip 	= 'AF'.join('.',array_slice(explode('.',$_SERVER['REMOTE_ADDR']), 2)); // deux dernier chiffre de l'ip
	$user 	= e('login',mysql_fetch_array(mysql_query("SELECT UCASE(loginor) AS login FROM employe WHERE ip='".mysql_escape_string($_SERVER['REMOTE_ADDR'])."'")));

	// fiche de stock
	$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 set ".
				"STMID='$user',".
				"STMWS='$ip',".
				"STMSS='$siecle',".
				"STMAA='$annee',".
				"STMMM='$mois',".
				"STMJJ='$jour',".

				"STSER='$_POST_ESCAPE[STSER]'".
			" where NOART='$_POST_ESCAPE[code_article]' and DEPOT='$_POST_ESCAPE[depot]'";
	$res 	= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : <br/>$sql");

	// fiche achat fournisseur
	$sql = 	"update ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 set ".
				"USAFE='$user',".
				"WSAFR='$ip',".
				"DAFMS='$siecle',".
				"DAFMA='$annee',".
				"DAFMM='$mois',".
				"DAFMJ='$jour',".

				"AFOG3='$_POST_ESCAPE[AFOG3]',".
				"AFPCB='$_POST_ESCAPE[AFPCB]',".
				"ARF01='$_POST_ESCAPE[ARF01]',".
				"AFPPD='$_POST_ESCAPE[AFPPD]'".
			" where NOART='$_POST_ESCAPE[code_article]' and NOFOU='$_POST_ESCAPE[fournisseur]'";
	$res 	= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : <br/>$sql");

	// fiche article
	$sql = "update ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 set ".
				"USARE='$user',".
				"WSARR='$ip',".
				"DARMS='$siecle',".
				"DARMA='$annee',".
				"DARMM='$mois',".
				"DARMJ='$jour',".

				"POIDN='$_POST_ESCAPE[POIDN]',".
				"LONGA='$_POST_ESCAPE[LONGA]',".
				"LARGA='$_POST_ESCAPE[LARGA]',".
				"HAUTA='$_POST_ESCAPE[HAUTA]',".
				"ART30='$_POST_ESCAPE[ART30]',".
				"ART31='$_POST_ESCAPE[ART31]',".
				"ART33='$_POST_ESCAPE[ART33]',".
				"ART34='$_POST_ESCAPE[ART34]',".
				"TAUAR='$_POST_ESCAPE[TAUAR]',".
					"LUACH='$_POST_ESCAPE[TAUAR]',".
					"LUNTA='$_POST_ESCAPE[TAUAR]',".
					"LUCDE='$_POST_ESCAPE[TAUAR]',".
					"ULPRE='$_POST_ESCAPE[TAUAR]',".
					"LUSTA='$_POST_ESCAPE[TAUAR]',".
				"CDCON='$_POST_ESCAPE[CDCON]',".
				"DIAA4='$_POST_ESCAPE[DIAA4]',".
				"CONDI='$_POST_ESCAPE[CONDI]',".
				"ARTD4='$_POST_ESCAPE[ARTD4]',".
				"SURCO='$_POST_ESCAPE[SURCO]',".
				"ARTD5='$_POST_ESCAPE[ARTD5]'".
			"where NOART='$_POST_ESCAPE[code_article]'";
	$res 	= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : <br/>$sql");

	odbc_close($loginor);

	// si l'ancien conditionnement de vente etait différent du nouveau --> inventaire
	if ($_POST['CDCON'] != $_POST['old_CDCON'] || $_POST['AFPCB'] != $_POST['old_AFPCB']) {
		require_once '../inc/xpm2/smtp.php';
		$mail = new SMTP;
		$mail->Delivery('relay');
		$mail->Relay(SMTP_SERVEUR,SMTP_USER,SMTP_PASS,(int)SMTP_PORT,'autodetect',SMTP_TLS_SLL ? SMTP_TLS_SLL:false);
		$mail->AddTo('francois.dore@coopmcs.com', "Francois Dore") or die("Erreur d'ajour de destinataire");
		$mail->From('reflex@coopmcs.com');
		$mail->Html("Suite à un changement de conditionnement, veuillez inventorier l'article $_POST_ESCAPE[code_article] dans le dépot $_POST_ESCAPE[depot]");
		$sent = $mail->Send("Inventaire de $_POST_ESCAPE[code_article]");
	}

	$previous_directory = getcwd();
	chdir('c:/easyphp/www/intranet/scripts/Interfaces Rubis-Reflex') or die("Impossible de changer de répertoire de travail");
	exec('perl export-article-to-reflex.pl --article='.$_POST['code_article']); // envoi la demande de creation a reflex
	chdir($previous_directory) or die("Impossible de revenir au répertoire de travail");

	$message .= "Article modifié";
}


?>
<html>
<head>
<meta http-equiv="Content-Type" content="text/html; charset=iso-8859-1"/> 
<title>Gestion des VL article</title>

<style>

body {
    font-family: verdana;
    font-size: 0.8em;
}
h1 {
    font-size: 1.2em;
    margin-top:2em;
}
.message {
    color: red;
    font-weight: bold;
    text-align: center;
}
th span {
    font-weight: normal;
}
option {
    font-family: courier new;
}
#recherche {
    border: 1px solid grey;
    margin: auto;
    padding: 20px;
    width: 50%;
}
#lignes {
    border: 1px solid black;
    border-collapse: collapse;
    margin: auto;
    width: 90%;
}
#lignes th, #lignes td {
    font-size: 0.9em;
}
caption {
    background-color: #ddd;
    padding: 2px;
}
.legende {
    font-size: 0.7em;
    margin: 2em auto auto;
    width: 55%;
}
td.libelle {
    text-align: right;
}
input[type=text] {
    width: 5em;
}
input.unite {
    width: 3em;
}
td.valeur {
    text-align: left;
    padding-left: 1em;
}
td.valeur select, td.valeur input[type="text"] {
  /*  margin-left: 1em;*/
}
input[name="AFOG3"] {
    width: 8em;
}
.fiche_stock {
    background-color: pink;
}
.fiche_article {
    background-color: lightgreen;
}
.fiche_complement {
    background-color: lightblue;
}
.fiche_fournisseur {
    background-color: yellow;
}
.conditionnement {
    border: 1px solid black;
    margin: 1em auto auto;
    padding: 1em;
    width: 88%;
}
.moins-visible {
	color:#999;
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
		alert("Veuillez préciser un code article");
		erreur = true;
	}

	if (!erreur)
		form.submit();
}


function max(val1,val2) {
	return (val1 > val2 ? val1 : val2);
}

function update_data(obj) {
	var objName = $(obj).attr('name') ;
	var newVal	= $(obj).val();

	if (objName == 'fournisseur') {
		verif_form();
		return;
	}

	if (objName == 'CONDI') { // conditionnement de vente
		$('#SURCO').val( newVal * $('#SUR_CONDITIONNEMENT_VENTE').text() );

	} else if (objName == 'ARTD4') { // unite de conditionnement de vente
		$('#UNITE_CONDITIONNEMENT').text( newVal );		

	} else if (objName == 'SURCO') { // sur conditionnement de vente
		$('#SUR_CONDITIONNEMENT_VENTE').text( newVal / $('#CONDI').val() );

	} else if (objName == 'ARTD5') { // unite de sur conditionnement de vente

	} else if (objName == 'ARF01' || objName == 'DIAA4') {
		if (newVal == 1) {
			$('label[for='+objName+'-1]').removeClass('moins-visible');
			$('label[for='+objName+'-2]').addClass('moins-visible');
		} else {
			$('label[for='+objName+'-1]').addClass('moins-visible');
			$('label[for='+objName+'-2]').removeClass('moins-visible');
		}
	}

	update_unite_logistique(objName,newVal);
}

function update_unite_logistique(objName,newVal) {
	var unite_logistique = '';
	//console.log("$('select[name=CDCON] option:selected').val()="+$('select[name=CDCON] option:selected').val());
		
	if ($('select[name=CDCON] option:selected').val()=='OUI') { // divisible --> unité de facturation
		unite_logistique = $('select[name=TAUAR] option:selected').val();
		$('#AFPCB').val('1').attr('disabled','disabled');
		$('input[name=DIAA4]').hide(0);

	} else {  // non divisible
		$('#AFPCB').removeAttr('disabled');
		$('input[name=DIAA4]').show(0);

		// on copie la saisie de condi dans les deux cases
		if (objName == 'CONDI' || objName == 'AFPCB')
			$('#CONDI, #AFPCB').val(newVal);

		if ($('input[name=DIAA4]:checked').val() == 1) {
			unite_logistique = $('#ARTD4').val();
		} else {
			unite_logistique = $('#ARTD5').val();
		}
	}

	$('.unite_logistique').text(unite_logistique);
}


//-->
</script>

</head>
<body>
<a class="btn" href="../index.php"><i class="icon-arrow-left"></i> Revenir aux outils Reflex</a>

<div class="message"><?=$message?></div>

<form name="cde" method="POST" action="<?=$_SERVER['PHP_SELF']?>">
<input type="hidden" name="action" value="gestion_vl" />
<div id="recherche">
	<h1>Gestion des VL article</h1>
	Code article
	<input type="text" id="code_article" name="code_article" value="<?= isset($_POST['code_article'])?$_POST['code_article']:'' ?>" placeholder="code article" size="10" maxlength="15"/>
	<select name="depot">
		<option value="AFA" selected="selected">AFA - Plescop</option>
		<option value="AFL">AFL - Caudan</option>
		<option value="AFB">AFB - Esp. Adh&eacute;rent</option>
	</select>
	<a class="btn btn-success" onclick="verif_form();"><i class="icon-ok"></i> Gestion des VL</a>
</div>


<?
// on met a jour les état envoyée a reflex dans Rubis
if (	isset($_POST['action']) && $_POST['action'] == 'gestion_vl'
	&&	isset($_POST['code_article']) && $_POST['code_article']
	&&	isset($_POST['depot']) && $_POST['depot']
	) {

		$_POST_ESCAPE = array_map('mysql_escape_string', $_POST);

		$condition_fournisseur = '';
		if (isset($_POST['fournisseur']) && strlen($_POST['fournisseur'])>0)
			$condition_fournisseur = "ARTICLE_FOURNISSEUR.NOFOU='$_POST_ESCAPE[fournisseur]'";
		else
			$condition_fournisseur = "ARTICLE_FOURNISSEUR.NOFOU=ARTICLE.FOUR1";

		$sql = <<<EOT
select
	ARTICLE.ETARE as SUSPENDU,
	ARTICLE.USARE as LAST_USER,
	(ARTICLE.DARCJ || '/' || ARTICLE.DARCM || '/' || ARTICLE.DARCS || ARTICLE.DARCA) as CREATION_DATE,
	(ARTICLE.DARMJ || '/' || ARTICLE.DARMM || '/' || ARTICLE.DARMS || ARTICLE.DARMA) as LAST_MODIFICATION,
	ARTICLE.DESI1 as DESIGNATION1,
	ARTICLE.DESI2 as DESIGNATION2,
	ARTICLE.FOUR1 as FOURNISSEUR_HABITUEL,
	ARTICLE.ART30 as TYPE_SUPPORT,
	ARTICLE.ART31 as TAILLE_EMPLACEMENT,
	ARTICLE.ART33 as FAMILLE_STOCKAGE,
	ARTICLE.ART34 as FAMILLE_PREPA,
	ARTICLE.POIDN as POIDS_NET,
	ARTICLE.LONGA as LONGEUR,
	ARTICLE.LARGA as LARGEUR,
	ARTICLE.HAUTA as HAUTEUR,
	ARTICLE.ARTD4 as UNITE_CONDITIONNEMENT,
	ARTICLE.ARTD5 as UNITE_SUR_CONDITIONNEMENT,
	ARTICLE.CONDI as CONDITIONNEMENT_VENTE,
	ARTICLE.SURCO as SUR_CONDITIONNEMENT_VENTE,
	ARTICLE.TAUAR as TABLE_UNITE,
	ARTICLE.CDCON as CONDITIONNEMENT_DIVISIBLE,
	ARTICLE.DIAA4 as CHOIX_CONDITIONNEMENT_VENTE,

	ARTICLE_FOURNISSEUR.REFFO as REFERENCE_FOURNISSEUR,
	ARTICLE_FOURNISSEUR.AFOG3 as EAN13,
	ARTICLE_FOURNISSEUR.AFPCB as CONDITIONNEMENT_ACHAT,
	ARTICLE_FOURNISSEUR.AFPPD as SUR_CONDITIONNEMENT_ACHAT,
	ARTICLE_FOURNISSEUR.ARF01 as CHOIX_CONDITIONNEMENT_ACHAT,

	FICHE_STOCK.STCLA as CLASS,
	FICHE_STOCK.STSER as SERVI,
	FICHE_STOCK.STOMI as MINI,
	FICHE_STOCK.STALE as ALERTE,
	FICHE_STOCK.STOMA as MAXI,

	PRIX_REVIENT.PRVT2 as PR2
from
				${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
	left join 	${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 FICHE_STOCK
		on 			ARTICLE.NOART=FICHE_STOCK.NOART and FICHE_STOCK.DEPOT='$_POST_ESCAPE[depot]'
	left join 	${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
		on 			ARTICLE.NOART=ARTICLE_FOURNISSEUR.NOART and $condition_fournisseur
	left join 	${LOGINOR_PREFIX_BASE}GESTCOM.ATARPAP1 PRIX_REVIENT
		on 			ARTICLE.NOART=PRIX_REVIENT.NOART
where
		ARTICLE.NOART='$_POST_ESCAPE[code_article]'
	and PRIX_REVIENT.PRV03='E'	-- tarif de revient en cours
EOT;

	/*
	print_r($_POST);
	printf("<xmp>%s</xmp>",$sql);
	*/

	$reflex = odbc_connect(REFLEX_DSN,REFLEX_USER,REFLEX_PASS) or die("Impossible de se connecter à Reflex via ODBC ($REFLEX_DSN)");
	$loginor= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res 	= odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete : <br/>$sql");
	$row	= odbc_fetch_array($res);
	$row	= array_map('trim',$row);

	// default value
	if ($row['UNITE_CONDITIONNEMENT'] == '') 		$row['UNITE_CONDITIONNEMENT'] == 'UN';
	if ($row['UNITE_SUR_CONDITIONNEMENT'] == '') 	$row['UNITE_SUR_CONDITIONNEMENT'] == 'PAL';
	if ($row['TABLE_UNITE'] == '') 					$row['TABLE_UNITE'] == 'UN';

?>
<table id="lignes">
<caption>
	Article <strong><?=$_POST['code_article']?></strong> (d&eacute;pôt <?=$_POST['depot']?>)<br/>
	<em><?=$row['DESIGNATION1']?><br/>
		<?=$row['DESIGNATION2']?></em>
	<br/>

	<span class="fiche_fournisseur">
	Fiche fournisseur :
	<?=table2select(array(	'name'		=> 'fournisseur',
							'table'		=> 'AARFOUP1',
							'key'		=> 'NOFOU',
							'label'		=> 'REFFO',
							'selected' 	=> isset($_POST['fournisseur']) && strlen($_POST['fournisseur'])>0 ? $_POST['fournisseur'] : $row['FOURNISSEUR_HABITUEL'],
							'where'		=> "NOART='$_POST_ESCAPE[code_article]'"
	))?>
	</span>

	<!--<strong><?=$row['FOURNISSEUR_HABITUEL']?> <?=$row['REFERENCE_FOURNISSEUR']?></strong>-->
	<!--<input type="hidden" name="fournisseur" value="<?=$row['FOURNISSEUR_HABITUEL']?>" />-->
	<br/>
	<span class="fiche_stock">Servi sur stock :
		<select name="STSER">
			<option value="OUI"<?= $row['SERVI']=='OUI' ? ' selected="selected"':'' ?>>OUI</option>
			<option value="NON"<?= $row['SERVI']=='NON' ? ' selected="selected"':'' ?>>NON</option>
		</select>
	&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Classe : <?=$row['CLASS']?></span><br/>
	Date de cr&eacute;ation : <?=$row['CREATION_DATE']?>&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Dernière modification : <?=$row['LAST_MODIFICATION']?> par <?=$row['LAST_USER']?><br/>
</caption>

<tbody>
	<tr><th colspan="2">Information VL10&nbsp;&nbsp;&nbsp;&nbsp;<span class="fiche_stock">Fiche stock</span>&nbsp;&nbsp;&nbsp;<span class="fiche_fournisseur">Fiche fournisseur</span>&nbsp;&nbsp;&nbsp;<span class="fiche_article">Fiche article</span>&nbsp;&nbsp;&nbsp;<span class="fiche_complement">Complément</span></th></tr>
	<tr>
		<td class="libelle fiche_stock">Stock mini</td>
		<td class="valeur fiche_stock"><?=(int)$row['MINI']?></td></tr>
	<tr>
		<tr>
		<td class="libelle fiche_stock">Stock alerte</td>
		<td class="valeur fiche_stock"><?=(int)$row['ALERTE']?></td></tr>
	<tr>
		<tr>
		<td class="libelle fiche_stock">Stock maxi</td>
		<td class="valeur fiche_stock"><?=(int)$row['MAXI']?></td></tr>
	<tr>
	<tr>
		<td class="libelle fiche_fournisseur">EAN13</td>
		<td class="valeur fiche_fournisseur"><input type="text" name="AFOG3" maxlength="13" value="<?=$row['EAN13']?>"/></td></tr>
	<tr>
		<td class="libelle fiche_article">Poids Net (kg)</td>
		<td class="valeur fiche_article"><input type="text" name="POIDN" value="<?=$row['POIDS_NET']?>"/></td></tr>
	<tr>
		<td class="libelle fiche_article">Longeur (mm)</td>
		<td class="valeur fiche_article"><input type="text" name="LONGA" value="<?=(int)$row['LONGEUR']?>"/></td></tr>
	<tr>
		<td class="libelle fiche_article">Largeur (mm)</td>
		<td class="valeur fiche_article"><input type="text" name="LARGA" value="<?=(int)$row['LARGEUR']?>"/></td></tr>
	<tr>
		<td class="libelle fiche_article ">Hauteur (mm)</td>
		<td class="valeur fiche_article"><input type="text" name="HAUTA" value="<?=(int)$row['HAUTEUR']?>"/></td></tr>
	<tr>
		<td class="libelle fiche_complement">Type support</td>
		<td class="valeur fiche_complement"><?=table2select(array(	'name'		=> 'ART30',
																	'table'		=> 'HLTYSUP',
																	'key'		=> 'TUCTSU',
																	'label'		=> 'TULTSU',
																	'selected' 	=> $row['TYPE_SUPPORT']
											))?>
		</td>
	<tr>
		<td class="libelle fiche_complement">Taille emplacement</td>
		<td class="valeur fiche_complement"><?=table2select(array(	'name'		=> 'ART31',
																	'table'		=> 'HLTAILP',
																	'key'		=> 'TACTAI',
																	'label'		=> 'TALTAI',
																	'selected' 	=> $row['TAILLE_EMPLACEMENT']
											))?>
		</td>
	</tr>
	<tr>
		<td class="libelle fiche_complement">Famille de stockage</td>
		<td class="valeur fiche_complement"><?=table2select(array(	'name'		=> 'ART33',
																	'table'		=> 'HLFASTP',
																	'key'		=> 'FSCFAS',
																	'label'		=> 'FSLFAS',
																	'selected' 	=> ($row['FAMILLE_STOCKAGE']=='DEP'?'DEF':$row['FAMILLE_STOCKAGE'])
											))?>
		</td>
	<tr>
		<td class="libelle fiche_complement">Famille de prépa</td>
		<td class="valeur fiche_complement"><?=table2select(array(	'name'		=> 'ART34',
																	'table'		=> 'HLFAPRP',
																	'key'		=> 'FPCFPR',
																	'label'		=> 'FPLFPR',
																	'selected' 	=> $row['FAMILLE_PREPA']
											))?>
		</td>
	</tr>
</tbody>
</table>

<div class="conditionnement non-divisible">
<h1 style="margin-top:0;">Unité de facturation : 
	<?=table2select(array(	'name'		=> 'TAUAR',
							'table'		=> 'ATABLEP1',
							'key'		=> 'CODPR',
							'label'		=> 'LIBPR',
							'selected' 	=> trim($row['TABLE_UNITE']),
							'where'		=> "TYPPR='UNP'"
	))?>
	<span class="pr2" style="font-weight:normal;">(prix d'achat PR2=<?=$row['PR2']?>&euro;)</span>
</h1>

<h1>Achat</h1>
<div class="fiche_fournisseur">
	<input type="radio" id="ARF01-1" name="ARF01" value="1"<?=$row['CHOIX_CONDITIONNEMENT_ACHAT']==1 ? ' checked="checked"':''?>  onclick="update_data(this);"/>
	<label for="ARF01-1"<?=$row['CHOIX_CONDITIONNEMENT_ACHAT']==1 ? '':' class="moins-visible"'?>>Conditionnement d'achat</label>
	<input type="text" id="AFPCB" name="AFPCB" value="<?=$row['CONDITIONNEMENT_ACHAT']?>"<?=$row['CONDITIONNEMENT_DIVISIBLE']=='OUI'?' disabled="disabled"':''?> onkeyup="update_data(this);"/> <?=$row['TABLE_UNITE']?>
	<br/>

	<input type="radio" id="ARF01-2" name="ARF01" value="2"<?=$row['CHOIX_CONDITIONNEMENT_ACHAT']==2 ? ' checked="checked"':''?> onclick="update_data(this);"/>
	<label for="ARF01-2"<?=$row['CHOIX_CONDITIONNEMENT_ACHAT']==2 ? '':' class="moins-visible"'?>>Sur conditionnement d'achat</label>
	<input type="text" id="AFPPD" name="AFPPD" value="<?=$row['SUR_CONDITIONNEMENT_ACHAT']?>"/> <?=$row['TABLE_UNITE']?>
	
</div>

<h1>Vente</h1>
<div class="fiche_article">
	<input type="hidden" name="old_CDCON" value="<?=$row['CONDITIONNEMENT_DIVISIBLE']?>"/>
	<input type="hidden" name="old_AFPCB" value="<?=$row['CONDITIONNEMENT_ACHAT']?>"/>

	<select name="CDCON" id="CDCON" onchange="update_data(this);">
		<option value="OUI"<?= $row['CONDITIONNEMENT_DIVISIBLE']=='OUI' ? ' selected="selected"':'' ?>>Article divisible à la vente</option>
		<option value="NON"<?= $row['CONDITIONNEMENT_DIVISIBLE']=='NON' ? ' selected="selected"':'' ?>>Article non divisible à la vente</option>
	</select><br/>

	<input type="radio" id="DIAA4-1" name="DIAA4" value="1"<?=$row['CHOIX_CONDITIONNEMENT_VENTE']==1 ? ' checked="checked"':''?> onclick="update_data(this);"/>
	<input type="hidden" name="DIAA4" value="1"/>
	<label for="DIAA4-1"<?=$row['CHOIX_CONDITIONNEMENT_VENTE']==1 ? '':' class="moins-visible"'?>>Conditionnement de vente</label>
	<input type="text" id="CONDI" name="CONDI" value="<?=$row['CONDITIONNEMENT_VENTE']?>" onkeyup="update_data(this);"/> <?=$row['TABLE_UNITE']?>
	= <input type="text" id="ARTD4" name="ARTD4" value="<?=$row['UNITE_CONDITIONNEMENT']?>" class="unite" onkeyup="update_data(this);"/>
	(BTE, TOU, COU, SAC, ...)
	<br/>
	

	<input type="radio" id="DIAA4-2" name="DIAA4" value="2"<?=$row['CHOIX_CONDITIONNEMENT_VENTE']==2 ? ' checked="checked"':''?> onclick="update_data(this);"/>
	<label for="DIAA4-2"<?=$row['CHOIX_CONDITIONNEMENT_VENTE']==2 ? '':' class="moins-visible"'?>>Sur conditionnement de vente</label>
	<input type="text" id="SURCO" name="SURCO" value="<?= $row['CONDITIONNEMENT_VENTE'] * $row['SUR_CONDITIONNEMENT_VENTE']?>" onkeyup="update_data(this);"/> <?=$row['TABLE_UNITE']?>
	= <span id="SUR_CONDITIONNEMENT_VENTE"><?=$row['SUR_CONDITIONNEMENT_VENTE']?></span> <span id="UNITE_CONDITIONNEMENT"><?=$row['UNITE_CONDITIONNEMENT']?></span>
	= <input type="text" id="ARTD5" name="ARTD5" value="<?=$row['UNITE_SUR_CONDITIONNEMENT']?>" class="unite" onkeyup="update_data(this);"/>
	(PAL, PQ3, ...)
</div>



<h1>Logistique</h1>
<div class="fiche_reflex">
<?	$unite_logistique = '';	
	if ($row['CONDITIONNEMENT_DIVISIBLE']=='OUI') { // divisible --> unité de facturation
		$unite_logistique = $row['TABLE_UNITE'];
	} else {  // non divisible
		if ($row['CHOIX_CONDITIONNEMENT_VENTE'] == 1) {
			$unite_logistique = $row['UNITE_CONDITIONNEMENT'];
		} else {
			$unite_logistique = $row['UNITE_SUR_CONDITIONNEMENT'];
		}
	}
?>
	Cet article se réceptionne en <span class="unite_logistique"><?=$unite_logistique?></span><br/>
	Cet article se prépare en <span class="unite_logistique"><?=$unite_logistique?></span>
</div>
</div>

<div style="width:50%;margin:auto;margin-top:1em;">
	<a class="btn btn-success" onclick="
				$('input').removeAttr('disabled');
				$('input[name=action]').val('validation_vl');
				document.cde.submit();
				"><i class="icon-ok"></i> Enregistrer les modifications</a>
	<a class="btn btn-warning" onclick="document.cde.reset();">Reset</a>
</div>
<? } ?>
</form>
</body>
</html>
<?

function getTableReflex($params) {
	global $reflex,$REFLEX_BASE,$loginor,$LOGINOR_PREFIX_BASE;
	//params = {table, where}

	$prefix_base= $REFLEX_BASE;
	$dsn_source = $reflex;

	if (substr($params['table'],0,1) == 'A') { // cas d'une table rubis
		$prefix_base= $LOGINOR_PREFIX_BASE.'GESTCOM';
		$dsn_source = $loginor;
	}

	$sql = "SELECT * FROM $prefix_base.$params[table]";
	if (isset($params['where']) && $params['where'])
		$sql .= " WHERE $params[where]";

	$res = odbc_exec($dsn_source,$sql)  or die("Impossible de lancer la requete : <br/>$sql");
	$rows = array();
	while ($row = odbc_fetch_array($res))
		array_push($rows,$row);
	return $rows;
}


function table2select($params) {
	//params = {name, rows (array), key, label, selected}
	echo '<select name="'.$params['name'].'" id="'.$params['name'].'" onchange="update_data(this);">';
	foreach (getTableReflex(array('table'=>$params['table'],'where'=>$params['where'])) as $r) {
		echo 	'<option value="'.$r[$params['key']].
				'"'.(isset($params['selected']) && trim($r[$params['key']]) == trim($params['selected']) ? ' selected="selected"':'').'>'.
				$r[$params['key']].str_repeat(' ',3 - strlen($r[$params['key']])).' - '.trim($r[$params['label']])."</option>\n";
	}
	echo "</select>\n";
}
?>