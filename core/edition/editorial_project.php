<?php
/**
 * TPL Editorial Project class.
 * Custom taxonomies for Edition custom post type
 */
class TPL_Editorial_Project
{
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
            if ( is_file( TPL_SHELF_DIR . $editorial->slug . '_shelf.json' ) ) {
               echo '<a href="' . $shelf_url . '">' . __("View endpoint", 'editorial_project') . '</a>';
            }
            break;
         default:
            break;
      }

      return $out;
   }

   /**
    * Define the custom meta fields for new entry
    *
    * @echo
    */
  //  public function add_form_meta_fields() {
  //
  //     echo '<div class="form-field">
  //     <label for="term_meta[prefix_bundle_id]">Prefix bundle id</label>
  //     <input type="text" name="term_meta[prefix_bundle_id]" id="term_meta[prefix_bundle_id]" value="">
  //     <label for="term_meta[single_edition_prefix]">Single edition prefix</label>
  //     <input type="text" name="term_meta[single_edition_prefix]" id="term_meta[single_edition_prefix]" value="">
  //     <label for="term_meta[subscription_prefix]">Subscription prefix</label>
  //     <input type="text" name="term_meta[subscription_prefix]" id="term_meta[subscription_prefix]" value="">
  //     <label for="term_meta[subscription_type]">Subscription type</label>
  //     <input type="text" name="term_meta[subscription_type]" id="term_meta[subscription_type]" value="">
  //     </div>';
  //  }

   /**
    * Define the custom meta fields for new entry
    *
    * @param  object $term
    * @echo
    */
   public function edit_form_meta_fields( $term ) {

      $term_meta = get_option( 'taxonomy_term_' . $term->term_id );
      $subscription_types = unserialize( $term_meta["subscription_type"] );
      $img_add = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYAgMAAACdGdVrAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAOPAAADjwGKNpDpAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAAxQTFRF////AAAAAAAAAAAA+IwCTQAAAAN0Uk5TADhjOVJIxwAAACFJREFUCJljYGBgv8AAAhRRoaHRT0NDGf6DAYyCClLFBgAZCSHpoJBTcAAAAABJRU5ErkJggg435f5aefc9ece0446a6ae170863f50a3"/>';
      $img_remove = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAZCAYAAAArK+5dAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAO3AAADtwB+LUWtAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAA4SURBVEiJY/z//z8DLQETTU0ftWDUgqFhASMDA4MHrS2gaVYe+nEw9C2gfSoarQ9GLRi1gPYWAADB5Ae3h3/zOwAAAABJRU5ErkJgggafaa68760bfc367f77f3e9abf6847fcd"/>';

      echo '
      <tr class="form-field">
      <th scope="row" valign="top"><label>Orientation</label></th>
      <td>
      <input type="radio" name="term_meta[pr_orientation]" id="orientation_horizontal" value="horizontal" '.( esc_attr( $term_meta["pr_orientation"] ) == 'horizontal' ? "checked" : "").'><label for="orientation_horizontal">Horizontal</label>
      <input type="radio" name="term_meta[pr_orientation]" id="orientation_vertical" value="vertical" '.( esc_attr( $term_meta["pr_orientation"] ) == 'vertical' ? "checked" : "").'><label for="orientation_vertical">Vertical</label>
      <input type="radio" name="term_meta[pr_orientation]" id="orientation_both" value="both" '.( esc_attr( $term_meta["pr_orientation"] ) == 'both' ? "checked" : "").'><label for="orientation_both">Both</label>
      </td>
      </tr>
      <tr>
      <th scope="row" valign="top"><label for="zoomable">Zoomable</label></th>
      <td>
      <input type="checkbox" name="term_meta[pr_zoomable]" id="zoomable" '.( esc_attr( $term_meta["pr_zoomable"] ) == "on" ? "checked" : "").'>
      </td>
      </tr>
      <tr>
      <th scope="row" valign="top"><label for="color-picker">Body Background Color</label></th>
      <td>
      <input type="text" value="#eeeeee" class="tpl-color-picker" data-default-color="#ffffff" />
      </td>
      </tr>
      <tr>
      <th scope="row" valign="top"><label for="vertical-bounce">Vertical Bounce</label></th>
      <td>
      <input type="checkbox" name="term_meta[pr_verticle_bounce]" id="vertical-bounce" '.( esc_attr( $term_meta["pr_verticle_bounce"] ) == "on" ? "checked" : "").'>
      </td>
      </tr>
      <tr>
      <th scope="row" valign="top"><label for="index-bounce">Index bounce</label></th>
      <td>
      <input type="checkbox" name="term_meta[pr_index_bounce]" id="index-bounce" '.( esc_attr( $term_meta["pr_index_bounce"] ) == "on" ? "checked" : "").'>
      </td>
      </tr>
      <tr class="form-field">
      <th scope="row" valign="top"><label for="index-height">Index height</label></th>
      <td><input type="number" name="term_meta[pr_index_height]" id="index-height" value="' . ( esc_attr( $term_meta["pr_index_height"] ) ? esc_attr( $term_meta["pr_index_height"] ) : "" ) . '"></td>
      </tr>
      <tr>
      <th scope="row" valign="top"><label for="media-autoplay">Media autoplay</label></th>
      <td>
      <input type="checkbox" name="term_meta[pr_media_autoplay]" id="media-autoplay" '.( esc_attr( $term_meta["pr_media_autoplay"] ) == "on" ? "checked" : "").'>
      </td>
      </tr>
      <tr class="form-field">
      <th scope="row" valign="top"><label for="prefix_bundle_id">Prefix bundle id</label></th>
      <td><input type="text" name="term_meta[prefix_bundle_id]" id="term_meta[prefix_bundle_id]" value="' . ( esc_attr( $term_meta["prefix_bundle_id"] ) ? esc_attr( $term_meta["prefix_bundle_id"] ) : "" ) . '"></td>
      </tr>
      <tr class="form-field">
      <th scope="row" valign="top"><label for="prefix_bundle_id">Single edition prefix</label></th>
      <td><input type="text" name="term_meta[single_edition_prefix]" id="term_meta[single_edition_prefix]" value="' . ( esc_attr( $term_meta["single_edition_prefix"] ) ? esc_attr( $term_meta["single_edition_prefix"] ) : "" ) . '"></td>
      </tr>
      <tr class="form-field">
      <th scope="row" valign="top"><label for="subscription_prefix">Subscription prefix</label></th>
      <td><input type="text" name="term_meta[subscription_prefix]" id="term_meta[subscription_prefix]" value="' . ( esc_attr( $term_meta["subscription_prefix"] ) ? esc_attr( $term_meta["subscription_prefix"] ) : "" ) . '"></td>
      </tr>';

      if ( !empty( $subscription_types ) ) {
         $i = 0;
         foreach ( $subscription_types as $field ) {

            echo '<tr class="form-field pr_repeater" id="pr_repeater" data-index="'.$i.'">
            <th scope="row" valign="top"><label for="subscription_type">' . ( !$i ? 'Subscription type' : '') . '</label></th>
            <td><input type="text" name="term_meta[subscription_type][' . $i . ']" id="term_meta[subscription_type]" value="' . ( esc_attr( $field ) ? esc_attr( $field ) : "" ) . '"></td>
            <td>' . ( !$i ? '<a href="#" id="add-subscription">' . $img_add : '<a href="#" id="remove-subscription" class="remove-subscription">' . $img_remove ) . '</a></td>
            </tr>';
            $i++;
         }
      }
      else {
         echo '<tr class="form-field pr_repeater" id="pr_repeater" data-index="0">
         <th scope="row" valign="top"><label for="subscription_type">Subscription type</label></th>
         <td><input type="text" name="term_meta[subscription_type][0]" id="term_meta[subscription_type]" value="' . ( esc_attr( $term_meta["subscription_type"] ) ? esc_attr( $term_meta["subscription_type"] ) : "" ) . '"></td>
         <td><a href="#" id="add-subscription">' . $img_add . '</a></td>
         </tr>';
      }
   }

   /**
    * Save the values ​​in a custom option
    * @param  int $term_id
    * @void
    */
   public function save_form_meta_fields( $term_id ) {

      if ( !isset( $_POST['term_meta'] ) ) {
         return;
      }


      $term_meta = get_option( 'taxonomy_term_' . $term_id );
      $term_keys = array_keys( $_POST['term_meta'] );
      foreach ( $term_keys as $key ) {
         if ( isset( $_POST['term_meta'][$key] ) ) {
            if ( $key == 'subscription_type' ) {
               foreach ( $_POST['term_meta'][$key] as $type ) {
                  $term_meta[$key] = serialize( $_POST['term_meta'][$key] );
               }
            }
            else {
               $term_meta[$key] = $_POST['term_meta'][$key];
            }
         }
      }

      if ( !isset( $_POST['term_meta']['pr_zoomable'] ) ) {
         $term_meta['pr_zoomable'] = '';
      }

      if ( !isset( $_POST['term_meta']['pr_verticle_bounce'] ) ) {
         $term_meta['pr_verticle_bounce'] = '';
      }

      if ( !isset( $_POST['term_meta']['pr_index_bounce'] ) ) {
         $term_meta['pr_index_bounce'] = '';
      }

      if ( !isset( $_POST['term_meta']['pr_media_autoplay'] ) ) {
         $term_meta['pr_media_autoplay'] = '';
      }

      update_option( 'taxonomy_term_' . $term_id, $term_meta );
   }

   /**
    * Add required scripts
    */
   public function add_form_scripts() {
      wp_register_script( 'editorial_project', TPL_PLUGIN_ASSETS . '/js/editorial_project.js', array( 'jquery', 'wp-color-picker' ), '1.0', true );
      wp_enqueue_script( 'editorial_project' );

       // Css rules for Color Picker
       wp_enqueue_style( 'wp-color-picker' );


   }
}

$pressroom_editorial_project = new TPL_Editorial_Project();
