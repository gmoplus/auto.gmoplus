<script>
var lm_configs = new Array();
lm_configs['mode'] = false;
lm_configs['in_box'] = {if $mode == 'box'}true{else}false{/if};
lm_configs['print_page_url'] = "{pageUrl key='loanMortgage_print'}";
lm_configs['listing_id'] = {if $listing_data.ID}{$listing_data.ID}{else}false{/if};
lm_configs['show_cents'] = {$config.show_cents};
lm_configs['price_delimiter'] = "{$config.price_delimiter}";
lm_configs['cents_separator'] = "{$config.price_separator}";
lm_configs['currency'] = '{$lm_amount.1}';
lm_configs['lang_code'] = '{if $smarty.const.RL_LANG_CODE == 'en'}en-GB{else}{$smarty.const.RL_LANG_CODE|lower}{/if}';
lm_configs['loan_term_mode'] = '{if $config.loanMortgage_loan_term_mode == 'years'}year{else}month{/if}';
lm_configs['loan_currency_mode'] = 'original';
lm_configs['loan_orig_amount'] = {if $lm_amount.0}{$lm_amount.0}{else}0{/if};
lm_configs['loan_orig_currency'] = '{$lm_amount.1}';

var lm_phrases = new Array();
lm_phrases['loan_amount'] = '{$lang.loanMortgage_loan_amount}';
lm_phrases['num_payments'] = '{$lang.loanMortgage_num_payments}';
lm_phrases['monthly_payment'] = '{$lang.loanMortgage_monthly_payment}';
lm_phrases['total_paid'] = '{$lang.loanMortgage_total_paid}';
lm_phrases['total_interest'] = '{$lang.loanMortgage_total_interest}';
lm_phrases['payoff_date'] = '{$lang.loanMortgage_payoff_date}';
lm_phrases['pmt_date'] = '{$lang.loanMortgage_pmt_date}';
lm_phrases['amount'] = '{$lang.loanMortgage_amount}';
lm_phrases['interest'] = '{$lang.loanMortgage_interest}';
lm_phrases['principal'] = '{$lang.loanMortgage_principal}';
lm_phrases['balance'] = '{$lang.loanMortgage_balance}';
lm_phrases['error_amount'] = '{$lang.loanMortgage_error_amount}';
lm_phrases['error_term'] = '{$lang.loanMortgage_error_term}';
lm_phrases['error_rate'] = '{$lang.loanMortgage_error_rate}';
lm_phrases['amz_schedule'] = '{$lang.loanMortgage_amz_schedule}';
lm_phrases['reset'] = '{$lang.loanMortgage_reset}';
</script>

{addJS file=$smarty.const.RL_PLUGINS_URL|cat:'loanMortgageCalculator/static/loan_calc.js'}

<script class="fl-js-dynamic">
{literal}
$(document).ready(function(){
    $('.lm_opacity').animate({opacity: 0.4});
    $('#lm_loan_amount').val(lm_configs['loan_orig_amount']);
    
    // Months/years switcher
    $('#lm_loan_term_area').click(function(){
        if ( lm_configs['loan_term_mode'] == 'year' ) {
            /* switch to month */
            $('#lm_loan_term_year').css('font-weight', 'normal').animate({opacity: 0.4});
            $('#lm_loan_term_month').css('font-weight', 'bold').animate({opacity: 1});
            
            lm_configs['loan_term_mode'] = 'month';
        }
        else {
            /* switch to year */
            $('#lm_loan_term_month').css('font-weight', 'normal').animate({opacity: 0.4});
            $('#lm_loan_term_year').css('font-weight', 'bold').animate({opacity: 1});
            
            lm_configs['loan_term_mode'] = 'year';
        }
        
        if ( lm_configs['mode'] ) {
            loan_check();
        }
    });
    
    // Currency switcher
    $('#lm_loan_cur_area').click(function(){
        if (lm_configs['loan_currency_mode'] == 'original') {
            // Switch to month
            $('#lm_loan_cur_orig').css('font-weight', 'normal').animate({opacity: 0.4});
            $('#lm_loan_cur_conv').css('font-weight', 'bold').animate({opacity: 1});
            
            var price = $('#lm_loan_amount').val() / currencyConverter.inRange(lm_configs['currency']) * currencyConverter.rates[currencyConverter.config['currency']][0];
            price = currencyConverter.encodePrice(price, true, true);
            $('#lm_loan_amount').val(price);
            
            lm_configs['loan_currency_mode'] = 'converted';
        } else {
            /* switch to year */
            $('#lm_loan_cur_conv').css('font-weight', 'normal').animate({opacity: 0.4});
            $('#lm_loan_cur_orig').css('font-weight', 'bold').animate({opacity: 1});
            
            $('#lm_loan_amount').val(lm_configs['loan_orig_amount']);
            lm_configs['currency'] = lm_configs['loan_orig_currency'];
            
            lm_configs['loan_currency_mode'] = 'original';
        }
        
        if (lm_configs['mode']) {
            loan_check();
        }
    });
    
    loan_build_payment_date();

    var print = function(){
        if (loan_check(true)) {
            var url = lm_configs['print_page_url'];

            var loanamt = $('#lm_loan_amount').val();
            var term = $('#lm_loan_term').val();
            var rate = $('#lm_loan_rate').val();
            var month = $('#lm_loan_date_month option:selected').text();
            var year = $('#lm_loan_date_year option:selected').text();

            url += rlConfig['mod_rewrite'] ? '?' : '&';
            url += 'id='+lm_configs['listing_id']+'&';
            url += 'amount='+loanamt+'&';
            url += 'currency='+lm_configs['currency']+'&';
            url += 'term='+term+'&';
            url += 'term_mode='+lm_configs['loan_term_mode']+'&';
            url += 'rate='+rate+'&';
            url += 'mode='+lm_configs['loan_currency_mode']+'&';
            url += 'date_month='+month+'&';
            url += 'date_month_number='+$('#lm_loan_date_month').val()+'&';
            url += 'date_year='+year;

            window.open(url, '_blank');
        }
        
        return false;
    }
    
    $('#lm_print_schedule').on('click', function(){
        print();
    });
});
{/literal}
</script>
