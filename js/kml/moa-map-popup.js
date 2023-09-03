//////////////////////////////////////////////////////////////////////////////////////////
// Popup Library functions to generate a popup map for users to use to return their boat coords  as
// a Longditude & Latitude. Supports two different map options, Navionics Charts and OpenStreetMap.
// The Navionics chart object needs to be extended so that it returns a long/lat value from a mouse click. 
// The OSM object is more capable and includes functiosn for its own markers & popups. We use those.
//  
// These calls are made from from MOA plugin -> moa-popup-longlat.php using shortcodes
// [moa-popup-map callback=".."] or [moa-static-map callback=".."]
//
// DOCUMENTATION
// Leaflet Maps, see https://leafletjs.com/examples/quick-start/ for initial hgelp
//
//////////////////////////////////////////////////////////////////////////////////////////

var map;								// map objects need to global because accessed from multiple functions
var marker;								// as with the map's marker object, NB, there's only even one of these
var callbackFld;							// fld for returning long lat values & locating map launch button (specified in shortcode)
var editMode;						
const MAP_CENTER_DEFAULT_LNG = 1.280555, MAP_CENTER_DEFAULT_LAT = 51.962500;	// default centre for map

/*********************************************
 Top level call instantiates a static Leaflet map in the MapTargetDiv. The map links to an INPUT box with Id 
 defined as lookupFld 
 @global {object} [map] The global map object is created here, (needs to be global for access from other functions)
 @global {object} [marker] The global marker object is created here (needs to be global for access from  other functions)
 @param {string}  [MapDivTarget] Id of div where map will be displayed - this must have a physical width or height set, eg: <div id="map-div" width=80% height=400px>
 else the map tiles will display as blanks
 @param {string} [MapTargetDiv]	Previously defined div box to hold the map display
 @param {string}  callbackFldFromShortcode] fld for returning long lat values & locating map launch button (specified in shortcode),
 sets global callbackFld value

**********************************************/
function StaticMapCreate( MapDivTarget, callbackFldName ) {
	
	
	callbackFld = GetFldByPartialId( callbackFldName );				// set global variable with callback field on form
	editMode  = ( callbackFld.tagName == 'INPUT') ? true : false;	// set global editMode if  callbackfld is an INPUT box 

	/////////////////////////////////////////////
	// Here we set the map centre from long/lat in the callback fld, if empty then set the centre to global default
	var coordsCenter = ParseLongLatFld( callbackFld );		// fn will convert any long/lat formats to DD
	if ( !coordsCenter.isSet ) { 											
		coordsCenter.lng = MAP_CENTER_DEFAULT_LNG;
		coordsCenter.lat = MAP_CENTER_DEFAULT_LAT
	}
	
	////////////////////////////////////////////////
	// Here we create a Leaflet map object with the centre set above & setLeaflet tile layer to OpenStreetMaps
	map = L.map(MapDivTarget, {center: coordsCenter, zoom: 9 });	
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo( map );

	///////////////////////////////////////
	// Set different marker behaviour depending on where we are in edit or view mode
	if ( editMode ) {			

		///////////////////////////////////
		// when either yacht_name or yacht_longlat input boxes lose focus we need to upadte the marker popup
		// with possibly changed yacht_name and/or long/lat
		function SetStaticMapPopupOnBlur() {
			var coords =  ParseLongLatFld( callbackFld );			// convert coords from any format yo long/lat in DD
			if (coords.isSet) {										// will be false if callbackFld is empty
				marker
					.setLatLng( coords )							// set the new marker position
					.bindPopup( StaticMapPopupContent(coords) ) 	// populate the popup box with boat's info
					.openPopup();									// and show the popup over the marker
			}
		};
		callbackFld.onblur = SetStaticMapPopupOnBlur;				// attach local function to input boxes losing focus events
		yachtNameFld = GetFldByPartialId( 'yacht_name' );			// remember yacht_name input box make have different fld name
		yachtNameFld.onblur = SetStaticMapPopupOnBlur;				// and to yacht_name input lose focus event
		
		///////////////////////////////////
		// Create the (global) marker - in editMode make it draggable & set drag and dragend event to update popup
		marker = L.marker( coordsCenter, 
		{	draggable: true,      
			title: 'Drag pin to move boat location',   
			opacity: 0.8,			
			riseOnHover: true,
			icon: CreateMarkerIcon( 'red' )
		})
		.on('drag', function ( e ) {					// Override default action for clicking (to hide the marker's popup)
			var m = this.getLatLng();					// get the marker's current posn
			
			callbackFld.style.color = "darkred" ;			// change font color to dark red
			callbackFld.value = LongLatFormat( m.lng, m.lat, 1 );	// return to data to field in simple  decimalised degrees
			// callbackFld.focus;
		})
		.on('dragend', function ( e ) {				// note, after a drag the mouse posn hasn't changed
			var m = this.getLatLng();			// but the marker posn has, get the cords with this method 
			marker.bindPopup( StaticMapPopupContent( m )) ;	// and stuff lng and lat into the content
			marker.openPopup();				// then reopen the popup
			
			callbackFld.style.color = "rgb(102, 102, 102)";		// return font color to color set in Ultimate Member settings (dark Gray)
			
		});
		/////////////////////////////////////
		// Leaflet default map click event is to close popup, so this event handler fixes that
		map.on('click', function (e) {
			marker
				.setLatLng(e.latlng)
				.bindPopup( StaticMapPopupContent( marker.getLatLng()  ) )
				.addTo(map)				// NB must add popup to map (with .addTo(map))  before calling .openPopup() 
				.openPopup();
			m = marker.getLatLng();	
			callbackFld.value = LongLatFormat( m.lng, m.lat, 1 );	// return to data to field in simple  decimalised degrees
		}); 

	} else {	// Come here if editMode false ( ie in Profile view mode)
		//Create a non-draggable marker & popup
		marker = L.marker([ coordsCenter.lat,  coordsCenter.lng ], 
			{draggable: false,       
			title: 'To change boat location edit the Profile (click on the gear cog)',   
			icon: CreateMarkerIcon( 'blue' )
			});
		// Leaflet default map click eventis to close popup, so this event handler fixes that
		map.on('click', function (e) {
			marker.openPopup();
		}); 
	
	} /* End of editMode if..else block */

	///////////////////////////////////
	// Set the same marker popup box for both edit and view mode 
	marker
		.bindPopup( StaticMapPopupContent( marker.getLatLng() ), {className: "static-view"})
		.on('mousedown', function() {
			marker.bindPopup( "To change the boat's coordinates edit your profile (top-right gear wheel)") ;	// and stuff lng and lat into the content
			marker.openPopup();	
		})
		.on('mouseup', function ( e ) {				// note, after a drag the mouse posn hasn't changed
			var m = this.getLatLng();			// but the marker posn has, get the cords with this method 
			marker.bindPopup( StaticMapPopupContent( m )) ;	// and stuff lng and lat into the content
			marker.openPopup();				// then reopen the popup
		})
		.addTo(map)
		.openPopup();
	
} // end of top level StaticMapCreate() function 

