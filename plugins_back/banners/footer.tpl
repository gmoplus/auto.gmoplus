<!-- banners/footer.tpl -->

{if $isBannersExistOnPage}
    <script>{literal}
        function bannerClick(id, obj) {
            $.post(rlConfig.ajax_url, {
                'mode': 'bannersBannerClick',
                'item': id
            });
            $(obj).attr('onclick', null)
        }
    {/literal}</script>
{/if}

{if $pageInfo.Controller === 'my_banners'}
    <script>{literal}
        function deleteBanner(id)
        {
            $.post(rlConfig.ajax_url, {
                'mode': 'bannersDeleteBanner',
                'page': {/literal}{if $smarty.get.pg}{$smarty.get.pg}{else}1{/if}{literal},
                'item': id
            }, function(response) {
                if (typeof response.status === 'undefined') {
                    printMessage('error', lang.error);
                } else if (response.status === 'OK') {
                    $('article#banner_' + id).remove();
                    printMessage('notice', response.message);

                    if (response.count === 0) {
                        $('section#controller_area').html(response.html);
                    } else if (typeof response.redirect !== 'undefined') {
                        location.href = response.redirect;
                    }
                } else if (response.status === 'ERROR') {
                    printMessage('error', response.message);
                }
            }, 'json');
        }
    {/literal}</script>
{/if}

<script>{literal}
    var bannersSlideShow = function() {
        flUtil.loadScript(rlConfig.plugins_url + 'banners/static/jquery.cycle.js', function () {
            var slideShowSel = 'div.banners-slide-show';

            if ($(slideShowSel).length) {
                $(slideShowSel).cycle({
                    fx: 'fade' // choose your transition type, ex: fade, scrollUp, shuffle, etc...
                });
            }
        });
    };

    var callScriptInHtmlBanners = function() {
        var $htmlBanners = $('div.banners-type-html-js');
        var evalAndRemoveScript = function () {
            $htmlBanners.find('script').each(function() {
                eval($(this).text());
                $(this).remove();
            });
        };

        if ($htmlBanners.find('script[src$="adsbygoogle.js"]').length) {
            flUtil.loadScript('https://pagead2.googlesyndication.com/pagead/js/adsbygoogle.js', function () {
                evalAndRemoveScript();
            });
        } else {
            evalAndRemoveScript();
        }
    };

    $(document).ready(function () {
        bannersSlideShow();
    });
{/literal}</script>

<style>{strip}
    {if $isBannersExistOnPage}{literal}
    div.banner {
        overflow: hidden;
        display: inline-block;
    }
    div.banners-box-between-categories {
        height: 100px;
        background-color: #666666;
    }
    div.banners-box {
        text-align: center;
    }
    div.banners-box > div.banner:not(:last-child) {
        margin-bottom: 5px;
    }
    div.banners-box > div.banner {
        width:100%;
    }
    .form-buttons a:not(.button):before {
        content: '' !important;
    }
    {/literal}{/if}

    {if $pageInfo.Key === 'my_banners'}{literal}
    @media screen and (max-width: 767px) {
        section#listings.my-banners .item div.info .title {
            position: relative;
            box-shadow: none;
        }
        section#listings.my-banners .item div.info {
            width: 100% !important;
        }
    }
    {/literal}{/if}
{/strip}</style>

<!-- banners/footer.tpl end -->
