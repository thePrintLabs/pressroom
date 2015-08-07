<?php
define( 'PR_ADDON_CONFIG_FILE', 'config.xml' );

class PR_Addons
{
	protected static $_addons = array();
	protected static $_errors = array();

	public function __construct() {
		$this->search();
	}

	/**
	 * Search addons installed
	 *
	 * @void
	 */
	public function search() {
		$settings = get_option( 'pr_settings' );
		$addons = $settings['pr_enabled_exporters'] ;
		$addons_to_check = array();

		foreach( $addons as $addon ) {
			if( !isset( $addon['config'] ) ) {
				continue;
			}

			$filename = basename( $addon['config'] );
			if ( $filename != PR_ADDON_CONFIG_FILE ) {
				return;
			}
			array_push( $addons_to_check, $addon);
		}

		$addons = self::_validate_addons( $addons_to_check );
		if ( empty( self::$_errors ) ) {
			$settings['pr_enabled_exporters'] = $addons;
			update_option( 'pr_settings', $settings );
		}
	}

	/**
	 * Get remote addons from online feed
	 *
	 * @return array $remotes
	 */
	public static function get_remote_addons( $product_id = false ) {
		$api_params = array(
			'key'         => 'd75dcbc196cdb7f91196e495dc9f8b47',
			'token'       => '13c6988e10f32dfaddf379396e4e1134',
		);
		if( $product_id ) {
			$api_params['product'] = $product_id;
		}
		$response = wp_remote_get( add_query_arg( $api_params, PR_API_EDD_URL . 'products' ), array( 'timeout' => 15, 'sslverify' => false ) );
		$response = json_decode( wp_remote_retrieve_body( $response ) );

		$remotes = array();
		if( $response && isset( $response->products ) ) {
			foreach( $response->products as $product ) {
				foreach( $product->info->category as $category ) {
					if( $category->slug == 'exporters' ) {
						array_push( $remotes, $product );
					}
				}
			}
		}

		return $remotes;
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
				$category = trim( isset( $name[1] ) ? $name[1] : '' );
				if( $discount->status == 'active' && $category == 'addons' ) {
					$discount->name = $name[0];
					$products = $discount->product_requirements;
					if( $products ) {
						foreach( $products as $product_id ) {
							$product = self::get_remote_addons( $product_id );
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
	 * Get add-ons objects
	 *
	 * @return array
	 */
	public static function get() {
		$model = new self();
		return $model::$_addons;
	}

	/**
	 * Validate addons array and check if all property is set
	 *
	 * @return array
	 */
	protected static function _validate_addons( $addons ) {

		self::$_errors = array();
		if ( empty($addons) ) {
			return;
		}

		foreach ( $addons as $k => $addon ) {
			$config_file = $addon['config'];
			$addon_meta = self::_parse_addon_config( $config_file );

			if ( !$addon_meta ) {
				array_push( self::$_errors, self::_addon_error_notice( 'Error: <b>malformed or missing xml config file ' . $config_file .'</b>' ) );
				continue;
			}

			if ( !$addon_meta['itemid'] ) {
				array_push( self::$_errors, self::_addon_error_notice( 'Error: <b>itemid can\'t be empty</b> in <b>' . $config_file .'</b>' ) );
				continue;
			}

			if ( empty( $addon_meta['slug'] ) ) {
				array_push( self::$_errors, self::_addon_error_notice( 'Error: <b>slug section can\'t be empty</b> in <b>' . $config_file .'</b>' ) );
				continue;
			}
			self::$_addons[$addon_meta['slug']] = array_merge( $addon, $addon_meta );
		}

		return self::$_addons;
	}

	/**
	 * Get property from config.xml file
	 * @param  string $config_file
	 *
	 * @return bool or array $metadata
	 */
	protected static function _parse_addon_config( $config_file ) {

		if(!file_exists( $config_file ) ) {
			return false;
		}

		$xml = simplexml_load_file( $config_file );

		if ( !isset( $xml->slug ) ) {
			return false;
		}

		// Get properties
		$metadata = array(
			'itemid'				=> isset( $xml->itemid ) ? $xml->itemid[0]->__toString() : false ,
			'name'					=> isset( $xml->name ) ? $xml->name[0]->__toString() : false ,
			'slug'					=> isset( $xml->slug ) ? $xml->slug[0]->__toString() : false ,
			'version'				=> isset( $xml->version ) ? $xml->version[0]->__toString() : false ,
			'paid'					=> isset( $xml->paid ) ? $xml->paid[0]->__toString() : false ,
			'description'		=> isset( $xml->description ) ? $xml->description[0]->__toString() : false ,
		);

		return $metadata;
	}


  /**
   * Generate a error notice for config files
   * @param  string $message
   *
   * @return string;
   */
  protected static function _addon_error_notice( $message ) {

    $html = '<div class="error">';
    $html.= '<p>' . _e( $message ) . '</p>';
    $html.= '</div>';

    return $html;
  }
}
