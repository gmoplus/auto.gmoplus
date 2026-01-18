<table cellpadding="0" cellspacing="0" style="width: 100%;">
    <tr>
        <td width="48%" align="left"  valign="top">
            <div>{$lang.shc_order_details}</div>
            <div style="width: 100%; line-height: 3px; border-top: 1px solid silver;"></div>{strip}
            <span>{$lang.shc_order_key}: {$order_info.Order_key}</span><br/ >
            <span>{$lang.shc_dealer}: {$order_info.dFull_name}</span><br/ >
            <span>{$lang.date}: {$order_info.Date}</span><br/ >
            <span>{$lang.shc_payment_status}: {if $paymentType == 'cash' || $order_info.Cash}{$lang.shc_payment_cash}{else}{$lang[$order_info.Status]}{/if}</span><br/ >
            <span>{$lang.shc_shipping_status}: {$order_info.Shipping_status}</span><br/ >
            {if !$isDigital}
                <span>{$lang.shc_shipping_method}: {$order_info.Shipping_method}</span><br/ >
            {/if}
        {/strip}</td>
        <td width="4%"></td>
        <td width="48%" align="right" valign="top">
            <div>{$lang.shc_shipping_details}</div>
            <div style="width: 100%; line-height: 3px; border-top: 1px solid silver;"></div>{strip}
            {foreach from=$order_info.fields item='item'}
                {if !empty($item.value)}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                {/if}
            {/foreach}
        {/strip}</td>
    </tr>
</table>
<div style="width: 100%; line-height: 5px; border-top: 1px solid silver;"></div>
<table width="100%" style="">
    <tr style="background-color: #f1f1f1;">
        <td width="10%" style="height: 25px; border: 1px solid silver;" align="center">&nbsp;</td>
        <td width="45%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.item}</td>
        <td width="10%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.shc_quantity}</td>
        <td width="15%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.price}</td>
        <td width="20%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.total}</td>
    </tr>
    {foreach from=$order_info.items item='item'}
    <tr>
        <td width="10%" style="border-bottom: 1px solid silver; border-left: 1px solid silver;">
            {if $item.main_photo}
                <img alt="{$item.Item}" width="70" style="width: 70px;margin-{$text_dir_rev}: 10px;" src="{$smarty.const.RL_FILES_URL}{$item.main_photo}" />
            {/if}
        </td>
        <td width="45%" style="border-bottom: 1px solid silver; border-left: 1px solid silver;">
            {$item.Item}
            {if $item.Digital && $showDigital}
            <div style="margin-top: 5px;"><a href="{pageUrl key='shc_purchases' vars='item='|$item.ID}">{$lang.shc_download}</a></div>
            {/if}
        </td>
        <td width="10%" style="border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">{$item.Quantity}</td>
        <td width="15%" style="border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">{strip}
            {$item.Price}
        {/strip}
        </td>
        <td width="20%" style="border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;" align="center">{strip}
            {$item.total}
        {/strip}</td>
    </tr>
    {/foreach}
    {if $order_info.Shipping_price}
    <tr>
        <td colspan="4" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.shc_shipping_price}&nbsp;&nbsp;</td>
        <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{strip}
            {$order_info.Shipping_price}
        {/strip}</td>
    </tr>
    {/if}
    {if $config.shc_method == 'multi' && $config.shc_commission_enable && $orderInfo.Dealer_ID == $account_info.ID}
        <tr>
            <td colspan="4" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.shc_commission}&nbsp;&nbsp;</td>
            <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{strip}
                {$order_info.Commission}
            {/strip}</td>
        </tr>
    {/if}
    <tr>
        <td colspan="4" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.total}&nbsp;&nbsp;</td>
        <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{strip}
            {$order_info.Total}
        {/strip}</td>
    </tr>
</table>
