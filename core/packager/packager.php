<?php
/**
 * PressRoom packager class.
 *
 */
require_once( PR_CORE_PATH . '/packager/book_json.php' );
require_once( PR_CORE_PATH . '/packager/shelf_json.php' );
require_once( PR_CORE_PATH . '/packager/hpub_package.php' );
require_once( PR_CORE_PATH . '/packager/progressbar.php' );

class PR_Packager
{
	public static $verbose = true;
	public $pb;

	protected $_edition_post;
	protected $_edition_dir;
	protected $_edition_cover_image;

	protected $_linked_query;
	protected $_posts_attachments = array();

	public function __construct() {

		$this->_get_linked_posts();
		$this->pb = new ProgressBar();
	}

	/**
	 * Generate the edition package
	 * object $editorial_project
	 * @void
	 */
	public function run( $editorial_project ) {

		ob_start();
		if ( !PR_EDD_License::check_license() ) {
			self::print_line( __( 'Not valid or expired license. ', 'edition' ), 'error' );
			exit;
		}

		if ( is_null( $this->_edition_post ) ) {
			ob_end_flush();
			return;
		}

		if( !$this->_linked_query->posts ) {
			self::print_line( __( 'No posts linked to this edition ', 'edition' ), 'error' );
			exit;
		}

		self::print_line( sprintf( __( 'Create package for %s', 'edition' ), $editorial_project->name ), 'info' );

		// Create edition folder
		$edition_post = $this->_edition_post;
		$edition_name = $editorial_project->slug . '_' . time();
		$this->_edition_dir = PR_Utils::make_dir( PR_TMP_PATH, $edition_name );
		if ( !$this->_edition_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . PR_TMP_PATH . $edition_name, 'error' );
			$this->set_progress( 100 );
			ob_end_flush();
			return;
		}

		$edition_dir = $this->_edition_dir;
		self::print_line( __( 'Create folder ', 'edition' ) . $edition_dir, 'success' );
		$this->set_progress( 2, __( 'Loading edition theme', 'edition' ) );

