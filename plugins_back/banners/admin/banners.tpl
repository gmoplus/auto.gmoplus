<!-- banners tpl -->

<!-- navigation tpl -->
<div id="nav_bar">{strip}
{if $smarty.get.module == 'banner_plans'}
    {if $smarty.get.action != 'add'}
        <a href="{$rlBaseC}module=banner_plans&action=add" class="button_bar"><span class="left"></span>
            <span class="center_add">{$lang.banners_addPlan}</span><span class="right"></span>
        </a>
        &nbsp;
    {/if}
    <a href="{$rlBaseC|replace:'&amp;':''}" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBanners}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_plans" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfPlans}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_boxes" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBoxes}</span><span class="right"></span>
    </a>
{elseif $smarty.get.module == 'banner_boxes'}
    {if $smarty.get.action != 'add'}
        <a href="{$rlBaseC}module=banner_boxes&action=add" class="button_bar"><span class="left"></span>
            <span class="center_add">{$lang.banners_addBox}</span><span class="right"></span>
        </a>&nbsp;
        &nbsp;
    {/if}
    <a href="{$rlBaseC|replace:'&amp;':''}" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBanners}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_plans" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfPlans}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_boxes" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBoxes}</span><span class="right"></span>
    </a>
{else}
    {if $smarty.get.action != 'add'}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span>
            <span class="center_add">{$lang.banners_addBanner}</span><span class="right"></span>
        </a>
        &nbsp;
    {/if}
    <a href="{$rlBaseC|replace:'&amp;':''}" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBanners}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_plans" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfPlans}</span><span class="right"></span>
    </a>
    &nbsp;
    <a href="{$rlBaseC}module=banner_boxes" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.banners_listOfBoxes}</span><span class="right"></span>
    </a>
{/if}
{/strip}</div>
<!-- navigation.tpl end -->

