<?php
/**
 * @package WordPress
 * @subpackage Easy Settings API Class
 * @author Ralf Albert
 * @version 0.6.2
 *
 */

/**
 * Plugin Name: Easy Settings-API Class (Demo)
 * Plugin URI: http://yoda.neun12.de/
 * Text Domain: ESAC_DEMO
 * Domain Path: /languages
 * Description: Demo for the the Easy Settings-API Class (ESAC). This plugin create a simple option page to show all available setting fields
 * Author: Ralf Albert
 * Version: 0.6.2
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
			
			$optionspage = new Easy_Settings_API();
			
			$base_config = new stdClass();
			$base_config->options_group 	= self::OPTIONS_GROUP;
			$base_config->options_name		= self::OPTIONS_NAME;
			$base_config->validate_callback	= array( __CLASS__, 'validate_input' );   //'Settings_API_Class_Demo::validate_input',

			$base_config->menu_position		= 'options';
			$base_config->page_slug			= 'sac_demopage';
			$base_config->page_title		= 'Settings API Class Demo Page';
			$base_config->menu_title		= 'SAC Demopage';
			$base_config->description		= 'This is a demo page for the Settings API Class.';
			$base_config->capability		= 'manage_options';
			$base_config->icon				= 'icon-options-general';
			
$optionspage->basic_config( $base_config, __FILE__ );

			
			/*
			 * JavaScript
			 * 
			 * tag = source 		:: simplest way (script will be enqueued with no dependencies, in head)
			 * 
			 * tag->src 			= string 	:: full relative path to file
			 * tag->dependencies	= array()	:: dependencies as array 
			 * tag->version			= string	:: js version
			 * tag->in_footer		= bool		:: load script in footer (true) or head (false)
			 */	

			$scripts = new stdClass();
			$scripts->sac_demo_js = plugins_url( 'js/demo_js.js', __FILE__ );
			
			$scripts->sac_demo1_js->src = plugins_url( 'js/alert.js', __FILE__ );
			
			$scripts->sac_demo2_js->src				= plugins_url('/js/demo_js.js', __FILE__ );
			$scripts->sac_demo2_js->dependencies	= array( 'jquery' );
			$scripts->sac_demo2_js->version			= false;
			$scripts->sac_demo2_js->in_footer		= true;


$optionspage->add_script( $scripts );


			/*
			 * Stylesheets
			 *
			 * tag->src = source
			 * tag->deps = dependencies
			 */	

			$styles = new stdClass();
			$styles->first_demo_style->src			= plugins_url( 'css/demostyle.css', __FILE__ );
			$styles->first_demo_style->deps = 'none';
			
			$styles->second_demo_style->src	= plugins_url( 'css/demostyle.css', __FILE__ );
			
			$styles->third_demo_style = plugins_url( 'css/demostyle.css', __FILE__ );

$optionspage->add_style( $styles );
			

			/*
			 * Sections
			 * 
			 * tag->title
			 * tag->description
			 */
			$sections= new stdClass();
			
			$sections->general->title 		= __('General Settings');
			$sections->general->description = __('Description for general settings (optional).');

			$sections->multi->title			= __('Multiple Choice');
			$sections->multi->description	= __('More than one choice are available.');
			
$optionspage->add_section( $sections );
					
			/*
			 * Settings fields
			 * 
			 * Each field can define some params. Minimum are 'id', 'type', 'title' and 'section'
			 * 
			 * tag->id
			 * tag->type
			 * tag->title
			 * tag->section
			 * tag->some_other_params
			 */
			
			/* fields for section 'general' */ 
			
			// heading field
			$field->heading = new stdClass();
			$field->heading->id			= 'demo_heading';
			$field->heading->type		= 'heading';
			$field->heading->title		= 'Heading';
			$field->heading->section	= 'general';
			$field->heading->description	= __('Headings only use the title and description as parameter.');
$optionspage->add_field( $field );
			
			// custom field
			$field->custom = new stdClass();
			$field->custom->id			= 'demo_custom';
			$field->custom->type		= 'custom';
			$field->custom->title		= 'Custom';
			$field->custom->sedction	= 'general';
			$field->custom->description	= 'Custum is using a callbackfunction to display the input';
			$field->custom->callback	= array( __CLASS__, 'custom_callback' );
			// each single array-element is passed as single argument to the
			// callback-function.
			// all keys in an associativ array will be lost.
			// if an array should passed as argument to the callback-function,
			// it must be itself an array.
			$field->custom->arguments	= array( 'one' => 'eins', array( 'two' => 'zwei' ) );
