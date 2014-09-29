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

class TPL_Themes
{
	protected $_themes;
	protected $_themes_names;
	protected $_pages_names;
	protected $_edition_post;

	/**
	 * TPL_Theme function.
	 * Class costructor
	 * @return void
	 */

	public function __construct() {

		$this->_search_themes();

		add_action( 'admin_notices', array( $this, 'theme_missing_notice' ) ) ;

		if (isset($_GET['edition_id'])) {
			$this->_edition_post = get_post($_GET['edition_id']);
		}
	}

	/**
	 *
	 */
	protected function _search_themes() {

		$themes = array();
		$default_headers = array(
			'theme'	=> 'theme',
			'rule'   => 'rule',
			'name'	=> 'name'
		);

		$dirs = TPL_Utils::read_themes();
		if ( empty( $dirs ) ) {
			return;
		}

		foreach ( $dirs as $dir ) {

			$pages = array();
			$files = TPL_Utils::readFiles( $dir );

			foreach ( $files as $file ) {

				$metadata = get_file_data( $dir . DIRECTORY_SEPARATOR . $file, $default_headers);
				if ( empty($metadata) ) {
					continue;
				}

				if ( !isset( $metadata['theme'] ) || !strlen( $metadata['theme'] ) ) {
					continue;
				}

				$theme_name = strtolower($metadata['theme']);

				if ( $metadata['name'] ) {
					$pages[] = $metadata['name'];
				}

				unset( $metadata['theme'] );
				$metadata['filename'] = $file;
				$themes[$theme_name][] = $metadata;
			}

			$this->_pages_names = $pages;
		}

		$this->_themes = $themes;
		//$this->_themes = array_unique($themes);
		$this->theme_cheker($this->_themes);
	}

	/**
	 * Get the list of installed themes
	 * @return array
	 */
	public static function get_themes_list() {

		$themes_list = array();
		$model = new self;
		if ( !empty( $model->_themes ) ) {
			ksort( $model->_themes );
			$themes = array_keys( $model->_themes );
			foreach ( $themes as $theme ) {
				$themes_list[] = array(
					'value' => $theme,
					'text'  => $theme
				);
			}
		}

		return $themes_list;
	}

	public static function get_pages_names() {
		$model = new self();
		return $model->_pages_names;
	}

	public function get_template_file_per_page($post_id) {
		$template = p2p_get_meta( $post_id, 'template', true );
		$current_theme = get_post_meta( $this->_edition_post->ID, '_tpl_themes_select', true );

		if($template) {
			return TPL_THEME_PATH . $current_theme . DIRECTORY_SEPARATOR . $template;
		}
		else {
			return false;
		}

	}

	public function get_template_path($edition_id){
		$current_theme = get_post_meta( $edition_id, '_tpl_themes_select', true );

		return TPL_THEME_PATH . $current_theme . DIRECTORY_SEPARATOR;
	}

	public function get_cover($edition_id){
		$current_theme = get_post_meta( $this->_edition_post->ID, '_tpl_themes_select', true );
		$themes = self::get_themes();
		$pages = $themes[$current_theme];
		foreach($pages as $page) {
			if($page['rule'] == 'cover') {
				$cover = $page['filename'];
			}
		}
		return TPL_THEME_PATH . $current_theme . DIRECTORY_SEPARATOR . $cover;
	}

	/**
	 * get_page_name function.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_themes() {
		$model = new self();
		return $model->_themes;
	}


	public static function theme_missing($theme_name, $param = '', $filename = '') {
		if($theme_name && $param) {
			return $theme_name;
		}
	}

	public function theme_missing_notice($theme_name, $param = '', $filename = '') {
		$theme_name = self::theme_missing($theme_name, $param);
		if($theme_name && $param) {
			$html = '<div class="error">';
				switch($param) {
					case 'cover':
						$html.= "<p>Missing $param in  theme $theme_name </p>";
						break;
					case 'rule':
					case 'name':
					default:
						$html.= "<p>Missing $param in  $filename of theme $theme_name </p>";
						break;
				}
			$html.= '</div>';
			echo $html;
		}
	}

	/**
	 * theme_cheker function.
	 * Validate themes array and check if all property is set to theme pages
	 * @access public
	 * @param array $themes (default: array())
	 * @return void
	 */
	public function theme_cheker($themes = array()) {
		foreach ($themes as $key => $theme) {
			$cover = array();
			foreach ($theme as $page) {
				if(!$page['rule']) {
					$this->theme_missing_notice($key, 'rule', $page['filename']);
				}
				else if($page['rule'] == 'cover') {
					$cover[] = $page['rule'];
				}
				else if (!strlen($page['name'])){
					$this->theme_missing_notice($key, 'name', $page['filename']);
				}
			}
			if(count($cover) < 1) {
				$this->theme_missing_notice($key, 'cover');
			}
		}
	}
}
