<!-- listings status verified -->

<div id="action_blocks">
    {if !isset($smarty.get.action)}
        <!-- filters -->
        <div id="filters" class="hide">
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.filter_by}
            
            <table class="form">
            <tr>
                <td class="name w130">{$lang.listing_id}</td>
                <td class="field">
                    <input class="filters" type="text" id="listing_id" maxlength="60" />
                </td>
            </tr>
            <tr>
                <td class="name w130">{$lang.username}</td>
                <td class="field">
                    <input class="filters" type="text" maxlength="255" id="Account" />
                </td>
            </tr>
            <tr>
                <td class="name w130">{$lang.mail}</td>
                <td class="field">
                    <input class="filters" type="text" id="email" maxlength="60" />
                </td>
            </tr>
            <tr>
                <td class="name w130">{$lang.status}</td>
                <td class="field">
                    <select class="filters w200" id="verified">
                        <option value="">- {$lang.all} -</option>
                        <option value="verified">{$lang.ls_verified}</option>
                        <option value="wait">{$lang.pending}</option>
                    </select>
                </td>
            </tr> 
            <tr>
                <td></td>
                <td class="field nowrap">
                    <input type="button" class="button" value="{$lang.filter}" id="filter_button" />
                    <input type="button" class="button" value="{$lang.reset}" id="reset_filter_button" />
                    <a class="cancel" href="javascript:void(0)" onclick="show('filters')">{$lang.cancel}</a>
                </td>
            </tr>
            </table>
            
            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}
        </div>
        
        <script type="text/javascript">
        {literal}
        var filters = new Array();
        var step = 0;
        var category_selected = null;

        $(document).ready(function(){
            
            if ( readCookie('verified_sc') )
            {
                $('#filters').show();
                var cookie_filters = readCookie('verified_sc').split(',');
                
                for (var i in cookie_filters)
                {
                    if ( typeof(cookie_filters[i]) == 'string' )
                    {
                        var item = cookie_filters[i].split('||');
                        $('#'+item[0]).val(item[1]);
                    }
                }
            }
            
            $('#filter_button').click(function(){
                filters = new Array();
                write_filters = new Array();
                
                createCookie('verified_pn', 0, 1);
                
                $('.filters').each(function(){
                    if ($(this).attr('value') != 0)
                    {
                        filters.push(new Array($(this).attr('id'), $(this).val()));
                        write_filters.push($(this).attr('id')+'||'+$(this).val());
                    }
                });
                
                // save search criteria
                createCookie('verified_sc', write_filters, 1);
                
                // reload grid
                verifiedPhotos.filters = filters;
                verifiedPhotos.reload();
            });
            
            $('#reset_filter_button').click(function(){
                eraseCookie('verified_sc');
                verifiedPhotos.reset();
                
                $("#filters select option[value='']").attr('selected', true);
                $("#filters input[type=text]").val('');
            });
            
            /* autocomplete js */
            $('#Account').rlAutoComplete();
        });
        {/literal}
        </script>
        <!-- filters end -->
    {/if}

</div>

