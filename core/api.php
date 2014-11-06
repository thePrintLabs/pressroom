<?php

/**
 * Get id of posts linked to an edition
 * @param int or object $edition
 * @param boolean $only_enabled
 * @return array
 */
function pr_get_edition_posts_id( $edition, $only_enabled = true ) {

  global $wpdb;
  $q= "SELECT ID FROM $wpdb->posts AS posts
  LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_from = posts . ID
  LEFT JOIN $wpdb->p2pmeta AS meta ON meta.p2p_id = p2p . p2p_id AND meta.meta_key = 'order'";

  if ( $only_enabled ) {
    $q.= "LEFT JOIN $wpdb->p2pmeta AS meta_status ON meta_status.p2p_id = p2p . p2p_id AND meta_status.meta_key = 'status'";
  }

  $q.= "WHERE post_status <> %s AND meta_status.meta_value = %b AND p2p.p2p_to = %u ORDER BY CAST(meta.meta_value AS UNSIGNED ) ASC";

  $linked_posts = $wpdb->get_col( $wpdb->prepare( $q, 'trash', 1, $edition->ID ) );

  return $linked_posts;
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
