<?php

/**
 * PressRoom packager class.
 *
 */

require_once PR_PACKAGER_PATH . 'progressbar.php';
header('Content-Encoding: none;');

class PR_Packager
{
	public static $verbose = true;
	public static $log_output;
	public $pb;
	public $linked_query;
	public $edition_dir;
	public $edition_cover_image;
	public $edition_post;
	public $package_type;
	public $log_id;

	protected $_posts_attachments = array();

	public function __construct() {
		$this->_get_linked_posts();
		$this->pb = new ProgressBar();
	}

	/**
	 * Generate the edition package
	 *
	 * object $editorial_project
	 * @void
	 */
	public function run( $editorial_project ) {

		$current_user = wp_get_current_user();

		$log = array(
			'action'		=>	'package',
			'object_id'	=>	$_GET['edition_id'],
			'ip'				=>	PR_Utils::get_ip_address(),
			'author'		=>	$current_user->ID,
			'type'			=>	$_GET['packager_type'],
		);

		$this->log_id = PR_Logs::insert_log( $log );

		ob_start();

		if( !isset( $_GET['packager_type'] ) ) {
			self::print_line( __( 'No package type selected. ', 'edition' ), 'error' );
			exit;
		}

		if( $_GET['packager_type'] != 'webcore' ) {
			$options = get_option( 'pr_settings' );
			$exporter = isset( $options['pr_enabled_exporters'][$_GET['packager_type']]) ? $options['pr_enabled_exporters'][$_GET['packager_type']] : false ;
			if( !$exporter || !PR_EDD_License::check_license( $exporter['itemid'], $exporter['name'] ) ) {
				$setting_page_url = admin_url() . 'admin.php?page=pressroom-addons';
				self::print_line( sprintf( __('Exporter %s not enabled. Please enable it from <a href="%s">Pressroom add-ons page</a>', 'edition'), $_GET['packager_type'], $setting_page_url ), 'error' );
				$this->exit_on_error();
				return;
			}
		}


		$this->package_type = $_GET['packager_type'];

		if ( is_null( $this->edition_post ) ) {
			ob_end_flush();
			return;
		}

		if( !$this->linked_query->posts ) {
			self::print_line( __( 'No posts linked to this edition ', 'edition' ), 'error' );
			$this->exit_on_error();
			return;
		}

		self::print_line( sprintf( __( 'Create package for %s', 'edition' ), $editorial_project->name ), 'success' );

		// Create edition folder
		$edition_post = $this->edition_post;
		$edition_name = $editorial_project->slug . '_' . time();
		$this->edition_dir = PR_Utils::make_dir( PR_TMP_PATH, $edition_name );
		if ( !$this->edition_dir ) {
			self::print_line( sprintf( __( 'Failed to create folder %s ', 'edition' ), PR_TMP_PATH . $edition_name ), 'error' );
			$this->set_progress( 100 );
			ob_end_flush();
			return;
		}

		self::print_line( sprintf( __( 'Create folder %s ', 'edition' ), $this->edition_dir ), 'success' );
		$this->set_progress( 5, __( 'Loading edition theme', 'edition' ) );

		// check if theme is active
		$theme_id = get_post_meta( $edition_post->ID, '_pr_theme_select', true );
		$themes = get_option( 'pressroom_themes' );
		$themes_page_url = admin_url() . 'admin.php?page=pressroom-themes';
		if( !isset( $themes[$theme_id]['active'] ) || !$themes[$theme_id]['active'] ) {
			self::print_line( sprintf( __('Theme %s not enabled. Please enable it from <a href="%s">Pressroom themes page</a>', 'edition'), $theme_id, $themes_page_url ), 'error' );
			$this->exit_on_error();
			return;
		}

		// Get associated theme
		$theme_assets_dir = PR_Theme::get_theme_assets_path( $edition_post->ID );
		if ( !$theme_assets_dir ) {
			self::print_line( __( 'Failed to load edition theme', 'edition' ), 'error' );
			$this->exit_on_error();
			return;
		}

		do_action( "pr_packager_{$this->package_type}_start", $this, $editorial_project );

		$this->set_progress( 30, __( 'Downloading assets', 'edition' ) );

		// Download all assets
		$downloaded_assets = $this->_download_assets( $theme_assets_dir );
		if ( !$downloaded_assets ) {
			$this->exit_on_error();
			return;
		}

		$total_progress = 40;
		$progress_step = round( $total_progress / count( $this->linked_query->posts ) );
		foreach ( $this->linked_query->posts as $k => $post ) {
			if( has_action( "pr_packager_parse_{$post->post_type}" ) ) {
				do_action_ref_array( "pr_packager_parse_{$post->post_type}", array( $this, $post ) );
				$parsed_post = false;
			}
			else {
				$parsed_post = $this->_parse_post( $post, $editorial_project );
				if ( !$parsed_post ) {
					self::print_line( sprintf( __( 'You have to select a layout for %s', 'edition' ), $post->post_title ), 'error' );
					continue;
				}
			}

			do_action( "pr_packager_{$this->package_type}", $this, $post, $editorial_project, $parsed_post );

			self::print_line( sprintf( __('Adding %s ', 'edition'), $post->post_title ) );
			$this->set_progress( $total_progress + $k * $progress_step );
		}

		do_action( "pr_packager_{$this->package_type}_end", $this, $editorial_project );

		$this->_clean_temp_dir();

		PR_Logs::update_log( $this->log_id, self::$log_output );

		$this->set_progress( 100, __( 'Successfully created package', 'edition' ) );

		self::print_line(__('Done', 'edition'), 'success');

		ob_end_flush();
	}

