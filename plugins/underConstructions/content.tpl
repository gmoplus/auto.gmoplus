<!DOCTYPE html>
<html xmlns="http://www.w3.org/1999/xhtml" lang="{$smarty.const.RL_LANG_CODE|lower}">
<head>
<title>{$config.under_constructions_meta_title}</title>
<meta name="viewport" content="width=device-width, user-scalable=no, initial-scale=1, maximum-scale=1" />
<meta name="generator" content="Flynax Classifieds Software" />
<meta charset="UTF-8" />
<meta name="description" content="{$config.under_constructions_meta_description}" />
<meta name="Keywords" content="{$config.under_constructions_meta_keywords}" />
<link href="{$smarty.const.RL_URL_HOME}plugins/underConstructions/static/style.css" type="text/css" rel="stylesheet" />
<style>
{literal}
@media only screen and (-webkit-min-device-pixel-ratio: 1.5),
only screen and (min--moz-device-pixel-ratio: 1.5),
only screen and (min-device-pixel-ratio: 1.5),
only screen and (min-resolution: 144dpi) {
    #logo img {
        background-image: url({/literal}{$rlTplBase}{literal}img/@2x/logo.png);
    }
}
{/literal}
</style>
<link rel="shortcut icon" href="{$rlTplBase}img/favicon.ico" />

{$ajaxJavascripts}
</head>
<body{if $smarty.const.RL_LANG_DIR == 'rtl'} dir="rtl"{/if}>

<div id="main-bg">
    <div id="container">
        <div id="logo"><img src="{$rlTplBase}img/logo.png" /></div>
        <div id="box">
            <h1>{$lang.under_constructions_h1}</h1>
            <h2>{$lang.under_constructions_h2}</h2>

            <section id="date">
                <div id="chart">
                    <div id="numbers">
                        <div id="time_day"></div>
                        <div id="time_hour"></div>
                        <div id="time_minute"></div>
                        <div id="time_sec"></div>
                    </div>
                    <div id="labels">
                        <div>{$lang.under_constructions_days}</div>
                        <div>{$lang.under_constructions_hours}</div>
                        <div>{$lang.under_constructions_minutes}</div>
                        <div>{$lang.under_constructions_seconds}</div>
                    </div>
                </div>
            </section>

            {if $plugins.massmailer_newsletter}
            <section id="subscribe">
                <form method="post" id=nl_subscribe>
                    <input type="text" id="email" placeholder="{$lang.massmailer_newsletter_your_e_mail}" />
                    <input id="button" type="button" value="{$lang.massmailer_newsletter_subscribe}" data-default-val="{$lang.massmailer_newsletter_subscribe}" />
                </form>
                <div id="notice" class="{if $errors}error{else}notice{/if}">
                    {if $errors}
                        {foreach from=$errors item='error'}
                            {$error}
                        {/foreach}
                    {elseif $pNotice}
                        {$pNotice}
                    {elseif $pAlert}
                        {$pAlert}
                    {/if}
                </div>
            </section>
            {/if}
        </div>
    </div>
</div>

<script src="{$smarty.const.RL_URL_HOME}libs/jquery/jquery.js"></script>
<script>
var ajax_url = '{$smarty.const.RL_URL_HOME}request.ajax.php';
var legacy_version = {if $legacy_version}true{else}false{/if};
var current_date = new Array();
var redirect_url = '{$smarty.const.RL_URL_HOME}';
var langCode = '{$smarty.const.RL_LANG_CODE}'
current_date['day'] = {$smarty.now|date_format:'%d'};
current_date['month'] = {$smarty.now|date_format:'%m'};
current_date['year'] = {$smarty.now|date_format:'%Y'};
current_date['hours'] = parseInt('{$smarty.now|date_format:'%H'}');
current_date['minutes'] = parseInt('{$smarty.now|date_format:'%M'}');
current_date['seconds'] = parseInt('{$smarty.now|date_format:'%S'}');

var lang = [];
lang['loading'] = "{$lang.loading}";
lang['no_response_from_server'] = '{$lang.massmailer_newsletter_no_response}';
lang['notice_bad_email'] = '{$lang.notice_bad_email}';
lang['massmailer_newsletter_guest'] = '{if $lang.massmailer_newsletter_guest}{$lang.massmailer_newsletter_guest}{else}Guest{/if}';

