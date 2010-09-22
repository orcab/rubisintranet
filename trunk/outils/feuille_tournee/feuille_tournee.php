<?php

include('../../inc/config.php');

$demain_ddmmyyyy	= date('d/m/Y' , mktime(0,0,0,date('m'),date('d')+1,date('Y')));
$date_yyyymmdd		= '';
$date_ddmmyyyy		= '';
if (isset($_POST['filtre_date']) && $_POST['filtre_date']) {
	$date_yyyymmdd = join('-',array_reverse(explode('/',$_POST['filtre_date'])));
	$date_ddmmyyyy = $_POST['filtre_date'];
}

?>
<html>
<head>
	<title></title>
<style>
body { font-family:verdana; }

table#tournee {
	border:solid 1px black;
	border-collapse:collapse;
	width:100%;
	margin-bottom:20px;
	page-break-after:always;
}

table#tournee td { border:dotted 1px black; }
table#tournee th { border:solid 1px black; }
table#tournee th { background-color:#F2F2F2; }
table#tournee td { height:1.5cm; }
table#tournee td.adresse { height:0.5cm; font-size:0.8em; background-color:#F5F5F5; }
table#tournee tr.separateur { border-top:solid 3px black; }

@media print {
	.hide_when_print { display:none; }
}

</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../../js/jquery.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar-setup.js"></script>

<script language="javascript">

function modifie_date_liv(prepa,nobon,nocli) {
	if (prepa == 'CLH' && !confirm("Bon fait par CLH, voulez vous vraiment l'exclure de la tournée ?")) {
		return ;
	} else {
		// faire un appel ajax pour modifier la date de livraison du bon au dimanche precedent.
		$('#exclure-'+nobon+'-'+nocli).attr('value','Patientez ...').css('background-image',"url('../../gfx/loading5.gif')"); // fait patientez

<?		//calcule la date du dimanche precedent
		$date_time		= strtotime($date_yyyymmdd);
		$day_number		= date('w',$date_time);
		
		$last_sunday = date('d/m/Y' , mktime(0,0,0,	date('m',$date_time),
													date('d',$date_time)-$day_number,
													date('Y',$date_time)));
?>
		$.ajax({
			url: 'ajax.php',
			type: 'GET',
			data: 'what=modifie_date_liv&nobon='+nobon+'&nocli='+nocli+'&date_ddmmyyyy=<?=$last_sunday?>',
			success: function(result){
						var json = eval('(' + result + ')') ;
						//if (json['debug']) alert(''+json['debug']);
						$('#bon-'+nobon+'-'+nocli).hide(); // efface la ligne
					}	
		});
	}
}
</script>

</head>
<body>
<form name="tournee" method="post">
	<div class="hide_when_print">
		<input type="text" id="filtre_date" name="filtre_date" value="<?=$date_ddmmyyyy?$date_ddmmyyyy:$demain_ddmmyyyy?>" size="8">
		<button id="trigger_date" style="background:url('../../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.tournee.filtre_date.value='';">
		<script type="text/javascript">
		  Calendar.setup(
			{
			  inputField	: 'filtre_date',         // ID of the input field
			  ifFormat		: '%d/%m/%Y',    // the date format
			  button		: 'trigger_date',       // ID of the button
			  date			: '<?=$date_ddmmyyyy?$date_ddmmyyyy:$demain_ddmmyyyy?>',
			  firstDay 		: 1
			}
		  );
		</script>

		<input type="submit" class="button valider" value="Afficher" />
	</div>


<?

