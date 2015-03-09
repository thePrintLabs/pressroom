<?php
/**
* PressRoom database class.
* Add custom tables into database
*/
class PR_Setup
{

  public function __construct() {}

    /**
    * Plugin installation
    *
    * @return boolean or array of error messages
    */
    public static function install() {

      $errors = array();
      $check_libs = self::_check_php_libs();
      if ( $check_libs ) {
        array_push( $errors, __( "Missing required extensions: <b>" . implode( ', ', $check_libs ) . "</b>", 'pressroom_setup' ) );
      }
      if ( !self::_setup_db_tables() ) {
        array_push( $errors, __( "Error creating required tables. Check your database permissions.", 'pressroom_setup' ) );
      }
      if ( !self::_setup_filesystem() ) {
        array_push( $errors, __( "Error creating required directory: <b>&quot;" . PR_PLUGIN_PATH . "api/&quot;</b> or <b>&quot;" . PR_UPLOAD_PATH . "api/&quot;</b>. Check your write files permissions.", 'pressroom_setup' ) );
      }
      if ( !empty( $errors ) ) {
        return $errors;
      }

      return false;
    }

    /**
    * Check if the required libraries are installed
    *
    * @return boolean or array of errors
    */
    private static function _check_php_libs() {

      $errors = array();
      $extensions = array( 'zlib', 'zip', 'libxml' );
      foreach ( $extensions as $extension ) {

        if( !extension_loaded( $extension ) ) {
          array_push( $errors, $extension );
        }
      }

      if ( !empty( $errors ) )
      return $errors;

      return false;
    }

    /**
    * Install supporting tables
    *
    * @return boolean
    */
    private static function _setup_db_tables() {

      global $wpdb;
      $table_receipts = $wpdb->prefix . PR_TABLE_RECEIPTS;
      $table_purchased_issues = $wpdb->prefix . PR_TABLE_PURCHASED_ISSUES;
      $table_auth_tokens = $wpdb->prefix . PR_TABLE_AUTH_TOKENS;
      $table_stats = $wpdb->prefix . PR_TABLE_STATS;

      $charset_collate = '';
      if ( !empty( $wpdb->charset ) ) {
        $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
      }

      if ( ! empty( $wpdb->collate ) ) {
        $charset_collate .= " COLLATE {$wpdb->collate}";
      }

      $sql_receipts = "CREATE TABLE IF NOT EXISTS $table_receipts (
      receipt_id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
      app_bundle_id VARCHAR(128),
      device_id VARCHAR(256),
      transaction_id VARCHAR(32),
      base64_receipt TEXT CHARACTER SET ascii COLLATE ascii_bin,
      product_id VARCHAR(256),
      type VARCHAR(32),
      PRIMARY KEY (receipt_id),
      INDEX app_and_user USING BTREE (app_bundle_id, device_id) COMMENT ''
      ) $charset_collate; ";

      $sql_purchased_issues = "CREATE TABLE IF NOT EXISTS $table_purchased_issues (
      app_id VARCHAR(255),
      user_id VARCHAR(255),
      product_id VARCHAR(255),
      PRIMARY KEY(app_id, user_id, product_id)
      ) $charset_collate; ";

      $sql_auth_tokens = "CREATE TABLE IF NOT EXISTS $table_auth_tokens (
      app_id VARCHAR(255),
      user_id VARCHAR(255),
      access_token VARCHAR(255),
      created_time int(10) UNSIGNED NOT NULL,
      expires_in int(10) UNSIGNED NOT NULL,
      PRIMARY KEY(app_id, user_id, access_token)
      ) $charset_collate; ";

      $sql_stats = "CREATE TABLE IF NOT EXISTS $table_stats (
      scenario VARCHAR(128),
      object_id INT(10) UNSIGNED NOT NULL,
      stat_date INT(10) UNSIGNED NOT NULL,
      counter INT(10) UNSIGNED NOT NULL,
      PRIMARY KEY(scenario, stat_date, object_id)
      ) $charset_collate; ";

      require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql_receipts );
      dbDelta( $sql_purchased_issues );
      dbDelta( $sql_auth_tokens );
      dbDelta( $sql_stats );

      return ( $wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") == $table_receipts
      && $wpdb->get_var("SHOW TABLES LIKE '$table_purchased_issues'") == $table_purchased_issues
      && $wpdb->get_var("SHOW TABLES LIKE '$table_auth_tokens'") == $table_auth_tokens
      && $wpdb->get_var("SHOW TABLES LIKE '$table_stats'") == $table_stats );

      return true;
    }

    /**
    * Install the plugin folders
    *
    * @return boolean
    */
    private static function _setup_filesystem() {

      $wp_upload_dir = wp_upload_dir();
      $api_dir = PR_Utils::make_dir( PR_PLUGIN_PATH, 'api' );
      $upload_dir = PR_Utils::make_dir( $wp_upload_dir['basedir'], 'pressroom' );

      if ( !$api_dir || !$upload_dir ) {
        return false;
      }

      $api_dir = $api_dir && PR_Utils::make_dir( PR_API_PATH, 'tmp' );
      $api_dir = $api_dir && PR_Utils::make_dir( PR_TMP_PATH, 'preview' );

      $upload_dir = PR_Utils::make_dir( PR_UPLOAD_PATH, 'hpub' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'web' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'shelf' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'themes' );

      if ( false !== ( $wp_load_path = self::_search_wp_load() ) ) {
        file_put_contents( PR_CORE_PATH . 'preview' . DS . '.pr_path', $wp_load_path );
      }

      return !$api_dir && !$upload_dir ? false : true;
    }

    /**
    * Search wp-load file in wordpress directory
    * @return string or boolean false
    */
    private static function _search_wp_load() {

      $home_path = ABSPATH;
      // Check in standard WordPress position
      if ( file_exists( $home_path . 'wp-load.php' ) ) {
        return $home_path . 'wp-load.php';
      }
      else {
        $dir = new RecursiveDirectoryIterator( $home_path );
        $it = new RecursiveIteratorIterator( $dir );
        $regex = new RegexIterator( $it, '/wp-load.php$/i', RecursiveRegexIterator::GET_MATCH );
        $files = iterator_to_array( $regex );
        if ( !empty( $files ) ) {
          return key( $files );
        }
      }
      return false;
    }
  }
