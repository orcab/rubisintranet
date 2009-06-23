<?
//print_r($_POST);exit;


$options = array();
if (isset($_POST['les_options'])) {
	$options = explode(',',$_POST['les_options']);
} elseif (isset($_GET['les_options'])) {
	$options = explode(',',$_GET['les_options']);
}

include('../inc/config.php');
require_once('overload.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base");

$id_devis = isset($_POST['id_devis']) && $_POST['id_devis'] ? $_POST['id_devis'] : '';
// SI L'ARTISAN N'EST PAS ADHERENT, ON PREND LE LIBÉLLÉ LIBRE !
$artisan_nom = $_POST['artisan_nom'] ;
if ($_POST['artisan_nom'] == 'NON Adherent' && $_POST['artisan_nom_libre'])
	$artisan_nom = $_POST['artisan_nom_libre'] ;

// DATE AU FORMAT yyyy-mm-dd hh:mm:ss
$date = implode('-',array_reverse(explode('/',$_POST['devis_date']))).' '.$_POST['devis_heure'].':00'; //2007-09-10 14:16:59;

$artisan_nom_escape = mysql_escape_string($artisan_nom);
$POST_escaped = array();
foreach ($_POST as $key => $val)
	if (!in_array(gettype($val),array('array','object'))) // pas un tableau ou un objet
		$POST_escaped[$key] = mysql_escape_string($val);

// SUPPRESSION DE L'ANCIEN DEVIS S'IL S'AGIT D'UNE MODIFICATION
if($id_devis) { // mode modification
	
	// ENREGISTREMENT DES NOUVELLES INFOS DEVIS DANS LA BASE
	$sql = <<<EOT
UPDATE devis SET
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
		email_client='$POST_escaped[client_email]'
WHERE id='$id_devis';
EOT;

//echo $sql ;

	mysql_query($sql) or die("Erreur dans la modification du devis : ".mysql_error());
	devis_log("update_devis",$id_devis,$sql);
	

} else {
	// ENREGISTREMENT DU DEVIS DANS LA BASE
	$sql = <<<EOT
		INSERT INTO devis	(`date`,date_maj,representant,artisan,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client)
		VALUES (
			'$date',
			NOW(),
			'$POST_escaped[artisan_representant]',
			'$artisan_nom_escape',
			'$POST_escaped[client_nom]',
			'$POST_escaped[client_adresse]',
			'$POST_escaped[client_adresse2]',
			'$POST_escaped[client_codepostal]',
			'$POST_escaped[client_ville])',
			'$POST_escaped[client_telephone]',
			'$POST_escaped[client_telephone2]',
			'$POST_escaped[client_email]'
		)
EOT;

	mysql_query($sql) or die("Erreur dans la creation du devis : ".mysql_error());
	$id_devis = mysql_insert_id();
	devis_log("insert_devis",$id_devis,$sql);
}
unset($POST_escaped,$artisan_nom_escape);



// ENREGISTREMENT DES LIGNES DEVIS DANS LA BASE
$total_devis		= 0 ;
$total_devis_adh	= 0 ;
$option				= 0 ;
mysql_query("DELETE FROM devis_ligne WHERE id_devis='$id_devis'") or die("Erreur dans la suppression des lignes du devis : ".mysql_error());
devis_log("delete_ligne",$id_devis,$sql);
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {
	if ($_POST['a_reference'][$i] && $_POST['a_qte'][$i]) { // ARTICLE SPÉCIFIÉ
		$sql  = "INSERT INTO devis_ligne (id_devis,ref_fournisseur,fournisseur,designation,qte,puht,pu_adh_ht,`option`) VALUES" ;
		$sql .= "('$id_devis','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_reference'][$i])))."','".
				strtoupper(mysql_escape_string(stripslashes($_POST['a_fournisseur'][$i])))."','".
				mysql_escape_string(stripslashes($_POST['a_designation'][$i]))."','".
				mysql_escape_string(stripslashes($_POST['a_qte'][$i]))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_pu'][$i])))."','".
				mysql_escape_string(stripslashes(str_replace(',','.',$_POST['a_adh_pu'][$i])))."','".
				mysql_escape_string($_POST['a_hid_opt'][$i]). 
				"')" ;
		if ($_POST['a_hid_opt'][$i]) { // c'est une option, on la compte pas dans le décompte finale
			$option++;
		} else {
			$total_devis		+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_pu'][$i]);
			$total_devis_adh	+= $_POST['a_qte'][$i] * str_replace(',','.',$_POST['a_adh_pu'][$i]);
		}
		

		mysql_query($sql) or die("Erreur dans creation des lignes devis : ".mysql_error()."<br>\n$sql");
		
	} elseif(!$_POST['a_reference'][$i] && $_POST['a_designation'][$i]) { // cas d'un commentaire
		$sql  = "INSERT INTO devis_ligne (id_devis,designation) VALUES" ;
		$sql .= "($id_devis,'".mysql_escape_string($_POST['a_designation'][$i])."')" ;

		mysql_query($sql) or die("Erreur dans creation des lignes devis (titre) : ".mysql_error());
	}
}
devis_log("insert_lignes",$id_devis);


// ENREGISTREMENT DES MOFIDICATIONS ARTICLE DANS LA BASE (OU CREATION)
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {
	if ($_POST['a_hid_maj'][$i] && $_POST['a_designation'][$i]) { // ARTICLE MIS A JOUR --> A ENREGISTRER
		$sql =	"REPLACE INTO devis_article2 (fournisseur,reference,designation,px_public,px_coop,date_creation,marge_coop,reference_simple) VALUES (".
					"'".strtoupper(mysql_escape_string($_POST['a_fournisseur'][$i]))."',".
					"'".strtoupper(mysql_escape_string($_POST['a_reference'][$i]))."',".
					"'".mysql_escape_string($_POST['a_designation'][$i])."',".
					"'".mysql_escape_string(ereg_replace(',','.',$_POST['a_pu'][$i]))."',".
					"'".mysql_escape_string(ereg_replace(',','.',$_POST['a_adh_pu'][$i]))."',".
					"NOW(),".
					"0,". // marge_coop
					"'".mysql_escape_string(ereg_replace('[^0-9A-Z]','',strtoupper($_POST['a_reference'][$i])))."'".
				")";
		mysql_query($sql) or die("Erreur dans la mise à jour des articles : ".mysql_error()."<br/>\n$sql");
		devis_log("replace_article",$id_devis,$sql);
	}
}



// GENERATION DU DOCUMENT PDF
// creation de l'objet
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH));


