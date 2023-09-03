//////////////////////////////////////////////////////////////////////////////////////////
//  Library of  functions to generate a static map/chart using Leaflet mapping library 
// with different layer options, including Navionics Charts and OpenStreetMap.
//  
// These calls are made from from MOA plugin -> moa-popup-map.php using shortcodes
// "[moa-static-map]" 
//
// Note: wp_localize() will have been applied to script, creates JS CurrentUser object from current logged-in user. 
// The properties are: .userid, .role="MOA Administrator"|"MOA Club Member"|"Website Registered"|"Guest" and .user_name
// This enables this script to make decisions on what boat details to reveal and whether they are editable by current used. 
//////////////////////////////////////////////////////////////////////////////////////////

var map;					// map objects need to global because accessed from multiple functions
var marker;					// as with the map's marker object, NB, there's only even one of these

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
	
    ////////////////////////////////////////////////
    // Create layers for the map, used as base map display, (NB default shownis set by defining it in map open call below)
    ////////////////////////////////////////////////
    var streets   = LayerMapOverlay( 'MapBox', 'mapbox/streets-v11' );
    var satellite = LayerMapOverlay( 'MapBox', 'mapbox/satellite-streets-v11' );
    var outdoors  = LayerMapOverlay( 'MapBox', 'mapbox/outdoors-v11') ;
    // var nav       = LayerMapOverlay( 'Navionics', 'nautical') ;
    //var navSonar  = LayerMapOverlay( 'Navionics', 'sonarcharts') ;
    
    
    ////////////////////////////////////////////////
    // Create an overlay from the KML file 
    ////////////////////////////////////////////////
    
    // a trick to redefine icon style for omnivore before it creates markers from the KML,for help on this see
    // https://stackoverflow.com/questions/50624063/how-to-use-custom-icons-on-a-leaflet-omnivore-layer/50625534
    // and omnivore doc on layers https://github.com/mapbox/leaflet-omnivore#custom-layers 
    var omnivoreStyleHelper = L.geoJSON(null, {
        pointToLayer: function (feature, latlng) {
            return L.marker(latlng, {icon: CreateMarkerIcon('blue') });
        }
    });
    // now we read in the kml file and loop thru each of the <Placemark> markers adding popup details
    var kmlLayer = omnivore.kml('/maps/moa-overlay.kml', null, omnivoreStyleHelper)
    .on('ready', function(layer) {                      // event forces a wait until the KML import completes
        
         // loops thru each <Placemark> block, getting properties from <ExtendedData>
        this.eachLayer( function( marker ) {            
            // Check CurrentUser to set icon colour, this is set by WordPress "Localizing" the script 
            // and setting the current user's details, including role 
            if ( CurrentUser.userid == marker.toGeoJSON().properties.user_id  ) {  // true if current user's placemark
                marker.setIcon ( CreateMarkerIcon('green') ); 
                marker.options.title = marker.toGeoJSON().properties.name + ' - your own boat!';
            } else {            //not editable, but set placemark's icon color depending on "role" in the placemark
                if ( marker.toGeoJSON().properties.user_role == "MOA Administrator" )   {
                    marker.setIcon( CreateMarkerIcon('red')) ; }
                if ( marker.toGeoJSON().properties.user_role == "MOA Club Member" )     {
                    marker.options.icon = CreateMarkerIcon( 'blue' ); }
                if ( marker.toGeoJSON().properties.user_role == "Website Registered" )  {
                    marker.setIcon( CreateMarkerIcon('violet') ); }
                if ( marker.toGeoJSON().properties.user_role == "Guest" )               {
                    marker.setIcon( CreateMarkerIcon('yellow') );}
                marker.options.title = marker.toGeoJSON().properties.yacht_name;
            }

            // Do we add "edit this item" into Popup content - inserts a link to js function "LayerMapPopupEdit()" 
            // Checks if this Marker belongs to the CurrentUser, (CurrentUser's ID was set by "wp_localize_script()" and
            // each KML Placemarker object has had the boat owner's ID inserted as a property
            var edit_request = '';                                              // reset the edit string to null for each loop
            if ( CurrentUser.userid == marker.toGeoJSON().properties.user_id    // the ownership check
                 || CurrentUser.userrole == "MOA Administrator" ){              // the administrator check
                var edit_request = `<div><a href="javascript:LayerMapPopupEdit('${marker.toGeoJSON().properties.user_login}','${marker.toGeoJSON().properties.user_id}')">Click here to edit these details</a></div>`;
                ;
            }
            
            // Marker Popup - create a different Popup for each Marker in the layer
            var popup = L.popup(  {autoPan:true,keepInView:true})                           // create a new Popup object
                .setContent( LayerMapPopupContent( marker, edit_request )) ;     // LayerMapPopupContent() sets the boat details into HTML
            marker.bindPopup( popup );                                      // and attach Popup to the Marker
            
            // Marker Tooltip effect - the only way found with Leaflet to get a 'tooltip' when mouse hovers over a marker
            // appears to be to grab the 'mouseover' event and create a new popup and 'moutout' events 
            // Rememebr to only show boat owner's name if role is club member
           marker.on('mouseover', function(e) {
                //open popup;
                var ShowOwnerDetails = ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Administrator" )
                    ? '</br>' + marker.toGeoJSON().properties.owner + ' (' + marker.toGeoJSON().properties.user_role + ')'
                    : "";
                
                var popupOnHover  = L.popup({ className: 'leaflet-popup-content-moa-hover' } )
                    .setLatLng(e.latlng) 
                    .setContent( marker.toGeoJSON().properties.yacht_name + ' - ' + marker.toGeoJSON().properties.yacht_class + ShowOwnerDetails )
                    .openOn(map);
            });
            marker.on('mouseout', function (e) {
               jQuery('.leaflet-popup-content-moa-hover').hide();       // hide the hover popup 
                // map.closePopup();                                    // close all popups, inc the full one clicked on
            });
            

            // Set  Search details - add marker details to search index, remeber only to add owner name if user's role merits it
            if  ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Administrator" ) {
                marker.options.searchfld = marker.toGeoJSON().properties.yacht_name + ' (' + marker.toGeoJSON().properties.owner +')';
            } else {
                marker.options.searchfld = marker.toGeoJSON().properties.yacht_name;
            }


        });
    })

    // Now we can create the Leaflet map object, (see https://leafletjs.com/examples/quick-start/ for help)
    // RobW - as of Dec 2021 Navionics seem to have removed support for their Charts, so change default layer from nav -> outdoors
	map = L.map(MapDivTarget, { center: [52.3, 1.5], zoom: 9, layers: [outdoors,kmlLayer] });     // specifies default overlay

    // Create the Layer Groups to display on the map layer control  box
    var baseMaps = {
	  // RobW - as of Dec 2021 Navionics seem to have removed support for their Charts, so remove these here
      //  "<span title='Navionics Charts'>Charts</span>": nav,
      // "<span title='Navionics Charts with detailed Sonar depth contours'>Charts (with depth contours)</span>": navSonar,
        "Street View": streets,
        "Satellite View": satellite,
        "<span title='Shows environmental and recreational features'>Outdoor View</span>": outdoors
    };
    var overlayMaps = {
        "<span title='Overlays MOA Members boat locations'>MOA Boat Locations</span>": kmlLayer
        // "<span title='Overlays Nautical Charts from Navionics'>Charts</span>": nav
        // "<span title='Overlays Nautical Charts from Navionics - using Sonar depth contours'>Charts</span>": navSonar
    };
    // add base and overlay map's layers popdown control, (collapsed=false means layer control shown expanded )
    L.control.layers(baseMaps, overlayMaps,{collapsed:true}).addTo(map)     
        
    // add a Scale to the map, default position is  bottom left
    L.control.scale().addTo(map);
    
    // add Leaflet Easyprint button to map allowing it to be printed, see
    var printPlugin = L.easyPrint({
        customWindowTitle: 'Macwester Owners Association Chart Printout',
        title: 'Print the map',
	    hidden: false,                      // hwther to hide the print control button, we want it visible
        // position: 'bottomright',         // button posn on map, uses Leafletnomenclature, default='topleft'
        // Use EasyPrintFormatCreate('...') to create 2 more EasyPrint print format options (^&  include in print control button)
        // sizeModes: ['A4Portrait', 'A4Landscape',EasyPrintFormatCreate('A3 Portrait'), EasyPrintFormatCreate('A3 Landscape')]
        sizeModes: ['A4Portrait', 'A4Landscape']
    }).addTo(map); 

   // Leaflet search code, see https://github.com/journocode/leaflet-search-example
   // var searchMarker =  new L.marker({icon:redIcon}) ; //CreateMarkerIcon( 'violet' ); 
   var controlSearch = new L.Control.Search({
        layer: kmlLayer,
        position:'topleft',    
        initial: false,
        zoom: 10,
        marker: false ,
        textPlaceholder: 'Search by Boat or Owner Name',
        propertyName: 'searchfld', // Specify which property is searched into, default is 'options.title' and 'properties.title'
    });
      
     controlSearch.on('search:locationfound', function(e) {
        e.layer.openPopup();
    })
    controlSearch.on('search:collapsed', function(e) {
        //kmlLayer.resetStyle( this );
        map.setView( [52.3, 1.5] , 9) ;
        //kmlLayer.eachLayer(function(layer) {
        //    kmlLayer.resetStyle(layer);
        //});
    })
    
    map.addControl(controlSearch); 
   
} // end of top level CreateStaticMap() function 

