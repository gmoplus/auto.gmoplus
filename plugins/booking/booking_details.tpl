<!-- booking details -->

<!-- tabs -->
<ul class="tabs tabs-hash">{strip}
    <li class="active" id="tab_requests">
        <a href="#requests" data-target="requests">{$lang.booking_booking_requests}</a>
    </li>
    <li id="tab_binding">
        <a href="#binding" data-target="binding">{$lang.booking_binding_days}</a>
    </li>
    <li id="tab_configs">
        <a href="#configs" data-target="configs">{$lang.booking_module}</a>
    </li>
{/strip}</ul>
<!-- tabs end -->

<!-- requests tab -->
<div id="area_requests" class="tab_area content-padding">
    {if empty($requests)}
        <div class="text-message">{$lang.booking_no_requests}</div>
    {else}
        {include file=$bookingBlocksFolder|cat:'requests.tpl'}
    {/if}
</div>
<!-- requests tab end -->

<!-- binding checkin / checkout tab -->
<div id="area_binding" class="tab_area content-padding hide">
    {include file=$bookingBlocksFolder|cat:'binding_days.tpl'}
</div>
<!-- binding checkin / checkout tab end -->

<!-- configs tab -->
<div id="area_configs" class="tab_area content-padding hide">
    {include file=$bookingBlocksFolder|cat:'configs.tpl'}
</div>
<!-- configs tab end -->

<script class="fl-js-dynamic">
lang.save                     = '{$lang.save}';
lang.booking_no_requests      = '{$lang.booking_no_requests}';
lang.booking_remove_notice    = '{$lang.booking_remove_notice}';
lang.booking_min_config_desc  = '{$lang.booking_min_config_desc}';
lang.booking_max_config_error = '{$lang.booking_max_config_error}';

var bookingConfigs            = [];
bookingConfigs.listing_id  = '{if $smarty.get.id}{$smarty.get.id}{elseif $smarty.session.add_listing.listing_id}{$smarty.session.add_listing.listing_id}{/if}';

booking.saveBindingDaysHandler();
booking.saveConfigsHandler();
booking.requestRemoveHandler();
</script>

<!-- booking details end -->
