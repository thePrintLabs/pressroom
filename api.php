<?php

/**
 * Get posts ids array linked to the edition
 *
 * @param object $edition
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
 *
 * @param object $edition
 * @param boolean $only_enabled
 * @return array or boolean false
 */
function pr_get_edition_posts( $edition, $only_enabled = true ) {

  $linked_posts_id = pr_get_edition_posts_id( $edition, $only_enabled );

  if ( !empty( $linked_posts_id) ) {

    $custom_post_types = pr_get_option( 'pr_custom_post_type' );
    if ( is_array( $custom_post_types ) ) {
      $post_types = array_merge( array( 'post', 'page' ), pr_get_option( 'pr_custom_post_type' ) );
    }
    else {
      $post_types = array( 'post', 'page', $custom_post_types );
    }

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
 * get ids of editions connected to $post
 *
 * @param  object $post
 * @return array
 */
function pr_get_connected_edition_ids( $post ) {

  global $wpdb;
  $q= "SELECT ID FROM $wpdb->posts AS posts
  LEFT JOIN $wpdb->p2p AS p2p ON p2p.p2p_to = posts . ID
  WHERE post_status <> %s AND p2p_type = %s AND p2p_from = %u;" ;

  $linked_posts = $wpdb->get_col( $wpdb->prepare( $q, 'trash', P2P_EDITION_CONNECTION, $post->ID ) );

  return $linked_posts;
}

/**
 * Get single option from pressroom option array
 *
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
 * Get single option from editorial project option array
 *
 * @param string $option
 * @param int $term_id
 * @return mixed or boolean false
 */
function pr_get_eproject_option( $term_id, $option ) {

  $option = PR_Editorial_Project::get_config( $term_id, $option);

  if ( $option ) {
    return $option;
  }

  return false;
}

/**
 * Rewrite edition link with book protocol
 *
 * @param  int $edition_id
 * @return string
 */
function pr_book( $edition_id ) {

  $edition = get_post( $edition_id );
  $book_url = str_replace( array( 'http://', 'https://' ), 'book://', PR_HPUB_URI );

  return $book_url . PR_Utils::sanitize_string( $edition->post_title . '.hpub' );
}

/**
 * Get previous or next post from edition posts array
 *
 * @param  int $post_id
 * @param  object $edition
 * @param  boolean $prev
 * @param  boolean $next
 * @return string or boolean
 */
function pr_get_edition_post( $post_id, $edition, $position = '' ) {

  $linked_query = pr_get_edition_posts( $edition, true );
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
 * Get previous post
 *
 * @param  int $post_id
 * @param  object $edition
 * @return string or boolean
 */
function pr_prev( $post_id = false, $edition = false ) {

  if( !$edition && isset( $_GET['edition_id'] ) ) {
    $edition = get_post( $_GET['edition_id'] );
  }
  if( !$post_id ) {
    global $post;
    $post_id = $post->ID;
  }

  return pr_get_edition_post( $post_id, $edition, 'prev' );
}

/**
 * Get next post
 *
 * @param  int $post_id
 * @param  object $edition
 * @return string or boolean
 */
function pr_next( $post_id = false, $edition = false ) {

  if( !$edition && isset( $_GET['edition_id'] ) ) {
    $edition = get_post( $_GET['edition_id'] );
  }
  if( !$post_id ) {
    global $post;
    $post_id = $post->ID;
  }
  return pr_get_edition_post( $post_id, $edition, 'next' );
}

/**
 * Rewrite post url with sharing domain
 *
 * @param  object $post
 * @return string $permalink
 */
function pr_get_sharing_placeholder( $post_id = false, $term_id ) {

  if( !$post_id ) {
    global $post;
    $post_id = $post->ID;
  }

  $permalink = get_permalink( $post_id );
  $domain = get_home_url();

  // $options = get_option('pr_settings');
  $options = get_option( "taxonomy_term_" . $term_id );
  $sharing_domain = isset( $options['_pr_sharing_domain'] ) ? $options['_pr_sharing_domain'] : '' ;
  if( $sharing_domain ) {
    $permalink = str_replace( $domain, $sharing_domain, $permalink );
  }

  return $permalink;
}

/**
 * Get sharing link
 *
 * @param  object $post
 * @return string $sharing_url
 */
function pr_get_sharing_url( $post_id = false, $term_id ) {

  if( !$post_id ) {
    global $post;
    $post_id = $post->ID;
  }
  $sharing_url = get_post_meta( $post_id, '_pr_sharing_url', true );

  if( $sharing_url ) {
    return $sharing_url;
  }

  return pr_get_sharing_placeholder( $post_id, $term_id );
}

/**
 * Get galleries array from shortcode
 *
 * @param  int $post_id
 * @return array
 */
function pr_get_galleries( $post_id ) {

  $post_content = get_post_field('post_content', $post_id);

  preg_match_all('/\[gallery.*name="(.*)".*ids=.(.*).\]/', $post_content, $matched_galleries);
  preg_match_all('/\[playlist.*name="(.*)".*ids=.(.*).\]/', $post_content, $matched_playlist);

  $matches = array();

  if( $matched_galleries ) {
    $matched_galleries = pr_cycle_matches( $matched_galleries );
  }

  if( $matched_playlist ) {
    $matched_playlist = pr_cycle_matches( $matched_playlist );
  }

  $matches = array_merge_recursive( $matched_galleries, $matched_playlist );

  return $matches;
}
/**
 * Get shortcode matches array return an elaborated array
 *
 * @param  array $matches
 * @return array
 */
function pr_cycle_matches( $matches ) {

  $galleries = $matches[2];
  $upload_dir = wp_upload_dir();

  if( $galleries ) {
    $book_galleries = array();

    foreach( $galleries as $key => $gallery ) {
      $attachments_id = explode( ",", $gallery );
      $book_gallery = array();

      foreach( $attachments_id as $k => $attachment_id ) {
        $attachment = get_post( $attachment_id );
        $attachment_path = $upload_dir['basedir'] . DS . $attachment->guid;
        $info = pathinfo( $attachment_path );
        $book_gallery[$k]['uri'] = PR_EDITION_MEDIA . $info['basename'];
        $book_gallery[$k]['caption'] = $attachment->post_excerpt;
      }

      $book_galleries[$matches[1][$key]] = $book_gallery;

    }
    return $book_galleries;
  }

  return array();
}

function pr_get_template_directory() {

  return get_option('pr_theme_root') . DS . get_option( 'stylesheet' );
}

function pr_get_template_directory_uri() {

  return get_option('pr_theme_uri') . DS . get_option( 'stylesheet' );
}
