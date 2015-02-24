<?php
/**
* PressRoom packager: Web package
*/
require_once( PR_PACKAGER_CONNECTORS_PATH . '/ftp_sftp.php' );

final class PR_Packager_Web_Package
{

  public $pstgs = array();
  public $root_folder;

  public function __construct() {

    add_action( 'pr_add_eproject_tab', array( $this, 'pr_add_option' ), 10, 2 );
    add_action( 'pr_add_edition_tab', array( $this, 'pr_add_option' ), 10, 3 );
    add_action( 'wp_ajax_test_ftp_connection', array( $this, 'test_ftp_connection' ) );

    // packager hooks
    add_action( 'pr_packager_web_start', array( $this, 'web_packager_start' ), 10, 2 );
    add_action( 'pr_packager_web', array( $this, 'web_packager_run' ), 10, 4 );
    add_action( 'pr_packager_web_end', array( $this, 'web_packager_end' ), 10, 2 );
    add_action( 'pr_web_toc_rewrite_url', array( $this, 'rewrite_url' ), 10, 2 );
  }

  /**
   * Check edition post settings else check for editorial project settings
   *
   * @param  int $edition_id
   * @param  int $eproject_id
   * @void
   */
  public function load_settings( $edition_id, $eproject_id ) {

    $settings = array(
      '_pr_container_theme',
      '_pr_ftp_protocol',
      '_pr_local_path',
      '_pr_ftp_server',
      '_pr_ftp_user',
      '_pr_ftp_password',
      '_pr_ftp_destination_path',
      '_pr_index_height'
    );

    if( !$settings ) {
      return false;
    }

    // check whether using edition settings or editorial project settings
    $override = get_post_meta( $edition_id, '_pr_web_override_eproject', true );

    foreach( $settings as $setting ) {

      if( $override ) {
        $option = get_post_meta( $edition_id, $setting, true );
      }
      else if( $eproject_id ) {
        $option = PR_Editorial_Project::get_config( $eproject_id, $setting);
      }
      if( $option ) {
        $this->pstgs[$setting] = $option;
      }
    }
  }

  /**
   * Load settings and create toc.
   *
   * @param  object $packager
   * @param  object $editorial_project
   * @void
   */
  public function web_packager_start( $packager, $editorial_project ) {

    $this->load_settings( $packager->edition_post->ID, $editorial_project->term_id );

    $this->root_folder = $packager->edition_dir;

    if( isset( $this->pstgs['_pr_container_theme'] ) && $this->pstgs['_pr_container_theme'] != "no-container" ) {
      $this->_get_container( $packager->edition_dir );
      $packager->edition_dir = $packager->edition_dir . DS . 'contents';
    }

    $packager->make_toc( $editorial_project, $packager->edition_dir, "toc" );
  }

  /**
   * Rewrite urls from html string and save file.
   *
   * @param  object $packager
   * @param  object $post
   * @param  object $editorial_project
   * @param  string $parsed_html_post
   * @void
   */
  public function web_packager_run( $packager, $post, $editorial_project, $parsed_html_post ) {

    if( $parsed_html_post ) {

      // Rewrite post url
      $container = isset( $this->pstgs['_pr_container_theme'] ) && $this->pstgs['_pr_container_theme'] != "no-container" ? true : false;
      if( $container ) {
        self::rewrite_url( $packager, $parsed_html_post );
      }
      else {
        $parsed_html_post = $packager->rewrite_url( $parsed_html_post );
      }

      // Create index.html file for no-container web package
      if( $packager->linked_query->posts[0]->post_title == $post->post_title && !$container ) {
        $post_title = "index";
      }
      else {
        $post_title = $post->post_title;
      }

      if ( !$packager->save_html_file( $parsed_html_post, $post_title, $packager->edition_dir ) ) {
        PR_Packager::print_line( sprintf( __( 'Failed to save post file: %s ', 'packager' ), $post->post_title ), 'error' );
        continue;
      }
    }
    else {
      // custom behaviour for extensions
      do_action( 'pr_packager_run_web_' . $post->post_type, $post, $packager->edition_dir, $packager );
    }
  }

  /**
   * Replace reader shortcode with parsed post,
   * save attachments,
   * set package date and close the package.
   *
   * @param  object $packager
   * @param  object $editorial_project
   * @void
   */
  public function web_packager_end( $packager, $editorial_project ) {

    if( isset( $this->pstgs['_pr_container_theme'] ) && $this->pstgs['_pr_container_theme'] != "no-container" ) {
      $this->_shortcode_replace( $packager );
    }

    $media_dir = PR_Utils::make_dir( $packager->edition_dir, PR_EDITION_MEDIA );

    if ( !$media_dir ) {
      PR_Packager::print_line( sprintf( __( 'Failed to create folder ', 'web_package' ), $packager->edition_dir . DS . PR_EDITION_MEDIA ), 'error' );
      $packager->exit_on_error();
      return;
    }

    $packager->set_progress( 70, __( 'Saving edition attachments files', 'web_package' ) );

    $packager->save_posts_attachments( $media_dir );
    $packager->set_progress( 78, __( 'Saving edition cover image', 'web_package' ) );

    $packager->save_cover_image();
    $packager->set_progress( 80, __( 'Generating book json', 'web_package' ) );

    $packager->set_package_date();
    $packager->set_progress( 90, __( 'Generating web package', 'web_package' ) );

    $this->_web_write( $packager, $editorial_project );

    PR_Utils::remove_dir( $this->root_folder );

  }

