<?php
if (!class_exists('PR_EDD_License')) {

  class PR_EDD_License {

    private $file;
  	private $license;
  	private $item_name;
  	private $item_shortname;
  	private $version;
  	private $author;

    /**
	   * Class constructor
  	 *
  	 * @global  array $edd_options
  	 * @param string  $_file
  	 * @param string  $_item_name
  	 * @param string  $_version
  	 * @param string  $_author
  	 * @param string  $_optname
  	 */
    function __construct( $_file, $_item_name, $_version, $_author, $_optname = null ) {

      global $edd_options;
  		$this->file           = $_file;
  		$this->item_name      = $_item_name;
  		$this->item_shortname = 'edd_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
  		$this->version        = $_version;
  		$this->license        = isset( $edd_options[ $this->item_shortname . '_license_key' ] ) ? trim( $edd_options[ $this->item_shortname . '_license_key' ] ) : '';
  		$this->author         = $_author;

      /**
  		 * Allows for backwards compatibility with old license options,
  		 * i.e. if the plugins had license key fields previously, the license
  		 * handler will automatically pick these up and use those in lieu of the
  		 * user having to reactive their license.
  		 */
		  if ( !empty( $_optname ) && isset( $edd_options[ $_optname ] ) && empty( $this->license ) ) {
			  $this->license = trim( $edd_options[ $_optname ] );
      }

      // Setup hooks
		  $this->_includes();
		  $this->_hooks();
		  $this->_auto_updater();
    }

    /**
     * Include the updater class
     *
     * @return  void
     */
	  private function _includes() {

		  if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
        require_once( PR_LIBS_PATH . 'EDD/EDD_SL_Plugin_Updater.php' );
      }
    }

    /**
  	 * Setup hooks
  	 *
  	 * @return  void
  	 */
	  private function _hooks() {

      add_filter( 'edd_settings_licenses', array( $this, 'settings' ), 1 );
		  // Activate license key on settings save
		  add_action( 'admin_init', array( $this, 'activate_license' ) );
		  // Deactivate license key
      add_action( 'admin_init', array( $this, 'deactivate_license' ) );
    }

    /**
  	 * Auto updater
  	 *
  	 * @access  private
  	 * @global  array $edd_options
  	 * @return  void
  	 */
	  private function _auto_updater() {

      $edd_updater = new EDD_SL_Plugin_Updater(
      	PR_API_URL,
      	$this->file,
      	array(
      		'version'   => $this->version,
      		'license'   => $this->license,
      		'item_name' => $this->item_name,
      		'author'    => $this->author
      	)
      );
    }

    /**
  	 * Add license field to settings
  	 *
  	 * @access  public
  	 * @param array   $settings
  	 * @return  array
  	 */
	  public function settings( $settings ) {

      $edd_license_settings = array(
  			array(
  				'id'      => $this->item_shortname . '_license_key',
  				'name'    => sprintf( __( '%1$s License Key', 'edd' ), $this->item_name ),
  				'desc'    => '',
  				'type'    => 'license_key',
  				'options' => array( 'is_valid_license_option' => $this->item_shortname . '_license_active' ),
  				'size'    => 'regular'
  			)
  		);

      return array_merge( $settings, $edd_license_settings );
    }

    /**
     * Activate the license key
  	 *
  	 * @access  public
  	 * @return  void
  	 */
	  public function activate_license() {

      if ( !isset( $_POST['edd_settings'] ) ) {
  			return;
      }

  		if ( !isset( $_POST['edd_settings'][ $this->item_shortname . '_license_key' ] ) ) {
  			return;
      }

  		if ( get_option( $this->item_shortname . '_license_active' ) == 'valid' ) {
  			return;
      }

      $license = sanitize_text_field( $_POST['edd_settings'][ $this->item_shortname . '_license_key' ] );

      // Data to send to the API
		  $api_params = array(
  			'edd_action' => 'activate_license',
  			'license'    => $license,
  			'item_name'  => urlencode( $this->item_name ),
        'url'        => home_url()
  		);

      // Call the API
		  $response = wp_remote_get(
  			add_query_arg( $api_params, PR_API_URL ),
  			array(
  				'timeout'   => 15,
  				'sslverify' => false
  			)
		  );

      // Make sure there are no errors
		  if ( is_wp_error( $response ) ) {
			  return;
      }

      // Decode license data
		  $license_data = json_decode( wp_remote_retrieve_body( $response ) );
      if ( !isset( $license_data->license ) ) {
        return;
      }

		  update_option( $this->item_shortname . '_license_active', $license_data->license );
	  }

    /**
  	 * Deactivate the license key
  	 *
  	 * @access  public
  	 * @return  void
  	 */
	  public function deactivate_license() {

      if ( ! isset( $_POST['edd_settings'] ) ) {
  			return;
      }
  		if ( ! isset( $_POST['edd_settings'][ $this->item_shortname . '_license_key' ] ) ) {
  			return;
      }

      // Run on deactivate button press
  		if ( isset( $_POST[ $this->item_shortname . '_license_key_deactivate' ] ) ) {
  			// Data to send to the API
  			$api_params = array(
  				'edd_action' => 'deactivate_license',
  				'license'    => $this->license,
  				'item_name'  => urlencode( $this->item_name )
  			);
  			// Call the API
  			$response = wp_remote_get(
  				add_query_arg( $api_params, PR_API_URL ),
  				array(
  					'timeout'   => 15,
  					'sslverify' => false
  				)
  			);
  			// Make sure there are no errors
  			if ( is_wp_error( $response ) ) {
  				return;
        }

        // Decode the license data
  			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
  			if ( $license_data->license == 'deactivated' ) {
  				delete_option( $this->item_shortname . '_license_active' );
        }
  		}
	  }
  }
}

/**
 * Register the new license field type
 *
 * This has been included in core, but is maintained for backwards compatibility
 *
 * @return  void
 */
if ( ! function_exists( 'edd_license_key_callback' ) ) {

  function edd_license_key_callback( $args ) {
		global $edd_options;
    if ( isset( $edd_options[ $args['id'] ] ) )
			$value = $edd_options[ $args['id'] ];
		else
			$value = isset( $args['std'] ) ? $args['std'] : '';

		$size = isset( $args['size'] ) && ! is_null( $args['size'] ) ? $args['size'] : 'regular';

		$html = '<input type="text" class="' . $size . '-text" id="edd_settings[' . $args['id'] . ']" name="edd_settings[' . $args['id'] . ']" value="' . esc_attr( $value ) . '"/>';
		if ( 'valid' == get_option( $args['options']['is_valid_license_option'] ) ) {
			$html .= '<input type="submit" class="button-secondary" name="' . $args['id'] . '_deactivate" value="' . __( 'Deactivate License',  'edd-recurring' ) . '"/>';
		}
		$html .= '<label for="edd_settings[' . $args['id'] . ']"> '  . $args['desc'] . '</label>';
		echo $html;
	}
}
