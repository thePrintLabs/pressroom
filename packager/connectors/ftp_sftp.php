<?php
error_reporting(E_ALL ^ E_DEPRECATED);
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-base.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ssh2.php';
require_once ABSPATH . 'wp-admin/includes/class-wp-filesystem-ftpsockets.php';

class PR_Ftp_Sftp
{
  public $errors = null;
  protected $_connection;

  public function __construct() {

    add_action( 'pr_add_web_field', array( $this, 'pr_add_field' ), 10, 1 );
    $this->errors = new WP_Error();

  }

  /**
   * Check protocol type and establishes a properly connection
   *
   * @param  array $params
   * @return boolean
   */
  public function connect( $params ) {

    if( !defined( 'FS_CHMOD_DIR' ) ) {
      define('FS_CHMOD_DIR', 644);
    }

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

      case 'local':
        return true;
        break;

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

    $this->errors->add( 'connect', $ftp->errors->get_error_messages() );
    return false;

  }

  /**
   * Copy local package dir to remote
   *
   * @param  string $source
   * @param  string $remote_path
   * @return bool
   */
  public function recursive_copy( $source, $remote_path ) {

    if ( is_a( $this->connection, 'WP_Filesystem_ftpsockets' ) ) {
      if( $this->connection->ftp->mput( $source, $remote_path, true ) ) {
        return true;
      }
    }
    else if( is_a( $this->connection, 'WP_Filesystem_SSH2' ) ) {
      $this->connection->mkdir( $remote_path . DS . basename( $source ) );
      if( $this->ssh2_copy( $source, $remote_path ) ) {
        return true;
      }
    }

    return false;
  }

  /**
   * Recursive copy from sftp protocol
   *
   * @param  string $source
   * @param  string $remote_path
   * @return bool
   */
  public function ssh2_copy( $source, $remote_path) {

    $source = str_replace( '\\', '/', realpath( $source ) );

    if ( is_dir( $source ) ) {
      $files = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $source ), RecursiveIteratorIterator::SELF_FIRST );
      foreach ( $files as $file ) {

        $info = pathinfo( $file );
        if ( !in_array( $info['basename'], PR_Utils::$excluded_files ) ) {
          $file = str_replace('\\', '/', realpath( $file ) );
          if ( is_dir( $file ) ) {
            if( $this->connection->mkdir( str_replace( realpath( PR_TMP_PATH ), $remote_path . DS,  $file ) ) ) {
              PR_Packager::print_line( sprintf( __( 'Folder %s  transfered  ', 'web_package' ), $info['basename'] ) , 'success' );
            }
            else {
              PR_Packager::print_line( sprintf( __( 'Fail to transfer folder %s', 'web_package' ), $info['basename'] ) , 'error' );
            }

          }
          elseif ( is_file( $file ) ) {
            if( $this->connection->put_contents( str_replace( realpath( PR_TMP_PATH ), $remote_path . DS, $file ), file_get_contents( $file ), true ) ) {
              PR_Packager::print_line( sprintf( __( 'File %s transfered  ', 'web_package' ), $info['basename'] ) , 'success' );
            }
            else {
              PR_Packager::print_line( sprintf( __( 'Fail to transfer file %s', 'web_package' ), $info['basename'] ) , 'error' );
            }
          }
        }
      }
    }
    elseif ( is_file( $source ) ) {
      $info = pathinfo( $source );
      if( $this->connection->put_contents( str_replace( realpath( PR_TMP_PATH ), $remote_path . DS, $file ), file_get_contents( $source ), true ) ) {
        PR_Packager::print_line( sprintf( __( 'File %s transfered', 'web_package' ), $info['basename'] ) , 'success' );
      }
      else {
        PR_Packager::print_line( sprintf( __( 'Fail to transfer file %s', 'web_package' ), $info['basename'] ) , 'error' );
      }
    }

    return true;
  }

  /**
   * add ftp fields in called metabox
   *
   * @param object $metabox
   * @void
   */
  public function pr_add_field( $metabox ) {
    $metabox->add_field( '_pr_ftp_protocol', __( 'Transfer protocol', 'editorial_project' ), '', 'radio', '', array(
      'options' => array(
        array( 'value' => 'local', 'name' => __( "local filesystem", 'web_package' ) ),
        array( 'value' => 'ftp', 'name' => __( "ftp", 'web_package' ) ),
        array( 'value' => 'sftp', 'name' => __( "sftp", 'web_package' ) ),
      )
    ));

    $metabox->add_field( '_pr_local_path', __( 'Local path', 'web_package' ), __( 'The path where the files will be created', 'web_package' ), 'text', PR_WEB_PATH );
    $metabox->add_field( '_pr_ftp_server', __( 'FTP Server / IP port', 'web_package' ), __( 'Server ip address and connection port', 'web_package' ), 'double_text', '' );
    $metabox->add_field( '_pr_ftp_user', __( 'FTP User Login', 'web_package' ), __( 'User for ftp connection', 'web_package' ), 'text', '' );
    $metabox->add_field( '_pr_ftp_password', __( 'FTP Password', 'web_package' ), __( 'Password for ftp connection', 'web_package' ), 'password', '' );
    $metabox->add_field( '_pr_ftp_destination_path', __( 'FTP Destination path', 'web_package' ), __( 'Ftp path for uploads', 'web_package' ), 'text', '' );
    $metabox->add_field( '_pr_test_connection', __( 'Test connection', 'web_package' ), '<button id="test-connection" class="button button-primary">Connect</button><span id="connection-result"></span>', 'custom_html', '' );
  }
}

$pr_ftp_sftp = new PR_Ftp_Sftp;
