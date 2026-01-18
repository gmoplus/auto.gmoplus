<!-- export import categories tpl -->
<!-- navigation bar -->

<div id="nav_bar">
    {if $smarty.get.action == 'import' || !isset($smarty.get.action)}
        <a href="{$rlBaseC}action=export" class="button_bar"><span class="left"></span><span
                class="center_export">{$lang.importExportCategories_export}</span><span class="right"></span></a>
    {/if}
    {if $smarty.get.action == 'export'}
        <a href="{$rlBaseC}" class="button_bar"><span class="left"></span><span
                class="center_import">{$lang.importExportCategories_import}</span><span class="right"></span></a>
    {/if}
</div>
<!-- navigation bar end -->

{if !isset($smarty.get.action)}
    {if !isset($smarty.post.submit)}
        {assign var='systemColumns' value=', '|implode:$systemColumns}
        {assign var="replace" value=`$smarty.ldelim`system_columns`$smarty.rdelim`}

        {assign var='multilingualColumns' value=', '|implode:$multilingualColumns}
        {assign var="replace2" value=`$smarty.ldelim`multilingual_columns`$smarty.rdelim`}
    {/if}

    {include file='blocks/m_block_start.tpl'}
    <h2>{$lang.importExportCategories_example}</h2>
    <br>
    <p>{$lang.importExportCategories_header_row|replace:$replace:$systemColumns|replace:$replace2:$multilingualColumns}</p>
    <br>
    <img src="{$smarty.const.RL_PLUGINS_URL}importExportCategories/admin/static/example-v2.png" alt="" title="" style="width: 700px;" />
    <br><br><br>
    <img src="{$smarty.const.RL_PLUGINS_URL}importExportCategories/admin/static/multilingual_example.png" alt="" title="" style="width: 1200px;" />
    <div class="clear"></div>
    {include file='blocks/m_block_end.tpl'}

    <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.categoryDropdown.js"></script>
    <script>
        var category_selected = {if $sPost.export_category_id}{$sPost.export_category_id}{else}null{/if};
        {literal}
            $(document).ready(function() {
                $('select[name=export_category_id]').categoryDropdown({
                    listingType: '#type',
                    default_selection: category_selected,
                    phrases: { {/literal}
                        no_categories_available: "{$lang.no_categories_available}",
                        select: "{$lang.select}",
                        select_category: "{$lang.select_category}"
                    {literal} }
                });
            });
        {/literal}
    </script>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action=import" id="importForm" method="post" enctype="multipart/form-data"
        onsubmit="return submit_form();">
        <input type="hidden" name="submit" value="1" />
        <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.listing_type}</td>
                <td class="field">
                    <select class="w200" name="export_listing_type" id="type">
                        <option value="">{$lang.select}</option>
                        {foreach from=$listing_types item='l_type'}
                            <option {if $sPost.export_listing_type == $l_type.Key}selected="selected" {/if}
                                value="{$l_type.Key}">{$l_type.name}</option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.parent_category}</td>
                <td class="field">
                    <select class="w200" name="export_category_id">
                        <option value="">{if $categories}{$lang.select}{else}{$lang.eil_select_listing_type}{/if}</option>
                        {foreach from=$categories item='category'}
                            <option {if $category.Level == 0}class="highlight_opt" {/if}
                                {if $category.margin}style="margin-left: {$category.margin}px;" {/if} value="{$category.ID}"
                                {if $sPost.export_category_id == $category.ID}selected="selected" {/if}>{$lang[$category.pName]}
                                ({$category.Count})
                            </option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.file}
                </td>
                <td class="field">
                    <input type="file" class="file" name="file_import" />
                </td>
            </tr>
            <tr>
                <td></td>
                <td class="field">
                    <input class="submit" type="submit" value="{$lang.importExportCategories_import}" />
                </td>
            </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    {assign var="tempRepPhrase" value=`$smarty.ldelim`field`$smarty.rdelim`}

    <script type="text/javascript">
        {literal}
            var submit_form = function() {
                let importFile     = $('[name="file_import"]').val(),
                    listingType    = $('[name="export_listing_type"]').val(),
                    allowedFormats = JSON.parse('{/literal}{$allowedFormats|@json_encode}{literal}');

                if (!listingType) {
                    printMessage('error', '{/literal}{$lang.importExportCategories_import_filename_empty|replace:$tempRepPhrase:$lang.listing_type}{literal}');
                    return false;
                } else if (importFile == '') {
                    printMessage('error', '{/literal}{$lang.importExportCategories_import_filename_empty|replace:$tempRepPhrase:$lang.file}{literal}');
                    return false;
                } else {
                    if ($.inArray(importFile.split('.').pop(), allowedFormats) < 0) {
                        printMessage('error', '{/literal}{$lang.importExportCategories_incorrect_file_ext}{literal}');
                        return false;
                    }
                }
                return true;
            }
        {/literal}
    </script>
{elseif $smarty.get.action === 'import'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <div style="padding: 10px;">
        <table class="lTable">
            <tr class="body">
                <td class="list_td_light">{$lang.importExportCategories_pre_import_notice}</td>
                <td style="width: 5px;" rowspan="100"></td>
                <td class="list_td_light" align="center" style="width: 200px;">
                    <input type="button" id="import_categories_button" value="{$lang.importExportCategories_import}"
                        style="margin: 0;width: 100px;" />
                </td>
            </tr>
        </table>
    </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <div id="grid"></div>
    <script type="text/javascript" src="{$smarty.const.RL_PLUGINS_URL}importExportCategories/admin/static/lib.js"></script>
    <script>
        lang.h1_heading                 = '{$lang.h1_heading}';
        lang.title                      = '{$lang.title}';
        lang.meta_description           = '{$lang.meta_description}';
        lang.meta_keywords              = '{$lang.meta_keywords}';
        lang.category_url_listing_logic = '{$lang.category_url_listing_logic}';

        let pathImport = '{$rlBaseC}', importInProcess = false;
        {literal}

        /**
         * @todo - Remove this code when compatibility will be >= 4.8.1
         */
        if (typeof lang.importExportCategories_titleOfManager === 'undefined') {
            lang.importExportCategories_titleOfManager = '{/literal}{$lang.importExportCategories_titleOfManager}{literal}';
        }

        $(document).ready(function() {
            $('.button_bar').remove();

            let $importButton = $('#import_categories_button');

            $importButton.click(function() {
                importInProcess = true;
                $importButton.prop('disabled', true).val(lang.loading);
                importCategories(0);
            });

            $(window).bind('beforeunload', function() {
                if (importInProcess) {
                    return 'Uploading the data is in process; closing the page will stop the process.';
                }
            });

            function importCategories(start) {
                $.post(rlConfig.ajax_url, {
                        item: 'importCategory',
                        stack: start
                    },
                    function(response) {
                        response = JSON.parse(response);
                        if (response.next === true && response.start > start) {
                            importCategories(response.start);
                        } else {
                            importInProcess = false;
                            location.href = rlUrlHome + 'index.php?controller=' + controller + '&done';
                        }
                    }
                )
            }
            let validError = false;
            var importCategoriesGrid = new gridObj({
                key: 'importCategories',
                id: 'grid',
                ajaxUrl: rlPlugins + 'importExportCategories/admin/importExportCategories.inc.php?q=ext',
                defaultSortField: false,
                title: lang['importExportCategories_titleOfManager'],
                fields: [
                    {name: 'name', mapping: 'Name'},
                    {name: 'parent', mapping: 'Parent'},
                    {name: 'type', mapping: 'Type'},
                    {name: 'path', mapping: 'Path'},
                    {name: 'locked', mapping: 'Lock'},
                    {name: 'title', mapping: 'Title'},
                    {name: 'h1', mapping: 'H1'},
                    {name: 'metaDescription', mapping: 'Meta_description'},
                    {name: 'metaKeywords', mapping: 'Meta_keywords'},
                ],
                columns: [{
                    header: lang['ext_name'],
                    dataIndex: 'name',
                    id: 'rlExt_item_bold',
                    width: 22
                }, {
                    header: lang['ext_parent'],
                    dataIndex: 'parent',
                    id: 'rlExt_item',
                    width: 15,
                    renderer: function(value) {
                        if (!value) {
                            return '<span style="color:#3D3D3D">{/literal}{$lang.no_parent}{literal}</span>';
                        }
                        return value;
                    }
                }, {
                    header: lang['title'],
                    dataIndex: 'title',
                    id: 'rlExt_item',
                    width: 12,
                }, {
                    header: lang['h1_heading'],
                    dataIndex: 'h1',
                    id: 'rlExt_item',
                    width: 12,
                }, {
                    header: lang['meta_description'],
                    dataIndex: 'metaDescription',
                    id: 'rlExt_item',
                    width: 11,
                }, {
                    header: lang['meta_keywords'],
                    dataIndex: 'metaKeywords',
                    id: 'rlExt_item',
                    width: 11,
                }, {
                    header: lang['ext_type'],
                    dataIndex: 'type',
                    width: 10,
                    renderer: function(value) {
                        return '<b>' + value + '</b>';
                    }
                }, {
                    header: '{/literal}{$lang.url}{literal}',
                    dataIndex: 'path',
                    width: 40,
                    renderer: function(value, row) {
                        if (!value) {
                            return '<span style="color:#df7c41">will generated</span>';
                        }
                        return value;
                    }
                }, {
                    header: lang['ext_locked'],
                    dataIndex: 'locked',
                    width: 8,
                    renderer: function(value) {
                        if (value == '1') {
                            return lang['ext_yes'];
                        }
                        return lang['ext_no'];
                    }
                }]
            });

            importCategoriesGrid.init();
            grid.push(importCategoriesGrid.grid);

        });
        {/literal}
    </script>
{elseif $smarty.get.action == 'export'}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form id="export_form" action="{$rlBaseC}action=export" method="post" onsubmit="return submit_form();">
        <input type="hidden" name="submit" value="1" />
        <input type="hidden" id="str_category" name="str_category" />
        <input type="hidden" id="strincludeType" name="strincludeType" />
        <table class="form">
            <tr>
                <td>
                    <div id="cat_checkboxed" style="margin: 0 0 8px;{if $sPost.cat_sticky}display: none{/if}">
                        <div class="tree">
                            {foreach from=$sections item='section'}
                                <fieldset class="light">
                                    <legend id="legend_section_{$section.ID}" class="up"
                                        onclick="fieldset_action('section_{$section.ID}');">{$section.name}</legend>
                                    <div id="section_{$section.ID}">
                                        {if !empty($section.Categories)}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'category_level_checkbox.tpl' categories=$section.Categories first=true}
                                            <div class="grey_area">
                                                <label><input class="checkbox" type="checkbox" name="include_sub_cat[]"
                                                        value="{$section.Type}" /> {$lang.include_subcats}</label>
                                                <span onclick="levelSection('check',{$section.ID})" class="green_10">{$lang.check_all}</span>
                                                <span class="divider"> | </span>
                                                <span onclick="levelSection('uncheck',{$section.ID})"
                                                    class="green_10">{$lang.uncheck_all}</span>
                                                </span>
                                            </div>
                                        {else}
                                            <div style="padding: 0 0 8px 10px;">{$lang.no_items_in_sections}</div>
                                        {/if}
                                    </div>
                                </fieldset>
                            {/foreach}
                        </div>
                    </div>

                    <script type="text/javascript">
                        var submit_form;
                        var tree_selected = {if $smarty.post.categories}[{foreach from=$smarty.post.categories item='post_cat' name='postcatF'}['{$post_cat}']{if !$smarty.foreach.postcatF.last},{/if}{/foreach}]
                    {else}false
                    {/if};
                    var tree_parentPoints = {if $parentPoints}[{foreach from=$parentPoints item='parent_point' name='parentF'}['{$parent_point}']{if !$smarty.foreach.parentF.last},{/if}{/foreach}]
                    {else}false
                    {/if};
                    {literal}
                        var uncheckChildCheckboxes = function() {
                            $('.tree input').off('click').click(function() {
                                var $parent = $(this);
                                var $parentLi = $parent.closest('li');
                                var $isIncludeSub = $parentLi.closest('div');
                                $isIncludeSub = $isIncludeSub.find('[name="include_sub_cat[]"]').is(":checked");
                                var $childs = $parentLi.find('ul');
                                if ($isIncludeSub) {
                                    $childs.find('input').removeAttr('checked');
                                }
                            });
                        }
                        $(document).ready(function() {
                            uncheckChildCheckboxes();
                            $('.opened').live('click', function() {
                                var $parent = $(this);
                                var $parentLi = $parent.closest('li');
                                $parentLi.find('.grey_area').css('display', 'none');

                            });

                            $('li>img:not(.opened)').live('click', function() {
                                $(this).closest('li').addClass('parent_li');
                                $(this).closest('li').find('.grey_area').last().css("display", "block");
                                var $li = $(this).closest('li');
                                $open = $li.find('.opened');
                                $close = $open.closest('li').find('.grey_area').css("display", "block");
                            });

                            $("[name='categories[]']").live('change', function() {
                                var $parent = $(this);
                                var $parentLi = $parent.closest('li');
                                var $isIncludeSub = $parentLi.closest('div');
                                $isIncludeSub = $isIncludeSub.find('[name="include_sub_cat[]"]').is(':checked');
                                if ($isIncludeSub) {
                                    if (this.checked) {
                                        $(this).parent().closest('li').find('img:not(.no_child)').removeAttr('style');
                                        $(this).parent().closest('li').addClass('iec-hide_img');
                                    } else {
                                        $(this).parent().closest('li').find('img:not(.no_child)').css('display', 'inline');
                                        $img = $(this).closest('li').removeClass('iec-hide_img');
                                    }
                                }
                            });
                            $("[name='include_sub_cat[]']").live('change', function() {
                                var $parent = $(this);
                                var $parentDiv = $parent.closest('div');
                                var $greatParentDiv = $parentDiv.parent().closest('div');
                                $img = $greatParentDiv.find('.iec-hide_img');
                                if (this.checked) {
                                    var $li = $img.closest('li');
                                    var $ul = $li.find('ul');
                                    $inputBefore = $greatParentDiv.find('input:checked');
                                    $liBefore = $inputBefore.closest('li');
                                    $liBefore.find('img:not(.no_child)').removeAttr("style");
                                    $liBefore.closest('li').addClass('iec-hide_img');
                                    $parentLi = $greatParentDiv.find('.parent_li');
                                    $parentInput = $parentLi.find('input:checked');
                                    $liParent = $parentInput.closest('li');
                                    $ulChild = $liParent.find('ul');
                                    $ulChild.find($("input")).removeAttr('checked');
                                    $liParent.find('img:not(.no_child)').removeAttr("style");
                                    $liParent.addClass('iec-hide_img');
                                } else {
                                    $greatParentDiv.find('img:not(.no_child)').css("display", "inline");
                                    $img.removeClass('iec-hide_img');
                                }
                            })

                            $("[name='categories[]']").prop("disabled", false);
                            $("#export_btn").prop("disabled", false);
                            flynax.treeLoadLevel('checkbox', 'flynax.openTree(tree_selected, tree_parentPoints)', 'div#cat_checkboxed');
                            flynax.openTree(tree_selected, tree_parentPoints);

                            $('input[name=cat_sticky]').click(function() {
                                $('#cat_checkboxed').slideToggle();
                                $('#cats_nav').fadeToggle();
                            });

                            submit_form = function() {
                                var data = $('#export_form').serializeArray();
                                var newDataCategory = '';
                                var newDataIncludeSubCate = '';

                                $.each(data, function() {
                                    if (this.name === 'categories[]') {
                                        newDataCategory += this.value + ',';
                                    }
                                    if (this.name === 'include_sub_cat[]') {
                                        newDataIncludeSubCate += this.value + ',';
                                    }
                                });

                                if (newDataCategory.length > 0) {
                                    newDataCategory = newDataCategory.substring(0, newDataCategory.length - 1);
                                }

                                if (newDataIncludeSubCate.length > 0) {
                                    newDataIncludeSubCate = newDataIncludeSubCate.substring(0, newDataIncludeSubCate.length - 1);
                                }

                                $('#str_category').val(newDataCategory);
                                $('#strincludeType').val(newDataIncludeSubCate);
                                $("#cat_checkboxed div.tree input").prop('checked', false);

                                if (!$('input[name = cat_sticky]').is(":checked")) {
                                    if (newDataCategory.length > 0) {
                                        return true;
                                    } else {
                                        printMessage('info', '{/literal}{$lang.importExportCategories_empty}{literal}');
                                        return false;
                                    }
                                } else {
                                    $('#system_message').css('display', 'none');
                                }
                            }
                        });

                    {/literal}
                </script>
                <script>
                    {literal}
                        function levelAll(flag) {
                            $li = $('div.tree').find('li');
                            if (flag === 'uncheck_all') {
                                $('#cat_checkboxed div.tree input[name=\'categories[]\']').prop('checked', false);
                                $li.removeClass('iec-hide_img');
                            } else {
                                $('#cat_checkboxed div.tree input').prop('checked', true);
                                $li.find('img:not(.no_child)').removeAttr("style");
                                $li.addClass('iec-hide_img');
                            }
                        }

                        function levelSection(flag, sectionID) {
                            $li = $('#section_' + sectionID).find('li');
                            if (flag === 'uncheck') {
                                $('#section_' + sectionID + ' input[name=\'categories[]\']').prop('checked', false);
                                $li.removeClass('iec-hide_img');
                            } else {
                                $('#section_' + sectionID + ' input[name=\'categories[]\']').prop('checked', true);
                                $subCatInclude = $('#section_' + sectionID).find('input[name=\'include_sub_cat[]\']');
                                if ($subCatInclude.is(':checked')) {
                                    $li.find('img:not(.no_child)').removeAttr("style");
                                    $li.addClass('iec-hide_img');
                                }
                            }
                        }

                        function levelDynamic(flag, $context) {
                            $div = $context.closest('div');
                            $li = $context.closest('li');
                            $ul = $li.find('ul');
                            $childLi = $ul.find('li');
                            if (flag === 'uncheck') {
                                $childLi.removeClass('iec-hide_img');
                            } else {
                                $divSubCatInclude = $li.closest('div');
                                $subCatInclude = $divSubCatInclude.find('input[name=\'include_sub_cat[]\']');
                                if ($subCatInclude.is(":checked")) {
                                    $parentInput = $li.find('input[name=\'categories[]\']').first();
                                    $parentInput.prop('checked', true);
                                    $li.find('img:not(.no_child)').removeAttr("style");
                                    $li.addClass('iec-hide_img');
                                }
                            }
                        }
                    {/literal}
                </script>

                <div class="grey_area">
                    <label><input class="checkbox" {if $sPost.cat_sticky}checked="checked" {/if} type="checkbox"
                            name="cat_sticky" value="true" /> {$lang.sticky}</label>
                    <span id="cats_nav" {if $sPost.cat_sticky}class="hide" {/if}>
                        <span onclick="levelAll('check_all')" class="green_10">{$lang.check_all}</span>
                        <span class="divider"> | </span>
                        <span onclick="levelAll('uncheck_all')" class="green_10">{$lang.uncheck_all}</span>
                    </span>
                </div>
            </td>
        </tr>
        <tr>
            <td class="field">
                <input type="submit" id="export_btn" value="{$lang.importExportCategories_export}" />
            </td>
        </tr>
    </table>
</form>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
{/if}

<!-- export import categories tpl end -->
