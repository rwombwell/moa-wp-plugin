7//////////////////////////////////////////////////////////////////////////////////////////
//  Library of  functions to generate a static map/chart using Leaflet mapping library 
// with different layer options, including Navionics Charts and OpenStreetMap.
//  
// These calls are made from from MOA plugin -> moa-popup-map.php using shortcodes
// "[moa-static-map]" 
//
// Note: wp_localize() will have been applied to script, creates JS CurrentUser object from current logged-in user. 
// The properties are: .userid, .role="MOA Admin"|"MOA Club Member"|"Website Registered"|"Guest" and .user_name
// This enables this script to make decisions on what boat details to reveal and whether they are editable by current used. 
//////////////////////////////////////////////////////////////////////////////////////////

// DEFAULTS FOR WIDE VIEW OF UK, EUROPE, CENTERED ROUGHLY IN MID-ENGLAND 
const MAP_OVERLAY_CENTER_DEFAULT_LAT = 54.265224078605684; 
const MAP_OVERLAY_CENTER_DEFAULT_LNG = -2.0874023437500004;
const ZOOM_OVERLAY_DEFAULT = 5;				

var map;					// map objects need to global because accessed from multiple functions
var marker;					// as with the map's marker object, NB, there's only even one of these
var sidebar;
var gTooltipOpenObj = null;				// is tooltip open? used if mobile, set by shape._tooltip.on("click") and reset by map.on ("click"..) event
var gTooltipOptions = null;
var gTooltipOpenId  = null;
var allItems = new L.FeatureGroup();

