<!-- smart banner -->

<div class="smart_banner d-flex d-lg-none">
    <div class="d-flex w-100">
        <span class="flex-shrink-0 sb-close mr-3 text-center">×</span>
        <div class="flex-shrink-0 sb-icon mr-2" style="background-image: url('{$banner_info.url}');"></div>
        <div class="flex-fill pr-2">
            <span class="sb-title">{$banner_info.name}</span>
            <div class="mt-2">
                <a class="button sb-button low" href="market://details?id={$config.android_smart_banner}">
                    {$lang.android_view}
                </a>
            </div>
        </div>
    </div>
</div>

<script class="fl-js-dynamic">
{literal}
    $(function(){
        var $smartBanner = $('.smart_banner');

        $smartBanner.on('click', '.sb-close, .sb-button',function(){
            $smartBanner.remove();
            createCookie('smart_banner', true, 30);
        });
    });
{/literal}
</script>

<!-- smart banner end -->
