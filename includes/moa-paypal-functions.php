<?php
//////////////////////////////////////////////////////////////
// PAYPAL FUNCTIONS - used to pay for subscriptions, which need different first and subsequent year amounts.
// The process is 
//  /office/full-membership-registration which has only [UM form=2780] 
//      Gets Personal, Boat and Address info, registes the user awaiting email-activation then redirects to 
//  /office/membership-form which has [moa-paypal-callback], [CF7 form Paypal Status] [CF7 form Address Info] and multiple [moa-paypal-block]
//      (A) Starts with PayPal & Cancel buttons, to redirect to PayPal transaation, which redirects to same page, and cancel which goes back  
//      (B) On return the [moa-paypal-callback] short inspects querystring returned by PayPal transaction, and shows/hides different [moa-paypal-block]   
//////////////////////////////////////////////////////////////


// constant definitions used below
define( 'MOA_PAYPAL_SANDBOX', FALSE );                                                 // switches between live & sandbox PayPal URLs
define( 'MOA_PAYPAL_SANDBOX_BUSINSESS_ACCOUNT', 'admin@daemon.co.uk' );                 // predefined Personal account for this = admin-buyer@daemon.co.uk (Asparagus12!)
//define( 'MOA_PAYPAL_SANDBOX_BUSINSESS_ACCOUNT', 'paypal.sandbox@macwester.org.uk' );  // predefined Personal account for this = sb-43td6436308636@personal.example.com (Asparagus12!)
define( 'MOA_PAYPAL_SANDBOX_MERCHANT_CODE', 'PAYPAL *MOA_D_SAND' );                   // not used in PayPal call out, Can be anything -  shown on user's form
define( 'MOA_PAYPAL_SANDBOX_EMAIL_RECIPIENTS',  'membership.secretary@macwester.org.uk, webmaster@macwester.org.uk');// club officials notified on successful paypal subscription, (seperate multiple addresses with commas for wp_mail() function)
define( 'MOA_PAYPAL_LIVE_BUSINSESS_ACCOUNT', 'treasurer@macwester.org.uk' );          // live PayPal Business account to use
define( 'MOA_PAYPAL_LIVE_MERCHANT_CODE', 'PAYPAL *MACWESTEROW' );                     // not used in PayPal call out, Can be anything -  shown on user's form
define( 'MOA_PAYPAL_LIVE_EMAIL_RECIPIENTS',  'membership.secretary@macwester.org.uk,payments@macwester.org.uk,comms@macwester.org.uk,webmaster@macwester.org.uk');
define( 'MOA_PAYPAL_LIVE_EMAIL_SENDER',  'Macwester Owners Association <membership.secretary@macwester.org.uk>');    // Sender email used for Welcome email to New User
define( 'PAYPAL_DEFAULT_AMOUNT', '20');                                                 
define( 'PAYPAL_DEFAULT_A3', '30');
define( 'PAYPAL_DEFAULT_P3', '1');
define( 'PAYPAL_DEFAULT_T3', 'Y');
define( 'PAYPAL_DEFAULT_SRC', '1');
define( 'PAYPAL_DEFAULT_CURR_CODE', 'GBP');
define( 'PAYPAL_DEFAULT_BUTTON_NAME', 'Subscribe');
define( 'PAYPAL_DEFAULT_NAME', 'Macwester Owners Assoc. Annual Subscription');

// hook in the form's paypal functions
add_action( 'admin_post_moa_paypal_button_hook', 'moa_paypal_button_post_hook') ;
add_action( 'admin_post_nopriv_moa_paypal_button_hook', 'moa_paypal_button_post_hook') ;


