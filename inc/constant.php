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

// anomalie --> gestion des poles
define('POLE_LOGISTIQUE'	,1 << 0);
define('POLE_COMMERCE'		,1 << 1);
define('POLE_EXPOSITION'	,1 << 2);
define('POLE_ADMINISTRATIF'	,1 << 3);
define('POLE_INFORMATIQUE'	,1 << 4);
define('POLE_LITIGE'		,1 << 5);
define('POLE_AUTRE'			,1 << 6);

// telmps maximum en second dans lequel une anomalie peut etre modifié ou suprimée
define('MAX_TIME_ANOMALIE_DELETION', 3600 * 24 ); // 24h

// taux de TVA
define('TTC1',19.6);
define('TTC2',5.5);


// coef et marge pratiqué en salle expo
define('MARGE_COOP',21);	$MARGE_COOP=MARGE_COOP;
define('COEF_EXPO',1.5);	$COEF_EXPO=COEF_EXPO;


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
	$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
	$tmp = array();
	while($row = mysql_fetch_array($res)) {
		$tmp[$row['code']] = $row['prenom'];
	}
	$tmp['LN'] = 'Jean René';
	$tmp['MAR'] = 'Marc';
	$tmp['LG'] = 'Laurent G';
	ksort($tmp);

	$vendeurs = array();
	$vendeurs['AM,LG,RLF,MAR,CG']   = 'Chauffage';
	$vendeurs['AG,CLM,JFS,JM,LN']   = 'Sanitaire';
	$vendeurs['BT,CLH,ELM,JLD,SLN,VN,YC'] = 'Electricité';
	return array_merge($vendeurs,$tmp);
}

?>