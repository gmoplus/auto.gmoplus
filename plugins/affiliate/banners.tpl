<!-- Affiliate Banners page tpl -->

{if $isLogin}
    {if $banners}
        <div class="aff-banners list-table content-padding">
            <div class="header">
                <div class="center" style="width:40px">#</div>
                <div>{$lang.aff_banner_details}</div>
            </div>

            {foreach from=$banners item='banner' name='bannersAff'}
                {math assign='iteration' equation='(((current?current:1)-1)*per_page)+iter' iter=$smarty.foreach.bannersAff.iteration current=$pInfo.current per_page=$config.aff_items_per_page}
                <div class="row">
                    <div class="center iteration no-flex">{$iteration}</div>
                    <div class="no-flex default">
                        {if $banner.Name}
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.aff_banner_name}</div>
                                <div class="value">{$banner.Name}</div>
                            </div>
                        {/if}

                        <div class="table-cell clearfix small">
                            <div class="name">{$lang.aff_banner_size}</div>
                            <div class="value">{$banner.Width}x{$banner.Height} {$lang.aff_banner_size_px}</div>
                        </div>

                        <div class="table-cell clearfix small image-code">
                            <div class="name">{$lang.aff_banner_image}</div>
                            <div class="value">
                                <div class="aff-banner">
                                    <img class="thumbnail" alt="{$banner.Name}" src="{$banner.Image_URL}" />
                                </div>
                                <textarea rows="4" cols=""><a href="{$banner.Affiliate_URL}" title="{$banner.Name}" target="_blank"><img width="{$banner.Width}" height="{$banner.Height}" src="{$banner.Image_URL}" alt="{$banner.Name}" /></a></textarea>
                                {$lang.aff_banner_code}
                            </div>
                        </div>
                    </div>
                </div>
            {/foreach}
        </div>

        <!-- paging block -->
        {paging calc=$pInfo.calc total=$banners|@count current=$pInfo.current per_page=$config.aff_items_per_page}
        <!-- paging block end -->
    {else}
        {$lang.aff_banners_not_exist}
    {/if}
{/if}

<!-- Affiliate Banners  page tpl end -->
