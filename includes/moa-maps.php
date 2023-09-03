<?php

// use SebastianBergmann\Environment\Console;

$gOverlayTypeArr = [
    ["id"=>"rowans-kelpies"  , "classes"=>["kelpie","rowan"]                , "title"=>"Kelpie & Rowan", "cnt"=>0],
    ["id"=>"mac-twenties"    , "classes"=>["mac 26","mac 27","mac 28"]      , "title"=>"Mac 26, 27 & 28", "cnt"=>0],
    ["id"=>"mac-thirties"    , "classes"=>["mac 30","malin","wight"]        , "title"=>"Mac 30, Malin & Wight", "cnt"=>0],
    ["id"=>"mac-thirtyfives" , "classes"=>["seaforth","pelegian","atlanta"] , "title"=>"Seaforth, Pelegian & Atlanta", "cnt"=>0],
    ["id"=>"others"          , "classes"=>[]                                ,"title"=>"Class Unspecified", "cnt"=>0], 
];


/**
 * Define the [moa-popup-getlonglat] shortcode which creates a popup window with a modified 
 * Navionics Chart extended to return longitude and latitude on the mouse click. The work to extend
 * the Navionics object is all done in JavaScript and loaded into a popup <div...> box. 
 * The popup is triggered by a <button...>, the id of which is picked up from the shortcode
 * and passed to the JS script. Similarly the JS script returns the Long/Lat to an <input.>
 * field also defined in the shortcode call. This field, if populated with a valid Long/Lat position is used to centre
 * is used to center the Navionics chart, if not the chart centers on a default of London(ish).    
 **/

 add_action( 'wp_enqueue_scripts', 'load_scripts_to_header' );
function load_scripts_to_header() {
    ////////////////////////////////////
    // Function to load scripts and styles into WP's page header if the page has a specific shortcode in it 
    // based on suggestion here: \https://wordpress.stackexchange.com/questions/278662/adding-js-in-header-when-using-wp-enqueue-script-in-a-shortcode
    // This works because this function is executed when the page loads and the header is accessible, whereas
    // the shortcode function is executed during the building of the content, by then the header is already done so 
    // any enquueing would have to stuff files into the page footer
    ///////////////////////////////////// 
    
    // next two lines for Leaflet libraries
     wp_enqueue_script('leaflet','https://unpkg.com/leaflet@1.6.0/dist/leaflet.js',NULL,"1.6.0");
     wp_enqueue_style( 'leaflet_style','https://unpkg.com/leaflet@1.6.0/dist/leaflet.css',NULL, "1.6.0");
     
    // Add the Leafler header Script and CSS files to support editing a user's boat long and lat (done on Registration Page and Profile Page)
    //wp_enqueue_script('proj4-lib', plugins_url( '../js/proj4.js' , __FILE__ ));
    wp_enqueue_script('moa-map-popup',   plugins_url( '../js/moa-map-popup.js' , __FILE__ ));
    wp_enqueue_style( 'moa-popup-styles',plugins_url( '../css/moa-popup-styles.css' , __FILE__ ));
    wp_enqueue_style( 'moa-map-styles',  plugins_url( '../css/moa-map-styles.css' , __FILE__ ));
    
    // add fancybox for LightBox image displays
    wp_enqueue_script( "fancybox",      "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js",array('jquery'),NULL,false);
    wp_enqueue_style( "fancybox-style", "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css");
    
    // this for masonry grid display, to use see https://masonry.desandro.com/ - container class="grid", div items class="grid-item"
    // wp_enqueue_script( "desandro-masonry", "https://unpkg.com/masonry-layout@4/dist/masonry.pkgd.min.js" , __FILE__ );

     global $wp_query;
     $postid = $wp_query->get_queried_object_id();
    $mypost = get_post($postid);
    
    $unfiltered_content = (isset( $mypost) ) ? $mypost->post_content : '';      // check page's content for required shortcode
    
    // condition below is true for the Layered Map of all Mac boats
    if( has_shortcode( $unfiltered_content,"moa-map-layers") ){
        moa_header_map_overlays();               // Loads scripts and styles needed for Layered Map screen, inc Leafler icons  & Marker-CLuster code
        // Load MapBox "Omnivore" library to support Leaflet KML overlays and ToGeoJSON() method
        // wp_enqueue_script('leaflet-omnivore','//api.tiles.mapbox.com/mapbox.js/plugins/leaflet-omnivore/v0.3.1/leaflet-omnivore.min.js'); // no longer needed
    }  
}

