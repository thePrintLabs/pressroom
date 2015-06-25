<?php

class PR_add_ons_page {

  public $pr_options = array();
  public function __construct() {

    if( !is_admin() ) {
      return;
    }

    add_action( 'admin_footer', array( $this, 'add_custom_scripts' ) );

    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );

    $this->pr_options = get_option( 'pr_settings' );
    //add_action( 'wp_ajax_pr_delete_theme', array( $this, 'pr_delete_theme' ) );
    //add_action( 'wp_ajax_pr_upload_theme', array( $this, 'pr_upload_theme' ) );
  }

  /**
  * Add options page to wordpress menu
  */
  public function pr_add_admin_menu() {

    add_submenu_page( 'pressroom', __( 'Add-ons' ), __( 'Add-ons' ), 'manage_options', 'pressroom-add-ons', array( $this, 'pr_add_ons_page' ));
  }

  /**
  * Render a single theme
  * @param array $add_on
  * @return string
  */
  public function _render_add_on( $add_on ) {

    $html = '<div class="theme ' . ( in_array($add_on->info->slug, $this->pr_options['pr_enabled_exporters']) ? 'active' : '' ) . '" data-name="' . $add_on->info->id . '" tabindex="0">
    <div class="theme-screenshot pr-theme-screenshot">
    <img src="'.$add_on->info->thumbnail.'" alt="">
    </div>
    <p class="pr-theme-description">' . $add_on->info->content . '</p>
    <p class="pr-theme-description">
    <span class="pr-theme-version">' . __("Price ", 'pressroom-themes' ) . $add_on->pricing->amount . '</span>
    <span>' . __("Category", 'pressroom-themes' ) . ' <a href="#" target="_blank">' . $add_on->info->category[0]->name . '</a></span>
    </p>';

    if ( in_array($add_on->info->slug, $this->pr_options['pr_enabled_exporters'] ) ) {
      $html.= '<h3 class="theme-name" id="' . $add_on->info->id . '-name"><span>Attivo:</span> ' . $add_on->info->title . '</h3>
      <div class="theme-actions">
      <a class="button button-primary pr-theme-deactivate" href="' . admin_url('admin.php?page=pressroom-themes&theme_id='. $add_on->info->id .'&theme_status=false') . '">Deactivate</a>
      <a class="button button-secondary pr-theme-delete" href="#">Delete</a>
      </div>';
    }
    else {
      $html.= '<h3 class="theme-name" id="' . $add_on->info->id . '-name">' . $add_on->info->title . '</h3>
      <div class="theme-actions pr-theme-actions">
      <a class="button button-primary pr-theme-activate" href="' . admin_url('admin.php?page=pressroom-themes&theme_id='. $add_on->info->id .'&theme_status=true') . '">Activate</a>
      <a class="button button-secondary pr-theme-delete" href="#">Delete</a>
      </div>';
    }

    $html.= '</div>';

    return $html;
  }

  /**
   * Render themes page
   * @echo
   */
  public function pr_add_ons_page() {

    $add_ons = PR_Add_ons::get_add_ons();
    echo '<div class="wrap" id="edd-add-ons">
    <h2>PressRoom Add-ons <span class="title-count theme-count" id="pr-theme-count">' . count( $add_ons ) . '</span>
    </h2>
    <br>
    <div class="theme-browser rendered">
    <div class="themes">';
    if( $add_ons ) {
      foreach ( $add_ons as $add_on ) {
        echo $this->_render_add_on( $add_on );
      }
    }
    echo '
    </div>
    <br class="clear">
    </div>
    </div>';
  }

  /**
   * Add custom scripts to footer
   *
   * @void
   */
  public function add_custom_scripts() {

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom-themes' ) {
      wp_register_script( "pr_themes_page", PR_ASSETS_URI . "/js/pr.themes_page.js", array( 'jquery' ), '1.0', true );
      wp_enqueue_script( "pr_themes_page" );
      //wp_localize_script( "pr_themes_page", "pr", $this->_get_i18n_strings() );
    }
  }

  /**
  * Active / Deactive theme
  * @void
  */
  protected function _update_add_on_status() {

    if ( isset( $_GET['add_on_status'], $_GET['add_on_slug'] ) && strlen( $_GET['add_on_slug'] ) ) {
      PR_Add_ons::set_add_on_status( $_GET['add_on_slug'], $_GET['add_on_status'] == 'true' );
    }
  }
}

$pr_add_ons_page = new PR_add_ons_page();
