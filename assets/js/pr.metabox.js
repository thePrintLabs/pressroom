jQuery(function(){
  // color picker init
  jQuery('.pr-color-picker').wpColorPicker();
  if(jQuery(".chosen-select").length)
    jQuery(".chosen-select").chosen();

    // calendar init
    jQuery('#_pr_date').datepicker({
      dateFormat : 'yy-mm-dd'
    });

    //repeater fields
    var prefix = jQuery('#_pr_prefix_bundle_id');
    var subscription = jQuery('#_pr_subscription_prefix');

    jQuery( '#_pr_prefix_bundle_id').keyup(function() {
      jQuery( '#_pr_single_edition_prefix, #_pr_subscription_prefix, .pr_repeater input[type="text"]' ).trigger( "keyup" );
    });

    jQuery('#_pr_single_edition_prefix, #_pr_subscription_prefix').keyup(function() {
      var autocompleted = jQuery(this).next();
      autocompleted.html(prefix.val() + '.' + jQuery(this).val() );
      jQuery( '.pr_repeater input[type="text"]' ).trigger( "keyup" );
    });

    jQuery( ".form-table" ).delegate('.pr_repeater input[type="text"]','keyup',function() {
      var autocompleted = jQuery(this).parent().find('.repeater-completer');
      autocompleted.html(prefix.val() + '.' + subscription.val() + '.' + jQuery(this).val() );
    });

    jQuery( "#_pr_prefix_bundle_id" ).trigger( "keyup" );



    //add cloned field
    jQuery( "#add-field" ).click(function(e) {
      e.preventDefault();


      var clone = jQuery( "#pr_repeater" ).clone();
      var minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';
      var last_index = jQuery( ".pr_repeater" ).last().data('index');

      clone.find('#add-field').attr('class','remove-field');
      clone.find('#add-field').attr('id','remove-field');
      clone.find('#remove-field').html(minus);

      var parent = jQuery(".pr_repeater" ).parent();
      clone.data('index',parseInt( last_index ) + 1 );

      clone.find('input[type="text"]').val('');

      var name = clone.find('input[type="text"]').attr('name');
      var radioname = clone.find('input[type="radio"]').attr('name');

      clone.find('input[type="text"]').attr('name',name.replace('[0]', '[' + (parseInt(last_index ) +1)  + ']'));
      clone.find('input[type="radio"]').attr('name',radioname.replace('[0]', '[' + (parseInt(last_index ) +1)  + ']'));
      clone.find('.repeater-completer').html( prefix.val() + '.' + subscription.val() );

      clone.appendTo( parent );

    });

    jQuery( ".form-table" ).delegate( ".remove-field", "click", function(e) {
      e.preventDefault();
      jQuery(this).parent().remove();
    });

    jQuery('#pressroom_metabox').removeClass('postbox');
    // jQuery('.tabbed').css('display','none');
    var className = jQuery('.taxonomy-pr_editorial_project .tabbed').first().attr('class');
    if(className) {
      className = className.replace( 'tabbed', '').trim();
      jQuery('.' + className).css('display', 'table-row');
    }

    jQuery('.basic_metabox').css('display','table-row');

    jQuery('.flatplan').css('display','table-row');


    jQuery('.nav-tab').click(function(e) {
      e.preventDefault();
      jQuery('.nav-tab').each(function(){
        jQuery(this).removeClass('nav-tab-active');
      })
      var tab = jQuery(this).data('tab');
      jQuery(this).addClass('nav-tab-active');
      jQuery('.tabbed').css('display','none');
      jQuery('.'+ tab).css('display','table-row');

    });

    //remove upload image
    jQuery('.remove-file').click(function(e) {
      e.preventDefault();
      if(confirm("Do you really want to delete this file?") ) {

        var field = jQuery(this).data('field');
        var term_id = jQuery(this).data('term');
        var attach_id = jQuery(this).data('attachment');
        var current = jQuery(this);

        var data = {
          'field'      : field,
          'term_id'    : term_id,
          'attach_id'  : attach_id,
          'action'     : 'remove_upload_file'
        };

        jQuery.post(ajaxurl, data, function(response) {
          if( response ) {
            current.parent().find('img').css('display', 'none');
            current.css('display','none');
          }
          else {
            alert('Error. Please retry');
          }
        });
      }

    });

    jQuery('#test-connection').click(function(e) {
      e.preventDefault();
      jQuery('#connection-result').html('<div class="spinner"></div>');
      jQuery('#connection-result').css('display','block');
      jQuery("#connection-result .spinner").css('display','inline-block').css('float','none');

      var server    = jQuery('input[name="_pr_ftp_server[0]"]').val();
      var port      = jQuery('input[name="_pr_ftp_server[1]"]').val();
      var base      = jQuery('input[name="_pr_ftp_destination_path"]').val();
      var user      = jQuery('input[name="_pr_ftp_user"]').val();
      var password  = jQuery('input[name="_pr_ftp_password"]').val();
      var protocol  = jQuery('input[name="_pr_ftp_protocol"]:checked').val();

      var data = {
        'server'      : server,
        'port'        : port,
        'base'        : base,
        'user'        : user,
        'password'    : password,
        'protocol'    : protocol,
        'action'      : 'test_ftp_connection'
      };

      jQuery.post(ajaxurl, data, function(response) {
        if( response ) {
          jQuery('#connection-result').html(response.data.message);
          jQuery('#connection-result').removeClass( 'connection-result-success connection-result-failure' );
          jQuery('#connection-result').addClass('connection-result connection-result-'+response.data.class);
        }
      })
    });


    var override_web = jQuery('#_pr_web_override_eproject');
    var override_hpub = jQuery('#_pr_hpub_override_eproject');
    var override_adps = jQuery('#_pr_adps_override_eproject');

    override_web.click(function(e) {
      checkOverride(jQuery(this), 'web_metabox');
    });

    override_hpub.click(function(e) {
      checkOverride(jQuery(this), 'hpub');
    });

    override_adps.click(function(e) {
      checkOverride(jQuery(this), 'adps_settings_metabox');
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
        jQuery( '#'+metabox + ' input').removeAttr('disabled');
        jQuery( '#'+metabox + ' select').removeAttr('disabled');
        jQuery( '#'+metabox + ' button').removeAttr('disabled');
        jQuery( '#'+metabox+' h3, .'+metabox+' label').css('color','#222');
      }
      else {
        jQuery( '#'+metabox+' input').attr('disabled','disabled');
        jQuery( '#'+metabox+' select').attr('disabled','disabled');
        jQuery( '#'+metabox + ' button').attr('disabled','disabled');
        jQuery( '#'+metabox+' h3, .'+metabox+' label').css('color','#ddd');
        element.removeAttr('disabled');
        element.parent().parent().find('label').css('color','#222');
      }
    }

    checkTrasferProtocol( jQuery('input[name="_pr_ftp_protocol"]:checked') ); //on document ready

    jQuery('input[name="_pr_ftp_protocol"]').live("change",function(e) {
      checkTrasferProtocol(jQuery(this));
    });

    function checkTrasferProtocol(element) {
      var value = element.val();
      switch( value ) {
        case 'ftp':
        case 'sftp':
          jQuery('.web_metabox input[type="text"], .web_metabox input[type="password"]').removeAttr('disabled');
          jQuery('.web_metabox input[name="_pr_local_path"]').attr('disabled','disabled');
          jQuery('#test-connection').removeAttr('disabled');
          break;
        case 'local':
          jQuery('.web_metabox input[type="text"], .web_metabox input[type="password"]').attr('disabled','disabled');
          jQuery('.web_metabox input[name="_pr_local_path"]').removeAttr('disabled');
          jQuery('#test-connection').attr('disabled','disabled');
          break;
      }
    }
  });
