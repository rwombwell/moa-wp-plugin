<?php 
/**************************
 * MOA CLIENT FOR ELASTIC SEARCH
 * Leverages  the ElasticSearch-PHP plugin described here: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/overview.html
 * Also uses the "SmaLot PdfParser", see GitHub stuff here: https://github.com/smalot/pdfparser/blob/master/doc/Usage.md 
 *  Both modules are installed by COMPOSER into this folder. To use composer run it from a DOS box, in the target folder then
 *      composer require elasticsearch/elasticsearch
 *      composer require smalot/pdfparser
 *************************/

/************
* Elastic Search server details, see here for how to set https://www.elastic.co/guide/en/elasticsearch/reference/current/security.html
* note that the default user's (elastic) password can be set in docker-compose.yaml file, see line in environment section - "ELASTIC_PASSWORD=ummagumma"
************/
define( "ELASTIC_HOST", "http://localhost:9200");
define( "ELASTIC_USERNAME", "elastic");
define( "ELASTIC_PASSWORD", "ummagumma");          
define( "ELASTIC_INDEX_JOURNALS", "moa_index");

require 'vendor/autoload.php'; // Load the autoloader
 
use Elastic\lasticsearch\ClientBuilder;
use Elastic\Elasticsearch\Exception\ElasticsearchException;
use Smalot\PdfParser\Parser;

// Create Elasticsearch client instance
$client = Elastic\Elasticsearch\ClientBuilder::create()
    ->setHosts( [ELASTIC_HOST] )
    ->setBasicAuthentication(  ELASTIC_USERNAME, ELASTIC_PASSWORD)
    ->build();


if ( $client) {
    // Test the connection
    try {
        $info = $client->info();
        //echo "Connected to Elasticsearch version {$info['version']['number']}\n";
        // print_r($info);
    } catch ( Elastic\Elasticsearch\Exception\ElasticsearchException $e) {
        echo "Error connecting to Elasticsearch: {$e->getMessage()}\n";
    }
    $parser = new Parser();
}


/**********************
 * Shortcode to Index JOURNALS 
 * moa_elasticsearch_reindex_fn
 *
 * @return string $result - list of indexed items, with each time timed  
 **********************/
function shortcode_to_index_journals(){
    global $client;
    $result = "";
    
    // Get the PDF file path.
    $pdf_folder=  ABSPATH . MOA_MEMBERS_ONLY_FOLDER . "/";                  // journals folder, eg "c:\xampp\https\macwester.org.uk\httdocs\journals"
    $pdf_path  =  $pdf_folder . "MacJournal-202*.pdf";      // wildcard pattern for pdfs to pick up, e.g.: "MacJournal*.pdf"
    $pdf_filelist = glob( $pdf_path );                      // get the file of files matching pattern as array
    
    $result .= deletePdfFiles( $pdf_filelist );             // empty the ES index
    // $result = deleteIndex ( ELASTIC_INDEX_JOURNALS );
    
    $result.= indexPdfFiles( $pdf_filelist , "journal");
    return $result;
}
add_shortcode( "moa-index-journals","shortcode_to_index_journals");

/****************************
 * Function to index an array of PDF files,
 * Expects $client and $parser to have been set up using Elasticsaerch-php, bombs out if not set  
 * Help on indexing text docs here https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/indexing_documents.html 
 * 
 * @param  array $pdf_filelist - numeric array of fully pathed pdf filenames to index
 * @param  string $pdfType - name of the file type being indxed, e.g.; "journal" 
 * @param boolean $force - set to true to force reindexing of all items
 * @return string - list of index results with filenames successfully indxed (or failures) as formatted lines 
 ****************************/
function indexPdfFiles( array $pdf_filelist, $pdfType = "pdf", $force=false) {
   
    global $client, $parser;
    if ( !isset( $client ) ) return ;

    $output = '<br>Indexing '. count( $pdf_filelist ) . " Items"; ;
    
    foreach (  $pdf_filelist as $pdfFile) {
        $result = indexPdfFile( $pdfFile, $pdfType, $force ) ;    
        $output .=   "<br>PDF file <b>". basename($pdfFile). "</b>" . $result["message"]. "<b>" ;
    }
    return $output;
} 



