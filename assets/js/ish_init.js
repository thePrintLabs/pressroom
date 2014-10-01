(function(w){
	var sw = document.body.clientWidth, //Viewport Width
		minViewportWidth = 240, //Minimum Size for Viewport
		maxViewportWidth = 2600, //Maxiumum Size for Viewport
		viewportResizeHandleWidth = 14, //Width of the viewport drag-to-resize handle
		$sgWrapper = jQuery('#sg-gen-container'), //Wrapper around viewport
		$sgViewport = $('#sg-viewport'), //Viewport element
		$sizePx = $('.sg-size-px'), //Px size input element in toolbar
		$sizeEms = $('.sg-size-em'), //Em size input element in toolbar
		$sizeSwiperSlide = $('.swiper-slide'),
		$bodySize = 16, //Body size of the document
		discoID = false,
		fullMode = false,
		discoMode = false,
		hayMode = false,
		hash = window.location.hash.replace(/^.*?#/,'');

	//URL Form Submission
	$('#url-form').submit(function(e) {
		var urlVal = $('#url').val();
		var regex = /((([A-Za-z]{3,9}:(?:\/\/)?)(?:[-;:&=\+\$,\w]+@)?[A-Za-z0-9.-]+|(?:www.|[-;:&=\+\$,\w]+@)[A-Za-z0-9.-]+)((?:\/[\+~%\/.\w-_]*)?\??(?:[-\+=&;%@.\w_]*)#?(?:[\w]*))?)/;

		if(regex.test(urlVal)) {
			return;
		} else {
			var newURL = "http://"+urlVal;
			$('#url').val(newURL);
			return;
		}

	});

	$(w).resize(function(){ //Update dimensions on resize
		sw = document.body.clientWidth;

		if(fullMode == true) {
			sizeFull();
		}
	});

	/* Nav Active State */
	function changeActiveState(link) {
		var $activeLink = link;
		$('.sg-size-options a').removeClass('active');

		if(link) {
			$activeLink.addClass('active');
		}
	}

	/* Pattern Lab accordion dropdown */
	$('.sg-acc-handle').on("click", function(e){
		var $this = $(this),
			$panel = $this.next('.sg-acc-panel');
		e.preventDefault();
		$this.toggleClass('active');
		$panel.toggleClass('active');
	});

	//Size Trigger
	$('#sg-size-toggle').on("click", function(e){
		e.preventDefault();
		$(this).parents('ul').toggleClass('active');
	});

	//Size View Events

	//Click Size Small Button
	$('#sg-size-s').on("click", function(e){
		e.preventDefault();
		fullMode = false;
		window.location.hash = 's';
		changeActiveState($(this));
		sizeSmall();
	});

	//Click Size Medium Button
	$('.tdevice a').on("click", function(e){
		e.preventDefault();
		fullMode = false;
		changeActiveState($(this));
		theight = $(this).data('height');
		twidth = $(this).data('width');
		console.log(theight);
		console.log(twidth);
		sizeiframe(twidth, true, theight);
	});

	function sizeFull() {
		sizeiframe(sw, false);
		updateSizeReading(sw);
	}



	//Pixel input
	$sizePx.on('keydown', function(e){
		var val = Math.floor($(this).val());

		if(e.keyCode === 38) { //If the up arrow key is hit
			val++;
			sizeiframe(val,false);
			window.location.hash = val;
		} else if(e.keyCode === 40) { //If the down arrow key is hit
			val--;
			sizeiframe(val,false);
			window.location.hash = val;
		} else if(e.keyCode === 13) { //If the Enter key is hit
			e.preventDefault();
			sizeiframe(val); //Size Iframe to value of text box
			window.location.hash = val;
			$(this).blur();
		}
		changeActiveState();
	});

	$sizePx.on('keyup', function(){
		var val = Math.floor($(this).val());
		updateSizeReading(val,'px','updateEmInput');
	});

	//Em input
	$sizeEms.on('keydown', function(e){
		var val = parseFloat($(this).val());

		if(e.keyCode === 38) { //If the up arrow key is hit
			val++;
			sizeiframe(Math.floor(val*$bodySize),false);
		} else if(e.keyCode === 40) { //If the down arrow key is hit
			val--;
			sizeiframe(Math.floor(val*$bodySize),false);
		} else if(e.keyCode === 13) { //If the Enter key is hit
			e.preventDefault();
			sizeiframe(Math.floor(val*$bodySize)); //Size Iframe to value of text box
		}
		changeActiveState();

		window.location.hash = parseInt(val*$bodySize);
	});

	$sizeEms.on('keyup', function(){
		var val = parseFloat($(this).val());
		updateSizeReading(val,'em','updatePxInput');
	});

	//Resize the viewport
	//'size' is the target size of the viewport
	//'animate' is a boolean for switching the CSS animation on or off. 'animate' is true by default, but can be set to false for things like nudging and dragging

	function sizeiframe(size,animate, height) {
		var theSize;

		if(size>maxViewportWidth) { //If the entered size is larger than the max allowed viewport size, cap value at max vp size
			theSize = maxViewportWidth;
		} else if(size<minViewportWidth) { //If the entered size is less than the minimum allowed viewport size, cap value at min vp size
			theSize = minViewportWidth;
		} else {
			theSize = size;
		}

		//Conditionally remove CSS animation class from viewport
		if(animate===false) {
			$sgWrapper.removeClass("vp-animate");
			$sgViewport.removeClass("vp-animate"); //If aninate is set to false, remove animate class from viewport
		} else {
			$sgWrapper.addClass("vp-animate");
			$sgViewport.addClass("vp-animate");
		}

		$sgWrapper.width(theSize); //Resize viewport wrapper to desired size + size of drag resize handler
		$sgViewport.width(theSize); //Resize viewport to desired size

		$sgWrapper.height(height);
		$sgViewport.height(height);

		updateSizeReading(theSize); //Update values in toolbar
	}




	//Update Pixel and Em inputs
	//'size' is the input number
	//'unit' is the type of unit: either px or em. Default is px. Accepted values are 'px' and 'em'
	//'target' is what inputs to update. Defaults to both
	function updateSizeReading(size,unit,target) {
		if(unit=='em') { //If size value is in em units
			emSize = size;
			pxSize = Math.floor(size*$bodySize);
		} else { //If value is px or absent
			pxSize = size;
			emSize = size/$bodySize;
		}

		if (target == 'updatePxInput') {
			$sizePx.val(pxSize);
		} else if (target == 'updateEmInput') {
			$sizeEms.val(emSize.toFixed(2));
		} else {
			$sizeEms.val(emSize.toFixed(2));
			$sizePx.val(pxSize);
		}
	}

	function updateViewportWidth(size) {
		$sgViewport.width(size);
		$sgWrapper.width(size*1 + 14);

		updateSizeReading(size);
	}

	// handles widening the "viewport"
	//   1. on "mousedown" store the click location
	//   2. make a hidden div visible so that it can track mouse movements and make sure the pointer doesn't get lost in the iframe
	//   3. on "mousemove" calculate the math, save the results to a cookie, and update the viewport
	$('#sg-rightpull').mousedown(function(event) {

		// capture default data
		var origClientX = event.clientX;
		var origViewportWidth = $sgViewport.width();

		fullMode = false;

		// show the cover
		$("#sg-cover").css("display","block");

		// add the mouse move event and capture data. also update the viewport width
		$('#sg-cover').mousemove(function(event) {

			viewportWidth = (origClientX > event.clientX) ? origViewportWidth - ((origClientX - event.clientX)*2) : origViewportWidth + ((event.clientX - origClientX)*2);

			if (viewportWidth > minViewportWidth) {


				window.location.hash = viewportWidth;
				sizeiframe(viewportWidth,false);
			}
		});
	});

	// on "mouseup" we unbind the "mousemove" event and hide the cover again
	$('body').mouseup(function(event) {
		$('#sg-cover').unbind('mousemove');
		$('#sg-cover').css("display","none");
	});

	// capture the viewport width that was loaded and modify it so it fits with the pull bar
	var origViewportWidth = $sgViewport.width();
	$sgWrapper.width(origViewportWidth);
	$sgViewport.width(origViewportWidth - 14);
	updateSizeReading($sgViewport.width());


})(this);
