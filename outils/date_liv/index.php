<?
include('../../inc/config.php');
require_once '../../inc/xpm2/smtp.php';

$num_cde	= '';
$select_fou = '';
$message	= '';

// CHERCHE L'EMAIL DE L'ADHRENT POUR LE PREVENIR DE SES DATES DE LIVRAISON
if (	isset($_POST['what'])
	&& ($_POST['what'] == 'date_liv' || $_POST['what'] == 'mise_a_dispo')
	&& isset($_POST['cde_fournisseur']) && $_POST['cde_fournisseur']) {

	$num_cde = strtoupper(mysql_escape_string($_POST['cde_fournisseur']));
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");

	$sql = <<<EOT
select DETAIL_BON.NOFOU,NOMFO,DETAIL_BON.CFCLI,NOMCL,CFDLS,CFDLA,CFDLM,CFDLJ,REFFO,CFDE1,CFDE2,CFDE3,CFQTE,PRINE,MONHT,CFART,CFCLI,CFCLB,RFCSB,CFLIG,CDE_CLIENT_ENTETE.LIVSB,CDE_CLIENT_ENTETE.NOMSB
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 DETAIL_BON
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on		DETAIL_BON.NOFOU	= FOURNISSEUR.NOFOU
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
			on		DETAIL_BON.CFCLI	= CLIENT.NOCLI
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 CDE_CLIENT_DETAIL
			on		DETAIL_BON.CFCLI	= CDE_CLIENT_DETAIL.NOCLI
				and	DETAIL_BON.CFCLB	= CDE_CLIENT_DETAIL.NOBON
				and DETAIL_BON.CFART	= CDE_CLIENT_DETAIL.CODAR
				and CDE_CLIENT_DETAIL.ETSBE = ''								-- commande client pas annul�e
		left join  ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 CDE_CLIENT_ENTETE
			on		DETAIL_BON.CFCLI	= CDE_CLIENT_ENTETE.NOCLI
				and	DETAIL_BON.CFCLB	= CDE_CLIENT_ENTETE.NOBON
where		CFBON='$num_cde'
		AND CFPRF='1'	-- pas un commentaire
		AND CFDET=''	-- pas annul�
		AND CFCLI<>''	-- associ� a un client
		AND CFCLB<>''	-- avec son n� de cde client
EOT;

		if ($_POST['what'] == 'mise_a_dispo') {
			$sql .= " AND CDDE1='OUI' AND CFDPM='*ENTREE' ";
		}

	//echo $sql;exit;
	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde fournisseurs : <br/>\n$sql");
	$four			= array();
	$four_nom		= array();
	$adhs			= array();
	$vendeurs		= array();
	$nb_result		= 0;

	while($row = odbc_fetch_array($res)) {
		$nb_result++;
		//array_push($row_loginor,$row); // charge les r�sultats en m�moire

		if (!in_array($row['NOFOU'],$four)) { // regarde combien de fournisseur correspond � la commande
			array_push($four,$row['NOFOU']);
			$four_nom[$row['NOFOU']] = $row['NOMFO'];
		}

		if (array_key_exists($row['CFCLI'],$adhs)) { //on a deja rencontr� le client
			array_push($adhs[$row['CFCLI']],$row);
		} else {
			$adhs[$row['CFCLI']] = array($row);
		}

		if (array_key_exists($row['LIVSB'],$vendeurs)) { //on a deja rencontr� le vendeur
			array_push($vendeurs[$row['LIVSB']],$row);
		} else {
			$vendeurs[$row['LIVSB']] = array($row);
		}
	}

	if ($nb_result <= 0)
		$message .= "<div class=\"message\" style=\"color:red;\">Aucune lignes concern&eacute;e pour le bon $num_cde</div>\n";

	//print_r($adhs);exit;
	//print_r($vendeurs);exit;

	//si la commande ne correspond qu'a un seul fourn --> on envoi les mail aux adh
	if (sizeof($four) == 1) {
		$mysql		= mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
		$database	= mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");



		foreach ($adhs as $no_cli => $lignes) { // pour chaque adh on envoi un mail concernant ses produits
			$sql		= "SELECT email,nom FROM artisan WHERE numero='$no_cli' LIMIT 0,1";

			$res = mysql_query($sql) or die ("Ne peux pas r�cup�rer l'email de l'artisan : ".mysql_error());
			$row = mysql_fetch_array($res);
			//$row['email']='ryo@wanadoo.fr';	// pour le debug
			//$row['nom']='benjamin';			// pour le debug
			if ($row['email']) { // un email de renseign� --> on peut envoyer
				$nom_four = trim($lignes[0]['NOMFO']);

				$html = '';
				if ($_POST['what'] == 'date_liv') { // on previent des futures date de livraison
					$html .= "<b>Voici les dates d'arriv�es dans nos locaux des articles command�s chez le fournisseur <u>$nom_four</u></b><br/>\n(Attention, les dates annonc�es par le fournisseur ne sont pas contractuelles et peuvent �tre soumises � un battement d'une semaine environ).<br/><b>De pr�f�rence renseigez vous aupr�s du p�le adh�rent pour confirmer la r�ception du produit : pauline.chaplais@coopmcs.com</b>";
				} elseif ($_POST['what'] == 'mise_a_dispo') { // on previent de la mise a dispo du matos
					$html .= "<b>Les articles suivant du fournisseur <u>$nom_four</u> viennent d'�tre mis � disposition � la coop�rative</b>";
				}

				$html .= <<<EOT
<br/><br/>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
		<th>Adh�rent</th>
        <th>N� Cde</th>
        <th>R�f�rence Cde</th>
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
				$cde_ligne_traitee = array();
				$need_email = FALSE;
				foreach ($lignes as $idx => $lig) { // pour chaque ligne des bons adh�rents

					// on v�rifie si un email n'a pas d�j� �t� envoy�
					if (mysql_num_rows(mysql_query("SELECT id FROM mise_a_dispo_sent WHERE no_cde_fourn='$num_cde' AND code_fourn='".mysql_escape_string($four[0])."' AND ligne='".mysql_escape_string($lig['CFLIG'])."' LIMIT 0,1"))) { // si oui --> on passe � la ligne suivante
						$message .= "<div class=\"message\" style=\"color:grey;\">Email d&eacute;j&agrave; envoy� � $row[nom] ($row[email])</div>\n";
						continue;
					}

					$need_email = TRUE;
					array_push($cde_ligne_traitee,$lig['CFLIG']); // on enregsitre la ligne traitee pour la base de donn�e
					array_push($cde_adh,$lig['CFCLB']);
					$designation	= $lig['CFDE1'];
					$designation	.= $lig['CFDE2'] ? '<br>'.$lig['CFDE2']:'';
					$designation	.= $lig['CFDE3'] ? '<br>'.$lig['CFDE3']:'';
					$lig['CFQTE']	= sprintf('%0.2f',$lig['CFQTE']);
					$lig['PRINE']	= sprintf('%0.2f',$lig['PRINE']);
					$lig['REFFO']	= $lig['REFFO'] ? $lig['REFFO'] : '&nbsp;';
					$html .= <<<EOT
<tr>
	<td>$row[nom]</td>
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
				} // fin pour chaque ligne
				$html .= "</table>";

				if ($need_email) {
					$mail = new SMTP;
					$mail->Delivery('relay');
					$mail->Relay(SMTP_SERVEUR);
					//$mail->AddTo('ryo@wanadoo.fr', 'Ben') or die("Erreur d'ajour de destinataire"); // pour les tests
					$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
					//$mail->AddTo('gwenael.croizer@coopmcs.com', 'Gwenael Croizer') or die("Erreur d'ajour de destinataire");
					$mail->From('elisabeth.binio@coopmcs.com','Elisabeth Binio');

					$mail->Html($html);
					//echo $row['nom']."\n<br>".$html."<br><br><br>";

					// on enregistre dans la base que le mail a ete envoy� (pour ne pas le r�envoyer plus tard)
					foreach ($cde_ligne_traitee as $ligne)
						mysql_query("INSERT IGNORE INTO mise_a_dispo_sent (no_cde_fourn,code_fourn,ligne,date_envoi) VALUES ('$num_cde','".mysql_escape_string($four[0])."','".mysql_escape_string($ligne)."',NOW())") or die ("Ne peux pas enregistrer l'envoi de mail ".mysql_error());
					
					if ($mail->Send("MCS : Dates de livraison du fournisseur $nom_four : Cde : ".join(', ',$cde_adh)))
						$message .= "<div class=\"message\" style=\"color:green;\">Email correctement envoy� � $row[nom] ($row[email])</div>\n";
					else 
						$message .= "<div class=\"message\" style=\"color:red;\">Erreur dans l'envoi de l'email � $row[nom] ($row[email])</div>\n";
						
						
				}
			} else { // fin if il a un email
				$message .= "<div class=\"message\" style=\"color:red;\">Erreur $row[nom] n'a pas d'email</div>\n";
			}
		}// fin pour chaque adh�rents





		if ($_POST['what'] == 'mise_a_dispo') { // pour les mises � dispo, on pr�vient aussi les vendeurs
		foreach ($vendeurs as $no_vendeur => $lignes) { // pour chaque vendeur on envoi un mail concernant ses produits
			$sql		= "SELECT email,prenom,nom FROM employe WHERE code_vendeur='$no_vendeur' LIMIT 0,1";

			$res = mysql_query($sql) or die ("Ne peux pas r�cup�rer l'email du vendeur : ".mysql_error());
			$row = mysql_fetch_array($res);
			//$row['email']='ryo@wanadoo.fr';	// pour le debug
			//$row['nom']='benjamin';			// pour le debug
			if ($row['email']) { // un email de renseign� --> on peut envoyer
				$nom_four = trim($lignes[0]['NOMFO']);

				$html = <<<EOT
<b>Les articles suivant du fournisseur <u>$nom_four</u> viennent d'�tre mis � disposition � la coop�rative</b>
<br/><br/>
<table border="1" cellpadding="3" cellspacing="0">
<tr>
		<th>Adh&eacute;rent</th>
        <th>N� Cde</th>
        <th>R�f�rence Cde</th>
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
				$cde_ligne_traitee = array();
				$need_email = FALSE;
				foreach ($lignes as $idx => $lig) { // pour chaque ligne des bons adh�rents

					// on v�rifie si un email n'a pas d�j� �t� envoy�
					if (mysql_num_rows(mysql_query("SELECT id FROM mise_a_dispo_sent WHERE no_cde_fourn='$num_cde' AND code_fourn='".mysql_escape_string($four[0])."' AND ligne='".mysql_escape_string($lig['CFLIG'])."' LIMIT 0,1"))) { // si oui --> on passe � la ligne suivante
						continue;
					}

					$need_email = TRUE;
					array_push($cde_ligne_traitee,$lig['CFLIG']); // on enregsitre la ligne traitee pour la base de donn�e
					array_push($cde_adh,$lig['CFCLB']);
					$designation	= $lig['CFDE1'];
					$designation	.= $lig['CFDE2'] ? '<br>'.$lig['CFDE2']:'';
					$designation	.= $lig['CFDE3'] ? '<br>'.$lig['CFDE3']:'';
					$lig['CFQTE']	= sprintf('%0.2f',$lig['CFQTE']);
					$lig['PRINE']	= sprintf('%0.2f',$lig['PRINE']);
					$lig['REFFO']	= $lig['REFFO'] ? $lig['REFFO'] : '&nbsp;';
					$html .= <<<EOT
<tr>
	<td>$lig[NOMSB]</td>
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
				} // fin pour chaque ligne
				$html .= "</table>";

				if ($need_email) {
					$mail = new SMTP;
					$mail->Delivery('relay');
					$mail->Relay(SMTP_SERVEUR);
					//$mail->AddTo('ryo@wanadoo.fr', 'Ben') or die("Erreur d'ajour de destinataire"); // pour les tests
					$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
					$mail->From('elisabeth.binio@coopmcs.com','Elisabeth Binio');

					$mail->Html($html);
					//echo $row['nom']."\n<br>".$html."<br><br><br>";

					if ($mail->Send("MCS : Dates de livraison du fournisseur $nom_four : Cde : ".join(', ',$cde_adh)))
						$message .= "<div class=\"message\" style=\"color:green;\">Email correctement envoy� au vendeur $row[prenom] ($row[email])</div>\n";
					else 
						$message .= "<div class=\"message\" style=\"color:red;\">Erreur dans l'envoi de l'email au vendeur $row[prenom] ($row[email])</div>\n";
					
				}
			} else { // fin if il a un email
				$message .= "<div class=\"message\" style=\"color:red;\">Erreur le vendeur $row[prenom] $row[nom] n'a pas d'email</div>\n";
			}
		}// fin pour chaque adh�rents
		} // fin mise a dispo pour les vendeurs


	}
	elseif (sizeof($four) > 1) { // cas ou plusieurs fournisseur ont le meme n� de cde
		$select_fou = "<select name=\"no_fou\">";
		foreach ($four as $val)
			$select_fou .= "<option value=\"$val\">".$four_nom[$val]."</option>";
		$select_fou .= "</select>";
	}
}


