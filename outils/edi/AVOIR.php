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
	and CDE_DETAIL.TRAIT='F'
	and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) <= '$now'
	and CONCAT(DSEMS,CONCAT(DSEMA,CONCAT(DSEMM,DSEMJ))) > '$date_precedente'
	and CDE_ENTETE.NOCLI='$row[numero_artisan]'
	and ARDIV='NON'
	and MONHT < 0	-- avoir
order by DTBOS asc,DTBOA asc,DTBOM asc,DTBOJ asc,CDE_ENTETE.NOBON asc, CDE_DETAIL.NOLIG ASC
EOT;

//echo $sql_detail;// exit;

$loginor		= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter � Loginor via ODBC ($LOGINOR_DSN)");
$detail_commande= odbc_exec($loginor,$sql_detail) ;

// ent�te de mail
$html .= "<b>Liste des Avoirs de $row[nom] $date_affichable</b><br/><br/>";

$old_nobon = '' ;
$total = 0 ;
// pour chaque ligne en reliquat
while($row_entete = odbc_fetch_array($detail_commande)) {
	$row_entete	= array_map('trim',$row_entete);
	$livsb = htmlentities(isset($vendeurs[$row_entete['LIVSB']]) ? $vendeurs[$row_entete['LIVSB']] : $row_entete['LIVSB']);

	if ($old_nobon != $row_entete['NOBON']) { // nouveau bon, on rajoute un entete
		if ($nb_bon > 0) {
			$html .= <<<EOT
<tr>
	<td colspan="2">&nbsp;</td>
	<td style="font-size:1em;font-weight:bold;">Montant Total HT</td>
	<td colspan="4" style="text-align:right;font-size:1.1em;font-weight:bold;">$total �</td>
</tr>
</table>
<br/><br/>
EOT;
			$total = 0 ;
		}

$agence = $AGENCES[$row_entete['AGENC']][0];

		$html .= <<<EOT
<table>
	<caption style="font-size:1.2em;">
		Bon n&ordm;$row_entete[NOBON] du $row_entete[DTBOJ]/$row_entete[DTBOM]/$row_entete[DTBOS]$row_entete[DTBOA] servi par $livsb ($agence)<br/>
		R&eacute;f&eacute;rence : $row_entete[RFCSB]<br/>
		Code chantier : $row_entete[CHAD1]<br/>
	</caption>
	<tr>
		<th class="code_article">Code article</th>
		<th class="fournisseur">Fournisseur</th>
		<th class="designation">Designation</th>
		<th class="qte">Qte</th>
		<th class="prix">P.U.</th>
		<th class="tot">Tot.</th>
	</tr>
EOT;
	}

	$designation = $row_entete['DS1DB'] ;
	if ($row_entete['DS2DB'])	$designation .= "<br/>$row_entete[DS2DB]";
	if ($row_entete['DS3DB'])	$designation .= "<br/>$row_entete[DS3DB]";
	if ($row_entete['CONSA'])	$designation .= "<br/>$row_entete[CONSA]";
	$type_cde	= $row_entete['TYCDE']=='SPE' ? 'S':'';
	$qte		= str_replace('.000','',$row_entete['QTESA']);
	$pu			= sprintf('%0.2f',$row_entete['PRINE']);
	$total		+= $qte * $pu ; //sprintf('%0.2f',$row_entete['MONTBT']);

	$html .= <<<EOT
<tr>
	<td class="code_article" style="text-align:center;">$row_entete[CODAR]</td>
	<td class="fournisseur" style="text-align:center;">$row_entete[NOMFO]<br/>$row_entete[REFFO]</td>
	<td class="designation">$designation</td>
	<td class="qte" style="text-align:center;">$qte</td>
	<td class="prix" style="text-align:right;">$pu</td>
	<td class="tot" style="text-align:right;">$row_entete[MONHT]</td>
</tr>
EOT;
	$old_nobon = $row_entete['NOBON'];
	$nb_bon++;
} // pour chaque BL


// derniere facture et pied de mail
if ($nb_bon) {
	$total = sprintf('%0.2f',$total);
	$html .= <<<EOT
<tr>
	<td colspan="2">&nbsp;</td>
	<td style="font-size:1em;font-weight:bold;">Montant Total HT</td>
	<td colspan="4" style="text-align:right;font-size:1.1em;font-weight:bold;">$total �</td>
</tr>
</table>
EOT;
}

?>