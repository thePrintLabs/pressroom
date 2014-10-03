<?php
/**
* TPL_ADBundle class.
*/
class TPL_ADBundle
{
	protected $_metaboxes = array();

	public function __construct() {

		if ( !is_admin() ) {
			return;
		}

		add_action( 'init', array( $this, 'add_adbundle_post_type' ), 20 );
		add_action( 'post_edit_form_tag', array( $this, 'form_add_enctype' ) );
		add_action( 'save_post_' . TPL_AD_BUNDLE, array( $this, 'save_adbundle' ), 40 );
		add_filter( 'add_meta_boxes', array( $this, 'add_adbundle_metaboxes' ), 40, 2 );

		// Packager hooks
		add_action( 'pr_packager_run_' . TPL_AD_BUNDLE, array( $this, 'adb_packager_run' ), 10, 2 );
		add_action( 'pr_packager_generate_book_' . TPL_AD_BUNDLE, array( $this, 'adb_packager_book' ), 10 );

		// Preview hooks
		add_action( 'pr_preview_' . TPL_AD_BUNDLE, array( $this, 'adb_preview' ), 10, 3 );
	}

	/**
	* Add custom post type ad bundle to worpress
	*
	* @void
	*/
	public function add_adbundle_post_type() {

		$labels = array(
			'name'                => _x( 'Ad Bundles', 'Ad Bundle General Name', 'adbundle' ),
			'singular_name'       => _x( 'Ad Bundle', 'Ad Bundle Singular Name', 'adbundle' ),
			'menu_name'           => __( 'Ad Bundles', 'adbundle' ),
			'parent_item_colon'   => __( 'Parent Ad Bundle:', 'adbundle' ),
			'all_items'           => __( 'All Ad Bundles ', 'adbundle' ),
			'view_item'           => __( 'View Ad Bundle', 'adbundle' ),
			'add_new_item'        => __( 'Add New Ad Bundle', 'adbundle' ),
			'add_new'             => __( 'Add New', 'adbundle' ),
			'edit_item'           => __( 'Edit Ad Bundle', 'adbundle' ),
			'update_item'         => __( 'Update Ad Bundle', 'adbundle' ),
			'search_items'        => __( 'Search Ad Bundle', 'adbundle' ),
			'not_found'           => __( 'Not found', 'adbundle' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'adbundle' ),
		);

		$args = array(
			'label'               => __( 'Adb_package_type', 'adbundle' ),
			'description'         => __( 'Pressroom Ad Bundle', 'adbundle' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-admin-page',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		);

		register_post_type( TPL_AD_BUNDLE, $args );
	}

	/**
	* Get adbundle metaboxes configuration
	*
	* @void
	*/
	public function get_custom_metaboxes( $post_type, $post ) {

		$adb_meta = new TPL_Metabox( 'adbundle_metabox', __( 'Ad Bundle metabox', 'adbundle' ), 'normal', 'high', $post->ID );
		$adb_meta->add_field( '_pr_html_file', __( 'Html file', 'adbundle' ), __( 'The HTML file from within the ZIP that will be used in the edition.', 'adbundle' ), 'text', 'index.html' );
		$adb_meta->add_field( '_pr_zip', __( 'Zip File', 'edition' ), __( 'Upload zip file', 'edition' ), 'file', '', array( 'allow' => array( 'url', 'attachment' ) ) );

		// Add metabox to metaboxes array
		array_push( $this->_metaboxes, $adb_meta );
	}

	/**
	* Define the metabox and field configurations.
	*
	* @void
	*/
	public function add_adbundle_metaboxes( $post_type, $post ) {

		$this->get_custom_metaboxes( $post_type, $post );
		foreach ( $this->_metaboxes as $metabox ) {
			add_meta_box( $metabox->id, $metabox->title, array( $this, 'add_adbundle_metabox_callback' ), TPL_AD_BUNDLE, $metabox->context, $metabox->priority );
		}
	}

	/**
	* Custom metabox callback print html input field
	*
	* @echo
	*/
	public function add_adbundle_metabox_callback() {

		echo '<input type="hidden" name="tpl_adbundle_nonce" value="' . wp_create_nonce('tpl_adbundle_nonce'). '" />';
		echo '<table class="form-table">';

		foreach ( $this->_metaboxes as $metabox ) {
			echo $metabox->fields_to_html();
		}

		echo '</table>';
	}

	/**
	* Add enctype to form for fileupload
	* @echo
	*/
	public function form_add_enctype() {

		echo ' enctype="multipart/form-data"';
	}

	/**
	* Save metabox form data
	* @param  int $post_id
	* @void
	*/
	public function save_adbundle( $post_id ) {

		$post = get_post( $post_id );
		if ( !$post || $post->post_type != TPL_AD_BUNDLE ) {
			return;
		}

		//Verify nonce
		if ( !isset( $_POST['tpl_adbundle_nonce'] ) || !wp_verify_nonce( $_POST['tpl_adbundle_nonce'], 'tpl_adbundle_nonce' ) ) {
			return $post_id;
		}

		//Check autosave
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return $post_id;
		}

		//Check permissions
		if ( !current_user_can( 'edit_page', $post_id ) ) {
			return $post_id;
		}

		$this->get_custom_metaboxes( TPL_AD_BUNDLE, $post);
		foreach ( $this->_metaboxes as $metabox ) {
			$metabox->save_values();
		}
	}

	/**
	* Add AdBundle support to packager
	* @param  object $post
	* @param  string $edition_dir
	* @void
	*/
	public function adb_packager_run( $post, $edition_dir ) {

		$attachment = self::get_adb_attachment( $post->ID );
		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$adb_attached_file = get_attached_file( $attachment->ID );

			if ( $zip->open( $adb_attached_file ) ) {

				$adb_title = TPL_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir . DIRECTORY_SEPARATOR . TPL_AD_BUNDLE . DIRECTORY_SEPARATOR . $adb_title ) ) {
					TPL_Packager::print_line( __( 'Unzipped file ', 'edition' ) . $adb_attached_file, 'success' );
				} else {
					TPL_Packager::print_line( __( 'Failed to unzip file ', 'edition' ) . $adb_attached_file, 'error' );
				}
				$zip->close();

			}
			else {
				TPL_Packager::print_line( __( 'Failed to unzip file ', 'edition') . $adb_attached_file, 'error' );
			}
		}
	}

	/**
	* [add_adb_bookjson description]
	* @param object $post
	* @param string $edition_dir
	* @void
	*/
	public function adb_packager_book( &$args ) {

		list( $press_options, $post, $edition_dir ) = $args;

		$adb_index = get_post_meta( $post->ID, '_pr_html_file', true );
		$adb_dir = TPL_Utils::sanitize_string( $post->post_title );

		$file_index = $edition_dir . DIRECTORY_SEPARATOR . TPL_AD_BUNDLE . DIRECTORY_SEPARATOR. $adb_dir . DIRECTORY_SEPARATOR . $adb_index;
		if ( is_file( $file_index ) ) {
			$press_options['contents'][] = TPL_AD_BUNDLE . DIRECTORY_SEPARATOR . $adb_dir . DIRECTORY_SEPARATOR . $adb_index;
			$args[0] = $press_options;
		}
		else {
			TPL_Packager::print_line( sprintf( __( "Can't find file %s. It won't add to book.json. See the wiki to know how to make an add bundle", 'edition' ), $file_index ), 'error' );
		}
	}

	/**
	 * [adb_preview description]
	 * @param  [type] $args [description]
	 * @return [type]       [description]
	 */
	public function adb_preview( &$args ) {

		list( $html, $edition, $post ) = $args;

		$attachment = self::get_adb_attachment( $post->ID );

		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$adb_attached_file = get_attached_file( $attachment->ID );
			if ( $zip->open( $adb_attached_file ) ) {

				$edition_dir = TPL_Utils::make_dir( TPL_PREVIEW_DIR, $edition->post_title );
				$adb_title = TPL_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir ) ) {

					$index_file = get_post_meta( $post->ID, '_pr_html_file', true );
					if ( $index_file ) {
						$index_path = $edition_dir . DIRECTORY_SEPARATOR . $adb_title . DIRECTORY_SEPARATOR . $index_file;
						if ( file_exists( $index_path ) ) {
							$args[0] = file_get_contents( $index_path );
						}
					}
				}
				$zip->close();
			}
		}
	}

	/**
	* Get adbundle zip attachment
	* @param  int $adb_id
	* @return object or boolean false
	*/
	public static function get_adb_attachment( $adb_id ) {

		$attachment_id = get_post_meta( $adb_id, '_pr_zip', true );
		if ( $attachment_id ) {
			$attachment = get_post($attachment_id);
			if ( $attachment ) {
				return $attachment;
			}
		}

		return false;
	}
}

$tpl_adbundle = new TPL_ADBundle;
