<!-- bumped up date tpl -->

<div class="table-cell small bump-date" id="bu_{$listing.ID}">
    <div class="name">{$lang.date}</div>
    <div class="value">{$listing.Date|date_format:$smarty.const.BUMPUP_TIME_FORMAT}</div>
</div>

{if $listing.expiring_status}
    <div class="table-cell small highlight-date" id="hi_{$listing.ID}">
        <div class="name">{$lang.active_till}</div>
        <div class="value">{$listing.expiring_status}</div>
    </div>
{/if}

<!-- bumped up date tpl end -->