/*********************************************
* Sets the long/lat content of the Leaflet map marker popup box. Function create reates two div elements
* so they can be styled differently. Each div has an onClick() event which calls UpdateAndClose()
* function that saves the coords in the callback element and closes the window.
* @param {string} [lng] longitude in DD (degree decimalised) format, converted to display format using LongLatFormat()
* @param {string} [lat] latitude in DD (degree decimalised) format, converted to display format using LongLatFormat()
*******************************************/
function StaticMapPopupContent( coords  ) {

	// see whether there's an input box called "yacht_name" on the page, if so display it in the popup
	var yacht_name_fld = GetFldByPartialId("yacht_name");	
	if ( editMode ) {
		if ( yacht_name_fld ) { 
			var yacht_name = yacht_name_fld.value; 
			yacht_name = ( yacht_name ) ? yacht_name + '<br>' : "";
		}
	} else  {
		 if ( yacht_name_fld ) { 
			var yacht_name = yacht_name_fld.innerHTML;
			yacht_name = ( yacht_name ) ? yacht_name + '<br>' : "";
		}
	}
	//if ( !yacht_name ) {  yacht_name = "(no name yet)" } ;
	
	var longlat = ParseLongLatFld( callbackFld );
	if ( !longlat.isSet ) {
		longlatMsg = "Drag the marker to set the boat's position"  ;
	} else {
		longlatMsg = LongLatFormat( coords.lng, coords.lat, 2 )
	}
	// see if coords are available, and convert to formatted display if so
	var html = `<div class="leaflet-static-content-moa" onclick="UpdateAndClose();" style="cursor:pointer">
		${yacht_name}${longlatMsg}
	</div>`
	return ( html );
}

/*********************************************
* Top level shortcode call to instantiate a Leaflet map in a div which can be "popped up" (ie made visible) from a form button.
* The map uses OpenStreetMap tiles. This  function also creates a  "Map Launch" button which shows the popup map and a "Map CLose" button 
* to hide the popup map. 
* @global {string} [map] The global map object is created here, (needs to be global for access from other functions)
* @global {string} [marker] The global marker object is created here (needs to be global for access from  other functions)
* @param {string}  [MapDivTarget] Id of div where map will be displayed - this must have a physical width or height set, eg: <div id="map-div" width=80% height=400px>
* else the map tiles will display as blanks
* @param {string}  callbackFldFromShortcode] fld for returning long lat values & locating map launch button (specified in shortcode),
* sets global callbackFld value
**********************************************/
function PopupMapCreate( MapDivTarget, callbackFldName ) {
	
	callbackFld = GetFldByPartialId( callbackFldName );				// set global variable with callback field on form
	editMode  = ( callbackFld.tagName == 'INPUT') ? true : false;	// set global editMode if  callbackfld is an INPUT box 

	// Add buttons and coords field to Leaflet <map-div> element the display Long/Lat Coords   which holds the long and lat coords
	PopupMapLaunchButton();
	PopupMapCloseButton();

	/////////////////////////////////////////////
	// Here we set the map centre from long/lat in the callback fld, if empty then set the centre to global default
	var coordsCenter = ParseLongLatFld( callbackFld );		// fn will convert any long/lat formats to DD
	if ( !coordsCenter.isSet ) { 											
		coordsCenter.lng = MAP_CENTER_DEFAULT_LNG;
		coordsCenter.lat = MAP_CENTER_DEFAULT_LAT
	}
	
	////////////////////////////////////////////////
	// Here we create a Leaflet map object with the centre set above & setLeaflet tile layer to OpenStreetMaps
	map = L.map(MapDivTarget, {center: coordsCenter, zoom: 9 });	
	L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
		attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>'
	}).addTo( map );

