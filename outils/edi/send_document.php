<?php

// script qui permet l'envoi d'EDI aux adhérents qui se lance tous les soirs

include('../../inc/config.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

// Liste des vendeur
$vendeurs = select_vendeur();

$arguments = array();
if (isset($argv)) { // des param en lignes de commandes car l'on veut forcer un artisan et une date
	foreach ($argv as $t)
		if (preg_match('/^--date=(\d{4}-\d{2}-\d{2})$/i',$t,$regs))
			$arguments['date'] = $regs[1];
		else if (preg_match('/^--artisan=(.+)$/i',$t,$regs))
			$arguments['artisan'] = $regs[1];
}

$day_number     = isset($arguments['date']) ? date('w',  strtotime($arguments['date'])) : date('w');   // soit un jour imposé, soit aujourd'hui par défaut
$now			= isset($arguments['date']) ? date('Ymd',strtotime($arguments['date'])) : date('Ymd'); // soit un jour imposé, soit aujourd'hui par défaut

$where_artisan  = isset($arguments['artisan']) ? " AND artisan.numero='$arguments[artisan]' " : '' ;

// recupère la liste des adhérents ayant un email et leur préférence d'envoi renseignées
$sql = <<<EOT
SELECT	nom,numero_artisan,email,AR,BL,RELIQUAT,AVOIR
FROM	artisan,send_document
WHERE	artisan.numero = send_document.numero_artisan
	AND email<>'' AND email IS NOT NULL
	AND (		(AR <>'0'		AND AR<>'')
			OR	(BL<>'0'		AND BL<>'')
			OR	(RELIQUAT<>'0'	AND RELIQUAT<>'')
			OR	(AVOIR<>'0'		AND AVOIR<>'')
		)
	$where_artisan
ORDER	BY nom ASC
EOT;

$res = mysql_query($sql) or die ("Ne peux pas récupérer la liste des artisans : ".mysql_error());
while($row = mysql_fetch_array($res)) { // pour chaque artisan

	// pour chaque type de document
	foreach (array('AR','BL','RELIQUAT','AVOIR') as $type_doc) {
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

td,th { border:solid 1px grey; }
.code_article { width:80px; }
.fournisseur { 	width:120px; }
.qte { 	width:20px; }
.spe { 	width:20px; }
</style>
EOT;
		
		//trouve le dernier jour d'envoi, pour n'envoyer que les BL entre la derniere fois et aujourd'hui
		$jour_envoi = explode(',',$row[$type_doc]);

		//print_r($jour_envoi);
		//echo "\$day_number=$day_number\n";

		for($i=0 ; $i<sizeof($jour_envoi) ; $i++) { // on parcours le tableau des jours
			if ($jour_envoi[$i] == $day_number) { // on arrive sur la case du jour concerné
				// on cherche le jour d'envoi précédent
				if ($i > 0) // pas sur le premier jour
					$jour_envoi_precedent = $jour_envoi[$i-1];
				else // sur le premier jour du tableau --> on prend le dernier, pile cyclique
					$jour_envoi_precedent = $jour_envoi[sizeof($jour_envoi)-1];

				//echo "\$i=$i\n";
				//echo "\$jour_envoi_precedent=$jour_envoi_precedent\n";
	
				if ($day_number > $jour_envoi_precedent) {
					$delta_jour = $day_number - $jour_envoi_precedent;
				} else {
					$delta_jour = 7-($jour_envoi_precedent-$day_number);
				}

				//echo "\$delta_jour=$delta_jour\n";

				$Ymd = isset($arguments['date']) ? date('Ymd',strtotime($arguments['date'])) : date('Ymd');
				$Y				= substr($Ymd,0,4);
				$m				= substr($Ymd,4,2);
				$d				= substr($Ymd,6,2);
				$date_precedente		= date('Ymd'  , mktime(0,0,0,$m, $d - $delta_jour  , $Y));
				$date_precedente_plus_un= date('d/m/Y', mktime(0,0,0,$m, $d - $delta_jour + 1 , $Y));
				$date_jour				= "$d/$m/$Y";
				$date_affichable = $date_jour==$date_precedente_plus_un ? "du $date_jour" : "du $date_precedente_plus_un au $date_jour";

				//echo "\$date_affichable=$date_affichable\n";
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

		} elseif ($type_doc == 'AVOIR' && in_array($day_number,$jour_envoi)) { // si l'on doit envoyer l'avoir ce jour là
			require('AVOIR.php');
			$titre = "MCS : Liste des Avoirs au $date_affichable";
		}

		//echo $titre."\n<br>". $html;

		// TOUT EST PRET, ON ENVOI LE MAIL
		if ($titre && $nb_bon) { // quelque chose à envoyer
		//if (0) {
			require_once '../../inc/xpm2/smtp.php';
			$mail = new SMTP;
			$mail->Delivery('relay');
			$mail->Relay(SMTP_SERVEUR,SMTP_USER,SMTP_PASS,SMTP_PORT,'autodetect',SMTP_TLS_SLL ? SMTP_TLS_SLL:false);
			//$mail->AddTo('ryo@wanadoo.fr', 'test1') or die("Erreur d'ajour de destinataire"); // pour les tests
			$mail->AddTo($row['email'], $row['nom']) or die("Erreur d'ajout de destinataire");
			$mail->From('no-reply@coopmcs.com');

			$mail->Html($html);
			if ($sent = $mail->Send($titre))
				echo now()." [SEND] $row[nom] : $type_doc\n";
			else
				echo now()." [NOT SEND] $row[nom] : $type_doc (".trim($mail->result).")\n";
		}
	} // foreach type de document
} // fin while artisan


function now() {
	return date("[Y-m-d H:i:s]");
}

?>