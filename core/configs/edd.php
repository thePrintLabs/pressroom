<?php
if (!class_exists('PR_EDD_License')) {

  class PR_EDD_License {

    private $file;
  	private $license;
  	private $item_name;
  	private $item_shortname;
  	private $version;
  	private $author;
    private $options;

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

      $this->options = get_option( 'pr_settings' );

  		$this->file           = $_file;
  		$this->item_name      = $_item_name;
  		$this->item_shortname = 'edd_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
  		$this->version        = $_version;
  		$this->license        = $this->options && isset( $this->options['pr_license_key'] ) ? $this->options['pr_license_key'] : '';
  		$this->author         = $_author;

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

      // Activate license key on settings save
		  add_action( 'update_option_pr_settings', array( $this, 'activate_license' ), 20, 2 );
		  // Deactivate license key
      add_action( 'update_option_pr_settings', array( $this, 'deactivate_license' ), 20, 2 );
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
     * Activate the license key
  	 *
  	 * @access  public
  	 * @return  void
  	 */
	  public function activate_license( $old_value, $new_value  ) {

      if ( isset( $new_value['pr_license_key'] ) && strlen( $new_value['pr_license_key'] ) &&
        ( !isset( $this->options['pr_license_is_valid'] ) || $this->options['pr_license_is_valid'] != 'valid' ) ) {

        $license = sanitize_text_field( $new_value['pr_license_key'] );
        $api_params = array(
    			'edd_action' => 'activate_license',
    			'license'    => $license,
    			'item_name'  => urlencode( $this->item_name ),
          'url'        => home_url()
    		);

  		  $response = wp_remote_get(
    			add_query_arg( $api_params, PR_API_URL ),
    			array(
    				'timeout'   => 15,
    				'sslverify' => false
    			)
  		  );

        if ( is_wp_error( $response ) ) {
          $new_value['pr_license_key'] = $new_value['pr_license_is_valid'] = '';
          update_option( 'pr_settings', $new_value );
          $param = urlencode( $response->get_error_message() );
          wp_redirect( admin_url( 'admin.php?page=pressroom&settings-updated=true&pmtype=error&pmcode=failed_actived_license&pmparam=' . $param ) );
          exit;
        }

        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        if ( !isset( $license_data->license ) || $license_data->license != 'valid' ) {
          $new_value['pr_license_key'] = $new_value['pr_license_is_valid'] = '';
          update_option( 'pr_settings', $new_value );
          $param = __( "The license key is invalid", 'pressroom-license' );
          wp_redirect( admin_url( 'admin.php?page=pressroom&settings-updated=true&pmtype=error&pmcode=failed_actived_license&pmparam=' . $param ) );
          exit;
        }

        $new_value['pr_license_key'] = trim( $license );
        $new_value['pr_license_is_valid'] = $license_data->license;
        update_option( 'pr_settings', $pr_settings );
        $param = __( "The license key is valid", 'pressroom-license' );
        wp_redirect( admin_url( 'admin.php?page=pressroom&settings-updated=true&pmtype=success&pmcode=success_actived_license&pmparam=' . $param ) );
        exit;
	    }
    }

    /**
  	 * Deactivate the license key
  	 *
  	 * @access  public
  	 * @return  void
  	 */
	  public function deactivate_license( $old_value, $new_value ) {

      if ( isset( $_POST['pr_license_key_deactivate'] ) ) {
        die ('here');
  			$api_params = array(
  				'edd_action' => 'deactivate_license',
  				'license'    => $this->license,
  				'item_name'  => urlencode( $this->item_name )
  			);

  			$response = wp_remote_get(
  				add_query_arg( $api_params, PR_API_URL ),
  				array(
  					'timeout'   => 15,
  					'sslverify' => false
  				)
  			);

  			if ( is_wp_error( $response ) ) {
  				return;
        }

        // Decode the license data
  			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
  			if ( $license_data->license == 'deactivated' ) {
          unset( $this->options['pr_license_key'], $this->options['pr_license_is_valid'] );
          update_option( 'pr_settings', $this->options );
        }
  		}
	  }

    public function check_license() {

      global $wp_version;

      $license = trim( get_option( 'edd_sample_license_key' ) );

      $api_params = array(
        'edd_action' => 'check_license',
        'license' => $license,
        'item_name' => urlencode( EDD_SAMPLE_ITEM_NAME ),
        'url'       => home_url()
      );

      // Call the custom API.
      $response = wp_remote_get( add_query_arg( $api_params, EDD_SAMPLE_STORE_URL ), array( 'timeout' => 15, 'sslverify' => false ) );


      if ( is_wp_error( $response ) )
        return false;

      $license_data = json_decode( wp_remote_retrieve_body( $response ) );

      if( $license_data->license == 'valid' ) {
        echo 'valid'; exit;
        // this license is still valid
      } else {
        echo 'invalid'; exit;
        // this license is no longer valid
      }
    }

  }
}