	/**
	* Set progressbar percentage
	*
	* @param int $percentage
	* @void
	*/
	public function set_progress( $percentage, $text = '' ) {
		$this->pb->setProgress( $percentage, $text );
	}

	/**
	 * Print live output
	 * @param string $output
	 * @param string $class
	 * @echo
 	 */
	public static function print_line( $output, $class = 'success', $enable_log = true ) {
		if ( self::$verbose ) {
			$out =  '<p class="liveoutput ' . $class . '"><span class="label">' . $class . '</span> ' . $output . '</p>';
			echo $out;
			if( $enable_log ) {
				self::$log_output .= $out;
			}
			ob_flush();
			flush();
		}
	}

	/**
	 * Save cover image into edition package
	 *
	 * @void
	 */
	public function save_cover_image() {

		$edition_cover_id = get_post_thumbnail_id( $this->edition_post->ID );
		if ( $edition_cover_id ) {

			$upload_dir = wp_upload_dir();
			$edition_cover_metadata = wp_get_attachment_metadata( $edition_cover_id );
			$edition_cover_path = $upload_dir['basedir'] . DS . $edition_cover_metadata['file'];
			$info = pathinfo( $edition_cover_path );

			if ( copy( $edition_cover_path, $this->edition_dir . DS . PR_EDITION_MEDIA . $info['basename'] ) ) {
				$this->edition_cover_image = $info['basename'];
				self::print_line( sprintf( __( 'Copied cover image %s ', 'edition' ), $edition_cover_path ), 'success' );
			}
			else {
				self::print_line( sprintf( __( 'Can\'t copy cover image %s ', 'edition' ), $edition_cover_path ), 'error' );
			}
		}
	}

	/**
	 * Add package meta data to edition
	 *
	 * @void
	 */
	public function set_package_date() {

		$date = date( 'Y-m-d H:i:s' );
		add_post_meta( $this->edition_post->ID, '_pr_package_date', $date, true );
		update_post_meta( $this->edition_post->ID, '_pr_package_updated_date', $date );
	}

	/**
	 * Add function file if exist
	 *
	 * @void
	 */
	public function add_functions_file() {

		$theme_dir = PR_Theme::get_theme_path( $this->edition_post->ID );
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

	/**
	* Save the html output into file
	*
	* @param  string $post
	* @param  boolean
	*/
	public function save_html_file( $post, $filename, $dir ) {

		return file_put_contents( $dir . DS . PR_Utils::sanitize_string( $filename ) . '.html', $post);
	}

	/**
	* Parse toc file
	*
	* @return string or boolean false
	*/
	public function toc_parse( $editorial_project ) {

    $toc = PR_Theme::get_theme_layout( $this->edition_post->ID, 'toc' );
    if ( !$toc ) {
      return false;
    }

		ob_start();
		$edition = $this->edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_package_type = $this->package_type;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->edition_post->ID );

		$edition_posts = $this->linked_query;
		$this->add_functions_file();
		require( $toc );
		$output = ob_get_contents();
		ob_end_clean();

		return $output;
	}

	/**
	 * Create toc
	 *
	 * @param  object $editorial_project
	 * @param  string $dir
	 * @void
	 */
	public function make_toc( $editorial_project, $dir, $filename = "index" ) {

		// Parse html of toc index.php file
		$html = $this->toc_parse( $editorial_project );
		if ( !$html ) {
			self::print_line( __( 'Failed to parse toc file', 'edition' ), 'error' );
			$this->exit_on_error();
			return;
		}

		// Rewrite toc url
		if ( has_action( "pr_{$this->package_type}_toc_rewrite_url" ) ) {
			do_action_ref_array( "pr_{$this->package_type}_toc_rewrite_url", array( $this, &$html ) );
		}
		else {
			$html = $this->rewrite_url( $html );
		}

		$this->set_progress( 10, __( 'Saving toc file', 'edition' ) );

		// Save cover html file
		if ( $this->save_html_file( $html, $filename, $dir ) ) {
			self::print_line( __( 'Toc file correctly generated', 'edition' ), 'success' );
			$this->set_progress( 20, __( 'Saving edition posts', 'edition' ) );
		}
		else {
			self::print_line( __( 'Failed to save toc file', 'edition' ), 'error' );
			$this->exit_on_error();
			return;
		}
	}

