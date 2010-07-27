<?php

include('../../inc/config.php');

$demain_ddmmyyyy	= date('d/m/Y' , mktime(0,0,0,date('m'),date('d')+1,date('Y')));
$date_yyyymmdd		= '';
$date_ddmmyyyy		= '';
if (isset($_POST['filtre_date']) && $_POST['filtre_date']) {
	$date_yyyymmdd = join('-',array_reverse(explode('/',$_POST['filtre_date'])));
	$date_ddmmyyyy = $_POST['filtre_date'];
}


/*$start_lat    = 47.6646733 ;
$start_long   = -2.476453 ;
$dest['LAT']  = 48.851932 ;
$dest['LONG'] = -2.423086 ;
$vector_dist = sqrt( pow(2, $dest['LAT'] - $start_lat ) + pow(2, $dest['LONG'] - $start_long ) );
echo $vector_dist;
exit;*/

// analyse le tableau des tournées pour connaitre les chauffeurs
$chauffeurs = array();
foreach ($tournee_chauffeur as $tournee)
	foreach ($tournee as $chauff)
		$chauffeurs[$chauff]=1;

$chauffeurs = array_keys($chauffeurs);

?>
<html>
<head>
	<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
	<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
	<script type="text/javascript" src="overlay.googlemaps.js"></script><!-- lib pour afficher les panneaux avec le nom des adhérents -->
	<title>Carte de la tournée</title>
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
	-moz-border-radius: 5px;
	border-radius: 5px;
	border:solid 2px black;
	color:black;
	/*color:#005500;*/
	font-size:0.5em;
	font-family:terminal;
	text-align:center;
	opacity:0.8;
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
<body style="margin:0px;">
<form name="tournee" method="post">
	<div>
		Chauffeur : <select name="chauffeur">
			<option value=""<?= isset($_POST['chauffeur']) && $_POST['chauffeur'] == '' ? ' selected':'' ?>>Tous</option>
<?			foreach ($chauffeurs as $chauffeur) { ?>
				<option value="<?=$chauffeur?>"<?= isset($_POST['chauffeur']) && $_POST['chauffeur'] == $chauffeur ? ' selected':'' ?>><?=$chauffeur?></option>
<?			} ?>
			<option value="non_definit"<?= isset($_POST['chauffeur']) && $_POST['chauffeur'] == 'non_definit' ? ' selected':'' ?>>Non définit</option>
		</select>
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
		</script>&nbsp;&nbsp;
		Afficher le tracé
		<select name="roads_type">
			<option value=""      <?=  isset($_POST['roads_type']) && $_POST['roads_type']==''      ? ' selected' : '' ?>>non</option>
			<option value="roads" <?=  isset($_POST['roads_type']) && $_POST['roads_type']=='roads' ? ' selected' : '' ?>>par la route</option>
			<option value="lines" <?= !isset($_POST['roads_type']) || $_POST['roads_type']=='lines' ? ' selected' : '' ?>>à vol d'oiseau</option><!-- cas par défaut -->
		</select>

		&nbsp;&nbsp;&nbsp;&nbsp;
		<input type="submit" class="button valider" value="Afficher">
<?	if (isset($_POST['roads_type']) && $_POST['roads_type']=='roads') { ?>
		&nbsp;&nbsp;Distance : <span id="distance"></span>
<?	} ?>
		&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;Debug : <span id="debug"></span>
	</div>
</form>

