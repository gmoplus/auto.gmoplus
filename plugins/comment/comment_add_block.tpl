{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'comment/static/lib.js'}

<div id="comments_dom">
    {include file=$smarty.const.RL_PLUGINS|cat:'comment/comment_dom.tpl'}
</div>

<div id="comments_add_form"{if $block && $block.Side == 'left'} class="d-block d-lg-none"{/if}>
    {include file=$smarty.const.RL_PLUGINS|cat:'comment/formComment.tpl'}
</div>

{if $block && $block.Side == 'left' && !$comment_own_listing_denied}
<div class="d-none d-lg-block">
    <input type="button" class="btn btn-info w-100" value="{$lang.comment_add_comment}" id="comment_open_popup">
</div>
{/if}

<script class="fl-js-dynamic">
var commentConfig = [];
commentConfig.listingID = {if $listing_data.ID}{$listing_data.ID}{else}false{/if};
commentConfig.symbolsNumber = {$config.comment_message_symbols_number};
commentConfig.comment_auto_approval = {if $config.comment_auto_approval}true{else}false{/if};

{literal}

var commentObject = new commentClass();
commentObject.init(
    commentConfig.listingID,
    commentConfig.symbolsNumber,
    'box',
    commentConfig.comment_auto_approval
);

$(function(){
    $('#comment_open_popup').click(function() {
        flUtil.loadStyle(rlConfig.tpl_base + 'components/popup/popup.css');
        flUtil.loadScript(rlConfig.tpl_base + 'components/popup/_popup.js', function() {
            $('body').popup({
                closeOnOutsideClick: false,
                click: false,
                content: $('#comments_add_form .fieldset .body form'),
                caption: lang.comment_add_comment,
                width: 350,
                onClose: function(popup) {
                    $('#comments_add_form .fieldset .body').append(popup.find('form[name=add_comment]'));
                    this.destroy();
                },
            });
        });
    });
});

{/literal}
</script>
