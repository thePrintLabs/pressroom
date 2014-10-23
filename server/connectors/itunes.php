<?php

final class PR_Connector_iTunes {

  const SANDBOX_URL     = "https://sandbox.itunes.apple.com/verifyReceipt";
  const PRODUCTION_URL  = "https://buy.itunes.apple.com/verifyReceipt";

  public function __construct() {}

  public static function get_shared_secret() {
    //return '16d33617f096456480ef1049e6263d5e';
    return get_option( 'pr_itunes_shared_secret' );
  }

  /*
   * Verify a base64-encoded receipt with the App Store.
   * In case verification is successful, a nested hash representing the data
   * returned from the App Store will be returned.
   * In case of verification error, exceptions will be raised.
   */
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
