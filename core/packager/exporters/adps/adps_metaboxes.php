<?php
/**
 * PressRoom packager: Adobe DPS metaboxes
 */

final class PR_Packager_ADPS_Metaboxes
{
  protected $_metaboxes = array();

  /**
   * constructor method
   * Add class functions to wordpress hooks
   *
   */
  public function __construct() {

    $options = get_option( 'pr_settings' );
    $exporters = isset( $options['pr_enabled_exporters'] ) ? $options['pr_enabled_exporters'] : false;

    if( !$exporters || !in_array( 'adps', $exporters ) ) {
      return;
    }

    $this->_hooks();
  }

  /**
   * Add custom metaboxes to custom post types
   *
   * @void
   */
  public function add_custom_metaboxes( $post_type, $post ) {

    global $tpl_pressroom;

    if( in_array( $post_type, $tpl_pressroom->get_allowed_post_types() ) ) {

      $this->get_custom_metaboxes( $post->ID );

      foreach ( $this->_metaboxes as $metabox ) {

        add_meta_box($metabox->id, $metabox->title, array($this, 'add_custom_metabox_callback'), $post_type, $metabox->context, $metabox->priority);
      }
    }
  }

  /**
   * Get custom metaboxes configuration
   *
   * @param int $post_id
   * @void
   */
  public function get_custom_metaboxes( $post_id ) {

    // Settings
    $adps_settings = new PR_Metabox( 'adps_settings_metabox', __( 'Adobe DPS settings', 'adps_settings_metabox' ), 'normal', 'default', $post_id );
    $adps_settings->add_field( '_pr_adps_override', __( 'Override Issue settings', 'adps_settings_metabox' ), __( 'If enabled, will be used post settings below', 'adps_settings_metabox' ), 'checkbox', false );
    $adps_settings->add_field( '_pr_adps_smooth_scrolling', __( 'Smooth scrolling', 'adps_settings_metabox' ), '', 'select', '', [
      'options' => [
        [ 'value' => 'never', 'text' => __( "Never", 'adps_settings_metabox' ) ],
        [ 'value' => 'always', 'text' => __( "Both", 'adps_settings_metabox' ) ],
        [ 'value' => 'portrait', 'text' => __( "Portrait", 'adps_settings_metabox' ) ],
        [ 'value' => 'landscape', 'text' => __( "Landscape", 'adps_settings_metabox' ) ],
      ]
    ]);
    $adps_settings->add_field( '_pr_adps_hide_from_toc', __( 'Hide From TOC', 'adps_settings_metabox' ), __( 'Prevent the article from appearing when users tap the TOC button in the viewer nav bar', 'adps_settings_metabox' ), 'checkbox', false );
    $adps_settings->add_field( '_pr_adps_is_flattened_stack', __( 'Horizontal Swipe Only', 'adps_settings_metabox' ), __( 'If enabled, users browse through the article by swiping left and right instead of up and down', 'adps_settings_metabox' ), 'checkbox', false );
    $adps_settings->add_field( '_pr_adps_is_trusted_content', __( 'Trusted content', 'adps_settings_metabox' ), __( 'Allow Access to Entitlement Information', 'adps_settings_metabox' ), 'checkbox', false );
    $adps_settings->add_field( '_pr_adps_article_access', __( 'Article access', 'adps_settings_metabox' ), '', 'select', '', [
      'options' => [
        [ 'value' => 'free', 'text' => __( "Free", 'adps_settings_metabox' ) ],
        [ 'value' => 'metered', 'text' => __( "Metered", 'adps_settings_metabox' ) ],
        [ 'value' => 'protected', 'text' => __( "Protected", 'adps_settings_metabox' ) ]
      ]
    ]);

    $this->_metaboxes['adps_settings_metabox'] = $adps_settings;

    // Custom fields
    $adps_fields = new PR_Metabox( 'adps_fields_metabox', __( 'Adobe DPS fields', 'adps_fields_metabox' ), 'normal', 'high', $post_id );
    $adps_fields->add_field( '_pr_adps_title', __( 'Title', 'adps_fields_metabox' ), __( 'The Title appears in the table of contents and in folio navigation views. The maximum number of characters for Title is 60', 'adps_fields_metabox' ), 'text', false );
    $adps_fields->add_field( '_pr_adps_byline', __( 'Byline', 'adps_fields_metabox' ), __( 'Specify the author’s name. The maximum number of characters for Byline is 40', 'adps_fields_metabox' ), 'text', false );
    $adps_fields->add_field( '_pr_adps_kicker', __( 'Kicker', 'adps_fields_metabox' ), __( 'The section title of a magazine, such as “Reviews,” “Features,” or “Editorial.” The kicker appears in the table of contents and in folio navigation views. The maximum number of characters for Kicker is 35', 'adps_fields_metabox' ), 'text', false );
    $adps_fields->add_field( '_pr_adps_description', __( 'Description', 'adps_fields_metabox' ), __( 'Describe the article. The description appears when the folio is viewed in browse mode. The Description appears in the table of contents and in folio navigation views. The maximum number of characters for Description is 120.', 'adps_fields_metabox' ), 'text', false );
    $adps_fields->add_field( '_pr_adps_section', __( 'Section', 'adps_fields_metabox' ), __( 'If you divide a folio into sections such as Sports, Business, and Style, specify a section name for each article in the folio. The maximum number of characters for Section is 60.', 'adps_fields_metabox' ), 'text', false );

    $this->_metaboxes['adps_fields_metabox'] = $adps_fields;
  }