elseif (		isset($_POST['what'])
			&&	$_POST['what'] == 'record_date_liv_in_rubis'
			&&	isset($_POST['cde_fournisseur']) && $_POST['cde_fournisseur']
			&&	isset($_POST['date_liv']) && $_POST['date_liv']) {
	
	$num_cde = strtoupper(mysql_escape_string($_POST['cde_fournisseur']));
	$loginor  = odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");

	$sql = <<<EOT
select	COUNT(CFBON) as NB_BON
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1
where		CFBON='$num_cde'
		AND CFEET=''	-- pas annul�
EOT;

	$res = odbc_exec($loginor,$sql)  or die("Impossible de lancer la requete de recherche des cde fournisseurs : <br/>\n$sql");
	$row = odbc_fetch_array($res);
	if ($row['NB_BON'] == 1) {
		
		$jour	= '';	$mois	= '';	$annee	= '';	$siecle = '';
		// examen du format de la date
		if (strlen($_POST['date_liv']) == 6) { // format jjmmaa
			$jour	= substr($_POST['date_liv'],0,2);
			$mois	= substr($_POST['date_liv'],2,2);
			$siecle = '20';
			$annee	= substr($_POST['date_liv'],4,2);
		} elseif (strlen($_POST['date_liv']) == 8) {  // format jjmmaaaa
			$jour	= substr($_POST['date_liv'],0,2);
			$mois	= substr($_POST['date_liv'],2,2);
			$siecle = substr($_POST['date_liv'],4,2);
			$annee	= substr($_POST['date_liv'],6,2);
		} elseif (strlen($_POST['date_liv']) == 10) {  // format jj/mm/aaaa
			list($jour,$mois,$tmp) = explode('/',$_POST['date_liv']);
			$siecle	= substr($tmp,0,2);
			$annee	= substr($tmp,2,2);
		} else {
			$message .= "<div class=\"message\" style=\"color:red;\">Format de date non reconnu</div>\n";
		}

		// la date est bonne, on la rentre dans Rubis
		if ($jour && $mois && $siecle && $annee && preg_match('/^[0-9]{8}$/',"$jour$mois$siecle$annee")) {
			if ($jour >= 1 && $jour <= 31 && $mois >= 1 && $mois <= 12 && $siecle == '20' && $annee >= 10) {
				// met � jour l'entete de la commande fournisseur
				$sql = <<<EOT
update	${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1
set		CFELS='$siecle', CFELA='$annee', CFELM='$mois', CFELJ='$jour', CFCON='OUI'
where	CFBON='$num_cde'
EOT;
				$res = odbc_exec($loginor,$sql)  or die("Impossible d'enregistrer la date de livraison dans l'entete : <br/>\n$sql");

				// met a jour le d�tail des lignes
				$sql = <<<EOT
update	${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1
set		CFDLS='$siecle', CFDLA='$annee', CFDLM='$mois', CFDLJ='$jour', CFCOD='OUI'
where	CFBON='$num_cde' and CFPRF='1'
EOT;
				$res = odbc_exec($loginor,$sql)  or die("Impossible d'enregistrer la date de livraison dans le d�tail des lignes : <br/>\n$sql");
				$message .= "<div class=\"message\" style=\"color:green;\">Date de livraison enregistr�e dans Rubis</br>Bon : $num_cde &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Date : $jour/$mois/$siecle$annee</div>\n";

				// va chercher les cde adh associ�e et met a jour les date de livraison si celle si est plus recente que la date sp�ciif�e
				$sql = <<<EOT
select	DISTINCT(CFCLB) as NUM_BON_ADH,CFCLI,
		DLSSB,DLASB,DLMSB,DLJSB -- date de livraison
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 DETAIL_CDE_FOURN
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 ENTETE_CDE_CLIENT
			on		DETAIL_CDE_FOURN.CFCLB=ENTETE_CDE_CLIENT.NOBON
				and DETAIL_CDE_FOURN.CFCLI=ENTETE_CDE_CLIENT.NOCLI
where		CFBON='$num_cde'	-- n� de cde fournisseur
		and CFDET=''			-- ligne pas annul�e
		and CFCLB<>''			-- cde special pour un adh
EOT;
				$res = odbc_exec($loginor,$sql)  or die("Impossible de rechercher les cde adh�rents associ�es<br/>\n$sql");
				while($row = odbc_fetch_array($res)) {
					$date_liv_adh   = $row['DLSSB'].$row['DLASB'].$row['DLMSB'].$row['DLJSB'];
					$date_liv_fourn = $siecle.$annee.$mois.$jour;
					if ($date_liv_fourn > $date_liv_adh) { // si la date de livraison fournisseur est sup�rieur � la date pr�vu de livraison adh
						// on met � jour la cde adh
						$sql = <<<EOT
update	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1
set		DLSSB='$siecle', DLASB='$annee', DLMSB='$mois', DLJSB='$jour'
where	NOBON='$row[NUM_BON_ADH]' and NOCLI='$row[CFCLI]'
EOT;
						$res2 = odbc_exec($loginor,$sql)  or die("Impossible d'enregistrer la date de livraison dans la cde adh�rent : <br/>\n$sql");
						$message .= "<div class=\"message\" style=\"color:green;\">Commande adh�rent $row[NUM_BON_ADH] modifi�e</div>\n";
					}
				}
			} else {
				$message .= "<div class=\"message\" style=\"color:red;\">La date $jour/$mois/$siecle$annee ne semble pas �tre une date valide</div>\n";
			}
		} else {
			$message .= "<div class=\"message\" style=\"color:red;\">Le format de date n'a pas pu �tre convertie</div>\n";
		}

		//echo "\$jour='$jour'   \$mois='$mois'   \$siecle='$siecle'   \$annee='$annee'";
		
	} elseif ($row['NB_BON'] > 1) {
		$message .= "<div class=\"message\" style=\"color:red;\">Il existe plusieurs commandes fournisseur avec ce num�ro</div>\n";
	} elseif ($row['NB_BON'] <= 0) {
		$message .= "<div class=\"message\" style=\"color:red;\">Il n'existe aucune commande fournisseur avec ce num�ro</div>\n";
	}
}

?>
<html>
<head>
<title>Envoi des d�lais de livraison des cde adh�rent</title>

<style>

body,td {
	font-family:verdana;
	font-size:0.8em;
}

table {
	width:50%;
	border:solid 1px grey;
	border-radius:10px 10px;
	margin:auto;
}

caption {
	text-align:left;
	font-weight:bold;
	font-size:0.9em;
	margin-bottom:5px;
}

table#assoc td {
	padding:1px;
	padding-left:3px;
	padding-right:3px;
	vertical-align:top;
	padding:5px;
}

table#assoc {
	margin-bottom:30px;
}

