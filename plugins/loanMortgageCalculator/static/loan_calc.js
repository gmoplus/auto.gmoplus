
/******************************************************************************
 *
 *	PROJECT: Flynax Classifieds Software
 *	VERSION: 4.9.1
 *	LISENSE: FL0F971OQTZ9 - https://www.flynax.com/flynax-software-eula.html
 *	PRODUCT: Classified Ads Script
 *	DOMAIN : gmowin.com
 *	FILE   : LOAN_CALC.JS
 *
 *	This script is a commercial software and any kind of using it must be
 *	coordinate with Flynax Owners Team and be agree to Flynax License Agreement
 *
 *	This block may not be removed from this file or any other files with out
 *	permission of Flynax respective owners.
 *
 *	Copyrights Flynax Classifieds Software | 2022
 *	https://www.flynax.com
 *
 ******************************************************************************/

function loan_check(validate){
    var errors       = new Array();
    var error_fields = new Array();

    // Validate form
    ['amount', 'term', 'rate'].forEach(function(name){
        var id     = '#lm_loan_' + name; 
        var $field = $(id);
        var value  = parseInt($field.val());

        if (!value) {
            errors.push(lm_phrases['error_' + name]);
            error_fields.push(id)
        }
    });

    if (errors.length > 0) {
        printMessage('error', errors, error_fields);
        return false;
    } else {
        if (!validate) {
            loan_show(
                $('#lm_loan_term').val(),
                $('#lm_loan_amount').val(),
                lm_configs['loan_term_mode'],
                $('#lm_loan_rate').val(),
                $('#lm_loan_date_month').val(),
                $('#lm_loan_date_year').val()
            );
        } else {
            return true;
        }
    }
}

var lm_encode_price = function(price, show_currency){
    // round price
    price = price < 0 ? 0 : price;
    price = Math.floor(price * 100) / 100;
    price = price.toFixed(lm_configs['show_cents'] ? 2 : 0);

    // replace cents separator
    price = price.replace('\.', lm_configs['cents_separator']);

    // add millesimal delimiter
    price = price.replace(/(\d)(?=(\d\d\d)+(?!\d))/g, '$1' + lm_configs['price_delimiter']);

    // add currency
    if (show_currency !== false) {
        if (lm_configs['currency'].length == 1) {
            price = lm_configs['currency'] + ' ' + price;
        } else {
            price = price + ' ' + lm_configs['currency'];
        }
    }

    return price;
}

function loan_clear(){
    $('#lm_loan_term').val('');
    $('#lm_loan_rate').val('');
    
    $('#lm_details_area').html($('#lm_details_area').data('hint'));
    $('#lm_amortization').slideUp();
    $('#lm-print-cont').addClass('hide');
    
    loan_build_payment_date();

    $('div.loan-box .lm-payment-cont').hide();
}

function loan_build_payment_date(){
    var i18n = typeof $.datepicker.regional[lm_configs['lang_code']] == 'undefined'
    ? $.datepicker.regional[""]
    : $.datepicker.regional[lm_configs['lang_code']];

    var $month = $('#lm_loan_date_month');
    var $years = $('#lm_loan_date_year');

    var date = new Date();
    
    /* build months */
    var cur_month = date.getMonth();

    $month.empty();
    
    for (var i = 0; i < 12; i++) {
        $month.append(
            $('<option>')
                .val(i + 1)
                .text(i18n.monthNamesShort[i])
                .attr('selected', !!(i == cur_month))
        );
    }

    /* build years */
    var cur_year = date.getFullYear();
    
    $years.empty();

    for (var i = cur_year - 10; i < cur_year + 50; i++) {
        $years.append(
            $('<option>')
                .val(i)
                .text(i)
                .attr('selected', !!(i == cur_year))
        );
    }
}

function lm_increase_month(start_year, start_month, months){
    var date = new Date(start_year, start_month-1, 1);
    date.setMonth(date.getMonth() + months);

    return date;
}

