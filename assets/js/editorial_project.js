jQuery( "#add-subscription" ).click(function(e) {
  e.preventDefault();

  var last_index = jQuery( ".tpl_repeater" ).last().data('index');

  var clone = jQuery( "#tpl_repeater" ).clone();

  var minus = '<img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAABgAAAAZCAYAAAArK+5dAAAABHNCSVQICAgIfAhkiAAAAAlwSFlzAAAO3AAADtwB+LUWtAAAABl0RVh0U29mdHdhcmUAd3d3Lmlua3NjYXBlLm9yZ5vuPBoAAAA4SURBVEiJY/z//z8DLQETTU0ftWDUgqFhASMDA4MHrS2gaVYe+nEw9C2gfSoarQ9GLRi1gPYWAADB5Ae3h3/zOwAAAABJRU5ErkJgggafaa68760bfc367f77f3e9abf6847fcd"/>';
  //clone.find('#add-subscription').removeAttr('id');
  clone.find('#add-subscription').attr('class','remove-subscription');
  clone.find('#add-subscription').attr('id','remove-subscription');

  clone.find('#remove-subscription').html(minus);

  var parent = jQuery(".tpl_repeater" ).parent('.form-table tbody');
  clone.appendTo( parent );

  clone.find('input').val('');

  var last_cloned_index = jQuery( ".tpl_repeater" ).last();

  last_cloned_index.data('index',parseInt( last_index + 1));

  clone.find('input').attr('name','term_meta[subscription_type][' + parseInt(last_index +1 ) + ']');


});

// jQuery( ".remove-subscription" ).click(function(e) {
//   e.preventDefault();
//   jQuery(this).parent().parent().remove();
// });

jQuery( ".form-table" ).delegate( ".remove-subscription", "click", function(e) {
  e.preventDefault();
  jQuery(this).parent().parent().remove();
});

jQuery(document).ready(function($){
    jQuery('.tpl-color-picker').wpColorPicker();
});
