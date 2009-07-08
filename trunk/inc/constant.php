<?php

// pour les fichiers PDF
define('EURO',chr(128));

// droit d'utilisation --> STOCKER DANS LE CHAMPS DROIT DE LA TABLE EMPLOYE
define('PEUT_CREER_ARTICLE',		1 << 0);	$PEUT_CREER_ARTICLE			= PEUT_CREER_ARTICLE ;
define('PEUT_CREER_DEVIS',			1 << 1);	$PEUT_CREER_DEVIS			= PEUT_CREER_DEVIS ;
define('PEUT_ASSOCIER_CMD_AU_DEVIS',1 << 2);	$PEUT_ASSOCIER_CMD_AU_DEVIS = PEUT_ASSOCIER_CMD_AU_DEVIS ;
define('PEUT_DEPLACER_ARTICLE',		1 << 3);	$PEUT_DEPLACER_ARTICLE		= PEUT_DEPLACER_ARTICLE ;
define('PEUT_MODIFIER_ARTICLE',		1 << 4);	$PEUT_MODIFIER_ARTICLE		= PEUT_MODIFIER_ARTICLE ;
define('PEUT_MODIFIER_UTILISATEUR', 1 << 5);	$PEUT_MODIFIER_UTILISATEUR	= PEUT_MODIFIER_UTILISATEUR ;
define('PEUT_CHANGER_EDI',			1 << 6);	$PEUT_CHANGER_EDI			= PEUT_CHANGER_EDI ;
define('PEUT_EDITER_DEVIS_PRIX_ADH',1 << 7);	$PEUT_EDITER_DEVIS_PRIX_ADH	= PEUT_EDITER_DEVIS_PRIX_ADH ;

// anomalie --> gestion des poles
define('POLE_LOGISTIQUE'	,1 << 0);
define('POLE_COMMERCE'		,1 << 1);
define('POLE_EXPOSITION'	,1 << 2);
define('POLE_ADMINISTRATIF'	,1 << 3);
define('POLE_INFORMATIQUE'	,1 << 4);
define('POLE_LITIGE'		,1 << 5);
define('POLE_AUTRE'			,1 << 6);

// telmps maximum en second dans lequel une anomalie peut etre modifi� ou suprim�e
define('MAX_TIME_ANOMALIE_DELETION', 3600 * 24 ); // 24h

// taux de TVA
define('TTC1',19.6);
define('TTC2',5.5);

// jour de la semaine en FR
$jours_mini = array('Dim','Lun','Mar','Mer','Jeu','Ven','Sam');

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

function select_vendeur() {
	$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL AND code_vendeur<>'' ORDER BY prenom ASC");
	$tmp = array();
	while($row = mysql_fetch_array($res)) {
		$tmp[$row['code']] = $row['prenom'];
	}
	$tmp['LN'] = 'Jean Ren�';
	$tmp['MAR'] = 'Marc';
	ksort($tmp);

	$vendeurs = array();
	$vendeurs['AM,LG,RLF,MAR,CG']   = 'Chauffage';
	$vendeurs['AG,CLM,JFS,JM,LN']   = 'Sanitaire';
	$vendeurs['BT,CLH,ELM,JLD,SLN,VN'] = 'Electricit�';
	return array_merge($vendeurs,$tmp);
}

?>