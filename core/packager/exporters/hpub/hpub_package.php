<?php
/**
* PressRoom packager: Hpub package
* Exporter Name: hpub
* Exporter Title: HTML Publication
*/

require_once( 'book_json.php' );
require_once( 'shelf_json.php' );

final class PR_Packager_HPUB_Package
{
  public function __construct() {

    $options = get_option( 'pr_settings' );
    $exporters = isset( $options['pr_enabled_exporters'] ) ? $options['pr_enabled_exporters'] : false;

    if( !$exporters || !in_array( 'hpub', $exporters ) ) {
      return;
    }

    add_action( 'pr_add_eproject_tab', array( $this, 'pr_add_option' ), 10, 2 );
    add_action( 'pr_add_edition_tab', array( $this, 'pr_add_option' ), 10, 3 );
    add_action( 'pr_packager_hpub_start', array( $this, 'hpub_start' ), 10, 2 );
    add_action( 'pr_packager_hpub', array( $this, 'hpub_run' ), 10, 4 );
    add_action( 'pr_packager_hpub_end', array( $this, 'hpub_end' ), 10, 2 );
  }


  /**
   * Create metabox and custom fields
   *
   * @param object &$metaboxes
   * @param int $item_id (it can be editorial project id or edition id);
   * @void
   */
  public function pr_add_option( &$metaboxes, $item_id, $edition = false ) {

    $hpub = new PR_Metabox( 'hpub', __( 'hpub', 'edition' ), 'normal', 'high', $item_id );
    if( $edition ) {
      $hpub->add_field( '_pr_hpub_override_eproject', __( 'Override Editorial Project settings', 'editorial_project' ), __( 'If enabled, will be used edition settings below', 'edition' ), 'checkbox', false );
    }

		$hpub->add_field( '_pr_default', '<h3>Visualization properties</h3><hr>', '', 'textnode', '' );
		$hpub->add_field( '_pr_orientation', __( 'Orientation', 'edition' ), __( 'The publication orientation.', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 'both', 'name' => __( "Both", 'edition' ) ),
				array( 'value' => 'portrait', 'name' => __( "Portrait", 'edition' ) ),
				array( 'value' => 'landscape', 'name' => __( "Landscape", 'edition' ) ),
			)
		) );
		$hpub->add_field( '_pr_zoomable', __( 'Zoomable', 'editorial_project' ), __( 'Enable pinch to zoom of the page.', 'edition' ), 'checkbox', false );
		$hpub->add_field( '_pr_body_bg_color', __( 'Body background color', 'edition' ), __( 'Background color to be shown before pages are loaded.', 'editorial_project' ), 'color', '#fff' );
		$hpub->add_field( '_pr_bg_view', __( 'View background color', 'edition' ), __( 'View color to be shown before pages are loaded.', 'editorial_project' ), 'color', '#fff' );
		$hpub->add_field( '_pr_background_image_portrait', __( 'Background image portrait', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in portrait mode.', 'editorial_project' ), 'file', '' );
		$hpub->add_field( '_pr_background_image_landscape', __( 'Background image landscape', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in landscape mode.', 'editorial_project' ), 'file', '' );
		$hpub->add_field( '_pr_page_numbers_color', __( 'Page numbers color', 'edition' ), __( 'Color for page numbers to be shown before pages are loaded.', 'editorial_project' ), 'color', '#ffffff' );
		$hpub->add_field( '_pr_page_numbers_alpha', __( 'Page number alpha', 'edition' ), __( 'Opacity for page numbers to be shown before pages are loaded. (min 0 => max 1)', 'editorial_project' ), 'decimal', 0.3 );
		$hpub->add_field( '_pr_page_screenshot', __( 'Page Screenshoot', 'edition' ), __( 'Path to a folder containing the pre-rendered pages screenshots.', 'editorial_project' ), 'text', '' );
		$hpub->add_field( '_pr_default', '<h3>Behaviour properties</h3><hr>', '', 'textnode', '' );

		$hpub->add_field( '_pr_start_at_page', __( 'Start at page', 'edition' ), __( 'Defines the starting page of the publication. If the number is negative, the publication starting at the end and with numbering reversed. ( Note: this setting works only the first time issue is opened )', 'editorial_project' ), 'number', 1 );
		$hpub->add_field( '_pr_rendering', __( 'Rendering type', 'edition' ), __( 'App rendering mode. See the page on <a target="_blank" href="https://github.com/Simbul/baker/wiki/Baker-rendering-modes">Baker rendering modes.</a>', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 'screenshots', 'name' => __( "Screenshots", 'edition' ) ),
				array( 'value' => 'three-cards', 'name' => __( "Three cards", 'edition' ) )
			)
		) );
		$hpub->add_field( '_pr_vertical_bounce', __( 'Vertical Bounce', 'edition' ), __( 'Bounce animation when vertical scrolling interaction reaches the end of a page.', 'editorial_project' ), 'checkbox', true );
		$hpub->add_field( '_pr_media_autoplay', __( 'Media autoplay', 'edition' ), __( 'Media should be played automatically when the page is loaded.', 'editorial_project' ), 'checkbox', true );
		$hpub->add_field( '_pr_vertical_pagination', __( 'Vertical pagination', 'edition' ), __( 'Vertical page scrolling should be paginated in the whole publication.', 'editorial_project' ), 'checkbox', false );
		$hpub->add_field( '_pr_page_turn_tap', __( 'Page turn tap', 'edition' ), __( 'Tap on the right (or left) side to go forward (or back) by one page.', 'editorial_project' ), 'checkbox', true );
		$hpub->add_field( '_pr_page_turn_swipe', __( 'Page turn swipe', 'edition' ), __( 'Swipe on the page to go forward (or back) by one page.', 'editorial_project' ), 'checkbox', true );

		$hpub->add_field( '_pr_default', '<h3>Toc properties</h3><hr>', '', 'textnode', '' );
		$hpub->add_field( '_pr_index_height', __( 'TOC height', 'edition' ), __( 'Height (in pixels) for the toc bar.', 'editorial_project' ), 'number', 150 );
		$hpub->add_field( '_pr_index_width', __( 'TOC width', 'edition' ), __( 'Width (in pixels) for the toc bar. When empty, the width is automatically set to the width of the page.', 'editorial_project' ), 'number', '' );
		$hpub->add_field( '_pr_index_bounce', __( 'TOC bounce', 'edition' ), __( 'Bounce effect when a scrolling interaction reaches the end of the page.', 'editorial_project' ), 'checkbox', false );

    array_push( $metaboxes, $hpub );
  }
  /**
   * Initial step of hpub packager
   * Generate toc
   *
   * @param  object $packager istance of packager class
   * @param  object $editorial_project
   * @void
   */
  public function hpub_start( $packager, $editorial_project ) {

    // Parse html of toc index.php file
    $packager->make_toc( $editorial_project, $packager->edition_dir );
  }

  /**
   * Hooked to posts foreach
   * Html post parsing and html files writing
   *
   * @param object $packager istance of packager class
   * @param object $post
   * @param object $editorial_project
   * @param string $parsed_html_post
   * @void
   */
  public function hpub_run( $packager, $post, $editorial_project, $parsed_html_post ) {

    if( $parsed_html_post ) {
      // Rewrite post url
      $parsed_html_post = $packager->rewrite_url( $parsed_html_post );

      if ( !$packager->save_html_file( $parsed_html_post, $post->post_title, $packager->edition_dir ) ) {
        PR_Packager::print_line( sprintf( __( 'Failed to save post file: %s', 'packager' ), $post->post_title ), 'error' );
        continue;
      }
    }
    else {
      do_action( 'pr_packager_run_hpub_' . $post->post_type, $post, $packager->edition_dir );
    }

  }

  /**
   * Hpub closure step
   * Saving attachments, creating book.json, creating hpub
   *
   * @param object $packager istance of packager class
   * @param object $editorial_project
   * @void
   */
  public function hpub_end( $packager, $editorial_project ) {

    $media_dir = PR_Utils::make_dir( $packager->edition_dir, PR_EDITION_MEDIA );
    if ( !$media_dir ) {
      PR_Packager::print_line( sprintf( __( 'Failed to create folder %s ', 'edition' ), $packager->edition_dir . DS . PR_EDITION_MEDIA ), 'error' );
      $packager->exit_on_error();
      return;
    }
    $packager->set_progress( 70, __( 'Saving edition attachments files', 'edition' ) );

    $packager->save_posts_attachments( $media_dir );
    $packager->set_progress( 78, __( 'Saving edition cover image', 'edition' ) );

    $packager->save_cover_image();
    $packager->set_progress( 80, __( 'Generating book json', 'edition' ) );

    $packager->set_package_date();
    $packager->set_progress( 90, __( 'Generating selected package type', 'edition' ) );

    if ( PR_Packager_Book_JSON::generate_book( $packager, $editorial_project->term_id ) ) {
      PR_Packager::print_line( __( 'Generated book.json', 'edition' ), 'success' );
    }
    else {
      PR_Packager::print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
      $packager->exit_on_error();
      return;
    }

    $hpub_package = self::build( $packager, $editorial_project );
    if ( $hpub_package ) {
      PR_Packager::print_line( sprintf( __( 'Generated hpub ', 'edition' ), $hpub_package ), 'success' );
    } else {
      PR_Packager::print_line( __( 'Failed to create hpub package ', 'edition' ), 'error' );
      $packager->exit_on_error();
      return;
    }

    if ( PR_Packager_Shelf_JSON::generate_shelf( $editorial_project ) ) {
      PR_Packager::print_line( sprintf( __( 'Generated shelf.json for editorial project: ', 'edition' ), $editorial_project->name ), 'success' );
    }
    else {
      PR_Packager::print_line( __( 'Failed to generate shelf.json ', 'edition' ), 'error' );
      $packager->exit_on_error();
      return;
    }
  }

  /**
   * Create zip file
   *
   * @param  object $packager
   * @param  object $editorial_project
   * @return string or boolean false
   */
  public static function build( $packager, $editorial_project ) {

    $filename = PR_HPUB_PATH . PR_Utils::sanitize_string ( $editorial_project->slug ) . '_' . $packager->edition_post->ID . '.hpub';
    if ( PR_Utils::create_zip_file( $packager->edition_dir, $filename, '' ) ) {

      return $filename;
    }
    return false;
  }

  /**
  * Save json string to file
  * @param  array $content
  * @param  string $filename
  * @param  string $path
  * @return boolean
  */
  public static function save_json_file( $content, $filename, $path ) {

    $encoded = json_encode( $content );
    $json_file = $path . DS . $filename;
    if ( file_put_contents( $json_file, $encoded ) ) {
      return true;
    }
    return false;
  }
}

$pr_packager_hpub_package = new PR_Packager_HPUB_Package;
