<!-- massmailer/newsletter -->

<script type="text/javascript" src="{$smarty.const.RL_PLUGINS_URL}massmailer_newsletter/static/lib.js"></script>
<script type="text/javascript">
    massmailer.phrases['send_confirm'] = "{$lang.massmailer_newsletter_send_confirm}";
    massmailer.phrases['completed']    = "{$lang.massmailer_newsletter_completed_notice}";
    massmailer.phrases['empty_emails'] = "{$lang.massmailer_newsletter_no_selected_emails}";
    massmailer.config['id']            = {if $smarty.get.massmailer}{$smarty.get.massmailer}{else}false{/if};
</script>
<script>
    {literal}
        $('document').ready(function() {
            $('#langSelect').on('change', function() {
                $.post(rlConfig.ajax_url, {
                        item: 'getCountMassmailer',
                        langMassmailer: this.value
                    },
                    function(response) {
                        if (response.status == 'OK') {
                            $strHtml = '<li>' +
                                '<label>' +
                                '<input type="checkbox" name="site_accounts" />' +
                                ' ' + response.data.langAcType + ' ' + '(' + response.data.total + ')' +
                                '</label>' +
                                '</li>';


                            $(".countUser").empty()

                            $.each(response.data, function(i, item) {
                                if (item.Key) {
                                    $strHtml += '<li style="padding-left: 20px;">' +
                                        '<label>' +
                                        '<input class="accounts"' +
                                        'type="checkbox"' +
                                        'name="type[]"' +
                                        'value="' + item.Key + '"/>' +
                                        ' ' + item.name + ' (' + item.count + ')' +
                                        '</label>' +
                                        '</li>';
                                }
                            })
                            $(".countUser").empty();
                            $(".countUser").html($strHtml);
                        }

                    }, 'json')

            });

        });
    {/literal}
</script>
<!-- navigation bar -->
<div id="nav_bar">{strip}
        {if $smarty.get.page == 'newsletter'}
            <a href="javascript:void(0)" onclick="show('filters');" class="button_bar">
                <span class="left"></span>
                <span class="center_search">{$lang.filters}</span>
                <span class="right"></span>
            </a>
        {/if}

        {if !$smarty.get.page && $smarty.get.action != 'add'}
            <a href="{$rlBaseC}action=add" class="button_bar">
                <span class="left"></span>
                <span class="center_add">{$lang.massmailer_newsletter_add_massmailer}</span>
                <span class="right"></span>
            </a>

            <a href="{$rlBaseC}page=newsletter" class="button_bar">
                <span class="left"></span>
                <span class="center_recepeints">{$lang.massmailer_newsletter_recipients}</span>
                <span class="right"></span>
            </a>
        {/if}

        {if $smarty.get.action || $smarty.get.page}
            <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar">
                <span class="left"></span>
                <span class="center_list">{$lang.massmailer_newsletter_newsletters_list}</span>
                <span class="right"></span>
            </a>
        {/if}
    {/strip}
</div>
<!-- navigation bar end -->

