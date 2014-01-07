<?php

// pour les fichiers PDF
define('EURO',chr(128));

// droit d'utilisation --> STOCKER DANS LE CHAMPS DROIT DE LA TABLE EMPLOYE
define('PEUT_CREER_ARTICLE',				1 << 0);	$PEUT_CREER_ARTICLE					= PEUT_CREER_ARTICLE ;
define('PEUT_CREER_DEVIS',					1 << 1);	$PEUT_CREER_DEVIS					= PEUT_CREER_DEVIS ;
define('PEUT_ASSOCIER_CMD_AU_DEVIS',		1 << 2);	$PEUT_ASSOCIER_CMD_AU_DEVIS			= PEUT_ASSOCIER_CMD_AU_DEVIS ;
define('PEUT_DEPLACER_ARTICLE',				1 << 3);	$PEUT_DEPLACER_ARTICLE				= PEUT_DEPLACER_ARTICLE ;
define('PEUT_MODIFIER_ARTICLE',				1 << 4);	$PEUT_MODIFIER_ARTICLE				= PEUT_MODIFIER_ARTICLE ;
define('PEUT_MODIFIER_UTILISATEUR',			1 << 5);	$PEUT_MODIFIER_UTILISATEUR			= PEUT_MODIFIER_UTILISATEUR ;
define('PEUT_CHANGER_EDI',					1 << 6);	$PEUT_CHANGER_EDI					= PEUT_CHANGER_EDI ;
define('PEUT_EDITER_DEVIS_PRIX_ADH',		1 << 7);	$PEUT_EDITER_DEVIS_PRIX_ADH			= PEUT_EDITER_DEVIS_PRIX_ADH ;
define('PEUT_MODIFIER_FICHE_FOURNISSEUR',	1 << 8);	$PEUT_MODIFIER_FICHE_FOURNISSEUR	= PEUT_MODIFIER_FICHE_FOURNISSEUR ;
define('PEUT_MODIFIER_FICHE_ARTISAN',		1 << 9);	$PEUT_MODIFIER_FICHE_ARTISAN		= PEUT_MODIFIER_FICHE_ARTISAN ;
define('PEUT_MODIFIER_TYPE_PRODUIT',		1 << 10);	$PEUT_MODIFIER_TYPE_PRODUIT			= PEUT_MODIFIER_TYPE_PRODUIT ;
define('PEUT_EDITER_ARTICLE_EN_MASSE',		1 << 11);	$PEUT_EDITER_ARTICLE_EN_MASSE		= PEUT_EDITER_ARTICLE_EN_MASSE ;
define('PEUT_ENVOYER_LIGNE_A_REFLEX',		1 << 12);	$PEUT_ENVOYER_LIGNE_A_REFLEX		= PEUT_ENVOYER_LIGNE_A_REFLEX ;
define('PEUT_ENVOYER_DES_SMS',				1 << 13);	$PEUT_ENVOYER_DES_SMS				= PEUT_ENVOYER_DES_SMS ;

// anomalie --> gestion des poles
define('POLE_LOGISTIQUE'	,1 << 0);
define('POLE_COMMERCE'		,1 << 1);
define('POLE_EXPOSITION'	,1 << 2);
define('POLE_ADMINISTRATIF'	,1 << 3);
define('POLE_INFORMATIQUE'	,1 << 4);
define('POLE_LITIGE'		,1 << 5);
define('POLE_AUTRE'			,1 << 6);

// temps maximum en second dans lequel une anomalie peut etre modifié ou suprimée
define('MAX_TIME_ANOMALIE_DELETION', 3600 * 24 ); // 24h

// taux de TVA
define('TTC1',20);
define('TTC2',10);

// remise accordé pour le sefl service (web ou ecran tacile)
define('REMISE_WEB',1); // remise de 1%

// coef et marge pratiqué en salle expo
define('MARGE_COOP',22);				$MARGE_COOP=MARGE_COOP;
define('COEF_EXPO',1.5);				$COEF_EXPO=COEF_EXPO;
define('CATALFOU','catalfou.sqlite');	$CATALFOU=CATALFOU;


// structure du panier en session
define('CODE_ARTICLE',0);
define('QTE',1);
define('DESIGNATION',2);
define('FOURNISSEUR',3);
define('REF_FOURNISSEUR',4);
define('PRIX',5);
define('ACTIVITE',6);
define('CONDITIONNEMENT',7);
define('UNITE',8);
define('STOCK_AFA',9);
define('STOCK_AFL',10);

// jour de la semaine en FR
$jours_mini = array('Dim','Lun','Mar','Mer','Jeu','Ven','Sam');

