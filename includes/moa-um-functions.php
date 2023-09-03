<?php
/******************************
 * THIS MODULE HAS CODE MODIFICATIOSN FOR "ULTIMATE MEMBER" PLUGIN AND "LRM LOGIN AND REGISTRATION POPUL MODAL" PLUGIN  
 * bBoth these are used in EBSOC and MOA websites 
 *****************************/

/***********************************
* These Ultimate Member FormIds are used in the code below. Change as needed  
************************************/
define ( 'MOA_UM_MEMBERSHIP_FORM_ID',   2780 ); // id of the UM Membership form, neeeded to invoke various database settings 
define ( 'MOA_UM_REGISTRATION_FORM_ID', 1043 ); // id of the UM Registration form
define ( 'MOA_UM_PROFILE_FORM_ID', 251 ); // id of the UM Registration form

/***********************************
* MENUS - When a page has a UM Restricted flag set the WP Menus display the title "Restricted Content". This filter turns that off
* see here: https://wordpress.org/support/topic/the-new-ultimate-plugin-blocked-the-restricted-menu-titles/
************************************/
add_filter( 'um_ignore_restricted_title', '__return_true' );

/*************************
 * USER ACCOUNTS PAGE - Allow users to delete account without password - necessary for Social Media accounts where the pwd was randomly set
 * by the Social media login and so user doens;t know the account. Applies across the board to user-initated self-deletion, and so 
 * works on the UM Account> >Delete pages as well!
 * taken from here,https://wordpress.org/support/topic/delete-account-without-password-2/
 **************************/
add_filter('um_account_delete_require_current','__return_false');

/**************************
* ADD HIDE-SHOW PASSWORD TO UM LOGIN FORMS
* A problem with this is that the "show-hide" link displays in its own <div> below the password field, not in it. Could fix this by making 
* the div position=absolute and shifting it up a bit, then replacing the <a> text block with the eye.
* See https://www.champ.ninja/2020/05/show-passwords-feature-in-ultimate-member-forms/
* Note: the LRM Login Module has an inbuilt hide-show password eye (in the right place too!) 
* The way suggested uses the a tag to click on, we've improved on this by letting the user click on the eye icon to toggle the pwd.
**************************/
add_filter("um_confirm_user_password_form_edit_field","um_user_password_form_edit_field", 10, 2 );
add_filter("um_user_password_form_edit_field","um_user_password_form_edit_field", 10, 2 );
function um_user_password_form_edit_field( $output, $set_mode ){
    
  /* // This is the link using an atag as the clickable item  
    ob_start();
     ?>
    <div id='um-field-show-passwords-<?php echo $set_mode;?>' style='text-align:right;display:block;'>
    	<i class='um-faicon-eye-slash'></i>
    	<a href='#'><?php _e("Show password","ultimate-member"); ?></a>
    </div>
    <script type='text/javascript'>
	    jQuery('div[id="um-field-show-passwords-<?php echo $set_mode;?>"] a').click(function(){ 
		 
            var $parent = jQuery(this).parent("div"); 
            var $form = jQuery(".um-<?php echo $set_mode;?> .um-form");

		    $parent.find("i").toggleClass(function() {
		    	if ( jQuery( this ).hasClass( "um-faicon-eye-slash" ) ) {
	                $parent.find("a").text('<?php _e("Hide password","ultimate-member"); ?>');
		    		jQuery( this ).removeClass( "um-faicon-eye-slash" )
		    		$form.find(".um-field-password").find("input[type=password]").attr("type","text");
		    	   return "um-faicon-eye";
			    }
				 
				jQuery( this ).removeClass( "um-faicon-eye" );
				$parent.find("a").text('<?php _e("Show password","ultimate-member"); ?>');
			    $form.find(".um-field-password").find("input[type=text]").attr("type","password");
			  
                return "um-faicon-eye-slash";
			});

		    return false; 

		});
	</script>
    <?php 
	return $output.ob_get_clean();
    */
    // This is the code to let the eye icon be the clickable area
    ob_start();
    ?><div id='moa-um-field-show-passwords-<?php echo $set_mode;?>' 
        style='
            text-align:right;
            display:block;
            position:relative;
            margin-right:10px;
            top:-2.0rem;
            font-size:1.5rem;
            cursor: pointer; 
            height:0px; /* really important, the posn=relative lifts the eye up but height=0 means the span doesnt take up any space*/
        '>
    	<span class='moa-toggle-password um-faicon-eye-slash' title='Click to reveal/hide password'></span>
    </div>
    <script type='text/javascript'>
    
        /*
        debugger;
        var eyeFld = jQuery('.moa-toggle-password');
        var pwdFld = jQuery('input[type=password]');
        
        if ( pwdFld ) {
            pwdFld.parentNode.insertBefore( eyeFld, pwdFld.nextSibling); 		// insert button immediately after callback field
		// get pwdFld field's height and font 
		var h = pwdFld.offsetHeight;								
		var f = jQuery(pwdFld).css('font-size');							
		 //  set eye to match these 
		btn.setAttribute("style", 'padding: 0px 5px 0px 5px; height:' + h + 'px; font-size:' + f);
		// locate the Button at same vertical position and horizontal position just to right of box
		callbackFld.setAttribute("style", 'width: calc(100% - 3.5em); display: inline !important; margin-right: 5px !important;');
        }
        */    
        
        $("body").on('click', '.moa-toggle-password', function() {
            $(this).toggleClass("um-faicon-eye-slash um-faicon-eye");
            var input = $(".um-field-password input");
            if (input.attr("type") === "password") {
                input.attr("type", "text");
            } else {
                input.attr("type", "password");
            }
        });


    </script>
   
    <?php 
	return $output.ob_get_clean();

}

/*****************
 * EMPTY PROFILE FIELDS - Note that Empty fields on the profile form are by default hidden when in 
 * "view" mode.  The code snippet below display displays the fallback value for the empty fields. 
 * It works only for the "view" mode. Taken from 
 * https://docs.ultimatemember.com/article/1548-how-to-display-fallback-value-if-the-field-value-is-empty  
 * *************/
/**
 * Displays fallback value if the field value is empty. For the "view" mode only.
 * @param  string $value
 * @param  string $default
 * @param  string $key
 * @param  string $type
 * @param  array  $data
 * @return string
 */
function um_field_value_fallback( $value, $default, $key, $type, $data ) {
    
    if ( empty( $value ) && isset( UM()->fields()->viewing ) && UM()->fields()->viewing === true ) {
        $fields_without_metakey = UM()->builtin()->get_fields_without_metakey();
        if ( !in_array( $type, $fields_without_metakey ) ) {
            $value = '(not set)';
            $value = '&nbsp';
        }
    }
    
    return $value;
}
add_filter( 'um_field_value', 'um_field_value_fallback', 50, 5 );

