<?php

  /**
   * TPL Editorial Project class.
   * Custom taxonomies for Edition custom post type
   */
  class TPL_Editorial_Project {

    /**
     * [TPL_Editorial_Project costructor
     */
    public function TPL_Editorial_Project() {
      add_action( 'init', array($this,'add_editorial_project_tax' ) );
      add_filter("manage_edit-".TPL_EDITORIAL_PROJECT."_columns", array($this,'editorial_project_columns'));
      add_filter("manage_".TPL_EDITORIAL_PROJECT."_custom_column", array($this,'manage_shelf_columns'), 10, 3);
      add_action( TPL_EDITORIAL_PROJECT . '_add_form_fields', array($this,'tpl_add_new_meta_field'));
      add_action( TPL_EDITORIAL_PROJECT . '_edit_form_fields', array($this, 'tpl_taxonomy_edit_meta_field'), 10, 2);
      add_action( 'edited_' . TPL_EDITORIAL_PROJECT, array($this, 'save_taxonomy_custom_meta' ));
      add_action( 'create_' . TPL_EDITORIAL_PROJECT, array($this, 'save_taxonomy_custom_meta' ));
      add_action( 'admin_print_scripts', array($this, 'add_script') );
    }

    /**
     * Add taxonomy editorial_project for edition custom post type
     */
    public function add_editorial_project_tax(){
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
    		// 'update_count_callback' => '_update_post_term_count',
    		'query_var'             => true,
    		'rewrite'               => array( 'slug' => 'editorial-project' ),
    	);

    	register_taxonomy( TPL_EDITORIAL_PROJECT, TPL_EDITION, $args );
    }


    public function editorial_project_columns($edit_columns) {
        $new_columns = array(
          'cb' => '<input type="checkbox" />',
          'name' => __('Name'),
          'header_icon' => __('Shelf.json', 'editorial_project'),
          'description' => __('Description'),
          'slug' => __('Slug'),
          'posts' => __('Posts')
        );
        return $new_columns;
    }

    public function manage_shelf_columns($out, $column_name, $editorial_id) {
      $editorial = get_term($editorial_id, TPL_EDITORIAL_PROJECT);
      switch ($column_name) {
          case 'header_icon':
              echo '<a href="'.TPL_SHELF_URI.$editorial->slug.'_shelf.json">'.__("View endpoint", 'editorial_project').'</a>';
              break;

          default:
              break;
      }
      return $out;
    }

    public function tpl_add_new_meta_field() {
      echo '
        <div class="form-field">
          <label for="term_meta[prefix_bundle_id]">Prefix bundle id</label>
          <input type="text" name="term_meta[prefix_bundle_id]" id="term_meta[prefix_bundle_id]" value="">
          <label for="term_meta[single_edition_prefix]">Single edition prefix</label>
          <input type="text" name="term_meta[single_edition_prefix]" id="term_meta[single_edition_prefix]" value="">
          <label for="term_meta[subscription_prefix]">Subscription prefix</label>
          <input type="text" name="term_meta[subscription_prefix]" id="term_meta[subscription_prefix]" value="">
          <label for="term_meta[subscription_type]">Subscription type</label>
          <input type="text" name="term_meta[subscription_type]" id="term_meta[subscription_type]" value="">
        </div>';
    }
    public function tpl_taxonomy_edit_meta_field($term) {
      $t_id = $term->term_id;
      $term_meta = get_option( "taxonomy_term_$t_id" );
      echo '
        <tr class="form-field">
          <th scope="row" valign="top"><label for="prefix_bundle_id">Prefix bundle id</label></th>
          <td>
            <input type="text" name="term_meta[prefix_bundle_id]" id="term_meta[prefix_bundle_id]" value="'.(esc_attr( $term_meta["prefix_bundle_id"] ) ? esc_attr( $term_meta["prefix_bundle_id"] ) : "").'">
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row" valign="top"><label for="prefix_bundle_id">Single edition prefix</label></th>
          <td>
            <input type="text" name="term_meta[single_edition_prefix]" id="term_meta[single_edition_prefix]" value="'.(esc_attr( $term_meta["single_edition_prefix"] ) ? esc_attr( $term_meta["single_edition_prefix"] ) : "").'">
          </td>
        </tr>
        <tr class="form-field">
          <th scope="row" valign="top"><label for="subscription_prefix">Subscription prefix</label></th>
          <td>
            <input type="text" name="term_meta[subscription_prefix]" id="term_meta[subscription_prefix]" value="'.(esc_attr( $term_meta["subscription_prefix"] ) ? esc_attr( $term_meta["subscription_prefix"] ) : "").'">
          </td>
        </tr>';


          $repeated = unserialize($term_meta["subscription_type"]);
          $plus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAYAgMAAACdGdVrAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAOPAAADjwGKNpDpAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAAxQTFRF////AAAAAAAAAAAA+IwCTQAAAAN0Uk5TADhjOVJIxwAAACFJREFUCJljYGBgv8AAAhRRoaHRT0NDGf6DAYyCClLFBgAZCSHpoJBTcAAAAABJRU5ErkJggg435f5aefc9ece0446a6ae170863f50a3"/>';
          $minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAZCAYAAAArK+5dAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAO3AAADtwB+LUWtAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAA4SURBVEiJY/z//z8DLQETTU0ftWDUgqFhASMDA4MHrS2gaVYe+nEw9C2gfSoarQ9GLRi1gPYWAADB5Ae3h3/zOwAAAABJRU5ErkJgggafaa68760bfc367f77f3e9abf6847fcd"/>';
          $i = 0;
          if($repeated) {
            foreach($repeated as $field ) {
              echo '
                <tr class="form-field tpl_repeater" id="tpl_repeater" data-index="'.$i.'">
                  <th scope="row" valign="top"><label for="subscription_type">'.($i == 0 ? 'Subscription type' : '').'</label></th>
                  <td>
                    <input type="text" name="term_meta[subscription_type]['.$i.']" id="term_meta[subscription_type]" value="'.(esc_attr( $field ) ? esc_attr( $field ) : "").'">
                  </td>
                  <td>'.($i == 0 ? '<a href="#" id="add-subscription">'.$plus : '<a href="" id="remove-subscription" class="remove-subscription">'.$minus).'</a></td>
                </tr>';
                $i++;
            }
          }
          else {
            echo '
              <tr class="form-field tpl_repeater" id="tpl_repeater" data-index="'.$i.'">
                <th scope="row" valign="top"><label for="subscription_type">Subscription type</label></th>
                <td>
                  <input type="text" name="term_meta[subscription_type]['.$i.']" id="term_meta[subscription_type]" value="'.(esc_attr( $term_meta["subscription_type"] ) ? esc_attr( $term_meta["subscription_type"] ) : "").'">
                </td>
                <td>'.($i == 0 ? '<a href="#" id="add-subscription">'.$plus : '<a href="" id="remove-subscription">'.$minus).'</a></td>
              </tr>';
              $i++;
          }

    }

    public function save_taxonomy_custom_meta( $term_id ) {
      if ( isset( $_POST['term_meta'] ) ) {
        $t_id = $term_id;
        $term_meta = get_option( "taxonomy_term_$t_id" );
        $cat_keys = array_keys( $_POST['term_meta'] );
        //var_dump($_POST['term_meta']);
        foreach ( $cat_keys as $key ){
            if ( isset( $_POST['term_meta'][$key] ) ){
              if($key == 'subscription_type') {
                  foreach($_POST['term_meta'][$key] as $type) {
                    $term_meta[$key] = serialize($_POST['term_meta'][$key]);
                  }
              }
              else {
                $term_meta[$key] = $_POST['term_meta'][$key];
              }
            }
        }
        //save the option array
        update_option( "taxonomy_term_$t_id", $term_meta );
      }
    }

    public function add_script() {
      wp_register_script( 'editorial_project', TPL_PLUGIN_ASSETS . '/js/editorial_project.js', array( 'jquery' ), '1.0', true );
      wp_enqueue_script( 'editorial_project' );
    }
  }
  $editorial_project = new TPL_Editorial_Project();