/*********************************************
* Top level call to instantiate a Leaflet map on a page. Called from WP [moa-static-map] shortcode. Uses MapBox tiles 
* (registered for rwilde@daemon.co.uk/Asparagus12!) and Navionics tiles, (registration code below). 
* Leaflet maps do not defnite a tile layer by default - so set this seperately using MapBox maps (which are prettiest)
* Note the MapBox access token appears to be a general public one that's free but limited no of accesses per min. 
* @param {string}  [MapDivTarget] Id of div where map will be displayed - this must have a physical width or height set, eg: <div id="map-div" width=80% height=400px>
* else the map tiles will display as blanks
* @global {string} [map] The global map object is created here, (needs to be global for access from other functions)
* @global {string} [marker] The global marker object is created here (needs to be global for access from  other functions)
**********************************************/
function LayerMapCreate( MapDivTarget ) {
	
    //////////////////
    // Get any query string parameters for the page, use the urlParams.get('name') to get an individual arg
    var urlParams = new URLSearchParams(window.location.search);                            // this JS object gets querystring parms from page
    
	 //////////////////
	// Create a div for displaying Tooltips on a mobile. This is styled in ebsoc-map-styles.css and called 
	// from showTooltipFixed( content) in shape.on("click"..) event and map.on("click") event
    createFixedTooltip( MapDivTarget );

    ////////////////////
    // Create layers for the map, used as base map display, (NB default shownis set by defining it in map open call below)
    //var streets   = GetMapTiles( 'MapBox', 'mapbox/streets-v11' );
    var satellite = GetMapTiles( 'MapBox', 'mapbox/satellite-streets-v11' );
    var outdoors  = GetMapTiles( 'MapBox', 'mapbox/outdoors-v11') ;
    //var landscape = GetMapTiles( 'Thunderforest','Landscape') ;
    ///////////////////
    // Create the Layer Groups to display on the map layer control  box
    var baseMaps = {
   //     "Street View": streets,
        "Satellite View": satellite,
        "<span title='Shows environmental and recreational features'>Outdoor View</span>": outdoors,
    //    "<span title='Shows marshes and river features'>Landscape View</span>": landscape,
    };

    ////////////////////
    // MAP ZOOM & CENTER - Check Cookies - to get last used zoom & center coords from cookie,(these are set by map.on('baselayerchange'...) and 'zoom' events)
    // Also check the querystring
    var zoomFromCookie   = urlParams.get('z') || getCookie( "map-zoom" ) ;   // returns zoom level
    var centerFromCookie = urlParams.get('c') || getCookie( "map-center" );   // returns center as comma separated string    
    if (centerFromCookie) {centerFromCookie = centerFromCookie.split(",");}     // convert to a {lat,lng} object

    /////////////////////
    // BASE LAYER - check Cookie to get last used BaseMap and layers, note cookie returns the name, so have to lookup the object
    var baseMapToShow = outdoors;                                   // default baseMap to show
    var baselayerFromCookie = getCookie( "map-baselayer" ) || '' ;   // returns title of the base Map
    for ( key in baseMaps ) {                                        // lookup the object
        if ( baselayerFromCookie  && (key ===  baselayerFromCookie) ) {
            baseMapToShow = baseMaps[key];
        }
    }
    /////////////////////
    // BASE LAYER - check querystring parms to see if a different bas map layer is requested, if so the arg'll be name of the map object
    if  (  urlParams.get('bl') ) {
        switch  (urlParams.get('bl')) {
            case 'satellite':   baseMapToShow = satellite; break;
            case 'outdoors':    baseMapToShow = outdoors;  break;
        }
    }
     /////////////////////
    // MAP OBJECT - Create Leaflet map object, set the centre and zoom levels from those remebered in cookie or default
    map = L.map( MapDivTarget, { 
        center: centerFromCookie || [MAP_OVERLAY_CENTER_DEFAULT_LAT, MAP_OVERLAY_CENTER_DEFAULT_LNG], 
        zoom: zoomFromCookie || ZOOM_OVERLAY_DEFAULT , 
        zoomControl: false, 
		layers: [ baseMapToShow ],           // specifies base and overlay maps to open with
        //dragging: !L.Browser.mobile, // 2-finger scroll , NB better to set  gestureHandling (below)
        //gestureHandling: isMobile(),	// 2-finger scroll with Leaflet Gesture Handling plugin
     });

	/////////////////////////////////////////////////
    // CLUSTERED MARKERS  - Create a Layer Grouping to be used for clustering markers  
    // NB, the icon is a simple L.divIcon, eitehr circle or square, as styled by CSS border-radius
    // For options see plugin https://github.com/Leaflet/Leaflet.markercluster 
    function CreateMarkerCluster( key , title) {
        var markerCluster = new L.MarkerClusterGroup({ 
            iconCreateFunction: function (cluster) {
                var childCount = cluster.getChildCount();
                var heading = `"Cluster of ${childCount} ${title}"`;
                var html = `<div title=${heading}><span>${childCount}</span></div>`;
                var className = 'marker-cluster marker-cluster-' + key; 	// colors and background is styled in moa-maps-styles.css 
                // return L.divIcon({ html: html, className: className, iconSize: L.point(40, 40) });
				return L.divIcon({ html: html, className: className, iconSize: L.point(40, 40) });
            },
            spiderfyOnMaxZoom: true, showCoverageOnHover: true, zoomToBoundsOnClick: true 
        });
        return markerCluster;
    }

    //////////////////////////////
    // ZOOM CONTROL - NB the by zoomControl:false; prevented from being added by default in the L.map() command 
    new L.Control.Zoom({ position: 'topleft' }).addTo(map);
    
   //////////////////////////////
    // OVERLAY LAYER CONTROL - add overlays to Layer Control (NB "collapsed=false" will show control expanded )
    var LayerControl = L.control.layers(  baseMaps, null , {position: 'topleft', collapsed:true}).addTo(map);     
    LayerControl.expand();        // show the control collapsed if item checked
    
     ////////////////////
     // MAP SCALE CONTROL - add scale control to map
	 L.control.scale( {position:'bottomleft'}).addTo(map);    // add Scale to the map, default position is  bottom left

	///////////
    // OVERLAY WANTED - Starts the Overlay LAyer Control with some overlays checked. To do this we check in three places ...
	// (a) Remembered from Cookie, in which case returned as as comma separated list
	// (b) Input from Querystring, in format "...?ol=ma-twenties,mac-thirtyfives..." etc. as comma separated list
    // (c) DEfault from User it they have a Yacht_Clas defined, then that & the relevant overlay is available in CurrentUser.yacht_class & CurrentUser.user_overlay
	// Logic is Querystring(c) trumps Cookie(b) trumps User(a) 
    overlaysWanted = ( urlParams.get('ol') ) ?  urlParams.get('ol') : null;        
    if ( overlaysWanted ) {
        addToCookie(  "map-overlays", overlaysWanted , 1);
    } else {
        var overlaysWanted = getCookie( "map-overlays" ) || '';   
    } if ( !overlaysWanted ) {
		// COme here if its the first time the user opens thwe site, so default is to set all the overlays on
		overlaysWanted = 'others,mac-thirtyfives,mac-thirties,mac-twenties,rowans-kelpies';  
		//overlaysWanted = CurrentUser.user_overlay;		// this would set on only the overlay for the user's class of yacht
		if ( overlaysWanted ) {
			addToCookie(  "map-overlays", overlaysWanted , 1);
		}
	}

	//////////////////////////////////////
    // EASYPRINT CONTROL - Leaflet Easyprint print module - shows a button to map allowing it to be printed
    // NB: commented out because it fails with the overlay map - not sure why, worked ok with Navionics maps, maybe a problem with the map tiles used
    //////////////////////////////////////
    /*if ( !isMobile() ) {
        var printPlugin = L.easyPrint({
            customWindowTitle: 'EB Soc Map Printout',
            title: 'Print the map',
            hidden: false,                      // whether to hide the print control button, we want it visible
            // position: 'bottomright',         // button posn on map, uses Leafletnomenclature, default='topleft'
            // Use EasyPrintFormatCreate('...') to create 2 more EasyPrint print format options (^&  include in print control button)
            // sizeModes: ['A4Portrait', 'A4Landscape',EasyPrintFormatCreate('A3 Portrait'), EasyPrintFormatCreate('A3 Landscape')]
            sizeModes: ['A4Portrait', 'A4Landscape']
        }).addTo(map);
    }*/

    /////////////////////////////////
	// OVERLAY LAYERS - Initialise LISTS and LOAD BOAT LIST- get these as variable in json constant data file (/maps/boats-overlay.json) 
    // Then loop thru DrawingCreateLayer(shapes,key) for each overlay key (eg "mac-twenties") to build a Leaflet "Overlay Layer" for that overlay key.
	// The overlayList[] array of overlay types holds the {overlay-id (key), overlay-title, overlay-count}, then we use the count (if > 20) to 
	// determine whether the overlay layer should be "clustered" 
	var overlayList = JSON.parse(OVERLAYS_DATA);				// array of overlays, eg: [ {id:"atlanta", title:"Atlanta", cnt:16},...]
    var shapes_JSON = SHAPES_DATA								// full list of all boast, as array of Leaflet GeoJSON objects, ready for ingesting into Leaflet LAyer
    var shapeLayers=[];               							// this array will hold the shape layers for each post_type
	var overlaySearchControl=[];								// this array will hold multiple serach icons
    
	////////////
	// OVERLAY LAYERS - Main Loop through overlaysList[] to build the list of boats from the shapes_JSON data
    overlayList.forEach( (element, index, array) => {		// the overlayList[] holds array of objects [id=<shor-tname>,title=<long-name>,cnt=<item-count>),{id=...},]
		var key = element.id;			// the key is used to identity the overlay layer, e.g.: "mac-tewenies",
		var title = element.title;		// titles the overlay, e.g.: "Mac 26s,27s and 28s", important, on-click events return this as the identifier
		var cnt = element.cnt;			// pre-populated count of number of objects, this is added to the title 					

		if (cnt) {						// only add the overlay if it has items in it!

			// BUILD LAYER FROM the shapes_JSON data that holds all shapes - a big function that extracts all objects in the layer overlay
			if ( overlay = DrawingCreateLayer( shapes_JSON , key ) )  {    
				
				////////////
				// BUILD CLUSTERING - only apply Leaflet cluster if item count > 20
				if ( cnt>20 )    {
					var markerCluster = CreateMarkerCluster( key, title );		// creates the cluster of items
					overlay = markerCluster.addLayer( overlay );				// builds overlay items into a cluster object, reassigns the overlay var = cluster not items 
					//map.addLayer( markerCluster );								// Donn't add to map yet
					LayerControl.addOverlay( markerCluster , title + " (" + cnt +")" )	// adds overlay cluster to overlay control 
				} else {
					LayerControl.addOverlay( overlay , title + " (" + cnt +")"  );    // adds overlay (with no Marker Clustering) to overlay control 
				}

				// LAYER SEARCH - build a separate search button for each layer, a bit crass but does the job
				overlaySearchControl[key] = AddSearch( key, title, overlay );     	 // this function builds & returns a search layer
				shapeLayers[key] = overlay;                                			 // remeber the shape layers in this array

				// SHOW ACTIVE LAYERS (REMEMBERED FROM COOKIE) - the cookie remembers which overlays were clicked, use that to re-click them
				var x = overlaysWanted.split(/\s*,\s*/);        		// convert overlaysWanted string with comma separated list of layer keys into array
				if ( x.indexOf( key ) > -1 || overlaysWanted == 'all' ) {  // check if key value is in one of the array indexes
					map.addLayer( overlay );	                   	 	// it is? then make the overlay visibile on map, overlay control automatically shows as active
					LayerControl.collapse();                         	// and as a nicely, collapse the Overlay Control if any overlay visible
					map.addControl( overlaySearchControl[ key]  );   	// this is how we display the search box for this layer
					// The Leaflet Search Control lacks an option to set a classname , making it difficult to change the search icon colors by CSS
					// below is a workaround to force a classname on the control, (see https://stackoverflow.com/questions/63821258/how-to-add-css-class-to-leaflet-control)
					L.DomUtil.addClass(overlaySearchControl[ key].getContainer(),'moa-search-control-'+ key);
		
				}   
			}
		}
    });
  
    ////////////////////////////
    // SIDEBAR CONTROL - ADD TO MAP. We use Leaflet Sidebar Control to display  Marker Popups. This slides in from the left & works well out of the box.
	// Based on helpfrom http://turbo87.github.io/leaflet-sidebar/examples/. 
	// Note the control needs a <div id="sidebar"...> somewhere in the page HTML (which is inserted with "map-div" by moa-maps.php shortcode [moa-layered-map]
    // This is hidden by default and displayed  dynamically by jQuery triggered from the marker's 'onclick' event.
    // These click events and related properties are set in DrawingCreateLayer() function
    sidebar = L.control.sidebar('sidebar', { autoPan: false, position: 'left'});
    map.addControl( sidebar );
    
    ///////////
    // SIDEBAR CONTROL - CLOSE EVENT - when sidebar closes, if NOT mobile then reset marker to normal style 
    sidebar.on('hidden', function () {
        // reset the shape's highlight to normal
        if ( gTooltipOpenObj ) {
            //var pOld = gTooltipOpenObj.feature.properties;  
            //var normalOptions = setStyleMarker( pOld.post_type, pOld.grade, pOld.significance, false);
            //gTooltipOpenObj.setIcon( normalOptions.icon );
            highlightStyle( gTooltipOpenObj, false);
            gTooltipOpenObj = null;
        }
        showTooltipFixed( );           // if called withput a marker this clears the fixed tooltip

        // show the mobile menu button when sidebar hidden, wont have any effect on desktop version
        //jQuery( '.ebsoc-mobile-menu-button').show();
    });
  
	///////////////////////////
	// SIDEBAR CONTROL - SWIPE LEFT EVENT - adds function from ebsoc-map-popup.js:swipedetect() that hides sidebar when its div is swiped left
    // BUG - blocks mouse clicks to the div. Needs fixing
	var sidebarEl = document.getElementsByClassName('leaflet-sidebar')[0];		// need the sidebar container element 
	if ( sidebarEl ) {
            swipedetect( sidebarEl, function(swipedir){ 
            //swipedir contains either "none", "left", "right", "top", or "down"
            if ( swipedir == 'left' )
                sidebar.hide()
				//alert('You just swiped left!')
            })
    }    
    
    ///////////////////////////////
    // QUERYSTRING POPUP ITEM - This checks for querystring "...?pu=123" (the post_id of the data item to be popped up)
    // This routine looks for the post_id in  allItems object (holds all shapes), gets the post_type to identify
    // the associated LayerGroup (using shapeLayers[] array) then zooms (the cluster) to show shape and shows the 
    // overlay layer for that overlay_type on the map
    var popupPostId = ( urlParams.get('pu') ) ?  urlParams.get('pu') : null;        // 
    if ( popupPostId ) {
        // allItems holds all shapes irrespective of which post_type layr they are in
        allItems.eachLayer(function (layer) {                               // 1. Leaflet method to iterate thru allItems' shapes
            if (layer.feature.properties.post_id ==  popupPostId ) {         //    Try and match on post_id to get allItems shape reference
                var key = layer.feature.properties.overlay_type;               // 2. Get the shape's post_type & use that to identify
                var overlaySelected = shapeLayers[key];                     //    the associated overlay object for that post_type
                overlaySelected.eachLayer(function (shape) {                // 3. Iterate thru overlay's shapes to find 
                    if ( shape.feature.properties.post_id == popupPostId){   //    its shape reference using the post_id again
                        map.addLayer( overlaySelected );                    // 4. Ensure the overlay for the post_type is visible
                        overlaySelected.zoomToShowLayer( shape );           // 5. If overlay is a cluster then zoom  map to show shape
                    }
                });
                LayerControl.collapse();                                    // collapse the Overlay Control if any overlay visible
               layer.fireEvent('click');                                   // 6. Finally fire an event to simulate a shape click
            }
        });
    }   
     
    ////////////////////////////////////////
    // SEARCH CONTROL - EVENTS Leaflet search code, see https://github.com/stefanocudini/leaflet-search#options
    // var searchMarker =  new L.marker({icon:redIcon}) ; //CreateMarkerIcon( 'violet' ); 
    //////////////////////////////////////
    function AddSearch(key, title, layer) {

        var controlSearch = new L.Control.Search({
            layer: layer,
			position: 'topleft',
            initial: false,
            autoCollapse: true, // collapse search control after submit(on button or on tips if enabled tipAutoSubmit)
            // zoom: 10,
            marker: false,
            textPlaceholder: 'Search within ' + title,
            // container: ',             // id of a DIV container to put search box into
            propertyName: 'searchfld', 	// Specify which property is searched into, default is 'options.title' and 'properties.title'
        });
        controlSearch.on('search:locationfound', function (e) {
            console.log(  e );
            layer.zoomToShowLayer( e.layer );   // if the item is clustered then this zooms the map's cluster to show the item
            e.layer.fireEvent('click');         // this is how we open the map's popup - by triggering a click event on the item, and up it pops!
           
        })
        controlSearch.on('search:collapsed', function (e) {
            //alert( "controlSearch.on('search:collapsed')")
            map.setView(map.getCenter(), map.getZoom());
        })
		
		return controlSearch;

    } ///////////// End of Leaflet Search setup ////////////////

	//////////////////////////////////////////////////////
    // MAP EVENT HANDLERS
    //////////////////////////////////////////////////////

    ///////////////////////////
    // map.on("popup"...) capture this to pan the map to the centre to ensure popup image is visible, 
    // see https://stackoverflow.com/questions/22538473/leaflet-center-popup-and-marker-to-the-map
    // note, as a dirty fix we added 200px to height because .clientHeight didn't take account of the image,
    // Note fixed more easily by this https://stackoverflow.com/questions/38170366/leaflet-adjust-popup-to-picture-size  
    /* map.on('popupopen', function (e) {
        var px = map.project(e.target._popup._latlng);      // find the pixel location on the map where the popup anchor is
        px.y -= (e.target._popup._container.clientHeight)/2;  // find the height of the popup container, divide by 2, subtract from the Y axis of marker location
        map.panTo(map.unproject(px),{animate: true});       // pan to new center
     });*/
    
    //////////////////////////////
    // MOBILE ONLY - LOCATION EVENTS 
	// Leaflet Location Events, works with/without L.Control.Location plugin, see https://leafletjs.com/reference-1.2.0.html#locate-options for options
	// TO DO : we can use this to pick up whether points of interest are within range
    if ( isMobile() ) {
		map.on('locationfound', function(e) {
			var x=1;
			var radius = e.accuracy;
			var coords = e.latlng;		
		/*	L.marker(e.latlng).addTo(map)	
                .bindPopup("You are within " + parseInt(radius) + " meters from this point").openPopup();
            L.circle(e.latlng, radius).addTo(map);  */
        });
          /* map.on('locationerror', function (e) {
			//e.preventDefault();			// TO DO, don't turn this off, we nned this event to shut down the StaNav when it fails
            alert("GeoLocation Signal Lost");
        }); */
    } 
    
     ///////////////////////////
     // OVERLAY CLICK - EVENTS fired when user changes map overlay layer, and map 'baselayerchange'
     // these events set and get cookies so the map selection options can be persisted
     map.on('overlayadd', function(e) {

        //////////////////
        // SET COOKIE - this block converts the overlist value (eg "Interesting Builings et al") -> the layer's key (eg "listed-building")
        // its necessary because the event only returns the layer's value in e.name.toString()
		// expects overlayList[] = [{id:"mac-twenties, title:"Mac 26, 27,28", cnt:35},{id:"mac-twentyfives"...},{...}]
        var overlayKey = '';
        for ( var i=0 ; i < overlayList.length ; i++ ){
			overlayFullTitle = overlayList[i].title + ' (' + overlayList[i].cnt + ")" ;	// remember we've previously included the count in the overlay title
            if ( overlayFullTitle  === e.name.toString() ){								// so we have to use that formatto check which overlay was hit
				overlayKey = overlayList[i].id;
                break;
            }
        }
        addToCookie(  "map-overlays", overlayKey , 1);

        //////////////////
        // SET SEARCH BOX
        map.addControl( overlaySearchControl[overlayKey]  );   // display the search box for this layer
		L.DomUtil.addClass(overlaySearchControl[ overlayKey].getContainer(),'moa-search-control-'+ overlayKey);	// convoluted way of adding a class to the search button
    });

    map.on('overlayremove', function(e) {

        //////////////////
        // SET COOKIE - this block converts the overlist value (eg "Interesting Builings et al") -> the layer's key (eg "listed-building")
        // its necessary because the event only returns the layer's value in e.name.toString()
       //var keys = Object.keys( overlayList );
        //var vals = Object.values( overlayList );
        var overlayKey = '';
        for ( var i=0 ; i < i < overlayList.length  ; i++ ){
			overlayFullTitle = overlayList[i].title + ' (' + overlayList[i].cnt + ")" ;
			if ( overlayFullTitle  === e.name.toString() ){
				//if (  vals[i] === e.name.toString() ){
                //overlayKey = keys[i];
				overlayKey = overlayList[i].id;
                break;
            }
        }

        var overlaySelected = '';
        var arr = getCookie( "map-overlays" ).split(',');
        for ( x in arr) {
            if ( arr[x].indexOf( overlayKey ) == -1 ) {    // to show overlay on map (auto checks the control)
                overlaySelected = overlaySelected +  "," + arr[x].toString() ;
            }
        }
        setCookie( "map-overlays", overlaySelected.substring(1),1);

        //////////////////
        // SEARCH BOX REMOVE
        map.removeControl( overlaySearchControl[overlayKey]  );   // display the search box for this layer
            

    });

    map.on('baselayerchange', function(e) {
	    baselayerSelected = e.name.toString() ;
	    setCookie( "map-baselayer", baselayerSelected, 1  );      // 1 day cookie expiry
	  });



    ///////////////////////////
    // hide the sidebar when user clicks anywhere on the map object, 
    // sidebar is opened by shape 'onclick' event, set in ebsoc-map-popup.js::DrawingCreateLayer() function
    map.on('click', function(e) {
        sidebar.hide();                 // close the sidebar
        highlightStyle( null );
        // reset the shape's highlight to normal
/*         if ( gTooltipOpenObj ) {
            //var pOld = gTooltipOpenObj.feature.properties;  
            //var normalOptions = setStyleMarker( pOld.post_type, pOld.grade, pOld.significance, false);
            //gTooltipOpenObj.setIcon( normalOptions.icon );
            highlightStyle( gTooltipOpenObj, false);
			gTooltipOpenObj = null;
        } */
        showTooltipFixed( );           // if called withput a marker this clears the fixed tooltip
        // show the mobile menu button when sidebar hidden - wont have any effect on thr desktop view
        jQuery( '.ebsoc-mobile-menu-button').show();

    });

    ///////////////////////////
    // Function fired after user finishes moving the map, note original map center = e.target.options.center
    map.on('moveend', function (e) {
        var c = map.getCenter();                    // returns an object {lat:1234,lng:5678}
        var cArr = Object.values( c );              // convert this to array of values
        setCookie( "map-center", cArr , 1  );      // 1 day cookie expiry
    });

    ///////////////////////////
    // Function fired when user changes the zoom level
    map.on('zoomend', function (e) {
        var zoom = map.getZoom();
        setCookie( "map-zoom", zoom, 1  );      // 1 day cookie expiry
    });
   
	
} // end of top level CreateStaticMap() function 

