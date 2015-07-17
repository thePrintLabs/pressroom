(function($){

  var options = {
    bg: '#2f2f2f',
  	target: document.getElementById('pr-progressbar'),
  	id: 'pr-progressbar'
  };

  var nanobar = new Nanobar( options );

  var themes_loaded = false;
  $(".nav-tab").click(function() {

    $(".nav-tab").removeClass('nav-tab-active');
    jQuery(this).addClass('nav-tab-active');

    var tab = $(this).data('tab');
    switch(tab) {
      case 'remotes':
        if(themes_loaded == false) {
          nanobar.go( 10 );
          $.post(ajaxurl, {
            'action':'pr_get_remote_addons',
          }, function(response) {
            nanobar.go( 30 );
            if (response) {
              nanobar.go( 50 );
              jQuery(response).appendTo('#addons-container');
              themes_loaded = true;
              nanobar.go( 70 );
            } else {
              jQuery('<div>No themes founds</div>').appendTo('#addons-container');
            }
            $('#addons-installed').fadeOut('fast', function(){
              $('#addons-remote').fadeIn('fast');
            });
          }).done(function() {
            nanobar.go( 100 );
          });
        }
        else {
          $('#addons-installed').fadeOut('fast', function(){
            $('#addons-remote').fadeIn('fast');
          });
        }

        break;
      case 'installed':
        $('#addons-remote').fadeOut('fast', function() {
          $('#addons-installed').fadeIn('fast');
        });
        break;
      default:
        break;
    }
  });

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
