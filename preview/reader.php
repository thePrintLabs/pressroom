<?php
const CONCURRENT_PAGES = 3;
require_once('../../../../wp-load.php');
if ( !defined( 'WP_ADMIN' ) ) {
  define( 'WP_ADMIN', true );
}

if ( !is_admin() || !is_user_logged_in() ) {
  wp_redirect( home_url('/login') );
}

if ( !isset( $_GET['edition_id']) || !strlen( $_GET['edition_id'] ) ) {
  wp_die( __( "<b>Error getting required params. Please check your url address.</b>", 'pressroom' ) );
}

$edition_id = (int)$_GET['edition_id'];

if ( !isset( $_GET['post_id'] ) || !strlen( $_GET['post_id'] ) ) {
  $linked_posts = TPL_Preview::init( $edition_id );
  $concurrent_slides = min( count( $linked_posts ), CONCURRENT_PAGES );
}
else {
  $linked_posts = array( (int)$_GET['post_id'] );
  $concurrent_slides = 1;
}

if ( empty( $linked_posts ) ) {
  wp_die( __( "<b>There was an error while trying to build the edition preview.</b><p>Suggestions:</p><ul>
  <li>Check if the edition with id <b>$edition_id</b> exist</li><li>Ensure that there is least one post visible</li></ul>", 'pressroom' ) );
}

$edition = get_post( $edition_id );
$edition_name = TPL_Utils::sanitize_string( $edition->post_title );
$index_height = pr_get_option( 'pr-index-height' );

?>
<!DOCTYPE html>
<head>
  <meta charset="utf-8">
  <meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
  <title>Pressroom - Preview</title>
  <meta name="description" content="">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="../assets/css/reset.css">
  <link rel="stylesheet" href="assets/css/ish.css">
  <link rel="stylesheet" type="text/css" href="assets/css/preview.css" />
  <link rel="stylesheet" type="text/css" href="assets/css/idangerous.swiper.css" />
</head>
<body>
<header class="sg-header" role="banner">
  <a class="sg-nav-toggle" href="#sg-nav-container"><span class="icon-menu"></span>Menu</a>
  <div id="sg-nav-container" class="sg-nav-container">
    <ol class="sg-nav">
      <li>
        <a class="logo" title="PressRoom">PressRoom</a>
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
      <li id="desktop" >
        <a id="reset" class="sg-acc-handle group-device" data-agent="desktop" title="Desktop">Fullscreen</a>
      </li>
      <li>
        <a id="fire-toc" href="#" title="Open toc bar">Open Toc</a>
      </li>
    <ol>
    <div class="sg-controls" id="sg-controls">
      <div class="sg-control-content">
        <ul class="sg-control">
          <li class="sg-size">
            <div class="sg-current-size">
              <form id="sg-form">
                Width <input type="text" id="sg-size-width" class="sg-input sg-size-px" value="">
                Height <input type="text" id="sg-size-height" class="sg-input sg-size-height" value="">
                <button type="button" id="resize-submit">Resize</button>
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
      <div class="device">
        <div class="circle circle--left"><a class="arrow-left" href="#"></a></div>
        <div class="circle circle--right"><a class="arrow-right" href="#"></a></div>
        <div class="swiper-pages swiper-container" id="reader"
          data-edition="<?php echo $edition_id; ?>"
          data-conpages="<?php echo $concurrent_slides; ?>"
          data-url="<?php echo admin_url( 'admin-ajax.php'); ?>">
          <div class="swiper-wrapper">
            <?php
            foreach ( $linked_posts as $post_id ):
            ?>
            <div data-post="<?php echo $post_id; ?>" class="swiper-slide" data-hash="item-<?php echo $post_id; ?>"></div>
            <?php
            endforeach;
            ?>
          </div>
        </div>
        <div class="pagination"></div>
      </div>
    </div>
    <div id="toc" style="height:<?php echo $index_height ?>px;display:none">
      <iframe width="100%" frameborder="0" scrolling="no" src="<?php echo TPL_PREVIEW_URI . $edition_name . DIRECTORY_SEPARATOR . "toc.html"  ?>"></iframe>
    </div>
  </div>
</div>
<script src="assets/js/jquery-2.0.3.min.js"></script>
<script src="assets/js/idangerous.swiper.min.js"></script>
<script src="assets/js/idangerous.swiper.hashnav.min.js"></script>
<script src="assets/js/tpl.reader.js"></script>
<script src="assets/js/ish_init.js"></script>
</body>
</html>
