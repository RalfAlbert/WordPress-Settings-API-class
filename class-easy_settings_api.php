<?php
/**
 * @package WordPress
 * @subpackage Settings-API class
 * @author Ralf Albert
 * @version 0.6.0
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

//TODO: a lot
/*
 * - options-name und options-group optional machen
 * 	wenn options-name/group nicht angegeben ist, den pluginnamen auslesen und name/group anhängen
 * 
 * - capability optional machen (default: manage_options)
 * - page_slug / page_title optional machen. default: Pluginname + _optionspage (page_slug)/ __('Optionspage')
 * 
 * 
 */

/*
 * Credits go to:
 * - Alison Barret[1]
 * 	 His tutorial[2] has inspired me and helped me to write this class
 *
 * [1] http://alisothegeek.com/author/abarrett/
 * [2] http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 */

if( ! class_exists( 'Easy_Settings_API' ) ){
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
		 * Defining which class should be used for the output
		 * @var string $output_class
		 */
		public $output_class;
		
		/**
		 * 
		 * The standard output-classname
		 * @var constant string
		 */
		CONST STANDARD_OUTPUT_CLASS = 'Easy_Settings_API_HTML_Output';
		
		/**
		 * 
		 * Options from database
		 * @var array $options
		 */
		protected $options = array();
			
		/**
		 *
		 * All settings
		 * @var array
		 */
		protected static $_settings = array();
		
		protected static $static_settings = array();
		
		/**
		 *
		 * Single settings
		 * @var string mixed
		 */
		protected $options_group, $options_name, $validate_callback;
		protected $menu_position, $page_slug, $page_title, $menu_title;
		protected $capability, $description, $icon;
	
		/**
		 *
		 * Sections
		 * @var array
		 */
		protected $sections = array();
	
		/**
		 *
		 * Descriptions for each section (optional setting)
		 * @var array
		 */
		protected $section_descs = array();
	
		/**
		 *
		 * Field data for each field in different sections
		 * @var array
		 */
		protected $settings_fields = array();
		
		public $parent = '';
		private $errors = '';
			
		/**
		 *
		 * Constructor
		 * Starts the class automatically if $settings is given
		 * @param array $settings
		 * @return none
		 * @since 0.1
		 * @access public
		 */
		public function __construct( array $settings = null, $parent = '' ) {
			$this->parent = $parent;
			
			// check if $settings was set. if not, just create an object of this class
			if( null !== $settings ) {
				$this->setup( $settings );
				$this->init();
			}
	
		}
	
		protected function add_error( $msg = '' ){
			$this->errors .= '<p>' . $msg . '</p>';	
		}
		
		public function show_errors(){
			if( '' != $this->errors ){
				echo "<div class='error'>{$this->errors}</div>";
				
				remove_action( 'admin_menu', array( &$this, 'add_page' ) );
				remove_action( 'admin_init', array( &$this, 'register_settings' ) );		
			}
		}
		
		/**
		 *
		 * Setup the class-vars
		 * Validate, sanitize and copy the settings
		 * @param array $settings
		 * @return bool true|false
		 * @uses wp_parse_args()
		 * @since 0.1
		 * @access public
		 */
		public function setup( array $settings = null) {
			if( null === $settings )
				return false;
			
			// create defaults
			$defaults = $this->get_defaults();
					
			// get html-output-class
			$this->set_output();
			
			// copy settings_fields_defaults from html-output-class 
			$defaults['settings_fields'] = $this->output->settings_fields_defaults;
	
			// copy and parse $settings to class-var
			$this->set_settings( $settings, $defaults );
			
			// validate where the option-page should be appear 
			$whitelist_where = array(
				'dashboard', 'posts', 'media', 'links',
				'pages', 'comments', 'theme', 'plugins',
				'users', 'options', 'management', 'menu'
			);
	
			self::$_settings['menu_position'] = strtolower( self::$_settings['menu_position'] );
			
			if ( ! in_array( self::$_settings['menu_position'], $whitelist_where ) )
				self::$_settings['menu_position'] = 'options';
				
			// extract vars from $_settings
			// copy needed vars from array $_settings to the class-vars
			$whitelist_vars = array(
					'options_group', 'options_name', 'validate_callback',
					'menu_position', 'page_slug', 'page_title', 'menu_title',
					'description', 'capability', 'icon',
					'sections', 'section_desc', 'settings_fields',
			);
			
			foreach( self::$_settings as $key => &$value ){
				if( in_array( $key, $whitelist_vars ) )
					$this->$key = $value;
			}
	
			self::$static_settings = self::$_settings;
			
			return true;
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
		public function init(){
			add_action( 'admin_notices', array( &$this, 'show_errors' ) );
			
			add_action( 'admin_menu', array( &$this, 'add_page' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );
		}
	
		/**
		 * 
		 * Create the array with default values
		 * Try to read the plugin-header and copy all available data from the
		 * plugin-header to the defaults array.
		 * @param none
		 * @return array $defaults
		 * @uses get_plugin_data()
		 * @since 0.6.1
		 * @access protected
		 */
		protected function get_defaults(){
 			$defaults = array(
				'menu_position'		 => 'options',
				'page_slug'			 => '',
				'options_group'		 => '',
				'options_name'		 => '',
				'validate_callback'	 => '',
				'page_title'		 => '',
				'menu_title'		 => '',
				'capability'		 => 'manage_options',
				'description'		 => '',
 				'icon'				 => 'icon-options-general',
	
				'sections'			 => array(
					'default' => __('Default Section')
				),
	
				'section_desc'		 => array(
					'default' => __('Description for the default section.')
				),
	
				'settings_fields'	 => array(
					array(
						'id'		 => 'default_heading',
						'title'		 => __('Default heading'),
						'desc'		 => __('This heading is displayed when the default values are used.'),
						'type'		 => 'heading',
						'section'	 => 'default'
					),
				)
			);
			
			/*
			 * fetch plugin-header and copy data from plugin-header to defaults array
			 */
			if( '' != $this->parent && is_admin() ){
				// must be included for function get_plugin_data
				require_once ABSPATH.'wp-admin/includes/plugin.php';
				$data = get_plugin_data( $this->parent );
					
				$name = isset( $data['Name'] ) ? $data['Name'] : 'Default Plugin';
				$desc = isset( $data['Description'] ) ? $data['Description'] : 'Default description';
					
				$slug = strtolower( esc_attr( str_replace( ' ', '_', $name ) ) );
				
				$defaults['page_slug'] 		= $slug;
				$defaults['options_group']	= $slug;
				$defaults['options_name']	= $slug;
				$defaults['page_title'] 	= $name;
				$defaults['menu_title'] 	= $name;
				$defaults['description'] 	= $desc;
			}
			
			return $defaults;			
		}
				
		/**
		 * 
		 * Return options from database or $this->options if already set
		 * @param string $options_name
		 * @return array $options
		 * @since 0.4
		 * @access protected
		 */
		protected function get_options( $options_name = '' ){
			// we can't get options from database if we do not know which option to retrive
			// but if $this->options is already set, return this options
			if( '' == $options_name && empty( $this->options ) )
				return false;
				
			if( empty( $this->options ) )
				$this->options = get_option( $options_name );
				
			return $this->options;
		}
		
		/**
		 * 
		 * Returns the html-output-object
		 * Create it if it does not exists
		 * @param none
		 * @return object $output
		 * @since 0.3
		 * @access protected
		 */
		protected function get_output(){
			if( null === $this->output )
				$this->set_output();
				
			return $this->output;
		}
			
		/**
		 * 
		 * Setup the html-output-class and create the html-output-object
		 * @param none
		 * @return object $output
		 * @since 0.3
		 * @access protected
		 */
		protected function set_output(){
			// create the output-object
			if( null === $this->output ){
				// setup the output-class
				// if no output-class is defined, use the standard output-class
				if( ! isset( $this->output_class ) || empty( $this->output_class ) )
					$this->output_class = self::STANDARD_OUTPUT_CLASS;
					
				// require file with output-class if class does not exists
				// class-name will be convertet to filename
				// pattern: "class-" + class-name in lower case + ".php"
				if( ! class_exists( $this->output_class ) )
					require_once 'class-'.strtolower( $this->output_class ).'.php';
					
				// create the html-output object
				$this->output = new $this->output_class;
			}
			else
				return $this->output;		
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
			return self::$_settings;
		}
	
		/**
		 * 
		 * Setter for $settings
		 * Set $_settings if $settings is given and $defaults not
		 * Sanitize $settings
		 * Merge $settings and $defaults if both are given
		 * 
		 * @param array $settings
		 * @return array $_settings
		 * @since 0.3
		 * @access public
		 */
		public function set_settings( array $settings = null, $defaults = array() ){
			// no settings, no action
			if( null === $settings )
				return false;
				
			// Sanitize the users data!
			// There is only one thing that makes you sleep well:
			// Better than security is more security
			array_walk_recursive( $settings, array( &$this, 'sanitize_settings' ) );
				
			// if defaults are set, merging them with settings
			if( ! empty( $defaults ) )
				self::$_settings = wp_parse_args( $settings, $defaults );
			else				
				self::$_settings = $settings;

			// check for empty values
			$not_empty = array( 'page_slug', 'options_group', 'options_name', 'menu_title', 'page_title' );
			$val_error = array();
			
			foreach( $not_empty as $key ){
				if( empty( self::$_settings[$key] ) )
					array_push( $val_error, $key );
			}
			if( ! empty( $val_error ) && is_admin() ){
				$msg = '<h4>Error in class <b>Easy Settings-API</b></h4><br />The following value(s) have to be set in configuration array:';
				$msg .= '<ol>';
				foreach( $val_error as $e )
					$msg .= "<li>{$e}</li>";
				$msg .= '</ol>';
				
				$this->add_error( $msg );
			}
			
			return self::$_settings;
			
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
			self::$_settings['admin_page'] = $where( $this->page_title, $this->menu_title, $this->capability, $this->page_slug, array( &$this, 'display_page' ) );
			
			// register javascript(s) if set
			if( ! empty( self::$_settings['js_scripts'] ) && is_array( self::$_settings['js_scripts'] ) ){
				self::register_js( self::$_settings['js_scripts'] );
			}
				
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
		public function create_setting( array $args = array() ) {
			if( null === $this->output )
				$this->output = $this->get_output();
					
			wp_parse_args( $args, $this->output->settings_fields_defaults );
			
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
			if( null === $this->output )
				$this->output = $this->get_output();
	
			$args = new stdClass();
			$args->options_group = $this->options_group;
			$args->page_slug = $this->page_slug;
			$args->description = $this->description;
			$args->page_title = $this->page_title;
			$args->icon = $this->icon;
			
			$this->output->display_page( $args );
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
		 * Outputs the HTML for every single setting field
		 * @param array $args
		 * @return none
		 * @uses esc_textarea (since WP 3.1)
		 * @uses esc_html
		 * @since 0.1
		 * @access public
		 */
		public function display_settings_field( array $args = array() ) {
			// get outpot-object
			if( null === $this->output )
				$this->output = $this->get_output();
	
			// get options from db
			$this->options = $this->get_options( $this->options_name );	
			
			// extracting vars for display in $output
			$whitelist_vars = $this->output->settings_fields_defaults; //array( 'id', 'type', 'std', 'class', 'desc' );
			$args = wp_parse_args( $args, $whitelist_vars );
	
			$copy_vars = array( 'id', 'type', 'std', 'class' );
			
			foreach( $copy_vars as $cv ){
				if( isset( $args[$cv] ) )
					$$cv = $args[$cv];
			}
			
			// set standard for multi checkbox
			if( ( isset( $std ) && is_array( $std ) ) && 
				( isset( $type) && ( 'mcheckbox' == $type || 'mselect' == $type) ) &&
				! isset( $this->options[$id] ) ) {
	
				 	foreach( $std as $key ) {
						if( ! isset( $this->options[$id . '-' . $key] ) )
							$this->options[$id . '-' . $key] = 'on';
					}
			 }
	
			// set standard for all other
			if( ! isset( $this->options[$id] ) && isset( $std ) )
				$this->options[$id] = $std;
	
			// set css class
			if( ! empty( $class ) )
				$args->class = ' class="' . $class . '"';
	
			// copy options to output-object
			$this->output->set_options( $this->options );
	
			// convert args to object (output-object need args as object)
			// and add some vars
			$args = (object) $args;
			$args->options_name = $this->options_name;
	
			// display setting field
			call_user_func( array( $this->output, $type ), $args );
			
			// reset vars
			foreach( $args as $key => $value ){
				if( key_exists( $key, $whitelist_vars ) )
					unset( $this->output->$key );
			}
			
		}

		/**
		 * 
		 * Register JavaScript(s) for the optionspage
		 * If this method is called before the optionspage was added, than the javascripts only will be registered
		 * If the JavaScripts are already registered, than they will be enqueued
		 * @param array $scripts Array with (string) tag, (string) source, (array) dependencies, (string) version, (bool) in_footer
		 * @return bool true|false|number of registered scripts
		 * @since 0.6
		 * @access public static
		 */
		public static function register_js( array $scripts = null ){
			// no $scripts, no action
			if( empty( $scripts ) )
				return false;

			// just set js_scripts, optionspage wasn't added yet. e.g. for external calls
			if( empty( self::$_settings['admin_page'] ) ){
				self::$_settings['js_scripts'] = $scripts;	
				return sizeof( self::$_settings['js_scripts'] );
			}
			
			//optionspage was already added. set js_scripts if it isn't set yet
			if( empty( self::$_settings['js_scripts'] ) )
				self::$_settings['js_scripts'] = $scripts;
			
			// optionspage was added, js_src was set, add actionhook
			add_action( 'load-' . self::$_settings['admin_page'], array( __CLASS__, 'enqueue_scripts' ) );
			
			return true;
		}

		/**
		 * 
		 * Enqueue Scripts
		 * Enqueue registered JavaScripts
		 * @param none (use in $_settings stored sources)
		 * @return void
		 * @since 0.6
		 * @access public static
		 */
		public static function enqueue_scripts(){
			// no scripts, no action
			if( empty( self::$_settings['js_scripts'] ) || empty( self::$_settings['page_slug'] ))
				return false;
				
			// use the page_slug as part of the tag if no tag was set
			$name = self::$_settings['page_slug'];

			foreach( self::$_settings['js_scripts'] as $tag => $values ){
				// no tag was set
				if( ! is_string( $tag ) )
					$tag = $name . '-' . $tag;
					
				// the simplest way, $values is just a string. make $values an array
				if( ! is_array( $values ) ){
					$values = array( 'src' => $values );

				}
				
				$defaults = array(	'src' 			=> false,
									'dependencies' 	=> array(),
									'version'		=> false,
									'in_footer'		=> true
							);
				$values = wp_parse_args( $values, $defaults );
				
				if( ! is_array( $values['dependencies'] ) )
					$values['dependencies'] = (array) $values['dependencies'];
				
				// maybe no source was set. but don't care about if $src exists or is readable!!!
				if( ! $values['src'] )
					continue;
					
				wp_enqueue_script( $tag, $values['src'], $values['dependencies'], $values['version'], $values['in_footer'] );
			}
			
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

	/* --------------- utilities --------------- */
		/**
		 * 
		 * Check values of checkboxes after sending data via form
		 * If a checkbox-value s not set, the checkbox was't selected.
		 * In ths case the checkbox-value will be created and set to 0 (zero)
		 * @param array $settings_fields Array with settings for fields (from $settings)
		 * @param array $input Array with values send via POST
		 * @return array $input Modified array
		 * @since 0.5.1
		 * @access public static
		 */
		public static function check_checkboxes( array $settings_fields, array $input ){		
			foreach( $settings_fields as $field ){
				if( 'checkbox' === $field['type'] ){
					if( ! isset( $input[ $field['id'] ] ) )
						$input[ $field['id'] ] = 0;	
				}
			}
		
			return $input;
		}
	} // end_class_Easy_Settings_API_Class
}