/**************************
 * UM FORMS FIELD LEVEL HOOKS
 * Validation for 'yacht_longlat', ensures that the input is either empty or a valid Long Lat pair in
 * comma separated decimal format, e.g.: '1.207262, 51.87852'
 *  Calling this function requires setting the field level validation in the UM Form editor to
 *  "Custom Validation" with "Custom Action" = "yacht_longlat" (the same as the suffic in the hook call
 * @param string $key   the vame of the field being validated
 * @param array  $array all properties of the field called $key
 * @param array  $args  returned properties of the form, inc field names and values as entered
 **************************/
function moa_custom_validate_longlat( $key, $array, $args ) {
    
    if ( !isset( $args[$key] )  || empty( trim($args[$key]) ) ) {
        return;
    }
    $longlat = ParseDD( $args[$key] );   // converts input longlat to DD format if possible, returns "" if invalid
    
    if ( empty( $longlat ) ) {
        UM()->form()->add_error( $key,  __( 'Expects comma separated decimals, Longitude first, e.g. 1.23456, 45.6789 - best to use MAP button to get coords', 'moa' ));
    } else {
        // UM()->form()->post_form[ $key ] = $longlat;
        UM()->fields()->edit_field( $key, $longlat );
    }
}
add_action( 'um_custom_field_validation_yacht_longlat', 'moa_custom_validate_longlat', 30, 3 );


/////////////////////////////////////////////////
// UM REGISTRATION PROCESS
// New Member Application Form, which requires a PayPal link
// Note still testing this, but the code below is called from a Registration Form,
// when the user hits the submit button, before the fields are validiated
// Below is the order these hooks are executed when Submit button hit
/////////////////////////////////////////////////

//////////////////////////////////////
// UM MEMBERSHIP FORM - REDIRECT TO NEXT PAGE 
// Uses a UM hook explained  here: https://docs.ultimatemember.com/article/1646-redirect-to-a-page-after-registration
// This hook is called at the end of the UM registration process, (but before welcome emails sent etc)
// So here we check (a) is the UM Form the Membership Form? and (b) and has "Full Membership requre"d" been set in the args (done by a hideen field on the form).
// If the latter then we auto_login the user which is needed for the CF7 Subscription Form that we reirect to
// The $args[] arry holds all the UM form and user registration details
add_action( 'um_registration_complete', 'moa_registration_complete', 10, 2 );
function moa_registration_complete( $user_id, $args ) {
   
    // Check if we need to rebuild the KML MOA boat Map Overlay File 
    CheckOverlayRebuildNeeded();
    
    // 'club_membership_wanted' is a hidden field that's set on the Members form NOT the registration form 
    if ( ($args['form_id'] == MOA_UM_MEMBERSHIP_FORM_ID) && (strtolower( $args['club_membership_wanted']) == "yes")  ) {
        //UM()->user()->set_registration_details($submitted, $args);    // don't need to do this, already done
        UM()->user()->auto_login($user_id);
        UM()->user()->generate_profile_slug( $user_id );
        
        /////////////////////////////
        // ...->email_pending() method does the following
        // assigns new user's activation key and sends it in awaiting email confirmation, 
        // 2023 update - now we've installed Ajax Login & Registration popup module we don't want to do email activation
        // We could here change user's status from "awaiting email confirmation to "approved", so user doesn't get an email
        ///////////////////////////
        // UM()->user()->email_pending();                              
        // UM()->user()->set_status( 'approved' );     // but we dont
        
        // MOA adds send admin email that user has registered
        um_send_registration_notification( $user_id, $args );
      
        // head back to the page we've come from, this displays user's new logged in status and continues the process
        exit(  wp_redirect( home_url( '/office/membership-form') )  );     // exit directly to membership form

    } else {
        // exit(  wp_redirect(site_url() )  );     // we let the user get sent an activation email
    }
}

/*********************
 * RESTRICTED ACCESS MESSAGE - CUSTOMISED TPO HANDLE DIFFERENT ROLES
 * Ultimate Member has a global access msg set here: WP Admin > Ultimate Member > Settings > Access > see “Restricted Access Message”.
 * The problem is that this msg doesn't distinuigish which role, triggered the access denial, eg: visitor or Registered
 * So, the solution, (taken from https://wordpress.org/support/topic/shortcode-in-custom-restrict-content-message/) is to embed a shortcode in the global message text which distinguishs the roles and gives different access denial messages. 
 
*  - Add programatic content which picks up the current page permalink
 * and stuffs this into the restricted warning with <a...> tags for Sign Up and Login, inserting the
 * all important "redirect_to" tag attribute which is what UM uses to do the final redirect.
 * Requires 2 stages,
 *   1. Add a filter to set "um_get_filter__restricted_access_message", pointing to a shortcode
 *
 * Set value in WP Admin > Ultimate Member > Settings > Access > see “Restricted Access Message”
 *   then just set one (or more) shortcodes to be executed, e.g.: "[set restricted access-message]" .
 *   
 * Added Mar 2023
 ************************/
 // 1. Here the add_filter to set up the shortcode
 add_filter("um_get_option_filter__restricted_access_message", function( $value ){
    // $value is the full text of the restricted message, including any shortcodes
    if( is_admin() ) return $value;     // we need this bomb-out for the admin pages 
    
    if ( version_compare( get_bloginfo('version'),'5.4', '<' ) ) {
        return do_shortcode( $value );
    } else {
        return apply_shortcodes( $value );
    }
    return $value;
} );

