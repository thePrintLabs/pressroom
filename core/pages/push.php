<?php

class PR_push_notification_page {

  public $push_app_id;
  public $push_app_key;

  /**
   * constructor method
   * Add class functions to wordpress hooks
   *
   * @void
   */
  public function __construct() {

    if ( !is_admin() ) {
      return;
    }

    add_action( 'admin_footer', array( $this, 'add_custom_scripts' ) );

    add_action( 'admin_menu', array( $this, 'pr_add_admin_menu' ) );
    add_action( 'wp_ajax_pr_push_get_editions_list', array( $this, 'ajax_push_editions_list' ) );
    add_action( 'wp_ajax_pr_send_push_notification', array( $this, 'ajax_send_push_notification' ) );
  }

  /**
   * Add options page to wordpress menu
   */
  public function pr_add_admin_menu() {

    add_submenu_page( 'pressroom', __( 'Push notification', 'pressroom-push' ), __( 'Push Notification', 'pressroom-push' ), 'manage_options', 'pressroom-push', array( $this, 'pressroom_push_notification_page' ) );
  }

  /**
   * Get editorial projects list
   *
   * @return array
   */
  public function pr_editorial_projects_list() {

    $eprojects = get_terms( PR_EDITORIAL_PROJECT, array( 'hide_empty' => false ) );
    return $eprojects;
  }

  /**
   * Get editions by editorial project via ajax request
   *
   * @echo
   */
  public function ajax_push_editions_list() {

    if ( !isset( $_POST['eproject_slug'] ) ) {
      return;
    }

    $editions_list = array();
    $editions = PR_Editorial_Project::get_all_editions( $_POST['eproject_slug'], 20 );
    foreach ( $editions as $edition ) {
      echo '<option value="' . $edition->post_name . '">' . esc_attr( $edition->post_title ) . '</option>';
    }
    exit;
  }

  /**
   * Send push notification via ajax request
   *
   * @echo
   */
  public function ajax_send_push_notification() {

    $msg = __( "Generic error.", 'pressroom' );

    if ( !isset( $_POST ) && empty( $_POST ) ) {
      wp_send_json_error( $msg );
    }

    $data = $_POST['pr_push'];
    if ( !isset( $data['editorial_project'] ) || !strlen( $data['editorial_project'] ) ) {
      wp_send_json_error( $msg );
    }

    $eproject = PR_Editorial_Project::get_by_slug( $data['editorial_project'] );
    if ( !$eproject ) {
      wp_send_json_error( __( "Editorial project doesn't exist.", 'pressroom' ) );
    }

    $push_time = $data['time'] == 'later' ? $data['date_time'] : false;
    $push_service = PR_Editorial_Project::get_config( $eproject->term_id , 'pr_push_service' );
    $this->push_app_id = PR_Editorial_Project::get_config( $eproject->term_id , 'pr_push_api_app_id' );
    $this->push_app_key = PR_Editorial_Project::get_config( $eproject->term_id , 'pr_push_api_key' );

    switch ( $push_service ) {
      case 'parse':
        $this->send_parse_push_notification( $data, $push_time );
        break;

      case 'urbanairship':
        $this->send_urbanairship_push_notification( $data, $push_time );
        break;
    }
    wp_send_json_error( $msg );
  }

