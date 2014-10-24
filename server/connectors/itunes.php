<?php

final class PR_Connector_iTunes extends PR_Server_API {

  const SANDBOX_URL     = "https://sandbox.itunes.apple.com/verifyReceipt";
  const PRODUCTION_URL  = "https://buy.itunes.apple.com/verifyReceipt";

  public $app_id;
  public $user_id;
  public $base64_receipt;
  public $receipt_data;
  public $eproject;
  public $environment = 'production';

  /**
   * iTunes connector
   * @param string $app_id
   * @param string $user_id
   */
  public function __construct( $app_id = false, $user_id = false ) {

    if ( $app_id && $user_id ) {
      $this->app_id = $app_id;
      $this->user_id = $user_id;
    }
    else {
      add_action( 'init', array( $this, 'add_endpoint' ), 10 );
      add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
    }
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *	@void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_tag( '%editorial_project%', '([^&]+)' );
    add_rewrite_rule( 'pressroom-api/itunes_purchase_confirmation/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchase_confirmation&editorial_project=$matches[1]',
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
    if ( $request && $request == 'itunes_purchase_confirmation' ) {
      $this->_purchase_confirmation();
    }
  }

  /**
   * Get iTunes shared secret
   * @return string
   */
  public function get_shared_secret() {

    return TPL_Editorial_Project::get_config( $this->eproject->term_id , '_pr_itunes_secret' );
  }

  /**
   * Retrieve latest receipts
   *
   * @return string or void
   */
  public function retrieve_receipts() {

    global $wpdb;
    $sql = "SELECT base64_receipt FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " WHERE app_id = %s";
    $sql.= "AND user_id = %s AND type = 'auto-renewable-subscription' ORDER BY transaction_id DESC";
    $this->base64_receipt = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $this->user_id ), 0, 0 );
    return $this->base64_receipt;
  }

  /**
   * Save base64-encoded receipt from App Store to db.
   * @param string $transaction_id
   * @param string $product_id
   * @param string $purchase_type
   * @return boolean
   */
  public function save_receipt( $transaction_id, $product_id, $purchase_type ) {

    global $wpdb;
    $sql = "INSERT IGNORE INTO " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " SET ";
    $sql.= "transaction_id = %s, app_id = %s, user_id = %s, product_id = %s, type = %s, base64_receipt = %s";
    return $wpdb->query( $wpdb->prepare( $sql, $transaction_id, $this->app_id, $this->user_id, $product_id, $purchase_type, $this->base64_receipt ) );
  }

  /*
   * Verify a base64-encoded receipt with the App Store.
   * In case verification is successful, a nested hash representing the data
   * returned from the App Store will be returned.
   * In case of verification error, exceptions will be raised.
   */
  public function validate_receipt() {

    $shared_secret = $this->get_shared_secret();
    if ( !$shared_secret ) {
      $this->send_response( 500, 'Must assign a shared secret' );
    }

    $params = json_encode( array(
      'receipt-data'  => $this->base64_receipt,
      'password'      => $shared_secret
    ));

    $response = wp_remote_post( $this->environment == 'debug' ? self::SANDBOX_URL : self::PRODUCTION_URL, array(
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
      case 21007:
        $this->send_response( 502, 'Invalid iTunes response (21007): This receipt is a sandbox receipt, but it was sent to the production server.' );
      case 21008:
        $this->send_response( 502, 'Invalid iTunes response (21008): This receipt is a production receipt, but it was sent to the sandbox server.' );
      default:
        break;
    }
    return $data;
  }

  /**
   * Save the receipt and the editions purchased into the db.
   * @param  [type] $receipt       [description]
   * @param  [type] $purchase_type [description]
   * @return [type]                [description]
   */
  public function save_receipt_and_purchases( $receipt, $purchase_type ) {

    $this->save_receipt( $receipt->transaction_id, $receipt->product_id, $purchase_type );
    switch ( $purchase_type ) {

      case 'auto-renewable-subscription':
        $this->save_purchased_editions( $receipt );
        $this->send_response( 200 );
        break;

      case 'issue':
        $this->save_purchased_edition( $receipt->product_id );
        $this->send_response( 200 );
        break;

      default:
        $this->send_response( 404, "Not found." );
        break;
    }
  }

  /**
   * Save a purchased edition into the db
   * @param  string $product_id
   * @return boolean
   */
  public function save_purchased_edition( $product_id ) {

    global $wpdb;
    $sql = "INSERT IGNORE INTO " . $wpdb->prefix . TPL_TABLE_PURCHASED_ISSUES . " SET
    app_id = %s, user_id = %s, product_id = %s";
    return $wpdb->query( $wpdb->prepare( $sql, $this->app_id, $this->user_id, $product_id ) );
  }

  /**
   * Save multiple purchased editions into the db
   * @param  object $data
   * @void
   */
  public function save_purchased_editions( $single_receipt ) {

    //@TODO: INSERIRE IL CONTROLLO SULLA TIPOLOGIA DI ABBONAMENTO ALL O DALL'ULTIMA EDIZIONE
    global $wpdb;
    $data = $this->receipt_data;

    $transaction_id = $single_receipt->transaction_id;
    $from_date = (int)$single_receipt->purchase_date_ms / 1000;
    if ( $data->status == 0 ) {
      if ( is_array( $data->latest_receipt_info ) ) {
        foreach ( $data->latest_receipt_info as $receipt_info ) {
          if ( $receipt_info->transaction_id == $transaction_id ) {
            $to_date = (int)$receipt_info->expires_date / 1000;
            break;
          }
        }
      }
      else {
        $to_date = (int)$data->latest_receipt_info->expires_date / 1000;
      }
    }
    elseif ( $data->status == 21006 ) {
      if ( is_array( $data->latest_expired_receipt_info ) ) {
        foreach ( $data->latest_expired_receipt_info as $expired_receipt_info ) {
          if ( $expired_receipt_info->transaction_id == $transaction_id ) {
            $to_date = (int)$expired_receipt_info->expires_date / 1000;
            break;
          }
        }
      }
      else {
        $to_date = (int)$data->latest_expired_receipt_info->expires_date / 1000;
      }
    }

    $editions = TPL_Editorial_Project::get_editions_in_range( $this->eproject, $from_date, $to_date );
    foreach ( $editions as $edition ) {

      $this->save_purchased_edition( $edition->slug );
    }
  }

  /**
   *
   * @void
   */
  protected function _purchase_confirmation() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
    elseif ( !isset( $_POST ) || empty( $_POST ) ) {
      $this->send_response( 400, "Bad request. Request doesn't contains required data." );
    }
    elseif ( !isset( $_POST['app_id'], $_POST['user_id']) ) {
      $this->send_response( 400, "Bad request. App identifier and/or user identifier doesn't exist." );
    }
    elseif ( !isset( $_POST['receipt_data'], $_POST['type']) || !strlen($_POST['receipt_data']) || !strlen($_POST['type']) ) {
      $this->send_response( 400, "Bad request. Receipt data and/or purchase type doesn't exist." );
    }

    $this->eproject = TPL_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$this->eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $purchase_type = $_POST['type'];
    $this->app_id = $_POST['app_id'];
    $this->user_id = $_POST['user_id'];
    $this->environment = $_POST['environment'];

    // @note: the replacement ' ' => '+' is used to support
    // the new receipt validation method implemented from iOS7 version
    $this->base64_receipt = str_replace( ' ', '+', stripcslashes( $_POST['receipt_data'] ) );

    $this->receipt_data = $this->validate_receipt();
    if ( $this->receipt_data ) {
      $receipt = $this->receipt_data->receipt;
      // @note: checks to support
      // the new receipt validation method implemented from iOS7 version
      if ( isset( $receipt->in_app ) ) {
        foreach ( $receipt->in_app as $single_receipt ) {
          $this->save_receipt_and_purchases( $single_receipt, $purchase_type );
        }
      }
      else {
        $this->save_receipt_and_purchases( $receipt, $purchase_type );
      }
    }
  }
}

$pr_server_connector_itunes = new PR_Connector_iTunes;
