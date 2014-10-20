<?php
/**
* TPL packager: Hpub package
*
*/
abstract class TPL_Packager_HPUB_Package
{
   public function __construct() {}

   public static function build( $filename, $dir ) {

      if ( TPL_Utils::create_zip_file( $dir, TPL_HPUB_PATH . $filename . '.hpub', '' ) ) {
         return $filename . '.hpub';
      }

      return false;
   }
}
