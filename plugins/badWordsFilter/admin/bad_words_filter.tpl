<!-- bad words  tpl -->
{assign var='sPost' value=$smarty.post}
<!-- Add bad word form -->
<div id="action_blocks">
    <div id="badword_add_action" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_phrase}
        <form action="{$rlBaseC}action=add" method="post">
            <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.bad_word}</td>
                    <td class="field">
                        <input type="text" id="badword_value" name="badword">
                    </td>
                </tr>
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.status}</td>
                    <td class="value">
                        <select id="badword_status" name="status" class="login_input_select lang_add">
                            <option value="active"
                                    {if $badWord_info.Status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                            <option value="approval"
                                    {if $badWord_info.Status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="value">
                        <input id="add_badword" class="button badword_add" type="button" name="add"
                               value="{$lang.add}"/>
                        <a class="cancel" href="javascript:void(0)"
                           onclick="show('badword_add_action')">{$lang.bw_cancel}</a>
                    </td>
                </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <div id="badword_filter" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption="Filter bad words"}
        <table class="form">
            <tbody>
            <tr>
                <td class="name">{$lang.bad_word}</td>
                <td class="field">
                    <input type="text" id="filter_value" style="width:400px;max-width:400px;">
                    <label style="display:block;padding: 5px 0;">
                        <input type="checkbox" id="exact_match" style="margin-right: 7px;">
                        Exact match
                    </label>
                </td>
            </tr>
            <tr>
                <td class="name" style="text-transform: capitalize;"></td>
                <td class="field">
                    <select id="in_language">
                        {if $langCount > 1}
                            <option value="all">{$lang.all}</option>{/if}
                        {foreach from=$allLangs item='languages' name='lang_foreach'}
                            <option value="{$languages.Code}">{$languages.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            </tbody>
        </table>
        <table class="form">
            <tbody>
            <tr>
                <td class="name no_divider"></td>
                <td class="field">
                    <input id="filter_button" type="button" value="Search">
                    <div class="loader" id="search_load"></div>
                    <a class="cancel" href="javascript:void(0)" onclick="show('badword_filter')">{$lang.bw_cancel}</a>
                </td>
            </tr>
            </tbody>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <script type="text/javascript">
        {literal}
        $(document).ready(function () {
            $("#filter_button").click(function () {
                var filters = new Array();
                if ($("#filter_value") != "") {

                    var exact_match = $('input#exact_match').is(':checked') ? 0 : 1;
                    filters.push(new Array('action', 'search'));
                    filters.push(new Array('name', $("#filter_value").val()));
                    filters.push(new Array('Code', $("#in_language").val()));
                    filters.push(new Array('exact_match', exact_match));

                    // reload grid
                    badwordsGrid.filters = filters;
                    badwordsGrid.reload();
                }
            });
        });
        {/literal}
    </script>
</div>
<!-- Add bad word form end -->
<div id="action_blocks">
    <div id="badword_import" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.add_phrase}
        <form method="post" enctype="multipart/form-data" onsubmit="return submit_form();">
            <input type="hidden" name="import" value="import"/>
            <table class="form">
                <tr>
                    <td class="name"><span class="red">*</span>{$lang.bw_select_file}</td>
                    <td class="field">
                        <input type="file" id="import_file" name="badword_file" accept=".csv,.txt">
                        <span class="field_description">{$lang.bw_compatible} <b>txt</b>, <b>csv</b></span>
                    </td>
                </tr>
                <tr id="badword_comas" class="hide">
                    <td class="name"><span class="red">*</span>{$lang.bw_delimetr}</td>
                    <td class="field">
                        <select id="delimiter" name="delimiter">
                            <option value="new_line">{$lang.bw_new_line}</option>
                            <option value="comma">{$lang.bw_comma}</option>
                            <option value="another">{$lang.bw_another}</option>
                        </select>
                        <input id="other_delimiter" type="text" class="text hide" name="delimiter_another" value="">
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="field">
                        <input type="submit" value="Go">
                        <a class="cancel" href="javascript:void(0)"
                           onclick="show('badword_import','#action_blocks div')">{$lang.bw_cancel}</a>
                    </td>
                </tr>
            </table>
        </form>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>
    <script type="text/javascript">
        var bwPhrases = [];
        bwPhrases['bw_file_empty'] = '{$lang.bw_file_empty}';
        {literal}
        $(document).ready(function () {

            $("#import_file").change(function () {
                show('badword_comas');
            });

            $("#delimiter").change(function () {
                if ($(this).val() == 'another') {
                    show('other_delimiter');
                }
            });

        });

        function submit_form() {
            var filename = $("#import_file").val();
            if (filename === '') {
                printMessage('error', bwPhrases['bw_file_empty']);
            } else {

                if (($("#delimiter").val() === 'another') && (!$("#other_delimiter").val())) {
                    printMessage('error', bwPhrases['bw_select_delimitr']);
                } else {
                    return true;
                }
            }

            return false;
        }
        {/literal}
    </script>
</div>

<!-- navigation bar -->
<div id="nav_bar">
    <a href="javascript:void(0)" onclick="show('badword_filter', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_search">{$lang.filter}</span><span class="right"></span></a>
    <a href="javascript:void(0)" onclick="show('badword_add_action', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add}</span><span class="right"></span></a>
    <a href="javascript:void(0)" onclick="show('badword_import', '#action_blocks div');" class="button_bar"><span class="left"></span><span class="center_import">{$lang.import}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

<!-- polls grid  -->
<div id="grid"></div>
<script type="text/javascript">//<![CDATA[
    var badwordsGrid;
    var bwLang = [];
    bwLang['add'] = '{$lang.add}';
    bwLang['bw_fill_bad_word'] = '{$lang.bw_fill_bad_word}';
    bwLang['bw_removed_successfully'] = '{$lang.bw_removed_successfully}';

    var badword_lang = {if $smarty.get.lang}{$smarty.get.lang}{else}0{/if};
    var bw_header = '{$lang.ext_bw}';

    {literal}
    $(document).ready(function () {
        badWordFilter.setLangId(badword_lang);

        badwordsGrid = new gridObj({
            key: 'badwords', //unique
            id: 'grid',
            ajaxUrl: rlPlugins + 'badWordsFilter/admin/bad_words_filter.inc.php?q=ext&lang=' + badword_lang,
            defaultSortField: 'name',
            title: {/literal}'{$lang.bw_grid_header}'{literal},
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'Code', mapping: 'Code'},
                {name: 'name', mapping: 'name'},
                {name: 'Status', mapping: 'Status'},
            ],
            columns: [
                {
                    header: {/literal}'{$lang.bw_language}'{literal},
                    dataIndex: 'Code',
                    width: 80,
                    fixed: true,
                    id: 'rlExt_item_bold'
                }, {
                    header: {/literal}'{$lang.ext_bw}'{literal},
                    dataIndex: 'name',
                    width: 22,
                    id: 'rlExt_badword_value',
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                    }),
                    renderer: function (val) {
                        return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                    }
                }, {
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    width: 100,
                    fixed: true,
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
                }, {
                    header: lang['ext_actions'],
                    width: 80,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function (badword_id) {
                        return "<a onclick='bwDelete(this);' data-id='" + badword_id + "' id='remove-bad-word'><img class='remove' src='" + rlUrlHome + "img/blank.gif' ext:qtip='" + lang['ext_delete'] + "' /></a>";
                    }
                }]
        });

        badwordsGrid.init();
        badWordFilter.init();

        grid.push(badwordsGrid.grid);
    });

    /**
     * Delete BadWord by ID
     *
     * @since 1.2.1
     * @param {event} e - Clicked element event
     */
    function bwDelete(e) {
        var badWordID = $(e).attr('data-id');
        if (!badWordID) {
            return false;
        }

        rlConfirm(
            lang['ext_notice_delete_badword'],
            'badWordFilter.removeBadWord',
            badWordID
        );

        return true;
    }
    {/literal}//]]>
</script>

<!-- Bad Words grid end -->
<div id="filter_block">

</div>
<!-- bad words tpl end -->
