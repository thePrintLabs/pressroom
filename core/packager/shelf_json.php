<?php
/**
 * PressRoom packager: Shelf.json
 *
 */
final class PR_Packager_Shelf_JSON
{
  private static $_press_to_baker = array(
    'post_name'                 => 'name',
    'post_title'                => 'title',
    'post_excerpt'              => 'info',
    '_pr_package_date'          => 'date',
    '_pr_package_updated_date'  => 'updated_date',
  );

  /**
   * Get all editions belonging to the same editorial projects of the edition
   * @param  string $folder
   */
  public static function generate_shelf( $editorial_project ) {

    $args = array(
      'post_type'             => PR_EDITION,
      PR_EDITORIAL_PROJECT   => $editorial_project->slug,
      'post_status'           => 'publish',
      'posts_per_page'        => -1,
    );
    $edition_query = new WP_Query( $args );
    $press_options = array();
    self::$_press_to_baker['_pr_product_id_' . $editorial_project->term_id] = 'product_id';
    foreach ( $edition_query->posts as $edition_key => $edition ) {

      $press_options[$edition_key] = array( 'url' => PR_HPUB_URI . $editorial_project->slug . '_' . $edition->ID . '.hpub' );

      // Add the cover image into the edition options
      $edition_cover_id = get_post_thumbnail_id( $edition->ID );
      if ( $edition_cover_id ) {
         $edition_cover = wp_get_attachment_image_src( $edition_cover_id, 'thumbnail_size' );
         if ( $edition_cover ) {
            $press_options[$edition_key]['cover'] = $edition_cover[0];
         }
      }

      // Add only allowed values into the edition options
      foreach ( $edition as $key => $edition_attribute ) {

        if ( array_key_exists( $key, self::$_press_to_baker ) ) {
          $baker_option = self::$_press_to_baker[$key];
          $press_options[$edition_key][$baker_option] = $edition_attribute;
        }
      }

      // Add allowed custom fields values into the edition options
      $meta_fields = get_post_custom( $edition->ID );
      foreach ( $meta_fields as $meta_key => $meta_value ) {

        if ( array_key_exists( $meta_key, self::$_press_to_baker ) ) {
          $baker_option = self::$_press_to_baker[$meta_key];
          switch ( $meta_key ) {

            case '_pr_product_id_' . $editorial_project->term_id :
              if ( isset( $meta_value[0] ) &&
                !( isset( $meta_fields['_pr_edition_free'] ) && $meta_fields['_pr_edition_free'][0] == 1 ) ) {
                $press_options[$edition_key][$baker_option] = PR_Edition::get_bundle_id( $edition->ID, $editorial_project->term_id );
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

    if( !PR_Packager::save_json_file( $press_options, $editorial_project->slug . '_shelf.json', PR_SHELF_PATH ) ) {
      return false;
    }
    return true;
  }
}
