<!-- shopping cart tpl -->

{include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/static/icons.svg'}

{if $config.shc_module}
    <span class="circle cart-icon-container circle_mobile-full-width circle_content-padding priority-z-index">
        <span class="default">
            <svg viewBox="0 0 18 18" class="icon align-self-center header-usernav-icon-fill cart-navbar-icon">
                <use xlink:href="#add-to-cart-listing"></use>
            </svg>
            <span class="button flex-fill">
                <span class="ml-2 count">{$shcTotalInfo.count}</span>
                <span class="shc-rtl-fix">{$lang.shc_count_items}</span>
                <span>/</span>
                <span class="summary shc_price">
                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                    {str2money string=$shcTotalInfo.total}
                    {if $config.system_currency_position == 'after'} {$config.system_currency}{/if}
                </span>
            </span>
        </span>
        <span class="content hide">
            <ul id="shopping_cart_block" class="cart-items circle__list_no-list-styles">
                {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/cart_items.tpl' shcItems=$shcItems}
            </ul>
        </span>
    </span>

    <script class="fl-js-dynamic">
    {literal}

    $(function(){
        $('.cart-icon-container > .default').click(function(){
            $('span.circle_opened').not($(this).parent()).removeClass('circle_opened');
            $(this).parent().toggleClass('circle_opened');

            if (typeof flUtil.setPriorityZIndex == 'function') {
                flUtil.setPriorityZIndex($(this).parent());
            }
        });

        $(document).bind('click touchstart', function(event){
            if (!$(event.target).parents().hasClass('circle_opened')) {
                $('.cart-icon-container').removeClass('circle_opened');
            }
        });
    });

    {/literal}
    </script>
{/if}

<!-- shopping cart tpl end -->
