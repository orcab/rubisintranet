<?php
////////////////////// NOM DE LA SOCI�T� ///////////////////////////////////////////////////////
define('SOCIETE','MCS');							$SOCIETE = SOCIETE ; // nom de la soci�t�

///////////////////// CONNEXION LOGINOR /////////////////////////////////////////////////////////
define('LOGINOR_DSN','RUBIS');						$LOGINOR_DSN					= LOGINOR_DSN ;				// nom du connecteur ODBC li� � Rubis
define('LOGINOR_USER','AFBP');						$LOGINOR_USER					= LOGINOR_USER ;			// un login ayant les droits de lecture et ecriture
define('LOGINOR_PASS','v7bp3ki2');					$LOGINOR_PASS					= LOGINOR_PASS ;			// son mot de passe
define('LOGINOR_PREFIX_BASE_PROD','AFA');			$LOGINOR_PREFIX_BASE_PROD		= LOGINOR_PREFIX_BASE_PROD ;// le prefix de la base xxxGESTCOM ou xxxSTATCOM
define('LOGINOR_PREFIX_BASE_TEST','AFZ');			$LOGINOR_PREFIX_BASE_TEST		= LOGINOR_PREFIX_BASE_TEST ;// le prefix de la base xxxGESTCOM ou xxxSTATCOM
define('LOGINOR_PREFIX_SOCIETE','AF');				$LOGINOR_PREFIX_SOCIETE 		= LOGINOR_PREFIX_SOCIETE ;	// le pr�fix de la soci�t� (en g�n�ral deux lettres)

define('REFLEX_DSN','reflex');						$REFLEX_DSN						= REFLEX_DSN ;				// nom du connecteur ODBC li� � reflex
define('REFLEX_USER','reflex');						$REFLEX_USER					= REFLEX_USER ;				// un login ayant les droits de lecture et ecriture
define('REFLEX_PASS','reflex');						$REFLEX_PASS					= REFLEX_PASS ;				// son mot de passe
define('REFLEX_PREFIX_BASE','RFXPRODDTA.reflex');	$REFLEX_PREFIX_BASE 			= REFLEX_PREFIX_BASE ;		// le prefix de la base REFLEX de prod
define('REFLEX_PREFIX_BASE_TEST','RFXTESTDTA.reflex');	$REFLEX_PREFIX_BASE_TEST 	= REFLEX_PREFIX_BASE_TEST ;	// le prefix de la base REFLEX de test

define('PRODUCTION_DIRECTORY_PROD','q:/');			$PRODUCTION_DIRECTORY_PROD 		= PRODUCTION_DIRECTORY_PROD;// PROD le pr�fix de la soci�t� (en g�n�ral deux lettres)
define('PRODUCTION_DIRECTORY_TEST','r:/');			$PRODUCTION_DIRECTORY_TEST 		= PRODUCTION_DIRECTORY_TEST;// TEST le pr�fix de la soci�t� (en g�n�ral deux lettres)

define('DAYS_UNTIL_LAST_ARTICLE_MODIF','3');		$DAYS_UNTIL_LAST_ARTICLE_MODIF 	= DAYS_UNTIL_LAST_ARTICLE_MODIF;// nomber de jour depuis la derniere modif article

define('FTP_HOST','10.211.200.1');					$FTP_HOST						= FTP_HOST;
define('FTP_USER','AFREFLEX');						$FTP_USER						= FTP_USER;
define('FTP_PASS','INTREF$');						$FTP_PASS						= FTP_PASS;
define('FTP_PATH','/AF/AFA/REFLEX/R54');			$FTP_PATH						= FTP_PATH;
define('FTP_PATH_TEST','/AF/AFZ/REFLEX/R54');		$FTP_PATH_TEST					= FTP_PATH_TEST;

define('SMS_GATEWAY','http://10.211.14.248:9090/sendsms?');


////////////////////// SERVEUR SMTP POUR L'ENVOI DE MAIL ////////////////////////////////////////
define('SMTP_SERVEUR','ns0.ovh.net');	// un serveur SMTP pour l'envoi de mail
//define('SMTP_SERVEUR','smtp.wanadoo.fr');	// un serveur SMTP pour l'envoi de mail
define('SMTP_USER','benjamin.poulain%coopmcs.com'); // le user sur un SMTP prot�g�
define('SMTP_PASS','v7bp3ki2'); // le password sur un SMTP prot�g�
define('SMTP_PORT',587); // le port SMTP
define('SMTP_TLS_SLL',''); // Utilis� TLS ou SSL ou rien ?
?>