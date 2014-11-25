<?php
/**
* PressRoom packager: Hpub package
*
*/
final class PR_Packager_HPUB_Package
{
  public function __construct() {}

  public static function build( $edition_post_id, $editorial_project, $source_dir ) {

    $filename = PR_HPUB_PATH . PR_Utils::sanitize_string ( $editorial_project->slug ) . '_' . $edition_post_id . '.hpub';
    if ( PR_Utils::create_zip_file( $source_dir, $filename, '' ) ) {

      $meta_key = '_pr_edition_hpub_' . $editorial_project->term_id;
      if ( get_post_meta( $edition_post_id, $meta_key, true ) ) {
        update_post_meta( $edition_post_id, $meta_key, $filename );
      }
      else {
        add_post_meta( $edition_post_id, $meta_key, $filename, true );
      }
      return $filename;
    }
    return false;
  }
}
