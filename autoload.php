<?php
require_once __DIR__ . '/define.php';
require_once PR_LIBS_PATH . 'PR/utils.php';

class PR_Autoload {

  public function __construct() {

    $this->_load_libs();
    $this->_load_api();
    $this->_load_taxonomies();
    $this->_load_post_types();

    $this->_load_packager();
    $this->_load_preview();

    $this->_load_server();

    $this->_load_pages();
  }

  protected function _load_api() {
    require_once PR_ROOT . 'api.php';
  }

  protected function _load_libs() {
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

  protected function _load_taxonomies() {
    $this->_load_files( PR_TAXONOMIES_PATH );
  }

  protected function _load_post_types() {
    $this->_load_files( PR_POST_TYPES_PATH );
  }

  protected function _load_packager() {
    require_once PR_PACKAGER_PATH . 'packager.php';
  }

  protected function _load_preview() {
    require_once PR_PREVIEW_PATH . 'preview.php';
  }

  protected function _load_server() {
    require_once PR_SERVER_PATH . 'server.php';
  }

  protected function _load_pages() {
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
