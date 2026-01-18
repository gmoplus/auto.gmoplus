{if $new_booking_requests || $changed_booking_reservations}
    <script class="fl-js-dynamic">{literal}
    if (!$('#user-navbar').hasClass('notify')) {
        $('#user-navbar').addClass('notify');
    }

    $('#user-navbar a').each(function(){
        {/literal}{if $new_booking_requests && $booking_requests_url}{literal}
            if ($(this).attr('href') == '{/literal}{$booking_requests_url}{literal}') {
                $(this).addClass('b_requests');
                $(this).after('<a class="counter requests_count" title="{/literal}{$lang.booking_new_requests|replace:'[count]':$new_booking_requests}" href="{$booking_requests_url}">{$new_booking_requests}{literal}</a>');
            }
        {/literal}{/if}{if $changed_booking_reservations && $booking_reservations_url}{literal}
            if ($(this).attr('href') == '{/literal}{$booking_reservations_url}{literal}') {
                $(this).addClass('b_reservations');
                $(this).after('<a class="counter reservations_count" title="{/literal}{$lang.booking_changed_reservations|replace:'[count]':$changed_booking_reservations}" href="{$booking_reservations_url}">{$changed_booking_reservations}{literal}</a>');
            }
        {/literal}{/if}{literal}
    });
    {/literal}</script>
{/if}
