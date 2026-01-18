<div style="background: #FFFFFF; position: relative;">
    <div style="padding: 14px;">
        <h1 style="margin: 0 0 20px;">{$lang.shc_order_key} {$order.Order_key}</h1>
        <div style="width: 100%;">
            <div style="margin-left: 0; width: 31.623931623931625%; display: block; min-height: 30px; box-sizing: border-box; float: left;">
                <strong>{$lang.shc_order_key}: {$order.Order_key}</strong><br>
                <address>
                    {$lang.date}: {$order.Date}<br>
                    {$lang.shc_txn_id}: {$order.Txn_id}<br>
                    {$lang.shc_payment_method}: {$order.Gateway}<br>
                    {$lang.shc_payment_status}: {$order.Payment_status}<br>
                </address>
            </div>
            <div style="margin-left: 2.564102564102564%; width: 31.623931623931625%; display: block; min-height: 30px; box-sizing: border-box; float: left;">
                <strong>{$lang.shc_shipping_details}</strong>
                <address>         
                    <strong>{$lang.shc_shipping_method}: {$order.Shipping_method}</strong><br>
                    {$lang.shc_shipping_status}: {$order.Shipping_status}<br>
                    {$lang.shc_country}: {$order.Country}<br>
                    {$lang.shc_city}: {$order.City}<br>
                    {$lang.shc_zip}: {$order.Zip_code}<br>
                    {$lang.shc_address}: {$order.Address}<br>
                    {$lang.shc_phone}: {$order.Phone}<br>
                </address>    
            </div>
            <div style="margin-left: 2.564102564102564%; width: 31.623931623931625%; display: block; min-height: 30px; box-sizing: border-box; float: left;">
                <strong>{$lang.shc_seller_info}</strong>
                <address>
                    <strong>{$lang.shc_dealer}: {$order.dFull_name}</strong><br>
                    {$lang.shc_company}: {$order.seller.company_name}<br>
                    {$lang.shc_country}: {$order.seller.country}<br>
                    {$lang.shc_city}: {$order.seller.city}<br>
                    {$lang.shc_zip}: {$order.seller.zip_code}
                    {$lang.shc_address}: {$order.seller.address}<br>
                    {$lang.shc_phone}: {$order.seller.phone}<br>
                </address>
            </div>
        </div>
        <div style="margin-top: 30px; width: 100%;">
            <div style="margin-left: 0;">
                <table style="width: 100%; margin-bottom: 20px; border-spacing: 0; display: table;">
                    <thead>
                        <tr>
                            <th style="display: table-cell; background-color: #fef5e7;"></th>
                            <th style="display: table-cell; background-color: #fef5e7;">{$lang.item}</th>
                            <th style="display: table-cell; background-color: #fef5e7;">{$lang.shc_price}</th>
                            <th style="display: table-cell; background-color: #fef5e7;">{$lang.shc_quantity}</th>
                            <th style="display: table-cell; background-color: #fef5e7;">{$lang.shc_total}</th>
                        </tr>
                    </thead>
                    <tbody>
                        {foreach from=$order.items item='item'}
                        <tr>
                            <td style="display: table-cell; padding: 8px; line-height: 20px; text-align: left; vertical-align: top; border-top: 1px solid #dddddd;">
                                <img style="width: 70px; margin-{$text_dir_rev}: 10px;" src="{$smarty.const.RL_FILES_URL}{$item.Main_photo}" alt="{$item.Item}" />
                            </td>
                            <td style="display: table-cell; padding: 8px; line-height: 20px; text-align: left; vertical-align: top; border-top: 1px solid #dddddd;"><p>{$item.Item}</p></td>
                            <td style="display: table-cell; padding: 8px; line-height: 20px; text-align: left; vertical-align: top; border-top: 1px solid #dddddd;">{$item.Price}</td>
                            <td style="display: table-cell; padding: 8px; line-height: 20px; text-align: left; vertical-align: top; border-top: 1px solid #dddddd;">{$item.Quantity}</td>
                            <td style="display: table-cell; padding: 8px; line-height: 20px; text-align: left; vertical-align: top; border-top: 1px solid #dddddd;">{$item.Total}</td>
                        </tr>
                        {/foreach}
                        <tr class="last_row">
                            <td colspan="3" style="border-top: 1px solid #dddddd;">&nbsp;</td>
                            <td style="text-align: right; padding: 8px; line-height: 20px; border-top: 1px solid #dddddd;">
                                <p>{$lang.shc_subtotal}</p>
                                <p>{$lang.shc_shipping_price}</p>
                                <p><strong>{$lang.shc_total}</strong></p>
                            </td>
                            <td style="text-align: right; padding: 8px; line-height: 20px; border-top: 1px solid #dddddd;">
                                <p>{$order.Subtotal}</p>
                                <p>{$order.Shipping_price}</p>
                                <p><strong>{$order.Total}</strong></p>
                            </td>
                        </tr>
                    </tbody>
                </table>    
            </div>
        </div>
        {if $order.Comment}
        <div style="margin-top: 30px; width: 100%;">
            <div style="margin-left: 0;">
                <h4 style="margin: 0 0 10px; padding: 0 0 6px; border-bottom: 1px solid #DDDDDD;">{$lang.shc_notes_email}</h4>
                {$order.Comment}
            </div>
        </div>
        {/if}
    </div>
</div>
