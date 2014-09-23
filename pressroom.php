<?php
	/**
	* Plugin Name: TPL - Pressroom Pro
	* Plugin URI: https://bitbucket.org/theprintlabs/tpl-baker-wp-plugin/wiki/Home
	* Description: Wordpress Pressroom.
	* Version: 0.1
	* Author: ThePrintLabs
	* Author URI: http://www.theprintlabs.com
	* License: GPLv2
	*
	* 	 _____                                               _____
	*  |  __ \                                             |  __ \
	*  | |__) | __ ___  ___ ___ _ __ ___   ___  _ __ ___   | |__) | __ ___
	*  |  ___/ '__/ _ \/ __/ __| '__/ _ \ / _ \| '_ ` _ \  |  ___/ '__/ _ \
	*  | |   | | |  __/\__ \__ \ | | (_) | (_) | | | | | | | |   | | | (_) |
	*  |_|   |_|  \___||___/___/_|  \___/ \___/|_| |_| |_| |_|   |_|  \___/
	*
	*  The PrintLabs ©
	*/

	if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

	require_once( 'libs/const.php' );
	require_once( TPL_LIBS_PATH . '/utils.php' );
	require_once( 'classes/database.php' );
	require_once( 'classes/config/redux.php' );
	require_once( 'classes/config/tgm.php' );
	require_once( 'classes/edition.php' );
	require_once( 'classes/press_list.php' );
	require_once( 'classes/editorial_project.php' );
	require_once( 'classes/theme.php' );
	require_once( 'classes/packager.php' );
	require_once( 'classes/adb_package.php' );
	require_once( 'classes/preview.php' );

	class TPL_Pressroom {

		protected $_push;
		protected $_post_type;
		protected $_number_of_max_edition;
		protected $_edition;
		protected $_adb_package;
		protected $_pages;
		public 		$_configs;
		/* protected $_themes; */

		public function __construct() {
			// Display the admin notification
			add_action( 'admin_notices', array( $this, 'check_libs_notice' ) ) ;
			add_action( 'p2p_init', array( $this, 'register_connection_type' ) );
			register_activation_hook( __FILE__, array( $this, 'setup' ) );
			add_action('admin_menu', array($this,'init_preview'));

			$this->load_edition();
			$this->load_adb_package();

			if (!isset($GLOBALS['tpl_options'])) {
				$this->_configs = get_option('tpl_options', array());
			}
		}

		 public function setup() {
        $database = new TPL_Database;
				$database-> install_database();
     }

		public function load_edition() {
			if ($this->_edition === null) {
				$this->_edition = new TPL_Edition();
			}
		}

		public function load_adb_package() {
			if ($this->_adb_package === null) {
				$this->_adb_package = new TPL_Adb_Package();
			}
		}

		public function init_preview() {
    	add_submenu_page( null, 'Preview screen', 'Preview', 'manage_options', 'preview-page', array($this, 'init_preview_callback'));
		}

		public function init_preview_callback() {

			echo '<div class="wrap"><div id="icon-tools" class="icon32"></div>';
				echo '<h2>Edition Preview</h2>';
			echo '</div>';
			
		}

		/**
		 * check_libs_notice function.
		 *
		 * @access public
		 * @static
		 * @return void
		 */
		public static function check_libs_notice() {
			$extension = self::check_libs();
			if($extension) {
			    $html = '<div class="error">';
			    $html.= "<p>Missing extension $extension </p>";
			    $html.= '</div>';
			    echo $html;
			}
		}

		/**
		 * check_libs function.
		 *
		 * @access private
		 * @return bool
		 */
		private static function check_libs() {
			$extensions = array('zlib', 'zip');
			foreach ($extensions as $extension) {
				if(!extension_loaded($extension)) {
					return $extension;
				}
			}
			return false;
		}



	/**
		* register_connection_type function.
		*
		* @access public
		* @return void
		*/
		public function register_connection_type() {
			echo "register".microtime();
			$registered = array( 'post', TPL_ADB_PACKAGE );
			$post_types = $this->_configs['tpl-custom-post-type'];
			foreach($post_types as $post_type) {
				$registered[] = $post_type;
			}

			p2p_register_connection_type( array(
					'name' => 'edition_post',
					'from' => $registered,
					'to' => TPL_EDITION,
					'sortable' 		=> false,
					'admin_box' 	=> array(
					'show' 		=> 'from',
					'context' 	=> 'advanced'
				),
				'fields' => array(
					'state' => array(
							'title' 		=> 'Post state',
							'type' 			=> 'checkbox',
							'default_state' => 1,
					),
					'template' 		=> array(
							'title' 	=> '',
							'type' 		=> 'hidden',
							'values'	=>	array(),
					),
					'order' => array(
							'title' 	=> '',
							'type' 		=> 'hidden',
							'default' 	=> 0,
							'values' 	=>	array(),
					),
				)
			));
		}
	}

	// instantiate the plugin class
	$tpl_pressroom = new TPL_Pressroom();
	$preview = new Tpl_Preview();
