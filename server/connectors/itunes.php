<?php

final class PR_Connector_iTunes extends PR_Server_API {

  const SANDBOX_URL     = "https://sandbox.itunes.apple.com/verifyReceipt";
  const PRODUCTION_URL  = "https://buy.itunes.apple.com/verifyReceipt";

  public $app_id;
  public $device_id;
  public $base64_receipt;
  public $eproject;
  public $environment = 'production';

  /**
   * iTunes connector
   */
  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
    add_action( 'init', array( $this, 'add_endpoint' ), 10 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
    add_action( 'pr_issue_download', array( $this, 'validate_purchases_on_download' ), 10, 6 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *  @void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_rule( '^pressroom-api/itunes_purchase_confirmation/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchase_confirmation&app_id=$matches[1]&user_id=$matches[2]&editorial_project=$matches[3]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/itunes_purchase_confirmation/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchase_confirmation&app_id=$matches[2]&user_id=$matches[3]&editorial_project=$matches[4]',
                      'top' );
    add_rewrite_rule( '^pressroom-api/itunes_purchases_list/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchases_list&app_id=$matches[1]&user_id=$matches[2]&editorial_project=$matches[3]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/itunes_purchases_list/([^&]+)/([^&]+)/([^&]+)/?$',
                      'index.php?__pressroom-api=itunes_purchases_list&app_id=$matches[2]&user_id=$matches[3]&editorial_project=$matches[4]',
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
   *
   * @return string
   */
  public function get_shared_secret() {

    return PR_Editorial_Project::get_config( $this->eproject->term_id , '_pr_itunes_secret' );
  }

  /**
   * Save base64-encoded receipt from App Store to db.
   *
   * @param object $receipt
   * @param string $transaction_id
   * @param string $purchase_type
   * @return boolean
   */
  public function save_receipt( $receipt, $transaction_id, $purchase_type ) {

    global $wpdb;

    $sql = "SELECT receipt_id FROM " . $wpdb->prefix . PR_TABLE_RECEIPTS . " ";
    $sql.= "WHERE app_bundle_id = %s AND transaction_id = %s AND device_id = %s";
    $receipt_id = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $transaction_id, $this->device_id ) );

    if ( !$receipt_id ) {

      $sql = "INSERT IGNORE INTO " . $wpdb->prefix . PR_TABLE_RECEIPTS . " SET ";
      $sql.= "app_bundle_id = %s, device_id = %s, base64_receipt = %s, transaction_id = %s, product_id = %s, type = %s";

      $out = $wpdb->query( $wpdb->prepare(
        $sql,
        $this->app_id,
        $this->device_id,
        $this->base64_receipt,
        $transaction_id,
        $receipt->product_id,
        $purchase_type
      ) );

      return $out;
    }
    else {
      return true;
    }
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
        //$data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21007): This receipt is a sandbox receipt, but it was sent to the production server.' );
        $this->environment = 'debug';
        $data = $this->validate_receipt();
        break;
      case 21008:
        //$data = (object)array( 'error' => 502, 'msg' => 'Invalid iTunes response (21008): This receipt is a production receipt, but it was sent to the sandbox server.' );
        $this->environment = 'production';
        $data = $this->validate_receipt();
        break;
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
    $sql = "SELECT DISTINCT base64_receipt, transaction_id FROM " . $wpdb->prefix . PR_TABLE_RECEIPTS;
    $sql.= " WHERE app_bundle_id = %s AND device_id = %s AND type IN ('auto-renewable-subscription', 'free-subscription')";
    $sql.= " ORDER BY transaction_id DESC LIMIT 0, 1";
    $data = $wpdb->get_row( $wpdb->prepare( $sql, $this->app_id, $this->device_id ) );
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
    $sql = "SELECT DISTINCT product_id FROM " . $wpdb->prefix . PR_TABLE_RECEIPTS ;
    $sql.= " WHERE app_bundle_id = %s AND device_id = %s AND type = 'issue'";
    $sql.= " ORDER BY transaction_id DESC";
    $data = $wpdb->get_results( $wpdb->prepare( $sql, $this->app_id, $this->device_id ) );
    return $data;
  }

  /**
   *
   * Get multiple purchased editions
   *
   * @param object $receipt_data
   * @return array
   */
  public function get_editions_in_subscription( $receipt_data ) {

    global $wpdb;
    $issues = array();
    $today = date('Y-m-d H:i');
    $transaction_id = $receipt_data->receipt->transaction_id;
    $subscription_bundle_id = $receipt_data->receipt->product_id;

    if ( $receipt_data->status == 0 ) {
      $to_date = date( 'Y-m-d H:i', (int)( ( isset( $receipt_data->latest_receipt_info->expires_date_ms ) ? $receipt_data->latest_receipt_info->expires_date_ms : $receipt_data->latest_receipt_info->expires_date ) / 1000 ) );
    }
    elseif ( $receipt_data->status == 21006 ) {
      $to_date = date( 'Y-m-d H:i', (int)( ( isset( $receipt_data->latest_expired_receipt_info->expires_date_ms ) ? $receipt_data->latest_expired_receipt_info->expires_date_ms : $receipt_data->latest_expired_receipt_info->expires_date ) / 1000 ) );
    }

    // Free subscription does not expire
    $free_subscription_id = PR_Editorial_Project::get_free_subscription_id( $this->eproject->term_id );
    if ( $free_subscription_id && $free_subscription_id == $subscription_bundle_id ) {
      $to_date = $today;
    }

    // Get the editorial project settings
    $eproject_options = PR_Editorial_Project::get_configs( $this->eproject->term_id );
    // Get the last published edition in editorial project and retrieve the date
    $last_edition_date = false;
    $last_edition = PR_Editorial_Project::get_latest_edition( $this->eproject );
    if ( $last_edition ) {
      $last_edition_date = get_post_meta( $last_edition, '_pr_date', true );
    }

    $subscription_method = PR_Editorial_Project::get_subscription_method( $this->eproject->term_id, $subscription_bundle_id );
    // Enable all editions if the subscription method is not defined or is setted on all
    // and the current date is between the subscription range
    if ( ( !$subscription_method || $subscription_method == 'all' ) && ( $to_date >= $today ) ) {
      $editions = PR_Editorial_Project::get_all_editions( $this->eproject );
    }
    elseif ( $subscription_method == 'last' ) {
      // Set the start date of subscription equal to latest published edition date,
      // if the expiring date is greater or equal to current date and the start date of subscription
      // is greater than the last published edition date
      if ( $to_date >= $today && $today > $last_edition_date ) {
        $today = $last_edition_date;
      }
      $editions = PR_Editorial_Project::get_editions_in_range( $this->eproject, $today, $to_date );
    }

    if ( !empty( $editions ) ) {
      foreach ( $editions as $edition ) {
        $subscription_plans = get_post_meta( $edition->ID, '_pr_subscriptions_select', true );
        if ( !empty( $subscription_plans ) ) {
          if ( in_array( $subscription_bundle_id, $subscription_plans ) ) {
            $edition_bundle_id = PR_Edition::get_bundle_id( $edition->ID, $this->eproject->term_id );
            array_push( $issues, $edition_bundle_id );
          }
        }
      }
    }
    return $issues;
  }

  /**
   * Get all purchased editions
   *
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

        if ( $this->save_receipt( $receipt_data->receipt, $transaction_id, $purchase_type ) ) {
          $editions = $this->get_editions_in_subscription( $receipt_data );
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
   * Check purchased editions on issue download
   *
   * @param  boolean $allow_download
   * @param  string $app_id
   * @param  string $device_id
   * @param  string $environment
   * @param  object $edition
   * @param  object $eproject
   * @void
   */
  public function validate_purchases_on_download( &$allow_download, $app_id, $device_id, $environment, $edition, $eproject ) {

    $this->app_id = $app_id;
    $this->device_id = $device_id;
    $this->environment = $environment;
    $this->eproject = $eproject;

    $purchases = $this->get_purchases();
    if ( !empty( $purchases['issues'] ) ) {
      // Get the editorial project settings
      $edition_bundle_id = PR_Edition::get_bundle_id( $edition->ID, $eproject->term_id );
      if ( in_array( $edition_bundle_id, $purchases['issues'] ) ) {
        $allow_download = true;
      }
    }
  }

  /**
   * After each In-App Purchase, Client will call this API endpoint
   * to send the transaction receipt back to the server.
   *
   * @return json string
   */
  protected function _action_purchase_confirmation() {

    global $wp;
    parent::validate_request();
    $eproject_slug = $wp->query_vars['editorial_project'];

    if ( !isset( $_POST['receipt_data'], $_POST['type']) || !strlen($_POST['receipt_data']) || !strlen($_POST['type']) ) {
      $this->send_response( 400, "Bad request. Receipt data and/or purchase type doesn't exist." );
    }

    $this->eproject = PR_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$this->eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $purchase_type = $_POST['type'];
    $product_id = isset( $_POST['product_id'] ) ? $_POST['product_id'] : false;

    $this->app_id = $wp->query_vars['app_id'];
    $this->device_id = $wp->query_vars['user_id'];
    $this->environment = isset( $_POST['environment'] ) ? $_POST['environment'] : 'production';

    // @note: the replacement ' ' => '+' is used to support
    // the new receipt validation method implemented from iOS7 version
    $this->base64_receipt = str_replace( ' ', '+', stripcslashes( $_POST['receipt_data'] ) );

    $receipt_data = $this->validate_receipt();
    if ( isset( $receipt_data->error ) ) {
      $this->send_response( $receipt_data->error, $receipt_data->msg );
    }

    $receipt = $receipt_data->receipt;
    // if transaction id == original transaction id is a new purchase
    // else it's a restore
    if ( $receipt->transaction_id == $receipt->original_transaction_id ) {
      if ( $this->save_receipt( $receipt, $receipt->transaction_id, $purchase_type ) ) {
        PR_Stats::increment_counter( 'purchase_' . $purchase_type );
        $this->send_response( 200 );
      }
    }
    elseif ( $this->save_receipt( $receipt, $receipt->original_transaction_id, $purchase_type ) ) {
      $this->send_response( 200 );
    }

    $this->send_response( 500 );
  }

  /**
   * Client will call this API endpoint to get a list of purchased issues and whether the user has an active subscription or not.
   * Baker will unlock for download all purchased issues.
   *
   * @return json string
   */
  protected function _action_purchases_list() {

    global $wp;
    parent::validate_request();
    $eproject_slug = $wp->query_vars['editorial_project'];

    $this->eproject = PR_Editorial_Project::get_by_slug( $eproject_slug );
    if( !$this->eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $this->app_id = $wp->query_vars['app_id'];
    $this->device_id = $wp->query_vars['user_id'];
    $this->environment = isset( $_GET['environment'] ) ? $_GET['environment'] : 'production';

    $purchases = $this->get_purchases();

    status_header( 200 );
    wp_send_json( $purchases );
  }
}

$pr_server_connector_itunes = new PR_Connector_iTunes;
