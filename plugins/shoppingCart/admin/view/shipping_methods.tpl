<!-- payment gateways tpl -->

{if $smarty.get.action}
	{assign var='sPost' value=$smarty.post}
	
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
	<form action="{$rlBaseC}module=shipping_methods&action=edit&item={$smarty.get.item}" method="post">
		<input type="hidden" name="submit" value="1" />

		{if $smarty.get.action == 'edit'}
			<input type="hidden" name="fromPost" value="1" />
		{/if}
		
		<table class="form">
			<tr>
				<td class="name">
					<span class="red">*</span>{$lang.name}
				</td>
				<td>
					{if $allLangs|@count > 1}
						<ul class="tabs">
							{foreach from=$allLangs item='language' name='langF'}
							<li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
							{/foreach}
						</ul>
					{/if}
					
					{foreach from=$allLangs item='language' name='langF'}
						{if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
						<input type="text" name="f[name][{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
						{if $allLangs|@count > 1}
								<span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
							</div>
						{/if}
					{/foreach}
				</td>
			</tr>
		
			{foreach from=$methodSettings item=sItem}
				{if $sItem.type == 'text' || $sItem.type == 'textarea' || $sItem.type == 'bool' || $sItem.type == 'select' || $sItem.type == 'radio'}
				<tr>
					{assign var='shippingOptionName' value="shc_"|cat:$smarty.get.item|cat:"+name+"|cat:$sItem.key}
					<td class="name">{if $sItem.name}{$sItem.name}{elseif $sItem.key == 'password'}{$lang.password}{else}{$lang[$shippingOptionName]}{/if}</td>
					<td class="field">
						<div class="inner_margin">
							{if $sItem.type == 'text'}
								<input name="f[settings][{$sItem.key}]" class="{if $sItem.type == 'int'}numeric{/if}" type="text" value="{if $sPost.f.settings[$sItem.key]}{$sPost.f.settings[$sItem.key]}{else}{$sItem.value}{/if}" maxlength="255" />
							{elseif $sItem.type == 'bool'}
								<label><input type="radio" {if $sItem.value == 1}checked="checked"{/if} name="f[settings][{$sItem.key}]" value="1" /> {$lang.enabled}</label>
								<label><input type="radio" {if $sItem.value == 0}checked="checked"{/if} name="f[settings][{$sItem.key}]" value="0" /> {$lang.disabled}</label>
							{elseif $sItem.type == 'textarea'}
								<textarea cols="5" rows="5" name="f[settings][{$sItem.key}]">{if $sPost.f.settings[$sItem.key]}{$sPost.f.settings[$sItem.key]}{else}{$sItem.value}{/if}</textarea>
							{elseif $sItem.type == 'select'}
								<select style="width: 204px;" name="f[settings][{$sItem.key}]" {if $sItem.items|@count < 2} class="disabled" disabled="disabled"{/if}>
									{if $sItem.items|@count > 1}
										<option value="">{$lang.select}</option>
									{/if}
									{foreach from=$sItem.items item='sValue' key='sKey' name='sForeach'}
										<option value="{if is_array($sValue)}{$sValue.ID}{else}{$sKey}{/if}" {if is_array($sValue)}{if $sItem.value == $sValue.ID || $sPost.f.settings[$sItem.key] == $sValue.ID}selected="selected"{/if}{else}{if $sKey == $sItem.value}selected="selected"{/if}{/if}>{if is_array($sValue)}{$sValue.name}{else}{$sValue}{/if}</option>
									{/foreach}
								</select>
							{elseif $sItem.type == 'radio'}
								{foreach from=$sItem.items item='rValue' name='rForeach' key='rKey'}
									<input id="radio_{$sItem.key}_{$rKey}" {if $rKey == $sItem.value}checked="checked"{/if} type="radio" value="{$rKey}" name="f[settings][{$sItem.key}][value]" /><label for="radio_{$sItem.key}_{$rKey}">&nbsp;{$rValue}&nbsp;&nbsp;</label>
								{/foreach}
							{else}
								{$sItem.value}
							{/if}
						</div>
					</td>
				</tr>
				{/if}
			{/foreach}
	        <tr>
	            <td class="name">{$lang.shc_test_mode}</td>
	            <td class="field">
	                {if $sPost.test_mode == '1'}
	                    {assign var='test_mode_yes' value='checked="checked"'}
	                {elseif $sPost.test_mode == '0'}
	                    {assign var='test_mode_no' value='checked="checked"'}
	                {else}
	                    {assign var='test_mode_no' value='checked="checked"'}
	                {/if}
	                <label><input {$test_mode_yes} class="lang_add" type="radio" name="f[test_mode]" value="1" /> {$lang.yes}</label>
	                <label><input {$test_mode_no} class="lang_add" type="radio" name="f[test_mode]" value="0" /> {$lang.no}</label>
	            </td>
	        </tr>
			<tr>
				<td class="name">{$lang.status}</td>
				<td class="field">
					<select name="f[status]">
						<option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
						<option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td class="field">
					<input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.save}{else}{$lang.add}{/if}" />
				</td>
			</tr>
		</table>
	</form>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{else}
	<!-- shipping methods grid -->
	<div id="grid"></div>
	<script type="text/javascript">
	var shippingMethodsGrid;

	{literal}
	$(document).ready(function(){		
		shippingMethodsGrid = new gridObj({
			key: 'shipping_methods',
			id: 'grid',
            ajaxUrl: rlPlugins + 'shoppingCart/admin/shopping_cart.inc.php?q=ext_shipping_methods',
			defaultSortField: 'name',
			remoteSortable: true,
			checkbox: false,
			actions: [
				[lang['ext_delete'], 'delete']
			],
			title: lang['ext_shipping_methods_manager'],

			fields: [
				{name: 'ID', mapping: 'ID', type: 'int'},
				{name: 'name', mapping: 'name'},
				{name: 'Key', mapping: 'Key'},
				{name: 'Status', mapping: 'Status', type: 'string'},
				{name: 'Type', mapping: 'Type'}
			],
			columns: [
				{
					header: lang['ext_id'],
					dataIndex: 'ID',
					width: 3,
					id: 'rlExt_black_bold'
				},{
					header: lang['ext_name'],
					dataIndex: 'name',
					width: 20,
					id: 'rlExt_item_bold'
				},{
					header: lang['ext_type'],
					dataIndex: 'Type',
					width: 15
				},{
					header: lang['ext_status'],
					dataIndex: 'Status',
					width: 100,
					fixed: true,
					editor: new Ext.form.ComboBox({
						store: [
							['active', lang['ext_active']],
							['approval', lang['ext_approval']]
						],
						displayField: 'value',
						valueField: 'key',
						typeAhead: true,
						mode: 'local',
						triggerAction: 'all',
						selectOnFocus:true
					}),
					renderer: function(val){
						return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
					}
				},{
					header: lang['ext_actions'],
					width: 50,
					fixed: true,
					dataIndex: 'Key',
					sortable: false,
					renderer: function(data) {
                        var out = "<img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='";
                        out += rlUrlHome + "img/blank.gif' onClick='location.href=\"";
                        out += rlUrlHome + "index.php?controller=" + controller + "&module=shipping_methods&action=edit&item=";
                        out += data + "\"' />";

                        return out;
					}
				}
			]
		});
		
		shippingMethodsGrid.init();
		grid.push(shippingMethodsGrid.grid);
		
	});
	{/literal}
	</script>
	<!-- shipping methods grid end -->
{/if}

<!-- payment gateways tpl end -->
