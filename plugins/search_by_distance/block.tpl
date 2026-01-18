<!-- search by distance block -->

{include file=$smarty.const.RL_PLUGINS|cat:'search_by_distance'|cat:$smarty.const.RL_DS|cat:'config.js.tpl'}

{strip}

{if $block.Side == 'top' || $block.Side == 'middle' || $block.Side == 'bottom'}
    {assign var='sbd_content_box' value=true}
{elseif $block.Side == 'middle_left' || $block.Side == 'middle_right'}
    {assign var='sbd_middle_box' value=true}
{/if}

<section class="sbd-box side_block_search{if $pageInfo.Key == 'search_by_distance' && $search_types} sbd-on-page{/if}">

{if $pageInfo.Key != 'search_by_distance'}
    <form method="post" action="{$rlBase}{if $config.mod_rewrite}{$pages.search_by_distance}.html{else}?page={$pages.search_by_distance}{/if}">
        <input type="hidden" name="sbd_block" value="1" />
{/if}

        <div class="form-container row g-3 {if $block.Side != 'left'}light-inputs{/if}">
            {if $sbd_countries|@count > 1}
                <div class="{if !$sbd_middle_box}col-md-3 {/if}{if $sbd_content_box}col-lg-3{else}col-lg-12{/if}">
                    <select class="w-100" name="block_country">
                        <option value="">{$lang.sbd_select_country}</option>

                        {assign var='selected' value=0}
                        {foreach from=$sbd_countries item='country'}
                            {if !$selected}
                                {if $smarty.post.block_country == $country.Code}
                                    {assign var='selected' value=1}
                                {elseif !$smarty.post.block_country && $smarty.session.GEOLocationData->Country_code == $country.Code}
                                    {assign var='selected' value=1}
                                {elseif !$smarty.post.block_country && $country.Code == $config.sbd_default_country}
                                    {assign var='selected' value=1}
                                {/if}
                            {/if}

                            <option data-key="{$country.Key}" value="{$country.Code}"{if $selected === 1} selected="selected"{assign var='selected' value=2}{/if}>{phrase key=$country.pName}</option>
                        {/foreach}
                    </select>
                </div>
            {else $sbd_countries|@count <= 1}
                <select class="d-none" name="block_country"><option checked="checked" value="{$config.sbd_default_country}"></option></select>
            {/if}

            <div class="{if !$sbd_middle_box}col-md-6 {/if}{if $sbd_content_box}col-lg-6{else}col-lg-12{/if}">
                <div class="d-flex">
                    <div class="mr-3">
                        <select class="w-100" name="block_distance">
                            {assign var='selected' value=0}
                            {foreach from=','|explode:$config.sbd_distance_items item='distance'}
                                {if !$selected}
                                    {if $smarty.post.block_distance == $distance}
                                        {assign var='selected' value=1}
                                    {elseif !$smarty.post.block_distance && $distance == $config.sbd_default_distance}
                                        {assign var='selected' value=1}
                                    {/if}
                                {/if}
                                <option value="{$distance}"{if $selected === 1} selected="selected"{assign var='selected' value=2}{/if}>{$distance} {if $config.sbd_units == 'miles'}{$lang.sbd_mi_short}{else}{$lang.sbd_km_short}{/if}</option>
                            {/foreach}
                        </select>
                    </div>

                    <div class="flex-fill">
                        {assign var='ph_replace' value=`$smarty.ldelim`type`$smarty.rdelim`}

                        {if $config.sbd_search_mode == 'mixed'}
                            <input class="w-100" type="text" placeholder="{$lang.sbd_within|replace:$ph_replace:$lang.sbd_location_search_hint}" name="block_zip" id="block_location_search" value="{$smarty.post.block_zip}" />

                            <input type="hidden" name="block_lat" value="{$smarty.post.block_lat}" />
                            <input type="hidden" name="block_lng" value="{$smarty.post.block_lng}" />

                            {addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/lib.js'}

                            <script class="fl-js-dynamic">
                            {literal}

                            $(function(){
                                if (typeof(sbdLocationAutocomplete) != 'undefined') {
                                    sbdLocationAutocomplete('input#block_location_search');
                                }
                            });

                            {/literal}
                            </script>
                        {else}
                            <input class="w-100" placeholder="{$lang.sbd_within|replace:$ph_replace:$lang.sbd_zipcode}" maxlength="10" name="block_zip" type="text" value="{if $smarty.post.block_zip}{$smarty.post.block_zip|htmlspecialchars}{/if}" />
                        {/if}
                    </div>
                </div>
            </div>

            {if $pageInfo.Key == 'search_by_distance' && $search_types}
                {include file=$smarty.const.RL_PLUGINS|cat:'search_by_distance'|cat:$smarty.const.RL_DS|cat:'refine_search.tpl'}
            {/if}

            <div class="{if !$sbd_middle_box}col-md-3 {/if}{if $sbd_content_box}col-lg-3{else}col-lg-12{/if}"><input class="w-100" type="submit" value="{$lang.search}" accesskey="{$lang.search}" /></div>
        </div>

{if $pageInfo.Key != 'search_by_distance'}
    </form>
{/if}

</section>

{/strip}

<!-- search by distance block -->
