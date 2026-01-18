<!-- label selectors box -->
{if $listing.Status == 'active'}
    {assign var="label_array" value=','|explode:$listing.Sub_status_data}
    <li class="single-inline">
        {if $listing.Plan_type == 'account'}
            {assign var="statuses" value=$membership_statuses}
        {else}
            {assign var="statuses" value=$status}
        {/if}
        
        {assign var="ls_array" value=","|explode:$listing.Sub_status}
        {foreach from=$statuses item='item'}
            {if $item.Key|in_array:$ls_array}
                {assign var="ls_default" value=$item.Key}
                {break}
            {/if}
        {/foreach}
        
    	<select id="status_selector_{$listing.ID}" data-id="{$listing.ID}" title="{$lang.ls_substatus}" class="hint selector">
    		{foreach from=$statuses item='item'}
    			{if $item.Type == 'all'}
    				<option value="{$item.Key}" {if $item.Key|in_array:$label_array}selected="selected"{/if}>{$item.name}</option>
    			{elseif $listing_type.Type|in_array:$item.Type}
    				<option value="{$item.Key}" {if $item.Key|in_array:$label_array}selected="selected"{/if}>{$item.name}</option>
    			{/if}
    		{/foreach}
    	</select>
    </li>

    {if $listing.Main_photo && $config.ls_verify_module && !'verified'|in_array:$label_array}
        {assign var="type_array" value=','|explode:$lb_statuses.verified.Type}
        <li class="nav-icon verified{if !$listing.Listing_type|in_array:$type_array} hide{/if}">
            <a href="{pageUrl key='verify_photos' vars='id='|cat:$listing.ID}"><span>
                {if 'wait'|in_array:$label_array}{$lang.ls_pending_verification}{else}{$lang.ls_verify_photos}{/if}
            </span>&nbsp;</a>
        </li>
    {/if}
{/if}
<!-- label selectors box end -->
