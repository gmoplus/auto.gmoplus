<!-- Listing Information -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <fieldset class="light">
        <legend id="legend_bwt_details" class="up" onclick="fieldset_action('bwt_details');">{$lang.bwt_order_information}</legend>
        <table class="form">
            <tr>
                <td class="name" width="180">{$lang.txn_id}</td>
                <td class="value">{$txn_info.Txn_ID}</td>
            </tr>
            <tr>
                <td class="name" width="180">{$lang.item}</td>
                <td class="value">{$txn_info.Item_name}</td>
            </tr>
            {if !empty($txn_info.Total)}
            <tr>
                <td class="name">{$lang.total}</td>
                <td class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$txn_info.Total|number_format:2:'.':','}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</td>
            </tr>
            {/if}
            {if $txn_info.dealer}
            <tr>
                <td class="name">{$lang.shc_dealer}</td>
                <td class="value">{$txn_info.dealer.Full_name}</td>
            </tr>
            {/if}
            <tr>
                <td class="name" width="180">{$lang.status}</td>
                <td class="value">{$lang[$txn_info.Status]}</td>
            </tr>
        </table>
    </fieldset>
    {if $payment_details}
    <fieldset class="light">
        <legend id="legend_bwt_payment_details" class="up" onclick="fieldset_action('bwt_payment_details');">{$lang.bwt_payment_details}</legend>
        <table class="form">
            <tr>
                <td class="value">{$payment_details.content}</td>
            </tr>
        </table>
    </fieldset>
    {/if}
    <table class="form">
        <tr>
            <td class="value" align="center"><input type="button" onclick="popupTxnInfo.close();" value="{$lang.close}" /></td>
        </tr>
    </table>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'} 
