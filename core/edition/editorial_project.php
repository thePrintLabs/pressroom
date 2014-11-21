<?php
/**
 * PressRoom Editorial Project class.
 * Custom taxonomies for Edition custom post type
 */
class PR_Editorial_Project
{
  protected $_metaboxes;

  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_editorial_project_taxonomy' ), 10 );
    add_action( 'init', array( $this, 'add_editorial_project_taxonomy' ), 0 );

    if ( is_admin() ) {
      add_filter( 'manage_edit-' . PR_EDITORIAL_PROJECT . '_columns', array( $this, 'editorial_project_columns' ) );
      add_filter( 'manage_' . PR_EDITORIAL_PROJECT . '_custom_column', array( $this, 'manage_columns' ), 10, 3 );
      add_filter( 'wp_tag_cloud', array( $this, 'remove_tag_cloud' ), 10, 2 );

      add_action( PR_EDITORIAL_PROJECT . '_edit_form', array( $this, 'add_tabs_to_form' ), 10, 2 );
      add_action( PR_EDITORIAL_PROJECT . '_edit_form_fields', array( $this, 'edit_form_meta_fields' ), 10, 2 );
      add_action( PR_EDITORIAL_PROJECT . '_add_form', array( $this,'customize_form' ) );
      add_action( PR_EDITORIAL_PROJECT . '_edit_form', array( $this,'customize_form' ) );
      add_action( 'edited_' . PR_EDITORIAL_PROJECT, array( $this, 'update_form_meta_fields' ) );
      add_action( 'create_' . PR_EDITORIAL_PROJECT, array( $this, 'save_form_meta_fields' ) );
      add_action( PR_EDITORIAL_PROJECT . '_term_edit_form_tag', array( $this,'form_add_enctype' ) );
      add_action( 'wp_ajax_remove_upload_file', array( $this, 'remove_upload_file_callback' ) );
      add_action( 'wp_ajax_reset_editorial_project', array( $this, 'reset_editorial_project' ) );
      add_action( 'admin_footer', array( $this, 'add_custom_script' ) );

    }
  }

  /**
  * Add taxonomy editorial_project for edition custom post type
  *
  * @void
  */
  public function add_editorial_project_taxonomy() {

    $labels = array(
      'name'                       => _x( 'Editorial Projects', 'editorial_project' ),
      'singular_name'              => _x( 'Editorial Project', 'editorial_project' ),
      'search_items'               => __( 'Search editorial project' ),
      'popular_items'              => __( 'Popular editorial project' ),
      'all_items'                  => __( 'All editorial project' ),
      'parent_item'                => null,
      'parent_item_colon'          => null,
      'edit_item'                  => __( 'Edit editorial project' ),
      'update_item'                => __( 'Update editorial project' ),
      'add_new_item'               => __( 'Add New editorial project' ),
      'new_item_name'              => __( 'New editorial project' ),
      'separate_items_with_commas' => __( 'Separate editorial project with commas' ),
      'add_or_remove_items'        => __( 'Add or remove editorial projects' ),
      'not_found'                  => __( 'No editorial project found.' ),
      'menu_name'                  => __( 'Editorial project' ),
    );

    $args = array(
      'hierarchical'          => true,
      'labels'                => $labels,
      'show_ui'               => true,
      'show_admin_column'     => true,
      'query_var'             => true,
      'rewrite'               => array( 'slug' => 'editorial-project' ),
    );

    register_taxonomy( PR_EDITORIAL_PROJECT, PR_EDITION, $args );
  }


  /**
  * Define columns on list table
  *
  * @param  array $edit_columns
  * @return array
  */
  public function editorial_project_columns( $edit_columns ) {

    $new_columns = array(
       'cb'           => '<input type="checkbox" />',
       'name'         => __( 'Name' ),
       'slug'         => __( 'Slug' ),
       'posts'        => __( 'Posts' ),
       'actions'  => __( 'Actions', 'editorial_project'),
    );

    return $new_columns;
  }

  /**
  * Custom column managment
  *
  * @param  string $out
  * @param  string $column_name
  * @param  int $editorial_id
  * @return string
  */
  public function manage_columns( $out, $column_name, $editorial_id ) {

    $editorial = get_term( $editorial_id, PR_EDITORIAL_PROJECT );
    switch ( $column_name ) {

       case 'actions':
          $shelf_url = home_url( 'pressroom-api/shelf/' . $editorial->slug );
          echo '<a target="_blank" href="' . $shelf_url . '">' . __("View shelf endpoint", 'editorial_project') . '</a><br/>';
          echo '<a href="#" data-term="'.$editorial_id.'" class="pr-reset" style="color:#A00">Restore default settings</a>';
          break;
       default:
          break;
    }

    return $out;
  }

  /**
   * Add fields to custom editorial project metabox
   *
   * @param  int $term_id
   */
  public function get_custom_metabox( $term_id ) {

    $hpub = new PR_Metabox( 'hpub_metabox', __( 'hpub', 'editorial_project' ), 'normal', 'high', $term_id );
    $hpub->add_field( '_pr_default', '<h3>Visualization properties</h3><hr>', '', 'textnode', '' );
    $hpub->add_field( '_pr_orientation', __( 'Orientation', 'editorial_project' ), __( 'The publication orientation.', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'portrait', 'name' => __( "Portrait", 'editorial_project' ) ),
        array( 'value' => 'landscape', 'name' => __( "Landscape", 'editorial_project' ) ),
        array( 'value' => 'both', 'name' => __( "Both", 'editorial_project' ) )
      )
    ) );
    $hpub->add_field( '_pr_zoomable', __( 'Zoomable', 'editorial_project' ), __( 'Enable pinch to zoom of the page.', 'editorial_project' ), 'checkbox', false );
    $hpub->add_field( '_pr_body_bg_color', __( 'Body background color', 'edition' ), __( 'Background color to be shown before pages are loaded.', 'editorial_project' ), 'color', '' );

    $hpub->add_field( '_pr_background_image_portrait', __( 'Background image portrait', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in portrait mode.', 'editorial_project' ), 'file', '' );
    $hpub->add_field( '_pr_background_image_landscape', __( 'Background image landscape', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in landscape mode.', 'editorial_project' ), 'file', '' );
    $hpub->add_field( '_pr_page_numbers_color', __( 'Page numbers color', 'edition' ), __( 'Color for page numbers to be shown before pages are loaded.', 'editorial_project' ), 'color', '#ffffff' );
    $hpub->add_field( '_pr_page_numbers_alpha', __( 'Page number alpha', 'edition' ), __( 'Opacity for page numbers to be shown before pages are loaded. (min 0 => max 1)', 'editorial_project' ), 'decimal', 0.3 );
    $hpub->add_field( '_pr_page_screenshot', __( 'Page Screenshoot', 'edition' ), __( 'Path to a folder containing the pre-rendered pages screenshots.', 'editorial_project' ), 'text', '' );
    $hpub->add_field( '_pr_default', '<h3>Behaviour properties</h3><hr>', '', 'textnode', '' );

    $hpub->add_field( '_pr_start_at_page', __( 'Start at page', 'edition' ), __( 'Defines the starting page of the publication. If the number is negative, the publication starting at the end and with numbering reversed. ( Note: this setting works only the first time edition is opened )', 'editorial_project' ), 'number', 1 );
    $hpub->add_field( '_pr_rendering', __( 'Rendering type', 'editorial_project' ), __( 'App rendering mode. See the page on <a target="_blank" href="https://github.com/Simbul/baker/wiki/Baker-rendering-modes">Baker rendering modes.</a>', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'screenshots', 'name' => __( "Screenshots", 'editorial_project' ) ),
        array( 'value' => 'three-cards', 'name' => __( "Three cards", 'editorial_project' ) )
      )
    ) );
    $hpub->add_field( '_pr_vertical_bounce', __( 'Vertical Bounce', 'edition' ), __( 'Bounce animation when vertical scrolling interaction reaches the end of a page.', 'editorial_project' ), 'checkbox', true );
    $hpub->add_field( '_pr_media_autoplay', __( 'Media autoplay', 'edition' ), __( 'Media should be played automatically when the page is loaded.', 'editorial_project' ), 'checkbox', true );
    $hpub->add_field( '_pr_vertical_pagination', __( 'Vertical pagination', 'edition' ), __( 'Vertical page scrolling should be paginated in the whole publication.', 'editorial_project' ), 'checkbox', false );
    $hpub->add_field( '_pr_page_turn_tap', __( 'Page turn tap', 'edition' ), __( 'Tap on the right (or left) side to go forward (or back) by one page.', 'editorial_project' ), 'checkbox', true );
    $hpub->add_field( '_pr_page_turn_swipe', __( 'Page turn swipe', 'edition' ), __( 'Swipe on the page to go forward (or back) by one page.', 'editorial_project' ), 'checkbox', true );

    $hpub->add_field( '_pr_default', '<h3>Toc properties</h3><hr>', '', 'textnode', '' );
    $hpub->add_field( '_pr_index_height', __( 'TOC height', 'edition' ), __( 'Height (in pixels) for the toc bar.', 'editorial_project' ), 'number', 150 );
    $hpub->add_field( '_pr_index_width', __( 'TOC width', 'edition' ), __( 'Width (in pixels) for the toc bar. When empty, the width is automatically set to the width of the page.', 'editorial_project' ), 'number', '' );
    $hpub->add_field( '_pr_index_bounce', __( 'TOC bounce', 'edition' ), __( 'Bounce effect when a scrolling interaction reaches the end of the page.', 'editorial_project' ), 'checkbox', false );

    $sub_meta = new PR_Metabox( 'sub_metabox', __( 'In-App Purchases', 'editorial_project' ), 'normal', 'high', $term_id );
    $sub_meta->add_field( '_pr_itunes_secret', __( 'iTunes Shared Secret', 'editorial_project' ), __( 'A shared secret is a unique code that you should use when you make the call to our servers for your In-App Purchase receipts.', 'editorial_project' ), 'text', '' );
    $sub_meta->add_field( '_pr_prefix_bundle_id', __( 'App Bundle ID', 'edition' ), __( 'Application Bundle ID is the unique identifier of your application', 'editorial_project' ), 'text', '' );
    $sub_meta->add_field( '_pr_single_edition_prefix', __( 'Single edition prefix', 'edition' ), __( 'Single edition prefix', 'editorial_project' ), 'text_autocompleted', '' );
    $sub_meta->add_field( '_pr_subscription_prefix', __( 'Subscription prefix', 'edition' ), __( 'Subscription prefix', 'editorial_project' ), 'text_autocompleted', '' );
    $sub_meta->add_field( '_pr_subscription_types', __( 'Subscription types', 'edition' ), __( 'Subscription types', 'editorial_project' ), 'repeater_with_radio', '', array(
      'radio_field'   => '_pr_subscription_method',
      'radio_options' => array(
        array( 'value' => 'all', 'name' => __( "All", 'editorial_project' ) ),
        array( 'value' => 'last', 'name' => __( "Last edition", 'editorial_project' ) )
      ),
    ) );

    $push_meta = new PR_Metabox( 'push_metabox', __( 'Push notification', 'editorial_project' ), 'normal', 'high', $term_id );
    $push_meta->add_field( 'pr_push_service', __( 'Push service', 'editorial_project' ), __( 'Push notification service', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'parse', 'name' => __( "Parse", 'editorial_project' ) ),
        array( 'value' => 'urbanairship', 'name' => __( "Urban Airship", 'editorial_project' ) ),
      )
    ) );
    $push_meta->add_field( 'pr_push_api_app_id', __( 'App key', 'edition' ), __( 'The main identifier of your app. <b>Urban Airship</b>: <i>App Key</i><b> - Parse: </b><i>Application Id</i>', 'editorial_project' ), 'text', '' );
    $push_meta->add_field( 'pr_push_api_key', __( 'App secret', 'edition' ), __( 'The secret token for authentication <b>Urban Airship</b>: <i>App Master Secret</i><b> - Parse: </b><i>REST API Key</i> ', 'editorial_project' ), 'text', '' );

    $metaboxes = array();
    do_action_ref_array( 'pr_add_eproject_tab', array( &$metaboxes, $term_id ) );

    $this->_metaboxes = array(
      $hpub,
      $sub_meta,
      $push_meta,
    );
    
    $this->_metaboxes = array_merge( $this->_metaboxes, $metaboxes );
  }

  /**
   * Add tabs menu to edit form
   *
   * @param object $term
   * @echo
   */
  public function add_tabs_to_form( $term ) {

    $this->get_custom_metabox( $term->term_id );
    echo '<h2 class="nav-tab-wrapper pr-tab-wrapper">';
    foreach ( $this->_metaboxes as $key => $metabox ) {
      echo '<a class="nav-tab ' . ( !$key ? 'nav-tab-active' : '' ) . '" data-tab="'.$metabox->id.'" href="#">' . $metabox->title . '</a>';
    }
    echo '</h2>';
  }


  /**
   * delete editorial project options
   *
   * @echo
   */
  public function reset_editorial_project() {

    $term_id = $_POST['term_id'];
    if( delete_option( 'taxonomy_term_' . $term_id ) ){
      $this->get_custom_metabox( $term_id );
      foreach ( $this->_metaboxes as $metabox ) {
        $metabox->save_term_values();
      }
      wp_send_json_success();
    }
    exit;
  }

  /**
  * Define the custom meta fields for new entry
  *
  * @param  object $term
  * @echo
  */
  public function edit_form_meta_fields( $term ) {

    $this->get_custom_metabox( $term->term_id );
    foreach ( $this->_metaboxes as $key => $metabox ) {
      echo $metabox->fields_to_html( true, $metabox->id );
    }

    // echo $this->add_reset_to_form();
  }

  /**
  * Save the values ​​in a custom option
  *
  * @param  int $term_id
  * @void
  */
  public function save_form_meta_fields( $term_id ) {

    $terms = get_terms( PR_EDITORIAL_PROJECT );
    foreach( $terms as $term ) {
      $option = self::get_config( $term->term_id, '_pr_prefix_bundle_id');
      if ( isset($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values();
    }


    if( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
      $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
      wp_redirect( $url );
      exit;
    }

  }

  /**
   * Save the values ​​in a custom option
   *
   * @param  int $term_id
   * @void
   */
  public function update_form_meta_fields( $term_id ) {

    $terms = get_terms( PR_EDITORIAL_PROJECT );
    foreach( $terms as $term ) {
      $option = self::get_config( $term->term_id, '_pr_prefix_bundle_id');
      if ( isset($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values( true );
    }

    if( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
      $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
      wp_redirect( $url );
      exit;
    }
  }

  /**
   * Add enctype to form for files upload
   *
   * @echo
   */
  public function form_add_enctype() {

    echo ' enctype="multipart/form-data"';
  }

  /**
   * Remove description and parent fields.
   * Move tabs after title
   * @echo
   */
  public function customize_form() {
  ?>
    <script type="text/javascript">
      jQuery(function(){
        jQuery('#tag-description, #description, #parent').closest('.form-field').remove();
        var tr = jQuery('<tr><td id="moved-tab" colspan="2"></td></tr>');
        tr.insertAfter(jQuery('#slug').closest('.form-field'));
        jQuery('.nav-tab-wrapper').appendTo('#moved-tab');
        jQuery('input[name="pr_push_service"]').change(function(){
          var $this = jQuery(this);
          if ( $this.is(':checked') ) {
            if ( $this.val() == 'urbanairship' ) {
              jQuery('label[for="pr_push_api_app_id"]').html('App Key');
              jQuery('label[for="pr_push_api_key"]').html('App Master Secret');
              jQuery('#pr_push_api_app_id').next('.description').html('Urban Airship generated string identifying the app setup. Used in the application bundle.');
              jQuery('#pr_push_api_key').next('.description').html('Urban Airship generated string used for server to server API access. This should never be shared or placed in an application bundle.');
            } else if ( $this.val() == 'parse' ) {
              jQuery('label[for="pr_push_api_app_id"]').html('Application ID');
              jQuery('label[for="pr_push_api_key"]').html('REST API Key');
              jQuery('#pr_push_api_app_id').next('.description').html('This is the main identifier that uniquely specifies your application. This is paired with a key to provide your clients access to your application\'s data.');
              jQuery('#pr_push_api_key').next('.description').html('This key should be used when making requests to the REST API. It also adheres to object level permissions.');
            }
          }
        }).change();
      });
    </script>
  <?php
  }

  public function remove_tag_cloud ( $return, $args ) {
    if ( $args['taxonomy'] == PR_EDITORIAL_PROJECT ) {
      return false;
    }
  }

  /**
   * Get an editorial project by slug
   * @param string $slug
   * @return object
   */
  public static function get_by_slug( $slug ) {

    $eproject = get_term_by( 'slug', $slug, PR_EDITORIAL_PROJECT );
    return $eproject;
  }

  /**
   * Get all configs for single editorial project
   * load configs for single editorial project
   *
   * @param  int $term_id
   * @return array $term_meta
   */
  public static function get_configs( $term_id ) {

    $options = get_option( 'taxonomy_term_' . $term_id );
    return $options;
  }

  /**
   * Load a config for single editorial project
   * @param int $term_id
   * @param string $meta_name
   * @return mixed
   */
  public static function get_config( $term_id , $meta_name ) {

    $options = self::get_configs( $term_id );
    return isset( $options[$meta_name] ) ? $options[$meta_name] : false;
  }

  public static function get_subscription_method( $term_id, $product_id ) {

    $options = self::get_configs( $term_id );
    $subscription_types = $options['_pr_subscription_types'];
    $subscription_methods = $options['_pr_subscription_method'];

    if ( isset( $subscription_types ) && !empty( $subscription_types ) ) {
      foreach ( $subscription_types as $k => $type ) {
        $identifier = $options['_pr_prefix_bundle_id'] . '.' . $options['_pr_subscription_prefix']. '.' . $type;
        if ( $identifier == $product_id ) {
          return $subscription_methods[$k];
        }
      }
    }
    return false;
  }

  /**
   * Get all editions in a range of dates
   * linked to an editiorial project
   * @param  object $eproject
   * @param  string $start_date
   * @param  string $end_date
   * @return array
   */
  public static function get_editions_in_range( $eproject, $start_date, $end_date ) {

    $editions_query = new WP_Query( array(
      'nopaging'        => true,
      'posts_per_page'  => -1,
      'post_status'     => 'publish',
      'post_type'       => PR_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => PR_EDITORIAL_PROJECT,
          'field'     => 'slug',
          'terms'     => $eproject->slug
        )
      ),
      'meta_query'    => array(
        array(
          'key'     => '_pr_date',
          'value'   => array( $start_date, $end_date ),
          'type'    => 'DATE',
          'compare' => 'BETWEEN'
        )
      )
    ));
    $editions = $editions_query->posts;
    return $editions;
  }

  /**
   * Get all published editions
   * linked to an editiorial project
   * @param  object/string $eproject
   * @return array
   */
  public static function get_all_editions( $eproject, $limit = -1 ) {

    $editions_query = new WP_Query( array(
      'nopaging'        => true,
      'posts_per_page'  => $limit,
      'post_status'     => 'publish',
      'post_type'       => PR_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => PR_EDITORIAL_PROJECT,
          'field'     => 'slug',
          'terms'     => is_string( $eproject ) ? $eproject : $eproject->slug
        )
      )
    ));
    $editions = $editions_query->posts;
    return $editions;
  }

  /**
   * Get latest published edition
   * linked to an editiorial project
   * @param  object $eproject
   * @return array
   */
  public static function get_latest_edition( $eproject ) {

    $editions_query = new WP_Query( array(
      'nopaging'        => true,
      'numberposts'     => 1,
      'posts_per_page'  => 1,
      'post_status'     => 'publish',
      'post_type'       => PR_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => PR_EDITORIAL_PROJECT,
          'field'     => 'slug',
          'terms'     => $eproject->slug
        )
      ),
    ));
    $editions = $editions_query->posts;
    if ( !empty( $editions ) ) {
      return $editions[0];
    }
    return false;
  }

  /**
   * Get the bundle id of an editorial project
   * @param int $editorial_project_id
   * @return string or boolean false
   */
  public static function get_bundle_id( $editorial_project_id ) {

    $eproject_bundle_id = false;
    $eproject_options = PR_Editorial_Project::get_configs( $editorial_project_id );
    if ( $eproject_options ) {
      $eproject_bundle_id = isset( $eproject_options['_pr_prefix_bundle_id'] ) ? $eproject_options['_pr_prefix_bundle_id'] : '';
    }
    return $eproject_bundle_id;
  }

  /**
   * Delete attachment and editorial project option
   *
   * @echo
   */
  public function remove_upload_file_callback() {

    $editorial_project_id = $_POST['term_id'];
    $term_meta = PR_Editorial_Project::get_configs( $editorial_project_id );
    $attach_id = $_POST['attach_id'];
    $field = $_POST['field'];
    $term_meta[$field] = '';
    if( update_option( 'taxonomy_term_' . $editorial_project_id, $term_meta ) ) {
      wp_delete_attachment( $attach_id );
      echo "removed";
    }
    exit;
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  public function add_custom_script() {

    wp_register_script( 'eproject', PR_ASSETS_URI . '/js/pr.eproject.min.js', array( 'jquery' ), '1.0', true );
    wp_enqueue_script( 'eproject' );
  }
}

$pr_editorial_project = new PR_Editorial_Project();
