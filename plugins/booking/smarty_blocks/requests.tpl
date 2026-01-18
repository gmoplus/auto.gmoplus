<!-- booking requests -->

{if $requests}
    <div class="list-table">
        <div class="header">
            <div class="center" style="width: 40px">#</div>
            <div>{$lang.listing}</div>

            {if $plugins.ref}
                <div style="width: 90px">{$lang.booking_ref_number}</div>
            {/if}

            <div style="width: 235px">
                {if $smarty.const.IS_ESCORT === true}{$lang.date}{else}{$lang.booking_period}{/if}
            </div>
            <div style="width: 195px">{$lang.booking_author}</div>
            <div style="width: 160px">{$lang.status}</div>
            <div style="width: 60px">{$lang.actions}</div>
        </div>

        {foreach from=$requests item='request' key='rKey'}
            <div class="row" id="item_request_{$request.ID}">
                <div class="center iteration no-flex">{$request.ID}</div>
                <div data-caption="{$lang.listing}" class="content">
                    <a href="{$request.url}" title="{$lang.booking_page_details}">{$request.title}</a>
                </div>

                {if $plugins.ref}
                    <div data-caption="{$lang.listing}">{if $request.ref_number}{$request.ref_number}{else}{$lang.not_available}{/if}</div>
                {/if}

                <div data-caption="{if $smarty.const.IS_ESCORT === true}{$lang.date}{else}{$lang.booking_period}{/if}">
                    {$request.From|date_format:$smarty.const.RL_DATE_FORMAT}

                    {if $request.Booking_type == 'time_range' && $request.Checkout}
                        - {$request.Checkout}
                    {else}
                        {if $request.To}
                            - {$request.To|date_format:$smarty.const.RL_DATE_FORMAT}
                        {/if}
                    {/if}
                </div>

                <div data-caption="{$lang.booking_author}">{$request.Author}</div>
                <div id="status_{$request.ID}" data-caption="{$lang.status}" class="statuses">
                    <span class="{if $request.status == 'process'}pending{elseif $request.status == 'refused'}expired{else}active{/if}">{if $request.status == 'process'}{if $request.Book_status == 'new'}{$lang.new}{else}{$lang.booking_request_read}{/if} ({/if}{if $request.status == 'process'}{$lang.pending}{elseif $request.status == 'booked'}{$lang.booking_legend_booked}{else}{$lang.booking_refused}{/if}{if $request.status == 'process'}){/if}
                    </span>
                </div>
                <div data-caption="{$lang.actions}">
                    <img id="{$request.ID}" class="remove remove_request" alt="{$lang.delete}" title="{$lang.delete}" src="{$rlTplBase}img/blank.gif" />
                </div>
            </div>
        {/foreach}
    </div>
{/if}

<!-- booking requests end -->
