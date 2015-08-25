<?php
define( "DS", DIRECTORY_SEPARATOR );

// Core
define( "PR_ROOT", plugin_dir_path( __FILE__ ) );
define( "PR_PAGES_PATH", trailingslashit( PR_ROOT . 'pages' ) );
define( "PR_TAXONOMIES_PATH", trailingslashit( PR_ROOT . 'taxonomies' ) );
define( "PR_POST_TYPES_PATH", trailingslashit( PR_ROOT . 'post_types' ) );
define( "PR_LIBS_PATH", trailingslashit( PR_ROOT . 'libs' ) );

// Packager
define( "PR_PACKAGER_PATH", trailingslashit( PR_ROOT . 'packager' ) );
define( "PR_PACKAGER_CONNECTORS_PATH", trailingslashit( PR_PACKAGER_PATH . 'connectors' ) );
define( "PR_PACKAGER_EXPORTERS_PATH", trailingslashit( PR_PACKAGER_PATH . 'exporters' ) );

// Server
define( "PR_SERVER_PATH", trailingslashit( PR_ROOT . 'server' ) );
define( "PR_SERVER_CONNECTORS_PATH", trailingslashit( PR_SERVER_PATH . 'connectors' ) );

// Peview
define( "PR_PREVIEW_PATH", trailingslashit( PR_ROOT . 'preview' ) );

// API
define( "PR_API_PATH", trailingslashit( PR_ROOT . 'api' ) );
define( "PR_TMP_PATH", trailingslashit( PR_API_PATH . 'tmp' ) );
define( "PR_PREVIEW_TMP_PATH", trailingslashit( PR_TMP_PATH . 'preview' ) );

// URL
define( "PR_PLUGIN_URI", plugin_dir_url( PR_LIBS_PATH ) );
define( "PR_CORE_URI", PR_PLUGIN_URI . 'core/' );
define( "PR_ASSETS_URI", PR_PLUGIN_URI. 'assets/' );
define( "PR_PREVIEW_URI", PR_PLUGIN_URI . 'api/tmp/preview/' );

// UPLOADS
$upload_dir = wp_upload_dir();
define( "PR_UPLOAD_PATH", $upload_dir['basedir'] . '/pressroom/' );
define( "PR_HPUB_PATH", trailingslashit( PR_UPLOAD_PATH . 'hpub' ) );
define( "PR_WEB_PATH", trailingslashit( PR_UPLOAD_PATH . 'web' ) );
define( "PR_SHELF_PATH", trailingslashit( PR_UPLOAD_PATH . 'shelf' ) );
define( "PR_IOS_SETTINGS_PATH", PR_UPLOAD_PATH . 'settings/' );

define( "PR_UPLOAD_URI", $upload_dir['baseurl'] . '/pressroom/' );
define( "PR_HPUB_URI", PR_UPLOAD_URI . 'hpub/' );
define( "PR_WEB_URI", PR_UPLOAD_URI . 'web/' );
define( "PR_SHELF_URI", PR_UPLOAD_URI . 'shelf/' );
define( "PR_IOS_SETTINGS_URI", PR_UPLOAD_URI . 'settings/' );

/* THEMES*/
define( "PR_THEMES_PATH", trailingslashit( PR_UPLOAD_PATH . 'themes' ) );

define( "PR_THEME_URI", PR_UPLOAD_URI . 'themes/' );

// @TODO change on production
define( "PR_API_URL", 'http://press-room.io/' );
define( "PR_API_EDD_URL", PR_API_URL . 'edd-api/' );
/* Packager */
define( "PR_EDITION_MEDIA", 'gfx/' );

// Custom post types
define( "PR_EDITION", 'pr_edition' );
define( "P2P_EDITION_CONNECTION", 'edition_post' );

// Custom taxonomies
define( "PR_EDITORIAL_PROJECT", 'pr_editorial_project' );

// Database
define( "PR_TABLE_RECEIPTS", 'pr_receipts' );
define( "PR_TABLE_RECEIPT_TRANSACTIONS", 'pr_receipt_transactions' );
define( "PR_TABLE_PURCHASED_ISSUES", 'pr_purchased_issues' );
define( "PR_TABLE_AUTH_TOKENS", 'pr_auth_tokens' );
define( "PR_TABLE_STATS" , 'pr_statistics' );
define( "PR_TABLE_LOGS" , 'pr_logs' );
