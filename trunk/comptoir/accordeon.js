// quand document chargé
$(document).ready(function(){

	var url_page = 'affiche_article.php?chemin=';

	// on click sur un h1
	$('div.lvl-1').click(function(){
		// on remballe tout
		$('div.lvl-2,div.lvl-3,div.lvl-4,div.lvl-5').css('display','none');
		//$('div.lvl-2,div.lvl-3,div.lvl-4,div.lvl-5').slideUp('slow');
		$('div.lvl-1,div.lvl-2,div.lvl-3,div.lvl-4,div.lvl-5').removeClass('active');
		// on depli les enfant h2 du h1 cliqué
		//$(this).nextUntil('div.lvl-1','div.lvl-2').css('display','block');
		$(this).nextUntil('div.lvl-1','div.lvl-2').slideDown('slow');
		$(this).addClass('active');
		parent.frames['basefrm'].location = url_page + $(this).attr('data');
	});


	// on click sur un h2
	$('div.lvl-2').click(function(){
		// on remballe tout
		$('div.lvl-3,div.lvl-4,div.lvl-5').css('display','none');
		//$('div.lvl-3,div.lvl-4,div.lvl-5').slideUp('fast');
		$('div.lvl-2,div.lvl-3,div.lvl-4,div.lvl-5').removeClass('active');
		// on depli les enfant
		//$(this).nextUntil('div.lvl-2','div.lvl-3').css('display','block');
		$(this).nextUntil('div.lvl-2','div.lvl-3').slideDown('slow');
		$(this).addClass('active');
		parent.frames['basefrm'].location = url_page + $(this).attr('data');
	});

	// on click sur un h3
	$('div.lvl-3').click(function(){
		// on remballe tout
		$('div.lvl-4,div.lvl-5').css('display','none');
		$('div.lvl-3,div.lvl-4,div.lvl-5').removeClass('active');
		// on depli les enfant
		//$(this).nextUntil('div.lvl-3','div.lvl-4').css('display','block');
		$(this).nextUntil('div.lvl-3','div.lvl-4').slideDown('slow');
		$(this).addClass('active');
		parent.frames['basefrm'].location = url_page + $(this).attr('data');
	});

	// on click sur un h4
	$('div.lvl-4').click(function(){
		// on remballe tout
		$('div.lvl-5').css('display','none');
		$('div.lvl-4,div.lvl-5').removeClass('active');
		
		// on depli les enfant
		//$(this).nextUntil('div.lvl-4','div.lvl-5').css('display','block');
		$(this).nextUntil('div.lvl-4','div.lvl-5').slideDown('slow');
		$(this).addClass('active');
		parent.frames['basefrm'].location = url_page + $(this).attr('data');
	});

	// on click sur un h5
	$('div.lvl-5').click(function(){
		$('div.lvl-5').removeClass('active');
		$(this).addClass('active');
		parent.frames['basefrm'].location = url_page + $(this).attr('data');
	});

});