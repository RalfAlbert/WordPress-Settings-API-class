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

/*
 * Credits go to:
 * - Alison Barret[1]
 * 	 His tutorial[2] has inspired me and helped me to write this class
 *
 * [1] http://alisothegeek.com/author/abarrett/
 * [2] http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 */

class Easy_Settings_API_Class
{
	/**
	 * 
	 * The HTML output object
	 * @var object $output
	 */
	private $output;
	
	/**
	 * 
	 * Defining which class should be used for the output. Cann be overwritten by add_filter
	 * @var string $output_class
	 */
	public $output_class;
	
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
	
		
	/**
	 *
	 * Constructor
	 * Starts the class automatically if $settings is given
	 * @param array $settings
	 * @return none
	 * @since 0.1
	 * @access public
	 */
	public function __construct( array $settings = NULL ) {
		
		if( NULL !== $settings ) {
			$defaults = array(
				'menu_position'		 => 'options',
				'page_slug'			 => 'custom_page',
				'options_group'		 => 'custom_options_group',
				'options_name'		 => 'custom-options',
				'validate_callback'	 => '',
				'page_title'		 => 'Custom Optionpage',
				'menu_title'		 => 'Custom Optionpage',
				'capability'		 => 'manage_options',
				'description'		 => '',
	
				'sections'			 => array(
					'custom' => __('Custom Section')
				),
	
				'section_desc'		 => array(
					'custom' => __('Description for the custom section.')
				),
	
				'settings_fields'			 => array(
					// first field
					array(
						'id'		 => 'sample_text',
						'title'		 => __('Sample Text'),
						'desc'		 => __('This is a sample description.'),
						'text_after' => '',
						'std'		 => 'sample text',
						'size'		 => 30,
						'type'		 => 'text',
						'section'	 => 'custom'
					),
					
					// second field
					// array (...),
					// third field ... and so on
				)
			);
	
			$this->set_settings( $settings, $defaults );

			if( ! empty( $this->_settings ) ){
				return $this->create_option_page();
			}
		}

	}

	/**
	 * 
	 * Creating the option page and hook into actions
	 */
	public function create_option_page(){
			$this->setup();
			$this->init();
			
			return true;
	}
	
	/**
	 *
	 * Setter for settings
	 * Validate, sanitize and copy the settings from given array to internal storage
	 * @param array $settings
	 * @return none
	 * @uses wp_parse_args()
	 * @since 0.1
	 * @access public
	 */
	public function setup() {
		if( empty( $this->_settings ) )
			throw new Exception( 'No settings was set' );
						
		// validate where the option-page should be appear 
		$whitelist_where = array(
			'dashboard', 'posts', 'media', 'links',
			'pages', 'comments', 'theme', 'plugins',
			'users', 'options', 'management', 'menu'
		);

		if ( ! in_array( $this->_settings['menu_position'], $whitelist_where ) )
			$this->_settings['menu_position'] = 'options';
			
		// extract vars from $settings
		// copy needed vars from array $settings to the class-object
		$whitelist_vars = array(
			'options_group', 'options_name', 'validate_callback',
			'menu_position', 'page_slug', 'page_title', 'menu_title',
			'capability', 'description', 'icon',
		);
		
		foreach( $this->_settings as $key => &$value ){
			if( in_array( $key, $whitelist_vars ) )
				$this->$key = $value;
		}

		// cleanup your mess
		unset( $whitelist_where, $whitelist_vars, $value, $key, $settings, $defaults );

					
		// setup the outpclass
		if( '' == $this->output_class )
			$this->set_output_class( 'Settings_API_Class_HTML_Output' );
		
		// create the html-output object
		$this->output = new $this->output_class;
	}
	
	/**
	 * 
	 * Set output class
	 * @param string $class
	 */
	public function set_output_class( $classname = '' ){
		if( '' != $classname )			
			$this->output_class = apply_filters( 'easy_settings_api_outputclass', $classname );
	}
	
	/**
	 *
	 * Getter for settings
	 * @param none
	 * @return array
	 * @since 0.1
	 * @access public
	 */
	public function get_settings() {
		return $this->_settings;
	}

	/**
	 * 
	 * Setter for $settings
	 * @param array $settings
	 * @return array $_settings
	 * @since 0.3
	 * @access public
	 */
	public function set_settings( array $settings, $defaults = array() ){
		// Sanitize the users data!
		// There is only one thing that makes you sleep well:
		// Better than security is more security
		array_walk_recursive( $settings, array( &$this, 'sanitize_settings' ) );

		// if defaults are set, merging them with settings
		if( ! empty( $defaults ) )
			$this->_settings = wp_parse_args( $settings, $defaults );
		else				
			$this->_settings = $settings;
			
		return $this->_settings;
		
	}
	