/////////////////
// Leaflet code to support map view with clusters, 
// Add the Leafler header Script and CSS files to support EBSOC style Leaflet map with clusters, sidebars, marker styles etc.
function moa_header_map_overlays() {
   
    global $gOverlayTypeArr ;
    
    // extra leaflet libraries for the marker Glyphs
     wp_enqueue_script('leaflet-icon-glyph', plugins_url('../js/leaflet-icon-glyph.js', __FILE__), NULL, NULL, NULL);
     wp_enqueue_style('leaflet-icon-glyph-style', "https://cdn.rawgit.com/olton/Metro-UI-CSS/master/build/css/metro-icons.min.css", NULL, NULL, NULL);
            
    // next two for leaflet sidebar
    wp_enqueue_script('leaflet-sidebar',      plugins_url('../js/leaflet-sidebar.js', __FILE__), NULL, NULL, NULL);
    wp_enqueue_style('leaflet-sidebar-style', plugins_url('../css/leaflet-sidebar.css', __FILE__), NULL, NULL, NULL);
      
    // extra leaflet libraries for special marker shapes, ploygons etc.
    // wp_enqueue_script('leaflet-semicircle', plugins_url('../js/leaflet.semicircle.js', __FILE__), NULL, NULL, NULL);
    // wp_enqueue_script('leaflet-polygon-gradient', plugins_url('../js/leaflet.polygon.gradient.js', __FILE__), NULL, NULL, NULL);
    
     // extra leaflet libraries for Maarker Clustering 
     wp_enqueue_script('leaflet-markercluster',       plugins_url( '../js/MarkerCluster/leaflet.markercluster.js' , __FILE__ ),NULL,NULL,NULL);
     wp_enqueue_style('leaflet-markercluster-style',  plugins_url("../css/MarkerCluster.css", __FILE__),NULL,NULL,NULL);
     wp_enqueue_style('leaflet-markerclusterdefault', plugins_url("../css/MarkerCluster.Default.css", __FILE__),NULL,NULL,NULL);
 
     //wp_register_script('leaflet-tabletop', plugins_url( '../js/tabletop.min.js' , __FILE__ ),NULL,NULL,NULL);
     wp_enqueue_script('leaflet-search',       plugins_url( '../js/leaflet-search.min.js' , __FILE__ ));
     wp_enqueue_style('leaflet-search-style',  plugins_url( '../css/leaflet-search.min.css' , __FILE__ ));
     wp_enqueue_style('search-style',          plugins_url( '../css/search-style.css' , __FILE__ ));
     
    // extra overlay map styling - NOTE MUST come after standard leaflet styles have been applied
    wp_enqueue_style( 'moa-map-overlay-styles',  plugins_url( '../css/moa-map-overlay-styles.css' , __FILE__ ));

     // BOAT MAP OVERLAY SCRIPT TIMESTAMP - used to set a version in the wp_enqueue_script() function to force browsers to reload it. Set when file is rebuilt
     $BoatsMapOverlayVersion = get_option("moa_json_boats_overlay_timestamp");
     wp_enqueue_script('moa-shapes-data',  site_url ('/maps/boats-overlay.json'), NULL, $BoatsMapOverlayVersion , NULL);
                   
    // next line for Leaflet EasyPrint library, creates a Print button on map
    wp_enqueue_script('leaflet-easyprint', plugins_url( '../js/leaflet-easyprint.js' , __FILE__ ));
 
    wp_enqueue_script('moa-map-layers',  plugins_url( '../js/moa-map-layers.js' , __FILE__ ));
    //  "localize" moa-map-layers.js file to create a "CurrentUser" JS object, so allow JS script to check if current user has edit rights
    // (properties .userid .userrole (admin|member|registered|guest) and .username).
    $user = wp_get_current_user();
    if ( $user->ID == 0 ) {
        $user_role = "guest";   $display_name = "Guest";
        $user_yacht_class = ''; $user_overlay = '';
    } else {
        $display_name = $user->data->display_name;
        if ( in_array( 'administrator', (array) $user->roles ) || in_array( 'um_moa-admin', (array) $user->roles )) {
            $user_role="MOA Admin";
        } else if ( in_array( 'um_moa-member', (array) $user->roles ) ) {
            $user_role="MOA Member";
        } else if ( in_array( 'um_registered', (array) $user->roles ) ) {
            $user_role="Registered";
        } else {
            $user_role = "Guest";
        }
        $user_yacht_class = get_user_meta( $user->ID, "yacht_class", true);
        $user_overlay = getBoatOverlayType( $user_yacht_class , false )["id"];
    }
    $arr = array (
        'userid' => $user->ID, 'userrole' => $user_role, 'username' => $display_name,
                 'yacht_class' => $user_yacht_class, 'user_overlay' => $user_overlay)
    ;
    wp_localize_script('moa-map-layers', 'CurrentUser', $arr  );
}

