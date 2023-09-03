<?php
/****************************
 * LRM AJAX LOGIN AND REGISTRATION POPUP MODAL FORM additions
 * This is needed for Adding extra registration fields, 
 * see this article https://docs.maxim-kaminsky.com/lrm/kb/how-to-add-custom-registration-fields/
 * The following functions allow for adding custom registration fields, 2 of which alter the wp-admin user registration form, which we don;t use
 * LRM POPUP REGISTRATION FORM 
 * 1. Add HTML code with <input..> stuff to the Reg form from Wp-Admin > Settings > Login/Register Module > EXPRESSIONS > PRO
 *    OR - we can add the HTML  using the LRM add_action( 'register_form', 'lrm_custom_registration_form' ); (see below)
 * in which case a typical addition in "Before Registration Button" looks like...
    ... 
    <div class="lrm-col-half-width lrm-col-first fieldset--yacht-name lrm-col">
      <label class="image-replace lrm-email lrm-ficon-boat" title=""></label>
      <input name="yacht-name" class="full-width has-padding has-border" type="text" placeholder="Boat Name" required="">
      <span class="lrm-error-message"></span>
    </div>
    ...
 * 2. Add Error handling for the custom field 
 *    using add_filter( 'registration_errors', 'lrm_custom_registration_errors', 10, 3 );
 * 3. Code that updates the database with Custom Fields after a user registers, 
 *    using add_action( 'user_register', 'lrm_custom_user_register' );
 * BACK END (WP-ADMIN) REGISTRATION FORM
 * 4. Admin Screen (Back End) new user registration called from Wp-Admin >> Users >> Add New User 
 *    using add_action( 'user_new_form', 'lrm_custom_admin_registration_form' );
 * 5. Admin Back End -  new user registration Error handling
 *    using add_action( 'user_profile_update_errors', 'lrm_custom_user_profile_update_errors', 10, 3 );
 * 6. Admin Back End -  Update the database after user registers, (use same code for LRM popup
 *    using add_action( 'edit_user_created_user', 'lrm_custom_user_register' );
 ****************************/

/////////////////////////////////////////////
// Custom fields appear after the "Terms and Conditions" checkbox, this trick puts the fields before the checkbox
// taken from this https://gist.github.com/max-kk/52be86f5843e57fe038550b10dd09c0e#file-show_fields_before_the_terms-php 
/////////////////////////////////////////////
if ( is_plugin_active("ajax-login-and-registration-modal-popup-pro/login-registration-modal-pro.php") ) {
    remove_action('lrm/register_form/before_button', array(LRM_Pro_Form::get(), 'before_registration_form_button__action'), 9);
    add_action('lrm/register_form', array(LRM_Pro_Form::get(), 'before_registration_form_button__action'), 9); 
} 
/**************************************
* Shortcode snippet to add boat fields to the LRM Registeration Form which, because its a popup is actually embedded 
* in every page. To get the shortcode to fire the shortcode needs to have been added in the LRM Settings configuration 
* here LRM Settings > EXPRESSIONS PRO > Custom Text/HTML > Before registration button field.
*
* One problem is that we don;t want these fields on the  UM Registration Form or the UM Login Form, hence the 
* use of um_is_core_page() test below which bombs out if its either of these two pages. 
***************************************/
add_shortcode("moa-lrm-register-boat-fields","moa_lrm_register_boat_fields_fn");
function moa_lrm_register_boat_fields_fn( $atts, $contents){
    // don't apply this shortcode if the page being displayed is the UM Registation page, that'll have it's own 
    //  
    if ( um_is_core_page('register') || um_is_core_page('login')) { 
        return;
    }
    echo '
<!-- Add immediately visible Boat fields with Map popup button to form  -->
<div class="moa-register-boat-profile-container">
    
    <div class="moa-register-boat-profile-heading">
        <p>Please give details about your Macwester</p>
    </div>

    <div class="lrm-col-half-width lrm-col-first fieldset--boatfields lrm-col">
        <label for="yacht_name">Name of Boat:</label>
        <input id="yacht_name" name="yacht_name" class="full-width has-border" type="text" placeholder="Boat Name" required="yes">
        <span class="lrm-error-message"></span>
    </div>
    
    <div class="lrm-col-half-width lrm-col-last fieldset--boatfields lrm-col">
       <label for="yacht_longlat">Location of Boat (Long,Lat):</label>
       <input id="yacht_longlat" name="yacht_longlat" class="full-width has-border" type="text" placeholder="Use MAP button to set" required="">
       <span class="lrm-error-message"></span>
    </div> 
    
    <!-- The Modal Popup Map block, map button created & added to right of yacht_longlat field by JavaScript code inside PopupMapCreate() -->
    <div id="moa-popup-window" class="moa-popup-window">
        <div class="map-div" id="map-div" style="height: 400px; width: 100%;">
            <button type="button" class="moa-popup-close" title="close map">&times;</button>
        </div>
        <script>
         (function( $ ) {
            PopupMapCreate("map-div", "yacht_longlat");
            })( jQuery );
        </script>
    </div>

    <div class="lrm-col-half-width lrm-col-first lrm-col fieldset--boatfields">
		<label for="boat-class-select">Choose Boat Class:</label>
		<select id="yacht_class" name="yacht_class" class="full-width has-border" required="yes" >
            <option value="unknown">Unknown</option>
            <option value="Kelpie">Mac Kelpie</option>
            <option value="26">Mac 26</option>
            <option value="27">Mac 27</option>
            <option value="Mac 28">Mac 28</option>
            <option value="Malin">Mac Malin</option>
            <option value="Rowan 22">Rowan 22</option>
            <option value="Rowan 8m">Rowan 8m</option>
            <option value="Rowan Crown">Rowan Crown</option>
            <option value="Wight Mk1">Mac Wight Mk1</option>
            <option value="Wight Mk2">Mac Wight Mk2</option>
            <option value="Seaforth">Mac Seaforth</option>
            <option value="Pelagian">Mac Pelagian</option>
            <option value="Atlanta">Mac Atlanta</option>
        </select>
    </div>
</div>

<div class="moa-extra-fields" style="display:none;background:cornflowerblue;color:white;">
    ADDITIONAL FIELDS GO HERE [MORE TO DO]
</div>

<!-- ADDITIONAL BOAT/PROFILE FIELDS ON SHOW/HIDE LINK -->
<a href="#" id="moa-show-flds">Show Additional Fields</a>
<script type="text/javascript">
    jQuery( document ).ready(function(event) {
        $("#moa-show-flds").click(function (){
            if ( $("#moa-show-flds").text().indexOf("Show") >= 0 ) {
                $("#moa-show-flds").html("Hide Additional Fields");
                $(".moa-extra-fields").show();
            } else {
                $("#moa-show-flds").html("Show Additional Fields");
                $(".moa-extra-fields").hide();
            }
        });
    });
</script>
';
}

