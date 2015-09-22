<?php
/*
Plugin Name: Pressroom
Plugin URI: http://press-room.io/
Description: PressRoom turns Wordpress into a multi channel publishing environment.
Version: 1.3
Author: thePrintLabs Ltd
Author URI: http://theprintlabs.com
License: GPLv2
*/

if (!defined( 'ABSPATH' )) exit; // Exit if accessed directly

require_once __DIR__ . '/autoload.php';

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

		$this->_hooks();
		$this->_actions();
		$this->_filters();

		$this->_create_edition();
		$this->_create_preview();
	}

	/**
	 * Activation plugin:
	 * Setup database tables and filesystem structure
	 *
	 * @void
	 */
	public function plugin_activation() {
		$response = PR_Setup::install();
		if ( false !== $response ) {
			$html = '<h1>' . __('Pressroom') . '</h1>
			<p><b>' .__( 'An error occurred during activation. Please see details below.', 'pressroom_setup' ). '</b></p>
			<ul><li>' .implode( "</li><li>", $response ). '</li></ul>';
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
			$selected_theme = get_post_meta( $connection->p2p_to, '_pr_theme_select', true );
			if ( !empty( $themes ) && $selected_theme ) {
				$pages = $themes[$selected_theme];
				foreach ( $pages as $page ) {
					if ( isset( $page['rule'] ) && $page['rule'] == 'post' ) {
						p2p_add_meta( $p2p_id, 'template', $page['path'] );
					}
				}
			}
		}
	}

	/**
	 * Check admin notices and display
	 *
	 * @echo
	 */
	public function check_pressroom_notice() {
		if ( isset( $_GET['pmtype'], $_GET['pmcode'] ) ) {
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
      return PR_THEMES_PATH;
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
	* @return boolean
	*/
	public function load_core_exporters() {
		$exporters = PR_Utils::search_dir( PR_PACKAGER_EXPORTERS_PATH );
		if ( !empty( $exporters ) ) {
			foreach ( $exporters as $exporter ) {
				$file = trailingslashit( PR_PACKAGER_EXPORTERS_PATH . $exporter ) . "index.php";
				if ( is_file( $file ) ) {
					require_once $file;
				}
			}
			return true;
		}
		return false;
	}

	/**
	 * Add jQuery datepicker script and css styles
	 * @void
	 */
	public function register_pressroom_styles() {

		wp_register_style( 'pressroom', PR_ASSETS_URI . 'css/pressroom.css' );
		wp_enqueue_style( 'pressroom' );
	}

	 /*
	 * Register hooks
	 * @void
	 */
	protected function _hooks() {
		register_activation_hook( __FILE__, array( $this, 'plugin_activation' ) );
		register_deactivation_hook( __FILE__, array( $this, 'plugin_deactivation' ) );
	}

	/**
	 * Register actions
	 * @void
	 */
	protected function _actions() {
		if ( !$this->_check_permalink_structure() ) {
			add_action( 'admin_notices', array( $this, 'permalink_notice' ) );
		}

		add_action( 'admin_notices', array( $this, 'check_pressroom_notice' ), 20 );
		add_action( 'p2p_init', array( $this, 'register_post_connection' ) );

		/* Once all plugin are loaded, load core and external exporters */
		add_action( 'plugins_loaded', array( $this, 'load_exporters' ) );
		add_action( 'plugins_loaded', array( $this, 'load_core_exporters' ), 20 );

		add_action( 'admin_init', array( $this, 'register_pressroom_styles' ), 20 );
	}

	/**
	 * Register filters
	 * @void
	 */
	protected function _filters() {
		add_filter( 'p2p_created_connection', array( $this, 'post_connection_add_default_theme' ) );

		/* Override default wordpress theme path */
		add_filter( 'theme_root', array( $this, 'set_theme_root' ), 10 );
		add_filter( 'theme_root_uri', array( $this, 'set_theme_uri' ), 10 );
		add_filter( 'template', array( $this, 'set_template_name'), 10 );
		add_filter( 'stylesheet', array( $this, 'set_template_name'), 10 );
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
			} elseif ( is_string( $custom_types ) && strlen( $custom_types ) ) {
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

// Instantiate the plugin class
$tpl_pressroom = new TPL_Pressroom();
