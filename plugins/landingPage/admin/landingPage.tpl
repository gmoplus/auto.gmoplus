<!-- Landing pages manager -->

<!-- navigation bar -->
<div id="nav_bar">
    {if $aRights.$cKey.add && $smarty.get.action != 'add'}
        <a href="{$rlBaseC}action=add" class="button_bar"><span class="center_add">{$lang.add_page}</span></a>
    {/if}

    {if $smarty.get.action}
        <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar"><span class="center_list">{$lang.pages_list}</span></a>
    {/if}
</div>
<!-- navigation bar end -->

<style type="text/css">
{literal}

.text-field {
    color: #828282;
    height: 29px;
    font-size: 13px;
    padding: 5px 5px 6px;
    border: 1px #acacac solid;
    border-radius: 5px;
    box-shadow: inset 0px 5px 10px -5px rgba(0,0,0,.25);
    max-width: 900px;
    width: 100%;
    box-sizing: border-box;
}
.text-field input {
    padding: 0;
    border: 0 !important;
    border-radius: 0;
    direction: ltr !important;
}
.text-field input:not(.error) {
    background: none;
}
.d-flex {
    display: flex;
}
.flex-fill {
    flex: 1;
}
input[type=text].min-liquid {
    min-width: 40px;
    width: auto;
    max-width: 300px;
    margin: 0 0 0 5px;
}
.w-100 {
    width: 100% !important;
}
.vertical-align-top {
    vertical-align: text-top;
}
.copy-phrase {
    margin: 0 0 0 5px;
}

{/literal}
</style>

