<!-- comments DOM -->

{if $config.comments_login_access && !$isLogin}
    <div class="info text-notice">{$lang.comment_login_to_see_comments}</div>
{elseif $comments}
    {assign var='hint_replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
    {assign var='comment_count' value=$total_comments}

    <div class="d-flex mb-2 align-items-center flex-wrap">
        <h{if $block.Side == 'left'}4{else}3{/if} class="flex-fill mb-2">{$lang.comments_number_info|replace:$hint_replace:$comment_count}</h{if $block.Side == 'left'}4{else}3{/if}>
        {if $config.comment_mode == 'tab' && !$comment_own_listing_denied && $comments|@count > 3}
            <a class="button low mx-auto mb-2 add-comment-anchor" href="#add-comment">{$lang.comment_add_comment}</a>
        {/if}
    </div>

    <ul class="comments-list">
        {foreach from=$comments item='comment'}
            <li class="mb-4">
                <div class="d-flex align-items-center flex-wrap">
                    <div class="mr-3">
                        {if $comment.Personal_address}
                            <a href="{$comment.Personal_address}">{$comment.Author}</a>
                        {else}
                            {$comment.Author}
                        {/if}
                    </div>
                    <div class="date">
                        {$comment.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                        {if $config.comment_show_time}
                            {$comment.Date|date_format:'%H:%M'}
                        {/if}
                    </div>
                </div>

                <div class="d-flex align-items-center flex-wrap">
                    <strong class="{if $config.comments_rating_module && $comment.Rating}mr-3 {/if}mt-1">{$comment.Title}</strong>
                    {if $config.comments_rating_module && $comment.Rating}
                        <div class="d-flex mt-1">
                            {section name='stars' start=1 loop=$comment.Rating+1}<span class="comment-star comment-star-page"></span>{/section}
                        </div>
                    {/if}
                </div>
                <div class="mt-1 table-cell">
                    <div class="value">{$comment.Description|nl2br}</div>
                </div>
            </li>
        {/foreach}
    </ul>

    {include file=$smarty.const.RL_PLUGINS|cat:'comment/comment_paging.tpl'}
{else}
    <div class="info text-notice">{$lang.comment_absent_comments_in_listings}</div>
{/if}

<!-- comments DOM end -->
