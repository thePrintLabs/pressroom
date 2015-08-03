<?php
define( 'PR_THEME_CONFIG_FILE', 'config.xml' );

class PR_Theme
{
	protected static $_themes = array();
	protected static $_errors = array();

	public function __construct() {

		$this->_hooks();
		$this->search_themes();
	}

	/**
	 * Get theme list from online feed
	 *
	 * @return array $themes
	 */
	public static function get_remote_themes( $product_id = false ) {
		$api_params = array(
      'key'         => 'd75dcbc196cdb7f91196e495dc9f8b47',
      'token'       => '13c6988e10f32dfaddf379396e4e1134',
    );
		if( $product_id ) {
			$api_params['product'] = $product_id;
		}
    $response = wp_remote_get( add_query_arg( $api_params, PR_API_EDD_URL . 'products' ), array( 'timeout' => 15, 'sslverify' => false ) );
    $response = json_decode( wp_remote_retrieve_body( $response ) );
		$themes = array();
		foreach( $response->products as $product ) {
      foreach( $product->info->category as $category ) {
        if( $category->slug == 'themes' ) {
          array_push( $themes, $product );
        }
      }
    }

		return $themes;
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
			$dirs = self::_scan_themes_dir();
			if ( empty( $dirs ) ) {
				return;
			}

			foreach ( $dirs as $dir ) {
				$files = PR_Utils::search_files( $dir, 'xml', false );
				foreach ( $files as $file ) {
					$filename = basename( $file );
					if ( $filename != PR_THEME_CONFIG_FILE ) {
						return;
					}

					array_push( $themes, array(
						'path' => str_replace( PR_THEMES_PATH, '', dirname( $file ) ),
					));
				}
			}

			$themes = self::_validate_themes( $themes );
			if ( empty( self::$_errors ) ) {
				update_option( 'pressroom_themes', $themes );
			}
		}
		self::$_themes = $themes;
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
				if ( $theme['active'] ) {
					array_push( $themes_list, array(
						'value' => $theme['uniqueid'],
						'text'  => $theme['name']
					) );
				}
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
	 * Get current theme settings
	 * @param int $edition_id
	 *
	 * @return array or boolean false
	 */
	public static function get_theme_settings( $edition_id ) {

		$theme_id = get_post_meta( $edition_id, '_pr_theme_select', true );
		if ( $theme_id ) {
			$themes = self::get_themes();
			return $themes[$theme_id];
		}

		return false;
	}

	/**
	 * Get current theme path
	 * @param int $edition_id
	 * @param bool $absolute
	 *
	 * @return string or boolean false
	 */
	public static function get_theme_path( $edition_id, $absolute = true ) {

		$theme_id = get_post_meta( $edition_id, '_pr_theme_select', true );
		if ( $theme_id ) {
			$themes = self::get_themes();
			$options = $themes[$theme_id];
			if( $absolute) {
				return PR_THEMES_PATH . $options['path'] . DS;
			}
			else {
				return $options['path'];
			}

		}

		return false;
	}

	/**
	 * Get current theme uri
	 * @param  int $edition_id
	 * @return string or boolean false
	 */
	public static function get_theme_uri( $edition_id ) {

		$theme_id = get_post_meta( $edition_id, '_pr_theme_select', true );
		if ( $theme_id ) {
			$themes = self::get_themes();
			$options = $themes[$theme_id];
			return PR_THEME_URI . $options['path'] . DS;
		}

		return false;
	}

	/**
	 * Get the path of layout
	 * @param  int $edition_id
	 * @param string $rule
	 * @return string or boolean false
	 */
	public static function get_theme_layout( $edition_id, $rule ) {

		$theme_id = get_post_meta( $edition_id, '_pr_theme_select', true );
		if ( $theme_id ) {
			$themes = self::get_themes();
			$options = $themes[$theme_id];
			foreach ( $options['layouts'] as $layout ) {
				if ( $layout['rule'] == $rule) {
					return PR_THEMES_PATH . $options['path'] . DS . $layout['path'];
				}
			}
		}

		return false;
	}

