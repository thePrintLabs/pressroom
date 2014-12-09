<?php
/**
 * PressRoom packager class.
 *
 */

require_once( PR_PACKAGER_EXPORTERS_PATH . '/hpub/hpub_package.php' );
require_once( PR_PACKAGER_EXPORTERS_PATH . '/web/web_package.php' );
require_once( PR_PACKAGER_PATH . '/progressbar.php' );

class PR_Packager
{
	public static $verbose = true;
	public $pb;
	public $edition_dir;
	public $linked_query;
	public $edition_cover_image;
	public $edition_post;
	public $package_type;

	public function __construct() {

		$this->_get_linked_posts();
		$this->pb = new ProgressBar();

		if( !isset($_GET['packager_type'])) {
			self::print_line( __( 'No package type selected. ', 'edition' ), 'error' );
			exit;
		}

		$this->package_type = $_GET['packager_type'];
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

		if ( is_null( $this->edition_post ) ) {
			ob_end_flush();
			return;
		}

		if( !$this->linked_query->posts ) {
			self::print_line( __( 'No posts linked to this edition ', 'edition' ), 'error' );
			exit;
		}

		self::print_line( sprintf( __( 'Create package for %s', 'edition' ), $editorial_project->name ), 'info' );

		// Create edition folder
		$edition_post = $this->edition_post;
		$edition_name = $editorial_project->slug . '_' . time();
		$this->edition_dir = PR_Utils::make_dir( PR_TMP_PATH, $edition_name );
		if ( !$this->edition_dir ) {
			self::print_line( __( 'Failed to create folder ', 'edition' ) . PR_TMP_PATH . $edition_name, 'error' );
			$this->set_progress( 100 );
			ob_end_flush();
			return;
		}

		self::print_line( __( 'Create folder ', 'edition' ) . $this->edition_dir, 'success' );
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

		do_action( "pr_packager_{$this->package_type}_start", $this, $editorial_project );

		$total_progress = 40;
		$progress_step = round( $total_progress / count( $this->linked_query->posts ) );

		foreach ( $this->linked_query->posts as $k => $post ) {

			$parsed_post = $this->_post_parse( $post, $editorial_project );
			if ( !$parsed_post ) {
				self::print_line( sprintf( __( 'You have to select a template for %s', 'edition' ), $post->post_title ), 'error' );
				continue;
			}

			do_action( "pr_packager_{$this->package_type}", $this, $post, $editorial_project, $parsed_post );

			self::print_line(__('Adding ', 'edition') . $post->post_title);
			$this->set_progress( $total_progress + $k * $progress_step );
		}

		do_action( "pr_packager_{$this->package_type}_end", $this, $editorial_project );

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
	protected function _exit_on_error() {

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
	 * @param  stirng $theme_assets_dir
	 * @return boolean
	 */
	protected function _download_assets( $theme_assets_dir ) {

		$edition_assets_dir = PR_Utils::make_dir( $this->edition_dir, 'assets' );
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

		$cover = PR_Theme::get_theme_cover( $this->edition_post->ID );
		if ( !$cover ) {
			return false;
		}

		ob_start();
		$edition = $this->edition_post;
		$editorial_project_id = $editorial_project->term_id;
		$pr_theme_url = PR_THEME::get_theme_uri( $this->edition_post->ID );

		$posts = $this->linked_query;
		$this->add_functions_file();
		require( $cover );
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

		$page = PR_Theme::get_theme_page( $this->edition_post->ID, $linked_post->p2p_id );
		if ( !$page || !file_exists( $page )  ) {
			return false;
		}

		ob_start();
		$edition = $this->edition_post;
		$editorial_project_id = $editorial_project->term_id;
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
	 * Save cover image into edition package
	 * @void
	 */
	public function save_cover_image() {

		$edition_cover_id = get_post_thumbnail_id( $this->edition_post->ID );
		if ( $edition_cover_id ) {

			$upload_dir = wp_upload_dir();
			$edition_cover_metadata = wp_get_attachment_metadata( $edition_cover_id );
			$edition_cover_path = $upload_dir['basedir'] . DIRECTORY_SEPARATOR . $edition_cover_metadata['file'];
			$info = pathinfo( $edition_cover_path );

			if ( copy( $edition_cover_path, $this->edition_dir . DIRECTORY_SEPARATOR . PR_EDITION_MEDIA . $info['basename'] ) ) {
				$this->edition_cover_image = $info['basename'];
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
		PR_Utils::remove_dir( $this->edition_dir );
	}

	/**
	 * Add package meta data to edition
	 *
	 * @void
	 */
	public function _set_package_date() {

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
}