// 2. Next we add a  shortcode which checks for the denyed user's role and produces the appropriate response 
add_shortcode("moa-um-restricted-message-shortcode", "um_restricted_message_shortcode_fn");
function um_restricted_message_shortcode_fn( $atts, $content ){
    
    $userId = UM()->user()->id;
     
    // get the roles that this page is restricted to
    $pageId = get_the_ID();
    // echo's the title, NB, if we try and get it via "get_the_title( $pageId ); we get "Restricted Content", i.e.: its already been modified by UM
    
    $title = get_the_title(); 
    $accessArr = UM()->access()->get_post_privacy_settings( $pageId);
    $rolesArr = $accessArr["_um_access_roles"];    // returns array of roles ["um_moa-member","um-moa-admin"] etc.
    
    // we expect three levels of restriction, where the array of restricted roles is set to one of:
    // MOA Admin Only = ["um-moa-admin"] 
    // MOA Admins and MOA Members = ["um-moa-admin","um_moa-member"] 
    // MOA Admins and MOA Members and Registered = ["um-moa-admin","um_moa-member","um_registered"]
    // So we give a different restricted message depending on the page's levels, also
    // whether the user is logged in, ($userId=0 if not logged in), i.e.: if a registered user is logged in
    // and being prevented from access because he's not moa-member, then we don't want to offer a login option!
    //
    // note the code below fires off the registration page, not the LRM popup registration code. To change this 
    // to invoke the LRM popup simply set the class on the link tag to "<a class="lrm-register" ...> etc.
    $restricted_msg = '<h2>' . $title . '</h2>' .
     '<p>You need to be Registered to access this page. Register (for free) here
<a redirect_to=' . get_permalink() . ' href="/office/registration-form">Register</a>.</p>
<p>Login here if you already have an account <a class="lrm-login" redirect_to=' . get_permalink() . ' href="/login">Login</a>.</p>
<p>If you want to join the Association see here for benefits and joining instructions
<a href="/office/membership-application-details">Join the Association</a></p>';
    
    $login_msg = ( !$userId ) ? 'Click here to <a class="lrm-login" redirect_to=' . get_permalink() . ' href="/login">Login</a> if you have an account, or ' : "";
    
    $member_msg = ' <h2>' . $title . '</h2>' . 
    '<p>You need to be a Full Member to access this page. ' . $login_msg . 'if you are not yet a paid up member of the Association, see here for benefits and joining instructions
<a href="/office/membership-application-details">Club Membership Details</a></p>';
        
    if ( array_key_exists("um_registered", $rolesArr)) {
        return $restricted_msg;
    } 
    return $member_msg;    


};
      
add_filter( 'um_user_register_submitted__email', 'moa_user_register_submitted__email', 10, 1 );
function moa_user_register_submitted__email( $user_email ) {
    return $user_email;
}



