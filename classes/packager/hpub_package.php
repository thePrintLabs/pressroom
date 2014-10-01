<?php
/**
* TPL packager: Hpub package
*
*/
abstract class TPL_Packager_HPUB_Package
{
   public function __construct() {}

   public static function build( $post, $folder ) {
      $filename = TPL_Utils::parse_string( $post->post_title );
      if ( TPL_Utils::create_zip_file( $folder, TPL_HPUB_DIR . $filename . '.hpub', false ) ) {
         return $filename . '.hpub';
      }

      return false;
   }
}
