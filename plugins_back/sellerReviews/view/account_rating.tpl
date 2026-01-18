<!-- SellerReviews rating of account tpl -->

{assign var='hint_replace' value=`$smarty.ldelim`count`$smarty.rdelim`}

{if $config.rl_version|version_compare:'4.9.0' > 0}
    <li class="account-rating counter d-flex align-items-center flex-wrap{if $pageInfo.Controller === 'listing_details'} mb-2{/if}{if $tpl_settings.name|strpos:'_flatty' !== false} pt-3{/if}">
        {if $config.srr_rating_module && $srrAccountInfo.Rating > 0}
            <span class="srr-account-rating">{$srrAccountInfo.Rating}</span>
        {/if}

        {if $config.srr_rating_module && $srrAccountInfo.Rating > 0}
            {include file=$smarty.const.SRR_VIEW_PATH|cat:'stars.tpl' srrRating=$srrAccountInfo.Rating starsContainerClass='srr-stars ml-2 mr-2'}
        {/if}

        <div class="srr-comments-count">
            {if $srrAccountInfo.Comments_Count == 0}
                {assign var='srrCommentsCountText' value=$lang.srr_add_comment}
            {else}
                {assign var='srrCommentsCountText' value=$lang.srr_comments_count|replace:$hint_replace:$srrAccountInfo.Comments_Count}
            {/if}

            {if $config.srr_display_mode === 'tab' && $srrAccountInfo.Personal_address}
                {assign var='srrCommentsCountLink' value=$srrAccountInfo.Personal_address|cat:'#srr_comments_tab'}
            {elseif $config.srr_display_mode === 'popup'}
                {if $isLogin || !$config.srr_login_access}
                    {assign var='srrCommentsCountLink' value='javascript: sellerReviews.loadCommentsInPopup()'}
                {else}
                    {assign var='srrCommentsCountLink' value='javascript:'}
                {/if}
            {/if}

            <a class="srr-account-comments" href="{$srrCommentsCountLink}">{$srrCommentsCountText}</a>

            <script class="fl-js-dynamic">{literal}
                $(function () {
                    $('a.srr-account-comments').click(function () {
                        if (isLogin || !srrConfigs.loginToAccess) {
                            $('#tab_srr_comments a').trigger('click');
                        } else {
                            printMessage('warning', lang.srr_login_to_see_comments);
                        }
                    })
                })
            {/literal}</script>
        </div>
    </li>
{else}
    {**
     * @todo - Remove this when compatibility will be > 4.9.0
     *}
    <script class="fl-js-dynamic">{literal}
        if (Number(srrConfigs.accountInfo.Comments_Count) === 0) {
            srrConfigs.accountInfo.commentsCountText = '{/literal}{$lang.srr_add_comment}{literal}';
        } else {
            srrConfigs.accountInfo.commentsCountText = '{/literal}{$lang.srr_comments_count|replace:$hint_replace:$srrAccountInfo.Comments_Count}{literal}';
        }

        if (srrConfigs.displayMode === 'tab' && srrConfigs.accountInfo.Personal_address) {
            srrConfigs.accountInfo.commentsCountLink = srrConfigs.accountInfo.Personal_address;
            srrConfigs.accountInfo.commentsCountLink += '#srr_comments_tab';
        } else if (srrConfigs.displayMode === 'popup') {
            if (isLogin || !srrConfigs.loginToAccess) {
                srrConfigs.accountInfo.commentsCountLink = 'javascript: sellerReviews.loadCommentsInPopup()';
            } else {
                srrConfigs.accountInfo.commentsCountLink = 'javascript:';
            }
        }

        sellerReviews.drawAccountRating(srrConfigs.accountInfo);
    {/literal}</script>
{/if}

<!-- SellerReviews rating of account tpl end -->
