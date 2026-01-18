
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : ADD_EDIT_LISTING.JS
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

/**
 * Append the code to existing hashTabs method which calls in the proper position we need
 *
 * @todo - Remove this code once the "tplStepFormAfterForm" hook used
 */
if (typeof hashTabs == 'function') {
    var hashTabs_tmp = hashTabs;

    hashTabs = function(){
        setDatepikerOptions();

        hashTabs_tmp();
    }
}

setDatePeriodEvents($('input[name="f[event_type]"]:checked').val());
$('body').on('click', 'input[name="f[event_type]"]', function(){ 
    setDatePeriodEvents($(this).val());
});

function setDatePeriodEvents(val) {

    if (!rlConfig['admin']) {
        if (val=='1') {
            $('input[name="f[event_date][from]"]').removeAttr('placeholder');
            $('input[name="f[event_date][to]"]').hide();
        }
        else {
            $('input[name="f[event_date][from]"]').attr('placeholder', lang['from'] );
            $('input[name="f[event_date][to]"]').show();
        }
    }
    else {
        if (val=='1') {
            $('input[name="f[event_date][to]"]').parent().hide();
            $('input[name="f[event_date][to]"]').parent().prev().hide();
        }
        else {
            $('input[name="f[event_date][to]"]').parent().show();
            $('input[name="f[event_date][to]"]').parent().prev().show();
        }
    }
}

$(window).load(function(){
    setDatepikerOptions();
});

function setDatepikerOptions() {
    var from_id = $('input[name="f[event_date][from]"]').attr('id');
    var to_id = $('input[name="f[event_date][to]"]').attr('id');


    if ($('input[name="f[event_date][from]"]').val()) {
        var dateVal = $('input[name="f[event_date][from]"]').val();
        var fromDate = new Date(dateVal), nowDate = new Date();
        var minDate = fromDate.getTime() > nowDate.getTime() ? nowDate : fromDate;
        
        $('#'+from_id).datepicker("option", 'minDate', minDate);
    }
    else {
        $('#'+from_id).datepicker("option", 'minDate', 0);
    }

    $('#'+from_id).datepicker('option', 'onSelect', function (selectedDate) {

        var option =  "minDate",
            instance = $(this).data("datepicker"),
            date = $.datepicker.parseDate(instance.settings.dateFormat || $.datepicker._defaults.dateFormat, selectedDate, instance.settings); 

        date.setDate(date.getDate()+1); 
        $('#'+to_id).datepicker("option", option, date);       
        
        if(!$('#'+to_id).val()) {
            var nextDay = $.datepicker.formatDate("yy-mm-dd", date);
            $('#'+to_id).val(nextDay);
        }
    });

    if($('#'+from_id).val()!= '') {
        $('#'+to_id).datepicker("option", "minDate", $('#'+from_id).val());
    }
}
