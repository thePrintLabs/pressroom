jQuery(function(){

  // Chosen init
  if(jQuery(".chosen-select").length)
    jQuery(".chosen-select").chosen();

    //tagsInput init
    jQuery('#pr_custom_post_type').tagsInput({
      'placeholderColor': '#2ea2cc',
      'defaultText'     : 'Add'
    });
});