///////////////////////////////////////////////
// Snippet to support Custom Field,  
// ONLY IF you want the user login via phone-number
///////////////////////////////////////////////
/* add_filter('lrm/login_info_filter', function ($info) {
    $user_login = $info['user_login'];
    // Optionally
    // $user_login = str_replace(["+", "-", " "], '', $info['user_login']);
    
    // Comments this to allow phone duplicates
    $users = get_users(array(
    'meta_key'     => 'phone',
    'meta_value'   => $user_login,
    ));
    
    if ( $users ) {
        $info['user_login'] = $users[0]->user_login;
    }
    return $info;
});
*/

/*
////////////////////////////////////
// 1. Add HTML for custom field to LRM Registration programatically, alternative to adding it via  
//     Wp-Admin > Settings > Login/Register Module > EXPRESSIONS > PRO
////////////////////////////////////
add_action( 'register_form', 'lrm_custom_registration_form' );
function lrm_custom_registration_form() {
    
    return;     // skip this function for now
    
    // note these fields are added after 
    $phone = ! empty( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
    echo '
<p>
    <label for="phone" class="image-replace lrm-email lrm-ficon-phone">' . esc_html_e( 'Phone', 'cn' ) . '<br/>
        <input "class="input" type="text" id="phone" name="phone" value="' . esc_attr( $phone ). '" />
    </label>
</p>';
}

//////////////////////////////////////
 // 2. Add Error handling for the custom field 
 ////////////////////////////////////
add_filter( 'registration_errors', 'lrm_custom_registration_errors', 10, 3 );
function lrm_custom_registration_errors( $errors, $sanitized_user_login, $user_email ) {
    if ( empty( $_POST['yacht_name'] ) ) {
        $errors->add( 'yacht_name_error', __( '<strong>ERROR</strong>: Please enter your boats name.', 'cn' ) );
    }
    if ( empty( $_POST['yacht_longlat'] ) ) {
        $errors->add( 'yacht_longlat_error', __( '<strong>ERROR</strong>: Please enter your boats long and lat (use the MAP button).', 'cn' ) );
    }
    
    return $errors;
}
*/

