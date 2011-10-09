<?php
class Easy_Settings_API_HTML_Output extends Easy_Settings_API
{
	public $options = array();
	
	public $single_setting_defaults = array(); 

	public $page_content_top;
	public $page_content_footer;
	
	public function __construct()
	{
		$this->page_content_top = '<div class="wrap">
		<div class="icon32" id="' . $this->icon . '"></div>
		<h2>' . $this->page_title . '</h2>';

		if ( isset( $this->description ) )
			$this->page_content_top .= '<p>' . $this->description . '</p>';

		$this->page_content_top .= '<form action="options.php" method="post">';
		
		$this->page_content_footer = '<p class="submit"><input name="Submit" type="submit" class="button-primary" value="' . __('Save Changes') . '" /></p></form></div>';

		$this->single_setting_defaults = array(
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
			'arguments'  => array(),
			'class'		 => ''
		);
		
	}	

/* ------------ display settings fields ------------ */
	
	/**
	 * custom settings field
	 */
	public function custom( $args )
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
	public function checkbox()
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
	public function select()
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
	public function radio()
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
	public function mcheckbox()
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
	public function mselect()
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
	public function textarea()
	{
		echo '<textarea' . $this->field_class . ' id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']" rows="' . $this->rows . '" cols="' . $this->cols . '" placeholder="' . $this->std . '">' . esc_textarea( $this->options[$this->id] ) . '</textarea>';
		$this->display_field_description( $this->desc );		
	}
		
	/**
	 * 
	 * display password field
	 */
	public function password()
	{
		echo '<input' . $this->field_class . ' type="password" id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']" value="' . $this->options[$this->id] . '" />' . $this->text_after;
		$this->display_field_description( $this->desc );
	}
		
	/**
	 * 
	 * display input field
	 */
	public function text()
	{
		echo '<input' . $this->field_class . ' type="text" size="' . $this->size . ' id="' . $this->id . '" name="' . $this->options_name . '[' . $this->id . ']"
			placeholder="' . $this->std . '" value="' . esc_html( $this->options[$this->id] ) . '" />' . $this->text_after;
		$this->display_field_description( $this->desc );

	}
		
	/**
	 * 
	 * display heading
	 */
	public function heading()
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