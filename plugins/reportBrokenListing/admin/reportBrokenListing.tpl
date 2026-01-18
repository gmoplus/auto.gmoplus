<!-- Navigation bar -->
<div id="nav_bar">
    {assign var='cur_page' value=$smarty.get.page}

    {if $smarty.get.page}
        {if $cur_page == 'reportDetail'}
            <a href="javascript:void(0)" data-listing-id="{$smarty.get.id}" id="remove-listing" class="button_bar"><span class="left"></span><span class="center_remove">{$lang.rbl_listing_remove}</span><span class="right"></span></a>
            <a href="javascript:void(0)" id="filter-points" class="button_bar"><span class="left"></span><span class="center_search">{$lang.filter}</span><span class="right"></span></a>
        {/if}

        {if $cur_page == 'reportPoints'}
            <a href="javascript:void(0)" id="add-point" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_item}</span><span class="right"></span></a>
        {/if}

        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.rbl_reports_list}</span><span class="right"></span></a>
    {else}
        <a href="{$rlBaseC}page=reportPoints" class="button_bar"><span class="left"></span><span class="center_list">{$lang.rbl_report_points}</span><span class="right"></span></a>
    {/if}
</div>

<div id="action_block">
    <div id="add_report_point" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_item}
        <form id="add-report-points" action="" method="post" onsubmit="return false;">
            <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.rbl_point_name}</td>
                    <td class="field">
                        {if $allLangs|@count > 1}
                            <ul class="tabs">
                                {foreach from=$allLangs item='language' name='langF'}
                                    <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                                {/foreach}
                            </ul>
                        {/if}

                        {foreach from=$allLangs item='language' name='langF'}
                            {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}

                            <input data-required="true" type="text" name="{$language.Code}" value="{$sPost.name[$language.Code]}" maxlength="350" />

                            {if $allLangs|@count > 1}
                                <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                                </div>
                            {/if}
                        {/foreach}
                    </td>
                </tr>

                <tr>
                    <td class="name" style="width: 170px">
                        <span class="red">*</span>
                        {$lang.rbl_reports_make_inactive_after}
                    </td>
                    <td class="field">
                        <input style="width: 40px;"  class="numeric" type="text" id="reports_count_to_critical" name="reports_count_to_critical" data-required="true" data-required-rule=">0">
                        <span class="settings_desc">{$lang.rbl_reports}</span>
                    </td>
                </tr>

                <tr>
                    <td class="name">{$lang.status}</td>
                    <td class="field">
                        <select name="status" id="point-status" class="login_input_select">
                            <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                            <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td id="submit_area" class="field">
                        <a class="cancel" href="javascript:void(0)" onclick="show('add_report_point')">{$lang.cancel}</a>
                    </td>
                </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl' }
    </div>
    <div id="filter_reports" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.rbl_filter_reports}
        <table>
            <tbody>
                    <tr>
                        <td valign="top">
                            <table class="form">
                                <tr>
                                    <td class="name">{$lang.rbl_report_point}</td>
                                    <td>
                                        <select id="point" name="point">
                                            <option value="0">-{$lang.all}-</option>
                                            {foreach from=$report_points item='item'}
                                                <option value="{$item.Key}">{$item.Value}</option>
                                            {/foreach}
                                            <option value="custom">{$lang.rbl_other}</option>
                                        </select>
                                    </td>
                                </tr>

                                <tr>
                                    <td class="name w130">{$lang.date}</td>
                                    <td class="field" style="white-space: nowrap;">
                                        <input style="width: 65px;" type="text" value="{$smarty.post.date_from}" size="12" maxlength="10" id="date_from" />
                                        <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                                        <input style="width: 65px;" type="text" value="{$smarty.post.date_to}" size="12" maxlength="10" id="date_to"/>
                                    </td>
                                </tr>
                                <tr>
                                    <td></td>
                                    <td class="field nowrap">
                                        <input type="button" class="button" value="{$lang.filter}" id="filter_button" />
                                        <input type="button" class="button" value="{$lang.reset}" id="reset_filter_button" />
                                        <a class="cancel" href="javascript:void(0)">{$lang.cancel}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
            </tbody>
        </table>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl' }
    </div>
    <div id="delete_block" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.rbl_remove_block}
        <div style="margin: 0 0 15px" class="delete-block-info">
            {$lang.rbl_label_has_reports}
        </div>
        {$lang.choose_removal_method}
        <div style="margin: 5px 10px;" class="actions">
            <div style="padding: 2px 0;">
                <label>
                    <input class="remove_by" type="radio" id="remove-all" name="del_method">
                    {$lang.rbl_remove_label_with_reports}
                </label>
            </div>
            <div style="padding: 2px 0;">
                <label>
                    <input class="remove_by" type="radio" id="to-another-label" name="del_method">
                    {$lang.rbl_assign_label_reports}
                </label>
            </div>
        </div>

        <div id="all-labels" class="hide">
            <table class="form">
                <tbody>
                    <tr>
                        <td class="name">{$lang.rbl_report_point}</td>
                        <td class="field">
                            <select id="assigning-to">
                                {foreach from=$rbl_points item='point'}
                                    <option value="{$point.Key}">{$point.Value}</option>
                                {/foreach}
                                <option value="custom">{$lang.rbl_other}</option>
                            </select>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <div id="top_buttons">
            <input id="delete-finally-btn" disabled="disabled" class="simple" type="button" value="{$lang.go}">
            <a class="cancel" href="javascript:void(0)">{$lang.cancel}</a>
        </div>

        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
