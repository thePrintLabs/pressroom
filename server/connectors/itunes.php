<?php
/*
 * Verify a base64-encoded receipt with the App Store.
 * In case verification is successful, a nested hash representing the data
 * returned from the App Store will be returned.
 * In case of verification error, exceptions will be raised.
 */
final class PR_iTunes_Connector extends PR_Server_API {

  const SANDBOX_URL     = "https://sandbox.itunes.apple.com/verifyReceipt";
  const PRODUCTION_URL  = "https://buy.itunes.apple.com/verifyReceipt";

  public function __construct() {

    add_action( 'init', array( $this, 'add_endpoint' ), 0 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 0 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
	 *	@void
	 */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_tag('%itunes_action%', '([^&]+)');
    add_rewrite_tag('%itunes_data%', '([^&]+)');
    add_rewrite_rule( 'pressroom-api/itunes_connect/(validate_receipt)/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_connect&itunes_action=$matches[1]&itunes_data=$matches[2]',
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
    if ( $request && $request == 'itunes_connect' ) {
      $this->handle_request();
    }
	}

	/**
	 * Handle Requests
	 *	This is where we send off for an intense pug bomb package
	 *
	 *	@return void
	 */
  protected function handle_request() {

    global $wp;
    $action = $wp->query_vars['itunes_action'];
    if ( !$action ) {
      $this->send_response(400, 'Bad request. Please specify an action.');
    }
    switch ( $action ) {

      case 'validate_receipt':
        $data = $wp->query_vars['itunes_data'];
        $response = $this->validate_receipt( $data );
        $this->send_response( 200, json_decode( $response ) );
        break;

      default:
        $this->send_response(400, 'Bad request. Please specify a valid action.');
        break;
    }
  }

  public static function get_shared_secret() {
    //return '16d33617f096456480ef1049e6263d5e';
    return get_option( 'pr_itunes_shared_secret' );
  }

  public function validate_receipt( $base64_receipt ) {

    $shared_secret = self::get_shared_secret();
    if ( !$shared_secret ) {
      $this->send_response( 500, 'Must assign a shared secret' );
    }

    $params = json_encode( array(
      'receipt-data'  => $base64_receipt,
      'password'      => $shared_secret
    ));

    $response = wp_remote_post( self::END_POINT_SANDBOX, array(
      'body'      => $params,
      'headers'   => array(
        'Content-Type'  => 'application/json',
      ),
    ));

    if ( is_wp_error( $response ) || !isset( $response['body'] ) ) {
      $this->send_response( 502, 'Invalid iTunes response data.' );
    }

    $data = json_decode( $response['body'] );
    if ( !is_object($data) || !isset( $data->status ) ) {
      $this->send_response( 502, 'Invalid iTunes response data.' );
    }

    switch ( $data->status ) {
      case 21000:
        $this->send_response( 502, 'Invalid iTunes response (21000): The App Store could not read the JSON object you provided.' );
      case 21002:
        $this->send_response( 502, 'Invalid iTunes response (21002): The data in the receipt-data property was malformed or missing.' );
      case 21003:
        $this->send_response( 502, 'Invalid iTunes response (21003): The receipt could not be authenticated.' );
      case 21004:
        $this->send_response( 502, 'Invalid iTunes response (21004): The shared secret you provided does not match the shared secret on file for your account.' );
      case 21005:
        $this->send_response( 502, 'Invalid iTunes response (21005): The receipt server is not currently available.' );
      //21007   This receipt is a sandbox receipt, but it was sent to the production server.
      //21008   This receipt is a production receipt, but it was sent to the sandbox server.
      default:
        die('here');
    }

    return $data;
  }

  // Mark issues as purchased, based on the app_store_data parameter.
  //
  // This function will examine a receipt verification response coming from the
  // App Store and mark as purchased all the issues it covers.
  // This function should be passed a verification response for an
  // auto-renewable subscription.
  function markIssuesAsPurchased($app_store_data, $app_id, $user_id) {
    global $log, $file_db;

    $receipt = $app_store_data->receipt;

    $start = intval($receipt->purchase_date_ms) / 1000;
    if ($data->status == 0) {
      $finish = intval($data->latest_receipt_info->expires_date) / 1000;
    } else if ($data->status == 21006) {
      $finish = intval($data->latest_expired_receipt_info->expires_date) / 1000;
    }

    $result = $file_db->query(
      "SELECT product_id FROM issues
      WHERE app_id='$app_id'
      AND product_id NOT NULL
      AND `date` > datetime($start, 'unixepoch')
      AND `date` < datetime($finish, 'unixepoch')"
    );
    $product_ids_to_mark = $result->fetchAll(PDO::FETCH_COLUMN);

    $insert = "INSERT OR IGNORE INTO purchased_issues (app_id, user_id, product_id)
      VALUES ('$app_id', '$user_id', :product_id)";
    $stmt = $file_db->prepare($insert);
    foreach ($product_ids_to_mark as $key => $product_id) {
      $stmt->bindParam(':product_id', $product_id);
      $stmt->execute();
    }
  }

  // Mark a single issue as purchased.
  //
  // This function will mark the issue with the given product_id as purchased.
  function markIssueAsPurchased($product_id, $app_id, $user_id) {
    global $log, $file_db;

    $file_db->query(
      "INSERT OR IGNORE INTO purchased_issues (app_id, user_id, product_id)
      VALUES ('$app_id', '$user_id', '$product_id')"
    );
  }
}

$pr_itunes_connector = new PR_iTunes_Connector;

?>