////////////////////////////////////////
// CREATE LAYERS
////////////////////////////////////////
/*******************
* CREATE LAYERS - Populates a LayerGroup with shapes for a post_type held as GeoJSON strings in DIV or INPUT field
* Analyses each GeoJSON shape and applies correct conversion, e.g shape=L.polygon( <JSONstring>)
* Also detects the GeoJSON properties for each shape an applies those as shape Tooltips and Popups
* @param {string} ID name for DIV or INPUT field holding GeoJSON string of drawn shapes.
* @param {string} post_type_wanted, if specified only returns shapes for this GeoJSON "post_type" property, if null retruns all 
* @param {boolean highlight, if true sets special style on shapes, used so the edited and overal shapes can be distinguished 
    * @param {integer}  post_id_exclude  post_id to exclude from from the overlays, uzsed to exclude the post being edited from other overlay

	* @returns {object} drawnItems layer (actually new L.FeatureGroup()) with Leaflet Draw objects, empty object if no shapes found
********************/
function DrawingCreateLayer( shapes_JSON, post_type_wanted = null, highlight = false, post_id_exclude = null ) {

	let start = Date.now();		// debug - time the function

	// initialise the drawnItems object to return, possibly empty if no shapes discovered
	var drawnItems = new L.FeatureGroup();

	if (shapes_JSON) {												// if nothing in the element 
		////////////////
		// GeoJSON shapes found but note Leaflet.Draw stores shape coords [lng,lat] whilst Leaflet stores
		// in reverse [lat,lng], transpose following  https://gis.stackexchange.com/questions/246102/leaflet-reads-geojson-x-y-as-y-x-how-can-i-correct-this
		// console.log(shapes_JSON);			//debug
		shapesArr = L.geoJSON(JSON.parse(shapes_JSON), {
			coordsToLatLng: function (coords) { return new L.LatLng(coords[0], coords[1], coords[2]); }
			// default order is coords[1], coords[01], coords[2], note the swap 
		}).toGeoJSON();

		///////////////////
		// shapesArr holds shapes in GeoJSON format as array of "features" in "FeatureCollection". 
		// Use each shape's "features[i].geometry" to construct a Leaflet shape object, BUT note
		// GeoJSON standard only defines 3 shape types: "Polygon", "Linestring" and "Point", for 
		// other shapes (Circle, Rectangle, etc.) look for  "properties.subType" which we added manually
		// when we creating the shape in the "draw:created" Event. ( following MS's conventions suggested 
		// for  Bing maps, see  https://docs.microsoft.com/en-us/azure/azure-maps/extend-geojson
		// Beelow we traverse shapesArr in for loop, but could have used Leaflet's GeoJSON "onEachFeature()".
		// as we do in the "draw:edit" event. see https://stackoverflow.com/questions/32834401/edit-feature-attributes-in-leaflet
		/////////////////////
		features = shapesArr.features;
		for (var i = 0; i < features.length; i++) {
			var coords 		  		= features[i].geometry.coordinates;
			var sGeometryType  		= features[i].geometry.type;				// NB, GeoJSON std only supports "PolyGon", "Polyline" & "Point"
			
			var sOverlayType  		= features[i].properties.overlay_type || '';	// get post type from GeoJSON or global set by function
			var sPostId 	  		= features[i].properties.post_id || 0;		// used to create an edit link
			var sYachtOwner  		= features[i].properties.owner_name|| '';	// boat data
			var sYachtName   		= features[i].properties.yacht_name || '';	// boat class as allocated from popdowns in main app, eg: "Mac 28", "Wight Mk2" etc.
			var sYachtClass   		= features[i].properties.yacht_class || '';	// boat class as allocated from popdowns in main app, eg: "Mac 28", "Wight Mk2" etc.
			
            //////////////////////////////////////////////////
			// MARKER OPTIONS - Each boat class defines a differnt marker color, set in setStyleMarker()
			// Assumed  the coordinate is of Type="Point", 
			markerOptions = setStyleMarker(  features[i].properties );		// then passing TRUE into this ucntion will produce a highlighted marker 
			
			var sThisUsersBoat = ( parseInt(CurrentUser.userid) === sPostId );						// true if the this particular boat is the user's 
			if (sThisUsersBoat) {
				markerOptions.icon.options.iconUrl=  '/maps/icons/pin-yellow.png';
			}
			//var options = {};
			//options = Object.assign({}, markerOptions, sStyle );						// shape's saved style overwrites default style 
			shape = L.marker(coords, markerOptions); 

			////////////////////////
			// TOOLTIP & POPUP - Set the shape's Tooltip and Popup 
			if (shape) {
				/////////////////
				// We'll be writing the new shape object into the map, as yet it doesn't have any feature.properties 
				// so we need to explicitly get them from the incoming GeoJSON object's properties
				shape.feature = features[i] || {}; 							// Intialize layer.feature
				shape.feature.type = features[i].type || "Feature"; 		// Intialize feature.type
				shape.feature.properties = features[i].properties || {}; 	// Intialize feature.properties

				////////////
				// TOOLTIP - build differently for Mobile and Desktop, and by post_type
				var sTooltip  = `<b>${sYachtName.toUpperCase()}</b> (${sYachtClass})<br />Owner: ${sYachtOwner}${(sThisUsersBoat) ? "<br />Your Boat!" : ''}`;	    
				shape.bindTooltip(sTooltip, {
					// permanent: true, offset: [-12, -28], interactive: false, direction: 'center', noWrap: false, opacity: 0.8
					className: ( isMobile() ) ? "leaflet-tooltip-mobile" : "leaflet-tooltip-ebsoc",
					permanent: false, offset: [0, 0], interactive: false, direction: 'top', noWrap: false, opacity: 0.8
				});
				
				/////////////////////////
				// SHAPE's POPUP - create the popup content for the shape from the shape's properties
				//var popupContent = createPopupContent( shape.feature.properties );
				
				////////////////////////
				// SHAPE's TOUCHEND  - handle different bahaviour for mobile and desktop usage
				shape.addEventListener('touchstart', function(e){
				    e.preventDefault();
				});

				////////////////////////
				// SHAPE's CLICK EVENT - handle different bahaviour for mobile and desktop usage
				// Set up the marker's click event, note different click behaviour if on a mobile to make up for lack of hover
				shape.on('click', function(e) {
					
					var p = e.target.feature.properties;
					var popupContent = createPopupContent( p );	// recreate popup content  for the clicked shape
				
					////////////////
					// HIGHLIGHT SELECTED ICON Set Highlight style on the selected shape, it gets reset by either a map.click 
					highlightStyle( e.target, true);		// highlight this shape, 
					// and check for other shapes associated with this post_id, highlight them all
					/*
					allItems.eachLayer(function ( layer ) {
						if (layer.feature.properties.post_id ==  e.target.feature.properties.post_id &&
							layer._leaflet_id != e.target._leaflet_id ) {
							highlightStyle( layer , true, false);
						}
					});
					*/

					/////////////////
					// MOBILE & TABLETS - these have no hover (mouseover) so can't do a tooltip on a hover. 
					// Instead we use the marker click event to show the tooltip thru the showFixedTooltip() function
					// then on a marker's second click we chieck if its the same marker. If so then we show the popup, if its
					// a different marker we load showFixedTooltip() with new marker's tooltip and show that.
					// The logic below uses global gTooltipOpenObj to hold the last shape(marker) object. Note this global gets 
					// reset to null by map.on("click"..) event (which is triggered when user clicks anywhere other than a marker). 
					if (sidebar) {
						if ( isMobile() && !isTablet() ) {
							// only do this for "oucht" devices, so there's no hover and thus no tooltip
							if ( gTooltipOpenObj && (gTooltipOpenId  ==  this._leaflet_id)  ) {	
								// true if marker's tooltip is open, so this click must be marker's second click 
								sidebar.setContent( popupContent ); 		// so load sidebar with marker's popup content
								sidebar.show();								// make the sidebar visible
								gTooltipOpenId = null ;						// reset the marker 
							} else {										// true if this is this marker's first click
								// come here if tooltip NOT open, so this must be the marker's first click 
								// this.openTooltip();						// so re-build a new HTML block using tooltip contents & image
								showTooltipFixed(  this );					// show the tooltip for this marker
								if (sidebar.isVisible() ) {					// check if sidebar NOT yet shown
									sidebar.setContent( popupContent ); 	// if not then load sidebar with marker's popup content
									sidebar.show();							// and show the sidebar 
								}
								gTooltipOpenId = this._leaflet_id;			// and record which marker is being shown
							}
						} else {						
							// true if its a desktop user, manual tooltip not needed, its already ebing shown on the hover (mouseover event)
							sidebar.setContent( popupContent ); 			// this is normal behaviour, i.e.: show marker's popup in sidebar
							sidebar.show();									// and make sidebar visible
						}
					} else {
						// true if no sidebar loaded, so we are being displayed in a WP Post List page NOT the Layer Map view
						// window.location.href = p.post_link;					// so jump to the clicked marker as a WP Post List new page 
					}
					
					// new images have been loaded into the page so this call is needed to reinitialise FancyBox so it seees them
					fancybox_init();        // this function picks up any <A..>  tag on the popup/page with attr ->'data-fancybox="gallery"' 

					// hide Map menu button, re-enabled by map click and sidebar hide - wont have any effect on desktop view
					jQuery( '.ebsoc-mobile-menu-button' ).hide();
			

				}); /////////////////// end of shape's click event //////////////////

				////////////////////////////////
				// ADD SHAPE TO LAYER - add the shape with all its properties now set to the  drawnItems Features group
				// NB the drawnItems Feature is created at the start of this function
				// check whether this post Id is being excluded
				if ( ((post_type_wanted === sOverlayType) || (post_type_wanted === null)) 
					 && (sPostId != post_id_exclude) ) {

					drawnItems.addLayer(shape); 			// add the shape to the shapes LayerGroup to  return
					allItems.addLayer( shape );				// record each shape's post_id so we can hilite shapes by post_id

					////////////
					// SEARCH FIELD- Set Search details according to post_type
					shape.options.searchfld =  sYachtName.toUpperCase() + ', ' + sYachtClass + ', ' + sYachtOwner; 
					
				}
			};	///////// end of if (shape) /////////////

		} ///////// end of for loop /////////////

	} /////////// end of if (shapeArr) //////////
	let timeTaken = Date.now() - start;	// debug - time taken for function 
	console.log(`Time taken for DrawingCreateLayer(${post_type_wanted}): ${timeTaken} + " ms`);
	
	return (drawnItems);

}


