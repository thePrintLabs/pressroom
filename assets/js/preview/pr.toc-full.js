function openToc(){
  	// e.preventDefault();
    $('body').toggleClass('overlay'); 
    return false;
};

$( document ).ready(function() {
	var fsStyle = '<style>.overlay{overflow:hidden}#toc{opacity:0;visibility:hidden;transition:opacity 0.5s,visibility 0 .5s, height 0 .5s;position:fixed;width:100%;height:0;top:0;left:0;background:#fff;z-index:5000;overflow-x:hidden;overflow-y:scroll;-webkit-overflow-scrolling:touch}#toc iframe{height:100%}.overlay #toc{opacity:1;visibility:visible;height:100%;transition:opacity .5s}';
    $('head').append(fsStyle);
});

$('#toc-frame').load(function(){
  $(this).contents().find("a").on('click', function(event) { openToc(); });
});