/******************************
* SHORTCODE - UPDATE USERs ADDRESS 
* Called from the Paypel Members Join page (/office/membership-form/). This shortcode to show the User's addresses as a form for editing. 
* Includes jQuery script that fires off an Ajax POST to update user's meta data (see next function). 
* Replaces the Contact Form 7 Form that was used previously, the problem with that being the submit button always redirected to the Home Page after being hit.
* The form sets the same classes on form elements as in  CF7 form  
*******************************/
add_shortcode( 'moa-user-addr', 'moa_user_addr_fn' );
function moa_user_addr_fn( $atts ) {

    $ajax_url   = admin_url( 'admin-ajax.php' );        // Localized AJAX URL needed below
    $user_id = get_current_user_id();                        // Get our current user ID

    echo '
    <div id="address-block"><p style="font-weight:700">YOUR ADDRESS - AS A MEMBER WE NEED THIS<p>
        <span id="address-msg">This is the address we will be using to post your New Members Pack.and the twice-yearly Journals. 
        If not correct you can edit it now, or at any time in the future from your <a href="/account/addrtab/">User>>Accounts</a> page</span>
        <form id="moa_addr_form" class="wpcf7-form init" method="POST">
            <div id="moa-address" style="margin:25px 0px 10px">
                <label for="addr1">Address Line 1 *
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="addr1" id="addr1" value="'.get_user_meta($user_id, 'addr_line1',true).'" />
                </label>
                <label for="addr2">Address Line 2
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="addr2" id="addr2" value="'.get_user_meta($user_id, 'addr_line2',true).'"  />
                </label>
                <label for="addr2">Town
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="town" id="town" value="'.get_user_meta($user_id, 'addr_town',true).'"  />
                </label>
                <label for="addr2">Postcode *
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="postcode" id="postcode" value="'.get_user_meta($user_id, 'addr_postcode',true).'" />
                </label>
                <label for="addr2">County
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="county" id="county" value="'.get_user_meta($user_id, 'addr_county',true).'" />
                </label>
                <label for="addr2">Country
                    <input type="text" class="wpcf7-form-control wpcf7-text" name="country" id="country" value="'.get_user_meta($user_id, 'addr_country',true).'" />
                </label>
                <input type="submit" value="Update Address" />
                <span id="response-msg" style="margin-top:20px; display:none"></span>
            </div>
        </form>
    </div>
    <script>
        // MOA adding Ajax call on the submit function
        jQuery( "document" ).ready( function( $ ) {
            // Form submission listener
            $( "#moa_addr_form" ).submit( function() {
                var addr1   = $( "#moa_addr_form #addr1"  ).val();   // Grab our post meta value
                var addr2   = $( "#moa_addr_form #addr2"  ).val();  
                var town    = $( "#moa_addr_form #town"   ).val();  
                var pcode   = $( "#moa_addr_form #postcode"  ).val();  
                var county  = $( "#moa_addr_form #county" ).val();  
                var country = $( "#moa_addr_form #country").val();  

                // -- validation - fails if these flds empty --
                if( !addr1  || !pcode ) {         
                    var msg = "Must complete the <b>Address Line 1</b> and the <b>Postcode</b> fields";
                    $( "#response-msg" ).html( msg ).css( "display","inline-block").css("color","darkred");
                } else {
                    $.ajax( {
                        url : "'. $ajax_url . '",           // Localized variable that holds the AJAX URL
                        type: "POST",                       // Declare our ajax submission method ( GET or POST )
                        data: {                             // This is our data object
                            action:     "moa-update-addr",  // AJAX POST Action
                            "addr1":    addr1,              //  data items 
                            "addr2":    addr2,   
                            "town":     town,
                            "postcode": pcode,
                            "county":   county,
                            "country":  country,
                        }
                    } )
                    .success( function( results ) {
                        var msg = "User Address Updated!"
                        console.log( msg );
                        $( "#response-msg" ).html( msg ).css( "display","inline-block");
                    } )
                    .fail( function( data ) {
                        console.log( data.responseText );
                        console.log( "Request failed: " + data.statusText );
                    } );
                }
                return false;   // Returning FALSE stops the Submit button from submitting the form, (and thus reloading the page)
            } );
        } );
    </script>
    ';

}

/************************
 * USER ADDRESS UPDATE - AJAX Callback function thast updates the user's address meta data. Called from jQuery fired from UPDATE USERs ADDRESS FORM
 * when Submit is hit. That POSTs the form's address fields into this routine.
 ***********************/
function moa_update_addr_ajax_callback() {

    // Ensure we have the data we need to continue
    if( ! isset( $_POST ) || empty( $_POST ) || ! is_user_logged_in() ) {
        // If we don't - return custom error message and exit
        header( 'HTTP/1.1 400 Empty POST Values' );
        echo 'Could Not Verify POST Values.';
        exit;
    }

    $user_id = get_current_user_id();                        // Get our current user ID
    $addr1  = sanitize_text_field( $_POST['addr1'] );      // Sanitize our user meta value
    $addr2  = sanitize_text_field( $_POST['addr2'] );  
    $town  = sanitize_text_field( $_POST['town'] );  
    $pcode  = sanitize_text_field( $_POST['postcode'] );  
    $county  = sanitize_text_field( $_POST['county'] );  
    $country  = sanitize_text_field( $_POST['country'] );  

    update_user_meta( $user_id, 'addr_line1', $addr1 );                // Update our user meta
    update_user_meta( $user_id, 'addr_line2', $addr2 );                // Update our user meta
    update_user_meta( $user_id, 'addr_town', $town );                // Update our user meta
    update_user_meta( $user_id, 'addr_postcode', $pcode );                // Update our user meta
    update_user_meta( $user_id, 'addr_county', $county );                // Update our user meta
    update_user_meta( $user_id, 'addr_country', $country );                // Update our user meta
    /*
    wp_update_user( array(
        'ID'            => $user_id,
        'user_email'    => $um_user_email,
    ) );
    */

    exit;
}
add_action( 'wp_ajax_nopriv_moa-update-addr', 'moa_update_addr_ajax_callback' );
add_action( 'wp_ajax_moa-update-addr', 'moa_update_addr_ajax_callback' );

