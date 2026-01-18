{if $config.shc_escrow}
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='shc_escrow' name=$lang.shc_escrow_item}
    <div class="row mb-2">
        <div class="col-12 escrow-container">
            {if $orderInfo.Escrow_status == 'pending'}
                <a href="javascript://" class="button low confirm-order">{$lang.shc_order_confirm}</a>
                <span>{$lang.or}</span>
                <a href="javascript://" class="button low cancel-order">{$lang.shc_cancel_order}</a>
            {elseif $orderInfo.Escrow_status == 'confirmed'}
                <span>{$lang.shc_escrow_confirmed}</span>
            {elseif $orderInfo.Escrow_status == 'canceled'}
                <span>{$lang.shc_escrow_canceled}</span>
            {/if}
        </div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.shc_escrow_expiration}</span></div></div>
        <div class="value">{$orderInfo.Escrow_date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
    </div>
    <div class="table-cell">
        <div class="name"><div><span>{$lang.shc_deal_id}</span></div></div>
        <div class="value">{$orderInfo.Deal_ID}</div>
    </div>
    <div id="cancel_order_form" class="hide">
        <div id="cancel_reason" class="submit-cell w-100">
            <label>{$lang.shc_cancel_reason}</label>
            <div class="field single-field">
                <textarea rows="3" id="cancel_reason_field"></textarea>
            </div>
        </div>
    </div>
    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
<script class="fl-js-dynamic">
    {literal}
    let shcEscrowOrderID = '{/literal}{$orderInfo.ID}{literal}';
    $(document).ready(function(){
        $('.confirm-order').click(function() {
            $('.confirm-order').flModal({
                caption: '',
                content: '{/literal}{$lang.shc_do_you_want_confirm_order}{literal}',
                prompt: 'escrowConfirmOrder()',
                width: 'auto',
                height: 'auto',
                click: false
            });
        });


        $('.cancel-order').click(function() {
            var el = '#cancel_order_form';

            flUtil.loadScript([
                rlConfig['tpl_base'] + 'components/popup/_popup.js',
            ], function(){
                $('.escrow-container').popup({
                    click: false,
                    scroll: true,
                    closeOnOutsideClick: false,
                    content: $(el).html(),
                    caption: '{/literal}{$lang.shc_do_you_want_cancel_order}{literal}',
                    navigation: {
                        okButton: {
                            text: '{/literal}{$lang.shc_ok}{literal}',
                            onClick: function(popup){
                                let reason = popup.$interface.find('#cancel_reason_field').val();
                                escrowCancelOrder(reason);
                                popup.close();
                            }
                        },
                        cancelButton: {
                            text: lang['cancel'],
                            class: 'cancel'
                        }
                    }
                });
            });
        });
    });

    let escrowConfirmOrder = function() {
        let data = {
            mode: 'shoppingCartConfirmOrder',
            item: shcEscrowOrderID
        };
        flUtil.ajax(data, function(response) {
            if (response.status === 'OK') {
                buildResponseField(response.text);
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    let escrowCancelOrder = function(reason) {
        let data = {
            mode: 'shoppingCartCancelOrder',
            item: shcEscrowOrderID,
            reason: reason
        };
        flUtil.ajax(data, function(response) {
            if (response.status === 'OK') {
                buildResponseField(response.text);
                printMessage('notice', response.message);
            } else {
                printMessage('error', response.message);
            }
        });
    }

    let buildResponseField = function(status) {
        let elEC = $('.escrow-container');
        elEC.addClass('table-cell');
        elEC.html('');
        elEC.prepend($('<div class="value" />').html(status));
        elEC.prepend($('<div class="name" />').html('{/literal}{$lang.status}{literal}'));
    }
    {/literal}
</script>
{/if}