{assign var='bannersPath' value=$smarty.const.RL_PLUGINS|cat:'banners'|cat:$smarty.const.RL_DS}
{if isset($smarty.get.module)}
    {include file=$bannersPath|cat:'admin'|cat:$smarty.const.RL_DS|cat:$smarty.get.module|cat:'.tpl'}
{else}
    {if $smarty.get.action}

    {assign var='sPost' value=$smarty.post}

    {if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}

    <!-- add/edit banner -->
    {include file='blocks/m_block_start.tpl'}

    <div style="margin: 5px 10px 10px;">
        <form onsubmit="return preSubmit();" action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{else}edit&amp;id={$smarty.get.id}{/if}" method="post" enctype="multipart/form-data">
            <input type="hidden" name="submit" value="1" />
            <input type="submit" name="banners-submit" class="hide" value="1" />
            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="postSubmit" value="1" />
            {/if}

            <!-- display plans -->
            <fieldset class="light">
                <legend id="legend_plans" class="up" onclick="fieldset_action('plans');">{$lang.plans}</legend>
                <div id="plans">
                    {if !empty($plans)}

                    {foreach from=$plans item='plan' name='fPlan' key='fKey'}
                        {if $sPost.plan && $plan.ID == $sPost.plan}
                            {assign var='sPlan' value=$plan}
                        {else}
                            {if $smarty.foreach.fPlan.first && $smarty.get.action == 'add'}
                                {assign var='sPlan' value=$plan}
                            {/if}
                        {/if}

                        {if $smarty.get.action == 'edit' && $sPlan.ID == $plan.ID}
                            {assign var='checked_plan' value=true}
                        {else}
                            {assign var='checked_plan' value=false}
                        {/if}

                        <div class="plan_item {if $smarty.get.action == 'edit' && !$checked_plan}hide{/if}">
                            <table class="sTable">
                            <tr>
                                <td align="center" style="width: 30px">
                                    <input {if $sPlan.ID == $plan.ID}checked="checked"{/if} style="margin: 0 10px 0 0;" index="{$fKey}" id="plan_{$plan.ID}" type="radio" name="plan" value="{$plan.ID}" />
                                </td>
                                <td>
                                    <label for="plan_{$plan.ID}" class="blue_11_normal">
                                        {assign var='l_type' value=$plan.Type|cat:'_plan'}
                                        {$plan.name} - <b>{if $plan.Price > 0}{$config.system_currency}{$plan.Price}{else}{$lang.free}{/if}</b>
                                    </label>
                                    <div>{$plan.des|replace:"\n":"<br />"}</div>
                                </td>
                            </tr>
                            </table>
                        </div>
                    {/foreach}

                    {if $smarty.get.action == 'edit' && $plans|@count > 1}
                        <input type="button" name="manage" value="{$lang.manage}" />
                        <script type="text/javascript">
                        var manage_plans = false;
                        {literal}
                            $(document).ready(function() {
                                $('input[name=manage]').click(function() {
                                    if ( !manage_plans ) {
                                        $('div#plans > .plan_item').fadeIn('normal');
                                        $(this).val('{/literal}{$lang.apply}{literal}')
                                        manage_plans = true;
                                    }
                                    else {
                                        $('div#plans input[type=radio]:not(:checked)').closest('div.plan_item').fadeOut('fast');
                                        $(this).val('{/literal}{$lang.manage}{literal}')
                                        manage_plans = false;
                                    }
                                });
                            });
                        {/literal}
                        </script>
                    {/if}

                    {else}
                        {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=banners&amp;module=banner_plans&amp;action=add">$1</a>'}
                        <span class="field_description">{$lang.banners_addPlanHint|regex_replace:'/\[(.*)\]/':$replace}</span>
                    {/if}
                </div>
            </fieldset>
            <!-- display plans end -->

            {if !empty($plans)}
            <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.set_owner}</td>
                <td class="field">
                    <input type="text" name="username" id="username" value="{if $sPost.username_tmp}{$sPost.username_tmp}{else}{$sPost.username}{/if}" />

                    <script type="text/javascript">
                    {literal}
                        $('#username').rlAutoComplete({add_id:true, id:false});
                    {/literal}
                    </script>
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span>{$lang.status}</td>
                <td class="field">
                    <select name="status" class="login_input_select">
                        <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.title}
                </td>
                <td class="field">
                    {if $allLangs|@count > 1}
                        <ul class="tabs">
                            {foreach from=$allLangs item='language' name='langF'}
                            <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                            {/foreach}
                        </ul>

                        {foreach from=$allLangs item='language' name='langF'}
                            <div class="tab_area{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
                                <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                                <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                            </div>
                        {/foreach}
                    {else}
                        <input type="text" name="name" value="{$sPost.name}" maxlength="350" />
                    {/if}
                </td>
            </tr>
            <tr>
                <td class="name"><span class="red">*</span> {$lang.banners_bannerSettings}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_banner_settings" class="up" onclick="fieldset_action('banner_settings');">{$lang.banners_bannerSettings}</legend>
                        <div id="banner_settings">
                            <table class="form">
                            <tr>
                                <td class="name"><span class="red">*</span>{$lang.banners_bannerBox}</td>
                                <td class="field">
                                    {if !empty($sPlan.boxes)}
                                    <select name="banner_box">
                                    {foreach from=$sPlan.boxes item='box' name='fBox'}
                                        {if $sPost.banner_box == $box.Key}
                                            {assign var='sBox' value=$box}
                                        {else}
                                            {if $smarty.foreach.fBox.first}
                                                {assign var='sBox' value=$box}
                                            {/if}
                                        {/if}
                                        <option {if $sBox.Key == $box.Key}selected="selected"{/if} size="{$box.width}:{$box.height}:{$sBox.side}" value="{$box.Key}">{$box.name}</option>
                                    {/foreach}
                                    </select>
                                    <label id="banner_box_qt">
                                        {assign var='heightTempl' value=`$smarty.ldelim`height`$smarty.rdelim`}
                                        {assign var="sideTempl" value=`$smarty.ldelim`side`$smarty.rdelim`}
                                        {assign var="widthTeml" value=`$smarty.ldelim`width`$smarty.rdelim`}
                                        {$lang.banners_bannerBoxNotice|replace:$sideTempl:$sBox.side|replace:$widthTeml:$sBox.width|replace:$heightTempl:$sBox.height}
                                    </label>
                                    {else}
                                        {assign var='replace' value='<a target="_blank" class="static" href="'|cat:$rlBase|cat:'index.php?controller=banners&amp;module=banner_boxes&amp;action=add">$1</a>'}
                                        <span class="field_description">{$lang.banners_addBoxHint|regex_replace:'/\[(.*)\]/':$replace}</span>
                                    {/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name"><span class="red">*</span>{$lang.banners_bannerType}</td>
                                <td class="field">
                                    <select name="banner_type">
                                    {foreach from=$sPlan.types item='type'}
                                        <option {if $sPost.banner_type == $type.Key}selected="selected"{/if} value="{$type.Key}">{$type.name}</option>
                                    {/foreach}
                                    </select>

                                    <span id="banners_type_html_responsive" class="field_description_noicon">{$lang.banners_html_responsive}
                                        <input {if $sPost.responsive}checked="checked"{/if} type="checkbox" name="responsive" value="1" />
                                    </span>
                                </td>
                            </tr>
                            </table>
                        </div>
                    </fieldset>
                </td>
            </tr>

            <tr id="banners_type_image_link" class="banners_type {if $sPost.banner_type && $sPost.banner_type != 'image'}hide{/if}">
                <td class="name">{$lang.banners_bannerLink}</td>
                <td class="field">
                    <input type="text" name="link" value="{$sPost.link}" />
                    <span class="field_description_noicon">{$lang.banners_tag_nofollow}
                        <input {if $sPost.nofollow}checked="checked"{/if} type="checkbox" name="nofollow" value="1" />
                    </span>
                </td>
            </tr>

            <tr id="banners_type_html" class="banners_type {if $sPost.banner_type != 'html'}hide{/if}">
                <td class="name"><span class="red">*</span> {$lang.banners_bannerType_html}</td>
                <td class="field">
                    <div class="hide">{$sPost.html}</div>
                    <textarea id="banner_html" name="html" rows="3" cols="">{$sPost.html}</textarea>
                </td>
            </tr>
            </table>
            {/if}
        </form>

        {if !$alerts}
        <table id="banners_type_image" class="form banners_type {if $sPost.banner_type && $sPost.banner_type != 'image'}hide{/if}">
        <tr>
            <td class="name">{$lang.pictures_manager}</td>
            <td class="field">
                <fieldset class="light">
                    <legend id="legend_banner_upload" class="up" onclick="fieldset_action('banner_upload');">{$lang.pictures_manager}</legend>
                    <div id="banner_upload">
                        {include file=$smarty.const.RL_PLUGINS|cat:'banners/upload/manager.tpl'}
                    </div>
                </fieldset>
            </td>
        </tr>
        </table>

        <table class="form">
        <tr>
            <td class="field">
                <input type="button" onclick="$('input[name=banners-submit]').click();" value="{if $smarty.get.action == 'add'}{$lang.add}{else}{$lang.edit}{/if}" />
            </td>
        </tr>
        </table>
        {/if}
    </div>

    {include file='blocks/m_block_end.tpl'}
    <!-- add/edit banner end -->

    {if !empty($plans)}
    <script type="text/javascript">
        lang['banners_unsaved_photos_notice'] = '{$lang.banners_unsaved_photos_notice}';
        lang['banners_remove_images_notice'] = '{$lang.banners_remove_images_notice}';

        var isEditMode = {if $smarty.get.action == 'edit'}true{else}false{/if};
        var sPlan = new Object;
        var plans = eval('{$plansJson}');
        var boxesSelector = $('select[name=banner_box]');
        var typeSelector = $('select[name=banner_type]');
        var prevIndex = parseInt($('input[id^=plan_]:checked').attr('index'));
        var banner_saved_type = '{$sPost.banner_type}';
        var banner_current_type = null;

        {literal}

        function preSubmit() {
            if ( banner_current_type == 'image' ) {
                // check for not uploaded photos
                var not_saved = $('#fileupload span.template-download').length;
                if ( not_saved == 0 ) {
                    $('#fileupload span.template-upload').addClass('suspended');
                    printMessage('error', lang['banners_unsaved_photos_notice']);
                    return false;
                }
                else {
                    return true;
                }
            }

            return true;
        }

        $(document).ready(function() {
            banner_current_type = typeSelector.val();
            updateTriggers();

            $('input[id^=plan_]').click(function() {
                var index = parseInt($(this).attr('index'));
                if ( prevIndex == index ) return false;

                if ( false !== isMediaUploaded() ) {
                    Ext.MessageBox.confirm(lang['alert'], lang['banners_changeBannerPlanNotice'], function(btn) {
                        if ( btn == 'yes' ) {
                            changeBannerPlan(index);
                        }
                        else {
                            $('input[type=radio][index='+ prevIndex +']').prop('checked', true);
                        }
                    });
                }
                else {
                    changeBannerPlan(index);
                }
            });

            boxesSelector.change(function() {
                removeMedia();

                var boxInfo = $(this).find('option:selected').attr('size').split(':');
                photo_width = parseInt(boxInfo[0]);
                photo_height = parseInt(boxInfo[1]);

                var noticeText = '{/literal}{$lang.banners_bannerBoxNotice|escape:"quotes"}{literal}'.replace('{side}', boxInfo[2]).replace('{width}', boxInfo[0]).replace('{height}', boxInfo[1]);
                $('label#banner_box_qt').html(noticeText);
                $('div#fileupload #size-notice').html('<b>'+ photo_width +'</b> x <b>'+ photo_height +'</b>');
                $('input[name=box_width]').val(photo_width);
                $('input[name=box_height]').val(photo_height);
                $('input[name=files]').css({ height: photo_height });
                $('div#fileupload span.draft').css({ width: photo_width, height: photo_height, 'line-height': photo_height +'px' });

                $('#fileupload').fileupload('option', {
                        previewMaxWidth: photo_width,
                        previewMaxHeight: photo_height
                    }
                );
                forceChangeStyles();
            });

            typeSelector.change(function() {
                removeMedia();

                banner_current_type = $(this).val();
                updateTriggers();
            });
        });

        function updateTriggers() {
            $('.banners_type').hide();
            $('#banners_type_'+ banner_current_type).show();

            stTrigger('image', '#banners_type_image_link');
            stTrigger('html', '#banners_type_html_responsive');
        }

        function stTrigger(type, selector) {
            $(selector)[banner_current_type === type ? 'show' : 'hide']();
        }

        function changeBannerPlan(index) {
            prevIndex = index;
            sPlan = plans[index];

            // made boxes
            boxesSelector.find('option').remove();
            var newBoxOptions = '';

            for (var i=0; i < sPlan.boxes.length; i++) {
                newBoxOptions += '<option size="'+ sPlan.boxes[i].width +':'+ sPlan.boxes[i].height +':'+ sPlan.boxes[i].side +'" value="'+ sPlan.boxes[i].Key +'">'+ sPlan.boxes[i].name +'</option>';
            };

            boxesSelector.html(newBoxOptions).change().fadeOut('fast', function() {
                $(this).fadeIn('fast');
            });

            // made types
            typeSelector.find('option').remove();
            var newTypeOptions = '';

            for (var j=0; j < sPlan.types.length; j++) {
                newTypeOptions += '<option value="'+ sPlan.types[j].Key +'">'+ sPlan.types[j].name +'</option>';
            };

            typeSelector.html(newTypeOptions).change().fadeOut('fast', function() {
                $(this).fadeIn('fast');
            });
        }

        function isMediaUploaded() {
            return !!$('#fileupload img.cancel, #fileupload img.delete').length;
        }

        function removeMedia() {
            if (false !== isMediaUploaded())
                $('#fileupload img.cancel, #fileupload img.delete').click();
        }

        function forceChangeStyles() {
            for ( var i = 0; i < document.styleSheets.length; i++ ) {
                if ( document.styleSheets[i].title == 'banners' ) {
                    var bannerStyles = new Object;
                    if ( document.styleSheets[i].cssRules ) {
                        bannerRules = document.styleSheets[i].cssRules;
                    }
                    else {
                        bannerRules = document.styleSheets[i].rules;
                    }

                    for ( var j = 0; j < bannerRules.length; j++ ) {
                        if ( bannerRules[j].selectorText == 'div#fileupload span.active, div#fileupload span.hover' ) {
                            bannerRules[j].style.width = (photo_width + 4) +'px';
                            bannerRules[j].style.height = photo_height +'px';
                        }
                        else if ( bannerRules[j].selectorText == 'canvas.new, img.thumbnail' ) {
                            bannerRules[j].style.width = photo_width +'px';
                            bannerRules[j].style.height = photo_height +'px';
                        }
                    }
                }
            }
        }
        {/literal}
    </script>
    {/if}

    {/if}

    {else}
    <div id="grid"></div>
    <script>
    var bannersGrid;
    var sortByPlan = {if $smarty.get.plan}{$smarty.get.plan}{else}false{/if};
    var sortByBox = {if $smarty.get.box}'{$smarty.get.box}'{else}false{/if};
    var sortByID = {if $smarty.get.filter}'{$smarty.get.filter}'{else}false{/if};

    var mass_actions = [
        [lang['ext_activate'], 'activate'],
        [lang['ext_suspend'], 'approve'],
        [lang['ext_delete'], 'delete']
    ];
    var options = (
        (sortByPlan ? '&plan='+ sortByPlan : false) ||
        (sortByBox ? '&box='+ sortByBox : false) ||
        (sortByID ? '&filter='+ sortByID : false)
    );

    {literal}

    var deleteBanner = function(id) {
        $.post(rlConfig.ajax_url, {
            'item': 'bannersDeleteBanner',
            'id': id
        }, function(response) {
            if (response.status === 'OK') {
                printMessage('notice', response.message);
                bannersGrid.reload();
            } else if (response.status === 'ERROR') {
                printMessage('error', response.message);
            }
        }, 'json')
    };

    var massActions = function(ids, action) {
        $.post(rlConfig.ajax_url, {
            'item': 'bannersMassActions',
            'action': action,
            'ids': ids
        }, function(response) {
            if (response.status === 'OK') {
                printMessage('notice', response.message);
                bannersGrid.reload();
            } else if (response.status === 'ERROR') {
                printMessage('error', response.message);
            }
        }, 'json')
    };

    $(document).ready(function(){

        bannersGrid = new gridObj({
            key: 'banners',
            id: 'grid',
            ajaxUrl: rlPlugins + 'banners/admin/banners.inc.php?q=ext'+ (options ? options : ''),
            defaultSortField: false,
            title: lang['banners_bannersTitleOfManager'],
            checkbox: true,
            actions: mass_actions,
            expander: true,
            reloadOnUpdate: true,
            expanderTpl: '<div style="margin: 0 5px 5px 83px"><table><tr><td class="banner-thumbnail">{thumbnail}</td></tr></table><div>',
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'name', mapping: 'name', type: 'string'},
                {name: 'Key', mapping: 'Key'},
                {name: 'name', mapping: 'name'},
                {name: 'Side', mapping: 'Side'},
                {name: 'Type', mapping: 'Type', type: 'string'},
                {name: 'Status', mapping: 'Status'},
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'int'},
                {name: 'Shows', mapping: 'Shows', type: 'int'},
                {name: 'clicks', mapping: 'clicks', type: 'int'},
                {name: 'Plan_ID', mapping: 'Plan_name'},
                {name: 'Plan_info', mapping: 'Plan_info'},
                {name: 'thumbnail', mapping: 'thumbnail', type: 'string'},
                {name: 'Date_release', mapping: 'Date_release', type: 'date', dateFormat: 'timestamp'},
                {name: 'Last_show', mapping: 'Last_show', type: 'date', dateFormat: 'timestamp'},
                {name: 'Pay_date', mapping: 'Pay_date', type: 'date', dateFormat: 'timestamp'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['ext_title'],
                    dataIndex: 'name',
                    width: 23
                },{
                    header: lang['ext_owner'],
                    dataIndex: 'Username',
                    width: 8,
                    id: 'rlExt_item_bold',
                    renderer: function(username, ext, row){
                        return "<a target='_blank' ext:qtip='"+lang['ext_click_to_view_details']+"' href='"+rlUrlHome+"index.php?controller=accounts&action=view&userid="+row.data.Account_ID+"'>"+username+"</a>"
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 5
                },{
                    header: lang['ext_add_date'],
                    dataIndex: 'Date_release',
                    hidden: true,
                    width: 10,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['ext_payed'],
                    dataIndex: 'Pay_date',
                    width: 8,
                    renderer: function(val, ext, row){
                        if ( row.json.Pay_date == '0' ) {
                            var date = '<span class="delete" ext:qtip="'+ lang['ext_click_to_set_pay'] +'">'+ lang['ext_not_payed'] +'</span>';
                        }
                        else {
                            var date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                            date = '<span class="build" ext:qtip="'+ lang['ext_click_to_edit'] +'">'+ date +'</span>';
                        }
                        return date;
                    },
                    editor: new Ext.form.DateField({
                        format: 'Y-m-d H:i:s'
                    })
                },{
                    header: '{/literal}{$lang.banners_bannerShows}{literal}',
                    dataIndex: 'Shows',
                    width: 7,
                    id: 'rlExt_black_bold'
                },{
                    header: '{/literal}{$lang.banners_bannerClicks}{literal}',
                    dataIndex: 'clicks',
                    width: 5,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['banners_bannerLastShow'],
                    dataIndex: 'Last_show',
                    hidden: true,
                    width: 10,
                    renderer: function(val, ext, row) {
                        if ( row.json.Last_show == '0' ) {
                            var date = '<span class="delete">'+ lang['banners_bannerNeverShow'] +'</span>';
                        }
                        else {
                            var date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M') +' H:s')(val);
                        }
                        return date;
                    }
                },{
                    header: lang['ext_plan'],
                    dataIndex: 'Plan_ID',
                    width: 11,
                    renderer: function (val, obj, row){
                        return '<img class="info" ext:qtip="'+ row.data.Plan_info +'" alt="" src="'+ rlUrlHome +'img/blank.gif" />&nbsp;&nbsp;<span>'+ val +'</span>';
                    }
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
                    })
                },{
                    header: lang['ext_actions'],
                    width: 90,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: true,
                    renderer: function(data) {
                        var out = "<center>";
                        out += "<a href='"+ rlUrlHome +"index.php?controller="+ controller +"&action=edit&id="+ data +"'><img class='edit' ext:qtip='"+ lang['ext_edit'] +"' src='"+ rlUrlHome +"img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='"+ lang['ext_delete'] +"' src='"+ rlUrlHome +"img/blank.gif' onclick='rlConfirm( \""+ lang['ext_notice_delete']+"\", \"deleteBanner\", \""+ Array(data) +"\", \"section_load\" )' />";
                        out += "</center>";
                        return out;
                    }
                }
            ]
        });

        bannersGrid.init();
        grid.push(bannersGrid.grid);

        // actions listener
        bannersGrid.actionButton.addListener('click', function() {
            var sel_obj = bannersGrid.checkboxColumn.getSelections();
            var action = bannersGrid.actionsDropDown.getValue();

            if (!action) {
                return false;
            }

            for( var i = 0; i < sel_obj.length; i++ ) {
                bannersGrid.ids += sel_obj[i].id;
                if ( sel_obj.length != i+1 ) {
                    bannersGrid.ids += '|';
                }
            }

            switch (action) {
                case 'activate':
                case 'approve':
                    Ext.MessageBox.confirm('Confirm', lang['banners_massActionsNotice'], function(btn) {
                        if (btn === 'yes') {
                            massActions(bannersGrid.ids, action);
                        }
                    });
                    break;
                case 'delete':
                    Ext.MessageBox.confirm('Confirm', lang['ext_notice_delete'], function(btn) {
                        if (btn === 'yes') {
                            massActions(bannersGrid.ids, action);
                        }
                    });
                    break;
            }
            bannersGrid.checkboxColumn.clearSelections();
        });
    });
    {/literal}
    </script>
    {/if}
{/if}

