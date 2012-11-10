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


/**
 * WPFlickr Admin
 * 
 * Main admin file which loads all settings panels and sets up admin menu.
 *
 * @author 		Nakunakifi
 * @category 	Admin
 * @package 	WPFlickr
 */
function cws_flickr_admin_scripts() 
{
	wp_enqueue_script( 'cws-flickr-plugin-admin', WPFLICKR_PLUGIN_URL . 'js/admin.js' );
	wp_localize_script( 'cws-flickr-plugin-admin', 'cws_flickr_', array( 	
										'siteurl' 	=> get_option( 'siteurl' ),
										'pluginurl' 	=> WPFLICKR_PLUGIN_URL,
										'cacheconfirm'	=> __( 'Are you sure you want to delete the cache?', 'cws_flickr' ),
									) );
}


/**
 * Admin Menus
 * 
 * Sets up the admin menus in wordpress.
 */
add_action( 'admin_menu', 'cws_flickr_add_page' );

function cws_flickr_add_page() {

	// Register options page
	$page = add_options_page(	
					__( 'Flickr Options', 'cws_flickr' ),
					__( 'Flickr Viewer', 'cws_flickr' ),
					'manage_options', 
					'cws_flickr', 
					'cws_flickr_options_page' );
								
   // Using registered $page handle to hook stylesheet loading 
   add_action( 'admin_print_styles-' . $page, 'cws_flickr_admin_scripts' );								
}


/**
 *
 * Draw the options page
 *
 */	
function cws_flickr_options_page() {

	// global $get_page;
	
	$hook = 'cws_flickr';
	
	if ( ! current_user_can( 'manage_options') ){ wp_die( __( 'You do not have sufficient permissions to access this page.' ) ); }
	
	?>
		<div class="wrap">
			<?php screen_icon(); ?>
			<h2>Flickr Settings Page</h2>
			<?php
				$options = get_option( 'cws_flickr_options' );		

 				$WPFlickr = new CWS_WPFlickr();

				// Check if we need to authenticate before displaying options form...
				$cws_FlickrAdmin = new CWS_FlickrAdmin( $WPFlickr->preflight_errors );
				
			if( ! $WPFlickr->preflight_errors ) {			
			
				if( isset( $_REQUEST[ 'cws_oauth_return'] ) ) {
				
					if( $cws_FlickrAdmin->debug ) error_log( 'Returned from callback' );
					
					if ( ! isset( $_REQUEST['page'] ) || $_REQUEST['page'] != $hook ) return; // Make sure we play nicely with other OAUth peeps
					
					// Save access token and run $cws_FlickrAdmin->is_authenticated() again...
					try{	
						if( $cws_FlickrAdmin->debug ) error_log( 'Storing Access Token' );
						$access_token = serialize( $cws_FlickrAdmin->consumer->getAccessToken( $_GET, unserialize( $cws_FlickrAdmin->request_token ) ) );
					
						add_option( 'CWS-FLICKR-ACCESS_TOKEN', $access_token ); 
						delete_option( 'CWS_FLICKR_REQUEST_TOKEN' );	// no longer need this token so delete it.
	
						header( "Location: " . CWS_WPFlickr::cws_get_admin_url( '/options-general.php' ) . "?page=cws_flickr" );
					}
					catch ( Zend_Oauth_Exception $ex ) {
						
						// Nuke request token...
						delete_option('CWS_FLICKR_REQUEST_TOKEN');						
						error_log( 'ERROR: ' . $ex );
						header( "Location: " . CWS_WPFlickr::cws_get_admin_url( '/options-general.php' ) . "?page=cws_flickr" );
						die();	
					}						
				}
				else {
					// If user is authenticated display options form
					if( $cws_FlickrAdmin->is_authenticated() )
					{
						?>							
						<form method="post" action="options.php">
							<?php	
							if( function_exists( 'settings_fields' ) ) {
								settings_fields( 'cws_flickr_options' );
							}
							
							// 
							if( function_exists( 'do_settings_sections' ) )	{
								do_settings_sections( 'cws_flickr' );
							}
							
							cws_flickr_setting_input();		// Grab the form					
							cws_flickr_meta_box_feedback();		// Grab the meta boxes
							// cws_flickr_meta_box_links();		// Grab the links meta boxes	
							?>
						</form>								
						<?php
					}
					else {
					?>
					<p>
						<?php _e( 'This is the preferred method of authenticating your Flickr account.', 'cws_flickr' ); ?> <br/>
						<?php _e( "All authentication is taken place on FLickr's secure servers.", 'cws_flickr' ); ?><br/>
					</p>
					<p>
                		<?php _e( 'Clicking the "Start the Login Process" link will redirect you to a login page at Flickr.com.', 'cws_flickr' ); ?><br/>
                		<?php _e( 'After accepting the login there you will be returned here.', 'cws_flickr' ); ?>
                	</p>
					<?php
						echo $cws_FlickrAdmin->get_grant_link();
					}
				}
		}
		else {
			$WPFlickr->showAdminMessages( $WPFlickr->preflight_errors );			
		}
?>		
		</div>
<?php
}