// Tournée des chauffeurs
$tournee_chauffeur = array(
	'124' =>	array(	'1' => 'PHILIPPE',
						'2' => 'LAURENT',
						'4' => 'GILLES'
				),
	'134' =>	array(	'1' => 'GILLES',
						'3' => 'PHILIPPE',
						'4' => 'LAURENT'
				),
	'135' =>	array(	'1' => 'LAURENT',
						'3' => 'GILLES',
						'5' => 'PHILIPPE'
				),
	'235' =>	array(	'2' => 'PHILIPPE',
						'3' => 'LAURENT',
						'5' => 'GILLES'
				),
	'245' =>	array(	'2' => 'GILLES',
						'4' => 'PHILIPPE',
						'5' => 'LAURENT'
				)
);

$login_chauffeur = array(
	'PHILIPPE'		=>'AFPR',
	'LAURENT'		=>'AFLH',
	'GILLES'		=>'AFGH',
	'CHRISTOPHE'	=>'AFCLM',
	'Non définit'	=>'AF'
);

function e($val,$tableau) {
	return $tableau[$val];
}

function recuperer_droit() {
	return e('droit',mysql_fetch_array(mysql_query("SELECT droit FROM employe WHERE ip='$_SERVER[REMOTE_ADDR]'")));
}

function devis_log($action='', $id_devis=0, $sql='', $complement='') {
	$sql = "INSERT INTO devis_log (date_action,action,ip,cle,complement,`sql`) VALUES (NOW(),'".mysql_escape_string($action)."','".mysql_escape_string($_SERVER['REMOTE_ADDR'])."','".mysql_escape_string($id_devis)."','".mysql_escape_string($complement)."','".mysql_escape_string($sql)."')";
	mysql_query($sql) or die("ne peux pas inserer le log ".mysql_error()." <br>$sql");
}

function my_utf8_decode($string) { // try to convert string (pseudo utf8) to iso8859-1
	$tmp = $string;
	$tmp = str_replace('Ã§','ç',$tmp);
	$tmp = str_replace('ä§','ç',$tmp);
	$tmp = str_replace('Ã©','é',$tmp);
	$tmp = str_replace('ä©','é',$tmp);
	$tmp = str_replace('Ã¨','è',$tmp);
	$tmp = str_replace('ä¨','è',$tmp);
	$tmp = str_replace('Ãª','ê',$tmp);
	$tmp = str_replace('äª','ê',$tmp);
	$tmp = str_replace('Ã«','ë',$tmp);
	$tmp = str_replace('ä«','ë',$tmp);
	$tmp = str_replace('Ã‰','É',$tmp);
	$tmp = str_replace('Ã?','Ê',$tmp);
	$tmp = str_replace('ä?','Ê',$tmp);
	$tmp = str_replace('Ã?','Ë',$tmp);
	$tmp = str_replace('ä?','Ë',$tmp);
	$tmp = str_replace('Ã®','î',$tmp);
	$tmp = str_replace('ä®','î',$tmp);
	$tmp = str_replace('Ã¯','ï',$tmp);
	$tmp = str_replace('ä¯','ï',$tmp);
	$tmp = str_replace('Ã¬','ì',$tmp);
	$tmp = str_replace('Ã?','Î',$tmp);
	$tmp = str_replace('ä?','Î',$tmp);
	$tmp = str_replace('Ã²','ò',$tmp);
	$tmp = str_replace('ä²','ò',$tmp);
	$tmp = str_replace('Ã´','ô',$tmp);
	$tmp = str_replace('ä´','ô',$tmp);
	$tmp = str_replace('Ã¶','ö',$tmp);
	$tmp = str_replace('ä¶','ö',$tmp);
	$tmp = str_replace('Ãµ','õ',$tmp);
	$tmp = str_replace('Ã³','ó',$tmp);
	$tmp = str_replace('Ã¸','ø',$tmp);
	$tmp = str_replace('äµ','õ',$tmp);
	$tmp = str_replace('ä³','ó',$tmp);
	$tmp = str_replace('ä¸','ø',$tmp);
	$tmp = str_replace('Ã?','Ô',$tmp);
	$tmp = str_replace('ä?','Ô',$tmp);
	$tmp = str_replace('Ã?','Ö',$tmp);
	$tmp = str_replace('ä?','Ö',$tmp);
	$tmp = str_replace('Ã ','à',$tmp);
	$tmp = str_replace('ä ','à',$tmp);
	$tmp = str_replace('Ã¢','â',$tmp);
	$tmp = str_replace('ä¢','â',$tmp);
	$tmp = str_replace('Ã¤','ä',$tmp);
	$tmp = str_replace('ä¤','ä',$tmp);
	$tmp = str_replace('Ã¥','å',$tmp);
	$tmp = str_replace('ä¥','å',$tmp);
	$tmp = str_replace('Ã?','Â',$tmp);
	$tmp = str_replace('ä?','Â',$tmp);
	$tmp = str_replace('Ã?','Ä',$tmp);
	$tmp = str_replace('ä?','Ä',$tmp);
	$tmp = str_replace('Ã¹','u',$tmp);
	$tmp = str_replace('Ã»','û',$tmp);
	$tmp = str_replace('Ã¼','ü',$tmp);
	$tmp = str_replace('ä¼','ü',$tmp);
	$tmp = str_replace('Ã?','Û',$tmp);
	$tmp = str_replace('Ã?','Ü',$tmp);
	$tmp = str_replace('ä¹','u',$tmp);
	$tmp = str_replace('ä»','û',$tmp);
	$tmp = str_replace('ä¼','ü',$tmp);
	$tmp = str_replace('ä¼','ü',$tmp);
	$tmp = str_replace('ä?','Û',$tmp);
	$tmp = str_replace('ä?','Ü',$tmp);
	$tmp = str_replace('Ã²','ñ',$tmp);
	$tmp = str_replace('Ã±','ñ',$tmp);
	$tmp = str_replace('Â°','°',$tmp);
	return $tmp;
}

