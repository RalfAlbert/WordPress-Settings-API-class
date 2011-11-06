<?php
/**
 * @package WordPress
 * @subpackage Settings-API class
 * @author Ralf Albert
 * @version 0.7.0
 * @license GPL
 */

/**
 License:
 ==============================================================================
 Copyright 2011 Ralf Albert  (email : me@neun12.de)

 This program is free software; you can redistribute it and/or modify
 it under the terms of the GNU General Public License as published by
 the Free Software Foundation; either version 2 of the License, or
 (at your option) any later version.

 This program is distributed in the hope that it will be useful,
 but WITHOUT ANY WARRANTY; without even the implied warranty of
 MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 GNU General Public License for more details.

 You should have received a copy of the GNU General Public License
 along with this program; if not, write to the Free Software
 Foundation, Inc., 59 Temple Place, Suite 330, Boston, MA  02111-1307  USA

 Requirements:
 ==============================================================================
 This plugin requires WordPress >= 3.0 and tested with PHP Interpreter >= 5.2.9
 */

if( ! class_exists( 'Easy_Settings_API_Class_HTML_Output' ) )
{
	class Easy_Settings_API_HTML_Output
	{		
		/**
		 * 
		 * Defaults for settings fields
		 * @var object $filed_defaults
		 */
		public static $fields_defaults = null;
				
		/**
		 * 
		 * Options from database
		 * @var array $options
		 */
		public static $options;
		
		/**
		 * 
		 * Constructor
		 * @param none
		 * @return void
		 * @since 0.4
		 * @access public
		 */
		public function __construct()
		{
			$sfd = new stdClass();
			$sfd->title		 = 'Empty';
			$sfd->section	 = 'general';			
			$sfd->id		 = 'default_field';
			$sfd->description = '';
			$sfd->text_after = '';
			$sfd->std		 = '';
			$sfd->type		 = 'text';
			$sfd->size		 = 0;
			$sfd->rows		 = 3;
			$sfd->cols		 = 25;
			$sfd->choices	 = array();
			$sfd->arguments	 = array();
			$sfd->class		 = '';
			
			$sfd->options_name = 'empty';
			
			self::$fields_defaults = $sfd;
			
		}
		
		/**
		 * 
		 * Print the options-page frame
		 * @param object $args
		 * @return void
		 * @since 0.4
		 * @access public
		 */
		public static function display_page( $args = null ){
			if( null === $args )
				return false;

			$def = new stdClass();
			$def->icon = 'icon-options-general';
			$def->page_title = __('Empty');
			$def->description = __('Empty');
			$def->options_group = '';
			$def->page_slug = '';
			
			$args = self::parse_args( $args, $def, true );
			
			echo '
			<div class="wrap">
				<div class="icon32" id="' . $args->icon . '"></div>
				<h2>' . $args->page_title . '</h2>
			';

				if ( '' != $args->description )
					echo '<p>' . $args->description . '</p>';

			echo '
				<form action="options.php" method="post">
			';
			
			settings_fields( $args->options_group );
			do_settings_sections( $args->page_slug );
			
			echo '
					<p class="submit">
						<input name="Submit" type="submit" class="button-primary" value="' . __('Save Changes') . '" />
					</p>
				</form>
			</div>
			';			
		}
		
	/* ------------ display settings fields ------------ */
		/**
		 * custom settings field
		 */
		public static function custom( $args ){
			if( null === $args )
				return false;
			
			if( isset( $args->callback ) ){
				if( ! is_array( $args->callback ) )
					return false;

				if( ! isset( $args->arguments ) )
					$args->arguments = array();
					
				if( ! is_array( $args->arguments ) )
					$args->arguments = (array) $args->arguments;
					
				call_user_func_array( $args->callback, $args->arguments );
			}
		
		}
	
		/**
		 * checkbox
		 */
		public static function checkbox( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			( isset( self::$options[$args->id] ) && 'on' === self::$options[$args->id] ) ? 
				$checked = 'checked="checked"' : $checked = ''; 
	
			printf(
				'<input id="%1$s" name="%2$s[%1$s]" %3$s type="checkbox" value="on" %5$s />&nbsp;
				<label for="%1$s">%4$s</label>',
				$args->id, $args->options_name,$args->class,  $args->text_after, $checked
			);
			
			self::display_field_description( $args->description );
		}
	
		/**
		 * radio buttons
		 */
		public static function radio( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			$i = 0;
			
			foreach( $args->choices as $key => $label) {

				( isset( self::$options[$args->id] ) && self::$options[$args->id] == $key ) ?
					$selected = 'checked="checked"' : $selected = ''; 
	
				printf(
					'<input id="%1$s4$s" name="%2$s[%1$s]" %3$s type="radio" value="%5$s" %6$s />&nbsp;
					 <label for="%1$s%4$s">%7$s</label>',
					$args->id, $args->options_name, $args->class, $i, $key, $selected, $label
				);
				
				if( $i < count( $args->choices ) - 1)
					echo '<br />';
	
				$i++;
			}
	
			self::display_field_description( $args->description );
		}	
		
		/**
		 * checkboxes with multiple selection
		 */
		public static function mcheckbox( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			$i = 0;
	
			foreach( $args->choices as $key => $label) {

				( isset( self::$options[$args->id . '-' . $key]) && 'on' === self::$options[$args->id . '-' . $key]) ?
					$checked = 'checked="checked"' : $checked = '';
	
				printf(
					'<input id="%1$s-%4$s" name="%2$s[%1$s-%4$s]" type="checkbox" value="on" %5$s />&nbsp;
					<label for="' . $args->id . '">' . $label . '</label>',
					$args->id, $args->options_name, $args->class, $key, $checked
				
				);
				
				if ( $i < count( $args->choices ) - 1 )
					echo '<br />';
	
				$i++;
			}
			
			// this hidden input is neccessary to identify if the form is already saved
			// or if it is the initial form with standard values
			//echo '<input type="hidden" name="' . $args->options_name . '[' . $args->id . ']" value="on" />';
			printf(
				'<input type="hidden" name="%2$s[%1$s]" value="on" />',
				$args->id, $args->options_name
			);
			
			self::display_field_description( $args->description );
		}
		
		/**
		 * select field
		 */
		public static function select( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			( isset( $args->size ) && 1 < $args->size ) ?
				$size = 'size="' . $args->size . '"' : $size = '';
	
			printf(
				'<select id="%1$s" name="%3$s[%1$s]" %2$s %4$s style="height:100%%">',			
				$args->id, $args->class, $args->options_name, $size
			);
			
			foreach( $args->choices as $key => $label ) {
		
				( isset( self::$options[$args->id] ) && self::$options[$args->id] == $key ) ?
					$selected = 'selected="selected"' : $selected = '';
	
				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$key, $selected, $label
				);
			}
	
			echo '</select>' . $args->text_after;
	
			self::display_field_description( $args->description );
		}
		
		/**
		 * select field with multiple selection
		 */
		public static function mselect( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			( isset( $args->size ) && 1 < $args->size ) ?
				$size = 'size="' . $args->size . '"' : $size = '';

			printf( '<select id="%1$s" %2$s name="%3$s[%1$s][]" %4$s multiple="multiple" style="height:100%%">', 
				$args->id, $args->class, $args->options_name, $size
			);
			
			foreach( $args->choices as $key => $label ) {
				
				( in_array( $key, self::$options[$args->id] ) ) ?
					$selected = 'selected="selected"' : $selected = '';

				printf(
					'<option value="%1$s" %2$s>%3$s</option>',
					$key, $selected, $label
				);
				
			}
	
			echo '</select>' . $args->text_after;
			
			self::display_field_description( $args->description );
		}
		
		/**
		 * textarea
		 */
		public static function textarea( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			$value = isset( self::$options[$args->id] ) ? self::$options[$args->id] : $args->std;
			
			printf(
				'<textarea id="%1$s" name="%2$s[%1$s]" %3$s rows="%4$s" cols="%5$s" placeholder="%6$s">%7$s</textarea>%8$s',
				$args->id, $args->options_name, $args->class, $args->rows, $args->cols, $args->std, esc_textarea( $value ), $args->text_after
			);

			self::display_field_description( $args->description );		
		}
		
		/**
		 * password field
		 */
		public static function password( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			$value = isset( self::$options[$args->id] ) ? self::$options[$args->id] : $args->std;
			
			printf(
				'<input id="%1$s" name="%2$s[%1$s]" %3$s type="password"  value="%4$s" />%5$s',
				$args->id, $args->options_name, $args->class, $value, $args->text_after
			);
			
			self::display_field_description( $args->description );
		}
		
		/**
		 * input field
		 */
		public static function text( $args = null ){
			if( null === $args )
				return false;

			$args = self::parse_args( $args, self::$fields_defaults );
			
			$value = isset( self::$options[$args->id] ) ? self::$options[$args->id] : $args->std;
			
			printf(
				'<input id="%1$s" name="%2$s[%1$s]" %3$s type="text"  value="%4$s" size="%6$s" placeholder="%7$s" />%5$s',
				$args->id, $args->options_name, $args->class, esc_html( $value ), $args->text_after, $args->size, $args->std
			);

			self::display_field_description( $args->description );	
		}
		
		/**
		 * heading
		 */
		public static function heading( $args ){
			if( null === $args )
				return false;

			$def = new stdClass();
			$def->description = '';
			
			$args = self::parse_args( $args, $def, true );
						
			echo '</td></tr><tr valign="top"><td colspan="2">' . $args->description;

		}
		
		/**
		 *
		 * Helper function
		 * @param string $desc
		 * @return void
		 * @since 0.2
		 * @access public static
		 */
		public static function display_field_description( $desc ){
			if( ! empty( $desc ) )
				echo '<br /><small>' . $desc . '</small>';
		}
		
		/**
		 * 
		 * Setter for $options
		 * @param array $options
		 * @return void
		 * @since 0.5
		 * @access public
		 */
		public function set_options( $options = null ){
			if( null !== $options )
				self::$options = $options;
		}
		
		/**
		 * 
		 * Getter for $options
		 * @param none
		 * @return array $options
		 * @since 0.5
		 * @access public
		 */
		public function get_options(){
			return self::$options;
		}
		
		/**
		 * 
		 * Getter for fields defaults
		 * @param none
		 * @return object $fields_defaults
		 * @since 0.5.2
		 * @access public
		 */
		public function get_fields_defaults(){
			return self::$fields_defaults;
		}
		
		/**
		 * 
		 * Parsing arguments
		 * 
		 * Retrieving two objects and fill the input-object with the default-object
		 * If the optional parameter $cleaning is set to true, keys which are not set in
		 * the defaults-object will be deleted.
		 * 
		 * @param object $input
		 * @param object $defaults
		 * @param bool $cleaning
		 * @return object $input parsed (and cleanded) object
		 * @since 0.5
		 * @access public static
		 */
		public static function parse_args( $input = null, $defaults = null, $cleaning = false ){
			if( null === $input || null === $defaults )
				return false;
			
			// let WordPress parsing arrays. cast the returned array to an object
			if( is_array( $input ) ){
				$input = (object) wp_parse_args( $input, (array) $defaults );
			}
			else {
				foreach( $defaults as $key => $value ){
					if( ! isset( $input->$key ) )
						$input->$key = $defaults->$key;
				}
			}
			
			if( $cleaning ){
				foreach( $input as $key => $value ){
					if( ! isset( $defaults->$key ) )
						unset( $input->$key );
				}
			}
			
			return $input;
		}


	} // end class_Settings_API_Class_HTML_Output
} // end if_class_exists