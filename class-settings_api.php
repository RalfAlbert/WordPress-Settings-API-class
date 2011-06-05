<?php
/**
 * @package WordPress
 * @subpackage Settings-API class
 * @author Ralf Albert
 * @version 0.2
 * @license GPL
 */

/*
 * Credits go to:
 * - Alison Barret[1]
 * 	 His tutorial[2] has inspired me and helped me to write this class
 * 
 * [1] http://alisothegeek.com/author/abarrett/
 * [2] http://alisothegeek.com/2011/01/wordpress-settings-api-tutorial-1/
 */

class Settings_API_Class
{

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
	public function __construct( array $settings = NULL )
	{		
		if( !empty( $settings ) )
		{
			$this->set_settings( $settings );
			$this->init();
		}
	}
	
	/**
	 * 
	 * Setter for settings
	 * @param array $settings
	 * @return none
	 * @uses wp_parse_args()
	 * @since 0.1
	 * @access public
	 */
	public function set_settings( array $settings )
	{

		$defaults = array(
			'menu_position'		=> 'options',
			'page_slug' 		=> 'custom_page',
			'options_group' 	=> 'custom_options_group',
			'options_name' 		=> 'custom-options',
			'validate_callback' => '',
			'page_title' 		=> 'Custom Optionpage',
			'menu_title' 		=> 'Custom Optionpage',
			'capability' 		=> 'manage_options',
			'description' 		=> '',

			'sections' 			=> array(
					'custom' => __('Custom Section')
			),
			
			'section_desc' 		=> array(
					'custom' => __('Description for the custom section.')
			),
		
			'fields' 	=> array( 
								array(								   
									'id'      => 'sample_text',
									'title'   => __( 'Sample Text' ),
									'desc'    => __( 'This is a sample description.' ),
									'text_after' => '',
									'std'     => 'sample text',
									'size'	  => 30,
									'type'    => 'text',
									'section' => 'custom'
								)
			)
		);

		$this->_settings = wp_parse_args( $settings, $defaults );
		
		// Sanitize the users data!
		// There is only one thing that makes you sleep well:
		// Better than security is more security
		array_walk_recursive( $this->_settings, array( &$this, 'sanitize_settings' ) );
				
		extract( $this->_settings );
		
		$this->options_group	 = $options_group;
		$this->options_name		 = $options_name;
		$this->validate_callback = $validate_callback;
		$this->menu_position	 = $menu_position;
		$this->page_slug		 = $page_slug;
		$this->page_title	 	 = $page_title;
		$this->menu_title	 	 = $menu_title;
		$this->capability	 	 = $capability;
		$this->description 	 	 = $description;
		$this->icon				 = $icon;
		
		$this->sections 		 = $sections;
		$this->settings_fields 	 = $fields;
		
		// section descriptions are optional. we have to check if some are defined
		if( isset( $section_desc ) )
			$this->section_descs = $section_desc;

		// validate
		$whitelist_where = array( 
				'dashboard', 'posts', 'media', 'links',
				'pages', 'comments', 'theme', 'plugins',
				'users', 'options', 'management', 'menu'
		);
		
		if( !in_array( $this->menu_position, $whitelist_where ) )
			$this->menu_position = 'options';
			
		// cleanup your mess
		unset(
			$where, $settings, $defaults, $page_slug, $options_group, $options_name,
			$validate_callback, $page_title, $menu_title, $capability,
			$description, $sections, $fields, $section_desc, $whitelist_where
		);		
			
	}
	
