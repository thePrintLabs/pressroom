<?php

final class PR_Connector_Authentication extends PR_Server_API {

  public $account_password;
  public $account_username;
  public $app_id;
  public $user_id;
  public $eproject;

  protected $_error_msg;

  const AUTHENTICATION_SANDBOX_STATUS = 1;
  const AUTHENTICATION_PRODUCTION_STATUS = 2;

  /**
   * Authentication connector
   * @param string $app_id
   * @param string $user_id
   * @param string $environment
   */
  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
    add_action( 'init', array( $this, 'add_endpoint' ), 10 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
    add_action( 'pr_issue_download', array( $this, 'validate_token_on_download' ), 20, 6 );
    add_action( 'pr_add_extra_options', array( $this, 'add_authentication_options' ), 10 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *  @void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( '^pressroom-api/authentication/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=authentication&app_id=$matches[1]&user_id=$matches[2]&editorial_project=$matches[3]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/authentication/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=authentication&app_id=$matches[2]&user_id=$matches[3]&editorial_project=$matches[4]',
                      'top' );
  }

  /**
   * Parse HTTP request
   * Must extend the parent class
   *
   *  @return die if API request
   */
  public function parse_request() {

    global $wp;
    $request = parent::parse_request();
    if ( $request ) {
      if ( $request == 'authentication' ) {
        $this->_action_account_login();
      }
    }
  }

  /**
   * Save authorization token into the db.
   * @param string $token
   * @param int $expire_time
   * @return mixed
   */
  public function save_auth_token( $token, $expire_time ) {

    global $wpdb;
    $sql = "INSERT IGNORE INTO " . $wpdb->prefix . PR_TABLE_AUTH_TOKENS . " SET ";
    $sql.= "app_id = %s, user_id = %s, access_token = %s, created_time = %d, expires_in = %d";
    return $wpdb->query( $wpdb->prepare( $sql, $this->app_id, $this->user_id, $token, time(), $expire_time ) );
  }

  /**
   * Delete authorization tokens from db.
   * @void
   */
  public function delete_auth_tokens() {

    global $wpdb;
    $wpdb->delete( $wpdb->prefix . PR_TABLE_AUTH_TOKENS, array( 'app_id' => $this->app_id, 'user_id' => $this->user_id ), array( '%s', '%s' ) );
  }

  /*
  * Verify the access token
  * @param string $token
  * @return string or boolean false
  */
  public function validate_token( $token ) {

    global $wpdb;
    $sql = "SELECT access_token FROM " . $wpdb->prefix . PR_TABLE_AUTH_TOKENS . " ";
    $sql.= "WHERE app_id = %s AND user_id = %s AND ( created_time + expires_in ) > UNIX_TIMESTAMP()";
    $access_token = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $this->user_id ) );
    if ( $access_token ) {
      return $access_token;
    }
    return false;
  }

  /**
   * Validate token on issue download
   * @param  boolean $allow_download
   * @param  string $app_id
   * @param  string $user_id
   * @param  string $environment
   * @param  object $edition
   * @param  object $eproject
   * @void
   */
  public function validate_token_on_download( &$allow_download, $app_id, $user_id, $environment, $edition, $eproject ) {

    if ( isset( $_GET['access_token'] ) ) {
      $this->app_id = $app_id;
      $this->user_id = $user_id;
      $this->environment = $environment;
      if ( $this->validate_token( $_GET['access_token'] ) ) {
        $allow_download = true;
      }
    }
  }

  public function add_authentication_options() {

    add_settings_section(
      'pr_authentication_section',
      '',
      array( $this, 'pr_add_authentication_endpoint' ),
      'pressroom'
    );

    add_settings_field(
      'pr_authentication_environment',
      __( 'Authentication environment', 'pressroom' ),
      array( $this, 'pr_authentication_environment' ),
      'pressroom',
      'pr_authentication_section'
    );

    add_settings_field(
      'pr_authentication_sandbox_url',
      __( 'Sandbox url', 'pressroom' ),
      array( $this, 'pr_sandbox_url' ),
      'pressroom',
      'pr_authentication_section'
    );

    add_settings_field(
      'pr_authentication_production_url',
      __( 'Production endpoint url', 'pressroom' ),
      array( $this, 'pr_production_url' ),
      'pressroom',
      'pr_authentication_section'
    );
  }

  public function pr_add_authentication_endpoint() {

    echo __( '<hr/><h2>Authentication</h2>', 'pressroom' );
  }

  public function pr_sandbox_url() {

    $options = get_option( 'pr_settings' );
    $default = get_site_url() . DS . 'api_account_login';
    $value = isset( $options['pr_sandbox_url'] ) ? $options['pr_sandbox_url'] : $default;
    $html = '<input size="70" type="text" placeholder="' . get_site_url() . DS . 'api_account_login' . '" name="pr_settings[pr_sandbox_url]" value="' . $value . '">';
    echo $html;
  }

  public function pr_production_url() {

    $options = get_option( 'pr_settings' );
    $default = get_site_url() . DS . 'api_account_login';
    $value = isset( $options['pr_production_url'] ) ? $options['pr_production_url'] : $default;
    $html = '<input size="70" type="text" placeholder="' . get_site_url() . DS . 'api_account_login' . '" name="pr_settings[pr_production_url]" value="' . $value . '">';
    echo $html;
  }

  public function pr_authentication_environment() {

    $options = get_option( 'pr_settings' );
    $value = isset( $options['pr_authentication_environment'] ) ? $options['pr_authentication_environment'] : "";
    $html = '<input id="pr_sandbox" type="radio" value="1" name="pr_settings[pr_authentication_environment]" '.( $value == self::AUTHENTICATION_SANDBOX_STATUS ? "checked" : "" ).'>' . '<label for="pr_sandbox">Sandbox</label> ';
    $html .= '<input id="pr_production" type="radio" value="2" name="pr_settings[pr_authentication_environment]" '.( $value == self::AUTHENTICATION_PRODUCTION_STATUS ? "checked" : "" ).'>' . '<label for="pr_production">Production</label>';
    echo $html;
  }

  /**
   * Client will call this API endpoint
   * to send the login credential to the remote server.
   * @return json string
   */
  protected function _action_account_login() {

    global $wp;
    parent::validate_request();
    $eproject_slug = $wp->query_vars['editorial_project'];

    if ( !isset( $_POST['username'], $_POST['password']) || !strlen($_POST['username']) || !strlen($_POST['password']) ) {
      $this->send_response( 400, "Bad request. Username and/or password doesn't exist." );
    }

    $this->eproject = PR_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$this->eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $this->app_id = $wp->query_vars['app_id'];
    $this->user_id = $wp->query_vars['user_id'];

    $this->environment = isset( $_POST['environment'] ) ? $_POST['environment'] : 'production';
    $this->account_username = $_POST['username'];
    $this->account_password = $_POST['password'];

    $data = $this->_sendRequest();
    if ( !$data ) {
      $this->send_response( 500, $this->_error_msg, false );
    }

    $status = !empty( $data->subscriptions ) ? 'subscribed' : 'nosubscription';

    // if ( $status == 'nosubscription' ) {
    //   $this->send_response( 500, __("Not found a valid subscription"), false );
    // }
    // else {

      $max_expiry_time = 3600 * 24 * 7; // @TODO: Inserire controllo data scadenza abbonamento

      $access_token = bin2hex(openssl_random_pseudo_bytes(16));
      $this->delete_auth_tokens();
      $this->save_auth_token( $access_token, $max_expiry_time );

      $params = array(
        'email'         => $data->email,
        'status'        => $status,
        'subscriptions' => $data->subscriptions,
        'access_token'  => $access_token,
        'expires_in'    => $max_expiry_time
      );

      $this->send_response( 200, $params );
    // }
  }

  /**
   * Send a cUrl request to remote server
   * @return object or boolean false
   */
  protected function _sendRequest() {

    $options = get_option( 'pr_settings' );
    $url = $options['pr_authentication_environment'] == self::AUTHENTICATION_PRODUCTION_STATUS ? $options['pr_production_url'] : $options['pr_sandbox_url'];
    $params = json_encode( array(
      'username'  => $this->account_username,
      'password'  => $this->account_password
    ));

    $response = wp_remote_post( $url, array(
      'body' => $params,
      'sslverify' => false,
      'headers'   => array(
        'Content-Type'  => 'application/json',
      ),
	  ));

    if ( is_wp_error( $response ) ) {
      $this->_error_msg = $response->get_error_message();
      return false;
    } else {
      $body = json_decode( wp_remote_retrieve_body( $response ) );
      if ( isset($body->login) && $body->login == 'success' ) {
        return $body;
      }
      $this->_error_msg = $body->error;
      return false;
    }
  }
}

$pr_server_connector_authentication = new PR_Connector_Authentication;
