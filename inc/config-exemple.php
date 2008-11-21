<?php
////////////////////// NOM DE LA SOCI�T� ///////////////////////////////////////////////////////
define('SOCIETE','EDF');				$SOCIETE = SOCIETE ; // nom de la soci�t�

///////////////////// CONNEXION MYSQL //////////////////////////////////////////////////////////
define('MYSQL_HOST','localhost');		// hote de la base MySQL
define('MYSQL_USER','');				// utilisateur ayant les droits RW
define('MYSQL_PASS','');				// son mot de passe
define('MYSQL_BASE','');				// la base contenant les tables

///////////////////// CONNEXION LOGINOR /////////////////////////////////////////////////////////
define('LOGINOR_DSN','RUBIS');			$LOGINOR_DSN = LOGINOR_DSN ;						// nom du connecteur ODBC li� � Rubis
define('LOGINOR_USER','');			$LOGINOR_USER = LOGINOR_USER ;						// un login ayant les droits de lecture et ecriture
define('LOGINOR_PASS','');		$LOGINOR_PASS = LOGINOR_PASS ;						// son mot de passe
define('LOGINOR_PREFIX_BASE','');	$LOGINOR_PREFIX_BASE = LOGINOR_PREFIX_BASE ;		// le prefix de la base xxxGESTCOM ou xxxSTATCOM
define('LOGINOR_DEPOT','');			$LOGINOR_DEPOT = LOGINOR_DEPOT ;					// le nom du d�pot
define('LOGINOR_AGENCE','');			$LOGINOR_AGENCE = LOGINOR_AGENCE ;					// le nom de l'agence
define('LOGINOR_PREFIX_SOCIETE','');	$LOGINOR_PREFIX_SOCIETE = LOGINOR_PREFIX_SOCIETE ;	// le pr�fix de la soci�t� (en g�n�ral deux lettres)

////////////////////// SERVEUR SMTP POUR L'ENVOI DE MAIL ////////////////////////////////////////
define('SMTP_SERVEUR','smtp.wanadoo.fr');	// un serveur SMTP pour l'envoi de mail



/////////////////////// EDITION PDF /////////////////////////////////////////////////////////////
// LOGO a mettre dans le r�pertoire 'gfx', PNG ou JPEG uniquement
define('PDF_CDE_ADH_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_CDE_ADH_LOGO_HAUT_DROITE','logo_artipole.png');

define('PDF_CDE_FOURNISSEUR_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_CDE_FOURNISSEUR_LOGO_HAUT_DROITE','logo_orcab.png');

define('PDF_DEVIS_RUBIS_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_DEVIS_RUBIS_LOGO_HAUT_DROITE','logo_artipole.png');

define('PDF_DEVIS_LOGO_HAUT_GAUCHE','logo_mcs.png');
define('PDF_DEVIS_LOGO_HAUT_DROITE','');

