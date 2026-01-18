{if $smarty.get.nvar_1 == 'unsubscribe'}
    {if $subscribe_info && !$unsubscribed}
        <div class="text-notice">
            {$lang.massmailer_newsletter_title_for_unsubscribe}
        </div>

        <form method="post" id="unsubscribe">
            <input type="hidden" name="action" value="unsubscribe" />
            <input type="submit" value="{$lang.massmailer_newsletter_unsubscribe}" />
        </form>
    {elseif $unsubscribed == true}
        {if !empty($lang.email_site_name)}
            {assign var='sitename_key' value='email_site_name'}
        {else}
            {assign var='sitename_key' value='pages+title+home'}
        {/if}
        <div class="text-notice">
            {$lang.massmailer_newsletter_person_unsubscibed|replace:'[sitename]':$lang.$sitename_key}
        </div>
    {else}
        <div class="text-notice">
            {$lang.massmailer_newsletter_title_for_unsubscribtion_doesnt_exist|replace:'[site_main_email]':$config.site_main_email}
        </div>
    {/if}
{else}
    {if $subscriber}
        {if $subscriber.Status == 'active'}
            {assign var='sitename_key' value='pages+title+home'}

            <div class="text-notice">
                {$lang.massmailer_newsletter_person_already_subscribed|replace:'[sitename]':$lang.$sitename_key}
            </div>
        {else}
            <div class="text-notice">
                {$lang.massmailer_newsletter_title_for_subscribtion_active|replace:'[name]':$subscriber.Name}
            </div>
        {/if}
    {else}
        <div class="text-notice">
            {$lang.massmailer_newsletter_title_for_subscribtion_doesnt_exist|replace:'[site_main_email]':$config.site_main_email}
        </div>
    {/if}
{/if}

<script class="fl-js-dynamic">
    var massmailer_confirm_message = '{$lang.massmailer_newsletter_are_you_sure_unsubscribe}';
    {literal}
    $(document).ready(function() {
        $('form#unsubscribe input[type="button"].remove').click(function(){
            form_submit();
        })
    })
    function form_submit() {
        if (confirm(massmailer_confirm_message)) {
            $('form#unsubscribe').submit();
        }
    }{/literal}
</script>
