<?php 


/**********
*  ELACTICSEARCH SEARCH SHORTCODE function to output the search form. This form will use AJAX to fetch and display the results
*  use [ajax_search_form] in your posts or pages to display the AJAX search form
***********/
add_shortcode('moa-ajax-elasticsearch-form', 'ajax_search_form_shortcode');
function ajax_search_form_shortcode() {


   	// add the jQuery plugin script aand css that adds checkboxes to the multiple select
	wp_enqueue_script( 'jquery-multiple-plugin-js'   , plugins_url( '../js/jquery.multiselect.js', __FILE__ ));
    wp_enqueue_style ( 'jquery-multiple-plugin-style', plugins_url( '../css/jquery.multiselect.css' , __FILE__ ));
    wp_enqueue_style ( 'moa-shortcodes-style', plugins_url( '../css/moa-shortcodes.css' , __FILE__ ));
	
    // The search needs to know which website page we are on so the search can be auto-filtered, this gets the page info.
    // This is then stuffed into the JS script with the Ajax call so the Ajax Search Callback handler can filter the search
    $page_info = array();
    if (is_page()) {
        $page_info['type'] = 'page';
        $page_info['id'] = get_the_ID();
        $page_info['slug'] = get_post_field('post_name', get_post());
    } elseif (is_single()) {
        $page_info['type'] = 'post';
        $page_info['id'] = get_the_ID();
        $page_info['slug'] = get_post_field('post_name', get_post());
    }

    $indexes = [];
    if ($page_info['slug'] === "es-search-form" ){
        array_push( $indexes, "moa_journal_index");
    
    }

    // now prepare the output for the shortcode
    ob_start();
    ?>
    <div id="search-popup" style="display:none;">
        <span id="search-popup-close" title="Close">X</span>
        Elasticsearch Ajax Search Form, for Page: <?php echo  $page_info['slug']?>
        <form role="search" method="get" id="ajax-searchform" action="#">
            <label for="ajax-s">Search for:</label>
            <input type="text" value="" name="s" id="ajax-s" placeholder="Search word ..." />
            <div class="ajax-index-select-container">
                <label for="ajax-index-select">Search in:</label>
                <select name="ajax-index-select" id="ajax-index-select" multiple class="jqmsLoaded ms-list-1">
                    <option value="all" selected>All content</option>
                    <option value="moa_journals">MOA journals</option>
                    <option value="moa_tech_articles">MOA Tech Articles</option>
                    <option value="moa_posts">Members Posts</option>
                    <option value="moa_forums">Forum Topics</option>
                </select>
            </div>
            <input type="submit" id="ajax-searchsubmit" value="<?php esc_attr_e('Search'); ?>" />
            <input type="hidden" id="ajax-indexes-wanted" value="" />
            <span id="loading-spinner" style="display:none;">
                <img src="/images/preloader-trans.gif" style="height:50px;" alt="Loading...">
            </span>
        </form>
        <div id="moa-es-ajax-search-results"></div>
    </div>

    <script>
    jQuery(document).ready(function($) {
    
        ///////////////////////////////////////////////// 
        // SHOW/HIDE POPUP SEARCH FORM - Popup the Ajax Search Form from Menu item click, 
        // assumes Custom Link added to menu, URL="#", Classname="moa-search-menu-item"
        $(".moa-search-menu-item").on("click", function(e) { // Use the appropriate ID or class for the menu item
            e.preventDefault();
            e.stopPropagation(); // Prevent this click from being propagated up
            $("#search-popup").fadeIn();
        });
        
        // Close the popup Search Form 
        $("#search-popup-close").on("click", function(e) {
            e.preventDefault();
            $("#search-popup").fadeOut();
        });

        // Close the popup when clicking outside of it
        /* $(document).on("click", function(e) {
            if (!$(e.target).closest("#search-popup").length && !$(e.target).is("#search-btn")) {
                $("#search-popup").fadeOut();
            }
        }); */

        ///////////////////////////////////////////////
        // MULTIPLE SELECT JQUERY
        // jQuery to initialise the multiselect control
        $("#ajax-index-select").multiselect();
        
        var selectedvals = [];
        $("#ajax-index-select").change(function(e) {      // jQuery to process the multiselect change events
            //debugger;
            e.preventDefault();
            e.stopPropagation(); // Prevent this click from being propagated up
            
            var options = $("#ajax-index-select option");
            var allWanted = false;
            for (i = 0; i < options.length; ++i) {
                if ( options[i]. value == "all" && options[i].selected )
                allWanted = true;
                break;
            }
            var indexArr = [];
            var i;
            for (i = 1; i < options.length; ++i) {
                if ( options[i].selected  || allWanted  ) {
                    indexArr.push( options[i].value );
                }
            }
            $("#ajax-indexes-wanted").val( indexArr );	

        });

        //////////////////////////////////////////////////////
        // EXECUTE AJAX SEARCH  - fires the AJAX Search when button clicked
        // note locatisation by using admin_url("admin-ajax.php") to replace ajax url
        $('#ajax-searchform').on('submit', function(e) {
            e.preventDefault();
            
            $("#loading-spinner").css("display","inline"); // Show loading icon
            $("#moa-es-ajax-search-results").empty();

            var searchQuery = $('#ajax-s').val();           // get the contents of the search box
            var indexWanetd = $('#ajax-indexes-wanted').val();           // holds comma delimited list of indexes to saerch
            $.ajax({
                type: 'POST',
                url: '<?php echo admin_url('admin-ajax.php')?>',    // ajax_search_params.ajax_url, // resolves to https://macwester.org.uk/wp-admin/admin-ajax.php
                data: {
                    action: 'ajax_search_callback',         // this is the WP Ajax hook fucntion to answer the search
                    query: searchQuery,                     // defines the element "query" in the Ajax $_POST[] array 
                    indexes: <?php echo json_encode($indexes) ?>,      // Page type (e.g., 'page', 'post', etc.)
                    page_id: '<?php echo get_the_ID() ?>',                     // json array of indexes to search on
                    page_slug: '<?php echo get_post_field('post_name', get_post()) ?>',       // Page slug
                },
                success: function(response) {
                    $("#loading-spinner").css("display","none"); // Hide loading icon
                    $("#moa-es-ajax-search-results").html(response);    // ElasticSearch response includes "hits" object which the PHP calling code extracts & formats
                    return false;
                }
            });
        });
    
    });
    </script>
    <style>
        /* Style the mag glass in the menu mar search item, note the icon we're using is black on white, so have to invert it*/
        .moa-search-menu-item img {
            filter: invert(1);
            vertical-align: middle;
            margin-right: 5px;
        }
        /* Ajax Search - POPUP WINDOW   */
        #search-popup {
            display: none;
            position: relative;
            top: 0px;
            left: 0;
            width: 100%;
            border: 1px solid darkgray;
            background-color: #fff;
            box-shadow: 3px 3px 7px #aea8a8;
            padding: 20px;
            z-index: 1;
        }
        #search-popup #search-popup-close {
            position: absolute;
            top: 2px;
            right: 2px;
            cursor: pointer;
            font-weight: 900;
            background: var(--moa-blue);
            color: white;
            padding: 0 7px;
        }
        #search-popup #search-popup-close:hover {
            background: darkred;
        }
        #search-popup #ajax-s{
            height: 30px;
            width: 300px;
            max-width: 80%;
            padding: 0 5px;
        }
        /* SEARCH  CONTROLS  */
        #search-popup #ajax-searchform{
            margin-bottom: 15px;
        }
        #search-popup .ajax-index-select-container{
            display: inline;
            position: relative;
            margin: 20px 0px 0px 0px;
            height: 50px;
        }
        #search-popup .ajax-index-select{
            height: 30px;
            line-height: 30px;
            padding: 5px 5px;
            background: #f2f2f2;
        }
        #search-popup #ajax-searchsubmit{
            height: 30px;
            padding: 0 10px;
            margin: 0px;
        }
        /* RESULTS - Style for the highlighted words in the text*/
        #moa-es-ajax-search-results em{
            font-style: unset;
            background: yellowgreen;
            font-weight: 800;    
        }
         #moa-es-ajax-search-results .es-results-container{
            background: lightgrey;
            padding: 2px;
        }
        #moa-es-ajax-search-results .es-results-container h4{
            margin-top:0px;
            padding:3px;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block{
            margin: 10px;
            padding:10px;
            background: white;
            border-radius: 5px;
            min-height: 160px;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block p{
            /* container for each row of the result block */
            border-top: solid 1px darkgray;
            /*background: linear-gradient(to bottom, #eee 0%, #e9e9e9 50%, #FFFFFF 100%);*/
        }
       #moa-es-ajax-search-results .es-results-container .es-result-block .cover-block{
            position: relative;
            width: 100px;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block .cover-title{
            position: absolute;
            top: 0;
            z-index: 1;
            font-weight: 700;
            color: white;
            background: var(--moa-blue);
            width: 94%;
            padding: 2px 0px;
            /* left: 2%; */
            text-align: center;
            line-height: 17px;
            font-size: 13px;
            margin: 3%;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block .cover-img{
            width: 100%; 
            heigh: auto;
            float: left; 
            border: 2px solid var( --moa-blue ); 
            margin-right: 10px;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block .result-toggle-text{
            /* style  to force view of each result blockto a single line, (a jQuery fn on button click switches the height="auto" to see full block */
            height: 24px;
            overflow: hidden; 
            position: relative;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block .result-toggle-text button{
            font-size: 20px;
            line-height: 20px;
            font-weight: 900;
            margin-right: 5px;
            border: none;
            background: var(--moa-blue);
            color: white;
            border-radius: unset;
        }
        #moa-es-ajax-search-results .es-results-container .es-result-block .result-toggle-text .result-pdf-link{
            font-size: 1em;
            font-weight: 500;
            float: right;
            position: absolute;
            right: 0;
        }
        button:focus {
            outline: unset;
        }
    </style>
   
    <?php
    return ob_get_clean();
}

/*************
* Enqueue the necessary JavaScript that handles the AJAX request:
*************/
/* not needed, script and style loading done inline 
function ajax_search_scripts() {
    
    //wp_enqueue_script('ajax-search', get_template_directory_uri() . '/js/ajax-search.js', array('jquery'), null, true);
    wp_enqueue_script( 'moa-es-ajax-search' , plugins_url( '../moa-elasticsearch/js/moa-es-ajax-search.js', __FILE__ ));
    wp_localize_script('moa-es-ajax-search', 'ajax_search_params', array('ajax_url' => admin_url('admin-ajax.php')));

}
add_action('wp_enqueue_scripts', 'ajax_search_scripts');  // no longer needed whilst JS script embedded in shortcode function
*/

/******************
 * AJAX CALL BACK FOR ES SEARCH 
 * Ajax Callback function for  the Ajax Search -It processes the ElasticSearch call and formats the results, whch are then passed back 
*  to the Ajax Search caller, via the Ajax "success" return object as a single block ready to display
 *
 * @return void Any return doen as an 'echo'
 *****************/

 add_action('wp_ajax_nopriv_ajax_search_callback', 'ajax_search_callback');
 add_action('wp_ajax_ajax_search_callback', 'ajax_search_callback');
  
function ajax_search_callback() {

    ///////////////////////////////
    // check that the ES session is already open, bomb out if not
     global $client, $parser;
     if ( !isset( $client ) ) {
         echo  "Cannot open ElasticSearch service";
         die();
     }
 
    ///////////////////////////////
    // OK, the clients up so get the search parameters and start the ES search
    $query = sanitize_text_field($_POST['query']);      // holds two items action="ajax_search_callback" and query=<querystring>
    $indexArr = $_POST['indexes'];                      // holds json object with indexes the user selected to search on, eg: "journals", "tech-articles" etc.
   
    // The standard WP search through posts, returning success with multiple hits as HTML formatted block, and "No Hits" msg is nothing found
    /* $wp_query = new WP_Query(array('s' => $query));
     if ($wp_query->have_posts()) {
        while ($wp_query->have_posts()) {
            $wp_query->the_post();
            echo '<h4><a href="' . get_permalink() . '">' . get_the_title() . '</a></h4>';
            echo '<p>' . get_the_excerpt() . '</p>';
        }
    } else {
        echo 'No results found.';
    }*/
    $parms = [
        'index' => ELASTIC_INDEX_JOURNALS,
        'body'  => [
            'query' => [
                'match' => [
                    'content' => $query 
                ]
            ],
            'highlight' => [
                'fields' => [
                    'content' => [
                        'fragment_size' => 450  // no of chars returned in each hit's highlight block, default=150
                    ]
               ]
            ],
            // The '_source' element returns all fields by default, the "content" is the entire contents of the PDF file, so skip that, we only want the 
            // pdf filename, title (same as PDF filename for journals) and type="journals"|"tech-artciles"
            "_source" => [
                "excludes" => ["content"]
            ]
        ]
    ];
    // Here's the ES search try to do the search and process the response
    try {
        $response = $client->search($parms);
        // Process the results
        echo '<div class="es-results-container">' ;
        foreach ($response['hits']['hits'] as $hit) {
            echo showResultBlock( $query, $hit); 
        }  
        echo '</div>';
        ?>
        <script> 
            ////////////////
            // JS code to expand/collapse each ES results blocks - changes div height from one line (24px) to "auto" to show whole block
            // Triggered by button click. 
            ////////////////
            $(".result-toggle-text button").on("click", function() {
                //debugger;
                
                const $target  = $(this).parent();          // this is the "+/-" button, enclosed in a parent div which is what we want
                var currentHeight = $target.css("height");
                const reducedHeight = "24px";
                
                // set the $target to auto height
                if ( !$target.hasClass("expanded") ) {
                    // set all items to reduced height
                    $(".result-toggle-text").css("height", reducedHeight).css("border", "none" );
    
                    $target
                        .data("oHeight",$target.height())
                        .css("height","auto")
                        .css("margin-bottom", "5px")
                        .data("nHeight",$target.height())
                        .height($target.data("oHeight"))
                        .animate({height: $target.data("nHeight")},400);

                    $target.css("border", "2px solid var(--moa-blue)" );
                    $target.addClass("expanded");
                    
                } else {
                    // $target.css("height", reducedHeight ).css("border", "none" ).css("margin-bottom", "none");
                    $target.animate({height: reducedHeight}, 400).css("border", "none" );
                    $target.removeClass("expanded");
                }
                
            });
        </script>
        <?php
         
    } catch ( Elastic\Elasticsearch\Exception\ElasticsearchException $e) {
        $result = "Elasticsearch error: " . $e->getMessage() ;
        echo $result;
        $x = $client->getTransport()->getNodePool()->nextNode(0);
    }
    
    die();
}


/*************************
 * Function to processa single block of search results into an HTML output. A "block" in this sense is a set of hits within a single journal.
 *
 * @param  mixed $hit - as returned from ES search call, this contains array of hit info, inc two sub arrays "_source[]" &  "highlight[]"
 * @return string - formatted HTML string of all hits in the block, ready to display 
 */
function showResultBlock( $query, $hit) {
    
    $filename = $hit['_source']['filename'];  // Assuming you've indexed the filename as 'filename'
    preg_match_all('/(19|20)\d{2}|(Autumn|Spring|Launch|Commemoration)/', $filename, $matches);
    $year = $matches[0][0];
    $season = $matches[0][1];
    $cover= $year . substr( $season, 0, 1) . '-Cover.jpg';
    $coverPath = site_url() . '/articles/' . $cover;
    $title = "Journal $year $season";

            
    // echo '<div class="es-result-block"><h4><a href="' . site_url() . "/" . MOA_MEMBER_VIRTUAL_FOLDER . "/" . $filename .  '">Journal: ' . $filename  . '</a></h4>';
    // $filepath =  site_url() . "/" . MOA_MEMBER_VIRTUAL_FOLDER . "/" . $filename;
     $output = '
        <div class="es-result-block">
        <h4>' . $title . '</h4>
        <!-- <div class="cover-block">
            <div class="cover-title">'. $title  . '</div> 
            <a href="/pdf-viewer/?pdf=' . $filename  .  '&search=' . $query . '" target="moa_pdf_viewer"><img class="cover-img" src="' . $coverPath . '"></img></a>
        </div> -->
        <div>';
    
    // loop through all hits, creating a paragraph for each, add a "+/-" expand/collapse button at start and a "See in Journal" button at the end of para 
    if (isset($hit['highlight']['content'])) {
        foreach ($hit['highlight']['content'] as $highlight) {
            
            $targetQuery = urlencode( wp_trim_words( $highlight, 5, '') );   // maybe use strip_tags($highlight) to remove highlighted <EM>..</EM> items

            $output .= '
            <p class="result-toggle-text">
                <button>+</button>' .
                $highlight .
                '<a href="/pdf-viewer/?pdf=' . $filename  .  '&search=' . $targetQuery . '" target="moa_pdf_viewer"><button class="result-pdf-link">See in Journal</button></a>
            </p>
            ';
        }
    }
    $output .='
        </div>
    </div>' ;

     return $output;
};