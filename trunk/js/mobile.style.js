$(document).ready(function() {
	$('label.mobile > input[type=checkbox]').click(function(){
		if(this.checked)
			$(this).parents('label').addClass('mobile-checked');
		else
			$(this).parents('label').removeClass('mobile-checked');
	});
});