TIMER = 180000 ;

//Reset de la carte
function clearMap() {	
	clearTimeout(timer_appli);
	timer_appli = setTimeout( function () {
						location.reload() ; 
				},TIMER );									
}

//Suppression des markers du tableau markersArray
function deleteOverlays() {
	if (markersArray) {
		for (i in markersArray) {
			markersArray[i].setMap(null); //Masque les markers de la carte
		}
		markersArray.length = 0;//Suppression du contenu du tableau
	}
}

 //Ajout un rond rouge en cas de click sur la carte
function addMarker(location) {
 	clearMap();
	marker = new google.maps.Marker({
			'position': location,
			'map': map,
			'icon' : rond_rouge
			});
	//Place le marker dans le tableau markersArray
	markersArray.push(marker);
	map.setCenter(location); //Détermine l'endroit ou l'on vient de click comme centre
	map.panTo(map.getCenter()); //Recentre la carte
}

//Fonction du bouton home
function HomeControl(controlDiv, map) { 
	$(controlDiv).css('margin','6px');
	$(controlDiv).addClass('tooltips1').attr('title',"Recadre la carte"); // Info bulle d'aide
	$(controlDiv).html('<img src="images/icon_map/home64px.png"/>');
	google.maps.event.addDomListener(controlDiv, 'click', function() {
		clearMap();
		map.panTo(vannes);// Recentre sur Vannes
		map.setZoom(9); // Remet le zoom initial
	});
}

function aideDefault() {
		//console.log("aideDefault");
        $('.tooltips1').each(function() { // Pour chaque élément avec la class "tooltips1"
            if (!$(this).hasClass('tooltips_showed')) { //On test si le tooltip est déjà affiché
                $(this).poshytip({
                    className: 'tip-darkgray',
                    bgImageFrameSize: 11,
                    alignTo:'target',
                    alignX: 'left',
                    alignY: 'center',
                    offsetX: 5,
                    showOn:'focus'
				}).poshytip('show').addClass('tooltips_showed'); // -> On ajoute une class "tooltips_showed" pour savoir qu'il est déja affiché
				
            } else { // cache le tooltips
                $(this).poshytip('hide').removeClass('tooltips_showed'); //Supprime la class "tooltips_showed" pour savoir qu'il est caché
            }
			
        }); // fin each tooltips

		$('.tooltips2').each(function() { //Pour chaque élément avec la class "tooltips2"
		
            if (!$(this).hasClass('tooltips_showed')) { //On test si le tooltip est déjà affiché
               $(this).poshytip({
					className: 'tip-darkgray',
					bgImageFrameSize: 11,
					showOn: 'focus',
					alignTo: 'target',
					alignX: 'inner-left',
					offsetX: 0,
					offsetY: 5
				}).poshytip('show').addClass('tooltips_showed');// -> On ajoute une class "tooltips_showed" pour savoir qu'il est déja affiché
		
            } else { // cache le tooltips
                $(this).poshytip('hide').removeClass('tooltips_showed'); //Supprime la class "tooltips_showed" pour savoir qu'il est caché
            }

        }); // fin each tooltips
		
		$('.tooltips3').each(function() { // pour chaque élément avec la class "tooltips"

            if (!$(this).hasClass('tooltips_showed')) { //On test si le tooltip est déjà affiché
                $(this).poshytip({
                    className: 'tip-darkgray2',
                    bgImageFrameSize: 11,
					showOn: 'focus',
					alignTo: 'target',
					alignX: 'inner-right',
					offsetY : 5,
					offsetX : -5	
				}).poshytip('show').addClass('tooltips_showed'); // -> On ajoute une class "tooltips_showed" pour savoir qu'il est déja affiché
	
            } else { // cache le tooltips
                $(this).poshytip('hide').removeClass('tooltips_showed'); //Supprime la class "tooltips_showed" pour savoir qu'il est caché
            }

        }); // fin each tooltips	
}

