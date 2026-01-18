<!-- claim listing tpl -->

<!-- navigation bar -->
<div id="nav_bar">{strip}
    <a href="{$rlBase}index.php?controller={$smarty.get.controller}" class="button_bar">
        <span class="left"></span>
        <span class="center_list">{$lang.cl_requests}</span>
        <span class="right"></span>
    </a>
{/strip}</div>
<!-- navigation bar end -->

{if $smarty.get.item}
    {* @todo - Remove this when compatibility will be > 4.9.0 and use "fancyapps" only *}
    {assign var='isFancyappsExist' value=false}
    {if file_exists($smarty.const.RL_LIBS|cat:'fancyapps/fancybox.umd.js')}
        {assign var='isFancyappsExist' value=true}
    {/if}

    {if $isFancyappsExist}
        <link href="{$smarty.const.RL_LIBS_URL}fancyapps/fancybox.css" type="text/css" rel="stylesheet" />
        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}fancyapps/fancybox.umd.js"></script>
    {else}
        <link href="{$smarty.const.RL_LIBS_URL}jquery/fancybox/jquery.fancybox.css" type="text/css" rel="stylesheet" />
        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}player/flowplayer.js"></script>
        <script type="text/javascript" src="{$smarty.const.RL_LIBS_URL}jquery/jquery.fancybox.js"></script>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {if $claim_info.Listings_IDs}
        <ul class="tabs">
            <li lang="basic" class="active">{$lang.cl_basic_tab}</li>
            <li lang="all_listings">{$lang.cl_all_listings_tab}</li>
        </ul>
    {/if}

    {if $claim_info.Listings_IDs}<div class="tab_area basic">{/if}
        <fieldset class="light">
            <legend id="legend_listingInfo" class="up" onclick="fieldset_action('listingInfo');">{$lang.listing}</legend>
            <div id="listingInfo">
                <div class="listing_details">
                    <table class="sTable">
                        <tr>
                            <td class="sidebar" style="width: 210px">
                                {if $photos}
                                    <ul class="media">
                                        {foreach from=$photos item='photo' name='photosF'}
                                            <li {if $smarty.foreach.photosF.iteration%2 != 0}class="nl"{/if}>
                                                <a data-fancybox="listing-gallery"
                                                   title="{$photo.Description}"
                                                   rel="group"
                                                   href="{$smarty.const.RL_FILES_URL}{$photo.Photo}"
                                                >
                                                    <img alt="" class="shadow" src="{$smarty.const.RL_FILES_URL}{$photo.Thumbnail}" />
                                                </a>
                                            </li>
                                        {/foreach}
                                    </ul>
                                {/if}

                                <ul class="statistics">
                                    <li><span class="name">{$lang.category}:</span> <a href="{$rlBase}index.php?controller=browse&id={$listing_data.Category_ID}" target="_blank">{$listing_data.category_name}</a></li>
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
            </div>
        </fieldset>

        <fieldset class="light">
            <legend id="legend_accountInfo" class="up" onclick="fieldset_action('accountInfo')">{$lang.account_information}</legend>

            <div id="accountInfo">
                <div class="account listing_details">
                    <table class="sTatic">
                        <tr>
                            <td valign="top" style="min-width: 210px; text-align: right; padding-right: 20px;">
                                <a target="_blank" title="{$lang.visit_owner_page}" href="{$rlBase}index.php?controller=accounts&action=view&userid={$account_info.ID}">
                                    <img style="display: inline; width: auto;" {if !empty($account_info.Photo)}class="thumbnail"{/if} alt="{$lang.seller_thumbnail}" src="{if !empty($account_info.Photo)}{$smarty.const.RL_FILES_URL}{$account_info.Photo}{else}{$rlTplBase}img/no-account.png{/if}" />
                                </a>

                                <ul class="info">
                                    {if $config.messages_module}<li><input id="contact_owner" type="button" value="{$lang.contact_owner}" /></li>{/if}
                                    {if $account_info.Own_page}
                                        <li><a target="_blank" title="{$lang.visit_owner_page}" href="{$account_info.Personal_address}">{$lang.visit_owner_page}</a></li>
                                        <li><a target="_blank" title="{$lang.other_owner_listings}" href="{$rlBase}index.php?controller=accounts&action=view&userid={$account_info.ID}#listings">{$lang.other_owner_listings}</a> <span class="counter">({$account_info.Listings_count})</span></li>
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
                </div>
            </div>
        </fieldset>

        <fieldset class="light">
            <legend id="legend_claimInfo" class="up" onclick="fieldset_action('claimInfo')">{$lang.cl_request}</legend>

            <div id="claimInfo">
                <div class="claim listing_details">
                    <form method="post" action="{$smarty.const.RL_URL_HOME}{$smarty.const.ADMIN}/index.php?controller=claimListing">
                        <input type="hidden" name="ID" value="{$claim_info.ID}" />

                        <table class="list">
                            <tr>
                                <td class="name" style="min-width: 210px; text-align: right;">{$lang.cl_method}</td>
                                <td class="value">
                                    {if $claim_info.Claim_method === 'image'}
                                        {$lang.photo}
                                    {else}
                                        {$claim_info.Claim_method|ucfirst}
                                    {/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name" style="min-width: 210px; text-align: right;">
                                    {assign var='cl_phone_field_name' value="listing_fields+name+"|cat:$config.cl_phone_field}
                                    {assign var='cl_email_field_name' value="listing_fields+name+"|cat:$config.cl_email_field}

                                    {if $claim_info.Claim_method == 'phone'}
                                        {$lang.$cl_phone_field_name}
                                    {elseif $claim_info.Claim_method == 'email'}
                                        {$lang.$cl_email_field_name}
                                    {else}
                                        {$lang.photo}
                                    {/if}
                                </td>
                                <td class="value">
                                    {if $claim_info.Claim_method == 'image'}
                                        <ul class="media" style="padding: 0; text-align: left; width: {$config.pg_upload_thumbnail_width}px;"><li>
                                            <a data-fancybox="claim-photo"
                                               title="{$lang.cl_image}"
                                               {if $claim_info.Data}rel="claim" href="{$smarty.const.RL_FILES_URL}claim_images/{$claim_info.Data}"{else}href="#"{/if}
                                            >
                                                <img width="{$config.pg_upload_thumbnail_width}"
                                                     height="{$config.pg_upload_thumbnail_height}"
                                                     alt=""
                                                     {if $claim_info.Data}class="shadow" src="{$smarty.const.RL_FILES_URL}claim_images/{$claim_info.Data}"
                                                     {else}src="{$smarty.const.RL_URL_HOME}templates/{$config.template}/img/no-picture.png"{/if}
                                                />
                                            </a>
                                        </li></ul>
                                    {else}
                                        {$claim_info.Data}
                                    {/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name" style="min-width: 210px; text-align: right;">{$lang.date}</td>
                                <td class="value">{$claim_info.Date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                            </tr>
                            <tr>
                                <td class="name" style="min-width: 210px; text-align: right;">{$lang.cl_ip_address}</td>
                                <td class="value">{$claim_info.IP}</td>
                            </tr>
                            <tr>
                                <td class="name" style="min-width: 210px; text-align: right;">
                                    {$lang.status}
                                </td>
                                <td class="value">
                                    {if $claim_info.Status == 'active'}{$lang.cl_confirmed}{else}{$lang.pending}{/if}
                                </td>
                            </tr>

                            {if $claim_info.Status == 'pending'}
                                <tr>
                                    <td class="name no_divider"></td>
                                    <td>
                                        <input type="submit" name="confirm" value="{$lang.cl_confirm}" />
                                    </td>
                                </tr>
                            {/if}
                        </table>
                    </form>
                </div>
            </div>
        </fieldset>
    {if $claim_info.Listings_IDs}</div>{/if}

    {if $claim_info.Listings_IDs}
        <div class="tab_area all_listings hide">
            <div id="grid"></div>
            <script type="text/javascript">
            var clListingsGrid;
            var grid_subtract_width = 72; // because the grid placed in a custom area (div>div)
            var cl_ids = '{$claim_info.Listings_IDs}';

            {literal}
            $(document).ready(function(){
                clListingsGrid = new gridObj({
                    key: 'clListingsGrid',
                    id: 'grid',
                    ajaxUrl: rlPlugins + 'claimListing/admin/claimListing.inc.php?q=ext&cl_ids=' + cl_ids,
                    defaultSortField: 'ID',
                    title: lang['cl_ext_caption'],
                    fields: [
                        {name: 'ID', mapping: 'ID', type: 'int'},
                        {name: 'Listing', mapping: 'Listing', type: 'string'},
                        {name: 'Account', mapping: 'Account', type: 'string'},
                        {name: 'Status', mapping: 'Status', type: 'string'}
                    ],
                    columns: [
                        {
                            header: lang['ext_id'],
                            dataIndex: 'ID',
                            width: 40,
                            fixed: true,
                            id: 'rlExt_black_bold'
                        },{
                            header: "{/literal}{$lang.listing}{literal}",
                            dataIndex: 'Listing',
                            width: 200
                        },{
                            header: "{/literal}{$lang.account}{literal}",
                            dataIndex: 'Account',
                            width: 200
                        }, {
                            header: lang['ext_status'],
                            dataIndex: 'Status',
                            width: 200,
                            fixed: true
                        }
                    ]
                });

                var grid_exist = false;
                $('ul.tabs li[lang=all_listings]').click(function(){
                    if (!grid_exist) {
                        $('div.tab_area.all_listings').show();

                        clListingsGrid.init();
                        grid.push(clListingsGrid.grid);
                        grid_exist = true;
                    }
                });
            });
            {/literal}</script>
        </div>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'}

    <script type="text/javascript">
    var owner_id = {if $account_info.ID}{$account_info.ID}{else}false{/if}

    {literal}
    $(document).ready(function(){
        {/literal}{if !$isFancyappsExist}{literal}
            $('ul.media a').fancybox({
                titlePosition: 'over',
                centerOnScroll: true,
                scrolling: 'yes'
            });
        {/literal}{/if}{literal}

        $('#contact_owner').click(function(){
            rlPrompt('{/literal}{$lang.contact_owner}{literal}', 'xajax_contactOwner', owner_id, true);
        });
    });
    {/literal}
    </script>
{else}
    <!-- grid -->
    <div id="grid"></div>
    <script type="text/javascript">//<![CDATA[
    var claimListingGrid;

    {literal}
    $(document).ready(function(){
        claimListingGrid = new gridObj({
            key: 'claimListingGrid',
            id: 'grid',
            ajaxUrl: rlPlugins + 'claimListing/admin/claimListing.inc.php?q=ext',
            defaultSortField: 'ID',
            title: lang['cl_ext_caption'],
            fields: [
                {name: 'ID', mapping: 'ID', type: 'int'},
                {name: 'Listing', mapping: 'Listing', type: 'string'},
                {name: 'Account', mapping: 'Account', type: 'string'},
                {name: 'Claim_method', mapping: 'Claim_method', type: 'string'},
                {name: 'Status', mapping: 'Status', type: 'string'},
                {name: 'Details', mapping: 'Details', type: 'string'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 40,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: "{/literal}{$lang.listing}{literal}",
                    dataIndex: 'Listing',
                },{
                    header: "{/literal}{$lang.account}{literal}",
                    dataIndex: 'Account',
                    width: 200,
                    fixed: true
                },{
                    header: '{/literal}{$lang.cl_method}{literal}',
                    dataIndex: 'Claim_method',
                    width: 150,
                    fixed: true
                }, {
                    header: lang['ext_status'],
                    dataIndex: 'Status',
                    fixed: true,
                    width: 100,
                    renderer: function(val, param1){
                        if (val == '{/literal}{$lang.cl_confirmed}{literal}') {
                            param1.style += 'background: #d2e798;';
                        } else {
                            param1.style += 'background: #f9cece;';
                        }
                        return val;
                    }
                },{
                    header: lang['ext_actions'],
                    width: 70,
                    fixed: true,
                    dataIndex: 'ID',
                    renderer: function(id) {
                        var out = "<center><a href='" + rlUrlHome + "index.php?controller=" + controller;
                        out += "&action=view&item=" + id + "'><img class='view' ext:qtip='" + lang['ext_view_details'];
                        out += "' src='" + rlUrlHome + "img/blank.gif' /></a>";
                        out += "<img class='remove' ext:qtip='" + lang['ext_delete'] + "' src='" + rlUrlHome;
                        out += "img/blank.gif' onClick='rlConfirm(\"" + lang['ext_notice_' + delete_mod];
                        out += "\", \"apAjaxRequest\", \"" + id + "\" )' />";

                        return out;
                    }
                }
            ]
        });

        claimListingGrid.init();
        grid.push(claimListingGrid.grid);
    });

    var apAjaxRequest = function(data) {
        if (data) {
            $.post(
                rlConfig['ajax_url'],
                {item: 'deleteClaimRequest', id: data},
                function(response){
                    if (response && response.status && response.message) {
                        if (response.status == 'OK') {
                            printMessage('notice', response.message);
                            claimListingGrid.reload();
                        } else {
                            printMessage('error', response.message);
                        }
                    }
                },
                'json'
            );
        }
    }
    {/literal}</script>
    <!-- grid end -->
{/if}

<!-- claim request tpl end -->
