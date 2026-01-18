
/******************************************************************************
 *  
 *  PROJECT: Flynax Classifieds Software
 *  VERSION: 4.9.3
 *  LICENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *  PRODUCT: General Classifieds
 *  DOMAIN: gmowin.com
 *  FILE: MASSMAILER_NEWSLETTER_SEND.PHP
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
