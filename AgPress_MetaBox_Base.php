<?php # -*- coding: utf-8 -*-
/**
 * Base class for Meta Boxes
 *
 * @author John Robinson
 * @version 0.1
 */
abstract class AgPress_MetaBox_Base {

  /**
	 * Nonce field.
	 *
	 * @var object
	 */
	protected $nonce;

  /**
	 * Constructor
	 *
	 * @param array $params
	 * @return void
	 */
	public function __construct( array $params ) {
    $defaults    = array (
			'key'           => NULL,
			'title'         => 'MISSING TITLE!',
      'posttypes'	    => array ( 'post', 'page' ),
		  'context'       => 'side',
		  'priority'      => 'low',
		  'filter_prefix' => strtolower( __CLASS__ ),
      'fields'        => array(),
      'text_domain'   => strtolower( __CLASS__ )
		);

		$this->vars  = array_merge( $defaults, $params );
		$this->nonce = wp_create_nonce( $this->vars['title'] );

		add_action( 'admin_init', array ( $this, 'register_boxes' ) );
		add_action( 'save_post',  array ( $this, 'save' ) );
  }

  /**
	 * Registers the meta box to all post types.
	 *
	 * @return void
	 */
	public function register_boxes()	{
		$posttypes = apply_filters(
			$this->vars['filter_prefix'] . '_post_types',
      (array) $this->vars['posttypes']
		);

		foreach ( $posttypes as $posttype )	{
			add_meta_box(
				$this->vars['key'],
				$this->vars['title'],
				array ( $this, 'print_meta_box' ),
				$posttype,
				'side',
				$this->vars['priority']
			);
		}
	}

  /**
   * Draws the meta box into the editor page.
   *
   * @param  object $data
   * @return void
   */
  public function print_meta_box( $data )
  {
    $id = $this->get_id( $data );

    $result = $this->nonce_input();
    $result .= '<table class="form-table">';

    // loop through fields...
    foreach( $this->vars['fields'] as $field ) {

      $value = get_post_meta( $id, $field['id'], true );

      $result .= '<tr>';
      $result .= '  <th><label for="' . $field['id'] . '">' . $field['label'] . '</label></th>';
      $result .= '  <td>';
      $result .= $this->get_input_markup( $field );
      $result .= '  </td>';
      $result .= '</tr>';
    }

    $result .= '</table>';
  }

	/**
	 * Saves the content to the post meta.
	 *
	 * @return void
	 */
	public function save( $post_id )	{

    if ( !wp_verify_nonce( $this->nonce, $this->vars['TITLE'] ) ) {
      return $post_id;
    }

		if ( $this->is_allowed_save( $post_id ) ) {
      // loop through fields and save the data
      foreach ($this->vars['fields'] as $field) {
          $old = get_post_meta($post_id, $field['id'], true);
          $new = $_POST[$field['id']];
          if ($new && $new != $old) {
              update_post_meta($post_id, $field['id'], $new);
          } elseif ('' == $new && $old) {
              delete_post_meta($post_id, $field['id'], $old);
          }
      }
    }
	}

	/**
	 * Checks if we should trigger the save action.
	 *
	 * @param  int  $post_id
	 * @return bool
	 */
	protected function is_allowed_save( $post_id )
	{
		// Check integrity, proper action and permission
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE )
		{
			return false;
		}
		if ( ! wp_verify_nonce( $_POST[$this->custom_fields['key'] . '_nonce'], basename( __FILE __) ) )
		{
			return true;
		}
		if (    ! current_user_can( 'edit_post', $post_id )
			and ! current_user_can( 'edit_page', $post_id )
		)
		{
			return false;
		}

		return true;
	}

  /**
	 * Creates an input[type=hidden] with an nonce (number used once).
	 *
	 * @see http://codex.wordpress.org/Function_Reference/wp_create_nonce
	 * return string
	 */
	protected function nonce_input()
	{
    $key = $this->vars['key'];
		return "<input type='hidden' name='{$key}_noncename'
				id='{$key}_noncename'	value='{$this->nonce}' />";
	}

  protected function get_input_markup( $field, $meta ) {
    $markup = '';
    $id = $field['id'];
    $desc = $field['desc'];

    switch ($field['type']) {
      case 'text' :
        $markup .= '<input type="text" name="' . $id . '" id="' . $id . '" value="' . $meta . '" />';
        $markup .= '<br /><span class="description">' . $desc . '</span>';
        break;
      case 'textarea' :
        $markup .= '<textarea name="' . $id . '" id="' . $id '">' . $meta . '</textarea>';
        $markup .= '<br /><span class="description">' . $desc . '</span>';
        break;
      case 'checkbox' :
        $markup .= '<input type="checkbox" name="' . $id . '" id="' . $id . '" ', $meta ? 'checked="checked"' : '', ' />';
        $markup .= '<br /><span class="description">' . $desc . '</span>';
    }
  }
}

?>