var curTime = 0;

{literal}

$(function(){
    // Set current date
    var cDate = new Date();
    cDate.setDate(current_date['day']);
    cDate.setMonth(current_date['month']-1);
    cDate.setFullYear(current_date['year']);
    cDate.setHours(current_date['hours']);
    cDate.setMinutes(current_date['minutes']);
    cDate.setSeconds(current_date['seconds']);

    var curSeconds = cDate.getTime();

    {/literal}
    // Set target date
    var tDate = new Date();
    tDate.setDate({$date|date_format:'%d'});
    tDate.setMonth({$date|date_format:'%m'} - 1);
    tDate.setFullYear({$date|date_format:'%Y'});
    tDate.setHours(parseInt('{$date|date_format:'%H'}'));
    tDate.setMinutes(parseInt('{$date|date_format:'%M'}'));
    tDate.setSeconds(parseInt('{$date|date_format:'%S'}'));
    {literal}

    var targetSeconds = tDate.getTime();
    curTime = (targetSeconds - curSeconds)/1000;

    printDate();

    var $button = $('input[type=button]');
    var $email = $('#email');
    var $notice = $('#notice');

    $('form').submit(function(){
        if (!$email.val()) {
            $notice
                .html(lang['notice_bad_email'])
                .attr('class', 'error');

            return false;
        }

        $button.val(lang['loading']);
        // Xajax call
        if (legacy_version) {
            xajax_subscribe('subscribe', lang['massmailer_newsletter_guest'], $('#email').val());
        }
        // Ajax call
        else {
            var name = $email.val().split('@')[0];
            var data = {
                mode: 'newsletterSubscribe',
                lang: langCode,
                name: (name && name.length > 2) ? name : lang['massmailer_newsletter_guest'],
                email: $email.val()
            };
            $.getJSON(ajax_url, data, function(response) {
                if (response) {
                    if (response.status == 'OK' || response.status == 'WARNING') {
                        $email.val('');
                        
                        if (response.status == 'OK') {
                            printMessage('notice', response.data.content);
                        } else {
                            printMessage('warning', response.data.content);
                        }
                    } else {
                        printMessage('error', response.data.message);
                    }
                } else {
                    printMessage('error', lang['no_response_from_server']);
                }

                $button.val($button.data('default-val')).removeAttr('disabled');
            }).fail(function(){
                printMessage('warning', lang['no_response_from_server']);
                $button.val($button.data('default-val')).removeAttr('disabled');
            });
        }
        return false;
    });

    $button
        .width($button.width())
        .click(function(){
            $('form').submit();
        });

    $email.focus(function(){
        $notice
            .text('')
            .attr('class', 'notice');
    });
});

var printDate = function(){
    var days = Math.floor(curTime/3600/24);
    var hours = Math.floor((curTime-(days*3600*24))/3600);

    var minutes = Math.floor((curTime-(days*3600*24)-(hours*3600))/60);
    var seconds = Math.floor((curTime-(days*3600*24)-(hours*3600))-(minutes*60));

    if (days < 0) {
        location.href = redirect_url;
        return;
    }

    days = days < 10 ? '0'+days: days;
    hours = hours < 10 ? '0'+hours: hours;
    minutes = minutes < 10 ? '0'+minutes: minutes;
    seconds = seconds < 10 ? '0'+seconds: seconds;

    var outTime = days+':' +hours+':'+minutes+':'+seconds;
    $('#time_obj').html(outTime);
    $('#time_day').html(days);
    $('#time_hour').html(hours);
    $('#time_minute').html(minutes);
    $('#time_sec').html(seconds);

    curTime--;
    setTimeout('printDate()', 1000);
}

/**
* Notices/errors handler
*
* @param string type - message type: error, notice, warning
* @param string/array message - message text
*
**/
var printMessage = function(type, message){
    if (!message || !type)
        return;

    $('#notice')
        .html(message)
        .attr('class', type);
};

{/literal}
</script>

</body>
</html>
