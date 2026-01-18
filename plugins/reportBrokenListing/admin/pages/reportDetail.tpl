<style type="text/css">
    @import url("{$smarty.const.RL_LIBS_URL}jquery/fancybox/jquery.fancybox.css");
</style>

{assign var='isFancyappsExist' value=false}
{if file_exists($smarty.const.RL_LIBS|cat:'fancyapps/fancybox.umd.js')}
    {assign var='isFancyappsExist' value=true}
{/if}

{if $isFancyappsExist}
    <link href="{$smarty.const.RL_LIBS_URL}fancyapps/fancybox.css" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}fancyapps/fancybox.umd.js"></script>
{else}
    <link href="{$smarty.const.RL_LIBS_URL}jquery/fancybox/jquery.fancybox.css" type="text/css" rel="stylesheet" />
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}player/flowplayer.js"></script>
    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.fancybox.js"></script>
{/if}

<ul class="tabs">
    <li lang="report" class="active">{$lang.rbl_report_message}</li>
    <li lang="listing">{$lang.listing_details}</li>
</ul>

<div class="tab_area report">
    <div>
        <div id="grid" style="padding-right: 10px;" ></div>
        <script>
            var listing_id = {if $smarty.get.id}'{$smarty.get.id}'{else}0{/if};
            var critical = {if $smarty.get.level}'{$smarty.get.level}'{else}false{/if};
            var reportDetailGrid;

            var rLangs = filter = [];
            rLangs['ext_critical'] = '{$lang.ext_critical}';
            {literal}

            if (critical) {
                filter.push(['status', critical]);
                filter.push(['filter', 1]);
            }

            $(document).ready(function () {
                $('#date_from').datepicker({
                    showOn: 'both',
                    buttonImage: '{/literal}{$rlTplBase}{literal}img/blank.gif',
                    buttonText: '{/literal}{$lang.dp_choose_date}{literal}',
                    buttonImageOnly: true,
                    dateFormat: 'yy-mm-dd'
                }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                $('#date_to').datepicker({
                    showOn: 'both',
                    buttonImage: '{/literal}{$rlTplBase}{literal}img/blank.gif',
                    buttonText: '{/literal}{$lang.dp_choose_date}{literal}',
                    buttonImageOnly: true,
                    dateFormat: 'yy-mm-dd'
                }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                var report = new Reports();
                report.init();

                window.report = report;

                reportDetailGrid = new gridObj({
                    key: 'reportBrokenPoints',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'reportBrokenListing/admin/pages/reportDetail.php?q=ext&listing_id=' + listing_id,
                    title: lang['rbl_report_message'],
                    filters: filter,
                    fields: [
                        {name: 'ID', mapping: 'ID', type: 'int'},
                        {name: 'Message', mapping: 'Message', type: 'string'},
                        {name: 'Account_username', mapping: 'Account_username', type: 'string'},
                        {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                        {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                        {name: 'Listing_ID', mapping: 'Listing_ID', type: 'int'},
                        {name: 'Criticality', mapping: 'Criticality', type: 'int'},
                        {name: 'IP', mapping: 'IP', type: 'string'}
                    ],
                    columns: [{
                        header: lang['ext_id'],
                        dataIndex: 'ID',
                        width: 40,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    }, {
                        header: lang['rbl_report_message'],
                        dataIndex: 'Message',
                        id: 'rlExt_badword_value'
                    }, {
                        header: lang['ext_reportBroken_report_by'],
                        dataIndex: 'Account_username',
                        width: 120,
                        fixed: true,
                        id: 'rlExt_item_bold',
                        renderer: function(username, param1, row){
                            var out = '';

                            if (row.data.Account_ID > 0) {
                                out = "<a target='_blank' ext:qtip='" + lang['ext_click_to_view_details'] + "' href='";
                                out += rlUrlHome + "index.php?controller=accounts&action=view&userid=";
                                out += row.data.Account_ID + "'>";
                            }

                            out += username;

                            if (row.data.Account_ID > 0) {
                                out += "</a>";
                            }
                            
                            return out;
                        }
                    }, {
                        header: lang['date'],
                        dataIndex: 'Date',
                        width: 120,
                        fixed: true,
                        id: 'rlExt_badword_value',
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                    }, {
                        header: 'IP',
                        dataIndex: 'IP',
                        width: 100,
                        fixed: true,
                        id: 'rbl_ip_value'
                    }, {
                        header: lang['rbl_criticality'],
                        dataIndex: 'Criticality',
                        width: 120,
                        fixed: true,
                        renderer: function (val, param1) {
                            var phrase = lang['rbl_criticality_low'];

                            if (val > 30 && val < 80) {
                                phrase = lang['rbl_criticality_medium'];
                                param1.style += 'background: #ffe7ad;';
                            } else if (val >= 80) {
                                phrase = lang['rbl_criticality_high'];
                                param1.style += 'background: #fbc4c4;';
                            }

                            return '<span style="display: block; text-align: center">' + phrase + '</span>';
                        }
                    }, {
                        header: lang['ext_actions'],
                        dataIndex: 'ID',
                        width: 80,
                        fixed: true,
                        renderer: function (id, obj, row) {
                            return "<center><img class='remove' ext:qtip='" + lang['ext_delete'] + "' src='" + rlUrlHome + "img/blank.gif' onClick='rlPrompt( \"" + lang['ext_notice_' + delete_mod] + "\",  \"report.deleteReport\", \"" + row.data.ID + "\")' /></center>";
                        }
                    }
                    ]
                });

                reportDetailGrid.init();
                grid.push(reportDetailGrid.grid);
                report.setGrid(reportDetailGrid);
            });
            {/literal}
        </script>
    </div>
</div>

<div class="tab_area listing listing_details hide">
    <table class="sTable">
        <tbody>
        <tr>
            <td class="sidebar">
                {if $photos}
                    <ul class="media">
                        {foreach from=$photos item='photo' name='photosF'}
                            <li {if $smarty.foreach.photosF.iteration%2 != 0}class="nl"{/if}>
                                <a data-fancybox="listing-gallery"
                                    title="{$photo.Description}" 
                                    rel="group" 
                                    href="{$smarty.const.RL_FILES_URL}{$photo.Photo}">
                                    <img alt="" class="shadow" src="{$smarty.const.RL_FILES_URL}{$photo.Thumbnail}"/></a>
                            </li>
                        {/foreach}
                    </ul>
                {/if}
            </td>
            <td valign="top">
                <!-- listing info -->
                {foreach from=$listing item='group'}
                    {if $group.Group_ID}
                        {assign var='hide' value=true}
                        {if $group.Fields && $group.Display}
                            {assign var='hide' value=false}
                        {/if}

                        {assign var='value_counter' value='0'}
                        {foreach from=$group.Fields item='group_values' name='groupsF'}
                            {if $group_values.value == '' || !$group_values.Details_page}
                                {assign var='value_counter' value=$value_counter+1}
                            {/if}
                        {/foreach}

                        {if !empty($group.Fields) && ($smarty.foreach.groupsF.total != $value_counter)}
                            <fieldset class="light">
                                <legend id="legend_group_{$group.ID}" class="up"
                                        onclick="fieldset_action('group_{$group.ID}');">{$group.name}</legend>
                                <div id="group_{$group.ID}" class="tree">

                                    <table class="list">
                                        {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                            {if !empty($item.value) && $item.Details_page}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                            {/if}
                                        {/foreach}
                                    </table>

                                </div>
                            </fieldset>
                        {/if}
                    {else}
                        {if $group.Fields}
                            <table class="list">
                                {foreach from=$group.Fields item='item' }
                                    {if !empty($item.value) && $item.Details_page}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                    {/if}
                                {/foreach}
                            </table>
                        {/if}
                    {/if}
                {/foreach}
                <!-- listing info end -->
            </td>
        </tr>
        </tbody>
    </table>
</div>

<script type="text/javascript">
    var rbLang = [];
    rbLang['delete_confirm'] = '{$lang.delete_confirm}';
    {literal}
    $(document).ready(function () {
        var reportBrokenListing = new ReportBrokenListings();

        window.reportBrokenListing = reportBrokenListing;
        reportBrokenListing.setActiveGrid(reportDetailGrid);
        {/literal}{if !$isFancyappsExist}{literal}
            $('ul.media a').fancybox({
                titlePosition: 'over',
                centerOnScroll: true,
                scrolling: 'yes'
            });
        {/literal}{/if}{literal}

        $('#remove-listing').click(function () {
            rlConfirm(rbLang['delete_confirm'], 'reportBrokenListing.removeListing', $(this).data('listing-id'));
        });
    });
{/literal}</script>
