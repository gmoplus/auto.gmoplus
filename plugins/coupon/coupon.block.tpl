<!-- coupon code box for version 4.4 and more -->    
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='coupon' name=$lang.coupon_code}
    <div id="coupon_code"><input class="text w150" id="coupon_code_name" name="coupon_code" value="{$smarty.post.coupon_code}" type="text" maxlength="20" size="20" onkeydown="javascript:if(13==event.keyCode){literal}{{/literal}return false;{literal}}{/literal}" /> <input class="low" id="check_coupon" type="button" style="margin: 0 5px;" value="{$lang.apply}"></div>
    <div id="coupon_code_info"></div>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
<script type="text/javascript" class="fl-js-dynamic">
    var item_id = '{$item_id}';
    var service = '{$service}';
    {literal}    
        $(document).ready(function() {
            $('.plans li').click(function() {
                item_id = $(this).find('input[name=plan]:checked').val();
            });
            $('#check_coupon').click(function() {
                checkCouponCode($('#coupon_code_name').val(), item_id, service);
            });
            $('.plans li:not(.active)').click(function() {
                checkCouponCode(false, item_id, service, true); 
            });
            $('#coupon_code_name').keydown(function(event){
                if(event.keyCode == 13) {
                    checkCouponCode($('#coupon_code_name').val(), item_id, service); 
                }
            });
        });

        var cancelCouponCode = function() {
            checkCouponCode(false, item_id, service, true);
            $('#coupon_code').show();  
            $('#coupon_code_info').hide();
        }
        
        var checkCouponCode = function(code, item_id, service, cancel) {
            $('#check_coupon').val('{/literal}{$lang.loading}{literal}');
            $('#coupon_code_info').hide();
            var ccGateway = $('input[name="gateway"]:checked').val();

            var data = {
                mode: 'checkCouponCode', 
                code: code, 
                item_id: item_id, 
                service: service, 
                cancel: cancel, 
                lang: rlLang,
                gateway: ccGateway
            };

            $.getJSON(rlConfig['ajax_url'], data, function(response) {
                if (response) {
                    if (response.status == 'OK') {
                        if (response.data.content) {
                            $('#coupon_code').hide();
                            $('#coupon_code_info').show();
                        }
                        $('#coupon_code_info').html(response.data.content);

                        if (response.total <= 0 && response.needRedirect) {
                            var couponCheckoutBtn = $('#btn-checkout, #form-checkout input[type="submit"]');
                            couponCheckoutBtn.unbind('click');
                            $('#custom-form').html('');
                            couponCheckoutBtn.val('{/literal}{$lang.continue}{literal}');

                            couponCheckoutBtn.click(function() {
                                var completeData = {
                                    mode: 'completeCouponCode', 
                                    lang: rlLang,
                                    gateway: ccGateway
                                };

                                $.getJSON(rlConfig['ajax_url'], completeData, function(response) {
                                    if (response.status == 'OK') {
                                        window.location.href = response.url;
                                    } else {
                                        printMessage('error', response.message);
                                    }
                                });
                                return false;
                            });
                        } else {
                            $('input[name="gateway"]:checked').trigger('click');
                        }
                    } else {
                        printMessage('error', response.data.message);
                    }
                    $('#check_coupon').val('{/literal}{$lang.apply}{literal}');
                }
            });
        }
    {/literal}
</script>
<!-- coupon code end -->