	/**
	 *
	 * Adding the page to the menu and register the settings
	 * @param none
	 * @return none
	 * @uses add_action()
	 * @since 0.1
	 * @access public
	 */
	public function init() {
		add_action( 'admin_menu', array( &$this, 'add_page' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
	}

	/**
	 *
	 * Add the page to the admin menu. Store page-hook in $_settings.
	 * @param none
	 * @return none
	 * @uses add_xxx_page()
	 * @since 0.1
	 * @access public
	 */
	public function add_page() {
		$where = 'add_' . $this->menu_position . '_page';
		$this->_settings['admin_page'] = $where( $this->page_title, $this->menu_title, $this->capability, $this->page_slug, array( &$this->output, 'display_page' ) );
	}

	/**
	 *
	 * Register the settings via WP Settings-API
	 * @param none
	 * @return none
	 * @uses register_settings()
	 * @uses add_settings_section()
	 * @uses create_settings()
	 * @since 0.1
	 * @access public
	 */
	public function register_settings() {

		register_setting( $this->options_group, $this->options_name, $this->validate_callback );

		foreach( $this->sections as $slug => $title ) {
			add_settings_section( $slug, $title, array( &$this->output, 'display_section' ), $this->page_slug );
		}

		foreach ( $this->settings_fields as $field ) {
			$this->create_setting( $field );
		}
		
		unset( $slug, $title, $field );
	}

	/**
	 *
	 * Create a settings field with given arguments
	 * @param array $args
	 * @return none
	 * @uses wp_parse_args()
	 * @uses add_settings_field()
	 * @since 0.1
	 * @access public
	 */
	public function create_setting( $args = array() ) {

		$defaults = array(
			'title'		 => 'Empty',
			'section'	 => 'general',
		
			'id'		 => 'default_field',
			'desc'		 => '',
			'text_after' => '',
			'std'		 => '',
			'type'		 => 'text',
			'size'		 => 0,
			'rows'		 => 3,
			'cols'		 => 25,
			'choices'	 => array(),
			'arguments' => array(),
			'class'		 => ''
		);
		
		wp_parse_args( $args, $defaults );
		
		// copy the 'id' to 'label_for'
		$args['label_for'] = $args['id'];

		add_settings_field( $args['id'], $args['title'], array( &$this->output, 'display_settings_field' ), $this->page_slug, $args['section'], $args );
		
		//cleanup
		unset( $args, $defaults );
	}
	
/* --------------- sanitizing --------------- */ 
	/**
	 *
	 * Sanitizing the users data
	 * Strings will be sanitize with esc_attr, all other values will be cast to integer (we only need strings and integer)
	 * @param mixed $data
	 * @return none $data will be modified by reference
	 * @since 0.2.1
	 * @access private
	 */
	private function sanitize_settings( &$data ) {
		if ( is_string( $data ) )
			esc_attr( $data );
		else
			$data = intval( $data );
	}	
} // end_class_Easy_Settings_API_Class

class Easy_Settings_API_Class_HTML_Output extends Easy_Settings_API_Class
{
	/**
	 * 
	 * Options read from database
	 * @var array $options
	 */
	private $options = array();
	
	public function __construct( array $settings )
	{
		// get options from database
		$this->get_option( $this->options_name );		
	}	

	/**
	 * 
	 * Return options from database
	 * @param string $options_name
	 */
	protected function get_option( $options_name ){
		if( empty( $this->options ) )
			$this->options = get_option( $options_name );
			
		return $this->options;
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
		 * collectinc the vars
		 * 
		 * extract vars from array
		 * do not use list() because the vars will be used in the displaying-methods
		 * only copy vars that will be needed
		 * 
		 */
		$whitelist_vars = array( 'id', 'type', 'std', 'class' );
		
		foreach( $args as $key => &$value ){
			if( in_array( $key, $whitelist_vars ) )
				$this->$key = $value;
		}
			

		// $this->options was normally defined in __constructor.
		$this->get_option( $this->options_name );

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
		foreach( $args as $key => $value ){
			if( in_array( $key, $whitelist_vars ) && isset( $this->key ) )
				unset( $this->$key );
		}
		
		unset( $args, $key, $whitelist_vars, $field );
	}

/* ------------ display settings fields ------------ */
	
	/**
	 * custom settings field
	 */
	private function custom( $args )
	{
		if( isset( $this->callback ) ){
			if( ! is_array( $this->callback ) )
				return false;
	
			if( ! isset( $args ) )
				$args = array();

			call_user_func_array( $this->callback, $args );
		}
	
	}
	
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

}// end Easy_Settings_API_Class_HTML_Output
