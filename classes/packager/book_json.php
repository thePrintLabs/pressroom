<?php
/**
* TPL packager: Book.json
*
*/
abstract class TPL_Packager_Book_JSON
{
   private $_press_to_baker = array(
      'tpl-orientation'       => 'orientation',
      'tpl-zoomable'          => 'zoomable',
      'opt-color-background'  => '-baker-background',
      'tpl-vertical-bounce' 	=> '-baker-vertical-bounce',
      'tpl-index-bounce'      => '-baker-index-bounce',
      'tpl-index-height'      => '-baker-index-height',
      'tpl-media-autoplay'	 	=> '-baker-media-autoplay',
      '_tpl_author'           => 'author',
      '_tpl_creator'          => 'creator',
      '_tpl_cover'            => 'cover',
      '_tpl_date'             => 'date',
      'post_title'            => 'title',
   );

   /**
    * Get all options and html files and save them in the book.json
    *
    * @void
    */
   public function generate_book_json() {

      $press_options = $this->_get_pressroom_options();

      foreach ( $this->_linked_query->posts as $post ) {

         $post_title = TPL_Utils::parse_string( $post->post_title );

         if ( $post->post_type == 'post' || !has_action( 'packager_bookjson_hook_' . $post->post_type ) ) {

            if ( is_file( $this->_edition_folder . DIRECTORY_SEPARATOR . $post_title . '.html' ) ) {
               $press_options['contents'][] = $post_title . '.html';
            }
            else {
               $this->_print_line( sprintf( __( 'Can\'t find file %s. It won\'t add to book.json ', 'edition' ), $this->_edition_folder . $post_title . '.html' ), 'error' );
            }
         }
         else {
            do_action( 'packager_bookjson_hook_' . $post->post_type, $post, $post_title, $this->edition_folder );
         }
      }

      if ( $this->_save_json_file( $press_options, 'book.json', $this->_edition_folder ) ) {
         $this->_print_line( __( 'Created book.json ', 'edition' ), 'success' );
      }
      else {
         $this->_print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
      }
   }

   /**
    * Get pressroom edition configuration options
    * @param  boolean $shelf
    * @return array
    */
   protected function _get_pressroom_options( $shelf = false ) {

      global $tpl_pressroom;

      $book_url = TPL_HPUB_URI;
      if ( !$shelf ) {
         $book_url = str_replace( array( 'http://', 'https://' ), 'book://', $book_url );
      }

      $options = array(
         'hpub'   => true,
         'url'    => $book_url . TPL_Utils::parse_string( $this->_edition_post->post_title . '.hpub' )
      );

      foreach ( $tpl_pressroom->configs as $key => $option ) {

         if ( array_key_exists( $key, $this->_press_to_baker ) ) {
            $baker_option = $this->_press_to_baker[$key];
            switch ( $key ) {
               case 'tpl-index-height':
                  $options[$baker_option] = (int)$option;
                  break;
               case 'tpl-orientation':
                  $options[$baker_option] = strtolower($option);
                  break;
               case 'tpl-zoomable':
               case 'tpl-vertical-bounce':
               case 'tpl-vertical-bounce':
               case 'tpl-index-bounce':
               case 'tpl-media-autoplay':
                  $options[$baker_option] = (bool)$option;
                  break;
               default:
                  $options[$baker_option] = ( $option == '0' || $option == '1' ? (int)$option : $option );
                  break;
            }
         }
      }

      foreach ( $this->_edition_post as $key => $value ) {

         if ( array_key_exists( $key, $this->_press_to_baker ) ) {
            $baker_option = $this->_press_to_baker[$key];
            $options[$baker_option] = $value;
         }
      }

      $edition_meta = get_post_custom( $this->_edition_post->ID );
      foreach ( $edition_meta as $meta_key => $meta_value ) {

         if ( array_key_exists( $meta_key, $this->_press_to_baker ) ) {
            $baker_option = $this->_press_to_baker[$meta_key];
            switch ( $meta_key ) {
               case '_tpl_cover':
                  $options[$baker_option] = TPL_EDITION_MEDIA . $this->_edition_cover_image;
                  break;
               case '_tpl_author':
               case '_tpl_creator':
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
