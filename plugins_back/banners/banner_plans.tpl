<!-- banner_plans.tpl -->

{if $plans|@count > 5}<div class="plans-container">{/if}
<ul class="plans{if $plans|@count > 5} more-5{else} count-{$plans|@count}{/if}">
{foreach from=$plans item='plan' name='plansF' key='plansK'}{strip}
    {if $plan.ID == $sPost.plan}
        {assign var='sPlan' value=$plan}
    {else}
        {if $smarty.foreach.plansF.first}
            {assign var='sPlan' value=$plan}
        {/if}
    {/if}

    {assign var='pColored' value=false}
    {if $plan.Color && $plan.Color != 'ffffff' && $plan.Color != 'fff'}
        {assign var='pColored' value=true}
    {/if}

    <li id="plan_{$plan.ID}">
        <div class="frame{if $pColored} colored{/if}" {if $pColored}style="background-color: #{$plan.Color};border-color: #{$plan.Color};"{/if}>
            <span class="name">{$plan.name}</span>
            <span class="price">
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                {$plan.Price}
                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
            </span>

            <span title="{$lang.banners_bannerLiveFor}" class="count">
                {if $plan.Period}
                    {if $plan.Plan_Type == 'period'}
                        {$plan.Period} {$lang.days}
                    {else}
                        {$plan.Period} {$lang.banners_liveTypeViews}
                    {/if}
                {else}
                    {$lang.unlimited}
                {/if}
            </span>

            <span title="{$lang.banners_bannerType}" class="count">
                <ul>
                {foreach from=","|explode:$plan.Types item=type}
                    {assign var="b_type" value='banners_bannerType_'|cat:$type}
                    <li>{$lang.$b_type}</li>
                {/foreach}
                </ul>
            </span>
    
            <div class="selector">
                <label><input {if $plan.ID == $sPlan.ID}checked="checked"{/if} class="multiline" type="radio" name="plan" value="{$plan.ID}" /></label>
            </div>
        </div>
    </li>
{/strip}{/foreach}
</ul>
{if $plans|@count > 5}</div>{/if}


<script class="fl-js-dynamic">
{literal}

$(function(){
    var $plans = $('ul.plans > li');

    $plans.click(function(){
        $(this).find('input[name=plan]').prop('checked', true);
    });
});

{/literal}
</script>

<!-- banner_plans.tpl end -->