table#record {
	padding-top:5px;
	padding-bottom:5px;
}

div.message {
	text-align:center;
	font-weight:bold;
}

</style>
<style type="text/css">@import url(../../js/boutton.css);</style>

<script language="javascript">
<!--

function send_date_liv() {
	if (document.association.cde_fournisseur.value) {
		document.association.what.value = 'date_liv';
		return 1;
	} else {
		alert("Aucun n� de commande fournisseur");
		return 0;
	}
}


function send_mise_a_dispo() {
	if (document.association2.cde_fournisseur.value) {
		document.association2.what.value = 'mise_a_dispo';
		return 1;
	} else {
		alert("Aucun n� de commande fournisseur");
		return 0;
	}
}

function record_date_liv_in_rubis() {
	if (document.association3.cde_fournisseur.value) {
		if (document.association3.date_liv.value) {
			document.association3.what.value = 'record_date_liv_in_rubis';
			return 1;
		} else {
			alert("Aucun date de livraison sp�cifi�e");
			return 0;
		}
	} else {
		alert("Aucun n� de commande fournisseur");
		return 0;
	}	
}

function change_de_input(obj) {
	if (obj.value.length >= 6)
		document.association3.date_liv.focus();
}

function init_focus() {
<?	if (isset($_POST['what'])) {
		if ($_POST['what'] == 'date_liv') { ?>
			document.association.cde_fournisseur.focus();
<?		} elseif ($_POST['what'] == 'mise_a_dispo') { ?>
			document.association2.cde_fournisseur.focus();
<?		}
	} ?>
} // fin init_focus

