<?php
require_once('../../../../wp-load.php');

if ( !isset( $_GET['edition_id']) || !strlen( $_GET['edition_id'] ) ) {
  return;
}

const CONCURRENT_PAGES = 3;

$edition_id = (int)$_GET['edition_id'];
$linked_posts = TPL_Preview::init( $edition_id );
$num_max_slides = count( $linked_posts );
$num_init_slides = min( $num_max_slides, CONCURRENT_PAGES );
$edition = get_post( $edition_id );
$edition_name = TPL_Utils::sanitize_string( $edition->post_title );
$tpl_pressroom->_load_configs();
$configs = $tpl_pressroom->configs;

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
        <div class="swiper-container">
          <div class="swiper-wrapper">
            <?php
            for ( $i = 0; $i < $num_max_slides; $i++ ):
            ?>
            <div id="item-<?php echo $i; ?>" class="swiper-slide" data-hash="slide<?php echo $i; ?>"></div>
            <?php
            endfor;
            ?>
          </div>
          <div class="swiper-scrollbar"></div>
        </div>
      </div>
    </div>
    <div id="toc" data-height="<?php echo $configs['pr-index-height'] ?>"><iframe height="0"  width="100%" frameborder="0" scrolling="no" src="<?php echo TPL_PREVIEW_URI . $edition_name . DIRECTORY_SEPARATOR . "toc.html"  ?>"></iframe></div>
  </div>
</div>
<script src="assets/js/jquery-2.0.3.min.js"></script>
<script src="assets/js/ish_init.js"></script>
<script src="assets/js/idangerous.swiper.min.js"></script>
<script src="assets/js/idangerous.swiper.hashnav.js"></script>
<!--<script src="assets/js/iscroll.js"></script>-->
<script type="text/javascript">

  var limitPosts = <?php echo $num_init_slides; ?>;
  var posts = [<?php echo implode( ',', $linked_posts ); ?>];
  var prScroll;
  var prSwiper;

  function lazyLoad(page, max){

    return $.get('<?php echo admin_url( 'admin-ajax.php'); ?>', {
      'post_id'     : posts[page],
      'edition_id'  : <?php echo $edition_id; ?>,
      'page'        : page,
      'action'      : 'preview_draw_page'
    }, function(src) {
      if (src) {
        addPage(page, src);
      }
    }).then(function(){
      if (page < max - 1){
        lazyLoad(page+1, max);
      }
    });

  }

  function addPage(page, src) {
    $('#item-'+page).html('<iframe height="100%" width="100%" frameborder="0" src="' + src + '"></iframe>');
    prSwiper.resizeFix();
  }

  // function initScroll(index) {
  //
  //   if ( prScroll ) {
  //       prScroll.destroy();
  //   }
  //
  //   prScroll = new IScroll('#item-'+index, {
  //     mouseWheel: true,
  //     scrollbars: true,
  //     interactiveScrollbars: true,
  //     bounce: false,
  //     preventDefault: false
  //   });
  // }

  $(function() {

    prSwiper = new Swiper(".swiper-container",{
      mode: "horizontal",
      loop: false,
      simulateTouch: false,
      grabCursor: false,
      roundLengths: true,
      calculateHeight: false,
      paginationClickable: true,
      keyboardControl: true,
      hashNav: true,
      initialSlide: 0,
      onFirstInit: function(swiper) {
        $(".circle--left").hide();
        lazyLoad(0, limitPosts);
      },
      onSlideChangeStart: function(swiper) {
        if ( swiper.activeIndex == swiper.slides.length - 1 ) {
          $(".circle--right").hide();
        } else if ( swiper.activeIndex == 0 ) {
          $(".circle--left").hide();
        } else {
          $(".circle--left, .circle--right").show();
        }

        if ( limitPosts < posts.length && swiper.activeIndex + 1 == limitPosts - 1 ) {
          var min = Math.min( <?php echo CONCURRENT_PAGES; ?>, posts.length - limitPosts);
          lazyLoad(limitPosts, limitPosts + min);
          limitPosts += min;
        }
      },
      onSlideChangeEnd: function(swiper) {
        //initScroll(swiper.activeIndex);
      },
    });

    $(".arrow-left").on("click", function(e){
      e.preventDefault();
      prSwiper.swipePrev();
    })

    $(".arrow-right").on("click", function(e){
      e.preventDefault();
      prSwiper.swipeNext();
    });
    $( "#fire-toc" ).click(function(e) {
      event.preventDefault();
      var height = document.getElementById('toc').getAttribute( 'data-height' );
      $( "#toc" ).height( height );
      if( height > 0 ) {
         document.getElementById('toc').setAttribute( 'data-height', 0 );
      }
      else {
         document.getElementById('toc').setAttribute( 'data-height', <?php echo $configs['pr-index-height'] ?> );
      }
   });
  });
</script>
</body>
</html>
