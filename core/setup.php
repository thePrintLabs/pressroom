<?php
/**
* PressRoom database class.
* Add custom tables into database
*/
class PR_Setup
{

  public function __construct() {}

    /**
    * Plugin installation
    *
    * @return boolean or array of error messages
    */
    public static function install() {

      $errors = array();
      $check_libs = self::_check_php_libs();
      if ( $check_libs ) {
        array_push( $errors, __( "Missing required extensions: <b>" . implode( ', ', $check_libs ) . "</b>", 'pressroom_setup' ) );
      }
      if ( !self::_setup_filesystem() ) {
        array_push( $errors, __( "Error creating required directory: <b>&quot;" . PR_PLUGIN_PATH . "api/&quot;</b> or <b>&quot;" . PR_UPLOAD_PATH . "api/&quot;</b>. Check your write files permissions.", 'pressroom_setup' ) );
      }
      if ( !self::_setup_starterr_theme() ) {
        array_push( $errors, __( "Error creating starterr theme in directory: <b>&quot;" . PR_PLUGIN_PATH . "api/&quot;</b> or <b>&quot;" . PR_UPLOAD_PATH . "api/&quot;</b>. Check your write files permissions.", 'pressroom_setup' ) );
      }
      if ( !empty( $errors ) ) {
        return $errors;
      }

      return false;
    }

    /**
    * Check if the required libraries are installed
    *
    * @return boolean or array of errors
    */
    private static function _check_php_libs() {

      $errors = array();
      $extensions = array( 'zlib', 'zip', 'libxml' );
      foreach ( $extensions as $extension ) {

        if( !extension_loaded( $extension ) ) {
          array_push( $errors, $extension );
        }
      }

      if ( !empty( $errors ) )
      return $errors;

      return false;
    }

    /**
    * Install the plugin folders
    *
    * @return boolean
    */
    private static function _setup_filesystem() {

      $wp_upload_dir = wp_upload_dir();
      $api_dir = PR_Utils::make_dir( PR_PLUGIN_PATH, 'api' );
      $upload_dir = PR_Utils::make_dir( $wp_upload_dir['basedir'], 'pressroom' );

      if ( !$api_dir || !$upload_dir ) {
        return false;
      }

      $api_dir = $api_dir && PR_Utils::make_dir( PR_API_PATH, 'tmp' );
      $api_dir = $api_dir && PR_Utils::make_dir( PR_TMP_PATH, 'preview' );

      $upload_dir = PR_Utils::make_dir( PR_UPLOAD_PATH, 'hpub' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'web' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'shelf' );
      $upload_dir = $upload_dir && PR_Utils::make_dir( PR_UPLOAD_PATH, 'themes' );

      if ( false !== ( $wp_load_path = self::_search_wp_load() ) ) {
        file_put_contents( PR_CORE_PATH . 'preview' . DS . '.pr_path', $wp_load_path );
      }

      return !$api_dir && !$upload_dir ? false : true;
    }

    /**
    * Search wp-load file in wordpress directory
    * @return string or boolean false
    */
    private static function _search_wp_load() {

      $home_path = ABSPATH;
      // Check in standard WordPress position
      if ( file_exists( $home_path . 'wp-load.php' ) ) {
        return $home_path . 'wp-load.php';
      }
      else {
        $dir = new RecursiveDirectoryIterator( $home_path );
        $it = new RecursiveIteratorIterator( $dir );
        $regex = new RegexIterator( $it, '/wp-load.php$/i', RecursiveRegexIterator::GET_MATCH );
        $files = iterator_to_array( $regex );
        if ( !empty( $files ) ) {
          return key( $files );
        }
      }
      return false;
    }

    /**
     * Unzip starterr theme to Pressroom upload is_dir
     *
     * @return boolean
     */
    private static function _setup_starterr_theme() {

      $file_path = PR_PLUGIN_PATH . PR_STARTERR_ZIP;

      if ( file_exists( $file_path ) ) {
        $zip = new ZipArchive;
        if ( $zip->open( $file_path ) ) {

          // if a starterr theme exist yet, NOT override
          if( is_dir( PR_THEMES_PATH . PR_STARTERR_THEME ) ) {
            unlink( $file_path );
            return true;
          }

          //extract and delete the zip file
          if ( $zip->extractTo( PR_THEMES_PATH ) ) {
            delete_option( 'pressroom_themes' );
            unlink( $file_path );
            return true;
          }
          else {
            return false;
          }
          $zip->close();
        }
      }

      return true;
    }
  }
