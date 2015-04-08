<?php
/**
* PressRoom packager: Adobe DPS package
*/

define( "PR_ADPS_PATH", PR_UPLOAD_PATH . 'adps/' );
define( "PR_ADPS_MEDIA", 'Links/' );
define( "PR_ADPS_ASSETS", 'assets/' );

final class PR_Packager_ADPS_Package
{
  private $_sidecar_xml;
  private $_folio_dir;

  private $_settings = array(
    '_pr_adps_import_from_sidecar',
    '_pr_adps_is_flattened_stack',
    '_pr_adps_is_trusted_content',
    '_pr_adps_smooth_scrolling',
    '_pr_adps_article_access'
  );

  public function __construct() {

    add_action( 'pr_add_eproject_tab', [ $this, 'pr_add_option' ], 10, 2 );
    add_action( 'pr_add_edition_tab', [ $this, 'pr_add_option' ], 10, 3 );

    add_action( 'pr_packager_adps_start', [ $this, 'adps_start' ], 10, 2 );
    add_action( 'pr_packager_adps', [ $this, 'adps_run' ], 10, 4 );
    add_action( 'pr_packager_adps_end', [ $this, 'adps_end' ], 10, 2 );

    if ( !file_exists( PR_ADPS_PATH ) ) {
      PR_Utils::make_dir( PR_UPLOAD_PATH, 'adps' );
    }
  }

