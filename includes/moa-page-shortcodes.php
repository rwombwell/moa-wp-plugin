<?php

define( 'MOA_MEMBER_VIRTUAL_FOLDER', 'members-area' ); 	// virtual folder used in following functions to view Journals, thus protecting /journals folder
define( 'MOA_MEMBERS_ONLY_FOLDER', 'journals');				// folder under WP root where journals are kept,( note )this has a .httaccess file with "deny all" securing it) 

/*******************
* MOA JOURNAL WIDGET-  WIDGET SIDEBAR to OPEN JOURNAL depending on user roles
* Shortcode to include in page that offers users a view the latest Journal. It checks the user's role
* if this is MOA Member or MOA Admin then the user gets offered a PDF of the entire journal. Other users see a PDF of the 
* 3 page abridged journal embedded in the page together with a few sample articles from recent journals.
* NB, this Shortcode function previously done by PHP Everywhere, which stopped using shortcodes from v3 onwards
********************/

add_shortcode("moa-journal-widget", "moa_journal_widget_fn");
function moa_journal_widget_fn( $atts, $content = null) {
	
	// Code to get the most recent MacJournal PDF in the Journals folder
	$pdf_template = ABSPATH  . MOA_MEMBERS_ONLY_FOLDER . '/MacJournal*.pdf'; 
	$file_arr = glob( $pdf_template );
	rsort( $file_arr);
	
	if ( $file_arr[0] == null ) {
		return "No Journals in the " . MOA_MEMBERS_ONLY_FOLDER . " area";
	} else {
		// Assumes the filename like "MacJournal-2023-Spring.pdf"
		$pdf_most_recent= basename( $file_arr[0]);

		// DEBUG ERROR ON glob() fucntion not returning files in right order
		//$pdf_most_recent = "MacJournal-2023-Spring.pdf";

		$year 			= substr( $pdf_most_recent ,11, 4);	// eg "2023"
		$season 		= substr( $pdf_most_recent ,16, 1);	// eg "S" or "A"
		$pdf_path 		= site_url() . '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/'. $pdf_most_recent ;
		$cover			= $year .  ( (substr($season,0,1) == "S") ? "S" : "A") . '-Cover.jpg';
		$cover_path		= site_url() . '/articles/' . $cover;
		$pdf_page_ref 	= site_url() . "/journal-pdf-view/?pdf-year=" . $year . "&pdf-season=" . $season;
		
		// build an output with a link to the /journal-pdf-view page, with a querystring giving season and year
		$output = 
		'<a href="' . $pdf_page_ref .'">' . 
			'<img src="' . $cover_path . '" 
				alt="Journal" class="attachment-0x0 aligncenter journal-cover" 
				style="max-width: 300px;width:70%; height:auto; box-shadow:5px 5px 10px #c5c3c3;" 
				title="Click here to view the Full journal as a PDF (Members only)">
			</img>
		</a>';

		return $output;
	}
}

/*******************
* MOA JOURNAL VIEW WITH ACCESS CONTROL
* EMbedded in page and called by JOURNAL WIDGET 
********************/
add_shortcode("moa-journal-view-with-access", "moa_journal_view_with_access_fn");
function moa_journal_view_with_access_fn( $atts, $content = null) {
	
	$year			= get_query_var( "pdf-year","");
	$season			= get_query_var( "pdf-season","");
	$cover			= $year . $season . '-Cover.jpg';
	$pdf_file 		= "MacJournal-" . $year . "-" . (($season == "S") ? "Spring" : "Autumn") .".pdf";
	$contents		= $year . $season . '-Contents.pdf';
	$cover_path		= '/articles/' . $cover;
	$contents_path	= '/articles/' . $contents;
	$pdf_path 		=  urldecode( site_url() . '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/'. $pdf_file ) ;
	
	$role			= um_user('role'); 			// get the users Ultimate Member role
	$output 		= "";
	// check if the role is in the list we allow full access to 
	if( in_array ($role , array('um_moa-admin','um_moa-member' ))) {
	
		$output .= 
		'<p><a href="'. $pdf_path . '" target="_blank" rel="noopener noreferrer">
		<img src="'. $cover_path.'" 
			style="width:200px;height: auto; box-shadow:5px 5px 10px #c5c3c3;margin-right:20px;" 
			title="Click here to view the Full journal as a PDF (Members only)">
		</a> Click for the latest Journal in a new Window</p>' ;
		$output .=  
		'<p><a href="/?page_id=73">
		<img src="https://www.macwester.org.uk/images/club-library.png" 
			style="width:200px;height: auto; box-shadow:5px 5px 10px #c5c3c3;margin-right:20px;" 
			title="Click here to view the Full journal as a PDF (Members only)">
		</a>Click to see Club Library of past Journals (way back to 1967!)</p>';
	} 
	else {
	    $output .= '<p>As a non-Club member your view of Macwester Journal has been restricted to the Contents only, together with a couple of illustrative articles from recent journals, e.g.: </p>
		<ul>
			<li><a href="/articles/2020S-Seabird-Goes-Ultrasonic.pdf" target="_blank">Seabird goes Ultrasonic</a> (Mike Hotard from Spring 2020) </li>
			<li><a href="/articles/2019A-Mucking-about-on-a-Mac.pdf" target="_blank">Mucking about on a Mac</a> (from Autumn 2018) and </li>
			<li><a href="/articles/2018A-Passage-to-Red-Brook.pdf" target="_blank">Passage to Red Brook</a> (Robert Parmentier from Autumn 2019)</li>
			<li><a href="/articles/2023S-Sugar-Scoop-Stern-Keith-Calton.pdf" target="_blank">Retrofitting a Sugar Scoop Stern</a>(Keith Calton from Spring 2023)</li>
		</ul>
		<p>Below is an extract only of the latest Journal. To see its full contents, or to get access to all other Journals in the Archive you need to become a Club Member - go to <a href="/office/">Office</a> tab to Join</p>';
	    $output .= '<embed src="'.$contents_path.'" type="application/pdf" width="800" height="1600">';
	}
	return $output;
}

/*******************
* EMBEDDED PDF VIEWER -  allows PDFs to be viewed inside website page. 
* To use, add shortcode to a page which is called with  querystring .../?pdf={pdf-filename) urlencoded and without path
* This shortcode use the PDFJS jQuery code (as used in Journal Listing as Popup), but here we call it on $(document.ready(..), thus 
* without the popup dialog so it fills the container div in the main page
*
* To show highlights see this article https://stackoverflow.com/questions/35501417/how-to-search-with-pdf-js/39770115#39770115 
********************/
add_shortcode("moa-pdf-embedded-viewer", "moa_pdf_js_view_item_fn");
function moa_pdf_js_view_item_fn( $atts, $content = null) {

	$fname 		= get_query_var( "pdf","");
	$searchWord = get_query_var( "search","");
	$searchQry 	= ( $searchWord ) ? '#search=' . $searchWord : '';

	$pdfviewer_path =  urldecode( site_url() . '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/'. $fname ) ;
			
	$foptions = get_query_var( "pdfoptions","");

	// Create a container div for thePDFJS iframe, the margines compensate for the padding that the std WP window applies
	$output = '<div id="pdf-viewer-container" style="height:90vh; width:calc(100% + 20px); margin:-16px 0px 0px -10px; max-width:unset;"></div>';

	// now attach the PDFJS as a scriptt insert, this is triggered on $(document.ready()) ..
	$output = $output . '
	<script>
	/////////////////////////////////////////////////////
	// NON-MEMBER PAGE using JQUERY UI DIALOG Plugin
	// Expects to have a container div set up id="pdf-viewer-container"

	jQuery(document).ready(function($){
		
		// We have an iframe with a dummy PDF loaded to get things going, have to refesh the frame with the actual pdf wanted.
		// Problem, the only way to refesh an <iframe with an embedded pdf is to remove the element, create a new iframe element
		// and embed it in the parent container. Thats done below...
		$("#pdf-viewer-container").find("iframe").remove();
		$("#pdf-viewer-container").find(".dialog-footer-text").remove();
		var iframe = document.createElement("iframe");
		// var webviewer = contents + "#view=FitH#nameddest=Contents";
		// var webviewer = "https://cdn.jsdelivr.net/npm/pdfjs-dist@2.7.570/build/pdf.worker.js?file=" + contents ;
		
		var webviewer = "/web/viewer.html?file=' .  $pdfviewer_path . "&search=" . $searchWord . '";
		//var webviewer = "/web/viewer.html?file=' .  $pdfviewer_path . '";
		iframe.id = "pdfViewer";
		iframe.src = webviewer;
		iframe.height = "95%";
		iframe.width = "100%";
		$("#pdf-viewer-container").append(iframe);
	
		// now set option for the jQuery UI Dialog popup box. remeber this maps to the <div id="pdf-viewer" ...> container>
		var wWidth = $(window).width();
		var dWidth = wWidth * 0.9;
		var wHeight = $(window).height();
		var dHeight = wHeight * 0.9;
		var link = $(this);
		/* $("#pdf-viewer-container")
		.dialog(  { 
			//modal:true,
			title: "' .$fname .'",  
			dialogClass: "pdf-viewer-dialog",
			width: dWidth, 
			height: dHeight, 
			maxHeight: 600, 
			maxWidth: 700,
			
		}); */
	  });

	  </script>';
	
	return $output;
}