/*************************************************************
* DELETE ES INDEXES OF ARRAY OF PDF FILES
* Function to delete an array of PDF files,
 * Expects $client and $parser to have been set up using Elasticsaerch-php, bombs out if not set  
 * Help on deleting here: https://www.elastic.co/guide/en/elasticsearch/client/php-api/current/deleting_documents.html 
 * 
 * @param  array $pdf_filelist - numeric array of fully pathed pdf filenames to index
 * @param  string $pdfType - name of the file type being indxed, e.g.; "journal" 
 * @return string - list of index results with filenames successfully indxed (or failures) as formatted lines 
  */
 function deletePdfFiles( array $pdf_filelist ){ 
     global $client;
   
    if ( !isset( $client ) ) return ;

    $result = 'Deleting '. count( $pdf_filelist ) . "items in index:" .ELASTIC_INDEX_JOURNALS . PHP_EOL; ;
    
    foreach (  $pdf_filelist as $pdfFile) {

        $filename = basename($pdfFile);
        
        $params = [
            'index' => ELASTIC_INDEX_JOURNALS,
            'id'    => md5($filename),
        ];
 
        try {
            $response = $client->delete( $params );
            $result .= "<br>PDF file <b>". $filename. "</b> was <b>deleted</b> in index:<b>" . $params['index'] . "</b>";
        } 
        catch ( Elastic\Elasticsearch\Exception\ElasticsearchException $e) {
            $result .= "<br>PDF file <b>". $filename. "</b> not deleted, error <b>" . $e->getMessage(). "</b> in index:<b>" . $params['index'] . "</b>" ;
        }
    }
    return $result;
}
/****************
 * DELETE ES INDEX
 * @param  mixed $index
 * @return string 
 *****************/
function deleteIndex( $index) {
    global $client;

    // Delete index 
    try {
         $response = $client->indices()->delete(['index' => $index ]);
         $result .= "<br>Index <b>". ELASTIC_INDEX_JOURNALS. "</b> was <b>deleted</b>";
     } 
     catch ( Elastic\Elasticsearch\Exception\ElasticsearchException $e) {
         $e_json = json_decode( substr( $e->getMessage(), strpos($e->getMessage(),"{") ) ); 
         $result .= "<br>Index <b>". ELASTIC_INDEX_JOURNALS. "</b> was <b>not deleted</b> error:" . $e_json->error->root_cause[0]->reason . "</b";
     }
     return $result;
 }
 
 

/**************
 * CHECK ES INDEX
 ************/
add_shortcode( "moa-elasticsearch-checkindex","moa_elasticsearch_checkindex_fn");

function moa_elasticsearch_checkindex_fn(){
    global $ElasticClient;

    $index_name = "moa_pdf_index";      // Set the index name.

    // Create the search query.
    $query = [
        "query"=> [
            "match"=> [
            "file"=>"pdf"
            ]
        ]
    ];

    // Execute the search query.
    $response = $ElasticClient->search([
        'index' => ELASTIC_INDEX_JOURNALS,
        'body'=> $query,
    ]);

    // Check the response.
    
    if ($response->getStatusCode() == 200) {
        // Get the hits.
       $hitsList = $response->hits;

        // Loop over the hits.
        foreach ($hitsList as $hit) {
            // Get the PDF file name.
       //     $pdf_file_name = $hit->_source->fileName;

            // Print the PDF file name.
            //echo $pdf_file_name . "\n";
    }
    } else {
    echo "An error occurred while listing PDF files.";
    }

}

/**************
 * WP HOOK TO GRAB WP SEARCH LINK SO WE CAN POINT IT AT ELASTICSEARCH
 * Hook to grap the WP search and modify it 
 *
 * @param  mixed $query
 * @return void
 ************/
function hook_to_intercept_search_query( $query ) {
    if ( $query->is_search && !is_admin() ) {
        // Modify the query as needed
        // For example, to search only posts and not pages:
        $query->set( 'post_type', 'post' );
    }
    return $query;
}
add_action( 'pre_get_posts', 'hook_to_intercept_search_query' );

/***************
 * Hook to redirect standard WP serach to Elasticsearch
 * redirect_wp_search_to_elasticsearch
 *
 * @return void
 **************/
function hook_to_redirect_wp_search_to_elasticsearch() {
    if (is_search() && !is_admin()) {
        global $client;
        
        $search_query = get_search_query();
        $params = [
            'index' => 'your_index_name',
            'body'  => [
                'query' => [
                    'match' => ['content' => $search_query]
                ]
            ]
        ];

        $response = $client->search($params);
        
        // Now you need to handle the $response to extract the posts and display them
        // This part can get complex depending on how you've indexed your data and how you want to display results.
        // For simplicity, we're redirecting to a custom page.
        
        // wp_redirect(home_url('/custom-search-page/?q=' . urlencode($search_query)));
        exit;
    }
}
add_action('template_redirect', 'hook_to_redirect_wp_search_to_elasticsearch');

