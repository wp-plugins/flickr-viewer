/**
 *	Main jQuery Gumpf
 *
 */
jQuery( document ).ready( function( $ ) {
	
	console.log( 'ready' );		
		/**
		 *
		 * Delete cache
		 *
		 */
		 $( '#delete_cache' ).click( function(e){		 
		 	
		 	// if ( confirm( 'Are you sure you want to delete the cache?' ) ) {
		 	if ( confirm( cws_flickr_.cacheconfirm ) ) {		 	
		 		console.log( 'Confirmed Delete' );
		 				 		
				$.ajax({
				
					type: 'POST',
					url: cws_flickr_.siteurl + '/wp-admin/admin-ajax.php',
					data: {
							action: 'deleteCache',
							path: '/cache/',
					},
					
/*
					beforeSend: function() {
						showBusy(); 
					},
*/
					
					success: function( data, textStatus, XMLHttpRequest ){
						//removeBusy();	  				
					},
					
					error: function( MLHttpRequest, textStatus, errorThrown ){
						alert( 'err'+errorThrown );
					}
				});
		 		
		 	}

		 });	
});
