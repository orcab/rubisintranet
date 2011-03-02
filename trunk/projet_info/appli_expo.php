<html>
<head>
<?  include 'connexion.php';  ?>
<title>Application Salle exposition MCS</title>  
<meta name="viewport" content="initial-scale=1.0, user-scalable=no" />
<meta http-equiv="Content-Type" content="text/html; charset=ISO-8859-1" />
<!--  Include de la google API -->
<script type="text/javascript" src="http://maps.google.com/maps/api/js?sensor=false"></script>
<!--  Librairie Jquery -->
<script type="text/javascript" src="js/jquery.js"></script>
<script type="text/javascript" src="js/jquery-ui.js"></script>
<!-- Include du plugin pour decode base64-->
<script type="text/javascript" src="js/jquery.base64.min.js"></script>
<!-- include du plugin jquery tooltips pour afficher des tooltip-->
<script type="text/javascript" src="js/jquery.poshytip.min.js"></script>
<!-- Fonction de l'application-->
<script type="text/javascript" src="function.js"></script>
<!-- include du css de l'application -->
<link rel="stylesheet" href="stylesheet.css" type="text/css" /> 
<!-- include du style des tooltip, d'autre disponible -->
<link rel="stylesheet" href="js/tip-darkgray/tip-darkgray.css" type="text/css" /> 
<link rel="stylesheet" href="js/tip-darkgray/tip-darkgray2.css" type="text/css" /> 
<script type="text/javascript">

var markersArray = []; //Tableau pour l'affichage du point rouge sur la carte
var map; //D�claration de la carte
var tab;
var bestTenAdh = new Array(); // les 10 adh�rents les plus proches (listing en encart)
var numero_artisan; //numero d'artisan parametre pour l'impression
var effects = ["clip", "blind", "drop","slide"];
var timer_appli ; // timer de l appli
//Variable pour les clic de filtre.
var onElec = false ;
var onPlomb = false ;

//Initialise le placement du click au centre du rond rouge
var rond_rouge = new google.maps.MarkerImage('images/rond_rouge.png',
	 // This marker is 20 pixels wide by 32 pixels tall.
	 new google.maps.Size(71, 71),
	 // The origin for this image is 0,0.
	 new google.maps.Point(0,0),
	 // The anchor for this image is the base of the flagpole at 0,32.
	 new google.maps.Point(35, 35)
);

var vannes = new google.maps.LatLng(47.8,-2.9); //Coordonn�e du centre de la carte du morbihan
  
function initialize() {

	//Cr�ation des tableaux pour les adh et les markers
	var markers		= new Array();
	var infowindows = new Array();
	var infoadhs	= new Array();
	
	//D�finition des options de la carte
	var myOptions = {
		zoom: 10, //Pour afficher le Morbihan
		center: vannes, //D�fini Vannes comme centre
		disableDefaultUI: true, //D�sactive l'interface de google API
		streetViewControl: false, //D�sactive Google Street View
		rightclick : false,
		mapTypeId: google.maps.MapTypeId.ROADMAP, // D�termine le type de carte
		/*ROADMAP displays the normal, default 2D tiles of Google Maps.
		* SATELLITE displays photographic tiles.
		* HYBRID displays a mix of photographic tiles and a tile layer for prominent features (roads, city names).
		* TERRAIN displays physical relief tiles for displaying elevation and water features (mountains, rivers, etc.).
		*/
	};

	map = new google.maps.Map(document.getElementById('map_canvas'), myOptions);
	
	//Cr�ation de la div pour zoomIn et placement de celle si sur la map
	var zoomInControlDiv = document.createElement('DIV');
	var zoomInControl = new ZoomInControl(zoomInControlDiv, map);
	zoomInControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT].push(zoomInControlDiv);
	
	//Cr�ation de la div pour zoomOut et placement de celle si sur la map
	var zoomOutControlDiv = document.createElement('DIV');
	var zoomOutControl = new ZoomOutControl(zoomOutControlDiv, map);
	zoomOutControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT].push(zoomOutControlDiv);
	
	//Cr�ation de la div pour home et placement de celle si sur la map
	var homeControlDiv = document.createElement('DIV');
	var homeControl = new HomeControl(homeControlDiv, map);
	homeControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT].push(homeControlDiv);

	//Cr�ation de la div pour help et placement de celle si sur la map	
	/*var helpControlDiv = document.createElement('DIV');
	var helpControl = new HelpControl(helpControlDiv, map);
	helpControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.BOTTOM_RIGHT].push(helpControlDiv);
	*/

	//Cr�ation de la div pour les adh proche
	var adhControlDiv = document.createElement('DIV');
	var adhControl = new AdhControl(adhControlDiv, map);
	adhControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.RIGHT].push(adhControlDiv);
	
	//Icone du filtre electricien
	var afficherElecControlDiv = document.createElement('DIV');
	var afficherElecControl = new AfficherElecControl(afficherElecControlDiv, map);
	afficherElecControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.BOTTOM].push(afficherElecControlDiv);
	
	//Icone du filtre plombier
	var afficherPlombControlDiv = document.createElement('DIV');
	var afficherPlombControl = new AfficherPlombControl(afficherPlombControlDiv, map);
	afficherPlombControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.BOTTOM].push(afficherPlombControlDiv);
	
	//Legend des markers	
	var legendControlDiv = document.createElement('DIV');
	var legendControl = new LegendControl(legendControlDiv, map);
	legendControlDiv.index = 1;
	map.controls[google.maps.ControlPosition.BOTTOM_LEFT].push(legendControlDiv);
	