	/**
	 * 
	 * Getter for settings
	 * @param none
	 * @return array
	 * @since 0.1
	 * @access public
	 */
	public function get_settings()
	{
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
	public function init()
	{
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
	public function add_page()
	{	
		$where = 'add_' . $this->menu_position . '_page';
		$this->_settings['admin_page'] = $where( $this->page_title, $this->menu_title, $this->capability, $this->page_slug, array( &$this, 'display_page' ) );		
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
	public function display_page()
	{	
		echo '<div class="wrap">
		<div class="icon32" id="' . $this->icon . '"></div>
		<h2>' . $this->page_title . '</h2>';
		
		if( isset( $this->description ) )
			echo '<p>' . $this->description . '</p>';
			
		echo '
		<form action="options.php" method="post">
			';
			settings_fields( $this->options_group );
			do_settings_sections( $this->page_slug );

			echo '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __( 'Save Changes' ) . '" /></p>
		</form>';
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
	public function register_settings()
	{

		register_setting( $this->options_group, $this->options_name, $this->validate_callback );
	
		foreach( $this->sections as $slug => $title )
		{
			add_settings_section( $slug, $title, array( &$this, 'display_section' ), $this->page_slug );
		}
	
		foreach( $this->settings_fields as $field )
		{
			$this->create_setting( $field );
		}
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
	public function create_setting( $args = array() )
	{

		$defaults = array(
			'id'      => 'default_field',
			'title'   => 'Empty',
			'desc'    => '',
			'text_after' => '',
			'std'     => '',
			'type'    => 'text',
			'size'	  => 30,
			'rows'	  => 3,
			'cols'	  => 25,
			'section' => 'general',
			'choices' => array(),
			'class'   => ''
		);
	
		extract( wp_parse_args( $args, $defaults ) );
	
		$field_args = array(
			'type'       => $type,
			'id'         => $id,
			'desc'       => $desc,
			'text_after' => $text_after,
			'std'        => $std,
			'size'		 => (int) $size,
			'rows'		 => (int) $rows,
			'cols'		 => (int) $cols,
			'choices'    => (array) $choices,
			'label_for'  => $id,
			'class'      => $class
		);
	
		add_settings_field( $id, $title, array( $this, 'display_settings_field' ), $this->page_slug, $section, $field_args );

	}
	
	/**
	 * 
	 * Display the description for a section if defined
	 * @param array $section
	 * @return none
	 * @since 0.1
	 * @access public
	 */
	public function display_section( $section )
	{
		if( key_exists( $section['id'], $this->section_descs ) )
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
	public function display_settings_field( $args = array() )
	{
		extract( $args );
		
		$options = get_option( $this->options_name );
				
		// set standard for multi checkbox
		if( is_array( $std ) && $type == 'mcheckbox' && !isset( $options[$id] ) )
		{
			foreach( $std as $key )
			{
				if( !isset( $options[$id.'-'.$key] ) )
					$options[$id.'-'.$key] = 'on';
			}
		}
				
		// set standard for all other
		if( !isset( $options[$id] ) && isset( $std ) )
			$options[$id] = $std;

		// set css class
		$field_class = '';
		if( !empty( $class ) )
			$field_class = ' class="' . $class . '"';
			
		// display setting field
		switch( $type )
		{
		
			case 'checkbox':
				$checked = '';
				if( isset( $options[$id] ) && $options[$id] == 'on' )
					$checked = ' checked="checked"';
			
				echo '<input' . $field_class . ' type="checkbox" id="' . $id . '" name="'.$this->options_name.'[' . $id . ']" value="on"' . $checked . ' /> <label for="' . $id . '">' . $text_after . '</label>';
				
				$this->show_field_description( $desc );
				
			break;
		
			case 'select':
				echo '<select' . $field_class . ' name="'.$this->options_name.'[' . $id . ']">';
			
				foreach( $choices as $value => $label )
				{
					$selected = '';
					if( $options[$id] == $value )
						$selected = ' selected="selected"';
					echo '<option value="' . $value . '"'.$selected.'>' . $label . '</option>';
				}
			
				echo '</select>';
			
				$this->show_field_description( $desc );
							
			break;
		
			case 'radio':
				$i = 0;
				foreach( $choices as $value => $label )
				{
					$selected = '';
					if ( $options[$id] == $value )
						$selected = ' checked="checked"';
						
					echo '<input' . $field_class . ' type="radio" name="'.$this->options_name.'[' . $id . ']" id="' . $id . $i . '" value="' . $value . '"'.$selected.'> <label for="' . $id . $i . '">' . $label . '</label>';
					
					if( $i < count( $choices ) - 1 )
						echo '<br />';
					
					$i++;
				}
			
				$this->show_field_description( $desc );
							
			break;
		
			case 'mcheckbox':
				$i = 0;
				
				foreach( $choices as $key => $label )
				{
					$checked = '';
					if( isset( $options[$id.'-'.$key] ) && 'on' == $options[$id.'-'.$key] ) 
						$checked = ' checked="checked"';
				
					echo '<input' . $field_class . ' type="checkbox" id="' . $id.'-'.$key . '" name="'.$this->options_name.'[' . $id.'-'.$key . ']" value="on"' . $checked . ' /> <label for="' . $id . '">' . $label . '</label>';
					
					if( $i < count( $choices ) - 1 )
						echo '<br />';

					$i++;
				}
				
				// this hidden input is neccessary to identify if the form is already saved
				// or if it is the initial form with standard values
				echo '<input type="hidden" name="'.$this->options_name.'[' . $id . ']" value="on" />';
				
				$this->show_field_description( $desc );
				
			break;
			
			case 'textarea':
				echo '<textarea' . $field_class . ' id="' . $id . '" name="'.$this->options_name.'[' . $id . ']" rows="'.$rows.'" cols="'.$cols.'" placeholder="' . $std . '">' . esc_textarea( $options[$id] ) . '</textarea>';
			
				$this->show_field_description( $desc );
							
			break;
		
			case 'password':
				echo '<input' . $field_class . ' type="password" id="' . $id . '" name="'.$this->options_name.'[' . $id . ']" value="' . $options[$id] . '" />'.$text_after;
			
				$this->show_field_description( $desc );
							
			break;
		
			case 'text':
				echo '<input' . $field_class . ' type="text" size="'. $size . ' id="' . $id . '" name="'.$this->options_name.'[' . $id . ']"
					placeholder="' . $std . '" value="' . esc_html( $options[$id] ) . '" />' . $text_after;
				
				$this->show_field_description( $desc );
			
			break;
			
			case 'heading':
			default:
				echo '</td></tr><tr valign="top"><td colspan="2">' . $desc;
			
			break;
		}
	}
	
	/**
	 * 
	 * Helper function to avoid code bloat
	 * @param string $desc
	 * @return none
	 * @since 0.2
	 * @access private
	 */
	private function show_field_description( $desc )
	{
		if( ! empty( $desc ) )
			echo '<br /><small>' . $desc . '</small>';
	}

	/**
	 * 
	 * Sanitizing the users data
	 * Strings will be sanitize with esc_attr, all other values will be cast to integer (we only need strings and integer)
	 * @param mixed $data
	 * @return none $data will be modified by reference
	 * @since 0.2.1
	 * @access private
	 */
	private function sanitize_settings( &$data )
	{
		if( is_string( $data ) )
			esc_attr( $data );
		else
			$data = intval( $data );
	}
}