//-->
</script>

</head>
<body onload="init_focus();">

<table id="assoc">
<caption>Pr�venir les adh�rents par Email</caption>
<form name="association" method="POST" action="index.php" onsubmit="return send_date_liv();">
<tr>
	<td style="width:10%;" nowrap>N� cde fournisseur :</td>
	<td style="width:10%;">
		<input type="hidden" name="what" value="" />
		<input name="cde_fournisseur" value="" size="8" />
	</td>
	<td style="text-align:left;">
		<input type="submit" class="button valider" style="background-image:url(../../js/boutton_images/email.gif)" value="Pr�venir des dates de livraisons" />
	</td>
</tr>
<? if ($select_fou) { // plusieur fournisseur corresponde, on propose d'en selectionn� un ?>
<tr>	
	<td colspan="3" style="text-align:center;"><img src="../../gfx/attention.png" /> Plusieurs fournisseur ont ce n� de bon, selectionn� le bon : <?=$select_fou?></td>
</tr>
<?  } ?>
</form>


<form name="association2" method="POST" action="index.php" onsubmit="return send_mise_a_dispo();">
<tr>
	<td style="width:10%;" nowrap>N� cde fournisseur :</td>
	<td style="width:10%;">
		<input type="hidden" name="what" value="" />
		<input name="cde_fournisseur" value="" size="8" />
	</td>
	<td style="text-align:left;">
		<input type="submit" class="button valider" style="background-image:url(../../js/boutton_images/email.gif)" value="Pr�venir de la mise � dispo du mat�riel" />
	</td>