////////////////////////////
add_shortcode( 'moa-map-layers', 'moa_map_layers_fn' );
function moa_map_layers_fn( $atts , $content) {
   
    
    ////////////////////
    // Check that the KML file exists, if not build it
    ///////////////////
    // $kml_path = get_home_path()  . "/maps/moa-overlay.kml" ;		// $path will be "/var/www/vhosts/httpdocs/macwester.org.uk/journals"
	// if ( !file_exists( $kml_path ) ) {
    //    CreateKmlFile( );
    // }
    
     ////////////////////
    // Check that the BOATS Overlay file exists, if not build it
    ///////////////////
    $kml_path = get_home_path()  . "/maps/boats-overlay.json" ;		// $path will be "/var/www/vhosts/httpdocs/macwester.org.uk/journals"
	if ( !file_exists( $kml_path ) ) {
        createBoatsMapOverlay( );
    }

    /////////////////////
    // Uses Javascript to insert OpenStreetMap API call
    /////////////////////
    $output ='
<!-- MOA MAP Div for Overlay Map  -->
<div class="map-div" id="map-div" style="height: calc( (var(--vh, 1vh) * 100) - 96px); max-height:600px; width: 100%;"> 
</div>
<script>
jQuery(document).ready(function() {
    (function($) {
        LayerMapCreate("map-div");
      })( jQuery );  
});
</script>
<div id="sidebar">leaflet-sidebar</div>';   // required for leaflet-sidebar, can be anywhere in html  
    return $output;
}

////////////////////////////////////////////
add_shortcode( 'moa-map-static', 'moa_map_static_fn' );
function moa_map_static_fn( $atts , $content) {

    // Get shortcode args 
    $callback_fld = (isset($atts['callback']) ) ? $atts['callback'] : '' ;  //attrib with fld name to return long/lat output to 

    // create a HTML <div ...> block for the modal popup window to hold the navionics chart
    $output ='<!-- MOA MAP For Static Map Edit function -->

        <div class="um-field-label">
            <label >Usual Yacht Mooring</label>
            <span class="um-tip um-tip-w" 
                original-title="Use the map to set your boat\'s Long and Lat coordinates - knowing this makes it\'s position visible to other MOA members on the MOA Charts pages">
                <i class="um-icon-help-circled"></i>
            </span>
            <div class="um-clear"></div>
        </div>
        <div class="map-div" id="map-div" style="height: 300px; width: 100%;">
        </div>
        <!-- no longer need to instantiate the map jQuery here, done in the js file downloaded -->
        <script>
            jQuery(document).ready(function() {
                 (function( $ ) {
                     StaticMapCreate("map-div", "' . $callback_fld . '");       
                })( jQuery );
            });
        </script>
        <!-- END OF MOA-MAP-STATIC -->
    ';
    return $output;
}


