<?php
/**
 * PressRoom setup cronjobs.
 */

class PR_Cron
{

  public function __construct() {}

 /**
  * Setup cronjobs
  *
  * @void
  */
  public static function setup() {
    wp_schedule_event( time(), 'daily', 'pr_checkexpiredtoken' );
    wp_schedule_event( time(), 'monthly', 'pr_clean_logs' );
  }

  /**
   * Clear schedule on deactivation
   *
   * @void
   */
  public static function disable() {
    wp_clear_scheduled_hook( 'pr_checkexpiredtoken' );
    wp_clear_scheduled_hook( 'pr_clean_logs' );
  }

}

/**
 * Remove expired token
 *
 * @void
 */
function do_checkexpiredtoken() {
  global $wpdb;
  $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . PR_TABLE_AUTH_TOKENS . ' WHERE ( created_time + expires_in ) < UNIX_TIMESTAMP()' );
}
add_action( 'pr_checkexpiredtoken', 'do_checkexpiredtoken');

/**
 * Add monthly schedule
 *
 * @param array $schedules
 */
function pr_add_a_cron_schedule( $schedules ) {

    $schedules['monthly'] = array(

        'interval' => 108000, // month

        'display'  => __( 'Monthly' ),
    );

    return $schedules;
}
add_filter( 'cron_schedules', 'pr_add_a_cron_schedule' );

/**
 * Remove old logs
 *
 * @void
 */
function do_cleanlogs() {

  $expiry_time = 3600 * 24 * 90; // 90 days
  global $wpdb;
  $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . PR_TABLE_LOGS . ' WHERE ( log_date + '.$expiry_time.' ) < UNIX_TIMESTAMP()' );
}
add_action( 'pr_clean_logs', 'do_cleanlogs');
