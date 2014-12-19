(function(w){
	var sw = document.body.clientWidth, //Viewport Width
		sh = $(window).height() - $('.sg-header').height(), //Viewport Width
		userAgent = navigator.userAgent,
		minViewportWidth = 320, //Minimum Size for Viewport
		maxViewportWidth = 2480, //Maxiumum Size for Viewport
		viewportResizeHandleWidth = 0, //Width of the viewport drag-to-resize handle
		$sgWrapper = $('#sg-gen-container'), //Wrapper around viewport
		$sgViewport = $('#sg-viewport'), //Viewport element
		$sizeWidth = $('.sg-size-px'), //Px size input element in toolbar
		$sizeHeight = $('.sg-size-height'), //Em size input element in toolbar
		$bodySize = 16, //Body size of the document
		fullMode = true;

		//Update dimensions on resize
		$(w).resize(function() {
			sw = document.body.clientWidth;
			sh = Math.max( window.innerHeight, document.body.clientHeight );
			if (fullMode == true) {
				sizeFull();
			}
			fixPagesHeight();
		});

		$sgViewport.bind("transitionend MSTransitionEnd webkitTransitionEnd oTransitionEnd", function(){
			fixPagesHeight();
		});

		/* Pattern Lab accordion dropdown */
		$('.s-menu').click(function(e){
			e.preventDefault();
			var $panel = $(this).next('.sg-acc-panel');
			$( ".s-menu, .sg-acc-panel" ).siblings().removeClass('active');
			$(this).toggleClass('active');
			$panel.toggleClass('active');
		});

		$('.o-menu').hover(function(e){
			e.preventDefault();
			var $panel = $(this).next('.sg-acc-panel');
			$(this).toggleClass('active');
			$panel.toggleClass('active');
		});

		// Reset size
		$('#reset').on("click", function(e){
			e.preventDefault();
			sw = document.body.clientWidth;
			sh = $(window).height() - $('.sg-header').height();
			navigator.__defineGetter__('userAgent', function(){
				return userAgent;
			});
			sizeFull();
			fixPagesHeight();
		});

		// Reset size
		$('#open').on("click", function(e){
			e.preventDefault();
			$iframe = $( '.swiper-slide-active' ).children('iframe');
			window.open( $iframe.attr('src') );
		});

		$('#resize-submit').on("click", function(e){
			e.preventDefault();
			var $w = $('#sg-size-width').val(), $h = $('#sg-size-height').val();
			if( !$w.length || !(Math.floor($w) == $w && $.isNumeric($w)) ) {
				$('#sg-size-width').addClass('input-error');
				return;
			} else {
				$('#sg-size-width').removeClass('input-error');
			}
			if( !$h.length || !(Math.floor($h) == $h && $.isNumeric($h)) ) {
				$('#sg-size-height').addClass('input-error');
				return;
			} else {
				$('#sg-size-height').removeClass('input-error');
			}
			navigator.__defineGetter__('userAgent', function(){
				return userAgent;
			});
			sizeiframe($w, true, $h);
		});

		$('.tdevice a').on("click", function(e){
			e.preventDefault();
			fullMode = false;
			var agent = $(this).data('agent');
			navigator.__defineGetter__('userAgent', function(){
    		return agent;
			});

			$(this).addClass('active');
			theight = $(this).data('height');
			twidth = $(this).data('width');
			sizeiframe(twidth, true, theight);
		});

		function sizeFull() {
			sizeiframe(sw, false, sh);
		}

		//Resize the viewport
		//'size' is the target size of the viewport
		//'animate' is a boolean for switching the CSS animation on or off. 'animate' is true by default, but can be set to false for things like nudging and dragging

		function sizeiframe(size,animate, height) {
			var width;
			size = (size ? size : $('sg-size-px').value);
			if(size>maxViewportWidth) { //If the entered size is larger than the max allowed viewport size, cap value at max vp size
				width = maxViewportWidth;
			} else if(size<minViewportWidth) { //If the entered size is less than the minimum allowed viewport size, cap value at min vp size
				width = minViewportWidth;
			} else {
				width = size;
			}

			//Conditionally remove CSS animation class from viewport
			if(animate===false) {
				$sgWrapper.removeClass("vp-animate");
				$sgViewport.removeClass("vp-animate"); //If aninate is set to false, remove animate class from viewport
			} else {
				$sgWrapper.addClass("vp-animate");
				$sgViewport.addClass("vp-animate");
			}

			$sgWrapper.width(width); //Resize viewport wrapper to desired size + size of drag resize handler
			$sgViewport.width(width); //Resize viewport to desired size

			$sgWrapper.height(height);
			$sgViewport.height(height);

			updateSizeReading(width, height); //Update values in toolbar
			//fixPagesHeight();
			//reloadPages();
		}

		//Update Pixel and Em inputs
		//'size' is the input number
		//'unit' is the type of unit: either px or em. Default is px. Accepted values are 'px' and 'em'
		//'target' is what inputs to update. Defaults to both
		function updateSizeReading(width, height) {
			$('#sg-size-width').val(width);
			$('#sg-size-height').val(height);
		}

		function reloadPages() {
			$( '.swiper-slide' ).each(function() {
				var st = $(this).data("status");
				if ( st == "loaded" ) {
					$iframe = $(this).children('iframe');
					$iframe.attr('src', $iframe.attr('src'));
				}
			});
		}

		// on "mouseup" we unbind the "mousemove" event and hide the cover again
		$('body').mouseup(function(event) {
			$('#sg-cover').unbind('mousemove');
			$('#sg-cover').css("display","none");
		});

		// capture the viewport width that was loaded and modify it so it fits with the pull bar
		$sgWrapper.width($sgViewport.width());
		$sgWrapper.height($sgViewport.height());
		updateSizeReading($sgViewport.width(), $sgViewport.height());
})(this);
