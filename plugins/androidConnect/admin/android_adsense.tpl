<!-- navigation bar -->
<div id="nav_bar">

	{if $aRights.$cKey.add && $smarty.get.action != 'add'}
		<a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_new_adsense}</span><span class="right"></span></a>
	{/if}
	<a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.show_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->
{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

{assign var='sPost' value=$smarty.post}

	<!-- add new/edit adsense -->
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
		<form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;id={$smarty.get.id}{/if}" method="post">
			<input type="hidden" name="submit" value="1" />

			{if $smarty.get.action == 'edit'}
				<input type="hidden" name="fromPost" value="1" />
				<input type="hidden" name="id" value="{$sPost.id}" />
			{/if}
			<table class="form">
			<tr>
				<td class="name">
					<span class="red">*</span>{$lang.name}
				</td>
				<td>
					<input type="text" name="name" value="{$sPost.name}" maxlength="350" />
				</td>
			</tr>
			<tr>
				<td class="name"><span class="red">*</span>{$lang.position}</td>
				<td class="field">
					<select name="side">
						<option value="">{$lang.select}</option>
						{foreach from=$side item='side' name='sides_f' key='sKey'}
							<option value="{$sKey}" {if $sKey == $sPost.side}selected="selected"{/if}>{$side}</option>
						{/foreach}
					</select>
				</td>
			</tr>
			<tr>
				<td class="name"><span class="red">*</span>{$lang.adsense_code}</td>
				<td class="field">
					<input type="text" name="code" value="{$sPost.code}" maxlength="350" />
					<span class="field_description_noicon">{$lang.admob_unit_id_help}</span>
				</td>
			</tr>
			<tr>
				<td class="name">{$lang.show_on_pages}</td>
				<td class="field" id="pages_obj">
					<fieldset class="light">
						{assign var='pages_phrase' value='admin_controllers+name+pages'}
						<legend id="legend_pages" class="up">{$lang.$pages_phrase}</legend>
						<div id="pages">
							<div id="pages_cont" {if !empty($sPost.show_on_all)}style="display: none;"{/if}>
								{assign var='bPages' value=$sPost.pages}
								<table class="sTable" style="margin-bottom: 15px;">
								<tr>
									<td valign="top">
									{foreach from=$android_pages item='page' key='key' name='pagesF'}
									<div style="padding: 2px 8px;">
										<label class="cLabel{if !isset($bPages.$key) && $key|in_array:$used_screens} disabled{/if}" for="page_{$key}" {if !isset($bPages.$key) && $key|in_array:$used_screens}title="{$lang.page_used}"{/if}><input class="checkbox disabled" {if !isset($bPages.$key) && $key|in_array:$used_screens}disabled="disabled"{elseif isset($bPages.$key)}checked="checked"{/if} id="page_{$key}" type="checkbox" name="pages[{$key}]" value="{$key}" /> {if $page == 'account_search_results'}{$lang.$page}{else}{$android_phrases.$page}{/if}</label>
									</div>
									{assign var='perCol' value=$smarty.foreach.pagesF.total/3|ceil}

									{if $smarty.foreach.pagesF.iteration % $perCol == 0}
										</td>
										<td valign="top">
									{/if}
									{/foreach}
									</td>
								</tr>
								</table>
							</div>

							<div class="grey_area" style="margin: 0 0 5px;">
								<span id="pages_nav" {if $sPost.show_on_all}class="hide"{/if}>
									<span onclick="$('#pages_cont input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
									<span class="divider"> | </span>
									<span onclick="$('#pages_cont input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
								</span>
							</div>
						</div>
					</fieldset>

					<script type="text/javascript">
					{literal}

					$(document).ready(function(){
						$('#legend_pages').click(function(){
							fieldset_action('pages');
						});

						$('input#show_on_all').click(function(){
							$('#pages_cont').slideToggle();
							$('#pages_nav').fadeToggle();
						});

						$('#pages input').click(function(){
							if ( $('#pages input:checked').length > 0 )
							{
								//$('#show_on_all').prop('checked', false);
							}
						});
					});

					{/literal}
					</script>
				</td>
			</tr>
			<tr>
				<td class="name"><span class="red">*</span>{$lang.status}</td>
				<td class="field">
					<select name="status">
						<option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
						<option value="inactive" {if $sPost.status == 'inactive'}selected="selected"{/if}>{$lang.approval}</option>
					</select>
				</td>
			</tr>
			<tr>
				<td></td>
				<td class="field">
					<input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
				</td>
			</tr>
			</table>
		</form>
	{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
	<!-- add new adsense end -->
{else}
	<script type="text/javascript">
	// blocks box types list
	var block_sides = [
	{foreach from=$side item='block_side' name='sides_f' key='sKey'}
		['{$sKey}', '{$block_side}']{if !$smarty.foreach.sides_f.last},{/if}
	{/foreach}
	];

	</script>
	<div id="gridAdsense"></div>
	<script type="text/javascript">//<![CDATA[
	var adsense;
	{literal}
	$(document).ready(function(){

		adsense = new gridObj({
			key: 'adsense',
			id: 'gridAdsense',
			ajaxUrl: rlPlugins + 'androidConnect/admin/android_adsense.inc.php?q=ext',
			defaultSortField: 'ID',
			title: lang['ext_manager'],
			fields: [
				{name: 'ID', mapping: 'ID'},
				{name: 'Name', mapping: 'Name'},
				{name: 'Side', mapping: 'Side'},
				{name: 'Code', mapping: 'Code'},
				{name: 'Status', mapping: 'Status'}
			],
			columns: [
				{
					header: lang['ext_id'],
					dataIndex: 'ID',
					width: 3
				},{
					header: lang['ext_name'],
					dataIndex: 'Name',
					width: 30
				},{
					header: lang['ext_position'],
					dataIndex: 'Side',
					width: 15,
					editor: new Ext.form.ComboBox({
						store: block_sides,
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
					header: lang['ext_adsense_code'],
					dataIndex: 'Code',
					width: 30
				},{
					header: lang['ext_status'],
					dataIndex: 'Status',
					width: 10,
					editor: new Ext.form.ComboBox({
						store: [
							['active', lang['ext_active']],
							['approval', lang['ext_approval']]
						],
						mode: 'local',
						typeAhead: true,
						triggerAction: 'all',
						selectOnFocus: true
					})
				},{
					header: lang['ext_actions'],
					width: 70,
					fixed: true,
					dataIndex: 'ID',
					sortable: false,
					renderer: function(data) {
						var out = "<center>";
						var splitter = false;


							out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&id="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";

							out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteAdsenseBox\", \""+Array(data)+"\", \"section_load\" )' />";

						out += "</center>";

						return out;
					}
				}
			]
		});

		adsense.init();
		grid.push(adsense.grid);

	});
	{/literal}
	//]]>
	</script>
{/if}
