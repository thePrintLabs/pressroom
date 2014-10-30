<?php

final class PR_Connector_iTunes extends PR_Server_API {

  const SANDBOX_URL     = "https://sandbox.itunes.apple.com/verifyReceipt";
  const PRODUCTION_URL  = "https://buy.itunes.apple.com/verifyReceipt";

  public $app_id;
  public $user_id;
  public $base64_receipt;
  public $eproject;
  public $environment = 'production';

  /**
   * iTunes connector
   * @param string $app_id
   * @param string $user_id
   * @param string $environment
   */
  public function __construct( $app_id = false, $user_id = false, $environment = 'production' ) {

    if ( $app_id && $user_id ) {
      $this->app_id = $app_id;
      $this->user_id = $user_id;
      $this->environment = $environment;
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
   *  @void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( 'pressroom-api/itunes_purchase_confirmation/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchase_confirmation&editorial_project=$matches[1]',
                      'top' );
    add_rewrite_rule( 'pressroom-api/itunes_purchases_list/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchases_list&editorial_project=$matches[1]',
                      'top' );
  }

  /**
   * Parse HTTP request
   * Must extend the parent class
   *
   *  @return die if API request
   */
  public function parse_request() {

    global $wp;
    $request = parent::parse_request();
    if ( $request ) {
      if ( $request == 'itunes_purchase_confirmation' ) {
        $this->_action_purchase_confirmation();
      } elseif ( $request == 'itunes_purchases_list' ) {
        $this->_action_purchases_list();
      }
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
   * Save base64-encoded receipt from App Store to db.
   * @param string or boolean false $transaction_id
   * @return int
   */
  public function save_receipt( $transaction_id ) {

    global $wpdb;
    // Old receipt validation method
    if ( $transaction_id ) {
      $sql = "SELECT receipt_id FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " ";
      $sql.= "WHERE app_bundle_id = %s AND device_id = %s AND transaction_id = %s";
      $receipt_id = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $this->user_id, $transaction_id ) );
      if ( $receipt_id ) {
        $sql = "UPDATE " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " SET base64_receipt = %s WHERE receipt_id = %d";
        $wpdb->query( $wpdb->prepare( $sql, $this->base64_receipt, $receipt_id ) );
      }
      else {
        $sql = "INSERT INTO " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " SET ";
        $sql.= "app_bundle_id = %s, device_id = %s, transaction_id = %s, base64_receipt = %s";
        $wpdb->query( $wpdb->prepare( $sql, $this->app_id, $this->user_id, $transaction_id, $this->base64_receipt ) );
        $receipt_id = $wpdb->insert_id;
      }
    }
    else {
      $sql = "SELECT receipt_id FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " ";
      $sql.= "WHERE app_bundle_id = %s AND device_id = %s";
      $receipt_id = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $this->user_id ) );
      if ( $receipt_id ) {
        $sql = "UPDATE " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " SET base64_receipt = %s WHERE receipt_id = %d";
        $wpdb->query( $wpdb->prepare( $sql, $this->base64_receipt, $receipt_id ) );
      }
      else {
        $sql = "INSERT INTO " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " ";
        $sql.= "SET app_bundle_id = %s, device_id = %s, base64_receipt = %s";
        $wpdb->query( $wpdb->prepare( $sql, $this->app_id, $this->user_id, $this->base64_receipt ) );
        $receipt_id = $wpdb->insert_id;
      }
    }
    return $receipt_id;
  }

  /**
   * Save receipt transactions into the db.
   * @param int $receipt_record_id
   * @param object $receipt
   * @param string $purchase_type
   * @return mixed
   */
  public function save_receipt_transactions( $receipt_record_id, $receipt, $purchase_type ) {

    global $wpdb;
    $sql = "INSERT IGNORE INTO " . $wpdb->prefix . TPL_TABLE_RECEIPT_TRANSACTIONS . " SET ";
    $sql.= "receipt_id = %d, transaction_id = %s, product_id = %s, type = %s";
    return $wpdb->query( $wpdb->prepare( $sql, $receipt_record_id, $receipt->transaction_id, $receipt->product_id, $purchase_type ) );
  }

  /*
   * Verify a base64-encoded receipt with the App Store.
   * In case verification is successful, a nested hash representing the data
   * returned from the App Store will be returned.
   * In case of verification error, exceptions will be raised.
   *
   * @return array
   */
  public function validate_receipt() {

    $shared_secret = $this->get_shared_secret();
    if ( !$shared_secret ) {
      $data = (object)array( 'error' => 500, 'msg' => 'Must assign a shared secret' );
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
      $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response data.' );
    }

    $data = json_decode( $response['body'] );
    if ( !is_object($data) || !isset( $data->status ) ) {
      $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response data.' );
    }

    switch ( $data->status ) {
      case 21000:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21000): The App Store could not read the JSON object you provided.' );
      case 21002:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21002): The data in the receipt-data property was malformed or missing.' );
      case 21003:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21003): The receipt could not be authenticated.' );
      case 21004:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21004): The shared secret you provided does not match the shared secret on file for your account.' );
      case 21005:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21005): The receipt server is not currently available.' );
      case 21007:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21007): This receipt is a sandbox receipt, but it was sent to the production server.' );
      case 21008:
        $data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21008): This receipt is a production receipt, but it was sent to the sandbox server.' );
      default:
        break;
    }
    return $data;
  }

  /**
   * Retrieve latest receipts
   *
   * @return array or boolean false
   */
  public function get_latest_subscription_receipt() {

    global $wpdb;
    $sql = "SELECT DISTINCT r.base64_receipt, t.transaction_id FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " r ";
    $sql.= "LEFT JOIN " . $wpdb->prefix . TPL_TABLE_RECEIPT_TRANSACTIONS . " t ON r.receipt_id = t.receipt_id ";
    $sql.= "WHERE r.app_bundle_id = %s AND r.device_id = %s AND t.type = 'auto-renewable-subscription' ";
    $sql.= "ORDER BY t.transaction_id DESC LIMIT 0, 1";
    $data = $wpdb->get_row( $wpdb->prepare( $sql, $this->app_id, $this->user_id ) );
    return $data;

  }

  /**
   *
   * Get multiple purchased editions
   *
   * @return array
   */
  public function get_purchased_editions() {

    global $wpdb;
    $sql = "SELECT DISTINCT t.product_id FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . " r ";
    $sql.= "LEFT JOIN " . $wpdb->prefix . TPL_TABLE_RECEIPT_TRANSACTIONS . " t ON r.receipt_id = t.receipt_id ";
    $sql.= "WHERE r.app_bundle_id = %s AND r.device_id = %s AND t.type = 'issue' ";
    $sql.= "ORDER BY t.transaction_id DESC";
    $data = $wpdb->get_results( $wpdb->prepare( $sql, $this->app_id, $this->user_id ) );
    return $data;
  }

  /**
   *
   * Get multiple purchased editions
   * @param object $receipt_data
   * @param object $receipt
   * @return array
   */
  public function get_editions_in_subscription( $receipt_data, $receipt ) {

    global $wpdb;
    $issues = array();
    $transaction_id = $receipt->transaction_id;
    $from_date = date( 'Y-m-d', (int)$receipt->original_purchase_date_ms / 1000 );
    if ( $receipt_data->status == 0 ) {
      if ( is_array( $receipt_data->latest_receipt_info ) ) {
        foreach ( $receipt_data->latest_receipt_info as $receipt_info ) {

          if ( $receipt_info->transaction_id == $transaction_id ) {
            $to_date = date( 'Y-m-d', (int)$receipt_info->expires_date_ms / 1000 );
            break;
          }
        }
      }
      else {
        $to_date = date( 'Y-m-d', (int)$receipt_data->latest_receipt_info->expires_date_ms / 1000 );
      }
    }
    elseif ( $receipt_data->status == 21006 ) {
      if ( is_array( $receipt_data->latest_expired_receipt_info ) ) {
        foreach ( $receipt_data->latest_expired_receipt_info as $expired_receipt_info ) {

          if ( $expired_receipt_info->transaction_id == $transaction_id ) {
            $to_date = date( 'Y-m-d', (int)$expired_receipt_info->expires_date_ms / 1000 );
            break;
          }
        }
      }
      else {
        $to_date = date( 'Y-m-d', (int)$receipt_data->latest_expired_receipt_info->expires_date_ms / 1000 );
      }
    }

    // Get the editorial project settings
    $eproject_options = TPL_Editorial_Project::get_configs( $this->eproject->term_id );
    // Get the last published edition in editorial project and retrieve the date
    $last_edition_date = false;
    $last_edition = TPL_Editorial_Project::get_latest_edition( $this->eproject );
    if ( $last_edition ) {
      $last_edition_date = get_post_meta( $last_edition, '_pr_date', true );
    }

    $today = date('Y-m-d');
    $subscription_method = TPL_Editorial_Project::get_subscription_method( $this->eproject->term_id, $receipt->product_id );

    // Enable all editions if the subscription method is not defined or is setted on all
    // and the current date is between the subscription range
    if ( ( !$subscription_method || $subscription_method == 'all' ) && ( $from_date <= $today && $to_date >= $today ) ) {
      $editions = TPL_Editorial_Project::get_all_editions( $this->eproject );
    }
    elseif ( $subscription_method == 'last' ) {
      // Set the start date of subscription equal to latest published edition date,
      // if the expiring date is greater or equal to current date and the start date of subscription
      // is greater than the last published edition date
      if ( $to_date >= $today && $from_date > $last_edition_date ) {
        $from_date = $last_edition_date;
      }
      $editions = TPL_Editorial_Project::get_editions_in_range( $this->eproject, $from_date, $to_date );
    }

    if ( !empty( $editions ) ) {
      foreach ( $editions as $edition ) {

        $edition_bundle_id = TPL_Edition::get_bundle_id( $edition->ID, $this->eproject->term_id );
        array_push( $issues, $edition_bundle_id );
      }
    }
    return $issues;
  }

  /**
   * Get all purchased editions
   * @return array
   */
  public function get_purchases() {

    $purchases = array();
    $is_subscribed = false;
    $purchased_editions = $this->get_purchased_editions();
    if ( $purchased_editions ) {
      foreach ( $purchased_editions as $edition ) {

        array_push( $purchases, $edition->product_id );
      }
    }

    $latest_receipt = $this->get_latest_subscription_receipt();
    if ( $latest_receipt ) {
      $transaction_id = $latest_receipt->transaction_id;
      $this->base64_receipt = $latest_receipt->base64_receipt;

      $receipt_data = $this->validate_receipt();
      if ( !isset( $receipt_data->error ) ) {
        $is_subscribed = $receipt_data->status == 0;
        $receipt = $receipt_data->receipt;

        // Support the new receipt validation method implemented from iOS7 version
        if ( isset( $receipt->in_app ) ) {
          $receipt_unique_id = $this->save_receipt( false );
          foreach ( $receipt->in_app as $k => $single_receipt ) {

            if ( $single_receipt->transaction_id == $transaction_id ) {
              $editions = $this->get_editions_in_subscription( $receipt_data, $single_receipt );
              $purchases = array_merge( $purchases, $editions );
            }
          }
        }
        else {
          $receipt_unique_id = $this->save_receipt( $receipt->transaction_id );
          $editions = $this->get_editions_in_subscription( $receipt_data, $receipt );
          $purchases = array_merge( $purchases, $editions );
        }
      }
    }

    return array(
      'issues'      => $purchases,
      'subscribed'  => $is_subscribed
    );
  }

  /**
   * After each In-App Purchase, Client will call this API endpoint
   * to send the transaction receipt back to the server.
   * @return json string
   */
  protected function _action_purchase_confirmation() {

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
    $product_id = isset( $_POST['product_id'] ) ? $_POST['product_id'] : false;

    $this->app_id = $_POST['app_id'];
    $this->user_id = $_POST['user_id'];
    $this->environment = isset( $_POST['environment'] ) ? $_POST['environment'] : 'production';

    // @note: the replacement ' ' => '+' is used to support
    // the new receipt validation method implemented from iOS7 version
    $this->base64_receipt = str_replace( ' ', '+', stripcslashes( $_POST['receipt_data'] ) );

    $receipt_data = $this->validate_receipt();
    if ( isset( $receipt_data->error ) ) {
      $this->send_response( $receipt_data->error, $receipt_data->msg );
    }

    $receipt = $receipt_data->receipt;
    // Support the new receipt validation method implemented from iOS7 version
    if ( isset( $receipt->in_app ) ) {
      $receipt_unique_id = $this->save_receipt( false );
      foreach ( $receipt->in_app as $k => $single_receipt ) {

        if ( $product_id && $product_id == $single_receipt->product_id ) {
          $this->save_receipt_transactions( $receipt_record_id, $single_receipt, $purchase_type );
        }
      }
    }
    else {
      $receipt_unique_id = $this->save_receipt( $receipt->transaction_id );
      $this->save_receipt_transactions( $receipt_record_id, $receipt, $purchase_type );
    }
    $this->send_response( 200 );
  }

  /**
   * Client will call this API endpoint to get a list of purchased issues and whether the user has an active subscription or not.
   * Baker will unlock for download all purchased issues.
   * @return json string
   */
  protected function _action_purchases_list() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
    elseif ( !isset( $_GET['app_id'], $_GET['user_id']) ) {
      $this->send_response( 400, "Bad request. App identifier and/or user identifier doesn't exist." );
    }

    $this->eproject = TPL_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$this->eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $this->app_id = $_GET['app_id'];
    $this->user_id = $_GET['user_id'];
    $this->environment = isset( $_GET['environment'] ) ? $_GET['environment'] : 'production';

    $purchases = $this->get_purchases();

    status_header( 200 );
    wp_send_json( $purchases );
  }
}

$pr_server_connector_itunes = new PR_Connector_iTunes;
