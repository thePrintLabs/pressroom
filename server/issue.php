<?php
final class PR_Server_Issue extends PR_Server_API
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
    add_rewrite_tag( '%issue_name%', '([^&]+)' );
    add_rewrite_rule( 'pressroom-api/issue/([^&]+)/?$',
                      'index.php?__pressroom-api=issue&issue_name=$matches[1]',
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
    if ( $request && $request == 'issue' ) {
      $this->_validate_issue();
    }
  }

  /**
   *
   * @void
   */
  protected function _validate_issue() {

    global $wp;
    $issue = $wp->query_vars['issue_name'];
    if ( !$issue ) {
      $this->send_response( 400, 'Bad request. Please specify an issue name.' );
    }

    $app_id = isset( $_GET['app_id'] ) ? $_GET['app_id'] : false;
    $user_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : false;

    // APPID -> EDITORIAL PROJECT
    // VERIFICARE SE EDIZIONE E' GRATUITA O A PAGAMENTO
    // VERIFICARE PARAMETRI CON ITUNES

    // @TODO: Implement management of multiple connectors
    $itunes_connector = new PR_Connector_iTunes( $app_id, $user_id );
    $receipt = $itunes_connector->retrieve_receipts();
    if ( $receipt ) {

      $data = $itunes_connector->validate_receipt();
      $itunes_connector->save_purchased_editions( $data );
    }
    // // Retrieve issue
    // $result = $file_db->query(
    //   "SELECT * FROM issues
    //   WHERE app_id='$app_id' AND name='$name'"
    // );
    // $issue = $result->fetch(PDO::FETCH_ASSOC);
    // $product_id = $issue['product_id'];
    //
    // $allow_download = false;
    // if ($product_id) {
    //   // Allow download if the issue is marked as purchased
    //   $result = $file_db->query(
    //     "SELECT COUNT(*) FROM purchased_issues
    //     WHERE app_id='$app_id' AND user_id='$user_id' AND product_id='$product_id'"
    //   );
    //   $allow_download = ($result->fetchColumn() > 0);
    // } else if ($issue) {
    //   // No product ID -> the issue is free to download
    //   $allow_download = true;
    // }
    //
    // if ($allow_download) {
    //   $attachment_location = $_SERVER["DOCUMENT_ROOT"] . "/issues/$name.hpub";
    //   if (file_exists($attachment_location)) {
    //     header('HTTP/1.1 200 OK');
    //     header("Cache-Control: public"); // needed for i.e.
    //     header("Content-Type: application/zip");
    //     header("Content-Transfer-Encoding: Binary");
    //     header("Content-Length:".filesize($attachment_location));
    //     header("Content-Disposition: attachment; filename=file.zip");
    //     readfile($attachment_location);
    //     $log->LogDebug("Downloading $attachment_location");
    //   } else {
    //     header('HTTP/1.1 404 Not Found');
    //     $log->LogInfo("Issue not found: $attachment_location");
    //   }
    // } else {
    //   header('HTTP/1.1 403 Forbidden');
    //   $log->LogInfo("Download not allowed: $name");
    // }

  }
}

$pr_server_issue = new PR_Server_Issue;
