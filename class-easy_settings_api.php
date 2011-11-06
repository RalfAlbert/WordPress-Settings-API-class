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
		 * Optionspage configuration
		 * @var object $config
		 */
		protected static $config = null;
		
		/**
		 * 
		 * Defaults for option-fields, retrieve by output-class
		 * @var object $fields_defaults
		 */
		protected $fields_defaults = null;
						
		/**
		 * 
		 * Errors
		 * @var string
		 */
		private $errors = array();
			
		/**
		 *
		 * Constructor
		 * Starts the class automatically if $settings is given
		 * @param array $settings
		 * @return none
		 * @since 0.1
		 * @access public
		 */
		public function __construct( $outputclass = '' ) {
			if( '' != $outputclass )
				$this->set_output( $outputclass );			
			
			self::$config			= new stdClass();
			self::$config->basic	= new stdClass();
			self::$config->scripts	= new stdClass();
			self::$config->styles	= new stdClass();
			self::$config->sections	= new stdClass();			
	
		}

		
/* -------------------------------------------------------------------------- */
/* class configuration ------------------------------------------------------ */
/* -------------------------------------------------------------------------- */
		public function basic_config( $config = null, $parent = '' ){
				// maybe only __FILE__ was set
				if( '' == $parent && null != $config && 
					( file_exists( $config) && is_readable( $config ) )
				){
					$config = new stdClass();
					$parent = $config;
				}
					
				if( null === $config || ! is_object( $config ) )
					return false;
						
				$basic_config = new stdClass();
				$basic_config->options_group 		= '';
				$basic_config->options_name			= '';
				$basic_config->validate_callback	= '';
	
				$basic_config->menu_position	= 'options';
				$basic_config->page_slug		= 'error_in_esac';
				$basic_config->page_title		= 'Error';
				$basic_config->menu_title		= 'Optionspage error';
				$basic_config->description		= 'There was some errors in setting up the optionspage';
				$basic_config->capability		= 'manage_options';
				$basic_config->icon				= 'icon-options-general';					
				
				// get data from plugin-header & merge with basic_config
				if( '' != $parent && is_admin() ){
					
					if( file_exists( $parent ) && is_readable( $parent ) ){
						
						$file_headers = $this->get_file_headers( $parent );
						
						if( $file_headers )
							$basic_config = self::parse_args( $basic_config, $file_headers );
					}
				}
				
				// merge base_config with config
				$config = self::parse_args( $config, $basic_config );

				// validate where the option-page should appear 
				$whitelist_where = array(
					'dashboard', 'posts', 'media', 'links',
					'pages', 'comments', 'theme', 'plugins',
					'users', 'options', 'management', 'menu'
				);
		
				$config->menu_position = strtolower( $config->menu_position );
				
				if ( ! in_array( $config->menu_position, $whitelist_where ) )
					$config->menu_position = 'options';
				
				// validate capability
				//TODO: validate capability 
				 
				// merge internal config with base_config
				self::$config->basic = self::parse_args( self::$config->basic, $config );			
					
				// check for empty values. this values can't be empty
				$not_empty = array( 'page_slug', 'options_group', 'options_name', 'menu_title', 'page_title' );
				$val_error = array();
				
				foreach( $not_empty as $key ){
					if( ! isset( self::$config->basic->$key ) || null === self::$config->basic->$key )
						array_push( $val_error, $key );
				}
				if( ! empty( $val_error ) && is_admin() ){
					$msg = __('<h4>Error in class <b>Easy Settings-API</b></h4><br />The following value(s) have to be set in configuration array:');
					$msg .= '<ol>';
					foreach( $val_error as $e )
						$msg .= "<li>{$e}</li>";
					$msg .= '</ol>';
					
					$this->add_error( $msg );
				}					
		}
		
		/**
		 * 
		 * Enter description here ...
		 * @param object $scripts
		 * @return void
		 */
		public function add_script( $scripts = null ){
			if( null === $scripts || ! is_object( $scripts ) )
				return false;
	
			$defaults = new stdClass();
			$defaults->src			= '';
			$defaults->dependencies	= false;
			$defaults->version		= false;
			$defaults->in_footer	= false;
			
			foreach( $scripts as &$script ){
				// handles strings (only path to source was set)
				if( ! is_object( $script ) ){
					$src = $script;
					$script = new stdClass();
					$script->src = $src;
				}
	
				$script = self::parse_args( $script, $defaults, true );
				
				// make sure dependencies is an array
				if( ! is_array( $script->dependencies ) )
					$script->dependencies = (array) $script->dependencies;
			}	
	
			self::$config->scripts = self::parse_args( self::$config->scripts, $scripts );
		}
		
		public function add_style( $styles = null ){
			if( null === $styles )
				return false;
				
			$defaults = new stdClass();
			$defaults->src	= '';
			$defaults->deps	= false;
			
			foreach( $styles as &$style ){
				// handles strings (only path to source was set)
				if( ! is_object( $style ) ){
					$src = $style;
					$style = new stdClass();
					$style->src = $src;
				}
	
				$style = self::parse_args( $style, $defaults, true );
			}	
						
			self::$config->styles = self::parse_args( self::$config->styles, $styles );
		}
		
		public function add_section( $sections = null ){
			if( null === $sections )
				return false;
				
			$defaults = new stdClass();
			$defaults->title		= 'Default';
			$defaults->description	= 'Default section';
			$defaults->fields		= array();
			
			foreach( $sections as &$section ){
				$section = self::parse_args( $section, $defaults, true );
			}	
						
			self::$config->sections = self::parse_args( self::$config->sections, $sections );
		}
		
		public function add_field( $fields = null, $section = '' ){
			if( null === $fields )
				return false;
				
			$defaults = new stdClass();
			$defaults->id		= 'default_id';
			$defaults->type		= 'heading';
			$defaults->title	= 'Default';
			$defaults->section	= 'default';
			
			if( '' != $section )
				$defaults->section = $section;
				
			// create a default section to catch all misguided fields
			if( ! isset( self::$config->sections->default ) ){
				$def = new stdClass();
				$def->default->title = 'Nirvana';
				$def->default->description = 'Default section to catch misguided fields';
				$this->add_section( $def );
			}
								
			// get the possible settings for a setting-field from the output-class
			if( ! isset( $this->fields_defaults ) )
				$this->get_fields_defaults();
				
			if( ! empty( $this->fields_defaults ) )
				$defaults = self::parse_args( $defaults, $this->fields_defaults );
			else
				$this->add_error( 'Can\t get field defaults from output class (add_field).');
			
			// copy fields to sections
			foreach( $fields as &$field ){
				$field = self::parse_args( $field, $defaults );
				
				$target_section = $field->section;
				if( isset( self::$config->sections->$target_section ) ){
					array_push( self::$config->sections->$target_section->fields, $field );
					
					// log an error if no suitable section found for this field
					if( 'default' == $target_section )
						$this->add_error( sprintf( __('No section found for field <em>%s</em>. Field assigned to default section'), $field->id ) );
				}
				else {
					// log errors for totally fuzzy fields
					$this->add_error( sprintf( __('No section found for field <em>%s</em>'), $field->id ) );
				}
			}
			
			// remove default section if no field was misguided
			if( empty( self::$config->sections->default->fields ) )
				unset( self::$config->sections->default );
		}
		
			