/*
////////////////////////////////////////////////////
// ADD A PROFILE PHOTO CAPABILITY TO THE REGISTRATION FORM - added Mar 2023 and NOT tested yet
// This creates a new field call 'Profile Photo' that can be added to the Registeration form
//  
// THERE ARE 2 FUNCTIONS NEEDED FOR THIS, ONE TO ADD THE FIELD, the SECOND TO SAVE IT  
//   1. CREATE THE NEW FIELD in UM Form Builder - ready to be added to the form, it's called "Profile Photo" .
//      Note to do the same for "cover photo" just shange refernces to "profile photo! 
//  2.  SAVE THE FEILD as a UM Photo - Multiply Profile Photo with different sizes
//
// taken from this ref: https://gist.github.com/champsupertramp/a7ce812c702865cb973445c9fe7a9544 which is a part
// and this explanatory ref on the above:   
// of other useful UM extensions listed here: https://github.com/ultimatemember/Extended
///////////////////////////////////////////////////
add_filter("um_predefined_fields_hook","um_predefined_fields_hook_profile_photo", 99999, 1 );
function um_predefined_fields_hook_profile_photo( $arr ){
    
    $arr['profile_photo'] = array(
        'title' => __('Profile Photo','ultimate-member'),
        'metakey' => 'profile_photo',
        'type' => 'image',
        'label' => __('Change your profile photo','ultimate-member'),
        'upload_text' => __('Upload your photo here','ultimate-member'),
        'icon' => 'um-faicon-camera',
        'crop' => 1,
        'max_size' => ( UM()->options()->get('profile_photo_max_size') ) ? UM()->options()->get('profile_photo_max_size') : 999999999,
        'min_width' => str_replace('px','',UM()->options()->get('profile_photosize')),
        'min_height' => str_replace('px','',UM()->options()->get('profile_photosize')),
    );
    return $arr;
}
*/
add_filter("um_predefined_fields_hook","um_predefined_fields_hook_cover_photo", 99999, 1 );
function um_predefined_fields_hook_cover_photo( $arr ){
    
    return $arr;   
    $arr['cover_photo'] = array(
        'title' => __('Cover Photo','ultimate-member'),
        'metakey' => 'cover_photo',
        'type' => 'image',
        'label' => __('Change your cover photo','ultimate-member'),
        'upload_text' => __('Upload your photo here','ultimate-member'),
        'icon' => 'um-faicon-camera',
        'crop' => 1,
        'max_size' => ( UM()->options()->get('cover_photo_max_size') ) ? UM()->options()->get('cover_photo_max_size') : 999999999,
        'min_width' => str_replace('px','',UM()->options()->get('cover_photosize')),
        'min_height' => str_replace('px','',UM()->options()->get('cover_photosize')),
    );
    return $arr;
}
/*
////////////////////////////////////////////////
// ADD A PROFILE PHOTO CAPABILITY TO THE REGISTRATION FORM - added Mar 2023 and NOT tested yet
// 2. Multiply Profile Photo with different sizes
//
// * Note the problem in adding a Google photo to the UM Profile Photo is that the Google photo is a GIF
// * so we need to convert. Not tried this yet, but see this ref to convert from GIF to JPG
// * : https://stackoverflow.com/questions/20343192/wordpress-function-to-convert-from-gif-to-jpg-and-set-it-as-the-post-thumbnail-f
// * 
// * and this ref with creates a jpg from a gif and other sources
// * https://www.php.net/manual/en/function.imagejpeg.php which suggests creating an image as a "resource" from a gif with 
// * the php fucntions imagecreatefromgif() and then saving it to a file as jpg with imagejpg(). SHoudl try this
// * 
// * and this, which illustrates call to um_import_images() that appears to import an image to a user  
// * https://gist.github.com/champsupertramp/5bc9e1211fc6d1451dc379610768b111
// * 
////////////////////////////////////////////////
add_action( 'um_registration_set_extra_data', 'um_registration_set_profile_photo', 9999, 2 );
function um_registration_set_profile_photo( $user_id, $args ){
    
    if ( empty( $args['custom_fields'] ) ) return;
    if( ! isset( $args['form_id'] ) ) return;
    if( ! isset( $args['profile_photo'] ) || empty( $args['profile_photo'] ) ) return;
    
    // apply this to specific form
    //if( $args['form_id'] != 12345 ) return;
    $files = array();
    $fields = unserialize( $args['custom_fields'] );
    $user_basedir = UM()->uploader()->get_upload_user_base_dir( $user_id, true );
    $profile_photo = get_user_meta( $user_id, 'profile_photo', true );
    $image_path = $user_basedir . DIRECTORY_SEPARATOR . $profile_photo;
    $image = wp_get_image_editor( $image_path );
    $file_info = wp_check_filetype_and_ext( $image_path, $profile_photo );
    $ext = $file_info['ext'];
    $new_image_name = str_replace( $profile_photo,  "profile_photo.".$ext, $image_path );
    $sizes = UM()->options()->get( 'photo_thumb_sizes' );
    $quality = UM()->options()->get( 'image_compression' );
    
    if ( ! is_wp_error( $image ) ) {
        $max_w = UM()->options()->get('image_max_width');
        if ( $src_w > $max_w ) {
            $image->resize( $max_w, $src_h );
        }
        $image->save( $new_image_name );
        $image->set_quality( $quality );
        $sizes_array = array();
        foreach( $sizes as $size ){
            $sizes_array[ ] = array ('width' => $size );
        }
        
        $image->multi_resize( $sizes_array );
        delete_user_meta( $user_id, 'synced_profile_photo' );
        update_user_meta( $user_id, 'profile_photo', "profile_photo.{$ext}" );
        @unlink( $image_path );
    }
}
//////////////// END OF ADD PROFILE PHOTO TO REGISTRATION PAGE ///////////////////


///////////////////////////////////////////////////////////
// UM ACCOUNTS PAGE Extensions
// Below we extend user's Account form to show custom fields for address and boat info. The
// addition also allow the user to edit his fields. We have to use custom code for this because
// the UM Account page is inbuilt and cannot be edited like the Registration Pages.
// The code below was based on examples here: https://docs.ultimatemember.com/article/65-extend-ultimate-member-account-page-with-custom-tabs-content
// The boat and address icons were taken from here: https://gist.github.com/plusplugins/b504b6851cb3a8a6166585073f3110dd
// Getting the custom fields to update proved tricky - see this article for help
// https://gist.github.com/champsupertramp/c1f6d83406e9e0425e9e98aaa36fed7d
///////////////////////////////////////////////////////////

///////////////////////////////////////////////////////////
// Below is how to extend the fields shown on the inbuilt account general tab.
// Note, this only works with PREDEFINED UM fields, (like "nickname"), it doesn't
// appear to find custom fields (like "yacht_name").
//////////////////////////////////////
function moa_account_tab_general_fields( $output, $shortcode_args ) {
    $output .= ',nickname';		// add more to this string with comma separation
    return $output;
}
add_filter( 'um_account_tab_general_fields', 'moa_account_tab_general_fields', 12, 2 );

/*
/////////////////////
// ADD BOATTAB - Adding a new tab "boattab" to Account Page takes three steps
// NO LONGER NEEDED - boat details are managed through user's Profile page 
///////////////////
// 1. create a UM object for boattab
function moa_boat_tab_in_um( $tabs ) {
    $tabs[800]['boattab']['icon'] = 'um-icon-android-boat';
    $tabs[800]['boattab']['title'] = 'Boat Information';
    $tabs[800]['boattab']['custom'] = true;
    $tabs[800]['boattab']['submit_title'] = 'Update Boat Info';
    // $tabs[800]['boattab']['show_button'] = false;				// disables the submit button
    return $tabs;
}
add_filter('um_account_page_default_tabs_hook', 'moa_boat_tab_in_um', 100 );

// 2. make our new tab object hookable
function moa_account_tab__boattab( $info ) {
    global $ultimatemember;
    extract( $info );
    $output = $ultimatemember->account->get_tab_output('boattab');
    if ( $output ) { echo $output; }
}
add_action('um_account_tab__boattab', 'moa_account_tab__boattab');

// 3. add some page content to the boattab object - with UM fields
function moa_account_content_hook_boattab( $output ){
    ob_start();
    ?>
	<div class="um-field">
		<!-- Here goes your custom content -->
		<?php
		global $ultimatemember;
		$id = um_user('ID');
		$output2 = '';
		$names = [ 'yacht_name','yacht_previous_name','yacht_class','yacht_call_sign','yacht_sailno','yacht_location',
		           'yacht_area_sailed','yacht_longlat','yacht_desc','mobile','yacht_map_show','yacht_pic'];
		$fields = [];
		foreach( $names as $name )
			$fields[ $name ] = UM()->builtin()->get_specific_field( $name );
		// $fields = apply_filters( 'um_account_secure_fields', $fields, $id ); // see below for why this is commented out
		foreach( $fields as $key => $data )
			$output2 .= UM()->fields()->edit_field( $key, $data );
		echo $output2;
		// Note To add the map popup button to support yacht location
		// add the shortcode [moa-popup-longlat-map callback="yacht_longlat" to the UM Accounts page
		// Or, the do_shortcode() could be added to the $output2 string above. 
		?>
	</div>		
	<?php
	$output .= ob_get_contents();
	ob_end_clean();
	return $output;
}
add_filter('um_account_content_hook_boattab', 'moa_account_content_hook_boattab');
*/

/**********************
 * ADD ADDRESS TAB to UM Account page, this for addresses
 * There are three parts to this (1) Create a "Tab" UM object (2) Make the UM Object "Hookable" (3) Add content to the Tab 
***********************/

////////////////////		
// 1. create a UM object for addrtab 
add_filter('um_account_page_default_tabs_hook', 'my_addr_tab_in_um', 100 );
function my_addr_tab_in_um( $tabs ) {
	$tabs[800]['addrtab']['icon'] = 'um-icon-android-home';
	$tabs[800]['addrtab']['title'] = 'Address Information';
	$tabs[800]['addrtab']['custom'] = true;
	$tabs[800]['addrtab']['submit_title'] = 'Update Address';
	// $tabs[800]['addrtab']['show_button'] = false;				// disables the submit button
	return $tabs;
}

////////////////////
// 2. make the new UM object hookable
add_action('um_account_tab__addrtab', 'um_account_tab__addrtab');
function um_account_tab__addrtab( $info ) {
	global $ultimatemember;
	extract( $info );
	$output = $ultimatemember->account->get_tab_output('addrtab');
	if ( $output ) { echo $output; }
}

