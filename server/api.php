<?php
abstract class PR_Server_API
{
  /**
   * Sniff Requests
   * If $_GET['__pressroom-api'] is set, we kill WP and serve up pug bomb awesomeness
   *
   *	@return die if API request
   */
  public function parse_request() {

    global $wp;
    if ( isset( $wp->query_vars['__pressroom-api'] ) ) {
      return $wp->query_vars['__pressroom-api'];
    }

    return false;
  }

  /**
   * Add API Endpoint
   *
   *	@void
   */
  public function add_endpoint() {

    add_rewrite_tag('%__pressroom-api%', '([^&]+)');
    add_rewrite_tag( '%app_id%', '([^&]+)' );
    add_rewrite_tag( '%user_id%', '([^&]+)' );
    add_rewrite_tag( '%editorial_project%', '([^&]+)' );
    add_rewrite_tag( '%edition_name%', '([^&]+)' );

  }

  /**
   * Check required params in the query string
   *
   * @void
   */
  public function validate_request() {

    global $wp;
    if ( !isset( $wp->query_vars['app_id'] ) || !strlen( $wp->query_vars['app_id'] ) ) {
      $this->send_response( 400, "Bad request. App identifier doesn't exist." );
    }
    elseif ( !isset( $wp->query_vars['user_id'] ) || !strlen( $wp->query_vars['user_id'] ) ) {
      $this->send_response( 400, "Bad request. User identifier doesn't exist." );
    }
    elseif ( !isset( $wp->query_vars['editorial_project'] ) ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
  }

  /**
   * Response Handler
   * This sends a JSON response to the browser
   * @param int $code
   * @param string $msg
   * @param int $force_header
   * @return json response;
   */
  protected function send_response( $code, $msg = '', $force_header = true ) {

    if ( $force_header ) {
      status_header( $code );
    }

    if ( $code == 200 ) {
      wp_send_json_success( $msg );
    }
    else {
      wp_send_json_error( array(
        'code' => $code,
        'message' => $msg
      ));
    }
  }
}
