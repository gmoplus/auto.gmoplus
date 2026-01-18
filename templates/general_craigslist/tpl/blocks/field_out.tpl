<!-- field output tpl -->

<div class="{strip}table-cell{if $grid_row} col-12{/if}
        {if $full_width} full-width{/if}
        {if $group.Key == 'common'}
            {if !$small} col-xl-6 col-sm-6{/if}
        {/if}
        {if $small} small{/if}
        {if ($item.Type == 'checkbox' && $item.Opt1) || $item.Type == 'textarea'} wide-field
            {if $item.Type == 'textarea'} textarea{/if}
        {/if}
        {if $item.Type == 'phone'} phone{/if}{/strip}" 
    id="df_field_{$item.Key}">
	{if $item.Type == 'image' && $small}{else}
		<div class="name" title="{$item.name}"><span>{if !$small}{$item.name}{else}{if $item.name}{$item.name}{else}{$lang[$item.pName]}{/if}{/if}</span></div>
	{/if}
	<div class="value{if $item.Type == 'image'} image{/if}">
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out_value.tpl'}
	</div>
</div>

<!-- field output tpl end -->
