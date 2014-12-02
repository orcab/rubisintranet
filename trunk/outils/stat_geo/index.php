<?php

include('../../inc/config.php');

define('SQLITE_DATABASE'	,'../../scripts/cde_rubis.db');

// si aucun jour de coché, on les coche tous
if (	!isset($_POST['jour1'])
	&& 	!isset($_POST['jour2'])
	&& 	!isset($_POST['jour3'])
	&& 	!isset($_POST['jour4'])
	&& 	!isset($_POST['jour5'])) {
	$_POST['jour1']='on';
	$_POST['jour2']='on';
	$_POST['jour3']='on';
	$_POST['jour4']='on';
	$_POST['jour5']='on';
}

// si aucun type de donnée selecttionné
if (!isset($_POST['type_donnee']))
	$_POST['type_donnee']='montant_bon';

?>
<html>
<head>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<title>Stats géographique</title>
<style>
body {
	font-family:verdana;
}

form {
	font-size:0.9em;
}

.marker {
	position:absolute;
	background-color:#FF776B;
	-webkit-border-radius: 5px;
	border-radius: 5px;
	border:solid 2px black;
	color:black;
	font-size:0.5em;
	font-family:terminal;
	text-align:center;
	opacity:0.8;
}

@media print {
	.hide_when_print { display:none; }
}
</style>


<!-- GESTION DES ICONS EN POLICE -->
<link rel="stylesheet" href="../../js/fontawesome/css/bootstrap.css"><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome.min.css"><!--[if IE 7]><link rel="stylesheet" href="../../js/fontawesome/css/font-awesome-ie7.min.css"><![endif]--><link rel="stylesheet" href="../../js/fontawesome/css/icon-custom.css">

<link rel="stylesheet" href="../../js/ui-lightness/jquery-ui-1.10.3.custom.min.css">
<script type="text/javascript" src="../../js/jquery.js"></script>
<script type="text/javascript" src="../../js/jquery-ui-1.10.3.custom.min.js"></script>
<script language="javascript">
<!--

