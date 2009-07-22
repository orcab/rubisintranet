<?
include('../../inc/config.php');
$num_cde	= '';
$select_fou = '';
$message	= '';

// CHERCHE L'EMAIL DE L'ADHRENT POUR LE PREVENIR DE SES DATES DE LIVRAISON
if (	isset($_POST['what'])
	&& ($_POST['what'] == 'date_liv' || $_POST['what'] == 'mise_a_dispo')
	&& isset($_POST['cde_fournisseur']) && $_POST['cde_fournisseur']) {

	$num_cde = strtoupper(mysql_escape_string($_POST['cde_fournisseur']));
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");

	$sql = <<<EOT
select DETAIL_BON.NOFOU,NOMFO,DETAIL_BON.CFCLI,NOMCL,CFDLS,CFDLA,CFDLM,CFDLJ,REFFO,CFDE1,CFDE2,CFDE3,CFQTE,PRINE,MONHT,CFART,CFCLI,CFCLB,RFCSB
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 DETAIL_BON
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.NOFOU	= FOURNISSEUR.NOFOU
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
			on		DETAIL_BON.CFCLI	= CLIENT.NOCLI
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 CDE_CLIENT_DETAIL
			on		DETAIL_BON.CFCLI	= CDE_CLIENT_DETAIL.NOCLI
				and	DETAIL_BON.CFCLB	= CDE_CLIENT_DETAIL.NOBON
				and DETAIL_BON.CFART	= CDE_CLIENT_DETAIL.CODAR
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 CDE_CLIENT_ENTETE
			on		DETAIL_BON.CFCLI	= CDE_CLIENT_ENTETE.NOCLI
				and	DETAIL_BON.CFCLB	= CDE_CLIENT_ENTETE.NOBON
where		CFBON='$num_cde'
		AND CFPRF='1'	-- pas un commentaire
		AND CFDET=''	-- pas annulé
		AND CFCLI<>''	-- associé a un client
		AND CFCLB<>''	-- avec son n° de cde client
EOT;

		if ($_POST['what'] == 'mise_a_dispo') {
			$sql .= " AND CDDE1='OUI' AND CFDPM='*ENTREE' ";
		}

	//echo $sql;exit;
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde fournisseurs : <br/>\n$sql");
	$four			= array();
	$four_nom		= array();
	$adhs			= array();
	//$row_loginor	= array();
	while($row = odbc_fetch_array($res)) {
		//array_push($row_loginor,$row); // charge les résultats en mémoire

		if (!in_array($row['NOFOU'],$four)) { // regarde combien de fournisseur correspond à la commande
			array_push($four,$row['NOFOU']);
			$four_nom[$row['NOFOU']] = $row['NOMFO'];
		}

		if (array_key_exists($row['CFCLI'],$adhs)) { //on a deja rencontré le client
			// on push dans le tableau existant
			array_push($adhs[$row['CFCLI']],$row);
		} else {
			// on cree un nouveau tableu
			$adhs[$row['CFCLI']] = array($row);
		}
	}

	//print_r($adhs);exit;

	//si la commande ne correspond qu'a un seul fourn --> on envoi les mail aux adh
	if (sizeof($four) == 1) {
		$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
		$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

		foreach ($adhs as $no_cli => $lignes) { // pour chaque adh on envoi un mail concernant ses produits
			$sql		= "SELECT email,nom FROM artisan WHERE numero='$no_cli' LIMIT 0,1";

			$res = mysql_query($sql) or die ("Ne peux pas récupérer l'email de l'artisan : ".mysql_error());
			$row = mysql_fetch_array($res);
			//$row['email']='ryo@wanadoo.fr';	// pour le debug
			//$row['nom']='benjamin';			// pour le debug
			if ($row['email']) { // un email de renseigné --> on peut envoyer
				$nom_four = trim($lignes[0]['NOMFO']);



				if ($_POST['what'] == 'date_liv') { // on previent des futures date de livraison

					// entête de mail
					$html = <<<EOT
<b>Voici les dates d'arrivées dans nos locaux des articles commandés chez le fournisseur $nom_four</b><br/>
(Attention, les dates annoncées par le fournisseur ne sont pas contractuelles et peuvent être soumises à un battement d'une semaine environ)<br/>
<br/>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
	<th>N° Cde</th>
	<th>Référence Cde</th>
	<th>Code</th>
	<th>Fourn</th>
	<th>Ref fourn.</th>
	<th>Designation</th>
	<th>Date liv.</th>
	<th>Qte</th>
	<th>P.U.</th>
	<th>Tot.</th>
</tr>
EOT;
					$cde_adh = array();
					foreach ($lignes as $idx => $lig) { // pour chaque ligne des bons adhérents
						array_push($cde_adh,$lig['CFCLB']);
						$designation	= $lig['CFDE1'];
						$designation	.= $lig['CFDE2'] ? '<br>'.$lig['CFDE2']:'';
						$designation	.= $lig['CFDE3'] ? '<br>'.$lig['CFDE3']:'';
						$lig['CFQTE']	= sprintf('%0.2f',$lig['CFQTE']);
						$lig['PRINE']	= sprintf('%0.2f',$lig['PRINE']);
						$lig['REFFO']	= $lig['REFFO'] ? $lig['REFFO'] : '&nbsp;';
						$html .= <<<EOT
<tr>
	<td>$lig[CFCLB]</td>
	<td>$lig[RFCSB]</td>
	<td>$lig[CFART]</td>
	<td>$lig[NOMFO]</td>
	<td>$lig[REFFO]</td>
	<td>$designation</td>
	<td style="font-weight:bold;">$lig[CFDLJ]/$lig[CFDLM]/$lig[CFDLS]$lig[CFDLA]</td>
	<td>$lig[CFQTE]</td>
	<td>$lig[PRINE]</td>
	<td>$lig[MONHT]</td>
</tr>
EOT;
					}
					$html .= "</table>";


				} elseif ($_POST['what'] == 'mise_a_dispo') { // on previent de la mise a dispo du matos

					// entête de mail
					$html = <<<EOT
<b>Les articles suivant du fournisseur $nom_four viennent d'être mis à disposition à la coopérative</b><br/>
<br/>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
	<th>N° Cde</th>
	<th>Référence Cde</th>
	<th>Code</th>
	<th>Fourn</th>
	<th>Ref fourn.</th>
	<th>Designation</th>
	<th>Date liv.</th>
	<th>Qte</th>
	<th>P.U.</th>
	<th>Tot.</th>
</tr>
EOT;
					$cde_adh = array();
					foreach ($lignes as $idx => $lig) { // pour chaque ligne des bons adhérents
						array_push($cde_adh,$lig['CFCLB']);
						$designation	= $lig['CFDE1'];
						$designation	.= $lig['CFDE2'] ? '<br>'.$lig['CFDE2']:'';
						$designation	.= $lig['CFDE3'] ? '<br>'.$lig['CFDE3']:'';
						$lig['CFQTE']	= sprintf('%0.2f',$lig['CFQTE']);
						$lig['PRINE']	= sprintf('%0.2f',$lig['PRINE']);
						$lig['REFFO']	= $lig['REFFO'] ? $lig['REFFO'] : '&nbsp;';
						$html .= <<<EOT
<tr>
	<td>$lig[CFCLB]</td>
	<td>$lig[RFCSB]</td>
	<td>$lig[CFART]</td>
	<td>$lig[NOMFO]</td>
	<td>$lig[REFFO]</td>
	<td>$designation</td>
	<td style="font-weight:bold;">$lig[CFDLJ]/$lig[CFDLM]/$lig[CFDLS]$lig[CFDLA]</td>
	<td>$lig[CFQTE]</td>
	<td>$lig[PRINE]</td>
	<td>$lig[MONHT]</td>
</tr>
EOT;
					}
					$html .= "</table>";
				}



				require_once '../../inc/xpm2/smtp.php';
				$mail = new SMTP;
				$mail->Delivery('relay');
				$mail->Relay(SMTP_SERVEUR);
				//$mail->AddTo('ryo@wanadoo.fr', 'Ben') or die("Erreur d'ajour de destinataire"); // pour les tests
				$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
				$mail->From('rachel.kerzulec@coopmcs.com','Rachel Kerzulec');

				$mail->Html($html);
				
				if ($mail->Send("MCS : Dates de livraison du fournisseur $nom_four : Cde : ".join(', ',$cde_adh)))
					$message .= "<div class=\"message\" style=\"color:green;\">Email correctement envoyé à $row[nom] ($row[email])</div>\n";
				else 
					$message .= "<div class=\"message\" style=\"color:red;\">Erreur dans l'envoi de l'email à $row[nom] ($row[email])</div>\n";
				
			} else { // fin if il a un email
				$message .= "<div class=\"message\" style=\"color:red;\">Erreur $row[nom] n'a pas d'email</div>\n";
			}
		}// fin pour chaque adhérents
	}
	elseif (sizeof($four) > 1) { // cas ou plusieurs fournisseur ont le meme n° de cde
		$select_fou = "<select name=\"no_fou\">";
		foreach ($four as $val)
			$select_fou .= "<option value=\"$val\">".$four_nom[$val]."</option>";
		$select_fou .= "</select>";
	}
}

?>
<html>
<head>
<title>Envoi des délais de livraison des cde adhérent</title>

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
	vertical-align:top;
	padding:5px;
}	

