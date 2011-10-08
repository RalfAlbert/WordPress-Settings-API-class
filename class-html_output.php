<?php
/**
 * @package WordPress
 * @subpackage Settings-API class
 * @author Ralf Albert
 * @version 0.2
 * @license GPL
 */

/**
 License:
 ==============================================================================
 Copyright 2010 Ralf Albert  (email : me@neun12.de)

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

if( ! class_exists( 'Settings_API_Class_HTML_Output' ) )
{
	class Settings_API_Class_HTML_Output
	{
		private $options = array();
		
		/**
		 *
		 * All settings
		 * @var array
		 */
		private $_settings = array();
		
		/**
		 *
		 * Single settings
		 * @var mixed
		 */
		public $options_group, $options_name, $validate_callback;
		public $menu_position, $page_slug, $page_title, $menu_title;
		public $capability, $description, $icon;
	
		/**
		 *
		 * Sections
		 * @var array
		 */
		public $sections = array();
	
		/**
		 *
		 * Descriptions for each section (optional setting)
		 * @var array
		 */
		public $section_descs = array();
	
		/**
		 *
		 * Field data for each field in different sections
		 * @var array
		 */
		public $settings_fields = array();
		
		public function __construct( array $settings )
		{
			// extract vars from $settings
			foreach( $settings as $key => &$value )
				$this->$key = $value;
		}
		

		/**
		 *
		 * Display the page
		 * @param none
		 * @return none
		 * @uses settings_fields()
		 * @uses do_settings_sections()
		 * @since 0.1
		 * @access public
		 */
		public function display_page() {
			echo '<div class="wrap">
			<div class="icon32" id="' . $this->icon . '"></div>
			<h2>' . $this->page_title . '</h2>';

			if ( isset( $this->description ) )
				echo '<p>' . $this->description . '</p>';

			echo '
			<form action="options.php" method="post">
				';
			settings_fields( $this->options_group );
			do_settings_sections( $this->page_slug );

			echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __('Save Changes') . '" /></p>
			</form>';
		}

		/**
		 *
		 * Display the description for a section if defined
		 * @param array $section
		 * @return none
		 * @since 0.1
		 * @access public
		 */
		public function display_section( array $section ) {
			if ( key_exists( $section['id'], $this->section_descs ) )
				echo $this->section_descs[$section['id']];
		}
		
		/**
		 *
		 * Outputs the HTML for every setting field
		 * @param array $args
		 * @return none
		 * @uses esc_textarea (since WP 3.1)
		 * @uses esc_html
		 * @since 0.1
		 * @access public
		 */
		public function display_settings_field( array $args ) {
			/*
			 * collecting the vars
			 */
	
			// extract vars from array
			foreach( $args as $key => &$value )
				$this->$key = $value;
	
			$this->options = get_option( $this->options_name );
	
			/*
			 * prepare the output
			 */
			
			// set standard for multi checkbox
			if( ( isset( $this->std ) && is_array( $this->std ) ) && 
				( $this->type == 'mcheckbox' || $this->type == 'mselect') &&
				! isset( $this->options[$this->id] ) ) {
	
				 	foreach( $this->std as $key ) {
						if( ! isset( $this->options[$this->id . '-' . $key] ) )
							$this->options[$this->id . '-' . $key] = 'on';
					}
			 }
	
			// set standard for all other
			if( ! isset( $this->options[$this->id] ) && isset( $this->std ) )
				$this->options[$this->id] = $this->std;
	
			// set css class
			$this->field_class = '';
			if( ! empty( $this->class ) )
				$this->field_class = ' class="' . $this->class . '"';
	
			// display setting field
			$field = $this->type;
			$this->$field();
			
			// reset vars
			foreach( $args as $key => $val )
				unset( $this->$key );
	
			unset( $args );
		}

