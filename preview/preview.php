<?php
require_once('../../../../wp-load.php');

if ( isset( $_GET['edition_id']) && strlen( $_GET['edition_id'] ) ) {

  $edition_id = (int)$_GET['edition_id'];
  $posts_id = TPL_Preview::init( $edition_id );
}

?>
<!DOCTYPE html>
<head>
<meta charset="utf-8">
<meta http-equiv="X-UA-Compatible" content="IE=edge,chrome=1">
<title></title>
<meta name="description" content="">
<meta name="viewport" content="width=device-width, initial-scale=1">
<link rel="stylesheet" type="text/css" href="assets/css/preview.css" />
<link rel="stylesheet" type="text/css" href="assets/css/idangerous.swiper.css" />
</head>
<body>
<div class="device">
<div class="circle circle--left"><a class="arrow-left" href="#"></a></div>
<div class="circle circle--right"><a class="arrow-right" href="#"></a></div>
<div class="swiper-container">
  <div class="swiper-wrapper"></div>
</div>
</div>
<script src="assets/js/jquery-2.0.3.min.js"></script>
<script src="assets/js/idangerous.swiper.min.js"></script>
<script src="assets/js/idangerous.swiper.hashnav.js"></script>
<script src="assets/js/iscroll.js"></script>
<script type="text/javascript">

  var posts = [<?php echo implode( ',', $posts_id ); ?>];
  var prScroll;
  var prSwiper;

  function lazyLoad(page) {
    jQuery.get('<?php echo admin_url( 'admin-ajax.php'); ?>', {
      'post_id'     : posts[page],
      'edition_id'  : <?php echo $edition_id; ?>,
      'page'        : page,
      'action'      : 'preview_draw_page'
    }, function(src) {
      if (src) {
        prSwiper.appendSlide( '<div class="swiper-container swiper-in-slider swiper-in-slider-new">\
        <div id="item-' + page + '" class="swiper-slide" data-hash="slide' + page + '">\
        <div class="content-slider" style="height:100%">\
        <iframe height="100%" width="100%" frameborder="0" sandobx="allow-scripts" src="' + src + '"></iframe></div>\
        </div>\
        </div>\
        <div class="swiper-scrollbar"></div>' );

        prSwiper.resizeFix();
        prSwiper.reInit();
        fixPagesHeight();

        prSwiper.swipeNext();
      }
    });
  }

  function fixPagesHeight(){
    $(".device").css({height:$(window).height()})
    $(".swiper-slide").css({height:$(window).height()})
    $(".swiper-wrapper").css({height:$(window).height()})
  }

  function initScroll(index) {

    if ( prScroll ) {
        prScroll.destroy();
    }

    prScroll = new IScroll('#item-'+index, {
        mouseWheel: true,
        scrollbars: true,
        interactiveScrollbars: true,
        bounce: false,
        preventDefault: false
    });
  }

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
      progress: true,
      onFirstInit: function (){
        fixPagesHeight();
        lazyLoad(0);
      },
      onSlideChangeEnd: function(swiper) {
        initScroll(prSwiper.activeIndex);
      },
      onProgressChange: function(swiper){
        for (var i = 0; i < swiper.slides.length; i++){
          var slide = swiper.slides[i];
          var progress = slide.progress;
          var translate, boxShadow;
          if (progress>0) {
            translate = progress*swiper.width;
            boxShadowOpacity = 0;
          }
          else {
            translate=0;
            boxShadowOpacity = 1  - Math.min(Math.abs(progress),1);
          }
          slide.style.boxShadow='0px 0px 10px rgba(0,0,0,'+boxShadowOpacity+')';
          swiper.setTransform(slide,'translate3d('+(translate)+'px,0,0)');
        }
      },
      onTouchStart:function(swiper){
        for (var i = 0; i < swiper.slides.length; i++){
          swiper.setTransition(swiper.slides[i], 0);
        }
      },
      onSetWrapperTransition: function(swiper) {
        for (var i = 0; i < swiper.slides.length; i++){
          swiper.setTransition(swiper.slides[i], swiper.params.speed);
        }
      }
    });

    // Set Z-Indexes
    for (var i = 0; i < prSwiper.slides.length; i++){
      prSwiper.slides[i].style.zIndex = i;
    }

    $(".arrow-left").on("click", function(e){
      e.preventDefault();
      prSwiper.swipePrev();
    })

    $(".arrow-right").on("click", function(e){
      e.preventDefault();
      if( prSwiper.slides.length < posts.length
        && posts[prSwiper.activeIndex + 1] ) {
        lazyLoad(prSwiper.activeIndex + 1);
      }
      prSwiper.swipeNext();
    });
  });


  $(window).on("resize",function(){fixPagesHeight()});
</script>
</body>
</html>