/******************************
* SHORTCODE - MOA_PAYPAL_CALLBACK SHORTCODE
* Shortcode embedded in the Membership application page to handle differnt PayPal requirements.
* The entire new membership process is 
* 1. New user completes Registration/Membership page (/office/registration-members-form/)
* 2. This then redirects to Paypal Form (/office/membership-form/) which calls the shortcode, several times.
* 
* In this shortcode we call a WP PayPal shortcode [wp_paypal button ...] to define a button we embed in the CF7 form "MOA Club Subscription".
* Hitting that button launches the PayPalpopup window  and specifies the return page "/paypal-return" which has the following
* [moa_paypal_callback ...] shortcode embedded in it. PayPal returns to this page with a set of querystring arguments
* which are read by the shortcode function below. This interprets the returned PayPal args to determine if the transaction
* completed, and if so, then changes the user's status, role and stores the Paypal data as user_metadata"
*******************************/
add_shortcode( 'moa_paypal_callback', 'moa_paypal_callback_fn' );
function moa_paypal_callback_fn( $atts ) {
    
    ///////////////////
    // 1. Check for immediate Bomb out - if either page in edit mode or user is Guest (i.e. $used_id not defined)
    //////////////////
    if ( isset($_GET['action'])  && $_GET['action'] === 'edit' ) { return; }
    $user_id = get_current_user_id();
    if (  !$user_id ) {
        // Guest user so redirect to a UM Form to get Membership Details  
        //$target_url = add_query_arg( "mode", "club_membership_wanted", home_url('/office/registration-form') ) ;  // create the URL and query string for PayPal
        $target_url = home_url('/office/registration-members-form') ;  // create the URL and query string for PayPal
        /* $rt = 'You are not a Registered user - to apply for full Club membership form, you need to Register first.
                Click here to register <a href="' . $target_url . '">Membership Form</a>' ;
        return $rt ;*/
        exit( wp_redirect( $target_url ) );
    }
    
    ////////////////
    // 2. Get user data and meta data - best to use std WP functions not the UM functions, which seemed more error prone
    // note, if a user doesn't have the meta data item the object request fails with an error, so
    // best to check the meta data keys exist using an in-line conditional "isset()" check before assigning it
    ////////////////
    $paypal_callback = FALSE;
    $userdata = get_user_by('ID', $user_id );
    $usermeta = get_user_meta( $user_id );
    $login_name = $userdata->user_login; // returns the display name
    $display_name = $userdata->display_name; // returns the display name
    $email      = $userdata->user_email; // returns the email address
    $role       = $userdata->roles[0];
    $account_status = (isset ($usermeta['account_status'][0]) ) ? $usermeta['account_status'][0] : '' ; // UM set "Approved", "Inactive" etc.
    
    ////////////////
    // 3. Get possible PayPal callback querystring args in case we are returning from PayPal
    ////////////////
    //setcookie('moa_paypal_call_started','started',time()+600);//debug to get things started, 10 min TTL cookie
    // $paypal_callback = ( isset($_COOKIE['moa_paypal_call_started']) ? TRUE : FALSE );
    //if ($paypal_callback) {
    $st = strtolower( (isset( $_GET['st'])  ?  $_GET['st']  : '') );         // payPal status code = "completed" if we're just done a payment
    $tx = strtolower( (isset( $_GET['tx'])  ?  $_GET['tx']  : '') );        // Paypal Transaction code, e.g.: "1234567"
    $amount =         (isset( $_GET['amt']) ?  $_GET['amt'] : '');    // Paypal Amount eg "20"
    $currency =       (isset( $_GET['cc'])  ?  $_GET['cc']  : '');     // Paypal currency "GBP"
    
    $paypal_callback_success = ( ($st == 'completed' || $st == 'pending') ? TRUE : FALSE );
    $paypal_callback = ( $st == 'completed' || $st == 'cancelled' || $st == 'pending' );
    ////////////////////////////
    // 4. Work out how the form is being called, sets $mode = ...
    //  "NEW_MEMBER"                 if user's $role ="um_registered"
    //  "RENEWAL_MEMBER"             if iuser's $role = "um_moa_member" or "um_moa_admin"
    //  "INVALID_MEMBER"             if user's role not one of the above, so we'll bomb out
    //  "PAYPAL_CALLBACK_SUCCESS"    if returning from paypal with completed
    //  "PAYPAL_CALLBACK_CANCELLED"  if returning from paypal with a cancelled note
    ////////////////////////////
    $mode = "UNDEFINED";
    if  ( !$paypal_callback ) {
        if ( $role == 'um_registered') {
            $mode = 'NEW_MEMBER';
        } else if ( $role == 'um_moa-member' || $role == 'um_moa-admin' || $role == 'administrator') {
            $mode= 'RENEWAL_MEMBER'; }
            else {
                $mode= 'INVALID_MEMBER';
            }
    } else  {
        // its a callback, so check the nonce, returns FALSE if wrong nonce, 1=nonce generated in last 12hrs, 2 if in last 24hrs
        if ( (isset($_REQUEST["_wpnonce"]) == FALSE)  || (wp_verify_nonce( $_REQUEST["_wpnonce"], "paypal_call") == FALSE)  )  {
            $mode = "ILLEGAL_NONCE";
        } else if ( $paypal_callback_success ) {
            // nonce was OK, so continue withm paypal callback
            if ( $role == 'um_registered' )  {
                $mode= 'PAYPAL_CALLBACK_SUCCESS_NEW_MEMBER';
            } else if ( $role == 'um_moa-member' || $role == 'um_moa-admin' || $role == 'administrator')  {
                $mode= 'PAYPAL_CALLBACK_SUCCESS_RENEWAL';
            } else {
                $mode= 'UNDEFINED';
            }
        } else {
            if ( $role == 'um_registered' )  {
                $mode= 'PAYPAL_CALLBACK_CANCELLED_NEW_MEMBER';
            } else if ( $role == 'um_moa-member' || $role == 'um_moa-admin' || $role == 'administrator')  {
                $mode= 'PAYPAL_CALLBACK_CANCELLED_RENEWAL';
            }
        }
    }
    
    // output the mode on screen if site's debug is set
    $rt = ( WP_DEBUG ) 
        ? '<span style="display: block;; margin: -30px 0px 30px 500px;; font-size: .8em">Mode=' . $mode . ((MOA_PAYPAL_SANDBOX) ? ' (Note PAYPAL=SANDBOX)' : "") . '</span>' 
        : '';
    
    // 
    $paypal_merchant_code = ( MOA_PAYPAL_SANDBOX ) ? MOA_PAYPAL_SANDBOX_MERCHANT_CODE : MOA_PAYPAL_LIVE_MERCHANT_CODE ;
	
    ////////////////
    // 7. Update the user's record if the PayPal return code says payment was completed
    // we change roles and add payPal subscription meta data, note for single meta keys we need to delete the key first
    ////////////////
    switch ( $mode ) {
        case 'NEW_MEMBER':
            // It's a New Member, so user has role=Registered and account_status=Approved
            // Action: the CF7 fform will get & display user's address & paypal fields, (which will be NULL)
            // we need to hide the PayPal details, show the address fields and show the PayPal link button
            //////////////////////
            // Show appropriate blocks
            add_action( 'wp_footer', 'moa_jquery_show_paypal_new_member' );
            // Show Address block if the addr fields are incomplete
            $addr_line1 = get_user_meta($user_id, "addr_line1", true);
            $addr_town = get_user_meta($user_id, "addr_town", true);
            $addr_postcode = get_user_meta($user_id, "addr_postcode", true);
            //if ( !$addr_line1  || !$addr_town || ! $addr_postcode) {
                // show the form anyway
                add_action( 'wp_footer', 'moa_jquery_show_address_block' );
            //}
            break;
            
        case 'RENEWAL_MEMBER':
            // It's an existing Member wanting to renew their membership, so user will already have
            // role='MOA Member' and account_status='Approved'
            // Action: the CF7 form automatically gets & deisplay user's address & paypal details, we just need to
            // to display those blocks & show paypal link button
            
            // $paypal_paid_date = get_user_meta($user_id,['paypal-paid-date'][0]);
            $paypal_status = ( isset($usermeta['paypal_status']) ? $usermeta['paypal_status'][0] : '') ;
            
            // show PayPal Subscription details only if user's metadata says paypal_status='paid'
            // in other word, this prevents user's who've paid by post who have no PayPal record online from
            // getting annoying "Subscription Expired" messages
            if ( strpos($paypal_status, 'paid') ) {
                // show PayPal Subscription details only if user's metadata says paypal_status='paid'
                add_action( 'wp_footer', 'moa_jquery_show_paypal_block' );
                // if anniversary date expired , (ie today date > anniversary date) then show Subscription Renewal Block
                $curdate  = date('d-m-Y' );
                $ani_date =  (isset( $usermeta['paypal_anniversary_date'][0]) ? $usermeta['paypal_anniversary_date'][0] : '');
                if (strtotime( $curdate) > strtotime($ani_date) ) {
                    // $rt .= 'Subscription Expired';
                    add_action( 'wp_footer', 'moa_jquery_show_paypal_renewal' );
                }
            }
            // and show Address Block
            add_action( 'wp_footer', 'moa_jquery_show_address_block' );
            
            break;
            
        case 'PAYPAL_CALLBACK_SUCCESS_NEW_MEMBER' :
            // update user's paypal detais and set role to Full Member, and account_status=Approved
            $userdata->set_role( 'um_moa-member');
            //UM()->user()->approve();       // note this also send an Approval email
            UM()->user()->set_status( 'approved' );
            
            update_user_meta( $user_id, 'paypal_status', 'new member paid');
            update_user_meta( $user_id, 'paypal_amount', $amount);
            update_user_meta( $user_id, 'paypal_transaction_code', $tx);
            update_user_meta( $user_id, 'paypal_paid_date', current_time('d-m-Y') );
            $paid_date = get_user_meta($user_id,['paypal_paid_date'][0])[0];
            update_user_meta( $user_id, 'paypal_anniversary_date',date('d-m-Y', strtotime( '+ 1 year')) );
            update_user_meta( $user_id, 'paypal_merchant_code', $paypal_merchant_code);
            
            // and show Address Block and Paypal info
            add_action( 'wp_footer', 'moa_jquery_show_paypal_success_msg' );
            add_action( 'wp_footer', 'moa_jquery_show_paypal_block' );
            // add_action( 'wp_footer', 'moa_jquery_show_address_block' );
            
            // send email with success msg and paypal details to user
            moa_paypal_mail_success("New Member", $userdata, $usermeta);
            break;
            
        case 'PAYPAL_CALLBACK_SUCCESS_RENEWAL' :
            // and show Address Block and Paypal info
            update_user_meta( $user_id, 'paypal_status', 'renewal paid');
            update_user_meta( $user_id, 'paypal_amount', $amount);
            update_user_meta( $user_id, 'paypal_transaction_code', $tx);
            update_user_meta( $user_id, 'paypal_paid_date', current_time('d-m-Y') );
            $paid_date = get_user_meta($user_id,['paypal_paid_date'][0])[0];
            update_user_meta( $user_id, 'paypal_anniversary_date',date('d-m-Y', strtotime( '+ 1 year')) );
            update_user_meta( $user_id, 'paypal_merchant_code', $paypal_merchant_code);
            
            add_action( 'wp_footer', 'moa_jquery_show_paypal_success_msg' );
            add_action( 'wp_footer', 'moa_jquery_show_paypal_block' );
            // add_action( 'wp_footer', 'moa_jquery_show_address_block' );
            
            // send email with success msg and paypal details to user
            //moa_paypal_mail_success("Renewal", $userdata, $usermeta);
            break;
            
        case 'PAYPAL_CALLBACK_CANCELLED_NEW_MEMBER' :
            // and show Address Block and Paypal info
            add_action( 'wp_footer', 'moa_jquery_show_paypal_cancelled_msg' );
            add_action( 'wp_footer', 'moa_jquery_show_paypal_new_member' );
            /* add_action( 'wp_footer', 'moa_jquery_show_address_block' ); */
            break;
            
        case 'PAYPAL_CALLBACK_CANCELLED_RENEWAL' :
            // and show Address Block and Paypal info
            add_action( 'wp_footer', 'moa_jquery_show_paypal_cancelled_msg' );
            add_action( 'wp_footer', 'moa_jquery_show_paypal_renewal' );
            //add_action( 'wp_footer', 'moa_jquery_show_address_block' );
            break;
        case 'ILLEGAL_NONCE' :
            moa_jquery_on_ready( '.form-button-bar', 'hide()'  ) ;
            $rt .= 'Illegal callback from PayPal - Transaction aborted';
        default :
            
    }
    return $rt;
    
}


