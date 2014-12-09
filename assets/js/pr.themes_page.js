(function($){
  $("#pr-theme-upload").change(function(){ $("#pr-theme-form").submit();});
  $(".pr-theme-delete").click(function(){ if(confirm(pr.delete_confirm)){
    $parent = $(this).parent().parent('.theme');
    $.post(ajaxurl, {
      'action':'pr_delete_theme',
      'theme_id': $parent.data('name')
    }, function(response) {
      if (response.success) {
        $counter = parseInt($('#pr-theme-count').text()) - 1;
        $('#pr-theme-count').text( $counter );
        $parent.fadeOut();
      } else {
        alert(pr.delete_failed);
      }
    });
  }});

  $("#pr-theme-add").on('click', function(e){
    e.stopPropagation();
    e.preventDefault();
    $("#pr-theme-upload").click();
  }).on('dragenter', function(e) {
    e.stopPropagation();
    e.preventDefault();
    //$('.add-new-theme').css('background-color', '#0074A2');
  }).on('dragover', function(e) {
    e.stopPropagation();
    e.preventDefault();
  }).on('drop', function(e) {
    //$('.add-new-theme').css('background-color', '#0074A2');
    e.preventDefault();
    var files = e.originalEvent.target.files || e.originalEvent.dataTransfer.files;
    uploadTheme(files[0]);
  });

  $(document).on('dragenter', function(e) {
    e.stopPropagation();
    e.preventDefault();
  }).on('dragover', function(e) {
    e.stopPropagation();
    e.preventDefault();
    //$('.add-new-theme').css('background-color', '#0074A2');
  }).on('drop', function(e) {
    e.stopPropagation();
    e.preventDefault();
  });

  function uploadTheme(file) {
    var formData = new FormData();
    formData.append('action', 'pr_upload_theme');
    formData.append('pr-theme-upload', file);

    $.ajax({
      url: ajaxurl,
      type: "POST",
      data: formData,
      cache: false,
      processData: false,
      contentType: false,
      success: function(data) {
        if( data.success ) {
          document.location.reload();
        } else {
          alert( pr.theme_upload_error );
        }
      }
    });
  }
})(jQuery);
