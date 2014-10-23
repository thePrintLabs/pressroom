<?php

class PR_Server
{
  public function __construct() {

    $this->_load_connectors();

    require_once( TPL_SERVER_PATH . 'api.php' );
    require_once( TPL_SERVER_PATH . 'shelf.php' );
    require_once( TPL_SERVER_PATH . 'issue.php' );
  }

  /**
   * Load plugin connectors
   *
   * @void
   */
  protected function _load_connectors() {

    if ( is_dir( TPL_CONNECTORS_PATH ) ) {
      $files = TPL_Utils::search_files( TPL_CONNECTORS_PATH, 'php' );
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
