<!-- payAsYouGoCredits plugin -->

<!-- navigation bar -->
<div id="nav_bar">
    {if $smarty.get.action}
    <a href="{$rlBaseC|replace:'&amp;':''}" class="button_bar"><span class="left"></span>
        <span class="center_list">{$lang.paygc_credits_manager}</span><span class="right"></span>
    </a>
    {else}
    <a href="{$rlBaseC}action=add" class="button_bar"><span class="left"></span>
        <span class="center_add">{$lang.paygc_add_item}</span><span class="right"></span>
    </a>
    {/if}
</div>
<!-- navigation bar end -->

{if $smarty.get.action}
{assign var='sPost' value=$smarty.post}

<!-- add/edit banner plan -->
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}
<form action="{$rlBaseC}action={if $smarty.get.action == 'add'}add{else}edit&amp;item={$smarty.get.item}{/if}" method="post">
    <input type="hidden" name="submit" value="1" />
    {if $smarty.get.action == 'edit'}
        <input type="hidden" name="fromPost" value="1" />
    {/if}
    <table class="form">
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
        <td class="name">{$lang.paygc_total_credits}</td>
        <td class="field">
            <input type="text" name="credits" value="{if $sPost.credits}{$sPost.credits}{else}0{/if}" class="numeric" style="width: 50px; text-align: center;" />
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.price}</td>
        <td class="field">
            <input type="text" name="price" value="{if $sPost.price}{$sPost.price}{else}0{/if}" class="numeric" style="width: 50px; text-align: center;" />
            <span class="field_description_noicon">{$config.system_currency}</span>
        </td>
    </tr>
    <tr>
        <td class="name">{$lang.status}</td>
        <td class="field">
            <select name="status">
                <option value="active" {if $sPost.status == 'active'}selected="selected"{/if}>{$lang.active}</option>
                <option value="approval" {if $sPost.status == 'approval'}selected="selected"{/if}>{$lang.approval}</option>
            </select>
        </td>
    </tr>
    </table>

    <table class="form">
    <tr>
        <td class="no_divider"></td>
        <td class="field">
            <input type="submit" value="{if $smarty.get.action == 'edit'}{$lang.edit}{else}{$lang.add}{/if}" />
        </td>
    </tr>
    </table>
</form>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
<!-- add/edit banner plan end -->

{else}

<!-- credits tpl -->
<div id="grid"></div>
<script type="text/javascript">
var creditsGrid;

{literal}
$(document).ready(function(){
    creditsGrid = new gridObj({
        key: 'credits',
        id: 'grid',
        ajaxUrl: rlPlugins + 'payAsYouGoCredits/admin/credits_manager.inc.php?q=ext',
        title: lang.paygc_credits_manager,
        defaultSortField: 'ID',
        defaultSortType: 'DESC',
        fields: [
            {name: 'ID', mapping: 'ID', type: 'int'},
            {name: 'Key', mapping: 'Key', type: 'string'},
            {name: 'name', mapping: 'name', type: 'string'},
            {name: 'Price', mapping: 'Price', type: 'float'},
            {name: 'Credits', mapping: 'Credits', type: 'int'},
            {name: 'Position', mapping: 'Position', type: 'int'},
            {name: 'Status', mapping: 'Status'}
        ],
        columns: [
            {
                header: lang['ext_name'],
                dataIndex: 'name',
                width: 20
            },{
                header: lang['ext_credits'],
                dataIndex: 'Credits',
                id: 'rlExt_item_bold',
                width: 120,
                fixed: true,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowDecimals: false
                }),
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: lang['ext_price']+' ('+rlCurrency+')',
                dataIndex: 'Price',
                id: 'rlExt_item',
                width: 120,
                fixed: true,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowDecimals: false
                }),
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
                }
            },{
                header: lang['ext_position'],
                dataIndex: 'Position',
                width: 100,
                fixed: true,
                editor: new Ext.form.NumberField({
                    allowBlank: false,
                    allowDecimals: false
                }),
                renderer: function(val){
                    return '<span ext:qtip="'+lang['ext_click_to_edit']+'">'+val+'</span>';
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
                dataIndex: 'ID',
                sortable: false,
                renderer: function(id) {
                    let imgEdit = `<img class="edit" ext:qtip="${lang.ext_edit}" src="${rlUrlHome}img/blank.gif">`;

                    return `<div style="text-align: center;">
                                <a href="${rlUrlController}&action=edit&item=${id}">${imgEdit}</a>
                                <img class="remove"
                                    ext:qtip="${lang.ext_delete}"
                                    src="${rlUrlHome}img/blank.gif"
                                    onclick="rlConfirm('${lang.ext_notice_delete}', 'deleteCreditPackage', '${id}')"
                                >
                            </div>`;
                }
            }
        ]
    });

    creditsGrid.init();
    grid.push(creditsGrid.grid);
});

let deleteCreditPackage = function(id) {
        flynax.sendAjaxRequest('deleteCreditPackage', {id: id}, function(response) {
            if (response && response.status === 'OK') {
                creditsGrid.reload();
                printMessage('notice', '{/literal}{$lang.item_deleted}{literal}');
            } else {
                printMessage('error', lang.system_error);
            }
        });
    }
{/literal}
</script>

{/if}

<!-- end payAsYouGoCredits plugin -->
