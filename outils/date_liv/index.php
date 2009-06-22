<?
include('../../inc/config.php');
$sent		= '';
$num_cde	= '';
$select_adh = '';

// CHERCHE L'EMAIL DE L'ADHRENT POUR LE PREVENIR DE SES DATES DE LIVRAISON
if (isset($_POST['what']) && $_POST['what'] == 'send_email' &&
	isset($_POST['cde_adherent']) && $_POST['cde_adherent']) {

	$num_cde = strtoupper(mysql_escape_string($_POST['cde_adherent']));
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$sql = <<<EOT
select DETAIL_BON.NOCLI,NOMCL,DTLIS,DTLIA,DTLIM,DTLIJ,NOMFO,REFFO,DS1DB,DS2DB,DS3DB,QTESA,PRINE,MONHT,CODAR
from	${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 DETAIL_BON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		DETAIL_BON.CODAR	= ARTICLE_FOURNISSEUR.NOART
				and	DETAIL_BON.FOUR1	= ARTICLE_FOURNISSEUR.NOFOU
		left join AFAGESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.FOUR1	= FOURNISSEUR.NOFOU
		left join AFAGESTCOM.ACLIENP1 CLIENT
			on		DETAIL_BON.NOCLI	= CLIENT.NOCLI
where		NOBON='$num_cde'
		AND PROFI='1'	-- pas un commentaire
		AND ETSBE=''	-- pas annulé
EOT;
	if (isset($_POST['no_cli']) && $_POST['no_cli'])
		$sql .= "\nAND DETAIL_BON.NOCLI='".strtoupper(mysql_escape_string($_POST['no_cli']))."'";

	//echo $sql;exit;
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde fournisseurs : <br/>\n$sql");
	$adhs			= array();
	$adhs_nom		= array();
	$row_loginor	= array();
	while($row = odbc_fetch_array($res)) {
		array_push($row_loginor,$row);
		if (!in_array($row['NOCLI'],$adhs)) {
			array_push($adhs,$row['NOCLI']);
			$adhs_nom[$row['NOCLI']] = $row['NOMCL'];
		}
	}

	//si la commande ne correspond qu'a un seul adh --> on envoi le mail
	if (sizeof($adhs) == 1) {
		$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
		$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");
		$keys		= array_keys($adhs);
		$sql		= "SELECT email,nom FROM artisan WHERE	numero='".$adhs[$keys[0]]."' LIMIT 0,1";

		$res = mysql_query($sql) or die ("Ne peux pas récupérer l'email de l'artisan : ".mysql_error());
		$row = mysql_fetch_array($res);
		$row['email']='ryo@wanadoo.fr'; // pour le debug
		$row['nom']='benjamin'; // pour le debug
		if ($row['email']) { // un email de renseigné --> on peut envoyer

			// entête de mail
			$html = <<<EOT
<b>Liste des dates de livraison de votre commande $num_cde</b><br/><br/>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
	<th>Code</th>
	<th>Fourn</th>
	<th>Ref</th>
	<th>Designation</th>
	<th>Date liv.</th>
	<th>Qte</th>
	<th>P.U.</th>
	<th>Tot.</th>
</tr>
EOT;
			foreach ($row_loginor as $vals) {
				$designation	= $vals['DS1DB'];
				$designation	.= $vals['DS2DB'] ? '<br>'.$vals['DS2DB']:'';
				$designation	.= $vals['DS3DB'] ? '<br>'.$vals['DS3DB']:'';
				$date_liv		= $vals['DTLIJ'].'/'.$vals['DTLIM'].'/'.$vals['DTLIS'].$vals['DTLIA'];
				$month			= $vals['MONHT'];
				$html .= <<<EOT
<tr>
	<td>$vals[CODAR]</td>
	<td>$vals[NOMFO]</td>
	<td>$vals[REFFO]</td>
	<td>$designation</td>
	<td style="font-weight:bold;">$date_liv</td>
	<td>$vals[QTESA]</td>
	<td>$vals[PRINE]</td>
	<td>$month</td>
</tr>
EOT;
			}
			$html .= <<<EOT
</table>
EOT;

			require_once '../../inc/xpm2/smtp.php';
			$mail = new SMTP;
			$mail->Delivery('relay');
			$mail->Relay(SMTP_SERVEUR);
			//$mail->AddTo('benjamin.poulain@coopmcs.com', 'test1') or die("Erreur d'ajour de destinataire"); // pour les tests
			$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
			$mail->From('rachel.kerzulec@coopmcs.com','Rachel Kerzulec');

			//echo $html;

			$mail->Html($html);
			$sent = $mail->Send("MCS : Dates de livraison de votre commande $num_cde");

		}
	}
	elseif (sizeof($adhs) > 1) { // cas ou plusieurs artisans ont le meme n° de cde
		$select_adh = "<select name=\"no_cli\">";
		foreach ($adhs as $val)
			$select_adh .= "<option value=\"$val\">".$adhs_nom[$val]."</option>";
		$select_adh .= "</select>";

	}
}

?>
<html>
<head>
<title>Association des cde adhérents et cde fournisseurs</title>

<style>

body,td {
	font-family:verdana;
	font-size:0.8em;
}

table#assoc {
	width:50%;
	border-collapse:collapse;
	border:solid 1px grey;
}

table#assoc td {
	padding:1px;
	padding-left:3px;
	padding-right:3px;
}	

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--

function send_email() {
	if (document.association.cde_adherent.value) {
		document.association.what.value='send_email';
		return 1;
	} else {
		alert("Aucun n° de commande adhérent");
		return 0;
	}
}

//-->
</script>

</head>
<body>

<form name="association" method="POST" action="index.php" onsubmit="return send_email();">
<input type="hidden" name="what" value="" />

<table id="assoc" align="center">
<tr>
	<td style="width:10%;" nowrap>N° cde adhérent :</td>
	<td style="width:10%;"><input name="cde_adherent" value="<?=$num_cde ? $num_cde:'' ?>" size="8" /></td>
	<td style="text-align:left;"><input type="submit" class="button valider" value="Prévenir des dates de livraisons" onclick="send_email();" /></td>
</tr>
<? if ($select_adh) { // plusieur adh corresponde, on propose d'en selectionné un ?>
<tr>	
	<td colspan="3" style="text-align:center;"><img src="../../gfx/attention.png" /> Plusieurs adhérents ont ce n° de bon, selectionné le bon : <?=$select_adh?></td>
</tr>
<?  } ?>
</table>
</form>

<div
<?	if		($sent===TRUE) 
		echo " style=\"color:green;font-weight:bold;text-align:center;\">Le mail a été correctement envoyé à $row[nom]" ;
	elseif	($sent===FALSE)
		echo " style=\"color:red;font-weight:bold;text-align:center;\">Une erreur est survenu pendant l'envoi du mail à $row[nom] ($row[email])";
	else
		echo ">";
?></div>
</body>
</html>