  /**
   * Custom metabox callback print html input field
   *
   * @echo
   */
  public function add_custom_metabox_callback( $post, $metabox ) {

    echo '<table class="form-table">';
    $custom_metabox = $this->_metaboxes[$metabox['id']];
    echo $custom_metabox->fields_to_html();
    echo '</table>';
  }

  /**
   * Save metabox form data
   *
   * @param  int $post_id
   * @void
   */
  public function save_pr_adps_fields( $post_id ) {

    //Check autosave
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
      return $post_id;
    }

    //Check permissions
    if ( !current_user_can( 'edit_page', $post_id ) ) {
      return $post_id;
    }

    $this->get_custom_metaboxes( $post_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_values();
    }
  }

  /**
   * Create metabox and custom fields
   *
   * @param object &$metaboxes
   * @param int $item_id (it can be editorial project id or edition id);
   * @void
   */
  public function pr_add_eproject_metabox( &$metaboxes, $item_id, $edition = false ) {

    $this->get_custom_metaboxes( $item_id );
    $adps_settings = $this->_metaboxes['adps_settings_metabox'];

    // Remove override field
    $adps_settings->remove_field( '_pr_adps_override' );
    if( $edition ) {
      $adps_settings->prepend_field( '_pr_adps_override_eproject', __( 'Override Editorial Project settings', 'editorial_project' ), __( 'If enabled, will be used edition settings below', 'edition' ), 'checkbox', false );
    }
    $adps_settings->add_field( '_pr_adps_download_path', __( 'Local download path', 'adps_package' ), __( 'Local download path (i.e. "/Users/mike/Downloads")', 'adps_package' ), 'text', false );

    array_push( $metaboxes, $adps_settings );
  }

  /**
   * Add custom script to metabox
   *
   * @void
   */
  public function add_custom_script() {

    wp_register_script( 'adps_metabox', PR_ASSETS_URI . '/js/pr.adps.metabox.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'adps_metabox' );
  }

  /**
   * Register hooks
   * @void
   */
  protected function _hooks() {

    add_action( 'pr_add_eproject_tab', [ $this, 'pr_add_eproject_metabox' ], 10, 2 );
    add_action( 'pr_add_edition_tab', [ $this, 'pr_add_eproject_metabox' ], 10, 3 );

    add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
		add_action( 'save_post', array( $this, 'save_pr_adps_fields'), 40 );
    add_action( 'admin_footer', array( $this, 'add_custom_script' ) );
  }
}

$pr_adps_metaboxes = new PR_Packager_ADPS_Metaboxes;