  /**
   * Create metabox and custom fields
   *
   * @param object &$metaboxes
   * @param int $item_id (it can be editorial project id or edition id);
   * @void
   */
  public function pr_add_option( &$metaboxes, $item_id, $edition = false ) {

    $web = new PR_Metabox( 'web_metabox', __( 'web', 'web_package' ), 'normal', 'high', $item_id );

    if( $edition ) {
      $web->add_field( '_pr_web_override_eproject', __( 'Override Editorial Project settings', 'editorial_project' ), __( 'If enabled, will be used edition settings below', 'edition' ), 'checkbox', false );
    }

    $web->add_field( '_pr_container_theme', __( 'Container theme', 'web_package' ), __( 'Web viewer theme', 'web_package' ), 'select', '', array(
      'options' => array(
        array( 'value' => 'standard', 'text' => __( "Standard Web Viewer", 'web_package' ) ),
        array( 'value' => 'no-container', 'text' => __( "No container", 'web_package' ) ),
        )
      )
    );

    do_action_ref_array( 'pr_add_web_field', array( &$web ) );

    array_push( $metaboxes, $web );
  }

  /**
   * Test ftp connection
   *
   * @void
   */
  public function test_ftp_connection() {

    $server = isset( $_POST['server'] ) ? $_POST['server'] : false ;
    $port = isset( $_POST['port'] ) ? $_POST['port'] : false ;
    $base = isset( $_POST['base'] ) ? $_POST['base'] : false ;
    $username = isset( $_POST['user'] ) ? $_POST['user'] : false ;
    $password = isset( $_POST['password'] ) ? $_POST['password'] : false ;
    $protocol = isset( $_POST['protocol'] ) ? $_POST['protocol'] : false ;

    $params = array(
      "hostname"  => $server,
      "base"      => $base,
      "port"      => (int) $port,
      "username"  => $username,
      "password"  => $password,
      "protocol"  => $protocol,
    );

    $ftp = new PR_Ftp_Sftp();

    if( $ftp->connect( $params ) ) {
      wp_send_json_success( array( 'message'=> 'Connection successfully', 'class'=>'success' ) );
      exit;
    }
    else {
      wp_send_json_error( array( 'message'=> $ftp->errors->get_error_messages(), 'class'=>'failure' ) );
      exit;
    }

    exit;
  }

  /**
   * Copy web reader in the package
   *
   * @param  string $dir
   * @void
   */
  protected function _get_container( $dir ) {

    PR_Utils::recursive_copy( PR_PACKAGER_EXPORTERS_PATH . 'web' . DS . 'reader', $dir);
  }

  /**
   * Replace shortcode reader index.html with posts iframes
   *
   * @param  object $packager
   * @void
   */
  protected function _shortcode_replace( $packager ) {


    $toc_full = isset( $this->pstgs['_pr_index_height'] ) ? false : true;
    if( $toc_full ) {
      $html = file_get_contents( $packager->edition_dir  . DS . '../' . 'index_full_toc.html');
    }
    else {
      $html = file_get_contents( $packager->edition_dir  . DS . '../' . 'index.html');
    }

    $replace = "";
    foreach( $packager->linked_query->posts as $post ) {


      $src = 'contents/'. PR_Utils::sanitize_string( $post->post_title ) .'.html';

      if( has_action( "pr_packager_shortcode_{$packager->package_type}_{$post->post_type}" ) ) {
        do_action_ref_array( "pr_packager_shortcode_{$packager->package_type}_{$post->post_type}", array( $post, &$src ) );
      }

      $replace.= '<div class="swiper-slide" data-hash="item-'. $post->ID .'">
      <iframe height="100%" width="100%" frameborder="0" src="' . $src . '"></iframe>
      </div>';
    }

    $html = str_replace( '[EDITION_POSTS]', $replace, $html );
    $html = str_replace( '[TOC_HEIGHT]', isset( $this->pstgs['_pr_index_height'] ) ? $this->pstgs['_pr_index_height'] : '', $html );

    file_put_contents( $packager->edition_dir . DS . '../' . 'index.html' , $html );
  }

