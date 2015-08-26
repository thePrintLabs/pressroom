<?php
final class PR_Server_Shelf_JSON extends PR_Server_API
{
  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
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
    add_rewrite_rule( '^pressroom-api/shelf/([^&]+)/?$',
                      'index.php?__pressroom-api=shelf_json&editorial_project=$matches[1]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/shelf/([^&]+)/?$',
                      'index.php?__pressroom-api=shelf_json&editorial_project=$matches[2]',
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
    if ( $request && $request == 'shelf_json' ) {
      $this->_action_get_shelf();
    }
  }

  /**
  * Get all editions of the editorial projects and create the shelf json output
  * @return string
  */
  protected function _action_get_shelf() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
    $shelf_path = PR_SHELF_PATH . DS . $eproject_slug . '_shelf.json';
    if( file_exists( $shelf_path ) ) {
      $shelf_json = file_get_contents( $shelf_path );
      if ( $shelf_json ) {
        status_header( 200 );
        header('Content-Type: application/json');
        echo $shelf_json;
      }
    }
    else {
      $this->send_response( 404, 'Bad request. Shelf.json file not found.' );
    }

    exit;
  }
}

$pr_server_shelf = new PR_Server_Shelf_JSON;