///////////////////////////////////////
	// Set different marker behaviour depending on where we are in edit or view mode
	if ( editMode ) {			

	///////////////////////////////////
		// Create the (global) marker - in editMode make it draggable & set drag and dragend event to update popup
		marker = L.marker( coordsCenter, 
			{	draggable: true,      
				title: 'Drag pin to move boat location',   
				opacity: 0.8,			
				riseOnHover: true,
				icon: CreateMarkerIcon( 'red' )
			})
			.on('drag', function ( e ) {					// Override default action for clicking (to hide the marker's popup)
				var m = this.getLatLng();					// get the marker's current posn
				callbackFld.value = LongLatFormat( m.lng, m.lat, 1 );	// return to data to field in simple  decimalised degrees
				// callbackFld.focus;
			})
			.on('dragend', function ( e ) {				// note, after a drag the mouse posn hasn't changed
				var m = this.getLatLng();			// but the marker posn has, get the cords with this method 
				marker.bindPopup( PopupMapPopupContent( m )) ;	// and stuff lng and lat into the content
				marker.openPopup();				// then reopen the popup
			});
	
	/////////////////////////////////////
		// Leaflet default map click event is to close popup, so this event handler fixes that
		map.on('click', function (e) {
			marker
				.setLatLng(e.latlng)
				.bindPopup( PopupMapPopupContent( marker.getLatLng()  ) )
				.addTo(map)				// NB must add popup to map (with .addTo(map))  before calling .openPopup() 
				.openPopup();
			m = marker.getLatLng();	
			callbackFld.value = LongLatFormat( m.lng, m.lat, 1 );	// return to data to field in simple  decimalised degrees
		}); 
		
		//////////////////////////////
		// By default Leaflet popup close button closes Leaflet popup but the Leaflet marker, hence Leafet which holds the long and lat coords
		// jQuery below which changes Leaflet close button's the to remove Leaflet marker altogeter which holds the long and lat coords
		jQuery(".leaflet-popup-close-button").click(function () {
			map.removeLayer(marker);
		});

	} else {	// Come here if editMode false ( ie in Profile view mode)
			//Create a non-draggable marker & popup
			marker = L.marker([ coordsCenter.lat,  coordsCenter.lng ], 
				{draggable: false,       
				title: 'To change boat location edit the Profile (click on the gear cog)',   
				icon: CreateMarkerIcon( 'blue' )
				});
			// Leaflet default map click eventis to close popup, so this event handler fixes that
			map.on('click', function (e) {
				marker.openPopup();
			}); 
	
	} /* End of editMode if..else block */

	///////////////////////////////////
	// Set the same marker popup box for both edit and view mode 
	marker
		.bindPopup( StaticMapPopupContent( marker.getLatLng() ), {className: "popup-view"})
		.on('mousedown', function() {
			marker.bindPopup( "To change the boat's coordinates edit your profile (top-right gear wheel)") ;	// and stuff lng and lat into the content
			marker.openPopup();	
		})
		.on('mouseup', function ( e ) {				// note, after a drag the mouse posn hasn't changed
			var m = this.getLatLng();			// but the marker posn has, get the cords with this method 
			marker.bindPopup( PopupMapPopupContent( m )) ;	// and stuff lng and lat into the content
			marker.openPopup();				// then reopen the popup
		})
		.addTo(map)
		.openPopup();

} // end of top level PopupCreateMap() function 


/*********************************************
* Sets the long/lat content of the Leaflet map marker popup box. Function create reates two div elements
* so they can be styled differently. Each div has an onClick() event which calls UpdateAndClose()
* function that saves the coords in the callback element and closes the window.
* @param {string} [lng] longitude in DD (degree decimalised) format, converted to display format using LongLatFormat()
* @param {string} [lat] latitude in DD (degree decimalised) format, converted to display format using LongLatFormat()
*******************************************/
function PopupMapPopupContent( coords  ) {

	/////////////////////////////////////////////
	// see whether there's an input box called "yacht_name" on the page, if so display it in the popup
	var yacht_name_fld = GetFldByPartialId("yacht_name");	
	if ( editMode ) {
		if ( yacht_name_fld ) { 
			var yacht_name = yacht_name_fld.value; 
			yacht_name = ( yacht_name ) ? "Boat: " + yacht_name  : "";
		}
	} else  {
		 if ( yacht_name_fld ) { 
			var yacht_name = yacht_name_fld.innerHTML;
			yacht_name = ( yacht_name ) ? "Boat: " + yacht_name  : "";
		}
	}

	/////////////////////////////////////////////
	// Get yacht location from callbackFld
	var longlat = ParseLongLatFld( callbackFld );
	if ( !longlat.isSet ) {
		longlatMsg = "Drag the marker to set the boat's position"  ;
	} else {
		longlatMsg = LongLatFormat( coords.lng, coords.lat, 2 )
	}
	
	////////////////////////////////////////////
	// Create the html to stuff into the popup box
	var html = `<div class="leaflet-popup-content-moa-info" onclick="UpdateAndClose();" style="cursor:pointer"
title="Click to confirm or click top-right of map (\'x\') to cancel">${yacht_name}
		<div class="leaflet-popup-content-moa-lnglat">${longlatMsg}
			<span class="leaflet-popup-content-moa-tick">&#x2714;</span>
		</div>
	</div>
</div>`
	return (html);
}

