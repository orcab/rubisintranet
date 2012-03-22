// Class ProgressBar
function ProgressBar(data) {
	this.id					= data.id;
	this.backgroundColor	= data.backgroundColor	|| 'white' ;
	this.color				= data.color			|| 'red' ;
	this.origin				= data.origin			|| 'left' ;
	this.max_value			= data.max_value		|| 0 ;
	
	this.update = function(value) {
		if (this.max_value > 0) {
			// calcul le pourcentage d'avencement
			var percent = value * 100 / this.max_value ;
			//console.log(percent);

			// modifier l'aspect de la progressbar
			$('#' + this.id).css('background-image','-moz-linear-gradient('+
								this.origin+','+
								this.color +' '+
								percent + '%,'+
								this.backgroundColor + ' 1px)')
							.text(
								percent.toFixed(1) + '% ' +
								'(' + value + '/' + this.max_value + ')'
							);
		}
	} // fin update
}
