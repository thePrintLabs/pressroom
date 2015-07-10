<?php
/**
 * Plugin Name: Pressroom Pro
 * Plugin URI: http://press-room.io/
 * Description: PressRoom turns Wordpress into a multi channel publishing environment.
 * Version: 1.1.1
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
 *  Copyright © 2014 - thePrintLabs Ltd.
 */

if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

require_once( __DIR__ . '/core/define.php' );
require_once( __DIR__ . '/core/settings.php' );
require_once( PR_LIBS_PR_PATH . 'utils.php' );

require_once( PR_CORE_PATH . 'setup.php' );
require_once( PR_CORE_PATH . 'edition/edition.php' );
require_once( PR_CORE_PATH . 'edition/editorial_project.php' );
require_once( PR_CORE_PATH . 'posts.php' );
require_once( PR_CORE_PATH . 'theme.php' );
require_once( PR_CORE_PATH . 'addons.php' );
require_once( PR_CORE_PATH . 'packager/packager.php' );
require_once( PR_CORE_PATH . 'preview/preview.php' );
require_once( PR_CORE_PATH . 'api.php' );
require_once( PR_CORE_PATH . 'logs.php' );

require_once( PR_CONFIGS_PATH . 'edd.php' );
//require_once( PR_CONFIGS_PATH . 'p2p.php' );
require_once( PR_LIBS_PATH . 'posts-to-posts/posts-to-posts.php' );

require_once( PR_SERVER_PATH . 'server.php' );

require_once( PR_LIBS_PR_PATH . 'UI/metabox.php' );
require_once( PR_LIBS_PR_PATH . 'UI/press_list.php' );
require_once( PR_LIBS_PR_PATH . 'UI/logs_list.php' );
require_once( PR_LIBS_PR_PATH . 'UI/gallery_name.php' );
require_once( PR_PAGES_PATH . 'options.php');

class TPL_Pressroom
{
	public $configs;
	public $edition;
	public $preview;

	public function __construct() {

		if ( !is_admin() ) {
			return;
		}

		$this->_load_configs();
		$this->_load_extensions();

		$this->_create_edition();
		$this->_create_preview();

		if( !$this->_check_permalink_structure() ) {
			add_action( 'admin_notices', array( $this, 'permalink_notice' ) );
		}

		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
		add_action( 'admin_notices', array( $this, 'check_pressroom_notice' ), 20 );
		add_action( 'p2p_init', array( $this, 'register_post_connection' ) );
		add_action( 'do_meta_boxes', array( $this, 'change_featured_image_box' ) );
		add_filter( 'admin_post_thumbnail_html', array( $this, 'change_featured_image_html' ) );
		add_filter( 'p2p_created_connection', array( $this, 'post_connection_add_default_theme' ) );


		/* Override default wordpress theme path */
		add_filter( 'theme_root', array( $this, 'set_theme_root' ), 10 );
		add_filter( 'theme_root_uri', array( $this, 'set_theme_uri' ), 10 );
		add_filter( 'template', array( $this, 'set_template_name'), 10 );
		add_filter( 'stylesheet', array( $this, 'set_template_name'), 10 );

		/* Once all plugin are loaded, load core and external exporters */
		add_action( 'plugins_loaded', array( $this, 'load_exporters' ) );
		add_action( 'plugins_loaded', array( $this, 'load_core_exporters' ), 20 );

	}

	/**
	 * Activation plugin:
	 * Setup database tables and filesystem structure
	 *
	 * @void
	 */
	public function plugin_activation() {

		$errors = PR_Setup::install();
		if ($errors !== false) {
			$html = '<h1>' . __('Pressroom') . '</h1>
			<p><b>' .__( 'An error occurred during activation. Please see details below.', 'pressroom_setup' ). '</b></p>
			<ul><li>' .implode( "</li><li>", $errors ). '</li></ul>';
			wp_die( $html, __( 'Pressroom activation error', 'pressroom_setup' ), ('back_link=true') );
		}

		do_action( 'press_flush_rules' );
		flush_rewrite_rules();
	}

	/**
	 * Deactivation plugin
	 *
	 * @void
	 */
	public function plugin_deactivation() {

		// delete_option('rewrite_rules');
		flush_rewrite_rules();
		PR_Cron::disable();
	}

