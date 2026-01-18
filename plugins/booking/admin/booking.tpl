<!-- booking system -->

<!-- navigation bar -->
<div id="nav_bar">
    {if $smarty.get.mode == 'booking_fields' && !$smarty.get.action}
        <a href="{$rlBaseC}mode=booking_fields&action=add" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_add">{$lang.add_field}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}

    {if $smarty.get.mode != 'listings'}
        <a href="{$rlBaseC}mode=listings" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.booking_admin_listings_tab}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}

    {if $smarty.get.mode != 'booking_colors'}
        <a href="{$rlBaseC}mode=booking_colors" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.booking_colors}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}

    {if $smarty.get.mode != 'booking_fields' || ($smarty.get.mode == 'booking_fields' && $smarty.get.action)}
        <a href="{$rlBaseC}mode=booking_fields" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.booking_fields_list}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}

    {if $smarty.get.mode || (!$smarty.get.mode && $smarty.get.action == 'view')}
        <a href="{$rlBaseC|replace:'&amp;':''}" class="button_bar">{strip}
            <span class="left"></span>
            <span class="center_list">{$lang.booking_requests}</span>
            <span class="right"></span>
        {/strip}</a>
    {/if}
</div>

<div class="clear" style="*margin: -3px 0; *height: 1px;"></div>
<!-- navigation bar end -->

{assign var='sPost' value=$smarty.post}