////////////////////
// 3. Add some page content to the UM tab object - with UM fields 
add_filter('um_account_content_hook_addrtab', 'moa_account_content_hook_addrtab');
function moa_account_content_hook_addrtab( $output ){
	ob_start();
	?>
	<div class="um-field">
		<!-- Here goes your custom content -->
		<?php
		global $ultimatemember;
		$id = um_user('ID');
		$output2 = '';
		$names = [ 'addr_line1','addr_line2','addr_town','addr_county','addr_postcode','addr_country','phone' ];
		$fields = [];
		foreach( $names as $name )
			$fields[ $name ] = UM()->builtin()->get_specific_field( $name );
		// $fields = apply_filters( 'um_account_secure_fields', $fields, $id );		// see below for why this is commented out
		foreach( $fields as $key => $data )
			$output2 .= UM()->fields()->edit_field( $key, $data );
		echo $output2;
		?>
	</div>		
	<?php
	$output .= ob_get_contents();
	
	// Note, it appears we can only call this "apply_filters('um_account_secure_fields'...)" once otherwise 
	// the last call overwrites any earlier one, and we find that any field changes in the earlier tabs are not saved.
	// So we have to pick up all the fields we have added  in each of the tabs and do the "apply_filters(...)" call in one go
	$namesAll = [  'yacht_name','yacht_previous_name','yacht_class','yacht_call_sign','yacht_location',
	               'yacht_area_sailed','yacht_longlat','yacht_rig','yacht_sailno', 'yacht_engine','yacht_desc',
                   'yacht_pic',
                   'yacht_image_1','yacht_image_2','yacht_image_3','yacht_image_4','yacht_image_5',
                   'mobile','yacht_map_show',
	               'phone','addr_line1','addr_line2','addr_town','addr_county','addr_country','addr_postcode' ];
		$fieldsAll = [];
		foreach( $namesAll as $name )
			$fieldsAll[ $name ] = UM()->builtin()->get_specific_field( $name );
		$fieldsAll = apply_filters( 'um_account_secure_fields', $fieldsAll, $id );

	ob_end_clean();
		
	return $output;
}


/**********************
 * ADD PAYPAL TAB to UM Account page, this for user's paypal info
 * There are three parts to this (1) Create a "Tab" UM object (2) Make the UM Object "Hookable" (3) Add content to the Tab
 ***********************/

////////////////////
// 1. create a UM object for paypal, BUT only if user has a subscription
add_filter('um_account_page_default_tabs_hook', 'my_paypal_tab_in_um', 100 );
function my_paypal_tab_in_um( $tabs ) {
    
    global $ultimatemember;     
    $id = um_user('ID');
    // check whether this user has 'paypal_status' metadata, if so then they paid on-line, if not they didn't
    if ( get_user_meta($id,  'paypal_status' , true) ) {
        $tabs[900]['paypaltab']['icon'] = 'um-icon-paypal';
        $tabs[900]['paypaltab']['title'] = 'PayPal Subscription';
        $tabs[900]['paypaltab']['custom'] = true;
        $tabs[900]['paypaltab']['submit_title'] = 'Close';
        $tabs[900]['paypaltab']['show_button'] = false;				// disables the submit button
    }
    return $tabs;
}

////////////////////
// 2. make the new UM object hookable
add_action('um_account_tab__paypaltab', 'um_account_tab__paypaltab');
function um_account_tab__paypaltab( $info ) {
    global $ultimatemember;
    extract( $info );
    $output = $ultimatemember->account->get_tab_output('paypaltab');
    if ( $output ) { echo $output; }
}

////////////////////
// 3. Add some page content to the UM tab object - with UM fields
add_filter('um_account_content_hook_paypaltab', 'moa_account_content_hook_paypaltab');
function moa_account_content_hook_paypaltab( $output ){

    ob_start();
    global $ultimatemember;
    $id = um_user('ID');
    $output2 = '';
    $flds = [ 'paypal_status','paypal_paid_date','paypal_anniversary_date','paypal_amount','paypal_merchant_code','paypal_transaction_code' ];
    
    ?>
	<div class="moa-um-account-paypal-tab">
		<!-- Paypal data - not ediable -->
		<div class="intro">This information for your on-line subscription is recorded through the On-Line PayPal module in this web site. 
		If you paid your subscription off-line, or by using PayPal directly there will be no data here. </div>
		<?php
		foreach( $flds as $key ) {
		  $output2 .= '<div class="field"><span class="label">' .  str_replace( "_", " ", $key ) . 
		              '</span><span class="data">' . (( $x = get_user_meta($id,  $key , true) ) ? $x : 'NO DATA') . 
		              '</span></div>';
		}
		echo $output2;
		?>
	</div>		
	<?php
	$output .= ob_get_contents();
	
	ob_end_clean();
		
	return $output;
}

////////////////////////////////////////////////////////////
// USER UPDATES via ACCOUNT pages is done by the function below, fired by any of the Account tab's "Update.." buttons 
// Then, by default, UM only saves its inbuilt fields, so to save custom fields we have to 
// explicitly invoke WP's update_user_meta() function using the field keys and values that are
// held in the WP $_POST[] array. Note these values are only for the tab, so we check whether
// the key on the tab page is in the $_POST[] array before updating it. 
/////////////////////////////////////////////////////////////
function moa_account_pre_update_profile(){
    $id = um_user('ID');
    //$namesAll = array_merge($arrAddrFlds, $arrYachtFlds);
    $namesAll = [   'yacht_name','yacht_previous_name','yacht_class','yacht_call_sign','yacht_rig','yacht_sailno',
                    'yacht_engine','yacht_location','yacht_area_sailed','yacht_longlat','yacht_desc','yacht_pic',
                    'mobile','yacht_map_show',
                    'yacht_image_1','yacht_image_2','yacht_image_3','yacht_image_4','yacht_image_5',
                    'phone','addr_line1','addr_line2','addr_town','addr_county','addr_country' ];
    foreach( $namesAll as $name ) {
        if (array_key_exists($name, $_POST)) {
             update_user_meta( $id, $name, $_POST[$name] );
        }
     }
}
add_action('um_account_pre_update_profile', 'moa_account_pre_update_profile', 100);

/////////////////////////////////////////////// 
// PROFILE and ACCOUNTS UPDATES
//
// two functions below show how to add a new tab into the Profile page of the Ultimate Member.
// * See the article https://docs.ultimatemember.com/article/69-how-do-i-add-my-extra-tabs-to-user-profiles
//
// This example adds the tab 'mycustomtab' that contains the field 'description'. You can add your own tabs and fields.
// Important! Each profile tab has an unique key. Replace 'mycustomtab' to your unique key.
//
// You can add this code to the end of the file functions.php in the active theme (child theme) directory.
//
// Ultimate Member documentation: https://docs.ultimatemember.com/
// Ultimate Member support (for customers): https://ultimatemember.com/support/ticket/
////////////////////////////


