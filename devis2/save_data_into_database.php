<?

function save_data_into_database($draft = FALSE) {

global $id_devis,$artisan_nom,$date,$artisan_nom_escape,$artisan_nom,$POST_escaped,$total_devis,$total_devis_adh,$option ;

$devis_format_texte = '';

$table_sufixe = '';
if ($draft) $table_sufixe = '_draft';

$id_devis = isset($_POST['id_devis']) && $_POST['id_devis'] ? $_POST['id_devis'] : '';

// on extrait le code artisan du nom
$code_artisan	= '';
if (preg_match('/(.+?) \((.+?)\)$/',$_POST['artisan_nom'],$matches)) {
	$artisan_nom			= $matches[1] ;
	$code_artisan			= $matches[2] ;
	$_POST['artisan_nom']	= $matches[1] ;
} else {
	$artisan_nom	= $_POST['artisan_nom'];
}

// SI L'ARTISAN N'EST PAS ADHERENT, ON PREND LE LIBÉLLÉ LIBRE !
if		($_POST['artisan_nom'] == 'NON Adherent' && $_POST['artisan_nom_libre'])
	$artisan_nom = $_POST['artisan_nom_libre'] ;
elseif ($_POST['artisan_nom'] == 'CAB 56' && $_POST['artisan_nom_libre'])
	$artisan_nom = 'CAB 56 : '.$_POST['artisan_nom_libre'] ;

// DATE AU FORMAT yyyy-mm-dd hh:mm:ss
$date = implode('-',array_reverse(explode('/',$_POST['devis_date']))).' '.$_POST['devis_heure'].':00'; //2007-09-10 14:16:59;

$artisan_nom_escape = mysql_escape_string($artisan_nom);
$POST_escaped = array();
foreach ($_POST as $key => $val)
	if (!in_array(gettype($val),array('array','object'))) // pas un tableau ou un objet
		$POST_escaped[$key] = mysql_escape_string($val);

// SUPPRESSION DE L'ANCIEN DEVIS S'IL S'AGIT D'UNE MODIFICATION
if($id_devis) { // mode modification
	
	if ($draft) { // on creer le devis brouillon s'il n'existe pas
		$sql = <<<EOT
INSERT IGNORE INTO devis_draft (id,`date`,date_maj,representant,code_artisan,artisan,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client,num_devis_rubis)
VALUES (
	'$id_devis',
	'$date',
	NOW(),
	'$POST_escaped[artisan_representant]',
	'$code_artisan',
	'$artisan_nom_escape',
	'$POST_escaped[client_nom]',
	'$POST_escaped[client_adresse]',
	'$POST_escaped[client_adresse2]',
	'$POST_escaped[client_codepostal]',
	'$POST_escaped[client_ville]',
	'$POST_escaped[client_telephone]',
	'$POST_escaped[client_telephone2]',
	'$POST_escaped[client_email]',
	'$POST_escaped[devis_num_devis_rubis]'
)
EOT;
		mysql_query($sql) or die("Erreur dans la creation du devis brouillon : ".mysql_error());
	}


	// ENREGISTREMENT DES NOUVELLES INFOS DEVIS DANS LA BASE
	$sql = <<<EOT
UPDATE devis${table_sufixe} SET
		`date`='$date',
		date_maj=NOW(),
		representant='$POST_escaped[artisan_representant]',
		code_artisan='$code_artisan',
		artisan='$artisan_nom_escape',
		nom_client='$POST_escaped[client_nom]',
		adresse_client='$POST_escaped[client_adresse]',
		adresse_client2='$POST_escaped[client_adresse2]',
		codepostal_client='$POST_escaped[client_codepostal]',
		ville_client='$POST_escaped[client_ville]',
		tel_client='$POST_escaped[client_telephone]',
		tel_client2='$POST_escaped[client_telephone2]',
		email_client='$POST_escaped[client_email]',
		num_devis_rubis='$POST_escaped[devis_num_devis_rubis]'
WHERE id='$id_devis';
EOT;

	//echo $sql ;

	mysql_query($sql) or die("Erreur dans la modification du devis : ".mysql_error());
	

} else {
	// ENREGISTREMENT DU DEVIS DANS LA BASE
	$sql = <<<EOT
		INSERT INTO devis${table_sufixe}	(`date`,date_maj,representant,code_artisan,artisan,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client,num_devis_rubis)
		VALUES (
			'$date',
			NOW(),
			'$POST_escaped[artisan_representant]',
			'$code_artisan',
			'$artisan_nom_escape',
			'$POST_escaped[client_nom]',
			'$POST_escaped[client_adresse]',
			'$POST_escaped[client_adresse2]',
			'$POST_escaped[client_codepostal]',
			'$POST_escaped[client_ville]',
			'$POST_escaped[client_telephone]',
			'$POST_escaped[client_telephone2]',
			'$POST_escaped[client_email]',
			'$POST_escaped[devis_num_devis_rubis]'
		)
EOT;

	mysql_query($sql) or die("Erreur dans la creation du devis : ".mysql_error());
	$id_devis = mysql_insert_id();
}


// enregistre en history
$devis_format_texte .= <<<EOT
Représantant      : $_POST[artisan_representant]
Artisan           : $artisan_nom ($code_artisan)
Date              : $date
N° de devis Rubis : $_POST[devis_num_devis_rubis]


EOT;

if ($_POST['client_nom'])			$devis_format_texte .= "Nom client        : $_POST[client_nom]\n";
if ($_POST['client_adresse'])		$devis_format_texte .= "Adresse client    : $_POST[client_adresse]\n";
if ($_POST['client_adresse2'])		$devis_format_texte .= "                  : $_POST[client_adresse2]\n";
if (	$_POST['client_codepostal']
	|| 	$_POST['client_ville'])		$devis_format_texte .= "                  : $_POST[client_codepostal] $_POST[client_ville]\n";
if (	$_POST['client_telephone']
	||	$_POST['client_telephone2'])$devis_format_texte .= "Tel client        : $_POST[client_telephone] / $_POST[client_telephone2]\n";
if ($_POST['client_email'])			$devis_format_texte .= "Email client      : $_POST[client_email]\n";

$devis_format_texte .= "\n";


// ENREGISTREMENT DES LIGNES DEVIS DANS LA BASE
$total_devis		= 0 ;
$total_devis_adh	= 0 ;
$option				= 0 ;
mysql_query("DELETE FROM devis_ligne${table_sufixe} WHERE id_devis='$id_devis'") or die("Erreur dans la suppression des lignes du devis : ".mysql_error());
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {

	// designation 1
	$designation = $_POST['a_designation'][$i];
	// si une designation 2 est saisie, on la rajoute en l'encadrant de balise <desi2>
	$designation .= $_POST['a_2designation'][$i] ? "\n<desi2>".$_POST['a_2designation'][$i]."</desi2>":'';

	if ($_POST['a_reference'][$i] && $_POST['a_qte'][$i]) { // ARTICLE SPÉCIFIÉ

		$sql  = "INSERT INTO devis_ligne${table_sufixe} (id_devis,ref_fournisseur,fournisseur,designation,qte,puht,pu_adh_ht,`option`,designation_color,`designation_background-color`) VALUES" ;
		$sql .= "('$id_devis','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_reference'][$i])))."','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_fournisseur'][$i])))."','".
				mysql_escape_string(stripslashes($designation))."','".
				mysql_escape_string(stripslashes($_POST['a_qte'][$i]))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_pu'][$i])))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_adh_pu'][$i])))."','".
				mysql_escape_string($_POST['a_hid_opt'][$i])."','".
				mysql_escape_string($_POST['a_designation_color'][$i])."','".
				mysql_escape_string($_POST['a_designation_background-color'][$i]).
				"')" ;
		if ($_POST['a_hid_opt'][$i]) { // c'est une option, on la compte pas dans le décompte finale
			$option++;
		} else {
			$total_devis		+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_pu'][$i]);
			$total_devis_adh	+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_adh_pu'][$i]);
		}
		
		mysql_query($sql) or die("Erreur dans creation des lignes devis : ".mysql_error()."<br>\n$sql");

		// enregistre en history
		$devis_format_texte .= 	($_POST['a_hid_opt'][$i] ? 'Opt':'   ').
								" Ref: ".strtoupper(stripslashes($_POST['a_reference'][$i])).
								" / Four: ".strtoupper(stripslashes($_POST['a_fournisseur'][$i])).
								" / Qte: ".$_POST['a_qte'][$i].
								" / PU: ".$_POST['a_pu'][$i].
								" / Tot: ".$_POST['a_qte'][$i]*$_POST['a_pu'][$i].'€'.
								" / adh PU: ".$_POST['a_adh_pu'][$i].
								" / adh Tot: ".$_POST['a_qte'][$i]*$_POST['a_adh_pu'][$i].'€'."\n";

		preg_match('/^(.*?)<desi2>(.*?)<\/desi2>$/smi',$designation,$matches);
		if (isset($matches[2])) {
			$devis_format_texte .= "Designation client : ".trim(stripslashes($matches[1]))."\n";
			$devis_format_texte .= "Designation adh    : ".trim(stripslashes($matches[2]))."\n";
		} else {
			$devis_format_texte .= "Designation client : ".trim(stripslashes($designation))."\n";
		}
		
	} elseif(!$_POST['a_reference'][$i] && $_POST['a_designation'][$i]) { // cas d'un commentaire

		$sql  = "INSERT INTO devis_ligne${table_sufixe} (id_devis,designation,designation_color,`designation_background-color`) VALUES ".
				"($id_devis,'".
				mysql_escape_string(stripslashes($designation))."','".
				mysql_escape_string($_POST['a_designation_color'][$i])."','".
				mysql_escape_string($_POST['a_designation_background-color'][$i]).
				"')" ;

		mysql_query($sql) or die("Erreur dans creation des lignes devis (titre) : ".mysql_error());

		// enregistre en history
		preg_match('/^(.*?)<desi2>(.*?)<\/desi2>$/smi',$designation,$matches);
		if (isset($matches[2])) {
			$devis_format_texte .= "Commentaire client : ".trim(stripslashes($matches[1]))."\n";
			$devis_format_texte .= "Commentaire adh    : ".trim(stripslashes($matches[2]))."\n";
		} else {
			$devis_format_texte .= "Commentaire client : ".trim(stripslashes($designation))."\n";
		}
			
	}
	$devis_format_texte .= "\n";
}