$optionspage->add_field( $field );
			

			// checkbox field
			$field->checkbox = new stdClass();
			$field->checkbox->id			= 'demo_checkbox';
			$field->checkbox->type			= 'checkbox';
			$field->checkbox->title			= __('Checkbox');
			$field->checkbox->section		= 'general';
			$field->checkbox->description 	= __('The description of the checkbox');
			$field->checkbox->text_after 	= __('Text after the checkbox. This text is formated as <code>label</code>.');
			$field->checkbox->std		 	= 'on'; // values are 'on' or '' (empty). Everything else than 'on' is equal to an empty value
$optionspage->add_field( $field );
			
			
			// adding more than one field at once
			$field = new stdClass();

			// input field
			$field->input->id		 	= 'demo_textinput';
			$field->input->title		= __('Text input');
			$field->input->description	= __('The description of the text input');
			$field->input->text_after 	= __('Text after the input-field');
			$field->input->std		 	= 'demo text';
			$field->input->size			= 30;
			$field->input->type			= 'text';
			
			// password field
			$field->password->id			= 'demo_password';
			$field->password->title			= __('Password');
			$field->password->description 	= __('You can even preselect a standard password');
			$field->password->text_after	= __('Text after the password-field');
			$field->password->std			= 'password';
			$field->password->size			= 30;
			$field->password->type			= 'password';
			
			// textarea field
			$filed->textarea->id			= 'demo_texarea';
			$filed->textarea->title			= __('Textarea');
			$filed->textarea->description	= __('The description of the textarea');
			$filed->textarea->text_after	= __('Text after the textarea');
			$filed->textarea->std			= 'Textareas are good for longer inputs. You can select the width and height of the textarea with the rows- and cols-parameter.';
			$filed->textarea->rows			= 3;
			$filed->textarea->cols			= 30;
			$filed->textarea->type			= 'textarea';
			$filed->textarea->section		= 'general';

$optionspage->add_field( $field );


			/* fields for section 'multi' (inputs with multiple choice) */
	
			$mfield = new stdClass();
					
			// radio field
			$mfields->radio->id				= 'demo_radio';
			$mfields->radio->title			= __('Radio');
			$mfields->radio->description	= __('The description of the radio');
			$mfields->radio->choices		= array(
												'yes'	=> 'Yes',
												'no'	=> 'No',
												'maybe'	=> 'Maybe'
												);
			$mfields->radio->std				= 'yes';
			$mfields->radio->type			= 'radio';
			//$mfields->radio->section		= 'multi';
			
			// select field
			$mfields->select->id			= 'demo_select';
			$mfields->select->title			= __('Select');
			$mfields->select->description	= __('The description of select');
			$mfields->select->choices		= array(
												'' => 'Please select',
												'yes'	 => 'Yes',
												'no'	 => 'No',
												'maybe'	 => 'Maybe'
												);
			$mfields->select->std			= '';
			$mfields->select->type			= 'select';
			//$mfields->select->section		= 'multi';
					
			// field multicheckbox
			$mfields->mcheckbox->id				= 'demo_multicheckbox';
			$mfields->mcheckbox->title			= __('Multi checkbox');
			$mfields->mcheckbox->description	= __('The description of multi checkbox');
			$mfields->mcheckbox->choices		= array( 
													'yes'	=> 'Yes',
													'no'	=> 'No',
													'maybe'	=> 'Maybe'
													);
			$mfields->mcheckbox->std			= array( 'no', 'maybe' );
			$mfields->mcheckbox->type			= 'mcheckbox';
			//$mfields->mcheckbox->section		= 'multi';

			// field multiselect
			$mfields->mselect->id			= 'demo_mselect';
			$mfields->mselect->title		= __('Multi-Select');
			$mfields->mselect->description	= __('The description of multi-select');
			$mfields->mselect->choices		= array(
												'' => 'Please select',
												'yes'	 => 'Yes',
												'no'	 => 'No',
												'maybe'	 => 'Maybe'
												);
			$mfields->mselect->std			= array( 'yes', 'no' );
			$mfields->mselect->size			= 0;
			$mfields->mselect->type			= 'mselect';
			//$mfields->mselect->section		= 'multi';

			// adding fields to section 'multi'
$optionspage->add_field( $mfields, 'multi' );

			
$optionspage->create_optionspage();
			
//$base_config->sections = $sections;
//$op = new Easy_Settings_API( $base_config );

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
