<?php

class TPL_Edition
{
	protected $_metaboxes = array();

	/**
	 * Register required hooks
	 *
	 * @void
	 */
	public function __construct() {

		if ( !is_admin() ) {
			return;
		}

		add_theme_support( 'post-thumbnails', array( TPL_EDITION ) );

		add_action( 'init', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( 'TPL_Theme', 'search_themes' ), 20 );

		add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_pressroom_metabox' ), 40 );
		add_action( 'save_post_' . TPL_EDITION, array( $this, 'save_edition'), 40 );

		add_action( 'wp_ajax_publishing', array( $this, 'ajax_publishing_callback' ) );

		add_action( 'admin_enqueue_scripts', array( $this,'register_edition_script' ) );

		add_action( 'post_edit_form_tag', array( $this,'form_add_enctype' ) );
		add_action( 'edit_form_advanced', array( $this, 'form_add_thickbox' ) );

		add_action( 'manage_' . TPL_EDITION . '_posts_columns', array( $this, 'cover_columns' ) );
		add_action( 'manage_' . TPL_EDITION . '_posts_custom_column', array( $this, 'cover_output_column' ), 10, 2 );

	}

	/**
	 * Add custom post type edition to worpress
	 *
	 * @void
	 */
	public function add_edition_post_type() {

		$labels = array(
			'name'                => _x( 'Editions', 'Post Type General Name', 'edition' ),
			'singular_name'       => _x( 'Edition', 'Post Type Singular Name', 'edition' ),
			'menu_name'           => __( 'Editions', 'edition' ),
			'parent_item_colon'   => __( 'Parent edition:', 'edition' ),
			'all_items'           => __( 'All editions', 'edition' ),
			'view_item'           => __( 'View edition', 'edition' ),
			'add_new_item'        => __( 'Add New Edition', 'edition' ),
			'add_new'             => __( 'Add New', 'edition' ),
			'edit_item'           => __( 'Edit edition', 'edition' ),
			'update_item'         => __( 'Update edition', 'edition' ),
			'search_items'        => __( 'Search edition', 'edition' ),
			'not_found'           => __( 'Not found', 'edition' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'edition' ),
		);

		$args = array(
			'label'                => __( 'edition_type', 'edition' ),
			'description'          => __( 'Press room edition', 'edition' ),
			'labels'               => $labels,
			'supports'             => array( 'title', 'author', 'thumbnail', 'custom-fields' ),
			'hierarchical'         => false,
			'public'               => true,
			'show_ui'              => true,
			'show_in_menu'         => true,
			'show_in_nav_menus'    => true,
			'show_in_admin_bar'    => true,
			'menu_position'        => 5,
			'menu_icon'            => 'dashicons-book',
			'can_export'           => true,
			'has_archive'          => true,
			'exclude_from_search'  => false,
			'publicly_queryable'   => true,
			'capability_type'      => 'post',
			'register_meta_box_cb' => array( $this, 'add_publication_metabox' ),
		);

		register_post_type( TPL_EDITION , $args );
	}

