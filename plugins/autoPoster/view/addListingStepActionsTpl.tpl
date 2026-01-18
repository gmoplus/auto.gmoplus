{if $cur_step == 'done'}
    <script class="fl-js-dynamic">
        var listing_id = {$smarty.session.add_listing.listing_id};
        {literal}
        $(document).ready(function () {
            if (listing_id) {
                autoPoster = new AutoPosters();
                autoPoster.sendPost(listing_id);
            }
        });
        {/literal}
    </script>
{/if}
