<?php

require_once PR_LIBS_PATH . 'P2P/scb-framework/load.php';
require_once PR_LIBS_PATH . 'PR/UI/p2p/p2p_metabox.php';
define( 'P2P_TEXTDOMAIN', 'pr_p2p' );

final class PR_P2P
{
  public function __construct() {
    add_action( 'wp_loaded', array( $this, '_p2p_init' ) );
    scb_init( array( $this, '_p2p_load' ) );
  }
  public function _p2p_load() {
    load_plugin_textdomain( P2P_TEXTDOMAIN, '', basename( dirname( __FILE__ ) ) . '/lang' );
    if ( !function_exists( 'p2p_register_connection_type' ) ) {
      require_once PR_LIBS_PATH . 'P2P/autoload.php';
    }

    P2P_Storage::init();
    P2P_Query_Post::init();
    P2P_Query_User::init();
    P2P_URL_Query::init();
    P2P_Widget::init();
    P2P_Shortcodes::init();

  }

  public function _p2p_init() {
    // Safe hook for calling p2p_register_connection_type()
    do_action( 'p2p_init' );
  }
}

$pr_p2p = new PR_P2P();
