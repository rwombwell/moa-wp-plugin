<?php
/************************
* LOAD MOA CONTACT US AJAX SCRIPT 
* Note that its necessary to 'Localise' this Ajax scripts to enable it to pass a WP nonce to the SP PHP 
* Ajax Receiver  
************************/
add_action( 'init', 'load_moa_contact_us_scripts' );        // init() fire after WP fully loaded, but before HTML headers created  
function load_moa_contact_us_scripts() {
    
    wp_register_script( "moa_contact-us_ajax", plugin_dir_url(__FILE__).'../js/moa-contact-us.js', array('jquery') );
    wp_localize_script( 'moa_contact-us_ajax', 'myAjax', array( 'ajaxurl' => admin_url('admin-ajax.php'), 'nonce'=>wp_create_nonce('ebsoc_nonce') ) );
    wp_enqueue_script ( 'moa_contact-us_ajax' );
    
    // wp_enqueue_style( 'moa-contact-style',     plugins_url( '../css/moa-contact-us.css' , __FILE__ ) );
    wp_enqueue_style( 'ebsoc-modal-styles', plugins_url( '../css/ebsoc-modal.css' ,   __FILE__ ) );
}

/********************************
 * MODAL WINDOW HANDLER - This is a wrapper that's installed into the WP FOOTER area. 
 * It includes skeleton HTML for a default popup window, hidden on startup, and made visible 
 * by a jQuery function launched by clicking on a menu item. 
 * Buried within the popup is a     
 * selected Ajax Login Plugin and jQuery functiosn to handle the modal popup.
 * Tested with 3 Ajax Login plugins:
 * 1.   "Login with Ajax" plugin - works fine, simple page, redirects to current page OK, detects is user already logged in
 *      & displays a logout link , configurable to include liks to registration page, password reset, etc.
 *      do_shortcode("[login-with-ajax registration=1]")
 * 2.   WP's default wp_login_form('echo' => false), works fine in popup, remeber to turn off 'echo' mode to return content
 * 3.   "AJAX Login & Registration Modal" Plugin, default page shows Login & Registration on tabbed page
 *      do_shortcode("[lrm_form default_tab=\"login\" logged_in_message=\"You are currently logged in!\"]") .
 *********************************/
add_action('wp_footer', 'ebsoc_footer_modal_wrapper');      // install the function into each page's footer area
function ebsoc_footer_modal_wrapper() {
    
    $html = '
<!-- MODAL WINDOW - place anywhere in the content, the JQ will load this from either <div class="show-in-modal" or href  -->
<div class="modal-wrapper styled hide">
	<div class="modal">
		<div class="close-modal"><i class="fa fa-times"></i></div>
		<div id="modal-content"></div>
   </div>
</div>
<!-- end modal-wrapper -->
    
 <!-- MODAL CONTACT US FORM - JS copies this to <div class="modal">...</div> -->
<div class="show-in-modal contact-us hide">' .
do_shortcode("[ebsoc-contact-us]") .
'</div>
<script>
    /***********************************
    * JS  script to handle opening and closing the Modal popup window, 
    * Same script can be used for opening different forms, e.g.: contact form, comments etc. by including
    * a Click event to link for the menu item to link it to the shortcode defined above    
     * Note cant be used for popup comment form because we dont have the page context.     
   ***********************************/
    jQuery(document).ready(function($) {
    	//////////////////////////////
    	// INSTALL jQUERY FUNCTION TO CLOSE MODAL WINDOW
        /////////////////////////////
    	$(".close-modal").click(function() {
    	  $(".modal").toggleClass("show").removeClass("profile").removeClass("register").removeClass("login").removeClass("comment");
          $(".modal-wrapper").toggleClass("show");
    	});
    
    	////////////////////////////
        // INSTALL MENU ITEM CLICK EVENT - this will launch the correct shortcode HTML block set up in shortcode 
        // note a problem with the modal popup  
    	// is that its too big for a small screen, so below we do a quick window size check   
        // and NOT install it screen width < 767px
        // Also note it supports different click invocations, for CONTACT US form, COMMENT form etc
        //////////////////////////// 
    	var $window = $(window);   // Detect windows width function
        function checkWidth() {
    		var windowsize = $window.width();
    		if (windowsize > 767) {
        		// if the window is greater than 767px wide then do below. we dont want the modal to show on
    			// mobile devices and instead the link will be followed.
    
                ///////////////////////
                // Install Click events for the Menu items, 
                // the class selectors below must match the classnames set on the WP Menu items
                //$(".menu-modal-login").click       ( {sourceDiv:"login"},      modalPopup );
                //$(".menu-modal-register").click    ( {sourceDiv:"register"},   modalPopup );
                //$(".menu-modal-profile").click     ( {sourceDiv:"profile"},    modalPopup );
    		      $(".menu-modal-contact-us").click  ( {sourceDiv:"contact-us"}, modalPopup );
    	
    		}
    	};
        checkWidth(); 				  // execute function to check width on load
    	$(window).resize(checkWidth); // execute function to check width on resize (eg Portait to Landscape)
    
        //////////////////////////////
        // modalPopup FUNCTION which copies the selected Form HTML into modal window for displaying 
        // depending on the element that invoked it (which we get from the "sourceDiv" variable)
        //////////////////////////////
        function modalPopup( e ) {
    
         	e.preventDefault(); 						// prevent further click events
            var sourceDiv = e.data.sourceDiv;           // the named parameter to the function is the
    		$(".modal").addClass( sourceDiv );          // add the target name "register", "profile" to modal div so CSS can know the form being popped up
            var contents = $( ".show-in-modal." + sourceDiv ).html();               // source - from div where the login shortcode has put the form content
    
			$(".modal").addClass("show", 1000, "easeOutSine");  // the target divs container - make it visible
			$(".modal-wrapper").addClass("show");               // together with its background (this masks rest of the page)
    
            var modalContent = $("#modal-content");             // target - div to stuff the content (ie login form)
			modalContent.html("loading..."); 				    // display loading animation or in this case static content
    		modalContent.html( contents ); 					  // fill the now-visible modal target with the form contents
			// modalContent.load( contents + " #modal-ready")   // OR, use the .load() function to get dynamic content from the server
    
            // if modal scrolled out of sight this call animates a slow scroll to the top of page bringing the modal into view
			//$("html, body").animate({ scrollTop: 0}, "slow");
    
			return false;
    	}
    
    });	/********** end of document.ready() function  *********/
    
</script>';
    
    echo $html;
    
}

