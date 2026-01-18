<!-- event_rates.tpl -->
{assign var='df_source' value='event_rates'|df}
{assign var='event_rates' value=$smarty.post.f.event_rates}
{assign var='df_currency' value='currency'|df}
{assign var='esc_val' value='*cust0m*'}

<div id="event-rates-master-container" >
    <div class="event-rates-field-container">
        {foreach from=$event_rates item='profile_rate' key='index'}

        {assign var='custom_rate' value=false}
        {if $profile_rate.rate == $esc_val}
            {assign var='custom_rate' value=true}
        {/if}

        <div class="submit-cell d-flex">
            
            <div class="name"></div>
            <div id="sf_field_event_rates_{$index}" class="event field d-flex align-items-center">{strip}
                <input type="hidden" value="{$profile_rate.id}" name="f[event_rates][{$index}][id]" />
                <div class="event-div-rates mr-2">
                    <select name="f[event_rates][{$index}][rate]" 
                            class="event-select-rates{if $custom_rate} hide{/if}" 
                            index="{$index}">
                        <option value="-1">{$lang.event_ticket_type}</option>

                        {foreach from=$df_source item='erate'}
                            <option {if $erate.Key == $profile_rate.rate}selected {/if} 
                                value="{$erate.Key}">
                                    {if $erate.name}
                                        {$erate.name}
                                    {else}
                                        {$lang[$erate.pName]}
                                    {/if}
                            </option>
                        {/foreach}

                        <option {if $custom_rate}selected{/if} value="{$esc_val}">{$lang.event_custom_rate}</option>
                    </select>

                    <input type="text"  
                            value="{$profile_rate.custom_rate}" 
                            name="f[event_rates][{$index}][custom_rate]"
                            class="event-input-custom-rate {if !$custom_rate}hide{/if}" />

                    <img class="remove{if !$custom_rate} hide{/if}" 
                            id="reset-rate-{$index}" src="{$rlTplBase}img/blank.gif" />
                </div>

                <input type="text" name="f[event_rates][{$index}][price]" 
                        value="{$profile_rate.price}"
                        class="event-input-price numeric mr-2"
                        size="14"
                        maxlength="14" />

                <select name="f[event_rates][{$index}][currency]" class="event-select-currency mr-2">
                    {foreach from=$df_currency item='currency'}
                        <option {if $currency.Key == $profile_rate.currency}
                                    selected
                                {/if} value="{$currency.Key}">{$currency.name}</option>
                    {/foreach}
                </select>

                <img class="remove" id="remove-rate-{$index}" data-index="{$profile_rate.id}" src="{$rlTplBase}img/blank.gif" />
            {/strip}
            </div>
        </div>
        {/foreach}
    </div>

    <div class="event-rates-add-field-container">
        <div class="submit-cell buttons">
            <div class="name"></div>
            <div id="sf_field_add_{$field.Key}" class="field">
                <a href="javascript:eventRates.create()" rel="nofollow">{$lang.add_event_rate}</a>
            </div>
        </div>
    </div>
</div>

<script class="fl-js-dynamic">
var event_rates_count = {if $event_rates}parseInt('{$event_rates|@count}'){else}0{/if};
var df_source = '<option value="-1">{$lang.event_ticket_type}</option>';
var df_currency = '';

{foreach from=$df_source item='js_source'}
    df_source += '{strip}
        <option value="{$js_source.Key}">
            {if $js_source.name}
                {$js_source.name}
            {else}
                {$lang[$js_source.pName]}
            {/if}</option>{/strip}';
{/foreach}
df_source += '<option value="{$esc_val}">{$lang.event_custom_rate}</option>';

{foreach from=$df_currency item='js_currency'}
    df_currency += '<option value="{$js_currency.Key}">{$js_currency.name}</option>';
{/foreach}

