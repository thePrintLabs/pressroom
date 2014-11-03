var tocSwiper = new Swiper('.swiper-container', { 
	slideElement:'article',
	slidesPerView: 3,
	resizeReInit: true,
	resistance: '100%',
	roundLengths: true,
	onSlideChangeEnd: function() {
	  BackgroundCheck.refresh();
	}
});

$checkImage = $('.cover__image');

document.addEventListener('DOMContentLoaded', function () {
	if ($checkImage.length > 0){
		BackgroundCheck.init({
			targets: '.check',
			images: '.cover__image',
			windowEvents: true
		});
	}
});