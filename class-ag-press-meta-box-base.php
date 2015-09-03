<?php

if (!defined('ABSPATH')) exit; // Exit if accessed directly

if ( ! class_exists( 'Ag_Press_Meta_Box_Base' ) ) {
  class Ag_Press_Meta_Box_Base {

    protected $title;
    protected $meta = array();
    protected $post_types = array();

    public function __construct($title, $meta = array(), $post_types = array()) {
      $this->title = $title;
      $this->meta = $meta;
      $this->post_types = $post_types;

      $this->add_hooks();
    }

    protected function add_hooks() {
      add_action( 'init', array( &$this, 'init' ) );
      add_action( 'admin_init', array( &$this, 'admin_init' ) );
    }

    public function init() {
      add_action( 'save_post', array( &$this, 'save_post' ) );
    }

    public function admin_init() {
      add_action('add_meta_boxes', array(&$this, 'add_meta_boxes'));
    }

    public function save_post($post_id)
    {
      // verify if this is an auto save routine.
      // If it is our form has not been submitted, so we dont want to do anything
      if(defined('DOING_AUTOSAVE') && DOING_AUTOSAVE)
      {
          return;
      }

      foreach ($this->post_types as $post_type) {
        if(isset( $_POST['post_type'])
          && $_POST['post_type'] == $post_type
          && current_user_can( 'edit_post', $post_id ) ) {

          foreach( $this->_meta as $field_name => $field_type ) {
            // Update the post's meta field
            update_post_meta( $post_id, $field_name, $_POST[$field_name]);
          }
        }
        else {
          return;
        }
      }
    }
    
    public function add_meta_boxes() {
      // Add this metabox to every selected post
      foreach ($this->post_types as $post_type) {
        add_meta_box(;
          sprintf('ag_press_%s_section', $post_type),
          sprintf('%s', $this->title),
          array(&$this, 'display_meta_boxes'),
          $post_type
          );
      }
    }

    public function display_meta_boxes() {
      // build form...
    }

  }
}
