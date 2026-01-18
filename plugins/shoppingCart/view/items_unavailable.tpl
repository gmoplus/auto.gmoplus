<!-- my cart page / items unavailable list -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id='unavailable_items' name=$lang.shc_unavailable_items class='unavailable'}
<div class="list-table cart-items-table">
    <div class="header">
        <div class="text-center" style="width: 40px;">#</div>
        <div>{$lang.item}</div>
        <div style="width: 40px;"></div>
    </div>

    {foreach from=$shcItems item='item' name='itemsUF'}
        {if !$item.shc_available}
        <div id="cart-item-{$item.ID}" class="row">
            <div class="text-center iteration no-flex">{$smarty.foreach.itemsUF.iteration}</div>
            <div data-caption="{$lang.item}" class="d-flex flex-column flex-md-row">
                {if $item.main_photo || $item.photo_tmp}
                    <div class="image mr-2">
                        <a href="{$item.listing_link}" target="_blank">
                            <img class="shc-item-picture"
                                 alt="{$item.Item}"
                                {if $item.main_photo}
                                    src="{$smarty.const.RL_FILES_URL}{$item.main_photo}" 
                                {else}
                                    src="data:image/png;base64, {$item.photo_tmp}"
                                {/if}
                            />
                        </a>
                    </div>
                {/if}
                <div class="mt-2 mt-md-0">
                    <a href="{$item.listing_link}" target="_blank">{$item.Item}</a>
                    <div class="unavailable-notice">
                        {if $item.Status == 'deleted'}
                            {$lang.shc_item_deleted}
                        {elseif $item.Dealer_ID == $account_info.ID}
                            {$lang.shc_owner_item}
                        {else}
                            {$lang.shc_not_available}
                        {/if}
                    </div>
                </div>  
            </div>
            <div class="action no-flex">
                <span title="{$lang.delete}" class="icon delete delete-item-from-cart" data-id="{$item.ID}" data-item-id="{$item.Item_ID}"></span>
            </div>
        </div>
        {/if}
    {/foreach}
</div>
{include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

<!-- my cart page / items unavailable list end -->
