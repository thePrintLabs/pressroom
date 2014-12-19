<?php
/**
* PR_Statistics class.
*/

class PR_Stats
{
  public function __construct() {

    $this->hooks();
  }

  public function hooks() {

    if ( is_admin() ) {
      add_action( 'admin_enqueue_scripts', array( $this,'register_dashboard_scripts' ) );
      add_action( 'wp_dashboard_setup', array( $this, 'add_pr_stats_db_widgets' ) );
    }
  }

  /**
  * Add chartjs to scripts
  * @void
  */
  public function register_dashboard_scripts() {

    global $pagenow;
    if( $pagenow == 'index.php' ) {
      wp_register_script( 'chartjs', PR_ASSETS_URI . '/js/chartjs.min.js', array( 'jquery'), '1.0', true );
      wp_enqueue_script( 'chartjs' );
    }
  }

  /**
   * Add statistics charts to dashboard
   * @void
   */
  public function add_pr_stats_db_widgets() {
    wp_add_dashboard_widget(
      'downloaded_editions_db_widget',
      __( 'Downloaded editions' ),
      array( $this, 'render_downloaded_editions_widget' )
    );
    wp_add_dashboard_widget(
      'purchased_editions_db_widget',
      __( 'Purchased editions and subscriptions' ),
      array( $this, 'render_purchased_editions_widget' )
    );
  }

  /**
   * Render downloaded editions chart
   * @echo
   */
  public function render_downloaded_editions_widget() {

    $data = $this->_get_chart_data( 'download_edition', date('Y-m-d'), 13 );
    $js_data = json_encode( array(
      'labels' => array_keys( $data ),
      'datasets' => array(
        array(
          'label' => "Downloaded editions",
          'fillColor' => "rgba(151,187,205,0.2)",
          'strokeColor' => "rgba(151,187,205,1)",
          'pointColor' => "rgba(151,187,205,1)",
          'pointStrokeColor' => "#fff",
          'pointHighlightFill' => "#fff",
          'pointHighlightStroke' => "rgba(151,187,205,1)",
          'data' => array_values( $data )
        )
      )
    ) );
    echo '<div><canvas id="dwnEditionChart" width="460" height="460"></canvas></div>';
    echo '<script type="text/javascript">
    jQuery(document).ready(function($){var d=' . $js_data . ', ctx = $("#dwnEditionChart").get(0).getContext("2d"),
    downloadedChart = new Chart(ctx).Line(d, {responsive:true}); });
    </script>';
  }

  /**
   * Render purchased editions and subscriptions chart
   * @echo
   */
  public function render_purchased_editions_widget() {

    $editions_data = $this->_get_chart_data( 'purchase_issue', date('Y-m-d'), 13 );
    $subscriptions_data = $this->_get_chart_data( 'purchase_auto-renewable-subscription', date('Y-m-d'), 13 );
    $js_data = json_encode( array(
      'labels' => array_keys( $editions_data ),
      'datasets' => array(
        array(
          'label' => "Purchased editions",
          'fillColor' => "rgba(220,220,220,0.5)",
          'strokeColor' => "rgba(220,220,220,0.8)",
          'highlightFill' => "rgba(220,220,220,0.75)",
          'highlightStroke' => "rgba(220,220,220,1)",
          'data' => array_values( $editions_data )
        ),
        array(
          'label' => "Purchased subscriptions",
          'fillColor' => "rgba(151,187,205,0.5)",
          'strokeColor' => "rgba(151,187,205,0.8)",
          'highlightFill' => "rgba(151,187,205,0.75)",
          'highlightStroke' => "rgba(151,187,205,1)",
          'data' => array_values( $subscriptions_data )
        ),
      )
    ) );
    echo '<div><canvas id="prchEditionChart" width="460" height="460"></canvas></div>';
    echo '<script type="text/javascript">
    jQuery(document).ready(function($){var d=' . $js_data . ', ctx = $("#prchEditionChart").get(0).getContext("2d"),
    downloadedChart = new Chart(ctx).Bar(d, {responsive:true}); });
    </script>';
  }

  /**
   * Increment counter
   * @param  string  $scenario
   * @param integer $object_id
   * @param  integer $count
   * @void
   */
  public static function increment_counter( $scenario, $object_id = 0, $count = 1 ) {

    global $wpdb;
    $date = strtotime(date('Y-m-d'));
    $counter = $wpdb->get_var( $wpdb->prepare( "SELECT counter FROM " . $wpdb->prefix . PR_TABLE_STATS . " WHERE scenario = %s AND object_id = %d AND stat_date = %d", $scenario, $object_id, $date ), 0, 0 );
    if ( is_null( $counter ) ) {
      $wpdb->insert( $wpdb->prefix . PR_TABLE_STATS, array(
        'scenario'    => $scenario,
        'object_id'   => $object_id,
        'stat_date'   => $date,
        'counter'     => 1
      ), array( '%s', '%d', '%d', '%d' ) );
    }
    else {
      $wpdb->query( $wpdb->prepare( "UPDATE " . $wpdb->prefix . PR_TABLE_STATS . " SET counter = counter + 1 WHERE scenario = %s AND object_id = %d AND stat_date = %d", $scenario, $object_id, $date ) );
    }
  }

  /**
   * Get counter
   * @param  string  $scenario
   * @param integer $object_id
   * @return integer
   */
  public static function get_counter( $scenario, $object_id = 0 ) {

    global $wpdb;
    $counter = $wpdb->get_var( $wpdb->prepare( "SELECT SUM(counter) AS counter FROM " . $wpdb->prefix . PR_TABLE_STATS . " WHERE scenario = %s AND object_id = %d GROUP BY object_id", $scenario, $object_id ), 0, 0 );
    return is_null( $counter ) ? 0 : $counter;
  }

  /**
   * Get statistics data for charts
   * @param  string  $scenario
   * @param string $end_date ( format: Y-m-d )
   * @param  integer $sub_days
   * @return array
   */
  protected function _get_chart_data( $scenario, $end_date, $sub_days = 30 ) {

    global $wpdb;

    $end_date = strtotime( $end_date );
    $start_date = strtotime( '-' . $sub_days . ' days', $end_date );

    $dates = PR_Utils::get_days( $start_date, $end_date, 'm-d' );
    $dates = array_fill_keys( $dates, 0 );

    $sql = $wpdb->prepare( "SELECT FROM_UNIXTIME( stat_date, '%%m-%%d' ) AS stat_date, SUM(counter) AS counter
    FROM " . $wpdb->prefix . PR_TABLE_STATS . " WHERE scenario = %s
    AND stat_date BETWEEN %d AND %d GROUP BY stat_date ORDER BY stat_date", $scenario, $start_date, $end_date );
    $results = $wpdb->get_results( $sql );
    if ( $results ) {
      foreach ( $results as $result ) {
        $dates[$result->stat_date] = $result->counter;
      }
    }

    return $dates;
  }
}

$pr_stats = new PR_Stats();