{literal}
    var eventRatesClass = function() {
        // desc
        this.onChange = function(index, selector) {
            if ($(selector).val() == '{/literal}{$esc_val}{literal}') {
                $(selector).addClass('hide')
                $('input[name="f[event_rates]['+ index +'][custom_rate]"]').removeClass('hide');
                $('img#reset-rate-'+ index).removeClass('hide');
            }
        }
        // desc
        this.onReset = function(index) {
            $('input[name="f[event_rates]['+ index +'][custom_rate]"]').addClass('hide');
            $('select[name="f[event_rates]['+ index +'][rate]"] option:eq(0)').attr('selected', true);
            $('select[name="f[event_rates]['+ index +'][rate]"]').removeClass('hide');
            $('img[id^="reset-rate-'+ index +'"]').addClass('hide');
        }
        // desc
        this.onRemove = function(index) {
            var id = $('#remove-rate-'+ index).data('index');
            if ( typeof(id) !== 'undefined' ) {
                $.getJSON(rlConfig['ajax_url'], { item: 'ev_deleteRate', id: id }, function(response) {});
            }

            $('div#sf_field_event_rates_'+ index)
            .closest('div.submit-cell')
            .fadeOut('fast', function() {
                $(this).remove()
            });
        }
        // desc
        this.create = function() {
            var index = event_rates_count++;

            $('<div/>', {'class':'submit-cell d-flex'}).append(
                $('<div/>', {'class':'name'})).append(
                $('<div/>', {'class':'event field d-flex align-items-center', 'id':'sf_field_event_rates_'+ index}).append(
                    $('<div/>', {'class':'event-div-rates mr-2'}).append(
                        $('<select/>', {
                            'class': 'event-select-rates',
                            'name' : 'f[event_rates]['+ index +'][rate]',
                            on: {
                                change: function() {
                                    eventRates.onChange(index, this);
                                }
                            }
                        }).append(df_source),
                        $('<input/>', {
                            'type' : 'text',
                            'class': 'event-input-custom-rate hide',
                            'name' : 'f[event_rates]['+ index +'][custom_rate]'
                        }),
                        $('<img/>', {
                            'class': 'remove hide',
                            'src'  : rlConfig['tpl_base'] + 'img/blank.gif',
                            'id'   : 'reset-rate-'+ index,
                            on: {
                                click: function() {
                                    eventRates.onReset(index);
                                }
                            }
                        })
                    ),
                    $('<input/>', {
                        'type' : 'text',
                        'name' : 'f[event_rates]['+ index +'][price]',
                        'class': 'event-input-price numeric mr-2',
                        'maxlength': '14',
                    }).prop('size', '14').numeric(),
                    $('<select/>', {
                        'class': 'event-select-currency mr-2',
                        'name' : 'f[event_rates]['+ index +'][currency]',
                    }).append(df_currency),
                    $('<img/>', {
                        'class': 'remove',
                        'src'  : rlConfig['tpl_base'] + 'img/blank.gif',
                        'id'   : 'remove-rate-'+ index,
                        on: {
                            click: function() {
                                eventRates.onRemove(index);
                            }
                        }
                    })
                )
            ).appendTo('div.event-rates-field-container');
        }
        // show or hide rates
        this.displayRates = function() {
            var mode = $('input[name="f[event_price_type]"]:checked').val();
            if (mode == '1') {
                $('#event-rates-master-container').hide();
            }
            else {

                if (!$('.event-rates-field-container > *').length) {
                    eventRates.create();
                }
                $('#event-rates-master-container').show();
            }
        }
    }
    var eventRates = new eventRatesClass();

    $('select.event-select-rates').change(function() {
        var index = parseInt($(this).attr('index'));
        eventRates.onChange(index, this);
    });

    $('img[id^="reset-rate-"]').click(function() {
        var index = parseInt($(this).attr('id').split('-')[2]);
        eventRates.onReset(index);
    });
    $('img[id^="remove-rate-"]').click(function() {
        var index = parseInt($(this).attr('id').split('-')[2]);
        eventRates.onRemove(index);
    });

    if ($('input[name="f[event_price_type]"]').length) {
        eventRates.displayRates();
        $('input[name="f[event_price_type]"]').change(function() {
            eventRates.displayRates();
        });
    }

{/literal}
</script>
<!-- event rates tpl end -->
