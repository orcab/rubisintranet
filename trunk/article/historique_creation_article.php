<?php

include('../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

 
if (isset($_POST['id_article_creer']) && isset($_POST['code_article_creer'])) { // mise a jour du status créer ou non
		$res = mysql_query("UPDATE historique_article SET status=NOT status,date_creation=NOW(),code_article='$_POST[code_article_creer]' WHERE id=$_POST[id_article_creer]") or die(mysql_error());
		$row_article =mysql_fetch_array(mysql_query("SELECT * FROM historique_article WHERE id='$_POST[id_article_creer]' LIMIT 0,1"));

		if ($row_article['status'] == 1) { // si l'article vient d'etre créé, on envoi un mail
			require_once '../inc/xpm2/smtp.php';
			$mail = new SMTP;
			$mail->Delivery('relay');
			$mail->Relay('smtp.wanadoo.fr');
			$mail->AddTo($row_article['de_la_part'], "") or die("Erreur d'ajour de destinataire");
			$mail->From('emilie.lenouail@coopmcs.com');
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
<input type="hidden" name="id_article_creer" value="">
<input type="hidden" name="code_article_creer" value="">
<table style="width:100%;" cellspacing="0">
<tr>
	<th class="label" nowrap>Demande de</th>
	<th class="label" nowrap>Code <?=SOCIETE?></th>
	<th class="label" nowrap>Designation</th>
	<th class="label" nowrap>Description</th>
	<th class="label" nowrap>Etat</th>
	<th class="label" nowrap>Date de la demande</th>
	<th class="label" nowrap>Date de création</th>
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
			<td class="valeur<?=$cree?>" style="width:10%" nowrap><input type="button" class="button <?=$row['status']?'annuler':'valider'?>" style="background-image:url(gfx/<?=$row['status']?'delete':'add'?>.png);" value="<?=$row['status']?'DE':''?>CREER" onclick="creer_article(<?=$row['id']?>);"></td>
<? } ?>
		</tr>
<? } ?>
</table>

</form>
</body>
</html>
