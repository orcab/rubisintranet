<?php

include('../../inc/config.php');

$date_last_open_day_ddmmyyyy	= '';

$day_today						= date('w'); # regarde le jour de la semaine
if ($day_today == 1) { // si lundi
	$tmp = mktime(0,0,0,date('m'),date('d')-3,date('Y')); // lundi dernier
} else {
	$tmp = mktime(0,0,0,date('m'),date('d')-1,date('Y')); // la veille
}
$date_last_open_day_ddmmyyyy	= date('d/m/Y', $tmp);





// recherche tous les bons fournisseurs de la journée en parametre
$date_yyyymmdd					= '';
$date = array() ;
if (isset($_POST['filtre_date']) && $_POST['filtre_date'] && ereg('^([0-9]{2})\/([0-9]{2})\/([0-9]{2})([0-9]{2})$',$_POST['filtre_date'],$regs)) {
	$date_yyyymmdd					= join('-',array_reverse(explode('/',$_POST['filtre_date'])));
	$date = array($regs[3],$regs[4],$regs[2],$regs[1]) ;

	$sql = <<<EOT
select	CFCLI,CFCLB,DET26,																								-- from cde fournisseur
		DSECS,DSECA,DSECM,DSECJ,LIVSB,NOMSB,AD1SB,AD2SB,CPOSB,BUDSB,DLSSB,DLASB,DLMSB,DLJSB,RFCSB,TELCL,TLCCL,			-- from header
		NOLIG,ARCOM,PROFI,TYCDD,CODAR,DS1DB,DS2DB,DS3DB,CONSA,QTESA,UNICD,NOMFO,REFFO,DET97,							-- from detail
		LOCAL																											-- from stock

from	${LOGINOR_PREFIX_BASE}GESTCOM.ACFDETP1 CDE_FOURNISSEUR
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ADETBOP1 CDE_ADHERENT
			on	CDE_FOURNISSEUR.CFCLI = CDE_ADHERENT.NOCLI			and CDE_FOURNISSEUR.CFCLB = CDE_ADHERENT.NOBON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 CDE_ADHERENT_HEADER
			on	CDE_FOURNISSEUR.CFCLI = CDE_ADHERENT_HEADER.NOCLI	and CDE_FOURNISSEUR.CFCLB = CDE_ADHERENT_HEADER.NOBON
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
			on	CDE_FOURNISSEUR.CFCLI = CLIENT.NOCLI
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.AFOURNP1 FOURNISSEUR
			on	CDE_FOURNISSEUR.NOFOU=FOURNISSEUR.NOFOU
		left join ${LOGINOR_PREFIX_BASE}GESTCOM.ASTOFIP1 STOCK
			on	CDE_FOURNISSEUR.CFART=STOCK.NOART and STOCK.DEPOT='${LOGINOR_DEPOT}'

where		CFDDS='$date[0]' and CFDDA='$date[1]' and CFDDM='$date[2]' and CFDDJ='$date[3]'	-- les bons de la date
		and CDE_FOURNISSEUR.CFDET=''														-- bon pas annulé
		and CDE_FOURNISSEUR.CFPRF='1'														-- pas un commentaire
		and CDE_FOURNISSEUR.CFCLI<>'' and CFCLB<>''											-- commande spécial
		and CDE_FOURNISSEUR.CDDE1='OUI'														-- ligne réceptionnées
		and CDE_ADHERENT.ETSBE=''															-- bon pas annulé
		and CDE_ADHERENT.TRAIT='R'															-- ligne non livré
ORDER BY
		CDE_FOURNISSEUR.CFCLI ASC,
		CDE_FOURNISSEUR.CFCLB ASC,
		STOCK.LOCAL ASC
EOT;
		
	//echo $sql ;// exit;

	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;
	$commande_adh = array();
	$ligne_cde_ok = array();
	while($row = odbc_fetch_array($res)) {
		if ($row['DET26'] == 'O') { // receptionné
			if (isset($commande_adh[$row['CFCLI'].'.'.$row['CFCLB']])) { // commande deja rencontrée
				if ($commande_adh[$row['CFCLI'].'.'.$row['CFCLB']] == 1) { // il n'y a pas de ligne F
					// faire quelques chose pour dire d'imprimer la ligne
					array_push($ligne_cde_ok,$row);
				}
			} else { // commande pas encore rencontrée
				$commande_adh[$row['CFCLI'].'.'.$row['CFCLB']] = 1;
				array_push($ligne_cde_ok,$row);
			}
		} else {
			$commande_adh[$row['CFCLI'].'.'.$row['CFCLB']] = 0;
		}

	} // fin while
	unset($row);

	/*
	echo "<pre>" ; print_r($ligne_cde_ok) ; echo "</pre>\n" ;
	echo "<pre>" ; print_r($commande_adh) ; echo "</pre>\n" ;

//	exit; */
	include_once('edition_pdf.php');

} // fin if date de renseigné


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
	<div style="font-size:0.8em;" class="hide_when_print">
	Examiner <strong>les commandes fournisseur</strong> de la journée du
		<input type="text" id="filtre_date" name="filtre_date" value="<?=$date_last_open_day_ddmmyyyy?>" size="8">
		<button id="trigger_date" style="background:url('../../js/jscalendar/calendar.gif') no-repeat left top;border:none;cursor:pointer;) no-repeat left top;">&nbsp;</button><img src="/intranet/gfx/delete_micro.gif" onclick="document.tournee.filtre_date.value='';">
		<script type="text/javascript">
		  Calendar.setup(
			{
			  inputField	: 'filtre_date',         // ID of the input field
			  ifFormat		: '%d/%m/%Y',    // the date format
			  button		: 'trigger_date',       // ID of the button
			  date			: '<?=$date_last_open_day_ddmmyyyy?>',
			  firstDay 		: 1
			}
		  );
		</script>

		<input type="submit" class="button valider" value="Afficher">
	</div>
</form>



</body>
</html>