////////////////////////////////// 
// 3. Code that updates the database with Custom Fields after a user registers
// Note that user_register hook is a standard WordPress hook, and so called by both the LRM and the UM Registrations functions.
// The field names are presented in $_POST data based on <input ...> ids, which from LRM are the same as the fielnames, however 
// UM appends the Form_Id to the input id, (like "yacht_name-1043") and so requires more subtle processing to strip off the "...-1043" part
// We skip the UM work, because UM will havealready  saved its fields by the time we get here, so its not important, 
// except for rebuilding the Mac Map because new boat details have (possinbly) been added - we do it anyway, doesn;t take long!
//////////////////////////////////
add_action( 'user_register', 'lrm_custom_user_register' );
function lrm_custom_user_register( $user_id ) {

    if ( um_is_core_page('register') ){     // see UM docs on core pages https://ultimatemember.com/php-docs/
        update_option( 'moa_overlay_rebuild', 1);   //and set flag and record who and when
        CheckOverlayRebuildNeeded();
        return;
    }

    if ( ! empty( $_POST['yacht_name'] ) ) {
        update_user_meta( $user_id, 'yacht_name', sanitize_text_field($_POST['yacht_name'] ) );
        update_option( 'moa_overlay_rebuild', 1);   //and set flag and record who and when
    }
    if ( ! empty( $_POST['yacht_class'] ) ) {
        update_user_meta( $user_id, 'yacht_class', sanitize_text_field($_POST['yacht_class'] ) );
        update_option( 'moa_overlay_rebuild', 1);   //and set flag and record who and when
    }
    if ( ! empty( $_POST['yacht_longlat'] ) ) {
        update_user_meta( $user_id, 'yacht_longlat', sanitize_text_field($_POST['yacht_longlat'] ) );
        update_option( 'moa_overlay_rebuild', 1);   //and set flag and record who and when
    }
    if ( ! empty( $_POST['yacht_class'] ) ) {
        update_user_meta( $user_id, 'yacht_class', sanitize_text_field($_POST['yacht_class'] ) );
        update_option( 'moa_overlay_rebuild', 1);   //and set flag and record who and when
    }
    // Check if we need to rebuild the KML MOA boat Map Overlay File 
    // (note the function always checks the wp_option "moa_overlay_rebuild" which we've just set) 
    CheckOverlayRebuildNeeded();
    
}

/*
//////////////////////////////////
// BACKEND REGISTRATION FORM
// 4. Admin Screen (Back End) new user registration called from Wp-Admin >> Users >> Add New User 
//    This adds a new section to the Admin form 
//////////////////////////////////
add_action( 'user_new_form', 'lrm_custom_admin_registration_form' );
function lrm_custom_admin_registration_form( $operation ) {
    // $operation may also be 'add-existing-user' so bomb out if so
    if ( $operation !== 'add-new-user' ) {
        return;
    }
    $phone = ! empty( $_POST['phone'] ) ? sanitize_text_field( $_POST['phone'] ) : '';
    echo '
    <h3>' . esc_html_e( "Personal Information (from LRM Plugin)", "cn" ) . '</h3>
    <table class="form-table">
        <tr>
            <th><label for="phone">' . esc_html_e( "Phone", "cn" ) . '</label><span class="description">' . esc_html_e( "(required)", "cn" ) . '</span></th>
            <td>
                <input type="text"
                       id="phone"
                       name="phone"
                       value="' . esc_attr( $phone ) . '"
                       class="regular-text"
                />
            </td>
        </tr>
    </table>' ;
}
////////////////////////////////////
// 5. Admin Back End -  new user registration Error handling
 //////////////////////////////////
add_action( 'user_profile_update_errors', 'lrm_custom_user_profile_update_errors', 10, 3 );
function lrm_custom_user_profile_update_errors( $errors, $update, $user ) {
    if ( $update ) {
        return;
    }
    if ( empty( $_POST['phone'] ) ) {
        $errors->add( 'phone_error', __( '<strong>ERROR</strong>: Please enter your Phone.', 'cn' ) );
    }
}
//////////////////////////////////
// 6. Admin Back End -  Update the database after user registers, (use same code for LRM popup
//////////////////////////////////
add_action( 'edit_user_created_user', 'lrm_custom_user_register' );

//////////////////////////////////
// 7. Admin Back End -  Display the user's custom data on Back End profile
//////////////////////////////////
add_action( 'show_user_profile', 'lrm_custom_show_extra_profile_fields' );
add_action( 'edit_user_profile', 'lrm_custom_show_extra_profile_fields' );
function lrm_custom_show_extra_profile_fields( $user ) {
    ?>
    <h3><?php esc_html_e( 'Personal Information', 'cn' ); ?></h3>

    <table class="form-table">
        <tr>
            <th><label for="phone"><?php esc_html_e( 'Phone', 'cn' ); ?></label></th>
            <td><?php echo esc_html( get_user_meta( $user->ID, 'phone', true ) ); ?></td>
        </tr>
    </table>
    <?php
}
*/