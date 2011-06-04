<?php
/**
 * @package WordPress
 * @subpackage Settings API Class
 * @author Ralf Albert
 * @version 0.1.0
 *
 */

/**
 * Plugin Name: Settings-API Class (Demo)
 * Plugin URI: http://yoda.neun12.de/
 * Text Domain: SAC_DEMO
 * Domain Path: /languages
 * Description: Demo for the Settings-API Class (SAC). This plugin create a simple option page to show all available setting fields
 * Author: Ralf Albert
 * Version: 0.2.0
 * Author URI: http://neun12.de/
 * Licence: GPL
 */

if( !class_exists( 'Settings_API_Class' ) )
	require_once dirname( __FILE__ ) . '/class-settings_api.php';

if( !class_exists( 'Settings_API_Class_Demo' ) && function_exists( 'add_action' ) ) 
{
	add_action( 'plugins_loaded', create_function( NULL, '$a = new Settings_API_Class_Demo();' ) );

	class Settings_API_Class_Demo
	{
		const OPTIONS_NAME = 'SAC_DEMO_SETTINGS';
		const OPTIONS_GROUP = 'SAC_DEMO';

		public function deactivation()
		{
			delete_option( self::OPTIONS_NAME );
		}
		
		public function __construct()
		{
			register_deactivation_hook( __FILE__, array( &$this, 'deactivation' ) );
			
			$settings = array(
				'options_group'		 => self::OPTIONS_GROUP,
				'options_name'		 => self::OPTIONS_NAME,
				'validate_callback'	 => 'Settings_API_Class_Demo::validate_input',

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

				'fields'			 => array(
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
				)
			);

			// start the class
			$optionpage = new Settings_API_Class( $settings );

			// optional way to initialize and start the class
			// $optionpage->set_settings( $settings );
			// $optionpage->init();			

		}
		
		public static function validate_input( $input )
		{
			//wp_die( var_dump( $input ) );
			return $input;
		}
		
	}
}