/**********************
* SIDEBAR POPUP - create the content to be displayed in the sidebar popup
* Called from  DrawingCreateLayer() for every shape 
* do Popup  -  NB, important to set the <img height in pixels so popup can aut-position properly
* see ref https://stackoverflow.com/questions/38170366/leaflet-adjust-popup-to-picture-size on this issue
**********************/
function createPopupContent( properties ) {
	
	//////////////////////////
	// CONVERT LONGLAT - from comma delimted to nautial format, 
	var coordsArr = properties.yacht_longlat.split(", ");				//first split long form lat and into an array
	var longlatDMS = LongLatFormat( coordsArr[0], coordsArr[1] , 3) ;	//this returns the nautical format
    
	// Work out what detail to display, role on current user's role
    var ShowAll = ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Admin" )? true : false;

	var sPostId		  		= properties.post_id || '';	// boat data
	var sYachtName   		= properties.yacht_name || '';	// boat class as allocated from popdowns in main app, eg: "Mac 28", "Wight Mk2" etc.
	var sYachtClass   		= properties.yacht_class || '';	// boat class as allocated from popdowns in main app, eg: "Mac 28", "Wight Mk2" etc.
	
	// Work out what detail to display, based on the current (viewing) user' role - only show full details to members (or admin)
   if ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Admin" ) {
	
		// PROFILE PIC IF IT EXISTS
		var sProfilePic = (( properties.owner_pic ))
		? `<div class="moa-popup-gravitar"><img src="${properties.owner_pic}"</img></div>`
		: '';
		
		sYachtDetails = 
		`<div class="moa-popup-label">Boat Name:</div><div class="moa-popup-field">${properties.yacht_name ||'not known'}</div>
		<div class="moa-popup-label">Previous Name:</div><div class="moa-popup-field">${properties.yacht_prev_name ||'&nbsp;'}</div>
		<div class="moa-popup-label">Class:</div><div class="moa-popup-field">${properties.yacht_class ||'&nbsp;'}</div>
		<div class="moa-popup-label">Owner:</div><div class="moa-popup-field">${properties.owner_name||''}</div>
		<div class="moa-popup-label">Role in Club:</div><div class="moa-popup-field">${properties.owner_role||''}</div>
		<div class="moa-popup-label">Rig:</div><div class="moa-popup-field">${properties.yacht_rig||'&nbsp;'}</div>
		<div class="moa-popup-label">Sail No:</div><div class="moa-popup-field">${properties.yacht_sail_no ||'&nbsp;'}</div>
		<div class="moa-popup-label">Year Built:</div><div class="moa-popup-field">${properties.yacht_date||'&nbsp;'}</div>
		<div class="moa-popup-label">Engine:</div><div class="moa-popup-field">${properties.yacht_engine ||'&nbsp;'}</div>
		<div class="moa-popup-label">Phone:</div><div class="moa-popup-field">${properties.owner_phone ||'&nbsp;'}</div>
		<div class="moa-popup-label">Call Sign:</div><div class="moa-popup-field">${properties.yacht_call_sign ||'&nbsp;'}</div>
		<div class="moa-popup-label">Location:</div><div class="moa-popup-field">${properties.yacht_location ||'&nbsp;'}</div>
		<div class="moa-popup-label">Long/Lat :</div><div class="moa-popup-field">${longlatDMS}<br />(${properties.yacht_longlat ||''})</div>
		<div class="moa-popup-label">Area Sailed:</div><div class="moa-popup-field">${properties.yacht_area_sailed ||'&nbsp;'}</div>
		<div class="moa-popup-label"></div><div class="field map-popup-content-field-desc"> ${properties.yacht_desc ||'&nbsp;'}</div>`;
   } else {
		var sProfilePic = '';
		sYachtDetails = 
		`<div class="moa-popup-label">Boat Name:</div><div class="moa-popup-field">${properties.yacht_name ||'not known'}</div>
		<div class="moa-popup-label">Class:</div><div class="moa-popup-field">${properties.yacht_class ||'&nbsp;'}</div>
		<div class="moa-popup-label">Owner:</div><div class="moa-popup-field">${properties.owner_name||'&nbsp;'}</div>
		<div class="moa-popup-label">Rig:</div><div class="moa-popup-field">${properties.yacht_rig||'&nbsp;'}</div>
		<div class="moa-popup-label">Location:</div><div class="moa-popup-field">${properties.yacht_location ||'&nbsp;'}</div>
		<div class="moa-popup-label">Long/Lat :</div><div class="moa-popup-field">${longlatDMS}<br />(${properties.yacht_longlat ||''})</div>
		<div class="moa-popup-label">Area Sailed:</div><div class="moa-popup-field">${properties.yacht_area_sailed ||'&nbsp;'}</div>
		<div class="moa-popup-label"></div>
		<div>
			<a class="moa-popup-edit-link" href="/office/membership-application-details" title="Registered users can't access all boat details">Full boat and owner contact details available only to Club Members -
			Click to Join Club</a>
		</div>
		`;
   }
	
	// YACHT PIC AS FANCYBOX LINK - for LightBox-style full screen show
	var sCaption = `${properties.yacht_name} (${properties.yacht_class}) Location: ${properties.yacht_location}`;
	var sYachtPic =  formatYachtPic( properties.yacht_pic , sCaption ); 		// possible default image is "/images/default-yacht-pic.jpg";
	
	// YACHT EXTRA IMAGES RENDERED INTO  MULTIPLE DIVS AS FANCYBOX GALLERY IMAGES
	// these are read from properties.yacht_extra_pics which is an array of objects {url:..., caption:...}
	var sYachtExtraPicsBlock = '';
	var sYachtExtraPicsArr = ( properties.yacht_extra_pics || null );			// array of additional yacht pics
	if (sYachtExtraPicsArr.length != 0) {
		sYachtExtraPicsBlock = `
		<div style="text-align:left;">Additional Images (from Profile)</div>
			<div class="grid extra-pics-container">`; 
		sYachtExtraPicsArr.forEach( function( item) {
			sYachtExtraPicsBlock +=  formatYachtPic( item.url , item.caption ); 		// possible default image is "/images/default-yacht-pic.jpg";
		});
		sYachtExtraPicsBlock += '</div>';
	} else if ( CurrentUser.userid == properties.post_id ) {
		sYachtExtraPicsBlock = `
		<div class="moa-popup-edit-link">
			<img src="/maps/icons/sailboat-blue-1.png" width=32 height=32 style="float:left; margin-top:2px;">
			<div>You can now add extra snaps to your profile - click the "Edit Profile" button below</a></div>
	  	</div>`;
	} else {
		sYachtExtraPicsBlock = '';
	}

	// Function to RETURN FORMTTED YACHT IMAGE
	function formatYachtPic( pic , caption = ''){
		return ( pic != '') 
		? `<div class="grid-item map-popup-image" style="display: block; text-align: center;">
		<a class="fancybox" href="${pic}" data-fancybox="gallery" data-options="\'closeBtn\':true" data-caption="${caption}" title="Click to View full screen">
			<img style="width:100%" src="${pic}"</img>
		</a>
		</div>`
		: `<div style="display: block; text-align: center;">
			<img style="width:100%" src="/images/default-yacht-pic.jpg"</img>
		</div>')`;
		;
	}
	
	// new images have been loaded into the page so this call is needed to reinitialise FancyBox so it seees them
	fancybox_init();        // this function picks up any <A..>  tag on the popup/page with attr ->'data-fancybox="gallery"' 
	
	////////////////////////
	// CHECK IF "USER CAN EDIT" LINK BE SHOWN - If the user is "MOA Admin" or this boat item happens to be the current users
	// added  then we build an edit link to the post & embed it in the popup
	// Note the object "CurrentUser" will have been set on the script file in the PHP code (using  wp_Localize())
	var sEditLinkButton = ( CurrentUser.userid == sPostId || CurrentUser.userrole == "MOA Admin"  )
	? `<div class="button">
			<a href="${properties.edit_link}" title="Edit this Item">
			<i class="fa fa-pencil" style="font-size:16px;font-family:FontAwesome;"></i>
			</a>
	  </div>`
	: '';

	var sEditLinkDiv = ( CurrentUser.userid == sPostId || CurrentUser.userrole == "MOA Admin")
	? `<div>
			<a class="moa-popup-edit-link" href="${properties.edit_link}" title="You have permission to Edit this Item">
			You have rights to edit this Boat Profile, click here</a>
	  </div>`
	: '';

	popupContent = `
		<div class="leaflet-popup-content-container">
			<div class="post-type">
				${sYachtName} (${sYachtClass})
			</div>
			<div class="post-menu">
				<div class="button" title="Close"
					onclick='sidebar.hide();'>
					<i class="fa fa-times" style="font-size:18px"></i>
	  			</div>
				${sEditLinkButton}
			</div>
			<div class="leaflet-popup-image-container">
				${sYachtPic}
				${sProfilePic}
			</div>
			<div class="post-title">
				<div class="post-summary">
					${sYachtDetails}
					${sYachtExtraPicsBlock}
					${sEditLinkDiv}
				</div>
			</div>
		</div>`;

	return popupContent;
}

