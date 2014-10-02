<?php
/**
* TPL packager class.
*
*/
require_once( TPL_CLASSES_PATH . '/packager/book_json.php' );
require_once( TPL_CLASSES_PATH . '/packager/shelf_json.php' );
require_once( TPL_CLASSES_PATH . '/packager/hpub_package.php' );

class TPL_Packager
{
	public static $verbose = true;

	protected $_edition_post;
	protected $_edition_dir;
	protected $_edition_cover_image;

	protected $_linked_query;
	protected $_posts_attachments = array();

	public function __construct() {

		$this->_get_linked_posts();
	}

	/**
	 * Generate the edition package
	 *
	 * @void
	 */
	public function run() {

		ob_start();

		if ( is_null( $this->_edition_post ) ) {
			ob_end_flush();
			return;
		}

		$editorial_terms = wp_get_post_terms( $this->_edition_post->ID, TPL_EDITORIAL_PROJECT );
		if ( empty( $editorial_terms ) ) {
			self::print_line( __( 'You must assign the edition to an editorial project ', 'edition' ), 'error' );
			ob_end_flush();
			return;
		}

		// Create edition folder
		$edition_dir = TPL_Utils::make_dir( TPL_TMP_DIR, $this->_edition_post->post_title );
		if ( !$edition_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . TPL_TMP_DIR . TPL_Utils::parse_string( $this->_edition_post->post_title ), 'error' );
			ob_end_flush();
			return;
		}

		$this->_edition_dir = $edition_dir;
		self::print_line( __( 'Create folder ', 'edition' ) . $edition_dir, 'success' );

