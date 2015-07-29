<?php

class PR_ADBundle
{
	protected $_metaboxes = array();

	/**
	 * construct
	 *
	 * @void
	 */
	public function __construct() {

		if ( !is_admin() ) {
			return;
		}

		add_action( 'press_flush_rules', array( $this, 'add_adbundle_post_type' ), 10 );
		add_action( 'init', array( $this, 'add_adbundle_post_type' ), 20 );
		add_action( 'post_edit_form_tag', array( $this, 'form_add_enctype' ) );
		add_action( 'save_post_pr_ad_bundle', array( $this, 'save_adbundle' ), 40 );
		add_filter( 'add_meta_boxes', array( $this, 'add_adbundle_metaboxes' ), 40, 2 );
		add_action( 'admin_menu', array( $this, 'remove_default_metaboxes' ) );

		add_action( 'pr_packager_run_hpub_pr_ad_bundle', array( $this, 'adb_packager_run' ), 10, 2 );
		add_action( 'pr_packager_run_web_pr_ad_bundle', array( $this, 'adb_packager_run' ), 10, 2 );
		add_action( 'pr_packager_run_adps_pr_ad_bundle', array( $this, 'adb_adps_packager_run' ), 10, 2 );

		add_action( 'pr_packager_shortcode_web_pr_ad_bundle', array( $this, 'adb_web_shortcode' ), 10, 2 );
		add_action( 'pr_packager_generate_book', array( $this, 'adb_packager_book' ), 10, 3 );
		add_action( 'pr_packager_parse_pr_ad_bundle', array( $this, 'adb_packager_post_parse' ), 10, 2 );

		add_action( 'pr_preview_pr_ad_bundle', array( $this, 'adb_preview' ), 10, 3 );
		add_action( 'pr_presslist_pr_ad_bundle', array( $this, 'adbundle_presslist' ), 10, 3 );

	}

