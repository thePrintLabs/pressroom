<?php

class PR_Edition
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

		add_theme_support( 'post-thumbnails', array( PR_EDITION ) );

		add_action( 'press_flush_rules', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( 'PR_Theme', 'search_themes' ), 20 );

		add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );

		add_action( 'save_post_' . PR_EDITION, array( $this, 'save_edition'), 40 );

		add_action( 'wp_ajax_publishing', array( $this, 'ajax_publishing_callback' ) );
		add_action( 'wp_ajax_render_console', array( $this, 'publishing_render_console' ) );

		add_action( 'admin_enqueue_scripts', array( $this,'register_edition_script' ) );

		add_action( 'post_edit_form_tag', array( $this,'form_add_enctype' ) );

		add_action( 'manage_' . PR_EDITION . '_posts_columns', array( $this, 'cover_columns' ) );
		add_action( 'manage_' . PR_EDITION . '_posts_custom_column', array( $this, 'cover_output_column' ), 10, 2 );

		add_action( 'admin_footer', array( $this, 'add_custom_script' ) );

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
		$e_meta = new PR_Metabox( 'edition_metabox', __( 'Edition Meta', 'edition' ), 'normal', 'high', $post->ID );
		$e_meta->add_field( '_pr_author', __( 'Author', 'edition' ), __( 'Author', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_creator', __( 'Creator', 'edition' ), __( 'Creator', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_publisher', __( 'Publisher', 'edition' ), __( 'Publisher', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_date', __( 'Publication date', 'edition' ), __( 'Publication date', 'edition' ), 'date', date('Y-m-d') );
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
			$e_meta->add_field( '_pr_product_id_' . $term->term_id, __( 'Product identifier', 'edition' ), __( 'Product identifier for ' . $term->name . ' editorial project', 'edition' ), 'text', '' );
		}

		$hpub = new PR_Metabox( 'hpub', __( 'hpub', 'edition' ), 'normal', 'high', $post->ID );
		$hpub->add_field( '_pr_hpub_override_eproject', __( 'Override Editorial Project settings', 'editorial_project' ), __( 'If enabled, will be used edition settings below', 'edition' ), 'checkbox', false );
		$hpub->add_field( '_pr_default', '<h3>Visualization properties</h3><hr>', '', 'textnode', '' );
		$hpub->add_field( '_pr_orientation', __( 'Orientation', 'edition' ), __( 'The publication orientation.', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 'portrait', 'name' => __( "Portrait", 'edition' ) ),
				array( 'value' => 'landscape', 'name' => __( "Landscape", 'edition' ) ),
				array( 'value' => 'both', 'name' => __( "Both", 'edition' ) )
				)
				) );
		$hpub->add_field( '_pr_zoomable', __( 'Zoomable', 'editorial_project' ), __( 'Enable pinch to zoom of the page.', 'edition' ), 'checkbox', false );
		$hpub->add_field( '_pr_body_bg_color', __( 'Body background color', 'edition' ), __( 'Background color to be shown before pages are loaded.', 'editorial_project' ), 'color', '' );

		$hpub->add_field( '_pr_background_image_portrait', __( 'Background image portrait', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in portrait mode.', 'editorial_project' ), 'file', '' );
		$hpub->add_field( '_pr_background_image_landscape', __( 'Background image landscape', 'edition' ), __( 'Image file to be shown as a background before pages are loaded in landscape mode.', 'editorial_project' ), 'file', '' );
		$hpub->add_field( '_pr_page_numbers_color', __( 'Page numbers color', 'edition' ), __( 'Color for page numbers to be shown before pages are loaded.', 'editorial_project' ), 'color', '#ffffff' );
		$hpub->add_field( '_pr_page_numbers_alpha', __( 'Page number alpha', 'edition' ), __( 'Opacity for page numbers to be shown before pages are loaded. (min 0 => max 1)', 'editorial_project' ), 'decimal', 0.3 );
		$hpub->add_field( '_pr_page_screenshot', __( 'Page Screenshoot', 'edition' ), __( 'Path to a folder containing the pre-rendered pages screenshots.', 'editorial_project' ), 'text', '' );
		$hpub->add_field( '_pr_default', '<h3>Behaviour properties</h3><hr>', '', 'textnode', '' );

		$hpub->add_field( '_pr_start_at_page', __( 'Start at page', 'edition' ), __( 'Defines the starting page of the publication. If the number is negative, the publication starting at the end and with numbering reversed. ( Note: this setting works only the first time edition is opened )', 'editorial_project' ), 'number', 1 );
		$hpub->add_field( '_pr_rendering', __( 'Rendering type', 'edition' ), __( 'App rendering mode. See the page on <a target="_blank" href="https://github.com/Simbul/baker/wiki/Baker-rendering-modes">Baker rendering modes.</a>', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 'screenshots', 'name' => __( "Screenshots", 'edition' ) ),
				array( 'value' => 'three-cards', 'name' => __( "Three cards", 'edition' ) )
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

		$metaboxes = array();
		do_action_ref_array( 'pr_add_edition_tab', array( &$metaboxes, $post->ID, true ) );

		$flatplan = array(
			'id' 		=> 'flatplan',
			'title' => __('Flatplan' , 'edition'),
		);
		$this->_metaboxes = array(
			(object) $flatplan,
			$e_meta,
			$hpub,
		);


		$this->_metaboxes = array_merge( $this->_metaboxes, $metaboxes );

	}

	/**
	 * Add one or more custom metabox to edition custom fields
	 *
	 * @void
	 */
	public function add_custom_metaboxes( $post_type, $post ) {

			add_meta_box( 'pressroom_metabox', __( 'Pressroom', 'edition' ), array($this, 'add_custom_metabox_callback'), PR_EDITION, 'advanced', 'high');

	}


	/**
	 * Custom metabox callback print html input field
	 *
	 * @echo
	 */
	public function add_custom_metabox_callback( $post ) {

		echo '<input type="hidden" name="pr_edition_nonce" value="' . wp_create_nonce('pr_edition_nonce'). '" />';
		echo '<div class="press-header postbox">';
		echo '<div class="press-container">';
		echo '<i class="press-pr-logo-gray-wp"></i>';
		echo '<div class="press-header-right">';
		$this->add_publication_action();
		echo '</div>';
		echo '</div>';
		echo '<hr/>';
		$this->add_tabs_to_form( $post );
		echo '</div>';
		echo '<table class="form-table">';


		foreach ( $this->_metaboxes as $metabox ) {
			if( $metabox->id != 'flatplan' ) {
				echo $metabox->fields_to_html( false, $metabox->id );
			}
		}

		echo '</table>';
		echo '<div class="tabbed flatplan">';
		$this->add_presslist();
		echo '</div>';
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
	 *
	 * @return void
	 */
	public function add_pressroom_metabox_callback() {

		$pr_table = new Pressroom_List_Table();
		$pr_table->prepare_items();
		$pr_table->display();
	}

	/**
	* Pressroom metabox callback
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
	*
	* @echo
	*/
	public function add_publication_action() {

		echo '<a id="preview_edition" target="_blank" href="' . PR_CORE_URI . 'preview/reader.php?edition_id=' . get_the_id() . '" class="button preview button">' . __( "Preview", "edition" ) . '</a>';
		echo '<select id="pr_packager_type"><option value="web">web</option><option value="hpub">hpub</option></select>';
		echo '<a id="publish_edition" target="_blank" href="#" class="button button-primary button-large">' . __( "Distribute", "edition" ) . '</a> ';

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
			if( $metabox->id != 'flatplan') {
				$metabox->save_values();
			}
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
					$default_template = $themes[$current_theme][0]['filename'];
					p2p_update_meta( $post->p2p_id, 'template', $default_template);
				}
			}
		}
	}

	public function publishing_render_console() {

		$packager = new PR_Packager();
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
		</div>';
		exit;
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

		wp_send_json_success();

	}

	/**
	 * Add jQuery datepicker script and css styles
	 * @void
	 */
	public function register_edition_script() {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'pressroom', PR_ASSETS_URI . 'css/pressroom.css' );
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
	protected function _get_subscription_types( $post ) {

		$terms = wp_get_post_terms( $post->ID, PR_EDITORIAL_PROJECT );
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
				echo '<a target="_blank" href="'. PR_CORE_URI . 'preview/reader.php?edition_id=' . get_the_id() . '" >Preview</a><br/>';
				echo '<a id="publish_edition" href="' . admin_url('admin-ajax.php') . '?action=publishing&edition_id=' . get_the_id() . '&pr_no_theme=true">' . __( "Packaging", "edition" ) . '</a> ';
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
	* add custom script to metabox
	*
	* @void
	*/
	public function add_custom_script() {

		wp_register_script( 'edition', PR_ASSETS_URI . '/js/pr.edition.js', array( 'jquery' ), '1.0', true );
		wp_enqueue_script( 'edition' );
	}
}