//Fonction d'affichage des bulles d'aides auprès des boutons
/*function HelpControl(controlDiv, map) {
    controlDiv.innerHTML = '<img src="images/icon_map/show_info64px.png" />';
	$(controlDiv).addClass('tooltips2 adh10').attr('title',"Masque l'aide"); // Info bulle d'aide
	$(controlDiv).css('margin','6px');
	google.maps.event.addDomListener(controlDiv, 'click', function() { // en cas de click
		clearMap();
		aideDefault();
	}); 
}*/

 //Fonction du bouton zoomIn
function ZoomInControl(controlDiv, map) {
	$(controlDiv).css('margin','6px');
	$(controlDiv).addClass('tooltips1').attr('title',"Zoom avant"); 
	$(controlDiv).html('<img src="images/icon_map/zoom_in64px.png"/>');
	google.maps.event.addDomListener(controlDiv, 'click', function() {
		clearMap();
		//Test si on peut zoomer
		if (map.getZoom()<=15) {
			map.setZoom(map.getZoom()+1);
		}		
	});
}

//Fonction du bouton zoomOut
function ZoomOutControl(controlDiv, map) {	
	$(controlDiv).css('margin','6px');
	$(controlDiv).addClass('tooltips1').attr('title',"Zoom arrière"); 
	$(controlDiv).html('<img src="images/icon_map/zoom_out64px.png"/>');
	google.maps.event.addDomListener(controlDiv, 'click', function() {
		clearMap();
		//Test si on peut dézoomer
		if (map.getZoom()>9) {	
			map.setZoom(map.getZoom()-1);
		}			
	});
}

//Legend des markers
function LegendControl(controlDiv, map) {
	$(controlDiv).html('<div><img src="images/10_adhplombier-mini.png"/>  Plombier &amp; Chauffagiste</br><img src="images/10_adhelectricien-mini.png"/>  Electricien</br><img src="images/10_adhboth-mini.png"/>  Plombier &amp; Electricien</div>');
	$(controlDiv).css({	'-moz-border-radius':'1em 1em 1em 1em',
						'background-color':'rgba(255, 255, 255, 0.7)',
						'vertical-align' : 'middle',
						'padding':'10px',
						'margin':'6px'
					});
}

//Fonction du bouton print des  adh
function PrintControl() {
	clearMap();
	$('#endImprim').html('<div id="text_imprim" >Impression en cours, veuillez patienter<br/><br/><br/><img src="images/impression.gif" /></div>');
	$('#fonds').css({	'display': 'inline',
						'z-index':'11000'
					});
	$('#endImprim').css({	'display':'inline',
							'background-image' : 'none',
							'z-index':'12000'
						});
	$.ajax({type: 	'POST', //method pour passer les parametres
			url: 	'output_pdf.php', //page de direction
			data: 	{	myJson:  JSON.stringify(bestTenAdh) },	//Transforme le tableau en string Json
			success :    function (json) {
					var json = jQuery.parseJSON(json);
					json.message = $.base64.decode(json.message);
					$('#endImprim').html('<div>'+json.message+'</div> <input type="button" id="close_imprim" />');
					 
						timer_imprim = setTimeout(function() { //timer de fermeture
											$('#fonds').hide('fade',{},1000);
											$('#endImprim').hide('fade',{},1000);
										},10000 );
								
					//	$('#fonds').css({'display': 'inline'});
						$('#endImprim').css({	//'display':'inline',
												'background-image':'url(images/imprim.jpg)',
												'background-repeat':'no-repeat',
												'background-position':'center center',
												'text-align': 'center'
											});
						//fonction du bouton fermer
						$('#close_imprim').click (function() {		
							$('#fonds').hide('fade',{},1000);
							$('#endImprim').hide('fade',{},1000);	
							clearTimeout(timer_imprim);
						});
			}
	});  
}




