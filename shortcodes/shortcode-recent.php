<?php
/**
 * Copyright (c) 2011, cheshirewebsolutions.com, Ian Kennerley (info@cheshirewebsolutions.com).
 * 
 * This program is free software; you can redistribute it and/or
 * modify it under the terms of the GNU General Public License
 * as published by the Free Software Foundation; either version 2
 * of the License, or (at your option) any later version.
 * 
 * This program is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with this program; if not, write to the Free Software
 * Foundation, Inc., 51 Franklin Street, Fifth Floor, Boston, MA  02110-1301, USA.
*/
function cws_flickr_shortcode_recent( $atts ) {
		
	// Allowed Sizes...
	$allowed_sizes = array(	'Square',
				'Large Square',
				'Thumbnail',
				'Small',
				'Small 320',
				'Medium',
				'Medium 640',
				'Medium 800',
				'Large',
				'Large 1600',
				'Large 2048',
				);
		
	// Get any specific image size if shortcode has passed any
	if ( isset( $atts['size'] ) ) {
		$size = 'Square';
					
		if( in_array( $atts['size'], $allowed_sizes ) ){
			$size = $atts['size'];
		}
	}
		
	$options = get_option( 'cws_flickr_options' );

	// Create instance of CWS_FlickrApi class	
	$my_flickr = new CWS_FlickrApi( );
								
	// Call recent_images
	return $recent_images = $my_flickr->get_recent_images_display( $size );
}