/**********************************************************
* MOA JOURNAL - LIST ALL JOURNALS in ARCHIVE PAGE
* Shortcode to include in pagejournals-archive page. Shows all journals in the /journals folder by year
* This shortcode 
* - Includes a multiple select box which allows users to select multiple decades they want to view.
* - Reads the list of journal file by scanning the journals folder. It builds multiple "decade container" boxes and the 
*   select options from this automatically. All styles 
* - Loads a jQuery "select multiple with checkboxes" free plugin that enhances the select multiple, adding checkboxes for clarity
* - Includes in-line cSS and JS code to style the page contents and process the clicks
* TODO - at the moment all the journalsare visible. We need to add an Ajax routine to handle downloads, that can also count them 
************************************************************/
add_shortcode("moa-journal-list", "moa_journal_list_fn");
function moa_journal_list_fn( $atts, $content = null) {
	
	////////////////////////////
	// Check if call coming from WP Admin Backend, ie prevents shorcode being executed when editing the page using TinyMCE
	// It appears that any shortcode can be called 3 times after pressing the "Update" button on the edit page screen!
	if( is_admin() ) return;

	// add the jQuery plugin script aand css that adds checkboxes to the multiple select
	wp_enqueue_script( 'jquery-multiple-plugin-js'   , plugins_url( '../js/jquery.multiselect.js', __FILE__ ));
    wp_enqueue_style ( 'jquery-multiple-plugin-style', plugins_url( '../css/jquery.multiselect.css' , __FILE__ ));
	
	wp_enqueue_style ( 'moa-shortcodes-style', plugins_url( '../css/moa-shortcodes.css' , __FILE__ ));
	
	// what permissions has the user?
	$isMember = false;
	if ( $user_id = get_current_user_id() ) {
		$userdata = get_user_by('ID', $user_id );
		$isMember = ( in_array( 'um_moa-admin', $userdata->roles ) || in_array( 'um_moa-member', $userdata->roles ) )? true : false;
	}

	// initialise variables that will accumulate html output
	$rows_all  = '';
	$selectDecadeOptions = '<option value="all">All</option>';
	$decadeLast = '';
	$defaultDisplay = true;
		
	// Read all filenames from the /journals folder into array, leaving out unwanted filenames '.', '..' etc.
	// We will need toarray of filenames to be in date descending but can't use scanddir( ..., SCANDIR_SORT_DESCENDING) because that's
	// case sensitive, instead use natcasesort() on the array to do case insensitive array sort, then reverse array order to get latest first 
	// Next few lines achieves this
	$path = get_home_path()  . MOA_MEMBERS_ONLY_FOLDER ;		// $path will be "/var/www/vhosts/httpdocs/macwester.org.uk/journals"
	$file_arr1 = array_diff( scandir( $path,$sorting_order = SCANDIR_SORT_NONE ), array( '.', '..','.htaccess' ) )  ;	
	natcasesort( $file_arr1 );	// this is the crucial case insensitive sort we need
	$files_arr = array_reverse( array_values( $file_arr1 )) ;
	
	// whip through the array oof filneames, extracting 
	foreach ( $files_arr as $fname ) {
		// Regex pression below can deakl with flenames like "1975 Autumn.pdf" or  "MacJournal 1975 Autumn.pdf" or "MacJournal-1975-Autumn.pdf" etc
		preg_match_all('/(19|20)\d{2}|(Autumn|Spring|Launch|Commemoration)/', $fname, $matches);
		if( isset($matches[0][0]) && isset($matches[0][1])) {
			$seasonLast= ( isset($season) )?$season:'';
			$year = $matches[0][0];
			$season = $matches[0][1];
			$decade = substr( $year, 0, 3) . '0';
			$decadeNext =  (string) ( (int) $decade + 9);
		
			// this sections gets processed only on a decade change
			if ( $decade != $decadeLast ) {
				$decadeId  = 'decade-' . $decade;
				if ( $decadeLast != '') $rows_all .= '
				</div>';

				// create a new "flex" decade container for the journal images with a seperate title div at the top 
				$displayTitle     = ($defaultDisplay)? "block;" : "none;";
				$displayContainer = ($defaultDisplay)? "flex;" : "none;";
				
				$rows_all .= '
				<div id="decade-title-'. $decade . '" class="journal-container-title" style="display:'.$displayTitle.'">Journals for '. $decade . ' to ' . $decadeNext. '</div>
				<div  id="decade-container-'. $decade .'" class="journal-container" style="display:'.$displayContainer.'">'  ;
				
				$decadeLast = $decade;

				// create a option tage for the decade select box that we have 
				$defaultSelected = ( $defaultDisplay)? 'selected="selected"' : '';
				$selectDecadeOptions .= '<option '. $defaultSelected .' value="'  .  $decade. '">'. $decade . '-' .$decadeNext. '</option>';

				$defaultDisplay = false;

			}
				
			// this section gets processed for every journal found
			// Note , how to add a FancyBox popup "<a class="fancybox" href="...." data-fancybox="gallery" data-options="\'closeBtn\':true" data-caption="'. ....
			$cover	      = $year . substr( $season, 0, 1) . '-Cover.jpg';
			$cover_fpath  = '/articles/' . $cover;
			$contents	  = $year . substr( $season, 0, 1) . '-Contents.pdf';
			$contents_fpath =  '/articles/' . $contents;
			$fname_path = '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/'. urlencode( $fname ). '?action=view';
			$target_window = ' target="frame pdfview" rel="noopener noreferrer" ';
			
			$pdfviewer_path =  '/pdf-view/?pdf=' . urlencode($fname );
			
			// use these settings if the PDF JS view is beign used for full journals
			//$fname_path = '/web/viewer.html?file=' . $fname_path;
			//$target_window = '';

			if ( $isMember == false ) {
				$row = '
				<div class="journal-item">
					<img class="journal-cover non-member" src="'. $cover_fpath . '" data-contents="'. $contents_fpath.'" data-title="'.$fname.'"/>
					<div class="journal-caption">' . $year . ' ' . $season . '</div>
				</div>';
			} else {
				/*$row = '
				<div class="journal-item">
					<a class="journal-link" href= "'. $fname_path .'" ' . $target_window . '>
						<img class="journal-cover" src="'. $cover_fpath . '"/>
					</a> 
					<div class="journal-caption">' . $year . ' ' . $season . '</div>
				</div>';*/
				$row = '
					<div class="journal-item">
						<a class="journal-link" href= "'. $pdfviewer_path . '">
							<img class="journal-cover" src="'. $cover_fpath . '"/>
						</a> 
						<div class="journal-caption">' . $year . ' ' . $season . '</div>
					</div>';
			} 
			$rows_all .= $row;
		}
	}
	$rows_all .= '</div>';

	$html ='
	<script>
	
	jQuery(document).ready(function($) {
		
		///////////////////////////////////////////////
		// MULTIPLE SELECT JQUERY
		// jQuery to initialise the multiselect control
		$("#decade-select").multiselect();

		// jQuery to process the multiselect change events
		var selectedvals = [];
		$("#decade-select").change(function() {
			var options = $("#decade-select option");
			var allWanted = false;
			for (i = 0; i < options.length; ++i) {
				if ( options[i]. value == "all" && options[i].selected )
				allWanted = true;
				break;
			}
			var i;
			for (i = 0; i < options.length; ++i) {
				
				var targetContainerId = "#decade-container-" + options[i].value ;
				var targetTitleId = "#decade-title-" + options[i].value ;

				if ( options[i].selected  || allWanted  ) {
					$( targetTitleId ).css("display","block");
					$( targetContainerId ).css("display","flex");
				} else {
					$( targetTitleId ).css("display","none");
					$( targetContainerId ).css("display","none");
				}
			}	

		});

		/////////////////////////////////////////////////////
		// NON-MEMBER POPUP using JQUERY UI DIALOG Plugin

		$(".non-member").click(function(){
		
			// pick up the name of the title and contents filename from the image the user clicked on, (from "data-contents" and "data-title" attributes)
			var contents = $(this).data( "contents" ); 	// returns href of contents page
			var title  = $(this).data( "title" ); 		// name of Journal eg: "Macjournal-2016-Spring.pdf" 

			// We have an iframe with a dummy PDF loaded to get things going, have to refesh the frame with the actual pdf wanted.
			// Problem, the only way to refesh an <iframe with an embedded pdf is to remove the element, create a new iframe element
			// and embed it in the parent container. Thats done below...
			$("#popup-pdf").find("iframe").remove();
			$("#popup-pdf").find(".dialog-footer-text").remove();
			var iframe = document.createElement("iframe");
			// var webviewer = contents + "#view=FitH#nameddest=Contents";
			// var webviewer = "https://cdn.jsdelivr.net/npm/pdfjs-dist@2.7.570/build/pdf.worker.js?file=" + contents ;
			var webviewer = "/web/viewer.html?file=" + contents ;
			iframe.src = webviewer;
			iframe.height = "95%";
			iframe.width = "100%";
			$("#popup-pdf").append(iframe);
			$("#popup-pdf").append("<div class=\"dialog-footer-text\">MOA Members should login to see full journal</div>");
			

			// now set optiosn for the jQuery UI Dialog popup box. remeber this maps to the <div id="popup-pdf" ...> container>
			var wWidth = $(window).width();
			var dWidth = wWidth * 0.9;
			var wHeight = $(window).height();
			var dHeight = wHeight * 0.9;
			var $link = $(this);
			$("#popup-pdf")
			.dialog(  { 
				//modal:true,
				title: title + " (pages 1-3 only)",  
				dialogClass: "popup-pdf-dialog",
				width: dWidth, 
				height: dHeight, 
				maxHeight: 600, 
				maxWidth: 700,
				buttons: [
					{ text: "Register",
						click: function() {window.location = "/office/registration-form?redirect_to=/club-house/journal-archive/";}
					},
					{ text: "Login",
						click: function() {window.location = "/login?redirect_to=/club-house/journal-archive/";}
					},
					{ text: "Close",
						click: function() { $( this ).dialog( "close" );}
					}, 
				],
			});
			
	  	}); 
		  
		////////////////////////////////////////////
		// SHOW/HIDE SPANS & DIVS
		// expects the Hide/Show button element has  class = "show-hide" and the class of the target to be in its data-target
		  $(".show-hide").click(function (){
			var target= $(this).data( "target" );
            $( "." + target ).slideToggle();
			$(this).html( ( $(this).html() == "Read More" ) ? "Read Less" : "Read More" );
        });
	}); 
	</script>

	<div class="journal-select-container">
		<label for="decade">Choose Decade:</label>
		<select name="decade-select" id="decade-select" multiple>' .
		$selectDecadeOptions	.	'
		</select>
	</div>
	' . $rows_all .
	'<div id="popup-pdf">
		<!--
			<p>You\'re not logged in as a MOA Member so you\'re seeing only the first 3 pages. If you are a full member click on the Login button below to see all the Journal</P>
			<p>Conditions <a href="#" class="show-hide" data-target="member-conditions">Show</a>
				<span class="member-conditions" style="display:none;">Only full members of the Macwester Owners can view and download these PDF journals (Members can use the login button below to see them in full).
				If you have registered (it\'s free) you will be able see the first 3 pages that include the contents page of what\'s available. 
				The Macwester Owners Association retains copyright to the material in the Journals. It is only for personal use and not to be copied or reproduced 
				for commercial purposes or republished in whole or part without prior permission from the Association Committee.
				</span>
			</p> 
		-->
		<!-- <img class="journal-contents-jpg" src="'. $contents_fpath . '"/> -->
		<!-- <object id="popup-pdf-object" data="/articles/1980S-Contents.pdf#view=Fit" type="application/pdf" width="100%" height="100%"> -->
		<iframe id="popup-pdf-object" src=""/web/viewer.html?file="/articles/1980S-Contents.pdf" width="100%" height="100%"></iframe>
	</div>';
	
	return $html;
}

/**********************************************************
* MOA JOURNAL VIEW - INSUFFICIENT RIGHTS PAGE
* Shortcode that displays error message for non-member users tryingt io view a Journal
************************************************************/
add_shortcode("moa-journal-view-error", "moa_journal_view_error_fn");
function moa_journal_view_error_fn( $atts, $content = null) {
	
	$html = '
<img class="sorry-img" src="/images/sorry-no-access-gray.png" width="100%" />
<div class="overlay" style="color: darkred; text-align: center;"><span style="font-size: 14pt;">You need to be a full club member to access this area</span></div>
Membership is chargeable; there is a £20 per year subscription, (£30 for the first year which includes joining pack).

As well as joining forces with other MacWester enthusiasts and boat owners, these are the benefits you get if you are a member:
<ul>
 	<li>Twice yearly delivered hard-copy of Macwester Journal</li>
 	<li>Periodical News letters</li>
 	<li>Access to our library containing articles from past Journals relating to repairs and modifications spanning the history of the Association</li>
 	<li>Joining pack which includes MOA burgee</li>
 	<li>Membership card (which confirms affiliation to RYA, recognised by most yachting clubs)</li>
</ul>
Click here <a href="/office/registration-members-form/">Online Club Membership Application Form</a> to get full access to the resources on this web site';
	return $html;
}

////////////////////////////////////////////
// MOA LIST JOURNALS - Function to trap redirected URLs
// This is used to serve files from protected folders we don't want to expose on the Internet. These folders are blocked 
// with a "deny all" in the folder's .htaccess file, and we reference the resource with a "virtuqal directory" tag, e.g.:
// "https://macwester.org.uk/members-only/MacJournal-yyyy-season.pdf".
// HOW IT WORKS...
// 1 . The WP hook we are using actually receives EVERY URL posted into the site. 
// 2.  Our hook function checks for any URL with this "Virtual folder", eg "/members-only/...", if found, then we check user's acces rights
// 3.  If user has acces we respond with our own HTTP, forcing a HTTP 200 retsponse, (needed because the resource doesn't exist)
//     and we echo back the contents of the file from the protected resource (using readfile()). 
// 3a. We check for querystring args "action=download", if there then we download the file, else we send header to view it, (set by HTTP header "Content-type")
// 3b. We also record that the user has downloaded a members-only resouce, storing it in his wp_usermeta data 
// 4.  If the user hasn't access rights they're redirected to the WP "membership-needed" page which explains & offers a link to join up
////////////////////////////////////////////
add_action('template_redirect','moa_template_redirect_check_for_virtual_dir_fn');
function moa_template_redirect_check_for_virtual_dir_fn() {
  
	$args = array();
	$url = parse_url( $_SERVER['REQUEST_URI'] );		// seperates URI from querystring, returning array: $uri["path"] and $uri["query"]
	if( isset( $url["query"] )) 
		parse_str( $url["query"], $args);					// seperates querystring into array $args["key"]=>"value"

	$arr = explode( "/" , $url["path"] );				// separates URI '/downloads/resource' into array $arr[1]="downloads" and $arr[2]="resource"
	$requestFolder = $arr[1];							// so seperate into virtual directory part and fileanme part
	$requestFile = '';
	for( $i=2; $i< count($arr); $i++ ) {				// this gets the path to filename even if many sub-folders (dir1/dir2/dir3/.../filename 
		$requestFile .= '/' .urldecode( $arr[$i] ) ;
	}
	// RECORD URL REQUESTED - if the user is logged in then record the urls they are visiting
	$session_id = wp_get_session_token();		//test what info we can get from the session

	// VIRTUAL FOLDER REQUESTED - Check if we are heading for the virtual "members-area" (downloads) folder?
	if ( $requestFolder  == MOA_MEMBER_VIRTUAL_FOLDER) {						
		$filepath =  ABSPATH . MOA_MEMBERS_ONLY_FOLDER . $requestFile ;	// we are, so build the real path to the filename

		// check the user's permissions, he has to have role = MOA-admin or MOA-member to do this
		$user_id = get_current_user_id();
    	$userdata = ( $user_id) ? get_user_by('ID', $user_id ) : array();
		if ( $user_id && (in_array( 'um_moa-admin', $userdata->roles ) || in_array( 'um_moa-member', $userdata->roles )) ) {
			if (  file_exists( $filepath ))	{			

				readfile( $filepath, true );	//  reads & echo' the file from the "/journals" protected area in one call!

				// Add this resource download eventto the user's wp_usermeta data
				$meta = get_user_meta( $user_id, 'moa_members_downloads', true);	//retrieves existing metadata array contents
				if ($meta) {
					array_push( 
						$meta, 
						array( "resource" =>  MOA_MEMBERS_ONLY_FOLDER . '/' . $requestFile,"date" => current_time("U") ) 
					);
				} else {
					$meta =array();
					$meta[0] = array( "resource" =>  MOA_MEMBERS_ONLY_FOLDER . '/' .$requestFile,"date" => current_time("U")) ;
				}
				update_user_meta( $user_id, "moa_members_downloads", $meta );
				
				// check whether a download requested or a view, change the HTTP header "Content-type" accordingly
				if ( array_key_exists( "action" , $args)  && $args["action"] == "download") {
					header('Content-type: application/x-msdownload',true,200);							// header to download a file
					header('Content-Disposition: attachment; filename="' . $requestFile  . '"') ;	// header to download the pdf
					header('Pragma: no-cache');
					header('Expires: 0');
				} else {
					header('Content-type: application/pdf',true,200);									// headers to view the pdf
					header('Content-Disposition: inline; filename="' . $requestFile  . '"') ;			// headers to view the pdf
					header('Pragma: no-cache');
					header('Expires: 0');
				}
				exit();
				
			// resource requested cannot be found so redirect to 403 error page
			} else {
				header( "HTTP/1.1 403 Unknow Resource" );
				header("Location: /403.php");
				exit();				
			}
		// user has insufficient access rights so redirect to "membership needed page"
		} else {
			header('Content-Type: text/html; charset=UTF-8');
			header('Location:  https://www.macwester.org.uk/club-house/membership-needed');
			exit();
		}
		
	}
}

/**********************************************************
* TECH ARTICLES LIST - LIST ALL ARTICLES FROM CSV FILE
* Reads a CSV file for the tech article data and with pointers to article PDF files 
* Provies a select popupdown  to select category of articles by boat class
***************/
add_shortcode("moa-tech-articles-list", "moa_tech_articles_list_fn");
function moa_tech_articles_list_fn( $atts, $content = null) {
	
	////////////////////////////
	// Check if call coming from WP Admin Backend, ie prevents shorcode being executed when editing the page using TinyMCE
	// It appears that any shortcode can be called 3 times after pressing the "Update" button on the edit page screen!
	if( is_admin() ) return;

	// add the jQuery plugin script aand css that adds checkbiooxes to the multiple select
	wp_enqueue_script( 'jquery-multiple-plugin-js'   , plugins_url( '../js/jquery.multiselect.js', __FILE__ ));
    wp_enqueue_style ( 'jquery-multiple-plugin-style', plugins_url( '../css/jquery.multiselect.css' , __FILE__ ));
	
    // add the shortcodes stylesheet
    wp_enqueue_style ( 'moa-shortcodes-style', plugins_url( '../css/moa-shortcodes.css' , __FILE__ ));
	
	// get user's permissions 
	$isMember = false;
	if ( $user_id = get_current_user_id() ) {
		$userdata = get_user_by('ID', $user_id );
		$isMember = ( in_array( 'um_moa-admin', $userdata->roles ) || in_array( 'um_moa-member', $userdata->roles ) )? true : false;
	}

	// initialise variables that will accumulate html output
    $html = '';
    $row_count = 1;
    $row = '';
    $rows_all  = '';
    $selectOptions = '<option value="all">All Articles</option>';
      
	$selectBoatClassOptions = '<option value="all">All</option>';
	$BoatClassLast = '';

    // Read all entries from the /tech-articles.csv spreadsheet into array,
	$fpath = get_home_path()  . '/tech-articles.csv' ;		// $path will be "/var/www/vhosts/httpdocs/macwester.org.uk/journals"

    // note <table class="table-responsive"> triggers a Bootstrap class library that makes the table scrollable on mobiles
    $html .= '
    <div class="tech-articles-container">
        <div class="tech-articles-header">Mac Class</div>
        <div class="tech-articles-header">Article Title</div>
        <div class="tech-articles-header">Description</div>
        <div class="tech-articles-header">Author</div>
        <div class="tech-articles-header">Journal</div>
        <div class="tech-articles-header">Pages</div>
        ';

    if ( ($handle = fopen( $fpath, "r")) !== FALSE) {
        $row1 = fgetcsv ($handle, 2000, ",") ;  // ignore line 1
        while ( ($data = fgetcsv ($handle, 2000, ",") ) !== FALSE) {
            $num = count( $data );      
            $row_count++;
            $row_article = $data[1]; 
            $row_title  = $data[2]; 
            $row_desc   = $data[3];
            $row_author = $data[4];
            $row_season = $data[5];
            $row_year   = $data[6];
            $row_page_start = $data[7];
            $row_page_num= $data[8];
            $row_page_stop = strval( intval($row_page_start) + intval($row_page_num) );
            $row_class  = $data[9];
            $row_class  = ( substr( $row_class,0,3) == "Mac") ?  substr( $row_class,4 ) : $row_class ;
            $row_class_desc  = ( $row_class != "" ) ? "Mac " . $row_class : "";
            $row_journal = $row_year . $row_season;
            
            // SELECT OPTIONS - add the yacht class to the select options - only once per class obviously
            preg_match_all('/(Kelpie|26|27|28|Wight|Rowan|Crown|Malin|Atlanta|Wight|Seaforth)/', $row_class, $matches);
    		if( isset($matches[0][0]) ) {
                $row_class_group = strtolower( $matches[0][0] );
            } else {
                $row_class_group = "non-specific";
                $row_class_desc  = "non-specific";
            } 
            if ( !str_contains( $selectOptions , 'value="' . $row_class_group .'"' )  ){
                $selectOptions .= '<option value="'  .  $row_class_group. '">'. $row_class_desc. '</option>';
            }
            // SELECT OPTION ENDS 

            // ARTICLE LINK - builds the link using the coed in the tech articles csv (col 2)
            $fname = sprintf( "%03d", $row_article ) . ".pdf" ;
            $fname_realpath = get_home_path() . "/" . MOA_MEMBERS_ONLY_FOLDER .'/tech-articles/'. $fname;
            if ( file_exists( $fname_realpath) ) {
                $fname_path = '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/tech-articles/'. urlencode( $fname ). '?action=view';
                $row_link = '
                <a class="article-link" href= "'. $fname_path .'" target="frame pdfview" rel="noopener noreferrer">'. $row_title . ' (' .  $fname . ')' . '</a>'; 
            } else {
                $row_link = $row_title . ' (' .  $fname . ')' ;
            }
			
            // JOURNAL LINK - builds a link to a journal using the same virtual directory as for journal-archives
            // Chrome browser interprets "#page=nn as a jump to page no, unfortunately no other browser does
            // in which case note Journal page numbers starts 3 pages in (cover, contents and ed intro are unnumbered) 
            $row_page_start_plus3 = strval((intval($row_page_start) + 3)) ;
            $fname_journal = "MacJournal-" . $row_year . "-" . ( ( $row_season == "S" ) ? "Spring" : "Autumn" ) . ".pdf";
            $fname_path_journal = '/'. MOA_MEMBER_VIRTUAL_FOLDER .'/'. urlencode( $fname_journal ). '#page=' . $row_page_start_plus3 . '?action=view';
			$row_link_journal  = '
                <a class="article-link" href= "'. $fname_path_journal .'" target="frame pdfview" rel="noopener noreferrer">'. $row_journal. '</a>'; 

            // Display the columns data in a set of div boxes
            $row .= "
            <div class=\"col1 article-class-$row_class_group\">$row_class_desc</div>
            <div class=\"col2 article-class-$row_class_group\">$row_link</div>
            <div class=\"col3 article-class-$row_class_group\">$row_desc</div>
            <div class=\"col4 article-class-$row_class_group\">$row_author</div>
            <div class=\"col5 article-class-$row_class_group\">$row_link_journal</div>
            <div class=\"col6 article-class-$row_class_group\">$row_page_start-$row_page_stop</div>
            ";
            
        }
        $html .= $row . "
        </div>";
        
        fclose($handle);
    }
		
    $html = '<div class="article-select-container">
		<label for="boat-class-select">Choose Boat Class:</label>
		<select name="boat-class-select" id="boat-class-select" >' .
		$selectOptions	.	'
		</select>
        <div id="select-count">Article Count = '. $row_count.'</div>
	</div>
    ' . $html .
    '
    <script>
    jQuery(document).ready(function($) {
		
       ///////////////////////////////////////////////
		// SELECT JQUERY  CHANGE DETECT
        $("#boat-class-select").change(function() {
            
            var selectedClassName = "article-class-" + $(this).find(":selected").val() ;
            var cnt = 0;
            var allWanted = false;
            var options = $("#boat-class-select option");
			for (i = 0; i < options.length; ++i) {
				if ( options[i]. value == "all" && options[i].selected )
				allWanted = true;
				break;
			}
			var i;
			for (i = 0; i < options.length; ++i) {
				
				var targetContainerClass = ".article-class-" + options[i].value ;
				
                if ( options[i].selected  || allWanted  ) {
					$( targetContainerClass ).css("display","block");
                    cnt += $( targetContainerClass + ".col1" ).length;      
      			} else {
					$( targetContainerClass ).css("display","none");
				}
			}	
            $( "#select-count" ).html("Article Count = " + cnt );   // update the div in the select container
          
        });    

    });
    </script>';

	return $html;
}


/**********************************************************
* GALLERIES LIST - LIST GALLERIES FOR DIFFERENT BOAT CLASSES
* Uses the FooGallery plugin, hence the shortcodes
* Provies a select popupdown  to select category of articles by boat class
*********************************************************/
add_shortcode("moa-galleries-list", "moa_galleries_list_fn");
function moa_galleries_list_fn( $atts, $content = null) {

	////////////////////////////
	// Check if call coming from WP Admin Backend, ie prevents shorcode being executed when editing the page using TinyMCE
	// It appears that any shortcode can be called 3 times after pressing the "Update" button on the edit page screen!
	if( is_admin() ) return;

	// add the jQuery plugin script aand css that adds checkbiooxes to the multiple select
	wp_enqueue_script( 'jquery-multiple-plugin-js'   , plugins_url( '../js/jquery.multiselect.js', __FILE__ ));
    wp_enqueue_style ( 'jquery-multiple-plugin-style', plugins_url( '../css/jquery.multiselect.css' , __FILE__ ));
	
	$galleryHtml = '';
	$selectOptions = '';
	$gallery_wanted_arr = array();

	////////////////////////
	// read the differnt Foo Galleries from the wp_posts table, Foo Galleries are of post_type="foogallery"
	$galleries = get_posts([
		'post_type' => 'foogallery',
		'post_status' => 'publish',
		'numberposts' => -1,
		'orderby' => 'publish_date',
		'order'    => 'DESC'
	]);
	$arr = array();				
	$arrTitle = array();		
	for ( $i=0 ; $i < count($galleries) ; $i++ ){
		$arr[$i]      =  $galleries[$i]->ID ;			// will hold the post id of the Foo galleries
		$arrTitle[$i] =  $galleries[$i]->post_title ;	// will hold the gallery titles, eg "Seaforth", "Mac 26" etc.
	}

	////////////////////////
	// Get the  "show" attribute in the shortcode to see what default galleries to display. 
	// If null then display nothing
	if ( is_array($atts) ) {
		if(  array_key_exists( "show", $atts ) && $x = $atts["show"] ) {						// List will be gallery names, maybe empty or comma delimited,  eg "Mac 26,Seaforth"
			$default_names = explode( "," , $x );		// if non-null explode  the names into an array
			if ( $wanted_names   = array_intersect(  $arrTitle, $default_names) ) {		// get all names intersecting with boat call name list 
				for ( $j=0, $i=0 ; $i < count($arrTitle) ; $i++ ){						// loop thru these names to get the array index, then
					if (  in_array( $arrTitle[$i], $wanted_names ) ) {					// use the index from the titles array to get the 
						$gallery_wanted_arr[$j++] = $arr[ $i ];							// ID of the gallery from the $arr[]
					}
				}
			}
		}
	}

	////////////////////////
	// Check user's yacht_class profile field to see if we can match it with a gallery of the same name, if so this overwrites any  default opening
	// But notesome  yatch_class fields have extra modifiers like "Wight Mk2" which needs to match with the gallery "Wight" title, hence the 
	// stripping off of leading substring in second test below
	$user_id = get_current_user_id();
	$user_boat_class = get_user_meta ( $user_id, "yacht_class", true );	
	if ( $i  =  array_search( $user_boat_class, $arrTitle, false )) {		// check for exact match in the gallery titles, eg: "Rowan 22" 
		$gallery_wanted_arr = (array) $arr[$i];
	} else {																// No direct match, so check for sleading substring, eg "Wight"
		$x = strtok( $user_boat_class , ' '); 								// get the leading substring
		if ( $i  = array_search(  $x, $arrTitle,  false )) {				
			$gallery_wanted_arr = (array) $arr[$i];							// success it matches
		}
	}

	////////////////////////
	// Check  the querystring for any galleries to display, expect ".../?id=1234,5678,..."
	if ( array_key_exists( "id", $_GET ) )  {
		$gallery_wanted_arr = preg_split ("/\,/",  $_GET["id"] );
	} 

	for ( $i=0  ; $i< count($arr) ; $i++ ) {
		$id =  $arr[$i] ;
		$title = $arrTitle[$i]; 	//get_the_title( $id );
		$boatClass = str_replace(' ', '-', strtolower($title) );
		
		// get the count of images
		$images = get_post_meta( $id, "foogallery_attachments", true ) ;		// this is how foo gallery stores its gallery images
		$imageCount = count( $images );
		
		// work out whether this boat_class is required to show or not, these values are injected into the div and select options below
		if (in_array( $id, $gallery_wanted_arr, false )) {
			$selected = "selected" ;
			$visible  ="display:block;" ;
		 } else {
			$selected = "" ;
			$visible  ="display:none;" ; 
		}

		// build the div blocks from the FooGallery shortcodes, note these divs may be hidden is not immediately wanted for display
		$galleryHtml .='
		<div id="gallery-title-'. $boatClass .'" class="gallery-container-title" style="'. $visible .'"> Macwester ' . $title .'</div>
		<div id="gallery-'. $boatClass .'" class="gallery-container gallery-'. $boatClass .'" style="'. $visible .'"><p>' .  do_shortcode( "[foogallery id=" .  strval( $id) ."]") . '</div>';
		
		// build the select options, note injection of the "selected" where a division is being displayed 
		$selectOptions .= '<option value="'  . $boatClass .'" '. $selected .'>'. $title. ' ('. $imageCount .')</option>';
		
	}
	$html = '
<div class="gallery-select-container">
	<label for="gallery-select">Choose Boat Class:</label>
	<select name="gallery-select" id="gallery-select" multiple>' .
	$selectOptions	.	'
	</select>
</div>
' . $galleryHtml .
'
<style>
	.gallery-container-title{
		position: relative;
		text-align: center;
		font-size: 1rem;
		font-weight: 700;
		color: var(--moa-blue);
		background-color: #c2bcc3;
		width: 230px;
		border-radius: 15px 15px 0 0;
		margin:0;
	}
	.gallery-container {
		background: #c2bcc3;
    	border-radius: 0 5px 5px 5px;
    	padding: 10px;
   		margin-bottom: 10px;
	}
	.fg-default.fg-gutter-10 {
		margin-bottom: unset;
	}
	/*********** jQUERY MULTISELECT popup box, container styles etc. ****************/
	.gallery-select-container {
		position: relative;
		margin: 20px 0px 0px 0px;
		height:50px;
	}
	.ms-options-wrap {
		width: 30%;
		right: 10px;
		margin: 0px 10px 10px;
		position: relative;
		top: -36px;
		left: 120px;
	}
	.ms-options-wrap.ms-active > .ms-options {
		box-shadow: 6px 5px 7px -2px rgba(87,85,87,1);
		margin-left: 10px;
	}
	.ms-options-wrap > button,
	.ms-options-wrap > button:focus {
		margin-left: 10px;
	}
	.ms-options-wrap > button:hover,
	.ms-options-wrap > button:focus {
		background-color:white!important;
		color:black!important;
		border:1px solid black;
		box-shadow: 6px 5px 7px -2px rgba(87,85,87,1);
	}
</style>
<script>
	jQuery(document).ready(function($) {

		///////////////////////////////////////////////
		// MULTIPLE SELECT JQUERY
		// jQuery to initialise the multiselect control
		$("#gallery-select").multiselect();

		// jQuery to process the multiselect change events
		var selectedvals = [];
		$("#gallery-select").change(function() {
			var options = $("#gallery-select option");
			var allWanted = false;
			for (i = 0; i < options.length; ++i) {
				if ( options[i]. value == "all" && options[i].selected )
				allWanted = true;
				break;
			}
			var i;
			for (i = 0; i < options.length; ++i) {
				
				var targetContainerId = "#gallery-" + options[i].value ;
				var targetTitleId = "#gallery-title-" + options[i].value ;

				if ( options[i].selected  || allWanted  ) {
					$( targetTitleId ).css("display","block");
					$( targetContainerId ).css("display","block");
				} else {
					$( targetTitleId ).css("display","none");
					$( targetContainerId ).css("display","none");
				}
			}	

		});
	
		///////////////////////////////////////////////
		// SELECT JQUERY  CHANGE DETECT
		$("#boat-class-select").change(function() {
			
			var selectedClassName = "article-class-" + $(this).find(":selected").val() ;
			var cnt = 0;
			var allWanted = false;
			var options = $("#boat-class-select option");
			for (i = 0; i < options.length; ++i) {
				if ( options[i]. value == "all" && options[i].selected )
				allWanted = true;
				break;
			}
			var i;
			for (i = 0; i < options.length; ++i) {
				var targetContainerClass = "#gallery-" + options[i].value ;
				if ( options[i].selected  || allWanted  ) {
					// $( targetContainerClass ).css("display","block");
					url = window.location.href.split("?")[0];		// url without query string
					targetId =  options[i]. value ;
					window.location.replace( url  + "?id=" + targetId );
				} else {
					// $( targetContainerClass ).css("display","none");
				}
			}	
		});    

	});

</script>
';

// if the user is unregistered they'll be an empty space under the select box, rather dumm
// so tack on  this gallery of "ken burns" transforming "images, note  images need to be in right order
// because the transform affect slides into one of the picture corners, (see the css on "nth-child" images)
//  so we need images where the focus of interest in the pic in in the appropiate corner 

	if ( $user_boat_class == "" ) {
		$html .= moa_galleries_kenburns_images_fn();
	}

	return $html;

}

///////////////////////////////////////////////////
// Shows gallery of "ken burns" style transforming images of Macs. 
// Note: if decide to change image, careful with the order. The  transform affect applied to the images. These move the image to one of the picture corners, (direction set in the 
// css on "nth-child" images). So choose images where the focus  of interest in the pic in in the appropiate corner 
// 
add_shortcode("moa-kenburns-images", "moa_galleries_kenburns_images_fn");
function moa_galleries_kenburns_images_fn(){

	return '
	<div id="kenburns">
		<img src="/images/quickgallery/10_Wight_Mk2.jpg" 	width="1024" height="768" title="Mac Medlay">
	 	<img src="/images/quickgallery/8 Kelpie.png"  		width="1024" height="768" title="Kelpie">
		 <img src="/images/quickgallery/8_Mac_26.jpg" 		width="1024" height="768" title="Mac Malin">
		 <img src="/images/quickgallery/2_Malin.jpg" 		width="1024" height="768" title="Mac Malin">
		 <img src="/images/quickgallery/15 Seaforth.jpg" 	width="1024" height="768" title="Seaforth">
		<img src="/images/quickgallery/14_Seaforth.jpg" 	width="1024" height="768" title="Seaforth">
		<img src="/images/quickgallery/6_Seaforth.jpg" " 	width="1024" height="768" title="Seaforth">

	</div>
	<style>
		#kenburns {
			border: 8px solid #ffffff;
			width: 600px;
			height: 400px;
			margin: 0 auto;
			overflow: hidden;  
			position: relative;
		}
		#kenburns img {
			width: 640px;
			height: 430px;
			margin-left: -300px;
			margin-top: -220px;
			opacity: 0;
			position: absolute;
			top: 50%;
			left: 49%;
			transition-property: opacity, transform;
			transition-duration: 5s, 20s;
		}
		/* Need to catch the div for phones  */
		@media screen and (max-width: 600px) {
			#kenburns 		{ width: 400px; height: 300px;}	
			#kenburns img 	{ width: 430px; height: 320px; top: 66%; left: 74%; }
		}
		@media screen and (max-width: 391px) {
			#kenburns 		{ width: 380px; height: 280px;}	
			#kenburns img 	{ width: 430px; height: 300px; top: 75%; left: 87%; }
		}
		@media screen and (max-width: 376px) {
			#kenburns 		{ width: 370px; height: 260px;}	
			#kenburns img 	{ width: 410px; height: 280px; top: 71%; left: 85%; }
		}
		#kenburns img  				{ transform-origin: bottom left;  }
		#kenburns :nth-child(2n+1) 	{ transform-origin: bottom right; }
		#kenburns :nth-child(3n+1) 	{ transform-origin: top left;	  }
		#kenburns :nth-child(4n+1) 	{ transform-origin: bottom right; }
		#kenburns .fx:first-child + img ~ img  {
			z-index: -1;
		}
		#kenburns .fx {
			opacity: 1;
			transform: scale(1.5) translate(30px);
		}
	</style>
	<script>
		(function(){
			document.getElementById("kenburns").getElementsByTagName("img")[0].className = "fx";
			window.setInterval( kenBurns, 5000 );   
			var images = document.getElementById("kenburns").getElementsByTagName("img"),
				numberOfImages = images.length,
				i = 1;
			function kenBurns() {
				if ( i==numberOfImages){ i = 0; }

				images[i].className = "fx";
				if( i===0 ){ 
					images[numberOfImages-2].className = "";
				}
				if( i===1 ){ 
					images[numberOfImages-1].className = "";
				}
				if( i>1 ){ 
					images[i-2].className = "";
				}
				i++;
			}
		})();
	</script>
	
	';

}

