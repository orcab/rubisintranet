<?

require_once('overload.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$vendeurs = select_vendeur();

$cfbon_escape = mysql_escape_string($num_cde);

$sql_entete = <<<EOT
select CFBON,CFEDM,CFEDJ,CFEDS,CFEDA,CFSER,CFEES,CFEEA,CFEEM,CFEEJ,CFELS,CFELA,CFELM,CFELJ,FNOMF,CFMON
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 BON, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
where	CFBON='$cfbon_escape'
	and BON.NOFOU=FOURNISSEUR.NOFOU
EOT;

$sql_detail = <<<EOT
select CFLIG,CFART,CFCLB,CFDDA,CFDDS,CFDDM,CFDDJ,CFDLA,CFDLS,CFDLM,CFDLJ,REFFO,CFDE1,CFDE2,CFDE3,CFUNI,CFQTE,CFCLI,CFPRF,CFCOM,LOCAL,LOCA2,LOCA3,CFPAN,CFMTH,
ENTETE_CDE_CLIENT.RFCSB,
CLIENT.NOMCL,
DETAIL_CDE_CLIENT.TRAIT
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 DETAIL
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK
		on		DETAIL.CFART = STOCK.NOART
			and	STOCK.DEPOT = '$LOGINOR_DEPOT'
			and	STOCK.STSTS = ''
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 ENTETE_CDE_CLIENT
		on		DETAIL.CFCLB=ENTETE_CDE_CLIENT.NOBON
			and	DETAIL.CFCLI=ENTETE_CDE_CLIENT.NOCLI
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
		on		DETAIL.CFCLI=CLIENT.NOCLI
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 DETAIL_CDE_CLIENT
		on		DETAIL.CFCLB=DETAIL_CDE_CLIENT.NOBON
			and DETAIL.CFCLI=DETAIL_CDE_CLIENT.NOCLI
			and DETAIL.CFCLL=DETAIL_CDE_CLIENT.NOLIG
where	CFBON	=	'$cfbon_escape'
	and CFDET	=	''
	and CFDAG	=	'$LOGINOR_DEPOT'
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
	$pdf->SetWidths(array(REF_WIDTH,DESIGNATION_DEVIS_WIDTH,UNITE_WIDTH,PU_WIDTH,PT_WIDTH,QTE_WIDTH,QTE_RECU_WIDTH,LOCAL_WIDTH));

	$row_original = $row ;
	$row =array_map('trim',$row);

	if ($row['CFPRF'] == 9) { // cas d'un commentaire
		if ($row['CFCOM']) {
			if (ereg('^ +',$row_original['CFCOM']))// un espace devant le commentaire défini un COMMENTAIRE
				$pdf->SetFillColor(255);
			else
				$pdf->SetFillColor(240); // pas d'espace définit un titre
			
			$pdf->SetFont('helvetica','B',10);
			if($pdf->GetY() +  7 > PAGE_HEIGHT - 60) // check le saut de page
				$pdf->AddPage();

			$pdf->Cell(0,7,$row['CFCOM'],1,1,'C',1);
			$pdf->SetFillColor(255);
		}
	} else { // cas d'un article
	
		if($pdf->GetY() +  7 > PAGE_HEIGHT - 75) // check le saut de page
			$pdf->AddPage();

		$designation = $row['CFDE1'] ;
		if ($row['CFDE2'])	$designation .= "\n$row[CFDE2]";
		if ($row['CFDE3'])	$designation .= "\n$row[CFDE3]";
		$designation .= " ($row[CFART])";
		if ($row['NOMCL'])	$designation .= "\nAdh : $row[NOMCL]";
		if ($row['CFCLB'])	$designation .= "\nCommande $row[CFCLB]";
		if ($row['RFCSB'])	$designation .= "    Réf : $row[RFCSB]";
		if ($row['CFCOM'])	$designation .= "\n$row[CFCOM]";


		// comparaison de la date de livraison et de la date du jour --> départ immédiat ou non
		$date_liv	= "$row[CFDLS]$row[CFDLA]$row[CFDLM]$row[CFDLJ]";
		$today		= date('Ymd');

		$reliquat = e('RELIQUAT',odbc_fetch_array(odbc_exec($loginor,"select COUNT(NOLIG) as RELIQUAT from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 where NOBON='$row[CFCLB]' and NOCLI='$row[CFCLI]' and TRAIT='F'"))) >= 1 ? TRUE : FALSE; // regarde s'il des articles ont déjà été livré sur la cde client.

		if		($row['TRAIT'] == 'F')
			$designation .= "\n              ARTICLE DEJA LIVRE";
		elseif	($today >= $date_liv && $row['CFCLB'])
			$designation .= "\n".($reliquat ? '/!\\RELIQUAT/!\\  ':'              ')."DEPART IMMEDIAT  à livrer pour le $row[CFDLJ]/$row[CFDLM]/$row[CFDLS]$row[CFDLA]";

		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ACFDETP1' and CDETA='' and CDCOD LIKE '%$row_entete[CFBON]$row[CFLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n".trim($commentaire_row['CDLIB']);
		
		//echo "'$designation'<br>\n";

		
		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => ($row['REFFO'] ? $row['REFFO'] : "$row[CFART]\n(code MCS)") . 
									($row['CFCLB'] ? "\n\nCommande\nspéciale":'')	,
																					'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10 , 'background-color' => array(220,220,220), 'background-fill' => $row['CFCLB'] ? TRUE : FALSE),
					array('text' => $designation		,							'text-align' => 'L', 'font-size' => 8, 'background-color' => array(220,220,220), 'background-fill' => $row['CFCLB'] ? TRUE : FALSE),
					array('text' => $row['CFUNI']		,							'text-align' => 'C'), // unité
					array('text' => round($row['CFPAN'],2).EURO		,				'text-align' => 'C'), // PU
					array('text' => $row['CFMTH'].EURO				,				'text-align' => 'C'), // PT
					array('text' => str_replace('.000','',$row['CFQTE'])		,	'text-align' => 'C'), // quantité
					array('text' => '' ), // case vide
					array('text' => $row['LOCAL'].( $row['LOCA2'] ? "\n$row[LOCA2]":'' ).( $row['LOCA3'] ? "\n$row[LOCA3]":'' )	,
							'text-align' => 'C', 'font-size' => 10) //localisation
					)
				);
	}
}


if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 100) // check le saut de page
	$pdf->AddPage();

$pdf->Output();

odbc_close($loginor);
?>