<?php
define( "TPL_PLUGIN_PATH", dirname(__FILE__ ) . '/../' );
define( "TPL_LIBS_PATH", dirname(__FILE__) . '/' );
define( "TPL_VENDORS_PATH", TPL_LIBS_PATH . 'vendors/' );
define( "TPL_CORE_PATH", TPL_PLUGIN_PATH . 'core/' );
define( "TPL_THEME_PATH", TPL_PLUGIN_PATH . 'themes/' );
define( "TPL_EXTENSIONS_PATH", TPL_PLUGIN_PATH . 'extensions/' );
define( "TPL_SERVER_PATH", TPL_PLUGIN_PATH . 'server/' );
define( "TPL_CONNECTORS_PATH", TPL_SERVER_PATH . 'connectors/' );

/* API */
define( "TPL_API_PATH", TPL_PLUGIN_PATH . 'api/' );
define( "TPL_HPUB_PATH", TPL_API_PATH . 'hpub/' );
define( "TPL_TMP_PATH", TPL_API_PATH . 'tmp/' );
define( "TPL_PREVIEW_TMP_PATH", TPL_TMP_PATH . 'preview/' );
define( "TPL_SHELF_PATH", TPL_API_PATH . 'shelf/' );

define( "TPL_PLUGIN_URI", plugin_dir_url(TPL_LIBS_PATH) );
define( "TPL_CORE_URI", TPL_PLUGIN_URI . 'core/' );
define( "TPL_SHELF_URI", TPL_PLUGIN_URI . 'api/shelf/' );
define( "TPL_ASSETS_URI", TPL_PLUGIN_URI. 'assets/' );
define( "TPL_HPUB_URI", TPL_PLUGIN_URI . 'api/hpub/' );
define( "TPL_THEME_URI", TPL_PLUGIN_URI . 'themes/' );
define( "TPL_PREVIEW_URI", TPL_PLUGIN_URI . 'api/tmp/preview/' );

/* Packager */
define( "TPL_EDITION_MEDIA", 'gfx/' );

/* Custom posts type */
define( "TPL_EDITION", 'tpl_edition' );
define( "P2P_EDITION_CONNECTION", 'edition_post' );

/* Custom taxonomies */
define( "TPL_EDITORIAL_PROJECT", 'tpl_editorial_project' );

/* Database */
define( "TPL_TABLE_RECEIPTS", 'pr_receipts' );
define( "TPL_TABLE_RECEIPT_TRANSACTIONS", 'pr_receipt_transactions' );
define( "TPL_TABLE_PURCHASED_ISSUES", 'pr_purchased_issues' );
define( "TPL_TABLE_APNS_TOKENS", 'pr_apns_tokens' );

/* ADBundle */
define( "TPL_AD_BUNDLE", 'tpl_adbundle' );
