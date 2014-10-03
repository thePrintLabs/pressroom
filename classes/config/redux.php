<?php
if ( !class_exists( 'ReduxFramework' ) && file_exists( TPL_VENDORS_PATH . 'ReduxFramework/ReduxCore/framework.php' ) ) {
   require_once( TPL_VENDORS_PATH . 'ReduxFramework/ReduxCore/framework.php' );
}

if ( !class_exists('TPL_Redux_Framework' ) ) {

   /**
   * TPL_Redux_Framework class.
   */
   class TPL_Redux_Framework {

      public $args = array();
      public $sections = array();
      public $ReduxFramework;

      public function __construct() {

         if ( Redux_Helpers::isTheme( __FILE__ ) ) {
            $this->initSettings();
         }
         else {
            add_action( 'plugins_loaded', array( $this, 'initSettings' ), 10 );
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

         if ( !isset( $this->args['opt_name'] ) ) { // No errors please
             return;
         }

         $this->ReduxFramework = new ReduxFramework( $this->sections, $this->args );
      }

      /**
       * setSections function.
       *
       * @return void
       */
      public function setSections() {

         $themes = array();
         $themes_list = TPL_Theme::get_themes_list();
         foreach ( $themes_list as $theme ) {
            $themes[$theme['value']] = $theme['text'];
         }

         // General setting section
         $this->sections[] = array(
            'title'     => __( 'General Settings', 'pressroom' ),
            'desc'      => __( 'General setting for baker plugin', 'pressroom' ),
            'icon'      => 'el-icon-home',
            'fields'    => array(
               array(
                  'id'        => 'tpl-theme',
                  'type'      => 'select',
                  'title'     => __( 'Theme', 'pressroom' ),
                  'subtitle'  => __( 'Select yout theme', 'pressroom' ),
                  'desc'      => __( 'You can choose your personal theme or create one and then select it.', 'pressroom' ),
                  'options'   => $themes,
                  'default'   => '2'
               ),
               array(
                  'id'            => 'tpl-maxnumer',
                  'type'          => 'slider',
                  'title'         => __( 'Max number of edition', 'pressroom' ),
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
            'title'     => __( 'Book.json', 'pressroom' ),
            'desc'      => __( 'Setting for book.json', 'pressroom' ),
            'icon'      => 'el-icon-book',
            'fields'    => array(
               array(
                  'id'        => 'tpl-orientation',
                  'type'      => 'button_set',
                  'title'     => __( 'Orientation', 'pressroom' ),
                  'subtitle'  => __( 'Select screen orientation', 'pressroom' ),
                  'default'   => 'Both',
                  'options'   => array(
                     'Horizontal'   => 'Horizontal',
                     'Vertical'     => 'Vertical',
                     'Both'         => 'Both',
                  ),
               ),
               array(
                  'id'        => 'tpl-zoomable',
                  'type'      => 'checkbox',
                  'title'     => __( 'Zoomable', 'pressroom' ),
                  'subtitle'  => __( 'If checked enable zoom on page', 'pressroom' ),
                  'default'   => false
               ),
               array(
                  'id'        => 'opt-color-background',
                  'type'      => 'color',
                  'title'     => __( 'Body Background Color', 'baker' ),
                  'subtitle'  => __( 'Pick a background color for the theme (default: #fff).', 'pressroom' ),
                  'default'   => '#FFFFFF',
                  'transparent'=> false,
                  'validate'  => 'color',
               ),
               array(
                  'id'        => 'tpl-vertical-bounce',
                  'type'      => 'checkbox',
                  'title'     => __( 'Vertical Bounce', 'pressroom' ),
                  'subtitle'  => __( 'If checked enable vertical bounce', 'pressroom' ),
                  'default'   => 0
               ),
               array(
                  'id'        => 'tpl-index-bounce',
                  'type'      => 'checkbox',
                  'title'     => __( 'Index bounce', 'pressroom' ),
                  'subtitle'  => __( 'If checked enable index bounce', 'pressroom' ),
                  'default'   => 0
                ),
               array(
                  'id'            => 'tpl-index-height',
                  'type'          => 'slider',
                  'title'         => __( 'Index height', 'pressroom' ),
                  'default'       => 150,
                  'min'           => 0,
                  'step'          => 5,
                  'max'           => 768,
                  'display_value' => 'text'
               ),
               array(
                  'id'        => 'tpl-media-autoplay',
                  'type'      => 'checkbox',
                  'title'     => __( 'Media autoplay', 'pressroom' ),
                  'subtitle'  => __( 'If checked enable media autoplay', 'pressroom' ),
                  'default'   => 0
               ),
            )
         );

         $this->sections[] = array(
            'title'     => __( 'Notification push', 'pressroom' ),
            'desc'      => __( 'Notification push', 'pressroom' ),
            'icon'      => 'el-icon-envelope',
            'fields'    => array(),
         );

         $this->sections[] = array(
            'title'     => __( 'Pro Settings', 'pressroom' ),
            'desc'      => __( 'Pro setting for pressroom plugin', 'pressroom' ),
            'icon'      => 'el-icon-star',
            'fields'    => array(
               array(
                   'id'        => 'tpl-custom-post-type',
                   'type'      => 'multi_text',
                   'title'     => __( 'Custom post type', 'pressroom' ),
                   'subtitle'  => __( 'Select your custom post type', 'pressroom' ),
                   'desc'      => __( 'You can choose your personal custom post type.', 'pressroom' ),
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

             'opt_name'          => 'tpl_options',					//global variable with option data
             'display_name'      => 'PressRoom Option page',
             'display_version'   => '0.1',
             'menu_type'         => 'menu',                  	//Specify if the admin menu should appear or not. Options: menu or submenu (Under appearance only)
             'allow_sub_menu'    => true,                    	// Show the sections below the admin menu item or not
             'menu_title'        => __( 'PressRoom', 'pressroom' ),
             'page_title'        => __( 'Sample Options', 'pressroom' ),
             'async_typography'  => false,                    	// Use a asynchronous font on the front end or font string
             'admin_bar'         => true,                    	// Show the panel pages on the admin bar
             'global_variable'   => '',                      	// Set a different name for your global variable other than the opt_name
             'dev_mode'          => false,                    	// Show the time the page took to load, etc
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
             'url'   => 'http://theprintlabs.github.io/pressroom/',
             'title' => 'Visit us on GitHub',
             'icon'  => 'el-icon-github'
         );
      }
   }
   $reduxConfig = new TPL_Redux_Framework();
}
