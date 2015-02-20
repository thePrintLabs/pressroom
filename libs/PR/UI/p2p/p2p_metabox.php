<?php

class PR_P2P_Metabox
{
  public function __construct() {

    add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
    add_action( 'save_post', array( $this, 'save_p2p_relation'), 40 );
  }

  /**
  * Add p2p metabox
  *
  * @param string $post_type
  * @param object $post
  * @void
  */
  public function add_custom_metaboxes( $post_type, $post ) {

    global $tpl_pressroom;
    $register_post_types = $tpl_pressroom->get_allowed_post_types();

    if( in_array( $post_type, $register_post_types ) ) {
      add_meta_box( 'pr_p2p_metabox', __( 'Editions', 'edition' ), array( $this, 'add_custom_metabox_callback' ), $post_type, 'side', 'high');
    }
  }

/**
 * Render metabox content
 *
 * @param object $post
 * @void
 */
  public function add_custom_metabox_callback( $post ) {


    $per_page = 1;
    $current_page = isset( $_POST['page'] ) ? $_POST['page'] : 1;

    $args = array(
      'posts_per_page' => $per_page,
      'paged' => $current_page,
      'post_type' => PR_EDITION
    );



    $editions = get_posts( $args );

    $total_items = count( $editions );

    $editions = array_slice( $editions, ($current_page - 1) * $per_page , $per_page );

    echo '<div id="editions-all" class="tabs-panel">';
    echo '<ul class="categorychecklist form-no-clear">';

    foreach( $editions as $edition ) {
      $connected = pr_get_connected_edition_ids( $post );
      echo '<li class="popular-category">
      <label class="selectit">
      <input value="' . $edition->ID .'" '. ( in_array( $edition->ID, $connected ) ? 'checked="checked"' : '' ) .' type="checkbox" name="post_editions[' . $edition->ID . ']">' . $edition->post_title . '
      </label>
      <input type="hidden" name="post_edition_status['. $edition->ID . ']" value="' . $edition->ID .'" />
      </li>';
    }

    echo '</ul></div>';
  }

  /**
   * Save relation between current post and selected editions
   *
   * @param  int $post_id
   * @void
   */
  public function save_p2p_relation( $post_id ) {

    global $tpl_pressroom;
    $register_post_types = $tpl_pressroom->get_allowed_post_types();
    $post = get_post( $post_id );
    $editions = isset( $_POST['post_edition_status'] ) ? $_POST['post_edition_status'] : false;

    if( in_array( $post->post_type, $register_post_types ) ) {
      foreach( $editions as $key => $edition ) {
        $relation = isset( $_POST["post_editions"][$key] ) ? $_POST["post_editions"][$key] : false;
        if( $relation ) {
          p2p_type( P2P_EDITION_CONNECTION )->connect( $post_id, $edition, array(
            'date' => current_time('mysql')
          ) );
        }
        else {
          p2p_type( P2P_EDITION_CONNECTION )->disconnect( $post_id, $edition );
        }
      }
    }
  }
}

$pr_p2p_metabox = new PR_P2P_Metabox();
