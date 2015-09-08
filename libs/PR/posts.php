<?php
class PR_posts
{
  protected $_metaboxes = array();
  /**
   * constructor method
   * Add class functions to wordpress hooks
   *
   */
  public function __construct() {

    add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
		add_action( 'save_post', array( $this, 'save_pr_post'), 40 );
  }

  /**
   * Add one or more custom metabox to edition custom fields
   *
   * @void
   */
  public function add_custom_metaboxes( $post_type, $post ) {

    global $tpl_pressroom;

    if( in_array( $post_type, $tpl_pressroom->get_allowed_post_types() ) ) {

      $this->get_custom_metaboxes( $post );

      foreach ( $this->_metaboxes as $metabox ) {

        add_meta_box($metabox->id, $metabox->title, array($this, 'add_custom_metabox_callback'), $post_type, $metabox->context, $metabox->priority);
      }
    }
  }

  /**
   * Custom metabox callback print html input field
   *
   * @echo
   */
  public function add_custom_metabox_callback( $post, $metabox ) {

    echo '<table class="form-table">';
    $custom_metabox = $this->_metaboxes[$metabox['id']];
    echo $custom_metabox->fields_to_html();
    echo '</table>';
  }

  /**
   * Get custom metaboxes configuration
   *
   * @param object $post
   * @void
   */
  public function get_custom_metaboxes( $post ) {

      $terms = wp_get_post_terms( $post->ID, PR_EDITORIAL_PROJECT );
      $placeholder = '';
      if(isset($terms[0])) {
        $placeholder = pr_get_sharing_placeholder( $post->ID, $terms[0]->term_id );
      }
      $e_meta = new PR_Metabox( 'sharing_metabox', __( 'Sharing', 'edition' ), 'normal', 'high', $post->ID );
      $e_meta->add_field( '_pr_sharing_url', __( 'Sharing Link', 'edition' ), __( 'Sharing link inside application. Leave it blank, for default value. ', 'pressroom' ), 'text', '', array( 'placeholder' => $placeholder ) );

      $this->_metaboxes['sharing_metabox'] = $e_meta;

  }

  /**
   * Save metabox form data
   *
   * @param  int $post_id
   * @void
   */
  public function save_pr_post( $post_id ) {

    //Check autosave
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
      return $post_id;
    }

    //Check permissions
    if ( !current_user_can( 'edit_page', $post_id ) ) {
      return $post_id;
    }

    $post = get_post( $post_id );
    $this->get_custom_metaboxes( $post );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_values();
    }
  }
}

$pr_posts = new PR_posts;