/*********************************************
* Function to create HTML block of boat details for the KML marker popup. Called from CreateStaticMap()
* @param {object} [marker] the marker object loaded from KML file, use toGeoJSON().properties to get at KML <ExtendedData> tags
* @param {string} [edit_request] if the marker can be edited then this string holds HTML to fire off the edit
* @returns {string} the completed HTML to display in the popup box
********************************************/
function LayerMapPopupContent( marker, edit_request ) {
    
    var longlatDMS = LongLatFormat( marker.getLatLng().lng, marker.getLatLng().lat , 3) ;
    var longlatDD  = LongLatFormat( marker.getLatLng().lng, marker.getLatLng().lat , 1) ;
    
    var yacht_pic_div = ( marker.toGeoJSON().properties.yacht_pic != "" 
    ? `<div style="display: block; text-align: center;"><img style="width:100%" src="${marker.toGeoJSON().properties.yacht_pic}"</img></div>;`
    : "");
    
    // Work out what detail to display, role on current user's role
    var ShowAll = ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Admin" )? true : false;

    if ( ShowAll) {
    return(`${yacht_pic_div}
<div class="moa-popup-label">Boat Name:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_name}</div>
<div class="moa-popup-label">Class:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_class}</div>
<div class="moa-popup-label">Rig:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_rig}</div>
<div class="moa-popup-label">Sail No:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_sail_no}</div>
<div class="moa-popup-label">Engine:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_engine}</div>
<div class="moa-popup-label">Owner:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.owner_name}</div>
<div class="moa-popup-label">Phone:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.owner_phone}</div>
<div class="moa-popup-label">Role:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.user_role}</div>
<div class="moa-popup-label">Call Sign:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_call_sign}</div>
<div class="moa-popup-label">Location:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_location}</div>
<div class="moa-popup-label">Long/Lat :</div><div class="moa-popup-field">${longlatDMS} (${longlatDD})</div>
<div class="moa-popup-label">Area Sailed:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_area_sailed}</div>
<div class="moa-popup-label">Notes:</div><div class="field map-popup-content-field-desc"> ${marker.toGeoJSON().properties.desc}</div>
${edit_request}` );
    } else {
        return(`${yacht_pic_div}
<div class="moa-popup-label">Boat Name:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.name}</div>
<div class="moa-popup-label">Class:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_class}</div>
<div class="moa-popup-label">Rig:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_rig}</div>
<div class="moa-popup-label">Location:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_location}</div>
<div class="moa-popup-label">Long/Lat :</div><div class="moa-popup-field">${longlatDMS} (${longlatDD})</div>
<div class="moa-popup-label">Area Sailed:</div><div class="moa-popup-field">${marker.toGeoJSON().properties.yacht_area_sailed}</div>
<div class="moa-popup-label">Notes:</div><div class="field map-popup-content-field-desc">Boat call sign, and owner contact details available to Club Members only, Click here to Join <a href="/office/membership-application-details">Club Membership Details</a></div>
${edit_request}` );

    }
}
  
