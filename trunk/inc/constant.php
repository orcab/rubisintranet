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

// anomalie --> gestion des poles
define('POLE_LOGISTIQUE'	,1 << 0);
define('POLE_COMMERCE'		,1 << 1);
define('POLE_EXPOSITION'	,1 << 2);
define('POLE_ADMINISTRATIF'	,1 << 3);
define('POLE_INFORMATIQUE'	,1 << 4);
define('POLE_LITIGE'		,1 << 5);
define('POLE_AUTRE'			,1 << 6);

// temps maximum en second dans lequel une anomalie peut etre modifi� ou suprim�e
define('MAX_TIME_ANOMALIE_DELETION', 3600 * 24 ); // 24h

// taux de TVA
define('TTC1',19.6);
define('TTC2',7);


// coef et marge pratiqu� en salle expo
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


// jour de la semaine en FR
$jours_mini = array('Dim','Lun','Mar','Mer','Jeu','Ven','Sam');

// Tourn�e des chauffeurs
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
	'Non d�finit'	=>'AF'
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
	$tmp = str_replace('ç','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('é','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('è','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ê','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ë','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('É','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('î','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ï','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ì','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('ò','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ô','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ö','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('õ','�',$tmp);
	$tmp = str_replace('ó','�',$tmp);
	$tmp = str_replace('ø','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('� ','�',$tmp);
	$tmp = str_replace('� ','�',$tmp);
	$tmp = str_replace('â','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('ä','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('å','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('ù','u',$tmp);
	$tmp = str_replace('û','�',$tmp);
	$tmp = str_replace('ü','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�','u',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('�?','�',$tmp);
	$tmp = str_replace('ò','�',$tmp);
	$tmp = str_replace('ñ','�',$tmp);
	$tmp = str_replace('°','�',$tmp);
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
			if($g) // si un groupe pr�cis�
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
	if (strlen($ean13) != 13) return false; // le code-barres doit contenir 13 caract�res
	if (!is_numeric($ean13)) return false; // le code-barres ne doit contenir que des chiffres
	$sum = 0;
	for ($index = 0; $index < 12; $index ++) {
		$number = (int) $ean13[$index];
		if (($index % 2) != 0) $number *= 3;
		$sum += $number;
	}
	$key = $ean13[12]; // cl� de contr�le �gale au dernier chiffre

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
        '�' =>  '&aacute;',     #latin small letter a
        '�' =>  '&Acirc;',     #latin capital letter A
        '�' =>  '&acirc;',     #latin small letter a
        '�' =>  '&AElig;',     #latin capital letter AE
        '�' =>  '&aelig;',     #latin small letter ae
        '�' =>  '&Agrave;',     #latin capital letter A
        '�' =>  '&agrave;',     #latin small letter a
        '�' =>  '&Aring;',     #latin capital letter A
        '�' =>  '&aring;',     #latin small letter a
        '�' =>  '&Atilde;',     #latin capital letter A
        '�' =>  '&atilde;',     #latin small letter a
        '�' =>  '&Auml;',     #latin capital letter A
        '�' =>  '&auml;',     #latin small letter a
        '�' =>  '&Ccedil;',     #latin capital letter C
        '�' =>  '&ccedil;',     #latin small letter c
        '�' =>  '&Eacute;',     #latin capital letter E
        '�' =>  '&eacute;',     #latin small letter e
        '�' =>  '&Ecirc;',     #latin capital letter E
        '�' =>  '&ecirc;',     #latin small letter e
        '�' =>  '&Egrave;',     #latin capital letter E
        '�' =>  '&ucirc;',     #latin small letter u
        '�' =>  '&Ugrave;',     #latin capital letter U
        '�' =>  '&ugrave;',     #latin small letter u
        '�' =>  '&Uuml;',     #latin capital letter U
        '�' =>  '&uuml;',     #latin small letter u
        '�' =>  '&Yacute;',     #latin capital letter Y
        '�' =>  '&yacute;',     #latin small letter y
        '�' =>  '&yuml;',     #latin small letter y
        '�' =>  '&Yuml;',     #latin capital letter Y
		'�' =>  '&ordm;',
		'�' =>  '&frac14;',
		'�' =>  '&frac12;',
		'�' =>  '&frac34;',
		'�' =>  '&sup2;',
		'�' =>  '&sup3;',
		'�' =>  '&euro;'
    );

    foreach ($html_entities as $key => $value)
        $str = str_replace($key, $value, $str);

    return $str;
}

?>