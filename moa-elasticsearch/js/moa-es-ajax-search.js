/*********************
 * MOA Elasticsearch Ajax script, has to be locatised to set up the object ajax_search_params.ajax_url
 * This calls the PHP Ajax hook "moa-es-ajax-search" with a $_POST[] item query=<search string>
 *********************/
jQuery(document).ready(function($) {
    $('#ajax-searchform').on('submit', function(e) {
        e.preventDefault();
        
        var searchQuery = $('#ajax-s').val();           // get the contents of the search box
        
        $.ajax({
            type: 'POST',
            url: ajax_search_params.ajax_url,           // resolves to https://macwester.org.uk/wp-admin/admin-ajax.php
            data: {
                action: 'moa_es_ajax_search',           // this is the WP Ajax hook fucntion to answer the search
                query: searchQuery                      // defines the element "query" in the Ajax $_POST[] array 
            },
            success: function(response) {
                $('#ajax-search-results').html(response);
                return false;
            }
        });
    });
});

/*********************
 * Script to handle MOA Popup Ajax Search function, has to be locatised to set up the object ajax_search_params.ajax_url
 * This calls the PHP Ajax hook "moa-es-ajax-search" with a $_POST[] item query=<search string>
 * TO DO - map this current ELasticSearch functions
 *********************/
// file named custom-search.js handles the popup

jQuery(document).ready(function($) {
    $("#search-btn").on("click", function(e) {
        e.preventDefault();
        $("#search-popup").fadeIn();
    });

    $("#search-popup-close").on("click", function(e) {
        e.preventDefault();
        $("#search-popup").fadeOut();
    });

    $("#search-input").on("keyup", function() {
        var query = $(this).val();

        $.post(ajax_object.ajax_url, {
            action: "custom_search",
            query: query
        }, function(data) {
            $("#search-results").empty();
            $.each(data.data, function(index, item) {
                $("#search-results").append("<p>" + item._source.field_name + "</p>"); // Change 'field_name' to your specific field name
            });
        });
    });
});
