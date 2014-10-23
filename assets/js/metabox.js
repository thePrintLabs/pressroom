jQuery(function(){
  var prefix = jQuery('#_pr_prefix_bundle_id');
  var subscription = jQuery('#_pr_subscription_prefix');

  jQuery('.tpl-color-picker').wpColorPicker();
  if(jQuery(".chosen-select").length)
    jQuery(".chosen-select").chosen();

  jQuery('#_pr_prefix_bundle_id').keyup(function() {
    jQuery( "#_pr_single_edition_prefix, #_pr_subscription_prefix, .tpl_repeater input " ).trigger( "keyup" );
  });

  jQuery('#_pr_single_edition_prefix, #_pr_subscription_prefix').keyup(function() {
      var autocompleted = jQuery(this).next().css('padding', '5px');
      autocompleted.html(prefix.val() + '.' + jQuery(this).val() );
      jQuery( ".tpl_repeater input " ).trigger( "keyup" );
  });

  jQuery( ".form-table" ).delegate('.tpl_repeater input','keyup',function() {
      var autocompleted = jQuery(this).parent().find('.repeater-completer').css('padding', '0.5%');
      autocompleted.html(prefix.val() + '.' + subscription.val() + '.' + jQuery(this).val() );
  });

  jQuery( "#_pr_prefix_bundle_id" ).trigger( "keyup" );




  jQuery( "#add-field" ).click(function(e) {
    e.preventDefault();

    var last_index = jQuery( ".tpl_repeater" ).last().data('index');

    var clone = jQuery( "#tpl_repeater" ).clone();

    var minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';
    clone.find('#add-field').attr('class','remove-field');
    clone.find('#add-field').attr('id','remove-field');

    clone.find('#remove-field').html(minus);

    var parent = jQuery(".tpl_repeater" ).parent();
    clone.appendTo( parent );

    clone.find('input').val('');

    var last_cloned_index = jQuery( ".tpl_repeater" ).last();

    last_cloned_index.data('index',parseInt( last_index + 1));
    var name = clone.find('input').attr('name');
    clone.find('input').attr('name',name.replace('[0]', '[' + parseInt(last_index +1 ) + ']'));
    clone.find('.repeater-completer').html( prefix.val() + '.' + subscription.val() );


  });

  jQuery( ".form-table" ).delegate( ".remove-field", "click", function(e) {
    e.preventDefault();
    jQuery(this).parent().remove();
  });

});
