<?php
/**
* PressRoom packager: Web package
*
*/

require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php' );

final class PR_Packager_Web_Package
{
  public function __construct() {

    add_action( 'pr_add_eproject_tab', array( $this, 'pr_add_option' ), 10, 2 );
    add_action( 'wp_ajax_test_ftp_connection', array( $this, 'test_ftp_connection' ) );
  }

  public function pr_add_option( &$metaboxes, $term_id ) {

    $web = new PR_Metabox( 'web_metabox', __( 'web', 'web_package' ), 'normal', 'high', $term_id );

    $web->add_field( 'pr_container_theme', __( 'Container theme', 'web_package' ), __( 'Web viewer theme', 'edition' ), 'select', '', array(
      'options' => array(
        array( 'value' => 'standard', 'text' => __( "Standard Web Viewer", 'web_package' ) ),
        )
      )
    );
    $web->add_field( '_pr_ftp_protocol', __( 'Transfer protocol', 'editorial_project' ), '', 'radio', '', array(
      'options' => array(
        array( 'value' => 'ftp', 'name' => __( "ftp", 'web_package' ) ),
        array( 'value' => 'sftp', 'name' => __( "sftp", 'web_package' ) ),
        array( 'value' => 'local', 'name' => __( "local filesystem", 'web_package' ) )
        )
      )
    );

    $web->add_field( '_pr_ftp_server', __( 'FTP Server / IP port', 'web_package' ), __( 'Server ip address and connection port', 'web_package' ), 'double_text', '' );
    $web->add_field( '_pr_ftp_user', __( 'FTP User Login', 'web_package' ), __( 'User for ftp connection', 'web_package' ), 'text', '' );
    $web->add_field( '_pr_ftp_password', __( 'FTP Password', 'web_package' ), __( 'Password for ftp connection', 'web_package' ), 'password', '' );
    $web->add_field( '_pr_ftp_destination_path', __( 'FTP Destination path', 'web_package' ), __( 'Ftp path for uploads', 'web_package' ), 'text', '' );
    $web->add_field( '_pr_test_connection', __( 'Test connection', 'web_package' ), '<button id="test-connection" class="button button-primary">Connect</button><span id="connection-result"></span>', 'custom_html', '' );

    array_push( $metaboxes, $web );
  }

  public function test_ftp_connection() {

    define('FS_CONNECT_TIMEOUT', 30);
    define('FS_TIMEOUT', 30);
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

    foreach( $params as $key => $param ) {
      if( !$param ) {
        wp_send_json_error( array( 'message'=> "Missing params $key. Please fill it and retry.", 'class'=>'failure' ) );
        exit;
      }
    }

    switch( $protocol ) {

      case 'ftp':
        if( !extension_loaded('sockets') || !function_exists('fsockopen') ) {
          wp_send_json_error( array( 'message'=> 'No sockets extension founds.', 'class'=>'failure' ) );
          exit;
        }
        $ftp = new WP_Filesystem_ftpsockets( $params );
        break;

      case 'sftp':
        if( !extension_loaded('ssh2') ) {
          wp_send_json_error( array( 'message'=> 'No ssh2 extension founds. Please install it.', 'class'=>'failure' ) );
          exit;
        }
        $ftp = new WP_Filesystem_SSH2( $params );
        break;
      default:
        break;
    }

    if( $ftp->connect() ) {
      wp_send_json_success( array( 'message'=> 'Connection successfully', 'class'=>'success' ) );
      exit;
    }
    else {
      wp_send_json_error( array( 'message'=> $ftp->errors->get_error_messages(), 'class'=>'failure' ) );
      exit;
    }

    exit;
  }


}
$pr_packager_web_package = new PR_Packager_Web_Package;
