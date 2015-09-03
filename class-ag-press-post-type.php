<?php

class Ag_Press_Post_Type {
  public $post_type_name;
  public $post_type_args;
  public $post_type_labels;

  public function __construct($name, $plural, $args = array(), $labels = array() ) {
    $this->post_type_name =   self::uglify( $name );
    $this->post_type_args =   $args;
    $this->post_type_labels = $labels;

    if ( ! post_type_exists( $this->post_type_name ) ) {
      add_action( 'init', array( &$this, 'register_post_type' ) );
    }

    $this->save();
  }

  public function register_post_type() {
    // Capitalize and make plural
    $name = self::beautify( $this->post_type_name );
    $plural = self::pluralize( $name );

    $labels = array_merge(
      // Defaults
      array(
        'name'                  => _x( $plural, 'post type general name' ),
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
      ),
      $this->post_type_labels
    );

    $args = array_merge(
      // Defaults
      array(
        'label'                 => $plural,
        'labels'                => $labels,
        'public'                => true,
        'show_ui'               => true,
        'supports'              => array( 'title', 'editor' ),
        'show_in_nav_menus'     => true,
        '_builtin'              => false
      ),
      $this->post_type_args
    );

    register_post_type( $this->post_type_name, $args );
  }

  public function add_taxonomy( $name, $plural, $args = array(), $labels = array() ) {
    if( empty( $name ) ) {
      return;
    }

    $post_type_name = $this->post_type_name;
    $taxonomy_name = self::uglify( $name );
    $taxonomy_labels = $labels;
    $taxonomy_args = $args;

    if ( ! taxonomy_exists( $taxonomy_name ) ) {

      $name = self::beautify( $name );
      $plural = self::pluralize( $name );

      // labels
      $labels = array_merge(
        // Defaults
        array(
          'name'                  => _x( $plural, 'taxonomy general name' ),
          'singular_name'         => _x( $name, 'taxonomy singular name' ),
          'search_items'          => __( 'Search ' . $plural ),
          'all_items'             => __( 'All ' . $plural ),
          'parent_item'           => __( 'Parent ' . $name ),
          'parent_item_colon'     => __( 'Parent ' . $name . ':' ),
          'edit_item'             => __( 'Edit ' . $name ),
          'update_item'           => __( 'Update ' . $name ),
          'add_new_item'          => __( 'Add New ' . $name ),
          'new_item_name'         => __( 'New ' . $name . ' Name' ),
          'menu_name'             => __( $name )
        ),
        $taxonomy_labels
      );

      $args = array_merge(
        // Default args
        array(
          'label'                 => $plural,
          'labels'                => $labels,
          'public'                => true,
          'show_ui'               => true,
          'show_in_nav_menus'     => true,
          '_builtin'              => false
        ),
        $taxonomy_args
      );

      add_action( 'init',
        function () use( $taxonomy_name, $post_type_name, $args ) ) {
          register_taxonomy( $taxonomy_name, $post_type_name, $args );
        }
    } else {
      add_action( 'init',
        function () use( $taxonomy_name, $post_type_name) ) {
          register_taxonomy_for_object_type( $taxonomy_name, $post_type_name );
        }
    }

  }

  public function add_meta_box( $title, $fields = array(), $context = 'normal', $priority = 'default' ) {

    if ( empty( $title) ) {
      return;
    }

    $post_type_name = $this->post_type_name;

    $box_id = strtolower( str_replace( ' ', '_', $title ) );
    $box_title = ucwords( str_replace( '_', ' ', $title ) );
    $box_context = $context;
    $box_priority = $priority;

    global $custom_fields;
    $custom_fields[$title] = $fields;

    add_action( 'admin_init',
      function() use( $box_id, $box_title, $post_type_name, $box_context, $box_priority ) ) {
        add_meta_box(
          $box_id,
          $box_title,
          function ( $post, $data ) {
            global $post;

            wp_nonce_field( plugin_basename( __FILE__ ), 'custom_post_type');

            // Grab all inputs from $data
            $custom_fields = $data['args'][0];

            // Grab saved values
            $meta = get_post_custom( $post->ID );

            if ( ! empty( $custom_fields) ) {

              echo '<table>' . PHP_EOL;
              // Loop
              foreach ( $custom_fields as $label => $type ) {
                echo '  <tr>' . PHP_EOL;

                $field_id_name  = self::uglify( $data['id'] ) . '_' . self::ugilfy( $label );

                echo '    <td><label for="' . $field_id_name . '">' . $label . '</label></td>' . PHP_EOL;
                echo '    <td><input type="text" name="custom_meta[' . $field_id_name . ']" id="' . $field_id_name . '" value="' . $meta[$field_id_name][0] . '" /></td>' . PHP_EOL;
                echo '  </tr>' . PHP_EOL;
              }

              echo '</table>' . PHP_EOL;
            }
          },
          $post_type_name,
          $box_context,
          $box_priority,
          array( $fields )
        );
      }
  }

  public function save() {
    $post_type_name = $this->post_type_name;

    add_action( 'save_post', function() use ( $post_type_name ) {
      if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
        return;
      }

      if ( ! wp_verify_nonce( $_POST['custom_post_type'], plugin_basename(__FILE__) ) ) {
        return;
      }

      global $post;

      if( isset( $_POST ) && isset( $post->ID ) && get_post_type( $post->ID ) == $post_type_name ) {
          global $custom_fields;
          // Loop through each meta box
          foreach( $custom_fields as $title => $fields ) {
              // Loop through all fields
              foreach( $fields as $label => $type ) {
                  $field_id_name  = self::uglify( $title ) . '_' . self::uglify( $label );

                  update_post_meta( $post->ID, $field_id_name, $_POST['custom_meta'][$field_id_name] );
              }
          }
      }
    });

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