///////////////////////////////////////////////////
// MEMBERS LIST - SHORTCODE FOR CUSTOM CODE TO RETURN LIST OF MEMBERS 
// includes fields like range of boats members'  to the user's boat location
// MUCH MORE TO DO!!
///////////////////////////////////////////////////
add_shortcode("moa-members-list", "moa_members_list_fn");

/**
 * Function to return a list of members e
 * @param float $range in km to include boats 
 * @return string html formatted 'grid' of all users details ready to display
**/
function moa_members_list_fn( $range ) {
	
	$rolesWanted = array( "um_registered","um_moa-member","um_moa-admin");
    
    // get the current user's boat long and lat, bomb out if no long lat  details given 
    $myUserId = get_current_user_id();
    if ( $myUserId == 0 ) {
        return "Error - you are not logged in";
    }
	// get user's boat log and lat, we'll need that later to compute range to other user's boat locations
	$x = get_user_meta( $myUserId, "yacht_longlat", true ) ;
    if ( isset($x) && $x != "" ) {
        $myLongLat = explode( ",", $x ) ;
        $myLong = trim( $myLongLat[0] );
        $myLat  = trim( $myLongLat[1] );
    } else {
        $myLong ="";
        $myLat ="";
    }


}


///////////////////////////////////////////////////
// CODE TO RETURN BOATS NEAR ME IN RANGE ORDER
// Includes shortcode [moa-boats-near-me] and supporting functions
///////////////////////////////////////////////////
add_shortcode("moa-boats-near-me", "moa_boats_near_me_fn");
/**
 * Function to return a list of users with boats in a range around me
 * @param float $range in km to include boats 
 * @return string html table of all users with  long and lat set
**/
function moa_boats_near_me_fn( $range ) {

	global $wp_roles;
    $rolesWanted = array( "um_registered","um_moa-member","um_moa-admin");
    
    // get the current user's boat long and lat, bomb out if no long lat  details given 
    $myUserId = get_current_user_id();
    if ( $myUserId == 0 ) {
        return "Error - you are not logged in";
    }
    $x = get_user_meta( $myUserId, "yacht_longlat", true ) ;
    if ( isset($x) && $x != "" ) {
        $myLongLat = explode( ",", $x ) ;
        $myLong = floatval( $myLongLat[0] );
        $myLat  = floatval( $myLongLat[1] );
    } else {
        //return "You have not given your boat loaction";
        return '<div>You have not set your Boat\'s Long Lat. To do so edit your <a href="/profile/?action=edit" title="edit your ptofile">Profile</a> and 
		use the Map \"drop pin\" function to set your boats location</div>';
    }
    // get the list of all users
    $DBRecord = array();
    $args = array(
        'role__in'	=> $rolesWanted,
        'orderby' 	=> 'ID',
        'order'   	=> 'DESC'
    );
    $users = get_users( $args );
    $i=0;
    foreach ( $users as $user )
    {
        $roles                          = $user->roles;
		$longlat                        = get_user_meta( $user->ID, 'yacht_longlat', true) ;
      
		if ( ( $user->ID != $myUserId) &&
             ( array_intersect( $roles, $rolesWanted ) ) && 
             (isset( $longlat) && $longlat != "") ) 
        {
        	$DBRecord[$i]['ID']             = $user->ID;
			$DBRecord[$i]['first_name']     = $user->first_name;
			$DBRecord[$i]['last_name']       = $user->last_name;
			$DBRecord[$i]['register_date']  = $user->user_registered;
			$DBRecord[$i]['email']          = $user->user_email;
			
			$UserData                       = get_user_meta( $user->ID );
			// $longlat                        = isset($UserData['yacht_longlat'][0]) ? $UserData['yacht_longlat'][0] : "";
		
		    $x = explode( "," , $longlat);
       	    $long = ( isset($x[0])) ? floatval($x[0]) : 0;
            $lat  = ( isset($x[1])) ? floatval($x[1]) : 0;
            if ( $lat !=0 && $long !=0  && (-90 <= $lat || $lat <= 90) &&  (-180 <= $long || $long <= 180) ) {
                $range          = floatval( haversineGreatCircleDistance( $myLat, $myLong, $lat, $long ));
                $DBRecord[$i]['range']          = $range;
                $DBRecord[$i]['yacht_longlat']  = $longlat;
               
				/*
				$DBRecord[$i]['role']           = implode ( "," , array_intersect( $roles, $rolesWanted ));
                $DBRecord[$i]['yacht_name']     = (isset( $UserData['yacht_name'][0]) )? $UserData['yacht_name'][0] : "";
                $DBRecord[$i]['yacht_location'] = (isset( $UserData['yacht_location'][0])) ? $UserData['yacht_location'][0] : "";
                $DBRecord[$i]['phone']          = ( isset($UserData['phone']) ) ? isset($UserData['phone'][0]) : "";
				*/
                $DBRecord[$i]['yacht_name']     = get_user_meta( $user->ID, 'yacht_name', true ) ;
                $DBRecord[$i]['yacht_location'] = get_user_meta( $user->ID, 'yacht_location', true); 
                $DBRecord[$i]['phone']          = get_user_meta( $user->ID, 'phone', true) ;
				$role           				= implode ( "," , array_intersect( $roles, $rolesWanted )) ;
				$DBRecord[$i]['role']           = ($role) ? $wp_roles->roles[$role]['name'] : "" ;
				$i++;
            } else {
                // error in long and lat
                //$zLat = floatval( $lat );
                //$zLong = floatval( $long);
               
            }
        } else {
			$zzz =1;
		}
    }

    
   usort( $DBRecord, 'moa_boats_near_me_compare_range');

    // convert array into a html table
    $html = "
	<div class=\"boats-nearby-container\">
		<div>Boat name</div><div>User</div><div>Registered or Member</div><div>Long & Lat</div><div>Range (nm)</div>
		<div>Location</div><div> When joined</div><div>Email</div>";
    foreach ($DBRecord as $item){
        $html .= "
			<div>" . strtoupper( $item["yacht_name"] ) ."</div>
			<div>" . ucwords( strtolower( $item["first_name"] . " " . $item["last_name"])) ."</div>
			<div>" . $item["role"] ."</div>
			<div>" . $item["yacht_longlat"]."</div>
			<div>" . number_format( $item["range"] / 1000 * 0.621371, 2 )."</div>
			<div>" . $item["yacht_location"] ."</div> 
			<div>" . date('M Y',strtotime($item["register_date"]))  ."</div>
			<div>" . $item["email"]  . "</div>
			";
    }
    $html .= "</table></div>";

	$html .= '
	<style>
		/* we  use a CSS Grid style for our table */
		.boats-nearby-container {
			display:grid;
			grid-template-columns: 10% 10% 12% 10% 8% 15% 1.2fr 1fr;
			max-width: 95%;
			margin:0px 1%!important;
		}
		.boats-nearby-container .tech-articles-header {
			background: darkgray;
			color:white;
		}
		.boats-nearby-container  > div {
			background: #f3f4f5;
			padding: 1px 5px;
			border: solid 1px darkgray;
			line-height: 1.4em;
		}
		.boats-nearby-container > div.col2 {
			background: lemonchiffon;
		}
	</style>
	';

    return $html;
}
/***
 * Callback Function for usort(), used as a "comparison function" by usort() in moa_boats_near_me() above to sort a multi dimensional array by an element,
 * According to the manual the comparison function must return an integer less than, equal to, or greater than zero if the first argument is considered 
 * to be less than, equal to, or greater than the second.
 */
