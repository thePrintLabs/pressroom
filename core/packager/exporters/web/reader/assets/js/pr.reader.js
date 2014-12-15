var prSwiper;
function fixPagesHeight() {
  $('.swiper-pages').css({ height: $('#sg-gen-container').height() });
  prSwiper.resizeFix();
}

$(function() {
  var reader = $( "#reader" ),
  bLeft = $(".circle--left").hide(), bRight = $(".circle--right").hide(), bToc = $("#fire-toc");

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
      }
    },
    onSlideChangeStart: function(s, d) {

      if ( s.activeIndex == s.slides.length - 1 ) {
        bRight.hide();
      } else if ( s.activeIndex == 0 ) {
        bLeft.hide();
      } else {
        bRight.show();
        bLeft.show();
      }

      $item = $(s.activeSlide());
      if ( d == 'to') {
        $prev = $item.prev(), $next = $item.next();
        if ( $next.length ) {
          bRight.show();
        }
        if ( $prev.length ) {
          bLeft.show();
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

  bToc.on("click", function(e){
    e.preventDefault();
    $( "#toc" ).slideToggle('slow', function(){ $("#toc").is(":hidden") ? $('#fire-toc').removeClass('active') : $('#fire-toc').addClass('active') });
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
