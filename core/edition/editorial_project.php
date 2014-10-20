<?php
/**
 * TPL Editorial Project class.
 * Custom taxonomies for Edition custom post type
 */
class TPL_Editorial_Project
{
  protected $_metabox;

  public function __construct() {

    if ( !is_admin() ) {
       return;
    }

    add_action( 'init', array( $this, 'add_editorial_project_taxonomy' ) );
    add_filter( 'manage_edit-' . TPL_EDITORIAL_PROJECT . '_columns', array( $this, 'editorial_project_columns' ) );
    add_filter( 'manage_' . TPL_EDITORIAL_PROJECT . '_custom_column', array( $this, 'manage_columns' ), 10, 3 );
    //add_action( TPL_EDITORIAL_PROJECT . '_add_form_fields', array( $this, 'add_form_meta_fields' ) );
    add_action( TPL_EDITORIAL_PROJECT . '_edit_form_fields', array( $this, 'edit_form_meta_fields' ), 10, 2 );
    add_action( 'edited_' . TPL_EDITORIAL_PROJECT, array( $this, 'save_form_meta_fields' ) );
    add_action( 'create_' . TPL_EDITORIAL_PROJECT, array( $this, 'save_form_meta_fields' ) );
    add_action( 'admin_print_scripts', array( $this, 'add_form_scripts' ) );
    add_action( TPL_EDITORIAL_PROJECT . '_term_edit_form_tag', array( $this,'form_add_enctype' ) );
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
  * Define the columns of the table
  * @param  array $edit_columns
  * @return array
  */
  public function editorial_project_columns( $edit_columns ) {

    $new_columns = array(
       'cb'           => '<input type="checkbox" />',
       'name'         => __( 'Name' ),
       'header_icon'  => __( 'Shelf.json', 'editorial_project'),
       'description'  => __( 'Description' ),
       'slug'         => __( 'Slug' ),
       'posts'        => __( 'Posts' )
    );

    return $new_columns;
  }

  /**
  * Custom column managment
  * @param  string $out
  * @param  string $column_name
  * @param  int $editorial_id
  * @return string
  */
  public function manage_columns( $out, $column_name, $editorial_id ) {

    $editorial = get_term( $editorial_id, TPL_EDITORIAL_PROJECT );
    switch ( $column_name ) {

       case 'header_icon':
          $shelf_url = TPL_SHELF_URI . $editorial->slug . '_shelf.json';
          if ( is_file( TPL_SHELF_PATH . $editorial->slug . '_shelf.json' ) ) {
             echo '<a href="' . $shelf_url . '">' . __("View endpoint", 'editorial_project') . '</a>';
          }
          break;
       default:
          break;
    }

    return $out;
  }

  public function get_custom_metabox( $term_id ) {
    $e_meta = new TPL_Metabox( 'edition_metabox', __( 'Edition metabox', 'edition' ), 'normal', 'high', $term_id );

    $e_meta->add_field( '_pr_default', '<h3>Basic option</h1><hr>', '', 'textnode', '' );
    $e_meta->add_field( '_pr_orientation', __( 'Orientation', 'edition' ), __( 'Orientation', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'horizontal', 'name' => __( "Horizontal", 'edition' ) ),
        array( 'value' => 'vertical', 'name' => __( "Vertical", 'edition' ) ),
        array( 'value' => 'both', 'name' => __( "Both", 'edition' ) )
      )
    ) );
    $e_meta->add_field( '_pr_zoomable', __( 'Zoomable', 'edition' ), __( 'Zoomable', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_body_bg_color', __( 'Body background color', 'edition' ), __( 'Body background color', 'edition' ), 'color', '' );

    $e_meta->add_field( '_pr_default', '<h3>Visualization properties</h1><hr>', '', 'textnode', '' );
    $e_meta->add_field( '_pr_background_image_portrait', __( 'Background image portrait', 'edition' ), __( 'Background image portrait', 'edition' ), 'file', '' );
    $e_meta->add_field( '_pr_background_image_landscape', __( 'Background image landscape', 'edition' ), __( 'Background image landscape', 'edition' ), 'file', '' );
    $e_meta->add_field( '_pr_page_numbers_color', __( 'Page numbers color', 'edition' ), __( 'Page numbers color', 'edition' ), 'color', '' );
    $e_meta->add_field( '_pr_page_numbers_alpha', __( 'Page number alpha', 'edition' ), __( 'Page number alpha', 'edition' ), 'decimal', '' );
    $e_meta->add_field( '_pr_page_screenshot', __( 'Page Screenshoot', 'edition' ), __( 'Path to a folder containing the pre-rendered pages screenshots.', 'edition' ), 'text', '' );

    $e_meta->add_field( '_pr_default', '<h3>Behaviour properties</h1><hr>', '', 'textnode', '' );
    $e_meta->add_field( '_pr_rendering', __( 'Rendering type', 'edition' ), __( 'Rendering type', 'edition' ), 'radio', '', array(
      'options' => array(
        array( 'value' => 'screenshots', 'name' => __( "Screenshots", 'edition' ) ),
        array( 'value' => 'three-cards', 'name' => __( "Three cards", 'edition' ) )
      )
    ) );
    $e_meta->add_field( '_pr_verticle_bounce', __( 'Vertical Bounce', 'edition' ), __( 'Vertical Bounce', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_media_autoplay', __( 'Media autoplay', 'edition' ), __( 'Media autoplay', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_vertical_pagination', __( 'Vertical pagination', 'edition' ), __( 'Vertical pagination', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_page_turn_tap', __( 'Page turn tap', 'edition' ), __( 'Page turn tap', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_page_turn_swipe', __( 'Page turn swipe', 'edition' ), __( 'Page turn swipe', 'edition' ), 'checkbox', '' );

    $e_meta->add_field( '_pr_default', '<h3>Book extended</h1><hr>', '', 'textnode', '' );
    $e_meta->add_field( '_pr_index_height', __( 'Index height', 'edition' ), __( 'Index height', 'edition' ), 'number', '' );
    $e_meta->add_field( '_pr_index_width', __( 'Index width', 'edition' ), __( 'Index width', 'edition' ), 'number', '' );
    $e_meta->add_field( '_pr_index_bounce', __( 'Index bounce', 'edition' ), __( 'Index bounce', 'edition' ), 'checkbox', '' );
    $e_meta->add_field( '_pr_start_at_page', __( 'Start at page', 'edition' ), __( 'Start at page', 'edition' ), 'number', '' );

    $e_meta->add_field( '_pr_default', '<h3>Subscription properties</h1><hr>', '', 'textnode', '' );
    $e_meta->add_field( '_pr_prefix_bundle_id', __( 'Prefix bundle id', 'edition' ), __( 'Prefix bundle id', 'edition' ), 'text', '' );
    $e_meta->add_field( '_pr_single_edition_prefix', __( 'Single edition prefix', 'edition' ), __( 'Single edition prefix', 'edition' ), 'text', '' );
    $e_meta->add_field( '_pr_subscription_prefix', __( 'Subscription prefix', 'edition' ), __( 'Subscription prefix', 'edition' ), 'text', '' );
    $e_meta->add_field( '_pr_subscription_types', __( 'Subscription types', 'edition' ), __( 'Subscription types', 'edition' ), 'repeater', '' );
    $this->_metabox = $e_meta;
  }

  /**
  * Define the custom meta fields for new entry
  *
  * @param  object $term
  * @echo
  */
  public function edit_form_meta_fields( $term ) {


    $this->get_custom_metabox( $term->term_id );
    echo $this->_metabox->fields_to_html( true );
  }

  /**
  * Save the values ​​in a custom option
  * @param  int $term_id
  * @void
  */
  public function save_form_meta_fields( $term_id ) {

    $this->get_custom_metabox( $term_id );
    $this->_metabox->save_term_values();

  }

  /**
  * Add enctype to form for fileupload
  * @echo
  */
	public function form_add_enctype() {

		echo ' enctype="multipart/form-data"';
	}

  /**
  * Add required scripts
  * @to do bisognerebbe spostarlo direttamente in metabox.js
  */
  public function add_form_scripts() {
    wp_register_script( 'editorial_project', TPL_ASSETS_URI . '/js/metabox.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
    wp_enqueue_script( 'editorial_project' );

    // Css rules for Color Picker
    wp_enqueue_style( 'wp-color-picker' );


  }
}

$pressroom_editorial_project = new TPL_Editorial_Project();
