<?php
if ( !defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

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


require_once 'Zend/Loader.php';
Zend_Loader::loadClass( 'Zend_Service_Flickr' );


/*
 *
 * 	Main Class to talk to Flickr API
 *
 */
class CWS_FlickrApi extends Zend_Service_Flickr {

	var $debug = TRUE;
	
	// TODO: which of these vars can I get rid of?...
	var $client;
	var $request_token;
	var $access_token;
	var $consumer;
	var $consumer_key;
	var $consumer_secret;
	var $return_to;
	var $scopes;
	var $oauth_options;
	var $approval_url;
	var $zend_loader_present = false;
	var $errors = array();
	
	function __construct()
	{
		if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::__construct()' );
	
		Zend_Loader::loadClass( 'Zend_Gdata_HttpClient' );
		Zend_Loader::loadClass( 'Zend_Oauth_Consumer' );
		Zend_Loader::loadClass( 'Zend_Http_Client' );
		Zend_Loader::loadClass( 'Zend_Cache' );	
		Zend_Loader::loadClass('Zend_Config_Xml');
		
	        // Get options from database
	        $this->request_token 	= get_option( 'CWS_FLICKR_REQUEST_TOKEN' );
	        $this->access_token	= get_option( 'CWS-FLICKR-ACCESS_TOKEN' );	        
		$this->options 		= get_option( 'cws_flickr_options' );
		$this->consumer_key 	= $this->options['consumer_key'];
		$this->consumer_secret 	= $this->options['consumer_secret'];
		$this->perms 		= $this->options['perms'];	        
	        
		$this->return_to 	= CWS_WPFlickr::cws_get_admin_url('/options-general.php') . '?page=cws_flickr&cws_oauth_return=true' ;
		
		// Prepare options array
		$this->oauth_options = array(
						    'callbackUrl' 	=> $this->return_to,
						    'siteUrl' 		=> 'http://www.flickr.com/services/oauth',
						    'consumerKey' 	=> $this->consumer_key,
						    'consumerSecret' 	=> $this->consumer_secret,
						    'requestTokenUrl' 	=> 'http://www.flickr.com/services/oauth/request_token',
						    'accessTokenUrl' 	=> 'http://www.flickr.com/services/oauth/access_token',
						    'authorizeUrl' 	=> 'http://www.flickr.com/services/oauth/authorize',
						    'requestScheme'     => Zend_Oauth::REQUEST_SCHEME_HEADER,
						    'version'           => '1.0',
						    'signatureMethod'   => 'HMAC-SHA1',
					  );
		
		$this->consumer = new Zend_Oauth_Consumer( $this->oauth_options );	
	}


	/*
	 *
	 * 	Pagination Helper
	 *
	 * 	$num_pages, int
	 * 	$page, int
	 *
	 *	return string
	 */
	 function get_pagination( $pages, $page ) {
	 
		if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_pagination()' );		
				
		// Create page links
		$html[] = "<ul class=\"page-nav\">\n";
		
		$previous 	= $page - 1;
		$next 		= $page + 1;
		
		// Previous link
		if( $previous > 0 )	{
			// TODO: work out why ?page=2 is re-written to /2/
			$html[] = "<li><a href=\"?cws_page=" . $previous . "\" id='prev_page'>Previous</a></li>";
		}
		
		for( $i=1 ; $i <= $pages ; $i++ ) {
		
			$class = "";
		
			// Add class to current page
			if( $i == $page) {
				$class = " class='selected'";
			}
	
			$html[] = "<li".$class.">";
			$html[] = "<a href=\"?cws_page=".$i."\" id='pages'>".$i."</a></li>\n";
		}
		
		// Next link
		if( $next <= $pages ) {
			$html[] = "<li><a href=\"?cws_page=" . $next . "\" id='next_page'>Next</a></li>";
		}
		
		$html[] = "</ul>\n";
		
		return implode( "\n", $html );
	}


	/**
	* 
	* Get photo information
	*
	* @param string $id  The photo ID to get information for.
	*
	* @return object
	*/	
	public function fetchInfo( $id ) {
	        static $method = 'flickr.photos.getInfo';
	
	        if ( empty( $id ) ) {
	            
	             // @see Zend_Service_Exception		            
	            require_once 'Zend/Service/Exception.php';
	            throw new Zend_Service_Exception('You must supply a photo ID');
	        }
	        
	        $options = array(
			            'api_key' 	=> $this->consumer_key, 
			            'method' 	=> $method,
			            'photo_id' 	=> $id
			        );

	        // Add some caching...
	        try	{
	        		
			// Setup Zend Cache for 24hrs...
			// TODO: make cache duration user configurable in Pro version...
			$frontendOptions = array( 'lifetime' => 86400, 'automatic_serialization' => true ); 
			$backendOptions  = array( 'cache_dir' => WPFLICKR_PLUGIN_PATH . 'cache/' ); 
			$cache = Zend_Cache::factory( 'Core', 'File', $frontendOptions, $backendOptions );
						
						
			// If we don't have cached version, grab em from Flickr
			if( ( $response = $cache->load( 'photoinfo_' . $id ) ) === false ) {
			
				if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_info() - This one is from Flickr servers.' );
												
				$WPFlickr = new CWS_WPFlickr( WPFLICKR_ISPRO );

				{					
					
					// die('Lite');	
					
					// Now search for photos
/*
					$restClient = $this->getRestClient();
					$restClient->getHttpClient()->resetParameters();
					$response = $restClient->restGet( '/services/rest/', $options );
*/
					$consumer 		= new Zend_Oauth_Consumer( );
					$client 		= $consumer->getHttpClient( );
					
					$client->setUri( "http://api.flickr.com/services/rest/" );
					$client->setMethod( Zend_Http_Client::GET );					
					$client->setConfig( array('timeout'=>30) ); // TODO: check this stopped the time out issue
					$client->setParameterGet( $options );
					$response = $client->request();
					
					// Uses Zend_Config_Xml to parse xml to array
					require_once 'Zend/Config/Xml.php';
					$photoinfo = new Zend_Config_Xml( $response->getBody() );
					
					$cache->save( $photoinfo, 'photoinfo_' . $id );
					return $photoinfo;										
				}
				
				if ( $response->isError() ) {
				
					// @see Zend_Service_Exception
					require_once 'Zend/Service/Exception.php';
					throw new Zend_Service_Exception( 'An error occurred sending request. Status code: ' . $response>getStatus() );
				}																						
			}
			// Grab it from cache
			else {
				if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_info() - This one is from cache.' );
			
				$cache->save( $response, 'photoinfo_' . $id );
				return $response;					 
			}

		} 
		catch( Zend_Gdata_App_Exception $ex ) {
			
			$this->errors[] =  $ex->getMessage();
			$this->get_errors( $this->errors );
		}
		
		// TODO: Check this is working...
		if ( $response->isError() ) {
		
			// @see Zend_Service_Exception
			require_once 'Zend/Service/Exception.php';
			throw new Zend_Service_Exception( 'An error occurred sending request. Status code: ' . $response>getStatus() );
		}
	    }	


	/**
	 *
	 *	Get Recent Images, only allows for public images...
	 *
	 */
	function get_recent_images( $size, $flickr, $username ) {
	
		if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_recent_images()' );

		$recent_image_results = $flickr->userSearch( $username );
		return $recent_image_results;	
	}


	/**
	 *
	 *	Display Recent Images	 
	 *
	 */
	
	function get_recent_images_display( ) {
	
		if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_recent_images_display()' );
	
		$this->access_token = get_option( 'CWS-FLICKR-ACCESS_TOKEN' );
		
		$my_access_token = unserialize( $this->access_token );
		
		if ( empty( $my_access_token ) ) {
	            
	             // @see Zend_Service_Exception		            
	            require_once 'Zend/Service/Exception.php';
	            throw new Zend_Service_Exception('You must supply a access token');
	        }
				
		$flickr 	= new Zend_Service_Flickr( $this->consumer_key );
		$recent_images 	= $this->get_recent_images( $size = 'Small', $flickr, $my_access_token->username );
		
		// TODO: improve names of .linky and .boxy...
		$html[] =  '<div id="linky">';
	
		foreach ( $recent_images as $recent_image ) {
					
			// $image = $flickr->getImageDetails( $recent_image->id );
			// echo '<pre>';
			// print_r( $recent_image );
			// echo '</pre>';			
			// echo "size = $size";
			$html[] =  '<div class="boxy">';
				
			$html[] =  "<img src='" . $recent_image->$size->uri . 
			"' width='" .
			            $recent_image->$size->width . "' height='" .
			            $recent_image->$size->height . "' />";
			
			$html[] = "<br>";

			$my_tags = $this->fetchInfo( $recent_image->id );			

			// Grab photo Title and Description...
			$my_photo_info 		= $this->fetchInfo( $recent_image->id );

			foreach ( $my_photo_info as $info )
			{				
				$html[] = '<p>' . $info->description .'</p><br>';
				$html[] = '<div class="meta">';
				$html[] = '<p><strong>' . $info->title .'</strong></p>';	
				$html[] = '</div>';
			}
			
			$html[] = '</div>';
		}
				
		$html[] = '</div>';					
						
		return implode( "\n", $html );
	}	


	/**
	 *
	 * Write errors to error log.
	 *
	 */		
	private function get_errors( $errors ) {
		if( $this->debug ) error_log( 'Inside: CWS_GPPApi::get_errors()' );
			
		foreach( $errors as $err ) {
			error_log( $err );
		}
	}	


	/**
	* 
	* Get photo set
	*
	* @param string $photoset_id  The photoset ID to chose random photo from.
	* @param string $per_page  The number of photos to display per page
	*
	* @return object
	*/	
	public function fetchPhotoset( $id, $per_page = null ) {
	
	        static $method = 'flickr.photosets.getPhotos';
	
	        if ( empty( $id ) ) {
	            
	             // @see Zend_Service_Exception		            
	            require_once 'Zend/Service/Exception.php';
	            throw new Zend_Service_Exception( 'You must supply a photo set ID' );
	        }
	        
		// Get page number from the url - if there isn't one - we're on page 1  
		$cws_page = isset( $_GET['cws_page'] ) ? $_GET['cws_page'] : 1;	        
	        
	        $options = array(
	            'api_key' 		=> $this->consumer_key, 
	            'method' 		=> $method,
	            'photoset_id' 	=> $id,	            
	            'extras'		=> 'license, date_upload, date_taken, owner_name, icon_server, original_format, last_update, geo, tags, machine_tags, o_dims, views, media, path_alias, url_sq, url_t, url_s, url_m, url_o',
	            // TODO: make this user configurable...
	            'per_page'		=> "$per_page", // TODO: take this out for random...
	            'page'		=> $cws_page,
	            'media' 		=> 'photo',

	        );
	        
	        
	        // Add some caching...
	        try	{	
	        		
			// Setup Zend Cache for 24hrs...
			// TODO: make cache duration user configurable...
			$frontendOptions = array( 'lifetime' => 86400, 'automatic_serialization' => true ); 
			$backendOptions  = array( 'cache_dir' => WPFLICKR_PLUGIN_PATH . 'cache/' ); 
			$cache = Zend_Cache::factory( 'Core', 'File', $frontendOptions, $backendOptions );
						
			// If we don't have cached version, grab em from Flickr
			if( ( $response = $cache->load( 'photo_set_' . $id . '_' . $options['page'] . '_' . $options['per_page']) ) === false ) {
			
				if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_photo_set() - This one is from Flickr servers.' );
								
				{
					$options['privacy_filter'] = '1';
					// Now search for photos
					$consumer 		= new Zend_Oauth_Consumer( );
					$client 		= $consumer->getHttpClient( );
					
					$client->setUri( "http://api.flickr.com/services/rest/" );
					$client->setMethod( Zend_Http_Client::GET );					
					$client->setConfig( array( 'timeout'=>30 ) ); // TODO: check this stopped the time out issue
					$client->setParameterGet( $options );
					$response = $client->request();
					
					// Uses Zend_Config_Xml to parse xml to array
					require_once 'Zend/Config/Xml.php';
					$photoset = new Zend_Config_Xml( $response->getBody() );															
					$cache->save( $photoset, 'photo_set_' . $id . '_' . $options['page'] . '_' . $options['per_page'] );

					return $photoset;					
				}
				
				if ( $response->isError() ) {
				
					// @see Zend_Service_Exception
					require_once 'Zend/Service/Exception.php';
					throw new Zend_Service_Exception( 'An error occurred sending request. Status code: ' . $response>getStatus() );
				}				
			}
			// Grab it from cache
			else {
				if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_photo_set() - This one is from cache.' );
			
				$cache->save( $response, 'photo_set_' . $id . '_' . $options['page'] );
				return $response;					 
			}

		} 
		catch( Zend_Gdata_App_Exception $ex ) {
			
			$this->errors[] =  $ex->getMessage();
			$this->get_errors( $this->errors );
		}
	}


	/**
	* 
	* Get random photo set display code
	*
	* @param string $photoset_id  The photoset ID to chose random photo from.
	* @param string $per_page  The number of photos to display per page
	*
	* @return string
	*/		    
	function get_photo_set_display( $photoset_id )
	{

		if( $this->debug ) error_log( 'Inside: CWS_FlickrApi::get_photo_set_display()' );

		$my_photo_set	= $this->fetchPhotoset( $photoset_id, $per_page = 5 );
		$my_total 	= $my_photo_set->photoset->pages; 

		$html[] =  '<div id="linky">';
		
		foreach ( $my_photo_set->photoset->photo as $photo ) {
		
			// echo '<pre>';
			// print_r( $photo );
			// echo '</pre>';					
			$temp = "http://farm" . $photo->farm . ".staticflickr.com/" . $photo->server . "/" . $photo->id . "_" . $photo->secret . "_b.jpg";
			
			$farm_id = $photo->farm;
			$server_id = $photo->server;

			// TODO: Pro version only...
			$my_info = $this->fetchInfo( $photo->id );
			
			$html[] =  '<div class="boxy">';
			$html[] =  "<a class=\"group\" rel=\"group1\" href=\"" . $temp . "\"><img src='" . $photo->url_s . 
			"' width='" .
			            $photo->$size->width . "' height='" .
			            $photo->$size->height . "' /></a>";			

			$html[] =  '<p>' . $my_info->photo->description .'</p><br>';
			$html[] = '<div class="meta">';
			$html[] =  '<p><strong>' . $my_info->photo->title .'</strong></p>';	
			$html[] = '</div>';			
			$html[] = '</div>';
		}		

		$html[] = '</div>';		

		// Get page number from the url - if there isn't one - we're on page 1  
		$cws_page = isset( $_GET['cws_page'] ) ? $_GET['cws_page'] : 1;
		
		// Work out pagination...
		$html[] = $this->get_pagination( $my_total, $cws_page );
				
		return implode( "\n", $html );
	}


	/**
	* 
	* Get random photo display code
	*
	* @param string $photoset_id  The photoset ID to chose random photo from.
	* @param string $size  The size to display the random photo
	* @param string $show_photoset_ttl Flag to display photo set title	
	*
	* @return string
	*/
	public function get_photo_random_display( $photoset_id, $size, $show_photoset_ttl = FALSE )
	{						
		switch ( $size ) {
		    case "Square":
			$size = "url_sq";	
		       break;
		    case "Thumbnail":
			$size = "url_t";	
		        break;
		    case "Small":
			$size = "url_s";	
		        break;
		    case "Medium":
			$size = "url_m";	
		        break;
		        
		        // If $size is not matched set as medium
		        default: $size = "url_m";		        
		}		
		
		$my_photo_set = $this->fetchPhotoset( $photoset_id, $per_page = null, $size );

		foreach ( $my_photo_set->photoset->photo as $photo ) {
			
			$my_photo_set_array[] = $photo->$size;
		}
		
		// Grab random key
		$rand_key = array_rand( $my_photo_set_array, 1 );
				
		$html[] =  "<img src='" . $my_photo_set_array[ $rand_key ] . 
				"' width='" .
					$photo->$size->width . "' height='" .
					$photo->$size->height . "' />";
							
		if( isset( $show_photoset_ttl ) and $show_photoset_ttl != '' ) {			
			$html[] = $photo->title;
		}
	
		return implode( "\n", $html );
	}


	/**
	* 
	* Get random photo booth strip display code
	*
	* @param string $photoset_id  The photoset ID to chose random photo from.
	* @param string $size  The size to display the random photo
	*
	* @return string
	*/	
	// http://tympanus.net/codrops/2012/08/01/photo-booth-strips-with-lightbox/
	function get_photo_strip_display( $photoset_id, $size )
	{
		$my_photo_set = $this->fetchPhotoset( $photoset_id  );  // TODO: pass size?
	
		// TODO: change class names for photo booth bits...
		$html[] = '<div class="pb-wrapper pb-wrapper-1">';
 		$html[] = '<div class="pb-scroll">';
 		$html[] = '<ul class="pb-strip">';
             
		foreach ( $my_photo_set->photoset->photo as $photo ) {
			$html[] = '<li>';
				            
			// TODO: Pro version only...
			$my_info = $this->fetchInfo( $photo->id );	                        
			
			$temp = "http://farm" . $photo->farm . ".staticflickr.com/" . $photo->server . "/" . $photo->id . "_" . $photo->secret . "_b.jpg";
			
			// TODO: Add in photo size to display...
			if( ! isset( $size ) ) {
			    $size = $photo->url_s; 
			}
			else {
				$size = "http://farm" . $photo->farm . ".staticflickr.com/" . $photo->server . "/" . $photo->id . "_" . $photo->secret . "_q.jpg";
			}
			
			$html[] = '<a class="group" rel="group1" href="' . $temp . '" rel="lightbox[album1]" title="' . $my_info->photo->title . '"><img src="' . $size . '" 
				width="' .$photo->$size->width . '" 
				height="' . $photo->$size->height . '" /></a>';
			
			$html[] = '</li>';	    
		}
		
		$html[] = '</ul>';         
		$html[] = '</div>';     
		$html[] = '<h3 class="pb-title">' . $my_info->photo->title . '</h3>';         
		$html[] = '</div>';
	            
		return implode( "\n", $html );
	}
	
	
	/**
	* 
	* Get photo set slider display code
	*
	* @param string $photoset_id  The photoset ID to chose random photo from.
	* @param string $size  The size of the photo
	* @param string $show_photoset_ttl Flag to display photo set title
	*
	* @return string
	*/	
	function get_slider_display( $photoset_id, $size, $show_photoset_ttl = FALSE )
	{
	
		// http://wp.tutsplus.com/tutorials/theme-development/adding-a-responsive-jquery-slider-to-your-wordpress-theme/
		// http://flexslider.woothemes.com/video.html
		
		// Get Photoset
		$my_photo_set = $this->fetchPhotoset( $photoset_id  );  // TODO: pass size?		

		$html[] = '<div class="flexslider">';
		$html[] = '<ul class="slides">';
		
		foreach ( $my_photo_set->photoset->photo as $photo ) {
		
			// echo '<pre>';
			// print_r( $photo );
			// echo '</pre>';					
			$temp = "http://farm" . $photo->farm . ".staticflickr.com/" . $photo->server . "/" . $photo->id . "_" . $photo->secret . "_b.jpg";
			
			$farm_id = $photo->farm;
			$server_id = $photo->server;

			// TODO: Pro version only...
			$my_info = $this->fetchInfo( $photo->id );
			
			$html[] =  "<li><a href=\"" . $temp . "\"><img src='" . $photo->url_m . 
			"' width='" .
			            $photo->$size->width . "' height='" .
			            $photo->$size->height . "' /></a></li>";			     		            
		}		

		$html[] = '</div>';

		if( isset( $show_photoset_ttl ) and $show_photoset_ttl != '' ) {			
			$html[] = 'title '.$photo->title;
		}

		return implode( "\n", $html );
	}
	
}