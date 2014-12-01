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

  public function register_dashboard_scripts() {

    wp_register_script( 'chartjs', PR_ASSETS_URI . '/js/chartjs.min.js', array( 'jquery'), '1.0', true );
    wp_enqueue_script( 'chartjs' );
  }

  public function add_pr_stats_db_widgets() {
    wp_add_dashboard_widget(
      'download_editions_db_widget',
      __( 'Downloaded editions' ),
      array( $this, 'render_downloaded_editions_widget' )
    );
  }

  public function render_downloaded_editions_widget() {

    $data = $this->_get_chart_data_downloaded_editions( date('Y-m-d')  );
    echo '<canvas id="myChart" width="400" height="400"></canvas>';
    echo '<script>';
    // Get context with jQuery - using jQuery's .get() method.
    echo 'jQuery(document).ready(function($){

      var data = ' . $data . ';



      var ctx = $("#myChart").get(0).getContext("2d");';
    // This will get the first returned node in the jQuery collection.
    echo 'var myNewChart = new Chart(ctx).Line(data); });';
    echo '</script>';
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

  protected function _get_chart_data_downloaded_editions( $end_date, $sub_days = 15 ) {

    global $wpdb;
    $end_date = strtotime( $end_date );
    $start_date = strtotime( '-' . $sub_days . ' days', $end_date );

    $dates = PR_Utils::get_days( $start_date, $end_date, 'y-m-d' );
    $dates = array_fill_keys( $dates, 0 );

    $sql = $wpdb->prepare( "SELECT FROM_UNIXTIME( stat_date, '%%y-%%m-%%d' ) AS stat_date, SUM(counter) AS counter FROM " . $wpdb->prefix . PR_TABLE_STATS . " WHERE scenario = 'download_edition'
    AND stat_date BETWEEN %d AND %d GROUP BY stat_date ORDER BY stat_date", $start_date, $end_date );
    $results = $wpdb->get_results( $sql );
    if ( $results ) {
      foreach ( $results as $result ) {
        $dates[$result->stat_date] = $result->counter;
      }
    }

    return json_encode( array(
      'labels' => array_keys( $dates ),
      'datasets' => array(
        array(
          'label' => "My Second dataset",
          'fillColor' => "rgba(151,187,205,0.2)",
          'strokeColor' => "rgba(151,187,205,1)",
          'pointColor' => "rgba(151,187,205,1)",
          'pointStrokeColor' => "#fff",
          'pointHighlightFill' => "#fff",
          'pointHighlightStroke' => "rgba(151,187,205,1)",
          'data' => array_values( $dates )
        )
      )
    ) );
  }
}

$pr_stats = new PR_Stats();
