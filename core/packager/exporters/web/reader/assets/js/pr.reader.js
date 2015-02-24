var prSwiper;
function fixPagesHeight() {
  $('.swiper-pages').css({ height: $('#sg-gen-container').height() });
  prSwiper.resizeFix();
}

function getNotLoadedSlide(item, d, i, m) {
  if ( i == m ) {
    return false;
  }
  var next = d == 'next' ? item.next() : item.prev();
  if ( !next.length ) {
    return false;
  }
  if ( next.data("status") != "loaded" ) {
    return next;
  } else {
    getNotLoadedSlide(next, d, i+1, m);
  }
}

$(function() {
  var reader = $( "#reader" ), conPages = reader.data( "conpages" ),
  bLeft = $(".circle--left").hide(), bRight = $(".circle--right").hide();

  $.fn.lazyLoad = function(i, m, d) {
    var $item = $(this), st = $item.data("status");
    if (st != "loaded") {
      return $.get(reader.data( "url" ), {
        'post_id'     : $item.data( "post" ),
        'edition_id'  : reader.data( "edition" ),
        'package_type'  : reader.data( "package-type" ),
        'action'      : 'preview_draw_page',
        'pr_no_theme' : true
      }, function(s) {
        if (s) {
          var $iframe = $("<iframe>", {src: s, "height": "100%", "width": "100%", "frameborder": 0});
          $iframe.load(function(){
            var $head = $iframe.contents().find("head");
            $head.append($('<style>body{-webkit-touch-callout: none;-webkit-user-select: none;-khtml-user-select: none;-moz-user-select: none;-ms-user-select: none;user-select: none;}</style>'));
            //prSwiper.resizeFix();
            //console.log( 'User Agent: ' + navigator.userAgent );
          });
          $item.data("status", "loaded").children('.spinner').replaceWith($iframe).hide().fadeIn('slow');
        }
      }).then(function(){
        if (i < m - 1 ) {
          $n = d == 'prev' ? $item.prev() : $item.next();
          if ( $n.length ) {
            $n.lazyLoad(i+1, m, d);
          }
        }
      });
    } else {
      if (i < m - 1 ) {
        $n = d == 'prev' ? $item.prev() : $item.next();
        if ( $n.length ) {
          $n.lazyLoad(i+1, m, d);
        }
      }
    }
  };

  prSwiper = new Swiper(".swiper-pages", {
    mode: "horizontal",
    pagination: '.dots',
    loop: false,
    simulateTouch: false,
    grabCursor: false,
    roundLengths: true,
    calculateHeight: false,
    paginationClickable: true,
    keyboardControl: true,
    hashNav: true,
    speed : 500,
    onFirstInit: function(s) {
      var hash = window.location.hash;
      $item = $(hash);
      if ( !$item.length ) {
        $item = $('.swiper-slide:first');
        $next = $item.next();
        if ( $next.length ) {
          bRight.show();
        }
        $item.lazyLoad(0, conPages, 'next' );
      }
    },
    onSlideChangeStart: function(s, d) {

      if ( s.activeIndex == s.slides.length - 1 ) {
        bRight.hide();
        bLeft.show();
      } else if ( s.activeIndex == 0 ) {
        bLeft.hide();
        bRight.show();
      } else {
        bRight.show();
        bLeft.show();
      }

      $item = $(s.activeSlide());
      if ( d == 'to') {
        $item.lazyLoad(0, 1, 'next' );
        $prev = $item.prev(), $next = $item.next();
        if ( $next.length ) {
          bRight.show();
          $next.lazyLoad(0, conPages, 'next' );
        }
        if ( $prev.length ) {
          bLeft.show();
          $prev.lazyLoad(0, conPages, 'prev' );
        }
      }
      else {
        $next = getNotLoadedSlide($item, d, 0, conPages - 1);
        if ( $next ) {
          $next.lazyLoad(0, conPages, d );
        }
      }
    },
    onSlideChangeEnd: function(swiper) {
    },
  });

  bLeft.on("click", function(e){
    e.preventDefault();
    prSwiper.swipePrev();
  })

  bRight.on("click", function(e){
    e.preventDefault();
    prSwiper.swipeNext();
  });

  $(window).on('hashchange', function(e){
    var h = document.location.hash;
    if (h.match("^#toc-")) {
      h = h.replace('#toc-', 'item-');
      if (!h) return;
      for (var i = 0; i < prSwiper.slides.length; i++) {
        var slide = prSwiper.slides[i];
        var slideHash = slide.data('hash');
        if (slideHash === h && slide.getData('looped') !== true) {
          var index = slide.index();
          if (prSwiper.params.loop) index = index - prSwiper.loopedSlides;
          prSwiper.swipeTo(index, prSwiper.speed);
        }
      }
    }
  });

  fixPagesHeight();
});