/* ------------ display settings fields ------------ */
		/**
		 * 
		 * display checkbox
		 */
		private function checkbox()
		{
			$checked = '';
			if( isset( $this->options[$this->id]) && $this->options[$this->id] == 'on' )
				$checked = ' checked="checked"';
	
			echo '<input' . $this->field_class . ' type="checkbox" id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']" value="on"' . $checked . ' /> <label for="' . $this->id . '">' . $this->text_after . '</label>';
	
			$this->display_field_description( $this->desc );
		}
	
		/**
		 * 
		 * display select field
		 */
		private function select()
		{
			$lines = '';
			if( isset( $this->size ) && 1 < $this->size )
				$lines = ' size="' . $this->size . '"';
	
			echo '<select' . $this->field_class . ' name="' . $this->options_name . '[' . $this->id . ']"' . $lines . ' style="height:100%">';
	
			foreach( $this->choices as $value => $label ) {
				$selected = '';
				if( $this->options[$this->id] == $value )
					$selected = ' selected="selected"';
				echo '<option value="' . $value . '"' . $selected . '>' . $label . '</option>';
			}
	
			echo '</select>';
	
			$this->display_field_description( $this->desc );
		}
		
		/**
		 * 
		 * display radio buttons
		 */
		private function radio()
		{
			$i = 0;
			
			foreach( $this->choices as $value => $label) {
				$selected = '';
				if( $this->options[$this->id] == $value)
					$selected = ' checked="checked"';
	
				echo '<input' . $this->field_class . ' type="radio" name="' . $this->options_name . '[' . $this->id . ']" id="' . $this->id . $i . '" value="' . $value . '"' . $selected . '> <label for="' . $this->id . $i . '">' . $label . '</label>';
	
				if( $i < count( $this->choices ) - 1)
					echo '<br />';
	
				$i++;
			}
	
			$this->display_field_description( $this->desc );
		}	
		
		/**
		 * 
		 * display checkboxes with multiple selection
		 */
		private function mcheckbox()
		{
			$i = 0;
	
			foreach( $this->choices as $key => $label) {
				$checked = '';
				if( isset( $this->options[$this->id . '-' . $key]) && 'on' == $this->options[$this->id . '-' . $key])
					$checked = ' checked="checked"';
	
				echo '<input' . $this->field_class . ' type="checkbox" id="' . $this->id . '-' . $key . '" name="' . $this->options_name . '[' . $this->id . '-' . $key . ']" value="on"' . $checked . ' /> <label for="' . $this->id . '">' . $label . '</label>';
	
				if ( $i < count( $this->choices ) - 1 )
					echo '<br />';
	
				$i++;
			}
			
			// this hidden input is neccessary to identify if the form is already saved
			// or if it is the initial form with standard values
			echo '<input type="hidden" name="' . $this->options_name . '[' . $this->id . ']" value="on" />';
			
			$this->display_field_description( $this->desc );
		}
		
		/**
		 * 
		 * display select field with multiple selection
		 */
		private function mselect()
		{
			$lines = '';
			if( isset( $this->size ) && 1 < $this->size )
				$lines = ' size="' . $this->size . '"';
	
			echo '<select' . $this->field_class . ' name="' . $this->options_name . '[]"' . $lines . ' multiple="multiple" style="height:100%">';
	
			foreach( $this->choices as $key => $label ) {
				$selected = '';
				if( isset( $this->options[$this->id . '-' . $key] ) )
					$selected = ' selected="selected"';
				echo '<option value="' . $key . '"' . $selected . '>' . $label . '</option>';
			}
	
			echo '</select>';
			
			$this->display_field_description( $this->desc );
		}
		
		/**
		 * 
		 * display textarea
		 */
		private function textarea()
		{
			echo '<textarea' . $this->field_class . ' id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']" rows="' . $this->rows . '" cols="' . $this->cols . '" placeholder="' . $this->std . '">' . esc_textarea( $this->options[$this->id] ) . '</textarea>';
			$this->display_field_description( $this->desc );		
		}
		
		/**
		 * 
		 * display password field
		 */
		private function password()
		{
			echo '<input' . $this->field_class . ' type="password" id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']" value="' . $this->options[$this->id] . '" />' . $this->text_after;
			$this->display_field_description( $this->desc );
		}
		
		/**
		 * 
		 * display input field
		 */
		private function text()
		{
			echo '<input' . $this->field_class . ' type="text" size="' . $this->size . ' id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']"
				placeholder="' . $this->std . '" value="' . esc_html( $this->options[$this->id] ) . '" />' . $this->text_after;
			$this->display_field_description( $this->desc );
	
		}
		
		/**
		 * 
		 * display heading
		 */
		private function heading()
		{
			echo '</td></tr><tr valign="top"><td colspan="2">' . $this->desc;		
		}
		
		/**
		 *
		 * Helper function
		 * @param string $desc
		 * @return none
		 * @since 0.2
		 * @access private
		 */
		private function display_field_description( $desc ) {
			if( ! empty( $desc ) )
				echo '<br /><small>' . $desc . '</small>';
		}	

	}// end class_Settings_API_Class_HTML_Output
} // end if_class_exists