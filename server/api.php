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
   *	@return void
   */
  public function add_endpoint() {

    add_rewrite_tag('%__pressroom-api%', '([^&]+)');
    add_rewrite_tag( '%editorial_project%', '([^&]+)' );
    add_rewrite_tag( '%edition_name%', '([^&]+)' );
  }

  /**
   * Response Handler
   * This sends a JSON response to the browser
   * @param int $code
   * @param string $msg
   * @return json response;
   */
  protected function send_response( $code, $msg = '' ) {

    status_header( $code );
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
