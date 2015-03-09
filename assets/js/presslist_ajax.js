(function($) {

list = {

    /**
     * Register our triggers
     *
     * We want to capture clicks on specific links, but also value change in
     * the pagination input field. The links contain all the information we
     * need concerning the wanted page number or ordering, so we'll just
     * parse the URL to extract these variables.
     *
     * The page number input is trickier: it has no URL so we have to find a
     * way around. We'll use the hidden inputs added in Pressroom_list::display()
     * to recover the ordering variables, and the default paged input added
     * automatically by WordPress.
     */
    init: function() {

        // This will have its utility when dealing with the page number input
        var timer;
        var delay = 500;

        // Pagination links, sortable link

        $('#pressroom_metabox .inside').delegate('.tablenav-pages .pagination-links a, .manage-column.sortable a, .manage-column.sorted a','click', function(e) {
            // We don't want to actually follow these links
            e.preventDefault();
            // Simple way: use the URL to extract our needed variables
            var query = this.search.substring( 1 );

            var data = {
                paged: list.__query( query, 'paged' ) || $('#presslist_paged').val(),
                order: list.__query( query, 'order' ) ||  $('#presslist_order').val(),
                orderby: list.__query( query, 'orderby' ) || $('#presslist_orderby').val(),
                edition_id: parseInt($('input[name=edition_id]').val()) || 'edition_id',
                post_per_page: $('#presslist_screen_per_page').val(),
            };
            list.update( data );

        });

        // Page number input
        $('input[name=paged]').on('keyup', function(e) {

            // If user hit enter, we don't want to submit the form
            // We don't preventDefault() for all keys because it would
            // also prevent to get the page number!
            if ( 13 == e.which )
                e.preventDefault();

            // This time we fetch the variables in inputs
            var data = {
                paged: parseInt( $('input[name=paged].current-page').val() ) || '1',
                order: $('#presslist_order').val() || 'asc',
                orderby: $('#presslist_orderby').val() || 'title',
                edition_id: parseInt($('input[name=edition_id]').val()) || 'edition_id'
            };

            // Now the timer comes to use: we wait half a second after
            // the user stopped typing to actually send the call. If
            // we don't, the keyup event will trigger instantly and
            // thus may cause duplicate calls before sending the intended
            // value
            window.clearTimeout( timer );
            timer = window.setTimeout(function() {
                list.update( data );
            }, delay);
        });
    },

    /** AJAX call
     *
     * Send the call and replace table parts with updated version!
     *
     * @param    object    data The data to pass through AJAX
     */
    update: function( data ) {
        $.ajax({
            // /wp-admin/admin-ajax.php
            url: ajaxurl,
            // Add action and nonce to our collected data
            data: $.extend(
                {
                    _ajax_presslist_nonce: $('#_ajax_presslist_nonce').val(),
                    action: '_ajax_fetch_presslist',
                },
                data
            ),
            // Handle the successful result
            success: function( response ) {
                // WP_List_Table::ajax_response() returns json
                var response = $.parseJSON( response );
                // Add the requested rows
                if ( response.rows.length )
                    $('.wp-list-table tbody').html( response.rows );
                // Update column headers for sorting
                if ( response.column_headers.length )
                    $('thead tr, tfoot tr').html( response.column_headers );
                // Update pagination for navigation
                if ( response.pagination.bottom.length )
                    $('.tablenav.top .tablenav-pages').html( response.pagination.top );
                if ( response.pagination.top.length )
                    $('.tablenav.bottom .tablenav-pages').html( response.pagination.bottom );

                $('#presslist_paged').val(data.paged);
                $('#presslist_order').val(data.order);
                $('#presslist_orderby').val(data.orderby);

            }
        });
    },

    __query: function( query, variable ) {

        var vars = query.split("&");
        for ( var i = 0; i <vars.length; i++ ) {
            var pair = vars[ i ].split("=");
            if ( pair[0] == variable )
                return pair[1];
        }
        return false;
    },
}

// Show time!
list.init();

$( ".wp-list-table tbody" ).delegate( ".presslist-status", "click", function(e) {

    e.preventDefault();
    el = jQuery(this);
    index = el.data('index');

    var data = {
        'id'      : index,
        'action'  : 'presslist'
    };

    jQuery.post(ajaxurl, data, function(response) {
        if( response ) {
            el.find('i').addClass('press-eye-off').removeClass('press-eye');
        }
        else {
            el.find('i').addClass('press-eye').removeClass('press-eye-off');;
        }

    });
});

$( ".wp-list-table tbody" ).delegate(".presslist-template",'change', function(e) {

    el = jQuery(this);
    index = el.find('option:selected').data('index');
    template = el.val();

    var data = {
        'template': template,
        'id': index,
        'action' : 'register_template'
    };

    jQuery.post(ajaxurl, data, function(response) {
        $('#'+ index).removeAttr('style');
    });

});

$('#doaction').on( "click", function(e) {

    e.preventDefault();
    var posts = new Array();
    var action_to_do = $('#pressroom_metabox .actions select option:selected').val();
    var el;
    $('input[name="linked_post"]:checked').each(function(){
        posts.push($(this).val());
    });

    var data = {
        'action' : 'bulk_presslist',
        'connected_posts': posts,
        'action_to_do': action_to_do
    };

    jQuery.post(ajaxurl, data, function(response) {
      $('input[name="linked_post"]:checked').each(function(){

          el = $('#r_'+$(this).val());
          if( response ) {
            el.find('i').removeClass('press-eye-off').addClass('press-eye');
          }
          else {
            el.find('i').removeClass('press-eye').addClass('press-eye-off');
          }
      });
    });
});

$( "#pressroom_metabox .inside" ).delegate(".number_element_input",'change', function(e) {

    e.preventDefault();

    var post_per_page = $(this).val();
    $('#presslist_screen_per_page').val(post_per_page);

    var query = window.location.search.substring( 1 );
    var data = {
        paged: 1,
        order: list.__query( query, 'order' ) ||  $('#presslist_order').val(),
        orderby: list.__query( query, 'orderby' ) || $('#presslist_orderby').val(),
        edition_id: parseInt($('input[name=edition_id]').val()) || 'edition_id',
        post_per_page: post_per_page
    };

    list.update( data );
});

})(jQuery);