function select_vendeur() {
	$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code,groupe FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
	$tmp = array();
	$groupes = array();
	while($row = mysql_fetch_array($res)) {
		$tmp[$row['code']] = $row['prenom'];

		foreach (explode(',',$row['groupe']) as $g) { // on construit un tableau des groupes
			$g = trim($g) ;
			if($g) // si un groupe précisé
				if (array_key_exists($g,$groupes) && is_array($groupes[$g])) // deja un tableau --> on push le code vendeur
					array_push($groupes[$g],$row['code']);
				else						// pas encore un tableau, on le cree avec le code vendeur
					$groupes[$g] = array($row['code']);
		}
	}
	
	$tmp['WEB'] = 'Web';
	$tmp['MAR'] = 'Marc';			array_push($groupes['chauffage'],'MAR');
	$tmp['LG']  = 'Laurent G.';		array_push($groupes['chauffage'],'LG');
	$tmp['JFS'] = 'Jean Francois';	array_push($groupes['plomberie'],'JFS'); array_push($groupes['sanitaire'],'JFS');
	ksort($tmp);

	// creation des groupes de vendeurs
	$vendeurs = array();
	foreach ($groupes as $groupe_name=>$members) {
		$vendeurs[join(',',$members)] = ucfirst($groupe_name);
	}
	
	return array_merge($vendeurs,$tmp);
}

