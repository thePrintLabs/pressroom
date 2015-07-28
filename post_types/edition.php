<?php

class PR_Edition
{
	public $pr_option;
	public $ck_exporters;
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

		$this->check_exporters();

		add_theme_support( 'post-thumbnails', array( PR_EDITION ) );

		add_action( 'press_flush_rules', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( 'PR_Theme', 'search_themes' ), 20 );

		add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );

		add_action( 'save_post_' . PR_EDITION, array( $this, 'save_edition'), 40 );

		add_action( 'wp_ajax_publishing', array( $this, 'ajax_publishing_callback' ) );
		add_action( 'wp_ajax_render_console', array( $this, 'publishing_render_console' ) );
		add_action( 'wp_ajax_pr_preview', array( $this, 'pr_preview' ) );

		add_action( 'post_edit_form_tag', array( $this,'form_add_enctype' ) );

		add_action( 'manage_' . PR_EDITION . '_posts_columns', array( $this, 'cover_columns' ) );
		add_action( 'manage_' . PR_EDITION . '_posts_custom_column', array( $this, 'cover_output_column' ), 10, 2 );

		add_action( 'admin_footer', array( $this, 'register_edition_scripts' ) );

		add_action( 'do_meta_boxes', array( $this, 'change_featured_image_box' ) );

		add_filter( 'admin_post_thumbnail_html', array( $this, 'change_featured_image_html' ) );

	}

	/**
	 * Add custom post type edition to worpress
	 *
	 * @void
	 */
	public function add_edition_post_type() {

		$labels = array(
			'name'                => _x( 'Issues', 'Post Type General Name', 'edition' ),
			'singular_name'       => _x( 'Issue', 'Post Type Singular Name', 'edition' ),
			'menu_name'           => __( 'Issues', 'edition' ),
			'parent_item_colon'   => __( 'Parent issue:', 'edition' ),
			'all_items'           => __( 'All issues', 'edition' ),
			'view_item'           => __( 'View issue', 'edition' ),
			'add_new_item'        => __( 'Add New Issue', 'edition' ),
			'add_new'             => __( 'Add New', 'edition' ),
			'edit_item'           => __( 'Edit issue', 'edition' ),
			'update_item'         => __( 'Update issue', 'edition' ),
			'search_items'        => __( 'Search issue', 'edition' ),
			'not_found'           => __( 'Not found', 'edition' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'edition' ),
		);

		$args = array(
			'label'                => __( 'edition_type', 'edition' ),
			'description'          => __( 'Pressroom issue', 'edition' ),
			'labels'               => $labels,
			'supports'             => array( 'title', 'excerpt', 'author', 'thumbnail', 'custom-fields' ),
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
		);

		register_post_type( PR_EDITION , $args );
	}

	/**
	 * Get custom metaboxes configuration
	 *
	 * @param object $post
	 * @void
	 */
	public function get_custom_metaboxes( $post ) {

		$editorial_terms = wp_get_post_terms( $post->ID, PR_EDITORIAL_PROJECT );
		$e_meta = new PR_Metabox( 'edition_metabox', __( 'Issue Meta', 'edition' ), 'normal', 'high', $post->ID );
		$e_meta->add_field( '_pr_author', __( 'Author', 'edition' ), __( 'Author', 'edition' ), 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$e_meta->add_field( '_pr_creator', __( 'Creator', 'edition' ), __( 'Creator', 'edition' ), 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$e_meta->add_field( '_pr_publisher', __( 'Publisher', 'edition' ), __( 'Publisher', 'edition' ), 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$e_meta->add_field( '_pr_date', __( 'Publication date', 'edition' ), __( 'Publication date', 'edition' ), 'date', date('Y-m-d') );
		$e_meta->add_field( '_pr_newsstand_issue_cover', __( ' iTunes Newsstand cover', 'edition' ), __( 'Cover art image should be .png file and have an aspect ratio between 1:2 and 2:1.<br/>This icon must be at least 1024px on the long side.', 'edition' ), 'file', '' );
		$e_meta->add_field( '_pr_theme_select', __( 'Edition theme', 'edition' ), __( 'Select a theme', 'edition' ), 'select', '', array( 'options' => PR_Theme::get_themes_list() ) );
		$e_meta->add_field( '_pr_edition_free', __( 'Edition free', 'edition' ), __( 'Edition free', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 0, 'name' => __( "Paid", 'edition' ) ),
				array( 'value' => 1, 'name' => __( "Free", 'edition' ) )
			)
		) );
		$e_meta->add_field( '_pr_subscriptions_select', __( 'Included in subscription', 'edition' ), __( 'Select a subscription type', 'edition' ), 'select_multiple', '', array(
			'options' => $this->_get_subscription_types( $post )
		) );
		foreach ( $editorial_terms as $term) {
			$e_meta->add_field( '_pr_product_id_' . $term->term_id, __( 'Product identifier', 'edition' ), __( 'Product identifier for ' . $term->name . ' editorial project', 'edition' ), 'text', '', array( 'required' => 'required' ) );
		}

		$metaboxes = array();

		// Hook to add tab to edition metabox
		do_action_ref_array( 'pr_add_edition_tab', array( &$metaboxes, $post->ID, true ) );

		$flatplan = array(
			'id' 		=> 'flatplan',
			'title' => __('Flatplan' , 'edition'),
		);
		$this->_metaboxes = array(
			(object) $flatplan,
			$e_meta,
		);

		$this->_metaboxes = array_merge( $this->_metaboxes, $metaboxes );
	}

	/**
	 * Add one or more custom metabox to edition custom fields
	 *
	 * @param string $post_type
	 * @param object $post
	 * @void
	 */
	public function add_custom_metaboxes( $post_type, $post ) {

			add_meta_box( 'pressroom_metabox', __( 'Pressroom', 'edition' ), array($this, 'add_custom_metabox_callback'), PR_EDITION, 'advanced', 'high');

	}


	/**
	 * Custom metabox callback print html input field
	 *
	 * @param object $post
	 * @echo
	 */
	public function add_custom_metabox_callback( $post ) {

		echo '<input type="hidden" name="pr_edition_nonce" value="' . wp_create_nonce('pr_edition_nonce'). '" />';
		echo '<div class="press-header postbox">';
		echo '<div class="press-container">';
		echo '<i class="press-pr-logo-gray-wp"></i>';
		echo '<div class="press-header-right">';
		$this->add_publication_action( $post->ID );
		echo '</div>';
		echo '</div>';
		echo '<hr/>';
		$this->add_tabs_to_form( $post );
		echo '</div>';
		echo '<table class="form-table">';


		foreach ( $this->_metaboxes as $metabox ) {
			// render html fields except for flatplan
			if( $metabox->id != 'flatplan' ) {
				echo $metabox->fields_to_html( false, $metabox->id );
			}
		}

		echo '</table>';
		echo '<div class="tabbed flatplan">';
		// render Wp list table for flatplan
		$this->add_presslist();
		echo '</div>';
	}

	/**
	 * Custom featured image title
	 * @void
	 */
	public function change_featured_image_box() {
		remove_meta_box( 'postimagediv', PR_EDITION, 'side' );
		add_meta_box('postimagediv', __('Cover Image'), 'post_thumbnail_meta_box', PR_EDITION, 'side', 'high');
	}

	/**
	 * Custom featured image labels
	 * @param  string $content
	 * @return string
	 */
	public function change_featured_image_html( $content ) {
		global $post;
		if ( $post && $post->post_type == PR_EDITION ) {
			$content = str_replace( __( 'Remove featured image' ), __( 'Remove cover image' ), $content);
			$content = str_replace( __( 'Set featured image' ), __( 'Set cover image' ), $content);
		}
		return $content;
	}

	/**
	* Add tabs menu to edit form
	*
	* @param object $term
	* @echo
	*/
	public function add_tabs_to_form( $post ) {

		$this->get_custom_metaboxes( $post );
		echo '<h2 class="nav-tab-wrapper pr-tab-wrapper">';
		foreach ( $this->_metaboxes as $key => $metabox ) {
			echo '<a class="nav-tab ' . ( !$key ? 'nav-tab-active' : '' ) . '" data-tab="'.$metabox->id.'" href="#">' . $metabox->title . '</a>';
		}
		echo '</h2>';
	}

	/**
	* Pressroom metabox callback
	* Render Wp list table
	*
	* @return void
	*/
	public function add_presslist() {

		$pr_table = new Pressroom_List_Table();
		$pr_table->prepare_items();
		$pr_table->display();
	}


	/**
	* Publication metabox callback
	* Render publication buttons
	*
	* @echo
	*/
	public function add_publication_action( $post_id ) {

		if( $this->ck_exporters ) {

			$packager_type = get_post_meta( $post_id, 'pr_packager_type', true );
			echo '<a id="preview_edition" target="_blank" href="#" class="button preview button">' . __( "Preview", "edition" ) . '</a>';
			echo '<select id="pr_packager_type" name="pr_packager_type">';
			foreach( $this->pr_options['pr_enabled_exporters'] as $key => $exporter ) {
				if( isset( $exporter['active'] ) && $exporter['active'] ) {
					echo '<option '. ( $packager_type == $key ? 'selected="selected"' : '' ) .' value="'.$key.'">'.$exporter['name'].'</option>';
				}
			}

			echo '</select>';
			echo '<a id="publish_edition" target="_blank" href="#" class="button button-primary button-large">' . __( "Distribute", "edition" ) . '</a> ';
			echo '<input type="hidden" value="'. PR_CORE_URI .'" id="pr_core_uri">';
		}
	}

	/**
	 * Save metabox form data
	 *
	 * @param  int $post_id
	 * @void
	 */
	public function save_edition( $post_id ) {

		$post = get_post($post_id);
		if ( !$post || $post->post_type != PR_EDITION ) {
			return;
		}

		//Verify nonce
		if ( !isset( $_POST['pr_edition_nonce'] ) || !wp_verify_nonce( $_POST['pr_edition_nonce'], 'pr_edition_nonce' ) ) {
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
			// flat plan does not have fields to save
			if( $metabox->id != 'flatplan') {
				$metabox->save_values();
			}
		}

		$pr_packager_type = isset( $_POST['pr_packager_type'] ) ? $_POST['pr_packager_type'] : false;
		if( $pr_packager_type ) {
			update_post_meta($post_id, 'pr_packager_type', $pr_packager_type );
		}

		$this->sanitize_linked_posts( $post );

		$edition_theme = get_post_meta( $post_id, '_pr_theme_select', true );
		if ( !$edition_theme ) {
			if ( TPL_Pressroom::is_edit_page() ) {
				$url = admin_url( 'post.php?post=' . $post_id . '&action=edit&pmtype=error&pmcode=theme' );
			} else {
				$url = admin_url( 'post-new.php?post_type=' . PR_EDITION . '&pmtype=error&pmcode=theme' );
			}
			wp_redirect( $url );
			exit;
		}
	}

	/**
	 * Sanitize posts order array
	 *
	 * @param  object $edition
	 * @void
	 */
	public function sanitize_linked_posts( $edition ) {

		$linked_posts = self::get_linked_posts( $edition );

		$old_order = 1;
		foreach ( $linked_posts->posts as $i => $post ) {

			$current_order = p2p_get_meta( $post->p2p_id, 'order', true );
			$current_order = max( $current_order, $old_order );
			$old_order++;

			p2p_update_meta( $post->p2p_id, 'order', $current_order);


			$template = p2p_get_meta( $post->p2p_id, 'template', true );

			if( !$template || !file_exists( PR_THEMES_PATH . $template ) ) {

				$current_theme = get_post_meta( $edition->ID, '_pr_theme_select', true );

				if ( $current_theme ) {
					$themes = PR_Theme::get_themes();
					// $default_template = $current_theme . DS . $themes[$current_theme]['layouts'][0]['path'];
					p2p_update_meta( $post->p2p_id, 'template', '');
				}
			}
		}
	}

	/**
	 * Check if all posts have a template association
	 *
	 * @param  object $edition
	 * @return array $out or boolean
	 */
	public function check_posts_templates( $edition ) {

		$linked_posts = self::get_linked_posts( $edition );

		$out = array();
		foreach ( $linked_posts->posts as $i => $post ) {

			if( has_action( "pr_presslist_{$post->post_type}" ) ) {
				continue;
			}

			$template = p2p_get_meta( $post->p2p_id, 'template', true );

			if( !$template || !file_exists( PR_THEMES_PATH . $template ) ) {
				array_push( $out, $post->p2p_id );
			}
		}

		if( !empty( $out ) ) {
			return $out;
		}

		return true;
	}

	/**
	 * Render the packager console
	 *
	 * @void
	 */
	public function publishing_render_console() {

		$packager = new PR_Packager();
		$pl_url = plugins_url( 'assets', __DIR__ );
		echo '<!DOCTYPE html>
<html>
<head>
<meta charset="UTF-8">
<title>' . __("PressRoom Packager", 'pressroom') . '</title>
<link href="' . $pl_url . '/css/pr.packager.css" rel="stylesheet" type="text/css">
<script type="text/javascript" src="' . $pl_url . '/js/download.min.js"></script>
</head>
<body>
<div id="publishing_popup">
<h1>' . __( 'Package and distribute', 'edition' ) . '</h1>';
		$packager->pb->render();
		echo '<div id="publishing_console">';
		$editorial_terms = wp_get_post_terms( $_GET['edition_id'], PR_EDITORIAL_PROJECT );
		if ( $editorial_terms ){
			foreach ( $editorial_terms as $term ) {
				$packager->run( $term );
			}
		}
		else {
			echo '<p class="liveoutput error"><span class="label">Error</span>' . __( ' No editorial project linked to this edition', 'edition' ) .'</p>';
		}
		echo '</div>
</div>
</body>
</html>';
		exit;
	}

	/**
	* Get reader for preview
	*
	* @void
	*/
	public function pr_preview() {

		require PR_PREVIEW_PATH . "reader.php";
	}

	/**
	 * Ajax publishing callback function
	 *
	 * @echo
	 */
	public function ajax_publishing_callback() {

		$post = get_post( $_POST['edition_id'] );

		if ( !$post || $post->post_type != PR_EDITION ) {
			return;
		}

		$this->get_custom_metaboxes( $post );

		foreach ( $this->_metaboxes as $metabox ) {
			if( $metabox->id != 'flatplan') {
				$metabox->save_values();
			}
		}

		$missing_templates = $this->check_posts_templates( $post );

		if ( is_array( $missing_templates ) ) {
			wp_send_json_error( array( 'missing'=> $missing_templates ) );
		}

		$this->sanitize_linked_posts( $post );

		// saving packager type
		$pr_packager_type = isset( $_POST['pr_packager_type'] ) ? $_POST['pr_packager_type'] : false;
		if( $pr_packager_type ) {
			update_post_meta( $post->ID, 'pr_packager_type', $pr_packager_type );
		}

		wp_send_json_success();
	}

	/**
	 * Add jQuery datepicker script and css styles
	 * @void
	 */
	public function register_edition_scripts() {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );

		wp_register_script( 'edition', PR_ASSETS_URI . '/js/pr.edition.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'edition' );

		global $pagenow, $post_type;
		if ( $pagenow == 'post.php' && $post_type == PR_EDITION ) {
			Pressroom_List_Table::add_presslist_scripts();
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
	 * Get linked posts
	 *
	 * @param object or int $edition
	 * @param array $post_meta
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
	 * @param array $post_meta
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
	 * Add custom columns
	 *
	 * @param  array $columns
	 * @return array $columns
	 */
	public function cover_columns( $columns ) {

		$columns["cover"] = "Cover";
		$columns["paid_free"] = "Paid/Free";

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

			default:
				break;
		}
	}

	/**
	 * Get an edition by slug
	 *
	 * @param string $slug
	 * @return object
	 */
	public static function get_by_slug( $slug ) {

		$args = array(
  		'name' => $slug,
  		'post_type' => PR_EDITION,
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
	 *
	 * @param int $edition_id
	 * @param int $editorial_project_id
	 * @return string or boolean false
	 */
	public static function get_bundle_id( $edition_id, $editorial_project_id ) {

		$edition_bundle_id = false;
		$product_id = get_post_meta( $edition_id, '_pr_product_id_' . $editorial_project_id , true );
		$eproject_options = PR_Editorial_Project::get_configs( $editorial_project_id );
		if ( $product_id && $eproject_options ) {
			$edition_bundle_id = $eproject_options['_pr_prefix_bundle_id'] . '.' . $eproject_options['_pr_single_edition_prefix']. '.' . $product_id;
		}
		return $edition_bundle_id;
	}

	/**
	* Get subscription types terms
	*
	* @param object $post
	* @return array
	*/
	protected function _get_subscription_types( $post ) {

		$terms = wp_get_post_terms( $post->ID, PR_EDITORIAL_PROJECT );
		$types = array();
		if ( !empty( $terms ) ) {
			foreach ( $terms as $term ) {

				$term_meta = get_option( "taxonomy_term_" . $term->term_id );
				if ( $term_meta ) {
					$term_types = isset( $term_meta['_pr_subscription_types'] ) ? $term_meta['_pr_subscription_types'] : '';
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
	 * Enabled exporters check
	 *
	 * @return bool
	 */
	public function check_exporters() {

		$this->pr_options = get_option( 'pr_settings' );
		$this->ck_exporters = false;
		if( isset( $this->pr_options['pr_enabled_exporters'] ) ) {
			foreach( $this->pr_options['pr_enabled_exporters'] as $exporter ) {
				$this->ck_exporters = true;
				return true;
			}
		}

		return false;
	}
}