// on genere les lignes les une apres les autres
for($i=0 ; $i<sizeof($_POST['a_reference']) ; $i++) {
	if ($_POST['a_reference'][$i] && $_POST['a_qte'][$i]) { // cas d'un article
		$prix = in_array('px_adh',$options) ? $_POST['a_pu'][$i] : $_POST['a_adh_pu'][$i];

		if ($prix <= 0)
			$pdf->SetFillColor(255,0,0);
		
			$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => $_POST['a_reference'][$i]	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($_POST['a_reference'][$i])>11 ? 9:10 ),
						array('text' => $_POST['a_fournisseur'][$i]	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
						array('text' => stripslashes($_POST['a_designation'][$i]).($_POST['a_hid_opt'][$i] ? " (option)":'')	, 'text-align' => 'L'),
						array('text' => $_POST['a_qte'][$i]			, 'text-align' => 'C'),
						array('text' => $prix.($_POST['a_hid_opt'][$i] ? "\n(option)":'')			, 'text-align' => 'R'),
						array('text' => $_POST['a_qte'][$i]*$prix.($_POST['a_hid_opt'][$i] ? "\n(option)":'')		, 'text-align' => 'R'),
						)
					);
		$pdf->SetFillColor(255);

	} elseif(!$_POST['a_reference'][$i] && $_POST['a_designation'][$i]) { // cas d'un commentaire
		$pdf->SetFillColor(230);
		$pdf->SetFont('','B');
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
			$pdf->AddPage();

		$pdf->MultiCell(0,7,stripslashes($_POST['a_designation'][$i]) ,1,'C',1);
		$pdf->SetFillColor(255);
	}
}


if($pdf->GetY() +  3*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();


$total = in_array('px_adh',$options) ? $total_devis : $total_devis_adh;
$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(230); // gris clair
$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,$total.EURO,1,0,'R',1);
$pdf->Ln();

$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH ,7,"MONTANT TOTAL TTC (TVA ".TTC1."%)",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,str_replace('.',',',sprintf("%0.2f",$total + $total * TTC1 / 100)).EURO,1,0,'R',1);
$pdf->Ln();

$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH ,7,"MONTANT TOTAL TTC (TVA ".TTC2."%)",1,0,'L',1);
$pdf->Cell(QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH ,7,str_replace('.',',',sprintf("%0.2f",$total + $total * TTC2 / 100)).EURO,1,0,'R',1);
$pdf->Ln();

if ($option > 0) { // il y a des options, on balance un disclaimer
	$pdf->Cell(	REF_WIDTH + FOURNISSEUR_WIDTH + DESIGNATION_DEVIS_WIDTH + QTE_WIDTH + PUHT_WIDTH + PUHT_WIDTH,7,
				"Le total ne tient pas compte " . ($option > 1 ? "des $option options choisit" : "de l'option choisit"),
				1,0,'R',1);
	$pdf->Ln();
}

$pdf->Output();
?>