  /**
   * Render push notification page form
   *
   * @echo
   */
  public function pressroom_push_notification_page() {
?>
    <h2>Pressroom Push Notification</h2>
<?php
    $eprojects = $this->pr_editorial_projects_list();
    if ( !empty( $eprojects) ) {
?>
    <ol>
      <li><?php echo __('Select an editorial project', 'pressroom-push'); ?></li>
      <li><?php echo __('Set the push notification settings', 'pressroom-push'); ?></li>
      <li><?php echo __('View the result'); ?></li>
    </ol>
    <form action="options.php" method="post" id="pr-push-form">
      <div id="bg">
        <div class="pr-push-content">
          <br class="clear">
          <div class="prp-panel active">
            <b><?php echo __('Editorial project', 'pressroom-push'); ?></b><br/>
            <?php
            foreach ( $eprojects as $i => $eproject ) {
              echo '<label for="prp-eproject-' . $eproject->term_id . '" class="radio">
              <input type="radio" name="pr_push[editorial_project]" id="prp-eproject-' . $eproject->term_id . '" value="' . $eproject->slug . '" ' . ( !$i ? 'checked="checked"' : '' ) . '><span>' . $eproject->name . '<br>
              <i>' . PR_Editorial_Project::get_bundle_id( $eproject->term_id ) . '</i></span></label>';
            }
            ?>
          </div>

          <div class="prp-panel">
            <p>
              <b><?php echo __('Push Notifications Type', 'pressroom-push'); ?></b>
              <label for="prp-type-0" class="radio"><input type="radio" name="pr_push[type]" class="prp-type" id="prp-type-0" value="message" checked="checked"> <?php echo __('Message push', 'pressroom-push'); ?></label>
              <label for="prp-type-1" class="radio"><input type="radio" name="pr_push[type]" class="prp-type" id="prp-type-1" value="download"> <?php echo __('Background download push', 'pressroom-push'); ?></label>
              <div id="pr-edition-d">
                <br>
                <b><?php echo __('Issue to download', 'pressroom-push'); ?></b>
                <label for="prp-edition-0" class="radio"><input type="radio" name="pr_push[edition]" id="prp-edition-0" value="latest" checked="checked"> <?php echo __('Latest published edition', 'pressroom-push'); ?></label>
                <label for="prp-edition-1" class="radio"><input type="radio" name="pr_push[edition]" id="prp-edition-1" value="specific"> <?php echo __('Specific edition', 'pressroom-push'); ?></label>
                <select name="pr_push[edition-slug]" class="combobox" disabled="disabled" id="prp-edition-s"></select>
              </div>
            </p>
            <br>
            <b><?php echo __('Delivery time', 'pressroom-push'); ?></b>
            <label for="prp-time-0" class="radio"><input type="radio" name="pr_push[time]" class="prp-time" id="prp-time-0" value="now" checked="checked"> <?php echo __('Send immediately', 'pressroom-push'); ?></label>
            <label for="prp-time-1" class="radio"><input type="radio" name="pr_push[time]" class="prp-time" id="prp-time-1" value="later"> <?php echo __('Schedule sending', 'pressroom-push'); ?></label>
            <div id="pr-edition-t">
              <br>
              <input id="prp-rp-time" name="pr_push[date_time]" type="text" value="<?php echo date( 'Y-m-d H:s:i', strtotime( '+1 hour' ) ); ?>" class="textbox">
            </div>
            <br>
            <b><?php echo __('Alert message', 'pressroom-push'); ?></b><br/>
            <textarea placeholder="The notification's message" name="pr_push[alert]" class="textbox textareabox"></textarea>
            <button type="submit" class="pr-option-btn"><?php echo __("Send notification", 'pressroom-push'); ?></button>
          </div>
          <div class="prp-panel">
            <div id="pr-push-console"></div>
          </div>
        </div>
      </div>
    </form>
    <?php
    }
    else {
?>
    <div class="error">
      <p><?php echo __( "Unable to use push notification service. You need to create at least one <a href=\"" . admin_url( 'edit-tags.php?taxonomy=' . PR_EDITORIAL_PROJECT . '&post_type=' . PR_EDITION ) . "\">editorial project.</a>", 'pressroom' ); ?></p>
    </div>
<?php
    }
  }

  /**
   * add custom script to metabox
   *
   * @void
   */
  public function add_custom_scripts() {

    global $pagenow;
    if( $pagenow == 'admin.php' && $_GET['page'] == 'pressroom-push' ) {
      wp_register_style( 'push_notification_page', PR_ASSETS_URI . 'css/jquery.datetimepicker.min.css' );
      wp_enqueue_style( 'push_notification_page' );
      wp_register_script( 'push_notification_moment', PR_ASSETS_URI . '/js/moment.min.js' );
      wp_enqueue_script( 'push_notification_moment' );
      wp_register_script( 'push_notification_moment_tz', PR_ASSETS_URI . '/js/moment.timezone.min.js', array( 'push_notification_moment' ) );
      wp_enqueue_script( 'push_notification_moment_tz' );
      wp_register_script( 'push_notification_datepicker', PR_ASSETS_URI . '/js/jquery.datetimepicker.min.js', array( 'jquery' ), '1.0', true );
      wp_enqueue_script( 'push_notification_datepicker' );
      wp_register_script( 'push_notification_page', PR_ASSETS_URI . '/js/pr.pushnotification.js', array( 'push_notification_datepicker' ) );
      wp_enqueue_script( 'push_notification_page' );
    }
  }

