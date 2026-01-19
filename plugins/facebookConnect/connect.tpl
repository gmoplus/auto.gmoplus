{*
<!-- Facebook connect block -->

<div id="fb-root"></div>

<style>
{literal}

.fb-connect {
    {/literal}
    padding-{if $smarty.const.RL_LANG_DIR == 'ltr'}left{else}right{/if}: 8px !important;
    text-align: center;
    {literal}
}
.fb-connect:before {
    display: none;
}
.fb-connect svg {
    {/literal}
    width: 22px;
    height: 22px;
    margin-bottom: 1px;
    vertical-align: middle;
    margin-{if $smarty.const.RL_LANG_DIR == 'ltr'}right{else}left{/if}: 10px;
    {literal}
}

{/literal}
</style>

<script type="text/javascript">
//<![CDATA[
var responsive_template = {if $tpl_settings.type == 'responsive_42'}true{else}false{/if};
var fbConnectLang = '{$lang.fConnect_connect}';
var fbSvgIcon = '<svg xmlns="http://www.w3.org/2000/svg"><path d="M22 10.957C22 4.905 17.075 0 11 0S0 4.905 0 10.957c0 5.468 4.023 10.001 9.281 10.823v-7.656H6.488v-3.167h2.793V8.543c0-2.746 1.642-4.263 4.155-4.263 1.204 0 2.462.214 2.462.214V7.19h-1.387c-1.366 0-1.792.845-1.792 1.711v2.056h3.05l-.487 3.167h-2.563v7.656C17.977 20.958 22 16.425 22 10.957" fill="#FFFFFE" fill-rule="nonzero"/></svg>';
{literal}

window.fbAsyncInit = function() {
    FB.init({
        appId: '{/literal}{$config.facebookConnect_appid}{literal}', // App ID from the app dashboard
        channelUrl : '{/literal}{$config.bookmarks_fb_box_url}{literal}', // Channel file for x-domain comms
        cookie: true, // Check Facebook Login status
        xfbml: true, // Look for social plugins on the page
        version: 'v2.2'
    });

    // bookmarks
    if ($('#fl-facebook-funs').length > 0) {
        FB.Event.subscribe('xfbml.render', function(response) {
            var width = $('#fl-facebook-funs').width();
            $('.fb_iframe_widget iframe, .fb_iframe_widget > span').width(width);
        });
    }
};

if (!document.getElementById('fb-nav-bar')) {
    var fcDOM = '<img style="cursor:pointer;" alt="" title="{/literal}{$lang.fConnect_login_title}{literal}" src="{/literal}{$smarty.const.RL_PLUGINS_URL}{literal}facebookConnect/static/fb_login.png" onclick="fcLogin();" />';
    $('input[value=login]:first').parent().find('input[type=submit]').after(fcDOM);
}
else {
    // move FB icon
    if ( $('form[name="userbar_login"]').length ) {
        $('input[name=username]').before($('img[onclick="fcLogin();"]'));
        $('img[onclick="fcLogin();"]').css('margin-right', '5px');
    }
}

if (responsive_template && $('img#fb-nav-bar').length) {
    $('img#fb-nav-bar').after('<a class="fb-connect" href="javascript:fcLogin()">' + fbConnectLang + '</a>');
    $('a.fb-connect').prepend(fbSvgIcon);
    $('img#fb-nav-bar').remove();
}

function fcLogin() {
    var seoUrl = rlConfig['libs_url'].replace('libs', 'plugins');
    window.location.href = seoUrl + 'facebookConnect/request.php';
}

(function(d) {
    var js, id = 'facebook-jssdk'; if (d.getElementById(id)) {return;}
    js = d.createElement('script'); js.id = id; js.async = true;
    js.src = "//connect.facebook.net/en_US/all.js";
    d.getElementsByTagName('head')[0].appendChild(js);
}(document));

{/literal}
//]]>
</script>

<!-- Facebook connect block end -->
*}
