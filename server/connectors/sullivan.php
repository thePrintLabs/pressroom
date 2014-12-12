<?php

final class PR_Connector_Sullivan extends PR_Server_API {

  const SANDBOX_URL     = "https://dev.ilmanifesto.info/api_account_login";
  const PRODUCTION_URL  = "https://dev.ilmanifesto.info/api_account_login";//"https://ilmanifesto.info/api_account_logout";

  public $account_password;
  public $account_username;
  public $app_id;
  public $user_id;
  public $eproject;
  public $environment = 'production';

  protected $_error_msg;

  /**
   * Sullivan connector
   * @param string $app_id
   * @param string $user_id
   * @param string $environment
   */
  public function __construct( $app_id = false, $user_id = false, $environment = 'production' ) {

    if ( $app_id && $user_id ) {
      $this->app_id = $app_id;
      $this->user_id = $user_id;
      $this->environment = $environment;
    }
    else {
      add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
      add_action( 'init', array( $this, 'add_endpoint' ), 10 );
      add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
    }
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *  @void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( 'pressroom-api/sullivan_login/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=sullivan_login&app_id=$matches[1]&user_id=$matches[2]&editorial_project=$matches[3]',
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
      if ( $request == 'sullivan_login' ) {
        $this->_action_account_login();
      }
    }
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

    // $this->app_id = $wp->query_vars['app_id'];
    // $this->user_id = $wp->query_vars['user_id'];
    $this->environment = isset( $_POST['environment'] ) ? $_POST['environment'] : 'production';
    $this->account_username = $_POST['username'];
    $this->account_password = $_POST['password'];

    $data = $this->_sendRequest();
    if ( !$data ) {
      $this->send_response( 500, array( 'error' => $this->_error_msg ), false );
    }

    $status = !empty( $data->subscriptions ) ? 'subscribed' : 'nosubscription';
    $params = array(
      'email'         => $data->email,
      'status'        => $status,
      'subscriptions' => $data->subscriptions
    );

    $this->send_response( 200, $params );
  }

  /**
   * Send a cUrl request to remote server
   * @return object or boolean false
   */
  protected function _sendRequest() {

    $url = $this->environment == 'production' ? self::PRODUCTION_URL : self::SANDBOX_URL;
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

$pr_server_connector_sullivan = new PR_Connector_Sullivan;
