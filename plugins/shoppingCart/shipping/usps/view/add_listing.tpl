<!-- usps setting -->

<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_usps_domestic_services}</div>
    <div class="field checkbox-field">
        {assign var='shcUspsServicesDomestic' value=$smarty.post.shipping.usps.domestic_services}
        <div class="row usps-services">
            {foreach from=$shc_usps_domestic_services item='service' key="key"}
                <span class="custom-input col-xs-12 col-lg-6 col-md-6 col-sm-4">
                    <label title="{$service.name}">
                        <input type="checkbox" {if $shcUspsServicesDomestic && $service.key|in_array:$shcUspsServicesDomestic}checked="checked"{/if} value="{$service.key}" name="shipping[usps][domestic_services][]" />
                        {$service.name}
                    </label>
                </span>
            {/foreach}
        </div>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_usps_international_services}</div>
    <div class="field checkbox-field">
        {assign var='shcUspsServicesInternational' value=$smarty.post.shipping.usps.international_services}
        <div class="row">
            {foreach from=$shc_usps_international_services item='service' key="key"}
                <span class="custom-input col-12">
                    <label title="{$service}">
                        <input type="checkbox" {if $shcUspsServicesInternational && $service|in_array:$shcUspsServicesInternational}checked="checked"{/if} value="{$service}" name="shipping[usps][international_services][]" />
                        {$service}
                    </label>
                </span>
            {/foreach}
        </div>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_usps_container}</div>
    <div class="field single-field">
        <select id="shc_usps_container" name="shipping[usps][container]">
        {foreach from=$shc_usps_containers item='container' key='key'}
            <option value="{$key}" {if $smarty.post.shipping.usps.container == $key}selected="selected"{/if}>{$container.name}</option>
        {/foreach}
        </select>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">
        {$lang.shc_usps_size}
    </div>
    <div class="field single-field">
        <select id="shc_usps_size" name="shipping[usps][size]">
            <option value="">{$lang.select}</option>
            <option value="REGULAR" {if $smarty.post.shipping.usps.size == 'REGULAR'}selected="selected"{/if}>{$lang.shc_usps_size_regular}</option>
            <option value="LARGE" {if $smarty.post.shipping.usps.size == 'LARGE'}selected="selected"{/if}>{$lang.shc_usps_size_large}</option>
        </select>
    </div>
</div>
<div class="submit-cell clearfix">
    <div class="name">{$lang.shc_usps_mail_type}</div>
    <div class="field single-field">
        <select id="shc_usps_mail_type" name="shipping[usps][mail_type]">
        {foreach from=$shc_usps_mail_types_domestic item='mail_type' key='key'}
            <option value="{$key}" {if $smarty.post.shipping.usps.mail_type == $key}selected="selected"{/if}>{$mail_type}</option>
        {/foreach}
        </select>
    </div>
</div>

<script class="fl-js-dynamic">
{literal}

$(document).ready(function(){
    $('.usps-services input[type="checkbox"]').click(function() {
        if($(this).val() == 'ALL') {
            shoppingCartSelectuspsService($(this).is(':checked'));
        }
    });
});

var shoppingCartSelectuspsService = function(is_all) {
    $('.usps-services input[type="checkbox"]').each(function() {
        if($(this).val() != 'ALL') {
            if(is_all) {
                $(this).parent().hide();
                $(this).prop('checked', false);
            } else {
                $(this).parent().show();
            }
        }
    });
}

{/literal}
</script>

<!-- end usps setting -->
