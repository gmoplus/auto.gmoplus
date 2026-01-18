{if $coupon_price_info}    
    <div class="table-cell clearfix">
        <div class="name">
            {$lang.coupon_code}:
        </div>
        <div class="value">
            <b>{$coupon_code}</b>, <a href="javascript:void(0);" id="cancel_coupon_code" onClick="cancelCouponCode()" class="dark_12">{$lang.coupon_reject}</span></a>
        </div>
    </div>
    <div class="table-cell clearfix">
        <div class="name">
            {$lang.price}:
        </div>
        <div class="value">
            {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$coupon_price_info.price} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
        </div>
    </div>
    <div class="table-cell clearfix">
        <div class="name">
            {$lang.coupon_discount}:
        </div>
        <div class="value">
            {$coupon_price_info.discount}
        </div>
    </div>
    <div class="table-cell clearfix">
        <div class="name">
            {$lang.total}:
        </div>
        <div class="value">
            {if $coupon_price_info.total==0}{$lang.free}{else}{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} {$coupon_price_info.total} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}{/if}
        </div>
    </div>
{/if}