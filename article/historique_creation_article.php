<?php

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

function nettoi_caractere_interdit($str) {
	$str = str_replace('"',"''",$str);
	$str = str_replace(';',',',$str);
	return $str;
}


if (isset($_GET['action']) && $_GET['action']=='export2rubis') {

	$code_tva = array(
		'00A'=>'001',
		'00B'=>'002',
		'00D'=>'003',
	);

	$date['siecle'] = substr(date('Y'),0,2);
	$date['annee']	= substr(date('Y'),2,2);
	$date['mois']	= date('m');
	$date['jour']	= date('d');

	// créer un fichier au format AFAART des article non créer pour l'intégrer dans rubis
	$res = mysql_query("SELECT * FROM historique_article WHERE status<>1") or die(mysql_error());
	header('Content-type: text/csv');
	header('Content-disposition: attachment;filename=AFAART.CSV');
	echo "ACBPRO;ACBEMM;ACBFOH;ACBAGE;ACBSFO;ACBRFF;ACBGE2;ACBART;ACBDE1;ACBDE2;ACBDPT;ACBFAM;ACBSFA;ACBCHP;ACBSCH;ACBEDT;ACBSTO;ACBSER;ACBTVA;ACBCL7;ACBCL6;ACBTAU;ACBTPA;ACBRE1;ACBRE2;ACBRE3;ACBGEN;ACBA11;ACBCF1;ACBA12;ACBCF2;ACBA13;ACBCF3;ACBA14;ACBCF4;ACBA15;ACBCF5;ACBA16;ACBCF6;ACBSAP;ACBAAP;ACBMAP;ACBJAP;ACBSPR;ACBAPR;ACBMPR;ACBJPR;ACBTPF;ACBCDT;ACBFCO\n"; // colonne header

	$last_mcs_codes = array();

	while ($row = mysql_fetch_array($res)) {
		$tmp = split("\n",$row['description']);
		
		$data['designation1'] = substr(nettoi_caractere_interdit(rtrim($tmp[0])),0,40);
		$data['designation2'] = substr(nettoi_caractere_interdit(rtrim($tmp[1])),0,40);
		//$data['designation3'] = substr(nettoi_caractere_interdit($tmp[2]),0,40);
		for($i=3 ; $i<sizeof($tmp) ; $i++) { // parse le champs description pour retrouvé les infos saisies
			if (preg_match("/^(.+?) : (.+)$/",rtrim($tmp[$i]),$matches))
				$data[$matches[1]] = nettoi_caractere_interdit($matches[2]);
		}

		//print_r($data);exit;

		// va chercher le dernier code crée dans l'activite pour trouve le code MCS
		if (isset($last_mcs_codes[$data['Activite']])) {	// on est deja aller chercher le dernier code créer dans la base
			$last_code_mcs = (int)$last_mcs_codes[$data['Activite']];
			$new_code_mcs = sprintf("%08d",$last_code_mcs + 1);
			$last_mcs_codes[$data['Activite']] = $new_code_mcs;
		} else {											// on va chercher le dernier code dans la base
			$res2 = mysql_query("SELECT code_article AS last_code_mcs FROM historique_article WHERE status='1' AND activite='$data[Activite]' AND LENGTH(code_article)=8 ORDER BY date_creation DESC LIMIT 0,1") or die(mysql_error());
			$row2 = mysql_fetch_array($res2);
			$last_code_mcs = (int)$row2['last_code_mcs'];
			$new_code_mcs = sprintf("%08d",$last_code_mcs + 1);
			$last_mcs_codes[$data['Activite']] = $new_code_mcs;
		}

		echo join(';',array(
			'ARTICLE',
			$data['Code fournisseur'],
			$data['Code fournisseur'],
			'AFA',
			'1',
			$data['Reference'],
			'',
			$new_code_mcs, // code mcs
			$data['designation1'],
			$data['designation2'],
			$data['Activite'],
			$data['Famille'],
			$data['Sous famille'],
			$data['Chapitre'],
			$data['Sous chapitre'],
			'NON',
			'OUI',
			'NON',
			isset($code_tva[$data['Activite']]) ? $code_tva[$data['Activite']]:'004', // code tva
			$data['Activite'], // code ventillation 7
			$data['Activite'], // code ventaillation 6
			'UN',
			str_replace('.',',',$data["Px d'achat/public"]),
			str_replace('.',',',$data['Remise1']),
			str_replace('.',',',$data['Remise2']),
			str_replace('.',',',$data['Remise3']),
			preg_match("/^[0-9]{13}$/",$data['Gencode']) ? $data['Gencode']:'', // gencode de 13 chiffres uniquement
			'1',substr(str_replace('.',',',$data['Coef']),0,7),				// ligne de tarif 1
			'1','1,33333',											//	a
			'1','1',
			'1','1,0526',
			'1','1,1202',
			'3',str_replace('.',',',$data["Px d'achat/public"]),	// 6
			$date['siecle'],$date['annee'],$date['mois'],$date['jour'], // date de création
			$date['siecle'],$date['annee'],$date['mois'],$date['jour'],	// date de last modif
			'',	// taxe parafiscale (eco taxe)
			'0', //	condtionneemnt de vente
			'0' // conditionnement fournisseur
		))."\n";
	}

	exit;
}

 
if (isset($_POST['id_article_creer']) && isset($_POST['code_article_creer'])) { // mise a jour du status créer ou non
		$res = mysql_query("UPDATE historique_article SET status=NOT status,date_creation=NOW(),code_article='$_POST[code_article_creer]' WHERE id=$_POST[id_article_creer]") or die(mysql_error());
		$row_article =mysql_fetch_array(mysql_query("SELECT * FROM historique_article WHERE id='$_POST[id_article_creer]' LIMIT 0,1"));

		if ($row_article['status'] == 1) { // si l'article vient d'etre créé, on envoi un mail
			require_once '../inc/xpm2/smtp.php';
			$mail = new SMTP;
			$mail->Delivery('relay');
			$mail->Relay('smtp.wanadoo.fr');
			$mail->AddTo($row_article['de_la_part'], "Creation article") or die("Erreur d'ajour de destinataire");
			$mail->From('no-reply@coopmcs.com');
			$html = <<<EOT
L'article <b>$row_article[titre]</b> a été créer. Son code est <b>$row_article[code_article]</b>
EOT;
			$mail->Html($html);
			$sent = $mail->Send("Article cree : $row_article[titre]");
		}
}