  /**
   * Initial step of Adobe DPS packager
   * Generate toc
   *
   * @param  object $packager istance of packager class
   * @param  object $editorial_project
   * @void
   */
  public function adps_start( $packager, $editorial_project ) {

    $this->_load_settings( $packager->edition_post->ID, $editorial_project->term_id );

    $this->_folio_dir = PR_Utils::make_dir( PR_ADPS_PATH, $editorial_project->slug . '_' . $packager->edition_post->ID );
    if ( !$this->_folio_dir ) {
      PR_Packager::print_line( sprintf( __( 'Failed to create folder: %s', 'packager' ), PR_ADPS_PATH . $this->_folio_dir ), 'error' );
      $packager->exit_on_error();
      return;
    }

    $this->_sidecar_xml = new XMLWriter();
    $this->_sidecar_xml->openURI( $this->_folio_dir . DS . 'sidecar.xml' );
    $this->_sidecar_xml->startDocument( '1.0', 'UTF-8', 'yes' );
    $this->_sidecar_xml->setIndent(4);
    $this->_sidecar_xml->startElement( 'sidecar' );
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
  public function adps_run( $packager, $post, $editorial_project, $parsed_html_post ) {

    $article_dir = PR_Utils::make_dir( $this->_folio_dir, PR_Utils::sanitize_string( $post->post_title ) );
    if ( !$article_dir ) {
      PR_Packager::print_line( sprintf( __( 'Failed to create folder: %s', 'packager' ), PR_Utils::sanitize_string( $post->post_title ) ), 'error' );
      $packager->exit_on_error();
      return;
    }

    if ( $parsed_html_post ) {
      // Rewrite post url
      $parsed_html_post = $packager->rewrite_url( $parsed_html_post, 'html', PR_ADPS_MEDIA );
      if ( $packager->save_html_file( $parsed_html_post, $post->post_title, $article_dir ) ) {

        $media_dir = PR_Utils::make_dir( $article_dir, PR_ADPS_MEDIA, false );
        if ( !$media_dir ) {
          PR_Packager::print_line( sprintf( __( 'Failed to create folder %s ', 'edition' ), $article_dir . DS . PR_ADPS_MEDIA ), 'error' );
          $packager->exit_on_error();
          return;
        }
        $packager->save_posts_attachments( $media_dir );

        $assets_dir = PR_Utils::make_dir( $article_dir, PR_ADPS_ASSETS, false );
        if ( !$assets_dir ) {
          PR_Packager::print_line( sprintf( __( 'Failed to create folder %s ', 'edition' ), $article_dir . DS . PR_ADPS_ASSETS ), 'error' );
          $packager->exit_on_error();
          return;
        }

        $edition_assets_dir = $packager->edition_dir . DS . 'assets';
        $copied_files = PR_Utils::recursive_copy( $edition_assets_dir, $assets_dir );
    		if ( is_array( $copied_files ) ) {
    			foreach ( $copied_files as $file ) {
            PR_Packager::print_line( sprintf( __( 'Error: Can\'t copy file %s ', 'edition' ), $file ), 'error' );
    			}
    			return false;
    		}
    		else {
          PR_Packager::print_line( sprintf( __( 'Copy assets folder with %s files ', 'edition' ), $copied_files ), 'success' );
    		}
      }
      else {
        PR_Packager::print_line( sprintf( __( 'Failed to save post file: %s', 'packager' ), $post->post_title ), 'error' );
        exit;
      }
    }
    else {
      do_action( 'pr_packager_run_adps_' . $post->post_type, $post, $article_dir );
    }

    $this->_add_sidecar_xml_entry( $post );
  }

  /**
   * Adobe Dps package closure step
   * Saving attachments, creating book.json, creating hpub
   *
   * @param object $packager istance of packager class
   * @param object $editorial_project
   * @void
   */
  public function adps_end( $packager, $editorial_project ) {

    $this->_sidecar_xml->endElement();  // sidecar tag
    $this->_sidecar_xml->endDocument();
    $this->_sidecar_xml->flush();

    // $media_dir = PR_Utils::make_dir( $packager->edition_dir, PR_EDITION_MEDIA );
    // if ( !$media_dir ) {
    //   PR_Packager::print_line( sprintf( __( 'Failed to create folder %s ', 'edition' ), $packager->edition_dir . DS . PR_EDITION_MEDIA ), 'error' );
    //   $packager->exit_on_error();
    //   return;
    // }
    // $packager->set_progress( 70, __( 'Saving edition attachments files', 'edition' ) );
    //
    // $packager->save_posts_attachments( $media_dir );
    // $packager->set_progress( 78, __( 'Saving edition cover image', 'edition' ) );
    //
    // $packager->save_cover_image();
    // $packager->set_progress( 80, __( 'Generating book json', 'edition' ) );
    //
    // $packager->set_package_date();
    // $packager->set_progress( 90, __( 'Generating selected package type', 'edition' ) );
    //
    // if ( PR_Packager_Book_JSON::generate_book( $packager, $editorial_project->term_id ) ) {
    //   PR_Packager::print_line( __( 'Generated book.json', 'edition' ), 'success' );
    // }
    // else {
    //   PR_Packager::print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
    //   $packager->exit_on_error();
    //   return;
    // }
    //
    // $hpub_package = self::build( $packager, $editorial_project );
    // if ( $hpub_package ) {
    //   PR_Packager::print_line( sprintf( __( 'Generated hpub ', 'edition' ), $hpub_package ), 'success' );
    // } else {
    //   PR_Packager::print_line( __( 'Failed to create hpub package ', 'edition' ), 'error' );
    //   $packager->exit_on_error();
    //   return;
    // }
    //
    // if ( PR_Packager_Shelf_JSON::generate_shelf( $editorial_project ) ) {
    //   PR_Packager::print_line( sprintf( __( 'Generated shelf.json for editorial project: ', 'edition' ), $editorial_project->name ), 'success' );
    // }
    // else {
    //   PR_Packager::print_line( __( 'Failed to generate shelf.json ', 'edition' ), 'error' );
    //   $packager->exit_on_error();
    //   return;
    // }
  }

  /**
   * Create metabox and custom fields
   *
   * @param object &$metaboxes
   * @param int $item_id (it can be editorial project id or edition id);
   * @void
   */
  public function pr_add_option( &$metaboxes, $item_id, $edition = false ) {

    $adps = new PR_Metabox( 'adps_metabox', __( 'Adobe DPS', 'adps_package' ), 'normal', 'high', $item_id );

    if( $edition ) {
      $adps->add_field( '_pr_adps_override_eproject', __( 'Override Editorial Project settings', 'editorial_project' ), __( 'If enabled, will be used edition settings below', 'edition' ), 'checkbox', false );
    }

    $adps->add_field( '_pr_adps_smooth_scrolling', __( 'Smooth scrolling', 'adps_package' ), '', 'select', '', [
      'options' => [
        [ 'value' => 'never', 'text' => __( "Never", 'adps_package' ) ],
        [ 'value' => 'always', 'text' => __( "Always", 'adps_package' ) ],
        [ 'value' => 'portrait', 'text' => __( "Portrait", 'adps_package' ) ],
        [ 'value' => 'landscape', 'text' => __( "Landscape", 'adps_package' ) ],
      ]
    ]);

    $adps->add_field( '_pr_adps_is_flattened_stack', __( 'Flattened stack', 'edition' ), __( 'Determines whether Horizontal Swipe Only is turned on', 'adps_package' ), 'checkbox', false );

    $adps->add_field( '_pr_adps_is_trusted_content', __( 'Trusted content', 'edition' ), __( 'Allow Access to Entitlement Information', 'adps_package' ), 'checkbox', false );

    $adps->add_field( '_pr_adps_article_access', __( 'Article access', 'adps_package' ), '', 'select', '', [
      'options' => [
        [ 'value' => 'free', 'text' => __( "Free", 'adps_package' ) ],
        [ 'value' => 'metered', 'text' => __( "Metered", 'adps_package' ) ],
        [ 'value' => 'protected', 'text' => __( "Protected", 'adps_package' ) ]
      ]
    ]);

    array_push( $metaboxes, $adps );
  }

  /**
   * Add an xml entry on sidecar xml file
   * @param object $post
   * @void
   */
  private function _add_sidecar_xml_entry( $post ) {

    if ( empty( $this->_settings ) ) {
      return false;
    }
    // Overwrite settings with those of the post
    foreach ( $this->_settings as $setting => $value ) {
      if ( !$value ) {
        $this->_settings[$setting] = get_post_meta( $post->ID, $setting, true );
      }
    }

    $this->_sidecar_xml->startElement( 'entry' );

    $this->_sidecar_xml->startElement( 'contentSource' );
    $this->_sidecar_xml->writeElement( 'articleName', PR_Utils::sanitize_string( $post->post_title ) );
    $this->_sidecar_xml->writeElement( 'sourceFormat', 'html' );
    $this->_sidecar_xml->writeElement( 'sourceFolder', $this->_folio_dir . DS . PR_Utils::sanitize_string( $post->post_title ) );

    $this->_sidecar_xml->endElement(); // contentSource tag

    $this->_sidecar_xml->writeElement( 'articleTitle', $post->post_title );
    $this->_sidecar_xml->writeElement( 'author', $post->post_author );
    $this->_sidecar_xml->writeElement( 'description', $post->post_excerpt );
    $this->_sidecar_xml->writeElement( 'isAd', $post->post_type === 'pr_ad_bundle' );

    $tags = wp_get_post_tags( $post->ID, array( 'fields' => 'names' ) );
    if ( $tags ) {
      $this->_sidecar_xml->writeElement( 'tags', implode(' ', $tags) );
    }

    $this->_sidecar_xml->writeElement( 'smoothScrolling', $this->_settings['_pr_adps_smooth_scrolling'] );
    $this->_sidecar_xml->writeElement( 'isFlattenedStack', $this->_settings['_pr_adps_is_flattened_stack'] );
    $this->_sidecar_xml->writeElement( 'isTrustedContent', $this->_settings['_pr_adps_is_trusted_content'] );
    $this->_sidecar_xml->writeElement( 'articleAccess', $this->_settings['_pr_adps_article_access'] );

    $this->_sidecar_xml->endElement(); // entry tag
  }

  /**
   * Check edition post settings else check for editorial project settings
   *
   * @param  int $edition_id
   * @param  int $eproject_id
   * @void
   */
  private function _load_settings( $edition_id, $eproject_id ) {

    // check whether using edition settings or editorial project settings
    $override = get_post_meta( $edition_id, '_pr_adps_override_eproject', true );

    $settings = array_flip( $this->_settings );
    foreach( $settings as $setting => $value ) {
      $option = false;
      if( $override ) {
        $option = get_post_meta( $edition_id, $setting, true );
      } else if( $eproject_id ) {
        $option = PR_Editorial_Project::get_config( $eproject_id, $setting);
      }
      $settings[$setting] = $option;
    }
    $this->_settings = $settings;
  }
}

$pr_packager_adps_package = new PR_Packager_ADPS_Package;