////////////////////////////////////////////
add_shortcode( 'moa-map-popup', 'moa_map_popup_fn' );
function moa_map_popup_fn( $atts , $content) {
    /////////////////////
    // Uses Javascript to insert OpenStreetMap API call
    /////////////////////
    

    // Get shortcode args 
    $callback_fld = (isset($atts['callback']) ) ? $atts['callback'] : '' ;  //attrib with fld name to return long/lat output to 
 
    // create a HTML <div ...> block for the modal popup window to hold the navionics chart
    $output ='<!--  MOA MAP For Popup Map Edit function  -->
    <div id="moa-popup-window" class="moa-popup-window">
        <div class="map-div" id="map-div" style="height: 400px; width: 90%; max-width:400px; margin:0 auto;">
            <button type="button" class="moa-popup-close" title="close map">&times;</button>
        </div>
        <script>
            jQuery(document).ready(function() {
                (function( $ ) {
                PopupMapCreate("map-div", "' . $callback_fld . '");
                })( jQuery );
            });    
        </script>
    </div>';
     return $output;
}

/*****************
 * EXPORT JSON OVERLAY FOR BOATS MAP 
 * @returns array, 1st element is json list of boats, 2nd element is array of boat classes to be used as different overlays
 ****************/
function createBoatsMapOverlay() {

    if (is_admin()) return;             // bomb out if called from the WP-admin page

    global $gOverlayTypeArr;            // need to access this because we write it out to the JSON file

    $cnt = 0;
     // We keep $shapesOut, the shapes output object, running thru the loop. This accumulates shapes for 
    // each post. It is initialised as an object with "FeatureCollection" header & "features" array
    $shapesOut = array( 'type' => 'FeatureCollection', 'features' => array() );

    /////////////////////
    // MAIN LOOP - boats are hekd against users, so loop thru user list using get_users() function
	$allusers = get_users( array('orderby' => 'meta_value', 'meta_key'=> 'yacht_class','order'=>'DESC',) );
	$longlat_cnt = 0;
        
    foreach ( $allusers as $user ) {
		++$cnt;                                 // incr the total boat/user count
        $user_id = $user->ID;
        $usermeta = get_user_meta($user_id);        // note earlier get_users() call doesn't return wp_usermeta data so get thathere.
       
		// YACHT COORDS - the next stage needs conditional on boat's long and lat is defined
        $yacht_map_show = ( isset( $usermeta['yacht_map_show']) && strpos( $usermeta['yacht_map_show'][0],"Hide")!= false )? "NO" : "YES";
        $yacht_longlat =( (isset($usermeta["yacht_longlat"]) && !empty($usermeta["yacht_longlat"][0]) ) ? $usermeta["yacht_longlat"][0] : "") ;
        $yacht_longlat = ParseDD( $yacht_longlat );
        
        // ADD YACHT TO OVERLAY - Only export to BOATS.JSON if there's a yacht_longlat set
       if ( ($yacht_longlat <> "")  &&  ($yacht_map_show <> "NO") ) {
            ++$longlat_cnt;     // incr the output count
            $coords = explode( "," , $yacht_longlat );

            // USER ROLE  (note shouldn't ever be any "guest" users!)
            if ( in_array( 'administrator', (array) $user->roles ) || in_array( 'um_moa-admin', (array) $user->roles )) {$user_role="MOA Admin";} 
            else if ( in_array( 'um_moa-member', (array) $user->roles ) ) {$user_role="MOA Member";} 
            else if ( in_array( 'um_registered', (array) $user->roles ) ) {$user_role="Registered";} 
            else {$user_role = "Guest";}

            // OTHER USER & YACHT VARIABLES
            $user_login = $user->data->user_login;
            $display_name = $user->data->display_name;
            $profile = "<a href='/profile/{$user_id}'>Owners Profile</a>";
            $yacht_desc = ( (isset($usermeta["description"]) && !empty($usermeta['description'][0]) ) ? $usermeta['description'][0]  : '');
            $yacht_desc= str_replace("\r\n", "", $yacht_desc) ;
            $yacht_name = ( isset( $usermeta["yacht_name"]) ? ucwords(strtolower($usermeta["yacht_name"][0])) : "");
            $yacht_prev_name = ( isset( $usermeta["yacht_previous_name"]) ? ucwords(strtolower($usermeta["yacht_previous_name"][0])) : "");
            $yacht_class = ( isset( $usermeta["yacht_class"]) ? $usermeta['yacht_class'][0] : "");
            $yacht_date = ( isset( $usermeta["yacht_date"]) ? $usermeta['yacht_date'][0] : "");
            $yacht_rig = ( isset( $usermeta["yacht_rig"]) ? $usermeta['yacht_rig'][0] : "");
            $yacht_sail_no = ( isset( $usermeta["yacht_sailno"]) ? $usermeta['yacht_sailno'][0] : "");
            $yacht_engine = ( isset( $usermeta["yacht_engine"]) ? $usermeta['yacht_engine'][0] : "");
            $yacht_location     =( isset( $usermeta["yacht_location"]) ? $usermeta['yacht_location'][0] : "");
            $yacht_area_sailed     =( isset($usermeta['yacht_area_sailed']) ? $usermeta['yacht_area_sailed'][0] : "");
            $yacht_call_sign     =( isset($usermeta['yacht_call_sign']) ? $usermeta['yacht_call_sign'][0] : "");
            $phone = ( isset($usermeta['phone']) ? $usermeta['phone'][0] : "" );
            // work out yacht pic from UM cover_photo, held in $usermeta[]
            $yacht_pic = "";
            $cover_photo = ( isset( $usermeta["cover_photo"]) ? $usermeta['cover_photo'][0] : "");  // could use line drawing e.g.: /images/mac-plan-rowan-22.jpg
            $cover_photo_path = "/wp-content/uploads/ultimatemember/{$user_id}/{$cover_photo}";
            $yacht_pic = (($cover_photo != "") &&  (file_exists( ABSPATH . $cover_photo_path) )) ? $cover_photo_path : "";
            // work out user's profile pic from UM profile_photo
            $profile_pic = "";
            $profile_photo = ( isset( $usermeta["profile_photo"]) ? $usermeta['profile_photo'][0] : "");  // could use line drawing e.g.: /images/mac-plan-rowan-22.jpg
            $profile_photo_path = "/wp-content/uploads/ultimatemember/{$user_id}/{$profile_photo}";
            $profile_pic = (($profile_photo != "") &&  (file_exists( ABSPATH . $profile_photo_path) )) ? $profile_photo_path : "";
            
            // WORK OUT OVERLAY_TYPE FROM YACHT_CLASS
            // getBoatOverlayType() returns an array {"id","title","yacht_type} for teh found class, 
            // NB, if flag=true it internally increments the global OverlayTypeArr[item]["cnt"] for the found yacht_class
            $x = getBoatOverlayType( $yacht_class, true);    
            $overlay_type = $x["id"];               // the overlay_type id, eg "kelpies-rowans" or "mac-twenties" etc.
            $overlay_type_title = $x["title"];      // the full title eg "Kelpies and Rowans" or "Mac 26s, 27 & 28s"
            $yacht_type = $x["yacht_type"] ;        // the converted yacht_class, eg from "mac 28" -> "mac28", without spaces, so easier to parse later on in JS script
        
            // ADD EXTRA PROFILE IMAGES TO PROPERTIES IF AVAILABLE, (up to 5 available) 
            $yacht_pic_obj_arr = getYachtPics (  $usermeta , $user_id);

            // BUILD LEAFLET GeoJSON "feature" with shape properties and coordinates
            $featureNew = array(
                'type' => 'Feature',
                'properties' => array(
                    'overlay_type'      => $overlay_type,          // used by JS code to identify which overlay to add item to e.g.: "ma-twenties", "mac-thirtyfives"
                    'overlay_title'     => $overlay_type_title,     // used by JS code to identify which overlay to add item to e.g.: "ma-twenties", "mac-thirtyfives"
                    'post_id'           => $user_id,                // the legacy stuff from EBSOC code
                    'edit_link'         => "/profile/$user_id?um_action=edit",
                    'owner_name'        => $display_name,
                    'owner_role'        => $user_role,
                    'owner_phone'       => $phone,
                    'owner_pic'         => $profile_pic,
                    'yacht_name'        => $yacht_name,
                    'yacht_prev_name'   => $yacht_prev_name,
                    'yacht_date'        => $yacht_date,
                    'yacht_type'        => $yacht_type,   
                    'yacht_class'       => $yacht_class,
                    'yacht_rig'         => $yacht_rig,
                    'yacht_sail_no'     => $yacht_sail_no,
                    'yacht_pic'         => $yacht_pic,
                    'yacht_engine'      => $yacht_engine,
                    'yacht_location'    => $yacht_location,
                    'yacht_longlat'     => $yacht_longlat,
                    'yacht_area_sailed' => $yacht_area_sailed,
                    'yacht_call_sign'   => $yacht_call_sign, 
                    'yacht_desc'        => $yacht_desc,
                    'yacht_extra_pics'  => $yacht_pic_obj_arr,
                ),
                'geometry' => array (
                    'type'          => 'Point',           // GeoJSON only supports type= "Point", "Polygon" and "LineString"
                    'coordinates'   => array (
                        // Note that Leaflet.Draw swaps the lat/lng order so we do the same below 
                        number_format((float)$coords[0], 8, '.', ''),       // coord[1] = lng
                        number_format((float)$coords[1], 8, '.', ''),       // coord[0] = lat
                    ),
                ),
            );
            // Add shape ($featureNew) as array item to the 'features' array in the $shapesOut object (FeatureCollection)
            array_push( $shapesOut['features'], $featureNew);
        }
    }
    // We now have a set of shapes in the $shapesOut object, with right header "FeatureCollection".
    // This has to be returned as a JSON string so use json_encode() to do that. Note if there were 
    // no shapes we'd output an empty header, which screws the JS import, so check $count to output a null.  
    if ( $longlat_cnt > 0) {

        // now we have all boat edtails (shapes) in the $shapesOut var
        $shapesOut = utf8ize( $shapesOut);      // ensure all chars are utf8, else json_encode throws an error
        $geojson = json_encode($shapesOut, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
        
        // convert chars which are legal in the json string but need escaping when written to a JS file
        $geojson = str_replace('\\"',      '\\\"',     $geojson);    // add 2nd escape to already escaped dquote {\"} -> {\\"}
        $geojson = str_replace('\\r\\n',   '\\\r\\\n', $geojson);    // add 2nd escape to CRLFs {\r\n} -> {\\r\\n}
        $geojson = str_replace("'",        "\'",       $geojson);    // add single escape to squote {'} -> {\'}
        
        // now convert the boat classes array so it can be used as a list of overlays
        $overlay_json = json_encode($gOverlayTypeArr, JSON_NUMERIC_CHECK | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        // finally eturn both the list of boats (as json strings) and the list of boat classes
        $overlay_json_to_write =  "const OVERLAYS_DATA = '" . $overlay_json . "';" . PHP_EOL . "const SHAPES_DATA = '" . $geojson . "';";
 
        // WRITE JSON TO FILE - scribbles out the boat info into a json file that's read by the Map Layer JS script
        $folder = get_home_path()  . 'maps/';                      // get full directory path (not URL) to folder area
        wp_mkdir_p(  $folder  );                                    // create folder if not exists
        $file_name = $folder . "boats-overlay.json";                // the data file name
        file_put_contents( $file_name, $overlay_json_to_write );    // write file contents
        
        /////////////////
        // UPDATE TIMESTAMP - held in wp_options and later applied to the script's wp_enqueue() , thus giving it a new" version"
        // which then ensures the browser relodas it over any cahced version 
        $dataUpdated =  date("Y-m-d-h:i:s");                                           // todays time and write to option
        update_option("moa_json_boats_overlay_timestamp", $dataUpdated, false);      // then format it ready for use as a timestamp in the wp_enque() function
        
    }
}
add_shortcode("moa-export-boats-map-overlay","createBoatsMapOverlay");

/**********************************
 * GET BLOCK OF EXTRA YACHT IMAGES FROM USER META DATA - ready to include in properties
 * @returns an array of objects, each { yacht_image_url, yacht_image_caption )
 * Expects these images to be stored by UM Profile "image upload" fields which stores the images in serialized 'yacht_image_{n}_metadata' fields
 * where {n} is 1-> 10. The serialized data holds object with keys:
 *  basename, name (url), original_name (caption),  ext (eg "jpg", type (eg "image/jpeg", size (eg: 96520). size_format (eg "94 KB")
 *********************************/
function getYachtPics( $usermeta, $user_id  ){

    $return_array = array();
    for ( $i=1; $i<= 5; $i++ ) {
        if ( isset($usermeta["yacht_image_{$i}"]) ) {
          
            $object = new stdClass;
            $picfile = $usermeta["yacht_image_{$i}"][0];
            $object-> url =  wp_upload_dir()["baseurl"] . '/ultimatemember/'.  $user_id . '/' . $picfile;
          
            $x = unserialize( $usermeta["yacht_image_{$i}_metadata"][0] );
            $object-> caption = $x["original_name"];
            array_push( $return_array , $object );
        }
    }
    return $return_array;
}
           
/**********************************
 * WORK OUT OVERLAY_TYPE FROM YACHT_CLASS - Function to work out which overlay type a particular yacht class lies in
 * @returns an array with overlay id, title, and classes in it 
 * Note has the side effect of incrementing the count held in  $gOverlayTypeArr[ $i ]["cnt"]
 Sets a collective overlay_type variable from the yacht_class. The  JS script DrawingCreateLayer() then creates an overlay for each
 expects overlays array to be set up as numeric array[] of associative arrays() where inner array has identifiers ["id", "title","cnt"]
 *********************************/
function getBoatOverlayType( $yacht_class, $incr_cnt = true) {

    global $gOverlayTypeArr;
    $overlay_type = array();
    $overlay_type["classes"] = "others";
    
    for( $i=0; $i < count( $gOverlayTypeArr)  ; $i++ ){
        foreach ( $gOverlayTypeArr[$i]["classes"] as $classType ){
            if ( stripos( $yacht_class, $classType ) !== false ) {
                $overlay_type["id"]         = $gOverlayTypeArr[$i]["id"];     
                $overlay_type["title"]      = $gOverlayTypeArr[$i]["title"];     
                $overlay_type["classes"]    = $gOverlayTypeArr[$i]["classes"];     
                $overlay_type["yacht_type"] = str_replace(" ","", $classType ); // converts "mac 28" -> "mac28", easier to use this than $yacht_class in JS script
                if ( $incr_cnt ) $gOverlayTypeArr[$i]["cnt"]++;
                break;
            }
        }
    }
    if ($overlay_type["classes"] == 'others') {
        $i = count($gOverlayTypeArr) -1;
        $overlay_type["id"]         = $gOverlayTypeArr[$i]["id"];     
        $overlay_type["title"]      = $gOverlayTypeArr[$i]["title"];     
        $overlay_type["classes"]    = $gOverlayTypeArr[$i]["classes"];     
        $overlay_type["yacht_type"]  = "";           
        if ( $incr_cnt ) $gOverlayTypeArr[$i]["cnt"]++;
    }
    return $overlay_type;

}

/*********************************
 * Function to check that Long Lat is in degree decimalised, (DD) and with feasible coords,
 * Handles formats like [-1.2345, 51.23456]
 * @parm string $str - long lat in either 1.2345�W 50.6789�N or "-1.2345, 50.6789" format
 * @returns string  -  long lat in DD format "-1.2345, 50.6789"
 ********************/
function ParseDD( $str ) {
    
    if ( empty($str)  ) { return "";  }    // bomb out if null string
    
    // $pattern breaks out "1.2345�E 50.6789�N" (with/without comma or degree "�" symbol)
    // into $parts[][] holdings numbers and directions seperately
    // preg_match_all() returns false if fails to match, so we bomb out
    $patttern = '/(-?\d{1,3}\.{1}\d{1,6})(?:[� ]*)([NSEW])?/';
    if ( !preg_match_all( $patttern, $str , $parts) ) {
        return ("");            // bomb out returning
    }
    
    // $parts[0][] holds long lat full text, e.g.:"1.2345E"
    // $parts[1][] holds long lat 1st part (ie the numeric part), e.g.:"1.2345"
    // $parts[2][] holds long lat 2nd part (ie the EWNS part), e.g.:"E"
    $lng    = $parts[1][0];
    $lat    = $parts[1][1];
    $lngdir = $parts[2][0];
    $latdir = $parts[2][1];
    
    if  ( $lngdir=="N" || $lngdir=="S")  {      // swap long and lat
        $x   = $lng;
        $lat = $lng;
        $lng = $x;
    }
    
    if ( $lngdir == "W") { $lng *= -1; }
    if ( $latdir == "S") { $lat *= -1; }
    
    $lng = number_format((float)$lng, 6, '.', '');
    $lat = number_format((float)$lat, 6, '.', '');
    
    // check in correct range for lat and long
    if ( ($lng >= -180 && $lng <= 180) && ($lat >= -90 && $lat <= 90) ) {
        return "{$lng}, {$lat}";
    }
}


/***********************
 *  Use it for json_encode some corrupt UTF-8 chars
 * useful for = malformed utf-8 characters possibly incorrectly encoded by json_encode
 ***********************/
function utf8ize( $mixed ) {
    if (is_array($mixed)) {
        foreach ($mixed as $key => $value) {
            $mixed[$key] = utf8ize($value);
        }
    } elseif (is_string($mixed)) {
        return mb_convert_encoding($mixed, "UTF-8", "UTF-8");
    }
    return $mixed;
}
/********************
* Function checks whether Macwester Boat map KML overlay file needs to be rebuilt.
* Checks the 'moa_overlay_rebuild' option flag which may have been set when either new user 
* Registers (then set by ....)
* user Profile updated (set by 
* or Account updated (set by um_after_user_updated() hook)
*********************/
function  CheckOverlayRebuildNeeded( ){
    
    $user_id = get_current_user_id();
    
    if ( get_option('moa_overlay_rebuild') == 1  ) {
        $start = microtime(true);       // defined in moa-import-export.php
        //CreateKmlFile( );       // takes about 3 secs on XAMPP server
        createBoatsMapOverlay();        // builds the JSON Boat Overlays with clustering
        $time_elapsed_secs = microtime(true) - $start;
        
        // console.log( "Time to prepare KML file: ${time_elapsed_secs}");
        update_option( 'moa_overlay_rebuild', 0);   //and reset flag and record who and when
        update_option( 'moa_kml_last_rebuilt_by', $user_id);
        update_option( 'moa_kml_last_rebuilt', date("m-d-Y h:m:s"));
    
     }

}