<? if ($date_yyyymmdd) {
	$day_number = date('w',strtotime($date_yyyymmdd)); ?>

<div id="map_canvas" style="width:100%; height:94%;"></div><!-- canvas pour la carte google -->
<script type="text/javascript">
<?
	$sql = <<<EOT
select	DISTINCT(CLIENT.NOCLI),CLIENT.NOMCL,TOUCL as TOURNEE,
		CLIENT.COFIN as COORDS,			-- coords de l'adresse de facturation
		ADR_LIV.VILLV as LIV_COORDS		-- coords de l'adresse de livraison
from	${LOGINOR_PREFIX_BASE}GESTCOM.AENTBOP1 BON
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ACLIENP1 CLIENT
				on BON.NOCLI=CLIENT.NOCLI
			left join ${LOGINOR_PREFIX_BASE}GESTCOM.ALIVADP1 ADR_LIV
				on BON.NOCLI=ADR_LIV.NOCLI and ADR_LIV.NOLIV='DEPOT'
where		CONCAT(DLSSB,CONCAT(DLASB,CONCAT('-',CONCAT(DLMSB,CONCAT('-',DLJSB)))))='$date_yyyymmdd'
		and TYVTE='LIV'
		and FACAV='F'
		and ETSEE=''
order by NOMCL ASC
EOT;

//echo "<div style='color:red;'>$sql</div>";exit;

	$loginor	= odbc_connect(LOGINOR_DSN,LOGINOR_USER,LOGINOR_PASS) or die("Impossible de se connecter à Loginor via ODBC ($LOGINOR_DSN)");
	$res		= odbc_exec($loginor,$sql) ;
	$data		= array();
	$center		= array(47.683087,47.683087, -2.801085, -2.801085); // min lat, max lat, min long, max long centré sur MCS
	define('MIN_LAT',0);	define('MAX_LAT',1);	define('MIN_LONG',2);	define('MAX_LONG',3);
	while($row = odbc_fetch_array($res)) {
		if (trim($row['LIV_COORDS'])) // une adresse de livraison est spécifié, on remplace celle de facturation
			$row['COORDS'] = $row['LIV_COORDS'];

		if (isset($tournee_chauffeur[$row['TOURNEE']][$day_number]))
			$chauf = $tournee_chauffeur[$row['TOURNEE']][$day_number];
		else
			$chauf = 'Non définit';
		
		if			($_POST['chauffeur'] == '') { // tous les chauffeurs, on ne fait pas de filtre

		} elseif	($_POST['chauffeur'] == 'non_definit' && $chauf != 'Non définit') { // que les transports sans chauffeur
					continue; // on saute le client car il a un chauffeur definit
		} elseif	($_POST['chauffeur'] != $chauf) { // que les transports avec chauffeurs
					continue; // on saute le client car il a un chauffeur aute que celui choisit
		}


		// definition du centre de la carte
		if (trim($row['COORDS'])) {
			list($lat,$long) = explode(',',ereg_replace('[^0-9\.\,\-]','',$row['COORDS']));// on nettoi et éclate les coords géo
			$center[MIN_LAT]  = min($center[MIN_LAT],$lat);
			$center[MAX_LAT]  = max($center[MAX_LAT],$lat);
			$center[MIN_LONG] = min($center[MIN_LONG],$long);
			$center[MAX_LONG] = max($center[MAX_LONG],$long);
			$row['LAT'] = $lat;
			$row['LONG'] = $long;
//			echo "lat=$lat   /   lng=$long\n";

			array_push($data,$row); // on stock toutes les infos pour plus tard
		}
	}
	// ici $data contient toutes les infos de la base loginor
//	print_r($data);
?>
	
	// définition des options de la carte
	var myOptions = {
		zoom: 9,									// Pour afficher le Morbihan
		center: new google.maps.LatLng( <?= ($center[MIN_LAT]+$center[MAX_LAT])/2 ?> , <?= ($center[MIN_LONG]+$center[MAX_LONG])/2 ?> ),		// centré sur le centre des différents points
		mapTypeId: google.maps.MapTypeId.ROADMAP
	};

	var map					= new google.maps.Map(document.getElementById("map_canvas"), myOptions); // creation de la carte
	// si l'on zoom, il faut effacé les anciennes coordonnées (voir la class overlay)
	google.maps.event.addListener(map, 'zoom_changed', function() { overlays_bounds = new Array(); });

	var directionsDisplay	= new google.maps.DirectionsRenderer( { suppressMarkers: true , preserveViewport:true } );
		directionsDisplay.setMap(map);
	var directionsService	= new google.maps.DirectionsService();
	var markers				= new Array();	// les petite fleche sous les panneaux
	var overlays			= new Array(); // les panneaux avec le nom des adhérents
	var distance			= 0;	// calcule de la distance pour le tracé de la route
	var circle				= null;	// le cercle de départ de la ligne flotante
	var ligne				= null; // la ligne flotante

	// gere le tracage de la ligne flottante
	google.maps.event.addListener(map, 'mousemove', function(e) {
		if (circle != null) { // si un point de départ de trait a été fait --> on tarce une pseudo ligne aux déplacements de la souris
			if (ligne != null) ligne.setMap(null) ; // la ligne flotante doit disparaitre
			// on retrace la ligne flottante
			ligne = new google.maps.Polyline( { 'map':map , 'path': [e.latLng, circle.getCenter()] , 'strokeOpacity':0.7 , 'strokeColor':'yellow' , 'strokeWeight':3 , 'geodesic':false } );
		}
	});

	// on s'arrange pour que tous les points de la carte soit visible
	var bounds = new google.maps.LatLngBounds(	new google.maps.LatLng( <?=$center[MIN_LAT]?>, <?=$center[MIN_LONG]?> ), // sw
												new google.maps.LatLng( <?=$center[MAX_LAT]?>, <?=$center[MAX_LONG]?> )  // ne
											);
	map.fitBounds(bounds); // adapte le zoom aux points

	// place la coopérative sur la carte
	markers['MCS'] = new google.maps.Marker({
			position: new google.maps.LatLng(47.683087, -2.801085), // MCS coords
			map: map,
			title:"MCS",
			icon: new google.maps.MarkerImage('gfx/mcs-rouge.png',
						new google.maps.Size(52,28),// This marker is 20 pixels wide by 32 pixels tall.
						new google.maps.Point(0,0),// The origin for this image is 0,0.		
						new google.maps.Point(26,14)// The anchor for this image is center at 8,8.
					)
	});

	// déclare l'image de la petite fleche
	var arrow_icon = new google.maps.MarkerImage('gfx/arrow.png',
						new google.maps.Size(20,20),// This marker is 20 pixels wide by 32 pixels tall.
						new google.maps.Point(0,0),// The origin for this image is 0,0.		
						new google.maps.Point(1,6)// The anchor for this image is center bottom
					)


	// pour chaque client a livrer
<?	foreach ($data as $row) { ?>
		var pos  = new google.maps.LatLng(<?=$row['LAT']?>, <?=$row['LONG']?>);
<?
		$name = htmlentities(trim($row['NOMCL']));
		$name = str_replace('&Eacute;','E',$name);
		$name_wraped = wordwrap($name,10);
		$lines = explode("\n",$name_wraped);
?>
		var name = "<?=$name?>";
		// place a little marker on the point
		markers['<?=$row['NOCLI']?>'] = new google.maps.Marker({ 'position': pos, 'map': map, 'title':name, 'icon': arrow_icon });
		// place a sign with name of the adhérent
		overlays['<?=$row['NOCLI']?>'] = new MyOverlay( { 'map': map, 'text':name, 'width':'65px', 'height':9*<?=sizeof($lines)?>+'px', 'position':pos, 'class':'marker' } );

		// gere le tracage de nouvelles route
		google.maps.event.addListener(markers['<?=$row['NOCLI']?>'], 'mouseover', function() {
				if	   (circle == null) { // encore aucun cercle de déssiné
					//on stock le premier cercle
					circle = new google.maps.Circle({ 'center':this.getPosition(), 'fillColor':'yellow', 'fillOpacity':0.5 , 'map':map , 'radius':500, 'strokeColor':'black', 'strokeOpacity':0.5, 'strokeWeight':2 });
				} else { // deja un cercle de dessiné
					// si c'est le meme, on ne fait rien
					if (circle.getCenter() == this.getPosition()) {
						// ne rien faire
					} else { // un autre point --> on dessine un trait et on supprime les cercles
						draw_lines( [ this.getPosition(), circle.getCenter() ] );
						ligne.setMap(null); // la ligne flotante doit disparaitre
					}
					// dans tous les cas on supprime l'ancien point
					circle.setMap(null);
					circle = null
				}
		});

<?	} // fin for each client
	odbc_close($loginor);
?>

	// gere le tracage de nouvelles route
	google.maps.event.addListener(markers['MCS'], 'mouseover', function() {
			if	   (circle == null) { // encore aucun cercle de déssiné
				//on stock le premier cercle
				circle = new google.maps.Circle({ 'center':this.getPosition(), 'fillColor':'yellow', 'fillOpacity':0.5 , 'map':map , 'radius':500, 'strokeColor':'black', 'strokeOpacity':0.5, 'strokeWeight':2 });
			} else { // deja un cercle de dessiné
				// si c'est le meme, on ne fait rien
				if (circle.getCenter() == this.getPosition()) {
					// ne rien faire
				} else { // un autre point --> on dessine un trait et on supprime les cercles
					draw_lines( [ this.getPosition(), circle.getCenter() ] );
					ligne.setMap(null); // la ligne flotante doit disparaitre
				}
				// dans tous les cas on supprime l'ancien point
				circle.setMap(null);
				circle = null
			}
	});



	var intermediaires = new Array();
	intermediaires.push( markers['MCS'].getPosition() ); // start of the lines
<?	
	// draw the roads
	$not_served_client = array();
	foreach ($data as $row) $not_served_client[$row['NOCLI']] = $row;
	//$not_served_client = array(	'056035' => {NOM=>'toto'} ,
	//						'056067' =>  ...)

	$start_cli	   = 'MCS';
	$start_lat  = 47.683087 ; // MCS lat
	$start_long = -2.801085 ; // MCS lng

	while (sizeof($not_served_client) >= 1) { // tant qu'il reste des destination non servi
	//for($i=1 ; $i<=4 ; $i++) { // test avec 10 destinations
		$best_vector_dist = 10000000000000000000;	// loin, tres loin ...
		$best_cli	      = '';						// client le plus proche du départ
		
		foreach ($not_served_client as $dest) { // pour le départ, on cherche la meilleur destination dans la liste des disponibles
			// sqrt ( (Xb - Xa)² + (Yb -Ya)² )
			// debug du calcul des meilleurs distances
			$vector_dist = sqrt( pow( $dest['LAT'] - $start_lat , 2 ) + pow( $dest['LONG'] - $start_long, 2 ) ); // --> merci pythagore

			if($vector_dist < $best_vector_dist) {
				$best_vector_dist = $vector_dist ; // nouvelle meilleur distance
				$best_cli = $dest['NOCLI'];
			}
		} // fin for $not_served_client
?>
		//document.getElementById('debug').innerHTML += "<br\>BEST s='<?=$start_cli?>' a='<?=$best_cli?>'<br\>";
		intermediaires.push( markers['<?=$best_cli?>'].getPosition() ); // ajoute un noeud au parcours

<?		if ($_POST['roads_type']=='roads') { ?>
			draw_roads( markers['<?=$start_cli?>'].getPosition() , markers['<?=$best_cli?>'].getPosition() );
<?		}

		$start_lat  = $not_served_client[$best_cli]['LAT']; // nouveau départ pour le prochain calcul
		$start_long = $not_served_client[$best_cli]['LONG'];
		$start_cli  = $best_cli;
		unset($not_served_client[$best_cli]); // on supprime le meilleur client des clients non servi
	} // while sizeof not_served
?>

<?	if ($_POST['roads_type']=='lines') { ?>
		draw_lines(intermediaires);
<?	} ?>



	/*function draw_lines(step) {
		new google.maps.Polyline( { 'map':map , 'path': step , 'strokeOpacity':0.3 , 'strokeColor':'#00F' , 'strokeWeight':3 , 'geodesic':false } );
	}*/

	function draw_lines(step) {
		var start_point = step[0];
		for(var i=1 ; i<step.length ; i++) {
			var maLine = new google.maps.Polyline( { 'map':map , 'path': [start_point,step[i]] , 'strokeOpacity':0.5 , 'strokeColor':'#00F' , 'strokeWeight':3 , 'geodesic':false } );
			google.maps.event.addListener(maLine, 'click', function() { this.setMap(null); }); // supprime la ligne si l'on click dessus
			google.maps.event.addListener(maLine, 'mouseover', function() { this.setOptions( {'strokeOpacity':0.2} ) }); // supprime la ligne si l'on click dessus
			google.maps.event.addListener(maLine, 'mouseout',  function() { this.setOptions( {'strokeOpacity':0.5} ) }); // supprime la ligne si l'on click dessus
			start_point = step[i];
		}
	}


	function draw_roads(from,to) {
		var directionsDisplay	= new google.maps.DirectionsRenderer( { 'map':map, 'suppressMarkers': true , 'preserveViewport':true , 'polylineOptions':{ 'strokeOpacity':0.3 } } );
		var directionsService	= new google.maps.DirectionsService();

		var request = {
			origin:			from, // LatLng or string
			destination:	to , // LatLng or string
			travelMode:		google.maps.DirectionsTravelMode.DRIVING
		};

		directionsService.route(request, function(response, status) {
			if (status == google.maps.DirectionsStatus.OK) {

				directionsDisplay.setDirections(response);
				distance += response.routes[0].legs[0].distance.value ;
				document.getElementById('distance').innerHTML = '~' + Math.round(distance/1000) + ' Km' ;
				

			} else if (status == google.maps.DirectionsStatus.INVALID_REQUEST) {
				document.getElementById('debug').innerHTML = 'INVALID_REQUEST';
			} else if (status == google.maps.DirectionsStatus.MAX_WAYPOINTS_EXCEEDED) {
				document.getElementById('debug').innerHTML = 'MAX_WAYPOINTS_EXCEEDED';
			} else if (status == google.maps.DirectionsStatus.NOT_FOUND) {
				document.getElementById('debug').innerHTML = 'NOT_FOUND';
			} else if (status == google.maps.DirectionsStatus.OVER_QUERY_LIMIT) {
				document.getElementById('debug').innerHTML = 'OVER_QUERY_LIMIT';
			} else if (status == google.maps.DirectionsStatus.REQUEST_DENIED) {
				document.getElementById('debug').innerHTML = 'REQUEST_DENIED';
			} else if (status == google.maps.DirectionsStatus.UNKNOWN_ERROR) {
				document.getElementById('debug').innerHTML = 'UNKNOWN_ERROR';
			} else if (status == google.maps.DirectionsStatus.ZERO_RESULTS) {
				document.getElementById('debug').innerHTML = 'ZERO_RESULTS';
			}
		});
	}
	</script>
<?  } // fin if date ?>
</body>
</html>