// texte d'entete et pied de page
// commande adh�rent
define('PDF_CDE_ADH_PIED1',"Adresse de la soci�t� - T�l. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_CDE_ADH_PIED2',"La raison sociale de la soci�t�");

// commande fournisseur
define('PDF_CDE_FOURNISSEUR_ENTETE1',"D�pot � livrer :\nNom soci�t�\nAdresse 1\nAdresse 2\nCP VILLE");
define('PDF_CDE_FOURNISSEUR_PIED1',"Adresse de la soci�t� - T�l. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_CDE_FOURNISSEUR_PIED2',"La raison sociale de la soci�t�");

// devis venant de rubis
define('PDF_DEVIS_RUBIS_PIED1',"Adresse de la soci�t� - T�l. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_DEVIS_RUBIS_PIED2',"La raison sociale de la soci�t�");

// devis salle expo
define('PDF_DEVIS_ENTETE1',"$SOCIETE vous accueille sur RENDEZ-VOUS au 02.97.45.45.45");
define('PDF_DEVIS_ENTETE2',"du lundi au vendredi de 9h � 12h - 13h30 � 18h et le samedi de 9h � 12h30 - 13h45 � 18h");
define('PDF_DEVIS_PIED1',"Adresse de la soci�t� - T�l. 02 97 45 45 45 - Fax 02 97 45 45 46");
define('PDF_DEVIS_PIED2',"La raison sociale de la soci�t�");
define('PDF_DEVIS_PRIX_NET1',"Ce devis est valable 2 mois � compter de sa date d'�mission. Certains produits peuvent �tre soumis � l'�co-contribution. Les nuances des marchandises expos�es ne peuvent �tre qu'indicatives.");
define('PDF_DEVIS_PRIX_NET2',"Les prix nets sont donn�s � titre indicatif et calcul�s � partir d'un coefficient de 1,50. Pour obtenir les prix exacts contacter $SOCIETE.");
define('PDF_DEVIS_PRIX_PUBLIC1',"Ce devis est valable 2 mois � compter de sa date d'�mission. Certains produits peuvent �tre soumis � l'�co-contribution. Les dimensions seront � v�rifier par votre installateur. Les prix indiqu�s sur ce devis sont indicatifs et r�visables suivant les fluctuations �conomiques. Les prix s'entendent hors pose. Les nuances des marchandises expos�es ne peuvent �tre qu'indicatives.");
define('PDF_DEVIS_GAMME1',"Certains produits peuvent �tre sousmis � l'�co-contribution. Les prix indiqu�s sur ce devis sont indicatifs et r�visables suivant les fluctuations �conomiques. Les nuances des marchandises expos�es ne peuvent �tre qu'indicatives.");



///////////////////////// DEVIS EXPO ///////////////////////////////////////////////////////
define('NOMBRE_DE_LIGNE',60);
define('JOUR_MAX_RELANCE_DEVIS',30) ; // apres 10 jours on relance le devis

// devis expo, adresse ip pouvant afficher les prix NET Adh�rent
if (isset($_SERVER['REMOTE_ADDR']))
	define('PEUX_AFFICHER_PRIX_NET_EXPO', $_SERVER['REMOTE_ADDR']=='addr_ip1' || $_SERVER['REMOTE_ADDR']=='addr_ip2' || $_SERVER['REMOTE_ADDR']=='addr_ip13');

// acces au server FTP g�rant les rendez-vous de salle expo
define('FTP_RDV_HOST','adr_ip');	// l'ip du serveur g�rant les fichier ics pour les rendez-vous.
define('FTP_RDV_USER','');			// un utlisateur avec les droits de lecture
define('FTP_RDV_PASS','');			// son mot de passe



include_once('constant.php');


///////////////////////// POLES ///////////////////////////////////////////////////////
// d�finition des chef de pole. Il faut les mettre apr�s l'appel de "constant.php"
$CHEFS_DE_POLE = array();
$CHEFS_DE_POLE[POLE_LOGISTIQUE]		= array('email'=>'toto@toto.com',	'nom'=>'Toto' ) ;
$CHEFS_DE_POLE[POLE_COMMERCE]		= array('email'=>'toto@toto.com',	'nom'=>'Tata' ) ;
$CHEFS_DE_POLE[POLE_EXPOSITION]		= array('email'=>'toto@toto.com',	'nom'=>'Titi' ) ;
$CHEFS_DE_POLE[POLE_ADMINISTRATIF]	= array('email'=>'toto@toto.com',	'nom'=>'Riri' ) ;
$CHEFS_DE_POLE[POLE_INFORMATIQUE]	= array('email'=>'toto@toto.com',	'nom'=>'Fifi' ) ;
$CHEFS_DE_POLE[POLE_AUTRE]			= array('email'=>'toto@toto.com',	'nom'=>'Loulou' ) ;



//////////////////////// CREATION ARTICLE ////////////////////////////////////////////
// personne pouvant recevoir des email de demande de cr�ation d''article
$CREATION_ARTICLE = array(
		array('nom'=>"Riri"		, 'email'=>'toto@toto.com' ),
		array('nom'=>"Fifi"		, 'email'=>'toto@toto.com' ),
		array('nom'=>"Loulou"	, 'email'=>'toto@toto.com' ),
	);


//////////////////////// TARIF ///////////////////////////////////////////////////////
define('TARIF_EQUIPE','equipe_mcs.png'); // fichier pour les contacts en fin de tarif (jpeg ou png)
define('TARIF_ORGANIGRAMME','organigramme_mcs.png'); // fichier contenant l'organigramme de la societe (jpeg ou png)
?>