// si on est sur un enregistrement définitif, alors on supprime le brouillon
if (!$draft) {
	mysql_query("DELETE FROM devis_ligne_draft WHERE id_devis='$id_devis'") or die("Impossible de supprimer les lignes devis du brouillon");
	mysql_query("DELETE FROM devis_draft WHERE id='$id_devis'") or die("Impossible de supprimer le devis brouillon");

	require_once '../inc/diff/lib/Diff.php';
	$devis_format_texte = trim($devis_format_texte);
	// on recupere le dernier enregistrement de l'historique
	$res = mysql_query("SELECT devis,LEFT(devis,2) as COMPRESS FROM devis_history where id_devis='$id_devis' ORDER BY `date` DESC LIMIT 0,1") or die("Impossible de récupérer le dernier enregistrement");
	$row = mysql_fetch_array($res);
	if ($row['COMPRESS'] == 'xœ') // compression GZIP
		$row['devis'] = gzuncompress($row['devis']);

	// on recherche les diff entre la version précedente et maintenant
	$diff = new Diff(explode("\n",$row['devis']), explode("\n",$devis_format_texte), array('ignoreWhitespace'=>true, 'ignoreCase'=>true, 'ignoreNewLines'=>true));
	
	// on enregistre dans history s'il existe des différences
	if ($diff->getGroupedOpcodes()) {
		//$sql = "INSERT INTO devis_history (`date`,user,id_devis,devis) VALUES (NOW(),'$_SERVER[REMOTE_ADDR]','$id_devis','".mysql_escape_string($devis_format_texte)."')";
		$sql = "INSERT INTO devis_history (`date`,user,id_devis,devis) VALUES (NOW(),'$_SERVER[REMOTE_ADDR]','$id_devis','".mysql_escape_string(gzcompress($devis_format_texte))."')";
		mysql_query($sql) or die("Impossible d'enregistrer l'historique : \n$sql\n".mysql_error());
	}
}


} // fin save_data_into_database
?>