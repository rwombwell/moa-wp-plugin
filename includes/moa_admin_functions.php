<?php

/******************
 * SET MOA STYLES for ADMIN PAGES
for admin stylesheet to support the Wp-Admin Pages, e.g.: users, BbPress etc. need to add this action
 ******************/
add_action( 'admin_enqueue_scripts', 'admin_styles' );	// necessary to get this into admin menu
function admin_styles() {
    wp_enqueue_style( 'moa-admin-styles', plugins_url( '../css/moa-admin-styles.css' ,   __FILE__ ) );
}


/***********************************
* ADMIN MENU - USER DISPALY - Add MOA PayPal fields to the user's admin fields  
***********************************/
add_action( 'show_user_profile', 'moa_admin_extra_edit_user_fields' );
add_action( 'edit_user_profile', 'moa_admin_extra_edit_user_fields' );
function moa_admin_extra_edit_user_fields( $user ) { 
    ?>
	<h3>MOA Address Details</h3>
    <table class="form-table moa_addr_details">
        <tr><th><label for="addr">Address Line 1</label></th>
            <td><input type="text" name="addr_line1" id="addr_line1" value="<?php echo esc_attr( get_the_author_meta( 'addr_line1', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
	    <tr><th><label for="addr">Address Line 2</label></th>
          <td><input type="text" name="addr_line2" id="addr_line2" value="<?php echo esc_attr( get_the_author_meta( 'addr_line2', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
	   <tr><th><label for="addr">Address Town</label></th>
          <td><input type="text" name="addr_town" id="addr_town" value="<?php echo esc_attr( get_the_author_meta( 'addr_town', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
	   <tr><th><label for="addr">Address County</label></th>
          <td><input type="text" name="addr_county" id="addr_county" value="<?php echo esc_attr( get_the_author_meta( 'addr_county', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
	   <tr><th><label for="addr">Address Country</label></th>
          <td><input type="text" name="addr_country" id="addr_country" value="<?php echo esc_attr( get_the_author_meta( 'addr_country', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
		<tr><th><label for="addr">Address Postcode</label></th>
          <td><input type="text" name="addr_postcode" id="addr_postcode" value="<?php echo esc_attr( get_the_author_meta( 'addr_postcode', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>    
		<tr><th><label for="addr">Phone</label></th>
          <td><input type="text" name="phone" id="phone" value="<?php echo esc_attr( get_the_author_meta( 'phone', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>    
	</table> 
	<h3>MOA Boat Details</h3>
    <table class="form-table moa_yacht_details">
        <tr><th><label for="yacht">Yacht Name</label></th>
            <td><input type="text" name="yacht_name" id="yacht_name" value="<?php echo esc_attr( get_the_author_meta( 'yacht_name', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
        <tr><th><label for="yacht">Yacht Previous Name</label></th>
            <td><input type="text" name="yacht_previous_name" id="yacht_previous_name" value="<?php echo esc_attr( get_the_author_meta( 'yacht_previous_name', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
		<tr><th><label for="yacht">Yacht Class</label></th>
            <td><input type="text" name="yacht_class" id="yacht_class" value="<?php echo esc_attr( get_the_author_meta( 'yacht_class', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
        <tr><th><label for="yacht">Yacht Rig</label></th>
            <td><input type="text" name="yacht_rig" id="yacht_rig" value="<?php echo esc_attr( get_the_author_meta( 'yacht_rig', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
        <tr><th><label for="yacht">Yacht Engine</label></th>
            <td><input type="text" name="yacht_engine" id="yacht_engine" value="<?php echo esc_attr( get_the_author_meta( 'yacht_engine', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
            <tr><th><label for="yacht">Yacht Year Built</label></th>
            <td><input type="text" name="yacht_year_built" id="yacht_year_built" value="<?php echo esc_attr( get_the_author_meta( 'yacht_year_built', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
		<tr><th><label for="yacht">Yacht Location</label></th>
            <td><input type="text" name="yacht_location" id="yacht_location" value="<?php echo esc_attr( get_the_author_meta( 'yacht_location', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
		<tr><th><label for="yacht">Area Sailed</label></th>
            <td><input type="text" name="yacht_area_sailed" id="yacht_area_sailed" value="<?php echo esc_attr( get_the_author_meta( 'yacht_area_sailed', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
        <tr><th><label for="yacht">Yacht Long/Lat</label></th>
            <td><input type="text" name="yacht_longlat" id="yacht_longlat" value="<?php echo esc_attr( get_the_author_meta( 'yacht_longlat', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
        <tr><th><label for="yacht">Yacht Description</label></th>
            <td><input type="text" name="yacht_desc" id="yacht_desc" value="<?php echo esc_attr( get_the_author_meta( 'yacht_desc', $user->ID ) ); ?>" class="regular-text" /><br />
            </td></tr>
	</table>
	
    <?php 
    $userdata = get_user_by('ID', get_current_user_id() );
    if ( in_array( 'um_moa-admin', $userdata->roles ) || in_array( 'um_moa-member', $userdata->roles ) ): ?>
    <h3>MOA Members Only Download Activity</h3>
    <div class="moa-downloads-container">    
        <table class="form-table moa-downloads-details">
            <?php  
            $arrDown = get_user_meta(  $user->ID, "moa_members_downloads",true ) ; 
            if ( $arrDown ) {
                for ($i = 0; $i < count($arrDown); $i++) { 
                    echo '
                    <tr>
                        <td>
                            <label for="downloaded_date'.$i.'">Download Date</label>
                            <input style="width:150px" type="text" name="downloaded_date'.$i.'" id="downloaded_date'.$i.'" value="'. gmdate('d/m/Y H:i', $arrDown[$i]['date']) .'" class="regular-text" />
                        </td>
                        <td>
                            <label for="downloaded_file'.$i.'">Downloaded Resource</label>
                            <input style="width:350px" type="text" name="downloaded_file'.$i.'" id="downloaded_file'.$i.'" value="'. $arrDown[$i]['resource'] .'" class="regular-text" />
                        </td>
                    </tr>';
                }
            } else {
                echo '<tr><td>No Members Only Downloads done (e.g.: Past Journals)</td></tr>';
            }
            ?>
        </table>
    </div>
    <?php endif ?>
   
    <h3>MOA On-Line PayPal Details</h3>
    <table class="form-table  moa_paypal_details">
        <tr>
            <th><label for="paypal">PayPal Status</label></th>
            <td><input type="text" name="paypal_status" id="paypal_status" value="<?php echo esc_attr( get_the_author_meta( 'paypal_status', $user->ID ) ); ?>" class="regular-text" /><br />
              <!--  <span class="description">Current user PayPal (one of 'renewal paid','new member paid' or blank)</span><br /> -->
            </td>
        </tr>
       	<tr>
            <th><label for="paypal">PayPal Paid Date</label></th>
            <td><input type="text" name="paypal_paid_date" id="paypal_paid_date" value="<?php echo esc_attr( get_the_author_meta( 'paypal_paid_date', $user->ID ) ); ?>" class="regular-text" /><br />
               <!--  <span class="description">Date the user paid their Club subscription (using on-line PayPal)</span><br /> -->
		    </td>
        </tr>
        <tr>
            <th><label for="paypal">PayPal Anniversary Date</label></th>
            <td>
                 <input type="text" name="paypal_anniversary_date" id="paypal_anniversary_date" value="<?php echo esc_attr( get_the_author_meta( 'paypal_anniversary_date', $user->ID ) ); ?>" class="regular-text" /><br />
           <!--     <span class="description">Anniversary date for the users subscription</span><br /> -->
            </td>
        </tr>
    </table>
	<?php 
}
/*******************
*  ADMIN MENU - USER DISPLAY -Add MOA user metadata fields to the Users page & make them editable from admin menu
*******************/
add_action( 'personal_options_update', 'moa_admin_save_extra_edit_user_fields' );
add_action( 'edit_user_profile_update', 'moa_admin_save_extra_edit_user_fields' );
function moa_admin_save_extra_edit_user_fields( $user_id ) {

    if ( ! current_user_can( 'edit_user', $user_id ) ) {
        return false;
    }
    update_user_meta( $user_id, 'addr_line1', esc_attr( $_POST['addr_line1'] ) );
    update_user_meta( $user_id, 'addr_line2', esc_attr( $_POST['addr_line2'] ) );
    update_user_meta( $user_id, 'addr_town`', esc_attr( $_POST['addr_town'] ) );
    update_user_meta( $user_id, 'addr_county', esc_attr( $_POST['addr_county'] ) );
    update_user_meta( $user_id, 'addr_country', esc_attr( $_POST['addr_country'] ) );
    update_user_meta( $user_id, 'addr_postcode', esc_attr( $_POST['addr_postcode'] ) );
    update_user_meta( $user_id, 'phone', esc_attr( $_POST['phone'] ) );
    
    update_user_meta( $user_id, 'yacht_name', esc_attr( $_POST['yacht_name'] ) );
    update_user_meta( $user_id, 'yacht_previous_name', esc_attr( $_POST['yacht_previous_name'] ) );
    update_user_meta( $user_id, 'yacht_class', esc_attr( $_POST['yacht_class'] ) );
    update_user_meta( $user_id, 'yacht_rig', esc_attr( $_POST['yacht_rig'] ) );
    update_user_meta( $user_id, 'yacht_engine', esc_attr( $_POST['yacht_engine'] ) );
    update_user_meta( $user_id, 'yacht_year_built', esc_attr( $_POST['yacht_year_built'] ) );
    update_user_meta( $user_id, 'yacht_location', esc_attr( $_POST['yacht_location'] ) );
    update_user_meta( $user_id, 'yacht_area_sailed', esc_attr( $_POST['yacht_area_sailed'] ) );
    update_user_meta( $user_id, 'yacht_longlat', esc_attr( $_POST['yacht_longlat'] ) );
    update_user_meta( $user_id, 'yacht_desc', esc_attr( $_POST['yacht_desc'] ) );
    
    update_user_meta( $user_id, 'paypal_status', esc_attr( $_POST['paypal_status'] ) );
}

