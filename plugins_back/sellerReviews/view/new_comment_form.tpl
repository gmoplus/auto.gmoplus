<!-- SellerReviews new comment form tpl -->

{if !$account_info.ID || $account_info.ID != $srrAccountInfo.Account_ID}
    <div class="mx-auto d-none" id="srr-add-new-comment-form">
        {include file='blocks/fieldset_header.tpl' name=$lang.srr_add_comment hide=false}

        <form name="srr_add_comment" action="" method="post" class="w-100" onsubmit="return sellerReviews.addNewComment($(this));">
            <div class="comments-popup-errors mb-3 d-none notice"></div>

            <div class="submit-cell">
                <div class="field">
                    <input type="text" id="srr_author" maxlength="30" class="w-100"
                        placeholder="{$lang.srr_author} *"
                        value="{if $isLogin}{$isLogin}{/if}"
                    />
                </div>
            </div>

            <div class="submit-cell">
                <div class="field">
                    <input type="text" id="srr_title" maxlength="60" class="w-100" placeholder="{$lang.srr_title} *" />
                </div>
            </div>

            {if $config.srr_rating_module}
                <div class="submit-cell">
                    {assign var='replace' value=`$smarty.ldelim`stars`$smarty.rdelim`}
                    <div class="d-flex">
                    {section name='stars' start=1 loop=$config.srr_stars_number+1}
                        <span title="{$lang.srr_set|replace:$replace:$smarty.section.stars.iteration}"
                              id="srr_star_{$smarty.section.stars.iteration}"
                              class="srr-star srr-star-add"
                        ></span>
                    {/section}
                    </div>
                </div>
            {/if}

            <div class="submit-cell">
                <div class="field">
                    <textarea class="text" id="srr_message" rows="5" placeholder="{$lang.description} *"></textarea>
                </div>
            </div>

            {if $config.srr_captcha}
                <div class="submit-cell">
                    <div class="field">
                        {include file='captcha.tpl' no_caption=true captcha_id='srr'}
                    </div>
                </div>
            {/if}

            <div class="submit-cell buttons">
                <div class="field">
                    <input class="w-100" type="submit" value="{$lang.srr_add_comment}" />
                </div>
            </div>
        </form>

        {include file='blocks/fieldset_footer.tpl'}
    </div>

    <script class="fl-js-dynamic">{literal}
        $(function () {
            $('#srr-add-new-comment-form #srr_message').textareaCount({
                'maxCharacterSize': srrConfigs.maxSymbolsInMessage,
                'warningNumber': 20
            });
        })
    {/literal}</script>
{/if}

<!-- SellerReviews new comment form tpl end -->
