<?php
/**
 * PressRoom Editorial Project class.
 * Custom taxonomies for Edition custom post type
 */
class PR_Editorial_Project
{
  public $tax_obj;
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

      add_action( 'admin_footer', array( $this, 'add_custom_scripts' ) );

      /* Issue to Editorial project relation one to one */
      add_action( 'admin_menu', array( $this, 'remove_meta_box' ) );
      add_action( 'add_meta_boxes', array( $this, 'add_meta_box' ) );
      add_filter( 'wp_terms_checklist_args', array( $this, 'filter_terms_checklist_args' ) );
      remove_action( 'wp_ajax_add-' . PR_EDITORIAL_PROJECT, '_wp_ajax_add_hierarchical_term' );
      add_action( 'wp_ajax_add-' . PR_EDITORIAL_PROJECT, array( $this, 'add_non_hierarchical_term' ) );
      add_action( 'save_post', array( $this, 'save_single_term' ) );
      add_action( 'edit_attachment', array( $this, 'save_single_term' ) );
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
      'hierarchical'          => false,
      'labels'                => $labels,
      'show_ui'               => true,
      'show_admin_column'     => true,
      'query_var'             => true,
      'rewrite'               => array( 'slug' => 'editorial-project' ),
    );

    register_taxonomy( PR_EDITORIAL_PROJECT, PR_EDITION, $args );
    $this->tax_obj = get_taxonomy( PR_EDITORIAL_PROJECT );
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
       'posts'        => __( 'Issues' ),
       'actions'      => __( 'Actions', 'editorial_project'),
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
          //$shelf_url = home_url( 'pressroom-api/shelf/' . $editorial->slug );
          $shelf_url = PR_IOS_SETTINGS_URI . $editorial->slug . '.xml';
          $newsstand_url = home_url( 'pressroom-api/newsstand-issue-feed/' . $editorial->slug );
          echo '<a target="_blank" href="' . $shelf_url . '">' . __("iOS settings endpoint", 'editorial_project') . '</a><br/>';
          echo '<a target="_blank" href="' . $newsstand_url . '">' . __("View Apple Newsstand feed", 'editorial_project') . '</a><br/>';
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

    $metaboxes = array();
    do_action_ref_array( 'pr_add_eproject_tab', array( &$metaboxes, $term_id ) );

    $this->_metaboxes = $metaboxes;
  }

  /**
   * Add tabs menu to edit form
   *
   * @param object $term
   * @echo
   */
  public function add_tabs_to_form( $term ) {

    echo '<div id="pressroom_metabox">';
    echo '<div class="press-header postbox">';
    echo '<div class="press-container">';
    echo '<i class="press-pr-logo-gray-wp"></i>';
    echo '<div class="press-header-right">';
    echo '</div>';
    echo '</div>';
    echo '<hr/>';
    $this->get_custom_metabox( $term->term_id );
    echo '<h2 class="nav-tab-wrapper pr-tab-wrapper">';
    foreach ( $this->_metaboxes as $key => $metabox ) {
      echo '<a class="nav-tab ' . ( !$key ? 'nav-tab-active' : '' ) . '" data-tab="'.$metabox->id.'" href="#">' . $metabox->title . '</a>';
    }
    echo '</h2>';
    echo '</div>';
    echo '</div>';


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
      echo $metabox->fields_to_html( true, 'tabbed ' . $metabox->id );
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
      if ( isset($_POST['_pr_prefix_bundle_id']) && strlen($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values();
    }

    // if( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
    //   $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
    //   wp_redirect( $url );
    //   exit;
    // }

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
      if ( isset($_POST['_pr_prefix_bundle_id']) && strlen($_POST['_pr_prefix_bundle_id']) && $option == $_POST['_pr_prefix_bundle_id'] && $term->term_id != $term_id ) {
        $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id . '&pmtype=error&pmcode=duplicate_entry&pmparam=app_id' );
        wp_redirect( $url );
        exit;
      }
    }

    $this->get_custom_metabox( $term_id );
    foreach ( $this->_metaboxes as $metabox ) {
      $metabox->save_term_values( true );
    }

    // if ( isset( $_POST['action']) && $_POST['action'] == 'editedtag' ) {
    //   $url = admin_url( 'edit-tags.php?action=edit&post_type='. PR_EDITION .'&taxonomy=' . PR_EDITORIAL_PROJECT . '&tag_ID=' . $term_id );
    //   wp_redirect( $url );
    //   exit;
    // }
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
        jQuery('#pressroom_metabox').appendTo('#moved-tab');
        jQuery('.submit').appendTo('.press-header-right');
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

  /**
   * Remove tag support for editoril project
   * @param  $return
   * @param  array $args
   * @return bool or void
   */
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
   *
   * @param int $term_id
   * @param string $meta_name
   * @return mixed
   */
  public static function get_config( $term_id , $meta_name ) {

    $options = self::get_configs( $term_id );
    return isset( $options[$meta_name] ) ? $options[$meta_name] : false;
  }

