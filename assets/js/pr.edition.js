(function($) {
  $("#publish_edition").click(function(e) {
    e.preventDefault();
    var edition_id = $('#post_ID').val();
    var packager_type = $("#pr_packager_type option:selected").val();
    var url = ajaxurl + '?action=render_console&edition_id=' + edition_id + '&pr_no_theme=true&packager_type='+packager_type;
    validateTemplates(url, edition_id);

  });

  $("#preview_edition").click(function(e) {
    e.preventDefault();
    var edition_id = $('#post_ID').val();
    var packager_type = $("#pr_packager_type option:selected").val();
    var pr_core_uri = $('#pr_core_uri').val();
    //var url = pr_core_uri + 'preview/reader.php?edition_id=' + edition_id + '&pr_no_theme=true'+'&package_type='+packager_type;
    var url = ajaxurl + '?action=pr_preview&edition_id=' + edition_id + '&pr_no_theme=true'+'&package_type='+packager_type;
    validateTemplates(url, edition_id);
  });

  function validateTemplates( callBackUrl, edition_id ) {

    var post = $('#post').serialize();
    var data = post + '&action=publishing&edition_id='+edition_id;
    var packager_type = $("#pr_packager_type option:selected").val();
    jQuery.ajax({
      'url'           : ajaxurl,
      'data'          : data,
      'type'          : 'POST',
      'dataType'      : 'json',
      success : function(response) {
        if(response.success) {
          window.open( callBackUrl ,"_blank");
        }
        else {
          missing_templates = response.data.missing;

          for( i = 0; i < missing_templates.length; i++) {

            $('#' + missing_templates[i] ).css('background-color', 'rgba( 217, 83, 79, 0.3)');
          }
          alert('No layout assigned to one or more articles');
        }
      }
    });
  }

}(jQuery));
