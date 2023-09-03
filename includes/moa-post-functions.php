<?php
/********************
 * CREATE POST TYPE FOR MEMBER POSTS - The following function replaces the plugin "Custom Post Type UI"
 * that we used for EBSOC. It creates an Admin page siedbar menu entry "Photo Competition"
 * Note we do the same for Tech Articles (done in moa-tech-articles.php)
 * Taken from https://www.codexworld.com/wordpress-custom-post-types-without-plugin/#:~:text=A%20custom%20post%20type%20can,post%20types%20without%20using%20Plugin.
 * ******************/
// Add Photo Competition Custom Post Type
function member_posts_init() {
    // set up labels for MOA Photo Competitions
    $labels = array(
        'name' => 'MOA Member Posts',
        'singular_name' => 'Member Posts',
        'add_new' => 'Add New Member Post',
        'add_new_item' => 'Add New Member Post',
        'edit_item' => 'Edit Member Post',
        'new_item' => 'New Member Post',
        'all_items' => 'All Member Posts',
        'view_item' => 'View Member Post',
        'search_items' => 'Search Member Post',
        'not_found' =>  'No Member Posts Found',
        'not_found_in_trash' => 'No Member Posts found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Member Posts',
    );
        // register post type
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'member-post'),
        'query_var' => true,
        'menu_icon' => 'dashicons-camera',
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'trackbacks',
            'custom-fields',
            'comments',
            'revisions',
            'thumbnail',
            'author',
            'page-attributes'
        ),
        'show_in_menu' => "edit.php",     // see this ref: https://developer.wordpress.org/reference/functions/register_post_type/#show_in_menu
        'menu_position' => 5        // 5 means position after Posts item, bignored because we've set "show_in_menu" to show post type as posts sub-menu item
  );
    register_post_type( 'member-post', $args );
    // register taxonomy
    register_taxonomy('member-post_category', 'member-post', array('hierarchical' => true, 'label' => 'Category', 'query_var' => true, 'rewrite' => array( 'slug' => 'member-post-category' )));
}
add_action( 'init', 'member_posts_init' );


/********************
 * CREATE POST TYPE FOR TECH-ARTICLES - The following function replaces the plugin "Custom Post Type UI"
 * that we used for EBSOC. It creates an Admin page siedbar menu entry "Tech Articles" 
 * Note we do the same for Photo COmpetitions (done in moa-photo-competition.php)
 * Taken from https://www.codexworld.com/wordpress-custom-post-types-without-plugin/#:~:text=A%20custom%20post%20type%20can,post%20types%20without%20using%20Plugin.
 * ******************/
// Add Technical Article Custom Post Type
function tech_article_init() {
    // set up labels for MOA Technical Articles
    $labels = array(
        'name' => 'MOA Technical Articles',
        'singular_name' => 'Technical Article',
        'add_new' => 'Add New Technical Article',
        'add_new_item' => 'Add New Technical Article',
        'edit_item' => 'Edit Technical Article',
        'new_item' => 'New Technical Article',
        'all_items' => 'All Technical Articles',
        'view_item' => 'View Technical Article',
        'search_items' => 'Search Technical Articles',
        'not_found' =>  'No Technical Article Found',
        'not_found_in_trash' => 'No Technical Article found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Technical Articles',
    );
    // register post type
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'tech-article'),
        'query_var' => true,
        'menu_icon' => 'dashicons-randomize',
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'trackbacks',
            'custom-fields',
            'comments',
            'revisions',
            'thumbnail',
            'author',
            'page-attributes'
        ),
        'show_in_menu' => "edit.php",     // see this ref: https://developer.wordpress.org/reference/functions/register_post_type/#show_in_menu
        'menu_position' => 5        // 5 means position after Posts item, bignored because we've set "show_in_menu" to show post type as posts sub-menu item
    );
    register_post_type( 'tech-article', $args );
    // register taxonomy
    register_taxonomy('tech-article_category', 'tech-article', array('hierarchical' => true, 'label' => 'Category', 'query_var' => true, 'rewrite' => array( 'slug' => 'tech-artcile-category' )));
}
add_action( 'init', 'tech_article_init' );

