{assign var='hide' value=true}
{foreach from=$order_info.fields item='item'}
    {if !empty($item.value)}
        {assign var='hide' value=false}
    {/if}
{/foreach}
{if $order_info.fields && !$hide}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_shipping_location_details' name=$lang.shc_shipping_details}
        <div class="row">
            <div class="col-sm-12 col-xs-12 fields">
                {foreach from=$order_info.fields item='item'}
                    {if !empty($item.value)}
                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                    {/if}
                {/foreach}
            </div>
        </div>
        {if $order_info.Mail}
        <div class="table-cell">
            <div class="name"><div><span>{$lang.mail}</span></div></div>
            <div class="value">{$order_info.Mail}</div>
        </div>
        {/if}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
{/if}
