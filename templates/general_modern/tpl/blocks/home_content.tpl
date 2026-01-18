<!-- home page content tpl -->

<section class="home-content">
    <div class="point1 clearfix">
        {if $config.home_page_h1}
            <h1>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
        {/if}
        
        <div class="home-content-bg">
            <div class="row">
                <div class="col-lg-8" id="home_content_image_container">
                    <img alt="{$config.site_name}" src="{$rlTplBase}img/blank_3x1.gif" />
                </div>
                <div class="col-lg-4" id="home_content_search_form">
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'side_bar_search.tpl'}
                </div>
            </div>
        </div>
    </div>
</section>

<!-- home page content tpl end -->
