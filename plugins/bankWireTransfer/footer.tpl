{if $pageInfo.Key == 'payment_history'}
    <script class="fl-js-dynamic">
        var transactions = new Array();
        {foreach from=$transactions item='item' key='key'}
            var html_item = '';
            {if $item.Gateway_key == 'bankWireTransfer' && !empty($item.Txn_ID)}
                html_item = '<div data-caption="{$lang.bwt_doc_file}" id="bwt-file-{$item.ID}">';
                {if $item.Status == 'unpaid'}
                    html_item += '<a href="javascript://" class="bwt-upload-file mb-2{if !empty($item.Doc)} hide{else} d-block{/if}" title="{$lang.bwt_upload}" data-item="{$item.ID}"><svg viewBox="0 0 24 24" width="18" height="18" class="icon grid-icon-fill align-middle"><use xlink:href="#upload"></use></svg>&nbsp;{$lang.upload}</a>';
                {/if}
                {if !empty($item.Doc)}
                    html_item += '<a class="d-block download text-truncate d-inline-block" style="max-width: 120px;" href="{$smarty.const.RL_FILES_URL}{$item.Doc}" target="_blank" title="{$lang.bwt_view_doc}"><svg width="18" height="18" viewBox="0 0 24 24" class="icon grid-icon-fill align-middle"><use xlink:href="#download"></use></svg>&nbsp;{$item.Doc_name}</a>';
                    {if $item.Status == 'unpaid'}
                        html_item += '<a class="bwt-delete-file d-block mt-2" data-item="{$item.ID}" href="javascript://"><svg width="18" height="18" viewBox="0 0 24 24" class="icon grid-icon-fill align-middle"><use xlink:href="#remove"></use></svg>&nbsp;{$lang.delete}</a>';
                    {/if}
                {/if}
                {if $item.Status == 'paid' && empty($item.Doc)}
                    html_item += '{$lang.not_available}';
                {/if}
                html_item += '</div>';
            {else}
                html_item = '<div data-caption="{$lang.bwt_doc_file}">{$lang.not_available}</div>';
            {/if}
            transactions[{$key}] = [];
            transactions[{$key}]['ID'] = '{$item.ID}';
            transactions[{$key}]['Txn_ID'] = '{$item.Txn_ID}';
            transactions[{$key}]['Gateway'] = '{$item.Gateway_key}';
            transactions[{$key}]['html_item'] = html_item;
        {/foreach}

        {literal}
        $(document).ready(function() {
            $('.transactions > div.header > div:eq(2)').after('<div style="width: 120px;">{/literal}{$lang.bwt_doc_file}{literal}</div>');
            for (var i = 0; i < transactions.length; i++) {
                var txnElID = '';

                if ($('#txn-id-' + transactions[i]['ID']).length > 0) {
                    txnElID = transactions[i]['ID'];
                } else if(transactions[i]['Txn_ID'] != '0' && transactions[i]['Txn_ID'] != '') {
                    txnElID = transactions[i]['Txn_ID'];
                }

                if (txnElID && transactions[i]['Gateway'] == 'bankWireTransfer') {
                    var tmpTxnID = $('#txn-id-' + txnElID).html();

                    let popupContentID = $('#txn_' + transactions[i]['ID']).length
                        ? transactions[i]['ID']
                        : transactions[i]['Txn_ID'];

                    $('#txn-id-' + txnElID).html('<a id="'+ txnElID +'" href="#" onClick="initFlModal(this, \'txn_'+ popupContentID +'\')" ref="nofollow" class="btw">'+ tmpTxnID +'</a>');
                }
                $('#txn-id-' + txnElID).parent().parent().after(transactions[i]['html_item']);
            }
        });

        function initFlModal(obj, element) {
            $(obj).flModal({
                width: 450,
                height: 'auto',
                source: '#' + element,
                click: false
            });
        }
        {/literal}
    </script>
    {foreach from=$transactions item='item'}
        {if $item.Gateway_key == 'bankWireTransfer' && !empty($item.Txn_ID)}
            <div class="hide" id="txn_{$item.ID}">
                <div class="caption_padding">{$lang.bwt_view_details}</div>
                <div class="list-table">
                    <div class="row">
                        <div class="no-flex default">
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.item}</div>
                                <div class="value">{$item.Txn_ID}</div>
                            </div>
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.price}</div>
                                <div class="value">{if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{$item.Total}{if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</div>
                            </div>
                            <div class="table-cell clearfix small">
                                <div class="name">{$lang.status}</div>
                                <div class="value">{$lang[$item.Status]}</div>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="table-cell clearfix small">
                            <div class="value">
                                <div class="table-cell clearfix small">
                                    <div class="name"><b>{$lang.bwt_payment_details}:</b></div>
                                    <div class="sLine"></div>
                                </div>
                                {if $item.Dealer_ID && $item.payment_details}
                                    {$item.payment_details.content}
                                {else}
                                    {$payment_details.content}
                                {/if}
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        {/if}
    {/foreach}

    {include file=$smarty.const.RL_PLUGINS|cat:'bankWireTransfer/upload.tpl'}
{/if}