/*****************************
* SHORTCODE - MOA_PAYPAL_BLOCK - There are many of these in the Full Membership Page "/office/registration-members-form/", each with  
* different "name" attributes that output <div id="name">...</div> blocks that can be shown/hidden so the same Members page can be used 
* to show different states returned from the PayPal callback generated by the [moa_paypal_callback] shortcode. 
******************************/
add_shortcode( 'moa_paypal_block', 'moa_paypal_block_fn' );
function moa_paypal_block_fn( $atts , $content) {
    
    // These are the attributes the [moa_paypal_block] shortcode can have.
    // Not all blocks will want the paypal button, those won't have the button attribute
    $name   = (isset($atts['name']) ? $atts['name'] : '');
    $title  = (isset($atts['title']) ? $atts['title'] : '');
    $message= (isset($content) ? $content : '');
    $paypal_button = (isset($atts['paypal_button']) ? $atts['paypal_button'] : '');
    $trans_name = (isset($atts['transaction_name']) ? $atts['transaction_name'] : '');
    $recur_amount = (isset($atts['recurring_amount']) ? $atts['recurring_amount'] : '');
    $first_yr_amount= (isset($atts['first_year_amount']) ? $atts['first_year_amount'] : '');
    
    if      ($name == "callback_cancelled") {
        $div = '<div id="paypal-callback-cancelled-msg">';
        $msg = $message ;
    }
    else if ($name == "callback_success")  {
        $div = '<div id="paypal-callback-success-msg">';
        $msg = $message;
    }
    else if ($name == "callout_new_member")  {
        $div = '<div id="paypal-block-new-member">';
        $msg = '<span id="paypal-new-member-msg">' .$message . '</span>';
    }
    else if ($name == "callout_renewal")   {
        $div = '<div id="paypal-block-renewal">' ;
        $msg = '<span id="paypal-renewal-msg">' .$message . '</span>';
    } else { 
        return 'Incorrect "name" attribute specified in the [moa_paypal_block ..] shortcode, must be one of "callback_cancelled", "callback_success", "callout_new_member" or "callout_renewal"';
    }
    
    // check whether the [moa_paypal_block...] shortcode is requesting a PayPal button, if so then evaluate the [moa_paypal_button] shortcode 
    // and return its output as a string to be included in total output
    if ( !empty($paypal_button) ) {
        $paypal_shtcde =  '[moa_paypal_button buttonname="' . $paypal_button .'" '; 
        $paypal_shtcde .= ' name="' . $trans_name . '" amount="' . $recur_amount .'" recurrence="1" period="Y" src="1"'; 
        if ( !empty($first_yr_amount) ) {
            $paypal_shtcde .= ' a1="'. $first_yr_amount . '" p1="1" t1="Y"'; 
        }
        $paypal_shtcde .= ']' ;
        $paypal_shrtcde_output = do_shortcode( $paypal_shtcde );
    } else {
        $paypal_shrtcde_output = '';
    }

    
    // Combine the output of all shortcode options and return it to build the page  
    if ( !empty($title) )  $title = '<span class="paypal-callback-title">' . $title .'</span>' . '<br>'; 
    $rt = $div . $title . $msg . '<br>' .  $paypal_shrtcde_output . '</div>';
    return $rt;
}