//////////////////////
// Hook gets called by Profile Update, Account Update and New Registration as a part
// of the UM()->profile->update method. This gets called BEFORE user data gets added to the DB
// This means we can check new against existing data to see whether KML map data may have been 
// altered, and if so then set the 'moa_overlay_rebuild' flag which is read by the CheckOverlayRebuildNeeded()
// function called by different hooks for REgistion, Profile and Account updates AFTER new data added to DB
/////////////////////
add_filter( 'um_before_update_profile', 'moa_before_update_profile', 10, 2 );
function moa_before_update_profile( $changes, $user_id) {
    
    // Check whether any of the KML file fields has changed, if so then set flag to rebuild KML file later
    // NB, we can't rebuild it now, since new data hasn't been saved yet
    if ( isset($changes['yacht_longlat']) && !empty($changes['yacht_longlat'])) {
        /* update_option( 'moa_overlay_rebuild', 0);
        $flds2Check= array_keys( $changes );
        foreach( $flds2Check as $fld ){
            // note $changes values is 1x array but db entries 2x array so correct by getting 2 array item for db entries
            $old_val = get_user_meta($user_id, $fld, true);
            $new_val  = $changes[ $fld ];
            if ( isset($new_val) && ($new_val != $old_val) ) {
                update_option( 'moa_overlay_rebuild', 1);
                return $changes;
            }
        } */
        // Set this flag everytime to rebuild the Boats Overlay file, the logic above worked for the std profile variables 
        // in the $changes variable, but didn;t pick upchanges to the "additional images" ( that vars'yacht_image_1...5)
        update_option( 'moa_overlay_rebuild', 1);
        
    }
    return $changes;        // Important we return the $changes for next stage of UM update process
}

////////////////////// 
// BEFORE PROFILE UPDATE - UM Hook called from Profile update, called after user clicks 'Save' but before items appled to database
// See .../core/um-actions-profile.php for the order of hook execution, $args does include yacht_image_{n} fields
/////////////////////
add_action( 'um_after_user_updated', 'moa_after_user_updated', 10, 3 );
function moa_after_user_updated( $user_id, $args, $userinfo ) {
  
   $x = $args;

}
////////////////////// 
// AFTER PROFILE UPDATE - UM Hook called from Profile update, after user clicks Save and after changes applied to database 
// See .../core/um-actions-profile.php for the order of hook execution
// NB, the $submitted arg includes most form fields but doesn;t incldue yacht_image_{n} fields, not sure why!
/////////////////////

add_action( 'um_user_after_updating_profile', 'moa_um_user_after_updating_profile', 10, 2 );
function moa_um_user_after_updating_profile( $submitted, $user_id ){
      // Call the function that rebuilds Macwester Boat map BOAT overlay file in case user details have changed
      // it will check for the wp_options flag 'moa_overlay_rebuild' = 1 which should have been set
      CheckOverlayRebuildNeeded();
  
}



//////////////////////
// Hook called from Account update, after user clicks Save
// See .../core/um-actions-account .php for the order of hook execution
/////////////////////
add_action( 'um_post_account_update', 'moa_post_account_update', 10 );
function moa_post_account_update() {
    // Call the function that rebuilds Macwester Boat map KML overlay file in case user details have changed
    CheckOverlayRebuildNeeded();
}
    
//////////////////////
// This UM hook is called after user clicks Save in Accounts Page, see above for Profiles Page update
/////////////////////
add_action( 'um_after_user_account_updated', 'moa_after_user_account_updated', 10, 2 );
function moa_after_user_account_updated( $user_id, $changes ) {
    // Call the function that rebuilds Macwester Boat map KML overlay file in case user details have changed
    CheckOverlayRebuildNeeded();
}
    

/**********************************************************
// * PROFILE TABS - Add new tab on user's profile, note this is a seperate call to the rendering 
// * of the main profile (BTW, whose name is "main") 
// *
// * @param array $tabData - tab name and icon to display
// * @return array
//
function um_mycustomtab_add_tab( $tabData ) {
    
    $tabData[ 'mycustomtab' ] = array(
        'name'   => 'Yacht Details',
        'icon'   => 'um-faicon-wrench',
        'custom' => true
    );
    UM()->options()->options[ 'profile_tab_' . 'mycustomtab' ] = true;
    return $tabData;
}
add_filter( 'um_profile_tabs', 'um_mycustomtab_add_tab', 1000 );

//
// Render tab content
//
function um_profile_content_mycustomtab_default( $args ) {
    
    $action = 'mycustomtab';    // name of the tab object for the content 
    // the user fields to see on this page
    $fields_metakey = array('description','last_name','first_name','yacht_name','yacht_longlat','yacht_class');
    
    // this tab gets called after the edit is saved, so set a a nonce for the saving process 
    $nonce = filter_input( INPUT_POST, '_wpnonce' );
    
    // check if is this the the call afted edit was saved,
    if( $nonce && wp_verify_nonce( $nonce, $action ) && um_is_myprofile() ) {
        foreach( $fields_predefined as $metakey ) {
            update_user_meta( um_profile_id(), $metakey, filter_input( INPUT_POST, $metakey ) );
        }
        UM()->user()->remove_cache( um_profile_id() );
    }
    
    // $fields1 = UM()->builtin()->get_specific_fields( implode( ',', $fields_predefined ) ); // only does predefined fields
    foreach ($fields_metakey as $field ) {
        if ( isset( UM()->builtin()->all_user_fields[$field] ) ) {
            $fields[$field] = UM()->builtin()->all_user_fields[$field];
        }
    }
    $x = UM();
    $v = UM()->fields();
    $y = UM()->fields()->editing ;
    $z = um_is_on_edit_profile();
    ?>
	<div class="um">
		<div class="um-form">
			<form method="post">
				<?php
				if( (um_is_myprofile() == true) && (um_is_on_edit_profile() == true) ) {
					foreach( $fields as $key => $data ) {
					   echo UM()->fields()->edit_field( $key, $data );
					}
				} else {
					foreach( $fields as $key => $data ) {
						echo UM()->fields()->view_field( $key, $data );
					}
				}
				?>
				<?php if( (um_is_myprofile() == true) && (um_is_on_edit_profile() == true) ) : ?>
					<div class="um-col-alt">
						<div class="um-left">
							<?php wp_nonce_field( $action ); ?>
							<input type="submit" value="<?php esc_attr_e( 'Update', 'ultimate-member' ); ?>" class="um-button" />
						</div>
					</div>
				<?php endif; ?>
			</form>
		</div>
	</div>
	<?php
}
add_action( 'um_profile_content_mycustomtab_default', 'um_profile_content_mycustomtab_default' );
*/
/////////////////////////////////////////////
// Problem Fix - UM style sheets include numerous uses of !important which prevent overwrite by MOA styles.  
//  A particular problem for MOA maps is when processing our shortcodes [moa-map-popup] and 
//  [moa-map-static] on the UM 'profile' and 'register' pages. The Leaflet marker icon is an image 
//  which gets its "margin:0!important" forced by 'um-profile.css'. This prevents Leaflet offsetting 
//  the map icon, with the disaterous effect of the icon pointing to wrong lat/long.     
//      
//  For removing other UM scripts from other non UM pages 
// see https://docs.ultimatemember.com/article/1490-how-to-remove-css-and-js-on-non-um-pages
//////////////////////////////////////////////