		// Get associated theme
		$theme_dir = PR_Theme::get_theme_path( $edition_post->ID );
		if ( !$theme_dir ) {
			self::print_line( __( 'Failed to load edition theme', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}
		$this->set_progress( 5, __( 'Downloading assets', 'edition' ) );

		// Download all assets
		$downloaded_assets = $this->_download_assets( $theme_dir . 'assets' );
		if ( !$downloaded_assets ) {
			$this->_exit_on_error();
			return;
		}
		$this->set_progress( 10, __( 'Parsing cover', 'edition' ) );

		// Parse html of cover index.php file
		$cover = $this->_cover_parse( $editorial_project );
		if ( !$cover ) {
			self::print_line( __( 'Failed to parse cover file', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}
		$this->set_progress( 15, __( 'Rewriting cover urls', 'edition' ) );

		// Rewrite cover url
		$cover = $this->_rewrite_url( $cover );
		$this->set_progress( 20, __( 'Saving cover file', 'edition' ) );

		// Save cover html file
		if ( $this->_save_html_file( $cover, 'index' ) ) {
			self::print_line( __( 'Cover file correctly generated', 'edition' ), 'success' );
			$this->set_progress( 22, __( 'Parsing toc file', 'edition' ) );
		}
		else {
			self::print_line( __( 'Failed to save cover file', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		// Parse html of cover index.php file
		$toc = $this->_toc_parse( $editorial_project );
		if ( !$toc ) {
			self::print_line( __( 'Failed to parse toc file', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		// Rewrite cover url
		$toc = $this->_rewrite_url( $toc );
		$this->set_progress( 28, __( 'Saving toc file', 'edition' ) );

		// Save cover html file
		if ( $this->_save_html_file( $toc, 'toc' ) ) {
			self::print_line( __( 'Toc file correctly generated', 'edition' ), 'success' );
			$this->set_progress( 30, __( 'Saving edition posts', 'edition' ) );
		}
		else {
			self::print_line( __( 'Failed to save toc file', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		$total_progress = 40;
		$progress_step = round( $total_progress / count( $this->_linked_query->posts ) );
		foreach ( $this->_linked_query->posts as $k => $post ) {
			// Parse post content
			$parsed_post = $this->_post_parse( $post, $editorial_project );
			if ( !$parsed_post ) {
				self::print_line( sprintf( __( 'You have to select a template for %s', 'edition' ), $post->post_title ), 'error' );
				continue;
			}

			// Rewrite post url
			$parsed_post = $this->_rewrite_url( $parsed_post );
			if ( !has_action( 'pr_packager_run_' . $post->post_type ) ) {
				if ( !$this->_save_html_file( $parsed_post, $post->post_title ) ) {
					self::print_line( __( 'Failed to save post file: ', 'edition' ) . $post->post_title, 'error' );
					continue;
				}
			}
			else {
				do_action( 'pr_packager_run_' . $post->post_type, $post, $edition_dir );
			}

			self::print_line(__('Adding ', 'edition') . $post->post_title);
			$this->set_progress( $total_progress + $k * $progress_step );
		}

		$media_dir = PR_Utils::make_dir( $edition_dir, PR_EDITION_MEDIA );
		if ( !$media_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . $edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA, 'error' );
			$this->_exit_on_error();
			return;
		}
		$this->set_progress( 70, __( 'Saving edition attachments files', 'edition' ) );

		$this->_save_posts_attachments( $media_dir );
		$this->set_progress( 78, __( 'Saving edition cover image', 'edition' ) );

		$this->_save_cover_image();
		$this->set_progress( 80, __( 'Generating book json', 'edition' ) );

		$this->_set_package_date();

		if ( PR_Packager_Book_JSON::generate_book( $edition_post, $this->_linked_query, $edition_dir, $this->_edition_cover_image, $editorial_project->term_id ) ) {
			self::print_line( __( 'Created book.json ', 'edition' ), 'success' );
			$this->set_progress( 85, __( 'Generating hpub package', 'edition' ) );
		}
		else {
			self::print_line( __( 'Failed to generate book.json ', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		$hpub_package = PR_Packager_HPUB_Package::build( $edition_post->ID, $editorial_project, $edition_dir );
		if ( $hpub_package ) {
			self::print_line( __( 'Generated hpub ', 'edition' ) . $hpub_package, 'success' );
			$this->set_progress( 90, __( 'Generating shelf json', 'edition' ) );
		} else {
			self::print_line( __( 'Failed to create hpub package ', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		if ( PR_Packager_Shelf_JSON::generate_shelf( $editorial_project ) ) {
			self::print_line( __( 'Generated shelf.json for editorial project: ', 'edition' ) . $editorial_project->name, 'success' );
			$this->set_progress( 95, __( 'Cleaning temporary files', 'edition' ) );
		}
		else {
			self::print_line( __( 'Failed to generate shelf.json ', 'edition' ), 'error' );
			$this->_exit_on_error();
			return;
		}

		$this->_clean_temp_dir();
		$this->set_progress( 100, __( 'Successfully created package', 'edition' ) );
		self::print_line(__('Done', 'edition'), 'success');
		ob_end_flush();
	}

	/**
	* Set progressbar percentage
	* @param int $percentage
	* @void
	*/
	public function set_progress( $percentage, $text = '' ) {

		$this->pb->setProgressBarProgress( $percentage, $text );
		usleep(1000000*0.1);
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
	 * Stop packager procedure and clear temp folder
	 * @void
	 */
	protected function _packager_exit_on_error() {

		$this->_clean_temp_dir();
		$this->set_progress( 100, __( 'Errore creating package', 'edition' ) );
		ob_end_flush();
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

		$this->_edition_post = get_post( $_GET['edition_id'] );

		$this->_linked_query = PR_Edition::get_linked_posts( $_GET['edition_id'], array(
			'connected_meta' => array(
				array(
					'key'		=> 'status',
					'value'	=> 1,
					'type'	=> 'numeric'
				)
			)
		) );
	}

	/**
	 * Download assets into package folder
	 * @param  stirng $theme_assets_dir
	 * @return boolean
	 */
	protected function _download_assets( $theme_assets_dir ) {

		$edition_assets_dir = PR_Utils::make_dir( $this->_edition_dir, 'assets' );
		if ( !$edition_assets_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . PR_TMP_PATH . DIRECTORY_SEPARATOR . 'assets', 'error');
			return false;
		}

		self::print_line( __( 'Created folder ', 'edition' ) . $edition_assets_dir, 'success' );

		if ( !is_dir( $theme_assets_dir ) ) {
			self::print_line( __( 'Error: Can\'t read assets folder ', 'edition' ) . $theme_assets_dir, 'error' );
			return false;
		}

		$copied_files = PR_Utils::recursive_copy( $theme_assets_dir, $edition_assets_dir );
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
	protected function _cover_parse( $editorial_project ) {

		$cover = PR_Theme::get_theme_cover( $this->_edition_post->ID );
		if ( !$cover ) {
			return false;
		}

		ob_start();
		$edition = $this->_edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->_edition_post->ID );

		$posts = $this->_linked_query;
		$this->_add_functions_file();
		require( $cover );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	* Parse toc file
	*
	* @return string or boolean false
	*/
	protected function _toc_parse( $editorial_project ) {

    $toc = PR_Theme::get_theme_toc( $this->_edition_post->ID );
    if ( !$toc ) {
      return false;
    }

		ob_start();
		$edition = $this->_edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->_edition_post->ID );

		$posts = $this->_linked_query;
		$this->_add_functions_file();
		require( $toc );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Parsing html file
	 * @param  object $post
	 * @return string
	 */
	protected function _post_parse( $linked_post, $editorial_project ) {

		$page = PR_Theme::get_theme_page( $this->_edition_post->ID, $linked_post->p2p_id );
		if ( !$page || !file_exists( $page )  ) {
			return false;
		}

		ob_start();
		$edition = $this->_edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->_edition_post->ID );
		
		global $post;
		$post = $linked_post;
		setup_postdata($post);
		$this->_add_functions_file();
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
			$urls = PR_Utils::extract_urls( $html );

			foreach ( $urls as $url ) {

				if ( strpos( $url, site_url() ) !== false ) {
					$post_id = url_to_postid( $url );
					if ( $post_id ) {

						foreach( $this->_linked_query->posts as $post ) {

							if ( $post->ID == $post_id ) {
								$path = PR_Utils::sanitize_string( $post->post_title ) . '.' . $extension;
								$post_rewrite_urls[$url] = $path;
							}
						}
					}
					else {

						$attachment_id = $this->_get_attachment_from_url( $url );
						if ( $attachment_id ) {
							$info = pathinfo( $url );
							$filename = $info['basename'];
							$post_rewrite_urls[$url] = PR_EDITION_MEDIA . $filename;

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

		return file_put_contents( $this->_edition_dir . DIRECTORY_SEPARATOR . PR_Utils::sanitize_string( $filename ) . '.html', $post);
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

			if ( copy( $edition_cover_path, $this->_edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA . $info['basename'] ) ) {
				$this->_edition_cover_image = $info['basename'];
				self::print_line( sprintf( __( 'Copied cover image %s ', 'edition' ), $edition_cover_path ), 'success' );
			}
			else {
				self::print_line( sprintf( __( 'Can\'t copy cover image %s ', 'edition' ), $edition_cover_path ), 'error' );
			}
		}
	}

	/**
	 * Clean the temporary files folder
	 *
	 * @void
	 */
	protected function _clean_temp_dir() {

		self::print_line(__('Cleaning temporary files ', 'edition') );
		PR_Utils::remove_dir( $this->_edition_dir );
	}

	/**
	 * Add package meta data to edition
	 *
	 * @void
	 */
	protected function _set_package_date() {

		$date = date( 'Y-m-d H:i:s' );
		add_post_meta( $this->_edition_post->ID, '_pr_package_date', $date, true );
		update_post_meta( $this->_edition_post->ID, '_pr_package_updated_date', $date );
	}

	/**
	 * Add function file if exist
	 *
	 * @void
	 */
	protected function _add_functions_file() {

		$theme_dir = PR_Theme::get_theme_path( $this->_edition_post->ID );
		$files = PR_Utils::search_files( $theme_dir, 'php', true );
		if ( !empty( $files ) ) {
			foreach ( $files as $file ) {
				if ( strpos( $file, 'functions.php' ) !== false ) {
					require_once $file;
					break;
				}
			}
		}
	}
}
