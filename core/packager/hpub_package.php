<?php
/**
* TPL packager: Hpub package
*
*/
final class TPL_Packager_HPUB_Package
{
  public function __construct() {}

  public static function build( $edition_post, $editorial_project, $dir ) {

    $filename = TPL_HPUB_PATH . TPL_Utils::sanitize_string ( $editorial_project->slug ) . '_' . $edition_post->ID . '.hpub';
    if ( TPL_Utils::create_zip_file( $dir, $filename, '' ) ) {

      add_post_meta($edition_post->ID, '_pr_hpub_' . $editorial_project->term_id, $filename, true);

      return $filename . '.hpub';
    }
    return false;
  }
}