/**
 *
 * Register and define the settings
 *
 */	
add_action( 'admin_init', 'cws_flickr_admin_init' );
function cws_flickr_admin_init() {
	register_setting( 'cws_flickr_options', 'cws_flickr_options', 'cws_flickr_validate_options' );	// settings_fields
}


/**
 *
 * Display theme suggestion links
 *
 */	
function cws_flickr_meta_box_links() {
?>
	<div class="widget-liquid-right">
		<div id="widgets-right">
			<div style="width:20%;" class="postbox-container side">
				<div class="metabox-holder">
					<div class="postbox" id="feedback">
						<h3><span>Awesome Themes</span></h3>
							<div class="inside">
								<p>Showcase your Photos with an awesome theme:</p>
								<ul>
									<li><a href="">Link 1</a></li>
									<li><a href="">Link 2</a></li>
									<li><a href="">Link 3</a></li>
									<li><a href="">Link 4</a></li>
									<li><a href="">Link 5</a></li>									
								</ul>
								
							</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	
}

/**
 *
 * Display feedback links
 *
 */	
function cws_flickr_meta_box_feedback() {
?>
	<div class="widget-liquid-right">
		<div id="widgets-right">
			<div style="width:20%;" class="postbox-container side">
				<div class="metabox-holder">
					<div class="postbox" id="feedback">
						<h3><span><?php _e( 'We want your feedback!', 'cws_gpp' ); ?></span></h3>
							<div class="inside">
								<p><?php _e( 'If you have found a bug please email us', 'cws_gpp' ); ?> <a href="mailto:info@cheshirewebsolutions.com?subject=Feedback%20Flickr%20Viewer">info@cheshirewebsolutions.com</a></p>								
								<?php
									// Prepare  Tweet URL for localization
									$tweet_url = 'http://twitter.com/share?url=http://bit.ly/q4nqNA&text=';
									$tweet_url .= __( "Check out this awesome WordPress Plugin I'm using - Flickr Viewer", 'cws_flickr' );
								?>
								<p>&raquo; <?php _e( 'Share it with your friends', 'cws_gpp' ); ?> <a href="<?php echo $tweet_url; ?>"><?php _e( 'Tweet It', 'cws_gpp' ); ?></a></p>
							</div>
					</div>
				</div>
			</div>
		</div>
	</div>
<?php
	
}


/**
 *
 * Display and fill the form field
 *
 */	
