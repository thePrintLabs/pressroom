<?php
final class PR_Server_Feed extends PR_Server_API
{
  public function __construct() {

    add_action( 'press_flush_rules', array( $this, 'add_endpoint' ), 10 );
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
    add_rewrite_rule( '^pressroom-api/newsstand-issue-feed/([^&]+)/?$',
                      'index.php?__pressroom-api=newsstand_issue_feed&editorial_project=$matches[1]',
                      'top' );
    add_rewrite_rule( '^([^/]*)/pressroom-api/newsstand-issue-feed/([^&]+)/?$',
                      'index.php?__pressroom-api=newsstand_issue_feed&editorial_project=$matches[2]',
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
    if ( $request && $request == 'newsstand_issue_feed' ) {
      $this->_action_newsstand_issue_feed();
    }
  }

  protected function _feed_header( $updated_date ) {
    header('Content-Type: ' . feed_content_type( 'rss-http' ) . '; charset=' . get_option( 'blog_charset' ), true );
    $this->_print_line( '<?xml version="1.0" encoding="' . get_option( 'blog_charset' ) . '"?>
<feed xmlns="http://www.w3.org/2005/Atom" xmlns:news="http://itunes.apple.com/2011/Newsstand">
<updated>' . mysql2date( 'Y-m-d\TH:i:s\Z', $updated_date, false ) . '</updated>' );
  }

  protected function _feed_footer() {
    $this->_print_line( '</feed>' );
  }

  protected function _feed_entry( $post ) {
    $entry = '<entry>
<id>' . $post->ID . '</id>
<updated>' . mysql2date( 'Y-m-d\TH:i:s\Z', $post->post_modified_gmt, false ) . '</updated>
<published>' . mysql2date( 'Y-m-d\TH:i:s\Z', $post->post_date_gmt, false ) . '</published>
<summary>' . $post->post_excerpt . '</summary>';
    $this->_print_line( $entry );
    $this->_feed_cover_art_icons( $post->ID );
    $this->_print_line( '</entry>' );
    return $entry;
  }

  protected function _feed_cover_art_icons( $post_id ) {
    $this->_print_line( '<news:cover_art_icons>' );
    $post_thumbnail_id = get_post_meta( $post_id, '_pr_newsstand_issue_cover', true );
    $image_attributes = wp_get_attachment_image_src( $post_thumbnail_id, 'full' );
    if( $image_attributes ) {
      $this->_print_line( '<news:cover_art_icon size="SOURCE" src="' . $image_attributes[0] . '"/>' );
    }

    $this->_print_line( '</news:cover_art_icons>' );
  }

  /**
   *
   * @void
   */
  protected function _action_newsstand_issue_feed() {

    global $wp;
    $eproject_slug = $wp->query_vars['editorial_project'];
    if ( !$eproject_slug ) {
      $this->send_response( 400, 'Bad request. Please specify an editorial project.' );
    }
    $eproject = PR_Editorial_Project::get_by_slug( $eproject_slug );
    if ( !$eproject ) {
      $this->send_response( 404, "Not found. Editorial project not found." );
    }

    $latest_issue = PR_Editorial_Project::get_latest_edition( $eproject );
    if ( !$latest_issue ) {
      $this->send_response( 404, "Not found. Editorial project is empty." );
    }

    $this->_feed_header( $latest_issue->post_modified_gmt );

    $issues = PR_Editorial_Project::get_all_editions( $eproject );
    if ( !empty( $issues ) ) {
      foreach ( $issues as $issue ) {
        $this->_feed_entry( $issue );
      }
    }
    $this->_feed_footer();
    exit;
  }

  protected function _print_line( $content ) {
    echo $content . "\n";
  }
}

$pr_server_feed = new PR_Server_Feed;
