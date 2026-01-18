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

<div class="listing-details details {if $config.map_module && $location.direct}loc-exists{/if}">
    {rlHook name='listingDetailsTopTpl'}

    <div class="d-flex flex-wrap flex-md-nowrap top-navigation mb-3">
        <div class="d-flex flex-fill flex-nowrap align-items-baseline mr-0 mr-md-3 mb-2 mb-md-0">
            <div class="d-flex justify-content-between">
                {rlHook name='tplListingDetailsNavLeft'}
                {rlHook name='tplListingDetailsNavRight'}
            </div>
            <h1 class="flex-fill">{$pageInfo.name}</h1>
        </div>
        {include file=$controllerDir|cat:'listing_details/navigation.tpl'}
    </div>

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

    <div id="area_listing" class="tab_area{if !$listing_type.Photo || !$photos} no-pictures{/if}">
        <div class="row">
            <div class="col-md-4">
                <div class="area-listing-stats">
                    {if $photos}
                        <section class="main-section">
                            {include file=$componentDir|cat:'listing-details-fancyapps-gallery/_listing-details-fancyapps-gallery.tpl'}
                        </section>
                    {/if}

                    <div class="stat-data d-none d-md-block">
                        {rlHook name='tplListingDetailsRating'}

                        <!-- statistics area -->
                        <section class="statistics clearfix">
                            <ul class="counters">
                                <li class="row no-gutters">
                                    <div class="col-5"><span class="name">{$lang.category}</span></div>
                                    <div class="col-7"><span class="count"><a title="{$lang[$listing_data.Category_pName]}" href="{$rlBase}{if $config.mod_rewrite}{$pages[$listing_type.Page_key]}/{$listing_data.Path}{if $listing_type.Cat_postfix}.html{else}/{/if}{else}?page={$pages[$listing_type.Page_key]}&category={$listing_data.Category_ID}{/if}">{$lang[$listing_data.Category_pName]}</a></span>
                                    </div>
                                </li>
                                {if $config.display_posted_date}
                                    <li class="row no-gutters">
                                        <div class="col-5"><span class="name">{$lang.posted}</span></div>
                                        <div class="col-7"><span class="count">{$listing_data.Date|date_format:$smarty.const.RL_DATE_FORMAT}</span></div>
                                    </li>{/if}
                                {if $config.count_listing_visits}
                                    <li class="row no-gutters">
                                        <div class="col-5"><span class="name">{$lang.shows}</span></div>
                                        <div class="col-7"><span class="count">{$listing_data.Shows}</span></div>
                                    </li>
                                {/if}
                                {if $listing_data.comments_count}
                                    <li class="row no-gutters">
                                        <div class="col-5"><span class="name">{$lang.comment_tab}</span></div>
                                        <div class="col-7"><span class="count"><a href="#comments">{$listing_data.comments_count}</a></span></div>
                                    </li>
                                {/if}
                                {rlHook name='listingDetailsCounters'}
                            </ul>
                            <ul class="controls">
                                {rlHook name='listingDetailsAfterStats'}
                            </ul>
                        </section>
                        <!-- statistics area end -->
                    </div>
                </div>
            </div>
            <div class="col-md-8">
                <section class="content-section clearfix">

                    <!-- listing details -->
                    <div class="d-flex align-items-center">
                        <!-- price tag -->
                        {if $price_tag_value}
                            <div class="price-tag mb-2 mr-auto" id="df_field_price">
                                <span>{$price_tag_value}</span>
                                {if $listing_data.sale_rent == 2 && $listing.common.Fields.time_frame.value}
                                    / {$listing.common.Fields.time_frame.value}
                                {/if}
                            </div>
                        {/if}
                        <!-- price tag end -->

                        {if $listing.common.Fields.bedrooms.value || $listing.common.Fields.bathrooms.value || $listing.common.Fields.square_feet.value}
                            <ul class="card-info">
                                <li class="services">{strip}
                                    {if $listing.common.Fields.bedrooms.value}
                                        <span title="{$listing.fields.bedrooms.name}" class="badrooms">{$listing.common.Fields.bedrooms.value}</span>
                                    {/if}
                                    {if $listing.common.Fields.bathrooms.value}
                                        <span title="{$listing.fields.bathrooms.name}" class="bathrooms">{$listing.common.Fields.bathrooms.value}</span>
                                    {/if}
                                    {if $listing.common.Fields.square_feet.value}
                                        <span title="{$listing.fields.square_feet.name}" class="square_feet">{$listing.common.Fields.square_feet.value}</span>
                                    {/if}
                                {/strip}</li>
                            </ul>
                        {/if}
                    </div>

                    {rlHook name='listingDetailsPreFields'}

                    <div class="listing-fields">
                    {foreach from=$listing item='group'}
                        <div class="{if $group.Key}{$group.Key}{else}no-group{/if}">
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
                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' id=$group.ID name=$group.name hide=$hide line=true}

                                    {foreach from=$group.Fields item='item' key='field' name='fListings'}
                                        {if !empty($item.value) && $item.Details_page}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                        {/if}
                                    {/foreach}

                                    {if $group.Key == 'location' && $config.map_module && $location.direct}
                                        <section title="{$lang.expand_map}" class="map-capture">
                                            <img alt="{$lang.expand_map}" 
                                                 src="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180}" 
                                                 srcset="{staticMap location=$location.direct zoom=$config.map_default_zoom width=480 height=180 scale=2} 2x" />
                                            <span class="media-enlarge"><span></span></span>
                                        </section>

                                        {include file='blocks'|cat:$smarty.const.RL_DS|cat:'listing_details_static_map.tpl'}
                                    {/if}

                                    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}
                                {/if}

                                {assign var='main_section_no_group' value=false}
                            {else}
                                {if $group.Fields}
                                    {foreach from=$group.Fields item='item'}
                                        {if !empty($item.value) && $item.Details_page}
                                            {include file='blocks'|cat:$smarty.const.RL_DS|cat:'field_out.tpl'}
                                        {/if}
                                    {/foreach}
                                {/if}
                            {/if}
                        </div>
                    {/foreach}
                    </div>
                    <!-- listing details end -->
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

            	<script class="fl-js-static">
            	{literal}
            	$(document).ready(function(){
            		if ( ld_inactive ) {
            			printMessage('warning', ld_inactive, false, true);
            		}
            		
        			$('#df_field_vin .value').append(html);
        			
        			$('#df_field_vin .value a').flModal({
        				content: frame,
        				source: source,
        				width: 900,
        				height: 640
        			});
            	});
            	{/literal}
            	</script>
            </div>
        </div>
    </div>

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
</div>
{else}
	<!-- TODO HERE -->
{/if}

<!-- listing details end -->