////////////////////////////////////
// Home grown shortcode to use for the call out to PayPal, based on the shorcode used in WP PayPal plugin, but
// this adds a nonce for callback securit. The shortcode expects to get parameters in the call as seen in the $atts[] array
///////////////////////////////////
add_shortcode( 'moa_paypal_button', 'moa_paypal_button_fn' );
function moa_paypal_button_fn( $atts ) {

    ///////////////////
    // 1. Check for immediate Bomb out - if either page in edit mode or user is Guest (i.e. $used_id not defined)
    //////////////////
    if ( isset($_GET['action'])  && $_GET['action'] === 'edit' ) { 
        return; 
    }
    /* $user_id = get_current_user_id();
    if (  !$user_id ) {
       $rt = 'You are not logged in - to apply for full Club membership form, you need to Register first.
                Click here to register <a href="' . site_url() . '/office/registration-form' . '">Registration Form</a>' ;
         return $rt ;
    } 
     */
    /////////////////
    // set up the nonce, start by changing the nonce lifetime from 24 hrs to 1 hr - note this has global across the site 
    /////////////////
    //add_filter( 'nonce_life', function () { return 1 * HOUR_IN_SECONDS; } ); // set nonce life to 1 hr
    // add_filter( 'nonce_life', function () { return 30; } );     // set nonce life span to 30 secs
    
   // work out whether we are in sandbox or live mode
    if ( MOA_PAYPAL_SANDBOX ) {
        $paypal_url = "https://www.sandbox.paypal.com/cgi-bin/webscr";
        $paypal_business  = MOA_PAYPAL_SANDBOX_BUSINSESS_ACCOUNT;
		$paypal_merchant_code = MOA_PAYPAL_SANDBOX_MERCHANT_CODE;
	} else {
        $paypal_url = "https://www.paypal.com/cgi-bin/webscr";
        $paypal_business  = MOA_PAYPAL_LIVE_BUSINSESS_ACCOUNT;
		$paypal_merchant_code = MOA_PAYPAL_LIVE_MERCHANT_CODE;
    }
    
    /////////////////////////
    // work out default return URL addreses based on whether attribute includes a leading "http"
    // Note home_url( $wp->request ) returns the current page, use this if no attributes for return and cancel_return given
    ////////////////////////
    global $wp;
    $current_page = home_url( $wp->request );   
    
    //////////////////////
    // PAYPAL Form - to post to PayPal and trigger the Paypal process (ie the popup, etc.)
    // Here we set up the form, substituting attributes from the shortcode and applying defaults (from head of this file) if attribute is not set
    // The attributes used are the same as in WP PayPal, see this ref on their use
    // https://wphowto.net/how-to-create-a-paypal-subscription-button-in-wordpress-911
    //////////////////
    $return_url_with_nonce = wp_nonce_url( $current_page , "paypal_call") ;        // add the nonce and the &st=cancelled arg
    $cancel_url_with_nonce = wp_nonce_url( $current_page , "paypal_call") . '&st=cancelled';        // add the nonce and the &st=cancelled arg
    $hidden = ' type="hidden" ' ;
    $target_url =  $paypal_url;
     
    $rt = '
<div id="12345">
<form method="post" action="' .  $target_url . '" class="paypal-button" target="_top" style="display: flex;">
   <div class="hide" id="errorBox"></div> 
   <input ' . $hidden . 'name="item_name" value="'.(isset($atts['name']) ? $atts['name'] : PAYPAL_DEFAULT_NAME ) .'">
   <input ' . $hidden . 'name="button" value="subscribe">
   <input ' . $hidden . 'name="amount" value="'.(isset($atts['amount']) ? $atts['amount'] : PAYPAL_DEFAULT_AMOUNT ) .'">
   <input ' . $hidden . 'name="a3" value="'.(isset($atts['amount']) ? $atts['amount'] : PAYPAL_DEFAULT_A3 ) .'">
   <input ' . $hidden . 'name="p3" value="'.(isset($atts['recurrence']) ? $atts['recurrence'] : PAYPAL_DEFAULT_P3 ) .'">
   <input ' . $hidden . 'name="t3" value="'.(isset($atts['period']) ? $atts['period'] : PAYPAL_DEFAULT_T3 ) .'">
   <input ' . $hidden . 'name="src" value="'.(isset($atts['src']) ? $atts['src'] : PAYPAL_DEFAULT_SRC ) .'"> ' . 
(isset($atts['a1']) ? "\r\n\t" .'<input ' . $hidden . 'name="a1" value="' . $atts['a1']  .'">' : '')  .
(isset($atts['p1']) ? "\r\n\t" .'<input ' . $hidden . 'name="p1" value="' . $atts['p1']  .'">' : '') .
(isset($atts['t1']) ? "\r\n\t" .'<input ' . $hidden . 'name="t1" value="' . $atts['t1']  .'">' : '') . '
   <input ' . $hidden . 'name="currency_code" value="'.(isset($atts['currency_code']) ? $atts['currency_code'] : PAYPAL_DEFAULT_CURR_CODE ) .'">
   <input ' . $hidden . 'name="notify_url" value="' .  site_url() . '/?wp_paypal_ipn=1">
   <input ' . $hidden . 'name="return" value="' . $return_url_with_nonce . '">
   <input ' . $hidden . 'name="cancel_return" value="' . $cancel_url_with_nonce . '">
   <input ' . $hidden . 'name="cmd" value="_xclick-subscriptions">
   <input ' . $hidden . 'name="business" value="' . $paypal_business . '">
   <input ' . $hidden . 'name="bn" value="JavaScriptButton_subscribe">
   <input ' . $hidden . 'name="env" value="' . $paypal_url . '">
   <button type="submit" class="paypal-button large" style="display: flex;">'.(isset($atts['buttonname']) ? $atts['buttonname'] : PAYPAL_DEFAULT_BUTTON_NAME ) .'</button> 
   <input type="button" class="button" id="form-cancel" style="display: flex;" name="cancel" value="Cancel" onClick="location.href=\'/account/\';" />
</form>
</div>
<style>
    .paypal-img:hover {
        background: url("/images/paypal-sub-img-hover.png") no-repeat;
    }
</style>
';
    return $rt;
}



