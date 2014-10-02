<?php
/**
* Plugin Name: TPL - Pressroom Pro
* Plugin URI: https://bitbucket.org/theprintlabs/tpl-baker-wp-plugin/wiki/Home
* Description: Wordpress Pressroom.
* Version: 1.0
* Author: ThePrintLabs
* Author URI: http://www.theprintlabs.com
* License: GPLv2
*
*   _____                                               _____
*  |  __ \                                             |  __ \
*  | |__) | __ ___  ___ ___ _ __ ___   ___  _ __ ___   | |__) | __ ___
*  |  ___/ '__/ _ \/ __/ __| '__/ _ \ / _ \| '_ ` _ \  |  ___/ '__/ _ \
*  | |   | | |  __/\__ \__ \ | | (_) | (_) | | | | | | | |   | | | (_) |
*  |_|   |_|  \___||___/___/_|  \___/ \___/|_| |_| |_| |_|   |_|  \___/
*
*  thePrintLabs Ltd. ©
*/

if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

require_once( 'libs/const.php' );
require_once( TPL_LIBS_PATH . 'utils.php' );
require_once( TPL_CLASSES_PATH . 'setup.php' );
require_once( TPL_CLASSES_PATH . 'config/redux.php' );
require_once( TPL_CLASSES_PATH . 'config/tgm.php' );

require_once( TPL_CLASSES_PATH . 'edition/edition.php' );
require_once( TPL_CLASSES_PATH . 'edition/editorial_project.php' );

require_once( TPL_CLASSES_PATH . 'press_list.php' );
require_once( TPL_CLASSES_PATH . 'theme.php' );
require_once( TPL_CLASSES_PATH . 'packager/packager.php' );
require_once( TPL_CLASSES_PATH . 'adbundle.php' );
require_once( TPL_CLASSES_PATH . 'preview.php' );

class TPL_Pressroom
{
	public $configs;

	protected $_edition;
	protected $_adbundle;

	public function __construct() {

		if ( !is_admin() ) {
			return;
		}

		$this->load_configs();

		$this->instance_edition();
		$this->instance_adbundle();

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
		add_action( 'p2p_init', array( $this, 'register_post_connection' ) );
	}

	/**
	 * Activation hook
	 * @void
	 */
	public function plugin_activation() {

		$errors = TPL_Setup::install();
		if ($errors !== false) {
			$html = '<h1>' . __('Pressroom') . '</h1>
			<p><b>' .__( 'An error occurred during activation. Please see details below.', 'pressroom_setup' ). '</b></p>
			<ul><li>' .implode( "</li><li>", $errors ). '</li></ul>';
			wp_die( $html, __( 'Pressroom activation error', 'pressroom_setup' ), ('back_link=true') );
		}
	}

	/**
	 * Load plugin configuration settings
	 * @void
	 */
	public function load_configs() {

		if ( is_null( $this->configs ) ) {
			$this->configs = get_option('tpl_options', array(
				'custom_post_type' => array()
			));
		}
	}

	/**
	 * Instance a new edition object
	 * @void
	 */
	public function instance_edition() {

		if ( is_null( $this->_edition ) ) {
			$this->_edition = new TPL_Edition;
		}
	}

	/**
	 * Instance a new adbundle object
	 * @void
	 */
	public function instance_adbundle() {

		if ( is_null( $this->_adbundle ) ) {
			$this->_adbundle = new TPL_ADBundle();
		}
	}

	/**
	 * Add connection between the edition and the posts
	 *
	 * @void
	 */
	public function register_post_connection() {

		$types = array( 'post', TPL_AD_BUNDLE );
		$custom_types = $this->_load_custom_post_types();
		$types = array_merge( $types, $custom_types );

		p2p_register_connection_type( array(
				'name' 		=> 'edition_post',
				'from'	 	=> $types,
				'to' 			=> TPL_EDITION,
				'sortable' 	=> false,
				'admin_box' => array(
					'show' 		=> 'from',
					'context'	=> 'advanced'
				),
				'fields' => array(
					'state' => array(
						'title'		=> __( 'Included in edition', 'pressroom' ),
						'type'		=> 'checkbox',
						'default'	=> 1,
					),
					'template' => array(
						'title' 		=> '',
						'type' 		=> 'hidden',
						'values'		=>	array(),
					),
					'order' => array(
						'title'		=> '',
						'type' 		=> 'hidden',
						'default' 	=> 0,
						'values' 	=>	array(),
					),
				)
		) );
	}

	/**
	 * Determine if is add or edit page
	 * @param  string  $new_edit
	 * @return boolean
	 */
	public static function is_edit_page() {

		global $pagenow;
    	if ( !is_admin() ) {
			return false;
		}

		return in_array( $pagenow, array( 'post.php' ) );
	}

	/**
	 * Load custom post types configured in settings page
	 * @return array - custom post types
	 */
	protected function _load_custom_post_types() {

		$types = array();
		if ( !empty( $this->configs ) && isset( $this->configs['custom_post_type'] ) ) {
			foreach ( $this->configs['custom_post_type'] as $post_type ) {

				array_push( $types, $post_type );
			}
		}

		return $types;
	}
}

/* instantiate the plugin class */
$tpl_pressroom = new TPL_Pressroom();
$tpl_preview = new TPL_Preview();
