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
      <li id="phone" >
        <a class="logo" title="PressRoom">pressroom</a>
      </li>
      <li id="phone" >
          <a class="sg-acc-handle group-device" title="Phone"><i class="fa fa-2x fa-mobile"></i>Phone</a>
          <ol class="sg-acc-panel">
            <li class="tdevice"><a class="sg-acc-handle" data-width="320" data-height="480" data-agent="iphone" href="#">iPhone <small></small><span>3.5"</span></a></li>
            <li class="tdevice"><a class="sg-acc-handle" data-width="640" data-height="960" data-agent="iphone" href="#">iPhone 4 <small></small><span>3.5"</span></a></li>
            <li class="tdevice"><a class="sg-acc-handle" data-width="640" data-height="1136" data-agent="iphone" href="#">iPhone 5 <small></small><span>4.0"</span></a></li>
            <li class="divider"></li>
          </ol>
      </li>
      <li id="tablet" >
          <a class="sg-acc-handle group-device" title="Tablet"><i class="fa fa-2x fa-tablet"></i>Tablet</a>
        <ol class="sg-acc-panel">
          <li class="tdevice"><a class="sg-acc-handle" data-width="2048" data-height="1536" data-agent="ipad" href="#">iPad Mini <small></small><span>7.9"</span></a></li>
          <li class="tdevice"><a class="sg-acc-handle" data-width="1024" data-height="768" data-agent="ipad" href="#">iPad 1 &amp; 2 <small></small><span>9.7"</span></a></li>
          <li class="tdevice"><a class="sg-acc-handle" data-width="2048" data-height="1536" data-agent="ipad" href="#">iPad 3 &amp; 4 <small></small><span>9.7"</span></a></li>
          <li class="divider"></li>
        </ol>
      </li>
      <li id="laptop" >
          <a class="sg-acc-handle group-device" title="Laptop"><i class="fa fa-2x fa-laptop"></i>Laptop</a>
          <ol class="sg-acc-panel">
            <li class="tdevice"><a class="sg-acc-handle" data-width="1366" data-height="768" data-agent="macbook" href="#">11' Macbook Air <small></small><span>11"</span></a></li>
            <li class="tdevice"><a class="sg-acc-handle" data-width="1440" data-height="900" data-agent="macbook" href="#">13' Macbook Air <small></small><span>13"</span></a></li>
            <li class="tdevice"><a class="sg-acc-handle" data-width="2880" data-height="1800" data-agent="macbook" href="#">15' Macbook Pro Retina <small></small><span>15"</span></a></li>
            <li class="divider"></li>
          </ol>
      </li>
      <li id="desktop" >
          <a id="reset" class="sg-acc-handle group-device" title="Desktop">Reset</a>
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
                Width <input type="text" class="sg-input sg-size-px" value="">
                Height <input type="text" class="sg-input sg-size-height" value="">
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
