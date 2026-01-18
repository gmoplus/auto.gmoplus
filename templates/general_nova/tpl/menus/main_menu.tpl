<!-- main menu block -->

<div class="menu d-flex h-100 align-items-center flex-fill shrink-fix justify-content-end">
    <div class="d-none d-lg-flex h-100 align-items-center flex-fill shrink-fix justify-content-end">
        <span class="mobile-menu-header d-none align-items-center">
            <span class="mr-auto">{$lang.menu}</span>
            <svg viewBox="0 0 12 12">
                <use xlink:href="#close-icon"></use>
            </svg>
        </span>

	{foreach name='mMenu' from=$main_menu item='mainMenu'}
		{if $mainMenu.Key == 'add_listing'}{assign var='add_listing_button' value=$mainMenu}{continue}{/if}
		<a title="{$mainMenu.title}"
           class="h-100{if $pageInfo.Key == $mainMenu.Key} active{/if}"
           {if $mainMenu.No_follow || $mainMenu.Login}
           rel="nofollow"
           {/if}
           href="{strip}
           {if $mainMenu.Page_type == 'external'}
               {$mainMenu.Controller}
           {else}
                {pageUrl key=$mainMenu.Key vars=$mainMenu.Get_vars}
           {/if}
           {/strip}">{$mainMenu.name}</a>
	{/foreach}
    </div>

    {if $add_listing_button}
        <a class="h-100 add-property icon-opacity d-none d-md-flex" 
        {if $mainMenu.No_follow || $mainMenu.Login}
        rel="nofollow"
        {/if}
        title="{$mainMenu.title}"
        href="{strip}
            {if $pageInfo.Controller != 'add_listing'
                && !empty($category.Path)
                && !$category.Lock
            }
                {$rlBase}
                {if $config.mod_rewrite}
                    {$add_listing_button.Path}/{$category.Path}/{$steps.plan.path}.html
                {else}
                    ?page={$add_listing_button.Path}&step={$steps.plan.path}&id={$category.ID}
                {/if}
            {else}
                {pageUrl key=$add_listing_button.Key}
            {/if}
        {/strip}"><span class="icon-opacity__icon"></span>{$add_listing_button.name}</a>
    {/if}

	{*<div class="more" style="display: none;"><span><span></span><span></span><span></span></span></div>*}
</div>

<span class="menu-button d-flex d-lg-none align-items-center" title="{$lang.menu}">
    <svg viewBox="0 0 20 14" class="mr-2">
        <use xlink:href="#mobile-menu"></use>
    </svg>
    {$lang.menu}
</span>

{*<ul id="main_menu_more"></ul>*}

<!-- main menu block end -->
