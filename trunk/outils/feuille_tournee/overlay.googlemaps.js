var overlays_bounds = new Array();

function MyOverlay(options) {
    this.setValues(options);

    this.div_	= document.createElement('div');
    this.div_.className		= options['class'];
	this.div_.style.width	= options['width'];
	this.div_.style.height	= options['height'];
	this.div_.innerHTML		= options['text'];
};

// MyOverlay is derived from google.maps.OverlayView
MyOverlay.prototype = new google.maps.OverlayView;

MyOverlay.prototype.onAdd = function() {
    this.getPanes().overlayLayer.appendChild(this.div_);
};

MyOverlay.prototype.onRemove = function() {
    this.div_.parentNode.removeChild(this.div_);
};

MyOverlay.prototype.draw = function() {
    var projection = this.getProjection();
    var position = projection.fromLatLngToDivPixel(this.position);
	this.left = position.x - parseInt(this.div_.style.width)/2 - 2 ;
	this.top  = position.y - parseInt(this.div_.style.height) - 10 ;

	this.div_.style.position	= 'absolute';
    this.div_.style.left		= this.left + 'px'; // center
    this.div_.style.top			= this.top + 'px'; // near bottom
    this.div_.style.display		= 'block';

	//document.getElementById('debug').innerHTML += 'draw: ' + this.text + ' ' + this.top + "<br>\n";
	var bounds = {  'top':this.top,
					'left':this.left,
					'width':this.width,
					'height':this.height,
					'x1':this.left,
					'y1':this.top,
					'x2':this.left + this.width.replace(/[^0-9\.]/g,''),
					'y2':this.top  + this.height.replace(/[^0-9\.]/g,''),
					'id':this.text
				};

/*	var colide = isColideWith(bounds);
	if (colide)
		document.getElementById('debug').innerHTML += this.text+" <b>ColideWith</b>: "+colide+ "<br>\n";
	else 
		document.getElementById('debug').innerHTML += this.text+" DontColide<br>\n";
*/

	// stocké ici les coords des boites pour les tester au fur et a mesure
	overlays_bounds.push( bounds );
};

function isColideWith(box1) {
	for each (box2 in overlays_bounds) { // pour chaque cadre deja enregistré
		// TOP LEFT colide
		if			(box2.x2 >= box1.x1 && box2.x2 <= box1.x2 && 
					 box2.y2 >= box1.y1 && box2.y2 <= box1.y2) {
			return box2.id;
		// TOP RIGHT colide
		} else if	(box2.x1 >= box1.x1 && box2.x1 <= box1.x2 && 
					 box2.y2 >= box1.y1 && box2.y2 <= box1.y2) {
			return box2.id;
		// BOTTOM RIGHT colide
		} else if	(box2.x1 >= box1.x1 && box2.x1 <= box1.x2 && 
					 box2.y1 >= box1.y1 && box2.y1 <= box1.y2) {
			return box2.id;
		// BOTTOM LEFT colide
		} else if	(box2.x2 >= box1.x1 && box2.x2 <= box1.x2 && 
					 box2.y1 >= box1.y1 && box2.y1 <= box1.y2) {
			return box2.id;
		}
	}
	return ''; // aucune colision
};
