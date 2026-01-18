<!-- my shopping cart page  -->

{addCSS file=$rlTplBase|cat:'components/step-form-bottom-nav/step-form-bottom-nav.css'}

{if !$no_access}
    {assign var='allow_link' value=true}
    {assign var='current_found' value=false}

    <!-- steps -->
    <ul class="steps mobile">
        {math assign='step_width' equation='round(100/count, 3)' count=$shc_steps|@count}
        {assign var='steps_values' value=$shc_steps|@array_values}
        {foreach from=$steps_values item='step' name='stepsF' key='step_key'}{strip}
            {if $cur_step == $step.key || !$cur_step}{assign var='allow_link' value=false}{/if}
            {assign var='next_index' value=$step_key+1}
            <li style="width: {$step_width}%;" class="{if $cur_step}{if $cur_step == $step.key}current{assign var='current_found' value=true}{elseif !$current_found}{if $steps_values[$next_index].key == $cur_step}prev {/if}past{/if}{elseif $smarty.foreach.stepsF.first}current{/if}">
                <a href="{if $allow_link}{pageUrl page='shc_my_shopping_cart' add_url='step='|cat:$step.key}{else}javascript:void(0){/if}" title="{$step.name}">{if $step.caption}<span>{$lang.step}</span> {$smarty.foreach.stepsF.iteration}{else}{$step.name}{/if}</a>
            </li>
        {/strip}{/foreach}
    </ul>
    <!-- end steps -->
{/if}

{if !$no_access}
    <h1>{$step_name}</h1>
{/if}

{if $cur_step == 'cart'}
    <!-- cart details -->
    <div class="area_cart step_area content-padding" id="cart_items">
        {if ($cart.items || $cart.dealers) && $cart.isAvailable}
            {if $config.shc_method == 'single'}
                <form id="form_single" class="mb-4" method="post" action="{pageUrl key='shc_my_shopping_cart'}">
                    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/items.tpl' shcItems=$cart.items shcDealer='single' shcTotal=$cart.total}
                </form>
            {elseif $config.shc_method == 'multi'}
                {foreach from=$cart.dealers item='dealer'}
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shopping_cart_dealer_'|cat:$dealer.ID name=$dealer.Full_name}
                    <form id="form_{$dealer.ID}" method="post" action="{pageUrl key='shc_my_shopping_cart'}">
                        {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/items.tpl' shcItems=$dealer.items shcDealer=$dealer.ID shcTotal=$dealer.total}
                    </form>
                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                {/foreach}
            {/if}
        {else}
            <div class="text-message mb-4">{$lang.shc_empty_cart}</div>
        {/if}
        {if $cart.hasUnavailable}
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/items_unavailable.tpl' shcItems=$cart.items}
        {/if}
    </div>
    <!-- end cart details -->
{elseif $cur_step == 'auth'}
    {addJS file=$rlTplBase|cat:'js/form.js'}

    <div class="area_auth step_area hide">
        <form method="post" action="{pageUrl key='shc_my_shopping_cart' add_url="step=`$shc_steps.auth.path`"}">
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/auth.tpl'}
            <input type="hidden" name="form" value="submit" />

            <span class="form-buttons">
                <a href="{buildPrevStepURL show_extended=1}">{$lang.perv_step}</a>
                <input type="submit" value="{$lang.next_step}" />
            </span>
        </form>

        <script class="fl-js-dynamic">
        flForm.auth();
        </script>
    </div>
{elseif $cur_step == 'shipping'}
    <div class="area_shipping step_area content-padding hide">
        <form method="post" action="{pageUrl key='shc_my_shopping_cart' add_url="step=`$shc_steps.shipping.path`"}" id="shipping-form">
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/shipping.tpl'}

            <input type="hidden" name="form" value="submit" />

            <div class="form-buttons form">
                <a href="{buildPrevStepURL show_extended=1}">{$lang.perv_step}</a>
                <input type="submit" value="{$lang.next_step}" />
            </div>
        </form>
    </div>
{elseif $cur_step == 'checkout'}
    <div class="area_checkout step_area content-padding hide">
        <form id="form-checkout" method="post" action="{pageUrl key='shc_my_shopping_cart' add_url="step=`$shc_steps.checkout.path`"}">
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/view/checkout.tpl'}

            <input type="hidden" name="step" value="checkout" />
            <span class="form-buttons" style="padding-top: 0;">
                {if !$completed}
                <a href="{buildPrevStepURL show_extended=1}">{$lang.perv_step}</a>
                {/if}
                <input type="submit" value="{$lang.checkout}" />
            </span>
        </form>
    </div>

{elseif $cur_step == 'done' }
    <div class="area_done content-padding step_area hide">
        <div class="text-message">
            {if $cash_only}
                {$lang.shc_done_cash_payment}
            {elseif $shcIsPaid}
                {$lang.shc_done_notice}
            {else}
                {$lang.shc_waiting_payment}
            {/if}
        </div>
    </div>
{/if}

<script class="fl-js-dynamic">
var shc_dealer = '{$shcDealer}';

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

<!-- steps -->

<!-- my shopping cart page end  -->
