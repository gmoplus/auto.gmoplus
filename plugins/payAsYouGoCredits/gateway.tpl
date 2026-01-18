<!-- payAsYouGoCredits plugin -->

<div id="paygc_credits">
    <div class="my_paygc_credits">
        <label><input type="radio" name="gateway" value="payAsYouGoCredits" />{$lang.paygc_use_credits}</label>
        <div class="padding">
            <div>{$lang.paygc_available_credits}: <span><b>{$account_total_credits}</b></span></div>
            <div>{$lang.paygc_required_amount}:&nbsp;
                <div class="required_amount_sufficient" id="paygc_required_amount">{strip}
                    {$price_item}
                {/strip}</div>
            </div>
            <div id="paygc_not_sufficient" class="hide">
                {$lang.paygc_not_sufficient}
            </div>
        </div>
    </div>
</div>

<script class="fl-js-dynamic">
    var price_item = parseFloat({$price_item});
    var total_credits = parseFloat('{$account_total_credits}');
    var paygc_rate = parseFloat({if $config.payAsYouGoCredits_rate_type == 'auto'}{$config.payAsYouGoCredits_rate}{else}1{/if});

    {literal}
    $(document).ready(function() {
        $('#payment_gateways').append($('#paygc_credits').html());
        $('#paygc_credits').remove();

        if ( price_item > total_credits ) {
            $('#paygc_required_amount').removeClass('required_amount_sufficient').addClass('required_amount_not_sufficient');
            $('#paygc_not_sufficient').fadeIn('normal');
            $('div.my_paygc_credits').find('input[name="gateway"]').attr('disabled', 'disabled');
        }
        $('ul.plans>li, table.plans>tbody>tr').click(function() {
            handlePayAsYouGoCreditsPrice($(this).find('input[name=plan]').val());
        });
        if (!price_item) {
            if ($('ul.plans').length || $('table.plans').length) {
                var planID = $('ul.plans input[name=plan]:checked, table.plans input[name=plan]:checked').val();
            } else {
                var planID = $('input[name=plan]').val();
            }

            handlePayAsYouGoCreditsPrice(planID);
        }
    });

    var handlePayAsYouGoCreditsPrice = function(selected_plan_id) {
        if (selected_plan_id) {
            var plan = plans[selected_plan_id];
            var plan_price = parseFloat(plan['Price']);

            if(paygc_rate > 0 && paygc_rate != '') {
                plan_price = _round((plan_price / paygc_rate), 2);
            }
            $('#paygc_required_amount').html(plan_price);

            if (plan_price > total_credits || total_credits <= 0 ) {
                $('#paygc_required_amount').addClass('required_amount_not_sufficient');
                $('#paygc_not_sufficient').fadeIn('normal');
                $('div.my_paygc_credits').find('input[name="gateway"]').attr('disabled', 'disabled');
            } else {
                $('#paygc_not_sufficient').fadeOut('fast');
                $('#paygc_required_amount').addClass('required_amount_sufficient');
                $('div.my_paygc_credits').find('input[name="gateway"]').attr('disabled', false);
            }
        }
    }

    var _round = function(number, digits) {
        var multiple = Math.pow(10, digits);
        var rndedNum = Math.round(number * multiple) / multiple;
        return rndedNum;
    }

    {/literal}
</script>

<!-- end payAsYouGoCredits plugin -->
