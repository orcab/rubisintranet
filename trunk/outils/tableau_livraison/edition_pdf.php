<?

include('../../inc/config.php');
require_once('overload.php');
require_once('etat.php');

if (!(isset($_GET['id']) && $_GET['id'])) {
	echo "ERREUR : Aucun N° de cde précisé.";
 	exit;
}

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
try {
	$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
} catch (PDOException $exception) {
	die ($exception->getMessage());
}

$id_escape = mysql_escape_string($_GET['id']);

$sql_entete = <<<EOT
SELECT	id,numero_bon,numero_artisan,date_bon,date_liv,vendeur,reference,montant,
		vendeurs.nom AS nom_vendeur
FROM				cde_rubis
		LEFT JOIN	vendeurs
			ON cde_rubis.vendeur=vendeurs.code
WHERE	id_bon='$id_escape'
LIMIT	0,1
EOT;

$sql_detail = <<<EOT
SELECT	code_article,fournisseur,ref_fournisseur,designation,unit,qte,prix,etat,date_dispo
FROM	cde_rubis_detail
WHERE	id_bon='$id_escape'
ORDER BY id ASC
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$entete_commande= $sqlite->query($sql_entete) or die("Impossible de recuperer l'entete du bon : ".array_pop($sqlite->errorInfo()));
$row_entete		= $entete_commande->fetch(PDO::FETCH_ASSOC);
$row_entete		= array_map('utf8_decode',$row_entete);
$detail_commande= $sqlite->query($sql_detail) or die("Impossible de recuperer le détail du bon : ".array_pop($sqlite->errorInfo()));


// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

$kit = array();

while($row = $detail_commande->fetch(PDO::FETCH_ASSOC)) {
	
	// largeur des colonnes
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH,TYPE_CDE_WIDTH,DISPO_WIDTH,LIVRE_WIDTH));

	$row_original = $row ;
	$row =array_map('utf8_decode',$row);

	/*if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (preg_match('/^ +/',$row_original['CONSA'])) { // un espace devant le commentaire défini un COMMENTAIRE
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
	*/
	
		/*$designation = $row['DS1DB'] ;
		if ($row['DS2DB'])	$designation .= "\n$row[DS2DB]";
		if ($row['DS3DB'])	$designation .= "\n$row[DS3DB]";
		if ($row['CONSA'])	$designation .= "\n$row[CONSA]";
		*/

		// gestion des kits
		/*if ($row['ARCOM']=='OUI') { // attention article d'un kit, il faut l'enregistré pour le resortir sur le kit
			if (!isset($kit[$row['DET97']])) // premier article du kit
				$kit[$row['DET97']] = array();

			$kit[$row['DET97']][] = $designation." x$row[QTESA] (".str_replace('.',',',sprintf('%0.2f',$row['QTESA']*$row['PRINE'])).EURO.")";// on rajoute la piece au kit
			continue;
		}*/
		
		//print_r($kit);exit;


		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		/*
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBOP1' and CDETA='' and CDCOD LIKE '%$row_entete[NOBON]$row[NOLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n$commentaire_row[CDLIB]";
		*/

		//var_dump($row['designation']);

		$dispo = '';
		if (!($row['etat'] & ETAT_COMMENTAIRE)) {	// si pas un com'
			if ($row['etat'] & ETAT_SPECIAL) {		// si un spécial
				if ($row['date_dispo']) {			// si receptionné
					$row['designation'] .= "\n         Disponible à la coopérative depuis le ".join('/',array_reverse(explode('-',$row['date_dispo'])));
					$dispo = 'D';
				}
			} else { // matériel stocké
				$dispo = 'D';
			}
		}

		$livre = '';
		if ($row['etat'] & ETAT_LIVRE) { // produit deja livré
			$dispo = '';
			$livre = 'L';
		}

		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => $row['code_article']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
					array('text' => $row['fournisseur'].($row['ref_fournisseur']?"\n$row[ref_fournisseur]":''), 'font-style' => 'B','text-align' => 'C','font-size' => 8 ),
					array('text' => str_replace('\n',"\n",$row['designation'])				, 'text-align' => 'L'),
					array('text' => $row['unit']											, 'text-align' => 'C'), // unité
					array('text' => $row['qte']>0? ereg_replace('\.0$','',$row['qte']) : ''							, 'text-align' => 'C'), // quantité
					array('text' => $row['qte']>0 ? sprintf('%0.2f',round($row['prix'],2)).EURO : ''	, 'text-align' => 'R'), // prix unitaire après remise
					array('text' => $row['qte']>0 ? ($row['qte'] * $row['prix']).EURO : ''	, 'text-align' => 'R'), // total après remise
					array('text' => $row['etat'] & ETAT_SPECIAL ? 'S' : ''					, 'text-align' => 'C'), // spécial ou pas
					array('text' => $dispo													, 'text-align' => 'C'), // dispo ou pas
					array('text' => $livre													, 'text-align' => 'C') // livré ou pas
					)
				);

		

		//print_r($kit);exit;
		/*
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
		*/

	//} fin cas d'un commentaire
}


// fin du devis
if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(240); // gris clair
$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH + DISPO_WIDTH + LIVRE_WIDTH,7,
								str_replace('.',',',sprintf('%0.2f',$row_entete['montant'])).EURO,1,0,'R',1);

unset($sqlite);

$pdf->Output();

?>