//initialise les date picker
$.datepicker.setDefaults({
 	dateFormat:'dd/mm/yy',
 	beforeShowDay: $.datepicker.noWeekends,
	changeMonth: true,
	changeYear:true,
	firstDay: 1,
	dayNamesShort: 		['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
	dayNames: 			['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'],
	dayNamesMin: 		['Di', 'Lu', 'Ma', 'Me', 'Je', 'Ve', 'Sa'],
	monthNamesShort: 	['Jan','Fev','Mar','Avr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Déc'],
	monthNames: 		['Janvier','Fevrier','Mars','Avril','Mai','Juin','Juillet','Aout','Septembre','Octobre','Novembre','Décembre']
});

$(document).ready(function(){
	
	$( '#date_from' ).datepicker({
		onClose: function( selectedDate ) {
			$( '#date_to' ).datepicker( 'option', 'minDate', selectedDate );
		}
	});

	$( '#date_to' ).datepicker({
	 	onClose: function( selectedDate ) {
			$( '#date_from' ).datepicker( 'option', 'maxDate', selectedDate );
		}
	});
});

</script>

</head>
<body style="margin:0px;">
<form name="tournee" method="post" action="<?=$_SERVER['PHP_SELF']?>">
	Statistique du
	<input type="text" id="date_from" name="date_from" value="<?= isset($_POST['date_from']) ? $_POST['date_from']:''?>" size="10" maxlength="10"/>
	au
	<input type="text" id="date_to" name="date_to" value="<?= isset($_POST['date_to']) ? $_POST['date_to']:''?>" size="10" maxlength="10"/>
	&nbsp;&nbsp;&nbsp;
	Tourn&eacute;e : 
	Lun<input type="checkbox" name="jour1" id="jour1"<?=isset($_POST['jour1']) ? ' checked="checked"':''?>/>
	Mar<input type="checkbox" name="jour2" id="jour2"<?=isset($_POST['jour2']) ? ' checked="checked"':''?>/>
	Mer<input type="checkbox" name="jour3" id="jour3"<?=isset($_POST['jour3']) ? ' checked="checked"':''?>/>
	Jeu<input type="checkbox" name="jour4" id="jour4"<?=isset($_POST['jour4']) ? ' checked="checked"':''?>/>
	Ven<input type="checkbox" name="jour5" id="jour5"<?=isset($_POST['jour5']) ? ' checked="checked"':''?>/>
	&nbsp;
	Avec CAB56<input type="checkbox" name="cab56" id="cab56"<?=isset($_POST['cab56']) ? ' checked="checked"':''?>/>
	&nbsp;

	<select name="type_donnee" id="type_donnee">
		<option value="montant_bon"	<?=$_POST['type_donnee']=='montant_bon' ? ' selected="selected"':''?>>Chiffre d'affaire</option>
		<option value="nombre_bon"	<?=$_POST['type_donnee']=='nombre_bon' 	? ' selected="selected"':''?>>Nombre de cde</option>
		<option value="nb_ligne"	<?=$_POST['type_donnee']=='nb_ligne' 	? ' selected="selected"':''?>>Nombre de ligne</option>
	</select>
	<a class="btn btn-success" onclick="document.tournee.submit();"><i class="icon-ok"></i> Afficher</a><br/>
</form>


<div id="map_canvas" style="width:100%; height:94%;"></div><!-- canvas pour la carte google -->
<?
	$datas = array();


	$where = array();
	$where[] = "CLIENT.COFIN<>''"; 	// adh ayant des coordonnées
	$where[] = "CLIENT.ETCLE=''"; 	// adh actif
	$where[] = "CLIENT.CATCL='1'"; 	// artisan

	$where_jours = array();
	if (isset($_POST['jour1'])) $where_jours[] = "TOUCL like '%1%'";
	if (isset($_POST['jour2'])) $where_jours[] = "TOUCL like '%2%'";
	if (isset($_POST['jour3'])) $where_jours[] = "TOUCL like '%3%'";
	if (isset($_POST['jour4'])) $where_jours[] = "TOUCL like '%4%'";
	if (isset($_POST['jour5'])) $where_jours[] = "TOUCL like '%5%'";
	if (sizeof($where_jours)>0)
		$where[] = "(".join(' OR ',$where_jours).")";

	if (!isset($_POST['cab56']))
		$where[] = "NOCLI<>'056039'";

	if (sizeof($where)>0)
		$where = " WHERE ".join(' AND ',$where);
	else
		$where = '';


	$sql = <<<EOT
select	CLIENT.NOCLI as CODE_CLIENT,CLIENT.NOMCL as NOM_CLIENT,TOUCL as TOURNEE_CLIENT,
		CLIENT.COFIN as COORDS_CLIENT			-- coords de l'adresse de facturation
from	${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
$where
EOT;

	//echo "<div style='color:red;'>$sql</div>";

	// stock les coordonnées des artisans
	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;	
	while($row = odbc_fetch_array($res)) {
		$datas[$row['CODE_CLIENT']] = array_map('trim',$row);

		$tmp = split(',',$row['COORDS_CLIENT']);
		$datas[$row['CODE_CLIENT']]['lat'] = $tmp[0];
		$datas[$row['CODE_CLIENT']]['lng'] = $tmp[1];
	}

	// stock le CA des artisans
	if (!file_exists(SQLITE_DATABASE)) die ("Base de donnée non présente");
	try {
		$sqlite = new PDO('sqlite:'.SQLITE_DATABASE); // success
		$sqlite->sqliteCreateFunction('REGEXP', 'preg_match', 2); // on cree la fonction REGEXP dans sqlite.
	} catch (PDOException $exception) {
		echo "Erreur dans l'ouverture de la base de données. Merci de prévenir Benjamin au 02.97.69.00.69 ou d'envoyé un mail à <a href='mailto:benjamin.poulain@coopmcs.com&subject=Historique commande en ligne'>Benjamin Poulain</a>";
		die ($exception->getMessage());
	}

	$where = array();
	if (isset($_POST['date_from']) && strlen($_POST['date_from'])>0)
		$where[] = "date_bon>='".join('-',array_reverse(explode('/',$_POST['date_from'])))."'";

	if (isset($_POST['date_to']) && strlen($_POST['date_to'])>0)
		$where[] = "date_bon<='".join('-',array_reverse(explode('/',$_POST['date_to'])))."'";

	if (sizeof($where)>0)
		$where = " WHERE ".join(' AND ',$where);
	else
		$where = '';

	$sql = <<<EOT
SELECT
	COUNT(*) AS nombre_bon,
	CAST(SUM(montant) AS INTEGER) AS montant_bon,
	SUM(nb_ligne) AS nb_ligne,
	numero_artisan
FROM
	cde_rubis
$where
GROUP BY
	numero_artisan
EOT;
	$res		= $sqlite->query($sql) or die("Impossible de lancer la requete de caclcul du CA : ".array_pop($sqlite->errorInfo()));
	while ($row = $res->fetch(PDO::FETCH_ASSOC)) {
		if (isset($datas[$row['numero_artisan']])) {
			$datas[$row['numero_artisan']] = array_merge(	$datas[$row['numero_artisan']], // previous values
															$row 							// new values
														);
		}
	}
	//echo "<div style='color:red;'>$sql</div>";
	//var_dump($datas['056032']);
?>

<script type="text/javascript">
	// définition des options de la carte
	var myOptions = {
		zoom: 10,									// Pour afficher le Morbihan
		center: new google.maps.LatLng( 47.888031, -2.836594 ),		// centré sur LOMINE
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	var map					= new google.maps.Map(document.getElementById('map_canvas'), myOptions); // creation de la carte
	
	// marker MCS
	new google.maps.Marker({
			position: new google.maps.LatLng(47.683087, -2.801085), // MCS coords
			map: map,
			title:"MCS Plescop",
			icon: new google.maps.MarkerImage('gfx/mcs-rouge.png',
						new google.maps.Size(26,14),// taille de l'image
						new google.maps.Point(0,0),// Origine de l'image
						new google.maps.Point(13,7)// center de l'image
					)
		});

	new google.maps.Marker({
			position: new google.maps.LatLng(47.781940, -3.329979), // MCS Caudan coords
			map: map,
			title:"MCS Caudan",
			icon: new google.maps.MarkerImage('gfx/mcs-rouge.png',
						new google.maps.Size(26,14),// taille de l'image
						new google.maps.Point(0,0),// Origine de l'image
						new google.maps.Point(13,7)// center de l'image
					)
		});

	new google.maps.KmlLayer({
    		url:'http://www.coopmcs.com/kml/morbihan.kml',
    		map:map,
    		preserveViewport :true
  		});

<?	$montants = array();
	//var_dump($datas);
	foreach($datas as $key => $val)
		if(isset($val[$_POST['type_donnee']]))
			$montants[] = $val[$_POST['type_donnee']];

	$montant_max = max($montants);

	foreach($datas as $key => $val) {
	// info : 6000 ~ superficie de Vannes
		if (isset($val[$_POST['type_donnee']])) { ?>
			var ca_infos = {
				strokeWeight:0,
		    	fillColor: '#FF0000',
		    	fillOpacity: 0.35,
		    	map: map,
		    	center: new google.maps.LatLng( <?=$val['lat']?>, <?=$val['lng']?> ),
		    	radius: <?=$val[$_POST['type_donnee']] * 6000 / $montant_max * 1.5?>
		    };
		    // Add the circle for this city to the map.
		    new google.maps.Circle(ca_infos);
<?		}
	}
?>
</script>
</body>
</html>