if ($date_yyyymmdd) {
	$day_number = date('w',strtotime($date_yyyymmdd));

	$sql = <<<EOT
select	CLIENT.NOCLI,CLIENT.NOMCL,TOUCL,NOBON,ENT32,NINT1,NINT2,NINT3,NINT4,NINT5,NINT6,
		CLIENT.AD1CL, CLIENT.AD2CL, CLIENT.RUECL, CLIENT.VILCL, CLIENT.CPCLF, CLIENT.BURCL, -- adresse du client
		CLIENT.TELCL as TEL1, CLIENT.TELCC as TEL2, CLIENT.TLXCL as TEL3,					-- tel du client
		ADR_LIV.NOMLV as LIV_NOM, ADR_LIV.AD1LV as LIV_ADR1, ADR_LIV.AD2LV as LIV_ADR2, ADR_LIV.RUELV as LIV_ADR3, ADR_LIV.VILLV as LIV_COORDS, ADR_LIV.CPOLV as LIV_CP, ADR_LIV.BURLV as LIV_VILLE,  -- adresse de livraison
		ADR_LIV.TELLV as LIV_TEL1, ADR_LIV.TLXLV as LIV_TEL2,
		(select count(ETSBE) from ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 where  NOBON=BON.NOBON and	NOCLI=BON.NOCLI and TRAIT='F' and PROFI='1' and ETSBE='') as NB_LIGNE_A_LIVRE
from	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
				on BON.NOCLI=CLIENT.NOCLI
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ALIVADP1 ADR_LIV
				on BON.NOCLI=ADR_LIV.NOCLI and ADR_LIV.NOLIV='DEPOT'
where	CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB)))))='$date_yyyymmdd'
		and TYVTE='LIV'
		and FACAV='F'
		and ETSEE=''
order by TOUCL ASC, NOMCL ASC
EOT;