/***************
* ADMIN MENU - USER Display - Remove unwanted contact fields from the user page 
* BUT can't get this to work!!!  
* based on http://wpnom.com/chapter/add-and-or-remove-wordpress-user-fields/
*****************/
add_filter('user_contactmethods', 'remove_user_contact_methods', 10,1);
function remove_user_contact_methods($profile_fields) {
    // Add new fields
    /* $profile_fields['facebook'] = 'JUNK';
    $profile_fields['twitter'] = 'Twitter Username from ROB';
    $profile_fields['gplus'] = 'Google+ URL';
  */ 
    
    unset($profile_fields['jabber']);
    unset($profile_fields['yim']);
    unset($profile_fields['aim']);
    unset($profile_fields['googleplus']);
    unset($profile_fields['url']);
    unset($profile_fields['twitter']);
    unset($profile_fields['instagram']);
    unset($profile_fields['pinterest']);
    unset($profile_fields['tumblr']);
    
    
    // Remove old fields
   /*  unset($profile_fields['facebook']);
    unset($profile_fields['twitter']);
    unset($profile_fields['gplus']);
    unset($profile_fields['pinterest']); */
    
    return $profile_fields;
}
/******************
 * ADMIN USER DETAIL DISPLAY -Remove that annoying Admin colout scheme options at the start of Admin's User page
 *****************/
