{if $smarty.get.action}
    {if $smarty.get.action == 'view'}

        {if $auction_info}
            {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/auction_details.tpl'}
        {/if}
        
        <!-- ext grid -->
        <div id="grid"></div>
        <script type="text/javascript">
        var auctionBidsGrid;
        var item_id = '{$smarty.get.item_id}';

        {literal}
        $(document).ready(function(){
            
            auctionBidsGrid = new gridObj({
                key: 'auction_bids',
                id: 'grid',
                ajaxUrl: rlPlugins + 'shoppingCart/admin/shopping_cart.inc.php?item_id=' + item_id +'&q=ext_bids',
                defaultSortField: 'Date',
                remoteSortable: true,
                checkbox: true,
                actions: [
                    [lang['ext_delete'], 'delete']
                ],
                title: lang['ext_shc_bids_manager'],
                fields: [
                    {name: 'ID', mapping: 'ID'},
                    {name: 'Item', mapping: 'Item'},
                    {name: 'bUsername', mapping: 'bUsername', type: 'string'},
                    {name: 'dUsername', mapping: 'dUsername', type: 'string'},
                    {name: 'bFull_name', mapping: 'bFull_name', type: 'string'},
                    {name: 'dFull_name', mapping: 'dFull_name', type: 'string'},
                    {name: 'Buyer_ID', mapping: 'Buyer_ID', type: 'string'},
                    {name: 'Dealer_ID', mapping: 'Dealer_ID', type: 'string'},
                    {name: 'Total', mapping: 'Total', type: 'string'},
                    {name: 'Commission_total', mapping: 'Commission_total'},
                    {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'}
                ],
                columns: [
                    {
                        header: lang['ext_id'],
                        dataIndex: 'ID',
                        width: 35,
                        fixed: true,
                        id: 'rlExt_black_bold'
                    },{
                        header: lang['shc_buyer'],
                        dataIndex: 'bFull_name',
                        width: 30,
                        renderer: function(data, obj, row)
                        {
                            if (row.data.bUsername) {
                                var out = '<a class="green_11_bg" href="'+rlUrlHome+'index.php?controller=accounts&action=view&userid='+row.data.Buyer_ID+'" ext:qtip="'+lang['ext_click_to_view_details']+'">'+data+'</a>';
                            }
                            else {
                                var out = '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                            }
                            return out;
                        }
                    },{
                        header: lang['ext_price'] + ' ('+rlCurrency+')',
                        dataIndex: 'Total',
                        width: 100,
                        fixed: true
                    }{/literal}{if $config.shc_method == 'multi' && $config.shc_commission_enable}{literal},{
                        header: lang['shc_dealer'],
                        dataIndex: 'dFull_name',
                        width: 30,
                        renderer: function(data, obj, row)
                        {
                            if (row.data.dUsername) {
                                var out = '<a class="green_11_bg" href="'+rlUrlHome+'index.php?controller=accounts&action=view&userid='+row.data.Dealer_ID+'" ext:qtip="'+lang['ext_click_to_view_details']+'">'+data+'</a>';
                            }
                            else {
                                var out = '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                            }
                            return out;
                        }
                    },{
                        header: lang['shc_commission']+' ('+rlCurrency+')',
                        dataIndex: 'Commission_total',
                        width: 100,
                        fixed: true,
                    }{/literal}{/if}{literal},{
                        header: lang['ext_date'],
                        dataIndex: 'Date',
                        width: 20,
                        renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M') + ' H:i:s')
                    },{
                        header: lang['ext_actions'],
                        width: 80,
                        fixed: true,
                        dataIndex: 'ID',
                        sortable: false,
                        renderer: function(data) {
                            var out = "<center>";
                            var splitter = false;

                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"shoppingCart.deleteBid\", \""+data+"\", \"load\" )' />";
     
                            out += "</center>";

                            return out;
                        }
                    }
                ]
            });
            
            auctionBidsGrid.init();
            grid.push(auctionBidsGrid.grid);
            
            // actions listener
            auctionBidsGrid.actionButton.addListener( 'click', function() {
                var sel_obj = auctionBidsGrid.checkboxColumn.getSelections();
                var action = auctionBidsGrid.actionsDropDown.getValue();

                if (!action) {
                    return false;
                }
                
                for (var i = 0; i < sel_obj.length; i++) {
                    auctionBidsGrid.ids += sel_obj[i].id;
                    if (sel_obj.length != i+1) {
                        auctionBidsGrid.ids += '|';
                    }
                }
                
                if (action == 'delete') {
                    Ext.MessageBox.confirm( 'Confirm', lang['ext_notice_'+delete_mod], function(btn) {
                        if (btn == 'yes') {
                            shoppingCart.deleteBid(auctionBidsGrid.ids);
                        }
                    });
                }
            });
            
        });
        {/literal}
        </script>
        <!-- ext grid end -->
    {/if}
{else}
    
    <!-- Search -->
    <div id="search" class="hide">
        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl' block_caption=$lang.search}
        <table>
        <tr>
            <td valign="top">
                <table class="form">
                <tr>
                    <td class="name w130">{$lang.shc_dealer}</td>
                    <td class="field">
                        <input type="text" id="username" maxlength="60" />
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.status}</td>
                    <td class="field"> 
                        <select id="shc_auction_status" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            {foreach from=$shc_auction_status item='status'}
                                <option value="{$status.key}" {if $sPost.auction.shc_auction_status == $status.key}selected="selected"{/if}>{$status.name}</option>
                            {/foreach}
                        </select>
                    </td>
                </tr>
                <tr id="has_winner_item" class="hide">
                    <td class="name w130">{$lang.shc_has_winner}</td>
                    <td class="field"> 
                        <select id="has_winner" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            <option value="yes" {if $sPost.auction.has_winner == 'yes'}selected="selected"{/if}>{$lang.yes}</option>
                            <option value="no" {if $sPost.auction.has_winner == 'no'}selected="selected"{/if}>{$lang.no}</option>
                        </select>
                    </td>
                </tr>
                <tr>
                    <td class="name w130">{$lang.shc_payment_status}</td>
                    <td class="field">
                        <select id="shc_payment_status" style="width: 200px;">
                            <option value="">{$lang.select}</option>
                            {foreach from=$shc_payment_status item='pstatus'}
                                <option value="{$pstatus.key}" {if $sPost.auction.shc_payment_status == $ptatus.key}selected="selected"{/if}>{$pstatus.name}</option>
                            {/foreach}
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
    
    var sFields = new Array('username', 'shc_auction_status', 'has_winner', 'shc_payment_status');
    var cookie_filters = new Array();
    
    $(document).ready(function(){
        $('#shc_auction_status').change(function() {
            if($(this).val() == 'closed') {
                $('#has_winner_item').show();
            } else {
                $('#has_winner_item').hide();
            }
        });
                
        if (readCookie('auction_sc')) {
            $('#search').show();
            cookie_filters = readCookie('auction_sc').split(',');
            
            for (var i in cookie_filters) {
                if (typeof(cookie_filters[i]) == 'string') {
                    var item = cookie_filters[i].split('||');
                    $('#'+item[0]).selectOptions(item[1]);
                    
                    if (item[0] == 'shc_auction_status' && item[1] == 'closed') {
                        $('#has_winner_item').show();
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
            createCookie('auction_sc', save_cookies, 1);
            
            filters.push(new Array('search', 1));
            
            auctionGrid.filters = filters;
            auctionGrid.reload();
        });
        
        $('#reset_filter_button').click(function() {
            eraseCookie('auction_sc');
            auctionGrid.reset();
            
            $("#search select option[value='']").attr('selected', true);
            $("#search input[type=text]").val('');
            
            $('#has_winner_item').hide(); 
        });
        
        /* autocomplete js */
        $('#username').rlAutoComplete();
    });
    
    {/literal}
    
    </script>
    <!-- end Search -->

    <!-- ext grid -->
    <div id="grid"></div>
    <script type="text/javascript">
    var auctionGrid;

    {literal}
    $(document).ready(function(){
        
        auctionGrid = new gridObj({
            key: 'auction',
            id: 'grid',
            ajaxUrl: rlPlugins + 'shoppingCart/admin/shopping_cart.inc.php?q=ext_auction',
            defaultSortField: 'Date',
            remoteSortable: true,
            checkbox: true,
            actions: [
                [lang['ext_delete'], 'delete']
            ],
            title: lang['ext_shc_auction_manager'],
            filters: cookie_filters,
            fields: [
                {name: 'ID', mapping: 'ID'},
                {name: 'Item', mapping: 'Item'},
                {name: 'Username', mapping: 'Username', type: 'string'},
                {name: 'Account_ID', mapping: 'Account_ID', type: 'string'},
                {name: 'Price', mapping: 'Price', type: 'string'},
                {name: 'Start_price', mapping: 'Start_price'},
                {name: 'Max_bid', mapping: 'Max_bid'},
                {name: 'Commission_total', mapping: 'Commission_total'},
                {name: 'shc_start_time', mapping: 'shc_start_time', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'left_time', mapping: 'left_time'},
                {name: 'shc_total_bids', mapping: 'shc_total_bids'},
                {name: 'pStatus', mapping: 'pStatus'},
                {name: 'Auction_won', mapping: 'Auction_won'},
                {name: 'shc_auction_status', mapping: 'shc_auction_status'},
                {name: 'Date', mapping: 'Date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Escrow_date', mapping: 'Escrow_date', type: 'date', dateFormat: 'Y-m-d H:i:s'},
                {name: 'Escrow_status', mapping: 'Escrow_status'}
            ],
            columns: [
                {
                    header: lang['ext_id'],
                    dataIndex: 'ID',
                    width: 35,
                    fixed: true,
                    id: 'rlExt_black_bold'
                },{
                    header: lang['shc_dealer'],
                    dataIndex: 'Username',
                    width: 120,
                    fixed: true,
                    renderer: function(username, obj, row)
                    {
                        if ( username )
                        {
                            var out = '<a class="green_11_bg" href="'+rlUrlHome+'index.php?controller=accounts&action=view&userid='+row.data.Account_ID+'" ext:qtip="'+lang['ext_click_to_view_details']+'">'+username+'</a>';
                        }
                        else
                        {
                            var out = '<span class="delete">{/literal}{$lang.account_removed}{literal}</span>';
                        }
                        return out;
                    }
                },{
                    header: lang['ext_item'],
                    dataIndex: 'Item',
                    width: 20
                },{
                    header: lang['ext_price'] + ' ('+rlCurrency+')',
                    dataIndex: 'Price',
                    width: 100,
                    fixed: true
                },{
                    header: lang['shc_start_price'] + ' ('+rlCurrency+')',
                    dataIndex: 'Start_price', 
                    width: 100,
                    fixed: true
                },{
                    header: lang['shc_current_bid'] + ' ('+rlCurrency+')',
                    dataIndex: 'Max_bid', 
                    width: 100,
                    fixed: true
                }{/literal}{if $config.shc_method == 'multi' && $config.shc_commission_enable}{literal},{
                    header: lang['shc_commission']+' ('+rlCurrency+')',
                    dataIndex: 'Commission_total',
                    width: 100,
                    fixed: true,
                }{/literal}{/if}{literal},{
                    header: lang['shc_left_time'],
                    dataIndex: 'left_time',
                    width: 100,
                    fixed: true
                },{
                    header: lang['total_bids'],
                    dataIndex: 'shc_total_bids',
                    width: 5
                },{
                    header: lang['start_time'],
                    dataIndex: 'shc_start_time',
                    width: 100,
                    fixed: true,
                    renderer: Ext.util.Format.dateRenderer('Y-m-d H:i:s'),
                },{
                    header: lang['ext_date'],
                    dataIndex: 'Date',
                    width: 80,
                    fixed: true,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['payment_status'],
                    dataIndex: 'pStatus',
                    width: 80,
                    fixed: true,
                    renderer: function (val, obj, row) {
                        if (val == lang['ext_paid']) {
                            obj.style += 'background: #D2E798;';
                            return '<span>' + val + '</span>';  
                        }
                        else if (val == lang['ext_unpaid']) {
                            obj.style += 'background: #FF878A;';
                            return '<span>' + val + '</span>'; 
                        }
                        else if (val == lang['shc_progress']) {
                            obj.style += 'background: #C0ECEE;';
                            return '<span>' + lang['shc_progress'] + '</span>'; 
                        }
                        else if (val == lang['canceled'])
                        {
                            obj.style += 'background: #d7d7d7;';
                            return '<span>' + val + '</span>';
                        }
                        else {
                            return '<span>' + val + '</span>'; 
                        }
                    }
                }{/literal}{if $config.shc_escrow}{literal},{
                    header: lang['shc_escrow_expiration'],
                    dataIndex: 'Escrow_date',
                    width: 120,
                    fixed: true,
                    renderer: Ext.util.Format.dateRenderer(rlDateFormat.replace(/%/g, '').replace('b', 'M'))
                },{
                    header: lang['shc_order_confirm'],
                    width: 80,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(val, obj, row) {
                        let escrowStatus = row.data.Escrow_status;
                        if (escrowStatus == 'confirmed')
                        {
                            obj.style += 'background: #D2E798;';
                            return '<span>' + lang['shc_escrow_confirmed'] + '</span>';
                        }
                        else if (escrowStatus == 'canceled')
                        {
                            obj.style += 'background: #FF878A;';
                            return '<span>' + lang['shc_escrow_canceled'] + '</span>';
                        }
                        else if (escrowStatus == 'pending')
                        {
                            if (row.data.Status == 'paid') {
                                return '<div class="cell-action"><a href="javascript://" onClick="rlConfirm(\'' + lang['shc_do_you_want_confirm_order'] + '\', \'confirmOrder\', \'' + val + ', ' + row.data.Buyer_ID + '\', \'load\')" title="' + lang['shc_order_confirm'] + '"><img src="' + rlUrlHome + 'img/blank.gif" class="escrow-pending"></a></div>';
                            } else {
                                return '<div class="cell-action"><span  title="' + lang['shc_order_unpaid'] + '"><img src="' + rlUrlHome + 'img/blank.gif" class="escrow-confirmed"></span></div>';
                            }
                        }
                    }
                }{/literal}{/if}{literal},{
                    header: lang['ext_status'],
                    dataIndex: 'shc_auction_status',
                    width: 80,
                    fixed: true,
                    editor: new Ext.form.ComboBox({
                        store: [
                            ['active', lang['ext_active']],
                            ['closed', lang['shc_closed']],
                            ['expired', lang['ext_expired']]
                        ],
                        displayField: 'value',
                        valueField: 'key',
                        typeAhead: true,
                        mode: 'local',
                        triggerAction: 'all',
                        selectOnFocus:true
                    }),
                    renderer: function(val, obj, row){
                        if (val == lang['ext_active']) {
                            obj.style += 'background: #D2E798;';
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">' + val + '</span>';  
                        }
                        else if (val == lang['shc_closed']) {
                            obj.style += 'background: #E0E0E0;';
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">' + val + '</span>'; 
                        }
                        else if (val == lang['ext_expired']) {
                            obj.style += 'background: #FF878A;';
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">' + val + '</span>'; 
                        }
                        else {
                            return '<span ext:qtip="'+lang['ext_click_to_edit']+'">' + val + '</span>'; 
                        }
                    }
                },{
                    header: lang['ext_actions'],
                    width: 80,
                    fixed: true,
                    dataIndex: 'ID',
                    sortable: false,
                    renderer: function(data, obj, row) {
                        var out = "<center>";
                        var splitter = false;

                        out += "<a href='"+rlUrlHome+"index.php?controller="+controller+"&module=auction&action=view&item_id="+data+"'><img class='view' ext:qtip='"+lang['ext_view']+"' src='"+rlUrlHome+"img/blank.gif' /></a>";
                        if (row.data.pStatus != lang['shc_progress'] && row.data.Auction_won != '') {
                            out += "<img class='remove' ext:qtip='"+lang['ext_delete']+"' src='"+rlUrlHome+"img/blank.gif' onClick='rlConfirm( \""+lang['ext_notice_'+delete_mod]+"\", \"shoppingCart.deleteAuctionItem\", \""+data+"\", \"load\" )' />";
                        }
 
                        out += "</center>";

                        return out;
                    }
                }
            ]
        });
        
        auctionGrid.init();
        grid.push(auctionGrid.grid);
        
        // actions listener
        auctionGrid.actionButton.addListener('click', function() {
            var sel_obj = auctionGrid.checkboxColumn.getSelections();
            var action = auctionGrid.actionsDropDown.getValue();

            if (!action) {
                return false;
            }
            
            for (var i = 0; i < sel_obj.length; i++) {
                auctionGrid.ids += sel_obj[i].id;
                if (sel_obj.length != i+1) {
                    auctionGrid.ids += '|';
                }
            }
            
            if (action == 'delete') {
                Ext.MessageBox.confirm('Confirm', lang['ext_notice_'+delete_mod], function(btn){
                    if (btn == 'yes') {
                        shoppingCart.deleteAuctionItem(auctionGrid.ids);
                    }
                });
            }
        });
        
    });

    let confirmOrder = function(orderID, accountID) {
        var data = {orderID: orderID, accountID: accountID};
        flynax.sendAjaxRequest('shoppingCartConfirmOrder', data, function(response){
            if (response.status == 'OK') {
                printMessage('notice', response.message);
                auctionGrid.reload();
            }
        });
    }
    {/literal}
    </script>
    <!-- ext grid end -->
{/if}