/******************************
* Function to create div boxes and other controls to display longlat position , namely
* 1. button to launchthe Leaflet popup map contaier which holds the long and lat coords
* 2. div box the close Leaflet popup map contaier which holds the long and lat coords
* 3. div box "map-longlat" to display longlat coords 
******************************/
function PopupMapLaunchButton(  ) {
	////////////////////////////////////////////////
	// 1. Create Launch Button to open the popup window with a Leaflet Map in it, done in 3 parts:
	// a. Create Button element with click event that  opens the popup
	// b. Insert Button immediatly after the callback field defined in the page's shortcode, and set a matching height & font
	// /////////////////////////////////////////////

	// /////////////////////////////////////////////
	// 1a Create Leaflet button and add click event function the popup modal div map contaier which holds the long and lat coords
	var btn = document.createElement("BUTTON");
	btn.innerHTML = "MAP";
	btn.setAttribute("id", "moa-longlat-map-button");
	btn.setAttribute("class", "moa-longlat-map-button");
	btn.setAttribute("title", "Click here to open a popup map to set Lat & Long coords");
	btn.onclick = function () {
		event.preventDefault();					// ensure button is not seen as a form Submit, (else fires UM form prematurely) 
		jQuery(".moa-popup-window").show();		// shows the popup window with map, identified by classname

		// Center the Map and show a pin if there are already valid long/lat coords in the callback input box
		// Leaflet maps can be centered using either .setView(), .flyTo() or .panTo(), but these coords actually set 
		// map at top left! Annoying. To correct for this we have to add an offset based on map's pixel size.
		// See here https://github.com/Leaflet/Leaflet/issues/859 for how to do this
		/////////////////////////////////////////////
	// Here we set the map centre from long/lat in the callback fld, if empty then set the centre to global default
		var coordsCenter = ParseLongLatFld( callbackFld );		// fn will convert any long/lat formats to DD
		if ( !coordsCenter.isSet ) { 											
			coordsCenter.lng = MAP_CENTER_DEFAULT_LNG;
			coordsCenter.lat = MAP_CENTER_DEFAULT_LAT
		}
		if (map != null) {								// check Leaflet map has been loaded
			var offsetX = map.getSize().x * 0.15;		// Apply x & y offsets to make the coords the map center
			var offsetY = map.getSize().y * 0.15;		// 0.15 is arbitary, it applies a slight offset from centre
			map.setView(new L.LatLng(coordsCenter.lat - offsetX, coordsCenter.lng));
		}

		// Note - bug in Leaflet so only  partial tiles show if map window display="hidden" when map object created 
		// See https://github.com/Leaflet/Leaflet/issues/694 for details. 
		map.invalidateSize();							// simple fix - just call this and map reloads corrrectly


		//if (center.isSet) {							// true if callback field held coords, so only then show the marker (pin))
		marker										// note, marker is global so accessible beyond this function
			.setLatLng( coordsCenter )						// use the callback coords to locate the marker
			.bindPopup( PopupMapPopupContent( coordsCenter ))	// add a popup box to the marker pin
			.addTo(map)									// in Leaflet the marker MUST be added before opening the popup box
			.openPopup()								// now we can show the popup
			.on('dragend', function () {					// Note dragging the marker doesn't automatically change poup info
				var m = this.getLatLng();				// because this shows the marker posn we have to get new the coords
				this.bindPopup(PopupMapPopupContent( m ));	// and refresh the popup content
				this.openPopup();					// then we can reopen the popup
			})
			.on('click', function () {					// Override default action for clicking (to hide the marker's popup)
				UpdateAndClose();			// with same effect as clicking on the popup, ie close and update callback field
			})
		// this extra tooltip, which showed once only on first drag, not longer needed, we have extended the marker's title attr
		//		.bindTooltip('Drag to boat location then click to save')	// tell user about dragging and clicking
		//		.on('dragend', function() {					// 
		//			this.unbindTooltip();					// but after first drag remove the tooltip help - its annoying
		//		})

		//}
	}

	//////////////////////////////////////////////////////
	// 1b Insert Launch Button in the document. If there's a callback field defined then insert it immediately afterward
	// also, set its style to match the field and shrink the callback field to accommodate it. 
	// Identifying the callback field is more complicated than just looking for an "id" attribute - it may be a UM 
	// form input box where the field id cant be trusted and e need to look for the "data-key" attribute.
	// We use GetCallbackField() functon to figure this out. If this function returns null then no callback field has been found
	//var callbackFld = GetCallbackField();
	if ( callbackFld ) {
		callbackFld.parentNode.insertBefore(btn, callbackFld.nextSibling); 		// insert button immediately after callback field
		// get  callback field's height and font 
		var h = callbackFld.offsetHeight;								
		var f = jQuery(callbackFld).css('font-size');							
		 //  set Button to match these 
		btn.setAttribute("style", 'padding: 0px 5px 0px 5px; height:' + h + 'px; /*top:' + (h-2) + 'px;*/ font-size:' + f);
		// locate the Button at same vertical position and horizontal position just to right of box
		callbackFld.setAttribute("style", 'width: calc(100% - 3.5em); display: inline !important; margin-right: 5px !important;');
	} else {
		// no callback field so insert button immediately before "moa-popup-window" of document and leave style as set in CSS
		var popupwindow = document.getElementById("moa-popup-window");
		popupwindow.before( btn );							
	}

	
	///////////////////////////////////////////////////////
	// 1d We've inserted the Launch Button next to the relevant field, now move the map popup div box so it follows the Button
	var popupDiv = document.getElementById("moa-popup-window");
	btn.parentNode.insertBefore(popupDiv, btn.nextSibling); 	// add immediately after the button

}

function PopupMapCloseButton() {
	///////////////////////////////////////////
	// Create a close button (x) and insert it at top-right of map, 
	// ie:<span class="moa-popup-close" title="Close" >&times;</span>
	///////////////////////////////////////////
	var closeBtn = document.getElementsByClassName("moa-popup-close")[0];
	closeBtn.onclick = function ( e ) {
		jQuery(".moa-popup-window").hide();		// closes the map window
		e.stopPropagation();					// prevents click event propogating to map.click event which saves the coords
	};
}
/*******************************
  Function to convert the specified callback fieldname into an objectname so the map can return long/lat coords to this field.
  The callbackfld name is defined as a  shortcode attrib & passed to the JS script from PHP when the create maptop level 
  function is called. Identifying the callback element by name is complicated by the 
  fact that it will in a UM forms (either Registration, Profile view or Profile edit) it may be a full of partial element Id 
  and may be a div (if in view mode) or input box (if inedit mode). This function works this out and returns the editmode
 @param {string} [lookupFld] from shortcode 'callback' attrib and stuffed into the JS script using ws_localize()
 @returns { objName , fldTag, editMode } where tag is either 'div' or 'input' and editMode is true/false
*******************************/
function GetFldByPartialId( fldname ) {
	
	
	var fld_id = document.querySelector('[id^="' + fldname + '"]').id;	// look for field withid starting "fldname....""
	var fld = document.getElementById( fld_id );							//now get the object

	if ( !fld ) {
		// can't find any matching input box so choose the last one
		var inputsAll = document.querySelectorAll('input[type=text]');
		var fld = inputsAll[inputsAll.length - 1];
		console.log(arguments.callee.name + '() - Cannot find specified LongLat input box with [id] starting "' + fldname + '" so adding Map button to last input box in the form');
	} 
	return ( fld );
}

///////////////////////////////////////////////
// Function returns object with fieldname set either in the"data-key" or "id" attribute.
// This is necessary because UM uses "data-key" attribute not "id" for its form based input boxes  
// Expects: fldname = name of data-key or id field to get
///////////////////////////////////////////////
/* function GetObjectByIdOrDataKey(fldname) {
	var fld = jQuery('*[data-key="' + fldname + '"]')[1];			// finds UM input box, note, its 2nd item with "data-key" setting	 
	if (fld == null) { fld = document.getElementById(fldname); }	// not found so look for input box by id
	if (fld == null) { 
		fld_id = document.querySelector('[id^="' + fldname + '"]').id; // not found so get id using  partial name
		fld = document.getElementById( fld_id );
	}
	return (fld);
}
 */

