<!-- page content -->
{assign var='featured_gallary' value=false}

<div id="wrapper" class="flex-fill">
    <section id="main_container">

        <!-- Kwd search and category alphabet start -->
        <section class="alphabetic-search-keywords-search">
            <div class="inside-container point1">
                <div class="row category-alphabet-container">
                    {assign var='spage_key' value=$listing_types.listings.Page_key}
                    <form class="keyword-search col-lg-3" method="post"
                          action="{$rlBase}{if $config.mod_rewrite}{$pages.search}.html{else}?page={$pages.search}{/if}">
                        <input type="hidden" name="form" value="keyword_search"/>
                        <input type="text" maxlength="255" name="f[keyword_search]" placeholder="{$lang.keyword_search}"
                               id="autocomplete"
                               {if $smarty.post.f.keyword_search}value="{$smarty.post.f.keyword_search}"{/if}
                               autocomplete="off"/>
                    </form>
                    <script class="fl-js-dynamic">
                        var header_ks_default = "{$lang.keyword_search}";
                        var view_details = '{$lang.view_details}';
                        var join_date = '{$lang.join_date}';
                        var category_phrase = '{$lang.category}';
                        {literal}
                        $(document).ready(function () {
                            $autocomplete = $('input#autocomplete');
                            $autocomplete.vsAutoComplete();
                            $autocomplete.focus(function () {
                                if ($(this).val() == header_ks_default) {
                                    $(this).val('');
                                }
                            }).blur(function () {
                                if ($(this).val() == '') {
                                    $(this).val(header_ks_default);
                                }
                            });
                        });
                        {/literal}
                    </script>
                    <div class="col-lg-9 category-alphabet">
                        {assign var='alphabet_array' value=','|explode:$lang.alphabet_characters}
                        {assign var='reduce' value=0}
                        {if 'All'|in_array:$alphabet_array}
                            {assign var='reduce' value=1}
                        {/if}

                        <ul class="alphabet-list">
                            {foreach from=','|explode:$lang.alphabet_characters item='character'}
                                {if $character|@mb_strlen:'utf-8' == 1}
                                    <li><span>{$character}</span></li>
                                {/if}
                            {/foreach}
                            <li><span>#</span></li>
                        </ul>
                    </div>
                    <div class="col-lg-12">
                        <div class="hide" id="cat_alphabet_cont">
                            <div class="close" title="Close"></div>
                            <div class="loading hide">{$lang.loading}</div>
                        </div>
                    </div>
                </div>
            </div>
        </section>
        <!-- Kwd search and category alphabet end -->

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'bread_crumbs.tpl'}

        {if $pageInfo.Key == 'home'}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'home_content.tpl'}
        {/if}

        <div class="inside-container point1 clearfix">
            {rlHook name='tplAbovePageContent'}

            {if $pageInfo.Controller == 'home' && $config.home_page_h1}
                <h1>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
            {/if}

            <div class="row{if $pageInfo.Controller == 'listing_details'} flex-row-reverse{/if}">
                <!-- left blocks area on home page -->
                {if $side_bar_exists && ($blocks.left || $pageInfo.Controller == 'listing_details')}
                    <aside class="left col-lg-4">
                        {strip}
                            {if $pageInfo.Controller == 'listing_details'}{include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_sidebar.tpl'}{/if}

                            {foreach from=$blocks item='block'}
                                {if $block.Side == 'left'}
                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                {/if}
                            {/foreach}
                        {/strip}
                    </aside>
                {/if}
                <!-- left blocks area end -->

                <section id="content" class="col-lg-{if $side_bar_exists}8{else}12{/if}">
                    {if $pageInfo.Key != 'home' && $pageInfo.Key != 'search_on_map' && $pageInfo.Controller != 'listing_details' && !$no_h1}
                        <div class="h1-nav d-flex">
                            <h1 class="flex-fill{if ($pageInfo.Key == 'login' || $pageInfo.Login) && !$isLogin} text-center{/if}">{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>

                            {if is_array($navIcons)}
                                <nav id="content_nav_icons">
                                    {rlHook name='pageNavIcons'}

                                    {foreach from=$navIcons item='icon'}
                                        {$icon}
                                    {/foreach}
                                </nav>
                            {/if}
                        </div>
                    {/if}

                    <div id="system_message">
                        {if $errors || $pNotice || $pAlert}
                            <script class="fl-js-dynamic">
                                var fixed_message = {if $fixed_message}false{else}true{/if};
                                var message_text = '', error_fields = '';
                                var message_type = 'error';
                                {if isset($errors)}
                                    error_fields = {if $error_fields}'{$error_fields|escape:"javascript"}'{else}false{/if};
                                    message_text += '<ul>';
                                    {foreach from=$errors item='error'}message_text += '<li>{$error|regex_replace:"/[\r\t\n]/":"<br />"|escape:"javascript"}</li>';{/foreach}
                                    message_text += '</ul>';
                                {/if}
                                {if isset($pNotice)}
                                    message_text = '{$pNotice|escape:"javascript"}';
                                    message_type = 'notice';
                                {/if}
                                {if isset($pAlert)}
                                    var message_text = '{$pAlert|escape:"javascript"}';
                                    message_type = 'warning';
                                {/if}
                                {literal}
                                $(document).ready(function(){
                                    if (message_text) {
                                        printMessage(message_type, message_text, error_fields, fixed_message);
                                    }
                                });
                            {/literal}</script>
                        {/if}

                        <!-- no javascript mode -->
                        {if !$smarty.const.IS_BOT}
                            <noscript>
                                <div class="warning">
                                    <div class="inner">
                                        <div class="icon"></div>
                                        <div class="message">{$lang.no_javascript_warning}</div>
                                    </div>
                                </div>
                            </noscript>
                        {/if}
                        <!-- no javascript mode end -->
                    </div>

                    {if $pageInfo.Key != 'search_on_map'}
                        {if $blocks.top}
                            <!-- top blocks area -->
                            <aside class="top">
                                {foreach from=$blocks item='block'}
                                    {if $block.Side == 'top'}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                    {/if}
                                {/foreach}
                                <!-- top blocks area end -->
                            </aside>
                        {/if}
                    {/if}

                    <section id="controller_area">{strip}
                            {if $pageInfo.Page_type == 'system'}
                                {include file=$content}
                            {else}
                                <div class="content-padding">{$staticContent}</div>
                            {/if}
                        {/strip}</section>

                    {if $pageInfo.Key != 'search_on_map'}
                        <!-- middle blocks area -->
                        {if $blocks.middle}
                            <aside class="middle">
                                    {foreach from=$blocks item='block'}
                                        {if $block.Side == 'middle'}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                        {/if}
                                    {/foreach}
                            </aside>
                        {/if}
                        <!-- middle blocks area end -->

                        {if $blocks.middle_left || $blocks.middle_right}
                            <!-- middle blocks area -->
                            <aside class="row two-middle">
                                <div class="col-md-6 col-sm-12">
                                    <div>
                                        {foreach from=$blocks item='block'}
                                            {if $block.Side == 'middle_left'}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                            {/if}
                                        {/foreach}
                                    </div>
                                </div>

                                <div class="col-md-6 col-sm-12">
                                    <div>
                                        {foreach from=$blocks item='block'}
                                            {if $block.Side == 'middle_right'}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                            {/if}
                                        {/foreach}
                                    </div>
                                </div>
                            </aside>
                            <!-- middle blocks area end -->
                        {/if}

                        {if $blocks.bottom}
                            <!-- bottom blocks area -->
                            <aside class="bottom">
                                {foreach from=$blocks item='block'}
                                {if $block.Side == 'bottom'}
                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                {/if}
                                {/foreach}
                            </aside>
                            <!-- bottom blocks area end -->
                        {/if}
                    {/if}
                </section>
            </div>
        </div>
    </section>
</div>

{if $pageInfo.Key != 'search_on_map' && $config.header_banner_space && $tpl_settings.header_banner}
    <div class="header-banner-cont w-100 position-absolute pt-2 pb-2">
        <div class="point1 h-100 mx-auto justify-center">
            <div id="header-banner">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'header_banner.tpl'}
            </div>
        </div>
    </div>
{/if}

<!-- page content end -->
