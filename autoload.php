<?php
require_once __DIR__ . '/define.php';
require_once PR_LIBS_PATH . 'PR/utils.php';

class PR_Autoload {

  public function __construct() {

    $this->libs();
    $this->api();
    $this->taxonomies();
    $this->post_types();

    $this->packager();
    $this->preview();
    $this->server();
    $this->pages();
  }

  public function api() {
    require_once PR_ROOT . 'api.php';
  }

  public function libs() {
    if ( is_dir( PR_LIBS_PATH ) ) {
      $dirs = PR_Utils::search_dir( PR_LIBS_PATH );
      if ( !empty( $dirs ) ) {
        foreach ( $dirs as $dir ) {
          $file = PR_LIBS_PATH . $dir . DS . strtolower( $dir ) . ".php";
          require_once $file;
        }
      }
    }
  }

  public function taxonomies() {
    $this->_load_files( PR_TAXONOMIES_PATH );
  }

  public function post_types() {
    $this->_load_files( PR_POST_TYPES_PATH );
  }

  public function packager() {
    require_once PR_PACKAGER_PATH . 'packager.php';
  }

  public function preview() {
    require_once PR_PREVIEW_PATH . 'preview.php';
  }

  public function server() {
    require_once PR_SERVER_PATH . 'server.php';
  }

  public function pages() {
    require_once PR_PAGES_PATH . 'options.php';
  }

  /**
   * Load plugin custom types
   *
   * @void
   */
  protected function _load_files( $path, $recursive = false ) {
    if ( is_dir( $path ) ) {
      $files = PR_Utils::search_files( $path, 'php' );
      if ( !empty( $files ) ) {
        foreach ( $files as $file ) {
          require_once $file;
        }
      }
    }
  }
}

$pr_autoload = new PR_Autoload;