{if $smarty.get.action == 'add' || $smarty.get.action == 'edit'}
    {assign var='sPost' value=$smarty.post}

    <!-- add new massmailer -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
    <form action="{strip}{$rlBaseC}action=
                                                                            {if $smarty.get.action == 'add'}add
                                                                            {elseif $smarty.get.action == 'edit'}edit&massmailer={$smarty.get.massmailer}
                                                                            {/if}
                                            {/strip}" enctype="multipart/form-data" method="post">
        <input type="hidden" name="fromPost" value="1" />
        <table class="form">
            <tr>
                <td class="name"><span class="red">*</span>{$lang.key}</td>
                <td class="field">
                    <input id="massmailer_key" {if $smarty.get.action == 'edit'}readonly="readonly" class="disabled" {/if}
                        type="text" value="{$smarty.post.massmailer_key}" name="massmailer_key" size="12" maxlength="100" />
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.massmailer_newsletter_subject}
                </td>
                <td class="field">
                    <input class="w350" type="text" name="subject" value="{$sPost.subject}" />
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.massmailer_newsletter_body}
                </td>
                <td class="field">

                    <div style="padding: 0 0 5px 0;">
                        <select id="var_sel">
                            <option value="">{$lang.select}</option>
                            {foreach from=$l_email_variables item='var'}
                                <option value="{$var}">{$var}</option>
                            {/foreach}
                        </select>
                        <input class="caret_button no_margin" id="input_{$language.Code}" type="button" value="{$lang.add}"
                            style="margin-left: 5px" />
                        <span class="field_description_noicon">{$lang.add_template_variable}</span>
                    </div>

                    {fckEditor name='body' width='100%' height='200' value=$sPost.body}

                    <script type="text/javascript">
                        {literal}
                            $(document).ready(function() {
                                if (typeof flynax.putCursorInCKTextarea == 'function') {
                                    flynax.putCursorInCKTextarea('body');
                                }

                                $('.caret_button').click(function() {
                                    var variable = $('#var_sel').val();

                                    if (variable == '') {
                                        return;
                                    }

                                    var instance = CKEDITOR.instances['body'];
                                    instance.getSelection().getStartElement().appendHtml(variable);
                                });
                            });
                        {/literal}
                    </script>
                </td>
            </tr>
            <tr>
                <td>
                    {$lang.massmailer_newsletter_user_lang}
                </td>
                <td class="field">
                    <select id="langSelect" name="lang">
                        <option value="all" {if empty($sPost.langSelect) || $sPost.langSelect === 'all'}selected{/if}>
                            {$lang.all}
                        </option>
                        {foreach from=$allLangs item='langSystem'}
                            <option value="{$langSystem.Code}" {if $sPost.langSelect === $langSystem.Code}selected{/if}>
                                {$langSystem.name}
                            </option>
                        {/foreach}
                    </select>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.massmailer_newsletter_recipients}</td>
                <td class="field">
                    <ul class="clear_list" style="padding: 5px 0 0;">
                        <li>
                            <label>
                                <input type="checkbox" name="newsletters_accounts"
                                    {if $sPost.newsletters_accounts}checked="checked" {/if} />
                                {$lang.massmailer_newsletter_newsletter} ({$other_type_count.subscribers.count})
                            </label>
                        </li>
                        <div class="countUser">
                            <li>
                                <label>
                                    <input type="checkbox" name="site_accounts" {if $sPost.site_accounts}checked="checked"
                                        {/if} />
                                    {$lang.account_type} ({$other_type_count.total_accounts})
                                </label>
                            </li>
                            {foreach from=$account_types item='type'}
                                {assign var='sType' value=$sPost.type}
                                {assign var='tKey' value=$type.Key}
                                <li style="padding-left: 20px;">
                                    <label>
                                        <input class="accounts" type="checkbox" name="type[]" value="{$tKey}"
                                            {if $sType && $tKey|in_array:$sType}checked="checked" {/if} />
                                        {$type.name} ({$type.count})
                                    </label>
                                </li>
                            {/foreach}
                        </div>
                        <li>
                            <label>
                                <input type="checkbox" name="contact_us" {if $sPost.contact_us}checked="checked" {/if} />
                                {$lang.massmailer_newsletter_contact_us} ({$other_type_count.contacts.count})
                            </label>
                        </li>
                    </ul>
                </td>
            </tr>
            <tr>
                <td class="name">{$lang.status}</td>
                <td class="field">
                    <select name="status">
                        <option value="active" {if $sPost.status == 'active'}selected="selected" {/if}>
                            {$lang.active}
                        </option>
                        <option value="approval" {if $sPost.status == 'approval'}selected="selected" {/if}>
                            {$lang.approval}
                        </option>
                    </select>
                </td>
            </tr>
            <tr>
                <td></td>
                <td>
                    <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
                </td>
            </tr>
        </table>
    </form>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add new massmailer end -->
{elseif $smarty.get.action == 'send'}
    <script type="text/javascript">
        var $total_res = {$massmailer_form.Recipients_newsletter_count.Count|string_format:"%d"};
        massmailer.phrases['massmailer_newsletter_sent_email_zero'] = '{$lang.massmailer_newsletter_sent_email_zero}';
    </script>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    <table class="list mn_list">
        <tr>
            <td class="name">{$lang.massmailer_newsletter_to}:</td>
            <td class="value topfix">
                {if $massmailer_form.Recipients_newsletter}
                    <div class="dark_13 mn_padding">
                        <a target="_blank"
                            href="{$rlBase}index.php?controller={$smarty.get.controller}&page=newsletter&subscribers=1">
                            {$lang.massmailer_newsletter_newsletter} ({$massmailer_form.Recipients_newsletter_count.Count})
                        </a>

                        {if !empty($massmailer_form.Recipients_newsletter_emails)}
                            <div class="emails" id="emails_newsletter"
                                style="margin: 10px; border: 1px #ccc solid; padding: 5px; font-weight: normal;">
                                {foreach from=$massmailer_form.Recipients_newsletter_emails item='emails'}
                                    <label>
                                        <input type="checkbox" name="emails[{$email.Mail}]" value="{$emails.Mail}" />{$emails.Mail}
                                    </label>
                                {/foreach}

                                <div class="grey_area">
                                    <span onclick="$('#emails_newsletter input').prop('checked', true);" class="green_10">
                                        {$lang.check_all}
                                    </span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#emails_newsletter input').prop('checked', false);" class="green_10">
                                        {$lang.uncheck_all}
                                    </span>
                                </div>
                            </div>
                        {/if}
                    </div>
                {/if}
                {if !empty($massmailer_form.Recipients_accounts)}
                    <div class="dark_13 mn_padding">
                        <a target="_blank"
                            href="{$rlBase}index.php?controller={$smarty.get.controller}&page=newsletter&subscribers=2">
                            {$lang.massmailer_newsletter_site_accounts}:
                        </a>

                        <ul class="clear_list">
                            {foreach from=$massmailer_form.Recipients_accounts item="recipients_accounts"}
                                {assign var='newKey' value=$recipients_accounts.Key}
                                <script type="text/javascript">
                                    var $total_res = $total_res + {$massmailer_form.Recipients_accounts_count.$newKey|string_format:"%d"};
                                </script>
                                <li>{$recipients_accounts.name} ({$massmailer_form.Recipients_accounts_count.$newKey})
                                    {assign var='emails' value=$massmailer_form.Recipients_accounts_emails.$newKey}

                                    {if !empty($emails)}
                                        <div class="emails" id="emails_{$newKey}"
                                            style="margin: 10px; border: 1px #ccc solid; padding: 5px; font-weight: normal;">
                                            {foreach from=$emails item='email'}
                                                <label>
                                                    <input type="checkbox" name="emails[{$email.Mail}]" value="{$email.Mail}" />
                                                    {$email.Mail}
                                                </label>
                                            {/foreach}

                                            <div class="grey_area">
                                                <span onclick="$('#emails_{$newKey} input').prop('checked', true);" class="green_10">
                                                    {$lang.check_all}
                                                </span>
                                                <span class="divider"> | </span>
                                                <span onclick="$('#emails_{$newKey} input').prop('checked', false);" class="green_10">
                                                    {$lang.uncheck_all}
                                                </span>
                                            </div>
                                        </div>
                                    {/if}
                                </li>
                            {/foreach}
                        </ul>
                    </div>
                {/if}

                {if $massmailer_form.Recipients_contact_us}
                    <div class="dark_13 mn_padding">
                        <a target="_blank"
                            href="{$rlBase}index.php?controller={$smarty.get.controller}&page=newsletter&subscribers=3">
                            {$lang.massmailer_newsletter_contact_us} ({$massmailer_form.Recipients_contact_us_count.Count})</a>
                        <script type="text/javascript">
                            var $total_res = $total_res + {$massmailer_form.Recipients_contact_us_count.Count|string_format:"%d"};
                        </script>

                        {if !empty($massmailer_form.Recipients_contact_us_emails)}
                            <div class="emails" id="emails_contact_us"
                                style="margin: 10px; border: 1px #ccc solid; padding: 5px; font-weight: normal;">
                                {foreach from=$massmailer_form.Recipients_contact_us_emails item='emails'}
                                    <label>
                                        <input type="checkbox" name="emails[{$email.Mail}]" value="{$emails.Mail}" />
                                        {$emails.Mail}
                                    </label>
                                {/foreach}

                                <div class="grey_area">
                                    <span onclick="$('#emails_contact_us input').prop('checked', true);" class="green_10">
                                        {$lang.check_all}
                                    </span>
                                    <span class="divider"> | </span>
                                    <span onclick="$('#emails_contact_us input').prop('checked', false);" class="green_10">
                                        {$lang.uncheck_all}
                                    </span>
                                </div>
                            </div>
                        {/if}
                    </div>
                {/if}
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.subject}:</td>
            <td class="value">
                <b>{$massmailer_form.Subject}</b>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.massmailer_newsletter_body}:</td>
            <td class="value topfix">
                <div class="mn_area">{$massmailer_form.Body}</div>
            </td>
        </tr>
        <tr>
            <td></td>
            <td class="value">
                <input id="start_send" type="button" value="{$lang.massmailer_newsletter_send}" />
                <a style="padding: 0 0 0 10px"
                    href="{$rlBase}index.php?controller={$smarty.get.controller}&action=edit&massmailer={$smarty.get.massmailer}">
                    {$lang.edit}
                </a>
            </td>
        </tr>
    </table>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    {include file=$smarty.const.RL_PLUGINS|cat:'massmailer_newsletter/admin/send_interface.tpl'}
{elseif $smarty.get.page == 'newsletter'}
    <!-- newsletter filter -->
    <div class="hide" id="filters">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.filter_by}
        <table class="sTable">
            <tr>
                <td valign="top">
                    <table class="form">
                        <tr>
                            <td class="name w130">{$lang.name}</td>
                            <td class="field"><input type="text" id="name" maxlength="40" /></td>
                        </tr>
                        <tr>
                            <td class="name w130">{$lang.mail}</td>
                            <td class="field"><input type="text" id="email" maxlength="50" /></td>
                        </tr>
                        <tr>
                            <td class="name w130">{$lang.subscribed_from}</td>
                            <td class="field">
                                <select id="subscribed_from">
                                    <option value="">{$lang.any}</option>
                                    <option value="1">{$lang.massmailer_newsletter_newsletter}</option>
                                    <option value="2">{$lang.accounts}</option>
                                    <option value="3">{$lang.contacts}</option>
                                </select>
                            </td>
                        </tr>
                        <tr id="account_types" class="hide">
                            <td class="name w130">{$lang.account_type}</td>
                            <td class="field">
                                <select id="account_type">
                                    <option value="">{$lang.any}</option>
                                    {foreach from=$account_types item='account_type'}
                                        <option value="{$account_type.Key}">{$account_type.name}</option>
                                    {/foreach}
                                </select>
                            </td>
                        </tr>
                        <tr>
                            <td class="name w130">{$lang.date}</td>
                            <td class="field" style="white-space: nowrap;">
                                <input style="width: 65px;" type="text" value="" size="12" maxlength="10" id="date_from" />
                                <img class="divider" alt="" src="{$rlTplBase}img/blank.gif" />
                                <input style="width: 65px;" type="text" value="" size="12" maxlength="10" id="date_to" />
                            </td>
                        </tr>
                        <tr>
                            <td></td>
                            <td class="field">
                                <input id="search_button" type="submit" value="{$lang.search}" />
                                <input type="button" value="{$lang.reset}" id="reset_filter_button" />

                                <a class="cancel" href="javascript:void(0)" onclick="show('filters')">{$lang.cancel}</a>
                            </td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    </div>

    <script type="text/javascript">
        var get_filter = 0;
        {if $smarty.get.subscribers}
            get_filter = {$smarty.get.subscribers};
        {/if}
        {literal}

            var sFields = new Array('name', 'email', 'subscribed_from', 'account_type', 'date_from', 'date_to');
            var cookie_filters = new Array();

            $(document).ready(function() {
                        if ($('select#subscribed_from option:selected').val() == 2) {
                            $('tr#account_types').show()
                        } else {
                            $('tr#account_types').hide()
                        }

                        $('#subscribed_from').change(function() {
                            if ($(this).val() == 2) {
                                $('tr#account_types').show()
                            } else {
                                $('tr#account_types').hide()
                            }
                        })

                        $(function() {
                                    $('#date_from').datepicker({
                                        showOn: 'both',
                                        buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                                        buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                                        buttonImageOnly: true,
                                        dateFormat: 'yy-mm-dd'
                                        }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);

                                        $('#date_to').datepicker({
                                            showOn: 'both',
                                            buttonImage    : '{/literal}{$rlTplBase}{literal}img/blank.gif',
                                            buttonText     : '{/literal}{$lang.dp_choose_date}{literal}',
                                            buttonImageOnly: true,
                                            dateFormat: 'yy-mm-dd'
                                            }).datepicker($.datepicker.regional['{/literal}{$smarty.const.RL_LANG_CODE}{literal}']);
                                        });

                                        if (readCookie('newsletter_sc')) {
                                            $('#filters').show();
                                            cookie_filters = readCookie('newsletter_sc').split(',');

                                            for (var i in cookie_filters) {
                                                if (typeof(cookie_filters[i]) == 'string') {
                                                    var item = cookie_filters[i].split('||');
                                                    $('#' + item[0]).selectOptions(item[1]);
                                                }
                                            }

                                            cookie_filters.push(new Array('search', 1));
                                        }

                                        $('#search_button').click(function() {
                                            var sValues = new Array();
                                            var filters = new Array();
                                            var save_cookies = new Array();
                                            var counter = 0;

                                            for (var si = 0; si < sFields.length; si++) {
                                                if ($.trim($('#' + sFields[si]).val()) != '' && $('#' + sFields[si])
                                                    .val() != undefined) {
                                                    sValues[counter] = $('#' + sFields[si]).val();
                                                    filters[counter] = new Array(sFields[si], $('#' + sFields[si])
                                                        .val());
                                                    save_cookies[counter] = sFields[si] + '||' + $('#' + sFields[
                                                        si]).val();
                                                    counter = counter + 1;
                                                }
                                            }

                                            // save search criteria
                                            createCookie('newsletter_sc', save_cookies, 1);
                                            filters.push(new Array('filters', 1));
                                            newsletterGrid.filters = filters;
                                            newsletterGrid.reload();

                                        });

                                        $('#reset_filter_button').click(function() {
                                            eraseCookie('newsletter_sc');
                                            newsletterGrid.reset();

                                            $("#search select option[value='']").attr('selected', true);
                                            $("#search input[type=text]").val('');
                                        });

                                        if (get_filter > 0) {
                                            $('#filters').show();
                                            cookie_filters = Array('subscribed_from||' + get_filter);
                                            var item = cookie_filters[0].split('||');
                                            $('#' + item[0]).selectOptions(item[1]);

                                            cookie_filters.push(new Array('search', 1));
                                        }
                                    })
                                {/literal}
    </script>
    <!-- newsletter grid -->
    <div id="grid"></div>
    <script type="text/javascript">
        {literal}
            var newsletterGrid;

            $(document).ready(function() {
                newsletterGrid = new gridObj({
                    key: 'newsletter',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'massmailer_newsletter/admin/massmailer_newsletter.inc.php?q=ext2',
                    defaultSortField: 'Name',
                    title: lang['ext_newsletter_manager'],
                    filters: cookie_filters,
                    fields: [
                        {name: 'ID', mapping: 'ID'},
                        {name: 'Name', mapping: 'Name'},
                        {name: 'Mail', mapping: 'Mail'},
                        {name: 'Status', mapping: 'Status'},
                        {name: 'From', mapping: 'From'},
                        {name: 'dev_subscriber', mapping: 'dev_subscriber'},
                        {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d'}
                    ],
                    columns: [{
                        header: lang['ext_name'],
                        dataIndex: 'Name',
                        id: 'rlExt_item_bold',
                        width: 23
                    }, {
                        header: '{/literal}{$lang.subscribed_from}{literal}',
                        dataIndex: 'From',
                        width: 14
                    }, {
                        header: lang['ext_email'],
                        dataIndex: 'Mail',
                        width: 250,
                        fixed: true
                    }, {
                        header: lang['ext_subscribe_date'],
                        dataIndex: 'Date',
                        width: 180,
                        fixed: true,
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace(
                            'b', 'M'))
                    }, {
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 10,
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
                            selectOnFocus: true,
                        })
                    }, {
                        header: lang['ext_actions'],
                        width: 60,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(data, ext, row) {
                            var line_id = row.data['ID'];
                            var sub_type = row.data['dev_subscriber'];
                            var out = "<img class='remove' ext:qtip='" + lang['ext_delete'] +
                                "' src='" + rlUrlHome;
                            out += "img/blank.gif' onClick='rlConfirm( \"" + lang[
                                'ext_notice_delete'];
                            out += "\", \"xajax_deleteSubscriber\", Array(\"" + line_id;
                            out += "\", \"" + sub_type + "\"), \"news_load\" )' />";

                            return out;
                        }
                    }]
                });

                newsletterGrid.init();
                grid.push(newsletterGrid.grid);

                newsletterGrid.grid.addListener('beforeedit', function(editEvent) {
                    if (editEvent.record.data.dev_subscriber != 'subscribers') {
                        editEvent.cancel = true;
                        newsletterGrid.store.rejectChanges();
                        Ext.MessageBox.alert(lang['ext_notice'], lang[
                            'ext_you_shouldnt_change_non_subscribers_users']);
                    }
                });
            });
        {/literal}
    </script>
    <!-- newsletter grid end -->
{else}
    <!-- massmailer grid -->
    <div id="grid"></div>
    <script type="text/javascript">
        {literal}
            var massmailerGrid;

            $(document).ready(function() {
                massmailerGrid = new gridObj({
                    key: 'massmailer',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'massmailer_newsletter/admin/massmailer_newsletter.inc.php?q=ext',
                    defaultSortField: 'Name',
                    title: lang['ext_massmailer_manager'],
                    fields: [
                        {name: 'ID', mapping: 'ID'},
                        {name: 'Name', mapping: 'Subject'},
                        {name: 'Description', mapping: 'Body'},
                        {name: 'Status', mapping: 'Status'},
                        {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d'}
                    ],
                    columns: [{
                        header: lang['ext_subject'],
                        dataIndex: 'Name',
                        id: 'rlExt_item_bold',
                        width: 30
                    }, {
                        header: lang['ext_add_date'],
                        dataIndex: 'Date',
                        width: 120,
                        fixed: true,
                        renderer: function(val) {
                            return Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '')
                                .replace('b', 'M'))(val);
                        },
                    }, {
                        header: lang['ext_status'],
                        dataIndex: 'Status',
                        width: 90,
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
                            selectOnFocus: true
                        })
                    }, {
                        header: lang['ext_actions'],
                        width: 80,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(data) {
                            var out = "<a href='" + rlUrlHome + "index.php?controller=" +
                                controller;
                            out += "&action=send&massmailer=" + data +
                                "'><img class='send' ext:qtip='";
                            out += lang['ext_massmailer_send'] + "' src='" + rlUrlHome +
                                "img/blank.gif' /></a>";
                            out += "<a href='" + rlUrlHome + "index.php?controller=" + controller;
                            out += "&action=edit&massmailer=" + data +
                                "'><img class='edit' ext:qtip='" + lang['ext_edit'];
                            out += "' src='" + rlUrlHome + "img/blank.gif' /></a>" +
                                "<img class='remove' ext:qtip='";
                            out += lang['ext_delete'] + "' src='" + rlUrlHome +
                                "img/blank.gif' onclick='rlConfirm(\"";
                            out += lang['ext_notice_' + delete_mod] +
                                "\", \"xajax_deleteMassmailerNewsletter\", \"";
                            out += Array(data) + "\", \"section_load\" )' />";

                            return out;
                        }
                    }]
                });

                massmailerGrid.init();
                grid.push(massmailerGrid.grid);

            });

        {/literal}
    </script>
    <!-- massmailer grid end -->
{/if}

<!-- massmailer/newsletter -->
