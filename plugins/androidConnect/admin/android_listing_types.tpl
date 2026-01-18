<!-- android listing types tpl -->

{if $smarty.get.action == 'edit'}

	{assign var='sPost' value=$smarty.post}

	<!-- edit type -->
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
	<form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;key={$smarty.get.key}{/if}" method="post">
		<input type="hidden" name="submit" value="1" />
	
		<input type="hidden" name="fromPost" value="1" />
	
		<table class="form">
		<tr>
			<td class="name">{$lang.name}</td>
			<td class="field"><b>{$type_info.name}</b></td>
		</tr>
		<tr>
			<td class="name">{$lang.android_type_icon}</td>
			<td class="field">
				{foreach from=$icons item='icon'}
					<div style="display: inline-block;*display: inline;*zoom: 1;text-align: center;">
						<label>
							<div style="background: #43494a;width: 42px;height: 34px;border-radius: 3px;padding-top: 8px;margin-bottom: 4px;">
								<img src="{$smarty.const.RL_PLUGINS_URL}androidConnect/static/icon_{$icon}.png" />
							</div>
							<div><input value="{$icon}" {if $sPost.icon == $icon}checked="checked"{/if} type="radio" name="icon" /></div>
						</label>
					</div>
				{/foreach}
			</td>
		</tr>
		<tr>
			<td class="name"><span class="red">*</span>{$lang.status}</td>
			<td class="field">
				<select name="status">
					<option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
					<option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
				</select>
			</td>
		</tr>
		<tr>
			<td></td>
			<td class="field">
				<input class="button" type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
			</td>
		</tr>
		</table>
	</form>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
	<!-- edit type end -->
	
{else}

	<!-- listing types grid -->
	<div id="grid"></div>
	<script type="text/javascript">//<![CDATA[
	var listingTypesGrid;
	
	{literal}
	$(document).ready(function(){
		
		listingTypesGrid = new gridObj({
			key: 'listingTypes',
			id: 'grid',
			ajaxUrl: rlPlugins + 'androidConnect/admin/android_listing_types.inc.php?q=ext',
			defaultSortField: 'name',
			title: lang['ext_listing_types_manager'],
			fields: [
				{name: 'name', mapping: 'name', type: 'string'},
				{name: 'Android_position', mapping: 'Android_position', type: 'int'},
				{name: 'Status', mapping: 'Android_status'},
				{name: 'Key', mapping: 'Key'}
			],
			columns: [
				{
					header: lang['ext_name'],
					dataIndex: 'name',
					width: 50,
					id: 'rlExt_item_bold'
				},{
					header: lang['ext_position'],
					dataIndex: 'Android_position',
					width: 10,
					editor: new Ext.form.NumberField({
						allowBlank: false,
						allowDecimals: false
					}),
					renderer: function(val){
						return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
					}
				},{
					header: lang['ext_status'],
					dataIndex: 'Status',
					fixed: true,
					width: 100,
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
					})
				},{
					header: lang['ext_actions'],
					width: 70,
					fixed: true,
					dataIndex: 'Key',
					sortable: false,
					renderer: function(data, ext, row) {
						var out = "<center>";
						out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&key="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
						out += "</center>";
						
						return out;
					}
				}
			]
		});
		
		{/literal}{rlHook name='apTplListingTypesGrid'}{literal}
		
		listingTypesGrid.init();
		grid.push(listingTypesGrid.grid);
		
	});
	{/literal}
	//]]>
	</script>
	<!-- listing types grid end -->
	
{/if}

<!-- android listing types tpl end -->