<?php

if ( ! class_exists ( 'Ag_Press_Custom_Post_Type_Base' ) ) {
  /**
	 * A base class for custom post types
	 */
  class Ag_Press_Custom_Post_Type_Base {
    protected $post_type_name;
    protected $post_type_args;
    protected $post_type_labels;

    protected $meta = array();

    /**
    * Constructor
    */
    public function _construct($name, array $args = array(), $labels = array() ) {

      $this->post_type_name = $name;
      $this->post_type_args = $args;
      $this->post_type_labels = $labels;
      $this->add_hooks();
    }

    protected function add_hooks() {
      // Register actions
      add_action( 'init', array( &$this, 'init' ) );
      add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }

    protected static function default_labels( $name ) {
      // Format name and pluralize
      $name = self::beautify( $name );
      $plural = self::pluralize( $name );

      return array(
        'name' => _x( $plural, 'post type general name' ),
        'singular_name'         => _x( $name, 'post type singular name' ),
        'add_new'               => _x( 'Add New', strtolower( $name ) ),
        'add_new_item'          => __( 'Add New ' . $name ),
        'edit_item'             => __( 'Edit ' . $name ),
        'new_item'              => __( 'New ' . $name ),
        'all_items'             => __( 'All ' . $plural ),
        'view_item'             => __( 'View ' . $name ),
        'search_items'          => __( 'Search ' . $plural ),
        'not_found'             => __( 'No ' . strtolower( $plural ) . ' found'),
        'not_found_in_trash'    => __( 'No ' . strtolower( $plural ) . ' found in Trash'),
        'parent_item_colon'     => '',
        'menu_name'             => $plural
      );
    }

    protected static function default_args( $name, $labels ) {
      // Format name and pluralize
      $name = self::beautify( $name );
      $plural = self::pluralize( $name );

      return array(
            'label'                 => $plural,
            'labels'                => $labels,
            'public'                => true,
            'show_ui'               => true,
            'supports'              => array( 'title', 'editor' ),
            'show_in_nav_menus'     => true,
            '_builtin'              => false,
      );
    }



    public function init() {
      $this->create_post_type();

      add_action( 'save_post', array( &$this, 'save_post' ) );
    }

    public function create_post_type() {
      $name = self::beautify( $this->post_type_name );
      $labels = array_merge(
        //Defaults
        $this->default_labels($name),
        // User
        $this->post_type_labels
      );

      $args = array_merge(
        // Defaults
        $this->default_args( $name, $labels ),
        // User
        $this->post_type_args
      );

      register_post_type( $name, $args );
    }

    /**
    	 * Save the metaboxes for this custom post type
    	 */
    	public function save_post($post_id)
    	{
            // verify if this is an auto save routine.
            // If it is our form has not been submitted, so we dont want to do anything
            if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
            {
                return;
            }

    		if(isset( $_POST['post_type'])
          && $_POST['post_type'] == self::POST_TYPE
          && current_user_can( 'edit_post', $post_id ) ) {

    			foreach( $this->_meta as $field_name ) {
    				// Update the post's meta field
    				update_post_meta( $post_id, $field_name, $_POST[$field_name]);
    			}
    		}
    		else {
    			return;
    		}
    	}

    	/**
    	 * hook into WP's admin_init action hook
    	 */
    	public function admin_init()
    	{
    		// Add metaboxes
    		add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
    	} // END public function admin_init()

    	/**
    	 * hook into WP's add_meta_boxes action hook
    	 */
    	public function add_meta_boxes()
    	{
    		// Add this metabox to every selected post
        $name = self::uglify($this->post_type_name)
    		add_meta_box(;
    			sprintf('wp_plugin_template_%s_section', self::POST_TYPE),
    			sprintf('%s Information', ucwords(str_replace("_", " ", self::POST_TYPE))),
    			array(&$this, 'add_inner_meta_boxes'),
    			self::POST_TYPE
    	    );
    	} // END public function add_meta_boxes()
		/**
		 * called off of the add meta box
		 */
		public function add_inner_meta_boxes($post)
		{
			// Render the job order metabox
			include(sprintf("%s/../templates/%s_metabox.php", dirname(__FILE__), self::POST_TYPE));
		}

    public static function beautify( $string ) {
      return ucwords( str_replace( '_', ' ', $string ) );
    }

    public static function uglify( $string ) {
      return strtolower( str_replace( ' ', '_', $string ) );
    }

    public static function pluralize( $string ) {

      $last = $string[strlen( $string ) - 1];

      if( $last == 'y' ) {
          $cut = substr( $string, 0, -1 );
          //convert y to ies
          $plural = $cut . 'ies';
      }
      else {
          // just attach an s
          $plural = $string . 's';
      }

      return $plural;
    }
  }
}
