<div class="d-flex link comments-link-to-source align-items-center" title="{$lang.comment_rating} {$listing_data.comments_rating}">
    {if $config.comments_rating_module}
        {assign var='comments_ceil' value=$listing_data.comments_rating|ceil}
        {math assign='star_width' equation='(ceil-(rating-1))*100' rating=$comments_ceil ceil=$listing_data.comments_rating}

        <div class="d-flex mr-2">
            {section name='stars' start=1 loop=$comments_ceil+1}
                <span class="comment-star{if $smarty.section.stars.last && $comments_ceil != $listing_data.comments_rating}-gray d-flex{/if} comment-star-info">
                    {if $smarty.section.stars.last && $comments_ceil != $listing_data.comments_rating}
                        <span class="comment-star comment-star-info" style="width: {$star_width}%;"></span>
                    {/if}
                </span>
            {/section}
        </div>
    {/if}

    {assign var='replace_count' value=`$smarty.ldelim`number`$smarty.rdelim`}
    {$lang.comments_number|replace:$replace_count:$listing_data.comments_count}
</div>
