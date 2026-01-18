<!-- comments tab -->

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'comment/static/lib.js'}

<div id="area_comments" class="tab_area hide">
    <div class="content-padding">
        <div id="comments_dom">
            <div class="pb-5">
                {if $config.comments_login_access && !$isLogin}
                    {$lang.comment_login_to_see_comments}
                {else}
                    {$lang.loading}
                {/if}
            </div>
        </div>

        {include file=$smarty.const.RL_PLUGINS|cat:'comment/formComment.tpl'}
    </div>
</div>

<script class="fl-js-dynamic">
var commentConfig = [];
var commentObject = false;
commentConfig.listingID = {if $listing_data.ID}{$listing_data.ID}{else}false{/if};
commentConfig.symbolsNumber = {$config.comment_message_symbols_number};
commentConfig.comment_auto_approval = {if $config.comment_auto_approval}true{else}false{/if};
commentConfig.comments_login_access = {if $config.comments_login_access}true{else}false{/if};
commentConfig.comments_login_post = {if $config.comments_login_post}true{else}false{/if};

{literal}
$(function() {
    var commentsInit = function(){
        var allowView = (commentConfig.comments_login_access && isLogin) || !commentConfig.comments_login_access;

        commentObject = new commentClass();
        commentObject.init(
            commentConfig.listingID,
            commentConfig.symbolsNumber,
            'tab',
            commentConfig.comment_auto_approval,
            allowView
        );

        if (allowView) {
            commentObject.getComments(1);
        }
    }

    $('.tabs li#tab_comments').click(function(){
        if (!commentObject) {
            commentsInit();
        }
    });

    if (!commentObject && ['comments_tab', 'comments'].indexOf(flynax.getHash()) >= 0) {
        commentsInit();
        flynax.slideTo('#comments_dom');
    }

    $(window).bind('hashchange', function(){
        if (flynax.getHash() == 'comments') {
            commentsInit();
            flynax.slideTo('#comments_dom');
        }
    });

    var $fieldset = $('.form_add_comment .fieldset');

    $('body').on('click', 'a.add-comment-anchor', function(e){
        flynax.slideTo($fieldset);
        return false;
    });

    if (flynax.getHash() == 'add-comment') {
        flynax.slideTo($fieldset);
    }
});

{/literal}
</script>

<!-- comments tab end -->
