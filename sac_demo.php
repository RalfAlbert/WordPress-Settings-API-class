<?php
/**
 * @package WordPress
 * @subpackage Settings API Class
 * @author Ralf Albert
 * @version 0.3.0
 *
 */

/**
 * Plugin Name: Settings-API Class (Demo)
 * Plugin URI: http://yoda.neun12.de/
 * Text Domain: SAC_DEMO
 * Domain Path: /languages
 * Description: Demo for the Settings-API Class (SAC). This plugin create a simple option page to show all available setting fields
 * Author: Ralf Albert
 * Version: 0.6.0
 * Author URI: http://neun12.de/
 * Licence: GPL
 */

if( ! class_exists( 'Easy_Settings_API' ) )
	require_once dirname( __FILE__ ) . '/class-easy_settings_api.php';

if( ! class_exists( 'Easy_Settings_API_Class_Demo' ) && function_exists( 'add_action' ) ) 
{
	add_action( 'plugins_loaded', array( 'Easy_Settings_API_Class_Demo', 'plugin_start' ) );

	class Easy_Settings_API_Class_Demo
	{
		/**
		 * 
		 * Instance of this class. Will be used in validate_input()
		 * @var object $plugin_self
		 */
		private static $plugin_self = null;
		
		/**
		 * 
		 * Instance of Easy_Settings_API. Will be used in validate_input()
		 * @var object $options_page
		 */
		public static $options_page = null;
		
		/**
		 * 
		 * Name of the options in database
		 * @var const string
		 */
		const OPTIONS_NAME = 'SAC_DEMO_SETTINGS';
		
		/**
		 * 
		 * Name of the options-group
		 * @var const string
		 */
		const OPTIONS_GROUP = 'SAC_DEMO';

		/**
		 * 
		 * Initialize the plugin and save an instance of the class
		 * @param none
		 * @return object $plugin_self
		 * @since 0.3
		 * @access public
		 */
		public static function plugin_start()
		{
			if( null === self::$plugin_self )
				self::$plugin_self = new self;
				
			return self::$plugin_self;
		} 
		
		/**
		 * 
		 * Will be called when the plugin is deactivated.
		 * The options are removed from database
		 * @param none
		 * @return void
		 * @since 0.1
		 * @access public
		 */
		public function deactivation()
		{
			delete_option( self::OPTIONS_NAME );
		}
		
		/**
		 * 
		 * Constructor
		 * @@param none
		 * @return void
		 * @since 0.1
		 * @access public
		 * @return object
		 */
		public function __construct()
		{
			if( null !== self::$plugin_self )
				return self::$plugin_self;
			
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );
			
			$this->settings = array(
				'options_group'		 => self::OPTIONS_GROUP,
				'options_name'		 => self::OPTIONS_NAME,
				'validate_callback'	 => array( __CLASS__, 'validate_input' ),   //'Settings_API_Class_Demo::validate_input',

				'menu_position'		 => 'options',
				'page_slug'			 => 'sac_demopage',
				'page_title'		 => 'Settings API Class Demo Page',
				'menu_title'		 => 'SAC Demopage',
				'description'		 => 'This is a demo page for the Settings API Class.',
				'capability'		 => 'manage_options',
				'icon'				 => 'icon-options-general',

				/*
				 * Enqueue JavaScript
				 * 
				 * Simple entry: tag => source (script will be enqueued with no dependencies, in head)
				 * 
				 * Entry with params: tag => array( params )
				 * 
				 * Params are:
				 *  - src: full relative path to file
				 *  - dependencies: dependencies as array 
				 *  - version: js version
				 *  - in_footer: load script in footer (true) or head (false)
				 */	
				'js_scripts' => array(
										'sac-demo2-js' => plugins_url( 'js/alert.js', __FILE__ ),
										
										'sac-demo-js' => array( 
														'src' 			=> plugins_url('/js/demo_js.js', __FILE__ ),
														'dependencies' 	=> array( 'jquery' ),
														'version'		=> false,
														'in_footer'		=> true
													),
													
									// some other javascripts
							),
			
				/*
				 * Sections
				 */
				'sections'			 => array(
					'general'	 => __('General Settings'),
					'multi' 	 => __('Multiple Choice')
				),

				/*
				 * Section descriptions
				 */
				'section_desc'		 => array(
					'general'	 => __('Description for general settings (optional).'),
					'multi'		 => __('More than one choice are available.'),
				),

				/*
				 * Settings fields
				 * 
				 * Each field can define some params. Minimum are 'id', 'type' and 'title'
				 */
				'settings_fields'	 => array(
					array(
						'id'		 => 'demo_heading',
						'title'		 => 'Heading',
						'desc'		 => __('Headings only use the title and description as parameter.'),
						'type'		 => 'heading',
						'section'	 => 'general',
					),
				
					array(
						'id'		=> 'demo_custom',
						'title'		=> 'Custom',
						'desc'		=> 'Custum is using a callbackfunction to display the input',
						'type'		=> 'custom',
						'callback'	=> array( __CLASS__, 'custom_callback' ),
						// each single array-element is passed as single argument to the
						// callback-function.
						// all keys in an associativ array will be lost.
						// if an array should passed as argument to the callback-function,
						// it must be itself an array.
						'arguments'	=> array( 'one' => 'eins', array( 'two' => 'zwei' ) ),
						'section'	=> 'general'
					),
					
					array(
						'id'		 => 'demo_checkbox',
						'title'		 => __('Checkbox'),
						'desc'		 => __('The description of the checkbox'),
						'text_after' => __('Text after the checkbox. This text is formated as <code>label</code>.'),
						'std'		 => 'on', // values are 'on' or '' (empty). Everything else than 'on' is equal to an empty value
						'type'		 => 'checkbox',
						'section'	 => 'general',
					),
					
					array(
						'id'		 => 'demo_textinput',
						'title'		 => __('Text input'),
						'desc'		 => __('The description of the text input'),
						'text_after' => __('Text after the input-field'),
						'std'		 => 'demo text',
						'size'		 => 30,
						'type'		 => 'text',
						'section'	 => 'general',
					),
					
					array(
						'id'		 => 'demo_password',
						'title'		 => __('Password'),
						'desc'		 => __('You can even preselect a standard password'),
						'text_after' => __('Text after the password-field'),
						'std'		 => 'password',
						'size'		 => 30,
						'type'		 => 'password',
						'section'	 => 'general',
					),
					
					array(
						'id'		 => 'demo_texarea',
						'title'		 => __('Textarea'),
						'desc'		 => __('The description of the textarea'),
						'text_after' => __('Text after the textarea'),
						'std'		 => 'Textareas are good for longer inputs. You can select the width and height of the textarea with the rows- and cols-parameter.',
						'rows'		 => 3,
						'cols'		 => 30,
						'type'		 => 'textarea',
						'section'	 => 'general',
					),
					
					array(
						'id'		 => 'demo_radio',
						'title'		 => __('Radio'),
						'desc'		 => __('The description of the radio'),
						'choices'	 => array(
								'yes'	=> 'Yes',
								'no'	=> 'No',
								'maybe'	=> 'Maybe'
						),
						'std'		 => 'yes',
						'type'		 => 'radio',
						'section'	 => 'multi',
					),
					
					array(
						'id'		 => 'demo_select',
						'title'		 => __('Select'),
						'desc'		 => __('The description of select'),
						'choices'	 => array(
								'' => 'Please select',
								'yes'	 => 'Yes',
								'no'	 => 'No',
								'maybe'	 => 'Maybe'
						),
						'std'		 => '',
						'type'		 => 'select',
						'section'	 => 'multi',
					),
					
					array(
						'id'		 => 'demo_multicheckbox',
						'title'		 => __('Multi checkbox'),
						'desc'		 => __('The description of multi checkbox'),
						'choices'	 => array( 
							'yes'	=> 'Yes',
							'no'	=> 'No',
							'maybe'	=> 'Maybe'
						),
						'std'		 => array( 'no', 'maybe' ),
						'type'		 => 'mcheckbox',
						'section'	 => 'multi',
					),

					array(
						'id'		 => 'demo_mselect',
						'title'		 => __('Multi-Select'),
						'desc'		 => __('The description of multi-select'),
						'choices'	 => array(
								'' => 'Please select',
								'yes'	 => 'Yes',
								'no'	 => 'No',
								'maybe'	 => 'Maybe'
						),
						'std'		 => array( 'yes', 'no' ),
						'size'		 => 0,
						'type'		 => 'mselect',
						'section'	 => 'multi',
					),

				), // end_settings-fields
				
			);

			// create the options-page
			self::$options_page = new Easy_Settings_API( $this->settings );

			// optional way to initialize and start the class
			// $optionpage->set_settings( $settings );
			// $optionpage->create_option_page();			

		}
		
		/**
		 * 
		 * Demo for settings-type 'custom'
		 * @param mixed $arg_one
		 * @param array $arg_two
		 * @return void
		 * @since 0.5
		 * @access public static
		 */
		public static function custom_callback( $arg_one, array $arg_two )
		{
			var_dump( $arg_one );
			var_dump( $arg_two );
		}
		
		/**
		 * 
		 * Validate the data send by formular
		 * @param array $input Data send by formular
		 * @return array $input Validated data
		 * @since 0.1
		 * @access public static
		 */
		public static function validate_input( $input )
		{
			/*
			 * Checkboxes send no value if they are NOT selected. This means, the array-value
			 * for the checkbox is not set. In this case, WordPress delete the option for the checkbox
			 * in the database.
			 * If there is no option set in database, but a standard-value is set, the class will
			 * take the standard-value as option for the checkbox.
			 * In short: If there is no option-value, the standard-value will be used
			 * 
			 * For unselected checkboxes we have to set a value of 0|false|mettigel or something
			 * else than 'on'.
			 * 
			 * If the checkbox IS selected, there will be a array-value 'on' and we do not have
			 * to care about the rest.
			 * 
			 */
			
			$settings_fields = self::$plugin_self->settings['settings_fields'];
			$input = call_user_func_array( array( self::$options_page, 'check_checkboxes' ), array( $settings_fields, $input ) );

			return $input;
		}
		
	}
}
