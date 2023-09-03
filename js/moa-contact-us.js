/***********
* AJAX CONTACT US - JS Ajax function called when "Contact Us" form Submit button in clicked 
* Sends Ajax request to "post_contact_us" in moa-contact-us.php 
* Awaits response "success" or "failure", 
* Success 
* 	returns a HTML block "response.success" which we insert into existing div "#ebsoc-contact-form-success"
*  and make visible
* Failure 
* 	if any of the fields are invalid, these are returned for us to insert into form for another attempt 
***********/
 function AjaxContactUs( event, form_data ) {
    
   event.preventDefault();
   jQuery("[id$='-response']").empty();     // clear all the response boxes
   var data = { action: "post_contact_us", data: jQuery( form_data ).serialize() };

   jQuery.ajax({
       type : "post",
       dataType : "json",
       url : myAjax.ajaxurl,        // path to WP admin ajax handler (set in script by WP Localisation)
       data : {action: "post_contact_us", data: jQuery( form_data ).serialize(), nonce:myAjax.nonce },
       success: function(response) {
            if(response.type == "success") {
               jQuery("#ebsoc-contact-us").hide();
               jQuery("#ebsoc-contact-form-success").append(response.success);
               jQuery("#ebsoc-contact-form-success").show();
           }
           else {
               if( response.name )     jQuery("#name-response").append(response.name ); 
               if( response.email )    jQuery("#email-response").append(response.email );
               if( response.subject)   jQuery("#subject-response").append(response.subject );
               if( response.message)   jQuery("#message-response").append(response.message );
           }
       }
   });
}