  public function send_parse_push_notification( $data, $push_time = false ) {

    switch ( $data['type'] ) {

      case 'message':

        $notification = array(
          'alert' => stripcslashes( $data['alert'] ),
        );
        break;

      case 'download':

        $notification = array(
          'alert' => stripcslashes( $data['alert'] ),
          'badge' => 1,
          'content-available' => 1,
        );
        if ( $data['edition'] == 'specific' ) {
          $notification['content-name'] = $data['edition-slug'];
        }
        break;
    }

    $params = array(
      'where' => '{}',
      'data'  => $notification
    );

    if ( $push_time ) {
      $params['push_time'] = gmdate( "Y-m-d\TH:i:s\+01:00", strtotime( $push_time ) );
    }

    $msg = __( "Data sent:", 'pressroom' ) . '<br>' . json_encode( $params ) . '<br>';
    $response = wp_remote_post( 'https://api.parse.com/1/push', array(
      'body'      => json_encode( $params ),
      'headers'   => array(
        'X-Parse-Application-Id'  =>  $this->push_app_id,
        'X-Parse-REST-API-Key'    =>  $this->push_app_key,
        'Content-Type'            => 'application/json',
      ),
    ));

    if ( is_wp_error( $response ) || !isset( $response['body'] ) ) {
      wp_send_json_error( __( "Invalid response data.", 'pressroom' ) );
    }

    $data = json_decode( $response['body'] );
    if ( !is_object( $data ) || !isset( $data->result ) ) {
      wp_send_json_error( $data->error );
    }
    wp_send_json_success( $msg );

  }

  public function send_urbanairship_push_notification( $data, $push_time = false ) {

    switch ( $data['type'] ) {

      case 'message':

        $notification = array(
          'alert' => stripcslashes( $data['alert'] ),
        );
        break;

      case 'download':

        $notification = array(
          'alert' => stripcslashes( $data['alert'] ),
          'ios'   => array(
            'badge' => 1,
            'content-available' => 1,
          )
        );

        if ( $data['edition'] == 'specific' ) {
          $notification['ios']['extra'] = array( 'content-name' => $data['edition-slug'] );
        }
        break;
    }

    if ( $push_time ) {
      $d = DateTime::createFromFormat( 'Y-m-d H:i', $push_time, new DateTimeZone( 'Europe/Berlin' ) );
      $d->setTimeZone( new DateTimeZone( 'UTC' ) );


      $params = array(
        'schedule'      => array( 'scheduled_time' => $d->format( 'Y-m-d\TH:i:s' ) ),
        'push'          => array(
          'audience'      => 'all',
          'device_types'  => 'all',
          'notification'  => $notification,
        ),
      );
    }
    else {
      $params = array(
        'audience'      => 'all',
        'device_types'  => 'all',
        'notification'  => $notification
      );
    }

    $msg = __( "Data sent:", 'pressroom' ) . '<br>' . json_encode( $params ) . '<br>';
    $response = wp_remote_post( 'https://go.urbanairship.com/api/' . ( $push_time ? 'schedules/' : 'push/' ), array(
      'body'      => json_encode( $params ),
      'headers'   => array(
        'Authorization'           => 'Basic ' . base64_encode( $this->push_app_id . ':' . $this->push_app_key ),
        'Content-Type'            => 'application/json',
        'Accept'                  => 'application/vnd.urbanairship+json; version=3;'
      ),
    ));

    if ( is_wp_error( $response ) || !isset( $response['body'] ) ) {
      wp_send_json_error( __( "Invalid response data.", 'pressroom' ) );
    }

    $data = json_decode( $response['body'] );
    if ( !is_object($data) || !$data->ok ) {
      wp_send_json_error( $data->error );
    }
    wp_send_json_success( $msg );
  }
}

$pr_push_notification_page = new PR_push_notification_page();
