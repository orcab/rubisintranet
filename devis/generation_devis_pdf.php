<?
//phpinfo();exit;


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


// GENERE UN N° UNIQUE
$cmd_rubis  = '' ;
$numero		= '' ;
$date		= '' ;
$heure		= '' ;


// ON RECHERCHE LE NUMERO DU DEVIS POUR UTILISER LE MEME
if(isset($_POST['id']) && $_POST['id']) { // mode modification
	$res = mysql_query("SELECT CONCAT(DATE_FORMAT(`date`,'%b%y-'),id) AS numero,num_cmd_rubis,DATE_FORMAT(date,'%d/%m/%Y') AS date_formater, DATE_FORMAT(date,'%H:%i') AS heure_formater FROM devis WHERE id=$_POST[id] LIMIT 0,1");
	$row = mysql_fetch_array($res);
	$numero		= $row['numero'];
	$cmd_rubis	= $row['num_cmd_rubis'];
	$date		= $row['date_formater'];
	$heure		= $row['heure_formater'];

//	print_r($row);
}


//echo "'$date_maj'   '$heure_maj'";exit;

// SI L'ARTISAN N'EST PAS ADHERENT, ON PREND LE LIBÉLLÉ LIBRE !
$artisan_nom = $_POST['artisan_nom'] ;
if ($_POST['artisan_nom'] == 'NON Adherent' && $_POST['artisan_nom_libre'])
	$artisan_nom = $_POST['artisan_nom_libre'] ;


// CALCULE DES VALEURS DE REMPLACEMENT
$values = array('devis.date'			=>	$date		? $date			: $_POST['devis_date'],
				'devis.heure'			=>	$heure		? $heure		: $_POST['devis_heure'],
				'devis.date_maj'		=>	date('d/m/Y'),
				'devis.heure_maj'		=>	date('H:i'),
				'devis.numero'			=>	$numero,
				'devis.theme'			=>  $_POST['devis_theme'],
				'artisan.nom'			=>	$artisan_nom,
				'artisan.representant'	=>	$_POST['artisan_representant'],
				'client.nom'			=>	$_POST['client_nom'],
				'client.adresse'		=>	$_POST['client_adresse'],
				'client.adresse2'		=>	$_POST['client_adresse2'],
				'client.codepostal'		=>	$_POST['client_codepostal'],
				'client.ville'			=>	$_POST['client_ville'],
				'client.telephone'		=>	$_POST['client_telephone'],
				'client.telephone2'		=>	$_POST['client_telephone2'],
				'client.email'			=>	$_POST['client_email']
				);

$values['devis.ptht'] =	0;

// construction du tableau PROPRES des valeurs articles
$article_devis = Array();
for($i=1,$j=1 ; $i<=NOMBRE_DE_LIGNE ; $i++ ) {
	if (isset($_POST['a'.$i.'_reference']) && $_POST['a'.$i.'_reference']) { // cas d'un article
		$article_devis['a'.$j.'_reference']  =$_POST['a'.$i.'_reference'];
		$article_devis['a'.$j.'_fournisseur']=$_POST['a'.$i.'_fournisseur'];
		$article_devis['a'.$j.'_designation']=$_POST['a'.$i.'_designation'];
		$article_devis['a'.$j.'_qte']	     =$_POST['a'.$i.'_qte'];
		// sanitaire
		if (in_array('prix_adh',$options)) { // Devis avec prix net
			$article_devis['a'.$j.'_puht']	     =str_replace(',','.',$_POST['a'.$i.'_pu_adh_ht']);
		} else { // devis avec prix public
			$article_devis['a'.$j.'_puht']	     =$_POST['a'.$i.'_puht'];
		}
		$article_devis['a'.$j.'_stock']	     =isset($_POST['a'.$i.'_stock']) ? $_POST['a'.$i.'_stock'] : '';
		$article_devis['a'.$j.'_expo']	     =isset($_POST['a'.$i.'_expo']) ? $_POST['a'.$i.'_expo'] : '';
		$j++;
	} elseif (!$_POST['a'.$i.'_reference'] && isset($_POST['a'.$i.'_designation']) && $_POST['a'.$i.'_designation']) { // cas d'un titre
		$article_devis['a'.$j.'_reference']  ='';
		$article_devis['a'.$j.'_fournisseur']='';
		$article_devis['a'.$j.'_designation']=$_POST['a'.$i.'_designation'];
		$article_devis['a'.$j.'_qte']	     ='';
		$article_devis['a'.$j.'_puht']	     ='';
		$article_devis['a'.$j.'_stock']	     ='';
		$article_devis['a'.$j.'_expo']	     ='';
		$j++;
	}
}


