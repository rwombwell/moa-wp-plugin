<?php
/**
 * Plugin Name: MOA Club PayPal Integration
 * Plugin URI: http://www.macwester.org.uk/moa-club-plugin
 * Description: A handcrafted Plugin built by the MacWester Owners Association Club for their web site, providing functions that integrate WP PayPal with the UM Forms and CF7 Forms user access & registration systems     
 * Version: 1.0
 * Author: Rob Wombwell
 * Author URI: http://www.macwester.org.uk
 */

// load up include files 
foreach ( glob( dirname( __FILE__ ) . '/includes/*.php' ) as $file ) {
    require $file;
}

// load up elastic search files
foreach ( glob( dirname( __FILE__ ) . '/moa-elasticsearch/*.php' ) as $file ) {
    require_once $file;
}

//////////////////////
// Add local style sheets from css\ folder into header
//////////////////////   
add_action('wp_enqueue_scripts','load_moa_styles');
function load_moa_styles(){
 
    // for Smash Baloon Facebook Feed Pro Plugin content
    wp_enqueue_style( 'moa-style-facebookfeeds', plugins_url( 'css/moa-facebook-feed.css' , __FILE__ ) );
	// for  CF7 Club Membership forms
    wp_enqueue_style( 'moa-style-cf7-forms', plugins_url( 'css/moa-cf7-styles.css' , __FILE__ ));
	// for  UM Registration Forms 
    wp_enqueue_style( 'moa-style-um-forms', plugins_url( 'css/moa-um-styles.css' , __FILE__ ));
	
	wp_enqueue_style( 'moa_bbpress-styles', plugins_url( 'css/moa-bbp-image-upload.css' , __FILE__ ) );
   // wp_enqueue_style( 'ebsoc-modal-styles', plugins_url( 'css/ebsoc-modal.css' ,   __FILE__ ) );  // called from moa-contact-us.php
	// for FancyBox gallery, (used by Photo Competition)
	wp_enqueue_script( "fancybox",      "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js",array('jquery'),NULL,false);
	wp_enqueue_style( "fancybox-styles", "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css");
	
	// Enhanced WPUF Post Forms, takedn from EBSOC stuff
	wp_enqueue_style( "moa-wpuf-styles", plugins_url( 'css/moa-wpuf-styles.css' , __FILE__ ) );
	// Styles for LRM and APSL Login and Registration Forms
	wp_enqueue_style( "moa-lrm-styles", plugins_url( 'css/moa-lrm-styles.css' , __FILE__ ) );
	
	// Load up Fancybox Gallery Viewer for Post Types
	wp_enqueue_script( "fancybox",      "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.js",array('jquery'),NULL,false);
	wp_enqueue_style( "fancybox-style", "https://cdn.jsdelivr.net/gh/fancyapps/fancybox@3.5.7/dist/jquery.fancybox.min.css");
	   
    
}    
