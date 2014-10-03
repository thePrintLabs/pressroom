var mySwiper = new Swiper(".swiper-container",{
           mode:"horizontal",
           simulateTouch: false, 
           grabCursor: false,
           roundLengths: true,
           calculateHeight: false,
           paginationClickable: true,
           keyboardControl: true,
           onFirstInit: function (){
            fixPagesHeight();
           }
         });

         $(".arrow-left").on("click", function(e){
           e.preventDefault();
           mySwiper.swipePrev();
         })
         $(".arrow-right").on("click", function(e){
           e.preventDefault();
           mySwiper.swipeNext();
         });

         function fixPagesHeight(){
            $(".device").css({height:$(window).height()})
            $(".swiper-slide").css({height:$(window).height()})
            $(".swiper-wrapper").css({height:$(window).height()})
         }
         $(window).on("resize",function(){fixPagesHeight()});

         var wrapper = document.getElementById("banana");
         var wrapper = $(".swiper-slide-active");

         var myScroll = new IScroll(wrapper, {
            mouseWheel: true,
            scrollbars: true,
            bounce: false,
            bindToWrapper: true,
            preventDefault: false
        });