	/**
	* Add custom post type ad-bundle to worpress
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

		register_post_type( 'pr_ad_bundle', $args );
	}

	/**
	* Get ad-bundle metaboxes configuration
	*
	* @void
	*/
	public function get_custom_metaboxes( $post_type, $post ) {

		$adb_meta = new PR_Metabox( 'adbundle_metabox', __( 'Ad Bundle Settings', 'adbundle' ), 'normal', 'high', $post->ID );
		$adb_meta->add_field( '_pr_html_file', __( 'Html file', 'adbundle' ), __( 'The HTML file from within the ZIP that will be used in the issue.', 'adbundle' ), 'text', 'index.html' );
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
			add_meta_box( $metabox->id, $metabox->title, array( $this, 'add_adbundle_metabox_callback' ), 'pr_ad_bundle', $metabox->context, $metabox->priority );
		}
	}

	/**
	 * Remove default post metaboxes
	 * @void
	 */
	public function remove_default_metaboxes() {
		remove_meta_box( 'authordiv', 'pr_ad_bundle', 'normal' ); // Author Metabox
		remove_meta_box( 'commentstatusdiv', 'pr_ad_bundle', 'normal' ); // Comments Status Metabox
		remove_meta_box( 'commentsdiv', 'pr_ad_bundle', 'normal' ); // Comments Metabox
		remove_meta_box( 'postcustom', 'pr_ad_bundle', 'normal' ); // Custom Fields Metabox
		remove_meta_box( 'postexcerpt', 'pr_ad_bundle', 'normal' ); // Excerpt Metabox
		remove_meta_box( 'revisionsdiv', 'pr_ad_bundle', 'normal' ); // Revisions Metabox
		remove_meta_box( 'slugdiv', 'pr_ad_bundle', 'normal' ); // Slug Metabox
		remove_meta_box( 'trackbacksdiv', 'pr_ad_bundle', 'normal' ); // Trackback Metabox
		remove_meta_box( 'categorydiv', 'pr_ad_bundle', 'normal' ); // Categories Metabox
		remove_meta_box( 'formatdiv', 'pr_ad_bundle', 'normal' ); // Formats Metabox
	}

	/**
	* Custom metabox callback
	* print html input field
	*
	* @echo
	*/
	public function add_adbundle_metabox_callback() {

		echo '<input type="hidden" name="pr_adbundle_nonce" value="' . wp_create_nonce('pr_adbundle_nonce'). '" />';
		echo '<table class="form-table">';

		foreach ( $this->_metaboxes as $metabox ) {
			echo $metabox->fields_to_html();
		}

		echo '</table>';
	}

	/**
	* Add form enctype for fileupload
	*
	* @echo
	*/
	public function form_add_enctype() {

		echo ' enctype="multipart/form-data"';
	}

	/**
	* Save metabox form data
	*
	* @param  int $post_id
	* @void
	*/
	public function save_adbundle( $post_id ) {

		$post = get_post( $post_id );
		if ( !$post || $post->post_type != 'pr_ad_bundle' ) {
			return;
		}

		//Verify nonce
		if ( !isset( $_POST['pr_adbundle_nonce'] ) || !wp_verify_nonce( $_POST['pr_adbundle_nonce'], 'pr_adbundle_nonce' ) ) {
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

		$this->get_custom_metaboxes( 'pr_ad_bundle', $post);
		foreach ( $this->_metaboxes as $metabox ) {
			$metabox->save_values();
		}
	}

	/**
	 * Add Ad-bundle support to packager
	 * This function is called by a custom hook
	 * from the packager class
	 *
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

				$adb_title = PR_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir . DS . 'pr_ad_bundle' . DS . $adb_title ) ) {
					PR_Packager::print_line( __( 'Unzipped file ', 'edition' ) . $adb_attached_file, 'success' );
				} else {
					PR_Packager::print_line( __( 'Failed to unzip file ', 'edition' ) . $adb_attached_file, 'error' );
				}
				$zip->close();

			}
			else {
				PR_Packager::print_line( __( 'Failed to unzip file ', 'edition') . $adb_attached_file, 'error' );
			}
		}
	}

	/**
	 * Add Ad-bundle support to Adobe DPS packager
	 * This function is called by a custom hook
	 * from the packager class
	 *
	 * @param  object $post
	 * @param  string $edition_dir
	 * @void
	 */
	public function adb_adps_packager_run( $post, $edition_dir ) {

		$attachment = self::get_adb_attachment( $post->ID );
		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {
			$zip = new ZipArchive;
			$adb_attached_file = get_attached_file( $attachment->ID );
			if ( $zip->open( $adb_attached_file ) ) {
				$adb_title = PR_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir ) ) {
					PR_Packager::print_line( __( 'Unzipped file ', 'edition' ) . $adb_attached_file, 'success' );
				} else {
					PR_Packager::print_line( __( 'Failed to unzip file ', 'edition' ) . $adb_attached_file, 'error' );
				}
				$zip->close();
			}
			else {
				PR_Packager::print_line( __( 'Failed to unzip file ', 'edition') . $adb_attached_file, 'error' );
			}
		}
	}

	public function adb_web_shortcode( $post, &$src ) {

		if($post->post_type=="pr_ad_bundle") {
			$adb_index = get_post_meta( $post->ID, '_pr_html_file', true );
			$adb_dir = PR_Utils::sanitize_string( $post->post_title );
			$src = 'contents' . DS . 'pr_ad_bundle' . DS . $adb_dir . DS . $adb_index;
		}
	}

	/**
	* Add ad-bundle in book.json contents
	* This function is called by a custom hook
	* from the book_json class
	*
	* @param array $press_options
	* @param object $post
	* @param string $edition_dir
	* @void
	*/
	public function adb_packager_book( &$press_options, $post, $edition_dir ) {

		if ( $post->post_type != 'pr_ad_bundle' ) {
			return;
		}

		$adb_index = get_post_meta( $post->ID, '_pr_html_file', true );
		$adb_dir = PR_Utils::sanitize_string( $post->post_title );

		$file_index = $edition_dir . DS . 'pr_ad_bundle' . DS. $adb_dir . DS . $adb_index;
		if ( is_file( $file_index ) ) {
			$press_options['contents'][] = 'pr_ad_bundle' . DS . $adb_dir . DS . $adb_index;
			PR_Packager::print_line( sprintf( __( "Adding ADBundle %s", 'edition' ), $file_index ) );
		}
		else {
			PR_Packager::print_line( sprintf( __( "Can't find file %s. It won't add to book.json. See the wiki to know how to make an add bundle", 'edition' ), $file_index ), 'error' );
		}
	}

	/**
	* Add ad-bundle to preview
	* This function is called by a custom hook
	* from the preview class
	*
	* @param string $page_url
	* @param object $edition
	* @param object $post
	* @void
	*/
	public function adb_preview( &$page_url, $edition, $post ) {

		$attachment = self::get_adb_attachment( $post->ID );

		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$adb_attached_file = get_attached_file( $attachment->ID );
			if ( $zip->open( $adb_attached_file ) ) {

				$edition_name = PR_Utils::sanitize_string( $edition->post_title );
				$adb_name = PR_Utils::sanitize_string( $post->post_title );

				$edition_dir = PR_Utils::make_dir( PR_PREVIEW_TMP_PATH, $edition_name );
				$adb_dir = PR_Utils::make_dir( $edition_dir, $adb_name );

				if ( $zip->extractTo( $adb_dir ) ) {

					$index_file = get_post_meta( $post->ID, '_pr_html_file', true );
					if ( $index_file && file_exists( $adb_dir . DS . $index_file ) ) {
						$page_url = PR_PREVIEW_URI . $edition_name . '/' . $adb_name . '/' . $index_file;
					}
				}
				$zip->close();
			}
		}
	}

	/**
	 * Custom post parsing. Adbundle does not require to create html file.
	 *
	 * @param  object $packager
	 * @param  object $post
	 * @void
	 */
	public function adb_packager_post_parse( $packager, $post ) {
		return;
	}

	/**
	 * Customization for adbundle presslist
	 *
	 * @param  object $post
	 * @param  string $html
	 * @void
	 */
	public function adbundle_presslist( $post, &$html ) {
		$html = '';
	}

	/**
	* Get adbundle zip attachment
	*
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

$pr_adbundle = new PR_ADBundle;