div.message {
	text-align:center;
	font-weight:bold;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--

function send_email(type_of_action) {
	if (document.association.cde_fournisseur.value) {
		document.association.what.value = type_of_action;
		document.association.submit();
	} else {
		alert("Aucun n° de commande fournisseur");
	}
}

//-->
</script>

</head>
<body>

<form name="association" method="POST" action="index.php">
<input type="hidden" name="what" value="" />

<table id="assoc" align="center">
<tr>
	<td style="width:10%;" nowrap>N° cde fournisseur :</td>
	<td style="width:10%;"><input name="cde_fournisseur" value="<?=$num_cde ? $num_cde:'' ?>" size="8" /></td>
	<td style="text-align:left;">
		<input type="button" class="button valider" value="Prévenir des dates de livraisons"		onclick="send_email('date_liv');" />
		<input type="button" class="button valider" value="Prévenir de la mise à dispo du matériel" onclick="send_email('mise_a_dispo');" style="margin-top:5px;" />
	</td>
</tr>
<? if ($select_fou) { // plusieur fournisseur corresponde, on propose d'en selectionné un ?>
<tr>	
	<td colspan="3" style="text-align:center;"><img src="../../gfx/attention.png" /> Plusieurs fournisseur ont ce n° de bon, selectionné le bon : <?=$select_fou?></td>
</tr>
<?  } ?>
</table>
</form>

<?= $message ? $message:'' ?>
</body>
</html>