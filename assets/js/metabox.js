jQuery( "#add-field" ).click(function(e) {
  e.preventDefault();

  var last_index = jQuery( ".tpl_repeater" ).last().data('index');

  var clone = jQuery( "#tpl_repeater" ).clone();

  var minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAARBAMAAAA1VnEDAAAAA3NCSVQICAjb4U/gAAAACXBIWXMAAAF3AAABdwE7iaVvAAAAGXRFWHRTb2Z0d2FyZQB3d3cuaW5rc2NhcGUub3Jnm+48GgAAAA9QTFRF////AAAAAAAAAAAAAAAAUTtq8AAAAAR0Uk5TADVCUDgXPZIAAAAaSURBVAhbY2CgKVA2BgIjCJvRBQwEMGVoBQCxXAPsAZwyyQAAAABJRU5ErkJggg60a8c977b5851eb7a101a51c617fd8ad"/>';
  //clone.find('#add-field').removeAttr('id');
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


});

jQuery( ".form-table" ).delegate( ".remove-field", "click", function(e) {
  e.preventDefault();
  jQuery(this).parent().remove();
});

jQuery(function(){
    jQuery('.tpl-color-picker').wpColorPicker();
    jQuery(".chosen-select").chosen();
});
