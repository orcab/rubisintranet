<?php
////////////////////// NOM DE LA SOCIÉTÉ ///////////////////////////////////////////////////////
define('SOCIETE','EDF');				$SOCIETE = SOCIETE ; // nom de la société

///////////////////// CONNEXION MYSQL //////////////////////////////////////////////////////////
define('MYSQL_HOST','localhost');		// hote de la base MySQL
define('MYSQL_USER','');				// utilisateur ayant les droits RW
define('MYSQL_PASS','');				// son mot de passe
define('MYSQL_BASE','');				// la base contenant les tables

///////////////////// CONNEXION LOGINOR /////////////////////////////////////////////////////////
define('LOGINOR_DSN','RUBIS');			$LOGINOR_DSN = LOGINOR_DSN ;						// nom du connecteur ODBC lié à Rubis
define('LOGINOR_USER','');			$LOGINOR_USER = LOGINOR_USER ;						// un login ayant les droits de lecture et ecriture
define('LOGINOR_PASS','');		$LOGINOR_PASS = LOGINOR_PASS ;						// son mot de passe
define('LOGINOR_PREFIX_BASE','');	$LOGINOR_PREFIX_BASE = LOGINOR_PREFIX_BASE ;		// le prefix de la base xxxGESTCOM ou xxxSTATCOM
define('LOGINOR_DEPOT','');			$LOGINOR_DEPOT = LOGINOR_DEPOT ;					// le nom du dépot
define('LOGINOR_AGENCE','');			$LOGINOR_AGENCE = LOGINOR_AGENCE ;					// le nom de l'agence
define('LOGINOR_PREFIX_SOCIETE','');	$LOGINOR_PREFIX_SOCIETE = LOGINOR_PREFIX_SOCIETE ;	// le préfix de la société (en général deux lettres)

////////////////////// SERVEUR SMTP POUR L'ENVOI DE MAIL ////////////////////////////////////////
define('SMTP_SERVEUR','smtp.wanadoo.fr');	// un serveur SMTP pour l'envoi de mail



/////////////////////// EDITION PDF /////////////////////////////////////////////////////////////
// LOGO a mettre dans le répertoire 'gfx', PNG ou JPEG uniquement
define('PDF_CDE_ADH_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_CDE_ADH_LOGO_HAUT_DROITE','logo_artipole.png');

define('PDF_CDE_FOURNISSEUR_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_CDE_FOURNISSEUR_LOGO_HAUT_DROITE','logo_orcab.png');

define('PDF_DEVIS_RUBIS_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_DEVIS_RUBIS_LOGO_HAUT_DROITE','logo_artipole.png');

define('PDF_DEVIS_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_DEVIS_LOGO_HAUT_DROITE','');

