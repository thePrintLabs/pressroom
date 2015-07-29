<?php

class PR_Custom_Html
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

		add_action( 'press_flush_rules', array( $this, 'add_custom_html_post_type' ), 10 );
		add_action( 'init', array( $this, 'add_custom_html_post_type' ), 20 );
		add_action( 'post_edit_form_tag', array( $this, 'form_add_enctype' ) );
		add_action( 'save_post_pr_custom_html', array( $this, 'save_custom_html' ), 40 );
		add_action( 'admin_menu', array( $this, 'remove_default_metaboxes' ) );
		add_filter( 'add_meta_boxes', array( $this, 'add_custom_html_metaboxes' ), 40, 2 );

		add_action( 'pr_packager_run_hpub_pr_custom_html', array( $this, 'chtml_packager_run' ), 10, 2 );
		add_action( 'pr_packager_run_web_pr_custom_html', array( $this, 'chtml_packager_run' ), 10, 2 );
		add_action( 'pr_packager_run_adps_pr_custom_html', array( $this, 'chtml_adps_packager_run' ), 10, 2 );

		add_action( 'pr_packager_shortcode_web_pr_custom_html', array( $this, 'chtml_web_shortcode' ), 10, 2 );
		add_action( 'pr_packager_generate_book', array( $this, 'chtml_packager_book' ), 10, 3 );
		add_action( 'pr_packager_parse_pr_custom_html', array( $this, 'chtml_packager_post_parse' ), 10, 2 );

		add_action( 'pr_preview_pr_custom_html', array( $this, 'chtml_preview' ), 10, 3 );
		add_action( 'pr_presslist_pr_custom_html', array( $this, 'chtml_presslist' ), 10, 3 );

	}

	/**
	* Add custom post type custom html to worpress
	*
	* @void
	*/
	public function add_custom_html_post_type() {

		$labels = array(
			'name'                => _x( 'Custom Html', 'Custom Html General Name', 'pr_custom_html' ),
			'singular_name'       => _x( 'Custom Html', 'Custom Html Singular Name', 'pr_custom_html' ),
			'menu_name'           => __( 'Custom Html', 'pr_custom_html' ),
			'parent_item_colon'   => __( 'Parent Custom Html:', 'pr_custom_html' ),
			'all_items'           => __( 'All custom html ', 'pr_custom_html' ),
			'view_item'           => __( 'View Custom Html', 'pr_custom_html' ),
			'add_new_item'        => __( 'Add New Custom Html', 'pr_custom_html' ),
			'add_new'             => __( 'Add New', 'pr_custom_html' ),
			'edit_item'           => __( 'Edit Custom Html', 'pr_custom_html' ),
			'update_item'         => __( 'Update Custom Html', 'pr_custom_html' ),
			'search_items'        => __( 'Search Custom Html', 'pr_custom_html' ),
			'not_found'           => __( 'Not found', 'pr_custom_html' ),
			'not_found_in_trash'  => __( 'Not found in Trash', 'pr_custom_html' ),
		);

		$args = array(
			'label'               => __( 'CHtml_package_type', 'pr_custom_html' ),
			'description'         => __( 'Pressroom Custom Html', 'pr_custom_html' ),
			'labels'              => $labels,
			'supports'            => array( 'title', 'author', 'thumbnail', 'custom-fields' ),
			'hierarchical'        => false,
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'show_in_nav_menus'   => true,
			'show_in_admin_bar'   => true,
			'menu_position'       => 5,
			'menu_icon'           => 'dashicons-media-code',
			'can_export'          => true,
			'has_archive'         => true,
			'exclude_from_search' => false,
			'publicly_queryable'  => true,
			'capability_type'     => 'post',
		);

		register_post_type( 'pr_custom_html', $args );
	}

	/**
	* Get custom html metaboxes configuration
	*
	* @void
	*/
	public function get_custom_metaboxes( $post_type, $post ) {

		$chtml_meta = new PR_Metabox( 'chtml_metabox', __( 'Custom Html Settings', 'pr_custom_html' ), 'normal', 'high', $post->ID );
		$chtml_meta->add_field( '_pr_html_file', __( 'Html file', 'pr_custom_html' ), __( 'The HTML file from within the ZIP that will be used in the issue.', 'pr_custom_html' ), 'text', 'index.html' );
		$chtml_meta->add_field( '_pr_zip', __( 'Zip File', 'edition' ), __( 'Upload zip file', 'edition' ), 'file', '', array( 'allow' => array( 'url', 'attachment' ) ) );

		// Add metabox to metaboxes array
		array_push( $this->_metaboxes, $chtml_meta );
	}

	/**
	* Define the metabox and field configurations.
	*
	* @void
	*/
	public function add_custom_html_metaboxes( $post_type, $post ) {

		$this->get_custom_metaboxes( $post_type, $post );
		foreach ( $this->_metaboxes as $metabox ) {
			add_meta_box( $metabox->id, $metabox->title, array( $this, 'add_chtml_metabox_callback' ), 'pr_custom_html', $metabox->context, $metabox->priority );
		}
	}

	/**
	 * Remove default post metaboxes
	 * @void
	 */
	public function remove_default_metaboxes() {
		remove_meta_box( 'authordiv', 'pr_custom_html', 'normal' ); // Author Metabox
		remove_meta_box( 'commentstatusdiv', 'pr_custom_html', 'normal' ); // Comments Status Metabox
		remove_meta_box( 'commentsdiv', 'pr_custom_html', 'normal' ); // Comments Metabox
		remove_meta_box( 'postcustom', 'pr_custom_html', 'normal' ); // Custom Fields Metabox
		remove_meta_box( 'postexcerpt', 'pr_custom_html', 'normal' ); // Excerpt Metabox
		remove_meta_box( 'revisionsdiv', 'pr_custom_html', 'normal' ); // Revisions Metabox
		remove_meta_box( 'slugdiv', 'pr_custom_html', 'normal' ); // Slug Metabox
		remove_meta_box( 'trackbacksdiv', 'pr_custom_html', 'normal' ); // Trackback Metabox
		remove_meta_box( 'categorydiv', 'pr_custom_html', 'normal' ); // Categories Metabox
		remove_meta_box( 'formatdiv', 'pr_custom_html', 'normal' ); // Formats Metabox
	}

	/**
	* Custom metabox callback
	* print html input field
	*
	* @echo
	*/
	public function add_chtml_metabox_callback() {

		echo '<input type="hidden" name="pr_chtml_nonce" value="' . wp_create_nonce('pr_chtml_nonce'). '" />';
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
	public function save_custom_html( $post_id ) {

		$post = get_post( $post_id );
		if ( !$post || $post->post_type != 'pr_custom_html' ) {
			return;
		}

		//Verify nonce
		if ( !isset( $_POST['pr_chtml_nonce'] ) || !wp_verify_nonce( $_POST['pr_chtml_nonce'], 'pr_chtml_nonce' ) ) {
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

		$this->get_custom_metaboxes( 'pr_custom_html', $post);
		foreach ( $this->_metaboxes as $metabox ) {
			$metabox->save_values();
		}
	}

	/**
	* Add Custom html support to packager
	* This function is called by a custom hook
	* from the packager class
	*
	* @param  object $post
	* @param  string $edition_dir
	* @void
	*/
	public function chtml_packager_run( $post, $edition_dir ) {

		$attachment = self::get_chtml_attachment( $post->ID );
		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$attached_file = get_attached_file( $attachment->ID );

			if ( $zip->open( $attached_file ) ) {

				$title = PR_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir . DS . 'pr_custom_html' . DS . $title ) ) {
					PR_Packager::print_line( __( 'Unzipped file ', 'edition' ) . $attached_file, 'success' );
				} else {
					PR_Packager::print_line( __( 'Failed to unzip file ', 'edition' ) . $attached_file, 'error' );
				}
				$zip->close();

			}
			else {
				PR_Packager::print_line( __( 'Failed to unzip file ', 'edition') . $attached_file, 'error' );
			}
		}
	}

	/**
	* Add Custom html support to adps packager
	* This function is called by a custom hook
	* from the packager class
	*
	* @param  object $post
	* @param  string $edition_dir
	* @void
	*/
	public function chtml_adps_packager_run( $post, $edition_dir ) {

		$attachment = self::get_chtml_attachment( $post->ID );
		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$attached_file = get_attached_file( $attachment->ID );

			if ( $zip->open( $attached_file ) ) {

				$title = PR_Utils::sanitize_string( $post->post_title );
				if ( $zip->extractTo( $edition_dir ) ) {
					PR_Packager::print_line( __( 'Unzipped file ', 'edition' ) . $attached_file, 'success' );
				} else {
					PR_Packager::print_line( __( 'Failed to unzip file ', 'edition' ) . $attached_file, 'error' );
				}
				$zip->close();

			}
			else {
				PR_Packager::print_line( __( 'Failed to unzip file ', 'edition') . $attached_file, 'error' );
			}
		}
	}

	public function chtml_web_shortcode( $post, &$src ) {

		if($post->post_type=="pr_custom_html") {
			$index = get_post_meta( $post->ID, '_pr_html_file', true );
			$dir = PR_Utils::sanitize_string( $post->post_title );
			$src = 'contents' . DS . 'pr_custom_html' . DS . $dir . DS . $index;
		}
	}

	/**
	* Add custom html in book.json contents
	* This function is called by a custom hook
	* from the book_json class
	*
	* @param array $press_options
	* @param object $post
	* @param string $edition_dir
	* @void
	*/
	public function chtml_packager_book( &$press_options, $post, $edition_dir ) {

		if ( $post->post_type != 'pr_custom_html' ) {
			return;
		}

		$index = get_post_meta( $post->ID, '_pr_html_file', true );
		$dir = PR_Utils::sanitize_string( $post->post_title );

		$file_index = $edition_dir . DS . 'pr_custom_html' . DS. $dir . DS . $index;
		if ( is_file( $file_index ) ) {
			$press_options['contents'][] = 'pr_custom_html' . DS . $dir . DS . $index;
			PR_Packager::print_line( sprintf( __( "Adding Chtml %s", 'edition' ), $file_index ) );
		}
		else {
			PR_Packager::print_line( sprintf( __( "Can't find file %s. It won't add to book.json. See the wiki to know how to make an custom html post", 'edition' ), $file_index ), 'error' );
		}
	}

	/**
	* Add custom html to preview
	* This function is called by a custom hook
	* from the preview class
	*
	* @param string $page_url
	* @param object $edition
	* @param object $post
	* @void
	*/
	public function chtml_preview( &$page_url, $edition, $post ) {

		$attachment = self::get_chtml_attachment( $post->ID );

		if ( $attachment && $attachment->post_mime_type == 'application/zip' ) {

			$zip = new ZipArchive;
			$attached_file = get_attached_file( $attachment->ID );
			if ( $zip->open( $attached_file ) ) {

				$edition_name = PR_Utils::sanitize_string( $edition->post_title );
				$name = PR_Utils::sanitize_string( $post->post_title );

				$edition_dir = PR_Utils::make_dir( PR_PREVIEW_TMP_PATH, $edition_name );
				$dir = PR_Utils::make_dir( $edition_dir, $name );

				if ( $zip->extractTo( $dir ) ) {

					$index_file = get_post_meta( $post->ID, '_pr_html_file', true );
					if ( $index_file && file_exists( $dir . DS . $index_file ) ) {
						$page_url = PR_PREVIEW_URI . $edition_name . '/' . $name . '/' . $index_file;
					}
				}
				$zip->close();
			}
		}
	}

	/**
	* Custom post parsing. Custom html does not require to create html file.
	*
	* @param  object $packager
	* @param  object $post
	* @void
	*/
	public function chtml_packager_post_parse() {

	}

	/**
	* Customization for custom html presslist
	*
	* @param  object $post
	* @param  string $html
	* @void
	*/
	public function chtml_presslist( $post, &$html ) {
		$html = '';
	}

	/**
	* Get custom html zip attachment
	*
	* @param  int $chtml_id
	* @return object or boolean false
	*/
	public static function get_chtml_attachment( $chtml_id ) {

		$attachment_id = get_post_meta( $chtml_id, '_pr_zip', true );
		if ( $attachment_id ) {
			$attachment = get_post($attachment_id);
			if ( $attachment ) {
				return $attachment;
			}
		}

		return false;
	}
}

$pr_custom_html = new PR_Custom_Html;
