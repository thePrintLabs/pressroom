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

/**
 * Get posts linked to an edition
 * @param int or object $edition
 * @param boolean $only_enabled
 * @return array or boolean false
 */
function pr_get_edition_posts( $edition, $only_enabled = true ) {

  $linked_posts_id = pr_get_edition_posts_id( $edition, $only_enabled );

  if ( !empty( $linked_posts_id) ) {

    $posts = new WP_Query( array(
      'post_type'   => 'any',
      'post_status' => 'any',
      'post__in'    => $linked_posts_id,
      'orderby' => 'post__in',
      'posts_per_page' => -1,
      'nopaging'    => true
    ) );
    return $posts;
  }

  return false;
}

/**
 * Get single option from pressroom option array
 * @param  string $option
 * @return mixed or boolean false
 */
function pr_get_option( $option ) {

   $configs = get_option( 'tpl_options' );
   if( isset( $configs[$option] ) ) {
      return $configs[$option];
   }

   return false;
}
