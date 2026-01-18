<!-- dataEntriesImport tpl -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
{assign var='sPost' value=$smarty.post}
<style>
{literal}
.flexdiv-container {
    display:flex;
}
.flex-item {
    margin:5px;
}
{/literal}
</style>
<form action="{$rlBaseC|replace:'&amp;':''}" method="post" enctype="multipart/form-data"
      onsubmit="return submit_form(this);">
    <input type="hidden" name="upload" value="1"/>

    <table class="form">
        <tr>
            <td class="name">{$lang.dataEntriesImport_import_to}</td>
            <td class="field">
                <input  type="radio" id="import_to_new" name="import_to" value="new"/>
                <label for="import_to_new">{$lang.dataEntriesImport_import_to_new}</label>
                <input  type="radio" id="import_to_exists" name="import_to" checked="checked"  value="exists"/>
                <label for="import_to_exists">{$lang.dataEntriesImport_import_to_exists}</label>
            </td>
        </tr>
        <tr class="data_entry_new">
            <td class="name"><span class="red">*</span>{$lang.name}</td>
            <td class="field">
                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}"
                                {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}
                {foreach from=$allLangs item='language' name='langF'}
                    {if $allLangs|@count > 1}<div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">{/if}
                    <input type="text" name="name[{$language.Code}]"
                            value="{$sPost.name[$language.Code]}" maxlength="350"/>
                    {if $allLangs|@count > 1}
                        <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                        </div>
                    {/if}
                {/foreach}
            </td>
        </tr>
        <tr class="data_entry_new">
            <td class="name">{$lang.order_type}</td>
            <td class="field">
                <select name="order_type">
                    <option value="alphabetic"
                            {if $sPost.order_type == 'alphabetic'}selected="selected"{/if}>{$lang.alphabetic_order}</option>
                    <option value="position"
                            {if $sPost.order_type == 'position'}selected="selected"{/if}>{$lang.position_order}</option>
                </select>
            </td>
        </tr>
        <tr class="data_entry_exists">
            <td class="name">
                <span class="red">*</span>{$lang.dataEntriesImport_data_entry}
            </td>
            <td class="field">
                <input type="hidden" name="import_to_parent" value="0"/>
                <div>
                    {assign var='isExistMultiFields' value=false}
                    <select name="df_zero_level" class="df_level_1">
                        <option value="0">{$lang.select}</option>
                        {foreach from=$data_formats item='entry'}
                            {if '' != $entry.name}
                                <option
                                    {if $sPost.data_entry_exists == $entry.ID}selected{/if}
                                    {if $entry.mf}
                                        disabled
                                        {assign var='isExistMultiFields' value=true}
                                    {/if}
                                    value="{$entry.ID}">{$entry.name}{if $entry.mf} *{/if}
                                </option>
                            {/if}
                        {/foreach}
                    </select>
                    {if $isMFInstalled && $isExistMultiFields}
                        <span class="field_description">{$lang.dataEntriesImport_mf_notice}</span>
                    {/if}
                </div>
            </td>
        </tr>

        <tr>
            <td class="name">
                <span class="red">*</span>{$lang.dataEntriesImport_source}
            </td>
            <td class="field">
                <input type="file" class="file" name="source"/>
                <span class="field_description">{$lang.dataEntriesImport_extensions_desc}</span>
            </td>
        </tr>
        <tr id="source_delimiter" class="hide">
            <td class="name">
                <span class="red">*</span>{$lang.dataEntriesImport_delimiter}
            </td>
            <td class="field">
                <select name="delimiter">
                    <option {if $sPost.delimiter == 'new_line'}selected="selected"{/if}
                            value="new_line">{$lang.dataEntriesImport_delimiter_new_line}</option>
                    <option {if $sPost.delimiter == 'tab'}selected="selected"{/if}
                            value="tab">{$lang.dataEntriesImport_delimiter_tab}</option>
                </select>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.dataEntriesImport_ignoreDuplicates}</td>
            <td class="field">
                <input checked type="radio" id="ignore_yes" name="ignore_duplicates" value="1"/>
                <label for="ignore_yes">{$lang.yes}</label>
                <input type="radio" id="ignore_no" name="ignore_duplicates" value="0"/>
                <label for="ignore_no">{$lang.no}</label>
            </td>
        </tr>

        <tr>
            <td></td>
            <td class="field">
                <input class="submit" type="submit" value="{$lang.dataEntriesImport_upload}"/>
            </td>
        </tr>
    </table>