{if $smarty.get.mode == 'booking_colors'}
    {include file='blocks/m_block_start.tpl'}

    <form action="{$rlBaseC}mode=booking_colors" method="post" onsubmit="$(this).find('[type=submit]').val('{$lang.loading}').attr('disabled', true);">
        <input type="hidden" name="submit" value="1" />
        <table class="form">
        <tr>
            <td class="name">{$lang.booking_admin_colors_available}</td>
            <td class="field">
                <input type="hidden" name="b[available]" value="{$sPost.b.available}" />
                <input type="hidden" name="b[available_rgb]" value="{$sPost.b.available_rgb}" />
                <div class="colorSelector" id="colorSelector_available">
                    <div style="background-color: {$sPost.b.available}"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.booking_admin_colors_closed}</td>
            <td class="field">
                <input type="hidden" name="b[closed]" value="{$sPost.b.closed}" />
                <input type="hidden" name="b[closed_rgb]" value="{$sPost.b.closed_rgb}" />
                <div class="colorSelector" id="colorSelector_closed">
                    <div style="background-color: {$sPost.b.closed}"></div>
                </div>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.booking_admin_colors_own}</td>
            <td class="field">
                <input type="hidden" name="b[own]" value="{$sPost.b.own}" />
                <input type="hidden" name="b[own_rgb]" value="{$sPost.b.own_rgb}" />
                <div class="colorSelector" id="colorSelector_own" style="display: inline-block;">
                    <div style="background-color: {$sPost.b.own}"></div>
                </div>

                <span class="field_description" style="position: absolute; margin: 10px 10px">{$lang.booking_admin_colors_own_desc}</span>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="field">
                <input type="submit" value="{$lang.save}" />
            </td>
        </tr>
        </table>
    </form>

    <script type="text/javascript">{literal}
        $(document).ready(function() {
            function addColorPicker(step) {
                $('#colorSelector_' + step).ColorPicker({
                    color: $('input[name="b[' + step + ']"]').val(),
                    onShow: function(colpkr) {
                        $(colpkr).fadeIn(500);
                        return false;
                    },
                    onHide: function(colpkr) {
                        $(colpkr).fadeOut(500);
                        return false;
                    },
                    onChange: function(hsb, hex, rgb) {
                        $('#colorSelector_' + step + ' div').css('backgroundColor', '#' + hex);
                        $('#colorSelector_' + step).parent().find('input[name="b[' + step + ']"]').val('#' + hex);
                        $('input[name="b[' + step + '_rgb]"]').val(rgb.r + ',' + rgb.g + ',' + rgb.b);
                    }
                });
            }

            addColorPicker('available');
            addColorPicker('closed');
            addColorPicker('own');
        });
    {/literal}</script>
    {include file='blocks/m_block_end.tpl'}
{elseif $smarty.get.mode == 'booking_fields'}
    {if isset($smarty.get.action)}
        {include file='blocks/m_block_start.tpl'}
        {assign var='sPost' value=$smarty.post}

        <!-- add new field -->
        <form action="{$rlBaseC}mode=booking_fields&action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&field={$smarty.get.field}{/if}" method="post" onsubmit="$(this).find('[type=submit]').val('{$lang.loading}').attr('disabled', true);">
            <input type="hidden" name="submit" value="1" />
            {if $smarty.get.action == 'edit'}
                <input type="hidden" name="fromPost" value="1" />
            {/if}

            <table class="form">
            <tr>
            <td class="name"><span class="red">*</span>{$lang.key}</td>
            <td class="field">
                <input {if $smarty.get.action == 'edit'}readonly{/if} class="{if $smarty.get.action == 'edit'}disabled{else}text{/if} lang_add" name="key" type="text" style="width: 150px;" value="{$sPost.key}" maxlength="30" />
            </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.name}</td>
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
                        <input type="text" name="name[{$language.Code}]" value="{$sPost.name[$language.Code]}" maxlength="350" />
                        {if $allLangs|@count > 1}
                                <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                            </div>
                        {/if}
                    {/foreach}
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.description}</td>
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
                        <textarea rows="" cols="" name="description[{$language.Code}]">{$sPost.description[$language.Code]}</textarea>
                        {if $allLangs|@count > 1}</div>{/if}
                    {/foreach}
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.required_field}</td>
                <td class="field">
                    {if $sPost.required == '1'}
                        {assign var='required_yes' value='checked'}
                    {elseif $sPost.required == '0'}
                        {assign var='required_no' value='checked'}
                    {else}
                        {assign var='required_no' value='checked'}
                    {/if}
                    <input {$required_yes} class="lang_add" type="radio" id="req_yes" name="required" value="1" /> <label for="req_yes">{$lang.yes}</label>
                    <input {$required_no} class="lang_add" type="radio" id="req_no" name="required" value="0" /> <label for="req_no">{$lang.no}</label>
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.status}</td>
                <td class="field">
                    <select name="status">
                        <option value="active" {if $sPost.status == 'active'}selected{/if}>{$lang.active}</option>
                        <option value="approval" {if $sPost.status == 'approval'}selected{/if}>{$lang.approval}</option>
                    </select>
                </td>
            </tr>

            <tr>
                <td class="name"><span class="red">*</span>{$lang.field_type}</td>
                <td class="field">
                    <select {if $smarty.get.action == 'edit'}disabled{/if} onchange="field_types(this.value);" name="type" {if $smarty.get.action == 'edit'}class="disabled"{/if}>
                        <option value="">{$lang.select}</option>
                        {foreach from=$b_types item='bType' key='key'}
                        <option {if $sPost.type == $key}selected{/if} value="{$key}">{$bType}</option>
                        {/foreach}
                    </select>
                    {if $smarty.get.action == 'edit'}
                        <input type="hidden" name="type" value="{$sPost.type}" />
                    {/if}
                </td>
            </tr>

            <tr>
                <td class="name">{$lang.booking_type}</td>
                <td class="field">
                    <select name="booking_type" {if $sPost.key == 'email' || $sPost.key == 'first_name' || $sPost.key == 'last_name'}class="disabled" disabled="disabled"{/if}>
                        <option {if $sPost.booking_type == 'date_range,date_time_range,time_range'}selected="selected"{/if} value="date_range,date_time_range,time_range">{$lang.any}</option>
                        <option {if $sPost.booking_type == 'date_range'}selected="selected"{/if} value="date_range">{$lang.booking_date_range}</option>
                        <option {if $sPost.booking_type == 'date_time_range'}selected="selected"{/if} value="date_time_range">{$lang.booking_date_time_range}</option>
                        <option {if $sPost.booking_type == 'time_range'}selected="selected"{/if} value="time_range">{$lang.booking_time_range}</option>
                    </select>

                    {if $sPost.key == 'email' || $sPost.key == 'first_name' || $sPost.key == 'last_name'}
                        <span class="field_description">
                            {if $sPost.key == 'email' || $sPost.key == 'first_name' || $sPost.key == 'last_name'}
                                {assign var='field_name' value=$lang[$sPost.key]}
                            {else}

                                {assign var='field_name' value='booking_fields+name+'|cat:$sPost.key}
                                {assign var='field_name' value=$lang.$field_name}
                            {/if}

                            {assign var='sReplace' value=`$smarty.ldelim`field`$smarty.rdelim`}
                            {$lang.booking_sys_field_protected|replace:$sReplace:$field_name}
                        </span>
                    {/if}
                </td>
            </tr>

            </table>

            <!-- additional options -->
            <div id="additional_options">
                <script type="text/javascript">
                    var langs_list = Array(
                        {foreach from=$allLangs item='languages' name='lF'}
                        '{$languages.Code}|{$languages.name}'{if !$smarty.foreach.lF.last},{/if}
                        {/foreach}
                    );
                </script>

                <!-- text field -->
                {assign var='textDefault' value=$sPost.text.default}
                <div id="field_text" class="hide">
                    <table class="form">
                    <tr>
                        <td class="name">{$lang.default_value}</td>
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
                                <input type="text" name="text[default][{$language.Code}]" value="{$textDefault[$language.Code]}" maxlength="350" />
                                {if $allLangs|@count > 1}
                                        <span class="field_description_noicon">{$lang.name} (<b>{$language.name}</b>)</span>
                                    </div>
                                {/if}
                            {/foreach}
                        </td>
                    </tr>

                    {assign var='text_cond' value=$sPost.text}
                    <tr>
                        <td class="name">{$lang.check_condition}</td>
                        <td class="field">
                            <select class="lang_add" name="text[condition]">
                                <option value="">- {$lang.condition} -</option>

                                {foreach from=$l_cond item='condition' key='cKey'}
                                    <option {if $text_cond.condition == $cKey}selected{/if} value="{$cKey}">{$condition}</option>
                                {/foreach}
                            </select>
                        </td>
                    </tr>

                    <tr>
                        <td class="name">{$lang.maxlength}</td>
                        <td class="field">
                            <input class="text lang_add numeric" name="text[maxlength]" type="text" style="width: 50px; text-align: center;" value="{$sPost.text.maxlength}" maxlength="3" />
                            <span class="field_description">{$lang.default_text_value_des}</span>
                        </td>
                    </tr>
                    </table>
                </div>
                <!-- text field end -->

                <!-- textarea field -->
                {assign var='textarea' value=$sPost.textarea}
                <div id="field_textarea" class="hide">
                    <table class="form">
                    <tr>
                        <td class="name">{$lang.maxlength}</td>
                        <td class="field">
                            <input class="text lang_add numeric" name="textarea[maxlength]" type="text" style="width: 50px; text-align: center;" value="{$textarea.maxlength}" maxlength="4" />
                            <span class="field_description">{$lang.default_textarea_value_des}</span>
                        </td>
                    </tr>
                    </table>
                </div>
                <!-- textarea field end -->

                <!-- number field -->
                {assign var='number' value=$sPost.number}
                <div id="field_number" class="hide">
                    <table class="form">
                    <tr>
                        <td class="name">{$lang.maxlength}</td>
                        <td class="field">
                            <input class="numeric" name="number[maxlength]" type="text" style="width: 60px; text-align: center;" value="{$sPost.number.maxlength}" maxlength="8" />
                            <span class="field_description">{$lang.number_field_length_hint}</span>
                        </td>
                    </tr>
                    </table>
                </div>
                <!-- number field end -->
            </div>
            <!-- additional options end -->

            <!-- additional JS -->
            {if $sPost.type != false}
                <script type="text/javascript">field_types('{$sPost.type}');</script>
            {/if}
            <!-- additional JS end -->

            <table class="sTable">
            <tr>
            <td style="width: 180px"></td>
            <td>
                <input class="button lang_add" type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
            </td>
            </tr>
            </table>
        </form>
        <!-- add new field end -->

        {include file='blocks/m_block_end.tpl'}
    {else}
        <div id="grid"></div>
        <script type="text/javascript">{literal}
        $(document).ready(function() {
            bookingFields = new gridObj({
                key: 'bookingFields',
                id: 'grid',
                ajaxUrl: rlPlugins + 'booking/admin/booking.inc.php?q=ext',
                defaultSortField: false,
                title: lang['booking_fields_manager'],
                fields: [
                    {name: 'name', mapping: 'name', type: 'string'},
                    {name: 'Type', mapping: 'Type'},
                    {name: 'Booking_type', mapping: 'Booking_type'},
                    {name: 'Required', mapping: 'Required'},
                    {name: 'Position', mapping: 'Position', type: 'int'},
                    {name: 'Status', mapping: 'Status'},
                    {name: 'Key', mapping: 'Key'}
                ],
                columns: [
                    {
                        header: lang['ext_name'],
                        dataIndex: 'name',
                        width: 60,
                        id: 'rlExt_item_bold'
                    },{
                        id: 'rlExt_item',
                        header: lang['ext_type'],
                        dataIndex: 'Type',
                        width: 30,
                    },{
                        header: '{/literal}{$lang.booking_type}{literal}',
                        dataIndex: 'Booking_type',
                        width: 30,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['date_range,date_time_range,time_range', '{/literal}{$lang.any}{literal}'],
                                ['date_range', '{/literal}{$lang.booking_date_range}{literal}'],
                                ['date_time_range', '{/literal}{$lang.booking_date_time_range}{literal}'],
                                ['time_range', '{/literal}{$lang.booking_time_range}{literal}']
                            ],
                            mode: 'local',
                            typeAhead: true,
                            triggerAction: 'all',
                            selectOnFocus: true
                        })
                    },{
                        header: lang['ext_required_field'],
                        dataIndex: 'Required',
                        width: 17,
                        editor: new Ext.form.ComboBox({
                            store: [
                                ['1', lang['ext_yes']],
                                ['0', lang['ext_no']]
                            ],
                            displayField: 'value',
                            valueField: 'key',
                            emptyText: lang['ext_not_available'],
                            typeAhead: true,
                            mode: 'local',
                            triggerAction: 'all',
                            selectOnFocus:true
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
                        }
                    },{
                        header: lang['ext_position'],
                        dataIndex: 'Position',
                        width: 10,
                        editor: new Ext.form.TextField({
                            allowBlank: false
                        }),
                        renderer: function(val){
                            return '<span ext:qtip="' + lang['ext_click_to_edit'] + '">' + val + '</span>';
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
                        width: 70,
                        fixed: true,
                        dataIndex: 'Key',
                        sortable: false,
                        renderer: function(data) {
                            var out = "<center>";
                            out += "<img class='edit' ext:qtip='" + lang['ext_edit'] + "' src='" + rlUrlHome;
                            out += "img/blank.gif' onClick='location.href=\"" + rlUrlHome + "index.php?controller=";
                            out += controller + "&mode=booking_fields&action=edit&field=" + data + "\"' />";
                            out += "<img class='remove' ext:qtip='" + lang['ext_delete'] + "' src='" + rlUrlHome;
                            out += "img/blank.gif' onClick='rlConfirm( \"" + lang['booking_ext_notice_delete_field'];
                            out += "\",\"apAjaxRequest\", \"" + ['bookingDeleteField', data] + "\", \"field_load\")' ";
                            out += "class='delete' /></center>";
                            return out;
                        }
                    }
                ]
            });

            bookingFields.init();
            grid.push(bookingFields.grid);

            // disallow to change "Booking type" option for system fields
            bookingFields.grid.addListener('beforeedit', function(editEvent) {
                var field_key = editEvent.record.data.Key;

                if (editEvent.field == 'Booking_type'
                    && field_key
                    && (field_key == 'email' || field_key == 'first_name' || field_key == 'last_name')
                ) {
                    printMessage(
                        'error',
                        '{/literal}{$lang.booking_sys_field_protected}{literal}'.replace(
                            '{field}',
                            lang['booking_fields+name+' + field_key]
                        )
                    );

                    return false;
                }
            });
        });
        {/literal}</script>
    {/if}
{elseif $smarty.get.mode == 'listings'}
    <div id="grid"></div>

    <script type="text/javascript">{literal}
    $(document).ready(function() {
        bookingAvailableListings = new gridObj({
            key: 'bookingAvailableListings',
            id: 'grid',
            ajaxUrl: rlPlugins + 'booking/admin/booking.inc.php?q=ext_listings',
            defaultSortField: false,
            title: '{/literal}{$lang.ext_listing_rate_range_manager}{literal}',
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'ref', mapping: 'ref'},
                {name: 'link', mapping: 'link', type: 'string'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true
                },{
                    header: lang['ext_ref_number'],
                    dataIndex: 'ref',
                    hidden: {/literal}{if $refExists}false{else}true{/if}{literal},
                    width: 5
                },{
                    header: lang.ext_title,
                    dataIndex: 'link',
                    width: 60,
                    id: 'rlExt_item_bold'
                }
            ]
        });

        bookingAvailableListings.init();
        grid.push(bookingAvailableListings.grid);
    });
    {/literal}</script>
{elseif $smarty.get.id}
    {include file='blocks/m_block_start.tpl'}

    <fieldset class="light">
        <legend id="legend_cats" class="up" onclick="fieldset_action('booking_page_details');">{$lang.booking_page_details}</legend>
        <div id="booking_page_details">
            <table class="form">
            <tr>
                <td class="name">{$lang.booking_ref_number}:</td>
                <td class="field">{if $request.ref_number}{$request.ref_number}{else}{$lang.not_available}{/if}</td>
            </tr>
            <tr>
                <td class="name">
                    {if $request.Booking_type == 'time_range'}
                        {$lang.booking_escort_date}:
                    {elseif $request.Booking_type == 'date_time_range'}
                        {$lang.booking_checkin_auto}:
                    {else}
                        {$lang.booking_checkin}:
                    {/if}
                </td>
                <td class="field">
                    <b>{$request.From|date_format:$smarty.const.RL_DATE_FORMAT}</b>

                    {if ($request.booking_check_in || $request.Checkin) && $request.Booking_type != 'time_range'}
                        - {if $request.Booking_type == 'date_time_range'}{$request.Checkin}{else}{$request.booking_check_in}{/if}
                    {/if}
                </td>
            </tr>

            {if $request.Booking_type == 'time_range'}
                <tr>
                    <td class="name">{$lang.booking_escort_type}:</td>
                    <td class="field">
                        {if $listing_escort}
                            {$listing_escort.escort_rates.Fields.escort_rates.value[$request.Checkin].title}
                        {else}
                            {$bookingRates[$request.Checkin].title}
                        {/if}
                    </td>
                </tr>
                <tr>
                    <td class="name">{$lang.booking_escort_time}:</td>
                    <td class="field">{$request.Checkout}</td>
                </tr>
                <tr>
                    <td class="name">{$lang.booking_escort_duration}:</td>
                    <td class="field">
                        {assign var="phrase_hours" value='booking_escort_duration_'|cat:$request.To}
                        {$lang.$phrase_hours}
                    </td>
                </tr>
            {else}
                <tr>
                    <td class="name">
                        {if $request.Booking_type == 'date_time_range'}
                            {$lang.booking_checkout_auto}:
                        {else}
                            {$lang.booking_checkout}:
                        {/if}
                    </td>
                    <td class="field">
                        <b>{$request.To|date_format:$smarty.const.RL_DATE_FORMAT}</b>

                        {if $request.booking_check_out || $request.Checkout}
                            - {if $request.Booking_type == 'date_time_range'}{$request.Checkout}{else}{$request.booking_check_out}{/if}
                        {/if}
                    </td>
                </tr>

                {if $request.Booking_type === 'date_range'}
                    <tr>
                        <td class="name">{$lang.booking_nights}:</td>
                        <td class="field">{$request.Booking_nights}</td>
                    </tr>
                {/if}
            {/if}

            <tr>
                <td class="name">{$lang.status}:</td>
                <td class="field">{$request.Stat}</td>
            </tr>
            <tr>
                <td class="name">{$lang.total}:</td>
                <td class="field">
                    {if $defPrice.currency}
                        {assign var="booking_currency" value=$defPrice.currency}
                    {else}
                        {assign var="booking_currency" value=$config.system_currency}
                    {/if}

                    {if $config.system_currency_position == 'before'}
                        {$booking_currency} {$request.Amount}
                    {else}
                        {$request.Amount} {$booking_currency}
                    {/if}
                </td>
            </tr>
            </table>
        </div>
    </fieldset>

    <fieldset class="light">
        <legend id="legend_cats" class="up" onclick="fieldset_action('booking_client_details');">{$lang.booking_client_details}</legend>
        <div id="booking_client_details">
            <table class="form">
            {foreach from=$request.fields key='fKey' item='field'}
                {assign var='field_value' value=$field.value}

                {if $field.Type == 'bool'}
                    {if $field.value == '1'}
                        {assign var='field_value' value=$lang.yes}
                    {else}
                        {assign var='field_value' value=$lang.no}
                    {/if}
                {/if}

                <tr>
                    <td class="name">{$field.name}:</td>
                    <td class="field">
                        {if $field.Condition == 'isUrl' || $field.Condition == 'isEmail'}
                            <a href="{if $field.Condition == 'isEmail'}mailto:{/if}{$field_value}" title="{$field_value}">{$field_value}</a>
                        {else}
                            {$field_value}
                        {/if}
                    </td>
                </tr>
            {/foreach}
            </table>
        </div>
    </fieldset>

    {include file='blocks/m_block_end.tpl'}
{else}
    <div id="grid"></div>
    <script type="text/javascript">
    /**
     * @todo - Remove when compatibility will be >= 4.8.1 with new phrase scopes
     */
    lang.booking_checkin  = '{$lang.booking_checkin}';
    lang.booking_checkout = '{$lang.booking_checkout}';
    lang.booking_accepted = '{$lang.booking_accepted}';
    lang.booking_refused  = '{$lang.booking_refused}';

    {literal}
    $(document).ready(function() {
        bookingRequestsGrid = new gridObj({
            key: 'bookingRequests',
            id: 'grid',
            fieldID: 'Request_ID',
            ajaxUrl: rlPlugins + 'booking/admin/booking.inc.php?q=ext_stat',
            defaultSortField: false,
            title: '{/literal}{$lang.booking_requests}{literal}',
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'Listing_ID', mapping: 'Listing_ID'},
                {name: 'ref_number', mapping: 'ref_number'},
                {name: 'link', mapping: 'link', type: 'string'},
                {name: 'Booking_status', mapping: 'Booking_status'},
                {name: 'Key', mapping: 'Key'},
                {name: 'Booking_client', mapping: 'Booking_client'},
                {name: 'Booking_from', mapping: 'Booking_from', type: 'date', dateFormat: 'U'},
                {name: 'Booking_to', mapping: 'Booking_to'},
                {name: 'Booking_ID', mapping: 'Booking_ID', type: 'int'},
                {name: 'Request_ID', mapping: 'Request_ID'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'Booking_ID',
                    width: 30,
                    fixed: true
                },{
                    header: lang.ext_listing_id,
                    dataIndex: 'Listing_ID',
                    width: 7
                },{
                    header: lang.ext_title,
                    dataIndex: 'link',
                    width: 60,
                    id: 'rlExt_item_bold'
                },{
                    header: lang['ext_booking_client'],
                    dataIndex: 'Booking_client',
                    width: 15
                },{
                    header: lang.booking_checkin,
                    dataIndex: 'Booking_from',
                    width: 15,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang.booking_checkout,
                    dataIndex: 'Booking_to',
                    width: 15
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Booking_status',
                    width: 80,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['process', lang.new],
                            ['booked',  lang.booking_accepted],
                            ['refused', lang.booking_refused]
                        ],
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
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'Request_ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                        out += "<img class='view' ext:qtip='" + lang['ext_view'] + "' src='";
                        out += rlUrlHome + "img/blank.gif' onClick='location.href=\"";
                        out += rlUrlHome + "index.php?controller=" + controller + "&action=view&id=";
                        out += data + "\"' />";
                        out += "<img class='remove' ext:qtip='" +  lang['ext_delete'] + "' src='";
                        out += rlUrlHome + "img/blank.gif' onClick='rlConfirm( \"";
                        out += lang['ext_booking_remove_notice_ap'] + "\", \"apAjaxRequest\", \"";
                        out += ['bookingDeleteRequest', data] + "\" )' class='delete' />";
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });

        bookingRequestsGrid.init();
        grid.push(bookingRequestsGrid.grid);
    });
    {/literal}</script>
{/if}