for($i=1 ; $i<=NOMBRE_DE_LIGNE ; $i++ ) {
	if (isset($article_devis['a'.$i.'_designation'])) {
		// on nettoi la designation (CR, ...)
		$designation = eregi_replace("(\{CR\})+","\n",$article_devis['a'.$i.'_designation']) ;
		$designation = eregi_replace("\\\\","",$designation) ;

	} else {
		$designation = '';
	}

	if (isset($article_devis['a'.$i.'_reference']) && $article_devis['a'.$i.'_reference']) {

		$values["a$i.reference"]	= $article_devis['a'.$i.'_reference'];
		$values["a$i.fournisseur"]	= $article_devis['a'.$i.'_fournisseur'];
		$values["a$i.designation"]	= $designation;
		$values["a$i.qte"]			= $article_devis['a'.$i.'_qte'];
		$values["a$i.puht"]			= str_replace('.',',',sprintf("%0.2f",$article_devis['a'.$i.'_puht'])).EURO;
		$values["a$i.ptht"]			= str_replace('.',',',sprintf("%0.2f",$article_devis['a'.$i.'_qte'] * $article_devis['a'.$i.'_puht'])).EURO;
		$values["a$i.stock"]		= $article_devis['a'.$i.'_stock'] ? 'X':'';
		$values["a$i.expo"]			= $article_devis['a'.$i.'_expo']  ? 'X':'';

		$values['devis.ptht'] += $article_devis['a'.$i.'_qte'] * $article_devis['a'.$i.'_puht'];
	} else { // ligne non spécifiée --> mise a 0 des valeurs
		$values["a$i.reference"]	= '';
		$values["a$i.fournisseur"]	= '';
		$values["a$i.designation"]	= $designation; // si jamais c'est un titre de section
		$values["a$i.qte"]			= '';
		$values["a$i.puht"]			= '';
		$values["a$i.ptht"]			= '';
		$values["a$i.stock"]		= '';
		$values["a$i.expo"]			= '';
		
		// on essai de calculer un sous total
		$j = $i+1 ; $soustotal = 0 ;
		while((isset($article_devis['a'.$j.'_reference']) && $article_devis['a'.$j.'_reference']) &&  $j < NOMBRE_DE_LIGNE) { // tant que dans la meme section et pas en fin de devis
			$soustotal += $article_devis['a'.$j.'_qte'] * $article_devis['a'.$j.'_puht'];
			$j++; // on descent sur l'article suivant
		}
		$values["a$i.soustotal"] = str_replace('.',',',sprintf("%0.2f",$soustotal)).EURO;
	}
}

$values['devis.ptttc1'] = str_replace('.',',',sprintf("%0.2f",$values['devis.ptht'] + $values['devis.ptht'] * TTC1 / 100)).EURO;
$values['devis.ptttc2'] = str_replace('.',',',sprintf("%0.2f",$values['devis.ptht'] + $values['devis.ptht'] * TTC2 / 100)).EURO;
$values['devis.ptht']   = str_replace('.',',',sprintf("%0.2f",$values['devis.ptht'])).EURO;


//print($values["a3.puht"]);


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

if (eregi('^devis_net',$values['devis.theme']))
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_NET_WIDTH,QTE_WIDTH,PNHT_WIDTH,PUHT_WIDTH,PTHT_WIDTH));
elseif (eregi('^devis',$values['devis.theme']))
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH));
elseif (eregi('^gamme',$values['devis.theme']))
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_GAMME_WIDTH,STOCK_WIDTH,EXPO_WIDTH,PUHT_WIDTH));

