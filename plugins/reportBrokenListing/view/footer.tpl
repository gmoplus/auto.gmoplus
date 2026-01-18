<!-- report broken listing form -->
<div class="hide" id="reportBrokenListing_form">
    <div class="caption hide">{$lang.reportbroken_add_comment}</div>
    <div class="text-center rbl_loading">{$lang.loading}</div>
    <div id="points"></div>
    <div class="report-nav hide">
        <div class="mt-4">
            <input type="submit" id="add-report" name="send" value="{$lang.rbl_report}" class="mr-3" />
            <a href="javascript://" class="close">{$lang.cancel}</a>
        </div>
    </div>
</div>

<div class="hide" id="remove_report_form">
    <div class="caption"></div>
    <div>{$lang.reportbroken_do_you_want_to_delete_list}</div>
    <div class="mt-4">
        <input type="submit" id="remove-report-button" name="remove" value="{$lang.yes}" class="mr-3" />
        <a href="javascript://" class="close">{$lang.no}</a>
    </div>
</div>

<script class="fl-js-dynamic">
    rlConfig['reportBroken_listing_id'] = {if $listing_data.ID}{$listing_data.ID}{else}0{/if};
    rlConfig['reportBroken_message_length'] = {if $config.reportBroken_message_length}{$config.reportBroken_message_length}{else}300{/if};
    lang['message'] = '{$lang.message}';

    $(document).ready(function() {literal}{{/literal}
        var reportBrokenListing = new ReportBrokenListings();
        reportBrokenListing.listing_id = rlConfig['reportBroken_listing_id'];
        reportBrokenListing.init();
    {literal}}{/literal});
</script>
<!-- report broken listing form end -->
