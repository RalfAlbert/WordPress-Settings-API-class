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

require_once 'class-easy_settings_api_html_output.php';

class Easy_Settings_API
{
	/**
	 * 
	 * The HTML output object
	 * @var object $output
	 */
	private $output = null;
	
	/**
	 * 
	 * Defining which class should be used for the output. Cann be overwritten by add_filter
	 * @var string $output_class
	 */
	public $output_class;
	
	private $output_class_standard = 'Easy_Settings_API_HTML_Output';
	
	/**
	 * 
	 * Options read from database
	 * @var array $options
	 */
	protected $options = array();
		
	/**
	 *
	 * All settings
	 * @var array
	 */
	protected $_settings = array();
	
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
	protected function setup() {
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
				'description', 'capability', 'icon',
				'sections', 'section_desc', 'settings_fields',
		);
		
		foreach( $this->_settings as $key => &$value ){
			if( in_array( $key, $whitelist_vars ) )
				$this->$key = $value;
		}

		// cleanup your mess
		unset( $whitelist_where, $whitelist_vars, $value, $key, $settings, $defaults );
					
		$this->set_output();
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
	protected function init() {
		add_action( 'admin_menu', array( &$this, 'add_page' ) );
		add_action( 'admin_init', array( &$this, 'register_settings' ) );
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
	
	protected function set_output(){
		if( null === $this->output ){		
			// setup the outputclass
			if( '' == $this->output_class )
				$this->set_output_class( $this->output_class_standard );
				
			// create the html-output object
			$this->output = new $this->output_class;
		}
		else
			return $this->output;		
	}
	
	protected function get_output(){
		if( null === $this->output )
			$this->set_output();
			
		return $this->output;
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
	 * Add the page to the admin menu. Store page-hook in $_settings.
	 * @param none
	 * @return none
	 * @uses add_xxx_page()
	 * @since 0.1
	 * @access public
	 */
	public function add_page() {
		$where = 'add_' . $this->menu_position . '_page';
		$this->_settings['admin_page'] = $where( $this->page_title, $this->menu_title, $this->capability, $this->page_slug, array( &$this, 'display_page' ) );
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
			add_settings_section( $slug, $title, array( &$this, 'display_section' ), $this->page_slug );
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
		$this->set_output();
		
		wp_parse_args( $args, $this->output->single_setting_defaults );
		
		// copy the 'id' to 'label_for'
		$args['label_for'] = $args['id'];

		add_settings_field( $args['id'], $args['title'], array( &$this, 'display_settings_field' ), $this->page_slug, $args['section'], $args );
		
		//cleanup
		unset( $args, $defaults );
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
		$this->set_output();
		echo $this->output->page_content_top;
		
		settings_fields( $this->options_group );
		do_settings_sections( $this->page_slug );
		
		echo $this->output->page_content_footer;
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
		 * prepare the output
		 */

		$this->set_output();

		$this->get_option( $this->options_name );

		// extracting vars for display in $output
		$whitelist_vars = $this->output->single_setting_defaults; //array( 'id', 'type', 'std', 'class', 'desc' );

		foreach( $args as $key => $value ){
			if( key_exists( $key, $whitelist_vars ) )
				$this->output->$key = $value;
		}

		$copy_vars = array( 'id', 'type', 'std', 'class' );
		
		foreach( $copy_vars as $cv ){
			if( isset( $args[$cv] ) )
				$$cv = $args[$cv];
		}
		
		// set standard for multi checkbox
		if( ( isset( $std ) && is_array( $std ) ) && 
			( $type == 'mcheckbox' || $type == 'mselect') &&
			! isset( $this->options[$id] ) ) {

			 	foreach( $std as $key ) {
					if( ! isset( $this->options[$id . '-' . $key] ) )
						$this->options[$id . '-' . $key] = 'on';
				}
		 }

		// set standard for all other
		if( ! isset( $this->options[$id] ) && isset( $std ) )
			$this->options[$id] = $std;

$this->output->options = get_option( $this->options_name );
var_dump($this->output->options);			
		// set css class
		$this->output->field_class = '';
		if( ! empty( $class ) )
			$this->output->field_class = ' class="' . $class . '"';

		// display setting field
		$field = $type;
		$this->output->$field();
		
		// reset vars
		foreach( $args as $key => $value ){
			if( key_exists( $key, $whitelist_vars ) )
				unset( $this->output->$key );
		}
		
		$this->output->options = array();
		
		unset( $args, $key, $whitelist_vars, $field );
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