	/**
	 * Get custom metaboxes configuration
	 *
	 * @param object $post
	 * @void
	 */
	public function get_custom_metaboxes( $post ) {

		$editorial_terms = wp_get_post_terms( $post->ID, TPL_EDITORIAL_PROJECT );
		$e_meta = new TPL_Metabox( 'edition_metabox', __( 'Edition metabox', 'edition' ), 'normal', 'high', $post->ID );
		$e_meta->add_field( '_pr_author', __( 'Author', 'edition' ), __( 'Author', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_creator', __( 'Creator', 'edition' ), __( 'Creator', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_publisher', __( 'Publisher', 'edition' ), __( 'Publisher', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_date', __( 'Publication date', 'edition' ), __( 'Publication date', 'edition' ), 'date', date('Y-m-d') );
		$e_meta->add_field( '_pr_theme_select', __( 'Edition theme', 'edition' ), __( 'Select a theme', 'edition' ), 'select', '', array( 'options' => TPL_Theme::get_themes_list() ) );
		$e_meta->add_field( '_pr_edition_free', __( 'Edition free', 'edition' ), __( 'Edition free', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 0, 'name' => __( "Paid", 'edition' ) ),
				array( 'value' => 1, 'name' => __( "Free", 'edition' ) )
			)
		) );
		$e_meta->add_field( '_pr_subscriptions_select', __( 'Included in subscription', 'edition' ), __( 'Select a subscription type', 'edition' ), 'select_multiple', '', array(
			'options' => $this->_get_subscription_types()
		) );
		foreach ( $editorial_terms as $term) {
			$e_meta->add_field( '_pr_product_id_' . $term->term_id, __( 'Product identifier', 'edition' ), __( 'Product identifier for ' . $term->name . ' editorial project', 'edition' ), 'text', '' );
		}
		// Add metabox to metaboxes array
		array_push( $this->_metaboxes, $e_meta );
	}

	/**
	 * Add one or more custom metabox to edition custom fields
	 *
	 * @void
	 */
	public function add_custom_metaboxes( $post_type, $post ) {

		$this->get_custom_metaboxes( $post );
		foreach ( $this->_metaboxes as $metabox ) {

			add_meta_box($metabox->id, $metabox->title, array($this, 'add_custom_metabox_callback'), TPL_EDITION, $metabox->context, $metabox->priority);
		}
	}

	/**
	 * Add metabox to edition post
	 *
	 * @void
	 */
	public function add_pressroom_metabox() {

		if ( TPL_Pressroom::is_edit_page() ) {

			add_meta_box( 'pressroom_metabox', __( 'Linked posts', 'edition' ), array( $this, 'add_pressroom_metabox_callback' ), TPL_EDITION );
		}
	}

	/**
	* Add side metabox for publication step
	*
	* @return void
	*/
	public function add_publication_metabox() {

		if ( TPL_Pressroom::is_edit_page() ) {

			add_meta_box( 'edition_metabox_side', __( 'Publication', 'edition' ), array( $this, 'add_publication_metabox_callback' ), TPL_EDITION, 'side', 'low' );
		}
	}

	/**
	 * Custom metabox callback print html input field
	 *
	 * @echo
	 */
	public function add_custom_metabox_callback() {

		echo '<input type="hidden" name="tpl_edition_nonce" value="' . wp_create_nonce('tpl_edition_nonce'). '" />';
		echo '<table class="form-table">';

		foreach ( $this->_metaboxes as $metabox ) {

			echo $metabox->fields_to_html();
		}

		echo '</table>';
	}

	/**
	 * Pressroom metabox callback
	 *
	 * @return void
	 */
	public function add_pressroom_metabox_callback() {

		$pr_table = new Pressroom_List_Table();
		$pr_table->prepare_items();
		$pr_table->display();
	}

	/**
	* Publication metabox callback
	*
	* @echo
	*/
	public function add_publication_metabox_callback() {

		echo '<a id="publish_edition" href="' . admin_url('admin-ajax.php') . '?action=publishing&edition_id=' . get_the_id() . '&pr_no_theme=true&width=800&height=600&TB_iframe=true" class="button button-primary button-large thickbox">' . __( "Packaging", "edition" ) . '</a> ';
		echo '<a id="preview_edition" target="_blank" href="' . TPL_CORE_URI . 'preview/reader.php?edition_id=' . get_the_id() . '" class="button button-primary button-large">' . __( "Preview", "edition" ) . '</a> ';
	}

	/**
	 * Save metabox form data
	 *
	 * @param  int $post_id
	 * @void
	 */
	public function save_edition( $post_id ) {

		$post = get_post($post_id);
		if ( !$post || $post->post_type != TPL_EDITION ) {
			return;
		}

		//Verify nonce
		if ( !isset( $_POST['tpl_edition_nonce'] ) || !wp_verify_nonce( $_POST['tpl_edition_nonce'], 'tpl_edition_nonce' ) ) {
			return $post_id;
		}

		//Check autosave
		if ( defined('DOING_AUTOSAVE') && DOING_AUTOSAVE ) {
			return $post_id;
		}

		//Check permissions
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}

		$this->get_custom_metaboxes( $post );
		foreach ( $this->_metaboxes as $metabox ) {

			$metabox->save_values();
		}

		$this->sanitize_linked_posts( $post );

		$edition_theme = get_post_meta( $post_id, '_pr_theme_select', true );
		if ( !$edition_theme ) {
			if ( TPL_Pressroom::is_edit_page() ) {
				$url = admin_url( 'post.php?post=' . $post_id . '&action=edit&pmtype=error&pmcode=theme' );
			} else {
				$url = admin_url( 'post-new.php?post_type=' . TPL_EDITION . '&pmtype=error&pmcode=theme' );
			}
			wp_redirect( $url );
			exit;
		}
	}

