<?php
/**
 * PressRoom database class.
 * Add custom tables into database
 */
class PR_Setup
{
  const VERSION_PRO = true; // @TODO: PRO

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
       array_push( $errors, __( "Error creating required directory: <b>&quot;" . PR_PLUGIN_PATH . "api/&quot;</b>. Check your write files permissions.", 'pressroom_setup' ) );
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

    $api_dir = PR_Utils::make_dir( PR_PLUGIN_PATH, 'api' );
    if ( !$api_dir ) {
      return false;
    }

    $api_dir = PR_Utils::make_dir( PR_API_PATH, 'hpub' );
    $api_dir = $api_dir && PR_Utils::make_dir( PR_API_PATH, 'tmp' );
    $api_dir = $api_dir && PR_Utils::make_dir( PR_API_PATH, 'shelf' );
    $api_dir = $api_dir && PR_Utils::make_dir( PR_TMP_PATH, 'preview' );

    return !$api_dir ? false : true;
  }
}
