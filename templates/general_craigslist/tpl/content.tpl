{if $pageInfo.Controller == 'listing_type' && $category.ID}
    {assign var='category_mode' value=true}
    {assign var='spage_path' value=$pages[$listing_type.Page_key]}
{elseif is_array($category_dropdown_types)}
    {assign var='cdt_count' value=$category_dropdown_types|@count}
    {if $cdt_count == 1}
        {assign var='spage_key' value=$listing_types[$category_dropdown_types.0].Page_key}
        {assign var='spage_path' value=$pages[$spage_key]}
    {elseif $cdt_count > 1}
        {assign var='spage_path' value=`$smarty.ldelim`type`$smarty.rdelim`}
    {/if}
{/if}

<div class="main-wrapper container">
    <div class="row">
        <div class="col-lg-{if $pageInfo.Controller == 'search_map'}12{else}9{/if}">
            <header class="page-header{if $pageInfo.Controller == 'search_map'} d-none{/if}">
                {strip}
                <div class="top-search{if $category_mode} in-category{/if}">
                    <form accesskey="{pageUrl key='search'}#keyword_tab" method="post" action="{if $category_mode}{$rlBase}{if $config.mod_rewrite}{$spage_path}/{$category.Path}/{$search_results_url}.html{else}?page={$spage_path}&id={$category.ID}&{$search_results_url}{/if}{else}{if $cdt_count == 0}{pageUrl key='search'}{else}{$rlBase}{if $config.mod_rewrite}{$spage_path}/{$search_results_url}.html{else}?page={$spage_path}&{$search_results_url}{/if}{/if}{/if}">
                        <input type="hidden" name="action" value="search" />
                        <input type="hidden" name="form" value="keyword_search" />
                        <input type="hidden" name="post_form_key" value="{if $category_mode}{$listing_type.Key}_{if $listing_type.Advanced_search}advanced{else}quick{/if}{else}{if $cdt_count == 1}{$category_dropdown_types.0}_{if $listing_types[$category_dropdown_types.0].Advanced_search}advanced{else}quick{/if}{/if}{/if}" />

                        {assign var='any_replace' value=`$smarty.ldelim`field`$smarty.rdelim`}
                        <input class="tags-autocomplete"
                               type="text"
                               placeholder="{if $category_mode && $category && $category.name}{$lang.search} {$category.name}{else}{$lang.keyword_search_hint}{/if}"
                               name="f[keyword_search]"
                               value="{$smarty.post.keyword_search}"
                               autocomplete="off"
                        />

                        {if $category_mode}
                            <input type="hidden" name="f[Category_ID]" value="{$category.ID}">
                        {else}
                            <select name="f[Category_ID]" {if $smarty.post.Category_ID}data-id="{$smarty.post.Category_ID}"{/if}>
                                <option value=""></option>
                            </select>

                            <script class="fl-js-dynamic">
                            var categoryDropdownTypes = {if $cdt_count == 1}'{$category_dropdown_types.0}'{elseif is_array($category_dropdown_types)}Array('{"', '"|@implode:$category_dropdown_types}'){else}''{/if};
                            var categoryDropdownData = null;

                            {if $cdt_count > 1}
                                categoryDropdownData = new Array();
                                {foreach from=$category_dropdown_types item='dropdown_type' name='fSearchForms'}
                                    {assign var='type_page_key' value=$listing_types[$dropdown_type].Page_key}

                                    categoryDropdownData.push({literal} { {/literal}
                                        ID: '{$dropdown_type}',
                                        Key: '{$dropdown_type}',
                                        Link_type: '{$listing_types[$dropdown_type].Links_type}',
                                        Path: '{$pages.$type_page_key}',
                                        name: '{phrase key='pages+name+lt_'|cat:$dropdown_type}',
                                        Sub_cat: {$smarty.foreach.fSearchForms.iteration},
                                        Advanced_search: {$listing_types[$dropdown_type].Advanced_search}
                                    {literal} } {/literal});
                                {/foreach}
                            {/if}

                            {literal}

                            flUtil.loadScript(rlConfig.tpl_base  + 'js/categoryDropdown.js', function() {
                                $('.top-search select[name="f[Category_ID]"]').categoryDropdown({
                                    listingTypeKey: categoryDropdownTypes,
                                    typesData: categoryDropdownData,
                                    phrases: { {/literal}
                                        no_categories_available: "{$lang.no_categories_available}",
                                        select: "{$lang.any_field_value|replace:$any_replace:$lang.category}",
                                        select_category: "{$lang.any_field_value|replace:$any_replace:$lang.category}"
                                    {literal} }
                                });
                            });

                            {/literal}
                            </script>
                        {/if}

                        <button type="submit"></button>
                    </form>
                    {foreach name='mMenu' from=$main_menu item='mainMenu'}
                        {if $mainMenu.Key == 'add_listing'}
                            <a class="add-property button{if $pageInfo.Controller != 'search_map'} d-lg-none{/if}" {if $mainMenu.No_follow || $mainMenu.Login}rel="nofollow" {/if}title="{$mainMenu.title}" href="{$rlBase}{if $pageInfo.Controller != 'add_listing' && !empty($category.Path) && !$category.Lock}{if $config.mod_rewrite}{$mainMenu.Path}/{$category.Path}/{$steps.plan.path}.html{else}?page={$mainMenu.Path}&amp;step={$steps.plan.path}&amp;id={$category.ID}{/if}{else}{if $config.mod_rewrite}{$mainMenu.Path}.html{$mainMenu.Get_vars}{else}?page={$mainMenu.Path}{/if}{/if}">{$mainMenu.name}</a>
                            {break}
                        {/if}
                    {/foreach}
                </div>
                {/strip}

                {if $pageInfo.Controller == 'listing_details'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'bread_crumbs.tpl'}
                {/if}
                <div class="top-navigation-container d-none d-lg-block">
                    <div class="top-navigation">
                        <span class="circle mobile-search-button d-sm-none" id="mobile-search-nav"><span class="default"></span></span>
                        {rlHook name='tplHeaderUserNav'}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'user_navbar.tpl'}
                        {rlHook name='tplHeaderUserArea'}
                    </div>
                </div>
            </header>

            <!-- page content -->
            <section id="main_container">
                <div class="inside-container row">
                    <section id="content" class="col-12">
                        {if $pageInfo.Controller != 'listing_details'}
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'bread_crumbs.tpl'}
                        {/if}

                        {rlHook name='tplAbovePageContent'}

                        {if $pageInfo.Key != 'search_on_map' && $pageInfo.Controller != 'listing_details' && !$no_h1}
                            {if $navIcons}
                                <div class="h1-nav">
                                    <nav id="content_nav_icons">
                                        {rlHook name='pageNavIcons'}

                                        {if !empty($navIcons)}
                                            {foreach from=$navIcons item='icon'}
                                                {$icon}
                                            {/foreach}
                                        {/if}
                                    </nav>
                            {/if}

                            {if ($pageInfo.Controller == 'home' && $config.home_page_h1) || $pageInfo.Controller != 'home'}
                                <h1{if ($pageInfo.Key == 'login' || $pageInfo.Login) && !$isLogin} class="text-center"{/if}>{if $pageInfo.h1}{$pageInfo.h1}{else}{$pageInfo.name}{/if}</h1>
                            {/if}

                            {if $navIcons}
                                </div>
                            {/if}
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
                                {/literal}
                            </script>
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
                                    <div class="col-xl-6 col-sm-12">
                                        <div>
                                            {foreach from=$blocks item='block'}
                                                {if $block.Side == 'middle_left'}
                                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                                                {/if}
                                            {/foreach}
                                        </div>
                                    </div>

                                    <div class="col-xl-6 col-sm-12">
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
            </section>
            <!-- page content end -->
        </div>
        <aside class="sidebar left col-lg-3 d-none{if $pageInfo.Controller != 'search_map'} d-lg-flex{/if}">
            <div class="sidebar-content sidebar-header-content">
                <header class="page-header">
                    <section class="main-menu">
                        <div id="logo">
                            <a href="{$rlBase}" title="{$config.site_name}">
                                <img alt="{$config.site_name}"
                                    src="{$rlTplBase}img/logo.png?rev={$config.static_files_revision}"
                                    srcset="{$rlTplBase}img/@2x/logo.png?rev={$config.static_files_revision} 2x" />
                            </a>
                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'lang_selector.tpl'}
                        </div>

                        <nav class="menu">
                            {include file='menus'|cat:$smarty.const.RL_DS|cat:'main_menu.tpl'}
                        </nav>
                    </section>
                </header>
            </div>
            <div class="sidebar-content">
                <!-- left blocks area on home page -->
                {if $side_bar_exists}
                    {strip}
                        {foreach from=$blocks item='block'}
                            {if $block.Side == 'left'}
                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'blocks_manager.tpl' block=$block}
                            {/if}
                        {/foreach}
                    {/strip}
                {/if}
                <!-- left blocks area end -->
            </div>
        </aside>
        {rlHook name='tplFooter'}
    </div>
</div>
<div id="mobile-sidebar" class="d-lg-none{if !$side_bar_exists} d-none{/if}">
    <div class="container">
        <div class="row">
            <div class="col-12 mobile-sidebar-content"></div>
        </div>
    </div>
</div>
<div class="container{if $pageInfo.Controller == 'search_map'} d-none{/if}">
    <div class="row">
        <div class="col-lg-3 d-none d-lg-flex footer-sidebar{if $pageInfo.Controller == 'listing_details'} d-none d-xl-flex{/if}">
            <div class="footer-sidebar-content"></div>
        </div>
        <footer class="col-lg-{if $pageInfo.Controller == 'search_map'}12{else}9{/if}" id="footer">
            <div class="footer-content">
                <div class="row">
                    <span class="scroll-top"></span>

                    <nav class="footer-menu col-12 pb-3">
                        {include file='menus'|cat:$smarty.const.RL_DS|cat:'footer_menu.tpl'}
                    </nav>
                    {include file='footer_data.tpl'}
                </div>
            </div>
        </footer>
    </div>
</div>
