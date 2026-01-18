<!-- coupon tpl -->

<!-- navigation bar -->
<div id="nav_bar">
    {if !isset($smarty.get.action)}
        <a onclick="show('search');" href="javascript:void(0)" class="button_bar"><span class="left"></span><span class="center_search">{$lang.search}</span><span class="right"></span></a>
    {/if}
    <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span><span class="center_add">{$lang.add_coupon}</span><span class="right"></span></a>
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="left"></span><span class="center_list">{$lang.coupons_list}</span><span class="right"></span></a>
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'edit' || $smarty.get.action == 'add'}

    {assign var='sPost' value=$smarty.post}

    <!-- add new news -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&amp;coupon={$smarty.get.coupon}{/if}" method="post">
    <input type="hidden" name="submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
    {/if}

    <table class="form">
    <tr>
        <td class="name"><span class="red">*</span>{$lang.coupon_code}</td>
        <td class="field">
            <input style="width: 150px;" id="generate" value="{$smarty.post.generate_coupon_code}" name="generate_coupon_code" type="text"/>
            <a id="generate_code" href="javascript:void(0);">{$lang.coupon_generate_code}</a>
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span>{$lang.coupon_type}</td>
        <td class="field">
            <select name="type" class="login_input_select lang_add" style="width:150px;">
                <option value="percent" {if $sPost.type == 'percent'}selected{/if}>{$lang.coupon_percent}</option>
                <option value="cost" {if $sPost.type == 'cost'}selected{/if}>{$lang.coupon_cost}</option>
            </select>
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span>{$lang.coupon_discount}</td>
        <td class="field">
            <input class="numeric" style="text-align: center; width: 50px;" name="coupon_discount" value="{$smarty.post.coupon_discount}" type="text" size="4" maxlength="4"/>
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.used_date}</td>
        <td class="field">
            {if $sPost.used_date == 'yes'}
                {assign var='used_date_yes' value='checked="checked"'}
                {assign var='date' value='1'}
            {elseif $sPost.used_date == 'no'}
                {assign var='used_date_no' value='checked="checked"'}
                {assign var='date' value='0'}
            {else}
                {assign var='used_date_yes' value='checked="checked"'}
                {assign var='date' value='1'}
            {/if}
            <label><input {$used_date_yes} type="radio" name="used_date" value="yes" > {$lang.yes}</label>
            <label><input {$used_date_no} type="radio" name="used_date" value="no" > {$lang.no}</label>
        </td>
    </tr>
    <tr id="available_coupone" {if $date != 1}class="hide"{/if}>
        <td class="name"><span class="red">*</span>{$lang.available_coupone}</td>
        <td class="field">
            <input value="{$smarty.post.date_from}"
                   name="date_from"
                   type="text"
                   size="12"
                   maxlength="10"
                   id="date_pick_from"
                   class="wauto"
                   autocomplete="off"
            />
            <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
            <input value="{$smarty.post.date_to}"
                   name="date_to"
                   type="text"
                   size="12"
                   maxlength="10"
                   id="date_pick_to"
                   class="wauto"
                   autocomplete="off"
            />
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span>{$lang.using_limit}</td>
        <td class="field">
            <input style="width: 50px;" class="numeric" name="using_limit" value="{$smarty.post.using_limit}" type="text" size="4" maxlength="4"/>
            <span class="field_description">{$lang.coupon_using_limit_tip}</span>
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span>{$lang.coupon_for}</td>
        <td class="field">
            {if $sPost.account_or_type == 'type'}
                {assign var='account_or_type' value='checked="checked"'}
                {assign var='account_show' value='1'}
            {elseif $sPost.account_or_type == 'account'}
                {assign var='account_or_type_no' value='checked="checked"'}
                {assign var='account_show' value='0'}
            {else}
                {assign var='account_or_type' value='checked="checked"'}
                {assign var='account_show' value='1'}
            {/if}
            <label><input {$account_or_type} type="radio" name="account_or_type" value="type" > {$lang.account_type}</label>
            <label><input {$account_or_type_no} type="radio" name="account_or_type" value="account" > {$lang.coupon_account}</label>
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span>{if $sPost.account_or_type == 'type' || !isset($sPost.account_or_type)}{$lang.coupon_enable_for}{else}{$lang.coupon_enter_username}{/if}</td>
        <td class="field">
            <fieldset id="account_or_type" class="light{if $account_show == '0'} hide{/if}">
                <legend id="legend_accounts_tab_area" class="up" onclick="fieldset_action('accounts_tab_area');">{$lang.account_type}</legend>
                <div id="accounts_tab_area" style="padding: 0 10px 10px 10px;">
                    <table>
                    <tr>
                        <td>
                            <table>
                            <tr>
                            {foreach from=$account_types item='a_type' name='ac_type'}
                                <td>
                                    <div style="margin: 0 20px 0 0;">
                                        <input {if $sPost.account_type && $a_type.Key|in_array:$sPost.account_type}checked="checked"{/if}
                                               style="margin-bottom: 0px;"
                                               type="checkbox"
                                               id="account_type_{$a_type.ID}"
                                               value="{$a_type.Key}"
                                               name="account_type[]"
                                        /> <label for="account_type_{$a_type.ID}">{$a_type.name}</label>
                                    </div>
                                </td>

                            {if $smarty.foreach.ac_type.iteration%3 == 0 && !$smarty.foreach.ac_type.last}
                            </tr>
                            <tr>
                            {/if}

                            {/foreach}
                            </tr>
                            </table>
                        </td>
                    </tr>
                    </table>

                    <div class="grey_area" style="margin: 8px 0 0;">
                        <span onclick="$('#accounts_tab_area input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                        <span class="divider"> | </span>
                        <span onclick="$('#accounts_tab_area input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                    </div>
                </div>
            </fieldset>

            <fieldset id="username" class="light{if $account_show == '1'} hide{/if}">
                <legend id="legend_username_tab_area" class="up" onclick="fieldset_action('username_tab_area');">{$lang.username}</legend>
                <div id="username_tab_area" style="padding: 0 10px 10px 10px;">
                    <input type="text" style="width:150px" name="username" value="{$smarty.post.username}" id="Account" />
                </div>
            </fieldset>
            <script type="text/javascript">
            {literal}

            $(document).ready(function(){
                $('input[name=used_date]').change(function(){
                    aditionalBlockHandlers();
                });

                var aditionalBlockHandlers = function(){
                    var value = $('input[name=used_date]:checked').val();

                    if ( value == 'yes' )
                    {
                        $('#available_coupone').show();
                    }
                    else
                    {
                        $('#available_coupone').hide();
                    }
                    return;
                };

                $('input[name=account_or_type]').change(function(){
                    aditionalBlockHandler();
                });

                var aditionalBlockHandler = function(){
                    var value = $('input[name=account_or_type]:checked').val();
                    var name = '';
                    if (value == 'type') {
                        $('#account_or_type').show();
                        $('#username').hide();
                        name = '<span class="red">*</span>{/literal}{$lang.coupon_enable_for}{literal}';
                    } else {
                        $('#account_or_type').hide();
                        $('#username').show();
                        name = '<span class="red">*</span>{/literal}{$lang.coupon_enter_username}{literal}';
                    }
                    $('input[name=account_or_type]').parent().parent().parent().next('tr').find('td.name').html(name);
                    return;
                };

                /* autocomplete js */
                $('#Account').rlAutoComplete();
            });
            {/literal}
            </script>
        </td>
    </tr>
    <tr>
        <td class="name"><span class="red">*</span> {$lang.coupon_available_for}</td>
        <td class="field">
            <fieldset class="light">
                <legend id="legend_coupon_services" class="up" onclick="fieldset_action('coupon_services');">{$lang.coupon_services}</legend>
                <div id="coupon_services" style="padding: 0 10px 10px 10px;">
                    <table>
                        <tr>
                        {foreach from=$services item='coupon_service' name='couponServiceF'}
                            <td>
                                <div style="padding: 2px 8px 2px 0;">
                                    <label>
                                        <input {if $sPost.services && $coupon_service.Key|in_array:$sPost.services}checked="checked"{/if}
                                               style="margin-bottom: 0px;"
                                               type="checkbox"
                                               value="{$coupon_service.Key}"
                                               name="services[]"
                                        /> {$coupon_service.name}
                                    </label>
                                </div>
                            </td>
                        {if $smarty.foreach.couponServiceF.iteration%3 == 0 && !$smarty.foreach.couponServiceF.last}
                        </tr>
                        <tr>
                        {/if}
                        {/foreach}
                        </tr>
                    </table>
                    <div class="grey_area" style="margin: 8px 0 0;">
                        <span onclick="servicesControl(true);" class="green_10">{$lang.check_all}</span>
                        <span class="divider"> | </span>
                        <span onclick="servicesControl(false);" class="green_10">{$lang.uncheck_all}</span>
                    </div>
                </div>
            </fieldset>
        </td>
    </tr>
    {foreach from=$services item='service'}
        {if $service.items}
            <tr id="service-items-{$service.Key}" class="hide">
                <td class="name">{$service.name}</td>
                <td class="field">
                    <fieldset class="light">
                        <legend id="legend_{$service.Key}" class="up" onclick="fieldset_action('plan');">{$service.title}</legend>
                        <div id="plan">
                            <div id="plan_checkboxed_{$service.Key}" style="margin: 0px 11px 5px;" {if !empty($sPost.show_on_all[$service.Key])}class="hide"{/if}>
                                <table>
                                    {foreach from=$service.items item='item'}
                                    <tr>
                                        <td>
                                            <label>
                                                <input type="checkbox"
                                                       name="service[{$service.Key}][]"
                                                       {if $sPost.service && $item.ID|in_array:$sPost.service[$service.Key]}checked="checked"{/if}
                                                       value="{$item.ID}"
                                                /> {$item.name}
                                            </label>
                                        </td>
                                    </tr>
                                    {/foreach}
                                </table>
                            </div>
                            <div class="grey_area">
                                <label><input class="checkbox show-on-all" {if $sPost.show_on_all[$service.Key]}checked="checked"{/if} type="checkbox" data-item="{$service.Key}" name="show_on_all[{$service.Key}]" value="true" /> {$lang.sticky}</label>
                                <span id="plan_nav_{$service.Key}" {if $sPost.show_on_all[$service.Key]}class="hide"{/if}>
                                    <span onclick="$('#plan_checkboxed_{$service.Key} label input').prop('checked', true);" class="green_10">{$lang.check_all}</span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#plan_checkboxed_{$service.Key} label input').prop('checked', false);" class="green_10">{$lang.uncheck_all}</span>
                                </span>
                            </div>
                        </div>
                    </fieldset>
                </td>
            </tr>
        {/if}
    {/foreach}
    <script type="text/javascript">
    {literal}

    $(document).ready(function(){
        $('.show-on-all').click(function(){
            var key = $(this).attr('data-item');
            $('#plan_checkboxed_' + key).slideToggle();
            $('#plan_nav_' + key).fadeToggle();
        });
        $('#coupon_services label input[name="services[]"]').each(function() {
            servicesHandle($(this).val(), $(this).is(':checked'), false);
        });
        $('#coupon_services label input[name="services[]"]').click(function() {
            servicesHandle($(this).val(), $(this).is(':checked'), true);
        });
    });

    var servicesControl = function(mode) {
        $('#coupon_services input').prop('checked', mode);
        $('#coupon_services label input[name="services[]"]').each(function() {
            servicesHandle($(this).val(), $(this).is(':checked'));
        });
    }

    var servicesHandle = function(service, is_checked, is_click) {
        if (is_checked) {
            $('#service-items-' + service).removeClass('hide');
        } else {
            $('#service-items-' + service).addClass('hide');
            if (is_click) {
                $('#service-items-' + service + ' input[type="checkbox"]').each(function() {
                    $(this).prop('checked', false);
                });
            }
        }
    }

    {/literal}
    </script>
    {if $isShoppingInstalled}
        <tr>
            <td class="name">{$lang.coupon_shopping}</td>
            <td class="field">
                {if $sPost.shopping == 'yes'}
                    {assign var='shopping_yes' value='checked="checked"'}
                {elseif $sPost.shopping == 'no'}
                    {assign var='shopping_no' value='checked="checked"'}
                {else}
                    {assign var='shopping_yes' value='checked="checked"'}
                {/if}
                <label><input {$shopping_yes} type="radio" name="shopping" value="yes" > {$lang.yes}</label>
                <label><input {$shopping_no} type="radio" name="shopping" value="no" > {$lang.no}</label>
                <span class="field_description">{$lang.coupon_shopping_tip}</span>
            </td>
        </tr>
    {/if}
    {if $isBookingInstalled}
    <tr>
        <td class="name">{$lang.coupon_booking}</td>
        <td class="field">
            {if $sPost.booking == 'yes'}
                {assign var='booking_yes' value='checked="checked"'}
            {elseif $sPost.booking == 'no'}
                {assign var='booking_no' value='checked="checked"'}
            {else}
                {assign var='booking_yes' value='checked="checked"'}
            {/if}
            <label><input {$booking_yes} type="radio" name="booking" value="yes" > {$lang.yes}</label>
            <label><input {$booking_no} type="radio" name="booking" value="no" > {$lang.no}</label>
        </td>
    </tr>
    {/if}
    <tr>
        <td class="name"><span class="red">*</span>{$lang.status}</td>
        <td class="field">
            <select name="status" class="login_input_select lang_add" style="width:150px;">
                <option value="active" {if $sPost.status == 'active'}selected{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>
    <tr>
        <td></td>
        <td class="field">
            <input class="button lang_add" type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
        </td>
    </tr>
    </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}


    <script type="text/javascript">
        {literal}
        $(document).ready(function(){
        $('#generate_code').click(function(){
            var rand='';
            for(var i = 0; i < 3; i++)
            {
                rand +=  String.fromCharCode(97 + Math.round(Math.random() * 25));
                rand +=  String.fromCharCode(65 + Math.round(Math.random() * 25));
                rand +=  Math.round(Math.random() * 25);
            }
            $('#generate').val(rand);
            });
        });
        $(function() {
            $('#date_pick_from').datepicker({showOn: 'both', buttonImage: '{/literal}{$rlTplBase}{literal}img/blank.gif', buttonImageOnly: true, dateFormat: 'yy-mm-dd', minDate: 0}).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
        });
        $(function() {
            $('#date_pick_to').datepicker({showOn: 'both', buttonImage: '{/literal}{$rlTplBase}{literal}img/blank.gif', buttonImageOnly: true, dateFormat: 'yy-mm-dd', minDate: 0}).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
        });
        {/literal}


    </script>

    <!-- add new news end -->
{else}

    <!-- Search -->
    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
        <table>
        <tr>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.coupon_code}</td>
                    <td class="field">
                        <input type="text" id="code" maxlength="60" value="{$sPost.coupon.code}" />
                    </td>
                </tr>
                <tr id="has_winner_item" class="hide">
                    <td class="name w130">{$lang.coupon_type}</td>
                    <td class="field">
                        <select id="type" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            <option value="percent" {if $sPost.coupon.type == 'percent'}selected="selected"{/if}>{$lang.coupon_percent}</option>
                            <option value="cost" {if $sPost.coupon.type == 'cost'}selected="selected"{/if}>{$lang.coupon_cost}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.coupon_for}</td>
                    <td class="field">
                        <select id="account_or_type" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            <option value="type" {if $sPost.coupon.account_or_type == 'type'}selected="selected"{/if}>{$lang.account_type}</option>
                            <option value="account" {if $sPost.coupon.account_or_type == 'account'}selected="selected"{/if}>{$lang.coupon_account}</option>
                        </select>
                    </td>
                </tr>
                <tr  id="account_area" class="hide">
                    <td class="name w130">{$lang.username}</td>
                    <td class="field">
                        <input type="text" id="username" maxlength="60" value="{$sPost.coupon.username}" />
                    </td>
                </tr>
                <tr id="account_types" class="hide">
                    <td class="name w130">{$lang.account_type}</td>
                    <td class="field">
                        <select id="account_type" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            {foreach from=$account_types item='a_type' name='ac_type'}
                            <option value="{$a_type.Key}" {if $sPost.coupon.account_type == $a_type.Key}selected{/if}>{$a_type.name}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.status}</td>
                    <td class="field">
                        <select id="status" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            <option value="active" {if $sPost.coupon.status == 'active'}selected{/if}>{$lang.active}</option>
                            <option value="approval" {if $sPost.coupon.status == 'approval'}selected{/if}>{$lang.approval}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td></td>
                    <td class="field">
                        <input id="search_button" type="submit" value="{$lang.search}" />
                        <input type="button" value="{$lang.reset}" id="reset_filter_button" />

                        <a class="cancel" href="javascript:void(0)" onclick="show('search')">{$lang.cancel}</a>
                    </td>
                </tr>
                </table>
            </td>
        </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript">
    {literal}

    var sFields = new Array('code', 'type', 'account_or_type', 'status', 'account_type', 'username');
    var cookie_filters = new Array();

    $(document).ready(function(){
        $('#account_or_type').change(function() {
            if($(this).val() == 'account') {
                $('#account_area').show();
                $('#account_types').hide();
            } else {
                $('#account_area').hide();
                $('#account_types').show();
            }
        });

        if (readCookie('coupon_sc')) {
            $('#search').show();
            cookie_filters = readCookie('coupon_sc').split(',');

            for (var i in cookie_filters) {
                if (typeof(cookie_filters[i]) == 'string') {
                    var item = cookie_filters[i].split('||');
                    $('#'+item[0]).selectOptions(item[1]);

                    if (item[0] == 'account_or_type' && item[1] == 'account') {
                        $('#account_area').show();
                    }

                    if (item[0] == 'account_or_type' && item[1] == 'type') {
                        $('#account_types').show();
                    }
                }
            }

            cookie_filters.push(new Array('search', 1));
        }

        $('#search_button').click(function(){
            var sValues = new Array();
            var filters = new Array();
            var save_cookies = new Array();

            for(var si = 0; si < sFields.length; si++) {
                sValues[si] = $('#'+sFields[si]).val();
                filters[si] = new Array(sFields[si], $('#'+sFields[si]).val());
                save_cookies[si] = sFields[si]+'||'+$('#'+sFields[si]).val();
            }

            // save search criteria
            createCookie('coupon_sc', save_cookies, 1);

            filters.push(new Array('search', 1));

            CouponCodeGrid.filters = filters;
            CouponCodeGrid.reload();
        });

        $('#reset_filter_button').click(function() {
            eraseCookie('coupon_sc');
            CouponCodeGrid.reset();

            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');

            $('#account_area').hide();
            $('#account_types').hide();
            $('#search').hide();
        });

        /* autocomplete js */
        $('#username').rlAutoComplete();
    });

    {/literal}

    </script>
    <!-- end Search -->

    <div id="gridCouponCodeGrid"></div>
    <script type="text/javascript">//<![CDATA[

    var type = [
            [lang['ext_coupon_percent'], 'percent'],
            [lang['ext_coupon_cost'], 'cost']
        ];
    {literal}

    var CouponCodeGrid;
    $(document).ready(function(){

        CouponCodeGrid = new gridObj({
            key: 'coupon',
            id: 'gridCouponCodeGrid',
            ajaxUrl: rlPlugins + 'coupon/admin/coupon.inc.php?q=ext',
            defaultSortField: 'ID',
            filters: cookie_filters,
            title: lang['ext_manager'],
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'Type', mapping: 'Type'},
                {name: 'Discount', mapping: 'Discount'},
                {name: 'Code', mapping: 'Code'},
                {name: 'Status', mapping: 'Status'},
                {name: 'count_uses', mapping: 'count_uses'},
                {name: 'Date_release', mapping: 'Date_release', type: 'date', dateFormat: 'Y-m-d H:i:s'}
            ],
            columns: [
                {
                    header: lang['ext_coupon_code'],
                    dataIndex: 'Code',
                    width: 13,
                    editor: new Ext.form.TextField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_type'],
                    dataIndex: 'Type',
                    width: 11,
                    editor: new Ext.form.ComboBox({
                        store: type,
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    })
                },{
                    header: lang['ext_coupon_discount'],
                    dataIndex: 'Discount',
                    width: 10,
                    editor: new Ext.form.NumberField({
                        allowBlank: false,
                        allowDecimals: false
                    }),
                    renderer: function(val){
                        return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                    }
                },{
                    header: lang['ext_add_date'],
                    dataIndex: 'Date_release',
                    width: 110,
                    fixed: true,
                    renderer:  function(val){
                        var date = Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))(val);
                        date = '<span class="build">'+date+'</span>';
                        return date;
                    }
                },{
                    header: lang['coupon_used_count'],
                    dataIndex: 'count_uses',
                    width: 150,
                    fixed: true,
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
                        mode: 'local',
                        typeAhead: true,
                        triggerAction: 'all',
                        selectOnFocus: true
                    })
                },{
                    header: lang['ext_actions'],
                    width: 110,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        var splitter = false;


                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&action=edit&coupon="+data+"'><img class='edit' ext:qtip='"+lang['ext_edit']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";

                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onclick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"xajax_deleteCoupon\", \""+Array(data)+"\", \"section_load\" )' />";

                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        CouponCodeGrid.init();
        grid.push(CouponCodeGrid.grid);

    });
    {/literal}
    //]]>
    </script>
{/if}

<!-- coupon tpl end -->
