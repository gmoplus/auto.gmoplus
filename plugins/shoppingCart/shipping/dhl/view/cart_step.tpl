<!-- DHL settings -->

<input type="hidden" name="items[{$item_id}][dhl][service_single]" class="service-single-dhl item-fixed-single" data-item="{$item_id}" value="{$item_data.services.total}">
<div class="submit-cell">
    <div class="name"></div>
    <div class="field single-field">
        <a href="javascript://" item="{$item_id}" method="dhl" class="calculate-rate button low" >{$lang.shc_get_quote}</a>
    </div>
</div>

<!-- end DHL settings -->
