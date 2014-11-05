<?php

class TPL_option_page {

  /**
   * constructor method
   * Add class functions to wordpress hooks
   *
   * @void
   */
  public function __construct() {

    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );
    add_action( 'admin_init', array( $this, 'pr_settings_init' ) );

    add_action( 'admin_enqueue_scripts', array( $this, 'add_chosen_script' ) );
    add_action( 'admin_footer', array( $this, 'add_custom_script' ) );

    add_filter( 'pre_update_option_pr_settings', array( $this, 'pr_save_options' ), 10, 2 );

  }

  /**
   * Add options page to wordpress menu
   */
  public function pr_add_admin_menu() {

    add_menu_page( 'pressroom', 'Pressroom', 'manage_options', 'pressroom', array( $this, 'pressroom_options_page' ) );
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
  		'pr_pluginPage_section',
  		__( 'General settings', 'pressroom' ),
  		array( $this, 'pr_settings_section_callback' ),
  		'pressroom'
  	);

  	add_settings_field(
  		'pr-theme',
  		__( 'Default theme', 'pressroom' ),
  		array( $this, 'pr_theme_render' ),
  		'pressroom',
  		'pr_pluginPage_section'
  	);

  	add_settings_field(
  		'pr-maxnumber',
  		__( 'Max edition number', 'pressroom' ),
  		array( $this, 'pr_maxnumber' ),
  		'pressroom',
  		'pr_pluginPage_section'
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
  }

  /**
   * Render theme field
   *
   * @void
   */
  public function pr_theme_render() {

    $themes = array();
    $themes_list = TPL_Theme::get_themes_list();
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

    wp_register_script( 'option_page', TPL_ASSETS_URI . '/js/option_page.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'option_page' );
  }

  /**
    * add chosen.js to metabox
    *
    * @void
    */
  public function add_chosen_script() {

    wp_enqueue_style( 'chosen', TPL_ASSETS_URI . 'css/chosen.min.css' );

    wp_register_script( 'chosen', TPL_ASSETS_URI . '/js/chosen.jquery.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'chosen' );

    wp_enqueue_style( 'tagsinput', TPL_ASSETS_URI . 'css/jquery.tagsinput.css' );

    wp_register_script( 'tagsinput', TPL_ASSETS_URI . '/js/jquery.tagsinput.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'tagsinput' );

  }

}

$tpl_option_page = new TPL_option_page();
