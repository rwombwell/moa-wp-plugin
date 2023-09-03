<?php 
/**
 * Update the custom field when the form submits
 * @param type $post_id
 */
add_action( 'wpuf_add_post_after_insert', 'update_my_brand_new_hook' );
add_action( 'wpuf_edit_post_after_update', 'update_my_brand_new_hook' );

function update_my_brand_new_hook( $post_id ) {
    if ( isset( $_POST["user_name"] ) ) {update_post_meta( $post_id, "user_name", $_POST["user_name"]   ); };
    if ( isset( $_POST["boat_name"] ) ) {update_post_meta( $post_id, "boat_name", $_POST["boat_name"]   ); };
    if ( isset( $_POST["boat_class"] ) ) {update_post_meta( $post_id, "boat_class", $_POST["boat_class"]); };
    
    // get the fields in this wpuf form, then look for the labels on the file_upload fields
    $field_array = array();
    $form_id = get_post_meta( $post_id, "_wpuf_form_id", true);
    
    $args = array('post_type'=>'wpuf_input', 'post_parent'=> $form_id , 'posts_per_page'=>-1);
    $posts_array = get_posts($args);
    foreach( $posts_array as $field) {
        $field_data =  unserialize($field->post_content);
        $template =$field_data ["template"];
        if ($template === "featured_image" || $template === "file_upload"){
            $field_label = $field_data ["label"];
            $field_name  = $field_data ["name"];
            if ( $field_name  == "featured_image" ) {   // the "featured image" is called "_thumbnail_id" in the post_meta records
                $field_name = "_thumbnail_id"; 
            }
            // now, get the wp_post_meta record for the post where the meta_key is the fieldname (ie "_thumbnail_id" of "file_upload_1" etc.
            // Each of thes e post_meta data records holds a meta_value that is the ID of the wp_post record where the image is held
            // so we get those posts, then force the label string into the "post_excerpt" field
            $post_image_id = intval(get_post_meta( $post_id, $field_name, true));
            if ( $post_image_id ) {
                $post_image = get_post( $post_image_id );
                $post_update = array(
                    'ID'         => $post_image->ID,
                    'post_excerpt' => $field_label
                );
                wp_update_post( $post_update );
            }
            //update_post_cache( $post_image );  
        }
    }
   
        
    
    
    
    
    
}
            
////////////////////////////////////////////
add_action('ADD_MOA_FIELDS', 'your_function_name', 10, 3 );
function your_function_name( $form_id, $post_id, $form_settings ) {
    // do what ever you want
    
    $cu = get_current_user_id ();
    $um = get_user_meta ($cu);
    $user_name= $um["first_name"][0] . " " . $um["last_name"][0];
    $boat_name= $um["yacht_name"][0];
    $boat_class= $um["yacht_class"][0];
    
    echo '
<li class="wpuf-el your_name field-size-large read-only " data-label="Name">
    <div class="wpuf-label">
        <label for="your_name_4455">Name</label>
    </div>
    <div class="wpuf-fields">
        <input class="textfield wpuf_user_name_4455" id="user_name_4455" type="text" data-required="no" data-type="text" name="user_name" placeholder="" value="' . $user_name. '" size="40" readonly>
    </div>
</li>
<li class="wpuf-el your_name field-size-large read-only " data-label="Boat Name">
    <div class="wpuf-label">
        <label for="your_name_4455">Boat Name (from your Profile)</label>
    </div>
    <div class="wpuf-fields">
        <input class="textfield wpuf_boat_name_4455" id="user_boat_name_4455" type="text" data-required="no" data-type="text" name="boat_name" placeholder="" value="' . $boat_name. '" size="40" readonly>
    </div>
</li>
<li class="wpuf-el your_name field-size-large read-only " data-label="Boat Class">
    <div class="wpuf-label">
        <label for="your_name_4455">Boat Class (from your Profile)</label>
    </div>
    <div class="wpuf-fields">
        <input class="textfield wpuf_boat_class_4455" id="user_boat_class_4455" type="text" data-required="no" data-type="text" name="boat_class" placeholder="" value="' . $boat_class . '" size="40" readonly>
    </div>
</li>
';
    
    // get the fields in this wpuf form, then look for the labels on the file_upload fields  
    $args = array('post_type'=>'wpuf_input', 'post_parent'=> $form_id , 'posts_per_page'=>-1);
   $posts_array = get_posts($args);
    foreach( $posts_array as $field) {
        $field_data =  unserialize($field->post_content);
        $template =$field_data ["template"];
        if ($template === "featured_image" || $template === "file_upload"){
            $label =$field_data ["label"];
        }
    }
    return;
}

