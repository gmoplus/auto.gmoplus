<!-- listing ration block -->

<li id="listing_rating_dom">
    {include file=$smarty.const.RL_PLUGINS|cat:'rating'|cat:$smarty.const.RL_DS|cat:'dom.tpl'}

    {if !$rating_denied && (($config.rating_prevent_visitor && $isLogin) || !$config.rating_prevent_visitor) && (!$config.rating_prevent_owner || ($config.rating_prevent_owner && $listing_data.Account_ID != $account_info.ID))}
        <script type="text/javascript">
        var rating_listing_id = {$listing_data.ID};
        {literal}

        $(document).ready(function(){
            var lr = $('ul.listing_rating_ul');
            lr.find('li').mouseenter(function(){
                var index = lr.find('li').index(this) + 1;
                for(var i = 0; i < index; i++)
                {
                    lr.find('li:eq('+i+')').addClass('hover');
                    if ( lr.find('li:eq('+i+') div').length > 0 )
                    {
                        lr.find('li div').hide();
                    }
                }
            }).mouseleave(function(){
                lr.find('li').removeClass('hover');
                lr.find('li div').show();
            }).click(function(){
                var stars = lr.find('li').index(this) + 1;
                xajax_rate(rating_listing_id, stars);
            });
        });

        {/literal}
        </script>
    {/if}

</li>

<!-- listing ration block end -->
