<!-- shopping cart navbar styles -->

{addCSS file=$rlTplBase|cat:'components/popup/popup.css'}

{assign var='navbar_icon_size' value=16}
{if $tpl_settings.name|@strpos:'_nova_wide' || $tpl_settings.name == 'general_cragslist_wide'}
    {assign var='navbar_icon_size' value=20}
{/if}

<style>
{literal}
svg.cart-navbar-icon {
    {/literal}
    width: {$navbar_icon_size}px;
    height: {$navbar_icon_size}px;
    {if $sc_is_nova}
    flex-shrink: 0;
    {else}
    vertical-align: middle;
    margin-top: -1px;
    {/if}
    {literal}
}
.cart-icon-container .content {
    width: auto !important;
}

.shc-item-unavailable {
    filter: grayscale(0.8);
}
.shc-rtl-fix {
    unicode-bidi: embed;
}
#shopping_cart_block {
    min-width: 280px;
}
.cart-item-picture img {
    width: 70px;
    height: 50px;
    object-fit: cover;
}

.cart-icon-container ul.cart-items > li {
    position: relative;
    height: auto;
    line-height: inherit;
    white-space: normal;
}
.cart-icon-container .content .controls a.button {
    width: initial;
    white-space: nowrap;
}
.clear-cart {
    filter: brightness(1.8);
}
.delete-item-from-cart {
    right: 0;
    top: 4px;
}
body[dir=rtl] .delete-item-from-cart {
    right: auto;
    left: 0;
}

@media screen and (min-width: 992px) {
    .cart-icon-container .default > span {
        display: initial !important;
    }
}
@media screen and (max-width: 767px) {
    .cart-icon-container .default > span {
        text-indent: -1000px;
        display: inline-block;
    }
    div.total-info div.table-cell > div.name,
    div.auction-popup-info div.table-cell > div.name {
        width: 110px !important;
    }
}
@media screen and (max-width: 480px) {
    #shopping_cart_block {
        width: 100%;
    }
}

svg.cart-navbar-icon {
    {/literal}
    {if $sc_is_nova}
    flex-shrink: 0;
    {else}
    vertical-align: middle;
    margin-top: -4px;
    {/if}
    {literal}
}

{/literal}

{if $sc_is_flatty || $sc_hide_name}
{literal}
.cart-icon-container .default > span {
    display: none !important;
}
svg.cart-navbar-icon {
    margin: 0 !important;
}
{/literal}
{/if}

{if !$sc_is_flatty}
{literal}
.cart-icon-container .default:before,
.cart-icon-container .default:after {
    display: none !important;
}
{/literal}
{/if}

</style>
<script>
rlConfig['shcOrderKey'] = '{$order_key}';
rlConfig['system_currency_position'] = '{$config.system_currency_position}';
rlConfig['system_currency'] = '{$config.system_currency}';
rlConfig['system_currency_code'] = '{$config.system_currency_code}'.toLowerCase();
</script>

<!-- shopping cart navbar styles end -->
