<!-- listing details -->

{if !$errors}

{if $config.map_module && $location.direct}
    {mapsAPI assign='mapAPI'}

    <script>
    rlConfig['mapAPI'] = [];
    rlConfig['mapAPI']['css'] = JSON.parse('{$mapAPI.css|@json_encode}');
    rlConfig['mapAPI']['js']  = JSON.parse('{$mapAPI.js|@json_encode}');
    </script>
{/if}

<div class="listing-details details{if $config.map_module && $location.direct} loc-exists{/if}">
    {rlHook name='listingDetailsTopTpl'}

    <section class="main-section">
        <div class="d-flex top-navigation mb-3">
            {if $listing_data.Account_ID != $account_info.ID}
            <div class="icons">
                  <span id="fav_{$listing_data.ID}" class="favorite add" title="{$lang.add_to_favorites}"><span class="icon"></span></span>
            </div>
            {/if}
            <div class="d-flex flex-wrap">
                <h1>{$pageInfo.name}</h1>

                {if $listing.common.Fields.bedrooms.value || $listing.common.Fields.bathrooms.value || $listing.common.Fields.square_feet.value}
                <ul class="card-info services w-100">
                    <li>
                        {strip}
                        {if $listing.common.Fields.bedrooms.value}
                            <span title="{$listing.fields.bedrooms.name}" class="badrooms">{$listing.common.Fields.bedrooms.value}</span>
                        {/if}
                        {if $listing.common.Fields.bathrooms.value}
                            <span title="{$listing.fields.bathrooms.name}" class="bathrooms">{$listing.common.Fields.bathrooms.value}</span>
                        {/if}
                        {if $listing.common.Fields.square_feet.value}
                            <span title="{$listing.fields.square_feet.name}" class="square_feet">{$listing.common.Fields.square_feet.value}</span>
                        {/if}
                        {/strip}
                    </li>
                </ul>
                {/if}
            </div>
        </div>

        <div class="row">
            <div class="col-lg-8">
                <div class="d-flex justify-content-between">
                    {rlHook name='tplListingDetailsNavLeft'}
                    {rlHook name='tplListingDetailsNavRight'}
                </div>

                {if $photos}
                    {include file=$componentDir|cat:'listing-details-gallery/_listing-details-gallery.tpl'}
                {/if}

                <div class="d-flex flex-wrap align-items-center justify-content-between">
                    <!-- price tag -->
                    {if $price_tag_value}
                        <div class="price-tag mb-3" id="df_field_price">
                            <span>{$price_tag_value}</span>
                            {if $listing_data.sale_rent == 2 && $listing.common.Fields.time_frame.value}
                                / {$listing.common.Fields.time_frame.value}
                            {/if}
                        </div>
                    {/if}
                    <!-- price tag end -->

                    <div class="mb-3">
                        {rlHook name='tplListingDetailsRating'}
                    </div>
                </div>

                <section class="content-section clearfix">
                    <!-- tabs -->
                    {if $tabs|@count > 1}
                        <ul class="tabs tabs-hash">
                            {foreach from=$tabs item='tab' name='tabF'}{strip}
                                <li {if $smarty.foreach.tabF.first}class="active"{/if} id="tab_{$tab.key}">
                                    <a href="#{$tab.key}" data-target="{$tab.key}">{$tab.name}</a>
                                </li>
                            {/strip}{/foreach}
                        </ul>
                    {/if}
                    <!-- tabs end -->

                    <!-- listing details -->
                    <div id="area_listing" class="tab_area">

                        {rlHook name='listingDetailsPreFields'}

                        {foreach from=$listing item='group'}
                            <div class="row group {if $group.Key}{$group.Key}{else}no-group{/if}">
                                {if $group.Group_ID && $group.Key != 'common'}
                                    {assign var='hide' value=false}
                                    {if !$group.Display}
                                        {assign var='hide' value=true}
                                    {/if}

                                    {assign var='value_counter' value='0'}
                                    {foreach from=$group.Fields item='group_values' name='groupsF'}
                                        {if $group_values.value == '' || !$group_values.Details_page}
                                            {assign var='value_counter' value=$value_counter+1}
                                        {/if}
                                    {/foreach}

                                    {if !empty($group.Fields) && ($smarty.foreach.groupsF.total != $value_counter)}
                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.ID name=$group.name hide=$hide is_listing_detail=true}

                                        {if $group.Key == 'location' && $config.map_module && $location.direct}
                                            <div class="col-12">
                                                <div class="row">
                                                    <div class="col-sm-6 fields">
                                                        <div class="row">
                                                        {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                                            {if !empty($item.value) && $item.Details_page}
                                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' grid_row=true}
                                                            {/if}
                                                        {/foreach}
                                                        </div>
                                                    </div>
                                                    <div class="col-sm-6 map">
                                                        {if $config.map_module && $location}
                                                            <section title="{$lang.expand_map}" class="map-capture">
                                                                <img alt="{$lang.expand_map}"
                                                                     src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180}"
                                                                     srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180 scale=2} 2x" />
                                                                {if !$listing_type.Photo || !$photos}<span class="media-enlarge"><span></span></span>{/if}
                                                            </section>
                                                        {/if}
                                                    </div>
                                                </div>
                                            </div>

                                            {if !$listing_type.Photo || !$photos}
                                                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_static_map.tpl'}
                                            {else}
                                                <script class="fl-js-dynamic">
                                                {literal}

                                                $(function(){
                                                    $('.map .map-capture img').click(function(){
                                                        flynax.slideTo('.listing-details');
                                                        $('#media .nav-buttons .nav-button.map').trigger('click');
                                                    });
                                                });

                                                {/literal}
                                                </script>
                                            {/if}
                                        {else}
                                            {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                                {if !empty($item.value) && $item.Details_page}
                                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' grid_row=true}
                                                {/if}
                                            {/foreach}
                                        {/if}

                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                                    {/if}

                                    {assign var='main_section_no_group' value=false}
                                {else}
                                    {if $group.Fields}
                                        {foreach from=$group.Fields item='item'}
                                            {if !empty($item.value) && $item.Details_page}
                                                {if $item.Key != 'description_add'}
                                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' grid_row=true}
                                                {else}
                                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl' grid_row=true full_width=true}
                                                {/if}
                                            {/if}
                                        {/foreach}
                                    {/if}
                                {/if}
                            </div>
                        {/foreach}
                    </div>
                    <!-- listing details end -->

                    {if $config.tell_a_friend_tab}
                        <!-- tell a friend tab -->
                        <div id="area_tell_friend" class="tab_area hide">
                            <div class="content-padding">
                                <div class="submit-cell">
                                    <div class="name">{$lang.friend_name} <span class="red">*</span></div>
                                    <div class="field"><input class="wauto" type="text" id="friend_name" name="friend_name" maxlength="50" size="30" value="{$smarty.post.friend_name}" /></div>
                                </div>

                                <div class="submit-cell">
                                    <div class="name">{$lang.friend_email} <span class="red">*</span></div>
                                    <div class="field"><input class="wauto" type="text" id="friend_email" name="friend_email" maxlength="50" size="30" value="{$smarty.post.friend_email}" /></div>
                                </div>

                                <div class="submit-cell">
                                    <div class="name">{$lang.your_name}</div>
                                    <div class="field"><input class="wauto" type="text" id="your_name" name="your_name" maxlength="100" size="30" value="{$account_info.Full_name}" /></div>
                                </div>

                                <div class="submit-cell">
                                    <div class="name">{$lang.your_email}</div>
                                    <div class="field"><input class="wauto" type="text" id="your_email" name="your_email" maxlength="30" size="30" value="{$account_info.Mail}" /></div>
                                </div>

                                <div class="submit-cell">
                                    <div class="name">{$lang.message}</div>
                                    <div class="field"><textarea id="message" name="message" rows="6" cols="50">{$smarty.post.message}</textarea></div>
                                </div>

                                {if $config.security_img_tell_friend}
                                    <div class="submit-cell">
                                        <div class="name">{$lang.security_code} <span class="red">*</span></div>
                                        <div class="field">
                                            {include file='captcha.tpl' no_caption=true}
                                        </div>
                                    </div>
                                {/if}

                                <div class="submit-cell buttons">
                                    <div class="name"></div>
                                    <div class="field"><input onclick="xajax_tellFriend($('#friend_name').val(), $('#friend_email').val(), $('#your_name').val(), $('#your_email').val(), $('#message').val(), $('#security_code').val(), '{$print.id}');$(this).val('{$lang.loading}');" type="button" name="finish" value="{$lang.send}" /></div>
                                </div>
                            </div>
                        </div>
                        <!-- tell a friend tab end -->
                    {/if}

                    <!-- tabs content -->
                    {rlHook name='listingDetailsBottomTpl'}
                    <!-- tabs content end -->
                </section>
            </div>
            <div class="col-lg-4 details-sidebar">
                {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_sidebar.tpl'}

                <!-- statistics area -->
                <section class="statistics">
                    <ul class="controls">
                        <li>
                            <ul class="counters">
                                {if $config.count_listing_visits}<li><span class="name">{$lang.shows}:</span> {$listing_data.Shows}</li>{/if}
                                {if $listing_data.comments_count}<li><a href="#comments"><span class="name">{$lang.comment_tab}:</span> {$listing_data.comments_count}</a></li>{/if}
                                {rlHook name='listingDetailsCounters'}
                            </ul>
                        </li>
                        {rlHook name='listingDetailsAfterStats'}

                        {include file=$controllerDir|cat:'listing_details/navigation.tpl' noFavIcon=true}
                    </ul>
                </section>
                <!-- statistics area end -->
            </div>
        </div>
    </section>

    <script class="fl-js-dynamic">
    {if isset($smarty.get.highlight)}
        flynaxTpl.highlightResults("{$smarty.session.keyword_search_data.keyword_search}", true);
    {/if}

    var ld_inactive = {if $pageInfo.Listing_details_inactive}'{$lang.ld_inactive_notice}'{else}false{/if};

    {literal}
        if ($('#df_field_vin .value').length > 0) {
            var html = '<a style="font-size: 14px;" href="javascript:void(0);">{/literal}{if $lang.check_vin}{$lang.check_vin}{else}Check Vin{/if}{literal}</a>';
            var vin = trim( $('#df_field_vin .value').text() );
            var frame = '<iframe scrolling="auto" height="600" frameborder="0" width="100%" src="http://www.carfax.com/cfm/check_order.cfm?vin='+vin+'" style="border: 0pt none;overflow-x: hidden; overflow-y: auto;background: white;"></iframe>';
            var source = '';
        }
    {/literal}
    </script>

    {rlHook name='listingDetailsBottomJs'}

    <script class="fl-js-dynamic">
    {literal}
    $(document).ready(function(){
        if (ld_inactive) {
            printMessage('warning', ld_inactive, false, true);
        }

        $('#df_field_vin .value').append(html);

        $('#df_field_vin .value a').flModal({
            content: frame,
            source: source,
            width: 900,
            height: 640
        });

        flynaxTpl.setupTextarea();
        flynaxTpl.contactSeller();
    });

    {/literal}
    </script>

</div>
{else}
    <!-- TODO HERE -->
{/if}

<!-- listing details end -->