function moa_boats_near_me_compare_range($a,$b)	{
	if ( !isset( $a['range']) || !isset( $b['range'])) return 0;
	if( $a['range'] == $b['range']) return 0;
	return ($a['range'] > $b['range']) ? 1 : -1;
}

/**
 * Function to calculates the distance between two points given then long-lat. It measures the great-circle distance between two points, with
 * the Haversine formula. Called by the moa_boats_near_me to calculate range from user's boat to all other registered boats
 * @param float $latitudeFrom Latitude of start point in [deg decimal]
 * @param float $longitudeFrom Longitude of start point in [deg decimal]
 * @param float $latitudeTo Latitude of target point in [deg decimal]
 * @param float $longitudeTo Longitude of target point in [deg decimal]
 * @param float $earthRadius Mean earth radius in [m]
 * @return float Distance between points in [m] (same as earthRadius)
 */
function haversineGreatCircleDistance($latitudeFrom, $longitudeFrom, $latitudeTo, $longitudeTo, $earthRadius = 6371000) {
	// convert from degrees to radians
	$latFrom = deg2rad($latitudeFrom);
	$lonFrom = deg2rad($longitudeFrom);
	$latTo = deg2rad($latitudeTo);
	$lonTo = deg2rad($longitudeTo);

	$latDelta = $latTo - $latFrom;
	$lonDelta = $lonTo - $lonFrom;

	$angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) +
		cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));
	return $angle * $earthRadius;
}