// texte d'entete et pied de page
// commande adhérent
define('PDF_CDE_ADH_PIED1',"Adresse de la société - Tél. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_CDE_ADH_PIED2',"La raison sociale de la société");

// commande fournisseur
define('PDF_CDE_FOURNISSEUR_ENTETE1',"Dépot à livrer :\nNom société\nAdresse 1\nAdresse 2\nCP VILLE");
define('PDF_CDE_FOURNISSEUR_PIED1',"Adresse de la société - Tél. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_CDE_FOURNISSEUR_PIED2',"La raison sociale de la société");

// devis venant de rubis
define('PDF_DEVIS_RUBIS_PIED1',"Adresse de la société - Tél. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_DEVIS_RUBIS_PIED2',"La raison sociale de la société");

// devis salle expo
define('PDF_DEVIS_ENTETE1',"$SOCIETE vous accueille sur RENDEZ-VOUS au 02.97.45.45.45");
define('PDF_DEVIS_ENTETE2',"du lundi au vendredi de 9h à 12h - 13h30 à 18h et le samedi de 9h à 12h30 - 13h45 à 18h");
define('PDF_DEVIS_PIED1',"Adresse de la société - Tél. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_DEVIS_PIED2',"La raison sociale de la société");
define('PDF_DEVIS_PRIX_NET1',"Ce devis est valable 2 mois à compter de sa date d'émission. Certains produits peuvent être soumis à l'éco-contribution. Les nuances des marchandises exposées ne peuvent être qu'indicatives.");
define('PDF_DEVIS_PRIX_NET2',"Les prix nets sont donnés à titre indicatif et calculés à partir d'un coefficient de 1,50. Pour obtenir les prix exacts contacter $SOCIETE.");
define('PDF_DEVIS_PRIX_PUBLIC1',"Ce devis est valable 2 mois à compter de sa date d'émission. Certains produits peuvent être soumis à l'éco-contribution. Les dimensions seront à vérifier par votre installateur. Les prix indiqués sur ce devis sont indicatifs et révisables suivant les fluctuations économiques. Les prix s'entendent hors pose. Les nuances des marchandises exposées ne peuvent être qu'indicatives.");
define('PDF_DEVIS_GAMME1',"Certains produits peuvent être sousmis à l'éco-contribution. Les prix indiqués sur ce devis sont indicatifs et révisables suivant les fluctuations économiques. Les nuances des marchandises exposées ne peuvent être qu'indicatives.");



///////////////////////// DEVIS EXPO ///////////////////////////////////////////////////////
define('NOMBRE_DE_LIGNE',60);
define('JOUR_MAX_RELANCE_DEVIS',30) ; // apres 10 jours on relance le devis

// devis expo, adresse ip pouvant afficher les prix NET Adhérent
if (isset($_SERVER['REMOTE_ADDR']))
	define('PEUX_AFFICHER_PRIX_NET_EXPO', $_SERVER['REMOTE_ADDR']=='addr_ip1' || $_SERVER['REMOTE_ADDR']=='addr_ip2' || $_SERVER['REMOTE_ADDR']=='addr_ip13');

// acces au server FTP gérant les rendez-vous de salle expo
define('FTP_RDV_HOST','adr_ip');	// l'ip du serveur gérant les fichier ics pour les rendez-vous.
define('FTP_RDV_USER','');			// un utlisateur avec les droits de lecture
define('FTP_RDV_PASS','');			// son mot de passe



include_once('constant.php');


///////////////////////// POLES ///////////////////////////////////////////////////////
// définition des chef de pole. Il faut les mettre après l'appel de "constant.php"
$CHEFS_DE_POLE = array();
$CHEFS_DE_POLE[POLE_LOGISTIQUE]		= array('email'=>'toto@toto.com',	'nom'=>'Toto' ) ;
$CHEFS_DE_POLE[POLE_COMMERCE]		= array('email'=>'toto@toto.com',	'nom'=>'Tata' ) ;
$CHEFS_DE_POLE[POLE_EXPOSITION]		= array('email'=>'toto@toto.com',	'nom'=>'Titi' ) ;
$CHEFS_DE_POLE[POLE_ADMINISTRATIF]	= array('email'=>'toto@toto.com',	'nom'=>'Riri' ) ;
$CHEFS_DE_POLE[POLE_INFORMATIQUE]	= array('email'=>'toto@toto.com',	'nom'=>'Fifi' ) ;
$CHEFS_DE_POLE[POLE_AUTRE]			= array('email'=>'toto@toto.com',	'nom'=>'Loulou' ) ;



//////////////////////// CREATION ARTICLE ////////////////////////////////////////////
// personne pouvant recevoir des email de demande de création d''article
$CREATION_ARTICLE = array(
		array('nom'=>"Riri"		, 'email'=>'toto@toto.com' ),
		array('nom'=>"Fifi"		, 'email'=>'toto@toto.com' ),
		array('nom'=>"Loulou"	, 'email'=>'toto@toto.com' ),
	);


//////////////////////// TARIF ///////////////////////////////////////////////////////
define('TARIF_EQUIPE','equipe_mcs.png'); // fichier pour les contacts en fin de tarif (jpeg ou png)
define('TARIF_ORGANIGRAMME','organigramme_mcs.png'); // fichier contenant l'organigramme de la societe (jpeg ou png)
?>