</div>
<!-- Navigation bar end-->

{if !$smarty.get.page}
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
        var reportGrid;
        
        {literal}
        $(document).ready(function(){
            var reportBrokenListing = new ReportBrokenListings();

            window.reportBrokenListing = reportBrokenListing;

            reportGrid = new gridObj({
                key: 'reportBroken',
                id: 'grid',
                ajaxUrl: rlPlugins + 'reportBrokenListing/admin/reportBrokenListing.inc.php?q=ext',
                fieldID: 'Listing_ID',
                title: lang['ext_manager'],
                fields: [
                    {name: 'ID', mapping: 'ID', type: 'int'},
                    {name: 'Listing_ID', mapping: 'Listing_ID', type: 'int'},
                    {name: 'Listing_title', mapping: 'Listing_title', type: 'string'},
                    {name: 'Reports_count', mapping: 'Reports_count', type: 'int'},
                    {name: 'Criticality', mapping: 'Criticality', type: 'int'},
                    {name: 'Status', mapping: 'Status', type: 'string'}
                ],
                columns: [{
                    header: lang['ext_listing_id'],
                    id: 'rlExt_black_bold',
                    dataIndex: 'Listing_ID',
                    width: 90,
                    fixed: true
                },{
                    header: lang['rbl_listing_title'],
                    dataIndex: 'Listing_title',
                    renderer: function(value, param1, row){
                        var out = '';

                        out  = '<a href="' + rlUrlHome + 'index.php?controller=listings&action=view&id=';
                        out += row.data.Listing_ID + '" target="_blank">' + value + '</a> ';

                        return out;
                    }
                },{
                    header: lang['rbl_reports_count'],
                    dataIndex: 'Reports_count',
                    width: 120,
                    fixed: true,
                    renderer: function(value, obj, row) {
                        return '<center>' + value + '</center>';
                    }
                },{
                    header: lang['rbl_listing_criticality'],
                    dataIndex: 'Criticality',
                    width: 120,
                    fixed: true,
                    renderer: function(value, param1, row){
                        param1.style = '';
                        var href_attr = {
                            text: value + '%',
                            style: 'width:100%;height:100%; display: block; text-decoration:none'
                        };

                        if (value > 30 && value < 80) {
                            param1.style += 'background: #ffe7ad;';
                        } else if (value >= 80) {
                            param1.style += 'background: #fbc4c4;';
                        }

                        var a_elem = $('<a/>', href_attr);
                        var $critical_box = $('<div />', {
                            class: 'critical_cell',
                            style: 'text-align: center'
                        }).append(a_elem);

                        return $critical_box.prop('outerHTML');
                    }
                },{
                    header: lang['rbl_listing_status'],
                    dataIndex: 'Status',
                    width: 120,
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
                    })
                },{
                    header: lang['ext_actions'],
                    dataIndex: 'ID',
                    width: 100,
                    fixed: true,
                    renderer: function(id, cell, row) {
                        var out = "<center>";

                        out += "<a href='" + rlUrlHome + "index.php?controller=" + controller + "&page=reportDetail&id=" + row.data.Listing_ID + "'><img class='view' ext:qtip='" + lang['ext_view'] + "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='" + lang['rbl_remove_all_reports'] + "' src='" + rlUrlHome + "img/blank.gif' onClick='rlConfirm( \"" + lang['ext_notice_' + delete_mod] + "\",  \"reportBrokenListing.removeAllListingReports\", \"" + row.data.Listing_ID + "\")' /></center>";

                        out += "</center>";

                        return out;
                    }
                }
                ]
            });

            reportGrid.init();
            reportBrokenListing.setActiveGrid(reportGrid);
            
            grid.push(reportGrid.grid);
        });
        {/literal}
        //]]>
    </script>
{else}
    {include file=$rblConfigs.a_pages|cat:$smarty.get.page|cat:'.tpl'}
{/if}

<script>
    lang['add'] = '{$lang.add}';
    lang['edit'] = '{$lang.edit}';
    lang['status'] = '{$lang.status}';
    lang['required_fields'] = '{$lang.required_fields}';
    lang['date'] = '{$lang.date}';
</script>
