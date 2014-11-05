<?php
/**
 * TPL Editorial Project class.
 * Custom taxonomies for Edition custom post type
 */
class TPL_Editorial_Project
{
  protected $_metaboxes;

  public function __construct() {

    add_action( 'init', array( $this, 'add_editorial_project_taxonomy' ), 0 );

    if ( is_admin() ) {
      add_filter( 'manage_edit-' . TPL_EDITORIAL_PROJECT . '_columns', array( $this, 'editorial_project_columns' ) );
      add_filter( 'manage_' . TPL_EDITORIAL_PROJECT . '_custom_column', array( $this, 'manage_columns' ), 10, 3 );
      add_action( TPL_EDITORIAL_PROJECT . '_pre_edit_form', array( $this, 'pre_edit_form' ), 10, 2 );
      add_action( TPL_EDITORIAL_PROJECT . '_edit_form_fields', array( $this, 'edit_form_meta_fields' ), 10, 2 );
      add_action( 'edited_' . TPL_EDITORIAL_PROJECT, array( $this, 'update_form_meta_fields' ) );
      add_action( 'create_' . TPL_EDITORIAL_PROJECT, array( $this, 'save_form_meta_fields' ) );
      add_action( TPL_EDITORIAL_PROJECT . '_term_edit_form_tag', array( $this,'form_add_enctype' ) );

    }
    global $pagenow;
    if ( $pagenow == 'edit-tags.php' && isset( $_GET['taxonomy'] ) && $_GET['taxonomy'] == TPL_EDITORIAL_PROJECT ) {
      add_action( 'admin_footer', array( $this,'remove_form_fields' ) );
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

    register_taxonomy( TPL_EDITORIAL_PROJECT, TPL_EDITION, $args );
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
       'header_icon'  => __( 'Shelf.json', 'editorial_project'),
       'slug'         => __( 'Slug' ),
       'posts'        => __( 'Posts' )
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

    $editorial = get_term( $editorial_id, TPL_EDITORIAL_PROJECT );
    switch ( $column_name ) {

       case 'header_icon':
          $shelf_url = home_url( 'pressroom-api/shelf/' . $editorial->slug );
          echo '<a href="' . $shelf_url . '">' . __("View endpoint", 'editorial_project') . '</a>';
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

    $basic_meta = new TPL_Metabox( 'basic_metabox', __( 'Basic', 'editorial_project' ), 'normal', 'high', $term_id );
    $vis_meta = new TPL_Metabox( 'vis_metabox', __( 'Visualization', 'editorial_project' ), 'normal', 'high', $term_id );
    $behavior_meta = new TPL_Metabox( 'behavior_metabox', __( 'Behaviour', 'editorial_project' ), 'normal', 'high', $term_id );
    $book_meta = new TPL_Metabox( 'book_metabox', __( 'Book extended', 'editorial_project' ), 'normal', 'high', $term_id );
    $sub_meta = new TPL_Metabox( 'sub_metabox', __( 'Subscriptions', 'editorial_project' ), 'normal', 'high', $term_id );
    $push_meta = new TPL_Metabox( 'push_metabox', __( 'Notification push', 'editorial_project' ), 'normal', 'high', $term_id );

    $basic_meta->add_field( '_pr_default', '<h3>Basic option</h1><hr>', '', 'textnode', '' );
    $basic_meta->add_field( '_pr_itunes_secret', __( 'Itunes secret', 'editorial_project' ), __( 'Itunes secret', 'editorial_project' ), 'text', '' );
    $basic_meta->add_field( '_pr_orientation', __( 'Orientation', 'editorial_project' ), __( 'Orientation', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'horizontal', 'name' => __( "Horizontal", 'editorial_project' ) ),
        array( 'value' => 'vertical', 'name' => __( "Vertical", 'editorial_project' ) ),
        array( 'value' => 'both', 'name' => __( "Both", 'editorial_project' ) )
      )
    ) );
    $basic_meta->add_field( '_pr_zoomable', __( 'Zoomable', 'editorial_project' ), __( 'Zoomable', 'editorial_project' ), 'checkbox', false );
    $basic_meta->add_field( '_pr_body_bg_color', __( 'Body background color', 'edition' ), __( 'Body background color', 'editorial_project' ), 'color', '' );

    $vis_meta->add_field( '_pr_default', '<h3>Visualization properties</h1><hr>', '', 'textnode', '' );
    $vis_meta->add_field( '_pr_background_image_portrait', __( 'Background image portrait', 'edition' ), __( 'Background image portrait', 'editorial_project' ), 'file', '' );
    $vis_meta->add_field( '_pr_background_image_landscape', __( 'Background image landscape', 'edition' ), __( 'Background image landscape', 'editorial_project' ), 'file', '' );
    $vis_meta->add_field( '_pr_page_numbers_color', __( 'Page numbers color', 'edition' ), __( 'Page numbers color', 'editorial_project' ), 'color', '' );
    $vis_meta->add_field( '_pr_page_numbers_alpha', __( 'Page number alpha', 'edition' ), __( 'Page number alpha', 'editorial_project' ), 'decimal', '' );
    $vis_meta->add_field( '_pr_page_screenshot', __( 'Page Screenshoot', 'edition' ), __( 'Path to a folder containing the pre-rendered pages screenshots.', 'editorial_project' ), 'text', '' );

    $behavior_meta->add_field( '_pr_default', '<h3>Behaviour properties</h1><hr>', '', 'textnode', '' );
    $behavior_meta->add_field( '_pr_rendering', __( 'Rendering type', 'editorial_project' ), __( 'Rendering type', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'screenshots', 'name' => __( "Screenshots", 'editorial_project' ) ),
        array( 'value' => 'three-cards', 'name' => __( "Three cards", 'editorial_project' ) )
      )
    ) );
    $behavior_meta->add_field( '_pr_verticle_bounce', __( 'Vertical Bounce', 'edition' ), __( 'Vertical Bounce', 'editorial_project' ), 'checkbox', true );
    $behavior_meta->add_field( '_pr_media_autoplay', __( 'Media autoplay', 'edition' ), __( 'Media autoplay', 'editorial_project' ), 'checkbox', true );
    $behavior_meta->add_field( '_pr_vertical_pagination', __( 'Vertical pagination', 'edition' ), __( 'Vertical pagination', 'editorial_project' ), 'checkbox', false );
    $behavior_meta->add_field( '_pr_page_turn_tap', __( 'Page turn tap', 'edition' ), __( 'Page turn tap', 'editorial_project' ), 'checkbox', true );
    $behavior_meta->add_field( '_pr_page_turn_swipe', __( 'Page turn swipe', 'edition' ), __( 'Page turn swipe', 'editorial_project' ), 'checkbox', true );

    $book_meta->add_field( '_pr_default', '<h3>Book extended</h1><hr>', '', 'textnode', '' );
    $book_meta->add_field( '_pr_index_height', __( 'Index height', 'edition' ), __( 'Index height', 'editorial_project' ), 'number', '' );
    $book_meta->add_field( '_pr_index_width', __( 'Index width', 'edition' ), __( 'Index width', 'editorial_project' ), 'number', '' );
    $book_meta->add_field( '_pr_index_bounce', __( 'Index bounce', 'edition' ), __( 'Index bounce', 'editorial_project' ), 'checkbox', false );
    $book_meta->add_field( '_pr_start_at_page', __( 'Start at page', 'edition' ), __( 'Start at page', 'editorial_project' ), 'number', '' );

    $sub_meta->add_field( '_pr_default', '<h3>Subscription properties</h1><hr>', '', 'textnode', '' );
    $sub_meta->add_field( '_pr_prefix_bundle_id', __( 'App bundle id', 'edition' ), __( 'App bundle id', 'editorial_project' ), 'text', '' );
    $sub_meta->add_field( '_pr_single_edition_prefix', __( 'Single edition prefix', 'edition' ), __( 'Single edition prefix', 'editorial_project' ), 'text_autocompleted', '' );
    $sub_meta->add_field( '_pr_subscription_prefix', __( 'Subscription prefix', 'edition' ), __( 'Subscription prefix', 'editorial_project' ), 'text_autocompleted', '' );
    $sub_meta->add_field( '_pr_subscription_types', __( 'Subscription types', 'edition' ), __( 'Subscription types', 'editorial_project' ), 'repeater_with_radio', '', array(
      'radio_field'   => '_pr_subscription_method',
      'radio_options' => array(
        array( 'value' => 'all', 'name' => __( "All", 'editorial_project' ) ),
        array( 'value' => 'last', 'name' => __( "Last edition", 'editorial_project' ) )
      ),
    ) );

    $push_meta->add_field( '_pr_default', '<h3>Push notification option</h1><hr>', '', 'textnode', '' );
    $push_meta->add_field( 'pr_push_service', __( 'Push service', 'editorial_project' ), __( 'Push service', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'parse', 'name' => __( "Parse", 'editorial_project' ) ),
        array( 'value' => 'urbanairship', 'name' => __( "Urban Airship", 'editorial_project' ) ),
      )
    ) );
    $push_meta->add_field( 'pr_push_api_app_id', __( 'App key', 'edition' ), __( 'The main identifier of your app. <b>Urban Airship</b>: <i>App Key</i><b> - Parse: </b><i>Application Id</i>', 'editorial_project' ), 'text', '' );
    $push_meta->add_field( 'pr_push_api_key', __( 'App secret', 'edition' ), __( 'The secret token for authentication <b>Urban Airship</b>: <i>App Master Secret</i><b> - Parse: </b><i>REST API Key</i> ', 'editorial_project' ), 'text', '' );

    $this->_metaboxes = array(
      $basic_meta,
      $vis_meta,
      $behavior_meta,
      $book_meta,
      $sub_meta,
      $push_meta
    );
  }


  public function pre_edit_form( $term ) {

    $this->get_custom_metabox( $term->term_id );
    echo '<h2 class="nav-tab-wrapper">';
    foreach ( $this->_metaboxes as $key => $metabox ) {
      echo '<a class="nav-tab" data-tab="'.$metabox->id.'" href="#">' . $metabox->title . '</a>';
    }
    echo '</h2>';
  }
  /**
  * Define the custom meta fields for new entry
  *
  * @param  object $term
  * @echo
  */
  public function edit_form_meta_fields( $term ) {

    foreach ( $this->_metaboxes as $key => $metabox ) {
      echo $metabox->fields_to_html( true, $metabox->id );
    }
  }

  /**
  * Save the values ​​in a custom option
  *
  * @param  int $term_id
  * @void
  */
  public function save_form_meta_fields( $term_id ) {

    $terms = get_terms( TPL_EDITORIAL_PROJECT );
    foreach( $terms as $term ) {
      $option = self::get_config( $term->term_id, '_pr_prefix_bundle_id');
      if ( isset($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. TPL_EDITION .'&taxonomy=' . TPL_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values();
    }


    if( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
      $url = admin_url( 'edit-tags.php?action=edit&post_type='. TPL_EDITION .'&taxonomy=' . TPL_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
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

    $terms = get_terms( TPL_EDITORIAL_PROJECT );
    foreach( $terms as $term ) {
      $option = self::get_config( $term->term_id, '_pr_prefix_bundle_id');
      if ( isset($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. TPL_EDITION .'&taxonomy=' . TPL_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values( true );
    }

    if( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
      $url = admin_url( 'edit-tags.php?action=edit&post_type='. TPL_EDITION .'&taxonomy=' . TPL_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
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
   * Get an editorial project by slug
   * @param string $slug
   * @return object
   */
  public static function get_by_slug( $slug ) {

    $eproject = get_term_by( 'slug', $slug, TPL_EDITORIAL_PROJECT );
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
      'post_type'       => TPL_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => TPL_EDITORIAL_PROJECT,
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
      'post_type'       => TPL_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => TPL_EDITORIAL_PROJECT,
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
      'post_type'       => TPL_EDITION,
      'meta_key'        => '_pr_date',
      'orderby'         => 'meta_value',
      'tax_query'       => array(
        array(
          'taxonomy'  => TPL_EDITORIAL_PROJECT,
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

<<<<<<< HEAD
  /**
   * Get the bundle id of an editorial project
   * @param int $editorial_project_id
   * @return string or boolean false
   */
  public static function get_bundle_id( $editorial_project_id ) {

    $eproject_bundle_id = false;
    $eproject_options = TPL_Editorial_Project::get_configs( $editorial_project_id );
    if ( $eproject_options ) {
      $eproject_bundle_id = $eproject_options['_pr_prefix_bundle_id'];
    }
    return $eproject_bundle_id;
=======
  public function remove_form_fields() {
    ?>
    <script>
      jQuery('#tag-description').closest('.form-field').remove();
	    jQuery('#description').closest('.form-field').remove();
      jQuery('#parent').closest('.form-field').remove();
    </script>
    <?php
>>>>>>> af7a629a8d002df84c49333787c6f5d0619a9c24
  }
}

$pressroom_editorial_project = new TPL_Editorial_Project();
