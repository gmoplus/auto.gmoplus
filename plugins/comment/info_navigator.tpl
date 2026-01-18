{if $featured_listing}
    {assign var='comment_listing' value=$featured_listing}
{else}
    {assign var='comment_listing' value=$listing}
{/if}

{if $comment_listing.comments_count}
    <span class="grid-nav-comment align-top">
        <a title="{$lang.comment_tab}"
            class="d-flex align-items-center"
            {if $config.comments_nav_target}target="_blank"{/if}
            href="{listingUrl listing=$comment_listing}#comments">
            <span class="comment-rating-icon mr-2"></span>
            {$comment_listing.comments_rating}
        </a>
    </span>
{/if}
