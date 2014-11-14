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

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom' ) {

      add_action( 'admin_enqueue_scripts', array( $this, 'add_chosen_script' ) );
      add_action( 'admin_footer', array( $this, 'add_custom_script' ) );
    }

    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );
    add_action( 'admin_init', array( $this, 'pr_settings_init' ) );
    add_filter( 'pre_update_option_pr_settings', array( $this, 'pr_save_options' ), 10, 2 );
    add_action( 'wp_ajax_refresh_cache_theme', array( $this, 'refresh_cache_theme' ) );
  }

  /**
   * Add options page to wordpress menu
   */
  public function pr_add_admin_menu() {

    add_menu_page( 'pressroom', 'Pressroom', 'manage_options', 'pressroom', array( $this, 'pressroom_options_page' ) );
    add_submenu_page('pressroom', __('Settings'), __('Settings'), 'manage_options', 'pressroom', array( $this, 'pressroom_options_page' ));
  }

  /**
   * add option to database
   *
   * @void
   */
  protected function pr_settings_exist() {

  	if( false == get_option( 'pressroom_settings' ) ) {

  		add_option( 'pressroom_settings' );

  	}

  }

  /**
   * register section field
   *
   * @void
   */
  public function pr_settings_init() {

  	register_setting( 'pressroom', 'pr_settings' );

  	add_settings_section(
  		'tpl_pressroom_section',
  		__( 'General settings', 'pressroom' ),
  		array( $this, 'pr_settings_section_callback' ),
  		'pressroom'
  	);

  	add_settings_field(
  		'pr-theme',
  		__( 'Default theme', 'pressroom' ),
  		array( $this, 'pr_theme_render' ),
  		'pressroom',
  		'tpl_pressroom_section'
  	);

  	add_settings_field(
  		'pr-maxnumber',
  		__( 'Max edition number', 'pressroom' ),
  		array( $this, 'pr_maxnumber' ),
  		'pressroom',
  		'tpl_pressroom_section'
  	);

    add_settings_field(
      'pr-sharing-domain',
      __( 'Sharing Domain', 'pressroom' ),
      array( $this, 'pr_sharing_domain' ),
      'pressroom',
      'tpl_pressroom_section'
    );

    add_settings_section(
      'pr_plugin_pro',
      __( 'Pro settings', 'pressroom' ),
      array( $this, 'pr_settings_section_pro_callback' ),
      'pressroom'
    );

    add_settings_field(
      'custom_post_type',
      __( 'Custom post types', 'pressroom' ),
      array( $this, 'pr_custom_post_type' ),
      'pressroom',
      'pr_plugin_pro'
    );

    add_settings_field(
      'pr_license',
      __( 'License key', 'pressroom' ),
      array( $this, 'pr_license_key' ),
      'pressroom',
      'pr_plugin_pro'
    );
  }

  /**
   * Render theme field
   *
   * @void
   */
  public function pr_theme_render() {

    $themes = array();
    $themes_list = PR_Theme::get_themes_list();
    foreach ( $themes_list as $theme ) {
      $themes[$theme['value']] = $theme['text'];
    }
  	$options = get_option( 'pr_settings' );
  	?>
  	<select name='pr_settings[pr-theme]' class="chosen-select">
      <?php
      foreach ( $themes as $theme ) {
        echo '<option value="' . $theme . '" ' . selected( $options['pr-theme'], $theme ) . ' > ' . $theme . '</option>';
      }
      ?>
  	</select>
    <a href="#" class="button button-primary" id="theme_refresh">Flush themes cache</a>
  <?php

  }

  /**
   * Render max number field
   *
   * @void
   */
  public function pr_maxnumber() {

  	$options = get_option( 'pr_settings' );
  	?>
  	<input type='number' name='pr_settings[pr-maxnumber]' value='<?php echo ( isset( $options['pr-maxnumber'] ) ? $options['pr-maxnumber'] : '') ?>'>
  	<?php
  }

  public function pr_sharing_domain() {

    $options = get_option( 'pr_settings' );
    ?>
    <input type='text' placeholder="<?=get_site_url();?>" name='pr_settings[pr-sharing-domain]' value='<?php echo ( isset( $options['pr-sharing-domain'] ) ? $options['pr-sharing-domain'] : '') ?>'>
    <?php
  }

  /**
   * Render custom_post_type field
   *
   * @void
   */
  public function pr_custom_post_type() {

  	$options = get_option( 'pr_settings' );
  	?>
  	<input id="pr_custom_post_type" type='text' name='pr_settings[pr_custom_post_type]' value='<?php echo ( isset( $options['pr_custom_post_type'] ) ? implode( ',', $options['pr_custom_post_type']) : '') ?>'>
  	<?php

  }

  /**
   * Render custom_post_type field
   *
   * @void
   */
  public function pr_license_key() {

    edd_license_key_callback(array(
      'id' => 'edd_pressroom_pro_license_key',
      'options' => array('is_valid_license_option' => false),
      'desc' => 'banana'
    ));
  }

  /**
   * render setting section
   *
   * @echo
   */
  public function pr_settings_section_callback() {

  	echo __( 'Basic option for pressroom<hr/>', 'pressroom' );

  }

  /**
   * render setting pro section
   *
   * @echo
   */
  public function pr_settings_section_pro_callback() {

    echo __( 'Pro setting option<hr/>', 'pressroom' );

  }

  /**
   * Render option page form
   *
   * @echo
   */
  public function pressroom_options_page() {
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
      $new_value['pr_custom_post_type'] = explode( ',', $new_value['pr_custom_post_type'] );
    }

    return $new_value;
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  public function add_custom_script() {

    wp_register_script( 'options_page', PR_ASSETS_URI . '/js/pr.option_page.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'options_page' );
  }

  /**
   * add chosen.js to metabox
   *
   * @void
   */
  public function add_chosen_script() {

    wp_enqueue_style( 'chosen', PR_ASSETS_URI . 'css/chosen.min.css' );
    wp_register_script( 'chosen', PR_ASSETS_URI . '/js/chosen.jquery.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'chosen' );
    wp_enqueue_style( 'tagsinput', PR_ASSETS_URI . 'css/jquery.tagsinput.css' );
    wp_register_script( 'tagsinput', PR_ASSETS_URI . '/js/jquery.tagsinput.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'tagsinput' );
  }


  public function refresh_cache_theme() {
    delete_option( 'pressroom_themes' );
    die();
  }
}

$pr_options_page = new PR_options_page();
