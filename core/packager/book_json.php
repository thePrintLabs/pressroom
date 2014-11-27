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
   * @param object $edition_post
   * @param object $linked_query
   * @param string $edition_dir
   * @param string $edition_cover_image
   * @void
   */
  public static function generate_book( $edition_post, $linked_query, $edition_dir, $edition_cover_image, $term_id ) {

    $press_options = self::_get_pressroom_options( $edition_post, $edition_dir, $edition_cover_image, $term_id );
    foreach ( $linked_query->posts as $post ) {
      $page_name = PR_Utils::sanitize_string( $post->post_title );
      $page_path = $edition_dir . DIRECTORY_SEPARATOR . $page_name . '.html';

      $press_options['sharing_urls'][] = pr_get_sharing_url( $post->ID );
      $press_options['titles'][] = $post->post_title;

      if ( is_file( $page_path ) ) {
        $press_options['contents'][] = $page_name . '.html';
      }
      else {
         PR_Packager::print_line( sprintf( __( 'Can\'t find file %s. It won\'t add to book.json ', 'edition' ), $page_path ), 'error' );
      }

      do_action_ref_array( 'pr_packager_generate_book', array( &$press_options, $post, $edition_dir ) );
    }

    if ( !empty( $press_options['contents'] ) ) {
      $press_options['contents'] = array_values( $press_options['contents'] );
    }

    if ( !empty( $press_options['sharing_urls'] ) ) {
      $press_options['sharing_urls'] = array_values( $press_options['sharing_urls'] );
    }

    return PR_Packager::save_json_file( $press_options, 'book.json', $edition_dir );
  }

   /**
    * Get pressroom edition configuration options
    *
    * @param  boolean $shelf
    * @return array
    */
   protected static function _get_pressroom_options( $edition_post, $edition_dir, $edition_cover_image, $term_id ) {

      global $tpl_pressroom;

      $book_url = str_replace( array( 'http://', 'https://' ), 'book://', PR_HPUB_URI );
      $hpub_url = str_replace( PR_HPUB_PATH, $book_url, get_post_meta( $edition_post->ID, '_pr_edition_hpub_' . $term_id, true ) );

      $options = array(
         'hpub'   => true,
         'url'    => $hpub_url
      );

      $configs = get_option( 'taxonomy_term_' . $term_id );

      if ( !$configs ) {
        return $options;
      }

      foreach ( self::$_press_to_baker as $key => $baker_option ) {

          $option = isset( $configs[$key] ) ? $configs[$key] : '';

          switch ( $key ) {
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
                copy( $media, $edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA . $path );
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

      foreach ( $edition_post as $key => $value ) {

         if ( array_key_exists( $key, self::$_press_to_baker ) ) {
            $baker_option = self::$_press_to_baker[$key];
            $options[$baker_option] = $value;
         }
      }

      $edition_meta = get_post_custom( $edition_post->ID );
      foreach ( $edition_meta as $meta_key => $meta_value ) {

         if ( array_key_exists( $meta_key, self::$_press_to_baker ) ) {
            $baker_option = self::$_press_to_baker[$meta_key];
            switch ( $meta_key ) {
               case '_pr_cover':
                  $options[$baker_option] = PR_EDITION_MEDIA . $edition_cover_image;
                  break;
               case '_pr_author':
               case '_pr_creator':
                  if ( isset( $meta_value[0] ) && !empty( $meta_value[0] ) ) {
                     $authors = explode( ',', $meta_value[0] );
                     foreach ( $authors as $author ) {
                        $options[$baker_option][] = $author;
                     }
                  }
                  break;
               default:
                  if ( isset( $meta_value[0] ) ) {
                     $options[$baker_option] = $meta_value[0];
                  }
                  break;
            }
         }
      }
      return $options;
   }
}
