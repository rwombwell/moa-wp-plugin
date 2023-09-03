<?php
/***************
 * Upload multiple files to an Elasticsearch indexer High-Level Outline:
* Create a WordPress admin page.
* Provide a form for multiple file uploads.
* Handle the uploaded files with PHP, sending them to Elasticsearch.
* Use JavaScript (with AJAX) to handle the upload process and provide visual feedback.
*/
///////////////////
// Add admin page
add_action('admin_menu', 'es_add_indexer_menu_item');
function es_add_indexer_menu_item() {
    // note without this script we get an error "jquery.min.js?ver=3.7.0:2 Uncaught TypeError: e(...).sortable is not a function" 
    // see the answer here which fixes it https://stackoverflow.com/questions/16694056/jquery-sortable-is-not-a-function
    wp_enqueue_script(  "moa-jqueryui", "https://ajax.googleapis.com/ajax/libs/jqueryui/1.10.3/jquery-ui.min.js");

    // this adds a menu item in the black sidebar menu
    add_menu_page ('MOA Elasticsearch PDF Indexer', 'MOA PDF Search Indexer', 'manage_options', 'moa-es-uploader', 'moa_es_uploader_admin_page');
  
}
/********
 * ADMIN INDEX PAGE - Used to create a page for indexing PDF files
 */
function moa_es_uploader_admin_page() {
    ?>
    <div class="wrap">
        <h2>Elasticsearch File Uploader</h2>
       
         <!-- The Dropdown Menu to select which PDF folder to index -->
         Select PDF Collection to index <select id="pdf-folder-select">
            <option value="journals">Journals</option>
            <option value="tech-articles">Tech Articles</option>
             <!-- Add more options as needed -->
        </select>

        <!-- radio button to force reindex or not -->
        <p>
            <label><input type="radio" name="force-reindex-option" value="normal" checked> Skip item if already indexed</label>
            <label><input type="radio" name="force-reindex-option" value="force"> Force Item to be Reindexed</label>
        </p>
        
        <!-- button to fetch ES index details -->
        <button id="fetch-es-info">Get Elasticsearch Index Info</button>
        <div id="es-info-output"><!-- Elasticsearch info will be displayed here --></div>

        <div style="border:1px solid darkgray;">
           
            <!-- button to start an ES index operation -->
            <button id="start-upload">Start Indexing PDFs</button>
            <div id="progress-bar-container" style="width: 100%; background-color: #ddd;">
                <div id="progress-bar" style="width: 0; height: 30px; background-color: #4CAF50;"></div>
            </div>
            <!-- scrollable div to display the individual index results as they are fed back -->
            <div id="progress-text-container" style="height:200px;overflow-y:scroll;">
                <div id="progress-text"></div>
            </div>
        </div>
       

    </div>
    <script>
    ///////////////////
    // JavaScript for Visual Feedback: 
    jQuery(document).ready(function($) {
        ///////////////////////////////////
        // JS AJAX CAll to Start the Upload of a Folder
        $('#start-upload').click(function() {
            var selectedFolder = $('#pdf-folder-select').val();     // get the folder to index from the select control
            var forceReindex = $('input[name="force-reindex-option"]:checked').val();
            $('#progress-text').html('');                           // clear out the display area
                   
            uploadNextFile(0, selectedFolder, forceReindex);        // launch the function toindex file, start from the first file (item 0)
        });

        // Function to call Ajax backend to index a single file, set by the 'item' variable passed back here
        function uploadNextFile(item, folder, forceReindex) {
            
            $.ajax({
                type: 'POST',
                // url: esUploaderData.ajaxurl,
                url: '<?php echo admin_url('admin-ajax.php')?>',
                data: {
                    action: 'index_files_on_es',
                    currentItem: item,
                    folder: folder,                 // Send the selected folder
                    forceReindex: forceReindex    // Send the reindexing option
                },
                dataType: 'json',
                success: function(response) {
                    if (response.status === 'success') {
                        $('#progress-bar').width(response.progress + '%');
                        var progressMsg = response.item + ' of ' + response.total + ' Scanned - ' + response.message + ' (in ' + response.timeTaken + ')<br>';
                        $('#progress-text').append( progressMsg);
                        var $target = $('#progress-text-container');            // scrolls the text output in the container div to keep  last line visible 
                        $target.animate({scrollTop: $target.height()}, 1000);

                        // If not done, request the next file
                        if (response.progress < 100) {
                            uploadNextFile(response.nextItem , folder, forceReindex);
                        } else {
                            $('#progress-text').append('<br>All files indexes!');
                        }
                    } else if (response.status === 'completed') {
                        $('#progress-text').append('<br>All files uploaded!');
                    } else if (response.status === 'no-files-found') {
                        $('#progress-text').append('<br>No files found to upload for ' + selectedFolder);
                    }
                },
                error: function() {
                    $('#progress-text').html('Error indexinf files');
                }
            });
        }
        // JS AJAX call to get  ES Index Info, (doc count and last updated time)
        $('#fetch-es-info').click(function() {
            $.ajax({
                type: 'POST',
                // url: esUploaderData.ajaxurl,
                url: '<?php echo admin_url('admin-ajax.php')?>',
                data: {
                    action: 'fetch_es_index_info'
                },
                dataType: 'json',
                success: function(response) {
                    var output = "Document Count: " + response.document_count + " Last Updated: " + response.last_updated;
                    $('#es-info-output').html(output);
                },
                error: function() {
                    $('#es-info-output').html('Error fetching Elasticsearch info.');
                }
            });
        });
    });
    </script>

    <?php
}