function moa_remove_um_profile_styles() {
// Checks whether we are on the UM Profile page - that's where the um_profile.css gives grief with
// the MOA Map LongLat margins. Here we apply the fix, which is to deregister the existing UM profile style 
// <plugins>\ultimate_member\assets\css\um-profile.css and reregister it with our modified style sheet copied to 
// <plugins>\moa-club-plugins\css\moa-um-profile.css. There's only one change, "margin:0"  is set NOT TO BE "!important"
// By keeping the same style handle 'um_profile' the enqueued style entry can find the registered style, except it
// will load the modified src not the original UM one.
// Note - it might seem best to dequeue and requeue the new script - but doing that seems to get out of sync 
// with another UM style um_responsive.css - demonstrated by duplicated buttons!  
// Note - to see the styles already registered see global $wp_styles->queued[] and $wp_styles->registered[];

    // if ( um_is_core_page('user') || um_is_core_page('register') ){     
    if ( um_is_core_page('user') ){     
       
        wp_deregister_style( 'um_profile' );
        wp_register_style('um_profile', plugins_url( "../css/modified-um-profile.css" , __FILE__ ));
        //wp_deregister_style( 'um_styles' );
        //wp_register_style('um_styles', plugins_url( '../css/modified-um-styles.css' , __FILE__ ),array(NULL),NULL,NULL);
        //wp_deregister_style( 'um_responsive' );
        //wp_register_style('um_responsive', plugins_url( '../css/modified-um-responsive.css' , __FILE__ ),array(NULL),NULL,NULL);
     }  
    return;
}
//add_action( 'wp_print_footer_scripts',  'moa_remove_um_profile_styles', 9 );
//add_action( 'wp_print_scripts',         'moa_remove_um_profile_styles', 9 );
add_action( 'wp_print_styles',          'moa_remove_um_profile_styles', 9 );


/*******************************************
* PAGE ACCESS CONTROL - USING UM CONTENT RESTRICTION
* Shortcode called from any page that implemented Ultimate Member Content restriction by role
 * Expects attr to define level of restriction, e.g.: 
 * restriction="registered", restriction="member", restriction="admin" 
 ********************************************/
add_shortcode( 'moa-user-must-be', 'moa_user_must_be_fn' );
function moa_user_must_be_fn( $atts , $content){
    
    $userRole=um_user('role'); 			// get the users Ultimate Member role
    $minRole="member";
    
    // get the restriction put on the page
    foreach( $atts as $key =>$value ){
        if ("$key" == "restriction") { $minRole=$value;}
    }
    
    // convert the restriction into $accesOk variable, ture|false
    switch ( $minRole) {
        case "registered" :  
            $accessOk = ( in_array( $userRole, array('um_registered', 'um_moa-member','um_moa-admin','administrator' )))? true: false;
            break;
        case "member":      
            $accessOk = ( in_array( $userRole, array('um_moa-member','um_moa-admin','administrator')))? true: false;
            break;
        case "admin":       
            $accessOk = ( in_array( $userRole, array('um_moa-admin','administrator')))? true: false;
            break;
    }
    // bomb out if Access OK
    if ( $accessOk ) 
        return;
    
    // Access failed  so echo warning rmessage the error and stop loading the page
    echo '
<p>You need to be Club Member to access this page.</p>
<p>See here for Joining Instructions <a href="/office/membership-application-details">Club Membership Details</a></p>';

     die;
}
/*******************************************
* Shortcode called from any page that implemented Ultimate Member Content restriction by role
********************************************/
add_shortcode( 'moa-um-login-mustbe-member', 'moa_um_login_mustbe_member_fn' );
function moa_um_login_mustbe_member_fn( $atts , $content){
    
    $html='<p>You need to be Club Member to access this page.</p>
    <p>See here for Joining Instructions <a href="/office/membership-application-details">Club Membership Details</a></p>';
    
    // check if the role is in the list we allow full access to
    //$role=um_user('role'); 			// get the users Ultimate Member role
    //if( in_array ($role , array('um_moa-admin','um_moa-member' ))) {
    //}
    
    echo $html;
    
}

/*******************************************
* SHOW/HIDE DIVS - SHORTCODE FOR ADDING AS CUSTOM FIELD IN UM FORM BUILDER
* This shortcode includes every thing needed to to Show/Hide div blocks Shortcode, to use it we need to add 
* a <"data-target=..." field in the "button" div which defines the target div to be shown/hidden. 
* Example below will show/hide any div with class="boat-part":
* <div id="boat-part-hide-link" class="form-part-hide-link" data-target="boat-part">Show Details</div>
********************************************/
add_shortcode( 'moa-toggle-divs-script', 'moa_toggle_divs_script_fn' );
function moa_toggle_divs_script_fn() {

    echo '
    <!-- MOA - Toggle visibility on container parts of the form -->
    <script>
         jQuery( document ).ready(function() {
            jQuery("[id=\'boat-part-hide-link\'],[id=\'addr-part-hide-link\']").click( function(){ 
                var target = jQuery(this).data("target");
                jQuery(this).toggleClass("active");
                jQuery("." + target ).slideToggle("slow");
                jQuery(this).text($(this).text() == "Show Details" ? "Hide Details" : "Show Details");
                // include next line to reset the Leaflet map, else if hidden then  doesn;t display correctly
                if ( map ) { map.invalidateSize(); }
            });

        });
    </script>
    <style>
        /* Initialise the div Show/Hide buttons - and the blocks they are hiding */
        /* #boat-part-hide-link, #addr-part-hide-link {display:none; }  */
        /* .addr-part {  display:none; } */
        .boat-further-container  { display:none;  } 
        .form-part-hide-link {
            font-size: 15px;
            color: var(--moa-blue);
            position: absolute;
            top: 0px;
            right: 5px;
            cursor:pointer;
        }
        .form-part-hide-link:hover {
            color: darkred;
            text-decoration: underline;
        }    
        .form-part-hide-link:before {
            content: "";
            position: relative;
            left: 3px;
            top: 17px;
            margin-right: 8px;
            border-left: 10px solid transparent;
            border-right: 10px solid transparent;
            border-top: 12px solid var(--moa-blue);
            border-bottom-color: transparent;
            clear: both;
        }
        .form-part-hide-link:hover:before { border-top: 12px solid darkred;}
        .form-part-hide-link.active:hover:before { border-bottom: 12px solid darkred;}
        .form-part-hide-link.active:before {
            top: -17px;
            border-bottom: 12px solid var(--moa-blue);
            border-top-color: transparent;
        }
      
    </style>
    ';
}

