<?

require_once('overload.php');

define('DEBUG',isset($_GET['debug'])?TRUE:FALSE);

$mysql    = mysql_connect(MYSQL_HOST, MYSQL_USER, MYSQL_PASS) or die("Impossible de se connecter à MySQL");
$database = mysql_select_db(MYSQL_BASE) or die("Impossible de se choisir la base MySQL");

$vendeurs = select_vendeur();

$cfbon_escape = mysql_escape_string($num_cde);

$sql_entete = <<<EOT
select CFBON,CFEDM,CFEDJ,CFEDS,CFEDA,CFSER,CFEES,CFEEA,CFEEM,CFEEJ,CFELS,CFELA,CFELM,CFELJ,FNOMF,CFMON,FOURNISSEUR.NOFOU
from ${LOGINOR_PREFIX_BASE}GESTCOM.ACFENTP1 BON, ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
where	CFBON='$cfbon_escape'
	and BON.NOFOU=FOURNISSEUR.NOFOU
EOT;

$sql_detail = <<<EOT
select CFLIG,CFART,CFCLB,CFDDA,CFDDS,CFDDM,CFDDJ,CFDLA,CFDLS,CFDLM,CFDLJ,REFFO,CFDE1,CFDE2,CFDE3,CFUNI,CFQTE,CFCLI,CFPRF,CFCOM,LOCAL,LOCA2,LOCA3,CFPAN,CFMTH,
ENTETE_CDE_CLIENT.RFCSB,
CLIENT.NOMCL,CLIENT.NOCLI,
DETAIL_CDE_CLIENT.TRAIT,
DETAIL.CDDE6 KIT,
ARTICLE.GENCO
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
	left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
		on		DETAIL.CFART = ARTICLE.NOART
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

		if ($row['KIT'] == 'OUI')
			$designation .= "\nCeci est un kit composé de :";

		// on cherche les commentaires associé à la ligne de commande (saisie sur une commande client)
		$commentaire_res = odbc_exec($loginor,"SELECT CDLIB FROM ${LOGINOR_PREFIX_BASE}GESTCOM.ACOMMEP1 WHERE CDFIC='ACFDETP1' and CDETA='' and CDCOD LIKE '%$row_entete[CFBON]$row[CFLIG]%' ORDER BY CDLIG") ;
		while($commentaire_row = odbc_fetch_array($commentaire_res))
			if ($commentaire_row['CDLIB'])	$designation .= "\n".trim($commentaire_row['CDLIB']);
		
		//echo "'$designation'<br>\n";

		$y_up_rect = $pdf->GetY();

		// affichage du code barre du produit si c'est pas un kit
		if (preg_match('/^\d{13}$/',$row['GENCO']) && $row['KIT'] != 'OUI') {
			$pdf->SetFillColor(0,0,0); // noir
			$pdf->EAN13(LEFT_MARGIN + REF_WIDTH + UNITE_WIDTH + PU_WIDTH + PT_WIDTH + QTE_WIDTH + DESIGNATION_DEVIS_WIDTH + 1.5, $y_up_rect , $row['GENCO'] , 5 , .20 );
		}

		
		$pdf->Row(	array( //   font-family , font-weight, font-size, font-color, text-align
					array('text' => ($row['REFFO'] ? $row['REFFO'] : "$row[CFART]\n(code MCS)") .
									($row['REFFO'] && !$row['CFCLB'] ? "\n " : '') . // si une référence fournisseur sans rien d'autre --> on rajout un \n pour le GENCODE
									($row['CFCLB'] ? "\nCommande\nspéciale":'')	, 'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10 , 'background-color' => array(220,220,220), 'background-fill' => $row['CFCLB'] ? TRUE : FALSE),
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

		// si cde spécial, on fait un lien vers le document bon cde "R"
		if ($row['CFCLB']) {
			//Link(float x, float y, float w, float h, mixed link)
			$pdf->Link(LEFT_MARGIN, $y_up_rect , PAGE_WIDTH - (LEFT_MARGIN + RIGHT_MARGIN) , $pdf->GetY(), 'http://'.$_SERVER['SERVER_ADDR'].'/intranet/commande_adherent/edition_pdf.php?NOBON='.$row['CFCLB'].'&NOCLI='.$row['NOCLI'].'&options[]=sans_prix&options[]=ligne_R');
		}
			

		// gestion du détail du kit
		if ($row['KIT'] == 'OUI') {
			// on va piocher dans la base loginor le détail du kit pour l'afficher
			$sql = <<<EOT
select		DETAIL_KIT.NOART,NUCOM,REFFO,DESI1,DESI2,LOCAl,LOCA2,LOCA3,GENCO
from		${LOGINOR_PREFIX_BASE}GESTCOM.AKITDEP1 DETAIL_KIT
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
					on DETAIL_KIT.NOART=ARTICLE.NOART
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
					on DETAIL_KIT.NOART=ARTICLE_FOURNISSEUR.NOART and ARTICLE.FOUR1=ARTICLE_FOURNISSEUR.NOFOU
				left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK
					on		DETAIL_KIT.NOART = STOCK.NOART
					and	STOCK.DEPOT = '$LOGINOR_DEPOT'
where		NOKIT='$row[CFART]'
EOT;
			$res_kit = odbc_exec($loginor,$sql) or die("Impossible de lancer la requete kit : $sql");
			while($row_kit = odbc_fetch_array($res_kit)) { // on parcours les articles du kit et on les enregistre pour plus tard
				// ici on affiche les détail du kit
				$y_up_rect = $pdf->GetY();

				$ref_kit			= $row_kit['REFFO'] ? $row_kit['REFFO'] : "$row_kit[NOART]\n(code MCS)" ;
				$designation_kit	= $row_kit['DESI1'].( $row_kit['DESI2'] ? "\n".trim($row_kit['DESI2']) : '')." (".trim($row_kit['NOART']).")" ;
				$local_kit			= $row_kit['LOCAL'].( $row_kit['LOCA2'] ? "\n$row_kit[LOCA2]":'' ).( $row_kit['LOCA3'] ? "\n$row_kit[LOCA3]":'' );
				$pdf->Row(	array(
								array('text' => $ref_kit, 			'font-style' => 'B',	'text-align' => 'C', 'font-size' => strlen($row['REFFO'])>10 ? 8:10),
								array('text' => $designation_kit,	'text-align' => 'L', 'font-size' => 8),
								array('text' => 'KIT'), // unité
								array('text' => ''), // PU
								array('text' => ''), // PT
								array('text' => str_replace('.0000','',$row_kit['NUCOM']) ,	'text-align' => 'C'), // quantité
								array('text' => '' ), // case vide
								array('text' => $local_kit	, 'text-align' => 'C', 'font-size' => 10) //localisation
							)
						); // fin row

				
				// affichage du code barre du produit si c'est pas un kit
				if ($row_kit['GENCO']) {
					$pdf->SetFillColor(0,0,0); // noir
					$pdf->EAN13(LEFT_MARGIN + REF_WIDTH + UNITE_WIDTH + PU_WIDTH + PT_WIDTH + QTE_WIDTH + DESIGNATION_DEVIS_WIDTH + 1.5, $y_up_rect , $row_kit['GENCO'] , 5 , .20 );
				}
			} // fin while kit
		} // fin if kit
	} // fin if article
} // fin while ligne en cde


if($pdf->GetY() +  2*7 > PAGE_HEIGHT - 100) // check le saut de page
	$pdf->AddPage();

$pdf->Output();

odbc_close($loginor);
?>