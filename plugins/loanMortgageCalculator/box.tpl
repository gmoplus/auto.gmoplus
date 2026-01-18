<!-- loan mortgage box content -->

<div class="loan-box">
    <div class="fieldset divider"><header>{$lang.loanMortgage_loan_terms}</header></div>

    <div class="loan-table-cont">
        <div>
            <span>{$lang.loanMortgage_loan_amount}</span>
            <div class="field">
                <input type="text" name="lm_loan_amount" class="numeric" size="6" id="lm_loan_amount" value="{$lm_amount.0}" />
            </div>
            <div class="switcher">
                {if $curConv_code && $lm_amount.1 && $lm_amount.1 != $curConv_code && $lm_amount.1 != $curConv_sign}
                    <span title="{$lang.loanMortgage_switch}" id="lm_loan_cur_area" class="pointer">
                        <span id="lm_loan_cur_orig" style="font-weight: bold;">{$lm_amount.1}</span>
                        /
                        <span id="lm_loan_cur_conv" class="lm_opacity">{$curConv_rates[$curConv_code].Code}</span>
                    </span>
                {else}
                    {$lm_amount.1}
                {/if}
            </div>
        </div>

        <div>
            <span>{$lang.loanMortgage_loan_term}</span>
            <div class="field">
                <input maxlength="3" type="text" class="wauto numeric" size="3" name="lm_loan_term" id="lm_loan_term" value="{if $config.loanMortgage_loan_term > 0}{$config.loanMortgage_loan_term}{/if}" />
            </div>
            <div class="switcher">
                <span title="{$lang.loanMortgage_switch}" id="lm_loan_term_area" class="pointer">
                    <span id="lm_loan_term_year" {if $config.loanMortgage_loan_term_mode == 'years'}style="font-weight: bold;"{else}class="lm_opacity"{/if}>{$lang.loanMortgage_years}</span>
                    /
                    <span id="lm_loan_term_month" {if $config.loanMortgage_loan_term_mode == 'months'}style="font-weight: bold;"{else}class="lm_opacity"{/if}>{$lang.loanMortgage_months}</span>
                </span>
            </div>
        </div>

        <div>
            <span>{$lang.loanMortgage_interest_rate}</span>
            <div class="field">
                <input type="text" maxlength="4" class="wauto numeric" size="4" name="lm_loan_rate" id="lm_loan_rate" value="{if $config.loanMortgage_loan_rate > 0}{$config.loanMortgage_loan_rate}{/if}" /> %
            </div>
        </div>

        <div>
            <span>{$lang.loanMortgage_first_pmt_date}</span>
            <div class="field">{strip}
                <select id="lm_loan_date_month"></select>
                <select id="lm_loan_date_year"></select>
            {/strip}</div>
        </div>

        <div>
            <span></span>
            <div class="field">
                <input onclick="loan_check();" title="{$lang.loanMortgage_calculate}" type="button" id="lm_loan_calculate" value="{$lang.loanMortgage_calculate}" />
            </div>
        </div>
    </div>

    <div class="lm-payment-cont hide">
        <div class="fieldset divider"><header>{$lang.loanMortgage_payments}</header></div>
        <div id="lm_details_area" class="clearfix"></div>

        {$lang.loanMortgage_amz_schedule}: <span class="link" id="lm_show_schedule">{$lang.show}</span> / <span class="link" id="lm_print_schedule">{$lang.loanMortgage_print}</span>
    </div>

    <div class="hide" id="lm_amortization_dom"><div id="lm_amortization_area"></div></div>
</div>

{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'loanMortgageCalculator/static/style-box.css'}

{include file=$smarty.const.RL_PLUGINS|cat:'loanMortgageCalculator/js_data.tpl' mode='box'}

<script class="fl-js-dynamic">
{literal}

$(function(){
    $('#lm_show_schedule').flModal({
        width: 700,
        height: '80vh',
        caption: lm_phrases['amz_schedule'],
        source: '#lm_amortization_dom',
        scroll: false
    });

    $('div.loan-box').closest('section').find('h3').append('<span id="loan_reset_form"></span>');
    $('#loan_reset_form').attr('title', lm_phrases['reset']).click(function(){
        loan_clear();
    });

    $('.loan-box input.numeric').numeric({negative: false});
});

{/literal}
</script>

<!-- loan mortgage box content end -->
