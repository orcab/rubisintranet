<?php

// script qui permet l'envoi d'EDI aux adhérents qui se lance tous les soirs

include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");


// Liste des vendeur
$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}
$vendeurs['LN'] = 'Jean René';
$vendeurs['MAR'] = 'Marc';

$day_number     = date('w');
$now			= date('Ymd');

// recupère la liste des adhérents ayant un email et leur préférence d'envoi rensignées
$sql = <<<EOT
SELECT	nom,numero_artisan,email,AR,BL,RELIQUAT
FROM	artisan,send_document
WHERE	artisan.numero = send_document.numero_artisan
	AND email<>'' AND email IS NOT NULL
	AND (	(AR <>'0' AND AR<>'')
			OR
			(BL<>'0' AND BL<>'')
			OR
			(RELIQUAT<>'0' AND RELIQUAT<>'')
		)
ORDER	BY nom ASC
EOT;

$res = mysql_query($sql) or die ("Ne peux pas récupérer la liste des artisans : ".mysql_error());
while($row = mysql_fetch_array($res)) { // pour chaque artisan

	// pour chaque type de document
	foreach (array('AR','BL','RELIQUAT') as $type_doc) {
		$html = <<<EOT
<style>
body,td,caption,th {
	font-family:verdana;
	font-size:9px;
}

table {
	width:700px;
	border:solid 1px black;
	border-spacing: 0px;
	border-collapse: collapse;
}

caption {
	text-align:left;
	font-weight:bold;
	border:solid 1px black;
}

td,th {
	border:solid 1px grey;
}

.code_article {
	width:80px;
}

.fournisseur {
	width:120px;
}

.qte {
	width:20px;
}

.prix {
}

.tot {
}

.spe {
	width:20px;
}
</style>
EOT;
		
		//trouve le dernier jour d'envoi, pour n'envoyé que les BL entre la derniere fois et aujourd'hui
		$jour_envoi = explode(',',$row[$type_doc]);
		for($i=0 ; $i<sizeof($jour_envoi) ; $i++) { // on parcours le tableau des jours
			if ($jour_envoi[$i] == $day_number) { // on arrive sur la case du jour concerné
				// on cherche le jour d'envoi précédent
				if ($i > 0) // pas sur le premier jour
					$jour_envoi_precedent = $jour_envoi[$i-1];
				else // sur le premier jour du tableau --> on prend le dernier, pile cyclique
					$jour_envoi_precedent = $jour_envoi[sizeof($i)-1];

				$delta_jour = $day_number > $jour_envoi_precedent ? $day_number - $jour_envoi_precedent : 7-($jour_envoi_precedent-$day_number) ;
				$date_precedente = date('Ymd',mktime(0,0,0,date('m'),date('d')-$delta_jour,date('Y')));
				$date_precedente_plus_un = date('d/m/Y',mktime(0,0,0,date('m'),date('d')-$delta_jour+1,date('Y')));
				$date_jour=date('d/m/Y');
				$date_affichable = $date_jour==$date_precedente_plus_un ? "du $date_jour" : "du $date_precedente_plus_un au $date_jour";
				break;
			}
		}


		$titre ='';  $nb_bon = 0 ;
		if ($type_doc == 'AR' && in_array($day_number,$jour_envoi)) {
			
			require('AR.php');
			$titre = "MCS : Liste des Acuses de reception $date_affichable";

		} elseif ($type_doc == 'BL' && in_array($day_number,$jour_envoi)) {

			require('BL.php');
			$titre = "MCS : Liste des Bons de livraison $date_affichable";

		} elseif ($type_doc == 'RELIQUAT' && in_array($day_number,$jour_envoi)) { // si l'on doit envoyer le reliquat ce jour là
			
			require('RELIQUAT.php');
			$titre = "MCS : Liste des reliquats au $date_jour";
		}

		// TOUT EST PRET, ON ENVOI LE MAIL
		if ($titre && $nb_bon) { // quelque chose à envoyer
			require_once '../../inc/xpm2/smtp.php';
			$mail = new SMTP;
			$mail->Delivery('relay');
			$mail->Relay(SMTP_SERVEUR);
			//$mail->AddTo('benjamin.poulain@coopmcs.com', 'test1') or die("Erreur d'ajour de destinataire"); // pour les tests
			$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
			$mail->From('benjamin.poulain@coopmcs.com');			

			$mail->Html($html);
			$sent = $mail->Send($titre);
			echo now()." [SEND] $row[nom] : $type_doc\n";
		}

	} // foreach type de document

} // fin while artisan


function now() {
	return date("[Y-m-d H:i:s]");
}

?>