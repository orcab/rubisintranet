<?

include('../inc/config.php');
require_once '../inc/xpm2/smtp.php';
$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");


if (isset($_GET['what']) && $_GET['what'] == 'send_return_mail' &&
	isset($_GET['nobon']) && $_GET['nobon'] &&
	isset($_GET['nomcli']) && $_GET['nomcli'] &&
	isset($_GET['email']) && $_GET['email']) {
	
	$html = "Le mat&eacute;riel concernant le bon <b>$_GET[nobon]</b> de l'artisan <b>$_GET[nomcli]</b> est revenu et control&eacute;";

	$mail = new SMTP;
	$mail->Delivery('relay');
	$mail->Relay(SMTP_SERVEUR);
	$mail->From('isabelle.gerardin@coopmcs.com','Isabelle Gerardin');
	$mail->AddTo('benjamin.poulain@coopmcs.com') or die("Erreur d'ajout de destinataire");
	$mail->Html($html);

	if ($mail->Send("Retour de marchandise : $_GET[nobon]"))
		echo "{debug:'email envoyé'}";
	else
		echo "{debug:'erreur : email pas envoyé'}";
}

// CAS PAR DEFAUT
else {
	echo "{debug:'Aucune procedure selectionnée'}";
}
?>