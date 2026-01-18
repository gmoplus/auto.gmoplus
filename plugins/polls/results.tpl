<!-- polls results list -->

{foreach from=$poll.items item='poll_item' name='pollsF'}
    <li{if !$smarty.foreach.pollsF.last} class="mb-2"{/if}>
        <div class="mb-1">{phrase key='polls_items+name+'|cat:$poll_item.Key} (<b>{$poll_item.Votes}</b> {$lang.votes})</div>
        <div class="text-center" style="{strip}
            font-size: 0.786em;
            height: 16px;
            line-height: 16px;
            width: {if $poll_item.percent < 10}10{elseif $poll_item.percent >= 10 && $poll_item.percent < 70}{$poll_item.percent*1.25}{else}{$poll_item.percent}{/if}%;
            background: {$poll_item.Color};
            color: {if $poll_item.Color == '#ffffff'}#000000{else}#ffffff{/if};
        {/strip}">
            {$poll_item.percent}%
        </div>
    </li>
{/foreach}

<!-- polls results list end -->
