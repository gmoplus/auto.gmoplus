{if $pageInfo.Key == 'view_details' || $blocks.comments_block}
<style>
{literal}
.comment-star {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23ff8970'%3E%3Cpath d='M7.952.656a.226.226 0 00-.21.13l-2.22 4.738-5.112.688a.23.23 0 00-.125.393l3.737 3.617-.937 5.163a.234.234 0 00.09.227c.07.052.163.058.24.016l4.531-2.503 4.532 2.503c.077.042.17.036.24-.016a.233.233 0 00.09-.227l-.938-5.163 3.738-3.617a.228.228 0 00-.126-.393l-5.11-.688L8.148.786a.222.222 0 00-.197-.13z'/%3E%3C/svg%3E");
}
{/literal}
</style>
{/if}
{if $pageInfo.Key == 'view_details'}
<style>
{literal}
.comment-star-gray {
    background-image: url("data:image/svg+xml;charset=utf-8,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 16 16' fill='%23e8e8e8'%3E%3Cpath d='M7.952.656a.226.226 0 00-.21.13l-2.22 4.738-5.112.688a.23.23 0 00-.125.393l3.737 3.617-.937 5.163a.234.234 0 00.09.227c.07.052.163.058.24.016l4.531-2.503 4.532 2.503c.077.042.17.036.24-.016a.233.233 0 00.09-.227l-.938-5.163 3.738-3.617a.228.228 0 00-.126-.393l-5.11-.688L8.148.786a.222.222 0 00-.197-.13z'/%3E%3C/svg%3E");
}
.comment-star-gray .comment-star {
    background-size: cover;
}
{/literal}
</style>
{/if}

{if $tpl_settings.name == 'general_cragslist_wide'}
<style>
{literal}

ul.pagination > li > a {
    background: none !important;
    padding: 0;
    line-height: inherit;
    height: inherit;
}
/**
 * @todo - Remove next style once the plugin compatibility > 4.8.2
 */
.comments-list .table-cell .value {
    word-wrap: break-word;
    overflow: hidden;
}

{/literal}
</style>
{/if}

{if $pageInfo.Key == 'view_details'}
<style>
{literal}

/* Comments plugin css styles */
.form_add_comment {
    max-width: 350px;
}
.comments-pagination-fix {
    width: 0 !important;
    min-width: 0 !important;
    border: 0 !important;
}
#comment_paging,
#comment_paging .transit {
    height: auto;
}
.comment-star-page {
    width: 16px;
    height: 16px;
}
.comment-star-info {
    width: 20px;
    height: 20px;
}
#comment_security_code ~ span {
    display: none;
}
.comment-star-add {
    width: 30px;
    height: 30px;
    opacity: .5;
    cursor: pointer;
}
.comment-star-add_active {
    opacity: 1;
}

{/literal}
</style>
{/if}

{if $blocks.comments_block}
<style>
{literal}

/* Latest comments box styles */
.comment-star-box {
    width: 12px;
    height: 12px;
}
.latest-comments-text {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
}

{/literal}
</style>
{/if}

{if $config.comments_login_access && !$isLogin && $pageInfo.Key == 'view_details'}
<script>
{literal}

$(function(){
    if (flynax.getHash() == 'comments') {
        printMessage('warning', lang.comment_login_to_see_comments);
    }
    $('a[href="#comments"]').click(function(){
        printMessage('warning', lang.comment_login_to_see_comments);
    });
});

{/literal}
</script>
{/if}
