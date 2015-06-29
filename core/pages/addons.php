<?php

class PR_addons_page {

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
    add_submenu_page( 'pressroom', __( 'Add-ons' ), __( 'Add-ons' ), 'manage_options', 'pressroom-addons', array( $this, 'pressroom_addons_page' ));
  }

  /**
  * Render a single theme
  * @param array $add_on
  * @return string
  */
  public function _render_add_on( $add_on, $installed, $activated ) {

    $html = '<div class="theme ' . ( $activated ? 'active' : '' ) . '" data-name="' . $add_on->info->id . '" tabindex="0">
    <form action="addons.php">
    <div class="theme-screenshot pr-theme-screenshot">
    <img src="'.$add_on->info->thumbnail.'" alt="">
    </div>
    <p class="pr-theme-description">' . $add_on->info->content . '</p>
    <p class="pr-theme-description">';

    $html .= '
    <span>' . __("Category", 'pressroom-themes' ) . ' <a href="#" target="_blank">' . $add_on->info->category[0]->name . '</a></span>
    </p>';

    if( $installed && $activated ) {
      $html .= '<p class="pr-theme-description"><span>' . __("Your license key", 'pressroom-addons' ) . ' <b>abc123456789dcas2aabc123456789</b></span></p>';
    }
    else if( $installed ) {
      $html .= '
      <p class="pr-theme-description pr-theme-description-input"><input type="text" style="width:100%" placeholder="' . __("Enter your license key", 'pressroom-addons' ) . '"></p>';
    }

    $html .= '<h3 class="theme-name" id="' . $add_on->info->id . '-name">' . $add_on->info->title . '</h3>';
    $html .= '<div class="theme-actions">';
    if( $installed && $activated ) {
      $html .= '<a class="button button-primary pr-theme-deactivate" href="' . admin_url('admin.php?page=pressroom-add-ons&theme_id='. $add_on->info->id .'&theme_status=false') . '">'.__( "Deactivate", 'pressroom-themes' ).'</a>';
    }
    else if( $installed ) {
      $html .= '<a class="button button-primary pr-theme-activate" href="' . admin_url('admin.php?page=pressroom-add-ons&theme_id='. $add_on->info->id .'&theme_status=false') . '">'.__( "Activate", 'pressroom-themes' ).'</a>';
    }
    else {
      $html .= '<a class="button button-primary pr-theme-deactivate" href="' . admin_url('admin.php?page=pressroom-add-ons&theme_id='. $add_on->info->id .'&theme_status=false') . '">'.__( "Buy", 'pressroom-themes' ).'</a>';
    }


    $html .= '
    </div>
    </form>
    </div>';

    return $html;
  }

  /**
   * Render themes page
   * @echo
   */
  public function pressroom_addons_page() {

    global $tpl_pressroom;
    $exporters = $tpl_pressroom->exporters;
    $add_ons = PR_Addons::get();
    echo '<div class="wrap" id="edd-add-ons">
    <h2>PressRoom Add-ons <span class="title-count theme-count" id="pr-theme-count">' . count( $add_ons ) . '</span>
    </h2>
    <br>
    <div class="theme-browser rendered">
    <div class="themes">';
    if( $add_ons ) {
      foreach ( $add_ons as $add_on ) {

        $installed = false;
        $activated = false;
        $key = array_search( $add_on->info->slug, $exporters );

        // check if file exist and is out of pressroom plugin dir ( embedded web exporter )
        if( file_exists( $key ) && strpos( $key, PR_PLUGIN_PATH ) === false ) {
          $installed = true;
        }
        if( in_array( $add_on->info->slug, $this->pr_options['pr_enabled_exporters'] ) ) {
          $activated = true;
        }

        echo $this->_render_add_on( $add_on, $installed, $activated );
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
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom-addons' ) {
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
      PR_Addons::set_add_on_status( $_GET['add_on_slug'], $_GET['add_on_status'] == 'true' );
    }
  }
}

$pr_addons_page = new PR_addons_page();