/////////////////////////////////////////////////////
function moa_paypal_mail_success( $mode, $userdata, $usermeta ) {
    // Email paypal confirmation details details to the user, note to email multiple recipients, seperate email addresses with commas.
    //$blogname = wp_specialchars_decode(get_option('blogname'), ENT_QUOTES);
    
    // refresh user data after the callback, otherwise it won't have the PayPal data in it
    $user_id = get_current_user_id();
    $userdata = get_user_by('ID', $user_id );
    $usermeta = get_user_meta( $user_id );
    
    $subject = 'Macwester Owners Association - PayPal Subscription paid';
    $headers = array('From: '. MOA_PAYPAL_LIVE_EMAIL_SENDER );
    
    $user_email     = $userdata->user_email;
    $user_login     = $userdata->user_login ;
    $user_email     = $userdata->user_email ;
    $display_name   = $userdata->display_name;
    $user_phone   = (!empty($usermeta['user_phone'][0] ) ? $usermeta['user_phone'][0] : '') ;
    
    $yacht_name     = (!empty($usermeta['yacht_name'][0])  ? $usermeta['yacht_name'][0]: '') ;
    $yacht_previous_name = (!empty($usermeta['yacht_previous_name'][0])  ? $usermeta['yacht_previous_name'][0]: '') ;
    $yacht_class    = (!empty($usermeta['yacht_class'][0]) ? $usermeta['yacht_class'][0]:'') ;
    $yacht_rig      = (!empty($usermeta['$yacht_rig'][0]) ? $usermeta['$yacht_rig'][0]:'') ;
    $yacht_sailno   = (!empty($usermeta['yacht_sailno'][0]) ? $usermeta['yacht_sailno'][0]:'') ;
    $yacht_engine   = (!empty($usermeta['$yacht_engine'][0]) ? $usermeta['$yacht_engine'][0]:'') ;
    $yacht_area_sailed = (!empty($usermeta['yacht_area_sailed'][0]) ? $usermeta['yacht_area_sailed'][0]:'') ;
    $yacht_location = (!empty($usermeta['yacht_location'][0]) ? $usermeta['yacht_location'][0]:'') ;
    $yacht_longlat = (!empty($usermeta['yacht_longlat'][0]) ? $usermeta['yacht_longlat'][0]:'') ;
    
    $addr_line1     = (!empty($usermeta['addr_line1'][0]) ? $usermeta['addr_line1'][0] : '') ;
    $addr_line2     = (!empty($usermeta['addr_line2'][0]) ? $usermeta['addr_line2'][0] : '') ;
    $addr_town      = (!empty($usermeta['addr_town'][0]) ? $usermeta['addr_town'][0] : '') ;
    $addr_county    = (!empty($usermeta['addr_county'][0]) ? $usermeta['addr_county'][0] : '') ;
    $addr_postcode  = (!empty($usermeta['addr_postcode'][0] ) ? $usermeta['addr_postcode'][0] : '') ;
    $addr_country   = (!empty($usermeta['addr_country'][0] ) ? $usermeta['addr_country'][0] : '') ;
    
    $paypal_transaction = (!empty($usermeta['paypal_transaction_code'][0]) ? $usermeta['paypal_transaction_code'][0]:'') ;
    $paypal_amount      = (!empty($usermeta['paypal_amount'][0] ) ? $usermeta['paypal_amount'][0]:'') ;
    $paypal_status      = (!empty($usermeta['paypal_status'][0] ) ? $usermeta['paypal_status'][0]:'') ;
    $paypal_paid_date   = (!empty($usermeta['paypal_paid_date'][0] ) ? $usermeta['paypal_paid_date'][0]:'') ;
    $paypal_anniversary = (!empty($usermeta['paypal_anniversary_date'][0] ) ? $usermeta['paypal_anniversary_date'][0]:'') ;
    $paypal_merchant    = (!empty($usermeta['paypal_merchant_code'][0] ) ? $usermeta['paypal_merchant_code'][0]:'') ;
    
    
    $crlf = "\r\n";
    $msg = "Hello $display_name, $crlf";
    $msg .= $crlf;
    $msg  = "Welcome to the Macwesters Owners Association; you are now a fully paid-up member and have access to all parts of the Asociation\'s web site. ";
    $msg .= "You will receive twice-yearly club journals posted to the address you have listed. ";
    $msg .= "If you are a new joiner then you will also receive a New Joiners Pack (membership card, free club burgee and latest journal). $crlf";
    $msg .= $crlf;
    $msg .= "The MOA website details are as follows: $crlf" ;
    $msg .= "MOA Web Site: " . wp_login_url() . "$crlf";
    $msg .= "Your login name: $user_login $crlf";
    $msg .= "and your password is as you specified. In case you forget it there is an email password reset option on the login page$crlf";
    $msg .= "Your PayPal Subscription details are as follows $crlf";
    $msg .= "  PayPal Transaction Code:           $paypal_transaction $crlf";
    $msg .= "  PayPal Merchant Code:              $paypal_merchant (this code will appear on the PayPal statement) $crlf";
    $msg .= "  PayPal First Year Subscription:    $paypal_amount GBP $crlf";
    $msg .= "  PayPal Subsequent Subscriptions:   " . PAYPAL_DEFAULT_AMOUNT . " GBP $crlf"; // use default because PayPal callback doesn't provide this data 
    $msg .= "  PayPal Subscription Paid Date:     $paypal_paid_date $crlf";
    $msg .= "  PayPal Subscription Anniversary:   $paypal_anniversary $crlf";
    $msg .=  $crlf;
    $msg .= "Any Paypal Subscription can be canceled by logging into to your PayPal account, going to your Profile, clicking Payments, ";
    $msg .=  "and then clicking \"Manage your pre-approved payments\". $crlf";
    $msg .=  $crlf;
    $msg .= "If you've any questions, or anything we can help you with, then please email us using the address below. $crlf";
    $msg .=  $crlf;
    $msg .=  "Best Regards $crlf";
    $msg .=  $crlf;
    $msg .=  "MOA Club Team " .  MOA_PAYPAL_LIVE_EMAIL_SENDER . $crlf;
    
    wp_mail($user_email, $subject , $msg, $headers);
    
    ///////////////////// send email to admin
    if ( strtolower($mode) == "new member") 
        $subject = "A New User has joined the MOA Club and paid their Subscription on-line by PayPal";
    else  
        $subject = "An Existing User has renewed their MOA Subscription and paid on-line by PayPal";
    
    $msg = "";    
    $msg .= "User $display_name has just joined the Club as a fully paid-up member . Their details are as follows: $crlf";
    $msg .= $crlf;
    $msg .= "USER DETAILS $crlf";
    $msg .= "User Display Name:  $display_name $crlf" ;
    $msg .= "User Login Name: $user_login $crlf" ;
    $msg .= "User Email: $user_email $crlf" ;
    $msg .= "User Phone: $user_phone $crlf" ;
    $msg .= $crlf ;
    $msg .= "BOAT DETAILS $crlf";
    $msg .= "Yacht name: $yacht_name $crlf" ;
    $msg .= "Yacht previous name: $yacht_previous_name $crlf" ;
    $msg .= "Yacht Class:  $yacht_class $crlf" ;
    $msg .= "Yacht Rig:  $yacht_rig $crlf" ;
    $msg .= "Yacht Sail No:  $yacht_sailno $crlf" ;
    $msg .= "Yacht Engine:  $yacht_engine $crlf" ;
    $msg .= "Yacht Location: $yacht_location $crlf" ;
    $msg .= "Yacht Mooring Coordinates: $yacht_longlat" . $crlf ;
    $msg .= "Area Usually Sailed: $yacht_area_sailed $crlf" ;
    $msg .= $crlf;
    $msg .= "ADDRESS DETAILS $crlf";
    $msg .= "Address 1:  $addr_line1 $crlf" ;
    $msg .= "Address 2: $addr_line2 $crlf" ;
    $msg .= "Town: $addr_town $crlf" ;
    $msg .= "County: $addr_county $crlf" ;
    $msg .= "Country: $addr_country $crlf" ;
    $msg .= "Postcode: $addr_postcode $crlf" ;
    $msg .= $crlf;
    $msg .= "PAYPAL TRANSACTION DETAILS$crlf";
    $msg .= "PayPal Transaction Code:           $paypal_transaction $crlf";
    $msg .= "PayPal Merchant Code:              $paypal_merchant (as it will appear on their PayPal statement) $crlf";
    $msg .= "PayPal First Year Subscription:    $paypal_amount GBP $crlf";
    $msg .= "PayPal Subsequent Subscriptions:   " . PAYPAL_DEFAULT_AMOUNT . " GBP $crlf"; // use default because PayPal callback doesn't provide this data
    $msg .= "PayPal Subscription Paid Date:     $paypal_paid_date $crlf";
    $msg .= "PayPal Subscription Anniversary:   $paypal_anniversary $crlf";
    $msg .=  $crlf;
    $msg .= "The user has received a confirmatory email listing their PayPal details. $crlf";
    $msg .=  $crlf;
    $msg .=  "Sent from the Membership Application Form Email Module$crlf";
    
    // work out who to send the email to
    if ( MOA_PAYPAL_SANDBOX ) {
        $paypal_admin_email = MOA_PAYPAL_SANDBOX_EMAIL_RECIPIENTS;
    } else {
        $paypal_admin_email = MOA_PAYPAL_LIVE_EMAIL_RECIPIENTS;
    }
    
    wp_mail($paypal_admin_email, $subject , $msg, $headers);
    
}