</form>
<table class="form" style="margin: 5px 0 0;">
    <tr>
        <td class="divider" colspan="3"><div class="inner">{$lang.dataEntriesImport_sample}</div></td>
    </tr>
    <tr>
        <td>
            <div class="flexdiv-container">
                <img class="flex-item" src="{$smarty.const.RL_PLUGINS_URL}dataEntriesImport/admin/static/import-csv.jpg" alt="" title=""/>
                <img class="flex-item" src="{$smarty.const.RL_PLUGINS_URL}dataEntriesImport/admin/static/sample-xls.jpg" alt="" title=""/>
                <img class="flex-item"src="{$smarty.const.RL_PLUGINS_URL}dataEntriesImport/admin/static/sample-txt.jpg" alt="" title=""/>
            </div>
        </td>
    </tr>
</table>


{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

<script type="text/javascript">
    var import_to_id = 0;
    var df_level = 0;
    var tmp_df_list = [];
    var languages = [];
    var dfLevelChanged;
    var current_df_mode = 1; // 0 = new, 1 = exists

    {foreach from=$allLangs item='language'}
    languages.push(['{$language.Code}', '{$language.name}']);
    {/foreach}

    {literal}
    $(document).ready(function () {
        $('.data_entry_new').css('display','none');
        $('input[name="import_to"]').click(function () {
            if ($(this).val() === 'exists') {
                    $('.data_entry_exists').css('display','table-row');
                    $('.data_entry_new').css('display','none');
                current_df_mode = 1;
            } else {
                    $('.data_entry_exists').css('display','none');
                    $('.data_entry_new').css('display','table-row');
                current_df_mode = 0;
            }
        });

        $('input[name="source"]').change(function () {
            var sourceExtension = $(this).val().split('.').pop();

            if (sourceExtension !== 'xls' && sourceExtension !== 'xlsx' && sourceExtension !== 'csv' ) {
                $('tr#source_delimiter').show();
            } else {
                $('tr#source_delimiter').hide();
            }
        });

        $('select[name=df_zero_level], select[name=df_zero_level_new]').change(dfLevelChanged);
    });

    // pos: 0 = left, 1 = right
    function getModePrefix(pos) {
        return current_df_mode ? '' : (pos ? 'new_' : '_new');
    }

    function getDfLevelFromClass(s_class) {
        return parseInt(s_class.replace(current_df_mode ? 'df_level_' : 'df_new_level_', ''));
    }

    dfLevelChanged = function (sel) {
        var df_level = getDfLevelFromClass($(sel.target).attr('class'));

        if ($(sel.target).val() !== "0") {
            import_to_id = $(sel.target).val();
            $('input[name="import_to_parent' + getModePrefix(0) + '"]').val(import_to_id);

            {/literal}{if $isMFInstalled}{literal}
            var isMFEntry = $(sel.target).find('option:selected').attr('data-mf') === '1';

            if (tmp_df_list[import_to_id] !== undefined) {
                dfLevelHandler(df_level);
            } else if (isMFEntry || df_level > 1) {
                $.post(rlConfig.ajax_url, {
                    'item': 'dataEntriesImport_getChildEntries',
                    'parent': import_to_id
                }, function (response) {
                    if (response.status === 'OK') {
                        if (response.entries && response.entries.length) {
                            tmp_df_list[import_to_id] = response.entries;
                            dfLevelHandler(df_level);
                        } else {
                            clearDfLevels(df_level)
                        }
                    }
                }, 'json')
            } else {
                clearDfLevels(df_level);
            }
            {/literal}{/if}{literal}
        } else {
            clearDfLevels(df_level);
            $('input[name="import_to_parent' + getModePrefix(0) + '"]').val($(sel.target).attr('parent'));
        }
    };

    function clearDfLevels(skip_level) {
        // current_df_mode = 0; // 0 = new, 1 = exists
        $('select[class^=df_' + getModePrefix(1) + 'level_]').each(function () {
            if (getDfLevelFromClass($(this).attr('class')) > skip_level) {
                $(this).parent().remove();
            }
        });
    }

    //
    function dfLevelHandler(level) {
        clearDfLevels(level);
        df_level = $('select[class^=df_' + getModePrefix(1) + 'level_]').length + 1;

        var new_level_select = '<div style="padding-top:5px;"><select class="df_' + getModePrefix(1) + 'level_' + df_level + '" parent="' + import_to_id + '">';
        new_level_select += '<option value="0">{/literal}{$lang.select}{literal}</option>';

        for (var i = 0; i < tmp_df_list[import_to_id].length; i++) {
            new_level_select += '<option value="' + tmp_df_list[import_to_id][i].ID + '">' + tmp_df_list[import_to_id][i].name + '</option>';
        }
        new_level_select += '</select></div>';

        $('select.df_' + getModePrefix(1) + 'level_' + level).parent().after(new_level_select);
        $('select.df_' + getModePrefix(1) + 'level_' + df_level).bind('change', dfLevelChanged);
    }

    // actions before submit
    function submit_form(form) {
        var fields = [];
        var errorMessage = '';

        if ($(form).attr('disabled')) {
            return false;
        }

        var deImport = $('input[name="import_to"]:checked').val();
        if (deImport === 'exists' && $('input[name="import_to_parent"]').val() === '0') {
            errorMessage += {/literal}"{$lang.notice_field_empty}".replace('{literal}{field}{/literal}', '<b>{$lang.dataEntriesImport_data_entry}</b>') + '<br />';{literal}
            fields.push('df_zero_level');
        }

        var source = $('input[name="source"]').val();

        if (source === '') {
            errorMessage += {/literal}"{$lang.notice_field_empty}".replace('{literal}{field}{/literal}', '<b>{$lang.dataEntriesImport_source}</b>') + '<br />';{literal}
            fields.push('source');
        } else {
            var allowExtensions = ['txt', 'csv', 'xls', 'xlsx'];
            var sourceExtension = source.split('.').pop();
            if (allowExtensions.indexOf(sourceExtension) === -1) {
                errorMessage += {/literal}"{$lang.notice_bad_file_ext}".replace('{literal}{ext}{/literal}', '<b>' + sourceExtension + '</b>') + '<br />';{literal}
                fields.push('source');
            }
        }

        if (fields.length > 0) {
            printMessage('error', errorMessage);
            highlightFields(fields);
            return false;
        }

        $(form).attr('disabled', true);

        return true;
    }

    // show error fields
    function highlightFields(fields) {
        var pattern = /[\w]+\[(\w{2})\]/i;
        for (var i = 0; i < fields.length; i++) {
            if (fields[i] !== 'source') {

                if (pattern.test(fields[i])) {
                    $('input[name="' + fields[i] + '"]').parent().parent().find('ul.tabs li[lang=' + fields[i].match(pattern)[1] + ']').addClass('error');
                    $('input[name="' + fields[i] + '"]').click(function () {
                        $(this).parent().parent().find('ul.tabs li[lang=' + $(this).attr('name').match(pattern)[1] + ']').removeClass('error');
                    });
                    $('textarea[name="' + fields[i] + '"]').parent().parent().parent().find('ul.tabs li[lang=' + fields[i].match(pattern)[1] + ']').addClass('error');
                    $('textarea[name="' + fields[i] + '"]').click(function () {
                        $(this).parent().parent().parent().find('ul.tabs li[lang=' + $(this).attr('name').match(pattern)[1] + ']').removeClass('error');
                    });
                }

                $('input[name="' + fields[i] + '"],select[name="' + fields[i] + '"]').addClass('error');
                $('input[name="' + fields[i] + '"],select[name="' + fields[i] + '"]').focus(function () {
                    $(this).removeClass('error');
                });
            }
        }
    }

    {/literal}
</script>

<!-- dataEntriesImport tpl end -->