////////////////////////////////////////////
// EXTENDING WPUF FORMS, see this useful ref: https://tareq.co/2012/04/how-to-extend-wp-user-frontend/
// NOTE VERY IMPORTANT - WPUF Ajax calls the PHP-XML module to build Dom objects. This module is
// notinstalled by default wiuth the standard LINUX LAMP package. If not installed then the WPUF
// Forms update fails to reload the next page After thje Ajax call. The error is seen in the server's
// PHP log, and is fixed by adding # yum install php-xml to the environment (and restarting apache)
// IMPORTANT, for AWS EC2 Linux instance restartingh apache is not sufficient, MUST also reboot
// from the AWS console
////////////////////////////////////////////

/******************************
 * Allows (publicly allowed) query vars to be added, removed, or changed prior to executing the query.
 * Needed to allow custom rewrite rules using your own arguments to work, or any other custom query
 * variables you want to be publicly available.
 *******************************/
function add_query_vars_filter( $vars ){
    array_push( $vars,
        "wpuf_formid",
		"wpuf_post_type",
		"overlay",
		"paged",
		"post_type",
		"posts_per_page",   // for ebsoc display post shortcode
		"pdf",				// for PDF embedded viewer 	filname and options
		"pdf-year",
		"pdf-season",
        );
    return $vars;
}
add_filter( 'query_vars', 'add_query_vars_filter' );


