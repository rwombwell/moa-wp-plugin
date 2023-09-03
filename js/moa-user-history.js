/****************
 * JS Ajax code to capture User History information, ie to track the website pages a user visits in their session
 * and then display this on the admin user edit page
 */
jQuery(document).ready(function($) {
    $('a').on('click', function(e) {
        var href = $(this).attr('href');

        $.ajax({
            type: 'POST',
            url: ajax_object.ajax_url,
            data: {
                action: 'capture_link_click',
                url: href
            },
            success: function(response) {
                // handle response if needed
            }
        });
    });
});
