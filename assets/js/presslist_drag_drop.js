jQuery(document).ready(function() {

    var fixHelperModified = function(e, tr) {
        var originals = tr.children(),
            helper = tr.clone();
        helper.children().each(function(index) {
          jQuery(this).width(originals.eq(index).width())
        });
        return helper;
    };

    var tableSelector = '#pressroom_metabox .wp-list-table tbody';
    jQuery(tableSelector).sortable({
        helper: fixHelperModified,
        stop : function(event, ui) {
            var order = jQuery(this).sortable('toArray').toString();

            jQuery.post(ajaxurl, {
                                action:'update-custom-post-order',
                                event: 'sort-posts',
                                order: order,
                                postPerPage: jQuery('#presslist_screen_per_page').val(),
                                currentPage: jQuery('#presslist_paged').val()
                                },
                                function(response) {

                                });
        }
    });
});
