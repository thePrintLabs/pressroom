<?php

if ( !class_exists( 'ReduxFramework' ) && file_exists( TPL_VENDORS_PATH . 'ReduxFramework/ReduxCore/framework.php' ) ) {
    require_once( TPL_VENDORS_PATH . 'ReduxFramework/ReduxCore/framework.php' );
}

if (!class_exists('TPL_Redux_Framework')) {


    /**
     * TPL_Redux_Framework class.
     */
    class TPL_Redux_Framework {

        public $args        = array();
        public $sections    = array();
        public $ReduxFramework;

        public function __construct() {
	        if (  true == Redux_Helpers::isTheme(__FILE__) ) {
                $this->initSettings();
            } else {
                add_action('plugins_loaded', array($this, 'initSettings'), 10);
            }
		}


        /**
         * initSettings function.
         *
         * @access public
         * @return void
         */
        public function initSettings() {

            // Set the default arguments
            $this->setArguments();

            // Set a few help tabs so you can see how it's done
            $this->setHelpTabs();

            // Create the sections and fields
            $this->setSections();

            if (!isset($this->args['opt_name'])) { // No errors please
                return;
            }

            $this->ReduxFramework = new ReduxFramework($this->sections, $this->args);
        }

        /**

          Filter hook for filtering the default value of any given field. Very useful in development mode.

         * */
        function change_defaults($defaults) {
            $defaults['str_replace'] = 'Testing filter hook!';

            return $defaults;
        }


        /**
         * setSections function.
         *
         * @access public
         * @return void
         */
        public function setSections() {
            // General setting section
            $this->sections[] = array(
                'title'     => __('General Settings', 'baker'),
                'desc'      => __('General setting for baker plugin', 'baker'),
                'icon'      => 'el-icon-home',
                'fields'    => array(
	               array(
	                    'id'        => 'tpl-theme',
	                    'type'      => 'select',
	                    'title'     => __('Theme', 'baker'),
	                    'subtitle'  => __('Select yout theme', 'redux-framework-demo'),
	                    'desc'      => __('You can choose your personal theme or create one and then select it.', 'baker'),
	                    'options'   => TPL_Themes::get_themes_list(),
	                    'default'   => '2'
	                ),
	                array(
                        'id'            => 'tpl-maxnumer',
                        'type'          => 'slider',
                        'title'         => __('Max number of edition', 'baker'),
                        'default'       => 100,
                        'min'           => 0,
                        'step'          => 5,
                        'max'           => 300,
                        'display_value' => 'text'
                    ),
                ),
            );
            //Book.json section
            $this->sections[] = array(
                'title'     => __('Book.json', 'baker'),
                'desc'      => __('Setting for book.json', 'baker'),
                'icon'      => 'el-icon-book',
                'fields'    => array(
                	array(
	                    'id'        => 'tpl-orientation',
	                    'type'      => 'button_set',
	                    'title'     => __('Orientation', 'baker'),
	                    'subtitle'  => __('Select screen orientation', 'baker'),

	                    //Must provide key => value pairs for select options
	              'options'  => array(
					        'Horizontal'   => 'Horizontal',
					        'Vertical'     => 'Vertical',
					        'Both'         => 'Both',
	                ),
	              ),
	                array(
	                    'id'        => 'tpl-zoomable',
	                    'type'      => 'checkbox',
	                    'title'     => __('Zoomable', 'baker'),
	                    'subtitle'  => __('If checked enable zoom on page', 'baker'),
	                    'default'   => false
	                ),
	                array(
                        'id'        => 'opt-color-background',
                        'type'      => 'color',
                        'title'     => __('Body Background Color', 'baker'),
                        'subtitle'  => __('Pick a background color for the theme (default: #fff).', 'baker'),
                        'default'   => '#FFFFFF',
                        'transparent'=> false,
                        'validate'  => 'color',
                    ),
                    array(
	                    'id'        => 'tpl-vertical-bounce',
	                    'type'      => 'checkbox',
	                    'title'     => __('Vertical Bounce', 'baker'),
	                    'subtitle'  => __('If checked enable vertical bounce', 'baker'),
	                    'default'   => 0
	                ),
	                array(
	                    'id'        => 'tpl-index-bounce',
	                    'type'      => 'checkbox',
	                    'title'     => __('Index bounce', 'baker'),
	                    'subtitle'  => __('If checked enable index bounce', 'baker'),
	                    'default'   => 0
	                ),
	                array(
                        'id'            => 'tpl-index-height',
                        'type'          => 'slider',
                        'title'         => __('Index height', 'baker'),
                        'default'       => 150,
                        'min'           => 0,
                        'step'          => 5,
                        'max'           => 768,
                        'display_value' => 'text'
                    ),
                    array(
	                    'id'        => 'tpl-media-autoplay',
	                    'type'      => 'checkbox',
	                    'title'     => __('Media autoplay', 'baker'),
	                    'subtitle'  => __('If checked enable media autoplay', 'baker'),
	                    'default'   => 0
	                ),
                )
            );
            $this->sections[] = array(
                'title'     => __('Notification push', 'baker'),
                'desc'      => __('Notification push', 'baker'),
                'icon'      => 'el-icon-envelope',
                'fields'    => array(

                ),
            );

            $this->sections[] = array(
                'title'     => __('Pro Settings', 'baker'),
                'desc'      => __('Pro setting for pressroom plugin', 'baker'),
                'icon'      => 'el-icon-star',
                'fields'    => array(
                  array(
                      'id'        => 'tpl-custom-post-type',
                      'type'      => 'multi_text',
                      'title'     => __('Custom post type', 'baker'),
                      'subtitle'  => __('Select your custom post type', 'baker'),
                      'desc'      => __('You can choose your personal custom post type.', 'baker'),
                  ),
                ),
            );
        }

        /**
         * setHelpTabs function.
         *
         * @access public
         * @return void
         */
        public function setHelpTabs() {

            // Custom page help tabs, displayed using the help API. Tabs are shown in order of definition.
            $this->args['help_tabs'][] = array(
                'id'        => 'redux-help-tab-1',
                'title'     => __('Theme Information 1', 'redux-framework-demo'),
                'content'   => __('<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo')
            );

            $this->args['help_tabs'][] = array(
                'id'        => 'redux-help-tab-2',
                'title'     => __('Theme Information 2', 'redux-framework-demo'),
                'content'   => __('<p>This is the tab content, HTML is allowed.</p>', 'redux-framework-demo')
            );

            // Set the help sidebar
            $this->args['help_sidebar'] = __('<p>This is the sidebar content, HTML is allowed.</p>', 'redux-framework-demo');
        }


        /**
         * setArguments function.
         *
         * @access public
         * @return void
         */
        public function setArguments() {

            $this->args = array(
                // TYPICAL -> Change these values as you need/desire
                'opt_name'          => 'tpl_options',					//global variable with option data
                'display_name'      => 'TPL - Pressroom Option page',
                'display_version'   => '0.1',
                'menu_type'         => 'menu',                  	//Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
                'allow_sub_menu'    => true,                    	// Show the sections below the admin menu item or not
                'menu_title'        => __('TPL - Pressroom', 'baker'),
                'page_title'        => __('Sample Options', 'baker'),
                'async_typography'  => false,                    	// Use a asynchronous font on the front end or font string
                'admin_bar'         => true,                    	// Show the panel pages on the admin bar
                'global_variable'   => '',                      	// Set a different name for your global variable other than the opt_name
                'dev_mode'          => true,                    	// Show the time the page took to load, etc
                'customizer'        => false,                    	// Enable basic customizer support
                //'open_expanded'     => true,                    	// Allow you to start the panel in an expanded way initially.
                //'disable_save_warn' => true,                    	// Disable the save warning when a user changes a field
                // OPTIONAL -> Give you extra features
                'page_priority'     => null,                    // Order where the menu appears in the admin area. If there is any conflict, something will not show. Warning.
                'page_parent'       => 'themes.php',            // For a full list of options, visit: http://codex.wordpress.org/Function_Reference/add_submenu_page#Parameters
                'page_permissions'  => 'manage_options',        // Permissions needed to access the options panel.
                'menu_icon'         => '',                      // Specify a custom URL to an icon
                'last_tab'          => '',                      // Force your panel to always open to a specific tab (by id)
                'page_icon'         => 'icon-themes',           // Icon displayed in the admin panel next to your menu_title
                'page_slug'         => 'tpl-options',              // Page slug used to denote the panel
                'save_defaults'     => true,                    // On load save the defaults to DB before user clicks save or not
                'default_show'      => false,                   // If true, shows the default value next to each field that is not the default value.
                'default_mark'      => '',                      // What to print by the field's title if the value shown is default. Suggested: *
                'show_import_export' => false,                   // Shows the Import/Export panel when not used as a field.

                // CAREFUL -> These options are for advanced use only
                'transient_time'    => 60 * MINUTE_IN_SECONDS,
                'output'            => true,                    // Global shut-off for dynamic CSS output by the framework. Will also disable google fonts output
                'output_tag'        => true,                    // Allows dynamic CSS to be generated for customizer and google fonts, but stops the dynamic CSS from going to the head
                // 'footer_credit'     => '',                   // Disable the footer credit of Redux. Please leave if you can help it.

                // FUTURE -> Not in use yet, but reserved or partially implemented. Use at your own risk.
                'database'              => '', // possible: options, theme_mods, theme_mods_expanded, transient. Not fully functional, warning!
                'system_info'           => false, // REMOVE

                // HINTS
                'hints' => array(
                    'icon'          => 'icon-question-sign',
                    'icon_position' => 'right',
                    'icon_color'    => 'lightgray',
                    'icon_size'     => 'normal',
                    'tip_style'     => array(
                        'color'         => 'light',
                        'shadow'        => true,
                        'rounded'       => false,
                        'style'         => '',
                    ),
                    'tip_position'  => array(
                        'my' => 'top left',
                        'at' => 'bottom right',
                    ),
                    'tip_effect'    => array(
                        'show'          => array(
                            'effect'        => 'slide',
                            'duration'      => '500',
                            'event'         => 'mouseover',
                        ),
                        'hide'      => array(
                            'effect'    => 'slide',
                            'duration'  => '500',
                            'event'     => 'click mouseleave',
                        ),
                    ),
                )
            );

            $this->args['share_icons'][] = array(
                'url'   => 'https://github.com/ReduxFramework/ReduxFramework',
                'title' => 'Visit us on GitHub',
                'icon'  => 'el-icon-github'
                //'img'   => '', // You can use icon OR img. IMG needs to be a full URL.
            );
            $this->args['share_icons'][] = array(
                'url'   => 'https://www.facebook.com/pages/Redux-Framework/243141545850368',
                'title' => 'Like us on Facebook',
                'icon'  => 'el-icon-facebook'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://twitter.com/reduxframework',
                'title' => 'Follow us on Twitter',
                'icon'  => 'el-icon-twitter'
            );
            $this->args['share_icons'][] = array(
                'url'   => 'http://www.linkedin.com/company/redux-framework',
                'title' => 'Find us on LinkedIn',
                'icon'  => 'el-icon-linkedin'
            );

        }

    }

    global $reduxConfig;
    $reduxConfig = new TPL_Redux_Framework();
}

/**
  Custom function for the callback referenced above
 */
if (!function_exists('redux_my_custom_field')):
    function redux_my_custom_field($field, $value) {
        print_r($field);
        echo '<br/>';
        print_r($value);
    }
endif;

/**
  Custom function for the callback validation referenced above
 * */
if (!function_exists('redux_validate_callback_function')):
    function redux_validate_callback_function($field, $value, $existing_value) {
        $error = false;
        $value = 'just testing';

        $return['value'] = $value;
        if ($error == true) {
            $return['error'] = $field;
        }
        return $return;
    }
endif;