	public function sanitize_linked_posts( $edition ) {

		$linked_posts = self::get_linked_posts( $edition );



		$old_order = 1;
		foreach ( $linked_posts->posts as $i => $post ) {

			$current_order = p2p_get_meta( $post->p2p_id, 'order', true );
			$current_order = max( $current_order, $old_order );
			$old_order++;

			p2p_update_meta( $post->p2p_id, 'order', $current_order);

			$template = p2p_get_meta( $post->p2p_id, 'template', true );

			if( !$template || !file_exists( TPL_THEME_PATH . $template ) ) {

				$current_theme = get_post_meta( $edition->ID, '_pr_theme_select', true );

				if ( $current_theme ) {
					$themes = TPL_Theme::get_themes();
					$default_template = $themes[$current_theme][0]['filename'];
					p2p_update_meta( $post->p2p_id, 'template', $default_template);
				}
			}
		}
	}

	/**
	 * Ajax publishing callback function
	 *
	 * @echo
	 */
	public function ajax_publishing_callback() {

		$packager = new TPL_Packager();
		echo '<style type="text/css">
		#publishing_popup {padding:10px 20px; font-family:"Helvetica Neue", Helvetica, Arial, sans-serif !important;}
		#publishing_popup .error .label {background: #c22d26;}
		#publishing_popup .success .label {background: #7dc42a;}
		#publishing_popup .info .label {background: #000;}
		#publishing_popup .label {color:#fff; text-transform:capitalize; padding:2px;width:60px;text-align:center;display:inline-block}
		#publishing_popup p { font-size: 12px; line-height: 20px; margin: 5px 0;}
		#publishing_popup h1 {margin:0 0 10px; color:#333;font-weight:300}
		#publishing_console { padding:10px; height:435px; margin: 0 auto; overflow:scroll;font-family:"Courier New", Courier, monospace; border:1px solid #d9d9d9;background:#f2f2f2; }
		</style>';
		echo '<div id="publishing_popup">
		<h1>' . __( 'Package and distribute', 'edition' ) . '</h1>';
		$packager->pb->render();
		sleep( 2 );
		echo '<div id="publishing_console">';
		$editorial_terms = wp_get_post_terms( $_GET['edition_id'], TPL_EDITORIAL_PROJECT );
		if ( $editorial_terms ){
			$total_steps = count( $editorial_terms ) * 12;
			foreach ( $editorial_terms as $term ) {
				$packager->run( $term );
			}
		}
		else {
			echo '<p class="liveoutput error"><span class="label">Error</span>' . __( ' No editorial project linked to this edition', 'edition' ) .'</p>';
		}
		echo '</div>
		</div>';
		exit;
	}

	/**
	 * Add jQuery datepicker script and css styles
	 * @void
	 */
	public function register_edition_script() {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'pressroom', TPL_ASSETS_URI . 'css/pressroom.css' );
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
	 * Add thickbox to form for the packager support
	 *
	 * @void
	 */
	public function form_add_thickbox() {

		add_thickbox();
	}

	/**
	 * Get linked posts
	 *
	 * @param object or int $edition
	 * @return array of objects
	 */
	public static function get_linked_posts( $edition, $post_meta = array() ) {

		$args = array(
			'connected_type'        => P2P_EDITION_CONNECTION,
			'connected_items'       => is_int( $edition ) ? get_post( $edition ) : $edition,
			'nopaging'              => true,
			'connected_orderby'     => 'order',
			'connected_order'       => 'asc',
			'connected_order_num'   => true,
			'cardinality'						=> 'one-to-many'
		);

		if ( !empty( $post_meta) ) {
			$args = array_merge( $args, $post_meta );
		}

		$linked_query = new WP_Query( $args );
		return $linked_query;
	}

