<!-- Auction details -->

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_start.tpl'}

    {if $auction_info.options.Auction_won}
        <fieldset class="light">
            <legend id="legend_auction_info" class="up" onclick="fieldset_action('auction_info');">{$lang.shc_order_details}</legend>
            <div id="auction_info">
                <table class="list">
                    <tr>
                        {if $auction_info.Main_photo}
                        <td class="name">
                            <a href="" title="{$auction_info.listing_title}">
                                <img alt="" src="{$smarty.const.RL_URL_HOME}files/{$auction_info.Main_photo}" />
                            </a>
                        </td>
                        {/if}
                        <td class="value">
                            <table class="table">
                                <tr>
                                    <td class="name">{$lang.item}:</td>
                                    <td class="value"><b>{$auction_info.listing_title}</b></td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.total}:</td>
                                    <td class="value"><b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.order.Total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b></td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.status}:</td>
                                    <td class="value">{$auction_info.shc_auction_status}</td>
                                </tr>
                                {if $config.shc_method == 'multi'}
                                <tr>
                                    <td class="name">{$lang.shc_commission}:</td>
                                    <td class="value"><b>{if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.order.Commission_total|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}</b></td>
                                </tr>
                                {/if}
                                <tr>
                                    <td class="name">{$lang.shc_payment_status}:</td> 
                                    <td class="value">
                                        <span class="item_{$auction_info.Status}">{$lang[$auction_info.order.Status]}</span>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.shc_buyer}:</td>
                                    <td class="value">
                                        <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$auction_info.buyer.ID}">{$auction_info.buyer.Full_name}</a>
                                    </td>
                                </tr>
                                <tr>
                                    <td class="name">{$lang.shc_dealer}:</td>
                                    <td class="value">
                                        <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$auction_info.dealer.ID}">{$auction_info.dealer.Full_name}</a>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>
                    {if $auction_info.pStatus == 'paid'}
                        <tr>
                            <td class="name">{$lang.txn_id}:</td>
                            <td class="value">{$auction_info.Txn_ID}</td>
                        </tr>
                        <tr>
                            <td class="name">{$lang.gateway}:</td>
                            <td class="value">{$auction_info.order.Gateway}</td>
                        </tr>
                        <tr>
                            <td class="name">{$lang.date}:</td>
                            <td class="value">{$auction_info.order.Pay_date|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                        </tr>
                    {/if}
                </table>
            </div>
        </fieldset>
    {else}
        <fieldset class="light">
            <legend id="legend_auction_info" class="up" onclick="fieldset_action('auction_info');">{$lang.shc_auction_details}</legend>
            <div id="auction_info">
            <table class="list">
                <tr>
                    {if !empty($auction_info.Main_photo)}
                    <td class="name">
                        <a href="" title="{$auction_info.listing_title}">
                            <img alt="" src="{$smarty.const.RL_URL_HOME}files/{$auction_info.Main_photo}" />
                        </a>
                    </td>
                    {/if}
                    <td class="value">
                        <table class="table">
                            <tr>
                                <td class="name">{$lang.item}:</td>
                                <td class="value"><b>{$auction_info.listing_title}</b></td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_buy_now}:</td>
                                <td class="value"><b>
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.price|intval|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_start_price}:</td>
                                <td class="value">
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.options.Start_price|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_reserved_price}:</td>
                                <td class="value">
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.options.Reserved_price|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_bid_step}:</td>
                                <td class="value">
                                    {if $config.system_currency_position == 'before'}{$config.system_currency}{/if} <span id="total">{$auction_info.options.Bid_step|number_format:2:'.':','}</span> {if $config.system_currency_position == 'after'}{$config.system_currency}{/if}
                                </td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_start_time}:</td>
                                <td class="value">{$auction_info.shc_start_time|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.shc_time_left}:</td>
                                <td class="value">{$auction_info.left_time}</td>
                            </tr>
                            <tr>
                                <td class="name">{$lang.status}:</td>
                                <td class="value">{$auction_info.shc_auction_status}</td>
                            </tr>
                            {if $auction_info.shc_auction_status == 'closed'}
                            <tr>
                                <td class="name">{$lang.shc_auction_date_close}:</td>
                                <td class="value">{$auction_info.options.End_time|date_format:$smarty.const.RL_DATE_FORMAT}</td>
                            </tr>
                            {/if}
                            {if $auction_info.buyer}
                            <tr>
                                <td class="name">{$lang.shc_buyer}:</td>
                                <td class="value">
                                    <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$auction_info.buyer.ID}">{$auction_info.buyer.Full_name}</a>
                                </td>
                            </tr>
                            {/if}
                            <tr>
                                <td class="name">{$lang.shc_dealer}:</td>
                                <td class="value">
                                    <a target="_blank" href="{$rlBase}index.php?controller=accounts&action=view&userid={$auction_info.dealer.ID}">{$auction_info.dealer.Full_name}</a>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>
        </fieldset>
    {/if}

    {include file=$smarty.const.RL_PLUGINS|cat:'shoppingCart/admin/view/shipping_info.tpl' order_info=$auction_info.order}

{include file='blocks'|cat:$smarty.const.RL_DS|cat:'m_block_end.tpl'} 

<!-- end Auction details -->
