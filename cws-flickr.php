<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/*
 * Plugin Name: Flickr Lite
 * Plugin URI: http://cheshirewebsolutions.com
 * Description: Add Flickr to your blog posts, pages and sidebar. With Carousel goodness and muchus more...
 * Version: 0.1 beta
 * Author: Ian Kennerley - <a href='http://twitter.com/CheshireWebSol'>@CheshireWebSol</a> on twitter
 * Author URI: http://cheshirewebsolutions.com
 * Author Email: hello@cheshirewebsolutions.com
 * License: GPLv2
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

/** Add Zend Library to include path ****************************************/
set_include_path ( realpath( dirname( __FILE__ ) ) . '/zend/library' );


if ( ! class_exists( 'CWS_WPFlickr' ) ) {

class CWS_WPFlickr {

	/** Debug ***************************************************************/
	public $debug = TRUE;

	var $preflight_errors 		= array();

	/** Version *************************************************************/	

	var $plugin_version;
	var $is_pro;
			
	/** URLS ****************************************************************/
	
    	var $plugin_path;
    	var $plugin_url;
    
	/**
	 * CWS_WPFlickr Constructor
	 *
	 * Let's get this party started!
	 */
    	// function __construct( $is_pro ) 
    	function __construct( ) 
    	{	
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::__construct()' );
		
		$this->is_pro = $is_pro;
		
        	// TODO: these constants are the same!?!?!...
        	define( 'WPFLICKR_PLUGIN_PATH', plugin_dir_path(__FILE__) );        	
        	define( 'WPFLICKR_PLUGIN_URL', WP_PLUGIN_URL.'/'.str_replace(basename( __FILE__),"",plugin_basename(__FILE__)) );
        	
        	define( 'WPFLICKR_ISPRO', FALSE ); // Set to TRUE for Pro Version of the plugin
        	
		// Pre Flight Check...
		$this->check_zend_loader();		
		$this->is_cache_writable();

        	// Set up locale
        	load_plugin_textdomain( 'cws_flickr', false, dirname( plugin_basename( __FILE__ ) ) . '/lang/' );        
        
        	// Add your own hooks/filters
        	// add_action( 'init', array( &$this, 'init' ) );
        
		// Include required files
		$this->includes();
        
		// Add shortcode support for widgets
		if ( ! is_admin() ){ add_filter( 'widget_text', 'do_shortcode', 11 ); }
		
		add_action( 'wp_print_scripts', array( &$this,'add_header_scripts') );
		add_action( 'wp_print_styles', array( &$this,'add_header_styles') );			
		
		// Add ajax hook for deleting cache
		add_action( 'wp_ajax_deleteCache', array( &$this, 'cws_flickr_delete_cache' ) );
		
		// Set up activation hooks			
		register_activation_hook( __FILE__, array(  &$this, 'cws_flickr_activate' ) );
        	// register_deactivation_hook( __FILE__, array( &$this, 'deactivate' ) );
    	}
    
	/**
	 *
	 *  Check Zend Loader is available.
	 *
	 */	    
    	function check_zend_loader() {    
    
    		if( $this->zend_loader_present = @fopen( 'Zend/Loader.php', 'r', true ) ) {
    			return TRUE;
    		}
    		else {
    		
    			$this->preflight_errors[] = 'Exception thrown trying to ' .
				'access Zend/Loader.php using \'use_include_path\' = true ' .
				'Make sure you include the Zend Framework in your ' .
				'include_path which currently contains: "' .
				ini_get('include_path') . '"';    		
						
    			return FALSE;
    		}
    	}
    
	/**
	 *
	 *  Check 'cache' is writable (attempt to make? )
	 *	Used for user feedback in settings page
	 *
	 */		
	public function is_cache_writable() {
	
		if( ! is_writable( WPFLICKR_PLUGIN_PATH . '/cache/' ) ) {
			if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::is_cache_writable()  Cache folder is NOT writable.'  . WPFLICKR_PLUGIN_PATH . '/cache/' );
			$this->preflight_errors[] = 'Cache folder is NOT writable.' . $this->plugin_path . '/cache/';
			return FALSE;
		}
		else {
			if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::is_cache_writable()  Cache folder is writable.' );	
			return TRUE;				
		}		
	} 	    
    

	/**
	 *
	 * Create Default Settings
	 *
	 */	 
	function cws_flickr_activate() {
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::cws_gpp_activate()' );
		
		/* perms 'read', 'write', 'delete', etc... */
        	$cws_flickr_options = array(
						'consumer_key' 		=> '1ba95822668e1181e229796002c5b2b5',
						'consumer_secret' 	=> 'ee228c3ab73762a9',
						'perms'			=> 'read'
					);
        						
		update_option( 'cws_flickr_options', $cws_flickr_options );            						
    	}


	/**
	 *
	 * Include required core files
	 *
	 */
	function includes() {
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::includes()' );
		
		if ( is_admin() ) $this->admin_includes();		
		
		
		// Only do this if Pre Flight passed	
		if( ! $this->preflight_errors ) {
			include_once( 'classes/cws-flickr-api.php' );
					
			// Pro Classes
			// if( $this->is_pro == TRUE ) {
			if( $this->is_pro_check() ) {
				include_once( 'classes/pro/cws-flickr-pro-api.php' );	
				include_once( 'shortcodes/pro/shortcode-pro-init.php' );			// Init the shortcodes
				include_once( 'widgets/pro/widget-pro-init.php' );				// Widget classes	
			}		
		
			include_once( 'shortcodes/shortcode-init.php' );			// Init the shortcodes
			include_once( 'widgets/widget-init.php' );				// Widget classes	
		}		
	
	}


	/**
	 *
	 * Include required Admin files
	 *
	 */	 
	function admin_includes() {
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::admin_includes()' );

		include_once( 'admin/cws-flickr-admin-init.php' );			// Admin section
		include_once( 'classes/cws-flickr-admin.php' ); 				// does this need including here?
	}


	/**
     	*
     	* Enqueue front-end scripts
     	*
     	*/	
	public function add_header_scripts() {
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::add_header_scripts()' );
				
		if ( ! is_admin() ) {	
		
			if( function_exists( 'wp_register_script' ) ) {
				
				// $options = get_option( 'cws_flickr_options' );			
								
				// Load FancyBox

				// if( $options['enable_fancybox'] == 1 )  {
				wp_register_script( 'cws_flickr_fb', WPFLICKR_PLUGIN_URL . 'fancybox/jquery.fancybox-1.3.4.js', array('jquery'),1, true );
				// }

								
				// Load main js file
				wp_register_script( 'cws_flickr_base_js', WPFLICKR_PLUGIN_URL . 'js/base.js', array('cws_flickr_masonry'),1, true );			
																	
				// Allows plugin location to be referenced in js files
				// wp_localize_script( 'cws_flickr_albums_js', 'cws_flickr_', array( 'siteurl' => get_option( 'siteurl' ), 'pluginurl' => WPFLICKR_PLUGIN_URL ));
				
				// Load Inifinite carousel jQuery Plugin		
				// wp_register_script( 'cws_flickr_albums_infcar', WPFLICKR_PLUGIN_URL . 'js/infiniteCarousel.js', array('jquery'),"1.6.1", true );
				
				// Load Infinite Carousel setup file				
				// wp_register_script( 'cws_flickr_albums_infcarsetup', WPFLICKR_PLUGIN_URL . 'js/ic_setup.js', array('jquery', 'cws_gpp_albums_infcar'),1, true);
				
				// Load jquery.blockUI.js	
				// wp_register_script( 'cws_flickr_albums_jqBlock', WPFLICKR_PLUGIN_URL . 'js/jquery.blockUI.js', array('jquery'),1, true);															
				
				//
				// wp_register_script( 'cws_flickr_albums_slideshow', WPFLICKR_PLUGIN_URL . 'js/slideshow.js', array('jquery'),1, true);
				// wp_register_script( 'cws_flickr_slideshow_init', WPFLICKR_PLUGIN_URL . 'js/init.js', array('jquery'),1, true);
				
				// Masonary
				wp_register_script( 'cws_flickr_masonry', WPFLICKR_PLUGIN_URL . 'js/jquery.masonry.min.js', array( 'jquery' ),1, true );
				// Modinizr
				wp_register_script( 'cws_flickr_modernizr', WPFLICKR_PLUGIN_URL . 'js/modernizr-2.5.3.min.js', array('jquery'),1, true );


				if( function_exists( 'wp_enqueue_script' ) ) {
				
					wp_enqueue_script( 'cws_flickr_modernizr' );
					wp_enqueue_script( 'cws_flickr_masonry' );
					wp_enqueue_script( 'cws_flickr_base_js' );

					// if( $options['enable_fancybox'] == 1 ) {
					wp_enqueue_script( 'cws_flickr_fb' );
					// }
					
/*
					wp_enqueue_script( 'cws_flickr_albums_js' );						
					wp_enqueue_script( 'cws_flickr_albums_infcar' );
					wp_enqueue_script( 'cws_flickr_albums_infcarsetup' );
					wp_enqueue_script( 'cws_flickr_albums_jqBlock' );
					wp_enqueue_script( 'cws_flickr_albums_slideshow' );
					wp_enqueue_script( 'cws_flickr_slideshow_init' );
*/												

				}
			}
		}
		
		
		if(  is_admin() ) {
			
			// Load main js file
			wp_register_script( 'cws_flickr_admin_js', FLICKR_PLUGIN_URL . 'js/admin.js', array('jquery'),1, true );				
			
			if( function_exists( 'wp_enqueue_script' ) ) {
				wp_enqueue_script( 'cws_flickr_admin_js' );						
			}
		}		
		
	}
	
		
	/**
     	*
     	* Enqueue front-end styles
     	*
     	*/	
	public function add_header_styles() {
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::add_header_styles()' );

		if ( ! is_admin() ) {	
			
			if( function_exists( 'wp_register_style' ) ) {
				
				// $options = get_option( 'cws_gpp_options' );
				
				wp_register_style( 'cws_flickr_fbcss', plugins_url( 'fancybox/jquery.fancybox-1.3.4.css', __FILE__ ), '', $this->plugin_version );
				wp_register_style( 'cws_flickr_albums_stylecss', plugins_url( 'css/style.css',__FILE__ ) , '', $this->plugin_version );

				if ( function_exists( 'wp_enqueue_style' ) ) {
				
					// Load FancyBox

					// if( $options['enable_fancybox'] == 1 ) {
						wp_enqueue_style( 'cws_gpp_fbcss' );
					// }

					wp_enqueue_style( 'cws_flickr_albums_stylecss' );
				}
			}
		}
	}
	
	
	/**
	 *
	 * Delete cache
	 *
	 */
	function cws_flickr_delete_cache( )
	{
		if( $this->debug ) error_log( 'Inside: CWS_WPFlickr::cws_gpp_delete_cache()' );	
		
		$dirname = WPFLICKR_PLUGIN_PATH . 'cache';
		
		if( $this->debug ) error_log( 'Delete ALL files in: ' . $dirname );
		
		$dir = opendir( $dirname );
		
		while( false !== $entry = readdir( $dir ) ) {
			if( $entry == '.' || $entry == '..' ) continue;
			if( is_file( "$dirname/$entry" ) ) unlink( "$dirname/$entry" );
		}
		
		closedir( $dir );
	}
	
	
	
	
	//
	
	
	/**
	 *
	 *  Display Messsage.
	 *  If Zend Loader is not found.
	 *  If Cache directory is not writable.
	 *
	 */	
	function showMessage( $preflight_check, $errormsg = false )
	{
		if ( $errormsg ) {
			echo '<div id="message" class="error">';
			
			foreach($preflight_check as $message){
				echo "<p><strong>$message</strong></p>";
			}
			
			echo "</div>";
			
		}
		else {
			echo '<div id="message" class="updated fade">';
		}
	
		//echo "<p><strong>$message</strong></p></div>";
	}    
	
	function showAdminMessages( $preflight_check )
	{
	    // Shows as an error message. You could add a link to the right page if you wanted.
	    $this->showMessage( $preflight_check, true );
	
	    //$this->showMessage($msg, true);
	
		// Only show to admins
	    // if ( current_user_can( 'manage_options' ) ) {
	    //    $this->showMessage( "Hello admins!" );
	    // }
	}	
	
	function cws_get_admin_url( $path = '' )
	{
		global $wp_version;
		
		if ( version_compare( $wp_version, '3.0', '>=') ) {
			return get_admin_url( null, $path );
		}
		else {
			return get_bloginfo( 'wpurl' ) . '/wp-admin' . $path;
		}
	}	
	
	
	function is_pro_check()
	{
		
		//var_dump( $is_pro );
		 return $is_pro;
		
		//return $this->is_pro;
	}

	//
	
	
	

}

}

// $wpFlickr = new CWS_WPFlickr( $is_pro = FALSE );
$wpFlickr = new CWS_WPFlickr(  );

?>