<?php

require_once( TPL_LIBS_PATH . 'metabox.php' );

/**
 * TPL_Edition class.
 */
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

		add_action( 'init', array( $this, 'add_edition_post_type' ), 10 );
		add_action( 'init', array( 'TPL_Theme', 'search_themes' ), 20 );

		add_action( 'add_meta_boxes', array( $this, 'add_custom_metaboxes' ), 30, 2 );
		add_action( 'add_meta_boxes', array( $this, 'add_pressroom_metabox' ), 40 );
		add_action( 'save_post_' . TPL_EDITION, array( $this, 'save_edition'), 40 );

		add_action( 'wp_ajax_publishing', array( $this, 'ajax_publishing_callback' ) );
		add_action( 'wp_ajax_preview', array( $this, 'ajax_preview_callback' ) );

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
	 * @void
	 */
	public function get_custom_metaboxes( $post_type, $post ) {

		$e_meta = new TPL_Metabox( 'edition_metabox', __( 'Edition metabox', 'edition' ), 'normal', 'high', $post->ID );
		$e_meta->add_field( '_pr_author', __( 'Author', 'edition' ), __( 'Author', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_creator', __( 'Creator', 'edition' ), __( 'Creator', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_publisher', __( 'Publisher', 'edition' ), __( 'Publisher', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_product_id', __( 'Product identifier', 'edition' ), __( 'Product identifier', 'edition' ), 'text', '' );
		$e_meta->add_field( '_pr_date', __( 'Publication date', 'edition' ), __( 'Publication date', 'edition' ), 'date', date('Y-m-d') );
		$e_meta->add_field( '_pr_theme_select', __( 'Edition theme', 'edition' ), __( 'Select a theme', 'edition' ), 'select', '', array( 'options' => TPL_Theme::get_themes_list() ) );
		$e_meta->add_field( '_pr_edition_free', __( 'Edition free', 'edition' ), __( 'Edition free', 'edition' ), 'radio', '', array(
			'options' => array(
				array( 'value' => 0, 'name' => __( "Paid", 'edition' ) ),
				array( 'value' => 1, 'name' => __( "Free", 'edition' ) )
			)
		) );
		$e_meta->add_field( '_pr_subscriptions_select', __( 'Included in subscription', 'edition' ), __( 'Select a subscription type', 'edition' ), 'checkbox_list', '', array(
			'options' => $this->_get_subscription_types()
		) );

		// Add metabox to metaboxes array
		array_push( $this->_metaboxes, $e_meta );
	}

	/**
	 * Add one or more custom metabox to edition custom fields
	 *
	 * @void
	 */
	public function add_custom_metaboxes( $post_type, $post ) {

		$this->get_custom_metaboxes( $post_type, $post );
		foreach ( $this->_metaboxes as $metabox ) {
			add_meta_box($metabox->id, $metabox->title, array($this, 'add_custom_metabox_callback'), TPL_EDITION, $metabox->context, $metabox->priority);
		}
	}

	/**
	 * Add required edition posts metabox
	 *
	 * @void
	 */
	public function add_pressroom_metabox() {

		if ( TPL_Pressroom::is_edit_page() ) {

			add_meta_box( 'pressroom_metabox', __( 'Linked posts', 'edition' ), array( $this, 'add_pressroom_metabox_callback' ), TPL_EDITION );
		}
	}

	/**
	* Add publication metabox
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
	 * @return void
	 */
	public function add_pressroom_metabox_callback() {

		$pr_table = new Pressroom_List_Table();
		$pr_table->prepare_items();
		$pr_table->display();
	}

	/**
	* Publication metabox callback
	* @echo
	*/
	public function add_publication_metabox_callback() {

		$preview_url = admin_url('admin-ajax.php') . '?action=preview&edition_id=' . get_the_id();
		echo '<a id="publish_edition" href="' . admin_url('admin-ajax.php') . '?action=publishing&edition_id=' . get_the_id() . '&width=800&height=600&TB_iframe=true" class="button button-primary button-large thickbox">' . __( "Packaging", "edition" ) . '</a> ';
		echo '<a id="preview_edition" target="_blank" href="'. TPL_PLUGIN_URI .'preview/index.php?url='. urlencode( $preview_url ) .'" class="button button-primary button-large">' . __( "Preview", "edition" ) . '</a> ';
	}

	/**
	 * Save metabox form data
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

		$this->get_custom_metaboxes( TPL_EDITION, $post);
		foreach ( $this->_metaboxes as $metabox ) {
			$metabox->save_values();
		}

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

	/**
	 * Ajax publishing callback function
	 * @echo
	 */
	public function ajax_publishing_callback() {

		$packager = new TPL_Packager();
		echo '<style>
		#publishing_popup .error .label {background: #c22d26;}
		#publishing_popup .success .label {background: #7dc42a;}
		#publishing_popup .info .label {background: #000;}
		#publishing_popup .label {color:#fff; text-transform:capitalize; padding: 2px 5px;}
		#publishing_popup p { font-family: "Open Sans",sans-serif; font-size: 12px; line-height: 20px; margin: 5px 0;}
		#publishing_popup h1 {margin-bottom: 10px}
		</style>';
		echo '<div id="publishing_popup"><h1>' . __( 'Publication progress', 'edition' ) . '</h1>';
		$packager->run();
		echo '</div>';
		exit;
	}

	public function ajax_preview_callback() {
		$preview = new TPL_Preview();
		$preview->init_preview_swiper();
		exit;
	}

	/**
	 * Add jQuery datepicker script and css styles
	 * @void
	 */
	public function register_edition_script() {

		wp_enqueue_script( 'jquery-ui-datepicker' );
		wp_enqueue_style( 'jquery-style', 'http://ajax.googleapis.com/ajax/libs/jqueryui/1.8.2/themes/smoothness/jquery-ui.css' );
		wp_enqueue_style( 'pressroom', TPL_PLUGIN_ASSETS . 'css/pressroom.css' );
	}

	/**
	 * Add enctype to form for fileupload
	 * @echo
	 */
	public function form_add_enctype() {

		echo ' enctype="multipart/form-data"';
	}

	/**
	 * Add thickbox to form for the packager support
	 * @void
	 */
	public function form_add_thickbox() {

		add_thickbox();
	}

	protected function _get_subscription_types() {

		$types = array();
		$terms = get_terms( TPL_EDITORIAL_PROJECT, array( 'hide_empty' => false ) );
		foreach ( $terms as $term ) {
			$term_meta = get_option( "taxonomy_term_" . $term->term_id );
			$term_types = unserialize( $term_meta['subscription_type'] );
			foreach ( $term_types as $type ) {
				array_push( $types, array(
					'value' => $term_meta['prefix_bundle_id']. '.' . $term_meta['subscription_prefix']. '.' . $type,
					'text'  => $type
				) );
			}
		}

		return $types;
	}

	/**
	 * Add custom columns
	 * @param  array $columns
	 * @return array $columns
	 */
	public function cover_columns( $columns ) {

		$columns["cover"] = "Cover";
		$columns["paid_free"] = "Paid/Free";
		$columns["previews"] = "Preview";

		return $columns;
	}

	/**
	 * Set output for custom columns
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

			case 'previews':
				$preview_url = admin_url('admin-ajax.php') . '?action=preview&edition_id=' . get_the_id();
				echo '<a target="_blank" href="'. TPL_PLUGIN_URI .'preview/index.php?url='. urlencode( $preview_url ) .'" >View</a>';
				break;

			default:
				break;
		}
	}
}