////////////////////////////////////////////
add_shortcode( 'moa-photo-competition-file-upload', 'moa_photo_competition_file_upload' );
/******************
 *
 * @param {array} $atts standard array of shortcode arguments
 * @param {string} $content standard shortcode contents string
 * @return string
 *******************/
function moa_photo_competition_file_upload( $atts, $contents){
 return '
 <style>
 #drop-area {
	border: 2px dashed #ccc;
	border-radius: 20px;
	width: 480px;
	font-family: sans-serif;
	margin: 100px auto;
	padding: 20px;
  }
  #drop-area.highlight {
	border-color: purple;
  }
  p {
	margin-top: 0;
  }
  .my-form {
	margin-bottom: 10px;
  }
  #gallery {
	margin-top: 10px;
  }
  #gallery img {
	width: 150px;
	margin-bottom: 10px;
	margin-right: 10px;
	vertical-align: middle;
  }
  .button {
	display: inline-block;
	padding: 10px;
	background: #ccc;
	cursor: pointer;
	border-radius: 5px;
	border: 1px solid #ccc;
  }
  .button:hover {
	background: #ddd;
  }
  #fileElem {
	display: none;
  }
 </style>
 <div class="moa-file-upload">
	(from shortcode)
	<div id="drop-area">
	<form class="my-form">
	<p>Upload multiple files with the file dialog or by dragging and dropping images onto the dashed region</p>
	<input type="file" id="fileElem" multiple accept="image/*" onchange="handleFiles(this.files)">
	<label class="button" for="fileElem">Select some files</label>
	</form>
	</div>
</div>';
}

////////////////////////////////////////////
add_shortcode( 'moa-photo-competition-file-upload_4', 'moa_photo_competition_file_upload_4' );
/******************
 *
 * @param {array} $atts standard array of shortcode arguments
 * @param {string} $content standard shortcode contents string
 * @return string
 *******************/
function moa_photo_competition_file_upload_4( $atts, $contents){
    return '
<li class="wpuf-el file_upload_4 field-size-large" data-label="Best Sailing Buddy">
    <div class="wpuf-label">
        <label for="file_upload_4_4455">Test 4th Field</label>
    </div>

    <div class="wpuf-fields">
        <div id="wpuf-file_upload_4-4455-upload-container" style="position: relative;"><div class="wpuf-file-warning"></div>
            <div class="wpuf-attachment-upload-filelist" data-type="file" data-required="no">
            <a id="wpuf-file_upload_4-4455-pickfiles" data-form_id="4455" class="button file-selector  wpuf_file_upload_4_4455" href="#" style="position: relative; z-index: 1;">Select File(s)</a>
            <ul class="wpuf-attachment-list thumbnails ui-sortable">
            </ul>
        </div>
        <div id="html5_1grgios0n17qupjrf8d10ie1j16767_container" class="moxie-shim moxie-shim-html5" style="position: absolute; top: -1px; left: 0px; width: 105px; height: 30px; overflow: hidden; z-index: 0;"><input id="html5_1grgios0n17qupjrf8d10ie1j1uc" type="file" style="font-size: 999px; opacity: 0; position: absolute; top: 0px; left: 0px; width: 100%; height: 100%;" multiple="" accept=""></div>
    </div><!-- .container -->
   </div> <!-- .wpuf-fields -->

    <script type="text/javascript">
    jQuery(function($) {
        var uploader = new WPUF_Uploader("wpuf-file_upload_4-4455-pickfiles", "wpuf-file_upload_4-4455-upload-container", 1, "file_upload_4", "jpg,jpeg,gif,png,bmp,mp3,wav,ogg,wma,mka,m4a,ra,mid,midi,avi,divx,flv,mov,ogv,mkv,mp4,m4v,divx,mpg,mpeg,mpe,pdf,", 10240);
        wpuf_plupload_items.push(uploader);
    });
    </script>
    
</li>';
}