function openToc(){
  $( "#toc" ).slideToggle('slow', function(){ $("#toc").is(":hidden") ? $('#fire-toc').removeClass('active') : $('#fire-toc').addClass('active') });
};
