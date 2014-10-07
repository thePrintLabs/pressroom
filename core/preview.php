<?php

class TPL_Preview {

  public function __construct() {

    add_action( 'wp_ajax_preview_draw_page', array( $this, 'draw_page' ), 10 );
  }

  /**
   * Init preview slider
   * @param int $edition_id
   * @return array
   */
  public static function init( $edition_id ) {

    $edition = get_post( $edition_id );
    if ( !$edition ) {
      return;
    }

    $connected = p2p_get_connections( P2P_EDITION_CONNECTION, array(
      'to' => $edition
    ));

    $posts_id = array();
    foreach ( $connected as $conn ) {
      array_push( $posts_id, $conn->p2p_from );
    }

    $edition_dir = TPL_Utils::sanitize_string( $edition->post_title );
    if ( TPL_Utils::make_dir( TPL_PREVIEW_DIR, $edition_dir ) ) {

      $font_path = TPL_Theme::get_theme_path( $edition->ID ) . 'assets/fonts';
      TPL_Utils::recursive_copy( $font_path, TPL_PREVIEW_DIR . DIRECTORY_SEPARATOR . $edition_dir . DIRECTORY_SEPARATOR . 'fonts');
    }

    return $posts_id;
  }

  /**
   * Draw single post in html
   *
   * @echo
   */
  public static function draw_page() {

    if ( !isset( $_GET['post_id'], $_GET['edition_id'] ) ) {
      return;
    }

    $post = get_post( $_GET['post_id'] );
    if ( !$post ) {
      return;
    }

    $edition = get_post( $_GET['edition_id'] );
    if ( !$edition ) {
      return;
    }

    $html = self::parse_html( $edition, $post );
    $html = self::rewrite_html_url( $edition, $html );

    if ( has_action( 'pr_preview_' . $post->post_type ) ) {

      $custom_html = '';
      $args = array( $custom_html, $post );
      do_action_ref_array( 'pr_preview_' . $post->post_type, array( &$args ) );
      $custom_html = $args[0];
      $html.= $custom_html;
    }

    $filename =  TPL_Utils::sanitize_string( $post->post_title ) . '.html';
    $edition_dir = TPL_Utils::sanitize_string( $edition->post_title );
    if ( TPL_Utils::make_dir( TPL_PREVIEW_DIR, $edition_dir ) ) {
      file_put_contents( TPL_PREVIEW_DIR . $edition_dir . DIRECTORY_SEPARATOR . $filename, $html );
      echo TPL_PREVIEW_URI . $edition_dir . '/' . $filename;
    }
    exit;
  }

  /**
   * Parsing html
   * @param object $edition
   * @param object $connected_post
   * @return string	html string
   */
  public static function parse_html( $edition, $connected_post ) {

    $p2p_id = p2p_type( P2P_EDITION_CONNECTION )->get_p2p_id( $connected_post, $edition );
    if ( !$p2p_id ) {
      return false;
    }

    $template = TPL_Theme::get_theme_page( $edition->ID, $p2p_id );
    if ( !$template ) {
      return false;
    }

    ob_start();
    global $post;
    $post = $connected_post;
    setup_postdata( $post );
    require( $template );
    $output = ob_get_contents();
    wp_reset_postdata();
    ob_end_clean();
    return $output;
  }

  /**
   * rewrite html url for preview
   * @param object $edition
   * @param  string $html
   * @return string $html
   */
  public static function rewrite_html_url( $edition, $html ) {

    if ( $html ) {

      $theme_uri = TPL_Theme::get_theme_uri( $edition->ID );

      libxml_use_internal_errors( true );
      $dom = new domDocument();
      $dom->loadHTML( $html );

      $links = $dom->getElementsByTagName( 'link' );
      foreach ( $links as $link ) {

        $href = $link->getAttribute( 'href' );
        $html = str_replace( $href, $theme_uri . $href, $html );
      }

      $scripts = $dom->getElementsByTagName( 'script' );
      foreach( $scripts as $script ) {

        $src = $script->getAttribute( 'src' );
        $html = str_replace( $src, $theme_uri . $src, $html );
      }
    }

    return $html;
  }

}
