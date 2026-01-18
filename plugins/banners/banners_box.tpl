<!-- banners box ({$block.Key}|{$block.Side}) -->

<div class="banners-box {if $boxBetweenCategories}item{/if}">
{if $banners}
    {if $info.slider && $banners|@count > 1}
        {assign var='bannerBoxWithFadeEffect' value=true}
    {/if}

    {if $bannerBoxWithFadeEffect}
    <div class="banners-slide-show" style="max-width:{$info.width}px; height:{$info.height}px; overflow: hidden; margin:auto;">
    {/if}

    {foreach from=$banners item='banner' name='bannerF'}
        {if $banner.Type == 'image'}
        <div class="banner" id="banner_{$banner.ID}" onclick="bannerClick({$banner.ID}, this);" style="margin:auto; max-width:{$info.width}px; height:{$info.height}px;">
            {if $banner.Link}<a {if $banner.externalLink}target="_blank"{/if} {if !$banner.Follow}rel="nofollow"{/if} href="{$banner.Link}">{/if}
                {assign var='banner_src' value=$smarty.const.RL_FILES_URL|cat:$info.folder|cat:$banner.Image}
                <img alt="{$banner.name}" title="{$banner.name}" src="{$banner_src}" data-thumb="{$banner_src}" style="width: 100%;" />
            {if $banner.Link}</a>{/if}
        </div>
        {elseif $banner.Type == 'html'}
        <div class="banner banners-type-html-js" id="banner_{$banner.ID}" style="{if $banner.Responsive}overflow:visible;{else}margin:auto; max-width:{$info.width}px; height:{$info.height}px;{/if}">
            {$banner.Html}
        </div>
        {/if}
    {/foreach}

    {if $bannerBoxWithFadeEffect}
    </div>
    {/if}
{else}
    <div class="banner" style="{if $boxBetweenCategories}display:inline;{/if}margin:auto; max-width:{$info.width}px; height:{$info.height}px;">
        <div dir="ltr" class="banner-space d-flex h-100 w-100 justify-content-center align-items-center">{$info.width} x {$info.height}</div>
    </div>
{/if}
</div>

<!-- banners box end -->
