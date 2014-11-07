jQuery(function(){

  // Chosen init
  if(jQuery(".chosen-select").length)
    jQuery(".chosen-select").chosen();

    //Theme flush
    jQuery('#theme_refresh').on( "click", function(e) {
       e.preventDefault();
       var data = {
          'action' : 'refresh_cache_theme',
       };

       jQuery.post(ajaxurl, data, function(response) {
         alert('Flushed. No need to Save.');
       });
    });

});
