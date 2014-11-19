<?php

class PR_Preview {

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

    $linked_query = pr_get_edition_posts( $edition, true );
    if ( empty( $linked_query ) ) {
      return;
    }

    $edition_dir = PR_Utils::sanitize_string( $edition->post_title );
    $edition_path = PR_PREVIEW_TMP_PATH . DIRECTORY_SEPARATOR . $edition_dir;
    PR_Utils::remove_dir( $edition_path );

    if ( PR_Utils::make_dir( PR_PREVIEW_TMP_PATH, $edition_dir ) ) {
      $font_path = PR_Theme::get_theme_path( $edition->ID ) . 'assets' . DIRECTORY_SEPARATOR . 'fonts';
      PR_Utils::recursive_copy( $font_path, $edition_path . DIRECTORY_SEPARATOR . 'fonts');
      self::draw_toc( $edition, $linked_query );
    }

    return $linked_query->posts;
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
      do_action_ref_array( 'pr_preview_' . $post->post_type, array( &$page_url, $edition, $post ) );
    }
    else {
      $filename =  PR_Utils::sanitize_string( $post->post_title ) . '.html';
      $edition_dir = PR_Utils::sanitize_string( $edition->post_title );

      $html = self::parse_html( $edition, $post );
      $html = self::rewrite_html_url( $edition, $html );

      if ( PR_Utils::make_dir( PR_PREVIEW_TMP_PATH, $edition_dir ) ) {
        file_put_contents( PR_PREVIEW_TMP_PATH . $edition_dir . DIRECTORY_SEPARATOR . $filename, $html );
        $page_url = PR_PREVIEW_URI . $edition_dir . DIRECTORY_SEPARATOR . $filename;
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

    $toc = PR_Theme::get_theme_toc( $edition->ID );
    if ( !$toc || !file_exists( $toc ) ) {
      return false;
    }

    ob_start();
    $pr_theme_url = PR_THEME::get_theme_uri( $edition->ID );
    $posts = $linked_posts;
    self::add_functions_file( $edition->ID );
    require_once( $toc );
    $output = ob_get_contents();
    ob_end_clean();

    $output = self::rewrite_html_url( $edition, $output );
    $output = self::rewrite_toc_url( $output, $edition->ID );

    $edition_dir = PR_Utils::sanitize_string( $edition->post_title );
    file_put_contents( PR_PREVIEW_TMP_PATH . $edition_dir . DIRECTORY_SEPARATOR . 'toc.html', $output );
  }

  /**
   * Parsing html
   * @param object $edition
   * @param object $connected_post
   * @return string  html string
   */
  public static function parse_html( $edition, $connected_post ) {

    $p2p_id = p2p_type( P2P_EDITION_CONNECTION )->get_p2p_id( $connected_post, $edition );
    if ( !$p2p_id ) {
      return false;
    }

    $template = PR_Theme::get_theme_page( $edition->ID, $p2p_id );
    if ( !$template || !file_exists( $template ) ) {
      return false;
    }

    ob_start();
    $pr_theme_url = PR_THEME::get_theme_uri( $edition->ID );
    global $post;
    $post = $connected_post;
    setup_postdata( $post );
    self::add_functions_file( $edition->ID );
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

      $theme_uri = PR_Theme::get_theme_uri( $edition->ID );

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
      $links = PR_Utils::extract_urls( $html );
      foreach ( $links as $link ) {

        $post_id = url_to_postid( $link );
        if ( $post_id ) {
          $html = str_replace( $link, PR_CORE_URI . 'preview/reader.php?edition_id=' . $edition_id . '#toc-' . $post_id, $html );
          $html = preg_replace("/<a(.*?)>/", "<a$1 target=\"_parent\">", $html);
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

    $editions = PR_Edition::get_linked_editions( $post );
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
    window.open("' . PR_CORE_URI . 'preview/reader.php?edition_id=" + edition + "&post_id=" + post, "_blank").focus();return false;};
    }, false);
    </script>';
  }

  /**
   * Add function file if exist
   * @param int $edition_id
   * @void
   */
  public static function add_functions_file( $edition_id ) {

    $theme_path = PR_Theme::get_theme_path( $edition_id );
    $files = PR_Utils::search_files( $theme_path, 'php', true );
    if ( !empty( $files ) ) {
      foreach ( $files as $file ) {
        if ( strpos( $file, 'functions.php' ) !== false ) {
          require_once $file;
          break;
        }
      }
    }
  }
}