/*********************************************
* Call to create a Leaflet map overlay, called from CreateStaticMap()
* (registered for rwilde@daemon.co.uk/Asparagus12!) and Navionics tiles, (registration code below). 
* Leaflet maps do not defnite a tile layer by default - so set this seperately using MapBox maps (which are prettiest)
* Note the MapBox access token appears to be a general public one that's free but limited no of accesses per min. 
* @param {string}  [mapVendor] select map tile provider, either "MapBox" or "Navionics"
* @param {string} [mapType] select the style of the vendors map, see inside the function for supported types
* @returns {object} the overlay object ready to use in Leaflet map
**********************************************/
function GetMapTiles(mapVendor, mapType) {
    var ret = {};
    if ( mapVendor == 'MapBox') {
      // NB, different mapbox map styles are listed here: https://docs.mapbox.com/api/maps/#mapbox-styles
      ret = L.tileLayer( 'https://api.mapbox.com/styles/v1/{id}/tiles/{z}/{x}/{y}?access_token={accessToken}', {
            id: mapType,
            tileSize: 512,
            maxZoom: 18,
            zoomOffset: -1,
            accessToken: 'pk.eyJ1IjoicndpbGRlIiwiYSI6ImNrZmQxcjM2cDFsZ2gzMW84NGZsZHd6ODEifQ.uj7lm67CTfsUSAF10URkBQ',
            attribution: '© <a href="https://www.mapbox.com/about/maps/" target="_blank">Mapbox</a>'
        });
    } 
    else if('Thunderforest') {
        //  Free maps provided by "Thunderview" (do good cycling map "OpenCycleMap"), mapType can be any of 
        // 'landscape','transport', 'transport-dark', 'cycle', 'outdoors', 'pioneer', 'spinal-map', 'mobile-atlas', 'neighbourhood'
        // This is especially good for paths and tracks, needs an API key, for for other map tiles
        // see https://manage.thunderforest.com/dashboard (account=<email> pwd=Asp***2!)
        var mbAttr1 = '<a href="http://openstreetmap.org">OpenStreetMap</a>';
        var mbAttr2 = '<a href="http://thunderforest.com/">Thunderforest</a>';
        mbUrl   ='https://tile.thunderforest.com/' + mapType + '/{z}/{x}/{y}.png?apikey=d6e1b8053b214f50a7f7a139076debc6'
        ret = L.tileLayer( mbUrl , { 
            attribution: '&copy; ' + mbAttr1 + ' Contributors & ' + mbAttr2,
            maxZoom: 18,
        });
    }
    else if (mapVendor == 'osMasterMaps') {
        // OS MasterMaps Topology 1:2500 maps
        var apiKey = 'ICqRSc1nrWdaU8bkxORw4gTJTSWRp8Lp';

        if ( mapType == 'TopologyColour' ) {
             // the following map options from OS code (but not used here)
            var mapOptions = { minZoom: 7, maxZoom: 19, center: [ 54.425, -2.968 ], zoom: 14, attributionControl: false };
            var mbUrl   ='https://api.os.uk/maps/vector/v1/vts/resources/styles?key=' + apiKey;
            ret = L.mapboxGL({ 
                style: mbUrl,
                attribution: 'OS Maps',
                transformRequest: url => { return { url: url += '&srs=3857'} }
            });
        } else if ( mapType == 'TopologyGrayscale' ) {
             // the following map options from OS code (but not used here)
             var mapOptions = { minZoom: 7, maxZoom: 19, center: [ 54.425, -2.968 ], zoom: 14, attributionControl: false };
             // based on example here: https://labs.os.uk/public/os-data-hub-examples/os-vector-tile-api/vts-example-custom-style
            var customStyleJson = 'https://raw.githubusercontent.com/OrdnanceSurvey/OS-Vector-Tile-API-Stylesheets/master/OS_VTS_3857_Open_Greyscale.json';
            ret = L.mapboxGL({
                style: customStyleJson,
                attribution: 'OS Maps',
                transformRequest: url => {
                    if (! /[?&]key=/.test(url)) url += '?key=' + apiKey
                    return { url: url + '&srs=3857'}
                }
            });
        } else if ( mapType == 'Outdoor_3857') {
            // Beware, this API key doesn't work, need to check with OS Support as to why
            // based on example: https://labs.os.uk/public/os-data-hub-examples/os-places-api/capture-and-verify-example-find
            // which includes the OS Places API Search module
            var mbUrl = 'https://api.os.uk/maps/raster/v1/zxy';
            ret = L.tileLayer( mbUrl + '/Outdoor_3857/{z}/{x}/{y}.png?key=' + apiKey, {
                maxZoom: 20
            });
            
        }
    } 
    return ret;
}


/*********************************************
* Call to create additional Leaflet EasyPrint formats to include as print options,
* Note needs icon image support in CSS file. see
* https://github.com/rowanwins/leaflet-easyPrint for guidance, also extra help for EasyPrint v2 in
* https://cran.r-project.org/web/packages/leaflet.extras2/leaflet.extras2.pdf
* TODO - The creation of new formats all works OK, BUT the printout size seems wrong, further experimentation 
* uploaded to /maps/icons folder
* @param {string} the name of format, only two supported 'A3 Protrait' and 'A3 LAndscape'
* @returns {object} the EasyPrint format object, ready to include in EasyPrint print control 
********************************************/
function EasyPrintFormatCreate( format ){
    if ( format == 'A3 Portrait') {
        return {
            width: 2339,    //Width & Height are defined in pixels at 90DPI, eg A4Portrait height=1045, width=715
            height: 3308,
            name: 'A3 Portrait',
            className: 'a3CssClassPortrait',
            tooltip: 'A3 size Portrait'
        }
    } else if (format == 'A3 Landscape') {
        return {
            width: 3308,  
            height: 2339,
            name: 'A3 Landscape',
            className: 'a3CssClassLandscape',
            tooltip: 'A3 size Landscape'
        }
    }
}

////////////////////////////////////////////////////////////////////
// LAYER MAP SUPPORT FUNCTIONS 
// TAKEN FROM EBSOC WORK - in ebsoc-map-popup.js
////////////////////////////////////////////////////////////////////