add_action('admin_head', 'remove_admin_color_picker');
function remove_admin_color_picker() {
    if(is_admin()){
        remove_action("admin_color_scheme_picker", "admin_color_scheme_picker");
    }
}


/******************
 * ADMIN MENU - USER DISPLAY -Remove the annoying profile Admin's User page
 *****************/
function hide_website_field(){
    // Hide the website field on the admin Add New User form
    echo "\n" . '<script type="text/javascript">
    jQuery(document).ready(function($) {
        $(\'label[for=url]\').parent().parent().hide();
        $(\'label[for=facebook]\').parent().parent().hide();
        $(\'label[for=linkedin]\').parent().parent().hide();
        $(\'label[for=soundcloud]\').parent().parent().hide();
        $(\'label[for=tumblr]\').parent().parent().hide();
        $(\'label[for=twitter]\').parent().parent().hide();
        $(\'label[for=youtube]\').parent().parent().hide();
        $(\'label[for=pinterest]\').parent().parent().hide();
        $(\'label[for=myspace]\').parent().parent().hide();
        $(\'label[for=wikipedia]\').parent().parent().hide();
        $(\'label[for=instagram]\').parent().parent().hide();
    }); 
    </script>' . "\n";
}
add_action('admin_head','hide_website_field');

/*********************
 * ADMIN PAGE VIEW - MAKE FIELDS SORTABLE - needed to allow give some structure to to 100 or so pages 
 * We could use Admin Columns Pro Plugin ($80) but this is how to make any column sortable, taken from this article  
 * https://pressidium.com/blog/customizing-wordpress-admin-tables-getting-started/#make-columns-sortable.The objective is to make 
 * add a sdSoarable Parent field to the All PAges View. First we use Admin Columns Plugin to add 'parent' field to the "All Page" admin view. 
 * That's great, but we need this to be sortable, which requires the Pro version of the plugin, so next we just add the filter below
 * manage_edit-{post-type}_sortable_columns where post-type = "page", then we need to know the actual name of the column, which isn;t "parent", 
 * its actually '641649238978d4', which is a GUID created by Admin Columns. So we use that "field name" and assign the actual sort field 
 * which is "post_parent". We figure out the GUID by debugging this filter back to get that value 
 * wp-admin\includes\class-wp-list-table.php:1240 and looking at $columns values. Alternaticely easier to get it by just "inspecting" the field!
 * The effect of setting this value for $sortable is to change the label into a link which generates a "...&orderby=post_parent&order=asc" querystring        
 *******************/
add_filter( 'manage_edit-page_sortable_columns', 'my_edit_page_sortable_parent_column' );
function my_edit_page_sortable_parent_column( $columns ) {
    $columns['641649238978d4'] = 'post_parent';     // Provide sort link for "Parent" column by the post/page's "post_parent" field
    $columns['5e4920be0d9a7']  = 'post_name';      // Provide sort link for "Slug" column (which is actually the 'post_name')
    $columns['5c614d3b1efbf']  = 'ID';               // Provide sort link for "Page_Id" column (which is actually the post's 'ID')
        
    //To make a column 'un-sortable' remove it from the array
    // unset($columns['title']);
    return $columns;
}
/***********************
 * ADMIN USER LIST PAGE
 * To add new columns we use the Plugin "Admin Columns", this covers the Users page 
 * But to make columns sortable we do use the following hook, we need the column Ids - get by inspecting the element in the browser's devtools 
 ***********************/
add_filter( 'manage_users_sortable_columns', 'my_users_sortable_parent_column' );
function my_users_sortable_parent_column( $columns ) {
    $columns['64770d6976c8b4'] = 'Registered';     
    $columns['5c69ff1b7a555'] = 'Last Login';  
		$columns['5c66fbca30cf0'] = 'Account';  
		$columns['5c66fbca2ff03'] = 'Boat';  
		$columns['5c6adfa277b35'] = 'ID';  
		$columns['role'] = 'Role';  
	      
    //To make a column 'un-sortable' remove it from the array
    // unset($columns['title']);
    return $columns;
}
// add two columns to the user list page - NOT NEDED, Use Plugin "Admin Columns" instead 

add_filter( 'manage_users_columns', 'my_manage_users_columns' );
function my_manage_users_columns( $columns ) {
    //$columns['registration_date'] = 'Registered';
    $columns['deuid'] = 'Social Login ID';
    return $columns;
}

// provide data for the two added columns
add_filter( 'manage_users_custom_column', 'my_manage_users_custom_column', 100, 3 );
function my_manage_users_custom_column( $row_output, $column_id_attr, $user ) {
    $date_format = 'Y/m/d \a\t g:i a';
    $d1 = 0;
    switch ( $column_id_attr ) {
        // case 'registration_date':
        case "5c6adfa277b35":
            $d1 = strtotime(get_userdata($user)->user_registered);
            if ($d1 > 0) {
                $d2 = new DateTime("@$d1");
                return $d2->setTimezone(wp_timezone())->format($date_format);
            }
            break;
        case 'last_login_date':
            $session_tokens = get_user_meta( $user, 'session_tokens', true );
            if (!empty($session_tokens)) {
                $d1 = max(array_column(array_values($session_tokens),'login'));
            }
            if ($d1 > 0) {
                $d2 = new DateTime("@$d1");
                return $d2->setTimezone(wp_timezone())->format($date_format);
            }
            break;
        case "deuid":
            return "TEST";
            //break;
        default:
    }
    return $row_output;
}


/********************
* DEFAULT EMAIL SENDER ADDRESS
* Rob W added - change the wordpress default email sender address.
* taken from here: https://www.firhma.com/change-wordpress-email-sender-without-a-plugin/
********************/
/*add_filter( 'wp_mail_from', 'wpb_sender_email' );
function wpb_sender_email( $original_email_address ) {
	return 'webmaster@dev.macwester.org.uk';
}

// Function to change sender name
add_filter( 'wp_mail_from_name', 'wpb_sender_name' );
function wpb_sender_name( $original_email_from ) {
	return 'Macwester Owners Association (Dev Site)';
}
*/