function loan_show(loan_term, loan_amount, term_unit, loan_rate, date_month, date_year){
    lm_configs['mode'] = true;

    var i18n = typeof $.datepicker.regional[lm_configs['lang_code']] == 'undefined'
    ? $.datepicker.regional[""]
    : $.datepicker.regional[lm_configs['lang_code']];

    var date_val   = parseInt(loan_term);
    var amount     = parseFloat(loan_amount);
    var numpay     = term_unit == 'year' ? date_val * 12 : date_val;
    var rate       = parseFloat(loan_rate);
    var date_month = parseInt(date_month);
    var date_year  = parseInt(date_year);

    // Currency converter plugin mode
    if (lm_configs['loan_currency_mode'] == 'converted') {
        lm_configs['currency'] = currencyConverter.rates[currencyConverter.config['currency']][1][0];
    }

    if (term_unit == 'year') {
        var new_year = date_year + date_val;

        if (date_month == 1) {
            var month_index = 11;
            new_year--;
        } else {
            var month_index = date_month - 2;
        }

        var date_off = i18n.monthNamesShort[month_index] +', '+new_year;
    } else {
        var new_date   = lm_increase_month(date_year, date_month, date_val);
        var year_index = new_date.getMonth();
        var date_off   = i18n.monthNamesShort[year_index] +', '+new_date.getFullYear();
    }

    rate = rate / 100;
    var monthly  = rate / 12;
    var payment  = ( (amount * monthly) / (1 - Math.pow( (1 + monthly), -numpay) ) );
    var total    = payment * numpay;
    var interest = total - amount;

    var summary_table = [
        [
            lm_phrases['loan_amount'],
            lm_phrases['num_payments'],
            lm_phrases['monthly_payment'],
            lm_phrases['total_paid'],
            lm_phrases['total_interest'],
            lm_phrases['payoff_date']
        ],
        [
            lm_encode_price(amount),
            numpay,
            lm_encode_price(payment),
            lm_encode_price(total),
            lm_encode_price(interest),
            date_off
        ]
    ];
    var header_table = [
        lm_phrases['pmt_date'],
        lm_phrases['amount'],
        lm_phrases['interest'],
        lm_phrases['principal'],
        lm_phrases['balance']
    ];
    var output = '';

    for (var i = 0; i < summary_table[0].length; i++) {
        if (lm_configs['in_box']) {
            output += '<div> \
                <div class="name">'+ summary_table[0][i] +'</div> \
                <div class="value">'+ summary_table[1][i] +'</div> \
            </div>';
        } else {
            output += '<div class="table-cell"> \
                <div class="name"><div><span>'+ summary_table[0][i] +'</span></div></div> \
                <div class="value">'+ summary_table[1][i] +'</div> \
            </div>';
        }
    }

    var row_tpl = '<div class="row"> \
        <div class="align-center" data-caption="'+header_table[0]+'"><b>{row_value_1}</b></div> \
        <div class="ralign" data-caption="'+header_table[1]+'">{row_value_2}</div> \
        <div class="ralign" data-caption="'+header_table[2]+'">{row_value_3}</div> \
        <div class="ralign" data-caption="'+header_table[3]+'">{row_value_4}</div> \
        <div class="ralign" data-caption="'+header_table[4]+'">{row_value_5}</div> \
    </div>';

    $('#lm_details_area').html(output);
    if (lm_configs['in_box']) {
        $('.lm-payment-cont').show();
    } else {
        $('#lm_show_amortization').fadeIn();
        $('#lm-print-cont').removeClass('hide');
    }

    var detail = '<div class="list-table"> \
        <div class="header"> \
            <div class="align-center" style="width: 24%;">'+ header_table[0] +'</div> \
            <div class="ralign" style="width: 19%;">'+ header_table[1] +'</div> \
            <div class="ralign" style="width: 19%;">'+ header_table[2] +'</div> \
            <div class="ralign" style="width: 19%;">'+ header_table[3] +'</div> \
            <div class="ralign" style="width: 19%;">'+ header_table[4] +'</div> \
        </div>';

    detail += row_tpl
        .replace('{row_value_1}', '-')
        .replace('{row_value_2}', '-')
        .replace('{row_value_3}', '-')
        .replace('{row_value_4}', '-')
        .replace('{row_value_5}', lm_encode_price(amount));

    newPrincipal = amount;

    var i = j = 1;
    var outInterest = 0;
    var outReduction = 0;
    var point = 12;

    /* year mode */
    if (lm_configs['loan_term_mode'] == 'year') {
        point = 13 - date_month;
    }

    while (i <= numpay) {
        newInterest  = monthly * newPrincipal;
        reduction    = payment - newInterest;
        newPrincipal = newPrincipal - reduction;
        
        outInterest  += newInterest;
        outReduction += reduction;
        
        if (lm_configs['loan_term_mode'] == 'year') {
            if (i % point == 0 || i == numpay) {
                point += 12;
                
                var it_date = lm_increase_month(date_year, date_month, i-1);
                var pmt_date = i18n.monthNamesShort[it_date.getMonth()] +', '+it_date.getFullYear();
                
                detail += row_tpl
                    .replace('{row_value_1}', pmt_date)
                    .replace('{row_value_2}', lm_encode_price(outInterest + outReduction, false))
                    .replace('{row_value_3}', lm_encode_price(outInterest, false))
                    .replace('{row_value_4}', lm_encode_price(outReduction, false))
                    .replace('{row_value_5}', lm_encode_price(newPrincipal, false));

                outInterest = outReduction = 0;
                j++;
            }
        } else {
            var it_date = lm_increase_month(date_year, date_month, i-1);
            var pmt_date = i18n.monthNamesShort[it_date.getMonth()] +', '+it_date.getFullYear();

            detail += row_tpl
                .replace('{row_value_1}', pmt_date)
                .replace('{row_value_2}', lm_encode_price(payment, false))
                .replace('{row_value_3}', lm_encode_price(newInterest, false))
                .replace('{row_value_4}', lm_encode_price(reduction, false))
                .replace('{row_value_5}', lm_encode_price(newPrincipal, false));
        }

        i++;
    }

    detail += '</div>';
    
    $('#lm_amortization_area').html(detail);
    $('#lm_amortization').slideDown();
}
