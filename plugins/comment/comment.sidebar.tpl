<div>
    {if $block_comments}
        <ul>
        {foreach from=$block_comments item='block_comment' name='commentF'}
            <li class="mb-3">
                <div class="d-flex align-items-baseline">
                    <a class="mr-2" title="{$block_comment.Listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$block_comment.Listing_link}">
                        {$block_comment.Comment_title}
                    </a>
                    {if $config.comments_rating_module && $block_comment.Comment_rating}
                        <div class="d-flex mt-1 ml-auto ml-md-0 ml-lg-auto">
                            {section name='stars' start=1 loop=$block_comment.Comment_rating+1}<span class="comment-star comment-star-box"></span>{/section}
                        </div>
                    {/if}
                </div>
                <div class="latest-comments-text">{$block_comment.Comment_description}</div>
            </li>
        {/foreach}
        </ul>
    {else}
        <div class="info">{$lang.comment_absent_comments_in_listings}</div>
    {/if}
</div>
