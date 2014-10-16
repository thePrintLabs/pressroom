<?php

class TPL_Preview {

  public function __construct() {

    add_action( 'add_meta_boxes', array( $this, 'add_preview_metabox' ), 10 );
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

    $linked_posts = pr_get_edition_posts_id( $edition );
    if ( empty( $linked_posts ) ) {
      return;
    }

    $edition_dir = TPL_Utils::sanitize_string( $edition->post_title );
    $edition_path = TPL_PREVIEW_TMP_PATH . DIRECTORY_SEPARATOR . $edition_dir;
    TPL_Utils::remove_dir( $edition_path );

    if ( TPL_Utils::make_dir( TPL_PREVIEW_TMP_PATH, $edition_dir ) ) {
      $font_path = TPL_Theme::get_theme_path( $edition->ID ) . 'assets' . DIRECTORY_SEPARATOR . 'fonts';
      TPL_Utils::recursive_copy( $font_path, $edition_path . DIRECTORY_SEPARATOR . 'fonts');
      self::draw_toc( $edition, $linked_posts );
    }

    return $linked_posts;
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

    $page_url = '';
    if ( has_action( 'pr_preview_' . $post->post_type ) ) {

      $args = array( '', $edition, $post );
      do_action_ref_array( 'pr_preview_' . $post->post_type, array( &$args ) );
      $page_url = $args[0];
    }
    else {
      $html = self::parse_html( $edition, $post );
      $html = self::rewrite_html_url( $edition, $html );

      $filename =  TPL_Utils::sanitize_string( $post->post_title ) . '.html';
      $edition_dir = TPL_Utils::sanitize_string( $edition->post_title );
      if ( TPL_Utils::make_dir( TPL_PREVIEW_TMP_PATH, $edition_dir ) ) {
        file_put_contents( TPL_PREVIEW_TMP_PATH . $edition_dir . DIRECTORY_SEPARATOR . $filename, $html );
        $page_url = TPL_PREVIEW_URI . $edition_dir . DIRECTORY_SEPARATOR . $filename;
      }
    }

    echo $page_url;
    exit;
  }

  /**
   * Draw toc html file
   * @param object $edition
   * @param array $linked_posts
   * @return string or boolean false
   */
  public static function draw_toc( $edition, $linked_posts ) {

    $toc = TPL_Theme::get_theme_toc( $edition->ID );
    if ( !$toc ) {
      return false;
    }

    ob_start();
    $posts = pr_get_edition_posts( $edition );

    require_once( $toc );
    $output = ob_get_contents();
    ob_end_clean();

    $output = self::rewrite_html_url( $edition, $output );
    $output = self::rewrite_toc_url( $output, $edition->ID );

    $edition_dir = TPL_Utils::sanitize_string( $edition->post_title );
    file_put_contents( TPL_PREVIEW_TMP_PATH . $edition_dir . DIRECTORY_SEPARATOR . 'toc.html', $output );
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
    if ( !$template || !file_exists( $template ) ) {
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
   * Rewrite html url for preview
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

  /**
   * Rewrite toc url for preview with hashtag
   * @param  string $html
   * @param  int $edition_id
   * @return string $html
   */
  public static function rewrite_toc_url( $html, $edition_id ) {
     if ( $html ) {
        $links = wp_extract_urls( $html );
        foreach ( $links as $link ) {

          $post_id = url_to_postid( $link );
          if ( $post_id ) {
             $html = str_replace( $link, TPL_CORE_URI . 'preview/reader.php?edition_id=' . $edition_id . '#toc-' . $post_id, $html );
          }
        }
     }
     return $html;
  }

  /**
   * Add preview metabox
   *
   * @return void
   */
  public function add_preview_metabox() {

    global $tpl_pressroom;
    $post_types = $tpl_pressroom->get_allowed_post_types();
    foreach ( $post_types as $type ) {
      add_meta_box( 'pr_preview_metabox', __( 'Preview', 'edition' ), array( $this, 'add_preview_metabox_callback' ), $type, 'side', 'low' );
    }
  }

  /**
   * Preview metabox callback print html box
   *
   * @echo
   */
  public function add_preview_metabox_callback( $post ) {

    $editions = TPL_Edition::get_linked_editions( $post );
    echo '<label for="post_status">' . __("Choose an edition:", 'pressroom') . '</label>
    <div id="post-preview-select">
    <select name="pr_prw_edition_id" id="pr_prw_edition_id">';
    foreach ( $editions->posts as $edition ) {
      echo '<option value="' . $edition->ID . '">' . $edition->post_title . '</option>';
    }
    echo '</select>
    </div>
    <hr/>
    <button type="button" id="preview_post" target="_blank" class="button button-primary button-large">' . __( "Preview", "pressroom" ) . '</button>
    <script type="text/javascript">
    window.addEventListener("load", function() {
    document.getElementById("preview_post").onclick = function(e){
    var e = document.getElementById("pr_prw_edition_id"),
    edition = e.options[e.selectedIndex].value,
    post = ' . $post->ID . ';
    window.open("' . TPL_CORE_URI . 'preview/reader.php?edition_id=" + edition + "&post_id=" + post, "_blank").focus();return false;};
    }, false);
    </script>';
  }
}
