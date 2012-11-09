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

/*************************************************
*
*	Set up 'Widget' to display albums
*	Drag and drop widget to a widgetized area 
*	of the theme
*
**************************************************/
	class Widget_RandomPhoto extends WP_Widget {
	     
		function Widget_RandomPhoto() {		
			parent::WP_Widget( false, $name = 'Flickr Random Photo' );		
		}


		function widget( $args, $instance ) { 
		 	
		 	global $key;
		 	
			extract( $args );
			
			$title          = apply_filters( 'widget_title', $instance['title'] );
			$photoset_id    = apply_filters( 'widget_title', $instance['photo_set_id'] );
			$show_photoset_ttl = apply_filters( 'widget_title', $instance['show_photoset_ttl'] );
																		
			if ( !isset ( $title ) ) {
				$title = "'Flickr Random Photo";
			}
			echo $args['before_widget'];
			echo $args['before_title'] . "<span>$title</span>" . $args['after_title'];			
			
			// Create instance of FlickrAPI class
			if( $my_flickr = new CWS_FlickrApi ) {

				$options 	= get_option( 'cws_flickr_options' );

				// Call user albums													
				if( isset( $photoset_id ) && $photoset_id != '' ) 
				{				
					$my_photo_random = $my_flickr->get_photo_random_display( $photoset_id, $size, $show_photoset_ttl );
				}
			
			echo $my_photo_random;
			echo $args['after_widget'];
		
			}		
			
		}	
		
		
		function update ( $new_instance, $old_instance ) {
	
			$instance = $old_instance;
			
			$instance['title'] 		= strip_tags( $new_instance['title'] );
			$instance['show_photoset_ttl'] 	= strip_tags( $new_instance['show_photoset_ttl'] );			
			$instance['photo_set_id'] 	= strip_tags( $new_instance['photo_set_id'] );			

	
			return $instance;	     	
		}
		
		
		function form( $instance ) {
		
			$title          	= esc_attr( $instance['title'] );
			$show_photoset_ttl 	= esc_attr( $instance['show_photoset_ttl'] );			
			$photo_set_id    	= esc_attr( $instance['photo_set_id'] );	
			 ?>
				<p>
					<label for="<?php echo $this->get_field_id( 'title' ); ?>"><?php _e( 'Title:', 'cws_flickr' ); ?></label> 
					<input class="widefat" id="<?php echo $this->get_field_id( 'title' ); ?>" name="<?php echo $this->get_field_name( 'title' ); ?>" type="text" value="<?php echo $title; ?>" />
				</p>
				<p>
					<label for="<?php echo $this->get_field_id( 'show_photoset_ttl' ); ?>"><?php _e( 'Show Photo Set title:', 'cws_flickr' ); ?></label> 
					<input id="<?php echo $this->get_field_id( 'show_photoset_ttl' ); ?>" type="checkbox" name="<?php echo $this->get_field_name( 'show_photoset_ttl' ); ?>"  value="1" <?php if ( $show_photoset_ttl == 1) { echo 'checked'; } ?> />						
				</p>				
				<p>
					<label for="<?php echo $this->get_field_id( 'photo_set_id' ); ?>"><?php _e( 'Photo Set ID:', 'cws_flickr' ); ?></label> 
					<input id="<?php echo $this->get_field_id( 'photo_set_id' ); ?>" type="text" name="<?php echo $this->get_field_name( 'photo_set_id' ); ?>"  value="<?php echo $photo_set_id; ?>" />		
				</p>	

			<?php 
		}	
		
	}
