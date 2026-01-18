<!-- auction payment page  -->

{if !$no_access}
    <!-- steps -->
    {assign var='allow_link' value=true}
    {assign var='current_found' value=false}

    <ul class="steps mobile">
        {math assign='step_width' equation='round(100/count, 3)' count=$shc_steps|@count}
        {assign var='steps_values' value=$shc_steps|@array_values}
        {foreach from=$steps_values item='step' name='stepsF' key='step_key'}{strip}
            {if $cur_step == $step.key || !$cur_step}{assign var='allow_link' value=false}{/if}
            {assign var='next_index' value=$step_key+1}
            <li style="width: {$step_width}%;" class="{if $cur_step}{if $cur_step == $step.key}current{assign var='current_found' value=true}{elseif !$current_found}{if $steps_values[$next_index].key == $cur_step}prev {/if}past{/if}{elseif $smarty.foreach.stepsF.first}current{/if}">
                <a href="{if $allow_link}{pageUrl page='shc_auction_payment' add_url='step='|cat:$step.key vars='item='|cat:$order_info.ID}{else}javascript:void(0){/if}" title="{$step.name}">{if $step.caption}<span>{$lang.step}</span> {$smarty.foreach.stepsF.iteration}{else}{$step.name}{/if}</a>
            </li>
        {/strip}{/foreach}
    </ul>
    <!-- end steps -->
{/if}

{if !$no_access}
    <h1>{$step_name}</h1>
{/if}

{if $cur_step == 'shipping'}
    <div class="area_shipping step_area content-padding hide">
        <form method="post" action="{pageUrl page='shc_auction_payment' add_url='step='|cat:$shc_steps.shipping.path vars='item='|cat:$order_info.ID}">
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping.tpl'}

            <input type="hidden" name="form" value="submit" />

            <span class="form-buttons" style="padding-top: 0;">
                <input type="submit" value="{$lang.next_step}" />
            </span>
        </form>
    </div>
{elseif $cur_step == 'checkout'}
    <div class="area_checkout step_area content-padding hide">

        <form id="form-checkout" method="post" action="{pageUrl page='shc_auction_payment' add_url='step='|cat:$shc_steps.checkout.path vars='item='|cat:$order_info.ID}">
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/checkout.tpl'}

            <input type="hidden" name="step" value="checkout" />
            <span class="form-buttons" style="padding-top: 0;">
                {if !$completed}
                <a href="{buildPrevStepURL show_extended=1}{if $config.mod_rewrite}?{else}&{/if}item={$order_info.ID}">{$lang.perv_step}</a>
                {/if}
                <input type="submit" value="{$lang.checkout}" />
            </span>
        </form>
    </div>
{elseif $cur_step == 'done'}
    <div class="area_done content-padding step_area hide">
        {if $shcIsPaid}
            <div class="text-message">{$lang.shc_done_notice}</div>
        {else}
            <div class="text-message">{$lang.shc_waiting_payment}</div>
        {/if}
    </div>
{/if}

<script class="fl-js-dynamic">
var shc_dealer = '{$order_info.Dealer_ID}';

{if $cur_step}
    flynax.switchStep('{$cur_step}');
{/if}

{literal}
    $(document).ready(function(){
        $('#shipping_comment').textareaCount({
            'maxCharacterSize': rlConfig['messages_length'],
            'warningNumber': 20
        });
        
        shoppingCart.handlerItems();
    });
{/literal}
</script>

<!-- auction payment page end  -->