/**************************************
 * AFTER LOGIN & REGISTRATION ACTIONS
 **************************************/

/*******************
 * AFTER LOGIN ACTION - CONVERT SOCIAL LOGIN AVATAR INTO UM PROFILE PAGE IMAGE
 * Called from wp-login() hook, checks if user registered via oogle or Facebook, and if so expects ASPL plugin will have 
 * saved the profile picture URL in user-meta 'deumage' value. If so gets the URL, reads it back and saves it as a JPEG in 
 * the UM profile pictures area (/uploads/ultimatemember/{uid}/profile-photo.jpg) 
 * TODO - not working yet, need to provide for both FB and Google images which are different!
*********************/
function moa_after_login_grab_social_login_avatar() {
   
    $user          = wp_get_current_user();
    $SocialMediaType = get_user_meta( $user->ID, 'deutype');
    if ( !$SocialMediaType ) {
        return;
    } else {
        //$gravatar_url    = get_avatar_url( $user->ID, ['size' => '50'] );
        $gravatar_url = get_user_meta( $user->ID, 'deuimage');
        $gravatar_url = "https://lh3.googleusercontent.com/a/AAcHTtebFhMulGtER4RXB1pe84fpaD19e-7dUwVDhSkF3Q=s96-c";
        if ( $gravatar_url ) {

            $gravatar_source = wp_remote_get( $gravatar_url );

            if ( ! is_wp_error( $gravatar_source ) && 200 == $gravatar_source['response']['code'] ) {
                $filename      = sanitize_file_name( 'profile-picture.jpg' );
                $path = 'wp-content/uploads/ultimatemember/' . $user->ID;
                if( !is_dir($path) ) {
                    mkdir($path , 0755);
                }
                $gravatar_file = fopen( $path . '/' . $filename, 'w' );
                // $gravatar_file = fopen( 'wp-content/uploads/ultimatemember/'. $filename, 'w' );
                fwrite( $gravatar_file, $gravatar_source['body'] );
                fclose( $gravatar_file );
            }
        }
    }
}
add_action( 'wp_login', 'moa_after_login_grab_social_login_avatar',10,2 );

/******************
 * AFTER LOGIN ACTION - CATCH SOCIAL LOGIN USERS AND PROMPT FOR BOAT DETAILS
 ******************/
function moa_after_login_catch_social_login_users() {

    $x = $_REQUEST; // just a hook for breakpoint
    $y = $_GET; 
    $z = $_SERVER;

}
//add_action( 'wp_login', 'moa_after_login_catch_social_login_users',10,2 );

/*******************************
* REDIRECT TO PAGE THAT LAUNCHED LOGIN - This fixes the annoying omission in Ultimate Member that
* after login, you can only redirect to user' Profile or a named URL, not the page from which the Login was 
* launched, which is typically what you'd want to do! Below is the reference which seems to work just fine.  
*   https://docs.ultimatemember.com/article/1802-redirect-to-the-previous-page-after-login
* The supporting UM Settings are 
*   Settings > Forms > Login Form > Options = Default and
*   Settings > User Roles > Registered > Login Options = Redirect to profile 
* NB, also we can set a redirect page if calling login from an anchor tag add attribute "redirect_to="...." to teh anchor tag
* This function was added Mar 2023

    $url = esc_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );   // gets the parent
    $pg = get_permalink();                                      // gets the page where the restricted content is
  
      $url  = $ultimatemember->permalinks->get_current_url();

//******************************/
add_filter( 'um_browser_url_redirect_to__filter', function( $url ) {
    if ( empty( $url ) && isset( $_SERVER['HTTP_REFERER'] ) ) {
        $url = esc_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    }
    return add_query_arg( 'umuid', uniqid(), $url );
} );
/******************
 * AFTER REGISTRATION ACTION - REDIRCT TO CURRENT PAGE AFTER REGISTRATION COMPLETE
 * based on article here: https://docs.ultimatemember.com/article/1646-redirect-to-a-page-after-registration
 * Note, We've exactly copied the example they gave, which by the look of things, skips the inbuilt autologin() and does that explicitly here
 ******************/

 
add_action( 'um_registration_complete', 'moa_um_after_registration_redirect_to' ,1 );
function moa_um_after_registration_redirect_to( $user_id ){
    $url = esc_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    $x = $_REQUEST; // just a hook for breakpoint
    $y = $_GET; 
    $z = $_SERVER;
    // return $url;   

//    um_fetch_user( $user_id );
 //   UM()->user()->auto_login( $user_id );
 //   wp_redirect( get_permalink() ); 
 //   exit;
}


/*********************************
 * AFTER REGISTRATION - EARLY EFFORTS AT REDIRECTION 
 * TODO to edit/correct this lot!
 *********************************/
/*
add_filter( 'um_registration_pending_user_redirect', 'my_registration_pending_user_redirect', 10, 3 );
function my_registration_pending_user_redirect( $url, $status, $user_id ) {
// your code here
    $url = esc_url( wp_unslash( $_SERVER['HTTP_REFERER'] ) );
    return $url;
}
*/

/*
//////////////////////////////////
add_action( 'um_registration_after_auto_login', 'moa_registration_after_auto_login', 10, 1 );
function moa_registration_after_auto_login( $user_id ) {
    global $ultimatemember;
    exit(  wp_redirect(site_url() )  );     // we let the user get sent an activation email
}
*/

/*********************************
 * REGISTRATION FORM - CHANGE TEXT STRING 
 * From "This email is incorrect" to "This email is not allowed, it already exists"
 * This is called from the validation routines in UM's ..\core\um-actions-form.php where an exil is form to already exist
 *
 * See reference here: https://developer.wordpress.org/reference/hooks/gettext/
 *********************************/
function moa_um_change_email_error_message( $translated_text, $text, $domain ) {
	switch ( $translated_text ) {
		case 'The email you entered is incorrect' :
			$translated_text = __( 'This email is not allowed, it already exists', 'ultimate-member' );
			break;
	}
	return $translated_text;
}
add_filter( 'gettext', 'moa_um_change_email_error_message', 20, 3 );