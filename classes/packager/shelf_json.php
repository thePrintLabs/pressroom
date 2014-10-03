<?php
/**
* TPL packager: Book.json
*
*/
final class TPL_Packager_Shelf_JSON
{
   private static $_press_to_baker = array(
      'post_name'       => 'name',
      'post_title'      => 'title',
      'post_content'    => 'info',
      '_tpl_date'       => 'date',
      '_tpl_cover'      => 'cover',
      '_tpl_product_id' => 'product_id'
   );

   /**
    * Get all editions belonging to the same editorial projects of the edition
    * @param  string $folder
    */
   public static function generate_shelf( $edition_post ) {

      $press_options = array();
      $terms = wp_get_post_terms( $edition_post->ID, TPL_EDITORIAL_PROJECT );

      foreach ( $terms as $term ) {
         $args = array(
            'post_type'             => TPL_EDITION,
            TPL_EDITORIAL_PROJECT   => $term->slug,
            'post_status'           => 'publish',
            'posts_per_page'        => -1,
         );

         $edition_query = new WP_Query( $args );

         foreach ( $edition_query->posts as $edition_key => $edition ) {

            $press_options[$edition_key] = array( 'url' => TPL_HPUB_URI . TPL_Utils::sanitize_string( $edition->post_title . '.hpub' ) );

            foreach ( $edition as $key => $edition_attribute ) {

               if ( array_key_exists( $key, self::$_press_to_baker ) ) {
                  $baker_option = self::$_press_to_baker[$key];
                  $press_options[$edition_key][$baker_option] = $edition_attribute;
               }
            }

            $meta_fields = get_post_custom( $edition->ID );

            foreach ( $meta_fields as $meta_key => $meta_value ) {

               if ( array_key_exists( $meta_key, self::$_press_to_baker ) ) {

                  $baker_option = self::$_press_to_baker[$meta_key];

                  switch ( $meta_key ) {

                     case '_tpl_date':
                        if ( isset( $meta_value[0] ) ) {
                           $press_options[$edition_key][$baker_option] = date( 'Y-m-d H:s:i', strtotime( $meta_value[0] ) );
                        }
                        break;
                     case '_tpl_cover':
                        if ( isset( $meta_value[0] ) ) {
                           $cover = wp_get_attachment_url( $meta_value[0] );
                           $press_options[$edition_key][$baker_option] = $cover;
                        }
                        break;
                     case '_tpl_product_id':
                        if ( isset( $meta_value[0] ) ) {
                           $press_options[$edition_key][$baker_option] = $meta_value[0];
                           if ( isset( $meta_fields['_tpl_edition_free'] ) && $meta_fields['_tpl_edition_free'][0] == 1 ) {
                                 unset( $press_options[$edition_key][$baker_option] );
                           }
                        }
                        break;
                     default:
                        if ( isset( $meta_value[0] ) ) {
                           $press_options[$edition_key][$baker_option] = $meta_value[0];
                        }
                        break;
                  }
               }
            }
         }

         return TPL_Packager::save_json_file( $press_options, $term->slug . '_shelf.json', TPL_SHELF_DIR );
      }
   }
}
