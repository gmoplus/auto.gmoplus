<!-- bookmarks floating bar -->

{if $button_size == 'medium'}
    {assign var='icon_size' value=32}
{elseif $button_size == 'small'}
    {assign var='icon_size' value=24}
{elseif $button_size == 'large'}
    {assign var='icon_size' value=42}
{/if}

<div class="bs-floating">
    <div class="a2a_kit a2a_kit_size_{$icon_size} a2a_floating_style a2a_vertical_style a2a_barsize_{$button_size} a2a_bartheme_{$theme}">
        {foreach from=','|explode:$services item='service'}
        {if $service == 'dd'}
            <a class="a2a_dd{if $counter} a2a_counter{/if}" href="https://www.addtoany.com/share"></a>
        {else}
            <a class="a2a_button_{$service}{if $counter} a2a_counter{/if}"></a>
        {/if}
        {/foreach}
    </div>
</div>

{if ($pageInfo.Controller_alt == 'listing_details' || ($pageInfo.Controller == 'add_listing' && $manageListing->step == 'preview'))
    && !$is_owner
    && $config.show_call_owner_button && $allow_contacts
}
<script>
{literal}

$(function(){
    let callOwnerHeight = $('.contact-owner-navbar').height();
    $('head').append('<style>@media screen and (max-width: 625px) {.a2a_floating_style {bottom: ' + callOwnerHeight + 'px;!important}}</style>');
});

{/literal}
</script>
{/if}

<!-- bookmarks floating bar end -->
