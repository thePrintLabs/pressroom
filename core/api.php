<?php

/**
 * Get id of posts linked to an edition
 * @param int or object $edition
 * @param boolean $only_enabled
 * @return array
 */
function pr_get_edition_posts_id( $edition, $only_enabled = true ) {

  $linked_posts = array();
  $connected = p2p_get_connections( P2P_EDITION_CONNECTION, array(
    'to' => is_int( $edition ) ? get_post( $edition ) : $edition,
  ));

  foreach ( $connected as $conn ) {
    if ( $conn->post_status != 'trash') {
      if ( $only_enabled ) {
        $visible = p2p_get_meta( $conn->p2p_id, 'status', true );
        if ( !$visible ) {
          continue;
        }
      }

      $order = p2p_get_meta( $conn->p2p_id, 'order', true );
      $linked_posts[$order] = $conn->p2p_from;
    }

    ksort( $linked_posts );

    return $linked_posts;
  }
}

/**
 * Get posts linked to an edition
 * @param int or object $edition
 * @param boolean $only_enabled
 * @return array or boolean false
 */
function pr_get_edition_posts( $edition, $only_enabled = true ) {

  $linked_posts_id = pr_get_edition_posts_id( $edition, $only_enabled );

  if ( !empty( $linked_posts_id) ) {

    $post_types = array_merge( array( 'post', 'page' ), pr_get_option( 'pr_custom_post_type' ) );
    $edition_query = new WP_Query( array(
      'post_type'           => $post_types,
      'post_status'         => 'any',
      'post__in'            => $linked_posts_id,
      'orderby'             => 'post__in',
      'posts_per_page'      => -1,
      'nopaging'            => true
    ) );
    return $edition_query;
  }

  return false;
}

/**
 * Get single option from pressroom option array
 * @param  string $option
 * @return mixed or boolean false
 */
function pr_get_option( $option ) {

   $configs = get_option( 'pr_settings' );
   if( isset( $configs[$option] ) ) {
      return $configs[$option];
   }

   return false;
}

/**
 * get edition link with book protocol
 * @param  int $edition_id
 * @return string
 */
function pr_book( $edition_id ) {

  $edition = get_post( $edition_id );
  $book_url = str_replace( array( 'http://', 'https://' ), 'book://', TPL_HPUB_URI );

  return $book_url . TPL_Utils::sanitize_string( $edition->post_title . '.hpub' );
}

/**
 * get previous or next post in edition posts array
 * @param  int $post_id
 * @param  int $edition_id
 * @param  boolean $prev
 * @param  boolean $next
 * @return string or boolean
 */
function pr_get_edition_post( $post_id, $edition_id, $position = '' ) {

  $linked_query = pr_get_edition_posts( $edition_id, true );
  $linked_posts = $linked_query->posts;
  foreach( $linked_posts as $k => $post ) {

    if( $post->ID == $post_id ) {

      if( $position == 'prev' ) {
        if ( $k > 0 && isset( $linked_posts[$k-1] ) ) {
          $prev = $linked_posts[$k-1];
          return $prev->guid;
        }
        else {
          return false;
        }
      }

      if ( $position == 'next' ) {
        if ( $k < count( $linked_posts ) && isset( $linked_posts[$k+1] ) ) {
          $next = $linked_posts[$k+1];
          return $next->guid;
        }
        else {
          return false;
        }
      }
    }
  }

  return false;
}

/**
 * get previous post
 * @param  int $current_post_id
 * @param  int $edition_id
 * @return string or boolean
 */
function pr_prev( $current_post_id, $edition_id ) {

  return pr_get_edition_post( $current_post_id, $edition_id, 'prev' );
}

/**
 * get next post
 * @param  int $current_post_id
 * @param  int $edition_id
 * @return string or boolean
 */
function pr_next( $current_post_id, $edition_id ) {

  return pr_get_edition_post( $current_post_id, $edition_id, 'next' );
}
