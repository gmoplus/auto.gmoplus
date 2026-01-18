{if $order_info.fields}
    <fieldset class="light">
        <legend id="legend_search_settings" class="up" onclick="fieldset_action('search_settings');">{$lang.shc_shipping_details}</legend>
        <table class="list">
            {foreach from=$order_info.fields item='item'}
                {if !empty($item.value)}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' item=$item}
                {/if}
            {/foreach}
        </table>
        {if $order_info.Mail}
        <tr>
            <td class="name">{$lang.mail}:</td>
            <td class="value">{$order_info.Mail}</td>
        </tr>
        {/if}
    </fieldset>
{/if}