{if $smarty.get.action}
    {assign var='sPost' value=$smarty.post}
    {assign var='site_path' value=$domain_info.path|ltrim:'/'}

    <!-- add new/edit page -->
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    <form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{elseif $smarty.get.action == 'edit'}edit&page={$smarty.get.page}{/if}" method="post">
        <input type="hidden" name="submit" value="1" />

        {if $smarty.get.action == 'edit'}
            <input type="hidden" name="fromPost" value="1" />
        {/if}

        <table class="form" style="margin-bottom: 10px;">
        <tr>
            <td style="width: 185px;"></td>
            <td>
                <div style="margin-bottom: 10px;">
                    <label>
                        <input type="checkbox" value="1" name="use_subdomain"{if $sPost.use_subdomain} checked="checked"{/if} class="vertical-align-top" />
                        {$lang.lp_use_subdomains}
                    </label>
                </div>

                {if $allLangs|@count > 1}
                    <ul class="tabs">
                        {foreach from=$allLangs item='language' name='langF'}
                        <li lang="{$language.Code}" {if $smarty.foreach.langF.first}class="active"{/if}>{$language.name}</li>
                        {/foreach}
                    </ul>
                {/if}
            </td>
        </tr>
        </table>

        <div class="tabs-content">
        {foreach from=$allLangs item='language' name='langF'}
            {if $allLangs|@count > 1}
                <div class="tab_area_lang{if !$smarty.foreach.langF.first} hide{/if} {$language.Code}">
            {/if}

            <table class="form">
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.lp_landing_page_url}
                </td>
                <td class="field">
                    <div class="text-field d-flex" data-type="landing">
                        {$domain_info.scheme}://
                        <span class="subdomain-cont{if !$sPost.use_subdomain} hide{/if}">
                            <input size="1" class="min-liquid" type="text" name="landing_page_subdomain[{$language.Code}]" value="{$sPost.landing_page_subdomain[$language.Code]}" />.
                        </span>
                        {if $has_www}
                            <span class="www-prefix">www.</span>
                        {/if}
                        <span class="host-text">
                            {$domain_info.domain|ltrim:'.'}/{if $site_path}{$site_path}/{/if}{if $language.Code != $config.lang}{$language.Code}/{/if}
                        </span>
                        <div class="flex-fill" style="margin-left: 5px;">
                            <input type="text" class="w-100" name="landing_page_url[{$language.Code}]" value="{$sPost.landing_page_url[$language.Code]}" />
                        </div>
                        {if $allLangs|@count > 1 && $smarty.foreach.langF.first}
                            <img alt="{$lang.ext_copy_phrase_to_lang}" src="{$rlTplBase}img/blank.gif" class="copy-phrase" />
                        {/if}
                    </div>
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.lp_original_page_url}
                </td>
                <td class="field">
                    <div class="text-field d-flex" data-type="original">
                        {$domain_info.scheme}://
                        <span class="subdomain-cont{if !$sPost.use_subdomain} hide{/if}">
                            <input size="1" class="min-liquid" type="text" name="original_page_subdomain[{$language.Code}]" value="{$sPost.original_page_subdomain[$language.Code]}" />.
                        </span>
                        {if $has_www}
                            <span class="www-prefix">www.</span>
                        {/if}
                        <span class="host-text">
                            {$domain_info.domain|ltrim:'.'}/{if $site_path}{$site_path}/{/if}{if $language.Code != $config.lang}{$language.Code}/{/if}
                        </span>
                        <div class="flex-fill" style="margin-left: 5px;">
                            <input type="text" class="w-100" name="original_page_url[{$language.Code}]" value="{$sPost.original_page_url[$language.Code]}" />
                        </div>
                        {if $allLangs|@count > 1 && $smarty.foreach.langF.first}
                            <img alt="{$lang.ext_copy_phrase_to_lang}" src="{$rlTplBase}img/blank.gif" class="copy-phrase" />
                        {/if}
                    </div>
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.title}
                </td>
                <td class="field">
                    <input type="text" name="meta_title[{$language.Code}]" value="{$sPost.meta_title[$language.Code]}" style="max-width: 600px;width: 100%;" />
                    <span class="field_description_noicon">{$language.Code}</span>
                </td>
            </tr>
            <tr>
                <td class="name">
                    {$lang.h1_heading}
                </td>
                <td class="field">
                    <input type="text" name="meta_h1[{$language.Code}]" value="{$sPost.meta_h1[$language.Code]}" style="max-width: 600px;width: 100%;" />
                    <span class="field_description_noicon">{$language.Code}</span>
                </td>
            </tr>
            <tr>
                <td class="name">
                    <span class="red">*</span>{$lang.meta_description}
                </td>
                <td class="field">
                    <textarea name="meta_description[{$language.Code}]" style="max-width: 612px;width: 100%;vertical-align: middle;">{$sPost.meta_description[$language.Code]}</textarea>
                    <span class="field_description_noicon">{$language.Code}</span>
                </td>
            </tr>
            <tr>
                <td class="name">
                    {$lang.meta_keywords}
                </td>
                <td class="field">
                    <textarea name="meta_keywords[{$language.Code}]" style="max-width: 612px;width: 100%;vertical-align: middle;">{$sPost.meta_keywords[$language.Code]}</textarea>
                    <span class="field_description_noicon">{$language.Code}</span>
                </td>
            </tr>
            <tr>
                <td class="name">
                    {$lang.lp_seo_text}
                </td>
                <td class="field ckeditor">
                    {assign var='lCode' value='seo_text_'|cat:$language.Code}
                    {fckEditor name='seo_text_'|cat:$language.Code width='100%' height='140' value=$sPost.$lCode}
                </td>
            </tr>
            </table>

            {if $allLangs|@count > 1}
                </div>
            {/if}
        {/foreach}
        </div>

        <table class="form">
        <tr>
            <td></td>
            <td class="field">
                <div style="padding: 0 0 5px;">
                    <select name="text_position">
                        <option value="">{$lang.block_side}</option>
                        {foreach from=$l_block_sides item='block_side' key='sKey'}
                        <option value="{$sKey}" {if $sKey == $sPost.text_position}selected="selected"{/if}>{$block_side}</option>
                        {/foreach}
                    </select>

                    <label style="margin-left: 10px;">
                        <input type="checkbox" value="1" name="use_design"{if $sPost.use_design} checked="checked"{/if} class="vertical-align-top" />
                        {$lang.use_block_design}
                    </label>
                </div>
            </td>
        </tr>
        <tr>
            <td class="name">{$lang.status}</td>
            <td class="field">
                <select name="status" {if $sPost.key|strpos:'lt' === 0 && $smarty.get.action == 'edit'}disabled class="disabled"{/if}>
                    <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                    <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
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

    <script type="text/javascript">
    {literal}

    $(function(){
        // subdomainHandler
        $useSubdomains = $('input[name=use_subdomain]');

        var subdomainHandler = function(){
            $('.subdomain-cont')[
                $useSubdomains.is(':checked') ? 'removeClass' : 'addClass'
            ]('hide');
            $('.www-prefix')[
                $useSubdomains.is(':checked') ? 'addClass' : 'removeClass'
            ]('hide');
        }

        $useSubdomains.change(function(){
            subdomainHandler();
        });

        subdomainHandler();

        // liquidFieldsHandler
        var liquidFieldsHandler = function(){
            $('.min-liquid').each(function(){
                if (this.value) {
                    this.size = this.value.length;
                }
            });
        }

        $('.min-liquid').on('keyup click', function(){
            liquidFieldsHandler();
        });

        liquidFieldsHandler();

        $('.host-text').click(function(){
            $(this).next().find('input').focus();
        });

        $('.copy-phrase').click(function(){
            var $textField = $(this).closest('.text-field');
            var type = $textField.data('type');
            var path = $(this).prev().find('input').val();
            var domain = $textField.find('.subdomain-cont input').val();

            if (!path && !domain) {
                return;
            }

            $('.tabs-content > div:not(:first)').each(function(){
                var $inputUrl = $(this).find('.text-field[data-type=' + type + '] input[name^=' + type + '_page_url]');

                if (!$inputUrl.val()) {
                    $inputUrl.val(path);
                }

                var $inputDomain = $(this).find('.text-field[data-type=' + type + '] input[name^=' + type + '_page_subdomain]');

                if (!$inputDomain.val()) {
                    $inputDomain.val(domain);
                }
            });

            liquidFieldsHandler();

            printMessage('notice', lang['ext_copy_phrase_done'], false, true);
        });
    });

    {/literal}
    </script>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
    <!-- add new page end -->
{else}
    <div id="grid"></div>
    <script type="text/javascript">
    lang['title'] = "{$lang.title}";
    lang['page_deleted'] = "{$lang.page_deleted}";
    var landingPage;

    {literal}
    $(function(){
        landingPage = new gridObj({
            key: 'landingPage',
            id: 'grid',
            ajaxUrl: rlPlugins + 'landingPage/admin/landingPage.inc.php?q=ext',
            defaultSortField: 'ID',
            defaultSortType: 'ASC',
            title: lang['lp_manager'],
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'Landing_path', mapping: 'Landing_path'},
                {name: 'Original_path', mapping: 'Original_path'},
                {name: 'Meta_title', mapping: 'Meta_title'},
                {name: 'Status', mapping: 'Status'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['title'],
                    dataIndex: 'Meta_title',
                    width: 10,
                },{
                    header: lang['lp_landing_page_url'],
                    dataIndex: 'Landing_path',
                    width: 15,
                    renderer: function(val) {
                        return '<a target="_blank" href="' + val + '">' + val + '</a>';
                    }
                },{
                    header: lang['lp_original_page_url'],
                    dataIndex: 'Original_path',
                    width: 15,
                    renderer: function(val) {
                        return '<a target="_blank" href="' + val + '">' + val + '</a>';
                    }
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    fixed: true,
                    width: 120,
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
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(id) {
                        return `<center>
                            <a href='${rlUrlController}&action=edit&page=${id}'><img class='edit' ext:qtip='${lang.ext_edit}' src='${rlUrlHome}img/blank.gif' /></a>
                            <img data-id='${id}'
                                 class='remove'
                                 ext:qtip='${lang.ext_delete}'
                                 src='${rlUrlHome}img/blank.gif'
                                 onclick="rlConfirm('${lang.ext_notice_delete}', 'deleteLandingPage', '${id}')" />
                        </center>`;
                    }
                }
            ]
        });

        {/literal}{rlHook name='apTpllandingPage'}{literal}

        landingPage.init();
        grid.push(landingPage.grid);
    });

    var deleteLandingPage = function(id) {
        flynax.sendAjaxRequest('deleteLandingPage', {id: id}, function(response) {
            if (response.status === 'OK') {
                landingPage.reload();
                printMessage('notice', lang['page_deleted']);
            } else {
                printMessage('error', lang['system_error']);
            }
        });
    }

    {/literal}
    //]]>
    </script>
{/if}

<!-- Landing pages manager end -->