{if $smarty.get.action == 'view'}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    <table class="form">
    <tr>
        <td class="divider" colspan="3"><div class="inner">{$lang.ls_verified_details}</div></td>
    </tr>
    <tr>
        <td class="name">{$lang.ls_verified_photo}</td>
        <td class="field">
            {if $photos}
                <ul class="verfied_photo">
                    <li>
                        <a title="" data-fancybox="verify" href="{$smarty.const.RL_FILES_URL}verified_photos/{$verified_data.ls_image}"><img alt="" class="shadow" style="max-width: 200px; border: 2px #d7dcc9 solid;" src="{$smarty.const.RL_FILES_URL}verified_photos/{$verified_data.ls_image}" /></a>
                    </li>
                </ul>
            {else}
                {$lang.not_available}
            {/if}

        </td>
    </tr>
    <tr>
        <td class="name">{$lang.description}</td>
        <td class="field">{if $verified_data.ls_desc}{$verified_data.ls_desc}{else}{$lang.not_available}{/if}</td>
    </tr>
    <tr>
        <td class="name">{$lang.date}</td>
        <td class="field">{$verified_data.ls_date}</td>
    </tr>
    {if $verified_data.Verified == 'wait'}
    <tr>
        <td class="no_divider"></td>
        <td class="field" style="display: flex;">
            <form action="{$rlBase}index.php?controller={$smarty.get.controller}&module=verify_photos&action=view&amp;id={$smarty.get.id}" method="post">
                <input type="hidden" name="submit" value="1" />
                <input type="hidden" name="status" value="verified" />
                <input type="submit" value="{$lang.ls_verify}" />
            </form>

            <form id="cancel_verification" action="{$rlBase}index.php?controller={$smarty.get.controller}&module=verify_photos&{$verify_type}&action=view&amp;id={$smarty.get.id}" method="post">
                <input type="hidden" name="type" value="{$verify_type}" />
                <input type="hidden" name="status" value="cancel" />
                <textarea class="reason_message hide" rows="5" cols="" name="message"></textarea>
                <a class="cancel" href="javascript:void(0)" style="display: block; margin-top: 10px;">{$lang.cancel}</a>
            </form>
        </td>
    </tr>
    {/if}
    </table>

    <div class="listing_details" style="padding-left: 0;padding-right: 0;">
        <table class="form">
        <tr>
            <td class="divider" colspan="3"><div class="inner">{$lang.listing}</div></td>
        </tr>
        <tr>
            <td class="sidebar" style="width: 200px">
                {if $photos}
                    <ul class="media">
                        {foreach from=$photos item='photo' name='photosF'}
                            <li {if $smarty.foreach.photosF.iteration%2 != 0}class="nl"{/if}>
                                <a title="{$photo.Description}" data-fancybox="gallery" rel="group" href="{$smarty.const.RL_FILES_URL}{$photo.Photo}">
                                    <img alt="" style="width: 100%; height: 100%;" class="shadow" src="{$smarty.const.RL_FILES_URL}{$photo.Thumbnail}" />
                                </a>
                            </li>
                        {/foreach}
                    </ul>
                {/if}

                <ul class="statistics">
                    <li><span class="name">{$lang.category}:</span> <a href="{$rlBase}index.php?controller=browse&amp;id={$listing_data.Category_ID}" target="_blank">{$listing_data.category_name}</a></li>
                    {if $config.count_listing_visits}<li><span class="name">{$lang.shows}:</span> {$listing_data.Shows}</li>{/if}
                    {if $config.display_posted_date}<li><span class="name">{$lang.posted}:</span> {$listing_data.Date|date_format:$smarty.const.RL_DATE_FORMAT}</li>{/if}
                </ul>
            </td>
            <td valign="top">
                <!-- listing info -->
                {rlHook name='apListingDetailsPreFields'}

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
                                <legend id="legend_group_{$group.ID}" class="up" onclick="fieldset_action('group_{$group.ID}');">{$group.name}</legend>
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
        </table>
    </div>

    <table class="form">
    <tr>
        <td class="divider" colspan="3"><div class="inner">{$lang.account_information}</div></td>
    </tr>
    <tr>
        <td valign="top" style="width: 200px; text-align: right; padding-right: 20px;">
            <a target="_blank" title="{$lang.visit_owner_page}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$account_info.ID}">
                <img style="display: inline; width: auto;" {if !empty($account_info.Photo)}class="thumbnail"{/if} alt="{$lang.seller_thumbnail}" src="{if !empty($account_info.Photo)}{$smarty.const.RL_FILES_URL}{$account_info.Photo}{else}{$rlTplBase}img/no-account.png{/if}" />
            </a>

            <ul class="info">
                {if $config.messages_module}<li><input id="contact_owner" type="button" value="{$lang.contact_owner}" /></li>{/if}
                {if $account_info.Own_page}
                    <li><a target="_blank" title="{$lang.visit_owner_page}" href="{$account_info.Personal_address}">{$lang.visit_owner_page}</a></li>
                    <li><a target="_blank" title="{$lang.other_owner_listings}" href="{$rlBase}index.php?controller=accounts&amp;action=view&amp;userid={$account_info.ID}#listings">{$lang.other_owner_listings}</a> <span class="counter">({$account_info.Listings_count})</span></li>
                {/if}
            </ul>
        </td>
        <td valign="top">
            <div class="username">{$account_info.Full_name}</div>
            {if $account_info.Fields}
                <table class="list" style="margin-bottom: 20px;">
                    <tr id="si_field_username">
                        <td class="name">{$lang.username}:</td>
                        <td class="value first">{$account_info.Username}</td>
                    </tr>
                    <tr id="si_field_date">
                        <td class="name">{$lang.join_date}:</td>
                        <td class="value">{$account_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                    </tr>
                    <tr id="si_field_email">
                        <td class="name">{$lang.mail}:</td>
                        <td class="value"><a href="mailto:{$account_info.Mail}">{$account_info.Mail}</a></td>
                    </tr>
                    <tr id="si_field_personal_address">
                        <td class="name">{$lang.personal_address}:</td>
                        <td class="value"><a target="_blank" href="{$account_info.Personal_address}">{$account_info.Personal_address}</a></td>
                    </tr>
                </table>

                <table class="list">
                {foreach from=$account_info.Fields item='item' name='sellerF'}
                    {if !empty($item.value)}
                        <tr id="si_field_{$item.Key}">
                            <td class="name">{$item.name}:</td>
                            <td class="value">{$item.value}</td>
                        </tr>
                    {/if}
                {/foreach}
                </table>
            {/if}
        </td>
    </tr>
    </table>

    <div class="hide">
        <div id="cancel_verification_tmp">
            <div>{$lang.ext_explain_your_reason}</div>
            <div><textarea class="reason_message" rows="5" cols="" name="cancel_verification"></textarea></div>
            <div>
                <input class="confirm" type="button" name="confirm" value="{$lang.go}">
                <a class="cancel" href="javascript:void(0)" >{$lang.cancel}</a>
            </div>
        </div>
    </div>

    {assign var='isFancyappsExist' value=false}
    {if file_exists($smarty.const.RL_LIBS|cat:'fancyapps/fancybox.umd.js')}
        {assign var='isFancyappsExist' value=true}
    {/if}

    <script type="text/javascript">
    let owner_id = {if $account_info.ID}{$account_info.ID}{else}false{/if};
    let is_fancy_app = {if $isFancyappsExist}true{else}false{/if};

    {literal}
    $(function(){
        if (is_fancy_app) {
            flUtil.loadStyle(rlConfig.libs_url + 'fancyapps/fancybox.css');
            flUtil.loadScript(rlConfig.libs_url + 'fancyapps/fancybox.umd.js', function(){
                Fancybox.bind("[data-fancybox]", {});
            });
        }
        else {
            flUtil.loadStyle(rlConfig.libs_url + 'jquery/fancybox/jquery.fancybox.css');
            flUtil.loadScript(rlConfig.libs_url + 'jquery/jquery.fancybox.js', function(){
                $("[data-fancybox]").fancybox();
            });
        }

        $('#contact_owner').click(function(){
            rlPrompt('{/literal}{$lang.contact_owner}{literal}', 'xajax_contactOwner', owner_id, true);
        });


        $('#cancel_verification .cancel').flModal({
            source: '#cancel_verification_tmp',
            caption: lang['ext_confirm'],
            width: 'auto',
            height: 'auto',
            content: '<div id="modal_content">' + lang.loading + '</div>',
            onReady: function() {
                var $closeButton = $('div.modal-window div span:last');

                $('#modal_content').html($('#cancel_verification_tmp').clone());
                $('#modal_content .confirm').click(function () {
                    $('#cancel_verification .reason_message').html($('#modal_content .reason_message').val());
                    $('#cancel_verification').submit();
                });
                $('#modal_content .cancel').click(function () {
                    $closeButton.click();
                });
            }
        });
    });
    {/literal}
    </script>
{else}

    <div id="gridverifiedPhotos"></div>
    <script type="text/javascript">//<![CDATA[
    lang['account'] = "{$lang.account}";
    var cookies_filters = false;

    var verifiedPhotos;

    {literal}
    $(document).ready(function(){

        verifiedPhotos = new gridObj({
            key: 'verify_photos',
            id: 'gridverifiedPhotos',
            ajaxUrl: rlPlugins + 'listing_status/admin/verify_photos.inc.php?q=ext',
            defaultSortField: 'ID',
            filters: cookies_filters,
            filtersPrefix: true,
            title: lang['ext_manager'],
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'listing_title', mapping: 'listing_title'},
                {name: 'Account', mapping: 'Account'},
                {name: 'ls_desc', mapping: 'ls_desc'},
                {name: 'Verified', mapping: 'Verified'},
                {name: 'Verified_key', mapping: 'Verified_key'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 35,
                    fixed: true
                },{
                    header: lang['ext_name'],
                    dataIndex: 'listing_title',
                    width: 100
                },{
                    header: lang['account'],
                    dataIndex: 'Account',
                    width: 100
                },{
                    header: lang['ext_description'],
                    dataIndex: 'ls_desc',
                    width: 130,
                },{
                    header: lang['ext_status'],
                    dataIndex: 'Verified',
                    width: 100,
                    fixed: true,
                    renderer : function(value, metadata, record, rowIndex, colIndex, store){
                        var color = record.data.Verified_key == 'verified' ? '#d2e798' : '#ffe7ad';
                        metadata.attr = 'style="background-color: '+color+'"';
                        return value;
                    },
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data) {
                        var out = "<center>";
                            out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&module=verify_photos&action=view&id="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                            out += "</center>";
                        return out;
                    }
                }
            ]
        });

        verifiedPhotos.init();
        grid.push(verifiedPhotos.grid);

    });
    {/literal}
    //]]>
    </script>
{/if}

<!-- listings status verified end -->
