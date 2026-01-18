<!-- booking reservations -->

{if $isLogin}
    <div class="content-padding {if defined('IS_ESCORT')}reservations-escort{/if}">
        {if $reservations}
            <section id="listings" class="list row">
                {foreach from=$reservations item='reservation' key='request_id' name='bReservations'}
                    {if $reservation.ltype}
                        {assign var='listing_type' value=$listing_types[$reservation.ltype]}
                    {/if}

                    <article id="item_request_{$request_id}" class="item{if !$listing_type.Photo} no-image{/if} two-inline col-sm-6{if !$side_bar_exists} col-md-12{/if}">
                        <div class="navigation-column">
                            {if $reservation.status == 'process'}<a id="{$request_id}" class="button remove_request" href="javascript:void(0)">{$lang.remove}</a>{/if}
                        </div>

                        <div class="clearfix">
                            <a title="{$reservation.title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$reservation.link}">
                                <div class="picture{if !$reservation.Main_photo} no-picture{/if}">
                                    {* @todo 3.1.0 - Remove this when "compatible" will be more than 4.6.2 *}
                                    {if $config.rl_version|version_compare:'4.6.2' <= 0}
                                        <img alt="{$reservation.title}"
                                             src="{$rlTplBase}img/blank_10x7.gif"
                                                {if $reservation.Main_photo}data-1x="{$smarty.const.RL_FILES_URL}{$reservation.Main_photo}"{/if}
                                                {if $reservation.Main_photo || $reservation.Main_photo_x2}data-2x="{$smarty.const.RL_FILES_URL}{if $reservation.Main_photo_x2}{$reservation.Main_photo_x2}{else}{$reservation.Main_photo}{/if}"{/if} />
                                    {else}
                                        <img alt="{$reservation.title}"
                                            src="{if $reservation.Main_photo}{$smarty.const.RL_FILES_URL}{$reservation.Main_photo}{else}{$rlTplBase}img/blank_10x7.gif{/if}"
                                            {if $reservation.Main_photo_x2}srcset="{$smarty.const.RL_FILES_URL}{$reservation.Main_photo_x2} 2x"{/if} />
                                    {/if}
                                </div>
                            </a>

                            <ul {if $tpl_settings.name !== 'escort_nova_wide'}class="{if $config.rl_version|version_compare:'4.9.1':'>'}card{else}ad{/if}-info"{/if}>
                                <li class="title">
                                    <a class="link-large" title="{$reservation.listing_title}" {if $config.view_details_new_window}target="_blank"{/if} href="{$reservation.link}">
                                        {$reservation.title}
                                    </a>
                                </li>

                                <li><b>{$lang.booking_request_id}:</b> {$request_id}</li>

                                <li>
                                    {if $reservation.Booking_type == 'time_range'}
                                        <b>{$lang.booking_escort_date}:</b>
                                    {elseif $reservation.Booking_type == 'date_time_range'}
                                        <b>{$lang.booking_checkin_auto}:</b>
                                    {else}
                                        <b>{$lang.booking_checkin}:</b>
                                    {/if}

                                    {$reservation.Booking_from|date_format:$smarty.const.RL_DATE_FORMAT}
                                    {if $reservation.Booking_type != 'time_range' && $reservation.Checkin} - {$reservation.Checkin}{/if}
                                </li>

                                {if $reservation.Booking_type != 'time_range'}
                                    <li>
                                        {if $reservation.Booking_type == 'date_time_range'}
                                            <b>{$lang.booking_checkout_auto}:</b>
                                        {else}
                                            <b>{$lang.booking_checkout}:</b>
                                        {/if}

                                        {$reservation.Booking_to|date_format:$smarty.const.RL_DATE_FORMAT}
                                        {if $reservation.Checkout} - {$reservation.Checkout}{/if}
                                    </li>
                                {/if}

                                {if $reservation.Booking_type == 'time_range'}
                                    {if $reservation.Checkout}
                                        <li><b>{$lang.booking_escort_time}:</b> {$reservation.Checkout}</li>
                                    {/if}

                                    {if $reservation.Type}
                                        <li><b>{$lang.booking_escort_type}:</b> {$reservation.Type}</li>
                                    {/if}

                                    {if $reservation.Duration}
                                        <li><b>{$lang.booking_escort_duration}:</b> {$reservation.Duration}</li>
                                    {/if}
                                {/if}

                                <li class="statuses">
                                    <b>{$lang.status}:</b>
                                    <span class="{if $reservation.status == 'process'}pending{elseif $reservation.status == 'refused'}expired{else}active{/if}">{if $reservation.status == 'process'}{if $reservation.Book_status == 'new'}{$lang.new}{else}{$lang.booking_request_read}{/if} ({/if}{if $reservation.status == 'process'}{$lang.pending}{elseif $reservation.status == 'booked'}{$lang.booking_legend_booked}{else}{$lang.booking_refused}{/if}{if $reservation.status == 'process'}){/if}</span>

                                    {if $reservation.Comment}
                                        <img class="qtip" alt="" title="{$reservation.Comment}" src="{$rlTplBase}img/blank.gif" />
                                    {/if}
                                </li>
                            </ul>
                        </div>
                    </article>
                {/foreach}
            </section>

            <!-- paging block -->
            {paging calc=$pInfo.calc total=$reservations|@count current=$pInfo.current per_page=$config.booking_reservations_per_page}
            <!-- paging block end -->

            {if $pageInfo.rel_prev}
                {assign var='previousPageUrl' value=$pageInfo.rel_prev}
            {else}
                {pageUrl key=$pageInfo.Key assign='previousPageUrl'}
            {/if}

            <script class="fl-js-dynamic">
            lang['booking_remove_notice']   = '{$lang.booking_remove_notice}';
            lang['booking_no_reservations'] = '{$lang.booking_no_reservations}';

            // Booking special configs
            var bookingConfigs = new Array();

            flynax.qtip();

            // Redirect to prev page after remove all reservations in current page
            booking.requestRemoveHandler('{$previousPageUrl}');
            </script>
        {else}
            <div class="text-notice">{$lang.booking_no_reservations}</div>
        {/if}
    </div>
{/if}

<!-- booking reservations end -->
