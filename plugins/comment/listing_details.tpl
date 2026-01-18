<!-- rating on the listing details page -->

{if $listing_data.comments_count}
    {include file=$smarty.const.RL_PLUGINS|cat:'comment/listing_details_dom.tpl'}

    <script class="fl-js-dynamic">
    rlConfig['comment_mode'] = '{$config.comment_mode}';
    {literal}

    $(function(){
        $('.comments-link-to-source').click(function(){
            switch (rlConfig['comment_mode']) {
                case 'tab':
                    $('.listing-details .tabs #tab_comments a').trigger('click');
                    break;

                case 'box':
                    flynax.slideTo('.comments_block_bottom');
                    break;
            }
        });
    });

    {/literal}
    </script>
{/if}

<!-- rating on the listing details page end -->
