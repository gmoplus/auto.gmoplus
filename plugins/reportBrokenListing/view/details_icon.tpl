<!-- report broken listing | listing details icon -->
{if (!$account_info || $listing_data.Account_ID !== $account_info.ID) && $pageInfo.Controller == 'listing_details'}
    <li>
        <span data-lid="{$listing_data.ID}" title="{$lang.reportbroken_add_in}" class="hide link" id="report-broken-listing">
            {$lang.reportbroken_add_in}
            <span class="rbl-icon ml-1">
                {if $config.rl_version|version_compare:'4.9.2' >= 0}
                    {fetch file=$smarty.const.RL_LIBS|cat:'icons/svg-line-set/road-sign-warning.svg'}
                {else}
                    {fetch file=$smarty.const.RL_PLUGINS|cat:'reportBrokenListing/static/road-sign-warning.svg'}
                {/if}
            </span>
        </span>
        <span data-lid="{$listing_data.ID}" title="{$lang.reportbroken_add_in}" class="hide link" id="remove-report">
            {$lang.reportbroken_remove_in}
            <span class="rbl-icon ml-1">
                {if $config.rl_version|version_compare:'4.9.2' >= 0}
                    {fetch file=$smarty.const.RL_LIBS|cat:'icons/svg-line-set/remove-circle.svg'}
                {else}
                    {fetch file=$smarty.const.RL_PLUGINS|cat:'reportBrokenListing/static/remove-circle.svg'}
                {/if}
            </span>
        </span>
    </li>
{/if}
<!-- report broken listing | listing details icon end -->
