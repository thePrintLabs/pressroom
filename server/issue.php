<?php
final class PR_Server_Issue extends PR_Server_API
{
  public $app_id;
  public $user_id;

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
   * Retrieve latest receipts
   * @return string or null
   */
  protected function _retrieve_receipts() {

    global $wpdb;
    $sql = "SELECT base64_receipt FROM " . $wpdb->prefix . TPL_TABLE_RECEIPTS . "
    WHERE app_id = %s AND user_id = %s AND type = 'auto-renewable-subscription'
    ORDER BY transaction_id DESC";

    $encoded_receipt = $wpdb->get_var( $wpdb->prepare( $sql, $this->app_id, $this->user_id ), 0, 0 );

    return $encoded_receipt;
  }

  /**
   *  Mark issues as purchased, based on the app_store_data parameter.
   *  This function will examine a receipt verification response coming from the
   *  App Store and mark as purchased all the issues it covers.
   *  This function should be passed a verification response for an
   *  auto-renewable subscription.
   */
  function mark_issues_as_purchased( $receipt ) {

    global $wp;

    $start_date = (int)$receipt->purchase_date_ms / 1000;

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

    $this->app_id = isset( $_GET['app_id'] ) ? $_GET['app_id'] : false;
    $this->user_id = isset( $_GET['user_id'] ) ? $_GET['user_id'] : false;

    // APPID -> EDITORIAL PROJECT
    // VERIFICARE SE EDIZIONE E' GRATUITA O A PAGAMENTO
    // VERIFICARE PARAMETRI CON ITUNES


    $receipt = $this->_retrieve_receipts();
    if ( $receipt ) {
      // @TODO: Implement management of multiple connectors
      $itunes_connector = new PR_Connector_iTunes;

      $data = $itunes_connector->validate_receipt( $receipt );
      $this->_mark_issues_as_purchased( $data->receipt );
    }

    $editorial = get_term_by( 'slug', $editorial_slug, TPL_EDITORIAL_PROJECT );
    if ( !$editorial ) {
      $this->send_response( 500, 'Editorial project not valid.' );
    }

    $args = array(
      'post_type'             => TPL_EDITION,
      TPL_EDITORIAL_PROJECT   => $editorial->slug,
      'post_status'           => 'publish',
      'posts_per_page'        => -1,
    );
    $edition_query = new WP_Query( $args );

    $press_options = array();
    self::$_press_to_baker['_pr_product_id_' . $editorial->term_id] = 'product_id';

    foreach ( $edition_query->posts as $edition_key => $edition ) {

      $press_options[$edition_key] = array(
        //'url' => TPL_HPUB_URI . TPL_Utils::sanitize_string( $edition->post_title . '.hpub' ) // @TODO: FREE
        'url' => esc_url( home_url( '/pressroom-api/issue/' . TPL_Utils::sanitize_string( $edition->post_title ) ) )
      );

      // Add the cover image into the edition options
      $edition_cover_id = get_post_thumbnail_id( $edition->ID );
      if ( $edition_cover_id ) {
         $edition_cover = wp_get_attachment_image_src( $edition_cover_id, 'thumbnail_size' );
         if ( $edition_cover ) {
            $press_options[$edition_key]['cover'] = $edition_cover[0];
         }
      }

      // Add only allowed values into the edition options
      foreach ( $edition as $key => $edition_attribute ) {

        if ( array_key_exists( $key, self::$_press_to_baker ) ) {
          $baker_option = self::$_press_to_baker[$key];
          $press_options[$edition_key][$baker_option] = $edition_attribute;
        }
      }

      // Add allowed custom fields values into the edition options
      $meta_fields = get_post_custom( $edition->ID );
      foreach ( $meta_fields as $meta_key => $meta_value ) {

        if ( array_key_exists( $meta_key, self::$_press_to_baker ) ) {

          $baker_option = self::$_press_to_baker[$meta_key];

          switch ( $meta_key ) {

            case '_pr_date':
              if ( isset( $meta_value[0] ) ) {
                 $press_options[$edition_key][$baker_option] = date( 'Y-m-d H:s:i', strtotime( $meta_value[0] ) );
              }
              break;

            case '_pr_product_id' . $term->term_id :
              if ( isset( $meta_value[0] ) &&
                !( isset( $meta_fields['_pr_edition_free'] ) && $meta_fields['_pr_edition_free'][0] == 1 ) ) {
                $press_options[$edition_key][$baker_option] = $meta_value[0];
              }
              break;

             default:
              if ( isset( $meta_value[0] ) ) {
                 $press_options[$edition_key][$baker_option] = $meta_value[0];
              }
              break;
          }
        }
      }
    }
    wp_send_json( $press_options );
  }
}

$pr_server_issue = new PR_Server_Issue;
