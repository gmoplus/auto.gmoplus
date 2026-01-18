{if !$comment_own_listing_denied}

<div class="form_add_comment mx-auto">
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.comment_add_comment hide=false}

    {if $isLogin || !$config.comments_login_post}
        <form name="add_comment" action="" method="post" class="w-100">
            <div class="comments-popup-errors mb-3 d-none notice"></div>

            <div class="submit-cell">
                <div class="field">
                    <input type="text" id="comment_author" maxlength="30" class="w-100"
                        placeholder="{$lang.comment_author} *"
                        value="{if $isLogin}{$isLogin}{else}{$smarty.post.author}{/if}" />
                </div>
            </div>

            <div class="submit-cell">
                <div class="field">
                    <input type="text" id="comment_title" maxlength="60" class="w-100"
                        placeholder="{$lang.comment_title} *" value="{$smarty.post.title}" />
                </div>
            </div>

            {if $config.comments_rating_module}
            <div class="submit-cell">
                {assign var='replace' value=`$smarty.ldelim`stars`$smarty.rdelim`}
                <div class="d-flex">
                {section name='stars' start=1 loop=$config.comments_stars_number+1}
                    <span title="{$lang.comment_set|replace:$replace:$smarty.section.stars.iteration}"
                          id="comment_star_{$smarty.section.stars.iteration}"
                          class="comment-star comment-star-add"></span>
                {/section}
                </div>
            </div>
            {/if}

            <div class="submit-cell">
                <div class="field">
                    <textarea class="text" id="comment_message" rows="5"
                        placeholder="{$lang.message}">{$smarty.post.message}</textarea>
                </div>
            </div>

            {if $config.security_img_comment_captcha}
                <div class="submit-cell">
                    <div class="field">
                        {include file='captcha.tpl' no_caption=true captcha_id='comment'}
                    </div>
                </div>
            {/if}

            <div class="submit-cell buttons">
                <div class="field"><input class="w-100" type="submit" value="{$lang.comment_add_comment}" /></div>
            </div>
        </form>
    {else}
        <div class="text-notice text-center">{$lang.comment_login_to_post}</div>

        <div class="content-padding w-100 login-page-form mx-auto">
            {include file='menus'|cat:$smarty.const.RL_DS|cat:'account_menu.tpl'}
        </div>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
</div>

{/if}
