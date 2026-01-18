{if $listing_info.Status == 'active'}
    <tr>
    	<td class="name">{$lang.ls_substatus}:</td>
    	<td class="value" id="status_bar">
    		<select class="selector" >
    			
    			{if $listing_info.Plan_type == 'account'}
                    {assign var="statuses" value=$membership_statuses}
                {else}
                    {assign var="statuses" value=$status}
                {/if}

    			{foreach from=$statuses item='item'}
    				{if $item.Type == 'all'}
    					<option value="{$item.Key}" {if $listing_info.Sub_status == $item.Key}selected="selected"{/if}>{$item.name}</option>
    				{elseif $listing_type.Type|in_array:$item.Type}
    					<option value="{$item.Key}" {if $listing_info.Sub_status == $item.Key}selected="selected"{/if}>{$item.name}</option>
    				{/if}
    			{/foreach}
    		</select>
    	<td>
    </tr>
    {if $status_admin}
        <tr>
            <td class="name">{$lang.ls_substatus_admin}:</td>
            <td class="value" id="admin_status_bar">
                <span class="labels">{if $listing_info.Sub_status_data_name}{$listing_info.Sub_status_data_name}{else}{$lang.ls_no_labels}{/if}</span>
                <span class="change-admin-label link" style="color: #8a27e2; cursor: pointer;">{$lang.change}</span>

                <div id="admin_labels" class="hide">
                    <div>
                        <ul>
                        {foreach from=$status_admin item='item'}
                            {assign var="pos_key" value='ls_position_'|cat:$item.Position}
                            <li>
                                <label><input {if $listing_info.Sub_status_data_val && $item.Key|in_array:$listing_info.Sub_status_data_val}checked="checked"{/if} type="checkbox" name="label" value="{$item.Key}" /> {$item.name} ({$lang.$pos_key})</label>
                            </li>
                        {/foreach}
                        </ul>
                        <input class="apply_label" type="button" name="ok" value="{$lang.apply}">
                    </div>
                </div>
            </td>
        </tr>
    {/if}

    <script type="text/javascript">
        var listing_id = {$listing_info.ID};
        {literal}
        $(document).ready(function(){ 
            $('body').on('click', '.apply_label', function(){ 
                var array = $.map($(".modal-wrapper input[type='checkbox']:checked"), function(c){return c.value; });

                $.getJSON(rlConfig['ajax_url'], {mode: 'listing_admin_status', item: 'listing_admin_status', id: listing_id, status: array, admin: 'true'}, function(response) {
                    if (response.status == 'ok') {
                        $('#admin_status_bar span.labels').html(response.labels);
                        $('body').trigger('click');
                        printMessage('notice', response.message);
                    }
                    else {
                        printMessage('error', response.message);
                    }
                }, 'json');
            });

            $('.change-admin-label').flModal({
                width: 'auto',
                height: 'auto',
                caption: {/literal}'{$lang.ls_substatus_admin}'{literal},
                content: '<div id="tmpAdminLabels" style="min-width:200px;"></div>',
                onClose: function(){
                    $('#admin_labels').append( $("#tmpAdminLabels > div"));
                },
                onReady: function(){
                    $("#tmpAdminLabels").append($('#admin_labels > div'));
                }
            });

            $('#status_bar select.selector').change(function(){ 
                
                $.getJSON(rlConfig['ajax_url'], {mode: 'listing_status', item: 'listing_status', id: listing_id, status: $(this).val()}, function(response) {
                    if (response.status == 'ok') {
                        printMessage('notice', response.message);
                    }
                    else {
                        printMessage('error', response.message);
                    }
                }, 'json');
            }); 
        });
    {/literal}
</script>
{/if}
