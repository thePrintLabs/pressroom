jQuery(function($) {
  // color picker init
  $('.pr-color-picker').wpColorPicker();
  if ($(".chosen-select").length) {
    $(".chosen-select").chosen();
  }
  // calendar init
  $('#_pr_date').datepicker({dateFormat : 'yy-mm-dd'});

  //repeater fields
  var prefix = $('#_pr_prefix_bundle_id');
  var subscription = $('#_pr_subscription_prefix');

  $( '#_pr_prefix_bundle_id').keyup(function() {
    $( '#_pr_single_edition_prefix, #_pr_subscription_free_prefix, #_pr_subscription_prefix, .pr_repeater input[type="text"]' ).trigger( "keyup" );
  });

  $('#_pr_single_edition_prefix, #_pr_subscription_prefix').keyup(function() {
    var autocompleted = $(this).next();
    autocompleted.html(prefix.val() + '.' + $(this).val() );
    $( '.pr_repeater input[type="text"], #_pr_subscription_free_prefix' ).trigger( "keyup" );
  });

  $('#_pr_subscription_free_prefix').keyup(function() {
    var autocompleted = $(this).next();
    autocompleted.html(prefix.val() + '.' + subscription.val() + '.' + $(this).val() );
  });

  $( ".form-table" ).delegate('.pr_repeater input[type="text"]','keyup',function() {
    var autocompleted = $(this).parent().find('.repeater-completer');
    autocompleted.html(prefix.val() + '.' + subscription.val() + '.' + $(this).val() );
  });

  $( "#_pr_prefix_bundle_id" ).trigger( "keyup" );

  //add cloned field
  $( "#add-field" ).click(function(e) {
    e.preventDefault();

    var clone = $( "#pr_repeater" ).clone();
    var minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';
    var last_index = $( ".pr_repeater" ).last().data('index');

    clone.find('#add-field').attr('class','remove-field');
    clone.find('#add-field').attr('id','remove-field');
    clone.find('#remove-field').html(minus);

    var parent = $(".pr_repeater" ).parent();
    clone.data('index',parseInt( last_index ) + 1 );

    clone.find('input[type="text"]').val('');

    var name = clone.find('input[type="text"]').attr('name');
    var radioname = clone.find('input[type="radio"]').attr('name');

    clone.find('input[type="text"]').attr('name',name.replace('[0]', '[' + (parseInt(last_index ) +1)  + ']'));
    clone.find('input[type="radio"]').attr('name',radioname.replace('[0]', '[' + (parseInt(last_index ) +1)  + ']'));
    clone.find('.repeater-completer').html( prefix.val() + '.' + subscription.val() );

    clone.appendTo( parent );

  });

  $( ".form-table" ).delegate( ".remove-field", "click", function(e) {
    e.preventDefault();
    $(this).parent().remove();
  });

  $('#pressroom_metabox').removeClass('postbox');
  $('.tabbed').css('display','none');
  var className = $('.taxonomy-pr_editorial_project .tabbed').first().attr('class');
  if (className) {
    className = className.replace( 'tabbed', '').trim();
    $('.' + className).css('display', 'table-row');
  }
  $('.basic_metabox').css('display','table-row');
  $('.flatplan').css('display','table-row');
  $('.nav-tab').click(function(e) {
    e.preventDefault();
    $('.nav-tab').each(function(){
      $(this).removeClass('nav-tab-active');
    })
    var tab = $(this).data('tab');
    $(this).addClass('nav-tab-active');
    $('.tabbed').css('display','none');
    $('.'+ tab).css('display','table-row');

    if (tab == 'pad_meta') {
      $( 'input[name="_pr_pad_sgs_shelf_backgroundFillStyle"]' ).change();
    } else if (tab == 'phone_meta') {
      $( 'input[name="_pr_phone_sgs_shelf_backgroundFillStyle"]' ).change();
    }
  });

  if ($(this).find( 'input[name="_pr_pad_sgs_shelf_backgroundFillStyle"]' )) {
    $( 'input[name="_pr_pad_sgs_shelf_backgroundFillStyle"]' ).change(function() {
      if ($(this).is(':checked')) {
        var bi = $('label[for="_pr_pad_sgs_shelf_backgroundImage"]').closest('tr'),
        bc = $('label[for="_pr_pad_sgs_shelf_backgroundFillStyleColor"]').closest('tr'),
        gs = $('label[for="_pr_pad_sgs_shelf_backgroundFillGradientStart"]').closest('tr'),
        ge = $('label[for="_pr_pad_sgs_shelf_backgroundFillGradientStop"]').closest('tr');
        switch ($(this).val()) {
          default:
            bi.fadeIn();
            bc.hide();
            gs.hide();
            ge.hide();
            break;
          case 'Gradient':
            bi.hide();
            bc.hide();
            gs.fadeIn();
            ge.fadeIn();
            break;
          case 'Color':
            bi.hide();
            bc.fadeIn();
            gs.hide();
            ge.hide();
            break;
        }
      }
    });
  }

  if ($(this).find( 'input[name="_pr_phone_sgs_shelf_backgroundFillStyle"]' )) {
    $( 'input[name="_pr_phone_sgs_shelf_backgroundFillStyle"]' ).change(function() {
      if ($(this).is(':checked')) {
        var bi = $('label[for="_pr_phone_sgs_shelf_backgroundImage"]').closest('tr'),
        bc = $('label[for="_pr_phone_sgs_shelf_backgroundFillStyleColor"]').closest('tr'),
        gs = $('label[for="_pr_phone_sgs_shelf_backgroundFillGradientStart"]').closest('tr'),
        ge = $('label[for="_pr_phone_sgs_shelf_backgroundFillGradientStop"]').closest('tr');
        switch ($(this).val()) {
          default:
            bi.fadeIn();
            bc.hide();
            gs.hide();
            ge.hide();
            break;
          case 'Gradient':
            bi.hide();
            bc.hide();
            gs.fadeIn();
            ge.fadeIn();
            break;
          case 'Color':
            bi.hide();
            bc.fadeIn();
            gs.hide();
            ge.hide();
            break;
        }
      }
    });
  }

  //remove upload image
  $('.remove-file').click(function(e) {
    e.preventDefault();
    if (confirm("Do you really want to delete this file?")) {
      var field = $(this).data('field'),
      term_id = $(this).data('term'),
      attach_id = $(this).data('attachment'),
      current = $(this),
      data = {
        'field'      : field,
        'term_id'    : term_id,
        'attach_id'  : attach_id,
        'action'     : 'remove_upload_file'
      };

      $.post(ajaxurl, data, function(response) {
        if ( response ) {
          current.parent().find('img').css('display', 'none');
          current.css('display','none');
        } else {
          alert('Error. Please retry');
        }
      });
    }
  });

  $('#test-connection').click(function(e) {
    e.preventDefault();
    $('#connection-result').html('<div class="spinner"></div>');
    $('#connection-result').css('display','block');
    $("#connection-result .spinner").css('display','inline-block').css('float','none');

    var server    = $('input[name="_pr_ftp_server[0]"]').val();
    var port      = $('input[name="_pr_ftp_server[1]"]').val();
    var base      = $('input[name="_pr_ftp_destination_path"]').val();
    var user      = $('input[name="_pr_ftp_user"]').val();
    var password  = $('input[name="_pr_ftp_password"]').val();
    var protocol  = $('input[name="_pr_ftp_protocol"]:checked').val();

    var data = {
      'server'      : server,
      'port'        : port,
      'base'        : base,
      'user'        : user,
      'password'    : password,
      'protocol'    : protocol,
      'action'      : 'test_ftp_connection'
    };

    $.post(ajaxurl, data, function(response) {
      if( response ) {
        $('#connection-result').html(response.data.message);
        $('#connection-result').removeClass( 'connection-result-success connection-result-failure' );
        $('#connection-result').addClass('connection-result connection-result-'+response.data.class);
      }
    })
  });


  var override_web = $('#_pr_web_override_eproject');
  var override_hpub = $('#_pr_hpub_override_eproject');
  var override_adps = $('#_pr_adps_override_eproject');

  override_web.click(function(e) {
    checkOverride($(this), 'web_metabox');
  });

  override_hpub.click(function(e) {
    checkOverride($(this), 'hpub');
  });

  override_adps.click(function(e) {
    checkOverride($(this), 'adps_settings_metabox');
  });

  if(override_web.length) {
    checkOverride(override_web, 'web_metabox');
  }

  if(override_hpub.length) {
    checkOverride(override_hpub, 'hpub');
  }

  if(override_adps.length) {
    checkOverride(override_adps, 'adps_settings_metabox');
  }

  function checkOverride(element, metabox) {

    if(element.is(':checked')) {
      $( '#'+metabox + ' input').removeAttr('disabled');
      $( '#'+metabox + ' select').removeAttr('disabled');
      $( '#'+metabox + ' button').removeAttr('disabled');
      $( '#'+metabox+' h3, .'+metabox+' label').css('color','#222');
    }
    else {
      $( '#'+metabox+' input').attr('disabled','disabled');
      $( '#'+metabox+' select').attr('disabled','disabled');
      $( '#'+metabox + ' button').attr('disabled','disabled');
      $( '#'+metabox+' h3, .'+metabox+' label').css('color','#ddd');
      element.removeAttr('disabled');
      element.parent().parent().find('label').css('color','#222');
    }
  }

  checkTrasferProtocol( $('input[name="_pr_ftp_protocol"]:checked') ); //on document ready

  $('input[name="_pr_ftp_protocol"]').live("change",function(e) {
    checkTrasferProtocol($(this));
  });

  function checkTrasferProtocol(element) {
    var value = element.val();
    switch( value ) {
      case 'ftp':
      case 'sftp':
        $('.web_metabox input[type="text"], .web_metabox input[type="password"]').removeAttr('disabled');
        $('.web_metabox input[name="_pr_local_path"]').attr('disabled','disabled');
        $('#test-connection').removeAttr('disabled');
        break;
      case 'local':
        $('.web_metabox input[type="text"], .web_metabox input[type="password"]').attr('disabled','disabled');
        $('.web_metabox input[name="_pr_local_path"]').removeAttr('disabled');
        $('#test-connection').attr('disabled','disabled');
        break;
    }
  }
});
