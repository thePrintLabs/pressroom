<?php
  /**
   * TPL database class.
   * Add custom tables into database
   */
  class TPL_Database {

    /**
     * TPL_Database costructor
     */
    public function TPL_Database() {

    }

    public function install_database() {
      global $wpdb;

    	$table_receipts = $wpdb->prefix . TPL_TABLE_RECEIPTS;
      $table_purchased_issues = $wpdb->prefix . TPL_TABLE_PURCHASED_ISSUES;
      $table_apns_tokens = $wpdb->prefix . TPL_TABLE_APNS_TOKENS;

    	/*
    	 * We'll set the default character set and collation for this table.
    	 * If we don't do this, some characters could end up being converted
    	 * to just ?'s when saved in our table.
    	 */
    	$charset_collate = '';

    	if ( ! empty( $wpdb->charset ) ) {
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

    	require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
    	dbDelta( $sql_receipts );
      dbDelta( $sql_purchased_issues );
      dbDelta( $sql_apns_tokens  );
    }
  }
$database = new TPL_Database();
