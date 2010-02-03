<?

function save_data_into_database($draft = FALSE) {

global $id_devis,$artisan_nom,$date,$artisan_nom_escape,$POST_escaped,$total_devis,$total_devis_adh,$option ;

$table_sufixe = '';
if ($draft) $table_sufixe = '_draft';

$id_devis = isset($_POST['id_devis']) && $_POST['id_devis'] ? $_POST['id_devis'] : '';
// SI L'ARTISAN N'EST PAS ADHERENT, ON PREND LE LIB�LL� LIBRE !
$artisan_nom = $_POST['artisan_nom'] ;
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
INSERT IGNORE INTO devis_draft (id,`date`,date_maj,representant,artisan,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client,num_devis_rubis)
VALUES (
	'$id_devis',
	'$date',
	NOW(),
	'$POST_escaped[artisan_representant]',
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
		artisan='$artisan_nom_escape',
		theme='',
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
	devis_log("update_devis",$id_devis,$sql);
	

} else {
	// ENREGISTREMENT DU DEVIS DANS LA BASE
	$sql = <<<EOT
		INSERT INTO devis${table_sufixe}	(`date`,date_maj,representant,artisan,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client,num_devis_rubis)
		VALUES (
			'$date',
			NOW(),
			'$POST_escaped[artisan_representant]',
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
	devis_log("insert_devis",$id_devis,$sql);
}



// ENREGISTREMENT DES LIGNES DEVIS DANS LA BASE
$total_devis		= 0 ;
$total_devis_adh	= 0 ;
$option				= 0 ;
mysql_query("DELETE FROM devis_ligne${table_sufixe} WHERE id_devis='$id_devis'") or die("Erreur dans la suppression des lignes du devis : ".mysql_error());
devis_log("delete_ligne",$id_devis,"DELETE FROM devis_ligne${table_sufixe} WHERE id_devis='$id_devis'");
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {
	if ($_POST['a_reference'][$i] && $_POST['a_qte'][$i]) { // ARTICLE SP�CIFI�
		$sql  = "INSERT INTO devis_ligne${table_sufixe} (id_devis,ref_fournisseur,fournisseur,designation,qte,puht,pu_adh_ht,`option`) VALUES" ;
		$sql .= "('$id_devis','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_reference'][$i])))."','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_fournisseur'][$i])))."','".
				mysql_escape_string(stripslashes($_POST['a_designation'][$i]))."','".
				mysql_escape_string(stripslashes($_POST['a_qte'][$i]))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_pu'][$i])))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_adh_pu'][$i])))."','".
				mysql_escape_string($_POST['a_hid_opt'][$i]). 
				"')" ;
		if ($_POST['a_hid_opt'][$i]) { // c'est une option, on la compte pas dans le d�compte finale
			$option++;
		} else {
			$total_devis		+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_pu'][$i]);
			$total_devis_adh	+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_adh_pu'][$i]);
		}
		

		mysql_query($sql) or die("Erreur dans creation des lignes devis : ".mysql_error()."<br>\n$sql");
		
	} elseif(!$_POST['a_reference'][$i] && $_POST['a_designation'][$i]) { // cas d'un commentaire
		$sql  = "INSERT INTO devis_ligne${table_sufixe} (id_devis,designation) VALUES" ;
		$sql .= "($id_devis,'".mysql_escape_string($_POST['a_designation'][$i])."')" ;

		mysql_query($sql) or die("Erreur dans creation des lignes devis (titre) : ".mysql_error());
	}
}
devis_log("insert_lignes",$id_devis);


// ENREGISTREMENT DES MOFIDICATIONS ARTICLE DANS LA BASE (OU CREATION)
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {
	if ($_POST['a_hid_maj'][$i] && $_POST['a_designation'][$i]) { // ARTICLE MIS A JOUR --> A ENREGISTRER
	
		// au cas o� l'on cr�e l'article de toute pi�ce, on renseigne son fourn, sa ref, sa desi, son prix public et son prix adh
		$sql =	"INSERT IGNORE devis_article2 (fournisseur,reference,reference_simple,designation,px_coop,px_achat_coop,date_creation,qui,ip) VALUES (".
					"'".strtoupper(mysql_escape_string($_POST['a_fournisseur'][$i]))."',".
					"'".strtoupper(mysql_escape_string($_POST['a_reference'][$i]))."',".
					"'".strtoupper(mysql_escape_string(preg_replace('/[^A-Z0-9]/i','',$_POST['a_reference'][$i])))."',". // reference simple
					"'".mysql_escape_string($_POST['a_designation'][$i])."',".
					"'".mysql_escape_string(ereg_replace(',','.',$_POST['a_pu'][$i]))."',". // prix expo
					"'".mysql_escape_string(ereg_replace(',','.',$_POST['a_adh_pu'][$i]) * (1-(MARGE_COOP/100)) )."',". // prix d'achat coop (calcul� a partir du prix adh saisie)
					"NOW(),". // date creation
					"'$POST_escaped[artisan_representant]',". // qui 
					"'$_SERVER[REMOTE_ADDR]'". // ip
				")";
		mysql_query($sql) or die("Erreur dans la mise � jour des articles : ".mysql_error()."<br/>\n$sql");
		

		if (mysql_affected_rows() == 0) { // au cas ou l'article existe deja, on modifie
			$sql =	"UPDATE IGNORE devis_article2 SET ".
						"designation='".mysql_escape_string($_POST['a_designation'][$i])."',".
						"px_coop='".	mysql_escape_string(ereg_replace(',','.',$_POST['a_pu'][$i]))."',".
						"px_achat_coop='".	mysql_escape_string(ereg_replace(',','.',$_POST['a_adh_pu'][$i]) * (1-(MARGE_COOP/100)) )."',".
						"date_modification=NOW(),".
						"qui='$POST_escaped[artisan_representant]',".
						"ip='$_SERVER[REMOTE_ADDR]'".
					" WHERE				fournisseur="	."'".strtoupper(mysql_escape_string($_POST['a_fournisseur'][$i]))	."'".
								" AND	reference="		."'".strtoupper(mysql_escape_string($_POST['a_reference'][$i]))		."'" ;

			mysql_query($sql) or die("Erreur dans la mise � jour des articles : ".mysql_error()."<br/>\n$sql");
			devis_log("replace_article",$id_devis,$sql);
		} else {
			devis_log("create_article",$id_devis,$sql);
		}
	}
}



// si on est sur un enregistrement d�finitif, alors on supprime le brouillon
if (!$draft) {
	mysql_query("DELETE FROM devis_ligne_draft WHERE id_devis='$id_devis'") or die("Immpossible de supprimer les lignes devis du brouillon");
	mysql_query("DELETE FROM devis_draft WHERE id='$id_devis'") or die("Immpossible de supprimer le devis brouillon");
}



} // fin save_data_into_database


?>