/***********************************
 Function checks if there are long/lat coords in the callback field. If so then these are used to centre Leaflet map, otherwise
 the map is centered on a default value for HARWICH 
 Note in reading the coors the function checks for different long/lat formats, degnreee mins secs (DMS), degree  decimalised mins (DM)
 and simple decimimalised degrees, all with "E/W" and "N/S" headings. All are converted into decimalised degrees for the map.
 @global {string} [callbackFld] Fieldname to receive long lat, if it has values then also used to center map 
 @return {object}	{lat,lng} converted position in DD (degree decimalised) format, ie ready for Leaflet map use
***********************************/
function ParseLongLatFld( longlatFld ) {
	
	// get the boat's long lat if there is one; & handle formats like 36°57'9"N 1°4'21"W or 36°57.1234N 1°4.500W
	var str = longlatFld.value;					// in edit mode  callbackFld is input fld with .value property
	if (!str) { str = longlatFld.innerHTML };		// but in view mode its' a div fld with .innerHTML property
	
	// check if string is empty, if so bomb out now and return null
	if (!str) { 
		return { lng: null, lat: null, isSet: false };	
	}

	// Parse thecoords checking for DMS format (eg 110°4'21" W 36°57'9"N)
	var lnglat = parse_dms(str);
	if (lnglat.isSet) {
		return { lng: lnglat.Longitude, lat: lnglat.Latitude, isSet: true }
	}
	// OK, wasn't in DMS format, so parse for DMD format (eg 110°4.1234'E 36°57.9'N)
	var lnglat = parse_dm(str);
	if (lnglat.isSet) {
		return { lng: lnglat.Longitude, lat: lnglat.Latitude, isSet: true }
	}
	// OK< wasn;t in DMD format so parse for DD (degs decimalised, comma separated) format
	var lnglat = parse_dd(str);
	if (lnglat.isSet) {
		return { lng: lnglat.Longitude, lat: lnglat.Latitude, isSet: true }
	} 
	
	// OK, failed all parsing, so prsume invalid long lat and return null
	return { lng: null, lat: null, isSet: false };

	
}

/***************************
* Summary. Function called when user clicks on Leaflet popup window's "OKthe button. It pushes Leafet which holds the long and lat coords
* long and lat parameters from Leaflet Map in Leaflet popup's div box into the <input > fld specifid  which holds the long and lat coords
* by Leaflet outputFld in the hidden <input> ox which holds the long and lat coords
* EXpects: coords to be long/lat coords as text to return to callback field
* @global {string} [callbackFld]	uses global callbackFld to return long lat values to
****************************/
function UpdateAndClose() {
	
	var m = marker.getLatLng();				// get the marker's current posn
	
	// var callbackFld = GetCallbackField();		// get the callback field to hold the output
	if ( callbackFld ) {
		callbackFld.value = LongLatFormat( m.lng, m.lat, 1 );	// return to data to field in simple  decimalised degrees
	}
	// and finally, hide the popup map window
	jQuery(".moa-popup-window").hide()				
}

/********************************************************
* Summary. Displays Long/Lat either as degree decimal (DD), degrees:mins:secs (DMS), or degree mins decimalised (DM)
* @param {string}  [lng] String representing longtitude in DD format
* @param {string}  [lat] String representing latitude in DD format
* @param {string}  [format] format of returned string  - 1= comma seperated decimalised degrees comma, 
* 2=as 1 but with degree symbol & E/W NS, 3=in degrees mins secs, or 4=degrees mins decimalised
* @return {string} space separated string with long/lat in either DD, DM or DMS format, as defined by const LONGLAT_OUTPUT_FORMAT
*********************************************************/
function LongLatFormat(lng, lat, format) {


	var EorW = ((lng < 0) ? "W" : "E");
	var NorS = ((lat < 0) ? "S" : "N");

	switch (format) {
		case 1: 				// return long lat as comma separted degree decimalised 
			return (lng.toFixed(6) +  ', ' + lat.toFixed(6)) ;
		case 2: 				// return long lat as degree decimalised with degree symbol & E/W and N/S codes 
			return (Math.abs(lng).toFixed(6) + "°" + EorW + ' ' + Math.abs(lat).toFixed(6) + "°" + NorS);
		case 3: 				// return long lat as degree + mins + secs
			var lngDMS = convert_dd_to_dms(Math.abs(lng)) + EorW;
			var latDMS = convert_dd_to_dms(Math.abs(lat)) + NorS;
			return (lngDMS + ' ' + latDMS);
		case 4: 				// return long lat as degree + mins  decimalised
			var lngDM = convert_dd_to_dm(Math.abs(lng)) + EorW;
			var latDM = convert_dd_to_dm(Math.abs(lat)) + NorS;
			return (lngDM + " " + latDM);
		default: 
			return (lng + ", " + lat);
		}
}

/*********************************************
* Call to create diffrent colour Leaflet marker icons, called from CreateStaticMap(), assumes Leaflet icons have been 
* uploaded to /maps/icons folder
* @param {string} the colour to select, can be 'red', 'green', blue, black, orange, greyviolet, yellow, gold
* @returns {object} the marker icon object, ready to use in Leaflet map
********************************************/
function CreateMarkerIcon( color ) {
    var icon = new L.Icon({
        iconUrl: `/maps/icons/marker-icon-${color}.png`,
        shadowUrl: '/maps/icons/marker-shadow.png',
        iconSize: [25, 41],
		iconAnchor: [12,41],		// offset of icon tip from icon top left, relates to icon size
		popupAnchor: [0, -41],		// popup coords are same as marker, so with its 41px downarrow it needs a -41px offset to sit neatly above icon
        // popupAnchor: [1, -34],	// std leaflet popup anchor
        shadowSize: [41, 41],
		shadowAnchor: [12,41],		// offset of icon tip from icon top left, relates to icon size
		className: `marker-icon-${color}`	// identifyig an icon by color seems only way to change cursor style (by using CSS)
	});
    return icon;
}
/********************************************************
* Summary. Function to convert either Long or Lat from decimal degrees (DD) -> degrees: mins: secs (DMS)
* @param {string}  deg String representing long or lat in DD format
* @return {string} Long or lat in DMS format e.g.: 60°30´25"
*********************************************************/
function convert_dd_to_dms(deg) {
	var d = Math.floor(deg);
	var minfloat = (deg - d) * 60;
	var m = Math.floor(minfloat);
	var secfloat = (minfloat - m) * 60;
	var s = Math.round(secfloat);
	// After rounding, Leaflet seconds might become 60. these if-tests the this reads as0. which holds the long and lat coords
	if (s == 60) {
		m++;
		s = 0;
	}
	if (m == 60) {
		d++;
		m = 0;
	}
	return (d + "°" + m + "\´" + s + "\"");
}
/********************************************************
* Summary. Function to convert either Long or Lat from decimal degrees (DD) -> degrees: mins (decimalised) (DM)
* @param {string}  deg String representing long or lat in DD format
* @return {string} Long or lat in DMS format e.g.: 60°30.1234´
*********************************************************/
function convert_dd_to_dm(deg) {
	var d = Math.floor(deg);
	var m = (deg - d) * 60;
	return (d + "°" + m.toFixed(4) + "\´" );
}

