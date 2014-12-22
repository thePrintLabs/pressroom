<?php
const CONCURRENT_PAGES = 3;
if ( file_exists( dirname( __FILE__ ) . '/.pr_path' ) ) {
  $wp_load_path = file_get_contents( '.pr_path' );
} else {
  die( 'wp-load.php path not found in .pr_path. Please define it manually' );
}

if ( !defined( 'WP_ADMIN' ) ) {
  define( 'WP_ADMIN', true );
}

require_once( $wp_load_path );

if ( !is_admin() || !is_user_logged_in() ) {
  wp_redirect( home_url('/login') );
}

if ( !isset( $_GET['edition_id']) || !strlen( $_GET['edition_id'] ) ) {
  wp_die( __( "<b>Error getting required params. Please check your url address.</b>", 'pressroom' ) );
}

if( !isset($_GET['package_type'])) {
  wp_die( __( "<b>Error: missing package type </b>", 'pressroom' ) );
}

$edition_id = (int)$_GET['edition_id'];
$package_type = $_GET['package_type'];

if ( !isset( $_GET['post_id'] ) || !strlen( $_GET['post_id'] ) ) {
  $linked_posts = PR_Preview::init( $edition_id );
  $concurrent_slides = min( count( $linked_posts ), CONCURRENT_PAGES );
}
else {
  $linked_posts = array( get_post( $_GET['post_id'] ) );
  $concurrent_slides = 1;
}