/**
 * Get subscription method for editorial project
 *
 * @param  int $editorial_project_id
 * @param  string $product_id
 * @return array or bool
 */
  public static function get_subscription_method( $editorial_project_id, $product_id ) {

    $subscriptions = self::get_subscriptions_id( $editorial_project_id );
    $subscription_methods = isset( $options['_pr_subscription_method'] ) ? $options['_pr_subscription_method'] : [];

    if ( !empty( $subscriptions ) && !empty( $subscription_methods ) ) {
      foreach ( $subscriptions as $k => $identifier ) {
        if ( $identifier == $product_id ) {
          return $subscription_methods[$k];
        }
      }
    }
    return false;
  }

  /**
   * Get subscriptions for editorial project
   *
   * @param  int $editorial_project_id
   * @return array or bool
   */
  public static function get_subscriptions_id( $editorial_project_id ) {

    $subscriptions = array();
    $options = self::get_configs( $editorial_project_id );
    $subscription_types = isset( $options['_pr_subscription_types'] ) ? $options['_pr_subscription_types'] : [];
    if ( !empty( $subscription_types ) ) {
      foreach ( $subscription_types as $type ) {
        $identifier = $options['_pr_prefix_bundle_id'] . '.' . $options['_pr_subscription_prefix']. '.' . $type;
        $subscriptions[] = $identifier;
      }
    }
    return $subscriptions;
  }

  /**
	 * Get the editorial project free subscription id
	 *
	 * @param int $editorial_project_id
	 * @return string or boolean false
	 */
	public static function get_free_subscription_id( $editorial_project_id ) {

		$free_subscription_id = false;
		$options = self::get_configs( $editorial_project_id );
		if ( $options && isset( $options['_pr_prefix_bundle_id'], $options['_pr_subscription_free_prefix'] ) ) {
      $free_subscription_id = $options['_pr_prefix_bundle_id'] . '.' . $options['_pr_subscription_prefix']. '.' . $options['_pr_subscription_free_prefix'];
		}
		return $free_subscription_id;
	}

  /**
   * Get all editions in a range of dates
   *
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
   * @param  object/string $eproject
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
          'terms'     => is_string( $eproject ) ? $eproject : $eproject->slug
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
   *
   * @param int $editorial_project_id
   * @return string or boolean false
   */
  public static function get_bundle_id( $editorial_project_id ) {

    $eproject_bundle_id = false;
    $eproject_options = self::get_configs( $editorial_project_id );

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
    $term_meta = self::get_configs( $editorial_project_id );
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
  public function add_custom_scripts() {

    global $pagenow;
    if( $pagenow == 'edit-tags.php' && $_GET['taxonomy'] == PR_EDITORIAL_PROJECT ) {
      wp_register_script( 'eproject', PR_ASSETS_URI . '/js/pr.eproject.min.js', array( 'jquery' ), '1.0', true );
      wp_enqueue_script( 'eproject' );
    }
  }

  /**
  * Remove the default metabox
  *
  * @return  void
  */
  public function remove_meta_box() {

    if( !is_wp_error( $this->tax_obj ) && isset( $this->tax_obj->object_type ) ) {
      foreach ( $this->tax_obj->object_type as $post_type ) {
        $id = !is_taxonomy_hierarchical( PR_EDITORIAL_PROJECT ) ? 'tagsdiv-'. PR_EDITORIAL_PROJECT : PR_EDITORIAL_PROJECT .'div' ;
        remove_meta_box( $id, $post_type, 'side' );
      }
    }
  }

  /**
  * Add our new customized metabox
  *
  * @access public
  * @return  void
  * @since 1.0.0
  */
  public function add_meta_box() {

    if( ! is_wp_error( $this->tax_obj ) && isset( $this->tax_obj->object_type ) ) {
      foreach ( $this->tax_obj->object_type as $post_type ) {
        $label = $this->tax_obj->labels->name;
        $id = !is_taxonomy_hierarchical( PR_EDITORIAL_PROJECT ) ? 'radio-tagsdiv-' . PR_EDITORIAL_PROJECT : 'radio-' . PR_EDITORIAL_PROJECT . 'div' ;
        add_meta_box( $id, $label ,array( $this,'metabox' ), $post_type , 'side', 'core', array( 'taxonomy'=> PR_EDITORIAL_PROJECT ) );
      }
    }
  }

  /**
  * Callback to set up the metabox
  * Mimims the traditional hierarchical term metabox, but modified with our nonces
  *
  * @param  object $post
  * @param  array $args
  * @return  print HTML
  */
  public function metabox( $post, $box ) {

    $defaults = array('taxonomy' => 'category');
    if ( !isset( $box['args']) || !is_array( $box['args'] ) ) {
      $args = array();
    }
    else {
      $args = $box['args'];
    }
    extract( wp_parse_args( $args, $defaults ), EXTR_SKIP );

    //get current terms
    $checked_terms = $post->ID ? get_the_terms( $post->ID, $taxonomy ) : array();

    //get first term object
    $single_term = ! empty( $checked_terms ) && ! is_wp_error( $checked_terms ) ? array_pop( $checked_terms ) : false;
    $single_term_id = $single_term ? (int) $single_term->term_id : 0;

    ?>
    <div id="taxonomy-<?php echo $taxonomy; ?>" class="radio-buttons-for-taxonomies categorydiv form-no-clear">
      <ul id="<?php echo $taxonomy; ?>-tabs" class="category-tabs">
        <li class="tabs"><a href="#<?php echo $taxonomy; ?>-all" tabindex="3"><?php echo $this->tax_obj->labels->all_items; ?></a></li>
      </ul>

      <?php wp_nonce_field( 'radio_nonce-' . $taxonomy, '_radio_nonce-' . $taxonomy ); ?>

      <div id="<?php echo $taxonomy; ?>-pop" class="tabs-panel" style="display: none;">
        <ul id="<?php echo $taxonomy; ?>checklist-pop" class="categorychecklist form-no-clear" >
          <?php $popular = get_terms( $taxonomy, array( 'orderby' => 'count', 'order' => 'DESC', 'number' => 10, 'hierarchical' => false ) );

          if ( ! current_user_can( $this->tax_obj->cap->assign_terms ) )
          $disabled = 'disabled="disabled"';
          else
          $disabled = '';

          $popular_ids = array(); ?>

          <?php foreach( $popular as $term ){

            $popular_ids[] = $term->term_id;

            $value = is_taxonomy_hierarchical( $taxonomy ) ? $term->term_id : $term->slug;
            $id = 'popular-'.$taxonomy.'-'.$term->term_id;

            echo "<li id='$id'><label class='selectit'>";
              echo "<input type='radio' id='in-{$id}'" . checked( $single_term_id, $term->term_id, false ) . " value='{$value}' {$disabled} />&nbsp;{$term->name}<br />";

              echo "</label></li>";
            } ?>
          </ul>
        </div>

        <div id="<?php echo $taxonomy; ?>-all" class="tabs-panel">
          <ul id="<?php echo $taxonomy; ?>checklist" data-wp-lists="list:<?php echo $taxonomy?>" class="categorychecklist form-no-clear">
            <?php wp_terms_checklist( $post->ID, array( 'taxonomy' => $taxonomy, 'popular_cats' => $popular_ids ) ) ?>
          </ul>
        </div>
        <?php if ( current_user_can( $this->tax_obj->cap->edit_terms ) ) : ?>
          <div id="<?php echo $taxonomy; ?>-adder" class="wp-hidden-children">
            <h4>
              <a id="<?php echo $taxonomy; ?>-add-toggle" href="#<?php echo $taxonomy; ?>-add" class="hide-if-no-js">
                <?php
                /* translators: %s: add new taxonomy label */
                printf( __( '+ %s' , 'radio-buttons-for-taxonomies' ), $this->tax_obj->labels->add_new_item );
                ?>
              </a>
            </h4>
            <p id="<?php echo $taxonomy; ?>-add" class="category-add wp-hidden-child">
              <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>"><?php echo $this->tax_obj->labels->add_new_item; ?></label>
              <input type="text" name="new<?php echo $taxonomy; ?>" id="new<?php echo $taxonomy; ?>" class="form-required" value="<?php echo esc_attr( $this->tax_obj->labels->new_item_name ); ?>" aria-required="true"/>
              <label class="screen-reader-text" for="new<?php echo $taxonomy; ?>_parent">
                <?php echo $this->tax_obj->labels->parent_item_colon; ?>
              </label>
              <?php if( is_taxonomy_hierarchical( $taxonomy ) ) {
                wp_dropdown_categories( array( 'taxonomy' => $taxonomy, 'hide_empty' => 0, 'name' => 'new'.$taxonomy.'_parent', 'orderby' => 'name', 'hierarchical' => 1, 'show_option_none' => '&mdash; ' . $this->tax_obj->labels->parent_item . ' &mdash;' ) );
              } ?>
              <input type="button" id="<?php echo $taxonomy; ?>-add-submit" data-wp-lists="add:<?php echo $taxonomy ?>checklist:<?php echo $taxonomy ?>-add" class="button category-add-submit" value="<?php echo esc_attr( $this->tax_obj->labels->add_new_item ); ?>" tabindex="3" />
              <?php wp_nonce_field( 'add-'.$taxonomy, '_ajax_nonce-add-'.$taxonomy ); ?>
              <span id="<?php echo $taxonomy; ?>-ajax-response"></span>
            </p>
          </div>
        <?php endif; ?>
      </div>
      <?php
    }
  /**
  * Tell checklist function to use our new Walker
  *
  * @access public
  * @param  array $args
  * @return array
  * @since 1.1.0
  */
  public function filter_terms_checklist_args( $args ) {

    // define our custom Walker
    if( isset( $args['taxonomy']) && PR_EDITORIAL_PROJECT == $args['taxonomy'] ) {

      // add a filter to get_terms() but only for radio lists
      //$this->switch_terms_filter(1);
      //add_filter( 'get_terms', array( $this, 'get_terms' ), 10, 3 );

      $args['walker'] = new Walker_Eproject_Radio;
      $args['checked_ontop'] = false;
    }
    return $args;
  }

  /**
  * Add new term from metabox
  * Mimics _wp_ajax_add_hierarchical_term() but modified for non-hierarchical terms
  *
  * @return data for WP_Lists script
  * @since 1.7.0
  */
  public function add_non_hierarchical_term(){
    $action = $_POST['action'];
    $taxonomy = get_taxonomy( substr( $action, 4 ) );
    check_ajax_referer( $action, '_ajax_nonce-add-' . $taxonomy->name );
    if ( !current_user_can( $taxonomy->cap->edit_terms ) )
    wp_die( -1 );
    $names = explode(',', $_POST['new'.$taxonomy->name]);

    foreach ( $names as $cat_name ) {
      $cat_name = trim( $cat_name );
      $category_nicename = sanitize_title( $cat_name );
      if ( '' === $category_nicename ) {
        continue;
      }

      if ( ! $cat_id = term_exists( $cat_name, $taxonomy->name ) ) {
        $cat_id = wp_insert_term( $cat_name, $taxonomy->name );
      }

      if ( is_wp_error( $cat_id ) ) {
        continue;
      }
      else if ( is_array( $cat_id ) ) {
        $cat_id = $cat_id['term_id'];
      }

      $data = sprintf( '<li id="%1$s-%2$s"><label class="selectit"><input id="in-%1$s-%2$s" type="radio" name="radio_tax_input[%1$s][]" value="%2$s" checked="checked"> %3$s</label></li>', $taxonomy->name, $cat_id, $cat_name );

      $add = array(
        'what' => $taxonomy->name,
        'id' => $cat_id,
        'data' => str_replace( array("\n", "\t"), '', $data ),
        'position' => -1
      );
    }

    $x = new WP_Ajax_Response( $add );
    $x->send();
  }

  /**
  * Only ever save a single term
  *
  * @param  int $post_id
  * @return int $post_id
  */
  function save_single_term( $post_id ) {

    // verify if this is an auto save routine. If it is our form has not been submitted, so we dont want to do anything
    if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE )
    return $post_id;

    // prevent weirdness with multisite
    if( function_exists( 'ms_is_switched' ) && ms_is_switched() )
    return $post_id;

    // make sure we're on a supported post type
    if ( is_array( PR_EDITION ) && isset( $_REQUEST['post_type'] ) && ! in_array ( $_REQUEST['post_type'], PR_EDITION ) )
    return $post_id;

    // verify nonce
    if ( isset( $_POST["_radio_nonce-{PR_EDITORIAL_PROJECT}"]) && ! wp_verify_nonce( $_REQUEST["_radio_nonce-{PR_EDITORIAL_PROJECT}"], "radio_nonce-{PR_EDITORIAL_PROJECT}" ) )
    return $post_id;

    // OK, we must be authenticated by now: we need to find and save the data

    if ( isset( $_REQUEST["radio_tax_input"][PR_EDITORIAL_PROJECT] ) ){

      $terms = (array) $_REQUEST["radio_tax_input"][PR_EDITORIAL_PROJECT];

      // make sure we're only saving 1 term
      $single_term = intval( array_shift( $terms ) );

      // set the single terms
      if ( current_user_can( $this->tax_obj->cap->assign_terms ) ) {
        wp_set_object_terms( $post_id, $single_term, PR_EDITORIAL_PROJECT );
      }
    }

    return $post_id;
  }
}

$pr_editorial_project = new PR_Editorial_Project();
