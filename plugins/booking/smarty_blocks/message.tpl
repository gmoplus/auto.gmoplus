<div class="hide" id="booking_message_obj">
    {if $fieldsetEnable}
        {include file='blocks/fieldset_header.tpl' id='booking_mes' name=$lang.booking_details}
    {/if}

    <div id="booking_message"></div>

    {if $fieldsetEnable}
        {include file='blocks/fieldset_footer.tpl'}
    {/if}
</div>