/*********************************************
* Call to create diffrent colour Leaflet marker icons, called from CreateStaticMap(), assumes Leaflet icons have been 
* uploaded to /maps/icons folder
	markerOptions = { icon: CreateMarkerIcon( null, 'brush-pallette-brown-33x30.png', [33, 30], 'brush-pallette-shadow-33x30.png', [33, 30]),  };
* @param {string}  colour of std  marker icon: 'blue'(default), 'red', 'green', black, orange, greyviolet, yellow, gold
* @param {string}  the post_type, eg  "constable-painting" sets up a different marker icon, colour ignored
* @returns {object} the marker icon object, ready to use in Leaflet map
********************************************/
//function CreateMarkerIcon( color, image = null , imageSize = [25,41], shadow = null, shadowSize = [41,41]) {
	function createMarkerIcon( color = 'blue', post_type = null ) {

		color = color || 'blue'; // just to make sure default value for color gets set
		switch (post_type) {
			case 'constable-painting': options = {
				//iconUrl: `/maps/icons/brush-pallette-${color}-33x30.png`,
				//shadowUrl: '/maps/icons/brush-pallette-shadow-33x30.png',
				//iconSize: [30, 28],iconAnchor: [15, 14],popupAnchor: [0, -41],shadowSize: [41, 41],shadowAnchor: [12, 30],
				iconUrl: `/maps/icons/square-${color}.png`,
				shadowUrl: '/maps/icons/brush-pallette-shadow-33x30.png',
				iconSize: [30, 30],
				iconAnchor: [15, 15],		// offset of icon tip from icon top left, relates to icon size
				popupAnchor: [0, -41],		// popup coords are same as marker, so with its 41px downarrow it needs a -41px offset to sit neatly above icon
				shadowSize: [41, 41],
				shadowAnchor: [12, 30],		// offset of icon tip from icon top left, relates to icon size
				tooltipAnchor:[14,0],
				className: `marker-icon-painting`	// identifyig an icon by color seems only way to change cursor style (by using CSS)
			}; break;
			default: options = {
				//iconUrl: `/maps/icons/marker-icon-${color}.png`,
				//iconSize: [25, 41],iconAnchor: [12, 41],popupAnchor: [0, -41],shadowSize: [41, 41],shadowAnchor: [12, 41],
				iconUrl: `/maps/icons/circle-${color}.png`,
				shadowUrl: '/maps/icons/marker-shadow.png',
				iconSize: [30, 30],
				iconAnchor: [15, 15],		// offset of icon tip from icon top left, relates to icon size
				popupAnchor: [0, -41],		// popup coords are same as marker, so with its 41px downarrow it needs a -41px offset to sit neatly above icon
				shadowSize: [12, 30],
				shadowAnchor: [12, 30],		// offset of icon tip from icon top left, relates to icon size
				tooltipAnchor:[14,0],
				className: `marker-icon-${color}`	// identifyig an icon by color seems only way to change cursor style (by using CSS)
			}; break;
		}
		return new L.Icon(options);
	}
		
/************
// GLYPH ICONS - Used for Listed-Buildings, subclass the Icon class ->  Icon.Glyph to access a house symbol on blue markers
// note requires Leaflet plugin Icon.Glyph, see / Leaflet ICONS here  https://github.com/Leaflet/Leaflet.Icon.Glyph/blob/gh-pages/README.md 
************/
// Use this sub-class for droplet style icons with Glyph 
//L.Icon.Glyph.EBSOC = L.Icon.Glyph.extend({ options: { iconUrl: '/maps/icons/marker-icon-blue.png', } });
//L.icon.glyph.ebsoc = function (options) { return new L.Icon.Glyph.EBSOC(options); };

// Use this sub-class for circle style icons with Glyph 
L.Icon.Glyph.EBSOC = L.Icon.Glyph.extend({ options: { 
		iconUrl: '/maps/icons/circle-blue.png', 
		iconSize: [32,32],
		iconAnchor: [16,16],
		glyphAnchor: [0, 0],
		tooltipAnchor: [14,-16],
	} 
});

L.icon.glyph.ebsoc = function ( options ) { return new L.Icon.Glyph.EBSOC( options ); };

/**************
 * HIGHLIGHT STYLES - Hightlights and "un-highlights" ia shape. Called when shape  clicked on and then clicked off
 * (as when another shape highlighted). Provides a simple highlight - for Points icon -> to red, for
 * Polygons and Polylines it sets the border to red line, 4 px wide
 *************/
function highlightStyle(shape, highlight = false, resetPrevious = true) {

	var opts = {};
	// check if there's already a highlighted shape to be un-highlighted
	if ( resetPrevious == true ) {
			if ( gTooltipOpenObj ) {									// ptr to previously highlighted shape 
			setStyle( gTooltipOpenObj )	;
			switch ( gTooltipOpenObj.feature.geometry.type) {		// copy of previously highlighted shape's pre-highlighted options
				case 'Point': {
					gTooltipOpenObj.setIcon( gTooltipOptions.icon );	// restore the shape's pre-hihilte options
					break;
				}
				default: {
					gTooltipOpenObj.setStyle( gTooltipOptions );		// polyline & polygons options treated the same
					break;
				}
			}
			gTooltipOptions = null;								// and clear the previously hilited detaails
			gTooltipOpenObj = null;
		}
	}

	// check if the call is to hilite another shape or just null
	if (shape && highlight) {

		gTooltipOptions  = Object.assign({}, shape.options);		// save a copy of the to-be-hilited shape's options
		var p = shape.feature.properties;

		opts = setStyleMarker( shape.feature.properties , highlight);
		shape.setIcon(opts.icon);

		gTooltipOpenObj = shape;							// and save pointer to the highlighted shape
	}
	
}

/**************
 * SET STYLES - for all three types: Markers, Polygons, Polylines. Sets a default style on a shape
 *************/
function setStyle(shape, highlight = false) {

	var opts = {}, optsMerged = {};
	opts = setStyleMarker( shape.feature.properties , highlight);
	shape.setIcon( opts.icon );

}

/***********
* MARKER STYLES - define Styles for Markers and Polygons for different post types 
* NB called when creating a  marker for an existing shape, (then shape. properties object is passed
* Also called when creating a new shape, then shape properties is null and post_type is defined
* @params {object} shapeProps shape's properties, needed so we can check for properties like 'grade' 'facility-type' etc.
***********/
function setStyleMarker( shapeProps = null , highlight = false, post_type = null ) {
 
	var zIndexOffset;

	switch ( shapeProps.yacht_type || post_type ) {
		// in all these, if var highlight true then shape is either edited and so needs to look different, typically with "red" icon
		case 'kelpie' 	: color="brown-1"; break;
		case 'rowan' 	: color="brown-2"; break;
		case 'mac26' 	: color="purple-1"; break;
		case 'mac27' 	: color="purple-2"; break;
		case 'mac28' 	: color="purple-3"; break;
		case 'mac30' 	: color="green-1"; break;
		case 'malin' 	: color="green-2"; break;
		case 'wight' 	: color="green-3"; break;
		case 'seaforth' : color="blue-3"; break;
		case 'pelegian' : color="blue-2"; break;
		case 'atlanta' 	: color="blue-1"; break;
		default			: color="gray-1"; 
	}
	
	return { 
		zIndexOffset: zIndexOffset, 
		/*
		icon		: L.icon({ iconUrl: ( ( highlight  ) ? `/maps/icons/sailboat-scarlet.png` : `/maps/icons/sailboat-${color}.png`), 
		iconSize	: [32,32], 
		iconAnchor	: [16, 16], }) ,
		className	: (( highlight  ) ? "highlight" : ""), 
		*/
		icon: L.icon({ iconUrl: ( ( highlight  ) ? `/maps/icons/pin-scarlet.png` : `/maps/icons/pin-${color}.png`), 
			iconSize: 		[25, 41],
			iconAnchor: 	[12, 41],		// offset of icon tip from icon top left, relates to icon size
			popupAnchor: 	[0, -41],		// popup coords are same as marker, so with its 41px downarrow it needs a -41px offset to sit neatly above icon
			tooltipAnchor: 	[0, -38],		// tooltip  coords are same as marker, so with its 41px downarrow it needs a -41px offset to sit neatly above icon
			// popupAnchor: [1, -34],	// std leaflet popup anchor
    		shadowUrl: 		'/maps/icons/marker-shadow.png',
			shadowSize: 	[41, 41],
			shadowAnchor: 	[12,41],		// offset of icon tip from icon top left, relates to icon size
		}) ,
		className: 		(( highlight  ) ? "highlight" : `pin-${color}`),  	// identifyig an icon by color seems only way to change cursor style (by using CSS)
        


	};

}
/************
 *  Checks if object is eempty, approach is valid for ECMA5+ browsers
 */
function isEmptyObject( obj ) {
	if ( (Object.keys( obj ).length === 0) && (obj.constructor === Object)) {
		return true;
	} else {
		return false;
	}
	
}

/******************
 * Cookie management for the Javascript
 **************** */
function setCookie( name, value, days) {
    var expires = "";
    if (days) {
        var date = new Date();
        date.setTime(date.getTime() + (days*24*60*60*1000));
        expires = "; expires=" + date.toUTCString();
    }
    document.cookie = name + "=" + (value || "")  + expires + "; path=/";
}
function getCookie(name) {
    var nameEQ = name + "=";
    var ca = document.cookie.split(';');
    for(var i=0;i < ca.length;i++) {
        var c = ca[i];
        while (c.charAt(0)==' ') c = c.substring(1,c.length);
        if (c.indexOf(nameEQ) == 0) return c.substring(nameEQ.length,c.length);
    }
    return null;
}

