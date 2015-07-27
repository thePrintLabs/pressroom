<?php
/**
* PR_Statistics class.
*/

class PR_Logs
{
  public function __construct() {

    if ( !is_admin() ) {
      return;
    }
  }

  /**
   * Insert log
   *
   * @param  integer $data
   * @return integer
   */
  public static function insert_log( $data ) {

    global $wpdb;
    $date = strtotime( 'now' );
    $wpdb->insert( $wpdb->prefix . PR_TABLE_LOGS, array(
      'action'      => $data['action'],
      'object_id'   => $data['object_id'],
      'log_date'    => $date,
      'ip'          => $data['ip'],
      'author'      => $data['author'],
      'type'        => $data['type'],
    ), array( '%s', '%d', '%d', '%s', '%d', '%s' ) );

    return $wpdb->insert_id;
  }

  /**
   * Update log
   *
   * @param  int  $log_id
   * @param  object $data
   * @void
   */
  public static function update_log( $log_id, $detail ) {

    global $wpdb;
    $update = $wpdb->query($wpdb->prepare("UPDATE $wpdb->prefix". PR_TABLE_LOGS ." SET detail='%s' WHERE ID= %d", $detail, $log_id));
  }

  /**
   * Get statistics data for charts
   *
   * @param  string $start_date
   * @param  string $end_date
   * @return array
   */
  public function get_logs( $start_date, $end_date) {

    global $wpdb;

    $start_date = strtotime( $start_date );
    $end_date = strtotime( $end_date );

    $sql = $wpdb->prepare( "SELECT *
    FROM " . $wpdb->prefix . PR_TABLE_LOGS . " WHERE
    log_date BETWEEN '%d' AND '%d' ORDER BY log_date DESC", $start_date, $end_date );
    $results = $wpdb->get_results( $sql );

    return $results;
  }
}

$pr_logs = new PR_Logs();
