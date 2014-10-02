<?php
/**
 * TPL database class.
 * Add custom tables into database
 */
class TPL_Setup
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
      if ( $check_libs )
         array_push( $errors, __( "Missing required extensions: <b>" . implode( ', ', $check_libs ) . "</b>", 'pressroom_setup' ) );

      if ( !self::_setup_db_tables() )
         array_push( $errors, __( "Error creating required tables. Check your database permissions.", 'pressroom_setup' ) );

      if ( !self::_setup_filesystem() )
         array_push( $errors, __( "Error creating required directory: <b>&quot;" . TPL_PLUGIN_PATH . "api/&quot;</b>. Check your write files permissions.", 'pressroom_setup' ) );

      if ( !empty( $errors ) )
         return $errors;

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
      $table_receipts = $wpdb->prefix . TPL_TABLE_RECEIPTS;
      $table_purchased_issues = $wpdb->prefix . TPL_TABLE_PURCHASED_ISSUES;
      $table_apns_tokens = $wpdb->prefix . TPL_TABLE_APNS_TOKENS;

    	$charset_collate = '';
      if ( !empty( $wpdb->charset ) ) {
         $charset_collate = "DEFAULT CHARACTER SET {$wpdb->charset}";
    	}

      if ( ! empty( $wpdb->collate ) ) {
         $charset_collate .= " COLLATE {$wpdb->collate}";
    	}

    	$sql_receipts = "CREATE TABLE IF NOT EXISTS $table_receipts (
        transaction_id VARCHAR(30),
        app_id VARCHAR(255),
        user_id VARCHAR(255),
        product_id VARCHAR(255),
        type VARCHAR(30),
        base64_receipt TEXT,
        PRIMARY KEY(transaction_id, app_id, user_id)
      ) $charset_collate; ";

      $sql_purchased_issues = "CREATE TABLE IF NOT EXISTS $table_purchased_issues (
        app_id VARCHAR(255),
        user_id VARCHAR(255),
        product_id VARCHAR(255),
        PRIMARY KEY(app_id, user_id, product_id)
      ) $charset_collate; ";

      $sql_apns_tokens = "CREATE TABLE IF NOT EXISTS $table_apns_tokens (
        app_id VARCHAR(255),
        user_id VARCHAR(255),
        apns_token VARCHAR(64),
        PRIMARY KEY(app_id, user_id, apns_token)
      ) $charset_collate;";

      require_once ( ABSPATH . 'wp-admin/includes/upgrade.php' );
      dbDelta( $sql_receipts );
      dbDelta( $sql_purchased_issues );
      dbDelta( $sql_apns_tokens  );

      return ( $wpdb->get_var("SHOW TABLES LIKE '$table_receipts'") == $table_receipts
         && $wpdb->get_var("SHOW TABLES LIKE '$table_purchased_issues'") == $table_purchased_issues
         && $wpdb->get_var("SHOW TABLES LIKE '$table_apns_tokens'") == $table_apns_tokens );
   }

   /**
    * Install the plugin folders
    *
    * @return boolean
    */
   private static function _setup_filesystem() {

      $api_dir = TPL_Utils::make_dir( TPL_PLUGIN_PATH, 'api' );
      if ( !$api_dir ) {
         return false;
      }

      TPL_Utils::make_dir( TPL_API_DIR, 'hpub' );
      TPL_Utils::make_dir( TPL_API_DIR, 'tmp' );
      TPL_Utils::make_dir( TPL_API_DIR, 'preview' );
      TPL_Utils::make_dir( TPL_API_DIR, 'shelf' );

      return true;
   }
}