///////////////////////////////////////////////////////////
// AJAX BACKEND FUNCTIONS TO INDEX DOCS
///////////////////////////////////////////////////////////


/***************************************************
 * AJAX CALLBACK TO INDEX FILES - CAlled from the Ajax Call when the "Index PDFs" button is called. In fact the fucntion is called repeatably 
 * for each file by the AJAX caller, this enables the Ajax caller to display an incrementally increasing progressbar as it lops through the file list.
 * The AJAX call includes the folder of the PDFs the user has decided to index, and an "item" counter which is used to loop thru the PDF files in the array of PDF read from the selected folder.
 * The item counter is incremented at the end of the function and returned to the Ajax caller thereby allowing it to resend this back to the function.
 * 
 * @param $_POST['folder'] - the object to index, "journals", "tech-articles"
 * @param $_POST['currentItem'] - the file item index in the file array from the folder, this is the item that gets ES indexed, 1st call is item=0
 * @param $_POST['forceReindex'] - string "force" or "normal", if forced then item gets reindexed 
 * 
 * @return void
 *****************************************************/
function index_files_on_es() {
    
    // get the parameters passed up in th e Ajax call 
    $selectedFolder = isset($_POST['folder']) ? sanitize_text_field($_POST['folder']) : 'journals';            // what to index, tech-articles or journals?
    $currentItem = isset($_POST['currentItem']) ? intval($_POST['currentItem']) : 0;                         // index the next item from the passed counter, default=0
    $forceReindex = isset($_POST['forceReindex']) ? (sanitize_text_field($_POST['forceReindex']) === "force" ) : false;  // Forcve reindexing of items? option="force" if yes
    
    // set the folder 
    switch ($selectedFolder) {
        case "journals": 
            $dir =  ABSPATH . MOA_MEMBERS_ONLY_FOLDER . "/";                  // journals folder, eg "c:\xampp\https\macwester.org.uk\httdocs\journals"
            $pdfFiles = glob( $dir . "MacJournal-202*.pdf" );
            break;
        case "tech-articles":
            $dir =  ABSPATH . MOA_MEMBERS_ONLY_FOLDER . "/tech-articles";    // tech-articles folder, eg "c:\xampp\https\macwester.org.uk\httdocs\journals"
            $pdfFiles = glob( $dir . "*.pdf" );
            break;
        default:
           echo json_encode(['status' => "invalid PDF folder $selectedFolder "]);
           die();
    }

    // Check if no files to index and bomb out if so
    $totalFiles = count($pdfFiles);
    if ($totalFiles == 0) {
        echo json_encode(['status' => "no-files-found"]);
        die();
    }
    

    if ($currentItem < $totalFiles) {
        $pdfFile = $pdfFiles[$currentItem];           // Process the next file in the list
        $filename = basename($pdfFile);
        // sleep(1);                                    // debug - Mock Elasticsearch upload with sleep  

        // call function to index a single PDF file 
        $result = indexPdfFile( $pdfFile , $selectedFolder , $forceReindex  );     // return an array with ["message" = success/error msg and "timeTaken" in secs]

        // For demonstration, echo the file name.
        $percentage = (($currentItem + 1) / $totalFiles) * 100;
        echo json_encode([
            'status' => 'success',
            'message'  => $result["message"],
            'timeTaken' => $result["timeTaken"], 
            'filename' => $filename, 
            'progress' => $percentage,
            'item' => $currentItem + 1,
            'total' => $totalFiles,
            'nextItem' => $currentItem + 1
        ]);
    } else {
        echo json_encode(['status' => 'Indexing Completed']);
    }
    
    die();
}
add_action('wp_ajax_index_files_on_es', 'index_files_on_es');
add_action('wp_ajax_nopriv_index_files_on_es', 'index_files_on_es'); // In case non-admins need access

