
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : MAILLER.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

function newsletterAction($button, $email, $name, guestMode) {
   $button.off('click').click(function() {
        $button.val(lang['loading']).attr('disabled', 'true');
        var data = {
            mode: 'newsletterSubscribe',
            name: guestMode ? lang['massmailer_newsletter_guest']: $name.val(),
            email: $email.val(),
            lang: rlLang
        };

        $.getJSON(rlConfig['ajax_url'], data, function(response) {
            if (response) {
                if (response.status === 'OK' || response.status === 'WARNING') {
                    guestMode ? '' : $name.val('');
                    $email.val('');
                    $button.val($button.data('default-val')).removeAttr('disabled');
                    if (response.status === 'OK') {
                        printMessage('notice', response.data.content);
                    } else {
                        printMessage('warning', response.data.content);
                    }
                } else {
                    $button.val($button.data('default-val')).removeAttr('disabled');
                    printMessage('error', response.data.message);
                }
            } else {
                $button.val($button.data('default-val')).removeAttr('disabled');
                printMessage('warning', lang['massmailer_newsletter_no_response']);
            }
        }).fail(function() {
            $button.val($button.data('default-val')).removeAttr('disabled');
            printMessage('warning', lang['massmailer_newsletter_no_response']);
        });
    });
};