//echo "<div style='color:red;'>$sql</div>";exit;

	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;

	$livraison = array();
	while($row = odbc_fetch_array($res)) {
			if ($row['NB_LIGNE_A_LIVRE'] > 0) { // si au moins une ligne a livrer

				if (isset($tournee_chauffeur[$row['TOUCL']][$day_number]))
					$chauf = $tournee_chauffeur[$row['TOUCL']][$day_number];
				else
					$chauf = 'Non définit';

				if (!isset($livraison[$chauf]))
					$livraison[$chauf] = array();
				
				// supprime les '.' des n° de téléphones
				$row['LIV_TEL1']	= str_replace('.',' ',$row['LIV_TEL1']);
				$row['LIV_TEL2']	= str_replace('.',' ',$row['LIV_TEL2']);
				$row['TEL1']		= str_replace('.',' ',$row['TEL1']);
				$row['TEL2']		= str_replace('.',' ',$row['TEL2']);
				$row['TEL3']		= str_replace('.',' ',$row['TEL3']);

				// affichage de l'adresse de livraison
				$adr = array();
				if (trim($row['LIV_COORDS'])) { // si un dépot est précisé dans les adr de livraison --> on livre au dépot et non pas a l'adresse de facturation
					if (trim($row['LIV_NOM']))	$adr[] = trim($row['LIV_NOM']);
					if (trim($row['LIV_ADR1']))	$adr[] = trim($row['LIV_ADR1']);
					if (trim($row['LIV_ADR2']))	$adr[] = trim($row['LIV_ADR2']);
					if (trim($row['LIV_ADR3']))	$adr[] = trim($row['LIV_ADR3']);
					if (trim($row['LIV_CP']))	$adr[] = trim($row['LIV_CP']);
					if (trim($row['LIV_VILLE']))$adr[] = trim($row['LIV_VILLE']);
					if (trim($row['LIV_TEL1'])) $adr[] = trim($row['LIV_TEL1']); // si le n° commence par un 06 ou 07
					if (trim($row['LIV_TEL2'])) $adr[] = trim($row['LIV_TEL2']); // si le n° commence par un 06 ou 07

				} else { // on livre a l'adresse de facturation
					if (trim($row['AD1CL'])) $adr[] = trim($row['AD1CL']);
					if (trim($row['AD2CL'])) $adr[] = trim($row['AD2CL']);
					if (trim($row['RUECL'])) $adr[] = trim($row['RUECL']);
					if (trim($row['VILCL'])) $adr[] = trim($row['VILCL']);
					if (trim($row['CPCLF'])) $adr[] = trim($row['CPCLF']);
					if (trim($row['BURCL'])) $adr[] = trim($row['BURCL']);
					if (trim($row['TEL1'])) $adr[] = trim($row['TEL1']); // si le n° commence par un 06 ou 07
					if (trim($row['TEL2'])) $adr[] = trim($row['TEL2']); // si le n° commence par un 06 ou 07
					if (trim($row['TEL3'])) $adr[] = trim($row['TEL3']); // si le n° commence par un 06 ou 07
				}

				$adr = "Adr liv : ".join(', ',$adr);

				$livraison[$chauf][] = array(	'nom_adh'	=>	$row['NOMCL'],
												'no_cli'	=>	$row['NOCLI'],
												'adr_adh'	=>	$adr,
												'no_bon'	=>	$row['NOBON'],
												'prepa'		=>	$row['ENT32'],
												'colis'		=>	ereg_replace('^0+','',trim($row['NINT1'])),
												'pal'		=>	ereg_replace('^0+','',trim($row['NINT2'])),
												'ce'		=>	ereg_replace('^0+','',trim($row['NINT3'])),
												'paroi'		=>	ereg_replace('^0+','',trim($row['NINT4'])),
												'pvc'		=>	ereg_replace('^0+','',trim($row['NINT5'])),
												'cu'		=>	ereg_replace('^0+','',trim($row['NINT6'])),
											);
			} // fin si nb_ligne a livré >0
	}

	/*echo "<pre>" ;
	print_r($livraison);
	echo "</pre>" ; exit;
	*/

	//$livraison = array('GILLES' => array( array('CAB 56 ','F80107') ));

	$old_adh = '';
	foreach ($livraison as $chauf=>$tournee) { ?>

		<table id="tournee">
			<tr>
				<th colspan="7">Feuille de tournée</th>
			</tr>
			<tr>
				<th colspan="4">Du : <?=$date_ddmmyyyy?$date_ddmmyyyy:$demain_ddmmyyyy?></th>
				<th colspan="3">Chauffeur : <?=$chauf?></th>
			</tr>
			<tr>
				<th style="width:6%;">Prépa</th>
				<th style="width:12%;">Adhérent</th>
				<th style="width:7%;">N° de<br/>bon</th>
				<th style="width:12%;">NB colis</th>
				<th style="width:3%;">C</th>
				<th style="width:20%;">NB colis déposé + sign</th>
				<th>Commentaire</th>
			</tr>

<?		$i=0;
		foreach ($tournee as $val) {	
			if ($val['nom_adh'] != $old_adh) { // nouvelle adh --> on affiche l'adresse de livraison
?>
				<tr class="separateur"><td colspan="7" class="adresse"><?=$val['adr_adh']?></td></tr>
<?			} ?>
			<tr id="bon-<?=$val['no_bon']?>-<?=$val['no_cli']?>" style="background-color:<?=$i&1?'#FBFBFB':'white'?>;">
				<td><?=$val['prepa']?></td>
				<td><?=$val['nom_adh']?></td>
				<td><?=$val['no_bon']?></td>
				<td style="font-size:0.8em;">
					<? $tmp = array();
						if ($val['colis'])	$tmp[]="$val[colis]&nbsp;colis";
						if ($val['pal'])	$tmp[]="$val[pal]&nbsp;pal";
						if ($val['ce'])		$tmp[]="$val[ce]&nbsp;ce";
						if ($val['paroi'])	$tmp[]="$val[paroi]&nbsp;paroi";
						if ($val['pvc'])	$tmp[]="$val[pvc]&nbsp;pvc";
						if ($val['cu'])		$tmp[]="$val[cu]&nbsp;cu";
					?><?=join(' / ',$tmp)?>
				</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td><input	type="button"
							id="exclure-<?=$val['no_bon']?>-<?=$val['no_cli']?>"
							class="button annuler hide_when_print"
<?							if ($val['prepa'] == 'CLH') { // si c'est un bon a CLH, on change l'apparence ?>
							style="color:grey;background-image:url('../../js/boutton_images/cancel-grey.png');"
<?							} ?>
							value="Exclure"
							onclick="modifie_date_liv('<?=$val['prepa']?>','<?=$val['no_bon']?>','<?=$val['no_cli']?>');" />
				</td><!-- commentaire + bouton de suppression du bon -->
			</tr>
<?
			$old_adh = $val['nom_adh'];
			$i++;
		} // fin for tournée
	} // fin foreah chauffeur ?>
	</table>
<?	odbc_close($loginor);
} // fin if date
?>

</form>
</body>
</html>