/**************************************
 * ES INDEX SINGLE PDF FILE 
 * indexPdfFile - Indexes an individual PDF file, expects the ES client to be already set up
 *
 * @param  mixed $pdfFile
 * @param  mixed $pdfType
 * @param boolean $force - flag to force a reindex whether if indexed
 * @return array  $result[] - $result["message"] message back from ES, and $result["timeTaken"] execution time in secs (as string)
 */
function indexPdfFile( $pdfFile , $pdfType="journals", $force=false ){
 
    // check that the ES session is already open, bomb out if not
    global $client, $parser;
    if ( !isset( $client ) ) {
        $result["message"] = "Cannot open ElasticSearch service";
        return $result;
    }

    /////////////
    //  Start the indexing process
    $result = array();     
    $start_time = microtime(true);
    $filename = basename($pdfFile);
       
    ////////////
    // check if journal already indexed, bomb out if so, unless the force flag is on
    $params = [
        'index' => ELASTIC_INDEX_JOURNALS,
        'id'    => md5($filename)
    ];
    $response = $client->exists($params);     // return true if item with this id existsior
    if ( ($response->getStatusCode() == "200" || $response == null) ) {
       // item already indexed 
        $result["message"] = $filename . " already indexed, no action";
        if ( $force == true ) {
            $result["message"] = $filename . " already indexed so updated";
        } else {
            $result["timeTaken"] = "0 secs" ;
            return $result;
        }
    } 
    
    // Come here if not yet indexed or indexed and forced to update 
    $pdf = $parser->parseFile($pdfFile);                // use PDF parser to get the text from the filename
    $text = $pdf->getText();                            // this is what takes the time
    $title = $pdf->getDetails()['Title'] ?? $filename;  // create a title field

    // set parameters to do an ElasticSearch DSL index operation
    $params = [
        'index' => ELASTIC_INDEX_JOURNALS,
        'id' => md5( $filename ),
        'body' => [
            'timestamp' =>  date('Y-m-d\TH:i:s.uP') ,  // this time format gives ISO 8601 timestamp compatible with ES's date type.
            'filename' => $filename,
            'title' => $title,
            'content' => $text,
            'type' =>  $pdfType,                        // types will be one of "journals", "tech-articles", "forum" or "posts"
            ]
    ];

    $response = $client->index($params);            // This is the indexing call to the ES server
       
    // Check the response, status =201 means item indexed ok, but we can also check this
    if ( $response['result'] == "created" || $response['result'] == "updated") {
        // $result .= "<br>PDF file <b>". $filename. "</b>  Index was <b>". $response['result']. "</b> in index:<b>" . $params['index'] . "</b>";
        $result["message"] = $filename . " indexed OK";

    } else {
        // $result .= "<br>PDF file <b>". $filename. "</b> ERROR into Elasticsearch index:<b>" . $params['index'] . "</b>";
        $result["message"] = $filename . " FAILED to index correctly, not sure why!" ;
    } 

    $execution_time = intval(microtime(true) - $start_time) . "secs";        // returns time taken in secs
    $result["timeTaken"] = $execution_time ;
    return $result;
}

function send_message($id, $message, $progress) {
    echo "id: $id\n";
    echo "data: { \"message\": \"$message\", \"progress\": $progress }\n\n";
    ob_flush();
    flush();
}


