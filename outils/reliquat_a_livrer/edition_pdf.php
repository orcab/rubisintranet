<?

require_once('overload.php');

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$vendeurs = select_vendeur();

// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair


// largeur des colonnes
$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,TYPE_CDE_WIDTH,NOLIG_WIDTH,LOCAL_WIDTH));


$kit		= array();
$old_bon	= '';
for($i=0 ; $i<sizeof($ligne_cde_ok) ; $i++) {
	$row = $ligne_cde_ok[$i];
	$row_original = $row ;
	$row = array_map('trim',$row);

	if ($commande_adh[$row['CFCLI'].'.'.$row['CFCLB']] == 0) continue; // si le bon n'est pas pret, on le saute (des lignes S non réceptionnées)

	if ($old_bon != $row['CFCLI'].'.'.$row['CFCLB']) // clé du bon adh
		$pdf->AddPage(); // on crée une nouvelle page pour le nouveau bon

	if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (ereg('^ +',$row_original['CONSA'])) { // un espace devant le commentaire défini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				$pdf->SetFillColor(240); // pas d'espace définit un titre
			}
			
			$pdf->SetFont('','B');
			if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
				$pdf->AddPage();

			$pdf->Cell(0,7,$row['CONSA'],1,1,'C',1);		
			$pdf->SetFillColor(255);
		}
	} else { // cas d'un article
	
		$designation = $row['DS1DB'] ;
		if ($row['DS2DB'])	$designation .= "\n$row[DS2DB]";
		if ($row['DS3DB'])	$designation .= "\n$row[DS3DB]";
		if ($row['CONSA'])	$designation .= "\n$row[CONSA]";

		// gestion des kits
		if ($row['ARCOM']=='OUI') { // attention article d'un kit, il faut l'enregistré pour le resortir sur le kit
			if (!isset($kit[$row['DET97']])) // premier article du kit
				$kit[$row['DET97']] = array();

			$kit[$row['DET97']][] = $designation." x$row[QTESA] (".str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO.")";// on rajoute la piece au kit
			continue;
		}
		
		//print_r($kit);exit;

		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
				array('text' => $row['CODAR']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
				array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 8 ),
				array('text' => (isset($kit[$row['DET97']])?'KIT ':'').$designation		, 'text-align' => 'L'),
				array('text' => $row['UNICD']											, 'text-align' => 'C'), // unité
				array('text' => str_replace('.000','',$row['QTESA'])					, 'text-align' => 'C'), // quantité
				array('text' => $row['TYCDD']=='SPE'?"S\nE":''								, 'text-align' => 'C'), // spécial ou pas
				array('text' => $row['NOLIG']											, 'text-align' => 'C'), // no de ligne
				array('text' => $row['LOCAL']											, 'text-align' => 'C')  // localisation
				)
			);
		
		
		//print_r($kit);exit;
		if (isset($kit[$row['DET97']])) { // on doit afficher les info du kit
			foreach ($kit[$row['DET97']] as $ligne)
				$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
								array('text' => ''	,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => $ligne,'text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8'),
								array('text' => '','text-align'=>'R','font-size'=>'8')
							)
						);
			
			unset($kit[$row['DET97']]);
		}

	} //fin article ou commentaire

	$old_bon = $row['CFCLI'].'.'.$row['CFCLB'];
} // fin for

$pdf->Output();

odbc_close($loginor);
?>