(function($) {

  var option = {
    format: 'Y-m-d H:i:s',
    lang: 'en',
    scrollMonth: true,
    maxDate: moment().add(0, 'days').format('YYYY/MM/DD'),
    yearStart: moment().format('YYYY'),
    yearEnd: moment().add(14, 'days').format('YYYY'),
  }
  // calendar init
  jQuery('#log_start_date').datetimepicker(option);
  jQuery('#log_end_date').datetimepicker(option);
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

        $('.pressroom_page_pressroom-logs').delegate('.tablenav-pages .pagination-links a, .manage-column.sortable a, .manage-column.sorted a','click', function(e) {
            // We don't want to actually follow these links
            e.preventDefault();
            // Simple way: use the URL to extract our needed variables
            var query = this.search.substring( 1 );

            var data = {
                paged: list.__query( query, 'paged' ) || $('#logslist_paged').val(),
                order: list.__query( query, 'order' ) ||  $('#logslist_order').val(),
                orderby: list.__query( query, 'orderby' ) || $('#logslist_orderby').val(),
                post_per_page: $('#logslist_screen_per_page').val(),
                start_date : $('#log_start_date').val(),
                end_date : $('#log_end_date').val(),
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
                order: $('#logslist_order').val() || 'asc',
                orderby: $('#logslist_orderby').val() || 'log_date',
                start_date : $('#log_start_date').val(),
                end_date : $('#log_end_date').val(),
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

        // Page number input
        $('#log-filter').on('click', function(e) {
            // If user hit enter, we don't want to submit the form
            // We don't preventDefault() for all keys because it would
            // also prevent to get the page number!
            if ( 13 == e.which )
                e.preventDefault();

            // This time we fetch the variables in inputs
            var data = {
                paged: '1',
                order: $('#logslist_order').val() || 'asc',
                orderby: $('#logslist_orderby').val() || 'log_date',
                start_date : $('#log_start_date').val(),
                end_date : $('#log_end_date').val(),
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
                    _ajax_logslist_nonce: $('#_ajax_logslist_nonce').val(),
                    action: '_ajax_fetch_logslist',
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
                    $('.wp-list-table thead tr, .wp-list-table tfoot tr').html( response.column_headers );
                // Update pagination for navigation
                if ( response.pagination.bottom.length )
                    $('.tablenav.top .tablenav-pages').html( response.pagination.top );
                if ( response.pagination.top.length )
                    $('.tablenav.bottom .tablenav-pages').html( response.pagination.bottom );

                $('#logslist_paged').val(data.paged);
                $('#logslist_order').val(data.order);
                $('#logslist_orderby').val(data.orderby);

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

$( ".pressroom_page_pressroom-logs" ).delegate(".number_element_input",'change', function(e) {

    e.preventDefault();
    var post_per_page = $(this).val();
    $('#logslist_screen_per_page').val(post_per_page);

    var query = window.location.search.substring( 1 );
    var data = {
        paged: 1,
        order: list.__query( query, 'order' ) ||  $('#logslist_order').val(),
        orderby: list.__query( query, 'orderby' ) || $('#logslist_orderby').val(),
        post_per_page: post_per_page,
        start_date : $('#log_start_date').val(),
        end_date : $('#log_end_date').val(),
    };

    list.update( data );
});



})(jQuery);