	/**
	 * Get current theme assets path
	 * @param int $edition_id
	 * @param bool $absolute
	 * @return string or boolean false
	 */
	public static function get_theme_assets_path( $edition_id, $absolute = true ) {

		$theme_id = get_post_meta( $edition_id, '_pr_theme_select', true );
		if ( $theme_id ) {
			$themes = self::get_themes();
			$options = $themes[$theme_id];
			if( $absolute) {
				return PR_THEMES_PATH . $options['path'] . DS . $options['assets'] . DS;
			}
			else {
				return $options['path'] . DS . $options['assets'];
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
		if ( $template ) {
			return PR_THEMES_PATH . $template;
		}

		return false;
	}

	/**
	 * Delete installed theme
	 * @param string $theme_id
	 * @return boolean
	 */
	public static function delete_theme( $theme_id ) {

		if ( !isset( $wp_filesystem ) || is_null( $wp_filesystem ) ) {
			WP_Filesystem();
			global $wp_filesystem;
		}

		$themes = self::get_themes();
		if ( isset( $themes[$theme_id] ) ) {
			$theme_data = $themes[$theme_id];
			$theme_path = PR_THEMES_PATH . $theme_data['path'] . DS;
			if ( file_exists( $theme_path ) ) {
				return $wp_filesystem->rmdir( $theme_path, true );
			}
		}
		return false;
	}

	/**
	 * Check if a theme is installed
	 * @param  string $theme_id
	 * @return boolean
	 */
	public static function check_theme_exist( $theme_id ) {

		$themes = self::get_themes();
		return array_key_exists( $theme_id, $themes );
	}

	/**
	 * Set installed theme status
	 * @param string $theme_id
	 * @param boolean $status
	 * @return boolean
	 */
	public static function set_theme_status( $theme_id, $status = true ) {

		$themes = self::get_themes();
		if ( isset( $themes[$theme_id] ) ) {
			$themes[$theme_id]['active'] = $status;
			update_option( 'pressroom_themes', $themes );
			return true;
		}
		return false;
	}

	/**
	 * Get remote addons from online feed
	 *
	 * @return array $remotes
	 */
	public static function get_discount_codes() {
		$api_params = array(
      'key'         => 'd75dcbc196cdb7f91196e495dc9f8b47',
      'token'       => '13c6988e10f32dfaddf379396e4e1134',
    );
    $response = wp_remote_get( add_query_arg( $api_params, PR_API_EDD_URL . 'discounts' ), array( 'timeout' => 15, 'sslverify' => false ) );
    $response = json_decode( wp_remote_retrieve_body( $response ) );
		$discount_codes = array();

		if( $response && isset( $response->discounts ) ) {
			foreach( $response->discounts as $discount ) {
				$name = explode( '::', $discount->name );
				$category = trim( $name[1] );
				if( $discount->status == 'active' && $category == 'themes' ) {
					$discount->name = $name[0];
					$products = $discount->product_requirements;
					if( $products ) {
						foreach( $products as $product_id ) {
							$product = self::get_remote_themes( $product_id );
							if( $product ) {
								$discount->products[] = '<a href="'.$product[0]->info->link.'">' . $product[0]->info->title . ' </a>';
							}
						}
					}

					array_push( $discount_codes, $discount );
				}
	    }
		}

		return $discount_codes;
  }

	/**
	 * Scan over folder of the installed themes.
	 *
	 * @return array
	 */
	protected static function _scan_themes_dir() {

		$themes = array();
		if ( is_dir( PR_THEMES_PATH ) === false ) {
			return false;
		}

		try {
			$resource = opendir( PR_THEMES_PATH );
			while ( false !== ( $file = readdir( $resource ) ) ) {

				if ( in_array( $file, PR_Utils::$excluded_files) )	{
					continue;
				}

				$theme = PR_THEMES_PATH . $file;
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
	 * Get property from config.xml file
	 * @param  string $config_file
	 *
	 * @return bool or array $metadata
	 */
	protected static function _parse_theme_config( $config_file ) {

		$xml = simplexml_load_file( $config_file );

		if ( !isset( $xml->layouts ) ) {
			return false;
		}

		$author = isset( $xml->author ) ? $xml->author : false;
		// Get properties
		$metadata = array(
			'uniqueid'			=> isset( $xml->uniqueid ) ? $xml->uniqueid[0]->__toString() : false ,
			'name'					=> isset( $xml->name ) ? $xml->name[0]->__toString() : false ,
			'date'					=> isset( $xml->date ) ? $xml->date[0]->__toString() : false ,
			'version'				=> isset( $xml->version ) ? $xml->version[0]->__toString() : false ,
			'paid'					=> isset( $xml->paid ) ? $xml->paid[0]->__toString() : false ,
			'description'		=> isset( $xml->description ) ? $xml->description[0]->__toString() : false ,
			'thumbnail'			=> isset( $xml->thumbnail ) ? $xml->thumbnail[0]->__toString() : false ,
			'website'				=> isset( $xml->website ) ? $xml->website[0]->__toString() : false ,
			'assets'				=> isset( $xml->assets ) ? $xml->assets[0]->__toString() : 'assets',
			'active'				=> 1,
			'author_name'		=> $author && isset( $author->name ) ? $author->name[0]->__toString() : false ,
			'author_email'	=> $author && isset( $author->email ) ? $author->email[0]->__toString() : false ,
			'author_site'		=> $author && isset( $author->url ) ? $author->url[0]->__toString() : false ,
			'layouts'				=> array()
		);

		foreach( $xml->layouts->item as $layout ) {
			$metadata['layouts'][] = array(
				'name'				=> $layout->name[0]->__toString(),
				'rule'				=> $layout->rule[0]->__toString(),
				'description'	=> $layout->description[0]->__toString(),
				'path'				=> $layout->path[0]->__toString(),
			);
		}

		return $metadata;
	}

	/**
	 * Validate themes array and check if all property is set to theme pages
	 *
	 * @return array
	 */
	protected static function _validate_themes( $themes ) {

		self::$_errors = array();
		if ( empty($themes) ) {
			return;
		}

		foreach ( $themes as $k => $theme ) {
			$config_file = PR_THEMES_PATH . $theme['path'] . DS . PR_THEME_CONFIG_FILE;
			$theme_meta = self::_parse_theme_config( $config_file );
			if ( !$theme_meta ) {
				array_push( self::$_errors, self::_theme_error_notice( 'Error: <b>malformed xml config file ' . $config_file .'</b>' ) );
				continue;
			}

			if ( !$theme_meta['uniqueid'] ) {
				array_push( self::$_errors, self::_theme_error_notice( 'Error: <b>uniqueid can\'t be empty</b> in <b>' . $config_file .'</b>' ) );
				continue;
			}

			if ( empty( $theme_meta['layouts'] ) ) {
				array_push( self::$_errors, self::_theme_error_notice( 'Error: <b>layouts section can\'t be empty</b> in <b>' . $config_file .'</b>' ) );
				continue;
			}

			foreach ( $theme_meta['layouts'] as $layout ) {
				if ( !$layout['rule'] ) {
					array_push( self::$_errors, self::_theme_missing_notice( $theme_meta['name'], 'rule' ) );
				}
				elseif ( !strlen( $layout['name'] ) ) {
					array_push( self::$_errors, self::_theme_missing_notice( $theme_meta['name'], 'name' ) );
				}
				elseif ( !strlen( $layout['path'] ) ) {
					array_push( self::$_errors, self::_theme_missing_notice( $theme_meta['name'], 'path' ) );
				}
			}
			self::$_themes[$theme_meta['uniqueid']] = array_merge( $theme, $theme_meta );
		}

		return self::$_themes;
	}

	/**
	 * Register wordpress hooks
	 * @void
	 */
	protected function _hooks() {
		add_action( 'admin_notices', array( 'PR_Theme', 'themes_notice' ) );
	}

	/**
	 * Generate a notice message
	 * @param  string $theme_name
	 * @param  string $param
	 *
	 * @return string;
	 */
	protected static function _theme_missing_notice( $theme_name, $param = '' ) {

		$html = '<div class="error">';
		$html.= "<p>Missing <b>$param</b> in <b>layouts section</b> of theme <b>$theme_name</b></p>";
		$html.= '</div>';

		return $html;
	}

	/**
	 * Generate a error notice for config files
	 * @param  string $message
	 *
	 * @return string;
	 */
	protected static function _theme_error_notice( $message ) {

		$html = '<div class="error">';
		$html.= '<p>' . _e( $message ) . '</p>';
		$html.= '</div>';

		return $html;
	}
}
