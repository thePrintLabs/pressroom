<?php
define( "PR_VERSION", "1.1.0" );
define( "PR_PRODUCT_NAME", "PressRoom PRO monthly license" );

define( "DS", DIRECTORY_SEPARATOR );
define( "PR_PLUGIN_PATH", realpath( __DIR__ . '/../' ) . '/' );
define( "PR_LIBS_PATH", PR_PLUGIN_PATH . 'libs/' );
define( "PR_LIBS_PR_PATH", PR_LIBS_PATH . 'PR/' );
define( "PR_EXTENSIONS_PATH", PR_PLUGIN_PATH . 'extensions/' );
define( "PR_CORE_PATH", PR_PLUGIN_PATH . 'core/' );
define( "PR_PAGES_PATH", PR_CORE_PATH . 'pages/' );
define( "PR_CONFIGS_PATH", PR_CORE_PATH . 'configs/' );

/* PACKAGER */
define( "PR_PACKAGER_PATH", PR_CORE_PATH . 'packager/' );
define( "PR_PACKAGER_CONNECTORS_PATH", PR_PACKAGER_PATH . 'connectors/' );
define( "PR_PACKAGER_EXPORTERS_PATH", PR_PACKAGER_PATH . 'exporters/' );

/* SERVER */
define( "PR_SERVER_PATH", PR_PLUGIN_PATH . 'server/' );
define( "PR_CONNECTORS_PATH", PR_SERVER_PATH . 'connectors/' );

/* API */
define( "PR_API_PATH", PR_PLUGIN_PATH . 'api/' );
define( "PR_TMP_PATH", PR_API_PATH . 'tmp/' );
define( "PR_PREVIEW_TMP_PATH", PR_TMP_PATH . 'preview/' );

define( "PR_PLUGIN_URI", plugin_dir_url( PR_LIBS_PATH ) );
define( "PR_CORE_URI", PR_PLUGIN_URI . 'core/' );
define( "PR_ASSETS_URI", PR_PLUGIN_URI. 'assets/' );
define( "PR_PREVIEW_URI", PR_PLUGIN_URI . 'api/tmp/preview/' );

/* UPLOADS */
$upload_dir = wp_upload_dir();
define( "PR_UPLOAD_PATH", $upload_dir['basedir'] . '/pressroom/' );
define( "PR_HPUB_PATH", PR_UPLOAD_PATH . 'hpub/' );
define( "PR_WEB_PATH", PR_UPLOAD_PATH . 'web/' );
define( "PR_SHELF_PATH", PR_UPLOAD_PATH . 'shelf/' );

define( "PR_UPLOAD_URI", $upload_dir['baseurl'] . '/pressroom/' );
define( "PR_HPUB_URI", PR_UPLOAD_URI . 'hpub/' );
define( "PR_WEB_URI", PR_UPLOAD_URI . 'web/' );
define( "PR_SHELF_URI", PR_UPLOAD_URI . 'shelf/' );

/* THEMES*/
define( "PR_THEMES_PATH", PR_UPLOAD_PATH . 'themes/' );
define( "PR_THEME_URI", PR_UPLOAD_URI . 'themes/' );
define( "PR_STARTERR_THEME", 'starterr' );
define( "PR_STARTERR_ZIP", 'starterr.zip' );

define( "PR_API_URL", 'http://press-room.io/' );
/* Packager */
define( "PR_EDITION_MEDIA", 'gfx/' );

/* Custom posts type */
define( "PR_EDITION", 'pr_edition' );
define( "P2P_EDITION_CONNECTION", 'edition_post' );

/* Custom taxonomies */
define( "PR_EDITORIAL_PROJECT", 'pr_editorial_project' );

/* Database */
define( "PR_TABLE_RECEIPTS", 'pr_receipts' );
define( "PR_TABLE_RECEIPT_TRANSACTIONS", 'pr_receipt_transactions' );
define( "PR_TABLE_PURCHASED_ISSUES", 'pr_purchased_issues' );
define( "PR_TABLE_AUTH_TOKENS", 'pr_auth_tokens' );
define( "PR_TABLE_STATS" , 'pr_statistics' );
define( "PR_TABLE_LOGS" , 'pr_logs' );