?>
<html>
<head>
<style type="text/css">@import url(../js/boutton.css);</style>
<meta http-equiv="Refresh" content="60">
<title>Historique des créations d'articles</title>
<link rel="shortcut icon" type="image/x-icon" href="/intranet/gfx/creation_article.ico" />
<style>
th.label {
	vertical-align:top;
	text-align:left;
	border:solid 1px black;
}

.valeur {
	width:70%;
	font-size:11px;
}

.creer {
	border:solid 1px green;
	color:green;
}

.pas_creer {
	border:solid 1px red;
	color:red;
}

select#completion_fourn {
	margin-left:88px;
	border:solid 1px #000080;
	border-top:none;
	display:none;
}
</style>

<style type="text/css">@import url(../js/boutton.css);</style>

<SCRIPT LANGUAGE="JavaScript">
<!--
function creer_article(id) {
	if (code_article = prompt("Code de l'article",'')) {
		document.historique_article.id_article_creer.value=id;
		document.historique_article.code_article_creer.value=code_article;
		document.historique_article.submit();
	}
}

function export2rubis() {
	document.location.href='historique_creation_article.php?action=export2rubis';
}

//-->
</SCRIPT>

</head>
<body>

<!-- menu de naviguation -->
<? include('../inc/naviguation.php'); ?>

<table>
<tr>
<td style="width:65%;vertical-align:top;">

<form method="post" action="historique_creation_article.php" name="historique_article">
<input type="hidden" name="id_article_creer" value=""/>
<input type="hidden" name="code_article_creer" value=""/>

<input type="button" value="Télécharger au format AFAART.CSV" class="button valider excel" onclick="export2rubis();" style="margin-top:10px;margin-bottom:10px;"/>

<table style="width:100%;" cellspacing="0">
<tr>
	<th class="label" nowrap>Demande de</th>
	<th class="label" nowrap>Code <?=SOCIETE?></th>
	<th class="label" nowrap>Designation</th>
	<th class="label" nowrap>Description</th>
	<th class="label" nowrap>Etat</th>
	<th class="label" nowrap>Date de la demande</th>
	<th class="label" nowrap>Date de création</th>
	<th class="label" nowrap>&nbsp;</th>
</tr>
<?
$res  = mysql_query("SELECT * FROM historique_article ORDER BY date_demande DESC LIMIT 0,60") or die(mysql_error());
while ($row = mysql_fetch_array($res)) {
	$cree = $row['status'] ? ' creer' : ' pas_creer' ;
?>
		<tr>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><?=$row['de_la_part']?></td>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap>&nbsp;<?=$row['code_article']?></td>
			<td class="valeur<?=$cree?>" style="width:30%;" nowrap><?=$row['titre']?></td>
			<td class="valeur<?=$cree?>" style="width:5%" nowrap>
<? if(isset($_GET['info']) && $_GET['info']==$row['id']) { ?>
				<pre><a name="<?=$row['id']?>"></a><?=$row['description']?></pre>
<? } else { ?>
				<a href="historique_creation_article.php?info=<?=$row['id']?>#<?=$row['id']?>">Info</a>
<? } ?>
			</td>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><?=$row['status'] ? 'Créé' : 'Pas créé	'?></td>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><?=$row['date_demande']?></td>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><?=$row['date_creation'] ? $row['date_creation'] : '&nbsp;' ?></td>
<?	if (recuperer_droit() & PEUT_CREER_ARTICLE) { // autoriser a créer et décréer des articles ?>
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><input type="button" class="button <?=$row['status']?'annuler':'valider'?>" style="background-image:url(../js/boutton_images/<?=$row['status']?'delete':'add'?>.png);" value="<?=$row['status']?'DE':''?>CREER" onclick="creer_article(<?=$row['id']?>);"></td>
<? } ?>
		</tr>
<? } ?>
</table>

</form>
</body>
</html>