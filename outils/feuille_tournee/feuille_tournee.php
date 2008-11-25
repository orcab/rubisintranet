<?php

include('../../inc/config.php');


$tournee_chauffeur = array(
	'124' =>	array(	'1' => 'PHILIPPE 1',
						'2' => 'PHILIPPE 2',
						'4' => 'GILLES'
				),
	'134' =>	array(	'1' => 'GILLES',
						'3' => 'PHILIPPE 1',
						'4' => 'PHILIPPE 2'
				),
	'135' =>	array(	'1' => 'PHILIPPE 2',
						'3' => 'GILLES',
						'5' => 'PHILIPPE 1'
				),
	'235' =>	array(	'2' => 'PHILIPPE 1',
						'3' => 'PHILIPPE 2',
						'5' => 'GILLES'
				),
	'245' =>	array(	'2' => 'GILLES',
						'4' => 'PHILIPPE 1',
						'5' => 'PHILIPPE 2'
				)
);

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
body {
	font-family:verdana;
}

table#tournee {
	border:solid 1px black;
	border-collapse:collapse;
	width:100%;
	margin-bottom:20px;
	page-break-after:always;
}

table#tournee td,table#tournee th {
	border:solid 1px black;
}

table#tournee th {
	background-color:#F2F2F2;
}

table#tournee td {
	height:60px;
}

@media print {
	.hide_when_print { display:none; }
}

</style>

<style type="text/css">@import url(../../js/boutton.css);</style>
<style type="text/css">@import url(../../js/jscalendar/calendar-brown.css);</style>
<script type="text/javascript" src="../../js/jscalendar/calendar.js"></script>
<script type="text/javascript" src="../../js/jscalendar/lang/calendar-fr.js"></script>
<script type="text/javascript" src="../../js/jscalendar/calendar-setup.js"></script>

</head>
<body>
<form name="tournee" method="post">
	<div class="hide_when_print">
		<input type="text" id="filtre_date" name="filtre_date" value="<?=$date_ddmmyyyy?$date_ddmmyyyy:$demain_ddmmyyyy?>" size="8">
		<button id="trigger_date" style="background:url('../../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.tournee.filtre_date.value='';">
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

		<input type="submit" class="button valider" value="Afficher">
	</div>
</form>

<?

if ($date_yyyymmdd) {
	$day_number = date('w',strtotime($date_yyyymmdd));

	$sql = <<<EOT
select AFAGESTCOM.ACLIENP1.NOCLI,AFAGESTCOM.ACLIENP1.NOMCL,TOUCL,NOBON,ENT32,NINT1,NINT2,NINT3,NINT4,NINT5,NINT6
from AFAGESTCOM.AENTBOP1,AFAGESTCOM.ACLIENP1
where
CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB)))))='$date_yyyymmdd'
and TYVTE='LIV'
and FACAV='F'
and ETSEE=''
and AFAGESTCOM.AENTBOP1.NOCLI=AFAGESTCOM.ACLIENP1.NOCLI
order by TOUCL ASC, NOMCL ASC
EOT;

//echo "<div style='color:red;'>$sql</div>";

	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;

	$livraison = array();
	while($row = odbc_fetch_array($res)) {

			$sql = <<<EOT
select count(ETSBE) as NB_LIGNE_A_LIVRE
from AFAGESTCOM.ADETBOP1
where 
		NOBON='$row[NOBON]'
	and	NOCLI='$row[NOCLI]'
	and TRAIT='F'
	and PROFI='1'
	and ETSBE=''
EOT;
			$res_detail	= odbc_exec($loginor,$sql) ;
			$row_detail	= odbc_fetch_array($res_detail);
			//print_r($row_detail); exit;

			if ($row_detail['NB_LIGNE_A_LIVRE'] > 0) {

				if (isset($tournee_chauffeur[$row['TOUCL']][$day_number]))
					$chauf = $tournee_chauffeur[$row['TOUCL']][$day_number];
				else
					$chauf = 'Non définit';

				if (!isset($livraison[$chauf]))
					$livraison[$chauf] = array();
					
				$livraison[$chauf][] = array(	'nom_adh'=>$row['NOMCL'],
												'no_bon'=>$row['NOBON'],
												'prepa'	=>$row['ENT32'],
												'colis'	=>ereg_replace('^0+','',trim($row['NINT1'])),
												'pal'	=>ereg_replace('^0+','',trim($row['NINT2'])),
												'ce'	=>ereg_replace('^0+','',trim($row['NINT3'])),
												'paroi'	=>ereg_replace('^0+','',trim($row['NINT4'])),
												'pvc'	=>ereg_replace('^0+','',trim($row['NINT5'])),
												'cu'	=>ereg_replace('^0+','',trim($row['NINT6'])),
											);
			} // fin si nb_ligne a livré >0
	}

	/*echo "<pre>" ;
	print_r($livraison);
	echo "</pre>" ; exit;
	*/

	//$livraison = array('GILLES' => array( array('CAB 56 ','F80107') ));

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
		foreach ($tournee as $val) { ?>
			<tr style="background-color:<?=$i&1?'#FBFBFB':'white'?>;">
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
				<td>&nbsp;</td>
			</tr>
<?			$i++;
		} // fin for tournée

		while (($i%15) > 0) {
			//echo "\$i=$i    \$i%16=".($i%16)."<br>\n"; ?>
			<tr style="background-color:<?=$i&1?'#FBFBFB':'white'?>;">
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
				<td>&nbsp;</td>
			</tr>
<?			$i++;
		}
	} // fin foreah chauffeur ?>
	</table>
<?	odbc_close($loginor);
} // fin if date
?>

</body>
</html>