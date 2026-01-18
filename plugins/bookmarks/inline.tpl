<!-- bookmarks inline bar -->

{if $button_size == 'medium'}
    {assign var='icon_size' value=32}
{elseif $button_size == 'small'}
    {assign var='icon_size' value=24}
{elseif $button_size == 'large'}
    {assign var='icon_size' value=42}
{/if}

{assign var='parent_tag' value='div'}
{if $key == 'bookmark_details'}
    {assign var='parent_tag' value='li'}
{/if}

{if $key == 'bookmark_done_step'}
<div class="mt-3 mb-3">
    {$lang.or}

    <div class="mt-3">
        {$lang.bookmarks_step_done_text}
    </div>
</div>
{/if}

<{$parent_tag} class="text-{$align}">
    <div class="a2a_kit a2a_kit_size_{$icon_size} a2a_default_style d-inline-block a2a_barsize_{$button_size} a2a_bartheme_{$theme}"
        {if $key == 'bookmark_done_step'}
         data-a2a-url="[listing_url]"
         data-a2a-title="[listing_title]"
        {/if}
        >
        {foreach from=','|explode:$services item='service'}
        {if $service == 'dd'}
            <a class="a2a_dd{if $counter} a2a_counter{/if}" href="https://www.addtoany.com/share"></a>
        {else}
            <a class="a2a_button_{$service}{if $counter} a2a_counter{/if}"></a>
        {/if}
        {/foreach}
    </div>
</{$parent_tag}>

<!-- bookmarks inline bar end -->
