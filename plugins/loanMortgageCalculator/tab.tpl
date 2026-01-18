<!-- loan mortgage tab content -->

<div id="area_loanMortgage" class="tab_area content-padding hide">
    <div class="row">{strip}
        <div class="col-lg-6">
            <div class="fieldset divider"><header>{$lang.loanMortgage_loan_terms}</header></div>

            <div>
                <div class="submit-cell">
                    <div class="name" title="{$lang.loanMortgage_loan_amount}">{$lang.loanMortgage_loan_amount}</div>
                    <div class="field combo-field">
                        <input type="text" name="lm_loan_amount" class="wauto numeric" size="6" id="lm_loan_amount" value="{$lm_amount.0}" />
                        {if $curConv_code && $lm_amount.1 && $lm_amount.1 != $curConv_code && $lm_amount.1 != $curConv_sign}
                            <span title="{$lang.loanMortgage_switch}" id="lm_loan_cur_area" class="switcher">
                                <span id="lm_loan_cur_orig" style="font-weight: bold;">{$lm_amount.1}</span>
                                /
                                <span id="lm_loan_cur_conv" class="lm_opacity">{$curConv_rates[$curConv_code].Code}</span>
                            </span>
                        {else}
                            {$lm_amount.1}
                        {/if}
                    </div>
                </div>
                <div class="submit-cell">
                    <div class="name" title="{$lang.loanMortgage_loan_term}">{$lang.loanMortgage_loan_term}</div>
                    <div class="field combo-field">
                        <input maxlength="3" type="text" class="wauto numeric" size="3" name="lm_loan_term" id="lm_loan_term" value="{if $config.loanMortgage_loan_term > 0}{$config.loanMortgage_loan_term}{/if}" />
                        <span title="{$lang.loanMortgage_switch}" id="lm_loan_term_area" class="switcher">
                        <span id="lm_loan_term_year" {if $config.loanMortgage_loan_term_mode == 'years'}style="font-weight: bold;"{else}class="lm_opacity"{/if}>{$lang.loanMortgage_years}</span>/<span id="lm_loan_term_month" {if $config.loanMortgage_loan_term_mode == 'months'}style="font-weight: bold;"{else}class="lm_opacity"{/if}>{$lang.loanMortgage_months}</span>
                        </span>
                    </div>
                </div>
                <div class="submit-cell">
                    <div class="name" title="{$lang.loanMortgage_interest_rate}">{$lang.loanMortgage_interest_rate}</div>
                    <div class="field combo-field">
                        <input type="text" maxlength="4" class="wauto numeric" size="4" name="lm_loan_rate" id="lm_loan_rate" value="{if $config.loanMortgage_loan_rate > 0}{$config.loanMortgage_loan_rate}{/if}" /> %
                    </div>
                </div>
                <div class="submit-cell">
                    <div class="name" title="{$lang.loanMortgage_first_pmt_date}">{$lang.loanMortgage_first_pmt_date}</div>
                    <div class="field two-fields">
                        <select id="lm_loan_date_month"></select>
                        <select id="lm_loan_date_year"></select>
                    </div>
                </div>
                <div class="submit-cell buttons">
                    <div class="name"></div>
                    <div class="field two-fields">
                        <input onclick="loan_check();" title="{$lang.loanMortgage_calculate}" type="button" id="lm_loan_calculate" value="{$lang.loanMortgage_calculate}" />
                        <span onclick="loan_clear();" title="{$lang.loanMortgage_reset}" class="red margin">{$lang.loanMortgage_reset}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="fieldset divider"><header>{$lang.loanMortgage_payments}</header></div>
            <div id="lm_details_area" data-hint="{$lang.loanMortgage_start_message}">
                {$lang.loanMortgage_start_message}
            </div>
            <div class="hide mt-3 text-center" id="lm-print-cont">
                <a href="javascript://" id="lm_print_schedule">{$lang.loanMortgage_print} <span class="text-lowercase">{$lang.loanMortgage_amz_schedule}</span></a>
            </div>
        </div>
    {/strip}</div>
    
    <div id="lm_amortization" class="hide">
        <div class="fieldset divider"><header>{$lang.loanMortgage_amz_schedule}</header></div>
        <div id="lm_amortization_area"></div>
    </div>
</div>

{addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'loanMortgageCalculator/static/style-tab.css'}
{include file=$smarty.const.RL_PLUGINS|cat:'loanMortgageCalculator/js_data.tpl'}

<script class="fl-js-dynamic">
{literal}

$(function(){
    $('#area_loanMortgage input.numeric').numeric({negative: false});
});

{/literal}
</script>

<!-- loan mortgage tab content end -->