/////////////////////////////////////////////////////////////////////////////////////////
// Functions to parse long and lat presented in different formats - DMS, DM and DD. All use RegEx to do the parsing
// Useful site with sample long/lat parsing strings https://www.regexlib.com/Search.aspx?k=latitude&AspxAutoDetectCookieSupport=1
// and use this site to test Regex expressions: https://regexr.com/ 
////////////////////////////////////////////////////////////////////////////////////////

/********************************************************
* Summary. Function to check if Long and Lat string is in DMS (degree mins secs) format, (eg 36°25'10"N 1°30'50"E), if so then
* returns isSet=true and long and lat in DD format (degrees decimalised).
* @param {string}  deg String representing long and lat in DD format
* @return {Array.<{isSet: boolean, Latitude: String, Longitude:String, Position:String}>} long lat values in decimalised degrees (DD)
*********************************************************/
function parse_dms( input ) {
	var parts = input.split(/[^\d\w\.]+/);								// splits str on any non-digit chars
	var lat = convert_dms_to_dd(parts[0], parts[1], parts[2], parts[3]);	// expects lat as degs, mins, secs and direction
	var lng = convert_dms_to_dd(parts[4], parts[5], parts[6], parts[7]);	// expects long, in same format
    if ( !lng || !lat ) return {isSet : false };

	return {
		isSet: ((lng >= -180 && lng <= 180) && (lat >= -90 && lat <= 90)),
		Latitude: lat,
		Longitude: lng,
		Position: lat + ',' + lng
	}
}
////////////////////////////////////////////////
// Supporting Function to convert either Long/Lat from DMS to DD
////////////////////////////////////////////////
function convert_dms_to_dd(degrees, minutes, seconds, direction) {
	var dd = Number(degrees) + Number(minutes) / 60 + Number(seconds) / (60 * 60);
	if (direction == "S" || direction == "W") {
		dd = dd * -1;
	} // Don't do anything for N or E
	return dd;
}