////////////////////////////////////////////////////////////////////////////////////
// Functions to work on ES INdexes
///////////////////////////////////////////////////////////////////////////////////

/************
 * Function to check an Index, expects to be called as an Ajax Callback and thus echo's the output
 */
function fetch_es_index_info() {
    // Include Elasticsearch Client
    global $client;
    if ( !isset( $client ) ) {
        echo "Cannot open ElasticSearch service";
        die;
    }

    $indexName = ELASTIC_INDEX_JOURNALS;  // Replace with your index name

    // Get document count
    $indicesStats = $client->indices()->stats(['index' => $indexName]);
    $docCount = $indicesStats['indices'][$indexName]['primaries']['docs']['count'];

    // Get the last update timestamp
    $params = [
        'index' => $indexName,
        'body' => [
            'size' => 1,
            'sort' => [
                'timestamp' => [  // this is a text string in format 'yyyy-mm-dd h:m:s', so should be sortable
                    'order' => 'desc'
                ]
            ]
        ]
    ];
    $response = $client->search($params);
    $lastUpdated = $response['hits']['hits'][0]['_source']['timestamp'];

    echo json_encode( ['document_count' => $docCount, 'last_updated' =>  date('d-m-Y h:m', strtotime($lastUpdated) )] );
    
    die();
}

add_action('wp_ajax_fetch_es_index_info', 'fetch_es_index_info');

/*********
 * Function to itemise files in an ES index and get their last updated timestamps
 * Expecst to be called as a Ajax callback, hence echo's the output rather than returning it
 */
function fetch_es_index_item_list() {
    // Include Elasticsearch Client
    global $client;
    if ( !isset( $client ) ) {
        echo "Cannot open ElasticSearch service";
        die;
    }

    $indexName = ELASTIC_INDEX_JOURNALS ;  // Replace with your index name

    $params = [
        'index' => $indexName,
        'body' => [
            'query' => [
                'match_all' => new \stdClass()  // Match all documents
            ],
            '_source' => ['timestamp_field'],  // Fetch only the timestamp field
            'size' => 100  // Fetch 100 documents per request, adjust as necessary
        ],
        'scroll' => '1m'  // Keep the scroll context alive for 1 minute
    ];

    $firstPage = $client->search($params);

    $allTimestamps = [];

    while (count($firstPage['hits']['hits']) > 0) {
        // Extract timestamps from the current batch
        foreach ($firstPage['hits']['hits'] as $hit) {
            $allTimestamps[] = $hit['_source']['timestamp_field'];
        }

        // Get the next batch of documents
        $scroll_id = $firstPage['_scroll_id'];
        $firstPage = $client->scroll([
            'scroll_id' => $scroll_id,
            'scroll' => '1m'
        ]);
    }

    echo json_encode(['timestamps' => $allTimestamps]);

    die();
}


/**********************************************
 * GET INFO ON  ES INDEX
 * Function to query Elasticsearch for a PDF conetnt and return highlight information
 * Basic search opertations using the elasticsearch-php client see https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/search_operations.html 
 * Intro to highlighting https://medium.com/@andre.luiz1987/using-highlighting-elasticsearch-9ccd698f08 
 * Elasticsearch link https://www.elastic.co/guide/en/elasticsearch/reference/current/highlighting.html 
 *
 * @param  string  $query -  query expression to search for 
 * @param  string  $index -  Elastricsearch index to search in, default ="moa-index"
 * @return array   $highlights -  array of fields and content 
 *********************************************/
function searchAndHighlight( $query, $index ="moa-index" ) {
    global $client;

    $params = [
        'index' => $index,
        'body' => [
            'query' => [
                'match' => ['content' => $query]
            ],
            'highlight' => [
                'fields' => [
                    'content' => new \stdClass()        // only way in PHP to create an empty object, {} just creates an empty array!
                ]
            ]
        ]
    ];

    $results = $client->search($params);

    $highlights = [];
    foreach ($results['hits']['hits'] as $hit) {
        $highlights[] = [
            'filename' => $hit['_source']['filename'],
            'highlight' => $hit['highlight']['content']
        ];
    }
    
    // print_r( $highlights );     //debug

    return $highlights;
}

