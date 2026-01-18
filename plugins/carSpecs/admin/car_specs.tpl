<!-- navigation bar -->
<div id="nav_bar">

    {if $smarty.get.service}
        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.cs_service_list}</span><span class="right"></span></a>
    {else}
	   <a href="{$rlBaseC}action=add_service" class="button_bar"><span class="left"></span><span class="center_add">{$lang.cs_add_service}</span><span class="right"></span></a>
    {/if}

    {if $smarty.get.action == 'mapping'}
        <a href="javascript:void(0)" onclick="show('add_mapping_item')" class="button_bar"><span class="left"></span><span class="center_add">{$lang.cs_add_mapping_item}</span><span class="right"></span></a>
        <a href="javascript:void(0)" onclick="cs_action('{$smarty.get.service},cs_testService', this);" class="button_bar"><span class="left"></span><span class="center_import">{$lang.cs_test_service}</span><span class="right"></span></a>
    {/if}
</div>
<!-- navigation bar end -->

{if $info && !$errors}
<script>
	{if $info|@count > 1}
		var info_message = '<ul>{foreach from=$info item="mess"}<li>{$mess}</li>{/foreach}</ul>';
	{else}
		var info_message = '{$info.0}';
	{/if}

	{literal}
	$(document).ready(function(){
		printMessage('info', info_message);
	});
	{/literal}
</script>
{/if}

{if ($smarty.get.action == 'edit_service' && $smarty.get.service) || $smarty.get.action == 'add_service'}
	{assign var='sPost' value=$smarty.post}

	<!-- add/edit -->
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
	<form action="{$rlBaseC}action={$smarty.get.action}{if $smarty.get.service}&amp;service={$smarty.get.service}{/if}" method="post">
		<input type="hidden" name="submit" value="1" />
		{if $smarty.get.action == 'edit_service'}
			<input type="hidden" name="fromPost" value="1" />
		{/if}

		<table class="form">
		<tr>
			<td class="name"><span class="red">*</span>{$lang.cs_service_name}</td>
			<td class="field">
				{if $allLangs|@count > 1}
					<ul class="tabs">
						{foreach from=$allLangs item='language' name='langF'}
						<li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
						{/foreach}
					</ul>
				{/if}

				{foreach from=$allLangs item='language' name='langF'}
					{if $allLangs|@count > 1}
						<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
					{/if}
					<input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" style="width: 250px;" />
					{if $allLangs|@count > 1}
						<span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span></div>
					{/if}
				{/foreach}
			</td>
		</tr>
		<tr>
			<td class="name">{$lang.cs_module}</td>
			<td class="field">
				<select name="module">
					{foreach from=$modules item="module"}
						<option value="{$module}" {if $smarty.get.action == 'edit_service' && $sPost.module == $module}selected="selected"{/if}>{$module}</option>
					{/foreach}
				</select>
			</td>
		</tr>
		<tr>
			<td class="name">{$lang.listing_type}</td>
			<td class="field">
				<select name="listing_type">
					{foreach from=$listing_types item="listing_type"}
						<option value="{$listing_type.Key}" {if $sPost.listing_type == $listing_type.Key}selected="selected"{/if}>{$listing_type.name}</option>
					{/foreach}
				</select>
			</td>
		</tr>

		<tr>
			<td class="name">{$lang.cs_authorization}</td>
			<td class="field">
				<fieldset class="light">
					<legend id="legend_credentials">{$lang.cs_credentials}</legend>
					<table class="form wide">
					<tr>
						<td class="name"><span class="red">*</span> {$lang.cs_auth_login}</td>
						<td class="field">
							<input class="lang_add" type="text" name="login" value="{$sPost.login}" />
						</td>
					</tr>
					<tr>
						<td class="name"><span class="red">*</span> {$lang.cs_auth_password}</td>
						<td class="field">
							<input class="lang_add" type="text" name="pass" value="{$sPost.pass}" />
						</td>
					</tr>
					<tr>
						<td class="name"><span class="red">*</span> {$lang.cs_api_key}</td>
						<td class="field">
							<input class="lang_add" type="text" name="api_key" value="{$sPost.api_key}" />
						</td>
					</tr>
					<tr>
						<td class="name"><span class="red">*</span> {$lang.cs_test_number}</td>
						<td class="field">
							<input class="lang_add" type="text" name="test_number" value="{$sPost.test_number}" />
						</td>
					</tr>
					</table>
				</fieldset>
			</td>
		</tr>
		<tr>
			<td class="name">{$lang.status}</td>
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
				<input type="submit" value="{if $smarty.get.action == 'edit_service'}{$lang.edit}{else}{$lang.add}{/if}" />
			</td>
		</tr>
		</table>
	</form>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

