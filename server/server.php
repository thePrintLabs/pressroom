<?php

class PR_Server
{
  public function __construct() {

    require_once( PR_SERVER_PATH . 'api.php' );
    require_once( PR_SERVER_PATH . 'shelf.php' );
    require_once( PR_SERVER_PATH . 'push.php' );
    $this->_load_connectors();
  }

  /**
   * Load plugin connectors
   *
   * @void
   */
  protected function _load_connectors() {

    if ( is_dir( PR_CONNECTORS_PATH ) ) {
      $files = PR_Utils::search_files( PR_CONNECTORS_PATH, 'php' );
      if ( !empty( $files ) ) {
        foreach ( $files as $file ) {
          require_once( $file );
        }
      }
    }
  }
}

/* instantiate the plugin class */
$pr_server = new PR_Server();