/********************
 * CREATE POST TYPE FOR PHOTO COMPETITIONS - The following function replaces the plugin "Custom Post Type UI"
 * that we used for EBSOC. It creates an Admin page siedbar menu entry "Photo Competition"
 * Note we do the same for Tech Articles (done in moa-tech-articles.php)
 * Taken from https://www.codexworld.com/wordpress-custom-post-types-without-plugin/#:~:text=A%20custom%20post%20type%20can,post%20types%20without%20using%20Plugin.
 * ******************/
// Add Photo Competition Custom Post Type
function photo_competition_init() {
    // set up labels for MOA Photo Competitions
    $labels = array(
        'name' => 'MOA Photo Competition',
        'singular_name' => 'Photo Competition',
        'add_new' => 'Add New Photo Competition',
        'add_new_item' => 'Add New Photo Competition',
        'edit_item' => 'Edit Photo Competition',
        'new_item' => 'New Photo Competition',
        'all_items' => 'All Photo Competitions',
        'view_item' => 'View Photo Competition',
        'search_items' => 'Search Photo Competitions',
        'not_found' =>  'No Photo Competitions Found',
        'not_found_in_trash' => 'No Photo Competition found in Trash',
        'parent_item_colon' => '',
        'menu_name' => 'Photo Competitions',
    );
    
    // register post type
    $args = array(
        'labels' => $labels,
        'public' => true,
        'has_archive' => true,
        'show_ui' => true,
        'capability_type' => 'post',
        'hierarchical' => false,
        'rewrite' => array('slug' => 'photo-competition'),
        'query_var' => true,
        'menu_icon' => 'dashicons-camera',
        'supports' => array(
            'title',
            'editor',
            'excerpt',
            'trackbacks',
            'custom-fields',
            'comments',
            'revisions',
            'thumbnail',
            'author',
            'page-attributes'
        ),
        'show_in_menu' => "edit.php",     // see this ref: https://developer.wordpress.org/reference/functions/register_post_type/#show_in_menu
        'menu_position' => 5        // 5 means position after Posts item, bignored because we've set "show_in_menu" to show post type as posts sub-menu item
    );
    register_post_type( 'photo-competition', $args );
    
    // register taxonomy
    register_taxonomy('photo-competition_category', 'photo-competition', array('hierarchical' => true, 'label' => 'Category', 'query_var' => true, 'rewrite' => array( 'slug' => 'photo-competition-category' )));
}
add_action( 'init', 'photo_competition_init' );


/******************
 * BUILDFANCYBOXIMAGES - Function to build list of a post's ATTACHED IMAGES as FancyBox anchortags, assumes that
 * fancybox JS and CSS has been already added (see above function) Fancybox: https://fancyapps.com/fancybox/3/docs/#setup
 *                 useful for returning specific named entity e.g.: "viewpoint".   
 * To build Feature Image only:     BuildFancyboxImages( $post, "feature") 
 * To build Viewpoint Image only:   BuildFancyboxImages( $post, "viewpoint")
 * To build Other Images:           BuildFancyboxImages( $post, "others") excludes viewpoint & feature
 * To build All Images:             BuildFancyboxImages( $post) includes viewpoint & feature
 * 
 * @param string $post      - the entire Post object we're looking at
 * @param string $filter    - to return a specific attachment image file STARTING with a name, this is 
 * @return string           - HTML out to stuff into the page
 */