	/**
	 * Get linked editions
	 *
	 * @param object or int $post
	 * @return array of objects
	 */
	public static function get_linked_editions( $post, $post_meta = array() ) {

		$args = array(
			'connected_type'        => P2P_EDITION_CONNECTION,
			'connected_items'       => is_int( $post ) ? get_post( $post ) : $post,
			'connected_direction'		=> 'from',
			'nopaging'              => true,
			'orderby'     					=> 'post_title',
			'order'									=> 'asc',
			'cardinality'						=> 'one-to-many'
		);

		if ( !empty( $post_meta) ) {
			$args = array_merge( $args, $post_meta );
		}

		$linked_query = new WP_Query( $args );
		return $linked_query;
	}

	/**
	 * Get subscription types terms
	 *
	 * @return array
	 */
	protected function _get_subscription_types() {

		global $post;
		$terms = wp_get_post_terms( $post->ID, TPL_EDITORIAL_PROJECT );
		$types = array();
		if ( !empty( $terms ) ) {
			foreach ( $terms as $term ) {

				$term_meta = get_option( "taxonomy_term_" . $term->term_id );
				if ( $term_meta ) {
					$term_types = $term_meta['_pr_subscription_types'];
					if( $term_types ) {
						foreach ( $term_types as $type ) {

							$types[$term->name][] = array(
								'value' => $term_meta['_pr_prefix_bundle_id']. '.' . $term_meta['_pr_subscription_prefix']. '.' . $type,
								'text'  => $type,
							);
						}
					}
				}
			}
		}

		return $types;
	}

	/**
	 * Add custom columns
	 *
	 * @param  array $columns
	 * @return array $columns
	 */
	public function cover_columns( $columns ) {

		$columns["cover"] = "Cover";
		$columns["paid_free"] = "Paid/Free";
		$columns["action"] = "Action";

		return $columns;
	}

	/**
	 * Set output for custom columns
	 *
	 * @param  string $column_name
	 * @param  int $id
	 * @void
	 */
	public function cover_output_column( $column_name, $id ) {

		switch ($column_name) {

	      case 'cover' :
				echo get_the_post_thumbnail( $id, 'thumbnail' );
				break;

	      case 'paid_free' :
	      	echo get_post_meta( $id, '_pr_edition_free', true ) ? 'Free' : 'Paid';
	      	break;

			case 'action':
				echo '<a target="_blank" href="'. TPL_CORE_URI . 'preview/reader.php?edition_id=' . get_the_id() . '" >Preview</a><br/>';
				echo '<a id="publish_edition" href="' . admin_url('admin-ajax.php') . '?action=publishing&edition_id=' . get_the_id() . '&pr_no_theme=true&width=800&height=600&TB_iframe=true" class=" thickbox">' . __( "Packaging", "edition" ) . '</a> ';
				break;

			default:
				break;
		}
	}

	/**
	 * Get an edition by slug
	 * @param string $slug
	 * @return object
	 */
	public static function get_by_slug( $slug ) {

		$args = array(
  		'name' => $slug,
  		'post_type' => TPL_EDITION,
  		'post_status' => 'any',
  		'numberposts' => 1
		);
		$edition_query = new WP_Query( $args );
		$editions = $edition_query->posts;
		if ( !empty( $editions ) ) {
			return $editions[0];
		}
		return false;
	}

	/**
	 * Get the bundle id of an edition into an editorial project
	 * @param int $edition_id
	 * @param int $editorial_project_id
	 * @return string or boolean false
	 */
	public static function get_bundle_id( $edition_id, $editorial_project_id ) {

		$edition_bundle_id = false;
		$product_id = get_post_meta( $edition_id, '_pr_product_id_' . $editorial_project_id , true );
		$eproject_options = TPL_Editorial_Project::get_configs( $editorial_project_id );
		if ( $product_id && $eproject_options ) {
			$edition_bundle_id = $eproject_options['_pr_prefix_bundle_id'] . '.' . $eproject_options['_pr_single_edition_prefix']. '.' . $product_id;
		}
		return $edition_bundle_id;
	}
}