	/**
	* Get all url from the html string and replace with internal url of the package
	*
	* @param  string $html
	* @param  string $ext  = 'html' extension file output
	* @return string or false
	*/
	public function rewrite_url( $html, $extension = 'html', $media_folder = PR_EDITION_MEDIA ) {

		if ( $html ) {
			$post_rewrite_urls = array();
			$external_urls = array();
			$urls = PR_Utils::extract_urls( $html );
			foreach ( $urls as $url ) {
				if ( strpos( $url, site_url() ) !== false || strpos( $url, home_url() ) !== false ) {
					$post_id = url_to_postid( $url );
					if ( $post_id ) {
						foreach( $this->linked_query->posts as $post ) {
							if ( $post->ID == $post_id ) {
								$path = PR_Utils::sanitize_string( $post->post_title ) . '.' . $extension;
								$post_rewrite_urls[$url] = $path;
							}
							else {
								array_push( $external_urls, $url);
							}
						}
					}
					else {
						$attachment_id = self::get_attachment_from_url( $url );
						if ( $attachment_id ) {
							$info = pathinfo( $url );
							$filename = $info['basename'];
							$post_rewrite_urls[$url] = $media_folder . $filename;
							// Add attachments that will be downloaded
							$this->_posts_attachments[$filename] = $url;
						}
					}
				}
				else { //external url
					array_push( $external_urls, $url);
				}
			}

			if ( !empty( $post_rewrite_urls ) ) {
				$html = str_replace( array_keys( $post_rewrite_urls ), $post_rewrite_urls, $html );
			}

			if ( !empty( $external_urls ) ) {
				foreach( $external_urls as $exturl ) {
					$html = str_replace( $exturl, $exturl . "?referrer=Baker", $html );
				}
			}
		}
		return $html;
	}

	/**
	* Copy attachments into the package folder
	*
	* @param  array $attachments
	* @param  string $media_dir path of the package folder
	* @void
	*/
	public function save_posts_attachments( $media_dir ) {

		if ( !empty( $this->_posts_attachments ) ) {
			$attachments = array_unique( $this->_posts_attachments );
			foreach ( $attachments as $filename => $url ) {
				if ( copy( $url, $media_dir . DS . $filename ) ) {
					PR_Packager::print_line( sprintf( __( 'Copied %s ', 'edition' ), $url ), 'success' );
				}
				else {
					PR_Packager::print_line( sprintf( __('Failed to copy %s ', 'edition'), $url ), 'error' );
				}
			}
		}
	}

	/**
	 * Add element to array of attachments
	 * @param array $attachments
	 */
	public function add_post_attachment( $key, $value ) {
		$this->_posts_attachments[$key] = $value;
	}

	/**
	 * Reset array of attachments
	 */
	public function reset_post_attachments() {
		$this->_posts_attachments = array();
	}

	/**
	* Stop packager procedure and clear temp folder
	*
	* @void
	*/
	public function exit_on_error() {

		$this->_clean_temp_dir();
		$this->set_progress( 100, __( 'Error creating package', 'edition' ) );

		PR_Logs::update_log( $this->log_id, self::$log_output );
		ob_end_flush();
	}

	/**
	 * Get attachment ID by url
	 *
	 * @param string $attachment_url
	 * @return string or boolean false
	 */
	public static function get_attachment_from_url( $attachment_url ) {

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
	* Select all posts in p2p connection with the current edition
	*
	* @void
	*/
	protected function _get_linked_posts() {

		if ( !isset( $_GET['edition_id'] ) ) {
			return array();
		}

		$this->edition_post = get_post( $_GET['edition_id'] );

		$this->linked_query = PR_Edition::get_linked_posts( $_GET['edition_id'], array(
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
	 *
	 * @param string $theme_assets_dir
	 * @return boolean
	 */
	protected function _download_assets( $theme_assets_dir ) {

		$edition_assets_dir = PR_Utils::make_dir( $this->edition_dir, basename( $theme_assets_dir ), false );
		if ( !$edition_assets_dir ) {
			self::print_line( sprintf( __( 'Failed to create folder %s', 'edition' ), PR_TMP_PATH . DS . basename( $theme_assets_dir ) ), 'error');
			return false;
		}

		self::print_line( sprintf( __( 'Created folder %s', 'edition' ), $edition_assets_dir ), 'success' );

		if ( !is_dir( $theme_assets_dir ) ) {
			self::print_line( sprintf( __( 'Error: Can\'t read assets folder %s', 'edition' ), $theme_assets_dir ), 'error' );
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
	* Parsing html file
	*
	* @param  object $post
	* @return string
	*/
	protected function _parse_post( $linked_post, $editorial_project ) {
		$page = PR_Theme::get_theme_page( $this->edition_post->ID, $linked_post->p2p_id );
		if ( !$page || !file_exists( $page )  ) {
			return false;
		}

		ob_start();
		$edition = $this->edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_package_type = $this->package_type;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->edition_post->ID );

		global $post;
		$post = $linked_post;
		setup_postdata($post);
		$this->add_functions_file();
		require( $page );
		$output = ob_get_contents();
		wp_reset_postdata();
		ob_end_clean();
		return $output;
	}

	/**
	* Clean the temporary files folder
	*
	* @void
	*/
	protected function _clean_temp_dir() {
		self::print_line(__('Cleaning temporary files ', 'edition') );
		PR_Utils::remove_dir( $this->edition_dir );
	}
}
