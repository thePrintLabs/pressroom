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
  }

  public static function disable() {
    wp_unschedule_event( time(), 'daily', 'pr_checkexpiredtoken' );
  }
}

function do_checkexpiredtoken() {
  global $wpdb;
  $wpdb->query( 'DELETE FROM ' . $wpdb->prefix . PR_TABLE_AUTH_TOKENS . ' WHERE ( created_time + expires_in ) < UNIX_TIMESTAMP()' );
}
add_action( 'pr_checkexpiredtoken', 'do_checkexpiredtoken');
