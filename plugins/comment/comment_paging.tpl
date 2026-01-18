<!-- comments pagination tpl -->

{if $comment_pages > 1}
    <ul class="pagination mb-4" id="comment_paging">
        {if $comment_page > 1}
            <li title="{$lang.prev_page}" class="navigator ls icon prev">
                <a accesskey="{$comment_page-1}" class="button" href="javascript://">&lsaquo;</a>
            </li>
        {/if}
        <li class="transit">
            <input type="text" name="fix-height" class="comments-pagination-fix m-0 pl-0 pr-0 invisible" />
            <span>{$lang.page} {$comment_page} {$lang.of} {$comment_pages}</span>
        </li>

        {if $comment_page != $comment_pages}
            <li title="{$lang.next_page}" class="navigator rs icon next">
                <a accesskey="{$comment_page+1}" class="button" href="javascript://">&rsaquo;</a>
            </li>
        {/if}
    </ul>
{/if}

<!-- comments pagination tpl end -->
