<?php
/**
* PressRoom packager: Book.json
*
*/
final class PR_Packager_Book_JSON
{
  private static $_press_to_baker = array(
    '_pr_orientation'                 => 'orientation',
    '_pr_zoomable'                    => 'zoomable',
    '_pr_body_bg_color'               => '-baker-background',
    '_pr_background_image_portrait'   => '-baker-background-image-portrait',
    '_pr_background_image_landscape'  => '-baker-background-image-landscape',
    '_pr_page_numbers_color'          => '-baker-page-numbers-color',
    '_pr_page_numbers_alpha'          => '-baker-page-numbers-alpha',
    '_pr_page_screenshot'             => '-baker-page-screenshots',
    '_pr_rendering'                   => '-baker-rendering',
    '_pr_vertical_bounce' 	          => '-baker-vertical-bounce',
    '_pr_media_autoplay'	 	          => '-baker-media-autoplay',
    '_pr_vertical_pagination'         => '-baker-vertical-pagination',
    '_pr_page_turn_tap'               => '-baker-page-turn-tap',
    '_pr_page_turn_swipe'             => '-baker-page-turn-swipe',
    '_pr_index_height'                => '-baker-index-height',
    '_pr_index_width'                 => '-baker-index-width',
    '_pr_index_bounce'                => '-baker-index-bounce',
    '_pr_start_at_page'               => '-baker-start-at-page',
    '_pr_author'                      => 'author',
    '_pr_creator'                     => 'creator',
    '_pr_cover'                       => 'cover',
    '_pr_package_date'                => 'date',
    '_pr_package_updated_date'        => 'updated_date',
    'post_title'                      => 'title',
    'post_excerpt'                    => 'info',
    'contents'                        => 'contents',
    'sharing_urls'                    => 'sharing_urls',
    'titles'                          => 'titles'
  );

  /**
  * Get all options and html files and save them in the book.json
  *
  * @param object $packager
  * @param int $term_id
  * @void
  */
  public static function generate_book( $packager, $term_id ) {

    $press_options = self::_get_pressroom_options( $packager, $term_id );
    foreach ( $packager->linked_query->posts as $post ) {
      $page_name = PR_Utils::sanitize_string( $post->post_title );
      $page_path = $packager->edition_dir . DIRECTORY_SEPARATOR . $page_name . '.html';

      $press_options['sharing_urls'][] = pr_get_sharing_url( $post->ID );
      $press_options['titles'][] = $post->post_title;

      if ( is_file( $page_path ) ) {
        $press_options['contents'][] = $page_name . '.html';
      }
      else {
        PR_Packager::print_line( sprintf( __( 'Can\'t find file %s. It won\'t add to book.json ', 'edition' ), $page_path ), 'error' );
      }

      do_action_ref_array( 'pr_packager_generate_book', array( &$press_options, $post, $packager->edition_dir ) );
    }

    if ( !empty( $press_options['contents'] ) ) {
      $press_options['contents'] = array_values( $press_options['contents'] );
    }

    if ( !empty( $press_options['sharing_urls'] ) ) {
      $press_options['sharing_urls'] = array_values( $press_options['sharing_urls'] );
    }

    return PR_Packager_HPUB_Package::save_json_file( $press_options, 'book.json', $packager->edition_dir );
  }


  /**
  * Get pressroom edition configuration options
  *
  * @param  boolean $shelf
  * @return array
  */
  protected static function _get_pressroom_options( $packager, $term_id ) {

    global $tpl_pressroom;

    $book_url = str_replace( array( 'http://', 'https://' ), 'book://', PR_HPUB_URI );
    $hpub_url = str_replace( PR_HPUB_PATH, $book_url, get_post_meta( $packager->edition_post->ID, '_pr_edition_hpub_' . $term_id, true ) );

    $options = array(
    'hpub'   => true,
    'url'    => $hpub_url
    );

    // check if use edition or editorial project hpub attributes
    $configs = get_option( 'taxonomy_term_' . $term_id );
    $override = get_post_meta( $packager->edition_post->ID, '_pr_hpub_override_eproject', true );


    if( $override ) {
      $configs = array();
      $custom_configs = get_post_meta( $packager->edition_post->ID );
      foreach( $custom_configs as $key => $custom_config ) {
        $configs[$key] = $custom_config[0];
      }
    }

    if ( !$configs ) {
      return $options;
    }

    // custom edition attributes
    foreach ( self::$_press_to_baker as $key => $baker_option ) {

      $option = isset( $configs[$key] ) ? $configs[$key] : '';

      switch ( $key ) {
        case '_pr_cover':
        $options[$baker_option] = PR_EDITION_MEDIA . $packager->edition_cover_image;
        break;
        case '_pr_author':
        case '_pr_creator':
        if( !$override ) {
          $option = get_post_meta( $packager->edition_post->ID, $key, true );
        }
        if ( isset( $option ) && !empty( $option ) ) {
          $authors = explode( ',', $option );
          foreach ( $authors as $author ) {
            $options[$baker_option][] = $author;
          }
        }
        break;
        case '_pr_index_height':
        case '_pr_index_width':
        $options[$baker_option] = is_numeric( $option ) ? $option : null;
        break;
        case '_pr_start_at_page':
        case '_pr_page_numbers_alpha':
        $options[$baker_option] = (int)$option;
        break;
        case '_pr_orientation':
        case '_pr_rendering':
        $options[$baker_option] = strtolower($option);
        break;
        case '_pr_zoomable':
        case '_pr_vertical_bounce':
        case '_pr_vertical_pagination':
        case '_pr_index_bounce':
        case '_pr_media_autoplay':
        case '_pr_page_turn_tap':
        case '_pr_page_turn_swipe':
        $options[$baker_option] = $option == 'on';
        break;
        case '_pr_background_image_portrait':
        case '_pr_background_image_landscape':
        $media = get_attached_file( $option );
        if ( $media ) {
          $media_info = pathinfo( $media );
          $path = $media_info['basename'];
          copy( $media, $packager->edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA . $path );
          $options[$baker_option] = PR_EDITION_MEDIA . $path;
        }
        else {
          $options[$baker_option] = '';
        }
        break;
        default:
        $options[$baker_option] = ( $option == '0' || $option == '1' ? (int)$option : $option );
        break;
      }
    }

    // core edition attributes
    foreach ( $packager->edition_post as $key => $value ) {

      if ( array_key_exists( $key, self::$_press_to_baker ) ) {
        $baker_option = self::$_press_to_baker[$key];
        $options[$baker_option] = $value;
      }
    }

    return $options;
  }
}