{if isset($smarty.get.module)}
    <script>{literal}
        var prepareDeleting = function(id) {
            $.post(rlConfig.ajax_url, {
                'item': 'bannersPrepareDeleting',
                'id': id
            }, function(response) {
                if (response.status === 'OK') {
                    if (typeof(response.func) !== 'undefined') {
                        if (response.func === 'deleteBox') {
                            deleteBox(id);
                        } else if (response.func === 'deletePlan') {
                            deletePlan(id);
                        }
                    } else if (typeof(response.html) !== 'undefined') {
                        $('#delete_container').html(response.html);
                        $('#delete_block').show();
                    }
                } else if (response.status === 'ERROR') {
                    printMessage('error', response.message);
                }
            }, 'json');
        };

        var deleteBox = function(key) {
            $.post(rlConfig.ajax_url, {
                'item': 'bannersDeleteBannerBox',
                'key': key
            }, function(response) {
                if (response.status === 'OK') {
                    printMessage('notice', response.message);
                    $('#delete_block').hide();
                    bannerBoxes.reload();
                } else if (response.status === 'ERROR') {
                    printMessage('error', response.message);
                }
            }, 'json')
        };

        var deletePlan = function(id) {
            $.post(rlConfig.ajax_url, {
                'item': 'bannersDeletePlan',
                'id': id
            }, function(response) {
                if (response.status === 'OK') {
                    printMessage('notice', response.message);
                    $('#delete_block').hide();
                    bannerPlans.reload();
                } else if (response.status === 'ERROR') {
                    printMessage('error', response.message);
                }
            }, 'json')
        };
    {/literal}</script>
{/if}
<!-- banners tpl -->