function eraseCookie(name) {   
    document.cookie = name +'=; Path=/; Expires=Thu, 01 Jan 1970 00:00:01 GMT;';
}
/***************
 * Adds a string to an existing cookie string, e.g.: cookie = "alpha,beta" and key ="delta"
 * then new cookie =  "alpha,beta,delta"
 * @param {string} cookieName the name of the cookie
 * @param {string} key the name of the strign to add
 * @param {integer} timeInDays the number of days until cookie expiry
 ***************/
 function addToCookie(  name, item , days) {
	var itemList = getCookie( "map-overlays" ) || '';
	if ( !itemList) { 									// true if nothing in item list
		itemList = item ;
	} else {
		if ( itemList.indexOf( item ) == -1)			// true if item alreadyNOT  in item list
    		itemList = itemList.concat( "," + item );	// so add it
	}
	setCookie( name, itemList, days  );     			 //  cookie expiry in days
}
/**************
*  Detects if user on Mobile or Desktop, seems to work very reliably, see's tablets as mobiles (iPad)
* alternative to Leaflet's inbuilt L.Browser.mobile() method which does the same thing
* For other methods in Leafler Browser utils see https://docs.eegeo.com/eegeo.js/v0.1.840/docs/leaflet/L.Browser/ 
***************/
function isMobile(){
    // credit to Timothy Huang for this regex test: 
    // https://dev.to/timhuang/a-simple-way-to-detect-if-browser-is-on-a-mobile-device-with-javascript-44j3
    
        if ( DEBUG_MOBILE ) return true;
    
        if(/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent))
            return true
       else
            return false
    }
    /**************
    *  Detects if user on Tablet
    ***************/
    function isTablet() {
        if ( DEBUG_TABLET ) return true;
        const userAgent = navigator.userAgent.toLowerCase();
        const isTablet = /(ipad|tablet|(android(?!.*mobile))|(windows(?!.*phone)(.*touch))|kindle|playbook|silk|(puffin(?!.*(IP|AP|WP))))/.test(userAgent);
        return isTablet;
    }
    
    /**************
    *  Simple way to detect if the screen is portait or landscape, typically used with isMobile() test
    ***************/
    function isLandscape(){
        if( window.innerHeight > window.innerWidth )
            return false;
        else 
            return true;
    
    }
    
	
/*******************
 * SWIPE - function for detecing a Swipe, used to close leaflet sidebar, see ebsoc-map-layers.js where sidebar setup
 * taken from http://www.javascriptkit.com/javatutors/touchevents2.shtml
 * USAGE: 
	var el = document.getElementById('someel')
	swipedetect(el, function(swipedir){
		swipedir contains either "none", "left", "right", "top", or "down"
		if (swipedir =='left')
			alert('You just swiped left!')
	})
* Note - important NOT to call e.preventDefault() in any of the touch events, otherwise mouse and touch clicks to open
* items on the DIV aren't seen. Hence them being commented out below.
******************/
function swipedetect(el, callback){
  
    var touchsurface = el, swipedir,startX,startY,distX,distY,
    threshold   = 150, //required min distance traveled to be considered swipe
    restraint   = 100, // maximum distance allowed at the same time in perpendicular direction
    allowedTime = 300, // maximum time allowed to travel that distance
    elapsedTime,startTime,
    handleswipe = callback || function(swipedir){}
  
    touchsurface.addEventListener('touchstart', function(e){
        var touchobj = e.changedTouches[0]
        swipedir = 'none'
        dist = 0
        startX = touchobj.pageX
        startY = touchobj.pageY
        startTime = new Date().getTime() // record time when finger first makes contact with surface
        //e.preventDefault()
    }, false)
  
    touchsurface.addEventListener('touchmove', function(e){
        //e.preventDefault() // prevent scrolling when inside DIV
    }, false)
  
    touchsurface.addEventListener('touchend', function(e){
        var touchobj = e.changedTouches[0]
        distX = touchobj.pageX - startX // get horizontal dist traveled by finger while in contact with surface
        distY = touchobj.pageY - startY // get vertical dist traveled by finger while in contact with surface
        elapsedTime = new Date().getTime() - startTime // get time elapsed
        if (elapsedTime <= allowedTime){ // first condition for awipe met
            if (Math.abs(distX) >= threshold && Math.abs(distY) <= restraint){ // 2nd condition for horizontal swipe met
                swipedir = (distX < 0)? 'left' : 'right' // if dist traveled is negative, it indicates left swipe
            }
            else if (Math.abs(distY) >= threshold && Math.abs(distX) <= restraint){ // 2nd condition for vertical swipe met
                swipedir = (distY < 0)? 'up' : 'down' // if dist traveled is negative, it indicates up swipe
            }
        }
        handleswipe(swipedir)
        //e.preventDefault()
    }, false)
}
  

/****************
 * FANCYBOX INITIALISE - Similar to the above BUT NOT called on document.ready(), instead this is called when popups are loaded so
 * that the  page's gallery can be reset to include any newly loaded <A> tag images.
 ****************/
function fancybox_init() {

	var ret = jQuery('[data-fancybox="gallery"]').attr('rel', 'fancybox').fancybox({
		//var ret = jQuery("a[href*='.jpg'], a[href*='.png']").attr('rel', 'fancybox').fancybox({
		//maxWidth  : 800,
		//maxHeight : 600,
		//fitToView : true,
		//width     : '90%',
		//height    : '90%',
		//autoSize  : true,
		//closeClick    : false,
		//openEffect    : 'none',
		//closeEffect   : 'none'
		buttons: [
			"zoom",
			"share",
			"slideShow",
			//"fullScreen",
			//"download",
			"thumbs",
			"close",
		],
		loop: true,			// makes fancybox display end link got to the first again, eroneously labeled 'cyclic' option in some doc!
	});
} ////////////////////// end of fancybox_init() /////////////////////////////

/********************************
* function to show tooltip for mobile devices, called from shape.on("click") event and shape._tooltip.on("remove")
* if called with null marker then fixed toolbar is hidden 
********************************/
function showTooltipFixed( marker = null ) {

	var div = document.getElementById( 'map-tooltip-fixed' );

    if ( marker) {
		var sOverlayType = marker.feature.properties.overlay_type || '';
		var sYachtPic = marker.feature.properties.yacht_pic || '';
		var sYachtName = marker.feature.properties.yacht_name.toUpperCase() || '';
		var sYachtClass = marker.feature.properties.yacht_class || '';
		var sOwnerName = marker.feature.properties.owner_name || '';
		var sOwnerRole = marker.feature.properties.owner_role || '';

		var divImg = document.getElementById( 'map-tooltip-fixed-image' );
		divImg.setAttribute("title", sYachtName );
		if ( sYachtPic ) {
			divImg.setAttribute("src", sYachtPic );
			divImg.setAttribute("style","display:block;")
		} else{
			divImg.setAttribute("src", "/images/default-yacht-pic.jpg" );
			divImg.setAttribute("style","display:none;")
		}

    	var content = `${sOwnerName} (${sOwnerRole})`;
		var title =  `${sYachtName}  (${sYachtClass})` ;

		jQuery( '#map-tooltip-fixed-title' ).html(  title );
		jQuery( '#map-tooltip-fixed-text' ).html(  content );

		// set the background color, expects CSS variables "--tooltip-background-<post_type>" to have been setup 
		var backgroundColor = (sOverlayType) ? `var(--tooltip-background-${sOverlayType})` : `--tooltip-background-default`;
		jQuery( '#map-tooltip-fixed' ).css('background-color', backgroundColor );

		div.style.display = "block"
	} else {
		div.style.display = "none"
	}	
};

//////////////////
// TOOLTIP ON A MOBILE - Create a div for displaying Tooltips on a mobile. This is styled in ebsoc-map-styles.css and called 
// from showTooltipFixed( content) in shape.on("click"..) event and map.on("click") event
//////////////////
function createFixedTooltip( MapDivTarget ) {

	///////////////////
	// Append the div block that will hold the marker's tooltip and image
	jQuery( '#' + MapDivTarget ).append( `
    <div id="map-tooltip-fixed" class="map-tooltip-fixed" style="display:none;position:absolute">
		<img id="map-tooltip-fixed-image" src="#"></img>
		<div id="map-tooltip-fixed-title"></div>
		<div id="map-tooltip-fixed-text"></div>
	</div>` 
	);

    ////////////////////
    // Add a click event to the div which will launch the currently selected marker's popup in the Leaflet sidebar
    jQuery( '#map-tooltip-fixed' ).bind('click', function(e) {           
        L.DomEvent.stopPropagation(e);                      
        // global gTooltipOpenObj is set by shape.on("click") event - it holds the selected marker object
        console.log( gTooltipOpenObj );             
        if ( gTooltipOpenObj )  {
			var p = gTooltipOpenObj.feature.properties;         // the properties hold the marker's popup content
			// this function builds the popup ready for the sidebar display
			var popupContent = createPopupContent( p );	// fast, popup data is already in the marker's properties
			if (sidebar) {
				sidebar.setContent( popupContent ); 
				sidebar.show();
			} 
        }
    });

}
