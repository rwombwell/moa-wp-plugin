<?php

/////////////////////////////////////////////////////////////////////////////////
// MOA Hooks for CF7 Functions
// See this page for CF7 hooks  http://hookr.io/plugins/contact-form-7/4.5.1/hooks/#index=a
/////////////////////////////////////////////////////////////////////////////////

//////////////////////
// define( 'MOA_CF7_MEMBERSHIP_ADDR_FORM_TITLE', 'Membership Form - Address Part');
define( 'MOA_CF7_MEMBERSHIP_ADDR_FORM_ID', 1052);
define( 'MOA_CF7_YACHT_FORM_ID', 3077);
/////////////////////

/////////////////////
// Note - Out of the box, CF7 forms don't execute shortcodes within a CF7 form. If that's needed, then  follw the advice 
// advice in the  blog below, and use WP "add_filter" call to include "do_shortcode()" in the CF7 execution chain
// https://wordpress.stackexchange.com/questions/45266/how-to-use-other-shortcodes-inside-contact-form-7-forms
// UPDATE: The MOA no longer depends on this, being able to exec shortcodes was needed when we were expanding the
// [moa_paypal...] shortcodes inside CF7 forms, but we've since moved all moa shortcodes to the outer page that calls the CF7
// form. Anyway we've kept the addition of the shortcode filter inside the CF7 for.
//////////////////////////////
add_filter( 'wpcf7_form_elements', 'do_shortcode' );


//////////////////////////////////////////////////////////////////////////////////////
add_action('wpcf7_before_send_mail', 'moa_update_user_data_from_cf7_form', 1);
function moa_update_user_data_from_cf7_form( $cfdata ) {
// This function is called after a CF7 Submit Button is hit but before the form's mail is sent
// The function parameter $cfdata holsd an array of cf7 form data, inc the field values.
// Note that within a CF7 we can't get the current user using the "wp_get_current_user()" call - for 
// some reason this returns a FALSE!. Apparently that's because the form may be exectuted by a 
// non-logged in user (daft!). The recommended work-around is to always provide a "login_name" field
// in the  form then get the user's name from the $formdata elements. This is discussed in the blog
// https://www.mootpoint.org/blog/wordpress-contact-form-7-user-registration/
// Another fix is to  set the flag "subscribers_only: true" in the CF7 form>>Additional Details, and then 
// wp_get_current_user() works as expected. Then we have to then ensure  the form can only be executed by logged-in users
    
    //////////////////////////////////////
    // get $formdata array of fields from $cf7 general array 
    if (!isset($cfdata->posted_data) && class_exists('WPCF7_Submission')) {
        $submission = WPCF7_Submission::get_instance();
        if ($submission) {
            $formdata = $submission->get_posted_data();
        }
    } elseif (isset($cfdata->posted_data)) {
        // For pre-3.9 versions of Contact Form 7
        $formdata = $cfdata->posted_data;
    } else {
        // We can't retrieve the form data so just abort
        return $cfdata;
    }

    ////////////////////////////////////////
	// Get the user's details in preparation for saving their data
	// this is how we usually get the current user, but beware, this dosn't work inside a CF7 form
	// so we have to embed a "[hidden user_login default:user_login] in the form and get user info as below
	// $current_user= wp_get_current_user();  
	// $user_id = $current_user->ID;
	$user_login = $formdata['user_login'];
	$userdata = get_user_by('login', $user_login);
	$user_id = $userdata->ID;
    if ( $user_id == FALSE ) {		// check the user's id is not null (ie user not logged in)
        return "Form can only be run by registered users";
    }
  
    ////////////////////////////////////////
	// Save User's ADDRESS Information
    // Check if this is the ADDRESS form using the form's Id, 
    if ( strtolower( $cfdata->id() ) == strtolower(MOA_CF7_MEMBERSHIP_ADDR_FORM_ID)) {
		// it is, so update the user's address  fields
	/* 	update_user_meta( $user_id, 'addr_line1',    $formdata['addr_line1'] );
		update_user_meta( $user_id, 'addr_line2',    $formdata['addr_line2'] );
		update_user_meta( $user_id, 'addr_town',     $formdata['addr_town'] );
		update_user_meta( $user_id, 'addr_county',   $formdata['addr_county'] );
		update_user_meta( $user_id, 'addr_postcode', $formdata['addr_postcode'] );
		update_user_meta( $user_id, 'addr_country',  $formdata['addr_country'] );
 */
	   // loop thru form keys and save all keys beginning "addr_..."
        foreach ($formdata as $key => $value) {
		    if ( substr( $key , 0, 5 ) === "addr_") {
		        update_user_meta( $user_id, $key,  $value );
		    }
		}
    }

    ////////////////////////////////////////
	// Save User's YACHT Information
    // Check if this is the YACHT form using the form's Id, 
    if ( strtolower( $cfdata->id() ) == strtolower(MOA_CF7_YACHT_FORM_ID)) {
		// it is, so update the user's address  fields
		/* update_user_meta( $user_id, 'yacht_name',   	$formdata['yacht_name'] );
		update_user_meta( $user_id, 'yacht_prev_name',  $formdata['yacht_prev_name'] );
		update_user_meta( $user_id, 'yacht_class',     	$formdata['yacht_class'] );
		update_user_meta( $user_id, 'yacht_location',   $formdata['yacht_location'] );
		update_user_meta( $user_id, 'yacht_area_sailed_in',   	$formdata['yacht_area_sailed_in'] );
		update_user_meta( $user_id, 'yacht_longlat',   	$formdata['yacht_longlat'] );
		update_user_meta( $user_id, 'yacht_desc',   	$formdata['yacht_desc'] );
     */
        // loop thru form keys and save all keys beginning "yacht_..."
        foreach ($formdata as $key => $value) {
		    if ( substr( $key , 0, 6 ) === "yacht_") {
    		    update_user_meta( $user_id, $key,  $value );
		    }
		}
    }

	return $cfdata;
}