/* -------------------------------------------------------------------------- */
		
		/**
		 * 
		 * Adding an error message to the internal error-handling
		 * @param string $msg
		 * @return void
		 * @since 0.6.1
		 * @access protected
		 */
		protected function add_error( $msg = '' ){
			$this->errors[] = $msg;	
		}
		
		/**
		 * 
		 * Show errors as admin notice
		 * Hooked in via 'admin_notices'
		 * @param none
		 * @return void
		 * @since 0.6.1
		 * @access public
		 */
		public function show_errors(){
			if( '' != $this->errors ){
				echo '<div class="error"><ol>';
				foreach( $this->errors as $error )
					echo "<li>{$error}</li>";
				echo '</ol></div>';
				
				// remove actions to avoid displaying a broken options-page
				remove_action( 'admin_menu', array( &$this, 'add_page' ) );
				remove_action( 'admin_init', array( &$this, 'register_settings' ) );		
			}
		}
				
		/**
		 *
		 * Adding the page to the menu and register the settings. Displays errors if any are encountered
		 * @param none
		 * @return none
		 * @uses add_action()
		 * @since 0.1
		 * @access public
		 */
		public function create_optionspage(){
			// show errors
			add_action( 'admin_notices', array( &$this, 'show_errors' ) );
			
			// add page to admin-menu and register settings
			add_action( 'admin_menu', array( &$this, 'add_page' ) );
			add_action( 'admin_init', array( &$this, 'register_settings' ) );			
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
			if( '' == $options_name )
				$options_name = self::$config->basic->options_name;
				
			if( empty( $this->options ) )
				$this->options = get_option( self::$config->basic->options_name );
			
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
		protected function set_output( $outputclass = '' ){
			if( '' != $outputclass )
				$this->output_class = $outputclass;
			
			// create the output-object
			if( null === $this->output ){
				// setup the output-class
				// if no output-class is defined, use the standard output-class
				if( empty( $this->output_class ) )
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
		 * Getter for config
		 * @param none
		 * @return object $config
		 * @since 0.7.0
		 * @access public
		 */
		public function get_config() {
			return self::$config;
		}
		
		/**
		 * 
		 * Get fields defaults from output-class
		 * @param none
		 * @return object $fields_defaults
		 * @since 0.7.0
		 * @access public
		 */
		public function get_fields_defaults(){
			if( ! isset( $this->output ) )
				$this->get_output();
				
			$this->fields_defaults = $this->output->get_fields_defaults();
				
			return $this->fields_defaults;
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
			$c = self::$config->basic;
			
			$where = 'add_' . $c->menu_position . '_page';
			$c->admin_page = $where( $c->page_title, $c->menu_title, $c->capability, $c->page_slug, array( &$this, 'display_page' ) );


			// register javascript(s) if set
			if( ! empty( self::$config->scripts ) )
				add_action( 'load-' . $c->admin_page, array( __CLASS__, 'enqueue_scripts' ) );				
			
			// hook up custom stylesheets
			if( ! empty( self::$config->styles ) )
				add_action( 'admin_print_styles-' . $c->admin_page, array( &$this, 'enqueue_styles' ) );
				
			// copy back modified config (copy admin_page to config->basic)
			self::$config->basic = $c;
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
			$c = self::$config->basic;
		
			register_setting( $c->options_group, $c->options_name, $c->validate_callback );
	
			foreach( self::$config->sections as $section => $data ) {
				add_settings_section( $section, $data->title, array( &$this, 'display_section' ), $c->page_slug );
				
				foreach ( $data->fields as $field ) {
					$this->create_setting( $field );
				}	
			}
	
		}	

		/**
		 *
		 * Create a settings field with given arguments
		 * @param object $args
		 * @return none
		 * @uses add_settings_field()
		 * @since 0.1
		 * @access public
		 */
		public function create_setting( $args = null ) {
			if( null === $args )
				return false;
			
			$c = self::$config->basic;
			
			if( ! isset( $this->fields_defaults ) )
				$this->get_fields_defaults();
				
			if( ! empty( $this->fields_defaults ) )	
				$args = self::parse_args( $args, $this->fields_defaults );
			else
				$this->add_error( sprintf( "Can't get field defaults from output class. (%s)", __METHOD__ ) );
						
			// copy the 'id' to 'label_for'
			$args->label_for = $args->id;
	
			add_settings_field( $args->id, $args->title, array( &$this, 'display_settings_field' ), $c->page_slug, $args->section, (array) $args );
			
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
			if( ! isset( $this->output ) )
				$this->get_output();
	
			$c = self::$config->basic;
			
			$args = new stdClass();
			$args->options_group = $c->options_group;
			$args->page_slug 	 = $c->page_slug;
			$args->description 	 = $c->description;
			$args->page_title 	 = $c->page_title;
			$args->icon 		 = $c->icon;
			
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
		public function display_section( $section ) {
			$sec = $section['id'];
			if( isset( self::$config->sections->$sec->description ) )
				echo self::$config->sections->$sec->description;
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
		public function display_settings_field( $args = null ) {
			if( null === $args )
				return false;
				
			// get options from db
			if( empty( $this->options ) )
				$this->get_options();	
			
			// extracting vars for display in $output
			if( ! isset( $this->fields_defaults ) )
				$this->get_fields_defaults();
				
			$args = self::parse_args( $args, $this->fields_defaults );
	
			$copy_vars = array( 'id', 'type', 'std', 'class' );
			
			foreach( $copy_vars as $cv ){
				if( isset( $args->$cv ) )
					$$cv = $args->$cv;
			}
			
			// set standard for multi-checkbox
			if( ( isset( $std ) && is_array( $std ) ) && 
				( isset( $type) && $type == 'mcheckbox' ) &&
				! isset( $this->options[$id] ) ) {
	
				 	foreach( $std as $key ) {
						if( ! isset( $this->options[$id . '-' . $key] ) )
							$this->options[$id . '-' . $key] = 'on';
					}
			 }

			// set standard for multi-select
			if( ( isset( $std ) && is_array( $std ) ) && 
				( isset( $type) && $type == 'mselect' ) &&
				! isset( $this->options[$id] ) ) {
	
					$this->options[$id] = $std;
			 }

			 // set standard for all other
			if( ! isset( $this->options[$id] ) && isset( $std ) )
				$this->options[$id] = $std;
	
			// set css class
			if( ! empty( $class ) )
				$args->class = 'class="' . $class . '"';
	
			// options_name is needed for ceckboxes, radio & select
			$args->options_name = self::$config->basic->options_name;
	
			// copy options to output-object
			$this->output->set_options( $this->options );

			// display setting field
			call_user_func( array( $this->output, $type ), $args );			
		}
		

/* -------------------------------------------------------------------------- */
/* --- scripts & styles --------------------------------------------------- */
/* -------------------------------------------------------------------------- */
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
			if( empty( self::$config->scripts ) || empty( self::$config->basic->page_slug ))
				return false;
				
			// use the page_slug as part of the tag if no tag was set
			$slug = self::$config->basic->page_slug;

			foreach( self::$config->scripts as $tag => $values ){
				// no tag was set (tag == integer)
				if( ! is_string( $tag ) )
					$tag = $slug.'_'.$tag;
				
				// maybe no source was set. but don't care about if $src exists or is readable!!!
				if( empty( $values['src'] ) )
					continue;
					
				wp_enqueue_script( $tag, $values['src'], $values['dependencies'], $values['version'], $values['in_footer'] );
			}
			
		}
		
		/**
		 * 
		 * Enqueue stylesheets
		 * Enqueue stylesheets, hooked by admin_print_styles-
		 * @param none
		 * @return void
		 * @since 0.6.2
		 * @access public
		 */
		public function enqueue_styles(){
			if( ! empty( self::$config->styles ) ){
				$styles = self::$config->styles;
									
				foreach( $styles as $tag => $data ){
					wp_enqueue_style( $tag, $data->src, $data->deps, false, 'all' );
				}
				
			}
		}
		
/* -------------------------------------------------------------------------- */
/* --- internal functions --------------------------------------------------- */
/* -------------------------------------------------------------------------- */
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
		protected static function parse_args( $input = null, $defaults = null, $cleaning = false ){
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
		
		/**
		 * 
		 * Reading plugin-headers and copy to object defaults
		 * @param string $file Path to plugin (with headers)
		 * @return object $defaults If fileheaders was found, false if file does not exists (or is not readable)
		 * @since 0.7.0
		 * @uses get_plugin_data()
		 */
		protected function get_file_headers( $file ){
			if( ! file_exists( $file ) || ! is_readable( $file ) )
				return false;				
			
			// must be included for function get_plugin_data
			if( ! function_exists( 'get_plugin_data' ) )
				require_once ABSPATH.'wp-admin/includes/plugin.php';
				
			$data = get_plugin_data( $file );
				
			$name = isset( $data['Name'] ) ? $data['Name'] : 'Default Plugin';
			$desc = isset( $data['Description'] ) ? $data['Description'] : 'Default description';
				
			$slug = strtolower( esc_attr( str_replace( ' ', '_', $name ) ) );
			
			$defaults = new stdClass();
			$defaults->page_slug 		= $slug;
			$defaults->options_group	= $slug . '_optionsgroup';
			$defaults->options_name		= $slug . '_options';
			$defaults->page_title	 	= $name;
			$defaults->menu_title	 	= $name;
			$defaults->description	 	= $desc;
			
			return $defaults;				
		}
		

/* -------------------------------------------------------------------------- */
/* --- utilities ------------------------------------------------------------ */
/* -------------------------------------------------------------------------- */
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
		public static function check_checkboxes( array $fields, array $input ){		
			foreach( $fields as $field ){
				if( 'checkbox' === $field['type'] ){
					if( ! isset( $input[ $field['id'] ] ) )
						$input[ $field['id'] ] = 0;	
				}
			}
		
			return $input;
		}
	} // end_class_Easy_Settings_API_Class
}