jQuery('.pr-reset').click(function(e) {
  if(confirm("Delete all saved options and restore default values?") ) {
    var term_id = jQuery(this).data('term');

    var data = {
        'term_id'    : term_id,
        'action'     : 'reset_editorial_project'
    };

    jQuery.post(ajaxurl, data, function(response) {
      if( response ) {
        alert('All option restored to default');
      }
    });
  }
});
