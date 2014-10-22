<?php
/*
 * Verify a base64-encoded receipt with the App Store.
 * In case verification is successful, a nested hash representing the data
 * returned from the App Store will be returned.
 * In case of verification error, exceptions will be raised.
 */
final class PR_iTunes_Connector implements PR_Connectors {

  const END_POINT = "https://sandbox.itunes.apple.com/verifyReceipt";

  public function __construct() {

    add_action( 'init', array( $this, 'add_endpoint' ), 0 );
    add_filter( 'query_vars', array( $this, 'add_query_vars' ), 0 );
		add_action( 'parse_request', array( $this, 'sniff_requests' ), 0 );
  }

  /**
   * Add public query vars
	 * @param array - List of current public query vars
	 * @return array
	 */
	public function add_query_vars( $vars ) {

    array_push( $vars, '__pressroom-api', 'itunes_connect' );
		return $vars;
	}

  /**
   * Add API Endpoint
   *
	 *	@return void
	 */
  public function add_endpoint() {
    add_rewrite_rule( '^pressroom-api/itunes_connect/?([0-9]+)?/?','index.php?__pressroom-api=1&itunes_connect=$matches[1]', 'top' );
  }

	/**
	 * Sniff Requests
	 * If $_GET['__pressroom-api'] is set, we kill WP and serve up pug bomb awesomeness
	 *
   *	@return die if API request
	 */
	public function sniff_requests() {

    global $wp;
		if ( isset( $wp->query_vars['__pressroom-api'] ) ) {
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
    die( var_dump( $wp->query_vars));
		$action = $wp->query_vars['itunes_connect'];
		if ( !$action ) {
      $this->send_response('Your request is not valid. Please specify an action');
    }
    switch ( $action ) {
      case 'verify_receipt':
        $this->verify_receipt(   );
        break;
      default:
        break;
    }


		// if($pugs)
		// 	$this->send_response('200 OK', json_decode($pugs));
		// else
		// 	$this->send_response('Something went wrong with the pug bomb factory');
	}



  public static function get_shared_secret() {
    return get_option( 'pr_itunes_shared_secret' );
  }

  public function verify_receipt( $base64_receipt ) {

    $shared_secret = self::get_shared_secret();

    $response = wp_remote_post( END_POINT, array(
      'receipt-data'  => $base64_receipt,
      'password'      => $shared_secret
    ));

    if ( is_wp_error( $response ) ) {
      $this->send_response( 500, 'Invalid response data' );
    }

    $data = json_decode( $response );
    //$log->LogDebug("Store response: ". var_export($data, true));

    if ( !is_object($data) ) {
      $this->send_response( 500, 'Invalid response data' );
    }

    if ( !isset( $data->status ) || ( $data->status != 0 && $data->status != 21006 ) ) {

      $product_id = $data->receipt->product_id;
      $log->LogError("Invalid receipt for $product_id : status " . $data->status);
      $this->send_response( 500, 'Invalid receipt' );
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

?>
