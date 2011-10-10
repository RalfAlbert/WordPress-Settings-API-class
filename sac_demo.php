<?php
/**
 * @package WordPress
 * @subpackage Settings API Class
 * @author Ralf Albert
 * @version 0.2.0
 *
 */

/**
 * Plugin Name: Settings-API Class (Demo)
 * Plugin URI: http://yoda.neun12.de/
 * Text Domain: SAC_DEMO
 * Domain Path: /languages
 * Description: Demo for the Settings-API Class (SAC). This plugin create a simple option page to show all available setting fields
 * Author: Ralf Albert
 * Version: 0.5.0
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
		private static $plugin_self = null;
		
		const OPTIONS_NAME = 'SAC_DEMO_SETTINGS';
		const OPTIONS_GROUP = 'SAC_DEMO';

		public static function plugin_start()
		{
			if( null === self::$plugin_self )
				self::$plugin_self = new self;
				
			return self::$plugin_self;
		} 
		
		public function deactivation()
		{
			delete_option( self::OPTIONS_NAME );
		}
		
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

				'sections'			 => array(
					'general'	 => __('General Settings'),
					'multi' 	 => __('Multiple Choice')
				),

				'section_desc'		 => array(
					'general'	 => __('Description for general settings (optional).'),
					'multi'		 => __('More than one choice are available.'),
				),

				'settings_fields'	 => array(
					array(
						'id'		 => 'demo_heading',
						'title'		 => 'Heading',
						'desc'		 => __('Headings only use the title and description as parameter.'),
						'type'		 => 'heading',
						'section'	 => 'general',
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

				)
			);

			// start the class
			$optionpage = new Easy_Settings_API( $this->settings );

			// optional way to initialize and start the class
			// $optionpage->set_settings( $settings );
			// $optionpage->create_option_page();			

		}
		
		public static function validate_input( $input )
		{
//			var_dump($_POST);
//			var_dump( $input );
//			wp_die( var_dump( $input ) );

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
			
			
			// check every settings field if there is a checkbox
			// if one is found, check if it was not selected (if there is no value set)
			// set values to 0 for all unselected checkboxes
			foreach( $this->settings['settings_fields'] as $field ){
				if( 'checkbox' === $field['type'] ){
					if( ! isset( $input[ $field['id'] ] ) )
						$input[ $field['id'] ] = 0;	
				}
			}
			
			
				
			return $input;
		}
		
	}
}
