{if $smarty.get.id && !isset($smarty.get.completed)}
    {if $plans}
        {if $smarty.get.id}
            {pageUrl key=$pageInfo.Key vars="id=`$smarty.get.id`" assign='formAction'}
        {elseif $account_info.ID}
            {pageUrl key=$pageInfo.Key vars="id=`$account_info.ID`" assign='formAction'}
        {/if}

        <form id="form-checkout" method="post" action="{$formAction}">
            <input type="hidden" name="buy_highlight" value="true"/>
            <input type="hidden" name="from_post" value="1"/>

            <!-- select a highlight plan -->
            {if $plans|@count > 1}{assign var='fieldset_phrase' value=$lang.m_select_highlight_plan}{else}{assign var='fieldset_phrase' value=$plans.$firstIndex.name}{/if}
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$fieldset_phrase}

            <div class="plans-container">
                {if $plans|@count > 1}
                    {foreach from=$plans item='plan'}{if $plan.Subscription && $plan.Price > 0 && !$plan.Listings_remains}{assign var=subscription_exists value=true}{elseif $plan.Featured && $plan.Price > 0 && !$plan.Listings_remains}{assign var=featured_exists value=true}{/if}{/foreach}
                    <ul class="plans{if $plans|@count > 5} more-5{/if}{if $subscription_exists} with-subscription{/if}{if $featured_exists} with-featured{/if}">
                        {foreach from=$plans item='plan' name='plansF'}{strip}
                            {include file=$mConfig.view|cat:'plan.tpl'}
                        {/strip}{/foreach}
                    </ul>
                {else}
                    <input type="hidden" name="plan" value="{$plans.$firstIndex.ID}">
                    <div class="table-cell  clearfix">
                        <div><div><span>{$lang.next_service_will_apply}</span></div></div>
                    </div>
                    <div class="table-cell  clearfix">
                        <div class="name" title="{$lang.m_package_price}"><div><span>{$lang.m_package_price}</span></div></div>
                        <div class="value">
                            {if $plans.$firstIndex.Using_ID && ($plans.$firstIndex.Highlights_available > 0 || $plans.$firstIndex.Is_unlim == 1)}
                                &#8212;
                            {elseif $plans.$firstIndex.Price > 0}
                                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                {$plans.$firstIndex.Price}
                                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                            {else}
                                {$lang.free}
                            {/if}
                        </div>
                    </div>
                    <div class="table-cell  clearfix">
                        <div class="name" title="{$lang.m_highlight_available}"><div><span>{$lang.m_highlight_available}</span></div></div>
                        <div class="value">
                            {if $plans.$firstIndex.Highlights > 0}
                                {if $plans.$firstIndex.Highlights_available}
                                    {assign var='hAvailable' value=$plans.$firstIndex.Highlights_available}
                                {else}
                                    {assign var='hAvailable' value=$plans.$firstIndex.Highlights}
                                {/if}
                                {assign var='rRest' value=`$smarty.ldelim`rest`$smarty.rdelim`}
                                {assign var='rAmount' value=`$smarty.ldelim`amount`$smarty.rdelim`}
                                {$lang.rest_of_amount|replace:$rRest:$hAvailable|replace:$rAmount:$plans.$firstIndex.Highlights}
                            {else}
                                {$lang.unlimited}
                            {/if}
                        </div>
                    </div>
                    <div class="table-cell  clearfix">
                        <div class="name" title="{$lang.m_highlighted_for}"><div><span>{$lang.m_highlighted_for}</span></div></div>
                        <div class="value">{$plans.$firstIndex.Days} <span> {$lang.days}</span></div>
                    </div>

                    {if $plans.$firstIndex.description}
                        <div class="table-cell clearfix">
                            <div class="name"><div><span>{$lang.description}</span></div></div>
                            <div class="value">{$plans.$firstIndex.description}</div>
                        </div>
                    {/if}
                {/if}
            </div>

            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

            <script class="fl-js-dynamic">
                var plans = plans || [];
                {literal}
                $(document).ready(function () {
                    var selected_plan_id = 0;
                    var last_plan_id = 0;

                    {/literal}
                    {foreach from=$plans item='plan'}
                        plans[{$plan.ID}] = [];
                        plans[{$plan.ID}]['Key'] = '{$plan.Key}';
                        plans[{$plan.ID}]['Price'] = {$plan.Price};
                    {/foreach}
                    {literal}

                    flynax.planClick();
                    flynax.qtip();
                });
                {/literal}
            </script>

            <!-- select a highlight plan end -->
            <div class="form-buttons">
                <input type="submit" value="{$lang.next}"/>
            </div>
        </form>
    {else}
        {$lang.m_no_highlight_plan}
    {/if}
{/if}

{if $smarty.get.id && isset($smarty.get.completed)}
    {if $smarty.const.IS_ESCORT === true}{$lang.m_highlight_success_escort}{else}{$lang.m_highlight_success}{/if}
    <div class="form-buttons">
        <a href="{$back_url}">
            <input type="button" value="{if $smarty.const.IS_ESCORT === true}{$lang.m_back_to_my_profiles}{else}{$lang.bumpup_back}{/if}">
        </a>
    </div>
{/if}

{if $smarty.get.id && isset($smarty.get.canceled)}
    {$lang.bump_up_error}
    <div class="form-buttons">
        <a href="{$back_url}">
            <input type="button" value="{if $smarty.const.IS_ESCORT === true}{$lang.m_back_to_my_profiles}{else}{$lang.bumpup_back}{/if}">
        </a>
    </div>
{/if}
