<!-- banner item -->

{assign var='bannerImageSize' value=$smarty.const.RL_FILES|cat:'banners/'|cat:$mBanner.Image|@getimagesize}
{assign var='bannerImageWidth' value=$bannerImageSize.0}
{assign var='bannerMinImageWidth' value=260}

<article class="item banner-item" id="banner_{$mBanner.ID}">{strip}
    <div class="title">{$mBanner.name}{if $mBanner.Type == 'html'} {$lang.banners_bannerType_html}{/if}</div>

    {if $mBanner.Type == 'image' && $mBanner.Image && $bannerImageWidth > $bannerMinImageWidth}
        <img class="banner-horizontal" alt="" title="{$mBanner.name}" src="{$smarty.const.RL_FILES_URL}banners/{$mBanner.Image}" />
    {/if}

    <div class="nav">
        {if $mBanner.Type == 'image' && $mBanner.Image && $bannerImageWidth <= $bannerMinImageWidth}
        <div class="info">
            <img class="banner-standard" alt="" title="{$mBanner.name}" src="{$smarty.const.RL_FILES_URL}banners/{$mBanner.Image}" />
        </div>
        {/if}
        <div class="navigation">
            <ul>
                <li class="nav-icon">
                    <a class="edit" href="{$rlBase}{if $config.mod_rewrite}{$pages.banners_edit_banner}.html?id={$mBanner.ID}{else}?page={$pages.banners_edit_banner}&id={$mBanner.ID}{/if}">
                        <span>{$lang.banners_editBanner}</span>
                    </a>
                </li>
                {if $mBanner.Status != 'expired' && $mBanner.Status != 'incomplete'}
                <li class="nav-icon">
                    <a class="renew" title="{$lang.banners_renewPlan}" href="{$rlBase}{if $config.mod_rewrite}{$pages.banners_renew}.html?id={$mBanner.ID}{else}?page={$pages.banners_renew}&id={$mBanner.ID}{/if}">
                        <span>{$lang.banners_renewPlan}</span>
                    </a>
                </li>
                {/if}
                <li class="nav-icon">
                    <a class="delete" id="delete_banner_{$mBanner.ID}" href="javascript://" title="{$lang.delete}"><span>{$lang.delete}</span></a>
                </li>
            </ul>
        </div>
        <div class="stat">
            <ul>
                <li>
                    <div class="statuses">
                        {if $mBanner.Status == 'incomplete'}
                            <a href="{$rlBase}{if $config.mod_rewrite}{$pageInfo.Path}.html?incomplete={$mBanner.ID}&step={$mBanner.Last_step}{else}?page={$pageInfo.Path}&incomplete={$mBanner.ID}&step={$mBanner.Last_step}{/if}" class="{$mBanner.Status}">
                                {$lang[$mBanner.Status]}
                            </a>
                        {elseif $mBanner.Status == 'expired'}
                            <a href="{$rlBase}{if $config.mod_rewrite}{$pages.banners_renew}.html?id={$mBanner.ID}{else}?page={$pages.banners_renew}&id={$mBanner.ID}{/if}" title="{$lang.banners_renewPlan}" class="{$mBanner.Status}">
                                {$lang[$mBanner.Status]}
                            </a>
                        {else}
                            <span {if $mBanner.Status == 'pending'}title="{$lang.banners_waitingApproval}"{/if} class="{$mBanner.Status}">{$lang[$mBanner.Status]}</span>
                        {/if}
                    </div>
                </li>

                {if $mBanner.Date_to && $mBanner.Plan_type == 'period'}
                    <li>
                        <span class="name">{$lang.active_till}</span> {$mBanner.Date_to|date_format:$smarty.const.RL_DATE_FORMAT}
                    </li>
                {elseif $mBanner.Date_to && $mBanner.Plan_type == 'views'}
                    <li>
                        <span class="name">{$lang.banners_showsLeft}</span> {math equation="x - y" x=$mBanner.Date_to y=$mBanner.Shows}
                    </li>
                {/if}

                {if $mBanner.Key}
                    <li>
                        <span class="name">{$lang.plan}</span> {assign var='planName' value='banner_plans+name+'|cat:$mBanner.Key}{$lang.$planName}
                    </li>
                {/if}

                <li>
                    <span class="name">{$lang.banners_bannerShows}</span> {$mBanner.Shows}
                </li>

                {if $mBanner.Type == 'image'}
                    <li>
                        <span class="name">{$lang.banners_bannerClicks} </span>{if $mBanner.clicks}{$mBanner.clicks}{else}0{/if}
                    </li>
                {/if}
            </ul>
        </div>
    </div>
{/strip}</article>

<!-- banner item end -->
