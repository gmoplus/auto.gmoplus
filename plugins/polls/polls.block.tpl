<!-- poll block -->

{if $poll}
    <div class="poll-container" id="poll_container_{$poll.ID}">
        {assign var='poll_name_key' value='polls+name+'|cat:$poll.ID}

        {if $poll.voted}
            {if $poll.Random == 1 || $block.Header == 0}
                <header class="pb-2"><b>{$lang.$poll_name_key}</b></header>
            {/if}

            <ul class="poll-votes">
                {include file=$smarty.const.RL_PLUGINS|cat:'polls'|cat:$smarty.const.RL_DS|cat:'results.tpl'}
            </ul>

            <div class="poll-total mt-3">{$lang.total_votes}: <b>{$poll.total}</b></div>
        {else}
            {if $poll.Random == 1 || $block.Header == 0}
                <header class="pb-2"><b>{$lang.$poll_name_key}</b></header>
            {/if}

            <div class="poll-inquirer">
                <ul class="poll-answer">
                {foreach from=$poll.items item='poll_item' name='pollsF'}
                    <li class="d-flex w-100 align-items-center{if !$smarty.foreach.pollsF.last} mb-1{/if}">
                        <span style="background: {$poll_item.Color};height: 18px;flex: 0 0 2px;" class="mr-1"></span>
                        <label>
                            <input type="radio" name="poll_{$poll.Key}" value="{$poll_item.Key}" />
                            {phrase key='polls_items+name+'|cat:$poll_item.Key}
                        </label>
                    </li>
                {/foreach}
                </ul>

                <div class="poll-nav pt-3 d-flex flex-wrap align-items-center">
                    <div><input type="button" value="{$lang.vote}" id="vote_button_{$poll.ID}" /></div>
                    <div class="ml-2 mr-2 mt-2 mb-2 text-center flex-fill"><span class="link">{$lang.polls_view_results}</span></div>
                </div>
            </div>

            <div class="poll-results hide">
                <ul class="poll-votes" id="poll_results_{$poll.ID}">
                    {include file=$smarty.const.RL_PLUGINS|cat:'polls'|cat:$smarty.const.RL_DS|cat:'results.tpl'}
                </ul>

                <div class="poll-total mt-3">{$lang.total_votes}: <b>{$poll.total}</b></div>

                <div class="poll-results-nav mt-2 ralign">
                    <span class="link">{$lang.polls_back_for_vote}</span>
                </div>
            </div>
        {/if}

        <script id="pollItem" type="text/x-jquery-tmpl">
        {literal}

        ${($data.lang_votes = '{/literal}{$lang.votes}{literal}'),''}

        <li>
            <div>${name} (<b>${Votes}</b> ${lang_votes})</div>
            <div style="width: {{if percent < 10}}10{{else percent >= 10 && percent < 70}}${percent*1.25}{{else}}${percent}{{/if}}%; background: ${Color};color: {{if Color == '#ffffff'}}#000000{{else}}#ffffff{{/if}};">
                ${percent}%
            </div>
        </li>

        {/literal}
        </script>
    </div>
{else}
    {phrase key='polls_not_created' db_check=true}
{/if}

<!-- poll block end -->