// on genere les lignes les une apres les autres
for($i=1 ; $i<=NOMBRE_DE_LIGNE ; $i++ ) {
	if ($values["a$i.reference"]) {

		if (eregi('^devis_net',$values['devis.theme'])) {

/*			$prix_net = e('prix_net',mysql_fetch_array(mysql_query("SELECT prix_net FROM article WHERE (ref_fournisseur='".mysql_escape_string($values["a$i.reference"])."' OR ref_fournisseur_condensee='".mysql_escape_string($values["a$i.reference"])."') LIMIT 0,1"))); 
			
			$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
						array('text' => $values["a$i.reference"]	, 'font-style' => 'B',	'text-align' => 'C'),
						array('text' => $values["a$i.fournisseur"]	, 'font-style' => '', 'text-align' => 'C'),
						array('text' => $values["a$i.designation"]	, 'text-align' => 'L'),
						array('text' => $values["a$i.qte"]			, 'text-align' => 'C'),
						array('text' => sprintf("%0.2f",$prix_net).EURO	, 'text-align' => 'R' , 'font-style' => 'I' , 'font-color' => array(150,150,150) , 'background-color' => array(255,0,0)),
						array('text' => $values["a$i.puht"]			, 'text-align' => 'R', 'font-style' => '' , 'font-color' => array(0,0,0) , 'background-color' => array(255,255,255)),
						array('text' => $values["a$i.ptht"]			, 'text-align' => 'R'),
						)
					);

*/
		} elseif (eregi('^devis',$values['devis.theme'])) {
			if ($values["a$i.puht"] <= 0) 
				$pdf->SetFillColor(255,0,0);

			
			if (in_array('non_chiffre',$options)) // non chiffré
				$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $values["a$i.reference"]	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($values["a$i.reference"])>11 ? 9:10 ),
							array('text' => $values["a$i.fournisseur"]	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
							array('text' => $values["a$i.designation"]	, 'text-align' => 'L'),
							array('text' => $values["a$i.qte"]			, 'text-align' => 'C')
							)
						);
			else // chiffré
				$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $values["a$i.reference"]	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($values["a$i.reference"])>11 ? 9:10 ),
							array('text' => $values["a$i.fournisseur"]	, 'font-style' => '', 'text-align' => 'C', 'font-size' => 10),
							array('text' => $values["a$i.designation"]	, 'text-align' => 'L'),
							array('text' => $values["a$i.qte"]			, 'text-align' => 'C'),
							array('text' => $values["a$i.puht"]			, 'text-align' => 'R'),
							array('text' => $values["a$i.ptht"]			, 'text-align' => 'R'),
							)
						);

			$pdf->SetFillColor(255);
			
		} elseif (eregi('^gamme',$values['devis.theme'])) {

			if (in_array('non_chiffre',$options)) // non chiffré
				$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
							array('text' => $values["a$i.reference"]	, 'font-style' => 'B',	'text-align' => 'C'),
							array('text' => $values["a$i.fournisseur"]	, 'font-style' => '', 'text-align' => 'C'),
							array('text' => $values["a$i.designation"]	, 'text-align' => 'L'),
							array('text' => $values["a$i.stock"]		, 'text-align' => 'C'),
							array('text' => $values["a$i.expo"]			, 'text-align' => 'C'),
							)
						);
			else // chiffré
				$pdf->Row(array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => $values["a$i.reference"]	, 'font-style' => 'B',	'text-align' => 'C'),
								array('text' => $values["a$i.fournisseur"]	, 'font-style' => '', 'text-align' => 'C'),
								array('text' => $values["a$i.designation"]	, 'text-align' => 'L'),
								array('text' => $values["a$i.stock"]		, 'text-align' => 'C'),
								array('text' => $values["a$i.expo"]			, 'text-align' => 'C'),
								array('text' => $values["a$i.puht"]			, 'text-align' => 'R'),
								)
							);
		}

	} elseif(!$values["a$i.reference"] && $values["a$i.designation"]) { // cas d'un titre
		$pdf->SetFillColor(230);
		$pdf->SetFont('','B');
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
			$pdf->AddPage();

		$pdf->Cell(0,7,$values["a$i.designation"].( isset($values["a$i.soustotal"]) && $values["a$i.soustotal"]!='0,00'.EURO ? ' ('.$values["a$i.soustotal"].')' : '' ),1,1,'C',1);
		$pdf->SetFillColor(255);
	}
}