//Fonction du bouton print de la div adh
function PrintControlAdherent() {
	$('#endImprim').html('<div id="text_imprim" >Impression en cours, veuillez patienter<br/><br/><br/><img src="images/impression.gif" /></div>');
	$('#fonds').css({	'display': 'inline',
						'z-index':'11000'	//Passe le fond devant la div de l adh
					});
	$('#endImprim').css({	'display':'inline',
							'background-image' : 'none',
							'z-index':'12000'//Passe le pop up imprimer devant le fond
						});
	$.ajax({type: 	'GET', //method pour passer les parametres
			url: 	'output_pdf_adh.php', //page de direction
			data: 	numero_artisan, // Parametre
			success :    function (json) {
					var json = jQuery.parseJSON(json);
					json.message = $.base64.decode(json.message);
					$('#endImprim').html('<div>'+json.message+'</div> <input type="button" id="close_imprim" />');
					timer_imprim = setTimeout(function() {//timer de fermeture
												$('#fonds').css({	'display':'inline',
																	'z-index':'0' 	//Passe le fond derriere la div de l adh
															});
												$('#endImprim').hide('fade',{},1000);
											},10000 );									
					/*$("#fonds").css({"display": "inline",
									 	//Passe le fond devant la div de l adh
								});*/
					$('#endImprim').css({	'display':'inline',
											//'z-index':'12000'//Passe le pop up imprimer devant le fond
											'background-image':'url(images/imprim.jpg)',
											'background-repeat':'no-repeat',
											'background-position':'center center',
											'text-align': 'center'
												});
					//fonction du bouton fermer			
					$('#close_imprim').click (function() {		
						$('#fonds').css({
										'display':'inline',
										'z-index':'0' 	//Passe le fond derriere la div de l adh
									});
						$('#endImprim').hide('fade',{},1000);	
						clearTimeout(timer_imprim);
					});
			}
	});	
}

//Fonction permettant le tri d'un tableau en fonction d'une valeur ici, les distances.
function sortByValue(valueMap) {
	var keyArray = new Array();
	for(var key in valueMap)
		keyArray.push(key);
		return keyArray.sort(function(a,b) {
								return valueMap[a]-valueMap[b];
							});
}

//Calcul de la distance entre le centre de la carte et l'adh
function latLngDistance(centerLat, centerLng, lat, lng) {
	var distance =(Math.sqrt( //Racine carré 
							Math.pow( //exposant
									lat- //De la forme Lat'-lat, centre (lat,lng) et pointadh (lat',lng)
									centerLat//Math.pow(nbr a mettre a l'exposant, exposant)
									,2)
							+Math.pow(//Math.pow(nbr a mettre a l'exposant, exposant)
									lng-//De la forme Lat'-lat, centre (lat,lng) et pointadh (lat',lng)
									centerLng,2)
							)
					);//Fin du calcul
	return distance;
}	
 
