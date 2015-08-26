<?php
final class PR_Server_Issue extends PR_Server_API
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
    add_rewrite_rule( '^pressroom-api/edition/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=edition&editorial_project=$matches[1]&edition_name=$matches[2]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/edition/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=edition&editorial_project=$matches[2]&edition_name=$matches[3]',
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
    if ( $request && $request == 'edition' ) {
      $this->_action_download_issue();
    }
  }

  /**
   *
   * @void
   */
  protected function _action_download_issue() {

    global $wp;
    $edition_slug = $wp->query_vars['edition_name'];
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$edition_slug || !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an issue name and/or an editorial project.' );
    }
    elseif ( !isset( $_GET['app_id'], $_GET['user_id']) ) {
      $this->send_response( 400, "Bad request. App identifier and/or user identifier doesn't exist." );
    }

    $eproject = PR_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $edition = PR_Edition::get_by_slug( $edition_slug );
    if( !$edition ) {
      $this->send_response( 404, "Not found. Edition not found." );
    }

    $app_id = isset( $_GET['app_id'] ) ? $_GET['app_id'] : false;
    $user_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : false;
    $environment = isset( $_GET['environment'] ) ? $_GET['environment'] : 'production';

    $allow_download = false;
    $edition_hpub = get_post_meta( $edition->ID, '_pr_edition_hpub_' . $eproject->term_id, true );
    $edition_type = get_post_meta( $edition->ID, '_pr_edition_free', true );

    if ( $edition_type == 0 ) {
      do_action_ref_array( 'pr_issue_download', array( &$allow_download, $app_id, $user_id, $environment, $edition, $eproject ) );
    }
    else {
      $allow_download = true;
    }

    if ( $allow_download && $edition_hpub && file_exists( $edition_hpub ) ) {

      header( "HTTP/1.1 200 OK" );
      header( "Pragma: public" );
      header( "Expires: 0" );
      header( "Cache-Control: must-revalidate, post-check=0, pre-check=0" );
      header( "Cache-Control: public" );
      header( "Content-Description: File Transfer" );
      header( "Content-Type: application/zip" );
      header( "Content-Transfer-Encoding: Binary" );
      header( "Content-Length:" . filesize( $edition_hpub ) );
      header( "Content-Disposition: attachment; filename=" . basename( $edition_hpub ) );
      readfile( $edition_hpub );

      // Record download
      if ( isset( $_SERVER['REQUEST_METHOD'] ) && $_SERVER['REQUEST_METHOD'] == 'GET' ) {
        PR_Stats::increment_counter( 'download_edition', $edition->ID );
      }
    }
    else {
      $this->send_response( 404, "Not found. Edition not found." );
    }
  }
}

$pr_server_issue = new PR_Server_Issue;
