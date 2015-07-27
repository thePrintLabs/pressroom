<?php
if ( !class_exists('PR_EDD_License') ) {

  class PR_EDD_License {

    private $settings;
    private $file;
  	private $license;
  	private $item_name;
  	private $item_shortname;
  	private $version;
  	private $author;
    private $license_key;
    private $type;

    /**
	   * Class constructor
  	 *
  	 * @global  array $edd_options
  	 * @param string  $_file
  	 * @param string  $_item_name
  	 * @param string  $_version
  	 * @param string  $_author
  	 * @param string  $_licence_key
  	 */
    function __construct( $_file, $_item_name, $_version, $_author, $_licence_key = '' ) {

      if ( is_admin() ) {

        $this->settings       = get_option( 'pr_settings' );
        $this->file           = $_file;
        $this->item_name      = $_item_name;
        $this->item_shortname = 'edd_' . preg_replace( '/[^a-zA-Z0-9_\s]/', '', str_replace( ' ', '_', strtolower( $this->item_name ) ) );
        $this->version        = $_version;
        $this->license        = $_licence_key;
        $this->author         = $_author;

        // Setup hooks
  		  $this->_includes();
  		  $this->_hooks();
  		  $this->_auto_updater();
      }
    }

    /**
     * Include the updater class
     *
     * @return  void
     */
	  private function _includes() {

		  if ( !class_exists( 'EDD_SL_Plugin_Updater' ) ) {
        require_once trailingslashit( PR_LIBS_PATH . "EDD" ) . "EDD_SL_Plugin_Updater.php";
      }
    }

    /**
  	 * Setup hooks
  	 *
  	 * @return  void
  	 */
	  private function _hooks() {

      // Activate license key on settings save
		  add_action( 'init', array( $this, 'activate_license' ), 20, 2 );
		  // Deactivate license key
      add_action( 'init', array( $this, 'deactivate_license' ), 10, 2 );
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
	  public function activate_license() {

      if ( isset( $_POST['item_slug']) && strlen( $_POST['item_slug'] ) ) {
        $item_slug = $_POST['item_slug'];
        if ( isset( $_POST['pr_license_key_' . $item_slug . '_activate'], $_POST['pr_settings']['pr_license_key_' . $item_slug] ) && strlen( $_POST['pr_settings']['pr_license_key_' . $item_slug] ) ) {
          $item_name = $_POST['item_' . $item_slug . '_name'];
          $license = sanitize_text_field( $_POST['pr_settings']['pr_license_key_' . $item_slug] );

          $settings = get_option( 'pr_settings' );
          $api_params = array(
      			'edd_action' => 'activate_license',
      			'license'    => $license,
      			'item_name'  => urlencode( $item_name ),
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
            $this->disables_item( $item_slug );
            $param = urlencode( $response->get_error_message() );
            wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=error&pmcode=failed_activated_license&pmparam=' . $param ) );
            exit;
          }

          $license_data = json_decode( wp_remote_retrieve_body( $response ) );
          if ( !isset( $license_data->license ) || $license_data->license != 'valid' ) {
            $this->disables_item( $item_slug );
            $param = urlencode( __( "The license key is invalid", 'pressroom-license' ) );
            wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=error&pmcode=failed_activated_license&pmparam=' . $param ) );
            exit;
          }

          $this->enables_item( $item_slug, $license_data->license, $license );

          $param = urlencode( __( "The license key is valid", 'pressroom-license' ) );
          wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=updated&pmcode=success_activated_license&pmparam=' . $param ) );
          exit;
	      }
      }
    }

    /**
  	 * Deactivate the license key
  	 *
  	 * @access  public
  	 * @return  void
  	 */
	  public function deactivate_license() {
      if ( isset( $_POST['item_slug']) && strlen( $_POST['item_slug'] ) ) {
        $item_slug = $_POST['item_slug'];
        if ( isset( $_POST['pr_license_key_' . $item_slug . '_deactivate'] ) ) {
          $item_name = $_POST['item_' . $item_slug . '_name'];

    			$api_params = array(
    				'edd_action' => 'deactivate_license',
    				'license'    => $this->license,
    				'item_slug'  => urlencode( $item_name )
    			);

    			$response = wp_remote_get(
    				add_query_arg( $api_params, PR_API_URL ),
    				array(
    					'timeout'   => 15,
    					'sslverify' => false
    				)
    			);

    			if ( is_wp_error( $response ) ) {
    				$param = urlencode( $response->get_error_message() );
            wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=error&pmcode=failed_deactivated_license&pmparam=' . $param ) );
            exit;
          }

    			$license_data = json_decode( wp_remote_retrieve_body( $response ) );
    			if ( $license_data->license == 'deactivated' ) {
            $this->disables_item( $item_slug );
            wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=updated&pmcode=success_deactivated_license' ) );
            exit;
          }
          else if( $license_data->license == 'failed' ) {
            $this->disables_item( $item_slug );
            wp_redirect( admin_url( 'admin.php?page='. $_POST['return_page'] .'&settings-updated=true&pmtype=updated&pmcode=success_deactivated_license' ) );
          }
        }
  		}
	  }

    public function enables_item( $item_slug, $value, $license ) {

      switch( $_POST['type'] ) {
        case 'theme':
          $themes = get_option( 'pressroom_themes' );
          $this->settings['pr_license_key_' . $item_slug] = trim( $license );
          $themes[$item_slug]['active'] = true;
          update_option( 'pr_settings', $this->settings );
          update_option( 'pressroom_themes', $themes );
          break;
        case 'exporter':

          $this->settings['pr_license_key_' . $item_slug] = trim( $license );
          $this->settings['pr_enabled_exporters'][$item_slug]['active'] = true;
          update_option( 'pr_settings', $this->settings );
          update_option( 'pr_valid_license_' . $item_slug, $value );
          break;
      }
    }

    public function disables_item( $item_slug ) {

      switch( $_POST['type'] ) {
        case 'theme':
          $themes = get_option( 'pressroom_themes' );
          $this->settings['pr_license_key_' . $item_slug] = '';
          $themes[$item_slug]['active'] = false;
          delete_option( 'pr_valid_license_' . $item_slug );
          update_option( 'pressroom_themes', $themes );
          update_option( 'pr_settings', $this->settings );
          break;
        case 'exporter':
          $this->settings['pr_license_key_' . $item_slug] = '';
          $this->settings['pr_enabled_exporters'][$item_slug]['active'] = '';
          delete_option( 'pr_valid_license_' . $item_slug );
          update_option( 'pr_settings', $this->settings );
          break;
      }
    }

    /**
     * Validate license
     * @return boolean
     */
    public static function check_license( $item_slug, $item_name, $is_free = false ) {

      if( $is_free ) {
        return true;
      }

      $options = get_option( 'pr_settings' );
      $license = isset( $options['pr_license_key_' . $item_slug] ) ? $options['pr_license_key_' . $item_slug] : '';
      if ( !strlen( $license ) ) {
        return false;
      }

      $api_params = array(
        'edd_action' => 'check_license',
        'license'    => $license,
        'item_name'  => urlencode( $item_name ),
        'url'        => home_url()
      );

      $response = wp_remote_get( add_query_arg( $api_params, PR_API_URL ), array( 'timeout' => 15, 'sslverify' => false ) );

      if ( !is_wp_error( $response ) ) {
        $license_data = json_decode( wp_remote_retrieve_body( $response ) );
        if( $license_data->license == 'valid' ) {
          return true;
        }
      }

      return false;
    }
  }
}