////////////////////////////////////////////
add_shortcode( 'moa-do-wpuf-form', 'moa_do_wpuf_post' );
/******************
 *
 * @param {array} $atts standard array of shortcode arguments
 * @param {string} $content standard shortcode contents string
 * @return string
 *******************/
function moa_do_wpuf_post( $atts , $content) {
    
    // get the query string which is expected to hold the WPUF FORM ID, eg "261"
    $form_id = $_REQUEST['wpuf_formid'];
    
    // Get shortcode args
    // $wpuf_form_id = (isset($atts['$wpuf_form_id']) ) ? $atts['$wpuf_form_id'] : '1' ;
    $wpuf_shortcode = '[wpuf_form id="' . $form_id .'"]';
    return ( do_shortcode($wpuf_shortcode)) ;
}

/****************************************
 * WPUF Hooked function called by WPUF form AFTER submit button pressed.
 *  Note the hook triggers AFTER WPUF has updated the post meta fields
 *  See this ref https://wedevs.com/docs/wp-user-frontend-pro/tutorials/using-action-hook-field/
 * @param {string} $post_id
 ****************************************/
add_action( 'wpuf_add_post_after_insert', 'ebsoc_wpuf_after_post_submit' );
add_action( 'wpuf_edit_post_after_update','ebsoc_wpuf_after_post_submit' );
function ebsoc_wpuf_after_post_submit ( $post_id ) {
    
    // Do something here, e.g.: 
    //if ( isset($_POST['my_custom_field']) ) {
    //    update_post_meta( $post_id, 'your_meta_key', $_POST['my_custom_field'] );
    //}
    return;
}

