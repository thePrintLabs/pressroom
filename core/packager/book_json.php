<?php
/**
* TPL packager: Book.json
*
*/
final class TPL_Packager_Book_JSON
{
   private static $_press_to_baker = array(
      'pr-orientation'       => 'orientation',
      'pr-zoomable'          => 'zoomable',
      'opt-color-background'  => '-baker-background',
      'pr-vertical-bounce' 	=> '-baker-vertical-bounce',
      'pr-index-bounce'      => '-baker-index-bounce',
      'pr-index-height'      => '-baker-index-height',
      'pr-media-autoplay'	 	=> '-baker-media-autoplay',
      '_pr_author'            => 'author',
      '_pr_creator'           => 'creator',
      '_pr_cover'             => 'cover',
      '_pr_date'              => 'date',
      'post_title'            => 'title',
   );

   /**
    * Get all options and html files and save them in the book.json
    * @param object $edition_post
    * @param object $linked_query
    * @param string $edition_dir
    * @param string $edition_cover_image
    * @void
    */
   public static function generate_book( $edition_post, $linked_query, $edition_dir, $edition_cover_image ) {

      $press_options = self::_get_pressroom_options( $edition_post, $edition_cover_image );

      foreach ( $linked_query->posts as $post ) {

         $post_title = TPL_Utils::sanitize_string( $post->post_title );

         if ( !has_action( 'pr_packager_generate_book_' . $post->post_type ) ) {

            if ( is_file( $edition_dir . DIRECTORY_SEPARATOR . $post_title . '.html' ) ) {
               $press_options['contents'][] = $post_title . '.html';
            }
            else {
               TPL_Packager::print_line( sprintf( __( 'Can\'t find file %s. It won\'t add to book.json ', 'edition' ), $edition_dir . DIRECTORY_SEPARATOR . $post_title . '.html' ), 'error' );
            }
         }
         else {
            $args = array( $press_options, $post, $edition_dir );
            do_action_ref_array( 'pr_packager_generate_book_' . $post->post_type, array( &$args ) );
            $press_options = $args[0];
         }
      }

      return TPL_Packager::save_json_file( $press_options, 'book.json', $edition_dir );
   }

   /**
    * Get pressroom edition configuration options
    * @param  boolean $shelf
    * @return array
    */
   protected static function _get_pressroom_options( $edition_post, $edition_cover_image ) {

      global $tpl_pressroom;

      $book_url = str_replace( array( 'http://', 'https://' ), 'book://', TPL_HPUB_URI );

      $options = array(
         'hpub'   => true,
         'url'    => $book_url . TPL_Utils::sanitize_string( $edition_post->post_title . '.hpub' )
      );

      foreach ( $tpl_pressroom->configs as $key => $option ) {

         if ( array_key_exists( $key, self::$_press_to_baker ) ) {
            $baker_option = self::$_press_to_baker[$key];
            switch ( $key ) {
               case 'pr-index-height':
                  $options[$baker_option] = (int)$option;
                  break;
               case 'pr-orientation':
                  $options[$baker_option] = strtolower($option);
                  break;
               case 'pr-zoomable':
               case 'pr-vertical-bounce':
               case 'pr-vertical-bounce':
               case 'pr-index-bounce':
               case 'pr-media-autoplay':
                  $options[$baker_option] = (bool)$option;
                  break;
               default:
                  $options[$baker_option] = ( $option == '0' || $option == '1' ? (int)$option : $option );
                  break;
            }
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
                  $options[$baker_option] = TPL_EDITION_MEDIA . $edition_cover_image;
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
