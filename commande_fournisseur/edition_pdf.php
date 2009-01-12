<?

include('../inc/config.php');
require_once('overload.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");


$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}
$vendeurs['LN'] = 'Jean René';
$vendeurs['MAR'] = 'Marc';

if (!(isset($_GET['CFBON']) && $_GET['CFBON'])) { ?>
	ERREUR : Aucun N° de cde précisé.
<? 	exit;
}

$cfbon_escape = mysql_escape_string($_GET['CFBON']);

$sql_entete = <<<EOT
select CFBON,CFEDM,CFEDJ,CFEDS,CFEDA,CFSER,CFEES,CFEEA,CFEEM,CFEEJ,CFELS,CFELA,CFELM,CFELJ,FNOMF,RUEFO,VILFO,BURFO,CPFOU,TELFO,TLCFO,CFMON
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 BON, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
where	CFBON='$cfbon_escape'
	and BON.NOFOU=FOURNISSEUR.NOFOU
EOT;

$sql_detail = <<<EOT
select CFLIG,CFART,CFCLB,CFDDA,CFDDS,CFDDM,CFDDJ,CFDLA,CFDLS,CFDLM,CFDLJ,REFFO,CFDE1,CFDE2,CFDE3,CFUNI,CFQTE,CFPAB,CFRE1,CFRE2,CFRE3,CFTY1,CFTY2,CFTY3,CFPAN,CFMTH,CFDTY,CFCLI,CFPRF,CFCOM
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1
where	CFBON='$cfbon_escape'
	and CFDET<>'ANN'
	and CFDAG='$LOGINOR_DEPOT'
order by CFLIG
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
$row_entete		= array_map('trim',$row_entete);
$detail_commande= odbc_exec($loginor,$sql_detail) ; 


// génération du doc PDF
$pdf=new PDF();
$pdf->SetDisplayMode('fullpage','two');
$pdf->SetMargins(LEFT_MARGIN,TOP_MARGIN,RIGHT_MARGIN); // marge gauche et haute (droite = gauche)
$pdf->AliasNbPages();
$pdf->AddPage();
$pdf->SetTextColor(0,0,0);
$pdf->SetDrawColor(0,0,0);
$pdf->SetFillColor(230); // gris clair

while($row = odbc_fetch_array($detail_commande)) {
	
	// largeur des colonnes
	$pdf->SetWidths(array(REF_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,PUHT_WIDTH,REMISE_WIDTH,PUNETHT_WIDTH,PTHT_WIDTH));

	$row_original = $row ;
	$row =array_map('trim',$row);

	// recherche du nom de l'adh et de la référence si cde associé
	if ($row['CFCLB'])
		$ref_adh = trim(e('RFCSB',odbc_fetch_array(odbc_exec($loginor,"select RFCSB from ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 where NOBON='$row[CFCLB]'"))));
	else 
		$ref_adh='';

	if ($row['CFCLI'])
		$nom_adh = trim(e('NOMCL',odbc_fetch_array(odbc_exec($loginor,"select NOMCL from ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 where NOCLI='$row[CFCLI]'"))));
	else
		$nom_adh='';


	if ($row['CFPRF'] == 9) { // cas d'un commentaire
		if ($row['CFCOM']) {
			if (ereg('^ +',$row_original['CFCOM'])) { // un espace devant le commentaire défini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				$pdf->SetFillColor(240); // pas d'espace définit un titre
			}
			
			$pdf->SetFont('','B');
			if($pdf->GetY() +  7 > PAGE_HEIGHT - 29) // check le saut de page
				$pdf->AddPage();

			$pdf->Cell(0,7,$row['CFCOM'],1,1,'C',1);		
			$pdf->SetFillColor(255);
		}
	} else { // cas d'un article
	
		$designation = $row['CFDE1'] ;
		if ($row['CFDE2'])	$designation .= "\n$row[CFDE2]";
		if ($row['CFDE3'])	$designation .= "\n$row[CFDE3]";
		$designation .= " ($row[CFART])";
		if ($nom_adh)		$designation .= "\nAdh : $nom_adh";
		if ($row['CFCLB'])	$designation .= "\nCommande $row[CFCLB]";
		if ($ref_adh)		$designation .= "    Réf : $ref_adh";
		if ($row['CFCOM'])	$designation .= "\n$row[CFCOM]";

		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ACFDETP1' and CDETA='' and CDCOD LIKE '%$row_entete[CFBON]$row[CFLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n$commentaire_row[CDLIB]";
		
		//echo "'$designation'<br>\n";

		$remise = $row['CFRE1'] ? $row['CFRE1'].($row['CFTY1']=='P'?'%':'') : '' ;
		if ($row['CFRE2']) $remise .= "\n$row[CFRE2]".($row['CFTY2']=='P'?'%':'');
		if ($row['CFRE3']) $remise .= "\n$row[CFRE3]".($row['CFTY3']=='P'?'%':'');


		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => $row['REFFO']?$row['REFFO']:"$row[CFART]\n(code MCS)", 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10 ),
					array('text' => $designation		, 'text-align' => 'L', 'font-size' => 8),
					array('text' => $row['CFUNI']		, 'text-align' => 'C'), // unité
					array('text' => str_replace('.000','',$row['CFQTE'])		, 'text-align' => 'C'), // quantité
					array('text' => sprintf('%0.2f',round($row['CFPAB'],2)).EURO	, 'text-align' => 'R'), // prix d'achat brut
					array('text' => str_replace('.00','',$remise?$remise:'')	, 'text-align' => 'C'), // remise
					array('text' => sprintf('%0.2f',round($row['CFPAN'],2)).EURO	, 'text-align' => 'R'), // prix unitaire après remise
					array('text' => $row['CFMTH'].EURO	, 'text-align' => 'R') // total après remise
					)
				);
	}
}



// fin du devis
if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(240); // gris clair
$pdf->Cell(REF_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + REMISE_WIDTH + PUNETHT_WIDTH + PTHT_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$row_entete['CFMON'])).EURO,1,0,'R',1);
$pdf->Ln();

$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(240); // gris clair
$pdf->Cell(REF_WIDTH ,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"Date d'échéance : $row_entete[CFEEJ]/$row_entete[CFEEM]/$row_entete[CFEES]$row_entete[CFEEA]",1,0,'L',1);
$pdf->Ln();

$pdf->Output();

odbc_close($loginor);
?>