function get_email_vendeur() {
	$res = mysql_query("SELECT UCASE(code_vendeur) AS code,email FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
	$tmp = array();
	while($row = mysql_fetch_array($res)) {
		$tmp[$row['code']] = $row['email'];
	}
	return $tmp;
}


function is_ean13($ean13) {
	if (strlen($ean13) != 13) return false; // le code-barres doit contenir 13 caractères
	if (!is_numeric($ean13)) return false; // le code-barres ne doit contenir que des chiffres
	$sum = 0;
	for ($index = 0; $index < 12; $index ++) {
		$number = (int) $ean13[$index];
		if (($index % 2) != 0) $number *= 3;
		$sum += $number;
	}
	$key = $ean13[12]; // clé de contrôle égale au dernier chiffre

	if (10 - ($sum % 10) != $key)
		return false;
	else
		return true;
}


function pas_identifie() {
	echo "ERREUR. Vous n'&ecirc;tes pas identifi&eacute;.";
	exit;
}

function getUserFromIp($ip) {
	$res = mysql_query("SELECT prenom,nom FROM employe WHERE ip='".mysql_escape_string($ip)."' LIMIT 0,1");
	return mysql_fetch_array($res);
}

function convertLatin1ToHtml($str) {
    $html_entities = array (
        '&' =>  '&amp;',     #ampersand  
        'á' =>  '&aacute;',     #latin small letter a
        'Â' =>  '&Acirc;',     #latin capital letter A
        'â' =>  '&acirc;',     #latin small letter a
        'Æ' =>  '&AElig;',     #latin capital letter AE
        'æ' =>  '&aelig;',     #latin small letter ae
        'À' =>  '&Agrave;',     #latin capital letter A
        'à' =>  '&agrave;',     #latin small letter a
        'Å' =>  '&Aring;',     #latin capital letter A
        'å' =>  '&aring;',     #latin small letter a
        'Ã' =>  '&Atilde;',     #latin capital letter A
        'ã' =>  '&atilde;',     #latin small letter a
        'Ä' =>  '&Auml;',     #latin capital letter A
        'ä' =>  '&auml;',     #latin small letter a
        'Ç' =>  '&Ccedil;',     #latin capital letter C
        'ç' =>  '&ccedil;',     #latin small letter c
        'É' =>  '&Eacute;',     #latin capital letter E
        'é' =>  '&eacute;',     #latin small letter e
        'Ê' =>  '&Ecirc;',     #latin capital letter E
        'ê' =>  '&ecirc;',     #latin small letter e
        'È' =>  '&Egrave;',     #latin capital letter E
        'û' =>  '&ucirc;',     #latin small letter u
        'Ù' =>  '&Ugrave;',     #latin capital letter U
        'ù' =>  '&ugrave;',     #latin small letter u
        'Ü' =>  '&Uuml;',     #latin capital letter U
        'ü' =>  '&uuml;',     #latin small letter u
        'Ý' =>  '&Yacute;',     #latin capital letter Y
        'ý' =>  '&yacute;',     #latin small letter y
        'ÿ' =>  '&yuml;',     #latin small letter y
        'Ÿ' =>  '&Yuml;',     #latin capital letter Y
		'°' =>  '&ordm;',
		'¼' =>  '&frac14;',
		'½' =>  '&frac12;',
		'¾' =>  '&frac34;',
		'²' =>  '&sup2;',
		'³' =>  '&sup3;',
		'€' =>  '&euro;'
    );

    foreach ($html_entities as $key => $value)
        $str = str_replace($key, $value, $str);

    return $str;
}


function getCellularPhoneNumberFromArtisan($numero_artisan) {
	global $mysql,$database;
	$res = mysql_query("SELECT * FROM artisan where categorie='1' and suspendu=0 and numero='".mysql_escape_string($numero_artisan)."' ORDER BY nom ASC") or die("ne peux pas retrouver les infos de l'artisan ".mysql_error());
	$row = mysql_fetch_array($res) ;
	$phone_number = '';

	for($i=1 ; $i<=4 ; $i++) // pour les 4 numero de tel
		if (preg_match('/^\s*0\s*[67]/',$row['tel'.$i])) { // on regarde celui qui commence par 06 ou 07
			$phone_number = preg_replace('/[^0-9]/','',$row['tel'.$i]); // supprime tout ce qui n'est pas un chiffre
			break;
		}

	return $phone_number;
}

// envoi un sms via la passerrelle
function sendSMS($phone_number,$text) {
	if ($text && $phone_number) {
		$reponse = join('',file(SMS_GATEWAY."phone=$phone_number&text=".rawurlencode($text)));
		if (preg_match('/Mesage\s+SENT\s*!/i',$reponse))
			return true;
		else
			return false;
	} else {
			return false;
	}
}


// détermine si un article est remisé ou non en fonction de son depot de retrait
function remiseArticle($ligne_article_panier,$code_user) {
	$remise = 0;

	if (isset($_POST['type_livraison']) && $_POST['type_livraison'] == 'caudan') {
		if ($ligne_article_panier[STOCK_AFL] != '')
			$remise = REMISE_WEB ; // si retrait sur caudan et produit stocké sur Caudan --> remise
	} else {
		if ($ligne_article_panier[STOCK_AFA] != '')
			$remise = REMISE_WEB ; // si retrait ou livraison sur plescop et produit stocké sur plescop --> remise
	}

	if (getCategorieUser($code_user) != 1) { // si le client n'est pas assujeti au remise
		$remise = 0;
	}

	return $remise;
}

// recupere la catégorie RUBIS d'un utilisateur
function getCategorieUser($username) {
	global $mysql,$database;

	if ($_SERVER['SERVER_ADDR'] == '10.211.14.46') { // test local
		return 1; // categ aristan
	}

/*	$db_prefix = $joomla_config->dbprefix;
	$mysql    = mysql_connect($joomla_config->host, $joomla_config->user, $joomla_config->password) or die("Impossible de se connecter");
	$database = mysql_select_db($joomla_config->db) or die("Impossible de se choisir la base");
*/
	$res = mysql_query("select categorie from artisan where numero='".mysql_escape_string($username)."'") or die("Ne peux pas trouver les infos user ".mysql_error());
	return e('categorie',mysql_fetch_array($res));
}

?>