<!-- SellerReviews comments-list tpl -->

<ul class="comments-list{if $srrCanAddReview || $config.srr_rating_module} mt-5{/if}">
    {foreach from=$srrComments item='comment'}
        <li class="mb-4">
            <div class="d-flex align-items-center flex-wrap">
                <div class="mr-3">
                    {if $comment.Author_ID}
                        {strip}
                        {if $comment.Author.Personal_address && $comment.Author.Status === 'active'}
                            <a href="{$comment.Author.Personal_address}">
                        {/if}
                        {$comment.Author.Full_name}
                        {if $comment.Author.Personal_address && $comment.Author.Status === 'active'}
                            </a>
                        {/if}
                        {/strip}
                    {else}
                        {$comment.Author}
                    {/if}
                </div>
                <div class="date">
                    {$comment.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                    {if $config.srr_show_time}
                        {$comment.Date|date_format:'%H:%M'}
                    {/if}
                </div>
            </div>

            <div class="d-flex align-items-center flex-wrap">
                <strong class="{if $config.srr_rating_module && $comment.Rating}mr-3 {/if}mt-1">{$comment.Title}</strong>
                {if $config.srr_rating_module && $comment.Rating}
                    <span class="mt-1">
                        {include file=$smarty.const.SRR_VIEW_PATH|cat:'stars.tpl' srrRating=$comment.Rating}
                    </span>
                {/if}
            </div>
            <div class="mt-1 table-cell">
                <div class="value">{$comment.Description|nl2br}</div>
            </div>
        </li>
    {/foreach}
</ul>

<!-- SellerReviews comments-list tpl end -->
