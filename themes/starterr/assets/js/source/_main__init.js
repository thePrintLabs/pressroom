var checkTitle = document.getElementsByClassName('cover__title--resize')[0];

fluidvids.init({
	selector: ['iframe', 'object'], 
	players: ['www.youtube.com', 'player.vimeo.com']
});

var mql = window.matchMedia("(min-width: 500px)");

var ua = window.navigator.userAgent;

var str = "BakerFramework";
var n = ua.search(str);

if (n < 0) {
	WebFontConfig = {
	google: { families: [ 'Lato:400,700,400italic,700italic:latin', 'Playfair+Display:400,700,400italic,700italic:latin' ] },
	active: function() {
		if(mql.matches) {
			if (typeof(checkTitle) != 'undefined' && checkTitle != null) {
				textFit(checkTitle, {minFontSize:10, maxFontSize: 150, reProcess: true});
			}
		}
	}
	};
	(function() {
		var wf = document.createElement('script');
		wf.src = ('https:' == document.location.protocol ? 'https' : 'http') +
		  '://ajax.googleapis.com/ajax/libs/webfont/1/webfont.js';
		wf.type = 'text/javascript';
		wf.async = 'true';
		var s = document.getElementsByTagName('script')[0];
		s.parentNode.insertBefore(wf, s);
	})();
} else {
	if(mql.matches) {
		if (typeof(checkTitle) != 'undefined' && checkTitle != null) {
			textFit(checkTitle, {minFontSize:10, maxFontSize: 150, reProcess: true});
		}
	}
}

mql.addListener(function(m) {
	if(m.matches){
		if (typeof(checkTitle) != 'undefined' && checkTitle != null) {
			textFit(checkTitle, {minFontSize:10, maxFontSize: 150, reProcess: true});
		}		
	} else {
		$('.textFitted').removeAttr("style");
	}
});