if (eregi('^devis',$values['devis.theme'])) {
	// on imprime les cases finales avec les prix

	if($pdf->GetY() +  3*7 > PAGE_HEIGHT - 29) // check le saut de page
		$pdf->AddPage();

	$pdf->SetFont('helvetica','B',10);
	$pdf->SetFillColor(230); // gris clair
	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell((in_array('non_chiffre',$options) ? abs(DESIGNATION_DEVIS_WIDTH - REF_WIDTH) : DESIGNATION_DEVIS_WIDTH),7,"MONTANT TOTAL HT",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + (in_array('non_chiffre',$options) ? REF_WIDTH : PUHT_WIDTH + PTHT_WIDTH)  ,7,$values['devis.ptht'],1,0,'R',1);
	$pdf->Ln();

	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell((in_array('non_chiffre',$options) ? abs(DESIGNATION_DEVIS_WIDTH - REF_WIDTH) : DESIGNATION_DEVIS_WIDTH),7,"MONTANT TOTAL TTC (TVA ".TTC1."%)",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + (in_array('non_chiffre',$options) ? REF_WIDTH : PUHT_WIDTH + PTHT_WIDTH) ,7,$values['devis.ptttc1'],1,0,'R',1);
	$pdf->Ln();

	$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
	$pdf->Cell((in_array('non_chiffre',$options) ? abs(DESIGNATION_DEVIS_WIDTH - REF_WIDTH) : DESIGNATION_DEVIS_WIDTH),7,"MONTANT TOTAL TTC (TVA ".TTC2."%)",1,0,'L',1);
	$pdf->Cell(QTE_WIDTH + (in_array('non_chiffre',$options) ? REF_WIDTH : PUHT_WIDTH + PTHT_WIDTH) ,7,$values['devis.ptttc2'],1,0,'R',1);
	$pdf->Ln();
}





$date = implode('-',array_reverse(explode('/',$_POST['devis_date']))).' '.$_POST['devis_heure'].':00'; //2007-09-10 14:16:59;

$id_devis = 0;

// SUPPRESSION DE L'ANCIEN DEVIS S'IL S'AGIT D'UNE MODIFICATION
if(isset($_POST['id']) && $_POST['id']) { // mode modification
	//mysql_query("DELETE FROM devis WHERE id=$_POST[id]") or die("Impossible de supprimer l'ancien devis pour modification ".mysql_error()); // suppresion du devis et des ligne via la cascade

	$artisan_nom_escape = mysql_escape_string($artisan_nom);
	$POST_escaped = array();
	foreach ($_POST as $key => $val)
		$POST_escaped[$key] = mysql_escape_string($val);	

	// ENREGISTREMENT DES NOUVELLES INFOS DEVIS DANS LA BASE
	$sql = <<<EOT
UPDATE devis SET
		`date`='$date',
		date_maj=NOW(),
		representant='$POST_escaped[artisan_representant]',
		artisan='$artisan_nom_escape',
		theme='$POST_escaped[devis_theme]',
		nom_client='$POST_escaped[client_nom]',
		adresse_client='$POST_escaped[client_adresse]',
		adresse_client2='$POST_escaped[client_adresse2]',
		codepostal_client='$POST_escaped[client_codepostal]',
		ville_client='$POST_escaped[client_ville]',
		tel_client='$POST_escaped[client_telephone]',
		tel_client2='$POST_escaped[client_telephone2]',
		email_client='$POST_escaped[client_email]'
WHERE id='$_POST[id]';
EOT;

//echo $sql ;

	mysql_query($sql) or die("Erreur dans la modification du devis : ".mysql_error());
	unset($POST_escaped,$artisan_nom_escape);
	$id_devis = $_POST['id'];

} else {
	// ENREGISTREMENT DU DEVIS DANS LA BASE
	$sql = "INSERT INTO devis (`date`,date_maj,representant,artisan,theme,nom_client,adresse_client,adresse_client2,codepostal_client,ville_client,tel_client,tel_client2,email_client) VALUES ('$date',NOW(),'".mysql_escape_string($_POST['artisan_representant'])."','".mysql_escape_string($artisan_nom)."','$_POST[devis_theme]','".mysql_escape_string($_POST['client_nom'])."','".mysql_escape_string($_POST['client_adresse'])."','".mysql_escape_string($_POST['client_adresse2'])."','$_POST[client_codepostal]','".mysql_escape_string($_POST['client_ville'])."','$_POST[client_telephone]','$_POST[client_telephone2]','$_POST[client_email]')" ;

	mysql_query($sql) or die("Erreur dans la creation du devis : ".mysql_error());
	$id_devis = mysql_insert_id();
}


