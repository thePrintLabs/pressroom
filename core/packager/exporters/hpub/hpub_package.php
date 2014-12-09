<?php
/**
* PressRoom packager: Hpub package
*
*/

require_once( 'book_json.php' );
require_once( 'shelf_json.php' );

final class PR_Packager_HPUB_Package
{
  protected $_posts_attachments = array();

  public function __construct() {

    add_action( 'pr_packager_hpub_start', array( $this, 'hpub_start' ), 10, 2 );
    add_action( 'pr_packager_hpub', array( $this, 'hpub_run' ), 10, 4 );
    add_action( 'pr_packager_hpub_end', array( $this, 'hpub_end' ), 10, 2 );
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
    $toc = $this->_toc_parse( $editorial_project, $packager );
    if ( !$toc ) {
      PR_Packager::print_line( __( 'Failed to parse toc file', 'edition' ), 'error' );
      $this->_exit_on_error();
      return;
    }

    // Rewrite toc url
    $toc = $this->_rewrite_url( $toc, 'html', $packager->linked_query->posts );
    $packager->set_progress( 28, __( 'Saving toc file', 'edition' ) );

    // Save cover html file
    if ( $this->_save_html_file( $toc, 'toc', $packager->edition_dir ) ) {
      PR_Packager::print_line( __( 'Toc file correctly generated', 'edition' ), 'success' );
      $packager->set_progress( 30, __( 'Saving edition posts', 'edition' ) );
    }
    else {
      PR_Packager::print_line( __( 'Failed to save toc file', 'edition' ), 'error' );
      $packager->_exit_on_error();
      return;
    }
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
  public function hpub_run( $packager, $post, $editorial_project, $parsed_html_post) {

    // Rewrite post url
    $parsed_html_post = $this->_rewrite_url( $parsed_html_post, 'html', $packager->linked_query->posts );

    do_action( 'pr_packager_run_' . $post->post_type, $post, $packager->edition_dir );

    if ( !$this->_save_html_file( $parsed_html_post, $post->post_title, $packager->edition_dir ) ) {
      PR_Packager::print_line( __( 'Failed to save post file: ', 'edition' ) . $post->post_title, 'error' );
      continue;
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
      PR_Packager::print_line( __( 'Failed to create folder ', 'edition' ) . $packager->edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA, 'error' );
      $packager->_exit_on_error();
      return;
    }
    $packager->set_progress( 70, __( 'Saving edition attachments files', 'edition' ) );

    $this->_save_posts_attachments( $media_dir );
    $packager->set_progress( 78, __( 'Saving edition cover image', 'edition' ) );

    $packager->save_cover_image();
    $packager->set_progress( 80, __( 'Generating book json', 'edition' ) );

    $packager->set_package_date();
    $packager->set_progress( 90, __( 'Generating selected package type', 'edition' ) );

    if ( PR_Packager_Book_JSON::generate_book( $packager->edition_post, $packager->linked_query, $packager->edition_dir, $packager->edition_cover_image, $editorial_project->term_id ) ) {
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

  /**
   * Create zip file
   *
   * @param  int $edition_post_id
   * @param  object $editorial_project
   * @param  string $source_dir
   * @return string or boolean false
   */
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

  /**
  * Parse toc file
  *
  * @return string or boolean false
  */
  protected function _toc_parse( $editorial_project, $packager ) {

    $toc = PR_Theme::get_theme_toc( $packager->edition_post->ID );
    if ( !$toc ) {
      return false;
    }

    ob_start();
    $edition = $packager->edition_post;
    $editorial_project_id = $editorial_project->term_id;
    $pr_theme_url = PR_THEME::get_theme_uri( $packager->edition_post->ID );

    $posts = $packager->linked_query;
    $packager->add_functions_file();
    require( $toc );
    $output = ob_get_contents();
    ob_end_clean();

    return $output;
  }

  /**
  * Save the html output into file
  * @param  string $post
  * @param  boolean
  */
  protected function _save_html_file( $post, $filename, $edition_dir ) {

    return file_put_contents( $edition_dir . DIRECTORY_SEPARATOR . PR_Utils::sanitize_string( $filename ) . '.html', $post);
  }

  //SPOSTARE SU HPUB?
  /**
  * Get all url from the html string and replace with internal url of the package
  * @param  string $html
  * @param  string $ext  = 'html' extension file output
  * @return string or false
  */
  protected function _rewrite_url( $html, $extension = 'html', $linked_posts ) {

    if ( $html ) {

      $post_rewrite_urls = array();
      $urls = PR_Utils::extract_urls( $html );

      foreach ( $urls as $url ) {

        if ( strpos( $url, site_url() ) !== false ) {
          $post_id = url_to_postid( $url );
          if ( $post_id ) {

            foreach( $linked_posts as $post ) {

              if ( $post->ID == $post_id ) {
                $path = PR_Utils::sanitize_string( $post->post_title ) . '.' . $extension;
                $post_rewrite_urls[$url] = $path;
              }
            }
          }
          else {

            $attachment_id = $this->_get_attachment_from_url( $url );
            if ( $attachment_id ) {
              $info = pathinfo( $url );
              $filename = $info['basename'];
              $post_rewrite_urls[$url] = PR_EDITION_MEDIA . $filename;

              // Add attachments that will be downloaded
              $this->_posts_attachments[$filename] = $url;
            }
          }
        }
      }

      if ( !empty( $post_rewrite_urls ) ) {
        $html = str_replace( array_keys( $post_rewrite_urls ), $post_rewrite_urls, $html );
      }
    }

    return $html;
  }

  /**
  * Copy attachments into the package folder
  * @param  array $attachments
  * @param  string $media_dir path of the package folder
  * @void
  */
  protected function _save_posts_attachments( $media_dir ) {

    if ( !empty( $this->_posts_attachments ) ) {
      $attachments = array_unique( $this->_posts_attachments );
      foreach ( $attachments as $filename => $url ) {

        if ( copy( $url, $media_dir . DIRECTORY_SEPARATOR . $filename ) ) {
          PR_Packager::print_line( __( 'Copied ', 'edition' ) . $url, 'success' );
        }
        else {
          PR_Packager::print_line(__('Failed to copy ', 'edition') . $url, 'error' );
        }
      }
    }
  }

  /**
  * Get attachment ID by url
  * @param string $attachment_url
  * @return string or boolean false
  */
  protected function _get_attachment_from_url( $attachment_url ) {

    global $wpdb;
    $attachment_url = preg_replace( '/-[0-9]{1,4}x[0-9]{1,4}\.(jpg|jpeg|png|gif|bmp)$/i', '.$1', $attachment_url );
    $attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid RLIKE '%s' LIMIT 1;", $attachment_url ) );
    if ( $attachment ) {
      return $attachment[0];
    }
    else {
      return false;
    }
  }

}

$pr_packager_hpub_package = new PR_Packager_HPUB_Package;