function cws_flickr_setting_input() {
	
	// Get optios from the database
	$options = get_option( 'cws_flickr_options' );		
?>
	
	<div class="widget-liquid-left">
		<div id="widgets-left">		
			<div class="postbox-container">
				<div class="metabox-holder">				
					<div class="postbox" id="settings">
						<table class="form-table">				

							<tr>
								<th scope="row"><?php _e( 'Delete cache', 'cws_gpp'); ?></th>
								<td>
									<input id="delete_cache" type="button" name="cws_flickr_options[delete_cache]" value="<?php _e( 'Delete cache', 'cws_gpp'); ?>" />
								</td>
							</tr>
							<tr>
								<th scope="row"><?php _e( 'Enable FancyBox', 'cws_gpp'); ?></th>
								<td>
									<input type="checkbox" name="cws_flickr_options[enable_fancybox]" value="1" <?php if ( $options['enable_fancybox'] == 1) { echo 'checked'; } ?> />
									<p>
										<small><?php _e( 'Sometimes your theme will already include this FancyBox. If so you can disable FancyBox from being included by this plugin.', 'cws_gpp'); ?></p>
								</td>
							</tr>				
						</table>
						
					</div>
					
					<input type="submit" name="submit" class="button-primary" value="<?php _e('Save Changes', 'cws_gpp') ?>" />
				</div>				
			</div>
		</div>				
	</div>	
	<?php
}

/**
 *
 * Validate user input
 *
 */	
function cws_flickr_validate_options( $input ) {

	$errors = array();
	
	// $valid['max_album_results']     = esc_attr( $input['max_album_results'] );
	$valid['album_thumb_size']      = esc_attr( $input['album_thumb_size'] );
	$valid['max_results']           = esc_attr( $input['max_results'] );
	$valid['thumb_size']            = esc_attr( $input['thumb_size'] );
	$valid['max_image_size']        = esc_attr( $input['max_image_size'] );
	$valid['album_results_page']    = esc_attr( $input['album_results_page'] );		
	// $valid['inc_private']           =  $input['inc_private'] ;
	// $valid['show_album_ttl']        =  $input['show_album_ttl'] ;
	// $valid['show_slider_mrkrs']     =  $input['show_slider_mrkrs'] ;
	// $valid['enable_fancybox']     	=  $input['enable_fancybox'] ;
	
	// Correct validation of checkboxes
	$valid[inc_private] 		= ( isset( $input[inc_private] ) && true == $input[inc_private] ? true : false );
	$valid[show_album_ttl] 		= ( isset( $input[show_album_ttl] ) && true == $input[show_album_ttl] ? true : false );
	$valid[show_slider_mrkrs] 	= ( isset( $input[show_slider_mrkrs] ) && true == $input[show_slider_mrkrs] ? true : false );
	$valid[enable_fancybox] 	= ( isset( $input[enable_fancybox] ) && true == $input[enable_fancybox] ? true : false );
		
	// Validate numbers
	// Make sure Max Album Results is numeric
/*
	if( !is_numeric( $valid['max_album_results'] ) ) {
		$errors['max_album_results'] = 'Please enter a number for the number of albums to show on a page.';
	}
*/
			
	if( !is_numeric( $valid['album_thumb_size'] ) ) {
		$errors['album_thumb_size'] = 'Please enter a number in pixels for the album thumbnail size.';
	}	

	if( !is_numeric( $valid['max_results'] ) ) {
		$errors['max_results'] = 'Please enter a number for the number of results to show on a page.';
	}	

	if( !is_numeric( $valid['thumb_size'] ) ) {
		$errors['thumb_size'] = 'Please enter a number in pixels for the image thumbnail size.';
	}	

	if( !is_numeric( $valid['max_image_size'] ) ) {
		$errors['max_image_size'] = 'Please enter a number in pixels for image size in the lightbox (e.g. 600).';
	}			
	
	// Display all errors together
	// TODO: check this out
	if( count( $errors ) > 0 ) {
			
		$err_msg = '';
			
		// Display errors
		foreach( $errors as $err ) {
			$err_msg .= "$err<br><br>"; 
		}

		add_settings_error(
			'nap_gp_text_string',
			'cws_flickr_texterror',
			$err_msg,
			'error'
		);
	}
	return $valid;
}