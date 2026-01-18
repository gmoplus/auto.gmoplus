<!-- binding days -->

<div id="bindings_obj">
    {if $booking_configs.Booking_type == 'time_range'}
        {$lang.booking_binding_days_unavailable}
    {else}
        <form id="binding_days_form" method="post">
            <div class="list-table content-padding" id="rate_ranges_table">
                <div class="header">
                    <div style="width: 200px;">{$lang.booking_checkin}</div>
                    <div>{$lang.booking_checkout}</div>
                    <div style="width: 80px;">{$lang.actions}</div>
                </div>

                <div class="row" id="bind_days_checkbox">
                    <div class="checkbox" data-caption="{$lang.booking_checkin}">
                        {section name=bookingWeekdays start=1 loop=8}
                            {assign var='i' value=$smarty.section.bookingWeekdays.index}

                            <div style="padding: 0 0 5px;">
                                <label>
                                    <input class="inline"
                                           type="checkbox"
                                           {if in_array($bookingWeekdays.en.$i, ','|explode:$binding_days.Checkin)
                                                || !$binding_days.Checkin}checked="checked"{/if}
                                           name="in"
                                           value="{$bookingWeekdays.en.$i}"
                                    /> {$bookingWeekdays.full.$i}
                                </label>
                            </div>
                        {/section}
                    </div>
                    <div data-caption="{$lang.booking_checkout}">
                        {section name=bookingWeekdays start=1 loop=8}
                            {assign var='i' value=$smarty.section.bookingWeekdays.index}
                            <div style="padding: 0 0 5px;">
                                <label>
                                    <input class="inline"
                                           type="checkbox"
                                           {if in_array($bookingWeekdays.en.$i, ','|explode:$binding_days.Checkout)
                                                || !$binding_days.Checkout}checked="checked"{/if}
                                           name="out"
                                           value="{$bookingWeekdays.en.$i}"
                                    /> {$bookingWeekdays.full.$i}
                                </label>
                            </div>
                        {/section}
                    </div>
                    <div data-caption="{$lang.actions}">
                        <input type="button" class="save_binding_days" value="{$lang.save}">
                    </div>
                </div>
            </div>
        </form>
    {/if}
</div>

<!-- binding days end -->
