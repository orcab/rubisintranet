<?

include('../inc/config.php');
require_once('overload.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter � MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");


$res = mysql_query("SELECT prenom,UCASE(code_vendeur) AS code FROM employe WHERE code_vendeur IS NOT NULL ORDER BY prenom ASC");
$vendeurs = array();
while($row = mysql_fetch_array($res)) {
	$vendeurs[$row['code']] = $row['prenom'];
}
$vendeurs['LN'] = 'Jean Ren�';


if (!(isset($_GET['NOBON']) && $_GET['NOBON'])) { ?>
	ERREUR : Aucun N� de cde pr�cis�.
<? 	exit;
}

$NOBON_escape = mysql_escape_string($_GET['NOBON']);
$NOCLI_escape = mysql_escape_string($_GET['NOCLI']);

$sql_entete = <<<EOT
select NOBON,DSECS,DSECA,DSECM,DSECJ,LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,MONTBT,TELCL,TLCCL
from ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON, ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
where	NOBON='$NOBON_escape'
	and BON.NOCLI='$NOCLI_escape'
	and BON.NOCLI = CLIENT.NOCLI
EOT;

$sql_detail = <<<EOT
select NOLIG,PROFI,TYCDD,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,PRINE,MONHT,NOMFO,REFFO
from	${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 BON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on	BON.NOFOU=FOURNISSEUR.NOFOU
		left join AFAGESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		BON.CODAR = ARTICLE_FOURNISSEUR.NOART
				and	BON.NOFOU = ARTICLE_FOURNISSEUR.NOFOU
where	NOBON='$NOBON_escape'
	and BON.NOCLI='$NOCLI_escape'
	and ETSBE<>'ANN'
	and BON.AGENC='$LOGINOR_AGENCE'
order by NOLIG
EOT;

if (DEBUG) {
	echo "SQL_ENTETE :<br>\n<pre>$sql_entete</pre><br><br>";
	echo "SQL_DETAIL :<br>\n<pre>$sql_detail</pre>";
}

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");
$entete_commande= odbc_exec($loginor,$sql_entete) ; 
$row_entete		= odbc_fetch_array($entete_commande);
$row_entete		= array_map('trim',$row_entete);
$detail_commande= odbc_exec($loginor,$sql_detail) ; 


// g�n�ration du doc PDF
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
	$pdf->SetWidths(array(REF_WIDTH,FOURNISSEUR_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,QTE_WIDTH,PUHT_WIDTH,PTHT_WIDTH,TYPE_CDE_WIDTH));

	$row_original = $row ;
	$row =array_map('trim',$row);

	if ($row['PROFI'] == 9) { // cas d'un commentaire
		if ($row['CONSA']) {
			if (ereg('^ +',$row_original['CONSA'])) { // un espace devant le commentaire d�fini un COMMENTAIRE
				$pdf->SetFillColor(255);
			} else {
				$pdf->SetFillColor(240); // pas d'espace d�finit un titre
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
		
		// on cherche les commentaires associ� � la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ADETBOP1' and CDETA='' and CDCOD LIKE '%$row_entete[NOBON]$row[NOLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n$commentaire_row[CDLIB]";

		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => $row['CODAR']	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 10 ),
					array('text' => $row['NOMFO'].($row['REFFO']?"\n$row[REFFO]":'')		, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => 8 ),
					array('text' => $designation		, 'text-align' => 'L'),
					array('text' => $row['UNICD']		, 'text-align' => 'C'), // unit�
					array('text' => str_replace('.000','',$row['QTESA'])		, 'text-align' => 'C'), // quantit�
					array('text' => sprintf('%0.2f',round($row['PRINE'],2)).EURO	, 'text-align' => 'R'), // prix unitaire apr�s remise
					array('text' => $row['MONHT'].EURO	, 'text-align' => 'R'), // total apr�s remise
					array('text' => $row['TYCDD']=='SPE'?'S':''	, 'text-align' => 'R') // sp�cial ou pas
					)
				);
	}
}


// fin du devis
if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 29) // check le saut de page
	$pdf->AddPage();

$pdf->SetFont('helvetica','B',10);
$pdf->SetFillColor(240); // gris clair
$pdf->Cell(REF_WIDTH + FOURNISSEUR_WIDTH,7,'',1,0,'',1);
$pdf->Cell(DESIGNATION_DEVIS_WIDTH,7,"MONTANT TOTAL HT",1,0,'L',1);
$pdf->Cell(UNITE_WIDTH + QTE_WIDTH + PUHT_WIDTH + PTHT_WIDTH + TYPE_CDE_WIDTH,7,str_replace('.',',',sprintf('%0.2f',$row_entete['MONTBT'])).EURO,1,0,'R',1);

$pdf->Output();

odbc_close($loginor);
?>