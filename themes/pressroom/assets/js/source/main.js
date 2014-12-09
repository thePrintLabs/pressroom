$checkTitle = $('.cover__title--resize');
$checkEmbed = $('.entry-content-asset');

if ($checkEmbed.length > 0){
	$(".entry-content-asset").fitVids();
}

var mql = window.matchMedia("(min-width: 500px)");

if(mql.matches) {
	if ($checkTitle.length > 0){
		textFit($checkTitle, {minFontSize:10, maxFontSize: 150, reProcess: false});
	}
}

mql.addListener(function(m) {
	if(m.matches){
		if ($checkTitle.length > 0){
			textFit($checkTitle, {minFontSize:10, maxFontSize: 150, reProcess: false});
		}		
	} else {
		$('.textFitted').removeAttr("style");
	}
});

var ua = window.navigator.userAgent;

var str = "BakerFramework";
var n = ua.search(str);

if (n < 0) {
	$( "<style>@font-face {font-family: 'Playfair Display';src: url('fonts/PlayfairDisplay-Bold.eot'); /* IE9 Compat Modes */src: url('fonts/PlayfairDisplay-Bold.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/PlayfairDisplay-Bold.woff') format('woff'), /* Modern Browsers */ url('fonts/PlayfairDisplay-Bold.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/PlayfairDisplay-Bold.svg#798d8a621812f2b0a3cb96fce20cf246') format('svg'); /* Legacy iOS */ font-style: normal;font-weight:700;}@font-face {font-family: 'Playfair Display';src: url('fonts/PlayfairDisplay-BoldItalic.eot'); /* IE9 Compat Modes */src: url('fonts/PlayfairDisplay-BoldItalic.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/PlayfairDisplay-BoldItalic.woff') format('woff'), /* Modern Browsers */ url('fonts/PlayfairDisplay-BoldItalic.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/PlayfairDisplay-BoldItalic.svg#f2e8588510672d694c8805b9213213f6') format('svg'); /* Legacy iOS */ font-style: italic;font-weight:700;}@font-face {font-family: 'Playfair Display';src: url('fonts/PlayfairDisplay-Italic.eot'); /* IE9 Compat Modes */src: url('fonts/PlayfairDisplay-Italic.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/PlayfairDisplay-Italic.woff') format('woff'), /* Modern Browsers */ url('fonts/PlayfairDisplay-Italic.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/PlayfairDisplay-Italic.svg#2bd500c7d53fe4ac5f23770a611a3ab6') format('svg'); /* Legacy iOS */ font-style: italic;font-weight:400;}@font-face {font-family: 'Playfair Display';src: url('fonts/PlayfairDisplay-Regular.eot'); /* IE9 Compat Modes */src: url('fonts/PlayfairDisplay-Regular.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/PlayfairDisplay-Regular.woff') format('woff'), /* Modern Browsers */ url('fonts/PlayfairDisplay-Regular.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/PlayfairDisplay-Regular.svg#04b27ca05cc09e86c39c371295e381ad') format('svg'); /* Legacy iOS */ font-style: normal;font-weight:400;}@font-face {font-family: 'Lato';src: url('fonts/Lato-Bold.eot'); /* IE9 Compat Modes */src: url('fonts/Lato-Bold.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/Lato-Bold.woff') format('woff'), /* Modern Browsers */ url('fonts/Lato-Bold.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/Lato-Bold.svg#eea591db52cf6ebc8992abb7621b9256') format('svg'); /* Legacy iOS */ font-style: normal;font-weight:700;}@font-face {font-family: 'Lato';src: url('fonts/Lato-BoldItalic.eot'); /* IE9 Compat Modes */src: url('fonts/Lato-BoldItalic.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/Lato-BoldItalic.woff') format('woff'), /* Modern Browsers */ url('fonts/Lato-BoldItalic.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/Lato-BoldItalic.svg#030f5996dac0d7b15fbd4081adedf95b') format('svg'); /* Legacy iOS */ font-style: italic;font-weight:700;}@font-face {font-family: 'Lato';src: url('fonts/Lato-Italic.eot'); /* IE9 Compat Modes */src: url('fonts/Lato-Italic.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/Lato-Italic.woff') format('woff'), /* Modern Browsers */ url('fonts/Lato-Italic.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/Lato-Italic.svg#51ac4eb0bc1817d276ca824c5353e08f') format('svg'); /* Legacy iOS */ font-style: italic;font-weight:400;}@font-face {font-family: 'Lato';src: url('fonts/Lato-Regular.eot'); /* IE9 Compat Modes */src: url('fonts/Lato-Regular.eot?#iefix') format('embedded-opentype'), /* IE6-IE8 */ url('fonts/Lato-Regular.woff') format('woff'), /* Modern Browsers */ url('fonts/Lato-Regular.ttf')format('truetype'), /* Safari, Android, iOS */ url('fonts/Lato-Regular.svg#48e70b8825d557df57af3e4f7d4c31be') format('svg'); /* Legacy iOS */ font-style: normal;font-weight:400;}</style>").appendTo( "head" );
}