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
function cws_flickr_shortcode_photo_random( $atts ) {	
	
	$options = get_option( 'cws_flickr_options' );
	
	// Create instance of CWS_FlickrApi class	
	// $my_photo_random = new get_photo_random(); 
	$my_photo_random = new CWS_FlickrApi( );
	$my_photo_random = $my_photo_random->get_photo_random_display( $atts['photoset_id'], $size );
				
	// Get Photoset based on $atts['photoset_id']
	return $my_photo_random;	
}