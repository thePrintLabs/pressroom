<?php
final class PR_Server_APNS_Token extends PR_Server_API
{
  public function __construct() {

    add_action( 'init', array( $this, 'add_endpoint' ), 10 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *	@void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( 'pressroom-api/apns_token/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=apns_token&app_id=$matches[1]&user_id=$matches[2]&editorial_project=$matches[3]',
                      'top' );
  }

  /**
   * Parse HTTP request
   * Must extend the parent class
   *
   *	@return die if API request
   */
  public function parse_request() {

    global $wp;
    $request = parent::parse_request();
    if ( $request && $request == 'apns_token' ) {
      $this->_action_register_token();
    }
  }

  /**
   *
   * @void
   */
  protected function _action_register_token() {

    global $wp;
    parent::validate_request();
    $eproject_slug = $wp->query_vars['editorial_project'];
    $app_id = $wp->query_vars['app_id'];
    $user_id = $wp->query_vars['user_id'];
    $environment = isset( $_POST['environment'] ) ? $_POST['environment'] : 'production';
    $device_token = $_POST['apns_token'];

    if ( !isset( $_POST['apns_token'] ) || !strlen( $_POST['apns_token'] ) ) {
      $this->send_response( 400, __( "Bad request. APNS Token doesn't exist.", 'pressroom' ) );
    }

    $eproject = TPL_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$eproject ) {
      $this->send_response( 404, __( "Not found. Editorial project not found.", 'pressroom' ) );
    }

    $push_service = TPL_Editorial_Project::get_config( $eproject->term_id , 'pr_push_service' );
    $push_app_id = TPL_Editorial_Project::get_config( $eproject->term_id , 'pr_push_api_app_id' );
    $push_app_key = TPL_Editorial_Project::get_config( $eproject->term_id , 'pr_push_api_app_id' );

    switch ( $push_service ) {
      case 'parse':
        $params = json_encode( array(
          'deviceType'  => 'ios',
          'deviceToken' => $device_token,
          'channels'    => array( $eproject->slug ),
        ));

        $response = wp_remote_post( 'https://api.parse.com/1/installations', array(
          'body'      => $params,
          'headers'   => array(
            'X-Parse-Application-Id'  =>  $push_app_id,
            'X-Parse-REST-API-Key'    =>  $push_app_key,
            'Content-Type'  => 'application/json',
          ),
        ));

        if ( is_wp_error( $response ) || !isset( $response['body'] ) ) {
          $this->send_response( 400, __( "Invalid response data.", 'pressroom' ) );
        }

        $data = json_decode( $response['body'] );
        if ( !is_object($data) || !isset( $data->objectId ) ) {
          $this->send_response( 400, __( "Token registration failed.", 'pressroom' ) );
        }
        break;
    }
  }
}
$pr_server_apns_token = new PR_Server_APNS_Token;
