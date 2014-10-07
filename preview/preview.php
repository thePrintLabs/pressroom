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
<script src="assets/js/jquery-2.0.3.min.js"></script>
<script src="assets/js/idangerous.swiper.min.js"></script>
<script src="assets/js/idangerous.swiper.hashnav.js"></script>
<script src="assets/js/idangerous.swiper.progress.js"></script>
<script src="assets/js/iscroll.js"></script>
<script type="text/javascript">

  var limitPosts = <?php echo $num_init_slides; ?>;
  var posts = [<?php echo implode( ',', $linked_posts ); ?>];
  var prScroll;
  var prSwiper;

  function lazyLoad(page, max){
    if (page < max - 1){
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
        lazyLoad(page+1, max);
      });
    } else {
        return $.get('<?php echo admin_url( 'admin-ajax.php'); ?>', {
          'post_id'     : posts[page],
          'edition_id'  : <?php echo $edition_id; ?>,
          'page'        : page,
          'action'      : 'preview_draw_page'
        }, function(src) {
          if (src) {
            addPage(page, src);
          }
        });
    }
  }

  function addPage(page, src) {
    $('#item-'+page).html('<iframe height="100%" width="100%" frameborder="0" src="' + src + '"></iframe>');

    prSwiper.resizeFix();
    //prSwiper.reInit();
    //fixPagesHeight();
  }

  function fixPagesHeight(){
    $('.device, .swiper-slide, .swiper-wrapper').css({height:$(window).height()})
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
      //cssWidthAndHeight: true,
      progress: true,
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
        initScroll(swiper.activeIndex);
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
      prSwiper.swipeNext();
    });

    $(window).on("resize",function(){fixPagesHeight()});
  });
</script>
</body>
</html>
