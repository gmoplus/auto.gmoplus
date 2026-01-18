<!-- newsletter block -->

{assign var='narrow_box' value=false}
{if $block.Side == 'middle_left' || $block.Side == 'middle_right'}
    {assign var='narrow_box' value=true}
{/if}

<div class="subscribe{if is_array($block)} row {if $block.Side == 'left'}pl-md-2 pr-md-2 pl-lg-0 pr-lg-0{else}light-inputs pl-md-2 pr-md-2{/if}{/if}">
    <div class="{if is_array($block)}{if $narrow_box}col-12 mb-3{elseif $block.Side == 'left'}col-md-4 col-lg-12 mb-3 mb-md-0 mb-lg-3 pl-md-2 pr-md-2{else}col-md-4 mb-3 mb-md-0 pl-md-2 pr-md-2{/if}{else}submit-cell{/if}">
        <input placeholder="{$lang.massmailer_newsletter_your_name}" type="text" class="newsletter_name w-100" maxlength="50" />
    </div>
    <div class="{if is_array($block)}{if $narrow_box}col-12 mb-3{elseif $block.Side == 'left'}col-md-4 col-lg-12 mb-3 mb-md-0 mb-lg-3 pl-md-2 pr-md-2{else}col-md-4 mb-3 mb-md-0 pl-md-2 pr-md-2{/if}{else}submit-cell flex-fill{/if}">
        <input placeholder="{$lang.massmailer_newsletter_your_e_mail}"
            type="text"
            class="newsletter_email w-100"
            maxlength="100" />
    </div>
    <div{if is_array($block)} class="{if $narrow_box}col-12{elseif $block.Side == 'left'}col-md-4 col-lg-12 pl-md-2 pr-md-2{else}col-md-4 pl-md-2 pr-md-2{/if}"{/if}>
        <input class="button subscribe_user w-100"
            type="button"
            value="{$lang.massmailer_newsletter_subscribe}"
            data-default-val="{$lang.massmailer_newsletter_subscribe}" />
    </div>
</div>

<script class="fl-js-dynamic">
    lang['massmailer_newsletter_no_response'] = '{$lang.massmailer_newsletter_no_response}';
    lang['massmailer_newsletter_guest'] = '{$lang.massmailer_newsletter_guest}';
</script>

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'massmailer_newsletter/static/mailler.js'}

<script type="text/javascript">
    {literal}
        $(document).ready(function(){
            $parent = $('.massmailer_newsletter');
            $button = $parent.find('.subscribe_user');
            var $name   = $parent.find('.newsletter_name');
            var $email  = $parent.find('.newsletter_email');
            newsletterAction($button, $email, $name, false);
        });
    {/literal}
</script>

<!-- newsletter block end -->
