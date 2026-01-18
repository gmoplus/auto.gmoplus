<!-- zip code search field tpl -->

{include file=$smarty.const.RL_PLUGINS|cat:'search_by_distance'|cat:$smarty.const.RL_DS|cat:'config.js.tpl'}

<div class="two-inline left">
    <div style="margin-{if $smarty.const.RL_LANG_DIR == 'rtl'}left{else}right{/if}: 15px;">
        <select name="f[{$field.Key}][distance]">
            {assign var='selected' value=0}
            {foreach from=','|explode:$config.sbd_distance_items item='distance'}
                {if !$selected}
                    {if $fVal.$fKey.distance == $distance}
                        {assign var='selected' value=1}
                    {elseif !$fVal.$fKey.distance && $distance == $config.sbd_default_distance}
                        {assign var='selected' value=1}
                    {/if}
                {/if}
                
                <option{if $selected === 1} selected="selected"{assign var='selected' value=2}{/if} value="{$distance}">{$distance} {if $config.sbd_units == 'miles'}{$lang.sbd_mi_short}{else}{$lang.sbd_km_short}{/if}</option>
            {/foreach}
        </select>
    </div>

    <div>
        {assign var='ph_replace' value=`$smarty.ldelim`type`$smarty.rdelim`}

        {if $config.sbd_search_mode == 'mixed'}
            <input style="width: 100%;" type="text" placeholder="{$lang.sbd_within|replace:$ph_replace:$lang.sbd_location_search_hint}" name="f[{$field.Key}][zip]" id="location_search" value="{$fVal.$fKey.zip}" />

            <input type="hidden" name="f[{$field.Key}][lat]" value="{$fVal.$fKey.lat}" />
            <input type="hidden" name="f[{$field.Key}][lng]" value="{$fVal.$fKey.lng}" />

            {addJS file=$smarty.const.RL_PLUGINS_URL|cat:'search_by_distance/static/lib.js'}
            <script class="fl-js-dynamic">{literal}if (typeof(sbdLocationAutocomplete) != 'undefined') { sbdLocationAutocomplete('input#location_search', '{/literal}{$field.Key}{literal}') } {/literal}</script>
        {else}
            <input style="width: 100%;" placeholder="{$lang.sbd_within|replace:$ph_replace:$lang.sbd_zipcode}" {if $fVal.$fKey.zip}value="{$fVal.$fKey.zip}"{/if} type="text" name="f[{$field.Key}][zip]" maxlength="10" />
        {/if}
    </div>
</div>

<!-- zip code search field tpl end -->