//Affichage de la div d 'un adhérent
function affichageDivAdh(numero_adh) {
 			$.ajax({type: 'GET', 
				url: 'info_json.php',
				data: 'function=data_artisan&numero_artisan='+numero_adh,
				success: function(json) {	
							//Timer pour fermer la div après 2min		
							timer = setTimeout(function() {
										time = 1; //variable pr le test de l'évènement
										$('#cadre').hide(effects[Math.floor(Math.random()*effects.length)], {}, 750);
										$('#fonds').hide('fade',{},1000);
										map.panTo(vannes);
										map.setZoom(9);	
									},120000 );								

							$('#cadre').css({	'display':'inline',
												'z-index':'10000' 	//Passe le fond devant la div de l adh
							});	
							$('#fonds').css({ 'z-index':'9000' }); //Passe le fond devant la div de l adh
									
							//Vide le tableau cadre
							$('#nom-adherent')	.html('');
							$('#photo0')		.html('');
							$('#donnee')		.html('');
							$('#photo5')		.html('');
							$('#desc')			.html('');
							$('#photo1')		.html('');
							$('#photo2')		.html('');
							$('#photo3')		.html('');
							$('#photo4')		.html('');
							$('#fermer')		.html('');
							
							//Code executé en asynchrone en cas de succes de la page PHP
							var data = jQuery.parseJSON(json);	
							
							data.nom		= $.base64.decode(data.nom);
							data.ville		= $.base64.decode(data.ville);
							data.adr1		= $.base64.decode(data.adr1);
							data.text_desc	= (data.text_desc ? (data.text_desc = $.base64.decode(data.text_desc)):'');
							
							numero_artisan = 'numero_artisan=' + data.numero;
							
							//Affichage du nom de l'adhérent
							$('#nom-adherent')	.text(data.nom)
												.append(			
							((data.activite==1 ? '</br><div><img src="images/10_adhplombier-mini.png"/> Plombier &amp; Chauffagiste</div>': "") //image de l'activité de l'adh
							||(data.activite==2 ? '</br><div><img src="images/10_adhelectricien-mini.png"/> Electricien</div>': "")
							||(data.activite==3 ? '</br><div><img src="images/10_adhboth-mini.png"/>Plombier &amp; Electricien</div>': ""))
							)
							
							//Affichage des données relative à l'adh
							$('#donnee').html('<div id=adr>'+(data.adr1 	? data.adr1:'')+'<br/>'
								+(data.ville 	? data.ville:'') +' '+(data.cp ? data.cp:'') +'</div><br/></br>'
								+(data.tel1 	? ('<div><img src="images/icon_map/telephone-icone.png" height="40px" />'+'  '+data.tel1)+'</div>':'') +'</br></br>'
								+(data.tel3 	? ('<div><img src="images/icon_map/fax-icone.png" height="40px" />'+'  '+data.tel3+'</div>') :'')+'</br></br>'
								+(data.email 	? ('<div><img src="images/icon_map/enveloppe-icone.jpg" height="40px" />'+'  '+data.email+'</div>'):'') +'<br/>'
							);
												
							//Remplir la cellule de description
							if (data.text_desc) {
								$('#desc').html('<div id="text_desc">'+data.text_desc.replace(/[\n\r]/,'<br/>')+'</div>');
								$('#text_desc').css({'background-color': 'rgba(40, 150, 200, 0.7)'}); //Couleur de la div text_desc
							} else {
								$('#text_desc').css({'background-color': 'white'}); //Si pas de description fond en blanc						
							}	
							
							//Remplir les cellules des photos
							if (data.photo) {
								var myString = data.photo;
								var mySplitResult = myString.split(",");
								for(i = 0; i < mySplitResult.length; i++) {
									if (mySplitResult[i] != "" ) {
										$('#photo'+i).html('<img src="images/photo_adh/'+data.numero+'/'+mySplitResult[i]+'" id="photo'+i+'" alt="'+mySplitResult[i]+'"/>');
									}
								}	
							}
							
							//Création du bouton fermer avec effet  et du bouton d'impression
							$('#fermer').append((data.website 	? (
									'<div><img src="images/icon_map/icone_email.png" height="40px" />'+' '+data.website+'<img src="../gfx/qrcode.php?text='+escape(data.website)+'" style="float:right;" class="qrcode"/></div></br></br>'):'')
									+'<a id="impression" href="#" onclick="PrintControlAdherent();"><img src="images/icon_map/print64px.png" style="border:none;"></a>'
									+'<div id="btn_fermer"><input type="button" id="close"><div>'
									);
							
							//Ouverture de la div avec effet
							$('#fonds').show('fade',{},2500);
							$('#cadre').show(effects[Math.floor(Math.random()*effects.length)], {}, 750);
							
							//Fonction bouton fermer
							$('#close').click (function() {											
								//fermeture de la div avec effet
								$('#cadre').hide(effects[Math.floor(Math.random()*effects.length)], {}, 750);
								$('#fonds').hide('fade',{},900);
								clearTimeout(timer);
							});
							
				} //Fin de success json
			});//Fin ajax	
}