</tr>
<? if ($select_fou) { // plusieur fournisseur corresponde, on propose d'en selectionn� un ?>
<tr>	
	<td colspan="3" style="text-align:center;"><img src="../../gfx/attention.png" /> Plusieurs fournisseur ont ce n� de bon, selectionn� le bon : <?=$select_fou?></td>
</tr>
<?  } ?>
</form>

</table>

<!--
<table id="record">
<caption>Enregistrer les dates de livraison dans Rubis</caption>
<form name="association3" method="POST" action="index.php" onsubmit="return record_date_liv_in_rubis();">
<tr>
	<td style="width:10%;vertical-align:top;" nowrap="nowrap">
		N� cde fournisseur :<br/>
		Date de livraison :
	</td>
	<td style="width:10%;vertical-align:top;" nowrap="nowrap">
		<input type="hidden" name="what" value="" />
		<input name="cde_fournisseur" value="" size="8" onkeyup="change_de_input(this);" /><br/>
		<input name="date_liv" value="" size="8" /><br/>
		<span style="color:grey;font-size:0.7em;">
			(25/04/2010)</br>
			(25042010)</br>
			(250410)
		</span>
	</td>
	<td style="text-align:left;vertical-align:top;">
		<input type="submit" class="button valider" value="Enregistrer les dates de livraison dans Rubis" />
	</td>
</tr>
</form>
</table>
-->
<?= $message ? $message:'' ?>
</body>
</html>