
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

$(function () {
    if (cpConfigs.showCookieNotice === true) {
        $('div#cookies_policy_big_form').fadeIn('normal');
        $('div.cookies_policy_icon').before('<div class="cookies_absolute_div hide" id="modal_mask" ></div>');
        $('div.cookies_absolute_div').fadeIn('normal');
        $('html, body').addClass('CP_lock_screen');
    }

    $('.cookies_policy_icon').click(function () {
        $('div#cookies_policy_big_form').fadeIn('normal');
        $('div.cookies_policy_icon').before('<div class="cookies_absolute_div hide" id="modal_mask" ></div>');
        $('div.cookies_absolute_div').fadeIn('normal');
        $('html, body').addClass('CP_lock_screen');

        if (cpConfigs.showCookieNotice === false) {
            $('div.cookies_absolute_div').click(function () {
                $(this).fadeOut('normal');
                $(this).remove();
                $('div#cookies_policy_big_form').fadeOut('normal');
                $('html, body').removeClass('CP_lock_screen');
            })
        }
    });

    enquire.register("screen and (max-width: 767px)", {
        match: function () {
            $(window).resize(function () {
                var box_height    = $('div#cookies_policy_big_form').height() / 2;
                var move_from_top = $(window).height() / 2 - box_height;

                $('div#cookies_policy_big_form').css({'top': move_from_top, 'bottom': 'auto'});
            })
        }
    }).register("screen and (min-width: 768px)", {
        match: function () {
            $('div#cookies_policy_big_form').css({'top': 'auto', 'bottom': '15px'});
        }
    });
});

$('input.cookie_accept').click(function () {
    createCookie('cookies_policy', true, 365);

    $('div.cookies_absolute_div').fadeOut('normal', function () {
        $(this).remove();
    });

    if (cpConfigs.removeCookieBox) {
        $('div#cookies_policy_big_form').fadeOut('normal', function () {
            $(this).remove();
            $('div.cookies_policy_icon').remove();
        });
    } else {
        $('div#cookies_policy_big_form').fadeOut('normal', function () {
            $(this).find('input.cookie_accept').remove();
        });
    }

    cpConfigs.showCookieNotice = false;

    if (rlPageInfo.controller === 'add_listing' || cpConfigs.blockAllCookies) {
        document.location.reload(true);
    } else {
        $('html, body').removeClass('CP_lock_screen');
    }
});

$('input.cookie_decline').click(function(){
    document.cookie.split('; ').forEach(function(name) {
        eraseCookie(name.split('=')[0]);
    });

    window.location.href = cpConfigs.redirectUrl;
});

if (cpConfigs.showCookieNotice === false){
    $('div.cookies_absolute_div').click(function(){
        $(this).fadeOut('normal');
        $(this).remove();
        $('div#cookies_policy_big_form').fadeOut('normal');
    });
}
