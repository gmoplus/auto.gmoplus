
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LIB.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2023
 *	https://www.flynax.com
 *
 ******************************************************************************/
$(document).ready(function(){ 
    $('#listings select.selector, #agencies select.selector').change(function(){ 
        var id    = $(this).data('id');
        var $self = $(this);
        var data  = {
            mode:   'listing_status',
            item:   'listing_status',
            id:     id,
            status: $(this).val(),
            lang:   rlLang,
        };
        flUtil.ajax(data, function(response, status){
            if (status == 'success' && response) {

                if (response.status == 'ok') {
                    if(response.html) {
                        var $labelBox = $("#listing_"+id).find("div.picture>div.listing_labels");
                        $labelBox.empty();
                        $labelBox.html(response.html);
                    }
                    printMessage('notice', response.message);
                }
                else {                    
                    $self.find('option[value='+response.default+']').attr('selected','selected');
                    if(response.message) {
                        printMessage('error', response.message);
                    }
                }
            }
        });
    });
});
