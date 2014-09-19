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

class TPL_Themes {

	protected $_themes;
	protected $_themes_names;
	protected $_pages_names;
	protected $_edition_post;

	/**
	 * TPL_Theme function.
	 * Class costructor
	 * @access public
	 * @return void
	 */

	public function TPL_Themes() {
		$this->scan_theme_directory();
		add_action( 'admin_notices', array( $this, 'theme_missing_notice' ) ) ;

		if (isset($_GET['edition_id'])) {
			$this->_edition_post = get_post($_GET['edition_id']);
		}
	}


	/**
	 * get_themes function.
	 *
	 * @access public
	 * @static
	 * @return array
	 */
	public static function get_themes_name() {
		$model = new self();
		return $model->_themes_names;
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

	public function scan_theme_directory() {
		$directories = TPL_Utils::readThemes(TPL_THEME_PATH);
		$default_headers = array(
		   'theme'        	=> 'theme',
		   'rule'   				=> 'rule',
		   'name'     			=> 'name',
		);
		$themes = array();
		$pages = array();
		foreach($directories as $directory) {
			$files = TPL_Utils::readFiles($directory);
			$pages_names = array();
			foreach($files as $file) {
				$docks = get_file_data($directory. DIRECTORY_SEPARATOR .$file, $default_headers);
				$docks['filename'] = $file;
				if($docks['theme']){
					$themes_names[strtolower($docks['theme'])] = strtolower($docks['theme']);
				}
				if($docks['name']) {
					$pages_names[] = $docks['name'];
				}
				$key = strtolower($docks['theme']);
				unset($docks['theme']);
				$themes[$key][] = $docks;

				$this->_themes_names = array_unique($themes_names);
				$this->_themes = $themes;
				$this->_pages_names = $pages_names;

			}
		}
		$this->theme_cheker($this->_themes);
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
