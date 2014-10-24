<?php
final class PR_Server_Shelf_JSON extends PR_Server_API
{
  private static $_press_to_baker = array(
    'post_name'       => 'name',
    'post_title'      => 'title',
    'post_content'    => 'info',
    '_pr_date'        => 'date',
  );

  public function __construct() {

    add_action( 'init', array( $this, 'add_endpoint' ), 10 );
    add_action( 'parse_request', array( $this, 'parse_request' ), 10 );
  }

  /**
   * Add API Endpoint
   * Must extend the parent class
   *
   *	@void
   */
  public function add_endpoint() {

    parent::add_endpoint();
    add_rewrite_tag( '%editorial_project%', '([^&]+)' );
    add_rewrite_rule( 'pressroom-api/shelf/([^&]+)/?$',
                      'index.php?__pressroom-api=shelf_json&editorial_project=$matches[1]',
                      'top' );
  }

  /**
   * Parse HTTP request
   * Must extend the parent class
   *
   *	@return die if API request
   */
  public function parse_request() {

    global $wp;
    $request = parent::parse_request();
    if ( $request && $request == 'shelf_json' ) {
      $this->_generate_shelf();
    }
  }

  /**
   * Get all editions of the editorial projects and create the shelf json output
   * @return string
   */
  protected function _generate_shelf() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }

    $eproject = TPL_Editorial_Project::get_by_slug( $eproject_slug );
    if ( !$eproject ) {
      $this->send_response( 500, 'Editorial project not valid.' );
    }

    $args = array(
      'post_type'             => TPL_EDITION,
      TPL_EDITORIAL_PROJECT   => $eproject->slug,
      'post_status'           => 'publish',
      'posts_per_page'        => -1,
    );
    $edition_query = new WP_Query( $args );

    $press_options = array();
    self::$_press_to_baker['_pr_product_id_' . $eproject->term_id] = 'product_id';

    foreach ( $edition_query->posts as $edition_key => $edition ) {

      $press_options[$edition_key] = array(
        //'url' => TPL_HPUB_URI . TPL_Utils::sanitize_string( $edition->post_title . '.hpub' ) // @TODO: FREE
        'url' => esc_url( home_url( '/pressroom-api/issue/' . TPL_Utils::sanitize_string( $edition->post_title ) ) )
      );

      // Add the cover image into the edition options
      $edition_cover_id = get_post_thumbnail_id( $edition->ID );
      if ( $edition_cover_id ) {
         $edition_cover = wp_get_attachment_image_src( $edition_cover_id, 'thumbnail_size' );
         if ( $edition_cover ) {
            $press_options[$edition_key]['cover'] = $edition_cover[0];
         }
      }

      // Add only allowed values into the edition options
      foreach ( $edition as $key => $edition_attribute ) {

        if ( array_key_exists( $key, self::$_press_to_baker ) ) {
          $baker_option = self::$_press_to_baker[$key];
          $press_options[$edition_key][$baker_option] = $edition_attribute;
        }
      }

      // Add allowed custom fields values into the edition options
      $meta_fields = get_post_custom( $edition->ID );
      foreach ( $meta_fields as $meta_key => $meta_value ) {

        if ( array_key_exists( $meta_key, self::$_press_to_baker ) ) {

          $baker_option = self::$_press_to_baker[$meta_key];

          switch ( $meta_key ) {

            case '_pr_date':
              if ( isset( $meta_value[0] ) ) {
                 $press_options[$edition_key][$baker_option] = date( 'Y-m-d H:s:i', strtotime( $meta_value[0] ) );
              }
              break;

            case '_pr_product_id' . $eproject->term_id :
              if ( isset( $meta_value[0] ) &&
                !( isset( $meta_fields['_pr_edition_free'] ) && $meta_fields['_pr_edition_free'][0] == 1 ) ) {
                $press_options[$edition_key][$baker_option] = $meta_value[0];
              }
              break;

             default:
              if ( isset( $meta_value[0] ) ) {
                 $press_options[$edition_key][$baker_option] = $meta_value[0];
              }
              break;
          }
        }
      }
    }
    wp_send_json( $press_options );
  }
}

$pr_server_shelf = new PR_Server_Shelf_JSON;