/***************************
 * AJAX "CONTACT US" FORM RECEIVER - called from the JS Ajax call made when Contact Form Submit button clicked
 * The call presents a nonce which si checked, (note producing a nonce requires Localizing the JS script
 * (see next function below). 
 * This code validates the fields and if failure returns field data in error message
 * If fields OK then this code sends an email to web site administrators and returns a success code that includes
 * a success message in an HTML blcok for teh calling AjaxJS script to insert into the form and make visible     
 ***************************/
add_action("wp_ajax_post_contact_us", "contact_us_form_callback");
add_action("wp_ajax_nopriv_post_contact_us", "contact_us_form_callback");
function contact_us_form_callback(){
    
    $params = array();
    parse_str($_POST['data'], $params);
    
    // NONCE - verify, note nonce was set earlier on 'ebsoc-ajax.js' script by locationion done in 'ebsoc-maps.php:LoadHeaders()'
    if ( !wp_verify_nonce( $_POST['nonce'], "ebsoc_nonce"))
        exit("nonce not verified, Ajax response cancelled");
        
        
    $name = trim($params['name']);
    $email = $params['email'];
    $message = $params['message'];
    $subject = $params['subject'];
    
    if ($name=="") {
        $error['name'] = "Please enter your name";
    }
    
    //if (!preg_match('/^[a-z0-9&.-_+]+@[a-z0-9-]+.([a-z0-9-]+.)*+[a-z]{2}/is', $email)) {
    //   $error['email'] = "Please enter a valid email address";
    //}
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error['email'] = "Invalid email format";
    }
    
    if ($message== "") {
        $error['message'] = "Please leave a comment.";
    }
    if ($subject=="") {
        $error['subject'] = "Please leave a subject.";
    }
    
    // The form's correct so process it, ie send an email
    if (!$error) {
        // Send an email and redirect user to "Thank you" page.
        $emailNoReply = "no-reply@macwester.org.uk";
        $emailTo    = get_option("admin_email");
        $emailExtra = get_option("contact_us_email");
        
        // debug to known addressess
        // $emailTo    = "rwombwell@daemon.co.uk";
        //$emailExtra = "rob.wombwell@gmail.com";
        
        if (isset($emailExtra) ){
            $emailTo = $emailTo . "," . $emailExtra;
        }
        
        $subject = "MACWESTER OWNERS ASSOCIATION WEBSITE CONTACT FORM From " . $name;
        $body =
        "The following user submitted a Contact Form\n\n" .
        "Name: "        . $name .
        "\nEmail: "     . $email .
        "\nSubject: "   . $subject .
        "\nComments: "  . $message .
        "\n\nYou should respond to them by email.\n\n" .
        "This message was generated by the www.macwester.org.uk web site contact-us web page";
        
        $headers = "From: macwester.org.uk <". $emailNoReply . ">" . "\r\n" . "Reply-To: " . $emailNoReply;
        
        $ret = wp_mail($emailTo, $subject, $body, $headers);
        
        $result['type'] = "success";
        $result['success'] =
'<div class="success">
    <img class="success-img" src="/images/maclogo_curly.gif"></img>
    <div class="success-msg">
        Thank you for your input ' . $name . '<br><br>
        Your email has been successfully sent and we will get back to you as soon as we can.<br><br>
        In the meantime, please continue to enjoy browsing this website, (click the top-right "x" to close this window)
    </div>
</div>';
        echo json_encode($result);
        // wp_redirect( site_url() . '/thankyou' );
    } # end if no error
    else {
        echo json_encode($error);
    } # end if there was an error sending
    
    die();          // this is required to return a proper result
}

