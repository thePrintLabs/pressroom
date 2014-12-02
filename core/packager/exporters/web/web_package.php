<?php
/**
* PressRoom packager: Web package
*
*/
require_once( PR_PACKAGER_CONNECTORS_PATH . '/ftp_sftp.php' );

final class PR_Packager_Web_Package
{
  public function __construct() {

    add_action( 'pr_add_eproject_tab', array( $this, 'pr_add_option' ), 10, 2 );
    add_action( 'pr_add_edition_tab', array( $this, 'pr_add_option' ), 10, 2 );
    add_action( 'wp_ajax_test_ftp_connection', array( $this, 'test_ftp_connection' ) );

    add_action( 'pr_packager_make', array( $this, 'web_run' ), 10, 2 );
  }

  public function web_run() {
    //mi aggancio al packager e mi copio la cartell temporanea
  }

  public function pr_add_option( &$metaboxes, $item_id ) {

    $web = new PR_Metabox( 'web_metabox', __( 'web', 'web_package' ), 'normal', 'high', $item_id );

    $web->add_field( 'pr_container_theme', __( 'Container theme', 'web_package' ), __( 'Web viewer theme', 'edition' ), 'select', '', array(
      'options' => array(
        array( 'value' => 'standard', 'text' => __( "Standard Web Viewer", 'web_package' ) ),
        )
      )
    );

    do_action_ref_array( 'pr_add_ftp_field', array( &$web ) );

    array_push( $metaboxes, $web );
  }

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


}
$pr_packager_web_package = new PR_Packager_Web_Package;