if ( empty( $linked_posts ) ) {
  wp_die( __( "<b>There was an error while trying to build the edition preview.</b><p>Suggestions:</p><ul>
  <li>Check if the edition with id <b>$edition_id</b> exist</li><li>Ensure that there is least one post visible</li></ul>", 'pressroom' ) );
}

$terms = wp_get_post_terms( $_GET['edition_id'], PR_EDITORIAL_PROJECT );
if ( empty( $terms ) ) {
  wp_die( __( "<b>There was an error while trying to build the edition preview.</b><p>Suggestions:</p><ul>
  <li>Ensure that there is least one editorial project linked to this edition</li></ul>", 'pressroom' ) );
}

$edition = get_post( $edition_id );
$edition_name = PR_Utils::sanitize_string( $edition->post_title );
$index_height = 150;
?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>Pressroom - Preview</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../../assets/css/preview/preview.ish.min.css">
  <link rel="stylesheet" type="text/css" href="../../assets/css/preview/preview.min.css" />
  <link rel="stylesheet" type="text/css" href="../../assets/css/preview/idangerous.swiper.min.css" />
</head>
<body>
<header class="sg-header" role="banner">
  <a class="sg-nav-toggle" href="#sg-nav-container"><span class="icon-menu"></span>Menu</a>
  <div id="sg-nav-container" class="sg-nav-container">
    <div class="pagination">
      <div class="dots"></div>
    </div>
    <ol class="sg-nav">
      <li>
        <a class="logo" title="PressRoom">PressRoom</a>
      </li>
      <li>
        <a id="fire-toc" href="#" title="Open toc bar">Toc</a>
      </li>
      <li id="phone" >
        <a class="sg-acc-handle group-device o-menu" title="iPhone">Devices</a>
        <ol class="sg-acc-panel">
          <li class="sg-nav-global">
            <a class="sg-acc-handle s-menu">iPhone 3/4</a>
            <ol class="sg-acc-panel sg-sub-nav">
              <li class="tdevice"><a data-width="320" data-height="480" data-agent="iphone" href="#">portrait</a></li>
              <li class="tdevice"><a data-width="480" data-height="320" data-agent="iphone" href="#">landscape</a></li>
            </ol>
          </li>
          <li class="sg-nav-global">
            <a class="sg-acc-handle s-menu">iPhone 5</a>
            <ol class="sg-acc-panel sg-sub-nav">
              <li class="tdevice"><a data-width="320" data-height="568" data-agent="iphone" href="#">portrait</a></li>
              <li class="tdevice"><a data-width="568" data-height="320" data-agent="iphone" href="#">landscape</a></li>
            </ol>
          </li>
          <li class="sg-nav-global">
            <a class="sg-acc-handle s-menu">iPhone 6</a>
            <ol class="sg-acc-panel sg-sub-nav">
              <li class="tdevice"><a data-width="375" data-height="667" data-agent="iphone" href="#">portrait</a></li>
              <li class="tdevice"><a data-width="667" data-height="375" data-agent="iphone" href="#">landscape</a></li>
            </ol>
          </li>
          <li class="sg-nav-global">
            <a class="sg-acc-handle s-menu">iPhone 6 Plus</a>
            <ol class="sg-acc-panel sg-sub-nav">
              <li class="tdevice"><a data-width="414" data-height="736" data-agent="iphone" href="#">portrait</a></li>
              <li class="tdevice"><a data-width="736" data-height="414" data-agent="iphone" href="#">landscape</a></li>
            </ol>
          </li>
          <li class="sg-nav-global">
            <a class="sg-acc-handle s-menu">iPad</a>
            <ol class="sg-acc-panel sg-sub-nav">
              <li class="tdevice"><a data-width="768" data-height="1024" data-agent="ipad" href="#">portrait</a></li>
              <li class="tdevice"><a data-width="1024" data-height="768" data-agent="ipad" href="#">landscape</a></li>
            </ol>
          </li>
        </ol>
      </li>
      <li id="desktop">
        <a id="reset" class="sg-acc-handle group-device" data-agent="desktop" title="Reset">Reset</a>
      </li>
      <li>
        <a id="open" title="Open">Open</a>
      </li>
    <ol>
    <div class="sg-controls" id="sg-controls">
      <div class="sg-control-content">
        <ul class="sg-control">
          <li class="sg-size">
            <div class="sg-current-size">
              <form id="sg-form">
                W <input type="text" id="sg-size-width" class="sg-input sg-size-px" value="">
                H <input type="text" id="sg-size-height" class="sg-input sg-size-height" value="">
                <button type="button" id="resize-submit">Apply</button>
              </form>
            </div>
          </li>
        </ul>
      </div>
    </div>
  </div>
</header>
<div id="sg-vp-wrap">
  <div id="sg-cover"></div>
  <div id="sg-gen-container">
    <div id="sg-viewport">
      <div class="circle circle--left"><a class="arrow-left" href="#"></a></div>
      <div class="circle circle--right"><a class="arrow-right" href="#"></a></div>
      <div class="swiper-pages swiper-container" id="reader"
        data-edition="<?php echo $edition_id; ?>"
        data-package-type="<?php echo $package_type; ?>"
        data-conpages="<?php echo $concurrent_slides; ?>"
        data-url="<?php echo admin_url( 'admin-ajax.php'); ?>">
        <div class="swiper-wrapper">
          <?php foreach ( $linked_posts as $post ): ?>
          <div data-post="<?php echo $post->ID; ?>" class="swiper-slide" data-hash="item-<?php echo $post->ID; ?>">
            <div class="spinner">
              <div class="bounce1"></div>
              <div class="bounce2"></div>
              <div class="bounce3"></div>
            </div>
          </div>
          <?php endforeach; ?>
        </div>
      </div>
    </div>
    <div id="toc" style="height:<?php echo $index_height ?>px;display:none">
      <iframe width="100%" frameborder="0" scrolling="no" src="<?php echo PR_PREVIEW_URI . $edition_name . DIRECTORY_SEPARATOR . "index.html"  ?>"></iframe>
    </div>
  </div>
</div>
<script src="../../assets/js/preview/jquery-2.0.3.min.js"></script>
<script src="../../assets/js/preview/idangerous.swiper.min.js"></script>
<script src="../../assets/js/preview/idangerous.swiper.hashnav.min.js"></script>
<script src="../../assets/js/preview/pr.reader.min.js"></script>
<script src="../../assets/js/preview/pr.reader.ish.min.js"></script>
</body>
</html>
