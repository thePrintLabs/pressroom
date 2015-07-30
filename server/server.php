<?php

class PR_Server
{
  public function __construct() {

    $files = PR_Utils::search_files( __DIR__ , 'php' );
    if ( !empty( $files ) ) {
      foreach ( $files as $file ) {
        require_once $file;
      }
    }
    
    $this->_load_connectors();
  }

  /**
   * Load plugin connectors
   *
   * @void
   */
  protected function _load_connectors() {

    if ( is_dir( PR_SERVER_CONNECTORS_PATH ) ) {
      $files = PR_Utils::search_files( PR_SERVER_CONNECTORS_PATH, 'php' );
      if ( !empty( $files ) ) {
        foreach ( $files as $file ) {
          require_once $file;
        }
      }
    }
  }
}

/* instantiate the plugin class */
$pr_server = new PR_Server();
