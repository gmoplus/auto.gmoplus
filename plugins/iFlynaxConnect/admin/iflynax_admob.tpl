<!-- navigation bar -->
<div id="nav_bar">{strip}
    {if $aRights.$cKey.add && !isset($smarty.get.action)}
        <a href="{$rlBaseC}action=add" class="button_bar">
            <span class="left"></span>
            <span class="center_add">{$lang.iflynax_admob_add_new}</span>
            <span class="right"></span>
        </a>
    {/if}

    {if isset($smarty.get.action)}
       <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar">
            <span class="left"></span>
            <span class="center_list">{$lang.iflynax_admob_show_list}</span>
            <span class="right"></span>
        </a>
    {/if}
{/strip}</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}
{assign var='sPost' value=$smarty.post}

    <!-- add new/edit admob -->
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
                <select name="position">
                    {foreach from=$position item='position' name='sides_f' key='sKey'}
                        <option value="{$sKey}"
                            {if $sKey == $sPost.position}
                                selected="selected"
                            {/if}>{$position}
                        </option>
                    {/foreach}
                </select>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.iflynax_admob_code}</td>
            <td class="field">
                <input type="text" name="code" value="{$sPost.code}" maxlength="350" />
                <span class="field_description_noicon">{$lang.iflynax_admob_unit_id_help}</span>
            </td>
        </tr>
        <tr>
            <td class="name"><span class="red">*</span>{$lang.show_on_pages}</td>
            <td class="field" id="pages_obj">
                <fieldset class="light">
                    {assign var='pages_name_key' value='admin_controllers+name+pages'}
                    <legend id="legend_iflynax_pages" onclick="fieldset_action('iflynax_pages');" class="up">{$lang.$pages_name_key}</legend>
                    <div id="iflynax_pages">
                        <div id="iflynax_pages_cont">
                            <table class="sTable" style="margin-bottom: 15px;">
                            <tr>
                                <td valign="top">
                                {foreach from=$iflynax_pages item='page' key='page_id' name='pagesF'}
                                <div style="padding: 2px 8px;">
                                    {if $sPost.pages}
                                        {assign var="page_is_selected" value=$page_id|in_array:$sPost.pages}
                                    {/if}

                                    {assign var='admob_page_used' value=false}

                                    {if !$page_is_selected && $page_id|in_array:$used_pages}
                                        {assign var='admob_page_used' value=true}
                                    {/if}

                                    <label class="cLabel{if $admob_page_used} disabled{/if}" title="{if $admob_page_used}{$lang.iflynax_admob_page_used}{/if}">
                                        <input class="checkbox{if $admob_page_used} disabled{/if}"
                                            {if $admob_page_used}
                                                disabled="disabled"
                                            {elseif $page_is_selected}
                                                checked="checked"
                                            {/if}
                                            type="checkbox" name="pages[]" value="{$page_id}"
                                        /> {$iflynax_phrases.$page}
                                    </label>
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
                            <span id="pages_nav">
                                <span onclick="$('#iflynax_pages_cont input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                <span class="divider"> | </span>
                                <span onclick="$('#iflynax_pages_cont input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                            </span>
                        </div>
                    </div>
                </fieldset>
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
    <!-- add new admob end -->
{else}
    <div id="gridAdmob"></div>

    <script type="text/javascript">//<![CDATA[
    var block_sides = [
        {foreach from=$position item='position' name='pos_f' key='sKey'}
            ['{$sKey}', '{$position}']{if !$smarty.foreach.pos_f.last},{/if}
        {/foreach}
    ];
    var gridAdmob = null;

    {literal}

    var deleteAdMobBox = function(admob_id) {
        var url = rlConfig['tpl_base'] + 'request.ajax.php?item=iflynax_admob_remove&id=' + admob_id;

        $.getJSON(url, function(response) {
            if (typeof response !== 'object' || response.status !== 'ok') {
                Ext.MessageBox.alert('', lang['system_error']);
            }
            gridAdmob.reload();
        });
    }

    $(document).ready(function() {
        gridAdmob = new gridObj({
            key: 'gridAdmob',
            id: 'gridAdmob',
            ajaxUrl: rlPlugins + 'iFlynaxConnect/admin/iflynax_admob.inc.php?q=ext',
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
                    width: 10,
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
                        return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                    }
                },{
                    header: '{/literal}{$lang.iflynax_admob_code}{literal}',
                    dataIndex: 'Code',
                    width: 35
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
                        out += "<a href='" + rlUrlHome + "index.php?controller=" + controller+"&action=edit&id=" + data + "'><img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='" + lang['ext_delete'] + "' src='" + rlUrlHome + "img/blank.gif' onclick='rlConfirm( \"" + lang['ext_notice_delete'] + "\", \"deleteAdMobBox\", \"" + Array(data) + "\", \"section_load\" )' />";
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        gridAdmob.init();
        grid.push(gridAdmob.grid);
    });
    {/literal}
    //]]>
    </script>
{/if}
