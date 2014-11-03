jQuery(function(){

  // color picker init
  jQuery('.tpl-color-picker').wpColorPicker();
  if(jQuery(".chosen-select").length)
    jQuery(".chosen-select").chosen();

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

});
