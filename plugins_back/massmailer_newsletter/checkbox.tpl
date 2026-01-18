<!-- subscribe checkbox -->

<label style="padding: 10px 0 5px;display: block;">
    {assign var='mm_checked' value=''}
    {if $smarty.post.profile}
        {if $smarty.post.profile.mn_subscribe}
            {assign var='mm_checked' value='checked="checked"'}
        {/if}
    {else}
        {if $config.mn_default_value}
            {assign var='mm_checked' value='checked="checked"'}
        {/if}
    {/if}

    <input value="1" type="checkbox" name="profile[mn_subscribe]" {$mm_checked}/>
    &nbsp;{$lang.massmailer_newsletter_subscribe_to}
</label>

<!-- subscribe checkbox end -->

