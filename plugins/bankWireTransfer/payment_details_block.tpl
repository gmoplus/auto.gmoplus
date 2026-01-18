{if !empty($payment_details)}
    {if $pageInfo.Controller == 'payment_history'}
        <div class="name"><b>{$lang.bwt_payment_details}:</b></div>
        <div class="sLine"></div>
    {/if}

    <div class="table-cell">
        <div class="value">
            {$payment_details.content}
        </div>
    </div>
{else}
    <div class="static-content">{$lang.bwt_missing_payment_details}</div>
{/if}