{elseif $smarty.get.action == 'mapping' && $smarty.get.service}

	<!-- add new mapping item -->
	<div id="add_mapping_item" class="hide">
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}
		<table class="form">
		<tr>
			<td class="name">{$lang.cs_remote_field}</td>
			<td class="field">
				<input id="mapping_item_remote" type="text" class="w250" />
			</td>
		</tr>
		<tr>
			<td class="name">{$lang.cs_local_field}</td>
			<td class="field">
				<select id="mapping_item_local" class="w250">
					<option value="0">{$lang.select}</option>
					{if $smarty.get.field}
						{foreach from=$local_values item="local_value"}
							<option value="{$local_value.Key}">{$local_value.name}</option>
						{/foreach}
					{else}
						<optgroup label="{$lang.cs_listingfields_label}">
						{foreach from=$listing_fields item="field"}
							<option value="{$field.Key}" {if $field.Key == $xml_field.fl}selected="selected"{/if} >{$field.name} ( {$field.Type_name} )</option>
						{/foreach}
						</optgroup>
						{if $system_fields}
							<optgroup label="{$lang.cs_sysfields_label}">
							{foreach from=$system_fields item="field"}
								<option value="{$field.Key}" {if $field.Key == $xml_field.fl}selected="selected"{/if} >{$field.name} ( {$field.Type_name} )</option>
							{/foreach}
							</optgroup>
						{/if}
					{/if}
				</select>
			</td>
		</tr>
		<tr>
			<td class="name">{$lang.status}</td>
			<td class="field">
				<select id="ni_status">
					<option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
					<option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
				</select>
			</td>
		</tr>
		{*<tr>
			<td class="name">{$lang.default}</td>
			<td class="field">
				<input type="checkbox" id="ni_default" value="1" />
			</td>
		</tr>*}
		<tr>
			<td></td>
			<td class="field">
				<input type="button" name="item_submit" value="{$lang.add}" />
				<a onclick="$('#add_mapping_item').slideUp('normal')" href="javascript:void(0)" class="cancel">{$lang.close}</a>
			</td>
		</tr>
		</table>
		{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
	</div>

	<script type="text/javascript">
		{literal}
			$(document).ready(function(){
				$('#mapping_item_local').change(function(){
					if( !$('#mapping_item_remote').val() )
					{
						$('#mapping_item_remote').val( $(this).val() );
					}
				});
			});

			$('input[name=item_submit]').click(function(){
				$(this).val( lang['loading'] );

				if( $('#mapping_item_local').val() != 0 && ( $('#mapping_item_remote').val() || $('#mapping_item_default').val() ) )
				{
                    cs_action("add_maping,cs_addMappingItem");
					// xajax_addMappingItem( $('#mapping_item_local').val(), $('#mapping_item_remote').val(), $('#mapping_item_default').val() );
				}else {
					$('input[name=item_submit]').val( lang['add'] );
					printMessage("error", "Fill all fields");
				}
			});
		{/literal}
	</script>

	{if !$smarty.get.field}
		<div id="grid"></div>
		<script type="text/javascript">//<![CDATA[
		{literal}
			var specsMappingGrid;

			$(document).ready(function(){
				specsMappingGrid = new gridObj({
					key: 'xml_mapping',
					id: 'grid',
					ajaxUrl: rlPlugins + 'carSpecs/admin/car_specs.inc.php?q=ext_mapping&service={/literal}{$smarty.get.service}{literal}',
					defaultSortField: 'Data_remote',
					title: lang['ext_cs_mapping_manager'],
					fields: [
						{name: 'ID', mapping: 'ID', type: 'int'},
						{name: 'Data_remote', mapping: 'Data_remote', type: 'string'},
						{name: 'Data_local', mapping: 'Data_local', type: 'string'},
						{name: 'Local_field_name', mapping: 'Local_field_name', type: 'string'},
						{name: 'Local_field_type', mapping: 'Local_field_type', type: 'string'},
						{name: 'Service', mapping: 'Service', type: 'string'},
						{name: 'Service_name', mapping: 'Service_name', type: 'string'},
						{name: 'Example_value', mapping: 'Example_value'},
						{name: 'Cdata', mapping: 'Cdata'},
						{name: 'Mf', mapping: 'Mf'},
						{name: 'Default', mapping: 'Default'},
						{name: 'Status', mapping: 'Status'}
					],
					columns: [{
							header: '{/literal}{$lang.cs_remote_field}{literal}',
							dataIndex: 'Data_remote',
							id: 'rlExt_item_bold',
							width: 20
						},{
							header: '{/literal}{$lang.cs_local_field}{literal}',
							dataIndex: "Local_field_name",
							width: 20,
							editor: new Ext.form.ComboBox({
								store: [
								{/literal}{foreach from=$listing_fields item="field"}
									['{$field.Key}', '{$field.name}'],
								{/foreach}
								{foreach from=$system_fields item="field" name="sysFieldsLoop"}
									['{$field.Key}', '{$field.name}']{if !$smarty.foreach.sysFieldsLoop.last},{/if}
								{/foreach}{literal}
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
							header: '{/literal}{$lang.cs_map_example_value}{literal}',
							dataIndex: 'Example_value',
							width: 20
						},{
							header: '{/literal}{$lang.cs_mapping_default}{literal}',
							dataIndex: 'Default',
							width: 10,
							editor: new Ext.form.TextArea({
								allowBlank: false
							}),
							renderer: function(val){
								return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
							}
						},{
							header: '{/literal}{$lang.cs_cdata}{literal}',
							dataIndex: 'Cdata',
							width: 10,
							editor: new Ext.form.ComboBox({
								store: [
									['1', lang['ext_yes']],
									['0', lang['ext_no']]
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
							header: lang['ext_status'],
							dataIndex: 'Status',
							width: 15,
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
							width: 90,
							fixed: true,
							dataIndex: 'ID',
							sortable: false,
							renderer: function(val, obj, row){
								var out = "<center>";
								var splitter = false;
								var service_key = row.data.Service;
								var item_key = row.data.Data_remote;

								if (row.data.Data_local.indexOf('category') == 0) {
									out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field=category'><img class='build'ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								} else if( row.data.Data_local.match(/(.*)_level[0-9]/) ) {
									var mf_fkey = /(.*)_level[0-9]/.exec(row.data.Data_local)[1];
									out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field=mf|"+mf_fkey+"'><img class='build'ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								} else if( row.data.Mf ) {
									out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field=mf|"+row.data.Data_local+"'><img class='build'ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								} else if( row.data.Local_field_type == 'select' || row.data.Data_local == 'currency' || row.data.Local_field_type == 'radio' || row.data.Local_field_type == 'checkbox' ) {
									out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field="+item_key+"><img class='build' ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								}

								out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \"{/literal}{$lang.drop_confirm}{literal}\", \"cs_action\", Array(\""+item_key+"\",\"cs_deleteMappingItem\"), \"section_load\" )' />";
								out += "</center>";

								return out;
							}
						}
					]
				});

				specsMappingGrid.init();
				grid.push(specsMappingGrid.grid);

				specsMappingGrid.grid.addListener('afteredit', function(editEvent)
				{
					//if( editEvent.value.indexOf('category') == 0 || editEvent.record.json.Local_field_type == 'select')
					//{
						specsMappingGrid.reload();
					//}
				});
			});
			{/literal}
		//]]>
		</script>
	{else}

		<!-- items mapping grid -->

		<div id="grid"></div>
		<script type="text/javascript">//<![CDATA[
		{literal}

			var specsItemMappingGrid;

			$(document).ready(function(){
				specsItemMappingGrid = new gridObj({
					key: 'xml_mapping',
					id: 'grid',
					ajaxUrl: rlPlugins + 'carSpecs/admin/car_specs.inc.php?q=ext_item_mapping&service={/literal}{$smarty.get.service}&field={$smarty.get.field} \
					{if $smarty.get.parent}&parent={$smarty.get.parent}{/if}{literal}',
					defaultSortField: 'Data_local',
					title: lang['ext_cs_mapping_manager'],
					fields: [
						{name: 'ID', mapping: 'ID', type: 'int'},
						{name: 'Example_value', mapping: 'Example_value', type: 'string'},
						{name: 'Data_local', mapping: 'Data_local', type: 'string'},
						{name: 'Local_field_name', mapping: 'Local_field_name', type: 'string'},
						{name: 'Local_field_type', mapping: 'Local_field_type', type: 'string'},
						{name: 'Service', mapping: 'Service', type: 'string'},
						{name: 'Service_name', mapping: 'Service_name', type: 'string'},
						{name: 'Status', mapping: 'Status'}
					],
					columns: [{
							header: '{/literal}{$lang.cs_remote_data}{literal}',
							dataIndex: 'Example_value',
							id: 'rlExt_item_bold',
							width: 30
						},{
							header: '{/literal}{$lang.cs_local_data}{literal}',
							dataIndex: "Data_local",
							width: 30,
							editor: new Ext.form.ComboBox({
								store: [
								{/literal}{foreach from=$listing_fields item="field"}
									['{$field.Key}', '{$field.name}'],
								{/foreach}
								{foreach from=$local_values item="value" name="sysFieldsLoop"}
									['{$value.Key}', '{$value.name}']{if !$smarty.foreach.sysFieldsLoop.last},{/if}
								{/foreach}{literal}
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
							header: lang['ext_status'],
							dataIndex: 'Status',
							width: 10,
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
							width: 90,
							fixed: true,
							dataIndex: 'ID',
							sortable: false,
							renderer: function(val, obj, row){
								var out = "<center>";
								var splitter = false;
								var service_key = row.data.Service;
								var item_key = row.data.Example_value;


								{/literal}{if $local_field_info.Data_local|strpos:"category_"|is_numeric}{literal}
								if( row.data.Data_local )
								{
									out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field={/literal}{$smarty.get.field}{literal}&amp;parent="+row.data.ID+"'><img class='build' ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								}
								{/literal}{elseif $mf_field}{literal}
								if( row.data.Data_local )
								{
									out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"&amp;field={/literal}{$smarty.get.field}{literal}&amp;parent="+row.data.ID+"'><img class='build' ext:qtip='"+lang['ext_build']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
								}
								{/literal}{else}{literal}
                                if (row.data.Data_local) {
                                    out += '<img class=\'export\' ext:qtip=\'' + lang['cs_copy_item'] + '\' src=\'' + rlUrlHome + 'img/blank.gif\' onClick=\'rlConfirm( "' + lang['cs_insert_item'] + '", "xajax_copyMappingItem", "' + item_key + '", "section_load" )\' />';
								}
								{/literal}{/if}{literal}

								out += "</center>";

								return out;
							}
						}
					]
				});

				specsItemMappingGrid.init();
				grid.push(specsItemMappingGrid.grid);

			});
			{/literal}
		//]]>
		</script>
	{/if}
{else}

	<div id="grid"></div>
	<script type="text/javascript">//<![CDATA[
	{literal}
		var carSpecsGrid;

		$(document).ready(function(){
			carSpecsGrid = new gridObj({
				key: 'car_specs',
				id: 'grid',
				ajaxUrl: rlPlugins + 'carSpecs/admin/car_specs.inc.php?q=ext_services',
				defaultSortField: 'name',
				title: lang['ext_cs_services_manager'],
				fields: [
					{name: 'ID', mapping: 'ID', type: 'int'},
					{name: 'name', mapping: 'name', type: 'string'},
					{name: 'Status', mapping: 'Status'},
					{name: 'Key', mapping: 'Key'}
				],
				columns: [{
						header: lang['ext_name'],
						dataIndex: 'name',
						id: 'rlExt_item_bold',
						width: 20
					},{
						header: lang['ext_status'],
						dataIndex: 'Status',
						width: 10,
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
						width: 90,
						fixed: true,
						dataIndex: 'ID',
						sortable: false,
						renderer: function(val, obj, row){
							var out = "<center>";
							var splitter = false;
							var service_key = row.data.Key;

							out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=mapping&amp;service="+service_key+"><img class='manage' ext:qtip='{/literal}{$lang.cs_statistics}{literal}' src='"+rlUrlHome+"img/blank.gif' /></a>";
							out += "<a href="+rlUrlHome+"index.php?controller="+controller+"&action=edit_service&amp;service="+service_key+"><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
							out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['cs_delete_item']+"\", \"cs_action\", Array(\""+service_key+"\",\"cs_deleteService\"), \"section_load\" )' />";
							out += "</center>";

							return out;
						}
					}
				]
			});

			carSpecsGrid.init();
			grid.push(carSpecsGrid.grid);
		});
		{/literal}
	//]]>
	</script>
{/if}
<script type="text/javascript">
var file_url = "{$smarty.const.RL_URL_HOME}admin/request.ajax.php";
var cs_lang = new Array();
cs_lang['add'] = "{$lang.add}";
{literal}

function cs_action(data, clickedElement)
{
    if ('object' === typeof clickedElement) {
        var $labelElement = $(clickedElement).find('span').length === 3
            ? $(clickedElement).find('span:nth-child(2)')
            : $(clickedElement);
        var previousText = $labelElement.text();
        $labelElement.text(lang['loading']);
    }

    var arg_array = data.split(',');
    var method = arg_array[1];
    var key = arg_array[0];
    var data = {};
    var params = {};
    var grid_obj;

    switch (method) {
        case 'cs_deleteService':
            data.item = method;
            grid_obj = carSpecsGrid;
            break;
        case 'cs_testService':
            params = parseURLParams(window.location.href);
            if (params.field) {
                grid_obj = specsItemMappingGrid;
            } else {
                grid_obj = specsMappingGrid;
            }
            data.item = method;
            break;
        case 'cs_deleteMappingItem':
            params = parseURLParams(window.location.href);
            data.item = method;
            data.get = params;
            if (params.field) {
                grid_obj = specsItemMappingGrid;
            } else {
                grid_obj = specsMappingGrid;
            }
            break;
        case 'cs_addMappingItem':
            data.item = method;
            params = parseURLParams(window.location.href);
            data.add_item = {
                item_local: $('#mapping_item_local').val(),
                item_remote: $('#mapping_item_remote').val(),
                item_default: $('#mapping_item_default').val()
            };
            data.get = params;
            if (params.field) {
                grid_obj = specsItemMappingGrid;
            } else {
                grid_obj = specsMappingGrid;
            }
            break;
    }

    data.service_key = key;
    $.post(
        file_url,
        data,
        function(response) {
            $labelElement.text(previousText);
            if (response.status.toLowerCase() == 'ok') {
                if (method == 'cs_addMappingItem') {
                    $('#mapping_item_local').val();
                    $('#mapping_item_remote').val();
                    $('#add_mapping_item').slideUp('normal');
                    $('input[name=item_submit]').val(cs_lang['add']);
                } else {
                    printMessage('notice', response.message);
                }

                grid_obj.reload();
            } else {
                printMessage('error', response.message);
            }
        }, 'json').fail(function() {
            $labelElement.text(previousText);
            printMessage('error', lang['cs_something_went_wrong']);
    });
}

function parseURLParams(url)
{
    var queryStart = url.indexOf('?') + 1,
        queryEnd = url.indexOf('#') + 1 || url.length + 1,
        query = url.slice(queryStart, queryEnd - 1),
        pairs = query.replace(/\+/g, ' ').split('&'),
        parms = {}, i, n, v, nv;

    if (query === url || query === '') {
        return;
    }

    for (i = 0; i < pairs.length; i++) {
        nv = pairs[i].split('=');
        n = decodeURIComponent(nv[0]);
        v = decodeURIComponent(nv[1]);

        if (!parms.hasOwnProperty(n)) {
            parms[n] = [];
        }

        parms[n].push(nv.length === 2 ? v : null);
    }


    var obj = {};

    $.each(parms, function (index, value) {
        obj[index] = value[0];
    });

    return obj;
}
{/literal}
</script>
