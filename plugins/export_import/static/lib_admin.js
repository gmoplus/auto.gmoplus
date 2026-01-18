
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: RLEXPORTIMPORT.CLASS.PHP
 *  
 *  The software is a commercial product delivered under single, non-exclusive,
 *  non-transferable license for one domain or IP address. Therefore distribution,
 *  sale or transfer of the file in whole or in part without permission of Flynax
 *  respective owners is considered to be illegal and breach of Flynax License End
 *  User Agreement.
 *  
 *  You are not allowed to remove this information from the file without permission
 *  of Flynax respective owners.
 *  
 *  Flynax Classifieds Software 2024 | All copyrights reserved.
 *  
 *  https://www.flynax.com
 ******************************************************************************/

var import_in_progress = false;

$(document).ready(function(){
	$('#start_import').click(function(){
		importExport.start();
		$('#start_import').fadeOut();
	});
    $(window).bind('beforeunload', function() {
        if (import_in_progress) {
            return lang['eil_beforeunload_hint'];
        }
    });

    importExportPagination.registerHandlers();

    $('#imported-listings').addClass('hide');
});

var eil_colHandler = function(){
	$('input[name^=cols]').each(function(){
		var index = $(this).closest('tr.col-checkbox').find('input').index(this) + 2;

		if ( $(this).is(':checked') )
		{
			$('table.import tr td:nth-child('+index+')').removeClass('disabled no_hover');
			$(this).closest('td').attr('title', '');
			$('table.import tr.header td:nth-child('+index+')').attr('title', '');
		}
		else
		{
			$(this).prop('checked', false);
			$('table.import tr td:nth-child('+index+')').addClass('disabled no_hover');
			$(this).closest('td').attr('title', eil_column_wont_imported);
			$('table.import tr.header td:nth-child('+index+')').attr('title', eil_column_wont_imported);
			$('table.import tr.header td:nth-child('+index+') select option').attr('selected', false);
		}
	});
}

var eil_typeHandler = function(key, element){
	if ( key )
    {
        $('select[name='+element+'] option:first').text(lang['loading']);
        $.post(rlConfig["ajax_url"],{ item: 'eil_fetchOptions', key: key, element: element },
        function(response){
            if(response.status == 'ok') {
                if(response.html.category) {
                    $('select[name=' + key + ']').html(response.html.category);
                }
                if(response.html.form) {
                    $("#export_table").html(response.html.form);
                }
                if(response.js) {
                    eval(response.js);
                }
            }
        }, 'json')
    }
    else
    {
        var option = '<option value="">'+eil_select_listing_type+'</option>';
        $('select[name='+element+']').html(option);
    }
}

var importExportClass = function(){
	var self = this;
	var item_width = width = percent = percent_value = 0;
	var popup = false;
	var request;

	this.phrases = new Array();
	this.config = new Array();

	this.import = function(index){
		// Show popup
		if (index == 0) {
			if (!popup) {
				popup = new Ext.Window({
					applyTo: 'statistic',
					layout: 'fit',
					width: 447,
					height: 120,
					closeAction: 'hide',
					plain: true
			    });

			    popup.addListener('hide', function(){
	            	self.stop();
	            });
			}

			popup.show();
		}

	    // Import request
	    request = $.getJSON("../plugins/export_import/admin/import.php", {index: index}, function(response){
			index = response['from'];
			var percent = Math.ceil((response['from'] * 100) / response['count']);
			percent = percent > 100 ? 100 : percent;

			$('#processing').css('width', percent+'%');
			$('#loading_percent').html(percent+'%');

			if (response['count'] > index) {
				var from = response['from'] + 1;
				var to = response['to'] + 1;
				to = response['count'] < to ? response['count'] : to;
				var import_current = from+'-'+to;
				$('#importing').html(import_current);

				self.import(index);
			} else {
				$('#import_start_nav').slideUp();
				printMessage('notice', self.phrases['completed'].replace('{count}', response['count']));
				setTimeout(function(){
					popup.hide();
                    listingsGrid.reload();
                    $('#imported-listings').removeClass('hide');
				}, 2000);
			}
		});
	}

	this.stop = function(){
		import_in_progress = false;
		request.abort();
	}

	this.start = function(){
		import_in_progress = true;
		self.import(0);
	}
};

var importExport = new importExportClass();
