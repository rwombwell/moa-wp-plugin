<?php
/*************************
 * COMMENT FORM SHORT CODE - Add shortcode for comment form so it can be called from MOA popup
 * see https://wordpress.stackexchange.com/questions/177234/how-to-display-the-comment-form-with-a-shortcode-while-removing-it-from-its-defa
 * Called from onepress menu comments.php snippet, where call to  
 *  comment_form(); is commented out and replaced with call 
 *  echo do_shortcode( "[moa-popup-window button=\"Add a Comment\" shortcode=\"moa-comment-form\" ]" ); 
 *  This shortcode converts the comment form fro an echo that's immedaitly displayed into a string that we can pass to the popup routine
 ************************/
add_shortcode( 'moa-comment-form', 'moa_comment_form_shortcode' );
function moa_comment_form_shortcode() {
    ob_start();
    comment_form();                     // gets the default comment form into the HTML buffer - ready to display
    $cform = ob_get_contents();         // copies  the display into a variable 
    ob_end_clean();                     // empties the display buffer
    return $cform;                      // returns the buffer contents instead of displaying it 
}

add_shortcode( 'moa-popup-window', 'moa_popup_window_fn' );
function moa_popup_window_fn( $atts , $content) {
    /*******************
     * Load Popup Javascript for Modal Window with accompanying stylesheet into Footer
     * Expects rags
     *   shortcode = "....." (e.g.: "bbp-topic-form...") then fills the popup with the shortcode output
     *   btn-text  = text to show on popup launch button (e.g.: btn-text="New Topic")
     *******************/
    
    // get button text from the moa-popup shortcode argument
    $btn_text =  ( isset($atts['button']) )
    ? $atts['button']
    : 'Open Popup' ;
    
    
    // get shortcode to run from the moa-popup shortcode argument
    // The shortcode we are calling returns the comment form as a string in a variable 
    if ( isset($atts['shortcode']) ) {
        $popup_contents = do_shortcode( "[" . $atts['shortcode'] ."]" );
    } else {
        $popup_contents = '<p>MOA Popup Window - no content specified</p>';
    }
    
    // create a <div ...> block for the modal popup window and emebed the shortcode output
    $output = '
    <!-- MOA - Popup Window Content, inserted from moa-comment-popup.php-->
    <button id="moa-popup-window-btn">'. $btn_text .'</button>
    <div id="moa-popup-window" class="moa-popup-window">
        <div class="moa-popup-window-content">
            <span class="moa-popup-window-close" title="Close">&times;</span>
            <!-- Modal popup content goes here --> ' .
            $popup_contents . '
          </div>
    </div>
    <script>
    /***********************
     * Javascript to manage opening and closing of modal popup windows. 
     * To use ensure
     *  	popup window enclosing div ID   = "moa-popup-window" 
     * 		popup window close span CLASS   = "moa-popup-window-close"
     * 		popup window launch button ID   = "moa-popup-window-btn"
     **********************/
    jQuery(document).ready( function() {
    	// get objects for the modal popup, the close button and the open button
    	var modal = document.getElementById("moa-popup-window");		             // Get the modal Windows element
    	var close = document.getElementsByClassName("moa-popup-window-close")[0];	 // Get the top right (x) CLose button (closes popup)
    	var btn = document.getElementById("moa-popup-window-btn");					 // Get the button that will launch modal popup
    	
    	// define  actions if the above items clicked
    	btn.onclick = function() { modal.style.display = "block"; }	//Makes login popup visible when user clicks the launch button
    	
    	close.onclick = function() { modal.style.display = "none"; } //closes the popup window by clicking (x) 
    
    	window.onclick = function(event) { 		// closes the popup window whenever user clicks anywhere outside of the modal popup
    		if (event.target == modal) {
    			modal.style.display = "none"
    		}
    	} 

    });
    </script>
     <!-- MOA - Popup Window Content ends -->';
            
    //wp_enqueue_script( 'moa-popup-window-js', plugins_url( '../js/moa-popup-window.js' , __FILE__ ) ,NULL,NULL,TRUE );
    wp_enqueue_style( 'moa-popup-style',     plugins_url( '../css/moa-popup-styles.css' , __FILE__ ) );
      
    return $output;
            
}

/* experiment to see is we can add extra stuff to the header of each reply, and we can, but need the float:right to move it to the right
*/
add_action( "bbp_theme_before_reply_admin_links", "bbp_theme_before_reply_admin_links_fn", 10,1);
function bbp_theme_before_reply_admin_links_fn() {
	// echo '<span style="float:right;">REPLY>' . bbp_get_form_reply_to().'</span>';
    // echo '<span style="float:right;">REPLY></span>';
    // echo '<span style="float:right;">' .  do_shortcode( "[moa-popup-window button=\"Reply\" shortcode=\"bbp-reply-form\"]" ) . '</span>';
	    
}
