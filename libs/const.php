<?php
	define ("TPL_PLUGIN_PATH", dirname(__FILE__ ).'/../');
	define ("TPL_LIBS_PATH", dirname(__FILE__));
	define ("TPL_VENDORS_PATH", dirname(__FILE__) . '/vendors/');
	define ("TPL_THEME_PATH", dirname(__FILE__) . '/../themes/');
	define ("TPL_HPUB_DIR", dirname(__FILE__) . '/../api/hpub/');
	define ("TPL_TMP_DIR", dirname(__FILE__) . '/../api/tmp');
	define ("TPL_PREVIEW_DIR", dirname(__FILE__) . '/../api/preview');
	define ("TPL_SHELF_DIR", dirname(__FILE__) . '/../api/shelf/');
	define ("TPL_SHELF_URI", plugin_dir_url(TPL_LIBS_PATH) . 'api/shelf/');
	define ("TPL_PLUGIN_ASSETS", plugin_dir_url(TPL_LIBS_PATH). '/assets/');
	define ("TPL_HPUB_URI", plugin_dir_url(TPL_LIBS_PATH) . 'api/hpub/');
	define ("TPL_PLUGIN_URI", plugin_dir_url(TPL_LIBS_PATH));

	/* Packager */
	define ("TPL_EDITION_MEDIA", 'gfx/');

	/* Custom posts type */
	define ("TPL_EDITION", 'tpl_edition');

	/* Custom taxonomies */
	define ("TPL_EDITORIAL_PROJECT", 'tpl_editorial_project');

	/* Pressroom Pro folder */
	define ("TPL_PRESSROOM_PRO", dirname(__FILE__) . '/../pressroom-pro');


	//PRO
	define ("TPL_PRESSROOM_PRO_ASSETS", plugin_dir_url(TPL_LIBS_PATH). 'pressroom-pro/assets');

	//Database
	define ("TPL_TABLE_RECEIPTS", 'tpl_receipts');
	define ("TPL_TABLE_PURCHASED_ISSUES", 'tpl_purchased_issues');
	define ("TPL_TABLE_APNS_TOKENS", 'tpl_apns_tokens');

	//ADB
	define ("TPL_ADB_PACKAGE", 'tpl_adb_package');
	define ("TPL_EDITION_ADB", 'adb_package/');
