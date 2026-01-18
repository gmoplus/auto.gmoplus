{if $listing.ID
    && $listing.booking_module
    && ($booking_allowed_plans[$listing.Plan_ID]
        || ($config.membership_module && $booking_allowed_membership_plans[$listing.Plan_ID])
    )
    && $listing_type.Booking_type != 'none'
}
    <li class="nav-icon">
        <a class="booking-details" href="{pageUrl key='booking_details' vars="id=`$listing.ID`"}">
            <span>{$lang.booking_module}</span>
        </a>
    </li>
{/if}
