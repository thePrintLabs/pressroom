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
  * Render a single addon
  * @param array $addon
  * @return string
  */
  public function _render_add_on( $addon, $installed, $activated ) {

    $options = get_option( 'pr_settings' );
    $item_id = $addon->info->id;
    $item_slug = $addon->info->slug;
    $item_name = $addon->info->title;
    $item_price = $addon->pricing->amount;
    $item_link = $addon->info->link;

    $html = '<div class="theme ' . ( $activated ? 'active' : '' ) . '" data-name="' . $item_id . '" tabindex="0">
    <form method="post" name="' . $item_slug . '">
    <div class="theme-screenshot pr-theme-screenshot">
    <img src="'.$addon->info->thumbnail.'" alt="">
    </div>
    <p class="pr-theme-description">' . $addon->info->content . '</p>
    <p class="pr-theme-description">';

    $html .= '
    <span>' . __("Category", 'pressroom-themes' ) . ' <a href="#" target="_blank">' . $addon->info->category[0]->name . '</a></span>
    </p>';

    if ( $installed && $activated ) {
      $html .= '<p class="pr-theme-description"><span>' . __("Your license key", 'pressroom-addons' ) . ' <b>' . $options['pr_license_key_' . $item_slug] . '</b></span></p>';
    }
    else if ( $installed ) {
      $html .= '<p class="pr-theme-description pr-theme-description-input"><input type="text" id="pr_license_key" name="pr_settings[pr_license_key_' . $item_slug . ']" style="width:100%" placeholder="' . __("Enter your license key", 'pressroom-addons' ) . '"></p>';
    }
    else {
      $html .= '<p class="pr-theme-description pr-theme-description">'.__( "Price ", 'pressroom-themes' ). $item_price .'</p>';
    }

    $html .= '<h3 class="theme-name" id="' . $item_id . '-name">' . $item_name . '</h3>';
    $html .= '<div class="theme-actions">';
    if ( $installed && $activated ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-deactivate" name="pr_license_key_' . $item_slug . '_deactivate" value="' . __( "Deactivate", 'pressroom-addons' ) . '"/>';
    }
    else if ( $installed ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-activate" name="pr_license_key_' . $item_slug . '_activate" value="' . __( "Activate", 'pressroom-addons' ) . '"/>';
    }
    else {
      $html .= '<a class="button button-primary pr-theme-deactivate" target="_blank" href="'.$item_link.'">'.__( "Buy", 'pressroom-themes' ).'</a>';
    }

    $html .= '</div>
    <input type="hidden" name="item_' . $item_slug . '_name" value="' . $item_name . '" />
    <input type="hidden" name="item_slug" value="' . $item_slug . '" />
    <input type="hidden" name="return_page" value="pressroom-addons" />
    <input type="hidden" name="type" value="exporter" />
    </form>
    </div>';

    return $html;
  }

  /**
   * Render themes page
   * @echo
   */
  public function pressroom_addons_page() {


    $enabled_exporters = isset( $this->pr_options['pr_enabled_exporters'] ) ? $this->pr_options['pr_enabled_exporters'] : false ;
    $addons = PR_Addons::get();
    echo '<div class="wrap" id="edd-add-ons">
    <h2>PressRoom Add-ons <span class="title-count theme-count" id="pr-theme-count">' . count( $addons ) . '</span>
    </h2>
    <br>
    <div class="theme-browser rendered">
    <div class="themes">';
    if ( $addons ) {
      foreach ( $addons as $addon ) {

        $is_installed = false;
        $filepath = $enabled_exporters[$addon->info->slug]['filepath'];

        // check if file exist and is out of pressroom plugin dir ( embedded web exporter )
        if ( file_exists( $filepath ) ) {
          $is_installed = true;
        }

        $is_activated = PR_EDD_License::check_license( $addon );

        echo $this->_render_add_on( $addon, $is_installed, $is_activated );
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
