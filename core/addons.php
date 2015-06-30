<?php
class PR_Addons
{
	protected static $_add_ons = array();

	public function __construct() {

		$this->search();
	}

	public static function search() {
		$api_params = array(
      'key'         => '0a3d8d5a0639ffc26ee159d5938a95fc',
      'token'       => 'cfad94f3c1652a52dda2b7ec5451780f',
    );
    $response = wp_remote_get( add_query_arg( $api_params, PR_API_EDD_URL . 'products' ), array( 'timeout' => 15, 'sslverify' => false ) );
    $response = json_decode( wp_remote_retrieve_body( $response ) );
		foreach( $response->products as $product ) {
      foreach( $product->info->category as $category ) {
        if( $category->slug == 'exporters' ) {
          array_push( self::$_add_ons, $product );
        }
      }
    }
  }

  /**
   * Get add-ons objects
   *
   * @return array
   */
  public static function get() {
		$model = new self();
    return $model::$_add_ons;
  }
}
