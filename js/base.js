jQuery( document ).ready( function() {

/*

	// Fix footer below Photo Booth....
	var pb = jQuery('.pb-wrapper').outerHeight();
	var mybody = jQuery('body').outerHeight();

	//jQuery("body").css("overflow", "hidden");
	jQuery('#footer').css('top', pb + mybody );
	//jQuery('#footer').css('top', ( jQuery('.pb-wrapper').outerHeight() + jQuery('body').outerHeight() );
	//
*/


// Initialize Masonry
  jQuery( '#linky' ).masonry({
		columnWidth: 300,
		itemSelector: '.boxy',
		isFitWidth: true,
		isAnimated: !Modernizr.csstransitions
	}).imagesLoaded(function() {
   jQuery('#linky').masonry('reload');
	});


	/* Apply fancybox to multiple items */
	jQuery("a.group").fancybox({
		'transitionIn'	:	'elastic',
		'transitionOut'	:	'elastic',
		'speedIn'		:	600, 
		'speedOut'		:	200, 
		'overlayShow'	:	false
	});
	
	

	
	

});