  /**
   * Check transfer protocol and transfer files
   *
   * @param  object $packager
   * @param  object $editorial_project
   * @void
   */
  protected function _web_write( $packager, $editorial_project ) {

    if(!isset($this->pstgs['_pr_ftp_protocol'])) {
      PR_Packager::print_line( __( 'Missing connetion protocol parameter', 'web_package' ), 'error' );
      return false;
    }

    switch( $this->pstgs['_pr_ftp_protocol'] ) {
      case 'local':
        $package_name = PR_Utils::sanitize_string ( $editorial_project->slug ) . '_' . $packager->edition_post->ID;
        $destination = isset( $this->pstgs['_pr_local_path'] ) ? $this->pstgs['_pr_local_path']  : PR_WEB_PATH ;
        if( file_exists( $destination ) ) {
          PR_Utils::recursive_copy( $this->root_folder, $destination . DS . $package_name );
        }
        else {
          PR_Packager::print_line( sprintf( __( 'Local path <i>%s</i> does not exist. Can\'t create package.', 'web_package' ), $destination ), 'error' );
          return false;
        }

        $filename = PR_WEB_PATH . $package_name . '.zip';

        if ( PR_Utils::create_zip_file( $this->root_folder, $filename, '' ) ) {
          $index_path = PR_WEB_PATH . $package_name . DS .'index.html';
          $index_uri = PR_WEB_URI . $package_name . DS .'index.html';

          if( file_exists( $index_path ) ) {
            PR_Packager::print_line( __( 'Package created. You can see it <a href="'. $index_uri .'">there</a> or <a href="'. PR_WEB_URI . $package_name . '.zip">download</a>', 'web_package' ), 'success' );
          }
          else {
            PR_Packager::print_line( __( 'Package created. You can download it <a href="'. PR_WEB_URI . $package_name . '.zip">there</a>', 'web_package' ), 'success' );
          }

        }
        break;
      case 'ftp':
      case 'sftp':
        $ftp = new PR_Ftp_Sftp();

        $params = array(
          "hostname"  => isset( $this->pstgs['_pr_ftp_server'][0] ) ? $this->pstgs['_pr_ftp_server'][0] : '',
          "port"      => isset( $this->pstgs['_pr_ftp_server'][1] ) ? (int) $this->pstgs['_pr_ftp_server'][1] : '',
          "base"      => isset( $this->pstgs['_pr_ftp_destination_path'] ) ? $this->pstgs['_pr_ftp_destination_path'] : '',
          "username"  => isset( $this->pstgs['_pr_ftp_user'] ) ? $this->pstgs['_pr_ftp_user'] : '',
          "password"  => isset( $this->pstgs['_pr_ftp_password'] ) ? $this->pstgs['_pr_ftp_password'] : '',
          "protocol"  => isset( $this->pstgs['_pr_ftp_protocol'] ) ? $this->pstgs['_pr_ftp_protocol'] : '',
        );

        if( $ftp->connect( $params ) ) {
          PR_Packager::print_line( __( 'Ftp connection successfull  ', 'web_package' ) , 'success' );
          PR_Packager::print_line( __( 'Start transfer', 'web_package' ) , 'success' );
          if( $ftp->recursive_copy( $this->root_folder, $this->pstgs['_pr_ftp_destination_path'] ) ) {
            PR_Packager::print_line( __( 'Transfer complete', 'web_package' ), 'success' );
          }
          else {
            PR_Packager::print_line( __( 'Error during transfer', 'web_package' ), 'error' );
          }
        }
        else {
          $error = $ftp->errors->get_error_message('connect');
          PR_Packager::print_line( sprintf( __( 'Failed to connect. More details: %s ', 'web_package' ) ,( is_array( $error) ? $error[0] : $error ) ) , 'error' );
          $packager->exit_on_error();
          exit;

        }
        break;
    }
  }

  /**
  * Get all url from the html string and replace with internal url of the package
  *
  * @param  object $packager
  * @param  string $html
  * @param  string $extension extension file output
  * @return string or false
  */
  public static function rewrite_url( $packager, &$html, $extension = 'html' ) {

    if ( $html ) {
      $linked_query = $packager->linked_query;
      $post_rewrite_urls = array();
      $urls = PR_Utils::extract_urls( $html );

      foreach ( $urls as $url ) {
        if ( strpos( $url, site_url() ) !== false || strpos( $url, home_url() ) !== false ) {
          $post_id = url_to_postid( $url );
          if ( $post_id ) {
            foreach( $linked_query->posts as $post ) {
              if ( $post->ID == $post_id ) {
                $html = str_replace( $url, '../index.html#toc-' . $post_id, $html );
                $html = preg_replace("/<a(.*?)>/", "<a$1 target=\"_parent\">", $html);
              }
            }
          }
        }
      }
    }
  }
}

$pr_packager_web_package = new PR_Packager_Web_Package;
