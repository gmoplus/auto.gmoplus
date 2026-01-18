
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.2
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
 *	Copyrights Flynax Classifieds Software | 2024
 *	https://www.flynax.com
 *
 ******************************************************************************/

$(document).ready(function(){
    $('input[name=site_accounts]').click(function(){
        $('input.accounts').prop('checked', $(this).is(':checked'));
    });
    $('input.accounts').click(function(){
        $('input[name=site_accounts]').prop('checked', $('input.accounts:checked').length > 0 ? true : false);
    });

    $('input#start_send').click(function(){
        if ($total_res > 0) {
            flynax.confirm(massmailer.phrases['send_confirm'], massmailer.start, null);
        } else {
            printMessage('error', massmailer.phrases['massmailer_newsletter_sent_email_zero']);
        }
    });
});

var massmailerClass = function(){
    var self = this;
    var item_width = width = percent = percent_value = 0;
    var window = false;
    var request;

    this.phrases = new Array();
    this.config = new Array();

    this.send = function(id, index, sel_emails){
        /* show window */
        if (index == 0) {
            if (!window) {
                window = new Ext.Window({
                    applyTo: 'statistic',
                    layout: 'fit',
                    width: 600,
                    height: 320,
                    closeAction: 'hide',
                    plain: true
                });

                window.addListener('hide', function(){
                    self.stop();
                });
            }

            window.show();
        }
        /* send request */
        request = $.post(
            "../plugins/massmailer_newsletter/admin/send.php",
            {id: id, index: index, selected_emails: sel_emails},
            function(response){
                response = jQuery.parseJSON(response);
                if (response['count'] <= 20) {
                    var multiplier = 1;
                }
                else if (response['count'] > 20 && response['count'] <= 500) {
                    var multiplier = 10;
                }
                else if (response['count'] > 500) {
                    var multiplier = 100;
                }

                if (index == 0) {
                    item_width = 562/response['count'];
                    percent_value = 100/response['count'];
                }
                index++;

                width += item_width;
                percent = response['count'] == index ? 100 : percent + percent_value*multiplier;

                var emails       = '';
                var show_percent = percent.toFixed(2);

                $('.x-window-body').css('height', 'auto');
                $('#total').html(response['count']);
                $('#sent').html(index*multiplier);
                $('#processing').css('width', show_percent+'%');
                $('#loading_percent').html(show_percent+'%');

                for (var i=0; i<response['data'].length; i++) {
                    if (response['data'][i]['Mail']) {
                        emails += response['data'][i]['Mail'];

                        if (i != multiplier) {
                            emails += ', ';
                        }
                    }
                }

                $('#sending > span').html(emails.substr(emails, emails.length-2));

                if (response['count'] > index * multiplier) {
                    self.send(id, index, sel_emails);
                } else {
                    printMessage('notice', self.phrases['completed'].replace('{count}', response['count']));
                    setTimeout(function(){
                        window.hide();
                        self.clear();
                    }, 4000);
                }
            }
        );
    }

    this.stop = function(){
        request.abort();
    }

    this.start = function(){
        var allValEmails = new Array();
        $("div.emails input[type='checkbox']:checked").each(function() {
            allValEmails.push($(this).val());
        });
        if (allValEmails.length > 0) {
            self.send(self.config['id'], 0, allValEmails);
        }
        else {
            this.stop();
            printMessage('error', self.phrases['empty_emails']);
        }
    }

    this.clear = function(){
        item_width = width = percent = percent_value = 0;
        $('#total').html(0);
        $('#sent').html(0);
        $('#processing').css('width', '0px');
        $('#loading_percent').html('0%');
        $('#sending').html('-');
    }
}

var massmailer = new massmailerClass();