/*********************************************
* Function to create HTML block of boat details for the marker popup. Called from CreateStaticMap()
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
    var ShowAll = ( CurrentUser.userrole == "MOA Member" || CurrentUser.userrole == "MOA Administrator" )? true : false;

    if ( ShowAll) {
    return(`${yacht_pic_div}
<div class="label">Boat Name:</div><div class="field">${marker.toGeoJSON().properties.name}</div>
<div class="label">Class:</div><div class="field">${marker.toGeoJSON().properties.yacht_class}</div>
<div class="label">Rig:</div><div class="field">${marker.toGeoJSON().properties.yacht_rig}</div>
<div class="label">Sail No:</div><div class="field">${marker.toGeoJSON().properties.yacht_sail_no}</div>
<div class="label">Engine:</div><div class="field">${marker.toGeoJSON().properties.yacht_engine}</div>
<div class="label">Owner:</div><div class="field">${marker.toGeoJSON().properties.owner}</div>
<div class="label">Phone:</div><div class="field">${marker.toGeoJSON().properties.phone}</div>
<div class="label">Role:</div><div class="field">${marker.toGeoJSON().properties.user_role}</div>
<div class="label">Call Sign:</div><div class="field">${marker.toGeoJSON().properties.yacht_call_sign}</div>
<div class="label">Location:</div><div class="field">${marker.toGeoJSON().properties.yacht_location}</div>
<div class="label">Long/Lat :</div><div class="field">${longlatDMS} (${longlatDD})</div>
<div class="label">Area Sailed:</div><div class="field">${marker.toGeoJSON().properties.yacht_area_sailed}</div>
<div class="label">Notes:</div><div class="field map-popup-content-field-desc"> ${marker.toGeoJSON().properties.desc}</div>
${edit_request}` );
    } else {
        return(`${yacht_pic_div}
<div class="label">Boat Name:</div><div class="field">${marker.toGeoJSON().properties.name}</div>
<div class="label">Class:</div><div class="field">${marker.toGeoJSON().properties.yacht_class}</div>
<div class="label">Rig:</div><div class="field">${marker.toGeoJSON().properties.yacht_rig}</div>
<div class="label">Location:</div><div class="field">${marker.toGeoJSON().properties.yacht_location}</div>
<div class="label">Long/Lat :</div><div class="field">${longlatDMS} (${longlatDD})</div>
<div class="label">Area Sailed:</div><div class="field">${marker.toGeoJSON().properties.yacht_area_sailed}</div>
<div class="label">Notes:</div><div class="field map-popup-content-field-desc">Boat call sign, and owner contact details available to Club Members only, Click here to Join <a href="/office/membership-application-details">Club Membership Details</a></div>
${edit_request}` );

    }
}

/*********************************************
* Function to open boat owners profile page,in edit mode. Called from CreateStaticMap()
* @param {object} [owner] the owner's login name - identifies which profile to edit
* @param {string} [owner_id] the owner's id - not needed but for good measure
* @returns {string} the completed HTML to display in the popup box
********************************************/
function LayerMapPopupEdit( owner, owner_id ) {
    window.open(`/profile/${owner_id}/?um_action=edit`,"_self");
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
function LayerMapOverlay(mapVendor, mapType) {
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
    } else if (mapVendor == 'Navionics') {
        // Notefor  Navionics Overlays, the web site has to be registered, else tiles display as blanks.
        // www.macwester.org.uk and  dev.macwester.org.uk have been with navKey below
        // Another problem - the JNC.Leaflet.NavionicsOverlay object fails to instantiate unless the early versions of the 
        // Navionics jQuery lib and CSS files are loaded (v0.2.0), but even if specifically asked for, the later ones (5.5.1) load instead.
        // Workaround is to include a HTML commented-out Navionics shortcode [nav-webapi] on the page - then both library versions load!
        // TODO: find a fix for this, possible using jQuery.noCOnflict()
        ret = new JNC.Leaflet.NavionicsOverlay({
            navKey: "Navionics_webapi_03567",
            chartType: ( mapType == 'charts' ? JNC.NAVIONICS_CHARTS.NAUTICAL : JNC.NAVIONICS_CHARTS.SONARCHART),
            isTransparent: false,
            logoPayoff: false,       // Enable Navionics logo without payoff (default behaviour)
            attribution: '© <a href="http://www.navionics.com/" target="_blank">Navionics</a>',
            zIndex: 1
        });
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
