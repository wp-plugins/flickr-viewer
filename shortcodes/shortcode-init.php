<?php
/**
 * Shortcodes init
 * 
 * Init main shortcodes
 *
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

// Lite Features...
include_once('shortcode-recent.php');
include_once('shortcode-photoset.php');
include_once('shortcode-photo-random.php');


/**
 * Shortcode creation
 **/
add_shortcode( 'cws_flickr_recent', 'cws_flickr_shortcode_recent' );
add_shortcode( 'cws_flickr_photoset', 'cws_flickr_shortcode_photoset' );
add_shortcode( 'cws_flickr_photo_random', 'cws_flickr_shortcode_photo_random' );


// Pro Features...
//include_once( WPFLICKR_PLUGIN_PATH . '/shortcodes/pro/shortcode-slider-pro.php');
// include_once('shortcode-photoset-strips.php');
// Videos!


/**
 * Shortcode creation
 **/
//add_shortcode( 'cws_flickr_slider', 'cws_flickr_shortcode_slider' );
// add_shortcode( 'cws_flickr_photoset_strips', 'cws_flickr_shortcode_photoset_strips' );