////////////////////////
add_action( 'wpcf7_mail_sent', 'moa_action_wpcf7_mail_sent', 10, 1 );
function moa_action_wpcf7_mail_sent( $contact_form ) {
// Hook called after email sent, but not used here
//$url = 'https://www.sandbox.paypal.com/cgi-bin/webscr?cmd=_s-xclick&hosted_button_id=X3HVPKCSGJGBL';
// $url = site_url() . '/profile';
    return ($contact_form );

};


//////////////////////
add_action( 'wp_footer', 'moa_mycustom_wp_footer' );
function moa_mycustom_wp_footer() {
// CF7 function - Adds the several Javascript function to the page footer. These do the following
// JS script set by moa-paypal/includes/moa-cf7-functions.php plugin - two parts (a) and (b) below 
// (a) JS toggle function on the Submit button to prevent a double click
// (b) A JS URL redirect the Submit button hit.
 //
    $x=1;		// marker line for debug breakpoint
    // turn off PHP so we can write Javascript literally
    ?>
<script type="text/javascript">
// (a) Javascript toggle to enable/disable the WPCF7 form submit button to prevent a user hitting it many times
// Based on this page https://medium.com/@theonlydaleking/preventing-multi-submit-with-wordpress-cf7-contact-form-7-ef48e1e3372b
var disableSubmit = false;
var textSubmit = "";
jQuery('input.wpcf7-submit[type="submit"]').click(function() {
    jQuery(':input[type="submit"]').attr('value',"Saving...")
    textSubmit = $(this).attr('value');
    if (disableSubmit == true) {
        return false;
    }
    disableSubmit = true;
    return true;
})
var wpcf7Elm = document.querySelector( '.wpcf7' );
if ( wpcf7Elm != null ) {
    wpcf7Elm.addEventListener( 'wpcf7submit', function( event ) {
        jQuery(':input[type="submit"]').attr('value', textSubmit )
        disableSubmit = false;
    }, false );
}

// (b) JS script to redirect to URL CF7 form after the Submit button hit. This uses the DOM to link a target location to 
// the CF7 'wpcf7mailsent' event, (triggers after the forms' saving has been done). This is the recommened  
// way to redirect (see CF7 web help. We check the CF7 FormId against that of "Membership Application Form" 
// to ensure the form then goes to the PayPal link 
document.addEventListener( 'wpcf7submit', function( event ) {
	if ( event.detail.contactFormId == 1052)
	location = '<?php echo  site_url() . '/' ; ?>';
}, false );
</script>
<?php
// All JavaScript done, so turn PHP back on again 
}

    
    
 