	/**
	 * Add connection between
	 * the edition and other allowed post type.
	 *
	 * @void
	 */
	public function register_post_connection() {

		$types = $this->get_allowed_post_types();

		p2p_register_connection_type( array(
				'name' 		=> P2P_EDITION_CONNECTION,
				'from'	 	=> $types,
				'to' 			=> PR_EDITION,
				'admin_column' => 'any',
				'admin_dropdown' => 'from',
				'sortable' 	=> false,
				'title' => array(
    				'from'	=> __( 'Included into issue', 'pressroom' )
    			),
				'to_labels' => array(
      			'singular_name'	=> __( 'Issue', 'pressroom' ),
      			'search_items' 	=> __( 'Search issue', 'pressroom' ),
      			'not_found'			=> __( 'No issue found.', 'pressroom' ),
      			'create'				=> __( 'Select an issue', 'pressroom' ),
  				),
				'admin_box' => array(
					'show' 		=> 'from',
					'context'	=> 'side',
					'priority'	=> 'high',
				),
				'fields' => array(
					'status' => array(
						'title'		=> __( 'Visible', 'pressroom' ),
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
	 * Add default theme template to post connection
	 *
	 * @param  int $p2p_id
	 * @void
	 */
	public function post_connection_add_default_theme( $p2p_id ) {

		$connection = p2p_get_connection( $p2p_id );
		if ( $connection->p2p_type == P2P_EDITION_CONNECTION ) {
			$themes = PR_Theme::get_themes();
			$theme_code = get_post_meta( $connection->p2p_to, '_pr_theme_select', true );
			if ( $theme_code && $themes ) {
				$pages = $themes[$theme_code];
				foreach ( $pages as $page ) {
					if ( isset($page['rule']) && $page['rule'] == 'post' ) {
						p2p_add_meta( $p2p_id, 'template', $page['path'] );
					}
				}
			}
		}
	}

	/**
	 * Custom featured image title
	 * @void
	 */
	public function change_featured_image_box() {

		remove_meta_box( 'postimagediv', PR_EDITION, 'side' );
		add_meta_box('postimagediv', __('Cover Image'), 'post_thumbnail_meta_box', PR_EDITION, 'side', 'high');
	}

	/**
	 * Custom featured image labels
	 * @param  string $content
	 * @return string
	 */
	public function change_featured_image_html( $content ) {

		global $post;
		if ( $post && $post->post_type == PR_EDITION ) {
			$content = str_replace( __( 'Remove featured image' ), __( 'Remove cover image' ), $content);
			$content = str_replace( __( 'Set featured image' ), __( 'Set cover image' ), $content);
		}
		return $content;
	}

	/**
	 * Check admin notices and display
	 *
	 * @echo
	 */
	public function check_pressroom_notice() {

		if ( isset( $_GET['pmtype'] ) && isset( $_GET['pmcode'] ) ) {

			$msg_type = $_GET['pmtype'];
			$msg_code = $_GET['pmcode'];
			$msg_param = isset( $_GET['pmparam'] ) ? urldecode( $_GET['pmparam'] ) : '';

			echo '<div class="pr-alert ' . $msg_type . '"><p>';
			switch ( $msg_code ) {
				case 'theme':
					echo _e( '<b>Error:</b> You must specify a theme for issue!', 'pressroom_notice' );
					break;
				case 'duplicate_entry':
					echo _e( sprintf('<b>Error:</b> Duplicate entry for <b>%s</b>. It must be unique', $msg_param ) );
					break;
				case 'failed_activated_license':
					echo _e( sprintf('<b>Error during activation:</b> %s', $msg_param ) );
					break;
				case 'success_activated_license':
					echo _e( sprintf('<b>Activation successfully:</b> %s', $msg_param ) );
					break;
				case 'failed_deactivated_license':
					echo _e( sprintf('<b>Error during deactivation:</b> %s', $msg_param ) );
					break;
				case 'success_deactivated_license':
					echo _e( '<b>License Deactivated.</b>' );
					break;
				case 'themes_cache_flushed':
					echo _e( '<b>Themes cache flushed successfully</b>' );
					break;
			}
			echo '</p></div>';
		}
	}

	/**
   * Unset theme root to exclude custom filter override
   *
   * @param string $path
   * return string
   */
  public function set_theme_root( $path ) {

		update_option( 'pr_theme_root', $path );
    if ( isset( $_GET['pr_no_theme'] ) ) {
      return realpath( PR_THEMES_PATH );
    }

    return $path;
  }

	public function set_theme_uri( $uri ) {

		update_option( 'pr_theme_uri', $uri );
		if ( isset( $_GET['pr_no_theme'] ) ) {
			return PR_THEME_URI;
		}

		return $uri;
	}

	/**
	 * Override default wordpress theme
	 *
	 * @param string $name
	 * return string
	 */
	public function set_template_name( $name ) {

		if ( isset( $_GET['pr_no_theme'], $_GET['edition_id'] ) ) {
			$name = PR_Theme::get_theme_path( $_GET['edition_id'], false );
		}

		return $name;
	}

	/*
	 * Get all allowed post types
	 *
	 * @return array
	 */
	public function get_allowed_post_types() {

		$types = array( 'post', 'page' );
		$custom_types = $this->_load_custom_post_types();
		$types = array_merge( $types, $custom_types );

		return $types;
	}

	/**
	 * Check if is add or edit page
	 *
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
	* Render admin notice for permalink
	*
	* @void
	*/
	public function permalink_notice() {

		$setting_page_url = admin_url() . 'options-permalink.php';
		echo '
		<div class="error pr-alert">
			<p>' . __( sprintf( 'Pressroom: PressRoom require <i>Post Name</i> format for permalink. You can set it in <a href="%s">setting page</a>', $setting_page_url ), 'pressroom' ). '</p>
		</div>';
	}

	/**
	 *  Load add-ons exporters
	 *
	 * @void
	 */
	public function load_exporters() {
		do_action_ref_array( 'pressroom/add_ons', array() );
	}

	/**
	* Get all core exporters from relative dir
	*
	* @void
	*/
	public function load_core_exporters() {

		$exporters = PR_Utils::search_dir( PR_PACKAGER_EXPORTERS_PATH );

		foreach( $exporters as $exporter ) {
			$file = PR_PACKAGER_EXPORTERS_PATH . "{$exporter}/index.php";
			if ( is_file( $file ) ) {
				require_once( $file );
			}
		}

		return true;
	}

	/**
	 * Check if permalink structure is set to Post Name
	 * ( required for Editorial Project endpoint )
	 *
	 * @return boolean
	 */
	protected function _check_permalink_structure() {

		$structure = get_option('permalink_structure');

		if( $structure != "/%postname%/" ) {
			return false;
		}

		return true;
	}

	/**
	 * Load plugin configuration settings
	 *
	 * @void
	 */
	protected function _load_configs() {

		if ( is_null( $this->configs ) ) {
			$this->configs = get_option('pr_settings', array(
				'pr_custom_post_type' => array()
			));
		}
	}

	/**
	 * Load plugin extensions
	 *
	 * @void
	 */
	protected function _load_extensions() {

		if ( is_dir( PR_EXTENSIONS_PATH ) ) {
			$files = PR_Utils::search_files( PR_EXTENSIONS_PATH, 'php' );
			if ( !empty( $files ) ) {
				foreach ( $files as $file ) {
					require_once( $file );
				}
			}
		}
	}

	/**
	 * Load custom post types configured in settings page
	 *
	 * @return array - custom post types
	 */
	protected function _load_custom_post_types() {

		$types = array();
		if ( !empty( $this->configs ) && isset( $this->configs['pr_custom_post_type'] ) ) {
			$custom_types = $this->configs['pr_custom_post_type'];
			if ( is_array( $custom_types ) ) {
				foreach ( $custom_types as $post_type ) {
					if ( strlen( $post_type ) ) {
						array_push( $types, $post_type );
					}
				}
			}
			elseif ( is_string( $custom_types ) && strlen( $custom_types ) ) {
				array_push( $types, $custom_types );
			}
		}
		return $types;
	}

	/**
	* Instance a new edition object
	*
	* @void
	*/
	protected function _create_edition() {

		if ( is_null( $this->edition ) ) {
			$this->edition = new PR_Edition();
		}
	}

	/**
	* Instance a new preview object
	*
	* @void
	*/
	protected function _create_preview() {

		if ( is_null( $this->preview ) ) {
			$this->preview = new PR_Preview;
		}
	}
}

/* instantiate the plugin class */
$tpl_pressroom = new TPL_Pressroom();
