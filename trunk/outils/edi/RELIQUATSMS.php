<?php

$sql_detail = <<<EOT
select CDE_ENTETE.NOBON,DTBOS,DTBOA,DTBOM,DTBOJ,LIVSB,RFCSB,DLSSB,DLASB,DLMSB,DLJSB,NOLIG,TYCDE,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,NOMFO,REFFO,PRINE,CDE_DETAIL.MONHT,CDE_ENTETE.MONTBT,CDE_DETAIL.AGENC,CHANTIER.CHAD1
from	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 CDE_ENTETE
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 CDE_DETAIL
			on CDE_ENTETE.NOCLI=CDE_DETAIL.NOCLI and CDE_ENTETE.NOBON=CDE_DETAIL.NOBON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on	CDE_DETAIL.NOFOU=FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARFOUP1 ARTICLE_FOURNISSEUR
			on		CDE_DETAIL.CODAR= ARTICLE_FOURNISSEUR.NOART	and	CDE_DETAIL.NOFOU= ARTICLE_FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AARTICP1 ARTICLE
			on	CDE_DETAIL.CODAR=ARTICLE.NOART
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AENTCHP1 CHANTIER
			on		CDE_ENTETE.NOCHA=CHANTIER.CHCHA and CDE_ENTETE.NOCLI=CHANTIER.CHCLI 
where	
		ETSBE<>'ANN'
	and	ETSEE<>'ANN'
	and CDE_DETAIL.PROFI='1'
	and CDE_DETAIL.TRAIT='R'
	and CONCAT(DLSSB,CONCAT(DLASB,CONCAT(DLMSB,DLJSB))) <= '$now'
	and CDE_ENTETE.NOCLI='$row[numero_artisan]'
	and ARDIV='NON'
order by DTBOS asc,DTBOA asc,DTBOM asc,DTBOJ asc,CDE_ENTETE.NOBON asc, CDE_DETAIL.NOLIG ASC
EOT;

//echo $sql_detail;
$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
$detail_commande= odbc_exec($loginor,$sql_detail) ;

// entête de mail
$html .= "RELIQUATS MCS\n";

$old_nobon = '' ;
$total = 0 ;
// pour chaque ligne en reliquat
while($row_entete = odbc_fetch_array($detail_commande)) {
	$row_entete	= array_map('trim',$row_entete);
	$livsb = htmlentities(isset($vendeurs[$row_entete['LIVSB']]) ? $vendeurs[$row_entete['LIVSB']] : $row_entete['LIVSB']);

	if ($old_nobon != $row_entete['NOBON']) { // nouveau bon, on rajoute un entete
		if ($nb_bon > 0) {
			$total = 0 ;
		}

$agence = $AGENCES[$row_entete['AGENC']][0];

		$html .= <<<EOT
Bon $row_entete[NOBON] du $row_entete[DTBOJ]/$row_entete[DTBOM]/$row_entete[DTBOS]$row_entete[DTBOA]
Ref $row_entete[RFCSB]
EOT;
	}

	$designation = $row_entete['DS1DB'] ;
	if ($row_entete['DS2DB'])	$designation .= " $row_entete[DS2DB]";
	$type_cde = $row_entete['TYCDE']=='SPE' ? 'S':'';
	$qte = str_replace('.000','',$row_entete['QTESA']);
	$pu			= sprintf('%0.2f',$row_entete['PRINE']);
	$total		+= $qte * $pu ; //sprintf('%0.2f',$row_entete['MONTBT']);

	$html .= <<<EOT
$row_entete[CODAR]
$row_entete[NOMFO]  $row_entete[REFFO]
$designation
QTE : $qte
EOT;
	$old_nobon = $row_entete['NOBON'];
	$nb_bon++;
} // pour chaque RELIQUATSMS
?>