<script>
{assign var='sReplace' value=`$smarty.ldelim`field`$smarty.rdelim`}
{assign var='missing_booking_field' value=$lang.notice_field_empty|replace:$sReplace:'error_field'}

lang['to']                             = '{$lang.to}';
lang['description']                    = '{$lang.description}';
lang['from']                           = '{$lang.from}';
lang['price']                          = '{$lang.price}';
lang['field_protected']                = '{$lang.field_protected}';
lang['booking_field_error']            = '{$missing_booking_field}';
lang['booking_fields+name+email']      = '{$lang.mail}';
lang['booking_fields+name+last_name']  = '{$lang.last_name}';
lang['booking_fields+name+first_name'] = '{$lang.first_name}';

{literal}

/**
 * Function for ajax requests
 *
 * @param data        - Key of action and data for request
 * @param additional
 */
var apAjaxRequest = function(data, additional) {
    data = data.split(',');

    if (data && data[0] && data[1]) {
        mode = data[0];
        item = data[1];
    } else {
        return;
    }

    var errors = false;

    // check system fields
    if (mode === 'bookingDeleteField' && jQuery.inArray(item, ['email', 'first_name', 'last_name']) >= 0) {
        printMessage('error', lang.field_protected.replace('{field}', lang['booking_fields+name+' + item]));
        errors = true;
    }

    if (mode && item && !errors) {
        $.post(
            rlConfig.ajax_url,
            {mode: mode, item: item, additional: additional},
            function(response) {
                if (response.status === 'OK') {
                    if (mode === 'bookingDeleteField') {
                        bookingFields.reload();
                    } else if (mode === 'bookingDeleteRequest') {
                        bookingRequestsGrid.reload();
                    }

                    printMessage('notice', response.data);
                } else {
                    printMessage('error', response.message);
                }
            },
            'json'
        );
    }
};
{/literal}</script>

<!-- booking system end -->