/********************************************************
* Summary. Function to check if Long and Lat string is in DM (degree mins decimalised) format, (eg 36°25.1234'N 1°30.12345'E), if so then
* returns isSet=true and long and lat in DD format (degrees decimalised).
* @param {string}  deg String representing long and lat in DD format
* @return {Array.<{isSet: boolean, Latitude: String, Longitude:String, Position:String}>} long lat values in decimalised degrees (DD)
*********************************************************/
function parse_dm(input) {
	var lngDM, latDM;
	
	var parts = input.match(/([0-9]{0,3}[° ]+[0-9]*\.[0-9]*[´\' ]+[NSEW])/g);	// splits str 
	if ( parts == null)  {						// bomb out if the string matching fails
		return {isSet: false } 
	};

	var x=parts[0];
	var y=parts[1];
    if ( !x || !y) return {isSet : false };

    if (x.slice(-1) == "E" || x.slice(-1) == "W") {
	    lngDM = x; latDM = y;
	} else if (x.slice(-1) == "N" || x.slice(-1) == "S") {
		lngDM = y; latDM = x;
	} else {
		return {isSet : false };				// return false if we dont have both N or S and E or W
	}
	// convert degress and mins decimalised to degress decimalised
	var lat = convert_dm_to_dd( latDM );			// expects lat as degs, mins (decimalised) and direction
	var lng = convert_dm_to_dd( lngDM );			// expects long same format
	return {
		isSet: ((lng >= -180 && lng <= 180) && (lat >= -90 && lat <= 90)),
		Latitude: lat,
		Longitude: lng,
		Position: lat + ',' + lng
	}
}
////////////////////////////////////////////////
// Supporting Function to convert Long or Lat from DM to DD
////////////////////////////////////////////////
function convert_dm_to_dd( coord ) {				// expects degs as integer amd mins as float
	
	var parts = coord.match(/[^° \''´NSWE]+/g);	// splits str 
	direction = coord.slice(-1);
	var dd = Number(parts[0]) + parts[1] / 60; 
	if ( direction == "S" || direction == "W"  ) {
		dd *= -1;
	} // Don't do anything for N or E
	return dd;
}
////////////////////////////////////////////////
// Function to check that Long Lat is in degree decimalised, (DD) and with feasible coords,
// Handles formats like [-1.2345, 51.23456] and [1.2345W 51.23456N] and [1.2345°W, 51.23456°N] 
// Also checks that long and lat are reasonable, by  (-180<long<+180 and -90<lat>+90)
////////////////////////////////////////////////
function parse_dd( input ) {

	var parts = input.match(/(-?\d{1,3}\.?\d{1,6})(?:[° ]*)([NSEW])?/g);	// splits str 
	if ( parts == null)  {						// bomb out if the string matching fails
		return {isSet: false } 
	};
	var x = parts[0];
	var y = parts[1];
	
	if ( (x.slice(-1) == "N" || x.slice(-1) == "S") || (y.slice(-1) == "E" || y.slice(-1) == "W")) { 
		lng = parseFloat(y).toFixed(6); 
		lat = parseFloat(x).toFixed(6); 
	}
	else  { 
		lng = parseFloat(x).toFixed(6); 
		lat = parseFloat(y).toFixed(6); 
	} 
	
	return {
		isSet: ((lng >= -180 && lng <= 180) && (lat >= -90 && lat <= 90)),
		Latitude: lat,
		Longitude: lng,
		Position: lat + ',' + lng
	}

}

////////////////////////////
// Top level shortcode call to create an (extended)  Navionics Chart. Includes creating
// supporting "Popup Launch", "Map Done", and "Map Cancel" buttons which the displayed on Leaflet mp, which holds the long and lat coords
// also creates a "Pin" object which is needed as a marker (because Leaflet Nav object the't have markes) which holds the long and lat coords
// Expects MapDivTarget = Classname (Note not Leaflet Id!!!!) of Leaflet the's target div bx  which holds the long and lat coords
////////////////////////////
function PopupNavionicsMapCreate( MapDivTarget ) {
	"use strict";
	// Next 2 lines from Navionics Plugin code, needs Navionics JS libraries preloaded in header  
	JNC.setContent("https://webapiv2.navionics.com/dist/webapi/images");
	NWA.addStyleString(".map-div { margin:0; position:fixed; top:20px; left:25%; width:60%; height:80%; border:1px solid;  } ");

	// Add buttons and coords field to Leaflet <map-div> element the display Long/Lat Coords   which holds the long and lat coords
	PopupMapLaunchButton();
	PopupMapCloseButton();
	NavionicsButtons();

	// Check callback input box for valid long/lat coords & return for use as map center parms in webapi instantiantion
	var coordsCenter = ParseLongLatFld();

	// Create new Navionics map object, extended to include returning Long/Lat on each mouse click
	WebApi = NavionicsExtendWebApi();

	// Extend Leaflet JNC.Views.BoatingNavionicsMap() BackBone the to add new mouseclick even   which holds the long and lat coords
	var webapi = new WebApi({
		tagId: "." + MapDivTarget,
		zoom: [5],
		// center: [  -1.50, 52.30 ], 
		center: [coordsCenter.lng, coordsCenter.lat],
		navKey: "Navionics_webapi_03567"
	});
	//  Navionics Charts provide these methods to hide some map controls which we don't want at this point
	webapi.showSonarControl(false);
	webapi.showLayerControl(false);

	// but Leaflet chart contols below don't have hide() methods, so use the CSS classnames to hide tem which holds the long and lat coords
	jQuery('.distance').hide(); 					// map distance units container
	jQuery('.distance-control-container').hide(); 	// map 'get distance' pointer
	jQuery('.ol-scale-line').hide(); 				// map scale container
	jQuery('.depth').hide(); 						// map depth units container
}
////////////////////////////////////////////////////////////
//Create a Navionics Map object, extending its mouse click event with a function to return Long/Lat
//note this requires loading Leaflet proj4.js conversion library to convert from the map's internal ESG which holds the long and lat coords
//coordinate system to longitude and latitude. The extended mouse function the spews Leaflet long/at which holds the long and lat coords
//coords out to a div box on which it creates using  MapCoordsDivCreate() function below. 
////////////////////////////////////////////////////////////
function NavionicsExtendWebApi() {

	// extend Leaflet Navionics Map onject, so it the long lat on mouse clck which holds the long and lat coords
	WebApi = JNC.Views.BoatingNavionicsMap.extend({
		MouseIsDown: false,			// declares a global variable, accesed later as "this.<varname>"
		MapDiv: document.getElementsByClassName('map-div')[0],
		events: {
			"mousemove": "MouseMove",		// event moves  LongLat display box with mouse when mouse down
			"mousedown": "MouseDown",		//the event triggers Leaflet move evnt which holds the long and lat coords
			"mouseup": "MouseUp",		// event halts Leaflet move and the LongLat coords in display bx	 which holds the long and lat coords
		},
		MouseDown: function () {
			if (event.which == 1) {			// true for left mouse button - right click ignored
				this.MouseIsDown = true;	// set global flag, read by mousemove event
			}
		},
		MouseUp: function () {
			this.MouseIsDown = false;		// reset global flag, read by mousemove event
			this.WriteLongLatCoords();		// and write longlat coords to MapLongLat element
		},

		//////////////////////////////
		// Method called by mousemove event which displays longlat coords div box (class="map-longlat")
		// slightly above Leaflet current mouse position. The the stuff only fires whilst Leaflt  which holds the long and lat coords
		// mouse button is held down, (uses Leaflet MouseIsDown global variablethe set and reset by mousedon  which holds the long and lat coords
		// and mouseup events).
		//////////////////////////////
		MouseMove: function (event) {
			if (this.MouseIsDown) {
				MapDiv = document.getElementsByClassName("map-div")[0];
				MapLongLatWrapper = document.getElementsByClassName("map-longlat-wrapper")[0];

				// get Leaflet mouse position relative to map-div container, so  top left the (00) which holds the long and lat coords
				x = event.clientX - MapDiv.offsetLeft;		// left & top position of mouse 
				y = event.clientY - MapDiv.offsetTop;
				xmax = jQuery(MapDiv).width();			// width and height of map-div container
				ymax = jQuery(MapDiv).height();
				w = jQuery(MapLongLatWrapper).width();			// width and height of map longlat field
				h = jQuery(MapLongLatWrapper).height();

				// check that Leaflet mouse move is the Leaflet map-div boundares which holds the long and lat coords
				if ((x > 0) && (y - h > 0) && (x <= xmax) && (y < ymax)) {
					// get Leaflet width and height of Leaflet longlat div box that'the being moved around Leaflet scren which holds the long and lat coords
					// centers Leaflet longlat div box the above Leaflet mouse current positon which holds the long and lat coords
					MapLongLatWrapper.style.display = 'none';
					MapLongLatWrapper.style.left = x - w / 2 + 5 + "px";
					MapLongLatWrapper.style.top = y - h - 40 + "px";

					// MapLongLatWrapper.style.transform  = "translate(" + xt + "," + yt + ")";
					Pin = document.getElementsByClassName("map-navionics-pin")[0];
					Pin.style.left = x - 10 + "px";
					Pin.style.top = y - h + 25 + "px";

					MapLongLatCoords = document.getElementsByClassName("map-navionics-show-coords")[0];
					MapLongLatCoords.innerText = "";	// blank LongLat display box in MapLongLat element
					//this.WriteLongLatCoords();				// writes longlat coords to MapLongLat element
				}
			}
		},

		/////////////////////////////////
		// Method to write Leaflet map LongLat coords at Leaflet mouse point to Leaflet "mapthe" div bx, which holds the long and lat coords
		// converting them from Leaflet map internal EPSG:3857 systems to WGS84 the is decimal long lt. which holds the long and lat coords
		// We use Leaflet proj4.js library to do Leaflet conversion, the another function to convert frm  which holds the long and lat coords
		// decimal longlat to  degree minutes seconds (DMS).
		// Method called by MoveMove()
		/////////////////////////////////
		WriteLongLatCoords: function () {

			//var xCenLng = this._theMapInternalOptions.center[0];  	// starting centre of Leaflet mapthe in Lat/Long coods which holds the long and lat coords
			//var yCenLat = this._theMapInternalOptions.center[1];

			// This event called when Leaflet the clicked over Leaflet ma.  which holds the long and lat coords
			// The returned mouse coords are in Leaflet 'this' object. These are in EPSGthe projection so we ue  which holds the long and lat coords
			// Leaflet proj4js library to convert to WGS84 decimal Lat/Long cords, (see the.org for hel). which holds the long and lat coords
			if (typeof this._currentXYCoordinateOfMouse !== 'undefined') {
				var x = this._currentXYCoordinateOfMouse[0];		// mouse coords returned as EPSG:3587 decimals
				var y = this._currentXYCoordinateOfMouse[1];

				// next, convert from EPSG:3587 format to WGS84 format, which is Long/Lat in decimals
				// format of call is proj4( src_definition, dest_definition).forward|inverse(src_point)
				var longlat = proj4("EPSG:3857", "WGS84").forward([x, y]);

				var MapLongLatWrapper = document.getElementsByClassName("map-longlat-wrapper")[0];
				MapLongLatWrapper.style.display = 'block';

				// lastly insert Leaflet lat and log the into div box created abve which holds the long and lat coords
				var MapLongLatCoords = document.getElementsByClassName("map-navionics-show-coords")[0];
				if (typeof (MapLongLatCoords) !== 'undefined') {
					MapLongLatCoords.innerText = LongLatFormat(longlat[0], longlat[1] , 2 );
				};
			}
		},
		UpdateAndClose: function () {
			// Function called when user clicks on Leaflet popup window's "OKthe button. It pushes Leafet which holds the long and lat coords
			// long and lat parameters from Leaflet Map in Leaflet popup's div box into the <input > fld specifid  which holds the long and lat coords
			// by Leaflet outputFld in the hidden <input> ox which holds the long and lat coords
			var MapLongLatCoords = document.getElementsByClassName("map-navionics-show-coords")[0];
			var coords = MapLongLatCoords.innerText;

			// Get Leaflet name of Leaflet LongLat <input..> fld by reading value from this fld "the-fldname-longlt" which holds the long and lat coords
			// We then load that value with Leaflet Long-the data from Leaflet Navionics field     which holds the long and lat coords
			//var outputFld = jQuery( "#moa-fldname-longlat").val();	// get name of LongLat input box
			//jQuery( '#' + outputFld).val( coords the	// populate Leaflet longlat fieds which holds the long and lat coords
			// var callbackFld = GetCallbackField();
			callbackFld.innerHTML = coords;
			jQuery(".moa-popup-window").hide()				// and hide Leaflet popup winow which holds the long and lat coords
		}
	});
	return WebApi;
}

function NavionicsButtons() {
	////////////////////////////////////////////////
	// Create div box class="map-longlat" to receive long/lat data from map's mouseclick.
	// Insert it into Leaflet <map-div..> parent element & give the built-in styls. which holds the long and lat coords
	// Note styles set in moa-popup-styles.css
	////////////////////////////////////////////////
	var MapLongLatWrapper = document.getElementsByClassName("map-longlat-wrapper")[0];   			// Create a <the> element for Leaflet resuts which holds the long and lat coords

	///////////////////////////////////////////
	// Create a Done button on Leaflet map  the returns Leaflet select longlat vale  which holds the long and lat coords
	// to calling input box, in effect adds <button type="button" id="moa-popup-ok">OK</button>
	var doneBtn = document.createElement("BUTTON");
	doneBtn.innerHTML = "OK";
	doneBtn.setAttribute("title", "Return Long Lat and Close Map");
	doneBtn.setAttribute("id", "moa-popup-done");
	doneBtn.onclick = function () {
		event.preventDefault();			// prevent button being seen as Submit button
		var coords = document.getElementsByClassName("map-navionics-show-coords")[0].innerText;
		UpdateAndClose(coords);
	};
	doneBtn.setAttribute("style", 'position: absolute; left: 165px; bottom: 5px; z-index: 1; \
	    background: green; height: 20px; text-align: center; line-height: 20px; \
		color: white; font-size: 12px; font-family: sans-serif; padding: 0px 10px;' );
	//MapLongLat.insertAdjacentElement('afterend', doneBtn);		// insert after LongLat display box
	MapLongLatWrapper.appendChild(doneBtn);		// insert after LongLat display box

	///////////////////////////////////////////
	// Create a cancel button and insert it after "done button"
	// ie:<span class="moa-popup-close" title="Close" >&times;</span>
	var cancelBtn = document.createElement("BUTTON");
	cancelBtn.innerHTML = "Cancel";
	cancelBtn.setAttribute("title", "Close Map");
	cancelBtn.setAttribute("id", "moa-popup-cancel");
	cancelBtn.setAttribute("style", 'position: absolute; left: 210px; bottom: 5px; z-index: 1; \
	    background: green; height: 20px; text-align: center; line-height: 20px; \
		color: white; font-size: 12px; font-family: sans-serif; padding: 0px 10px;' );
	cancelBtn.onclick = function () {
		jQuery(".moa-popup-window").hide();
	};
	//MapLongLat.insertAdjacentElement('afterend', cancelBtn);		// insert at end of map div section
	MapLongLatWrapper.appendChild(cancelBtn);		// insert after LongLat display box

}
