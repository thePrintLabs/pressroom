<?php
/**
* PressRoom packager: Web package
*
*/

final class PR_Packager_Web_Package
{
  public function __construct() {

    add_action( 'pr_add_eproject_tab', array( $this, 'pr_add_option' ), 10, 2 );
  }

  public function pr_add_option( &$metaboxes, $term_id ) {

    $web = new PR_Metabox( 'web_metabox', __( 'web', 'web_package' ), 'normal', 'high', $term_id );

    $web->add_field( 'pr_container_theme', __( 'Container theme', 'web_package' ), __( 'Web viewer theme', 'edition' ), 'select', '', array(
      'options' => array(
        array( 'value' => 'standard', 'text' => __( "Standard Web Viewer", 'web_package' ) ),
        )
      )
    );
    $web->add_field( '_pr_protocol', __( 'Transfer protocol', 'editorial_project' ), '', 'radio', '', array(
      'options' => array(
        array( 'value' => 'pr_ftp', 'name' => __( "ftp", 'web_package' ) ),
        array( 'value' => 'pr_sftp', 'name' => __( "sftp", 'web_package' ) ),
        array( 'value' => 'pr_local', 'name' => __( "local filesystem", 'web_package' ) )
        )
      )
    );

    array_push( $metaboxes, $web );
  }


}
$pr_packager_web_package = new PR_Packager_Web_Package;
