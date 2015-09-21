<?php

class PR_addons_page {

  public $pr_options = array();

  public function __construct() {

    if( !is_admin() ) {
      return;
    }

    add_action( 'admin_footer', array( $this, 'add_custom_scripts' ) );
    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );
    add_action( 'wp_ajax_pr_get_remote_addons', array( $this, 'get_remote_addons' ) );
    add_action( 'wp_ajax_pr_dismiss_notice', array( $this, 'dismiss_notice' ) );

    $this->pr_options = get_option( 'pr_settings' );
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
  public function render_add_on( $addon, $installed, $activated, $free, $trial ) {

    $options = get_option( 'pr_settings' );
    $item_id = $addon->info->id;
    $item_slug = $addon->info->slug;
    $item_name = $addon->info->title;

    if ( $trial ) {

      $item_price = '';
      $item_button = '';
      $i = 0;
      foreach ( $addon->pricing as $key => $price ) {
        $i++;
        if( $price > 0 ) {
          $item_price .= $price . '$' . ' ';
        }
        $item_link = PR_API_URL . 'checkout?edd_action=add_to_cart&download_id=' . $addon->info->id . '&edd_options[price_id]=' . ( $i );
        $item_button .= '<a class="button button-primary pr-theme-deactivate" target="_blank" href="'.$item_link.'">'.__( $price == 0 ? 'Trial' : 'Buy', 'pressroom-themes' ).'</a>';
      }
    }
    else {
      $item_price = $addon->pricing->amount . '$';
      $item_link = PR_API_URL . 'checkout?edd_action=add_to_cart&download_id=' . $addon->info->id;
      $item_button = '<a class="button button-primary pr-theme-deactivate" target="_blank" href="'.$item_link.'">'.__( "Buy", 'pressroom-themes' ).'</a>';
    }

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
    elseif( $free ) {
      $html .= '<p class="pr-theme-description pr-theme-description">'.__( "Free ", 'pressroom-themes' ). '</p>';
    }
    else {
      $html .= '<p class="pr-theme-description pr-theme-description">'.__( "Price ", 'pressroom-themes' ). $item_price .'</p>';
    }

    $html .= '<h3 class="theme-name" id="' . $item_id . '-name">' . $item_name . '</h3>';
    $html .= '<div class="theme-actions">';
    if ( $installed && $activated && !$free ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-deactivate" name="pr_license_key_' . $item_slug . '_deactivate" value="' . __( "Deactivate", 'pressroom-addons' ) . '"/>';
    }
    elseif ( $installed && !$free ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-activate" name="pr_license_key_' . $item_slug . '_activate" value="' . __( "Activate", 'pressroom-addons' ) . '"/>';
    }
    elseif( !$installed && $free ) {
      $html .= $item_button;
    }
    elseif( !$installed  && !$free ) {
      $html .= $item_button;
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
  * Render a single installed addon
  * @param array $addon
  * @return string
  */
  public function render_installed_addon( $addon, $installed, $activated, $free ) {

    $options = get_option( 'pr_settings' );
    $item_id = $addon['itemid'];
    $item_slug = $addon['slug'];
    $item_name = $addon['name'];
    $item_thumbnail = plugins_url( $addon['dir'] ) . '/thumb.png';

    $html = '<div class="theme ' . ( $activated ? 'active' : '' ) . '" data-name="' . $item_id . '" tabindex="0">
    <form method="post" name="' . $item_slug . '">
    <div class="theme-screenshot pr-theme-screenshot">
    <img src="'.$item_thumbnail.'" alt="">
    </div>
    <p class="pr-theme-description">' . $addon['description'] . '</p>';

    if ( $installed && $activated ) {
      $html .= '<p class="pr-theme-description"><span>' . __("Your license key", 'pressroom-addons' ) . ' <b>' . $options['pr_license_key_' . $item_slug] . '</b></span></p>';
    }
    else if ( $installed ) {
      $html .= '<p class="pr-theme-description pr-theme-description-input"><input type="text" id="pr_license_key" name="pr_settings[pr_license_key_' . $item_slug . ']" style="width:100%" placeholder="' . __("Enter your license key", 'pressroom-addons' ) . '"></p>';
    }
    elseif( $free ) {
      $html .= '<p class="pr-theme-description pr-theme-description">'.__( "Free ", 'pressroom-themes' ). '</p>';
    }

    $html .= '<h3 class="theme-name" id="' . $item_id . '-name">' . $item_name . '</h3>';
    $html .= '<div class="theme-actions">';
    if ( $installed && $activated && !$free ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-deactivate" name="pr_license_key_' . $item_slug . '_deactivate" value="' . __( "Deactivate", 'pressroom-addons' ) . '"/>';
    }
    else if ( $installed && !$free ) {
      $html .= '<input type="submit" class="button button-primary pr-theme-activate" name="pr_license_key_' . $item_slug . '_activate" value="' . __( "Activate", 'pressroom-addons' ) . '"/>';
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
   * Render addons page
   * @echo
   */
  public function pressroom_addons_page() {

    $enabled_exporters = isset( $this->pr_options['pr_enabled_exporters'] ) ? $this->pr_options['pr_enabled_exporters'] : false ;
    $addons = PR_Addons::get();
    $coupons = PR_Addons::get_discount_codes();
    $current_user = wp_get_current_user();

    echo '<div class="wrap" id="addons-container">
    <h2>PressRoom Add-ons</h2>';

    foreach( $coupons as $key => $coupon ) {
      $notice = get_user_meta( $current_user->ID, 'pr_addons_notice_' . $coupon->ID, true );
      if( !$notice ) {
        $products_list = '';
        foreach( $coupon->products as $product ) {
          $products_list .= $product . ',';
        }
        $type = $coupon->type == 'percent' ? '%' : '$';
        echo '<div class="discount-container discount-container-'.$coupon->ID.' pr-alert update-nag">
          <div class="discount-message">
          <p>'. sprintf( __( 'Get a coupon for %s save %d %s ', 'edition' ),$products_list , $coupon->amount, $type ) .'</p>
          </div>
          <div class="show-code-container">
            <a data-index="'.$key.'" class="show-code button button-primary" href="#">Show code</a>
          </div>
          <span class="pr-dismiss-notice-container">
            <a href="#" class="pr-dismiss-notice" data-index="'. $coupon->ID .'"><span class="dashicons dashicons-no"></span></a>
          </span>
          <div class="discount-code discount-code-' . $key . '" style="display:none"><b> '. $coupon->code.'</b></div>
        </div><br>';
      }
    }

    echo '<h2 class="nav-tab-wrapper pr-tab-wrapper">';
    echo '<a class="nav-tab nav-tab-active' . '" data-tab="installed" href="#">' . __('Installed', 'pressroom-addons') . '</a>';
    echo '<a class="nav-tab' . '" data-tab="remotes" href="#">' . __('Download', 'pressroom-addons') . '</a>';
    echo '</h2>';
    echo '<div id="pr-progressbar"></div><br/>';


    echo'
    <div class="theme-browser rendered" id="addons-installed">
    <div class="themes">';
    if ( $addons ) {
      foreach ( $addons as $addon ) {

        $is_installed = false;
        $filepath = isset( $addon['filepath'] ) ? $addon['filepath'] : false ;

        // check if file exist and is out of pressroom plugin dir ( embedded web exporter )
        if ( file_exists( $filepath ) ) {
          $is_installed = true;
        }

        $is_free = isset( $addon['paid'] ) && $addon['paid'] ? false : true;

        $is_activated = PR_EDD_License::check_license( $addon['slug'], $addon['name'], $is_free );

        echo $this->render_installed_addon( $addon, $is_installed, $is_activated, $is_free );
      }
    }

    echo '
    </div>
    <br class="clear">
    </div>
    </div>';
  }

  /**
   * Get addons list from online feed
   *
   * @echo
   */
  public function get_remote_addons() {

    $enabled_exporters = isset( $this->pr_options['pr_enabled_exporters'] ) ? $this->pr_options['pr_enabled_exporters'] : false ;
    $addons = PR_Addons::get_remote_addons();
    echo'
    <div class="theme-browser rendered" id="addons-remote">
    <div class="themes">';
    if ( $addons ) {
      foreach ( $addons as $addon ) {

        $is_installed = false;
        $is_trial = false;
        
        $filepath = isset( $enabled_exporters[$addon->info->slug]['filepath'] ) ? $enabled_exporters[$addon->info->slug]['filepath'] : false ;
        // check if file exist and is out of pressroom plugin dir ( embedded web exporter )
        if ( file_exists( $filepath ) ) {
          $is_installed = true;
        }
        if( isset( $addon->pricing->amount ) ) {
          $is_free = $addon->pricing->amount == 0 ? true : false;
        }
        else {
          foreach( $addon->pricing as $price ) {

            if( $price == 0 ) {
              $is_free = true;
              $is_trial = true;
            }
            else {
              $is_free = false;
            }
          }
        }


        $is_activated = PR_EDD_License::check_license( $addon->info->slug, $addon->info->title, $is_free );
        if( !$is_installed ) {
          echo $this->render_add_on( $addon, $is_installed, $is_activated, $is_free, $is_trial );
        }
      }
    }

    echo '
    </div>
    <br class="clear">
    </div>';
  }

  /**
   * Ajax callback to dismiss notice
   *
   * @json success
   */
  public function dismiss_notice() {

    $current_user = wp_get_current_user();
    if( update_user_meta($current_user->ID, 'pr_addons_notice_' . $_POST['id'], true) ) {
      wp_send_json_success();
    }

  }
  /**
   * Add custom scripts to footer
   *
   * @void
   */
  public function add_custom_scripts() {

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom-addons' ) {
      wp_register_script( "nanobar", PR_ASSETS_URI . "/js/nanobar.min.js", array( 'jquery' ), '1.0', true );
      wp_enqueue_script( "nanobar" );
      wp_register_script( "pr_addons_page", PR_ASSETS_URI . "/js/pr.addons_page.js", array( 'jquery' ), '1.0', true );
      wp_enqueue_script( "pr_addons_page" );
      //wp_localize_script( "pr_themes_page", "pr", $this->_get_i18n_strings() );
    }
  }

  /**
  * Active / Deactive addon
  * @void
  */
  protected function _update_add_on_status() {

    if ( isset( $_GET['add_on_status'], $_GET['add_on_slug'] ) && strlen( $_GET['add_on_slug'] ) ) {
      PR_Addons::set_add_on_status( $_GET['add_on_slug'], $_GET['add_on_status'] == 'true' );
    }
  }
}

$pr_addons_page = new PR_addons_page();
