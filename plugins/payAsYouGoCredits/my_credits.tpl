<!-- payAsYouGoCredits plugin -->

<div class="highlight">
    {if !$purchasePage}
        <div class="table-cell">
            <div class="name">{$lang.paygc_account_credits}</div>
            <div class="value"><b>{$creditsInfo.Total_credits}</b> {$lang.paygc_credits_count}</div>
        </div>
        {if $creditsInfo.Total_credits > 0 && $config.payAsYouGoCredits_period > 0}
        <div class="table-cell">
            <div class="name">{$lang.active_till}</div>
            <div class="value">{$creditsInfo.Expiration_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
        </div>
        {/if}
        <div class="submit-cell">
            <div class="name"></div>
            <div class="search-button">
                <a class="button" href="{pageUrl key='my_credits' add_url='purchase'}">{$lang.paygc_buy_credits}</a>
                <a class="pl-3" href="{pageUrl key='payment_history' vars='credits'}">{$lang.paygc_view_history}</a>
            </div>
        </div>
    {else}
        {if !empty($credits)}
            <div class="pb-4">{$lang.paygc_desc}</div>

            {if $paygcDescPeriod}
                <div class="pb-4">{$paygcDescPeriod}</div>
            {/if}

            <form method="post" action="">
                <input type="hidden" name="submit" value="true" />

                {include file='blocks/fieldset_header.tpl' id='credit_list' name=$lang.paygc_give_youself_credits}
                {include file="`$smarty.const.RL_PLUGINS`payAsYouGoCredits/packages.tpl"}
                {include file='blocks/fieldset_footer.tpl'}

                <input type="submit" value="{$lang.next}" />
            </form>
        {else}
            <div class="info">{phrase key='paygc_no_packages' db_check=true}</div>
        {/if}
    {/if}
</div>

<!-- end payAsYouGoCredits plugin -->
