<!-- SellerReviews comments controller tpl -->

<div class="content-padding">
    {assign var='srrCanAddReview' value=false}
    {if !$account_info.ID || $account_info.ID != $srrAccountInfo.Account_ID}
        {if !$config.srr_one_comment_only || ($config.srr_one_comment_only && !$srrIsReviewExists)}
            {assign var='srrCanAddReview' value=true}
        {/if}
    {/if}

    {if $srrComments}
        <div class="d-flex flex-wrap">
            {if $config.srr_rating_module}
                <div class="mr-2 flex-fill">
                    <div class="d-flex mb-2 align-items-center flex-wrap">
                        <div class="srr-account-info d-flex flex-fill align-items-center">
                            <h2 class="font-weight-bold">{$srrAccountInfo.Rating}</h2>
                            <div class="ml-2">
                                {include file=$smarty.const.SRR_VIEW_PATH|cat:'stars.tpl' srrRating=$srrAccountInfo.Rating srrStarBig=true}
                            </div>
                        </div>
                    </div>

                    {assign var='hint_replace' value=`$smarty.ldelim`number`$smarty.rdelim`}
                    {assign var='comment_count' value=$pagination.calc}

                    <div class="flex-fill mb-3">
                        {phrase key='srr_comments_number' db_check='true' assign='srr_lang_comments_number'}
                        {$srr_lang_comments_number|replace:$hint_replace:$comment_count}
                    </div>
                </div>
            {/if}

            {if $srrCanAddReview}
                <div>
                    <a class="button low mt-1 ml-auto"
                       id="srr-add-new-comment-button"
                       href="javascript://"
                       onclick="return sellerReviews.showAddCommentForm();"
                    >{$lang.srr_add_comment}</a>
                </div>
            {/if}
        </div>
    {else}
        <h3 class="flex-fill mb-2 text-center">{phrase key='srr_comments_empty' db_check='true'}</h3>
    {/if}

    {if $srrComments && $config.srr_rating_module}
        <div class="srr-count-by-stars mt-3">
            {section name='count_by_stars' start='-1' loop=$config.srr_stars_number step='-1'}
                {assign var='i' value=$smarty.section.count_by_stars.index+1}
                <a href="javascript:" class="stars-item-row" data-rating="{$i}">
                    <div class="d-flex align-items-center mt-1 mb-1">
                        {include file=$smarty.const.SRR_VIEW_PATH|cat:'stars.tpl' srrRating=$config.srr_stars_number srrActiveStars=$i}
                        <span class="srr-count-by-stars__total ml-3 mr-3 w-50 d-flex">
                            {if $srrCountByRatings.$i}
                                {math assign='srrActivePercent' equation='round(100 * active / total, 2)' total=$srrAccountInfo.Comments_Count active=$srrCountByRatings.$i}
                                <span class="srr-count-by-stars__active" style="width: {$srrActivePercent}%;"></span>
                            {/if}
                        </span>
                        <span class="srr-count-by-stars__count">{if $srrCountByRatings.$i}{$srrCountByRatings.$i}{else}0{/if}</span>
                    </div>
                </a>
            {/section}
        </div>
    {/if}

    {if $srrComments}
        {include file=$smarty.const.SRR_VIEW_PATH|cat:'comments-list.tpl'}
    {/if}
</div>

<!-- SellerReviews comments controller tpl end -->
