<!-- my cart page / items list -->

<div class="list-table cart-items-table">
    <div class="header">
        <div class="text-center" style="width: 40px;">#</div>
        <div>{$lang.item}</div>
        <div style="width: 90px;">{$lang.price}</div>
        <div style="width: 100px;">{$lang.shc_quantity}</div>
        <div style="width: 106px;">{$lang.total}</div>
        {if !$preview}<div style="width: 40px;"></div>{/if}
    </div>

    {assign var='item_index' value=1}
    {foreach from=$shcItems item='item' name='itemsF'}
        {if !$item.shc_available}{continue}{/if}

        <div id="cart-item-{$item.ID}" class="row">
            <div class="iteration no-flex text-center">{$item_index}</div>
            <div data-caption="{$lang.item}" class="d-flex flex-column flex-md-row">
                {if $item.main_photo}
                    <div class="image mr-2">
                        <a href="{$item.listing_link}" target="_blank">
                            <img alt="{$item.Item}" class="shc-item-picture" src="{$smarty.const.RL_FILES_URL}{$item.main_photo}" />
                        </a>
                    </div>
                {/if}

                <div class="mt-2 mt-md-0">
                    <a href="{$item.listing_link}" target="_blank">{$item.Item}</a>
                    {if $item.shipping_item_options && ($cur_step != 'cart' || $cur_step == '') && !$item.Digital}
                        {if isset($item.shipping_item_options.0)}
                            <div>
                                <div class="name">{$lang.shc_shipping_method_select}:</div>
                                {foreach from=$item.shipping_item_options item='option' key='key'}
                                    {if !empty($option.total)}
                                        <div class="field"><label><input type="radio" {if $option.selected}checked="checked"{/if} accesskey="{$option.total}" name="items[{$item.ID}][service]" value="{$key}" /> {$option.title} - {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}{str2money string=$option.total} {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</label></div>
                                    {/if}
                                {/foreach}
                            </div>
                        {else}
                            <div>
                                {$lang.shc_shipping_price}:
                                <span class="shc_price">
                                    {if $item.shipping_item_options.total}
                                        {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                                        {str2money string=$item.shipping_item_options.total}
                                        {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                    {else}
                                        {$lang.free}
                                    {/if}
                                </span>
                            </div>

                            <div>
                                {$lang.shc_shipping_method}:
                                <span>
                                    {if $config.shc_shipping_step}
                                        {if $item.shipping_item_options.service}
                                            {$item.shipping_item_options.service}
                                        {elseif $item.shipping_item_options.title}
                                            {$item.shipping_item_options.title}
                                        {/if}
                                    {else}
                                        {$lang.shc_pickup}

                                        {if !$single_seller}
                                            / <span class="link show-pickup-details" data-item-id="{$item.ID}">{$lang.view_details}</a>
                                            <script>
                                            {literal}
                                            if (typeof pickup_data == 'undefined') {
                                                var pickup_data = [];
                                            }
                                            {/literal}
                                            pickup_data['{$item.ID}'] = JSON.parse('{$item.pickup_details|@json_encode}');
                                            </script>
                                        {/if}
                                    {/if}
                                </span>
                            </div>
                        {/if}
                    {/if}
                </div>  
            </div>
            <div data-caption="{$lang.price}" class="nr item-price">
                <span class="shc_price">{$item.price_original}</span>
            </div>
            <div data-caption="{$lang.shc_quantity}" class="align-content-sm-center align-content-left text-left text-sm-center">
                {if $item.Digital && $item.Quantity_unlim}
                    <span>{$lang.not_available}</span>
                {else}
                    {if $preview}
                        {$item.Quantity}
                    {else}
                        <span class="nav decrease" title="{$lang.shc_decrease}">-</span>
                        <input accesskey="{$item.Price}" 
                            type="text" 
                            class="numeric quantity text-center" 
                            name="quantity[{$item.ID}]" 
                            id="quantity_{$item.ID}" 
                            value="{$item.Quantity}" 
                            data-dealer="{$item.Dealer_ID}"
                            data-prev-quantity="{$item.Quantity}" 
                            data-available-quantity="{math equation='(total-current)+1' total=$item.shc_quantity current=$item.Quantity}" 
                            maxlength="3" 
                        />
                        <span class="nav increase" title="{$lang.shc_increase}">+</span>
                    {/if}
                {/if}
            </div>
            <div data-caption="{$lang.total}" class="nr item-total">
                <span id="price_{$item.ID}" class="shc_price">{strip}
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                {str2money string=$item.total}
                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                {/strip}</span>
            </div>
            {if !$preview}
                <div class="action no-flex">
                    <span title="{$lang.delete}" class="icon delete delete-item-from-cart remove" data-id="{$item.ID}" data-item-id="{$item.Item_ID}"></span>
                </div>
            {/if}
        </div>

        {assign var='item_index' value=$item_index+1}
    {/foreach}
</div>
{if !$preview}
    <input type="hidden" name="form" value="submit" />
    <input type="hidden" name="dealer" value="{$item.Dealer_ID}" />

    <div class="ralign">
        <!-- total -->
        <div class="shc_value pt-3 pb-4">
            <span class="price-cell">{$lang.total}:</span>
            <span id="total_{$shcDealer}" class="value shc_price">{strip}
                {if $config.system_currency_position == 'before'}{$config.system_currency}{/if}
                {str2money string=$shcTotal}
                {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
            {/strip}</span>
        </div>
        <!-- total end -->
    </div>
    <div class="text-right">
        <input type="submit" value="{$lang.next_step}" />
    </div>
{/if}

<!-- my cart page / items list end -->