/******************************
 * CREATE LIST OF USER BOATS
 *****************************/
add_shortcode( 'moa-user-boat-list', 'moa_user_boat_list_fn' );
function moa_user_boat_list_fn() {
	
	$output= "";
    $cnt=0;
    
    //
    // Main Loop uses get_users() call which retruns details from wp_users table
    // Note adding 'role__in'=>['um_moa-admin','um_moa-member','um_registered'] to array arg
    // would allow selection by role
    //
	$args = array(
		'orderby' => 'meta_value',
		'meta_key'=> 'yacht_class',
		'order'=>'ASC', 
	);
	
	$allusers = get_users( $args );
    // $allusers = get_users( array('orderby'=>'ID', 'order'=>'ASC') );
    foreach ( $allusers as $user ) {

		++$cnt;     // incr the output count
      
        $user_id = $user->ID;
        $user_id_div = "<div>{$user_id}</div>";
       
		// simplify users' role to include in KML file, (shouldn't ever be any "guest" users!)
        if ( in_array( 'administrator', (array) $user->roles ) || in_array( 'um_moa-admin', (array) $user->roles )) {
            $user_role="MOA Admin";
        } else if ( in_array( 'um_moa-member', (array) $user->roles ) ) {
            $user_role="MOA Member";
        } else if ( in_array( 'um_registered', (array) $user->roles ) ) {
            $user_role="Registered";
        } else {
            $user_role = "Guest";
        }
        $user_role_div = "<div>{$user_role}</div>";
       
        // note the get_users() call doens't return data from wp_usermeta table, so get that seperately.
        $usermeta = get_user_meta($user_id);
        
        // create fields to export
        $user_login = $user->data->user_login;
        $user_login_div = "<div>{$user_login}</div>";

		$user_registered = $user->data->user_registered;
                
        $profile = "<a href='/profile/{$user_id}'>Owners Profile</a>";
        $profile_div = "<div>{$profile}</div>";
        
        $user_last_loggedin =( (isset($usermeta["_um_last_login"]) && !empty($usermeta["_um_last_login"][0]) ) ? $usermeta["_um_last_login"][0] : "") ;
		$user_last_loggedin = ( $user_last_loggedin ) ? date('Y-m-d h:m:s',$user_last_loggedin) : "";

		$yacht_map_show = ( isset( $usermeta['yacht_map_show']) && strpos( $usermeta['yacht_map_show'][0],"Hide")!= false )? "NO" : "YES";
        
        $yacht_longlat =( (isset($usermeta["yacht_longlat"]) && !empty($usermeta["yacht_longlat"][0]) ) ? $usermeta["yacht_longlat"][0] : "") ;
        $yacht_longlat = ParseDD( $yacht_longlat );
        $yacht_longlat_div =( $yacht_longlat ? "<coordinates>{$yacht_longlat}</coordinates>" : "") ;
        
        $desc = ( (isset($usermeta["description"]) && !empty($usermeta['description'][0]) ) ? $usermeta['description'][0]  : '');
        $desc = str_replace("\r\n", "", $desc) ;
        $desc_div = ( $desc != "" ? "<div>{$usermeta['description'][0]}</div>"  : '');
        
        $yacht_name = ( isset( $usermeta["yacht_name"]) ? ucwords(strtolower($usermeta["yacht_name"][0])) : "");
        $yacht_name_div = ( $yacht_name != "" ? "<div>{$yacht_name}</div>" : "");
       
		$yacht_class = ( isset( $usermeta["yacht_class"]) ? $usermeta['yacht_class'][0] : "");
        $yacht_class_div = ( $yacht_class != "" ? "<div>{$yacht_class}</div>" : "");
        
        $yacht_rig = ( isset( $usermeta["yacht_rig"]) ? $usermeta['yacht_rig'][0] : "");
        $yacht_rig_div = ( $yacht_rig != "" ? "<div>{$yacht_rig}</div>" : "");
        
        $yacht_sail_no = ( isset( $usermeta["yacht_sailno"]) ? $usermeta['yacht_sailno'][0] : "");
        $yacht_sail_no_div = ( $yacht_sail_no != "" ? "<div>{$yacht_sail_no}</div>" : "");
        
        $yacht_engine = ( isset( $usermeta["yacht_engine"]) ? $usermeta['yacht_engine'][0] : "");
        $yacht_engine_div = ( $yacht_engine != "" ? "<div>{$yacht_engine}</div>" : "");
        
        $yacht_location     =( isset( $usermeta["yacht_location"]) ? $usermeta['yacht_location'][0] : "");
        $yacht_location_div =( $yacht_location != ""  ? "<div style='color:blue;'>Moored:  {$yacht_location}</div>" : "");
        
        $yacht_area_sailed     =( isset($usermeta['yacht_area_sailed']) ? $usermeta['yacht_area_sailed'][0] : "");
        $yacht_area_sailed_div =( $yacht_area_sailed != "" ? "<div>Area sailed: {$yacht_area_sailed}</div>" : "");
        
        $yacht_call_sign     =( isset($usermeta['yacht_call_sign']) ? $usermeta['yacht_call_sign'][0] : "");
        
        $phone = ( isset($usermeta['phone']) ? $usermeta['phone'][0] : "" );
        $phone_div =( $phone != "" ? "<div>Owners Phone: {$phone}</div>" : "");
        
        $display_name = $user->data->display_name;
        $display_name_div = "<div>Owner: {$display_name}</div>";
        
		$last_name = ( isset($usermeta['last_name']) ? $usermeta['last_name'][0] : "" );
        $last_name_div =( $last_name != "" ? "<div>Last Name: {$last_name}</div>" : "");

        $yacht_pic = "";
        $cover_photo = ( isset( $usermeta["cover_photo"]) ? $usermeta['cover_photo'][0] : "");  // could use line drawing e.g.: /images/mac-plan-rowan-22.jpg
        $cover_photo_path = "/wp-content/uploads/ultimatemember/{$user_id}/cover_photo.jpg";
        if ( ($cover_photo != "") &&  (file_exists( ABSPATH . $cover_photo_path) ) ) {
            $yacht_pic = $cover_photo_path;
        } else {
            $yacht_pic = ""; // "/images/default-yacht-pic.jpg";
        }
        $yacht_pic_div = ( $yacht_pic != "" ? "<div><img style=\"width:100%\" src=\"{$yacht_pic}\" title=\"{$yacht_name}\"></img></div>" : "");
        
		////////// Create the output table
        /* $output .= '
		<div class="user-boat-row">' .
			$yacht_name_div .
			$yacht_class_div .
			$yacht_rig_div .
			$yacht_sail_no_div .
			$yacht_engine_div .
			$desc_div .
			$yacht_location_div .
			$yacht_area_sailed_div .
			$yacht_longlat_div .
			$yacht_pic_div .
			
			$phone_div .
			$display_name_div .
            $user_id_div .
            $user_role_div .
      	'</div>'
		;
		*/
		$output .= 
				'"' . $yacht_class . '",' .
				'"' . strtoupper($yacht_name) . '",' .
				'"' . $yacht_rig . '",' .
				'"' . $yacht_sail_no . '",' .
				'"' . $yacht_engine . '",' .
				'"' . $yacht_location . '",' .
				'"' . $yacht_area_sailed . '",' .
				'"' . $yacht_longlat . '",' .
				'"' . strtoupper($last_name) . '",' .
				'"' . $display_name . '",' .
				'"' . $user_id . '",' .
				'"' . $user_role . '",' .
				'"' . $user_registered . '",' .
				'"' . $user_last_loggedin . '",' .
				'"' . $phone . '"' . "\n"
			;
    }
    $output = '"Boat class","Boat name","Rig","Sail No","Engine","Location","Area Sailed","Long/Lat","Last Name","Full Name","User ID","User Role","Reistered ","Last Logged In","User Phone"' . "\n" . $output;

	/*
	$output = '
		<div class="user-boat-item-container">
			<div class="user-boat-item-header">' .
				"<div>Boat name</div>" .
				"<div>Boat class</div>" .
				"<div>Rig</div>" .
				"<div>Sail No</div>" .
				"<div>Engine</div>" .
				"<div>Description</div>" .
				"<div>Location</div>" .
				"<div>Area Sailed</div>" .
				"<div>Long/Lat</div>" .
				"<div>Boat Image Link</div>" .
				"<div>User Phone</div>" .
				"<div>User Name</div>" .
				"<div>User Id</div>" .
				"<div>User Role</div>" .
			'</div>' .
			$output .
		'</div>
		<style>
			.user-boat-item-container {
				display:grid;
				grid-template-columns: 5% 5% 6% 5% 4% 5% .6fr .5fr .5fr .5fr .5fr .5fr .5fr .5fr .5fr ;
				max-width: 95%;
				margin:0px 1%!important;
			}
		</style>
		';
		*/

  // write errors to file to root of system
  $file = ABSPATH . '/moa-user-boat-list.csv' ;
  $fp = fopen( $file, 'w');
  fwrite($fp, $output );
  fclose($fp);
  
  //return $output;
  return 'File exported <a href="/moa-boat-list.csv">moa-user-boat-list.csv</a>';
}

/*****************
 * SHORTCODE TO RETURN USER DETAILS - ID, Name, or Role use as [moa-current-user id] or [moa-current-user role] etc.
 ****************/
add_shortcode("moa-current-user","moa_current_user_fn");
function moa_current_user_fn( $atts, $content ){
	
	if ( is_admin() ) return;				// bomb out if we are in admin mode

	if ( is_array($atts) ) {
		if( in_array( "id", $atts ) ) {						// List will be gallery names, maybe empty or comma delimited,  eg "Mac 26,Seaforth"
			return um_user('ID');
		}
		else if(  in_array( "name", $atts ) ) {						// List will be gallery names, maybe empty or comma delimited,  eg "Mac 26,Seaforth"
			return um_user('display_name');
		}
		else if(  in_array( "role", $atts ) ) {						// List will be gallery names, maybe empty or comma delimited,  eg "Mac 26,Seaforth"
			return um_user('role');
		}
		else {
			return 'atts field must be one of "id", "role" or "name"';
		}
	}
}
