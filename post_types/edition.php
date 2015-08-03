<?php

class PR_Edition
{
	protected $_exporters_mb = array();

	/**
	 * Register required hooks
	 *
	 * @void
	 */
	public function __construct() {
		if ( !is_admin() ) {
			return;
		}

		add_theme_support( 'post-thumbnails', array( PR_EDITION ) );

		add_action( 'press_flush_rules', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( 'PR_Theme', 'search_themes' ), 20 );
		add_action( 'admin_notices', array( $this, 'edition_notices' ) );

		add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
		add_action( 'do_meta_boxes', array( $this, 'change_featured_image_box' ) );
		add_action( 'admin_footer', array( $this, 'register_edition_scripts' ) );
		add_action( 'admin_menu', array( $this, 'remove_default_metaboxes' ) );
		add_action( 'post_edit_form_tag', array( $this,'form_add_enctype' ) );
		add_action( 'edit_form_after_title', array( $this,'add_presslist_to_form' ) );

		add_filter( 'admin_post_thumbnail_html', array( $this, 'change_featured_image_html' ) );

		add_action( 'save_post_' . PR_EDITION, array( $this, 'save_edition'), 40 );
		add_action( 'wp_ajax_publishing', array( $this, 'ajax_publishing_callback' ) );
		add_action( 'wp_ajax_render_console', array( $this, 'publishing_render_console' ) );
		add_action( 'wp_ajax_pr_preview', array( $this, 'pr_preview' ) );

		add_action( 'manage_' . PR_EDITION . '_posts_columns', array( $this, 'cover_columns' ) );
		add_action( 'manage_' . PR_EDITION . '_posts_custom_column', array( $this, 'cover_output_column' ), 10, 2 );
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
	 * Get exporters metaboxes configuration
	 *
	 * @param object $post
	 * @void
	 */
	public function get_exporters_metaboxes( $post ) {
		$metaboxes = array();
		// Hook to add metabox to edition
		do_action_ref_array( 'pr_add_edition_exporter_metabox', array( &$metaboxes, $post->ID, true ) );
		$this->_exporters_mb = $metaboxes;
	}

	/**
	 * Get custom metaboxes configuration
	 *
	 * @param object $post
	 * @return object
	 */
	public function get_issuemeta_metabox( $post ) {
		$issue_metabox = new PR_Metabox( 'issue_metabox', __( 'Issue Meta', 'edition' ), 'side', 'high', $post->ID );
		$issue_metabox->add_field( '_pr_author', __( 'Author', 'edition' ), '', 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$issue_metabox->add_field( '_pr_creator', __( 'Creator', 'edition' ), '', 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$issue_metabox->add_field( '_pr_publisher', __( 'Publisher', 'edition' ), '', 'text', get_bloginfo( 'name' ), array( "required" => true ) );
		$issue_metabox->add_field( '_pr_date', __( 'Publication date', 'edition' ), '', 'date', date('Y-m-d') );
		$issue_metabox->add_field( '_pr_theme_select', __( 'Edition theme', 'edition' ), '', 'select', '', array( 'options' => PR_Theme::get_themes_list() ) );
		return $issue_metabox;
	}

	/**
	 * Add one or more custom metabox to edition custom fields
	 *
	 * @param string $post_type
	 * @param object $post
	 * @void
	 */
	public function add_custom_metaboxes( $post_type, $post ) {
		add_meta_box( 'pressroom_issue_metabox', __( 'Issue meta', 'edition' ), array( $this, 'add_issue_metabox_callback' ), PR_EDITION, 'side', 'default' );
		add_meta_box( 'pressroom_distribution_metabox', __( 'PressRoom', 'edition' ), array( $this, 'add_distribution_metabox_callback' ), PR_EDITION, 'side', 'default' );
		add_meta_box('postexcerpt', __('Description'), 'post_excerpt_meta_box', PR_EDITION, 'normal', 'high');

		$this->get_exporters_metaboxes( $post );
		foreach ( $this->_exporters_mb as $metabox ) {
			add_meta_box( $metabox->id, $metabox->title, array( $this, 'add_exporter_metabox_callback' ), PR_EDITION, $metabox->context, $metabox->priority, 'advanced', 'default' );
		}
	}

	/**
	 * Custom metabox callback print html input field
	 *
	 * @param object $post
	 * @echo
	 */
	public function add_exporter_metabox_callback( $post, $metabox ) {
		foreach ( $this->_exporters_mb as $exporter_mb ) {
			if ( $exporter_mb->id == $metabox['id'] ) {
				echo '<table class="form-table">';
				echo $exporter_mb->fields_to_html( false, $exporter_mb->id );
				echo '</table>';
			}
		}
	}

	/**
	 * Custom issue meta metabox callback print html input field
	 *
	 * @param object $post
	 * @echo
	 */
	public function add_issue_metabox_callback( $post, $metabox ) {
		$issue_metabox = $this->get_issuemeta_metabox( $post );
		echo $issue_metabox->fields_to_html( false, $metabox['id'], 'div' );
	}

	/**
	 * Custom distribution metabox callback
	 *
	 * @param object $post
	 * @echo
	 */
	public function add_distribution_metabox_callback( $post ) {
		$pr_settings = get_option( 'pr_settings' );
		$packager_type = get_post_meta( $post->ID, '_pr_packager_type', true );

		echo '<div class="submitbox" id="submitpost">
<div id="minor-publishing">
<div id="misc-publishing-actions">
<div class="misc-pub-section">
<a id="preview_edition" target="_blank" href="#" class="button preview button">' . __( "Preview", "edition" ) . '</a>
<select id="pr_packager_type" name="pr_packager_type">';
		foreach( $pr_settings['pr_enabled_exporters'] as $key => $exporter ) {
			if ( isset( $exporter['active'] ) && $exporter['active'] ) {
				echo '<option ' . ( $packager_type == $key ? 'selected="selected"' : '' ) . ' value="' . $key . '">' . $exporter['name'] . '</option>';
			}
		}
		echo '</select>
</div>
</div>
<div class="clear"></div>
</div>
<div id="major-publishing-actions">
<div id="publishing-action">
<input type="hidden" value="' . PR_CORE_URI . '" id="pr_core_uri">
<input type="hidden" name="pr_edition_nonce" value="' . wp_create_nonce('pr_edition_nonce'). '" />
<a id="publish_edition" target="_blank" href="#" class="button button-primary button-large">' . __( "Distribute", "edition" ) . '</a>
</div>
<div class="clear"></div>
</div>
</div>';
	}

	/**
	 * Render Wp list table for flatplan
	 * @void
	 */
	public function add_presslist_to_form( $post ) {
		if ( $post->post_type == PR_EDITION ) {
			echo '<div class="tabbed flatplan" id="pressroom_metabox">
<div class="inside">';
			$pr_table = new Pressroom_List_Table();
			$pr_table->prepare_items();
			$pr_table->display();
			echo '</div>
</div>';
		}
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

		$this->get_exporters_metaboxes( $post );
		foreach ( $this->_exporters_mb as $metabox ) {
			// flat plan does not have fields to save
			if( $metabox->id != 'flatplan') {
				$metabox->save_values();
			}
		}

		$issue_metabox = $this->get_issuemeta_metabox( $post );
		$issue_metabox->save_values();

		$pr_packager_type = isset( $_POST['pr_packager_type'] ) ? $_POST['pr_packager_type'] : false;
		if( $pr_packager_type ) {
			update_post_meta($post_id, '_pr_packager_type', $pr_packager_type );
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

		$this->get_exporters_metaboxes( $post );
		foreach ( $this->_exporters_mb as $metabox ) {
			if( $metabox->id != 'flatplan') {
				$metabox->save_values();
			}
		}

		$issue_metabox = $this->get_issuemeta_metabox( $post );
		$issue_metabox->save_values();

		$missing_templates = $this->check_posts_templates( $post );
		if ( is_array( $missing_templates ) ) {
			wp_send_json_error( array( 'missing'=> $missing_templates ) );
		}

		$this->sanitize_linked_posts( $post );

		// saving packager type
		$pr_packager_type = isset( $_POST['pr_packager_type'] ) ? $_POST['pr_packager_type'] : false;
		if( $pr_packager_type ) {
			update_post_meta( $post->ID, '_pr_packager_type', $pr_packager_type );
		}
		wp_send_json_success();
	}

	/**
	 * Remove default post metaboxes
	 * @void
	 */
	public function remove_default_metaboxes() {
		remove_meta_box( 'authordiv', PR_EDITION, 'normal' ); // Author Metabox
		remove_meta_box( 'commentstatusdiv', PR_EDITION, 'normal' ); // Comments Status Metabox
		remove_meta_box( 'commentsdiv', PR_EDITION, 'normal' ); // Comments Metabox
		remove_meta_box( 'postcustom', PR_EDITION, 'normal' ); // Custom Fields Metabox
		remove_meta_box( 'postexcerpt', PR_EDITION, 'normal' ); // Excerpt Metabox
		remove_meta_box( 'slugdiv', PR_EDITION, 'normal' ); // Slug Metabox
		remove_meta_box( 'trackbacksdiv', PR_EDITION, 'normal' ); // Trackback Metabox
		remove_meta_box( 'categorydiv', PR_EDITION, 'normal' ); // Categories Metabox
		remove_meta_box( 'formatdiv', PR_EDITION, 'normal' ); // Formats Metabox
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
	 *
	 *
	 */
	public function edition_notices() {
		global $post_type;
		if ( $post_type == PR_EDITION ) {
			$themes = PR_Theme::get_themes();
			if ( empty( $themes ) ) {
				echo '<div class="pr-alert update-nag">' . __( sprintf( 'Please activate a theme to start using PressRoom. You can upload a custom theme, or get new ones from the %s', '<a href="' . admin_url( 'admin.php?page=pressroom-themes' ) . '">theme settings page</a>' ), 'edition' ) . '</div>';
			}
		}
	}
}
