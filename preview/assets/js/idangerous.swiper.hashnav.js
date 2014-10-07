Swiper.prototype.plugins.hashNav = function (swiper, params) {
    'use strict';

    var isH = swiper.params.mode === 'horizontal';
    if (!params) return;

    function updateHash(internal) {
        document.location.hash = swiper.activeSlide().getAttribute('data-hash') || '';
    }

    function swipeToHash(e) {
        var hash = document.location.hash.replace('#', '');
        if (!hash) return;
        var speed = e ? swiper.params.speed : 0;
        for (var i = 0, length = swiper.slides.length; i < length; i++) {
            var slide = swiper.slides[i];
            var slideHash = slide.getAttribute('data-hash');
            if (slideHash === hash && slide.getData('looped') !== true) {
                var index = slide.index();
                if (swiper.params.loop) index = index - swiper.loopedSlides;
                swiper.swipeTo(index, speed);
            }
        }
    }

    //Plugin Hooks
    return {
        onSwiperCreated : function (args) {
            swipeToHash();
        },
        onSlideChangeStart: function () {
            updateHash(true);
        },
        onSwipeReset: function () {
            updateHash(true);
        }
    };
};