function BuildFancyboxImages( $post, $filter=null ) {
   
    /*******************
     * Add FancyBox jQuery plugin to enable responsive image popups, Example of useage:
     * 	<a data-fancybox="gallery" href="/images/Holland.mp4">
     *			<img style="width:150px; height:auto" src="/images/Bridge Cottage and River Stour.jpg"></a>
     * See here for help on FancyBox https://fancyapps.com/fancybox/3/docs/#introduction
     *******************/
     $count = 0;
    // echo do_shortcode( '[ebsoc_carousel_post post_id="'. $post->ID .'"]');
    $argsThumb = array(
        'order'          => 'ASC',
        'post_type'      => 'attachment',
        'post_parent'    => $post->ID,
        'post_mime_type' => 'image',
        'posts_per_page' => -1, 
        'post_status'    => null
    );
            
    // Get all image attachments and loop thru them
    $listDiv = "";
    $attachments = get_posts( $argsThumb );
    if ($attachments) {
    
        // the attachments array comes back in ID order, sort it according to the order of the saved attachments in the WPUF postmeta
        $attachments = SortImages( $attachments );
        
        foreach ($attachments as $attachment) {

            $pName = strtolower(substr($attachment->post_name ,0,strlen($filter)));     // shortened post name
            $thumbId = get_post_thumbnail_id( $post->ID );                              // the id for the posts' Featured Image 
            
            // see if we want to filter the files, results in building a true/false flag for this item
            // 1st test, if this is the Feature image, and we want to skip it break out of loop
            if ( (isset($filter) === false) || (strtolower($filter) == "all")) {
                $cond = true;
                
            } else if ( (strtolower($filter) === "feature") && ($attachment->ID === $thumbId ) ) {
                $cond = true;
                
            } else if ( (strtolower($filter) === "viewpoint") && (substr($attachment->post_name ,0, 9 )=== "viewpoint" ) ) {
                $cond = true;
                
            } else if ( (strtolower($filter) === "others") 
                        && (substr($attachment->post_name ,0, 9 ) <> "viewpoint" )
                        && ($attachment->ID <> $thumbId ) ) {
                $cond = true;
                $order_by =  get_post_meta($post->ID, "image_upload") ;
                
            } else {
                $cond = false;
                
            }
            
            // now get the attachment object, get the image URL and caption and build an <a> tag for FancyBox popup
            if ($cond) {
                $count ++;
                $src = wp_get_attachment_url($attachment->ID, 'thumbnail', false, false);
                
                // IMAGE CAPTION - get this from either the attachment's post_content or post_title
                // See RegEx which converts "abc ghi-xyz" -> "abc def"
                //if ( !$caption) $caption = $attachment->post_content;       // we prefer the the attachment description
                //if ( !$caption) $caption = $attachment->post_excerpt;       // if none then use attachment caption
                //if ( !$caption) $caption = (!$caption)? preg_replace( "/\-[^\-]+$/" , '', $attachment->post_title) : '';    //if none then user title
                $title   = ($attachment->post_title )  ? strip_tags( esc_html( $attachment->post_title))  :'' ;
                
                // Expect title to have then strip out any stuff right of "-"
                // strip the title ie "Beauford Cottage-c5921d27" -> "Beauford Cottage"  
                $x =strrpos ( $title, "-", 0 );
                if ( strlen( $title) - $x == 9) $title = substr( $title, 0, $x );
                
                $excerpt = ($attachment->post_excerpt) ? strip_tags( esc_html( $attachment->post_excerpt)) . '<br />' : '';
                $content    = ($attachment->post_content) ? strip_tags( esc_html( $attachment->post_content)) : '';
                $captionAll =  $title . '<br>' . $excerpt . $content;
                
                // VIEWPOINT DIRECTION - The bearing of the Artist's viewpoint, get this
                // from the post's postmeta variable "direction"
                $direction = get_post_meta( $post->ID,"direction", true );
                $direction = ( $direction ) ? $direction : 0;
                
                // BUILD <ARTICLE...> TAG for the image  
                // Fancybox anchor links, keeping same data-fancybox="gallery" attr creates a carousel effect
                $listDiv .= '
                <article>
                    <a class="fancybox" href="' . $src . '" data-fancybox="gallery" data-options="\'closeBtn\':true" data-caption="'. $captionAll . '">
                    <img class="ebsoc-attachment-image" src="' . $src . '" alt="'.$captionAll.'" title="Click to popup Gallery View" style="cursor:pointer;"></a>' .
                    ( ($attachment->post_title)   ? "<p class=\"ebsoc-attachment-title\">"   . $title."</p>" : '' ) .
                    ( ($attachment->post_excerpt) ? "<p class=\"ebsoc-attachment-caption\">" . $attachment->post_excerpt."</p>" : '' ) .
                    ( ($attachment->post_content) ? "<p class=\"ebsoc-attachment-desc\">"    . $attachment->post_content.'</p>': '' ) .
                '</article>';
           
            } //////// end of if($cond) - builds a single image //////////////  
        } //////// end of loop thru multiple image attachments ////////////
        
        //$listDiv .= '</div>' . PHP_EOL;
    }  ////////// end of if( $attachments ) - building a set of images /////
    
    if ( $count )
        return $listDiv;
    else 
        return '';
}


/****************
 * 
 * @param {array} $atts unsorted of atachments
 */
function SortImages( $atts) {
    return $atts;   
}