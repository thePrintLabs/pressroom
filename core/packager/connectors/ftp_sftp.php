<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php' );
require_once( ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php' );

class PR_Ftp_Sftp
{
  public $errors = null;
  protected $_connection;

  public function __construct() {

    add_action( 'pr_add_ftp_field', array( $this, 'pr_add_field' ), 10, 1 );
    $this->errors = new WP_Error();
  }

  public function connect( $params ) {

    if ( !defined( 'FS_CONNECT_TIMEOUT' ) ) {
      define( 'FS_CONNECT_TIMEOUT', 30 );
    }

    if ( !defined( 'FS_TIMEOUT' ) ) {
      define( 'FS_TIMEOUT', 30 );
    }

    foreach( $params as $key => $param ) {
      if( !$param ) {
        $this->errors->add( 'connect', sprintf( __( 'Missing params %1$s. Please fill it and retry.'), $key ) );
        return false;
      }
    }

    switch( $params['protocol'] ) {

      case 'ftp':
        if( !extension_loaded( 'sockets' ) || !function_exists( 'fsockopen' ) ) {
          $this->errors->add( 'connect', __( 'No sockets extension founds.' ) );
          return false;
        }
        $ftp = new WP_Filesystem_ftpsockets( $params );
        break;

      case 'sftp':
        if( !extension_loaded( 'ssh2' ) ) {
          $this->errors->add( 'connect', __( 'No ssh2 extension founds. Please install it.' ) );
          return false;
        }
        $ftp = new WP_Filesystem_SSH2( $params );
        break;
        default:
        break;
      }

    if( $ftp->connect() ) {
      $this->connection = $ftp;
      return true;
    }

    return $this->errors->add( 'connect', $ftp->errors->get_error_messages() );

  }

  public function recursive_copy( $source, $remote_path ) {

    if ( is_a( $this->connection, 'WP_Filesystem_ftpsockets' ) ) {
      if( $this->connection->ftp->mput( $source, $remote_path, true ) ) {
        return true;
      }
    }
    else if( is_a( $this->connection, 'WP_Filesystem_SSH2' ) ) {
      if( $this->ssh2_copy( $source, $remote_path ) ) {
        return true;
      }
    }

    return false;
  }

  public function ssh2_copy( $source, $remote_path ) {

    $files = PR_Utils::search_files( $source, '*', true);
    foreach( $files as $file ) {
      $this->connection->copy( $file, $remote_path, true );
    }
  }

  public function pr_add_field( $metabox ) {
    $metabox->add_field( '_pr_ftp_protocol', __( 'Transfer protocol', 'editorial_project' ), '', 'radio', '', array(
      'options' => array(
        array( 'value' => 'ftp', 'name' => __( "ftp", 'web_package' ) ),
        array( 'value' => 'sftp', 'name' => __( "sftp", 'web_package' ) ),
        array( 'value' => 'local', 'name' => __( "local filesystem", 'web_package' ) )
      )
    ));

    $metabox->add_field( '_pr_ftp_server', __( 'FTP Server / IP port', 'web_package' ), __( 'Server ip address and connection port', 'web_package' ), 'double_text', '' );
    $metabox->add_field( '_pr_ftp_user', __( 'FTP User Login', 'web_package' ), __( 'User for ftp connection', 'web_package' ), 'text', '' );
    $metabox->add_field( '_pr_ftp_password', __( 'FTP Password', 'web_package' ), __( 'Password for ftp connection', 'web_package' ), 'password', '' );
    $metabox->add_field( '_pr_ftp_destination_path', __( 'FTP Destination path', 'web_package' ), __( 'Ftp path for uploads', 'web_package' ), 'text', '' );
    $metabox->add_field( '_pr_test_connection', __( 'Test connection', 'web_package' ), '<button id="test-connection" class="button button-primary">Connect</button><span id="connection-result"></span>', 'custom_html', '' );
  }
}

$pr_ftp_sftp = new PR_Ftp_Sftp;
