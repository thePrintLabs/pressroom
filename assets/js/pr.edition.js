(function($) {
  $("#publish_edition").click(function(e) {
    e.preventDefault();

    var packager_type = $("#pr_packager_type option:selected").val();
    var edition_id = $('#post_ID').val();
    var post = $('#post').serialize();
    var data = post + '&action=publishing&edition_id='+edition_id;

    var url = ajaxurl + '?action=render_console&edition_id=' + edition_id + '&pr_no_theme=true&packager_type='+packager_type;
    window.open(url,"_blank");

    jQuery.ajax({
      'url'           : ajaxurl,
      'data'          : data,
      'type'          : 'POST',
      'dataType'      : 'json',
      success : function(response) {
        if(response.success) {
        }
      }
    });
  });

  $("#preview_edition").click(function(e) {
    e.preventDefault();

    var packager_type = $("#pr_packager_type option:selected").val();
    var edition_id = $('#post_ID').val();
    var pr_core_uri = $('#pr_core_uri').val();
    var url = pr_core_uri + 'preview/reader.php?edition_id=' + edition_id + '&package_type='+packager_type;
    window.open(url,"_blank");
  });

}(jQuery));