// ENREGISTREMENT DES DESIGNATION ARTICLE DANS LA BASE
for($i=1 ; $i<=NOMBRE_DE_LIGNE ; $i++ ) {
	if (isset($_POST['a'.$i.'_reference']) && $_POST['a'.$i.'_reference'] && isset($_POST['a'.$i.'_maj'])) { // ARTICLE SPÉCIFIÉ + MAJ FORCEE

		// on regarde deja si la référence existe dans la base.
		$sql = "SELECT id FROM devis_article WHERE ref_fournisseur='".strtoupper($_POST['a'.$i.'_reference'])."' AND fournisseur='".strtoupper(mysql_escape_string($_POST['a'.$i.'_fournisseur']))."' LIMIT 0,1";
		$res = mysql_query($sql) or die("Impossible de voir si la référence existe déjà dans la base : ".mysql_error());

		if (mysql_num_rows($res) > 0) { // la référence exist dans la base
			$row = mysql_fetch_array($res);
			$sql = "UPDATE devis_article SET designation='".mysql_escape_string($_POST['a'.$i.'_designation'])."', prix_public_ht='".str_replace(',','.',$_POST['a'.$i.'_puht'])."', remise=0, date_maj=NOW() WHERE id='$row[id]'";
			mysql_query($sql) or die("Impossible de voir si la référence existe déjà dans la base : ".mysql_error());

		} else { // la référence n'existe pas dans la base -> on la crée
			$sql  = "INSERT INTO devis_article (ref_fournisseur,fournisseur,designation,prix_public_ht,date_creation,date_maj) VALUES (";
			$sql .= "'".strtoupper(mysql_escape_string(preg_replace("/[^a-z0-9]/i","",$_POST['a'.$i.'_reference'])))."',";
			$sql .= "'".strtoupper(mysql_escape_string($_POST['a'.$i.'_fournisseur']))."',";
			$sql .= "'".mysql_escape_string($_POST['a'.$i.'_designation'])."',";
			$sql .= "".mysql_escape_string(str_replace(',','.',$_POST['a'.$i.'_puht'])).",";
			$sql .= "NOW(),NOW())";

			mysql_query($sql) or die("Impossible d'enregistrer la nouvelle référence dans la base : ".mysql_error());
		}
	}
}

// ENREGISTREMENT DES LIGNES DEVIS DANS LA BASE
mysql_query("DELETE FROM devis_ligne WHERE id_devis='$id_devis'") or die("Erreur dans la suppression des lignes du devis : ".mysql_error());
for($i=1 ; $i<=NOMBRE_DE_LIGNE ; $i++ ) {
	if (isset($_POST['a'.$i.'_reference']) && $_POST['a'.$i.'_reference']) { // ARTICLE SPÉCIFIÉ
		$sql  = "INSERT INTO devis_ligne (id_devis,code_article,ref_fournisseur,fournisseur,designation,qte,puht,pu_adh_ht,stock,expo) VALUES" ;
		$sql .= "($id_devis,'".$_POST['a'.$i.'_code']."','".strtoupper($_POST['a'.$i.'_reference'])."','".strtoupper(mysql_escape_string($_POST['a'.$i.'_fournisseur']))."','".mysql_escape_string($_POST['a'.$i.'_designation'])."','".$_POST['a'.$i.'_qte']."','".mysql_escape_string(str_replace(',','.',$_POST['a'.$i.'_puht']))."','".mysql_escape_string(str_replace(',','.',$_POST['a'.$i.'_pu_adh_ht']))."',".(isset($_POST['a'.$i.'_stock']) ? 1 : 0).",".(isset($_POST['a'.$i.'_expo']) ? 1 : 0).")" ;

		mysql_query($sql) or die("Erreur dans creation des ligne devis : ".mysql_error());

	} elseif (!$_POST['a'.$i.'_reference'] && isset($_POST['a'.$i.'_designation']) && $_POST['a'.$i.'_designation']) { // CAS D'UN TITRE
		$sql  = "INSERT INTO devis_ligne (id_devis,designation) VALUES" ;
		$sql .= "($id_devis,'".mysql_escape_string($_POST['a'.$i.'_designation'])."')" ;

		mysql_query($sql) or die("Erreur dans creation des ligne devis : ".mysql_error());
	}
}

$pdf->Output();
?>