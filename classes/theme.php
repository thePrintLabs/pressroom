<?php
/**
 * TPL_Theme class.
 * Theme folder structure:
 * - 	theme_name
 * -- 	index.php (required, must have comments theme: theme_name, rule: cover)
 * --	anyotherfile.php (template, must have comments theme: theme_name, rule: post, name: template_name)
 * -- 	assets
 * --- 	css
 * --- 	img
 * --- 	js
 */

class TPL_Theme
{
	protected static $_themes = array();
	protected static $_errors = array();

	public function __construct() {

		$this->search_themes();
	}

	/**
	 * Search themes installed
	 *
	 * @void
	 */
	public static function search_themes() {

		$themes = get_option( 'pressroom_themes' );
		if ( !$themes ) {
			$themes = array();
			$default_headers = array(
				'theme'	=> 'theme',
				'rule'   => 'rule',
				'name'	=> 'name'
			);

			$dirs = self::_scan_themes_dir();
			if ( empty( $dirs ) ) {
				return;
			}

			foreach ( $dirs as $dir ) {

				$pages = array();
				$files = TPL_Utils::read_php_files( $dir );
				foreach ( $files as $file ) {

					$metadata = get_file_data( $dir . DIRECTORY_SEPARATOR . $file, $default_headers);
					if ( empty($metadata) ) {
						continue;
					}

					$metadata['theme_path'] = basename( $dir );
					$metadata['filename'] = $file;
					$theme_name = TPL_Utils::parse_string( $metadata['theme_path'] );

					if ( $metadata['name'] ) {
						$pages[] = $metadata['name'];
					}

					$themes[$theme_name][] = $metadata;
				}
			}
		}

		self::$_themes = $themes;
		self::_validate_themes();
		add_action( 'admin_notices', array( 'TPL_Theme', 'themes_notice' ) );
	}

	/**
	 * Get the list of installed themes
	 *
	 * @return array
	 */
	public static function get_themes_list() {

		$model = new self;
		$themes_list = array();
		if ( !empty( $model::$_themes ) ) {
			foreach ( $model::$_themes as $k => $theme ) {

				$theme_name = self::_get_theme_name( $theme );
				array_push( $themes_list, array(
					'value' => $k,
					'text'  => $theme_name
				) );
			}
		}

		return $themes_list;
	}

	/**
	 * Get installed themes objects
	 *
	 * @return array
	 */
	public static function get_themes() {

		$model = new self();
		return $model::$_themes;
	}

	/**
	 * Display notice messages
	 *
	 * @echo
	 */
	public static function themes_notice() {

		foreach ( self::$_errors as $error ) {
			echo $error;
		}
	}

	/**
	 * Get current theme path
	 * @param  int $edition_id
	 * @return string or boolean false
	 */
	public static function get_theme_path( $edition_id ) {

		$theme = get_post_meta( $edition_id, '_tpl_themes_select', true );
		if ( $theme ) {
			return TPL_THEME_PATH . $theme . DIRECTORY_SEPARATOR;
		}

		return false;
	}

	/**
	 * Get current theme uri
	 * @param  int $edition_id
	 * @return string or boolean false
	 */
	public static function get_theme_uri( $edition_id ) {

		$theme = get_post_meta( $edition_id, '_tpl_themes_select', true );
		if ( $theme ) {
			return TPL_THEME_URI . $theme . DIRECTORY_SEPARATOR;
		}

		return false;
	}

	/**
	 * Get the path of cover edition
	 * @param  int $edition_id
	 * @return string or boolean false
	 */
	public static function get_theme_cover( $edition_id ) {

		$theme = get_post_meta( $edition_id, '_tpl_themes_select', true );
		$themes = self::get_themes();
		$files = $themes[$theme];
		foreach ( $files as $file ) {
			if ( $file['rule'] == 'cover') {
				$cover = $file['filename'];
				return TPL_THEME_PATH . $theme . DIRECTORY_SEPARATOR . $cover;
			}
		}

		return false;
	}

	/**
	 * Get the path of the template of a page edition
	 * @param  int $edition_id
	 * @param  int $post_id
	 * @return string or boolean false
	 */
	public static function get_theme_page( $edition_id, $post_id ) {

		$template = p2p_get_meta( $post_id, 'template', true );
		$theme = get_post_meta( $edition_id, '_tpl_themes_select', true );
		if ( $template && $theme ) {
			return TPL_THEME_PATH . $theme . DIRECTORY_SEPARATOR . $template;
		}

		return false;
	}

	/**
	 * Scan over folder of the installed themes.
	 *
	 * @return array
	 */
	protected static function _scan_themes_dir() {

		$themes = array();
		if ( is_dir( TPL_THEME_PATH ) === false ) {
			return false;
		}

		try {
			$resource = opendir( TPL_THEME_PATH );
			while ( false !== ( $file = readdir( $resource ) ) ) {

				if ( in_array( $file, TPL_Utils::$excluded_files) )	{
					continue;
				}

				$theme = TPL_THEME_PATH . $file;
				if ( is_dir( $theme ) ) {
					array_push( $themes, $theme );
				}
			}

			return $themes;

		} catch(Exception $e) {
			error_log( 'Caught exception: '. $e->getMessage(). "\n", 0);
			return false;
		}
	}

	/**
	 * Validate themes array and check if all property is set to theme pages
	 * 
	 * @void
	 */
	protected static function _validate_themes() {

		self::$_errors = array();
		if ( empty(self::$_themes) ) {
			return;
		}

		foreach ( self::$_themes as $k => $theme ) {

			$cover = array();
			$theme_name = self::_get_theme_name( $theme );

			foreach ( $theme as $page ) {

				if ( !isset( $page['rule'] ) || !strlen( $page['rule'] ) ) {
					array_push( self::$_errors, self::_theme_missing_notice( $theme_name, 'rule', $page['filename'] ) );
				}
				elseif ( $page['rule'] == 'cover' ) {
					array_push( $cover, $page['rule'] );
				}
				elseif ( !strlen( $page['name'] ) ) {
					array_push( self::$_errors, self::_theme_missing_notice( $theme_name, 'name', $page['filename'] ) );
				}
			}

			if ( empty( $cover ) ) {
				array_push( self::$_errors, self::_theme_missing_notice( $theme_name, 'cover' ) );
			}
		}

		if ( empty( self::$_errors ) ) {
			$themes = get_option( 'pressroom_themes' );
			if ( !$themes ) {
				add_option( 'pressroom_themes', self::$_themes );
			}
			elseif ( count( $themes ) != count( self::$_themes ) ) {
				update_option( 'pressroom_themes', self::$_themes );
			}
		}
	}

	/**
	 * Generate a notice message
	 * @param  string $theme_name
	 * @param  string $param
	 * @param  string $filename
	 *
	 * @return string;
	 */
	protected static function _theme_missing_notice( $theme_name, $param = '', $filename = '' ) {

		$html = '<div class="error">';
		switch ( $param ) {
			case 'cover':
				$html.= "<p>Missing <b>$param</b> in theme <b>$theme_name</b></p>";
				break;
			default:
				$html.= "<p>Missing <b>$param</b> in <b>$filename</b> of theme <b>$theme_name</b></p>";
				break;
		}
		$html.= '</div>';

		return $html;
	}

	/**
	 * Get template name by key
	 * @param  string $theme
	 * @return string
	 */
	protected static function _get_theme_name( $theme ) {

		$key = 'theme';
		$theme_name = array_unique( array_map( function( $item ) use ( $key ) {
			return $item[$key];
		}, $theme) );

		return $theme_name[0];
	}
}
