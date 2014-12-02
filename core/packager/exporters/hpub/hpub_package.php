<?php
/**
* PressRoom packager: Hpub package
*
*/

require_once( 'book_json.php' );
require_once( 'shelf_json.php' );

final class PR_Packager_HPUB_Package
{
  public function __construct() {

    add_action( 'pr_packager_make', array( $this, 'hpub_run' ), 10, 2 );
  }

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

  public function hpub_run( $packager, $editorial_project ) {

    if ( PR_Packager_Book_JSON::generate_book( $packager->edition_post, $packager->linked_query, $packager->edition_dir, $packager->cover_image, $editorial_project->term_id ) ) {
      PR_Packager::print_line( __( 'Generated book.json', 'edition' ), 'success' );
    }
    else {
      PR_Packager::print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
      $packager->_exit_on_error();
      return;
    }

    $hpub_package = self::build( $packager->edition_post->ID, $editorial_project, $packager->edition_dir );
    if ( $hpub_package ) {
      PR_Packager::print_line( __( 'Generated hpub ', 'edition' ) . $hpub_package, 'success' );
    } else {
      PR_Packager::print_line( __( 'Failed to create hpub package ', 'edition' ), 'error' );
      $packager->_exit_on_error();
      return;
    }

    if ( PR_Packager_Shelf_JSON::generate_shelf( $editorial_project ) ) {
      PR_Packager::print_line( __( 'Generated shelf.json for editorial project: ', 'edition' ) . $editorial_project->name, 'success' );
    }
    else {
      PR_Packager::print_line( __( 'Failed to generate shelf.json ', 'edition' ), 'error' );
      $packager->_exit_on_error();
      return;
    }
  }
}
$pr_packager_hpub_package = new PR_Packager_HPUB_Package;