function moa_paypal_button_post_hook() {
    // $_GET and $_POST are available
    $g = $_REQUEST;
    $p = $_POST;
    
    // BEWARE - NOT WORKING YET, THIS IS BUILDING A QUERY STRING, TO EEMULATE THE FORM POST
    // WE NEED TO SEND THE ARGS IN A POST REQUEST
    $url = $_POST['target'];
    unset($_POST['target']);
    unset($_POST['action']);
    $args_arr = array_map( 'esc_url', $_POST );     // sanitize h array elements to encode "&" and spaces etc
    $location = add_query_arg( $args_arr, $url ) ;  // create the URL and query string for PayPal
    exit ( $location ) ;                            // debug testing
    // wp_redirect($location);                         // and redirect to paypal
    exit;
}


/////////////////////////// Functions to show parts of the Form ///////////////////////
function moa_jquery_on_ready( $block, $action  ) {
    // expects $block = "#address-block" or ".form-button-bar" for element id or class identified
    // and $action = ".show()" or '.css("display" , "flex")'  or '.css( "display", "block" )' or '.css( { "display:block", "color:red" } )'
    return ( "<script type=\"text/javascript\">
	jQuery(document).ready( function() {jQuery('" . $block . "')" . $action . ";} ); 
</script>" );
  
}

function moa_jquery_show_address_block(  ) {
    ?><script type="text/javascript">
	jQuery(document).ready( function() {jQuery('.wf').show();} ); 
	jQuery(document).ready( function() {jQuery('#address-block').show();} ); 
	jQuery(document).ready( function() {jQuery('.form-button-bar').css( "display" ,"flex" );} ); 
</script><?php
}

function moa_jquery_show_paypal_block(  ) {
    ?><script type="text/javascript">jQuery(document).ready( function() {jQuery('#paypal-block-details').show();} );</script><?php
}

function moa_jquery_show_paypal_renewal(  ) {
?><script type="text/javascript"> 
	jQuery(document).ready( function() {jQuery('#paypal-block-renewal').show();} ); 
	jQuery(document).ready( function() {jQuery('.paypal-button').css( "display" ,"flex" );} ); 
</script><?php 
}
function moa_jquery_show_paypal_new_member(  ) {
?><script type="text/javascript">
	jQuery(document).ready( function() {jQuery('#paypal-block-new-member').show();} ); 
	jQuery(document).ready( function() {jQuery('.paypal-button').css( "display" ,"flex" );} ); 
</script><?php 
}
function moa_jquery_show_paypal_success_msg(  ) {
?><script type="text/javascript">jQuery(document).ready( function() {jQuery('#paypal-callback-success-msg').css( "display" ,"block" );} );</script><?php 
}
function moa_jquery_show_paypal_cancelled_msg(  ) {
?><script type="text/javascript">jQuery(document).ready( function() {jQuery('#paypal-callback-cancelled-msg').css( "display" ,"block" );} );</script><?php
}