		// Get associated theme
		$theme_dir = TPL_Theme::get_theme_path( $this->_edition_post->ID );
		if ( !$theme_dir ) {
			self::print_line( __( 'Failed to load edition theme', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		// Download all assets
		$downloaded_assets = $this->_download_assets( $theme_dir . 'assets' );
		if ( !$downloaded_assets ) {
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		// Parse html of cover index.php file
		$cover = $this->_cover_parse();
		if ( !$cover ) {
			self::print_line( __( 'Failed to parse cover file', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		// Rewrite cover url
		$cover = $this->_rewrite_url($cover);
		// Save cover html file
		if ( !$this->_save_html_file( $cover, 'index' ) ) {
			self::print_line( __( 'Failed to save cover file', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		self::print_line( __( 'Cover file correctly generated', 'edition' ), 'success' );

		foreach ( $this->_linked_query->posts as $post ) {
			// Parse post content
			$parsed_post = $this->_post_parse( $post );
			if ( !$parsed_post ) {
				self::print_line( sprintf( __( 'You have to select a template for %s', 'edition' ), $post->post_title ), 'error' );
				continue;
			}
			// Rewrite post url
			$parsed_post = $this->_rewrite_url( $parsed_post );

			if ( $post->post_type == 'post' || !has_action( 'packager_run_' . $post->post_type ) ) {
				if ( !$this->_save_html_file( $parsed_post, $post->post_title ) ) {
					self::print_line( __( 'Failed to save post file: ', 'edition' ) . $post->post_title, 'error' );
					continue;
				}
			}
			else {
				do_action( 'packager_run_' . $post->post_type, $post, $this->_edition_dir );
			}

			self::print_line(__('Adding ', 'edition') . $post->post_title);
		}

		$media_dir = TPL_Utils::make_dir( $edition_dir, TPL_EDITION_MEDIA );
		if ( !$media_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . $edition_dir . DIRECTORY_SEPARATOR . TPL_EDITION_MEDIA, 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		$this->_save_posts_attachments( $media_dir );

		$this->_save_cover_image();

		if ( !TPL_Packager_Book_JSON::generate_book( $this->_edition_post, $this->_linked_query, $this->_edition_dir, $this->_edition_cover_image ) ) {
			self::print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		self::print_line( __( 'Created book.json ', 'edition' ), 'success' );

		$hpub_package = TPL_Packager_HPUB_Package::build( $this->_edition_post, $this->_edition_dir );
		if ( $hpub_package ) {
			self::print_line( __( 'Generated hpub ', 'edition' ) . $hpub_package, 'success' );
		} else {
			self::print_line( __( 'Failed to create hpub package ', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		if ( !TPL_Packager_Shelf_JSON::generate_shelf( $this->_edition_post ) ) {
			self::print_line( __( 'Failed to generate shelf.json ', 'edition' ), 'error' );
			$this->_clean_temp_dir();
			ob_end_flush();
			return;
		}

		self::print_line( __( 'Created shelf.json ', 'edition' ), 'success' );

		$this->_clean_temp_dir();

		self::print_line(__('Done', 'edition'), 'success');

		ob_end_flush();
	}

	/**
	 * Print live output
	 * @param string $output
	 * @param string $class
	 * @echo
 	 */
	public static function print_line( $output, $class = 'info' ) {

		if ( self::$verbose ) {
			echo '<p class="liveoutput ' . $class . '"><span class="label">' . $class . '</span> ' . $output . '</p>';
			ob_flush();
			flush();
		}
	}

	/**
	 * Save json string to file
	 * @param  array $content
 	 * @param  string $filename
	 * @param  string $path
	 * @return boolean
	 */
	public static function save_json_file( $content, $filename, $path ) {

		$encoded = json_encode( $content );
		$json_file = $path . DIRECTORY_SEPARATOR . $filename;
		if ( file_put_contents( $json_file, $encoded ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Select all posts in p2p connection with the current edition
	 *
	 * @void
	 */
	protected function _get_linked_posts() {

		if ( !isset( $_GET['edition_id'] ) ) {
			return array();
		}

		$this->_edition_post = get_post( (int)$_GET['edition_id'] );

		$args = array(
			'connected_type'			=> 'edition_post',
			'connected_items' 		=> $this->_edition_post,
			'nopaging'					=> true,
			'connected_orderby' 		=> 'order',
			'connected_order' 		=> 'asc',
			'connected_order_num' 	=> true,
			'connected_meta'			=> array(
				array(
					'key'		=> 'state',
					'value'	=> 1,
					'type'	=> 'numeric'
				)
			)
		);

		$linked_query = new WP_Query($args);
		$this->_linked_query = $linked_query;
	}

	/**
	 * Download assets into package folder
	 * @param  stirng $theme_assets_dir
	 * @return boolean
	 */
	protected function _download_assets( $theme_assets_dir ) {

		$edition_assets_dir = TPL_Utils::make_dir( $this->_edition_dir, 'assets' );
		if ( !$edition_assets_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . TPL_TMP_DIR . DIRECTORY_SEPARATOR . 'assets', 'error');
			return false;
		}

		self::print_line( __( 'Created folder ', 'edition' ) . $edition_assets_dir, 'success' );

		if ( !is_dir( $theme_assets_dir ) ) {
			self::print_line( __( 'Error: Can\'t read assets folder ', 'edition' ) . $theme_assets_dir, 'error' );
			return false;
		}

		$copied_files = TPL_Utils::recursive_copy( $theme_assets_dir, $edition_assets_dir );
		if ( is_array( $copied_files ) ) {
			foreach ( $copied_files as $file ) {
				self::print_line( sprintf( __( 'Error: Can\'t copy file %s ', 'edition' ), $file ), 'error' );
			}
			return false;
		}
		else {
			self::print_line( sprintf( __( 'Copy assets folder with %s files ', 'edition' ), $copied_files ), 'success' );
		}

		return true;
	}

	/**
	 * Parse cover file
	 *
	 * @return string or boolean false
	 */
	protected function _cover_parse() {

		$cover = TPL_Theme::get_theme_cover( $this->_edition_post->ID );
		if ( !$cover ) {
			return false;
		}

		ob_start();
		$posts = $this->_linked_query;
		require_once($cover);
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Parsing html file
	 * @param  object $post
	 * @return string
	 */
	protected function _post_parse( $linked_post ) {

		$page = TPL_Theme::get_theme_page( $this->_edition_post->ID, $linked_post->p2p_id );
		if ( !$page ) {
			return false;
		}

		ob_start();
		global $post;
		$post = $linked_post;
		setup_postdata($post);
		require( $page );
		$output = ob_get_contents();
		wp_reset_postdata();
		ob_end_clean();

		return $output;
	}

	/**
	 * Get all url from the html string and replace with internal url of the package
	 * @param  string $html
	 * @param  string $ext  = 'html' extension file output
	 * @return string or false
	 */
	protected function _rewrite_url( $html, $extension = 'html' ) {

		if ( $html ) {

			$post_rewrite_urls = array();
			$urls = TPL_Utils::get_urls( $html );

			foreach ( $urls as $url ) {

				if ( strpos( $url, site_url() ) !== false ) {
					$post_id = url_to_postid( $url );
					if ( $post_id ) {

						foreach( $this->_linked_query->posts as $post ) {

							if ( $post->ID == $post_id ) {
								$path = TPL_Utils::parse_string( $post->post_title ) . '.' . $extension;
								$post_rewrite_urls[$url] = $path;
							}
						}
					}
					else {

						$attachment_id = $this->_get_attachment_from_url( $url );
						if ( $attachment_id ) {
							$info = pathinfo( $url );
							$filename = $info['basename'];
							$post_rewrite_urls[$url] = TPL_EDITION_MEDIA . $filename;

							// Add attachments that will be downloaded
							$this->_posts_attachments[$filename] = $url;
						}
					}
				}
			}

			if ( !empty( $post_rewrite_urls ) ) {
				$html = str_replace( array_keys( $post_rewrite_urls ), $post_rewrite_urls, $html );
			}
		}

		return $html;
	}

	/**
	 * Save the html output into file
	 * @param  string $post
	 * @param  boolean
	 */
	protected function _save_html_file( $post, $filename ) {

		return file_put_contents( $this->_edition_dir . DIRECTORY_SEPARATOR . TPL_Utils::parse_string( $filename ) . '.html', $post);
	}

	/**
	 * Copy attachments into the package folder
	 * @param  array $attachments
	 * @param  string $media_dir path of the package folder
	 * @void
	 */
	protected function _save_posts_attachments( $media_dir ) {

		if ( !empty( $this->_posts_attachments ) ) {
			$attachments = array_unique( $this->_posts_attachments );
			foreach ( $attachments as $filename => $url ) {

				if ( copy( $url, $media_dir . DIRECTORY_SEPARATOR . $filename ) ) {
					self::print_line( __( 'Copied ', 'edition' ) . $url, 'success' );
				}
				else {
					self::print_line(__('Failed to copy ', 'edition') . $url, 'error' );
				}
			}
		}
	}

	/**
	 * Get attachment ID by url
	 * @param string $attachment_url
	 * @return string or boolean false
	 */
	protected function _get_attachment_from_url( $attachment_url ) {

		global $wpdb;
		$attachment_url = preg_replace( '/-[0-9]{1,4}x[0-9]{1,4}\.(jpg|jpeg|png|gif|bmp)$/i', '.$1', $attachment_url );
		$attachment = $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM $wpdb->posts WHERE guid RLIKE '%s' LIMIT 1;", $attachment_url ) );
		if ( $attachment ) {
			return $attachment[0];
		}
		else {
			return false;
		}
	}

	/**
	 * Save cover image into edition package
	 * @void
	 */
	protected function _save_cover_image() {

		$edition_cover_id = get_post_thumbnail_id( $this->_edition_post->ID );
		if ( $edition_cover_id ) {

			$upload_dir = wp_upload_dir();
			$edition_cover_metadata = wp_get_attachment_metadata( $edition_cover_id );
			$edition_cover_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $edition_cover_metadata['file'];
			$info = pathinfo( $edition_cover_path );

			if ( copy( $edition_cover_path, $this->_edition_dir . DIRECTORY_SEPARATOR . TPL_EDITION_MEDIA . $info['basename'] ) ) {
				$this->_edition_cover_image = $info['basename'];
				self::print_line( sprintf( __( 'Copied cover image %s ', 'edition' ), $edition_cover_path ), 'success' );
			}
			else {
				self::print_line( sprintf( __( 'Can\'t copy cover image %s ', 'edition' ), $edition_cover_path ), 'error' );
			}
		}

		/*
		$edition_cover = get_post_custom_values( '_tpl_cover', $this->_edition_post->ID );
		if ( $edition_cover && !empty( $edition_cover ) ) {
			$path = get_attached_file( $edition_cover[0] );
			$info = pathinfo($path);
			if ( copy( $path, $this->_edition_dir . DIRECTORY_SEPARATOR . TPL_EDITION_MEDIA . $info['basename'] ) ) {
				$this->_edition_cover_image = $info['basename'];
				self::print_line( sprintf( __( 'Copied cover image %s ', 'edition' ), $path ), 'success' );
			}
			else {
				self::print_line( sprintf( __( 'Can\'t copy cover image %s ', 'edition' ), $path ), 'error' );
			}
		}
		*/
	}

	/**
	 * Clean the temporary files folder
	 *
	 * @void
	 */
	protected function _clean_temp_dir() {

		self::print_line(__('Cleaning temporary files ', 'edition') );
		TPL_Utils::remove_dir( $this->_edition_dir );
	}
}
