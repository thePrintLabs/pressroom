(function($){

  var options = {
    bg: '#2f2f2f',
  	target: document.getElementById('pr-progressbar'),
  	id: 'pr-progressbar'
  };

  var nanobar = new Nanobar( options );

  var themes_loaded = false;
  $(".nav-tab").click(function(){

    $(".nav-tab").removeClass('nav-tab-active');
    jQuery(this).addClass('nav-tab-active');

    var tab = $(this).data('tab');
    switch(tab) {
      case 'remotes':
        if(themes_loaded == false) {
          nanobar.go( 10 );
          $.post(ajaxurl, {
            'action':'pr_get_remote_themes',
          }, function(response) {
            nanobar.go( 30 );
            if (response) {
              nanobar.go( 50 );
              jQuery(response).appendTo('#themes-container');
              themes_loaded = true;
              nanobar.go( 70 );
            } else {
              jQuery('<div>No themes founds</div>').appendTo('#themes-container');
            }
            $('#themes-installed').fadeOut('fast', function(){
              $('#themes-remote').fadeIn('fast');
            });
          }).done(function() {
            nanobar.go( 100 );
          });
        }
        else {
          $('#themes-installed').fadeOut('fast', function(){
            $('#themes-remote').fadeIn('fast');
          });
        }

        break;
      case 'installed':
        $('#themes-remote').fadeOut('fast', function() {
          $('#themes-installed').fadeIn('fast');
        });
        break;
      default:
        break;
    }
  });



  $("#pr-theme-upload").change(function(){ $("#pr-theme-form").submit();});
  $(".pr-theme-delete").click(function(){ if(confirm(pr.delete_confirm)){
    nanobar.go( 10 );
    $parent = $(this).parent().parent('.theme');
    nanobar.go( 30 );
    $.post(ajaxurl, {
      'action':'pr_delete_theme',
      'theme_id': $parent.data('name')
    }, function(response) {
      if (response.success) {
        nanobar.go( 60 );
        $counter = parseInt($('#pr-theme-count').text()) - 1;
        $('#pr-theme-count').text( $counter );
        $parent.fadeOut();
        nanobar.go( 100 );
      } else {
        alert(pr.delete_failed);
      }
    });
  }});

  $("#pr-flush-themes-cache").click(function(){
    $.post(ajaxurl, {
      'action':'pr_flush_themes_cache'
    }, function(response) {
      if (response.success) {
        document.location.href = pr.flush_redirect_url;
      } else {
        alert(pr.flush_failed);
      }
    });
  });

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

  $('.show-code').click(function(){
    var index = $(this).data('index');
    $('.discount-code-' + index).slideToggle();
  });

  $('.pr-dismiss-notice').click(function(){
    var index = $(this).data('index');
    console.log(index);
    $.post(ajaxurl, {
      'action':'pr_dismiss_notice',
      'id'    : index,
    }, function(response) {
      if (response) {
        $('.discount-container-' + index).remove();
      }
    });
  });

})(jQuery);
