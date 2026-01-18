<!-- booking requests -->

{if $isLogin}
    <div class="content-padding requests{if $bookingConfigs.Booking_type == 'time_range'} escort{/if}" id="area_booking">
        {if $requests || $request}
            {if $config.mod_rewrite}
                {assign var='require_id' value=$smarty.get.request_id}
            {else}
                {assign var='require_id' value=$smarty.get.id}
            {/if}

            {if !$require_id}
                {include file=$bookingBlocksFolder|cat:'requests.tpl'}
            {else}
                {include file=$bookingBlocksFolder|cat:'styles.tpl'}

                {if $bookingConfigs.Booking_type == 'time_range'}
                    {if $listing_escort}
                        {assign var='listing' value=$listing_escort}
                    {/if}

                    {include file=$bookingBlocksFolder|cat:'escort_data.tpl'}
                {else}
                    {include file=$bookingBlocksFolder|cat:'calendar.tpl' fieldsetEnable=true}
                {/if}

                {include file=$bookingBlocksFolder|cat:'js_script.tpl' qtipEnable=true}
                {include file=$bookingBlocksFolder|cat:'checkin_checkout.tpl'}

                {if $request.Status_request == 'process'}
                    {if $bookingConfigs.Booking_type == 'time_range'}
                        {assign var="change_button_phrase" value=$lang.booking_button_change_date}
                    {else}
                        {assign var="change_button_phrase" value=$lang.booking_button_change_period}
                    {/if}

                    <a id="change_period" class="button" title="{$change_button_phrase}" href="javascript:void(0)">{$change_button_phrase}</a>
                    <a id="cancel_changes" class="red margin cancel hide" href="javascript:void(0)">{$lang.cancel}</a>
                {/if}

                <div id="booking_details" class="row clearfix">
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div>
                            {include file='blocks/fieldset_header.tpl' name=$lang.booking_page_details}

                            {if $plugins.ref}
                                <div class="table-cell clearfix">
                                    <div class="name"><div><span>{$lang.booking_ref_number}</span></div></div>
                                    <div class="field">{if $request.ref_number}{$request.ref_number}{else}{$lang.not_available}{/if}</div>
                                </div>
                            {/if}

                            <div class="table-cell clearfix check-in {if $request.Status_request == 'process' && $bookingConfigs.Booking_type != 'time_range'}current_dates{/if}">
                                <div class="name">
                                    <div>
                                        <span>
                                            {if $bookingConfigs.Booking_type == 'time_range'}
                                                {$lang.booking_escort_date}
                                            {elseif $bookingConfigs.Booking_type == 'date_time_range'}
                                                {$lang.booking_checkin_auto}
                                            {else}
                                                {$lang.booking_checkin}
                                            {/if}
                                        </span>
                                    </div>
                                </div>
                                <div class="field start_date">
                                    <b>{$request.From|date_format:$smarty.const.RL_DATE_FORMAT}</b>

                                    {if ($request.booking_check_in || $request.Checkin) && $bookingConfigs.Booking_type != 'time_range'}
                                        - {if $bookingConfigs.Booking_type == 'date_time_range'}{$request.Checkin}{else}{$request.booking_check_in}{/if}
                                    {/if}
                                </div>
                            </div>

                            {if $bookingConfigs.Booking_type == 'time_range'}
                                <div class="table-cell clearfix">
                                    <div class="name"><div><span>{$lang.booking_escort_type}</span></div></div>
                                    <div class="field type">
                                        {if $listing_escort.escort_rates.Fields.escort_rates.value}
                                            {$listing_escort.escort_rates.Fields.escort_rates.value[$request.Checkin].title}
                                        {else}
                                            {$bookingRates[$request.Checkin].title}
                                        {/if}
                                    </div>
                                </div>

                                <div class="table-cell clearfix">
                                    <div class="name"><div><span>{$lang.booking_escort_time}</span></div></div>
                                    <div class="field time">{$request.Checkout}</div>
                                </div>

                                <div class="table-cell clearfix {if !$request.To}hide{/if}">
                                    <div class="name"><div><span>{$lang.booking_escort_duration}</span></div></div>
                                    <div class="field duration">
                                        {assign var="phrase_hours" value='booking_escort_duration_'|cat:$request.To}
                                        {$lang.$phrase_hours}
                                    </div>
                                </div>
                            {else}
                                <div class="table-cell clearfix check-out {if $request.Status_request == 'process' && $bookingConfigs.Booking_type != 'time_range'}current_dates{/if}">
                                    <div class="name">
                                        <div>
                                            <span>
                                                {if $bookingConfigs.Booking_type == 'date_time_range'}
                                                    {$lang.booking_checkout_auto}
                                                {else}
                                                    {$lang.booking_checkout}
                                                {/if}
                                            </span>
                                        </div>
                                    </div>
                                    <div class="field end_date">
                                        <b>{$request.To|date_format:$smarty.const.RL_DATE_FORMAT}</b>

                                        {if $request.booking_check_out || $request.Checkout}
                                            - {if $bookingConfigs.Booking_type == 'date_time_range'}{$request.Checkout}{else}{$request.booking_check_out}{/if}
                                        {/if}
                                    </div>
                                </div>
                            {/if}

                            {if $bookingConfigs.Booking_type === 'date_range'}
                                <div class="table-cell clearfix">
                                    <div class="name"><div><span>{$lang.booking_nights}</span></div></div>
                                    <div class="field nights-count">{$bookingData.nights}</div>
                                </div>
                            {/if}

                            <div class="table-cell clearfix">
                                <div class="name"><div><span>{$lang.status}</span></div></div>
                                <div class="field" id="owRes">{$request.Stat}</div>
                            </div>
                            <div class="table-cell clearfix">
                                <div class="name"><div><span>{$lang.total}</span></div></div>
                                <div class="field total_cost amount">
                                    {if $defPrice.currency}
                                        {assign var="booking_currency" value=$defPrice.currency}
                                    {else}
                                        {assign var="booking_currency" value=$config.system_currency}
                                    {/if}

                                    {if $config.system_currency_position == 'before'}
                                        {$booking_currency} {$request.Amount}
                                    {else}
                                        {$request.Amount} {$booking_currency}
                                    {/if}
                                </div>
                            </div>

                            {include file='blocks/fieldset_footer.tpl'}
                        </div>
                    </div>
                    <div class="col-md-6 col-sm-6 col-xs-12">
                        <div>
                            {include file='blocks/fieldset_header.tpl' name=$lang.booking_client_details}

                            {foreach from=$request.fields item='field'}
                                {if $field.Type != 'textarea'}
                                    <div class="table-cell clearfix">
                                        <div class="name"><div><span>{$field.name}</span></div></div>
                                        <div class="field">{$field.value}</div>
                                    </div>
                                {/if}
                            {/foreach}

                            {include file='blocks/fieldset_footer.tpl'}
                        </div>
                    </div>
                </div>

                {foreach from=$request.fields item='field'}
                    {if $field.Type == 'textarea' && $field.value}
                        <div class="table-cell clearfix wide-field">
                            <div class="name {$field.Key}"><div><span>{$field.name}</span></div>
                            </div>
                            <div class="field">{$field.value}</div>
                        </div>
                    {/if}
                {/foreach}

                {if $request.Status_request == 'process'}
                    <div id="owner_actions">
                        {include file='blocks/fieldset_header.tpl' name=$lang.booking_requests_comments}

                        <div id="refuse_answer">
                            <textarea name="textarea_answer" rows="5" cols="" id="textarea_answer"></textarea>
                            <div>
                                <input type="button" id="accept_btn" class="button" value="{$lang.booking_accept}" />
                                <a id="refuse_btn" class="red margin cancel" href="javascript:void(0)">{$lang.booking_refuse}</a>
                            <div>
                        </div>

                        {include file='blocks/fieldset_footer.tpl'}
                    </div>
                {/if}

                <script class="fl-js-dynamic">{literal}
                $(function() {
                    $('#textarea_answer').textareaCount({
                        'maxCharacterSize': 255,
                        'warningNumber'   : 20
                    });

                    $('#accept_btn').click(function(){
                        booking.bookingRequestHandler('accept', '{/literal}{$require_id}{literal}')
                    });

                    $('#refuse_btn').click(function(){
                        booking.bookingRequestHandler('refuse', '{/literal}{$require_id}{literal}')
                    });

                    if (typeof $.convertPrice == 'function') {
                        $('#booking_details .total_cost').convertPrice({showCents: true});
                    }
                });{/literal}

                {if $request.Status_request == 'process'}
                    flynax.htmlEditor(['textarea_answer']);
                {/if}
                </script>
            {/if}
        {else}
            <div class="text-notice">{$lang.booking_no_requests}</div>
        {/if}
    </div>

    <script class="fl-js-dynamic">
    lang.booking_accept               = '{$lang.booking_accept}';
    lang.booking_refuse               = '{$lang.booking_refuse}';
    lang.booking_refused              = '{$lang.booking_refused}';
    lang.booking_accepted             = '{$lang.booking_accepted}';
    lang.booking_no_requests          = '{$lang.booking_no_requests}';
    lang.booking_remove_notice        = '{$lang.booking_remove_notice}';
    lang.save                         = '{$lang.save}';
    lang.booking_button_change_period = '{if $bookingConfigs.Booking_type == 'time_range'}{$lang.booking_button_change_date}{else}{$lang.booking_button_change_period}{/if}';

    /* booking special configs (if it isn't exist) */
    if (typeof bookingConfigs == 'undefined') {literal}{{/literal}
        var bookingConfigs = [];
    {literal}}{/literal}

    bookingConfigs['request_id'] = {if $request.Req_ID && $request.Status_request == 'process'}{$request.Req_ID}{else}0{/if};
    var tmpBookingConfigs = [];

    booking.requestRemoveHandler();
    booking.changePeriodHandler();
    booking.revertRequestChanges();
    </script>
{/if}

<!-- booking requests end -->
