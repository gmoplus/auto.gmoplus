<!doctype html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>{$lang.invoice_number}{$item_info.ID}</title>
</head>

<style>{literal}
    body {
        font-size: 12px;
        font-family: 'Dejavu Sans', Arial, Helvetica, sans-serif;
    }
    td.site-name {
        font-size: 18px;
    }
    .font-size-14 {
        font-size: 14px;
    }
    div.divider {
        width: 100%;
        line-height: 3px;
        border-top: 1px solid silver;
        padding-bottom: 10px;
    }
{/literal}</style>

<body>
    <table cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td width="50%" align="left">
                {assign var='logoExtension' value='png'}
                {if is_file("`$smarty.const.RL_ROOT`templates/`$config.template`/img/logo.svg")}
                    {assign var='logoExtension' value='svg'}
                {/if}

                <img src="{$smarty.const.RL_URL_HOME}templates/{$config.template}/img/logo.{$logoExtension}" style="border: 0" />
            </td>
            <td width="50%" align="right" class="site-name">
                {assign var='$page_home_name' value='pages+title+home'}
                {if $lang.email_site_name}{$lang.email_site_name}{else}{$lang[$page_home_name]}{/if}
                <br /><small>{$smarty.const.RL_URL_HOME}</small>
            </td>
        </tr>
        <tr>
            <td style="line-height: 4px;">
            </td>
        </tr>
    </table>
{if $type == 'payment'}
    <table cellpadding="0" cellspacing="0" style="width: 100%; padding-top: 20px; padding-bottom: 20px;">
        <tr>
            <td width="48%" align="left"  valign="top">
                <div class="font-size-14">{$lang.invoice_buyer_info}</div>
                <div class="divider"></div>{strip}
                {foreach from=$item_info.buyer.Fields item='field'}
                    <span>{$field.name}: {$field.value}</span><br/ >
                {/foreach}
            {/strip}</td>
            <td width="4%"></td>
            <td width="48%" align="right" valign="top">
                <div class="font-size-14">{$lang.invoice_seller_info}</div>
                <div class="divider"></div>{strip}
                {$config.invoices_billing}
            {/strip}</td>
        </tr>
    </table>
    <div style="width: 100%; line-height: 5px; border-top: 1px solid silver; margin-bottom: 20px;"></div>
    <table cellpadding="0" cellspacing="0" style="width: 100%;">
        <tr>
            <td align="left">{strip}
                {$lang.invoice_number}:&nbsp;{$item_info.ID}<br />
                {$lang.txn_id}:&nbsp;{$item_info.Txn_ID}<br />
                {$lang.payment_gateway}:&nbsp;{$item_info.Gateway}<br />
                {$lang.date}:&nbsp;{$item_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}
            {/strip}</td>
        </tr>
    </table>
    <table width="100%" style="margin-top: 20px;">
        <tr style="background-color: #f1f1f1;">
            <td width="55%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.item}</td>
            <td width="10%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.invoice_quantity}</td>
            <td width="15%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.status}</td>
            <td width="20%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.price}</td>
        </tr>
        <tr>
            <td width="55%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">
                {$item_info.Item_name}
            </td>
            <td width="10%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">1</td>
            <td width="15%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">{$lang[$item_info.Status]}</td>
            <td width="20%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;" align="center">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.price|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
        <tr>
            <td colspan="3" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.invoice_subtotal}&nbsp;&nbsp;</td>
            <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.subotal|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
        {if $config.invoices_include_tax}
        <tr>
            <td colspan="3" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.invoice_tax}&nbsp;&nbsp;</td>
            <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.tax_rate|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
        {/if}
        <tr>
            <td colspan="3" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.total}&nbsp;&nbsp;</td>
            <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.Total|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
    </table>
{elseif $type == 'service'}
    <table width="100%">
        <tr>
            <td style="line-height: 40px;">
            </td>
        </tr>
        <tr>
            <td colspan="5">#{$lang.invoice_txn_id} <u>{$item_info.Txn_ID}</u></td>
        </tr>
        <tr>
            <td style="line-height: 15px;">
            </td>
        </tr>
    </table>
    <table width="100%">
        <tr style="background-color: #f1f1f1;">
            <td width="40%" style="height: 25px; border: 1px solid silver;">{$lang.item}</td>
            <td width="15%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.date}</td>
            <td width="10%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.payment_gateway}</td>
            <td width="15%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.status}</td>
            <td width="20%" style="height: 25px; border: 1px solid silver;" align="center">{$lang.price}</td>
        </tr>
        <tr>
            <td width="40%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">
                {$item_info.Subject}<br />
                {$item_info.Description}
            </td>
            <td width="15%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">{$item_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
            <td width="10%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center">{$item_info.Gateway}</td>
            <td width="15%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;" align="center" >{$lang[$item_info.pStatus]}</td>
            <td width="20%" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;" align="center">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.Total|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
        <tr>
            <td colspan="4" align="right" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver;">{$lang.total}&nbsp;&nbsp;</td>
            <td width="20%" align="center" style="height: 25px; border-bottom: 1px solid silver; border-left: 1px solid silver; border-right: 1px solid silver;">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item_info.Total|number_format:2:'.':','}{if $config.system_currency_position == 'after'} {$config.system_currency}{/if}</td>
        </tr>
    </table>
{/if}
</body>
</html>
