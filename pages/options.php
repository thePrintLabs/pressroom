<?php

class PR_options_page {

  /**
   * constructor method
   * Add class functions to wordpress hooks
   *
   * @void
   */
  public function __construct() {

    if( !is_admin() ) {
      return;
    }

    add_action( 'admin_enqueue_scripts', array( $this, 'add_chosen_script' ) );
    add_action( 'admin_footer', array( $this, 'add_custom_script' ) );

    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );
    add_action( 'admin_init', array( $this, 'pr_settings_init' ) );
    add_filter( 'pre_update_option_pr_settings', array( $this, 'pr_save_options' ), 10, 2 );

    $this->_load_pages();
  }

  /**
   * Add options page to wordpress menu
   */
  public function pr_add_admin_menu() {

    add_menu_page( 'pressroom', 'Pressroom', 'manage_options', 'pressroom', array( $this, 'pr_options_page' ) );
    add_submenu_page('pressroom', __('Settings'), __('Settings'), 'manage_options', 'pressroom', array( $this, 'pr_options_page' ));
  }

  /**
   * register section field
   *
   * @void
   */
  public function pr_settings_init() {

  	register_setting( 'pressroom', 'pr_settings' );

  	add_settings_section(
  		'pr_pressroom_section',
  		'',
  		array( $this, 'pr_settings_section_callback' ),
  		'pressroom'
  	);

    add_settings_field(
      'custom_post_type',
      __( 'Custom post types', 'pressroom' ),
      array( $this, 'pr_custom_post_type' ),
      'pressroom',
      'pr_pressroom_section'
    );

    do_action( 'pr_add_extra_options' );
  }

  /**
   * Render custom post_type field
   *
   * @void
   */
  public function pr_custom_post_type() {

    $options = get_option( 'pr_settings' );
    $post_types = get_post_types( null, 'objects' );
    $excluded = array( 'post', 'page', 'attachment', 'revision', 'nav_menu_item', 'pr_edition' );
    $enabled_posttype = isset( $options['pr_custom_post_type'] ) ? $options['pr_custom_post_type'] : [];
    if ( !is_array( $enabled_posttype ) ) {
      $enabled_posttype = [ $enabled_posttype ];
    }

    echo '<fieldset>
    <legend class="screen-reader-text"><span>' . __( 'Custom post types', 'pressroom' ) . '</span></legend>';
    $i = 0;
    foreach ( $post_types as $post_type ) {
      if ( !in_array( $post_type->name, $excluded ) ) {
        $checked = in_array( $post_type->name, $enabled_posttype ) ? 'checked="checked"' : '';
        echo '<label title="' . $post_type->name . '"><input type="checkbox" name="pr_settings[pr_custom_post_type][]" value="' . $post_type->name . '" ' . $checked . ' />' . $post_type->labels->name . '</span></label><br>';
        $i++;
      }
    }
    if ( !$i ) {
      echo '<span>' . __( 'No custom post types founds', 'pressroom' ) . '</span>';
    }
    echo '</fieldset>';
  }

  /**
   * render setting section
   *
   * @echo
   */
  public function pr_settings_section_callback() {

  	echo __( '<hr/>', 'pressroom' );

  }

  /**
   * Render option page form
   *
   * @echo
   */
  public function pr_options_page() {
  ?>
    <form action='options.php' method='post'>
    <h2>Pressroom Options</h2>
  <?php
  	settings_fields( 'pressroom' );
  	do_settings_sections( 'pressroom' );
  	submit_button();
  ?>
  	</form>
  <?php
  }

  /**
   * filter value before saving.
   *
   * @param array $new_value
   * @param array $old_value
   * @return array $new_value
   */
  public function pr_save_options( $new_value, $old_value ) {

    if( isset( $new_value['pr_custom_post_type'] ) ) {
      $post_type = is_array( $new_value['pr_custom_post_type'] ) ? $new_value['pr_custom_post_type'] : explode( ',', $new_value['pr_custom_post_type'] );
      $new_value['pr_custom_post_type'] = $post_type;
    }

    return $new_value;
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  public function add_custom_script() {

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom' ) {
      wp_register_script( 'options_page', PR_ASSETS_URI . '/js/pr.option_page.js', array( 'jquery' ), '1.0', true );
      wp_enqueue_script( 'options_page' );
    }
  }

  /**
   * add chosen.js to metabox
   *
   * @void
   */
  public function add_chosen_script() {

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom' ) {
      wp_enqueue_style( 'chosen', PR_ASSETS_URI . 'css/chosen.min.css' );
      wp_register_script( 'chosen', PR_ASSETS_URI . '/js/chosen.jquery.min.js', array( 'jquery'), '1.0', true );
      wp_enqueue_script( 'chosen' );
    }
  }

  /**
	 * Load plugin pages
	 *
	 * @void
	 */
  protected function _load_pages() {
    $files = PR_Utils::search_files( __DIR__, 'php' );
  	if ( !empty( $files ) ) {
      foreach ( $files as $file ) {
        require_once $file;
      }
    }
  }
}

$pr_options_page = new PR_options_page();
