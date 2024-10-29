jQuery(document).on('click','#custom_amz_widget .inside .amzw-button #refresh_widget',function(e){
	e.preventDefault();
        
            jQuery(this).html("Loading...");
            var action = 'amz_dashbard_ajax';
            var data = {
                'action': action,
                
            };
            var ajaxurl =dashbaord_object.ajaxurl;
            jQuery.post(ajaxurl, data, function(response) {

                jQuery('#custom_amz_widget .inside').html(response);
            });
        
});