/****************
 * AJAX CONTACT FORM,
 *  from here http://www.phpcmsframework.com/2014/08/create-simple-wordpress-ajax-contact.html
 ***************/
add_shortcode("ebsoc-contact-us", "ebsoc_contact_us_shortcode");
function ebsoc_contact_us_shortcode( $atts, $content = null) {
    
    $html = '
<div id="ebsoc-contact-form-success" class="success-response"></div>
<form id="ebsoc-contact-us" class="ebsoc-contact-us" method="post" action="#" name="contact-form" id="contact-form">
    <h3>Contact Us</h3>
   <img class="flag-img" src="/images/maclogo_curly.gif"></img>   
   <p class="intro">If you would like to make a comment or observation on this website
    please complete this email form and we will get back to you</p>
    <div id="main">
        <div class="fullwidth">
            <label>Name:*</label>
            <span id="name-response" class="error-response"></span>
            <p><input id="name" type="text" name="name" size="40" /></p>
        </div>
        <div class="fullwidth">
            <label>Email:*</label>
            <span id="email-response" class="error-response"></span>
            <p><input id="email" type="text" name="email" size="100" /></p>
        </div>
        <div class="fullwidth">
            <label>Subject:*</label>
            <span id="subject-response" class="error-response"></span>
            <p><input id="subject" type="text" name="subject" size="30" /></p>
        </div>
        <div class="fullwidth">
            <label>Comments:*</label>
            <span id="message-response" class="error-response"></span>
            <p><textarea id="message" name="message" cols="30" rows="5"></textarea></p>
            <p><input  class="contact_button button" type="submit" name="submit" id="submit" value="Email Us!" /></p>
        </div>
        
    </div>
</form>
        
<script  type="text/javascript">
jQuery(document).ready(function($) {
    // this code executed when contact form submit button clicked
    jQuery( ".ebsoc-contact-us" ).submit( function(event ){
        AjaxContactUs( event, this );       // fire Ajax request to PHP code that handles it, code in ..\js\ebsoc-contact-us.js
    });
        
    // this code executed to validate each field as user leaves them 
    $(".ebsoc-contact-us #email").blur(validateFlds);
    $(".ebsoc-contact-us #name").blur( validateFlds );
    $(".ebsoc-contact-us #subject").blur( validateFlds );
    $(".ebsoc-contact-us #message").on("input", validateFlds );
     validateFlds();
        
    //////////////////////////////////
    function validateFlds() {
        
        // validate Email
        var emailOk = false;
        var emailReg = /^([\w-\.]+@([\w-]+\.)+[\w-]{2,4})?$/;
        var emailaddress = $(".ebsoc-contact-us #email").val();
        if( emailaddress ) {
            if(!emailReg.test(emailaddress)) {
                $(".ebsoc-contact-us #email-response").html("<font color=\"#cc0000\">Email address must contain @ sign, and no spaces</font>");
            } else {
                $(".ebsoc-contact-us #email-response").html("<font color=\"#cc0000\"></font>");
                emailOk = true;
            }
         } else {
                $(".ebsoc-contact-us #email-response").html("<font color=\"#cc0000\"></font>");
         }
        
        // validate name
        var nameOk = ( $(".ebsoc-contact-us #name").val() != "");
        
        // validate subject
        var subjectOk = ( $(".ebsoc-contact-us #subject").val() != "");
        
        // validate comments
        var commentsOk = ( $(".ebsoc-contact-us #message").val() != "");
        
         if ( nameOk && emailOk && subjectOk && commentsOk) {
               $(".ebsoc-contact-us #submit").prop("disabled", false).css({"background-color":"maroon"});
        } else {
                $(".ebsoc-contact-us #submit").prop("disabled", true).css({"background-color":"grey"});
        }
        
    };
        
});	/********** end of document.ready() function  *********/
</script>';
    
    return $html;
}