<?
	// connexion � la base mysql	
	connexion();
	
	define('PLOMBIER',		1 << 0);
	define('ELECTRICIEN',	1 << 1);

	$res = mysql_query("SELECT * FROM artisan WHERE numero<>'056039' AND suspendu=0 ORDER BY ville ASC") or die("Erreur dans la requette SQL (".mysql_error().")") ; //R�cup�re les infos geographiques adh
	while($row = mysql_fetch_array($res)) { //Pour chaque adh
		
		if ($row['geo_coords']) { //Si il a des coordon�es g�ographique
			list($lat,$lng) = explode(',',preg_replace('[^0-9\.\,\-]','',$row['geo_coords']));//On nettoie et �clate les coords g�o
			$infowindow = '<address>';
			$infowindow .= $row['adr1'] ? htmlentities($row['adr1'])."<br/>" : '';
			$infowindow .= $row['adr2'] ? htmlentities($row['adr2'])."<br/>" : '';
			$infowindow .= $row['adr3'] ? htmlentities($row['adr3'])."<br/>" : '';
			$infowindow .= htmlentities($row['cp']).' <strong>'.htmlentities($row['ville']).'</strong>';
			$infowindow .= $row['tel1'] ? '<br/>'.htmlentities("T�l�phone : $row[tel1]") : '';
			$infowindow .= '</address>' ;
			$infowindow .= $row['email'] ? 'Contacter par email : <a href=\\"mailto:'.$row['email'].'\\">'.$row['email'].'</a>' : '';		
	?>
		// Cr�ation d'un point adh�rent
		markers['<?=$row['numero']?>'] = new google.maps.Marker({
			position: new google.maps.LatLng(<?=$lat?>, <?=$lng?>),
			map: map,
			title:"<?=htmlentities($row['nom'])?>"
			<? if ($row['activite'] & PLOMBIER && $row['activite'] &  ELECTRICIEN) { ?> 
				,icon: 'images/both.png'
			<? } elseif ($row['activite'] & PLOMBIER) { ?>
				,icon: 'images/plombier.png'
			<? } elseif ($row['activite'] & ELECTRICIEN) { ?>
				,icon: 'images/electricien.png'
			<? } ?>
		});
			
		//G�n�ration d'un tableau javascript avec les infos adh de base
		infoadhs['<?=$row['numero']?>'] = new Array(
						"<?=htmlentities($row['nom'])?>",
						"<?=htmlentities($row['adr1'])?>",
						"<?=htmlentities($row['ville'])?>",
						"<?=htmlentities($row['cp'])?>",
						"<?=htmlentities($row['tel1'])?>",
						"<?=htmlentities($row['tel3'])?>",
						"<?=htmlentities($row['email'])?>",
						"<?=htmlentities($row['activite'])?>",
						"<?=htmlentities($lat)?>",
						"<?=htmlentities($lng)?>"
					);


		//R�cup�ration du num�ro d'adh au click
		google.maps.event.addListener(markers['<?=$row['numero']?>'], 'click', function() {
		//Au clic,le code se d�clenche
		//Post: m�thode utilis� pour envoy� le num�ro adh, url: page � ouvrir, data: d�cris la fonction � utiliser ainsi que le param�tre envoy�, success: en cas de reussite �venement qui se passe,jQuery.parseJSON : permet de transformer le json en objet
			clearMap();
			//Affiche la div de pr�sentation d un adh�rent
			affichageDivAdh('<?=$row['numero']?>');
			
		});//Fin R�cup�ration du num�ro d'adh au click
	
<?		} // fin if geo_coords
	} // fin pour chaque adh ?>
	
	//Appel les fonctions pour le rond rouge au clic
	google.maps.event.addListener(map, 'click', function(event) { 
	 	addMarker(event.latLng);
		setTimeout('deleteOverlays()',500);	
	});
	
	//Variable pour se d�placer au dernier centre valide des bords
	var dernier_centre_valide = vannes;
	var MAP_MAXIMUM_BOUNDS = new google.maps.LatLngBounds(
		new google.maps.LatLng(46.878968,-4.770813),
		new google.maps.LatLng(48.460173,-1.485901)
	);
	
	//Fonction qui se declenche lorsque les bords changent
	google.maps.event.addListener(map,'click', function(event) {
		
		clearMap();
		//Vide le tableau des 10 adh les plus proches
		bestTenAdh=[];
		//recenterCarte
		recenterCarte();
		//Affiche les info adh dans la case de droite
		remplissageInfoDivAdh();
			
	});
	
	//Fonction qui se d�clenche � la fin d'un drag
	google.maps.event.addListener(map,'dragend', function(event) {
		
		clearMap();
		//Vide le tableau des 10 adh les plus proches
		bestTenAdh=[];
		//recenterCarte	
		recenterCarte();
		//Affiche les info adh dans la case de droite
		remplissageInfoDivAdh();
		
	});
	
	//Recentre la carte si on d�passe les borne
	function recenterCarte() {
		//Test si les bords du SouthWest sont valides
		if (MAP_MAXIMUM_BOUNDS.contains(map.getBounds().getSouthWest())) {
			//Test si les bords du northEast sont valides
			if (MAP_MAXIMUM_BOUNDS.contains(map.getBounds().getNorthEast())) {
				dernier_centre_valide = map.getCenter(); //Recupere le centre actuel
			} else {
				map.panTo(dernier_centre_valide);//Recentre au dernier centre ne d�passant pas les limites
			}
		} else {
			map.panTo(dernier_centre_valide);//Recentre au dernier centre ne d�passant pas les limites
		}
	}
	
	//Affiche les infos adh si la boite est ouverte
	function remplissageInfoDivAdh() {
		//Remplissage des infos adh�rents
		$('#affichage').html('');//Vidage de la div affichage
		//Si la div est ouverte
		if ($('#adhcontrol').children('img').attr('src')== "images/icon_map/close64px.png") {
			infoDivAdh();
		}
	}

	//Fonction du bouton casier
	function AdhControl(controlDiv, map) {
		$(controlDiv).css({	'margin':'6px',
							'font-family': 'Verdana',
							'font-size': '22',
							'z-index':'50000'
						});	

		$(controlDiv)	.addClass('tooltips1 adh10')		//Info bulle d'aide
						.attr('title',"Adh�rents proches")
						.attr('id','adhcontrol')			//D�fini une Id  la div	
						.html('<img src="images/icon_map/add64px.png"/><div id="affichage"></div><div id="imprimer"></div>'); //Insere l image de la div, la div affichage et la div imprimer
		
		google.maps.event.addDomListener(controlDiv, 'click', function() {
			clearMap();
			
			//Creer dans la DIV imprimer un bouton fermer et un lien pour imprimer
			$('#imprimer').html('<a id="impression_liste" href="#" onclick="PrintControl()"><img src="images/icon_map/print64px.png" style="border:none"></a><input type="button" id="close_liste" />');
			
			//Fonction du bouton close_liste
			$('#close_liste').click (function() {
									bestTenAdh = [];
									$('#affichage').html('');
									$('#imprimer').css({ 'display': 'none' });
									$(controlDiv).animate({//Donne un effet au d�ploiement de la Div
										'width':'64px',
										'height':'64px',
									 }, 500,function() { //Callback permettant d'effectuer ce css apres que l'instruction soit termin�e			
														$(controlDiv).children('img').attr('src',"images/icon_map/add64px.png");//Remet l'image permettant d'ouvrir la Div
														//Css de la div
														$(controlDiv).css({	'background-color': 'rgba(255,255,255,0)',
																			'border': '0px solid black',
																			'-moz-border-radius':'0em',
																			'padding':'0px'
														});
														//Affiche l'image de d'ouverture de la div
														$(controlDiv).children('img').css({ 'display': 'inline' });
														$('.adh10').poshytip('show').addClass('tooltips_showed');
											});									
							});
			
			//Condition si il est ferm�
			if ($(controlDiv).children('img').attr('src')== "images/icon_map/add64px.png") {
				$('.adh10').poshytip('hide').removeClass('tooltips_showed'); // Masque l'aide de 10adh
				$(controlDiv).css({	'background-color': 'rgba(57,203,251,0.8)',
									'-moz-border-radius':'1em',
									'padding':'5px'
								});
				$(controlDiv).animate({	'width':'400px',
										'height':'600px'
									},500);//Effet d'ouverture		
				$('#imprimer').css({ 	'display': 'inline' ,
										'position' : 'absolute',
										'bottom':'0px'
									}); //Reaffiche la div imprimer
				
				$(controlDiv).children('img')	.attr('src',"images/icon_map/close64px.png")	//Empeche le rechargement de la div
												.css({	'display': 'none' });					//Masque l'image qui montre que la div est ouverte
				infoDivAdh(); //Rempli la div d'info
			} 
		});
	}//Fin adhcontrol
	  
	//Function de l'affichage des informations dans la div d'info
	function infoDivAdh () {	
		var center = map.getCenter();
		var centerLat = center.lat();
		var centerLng = center.lng();
		tab	= new Array(); //Tableau associatif des num�ros adh et des distances.	
								
		if (onPlomb==true) { // Markers des electriciens masqu�s
			for (i in infoadhs) {
				if (infoadhs[i][7] & 1<<0) { 
					tab[i] = latLngDistance(centerLat,centerLng,infoadhs[i][8],infoadhs[i][9]);
				}
			}	
		} else if (onElec==true) { // Markers des plombiers masqu�s
			for (i in infoadhs) {
				if (infoadhs[i][7] & 1<<1) { 
					tab[i] = latLngDistance(centerLat,centerLng,infoadhs[i][8],infoadhs[i][9]);
				}
			}
		} else if (onElec==false && onPlomb==false) { //Tous afficher (onPlomb & onElec == false)			
			for (i in infoadhs) {
				tab[i] = latLngDistance(centerLat,centerLng,infoadhs[i][8],infoadhs[i][9]);	
			}					
		}		
		
		tab = sortByValue(tab); //Ecrase le tableau associatif et le range en fonction des distances

		//Boucle permettant d'afficher les 10 adh les plus proches du centre de la carte
		for (i in tab) {
			if (i > 9) { //Si plus de 10 adh alors on casse la boucle
				break;	
			} else {
				bestTenAdh.push(tab[i]);//Met uniquement les 10 adh�rents les plus proches dans le tableau
				$('#affichage').append( '<div style="margin-bottom:15px;"><a href="#" onclick="affichageDivAdh(\''+tab[i]+'\')" >'+
											((infoadhs[tab[i]][7]==1 ? '<img src="images/10_adhplombier-mini.png"/>': "") //image de l'activit� de l'adh
										||	 (infoadhs[tab[i]][7]==2 ? '<img src="images/10_adhelectricien-mini.png"/>': "")
										||	 (infoadhs[tab[i]][7]==3 ? '<img src="images/10_adhboth-mini.png"/>': ""))
										+'  '+infoadhs[tab[i]][0] +'</div>'); //Si - de 10 adh afficher on afficher les adh dans la div								
			}
		}	
	}

	//Fonction pour afficher les marker
	function affichageMarker(activite, affichage) { 
		//Passe les markers en visible
		for ( i in infoadhs) {		
				markers[i].setVisible(true);
		}	
		//Passe les marker de l'activit� du filtre en visible ou invisible
		for ( i in infoadhs) {
			if ((infoadhs[i][7] | activite) == activite ) {// si adh est un plombier, << d�calage de bit a gauche (multiplie par 2)
				markers[i].setVisible(affichage);
			}
		}	
	}		

	//Fonction du filtre plombier
	function AfficherPlombControl(controlDiv, map) {
		//Propri�t� du filtre
		$(controlDiv).attr('id','plombfiltre');	
		$(controlDiv).css('margin','6px');
		$(controlDiv).html('<img src="images/icon_map/plombier64px.png"/>');
		$(controlDiv).addClass('tooltips2').attr('title',"Affiche les plombiers"); 
		google.maps.event.addDomListener(controlDiv, 'click', function() {			
			clearMap();
			//Si les markers sont affich�s
			if (onPlomb == false) {	
				bestTenAdh = [];
				affichageMarker(1<<1,false);
				onPlomb = true ;
				onElec=false;
			//Si les markers ne sont pas affich�s		
			} else if (onPlomb ==true) {
				bestTenAdh = [];
				affichageMarker(1<<1,true);
				onPlomb = false ;
				onElec=false;	
			}
			remplissageInfoDivAdh();				
		});
	}	
	
	//Fonction du filtre electricien	
	function AfficherElecControl(controlDiv, map) {	
		//Propri�t� du filtre
		$(controlDiv).css('margin','6px');
		$(controlDiv).attr('id','elecfiltre');
		$(controlDiv).html('<img src="images/icon_map/electricien64px.png"/>');
		$(controlDiv).addClass('tooltips3').attr('title',"Affiche les �lectriciens");	
		google.maps.event.addDomListener(controlDiv, 'click', function() {		
			clearMap();	
			//Si les markers sont affich�s
			if (onElec == false) {
				bestTenAdh = [];
				affichageMarker(1<<0,false);			
				onElec = true ;	
				onPlomb = false ;
			//Si les markers ne sont pas affich�s	
			} else if (onElec == true) {
				bestTenAdh = [];
				affichageMarker(1<<0,true);
				onElec = false ;
				onPlomb = false ;
			}	
		remplissageInfoDivAdh();							
		});		
	}	

	
	setTimeout('aideDefault()',2000);//affiche l'aide aubout d'une seconde
	
}//Fin fonction initialize

</script>
</head>

<body oncontextmenu="return bloc()" onload="initialize();" style="margin:0px;">
<div id="map_canvas" style="width:100%; height:100%;"></div>
<!--Bloc le clic droit sur le body -->
<script> function bloc() { return false; }  </script>
<!--Tableau servant � l'affichage des donn�es-->
<div id="fonds"></div>
<table id="cadre">
<tr><td id="logo-mcs"></td><td id="nom-adherent"></td><td id="logo-artipole"></td></tr>
<tr><td id="photo0"></td><td id="donnee"></td><td id="photo2"></td></td></tr>
<tr><td id="desc"></td><td id="photo1"></td><td id="photo5"></td></tr>
<tr><td id="photo3"></td><td id="photo4"></td><td id="fermer"></td></tr>
</table>
<!--<div id="fondsimprim"></div> -->
<div id="endImprim"></div>
</body>
</html>