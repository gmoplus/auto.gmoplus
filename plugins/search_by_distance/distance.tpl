<!-- distance (search by distance) -->

{if $listing}
    {assign var='source_item' value=$listing}
{else}
    {assign var='source_item' value=$dealer}
{/if}

{if $config.sbd_units == 'miles'}
    {assign var='sbd_unit' value='mi'}
{else}
    {assign var='sbd_unit' value='km'}
{/if}

{assign var='sbd_key_short' value='sbd_'|cat:$sbd_unit|cat:'_short'}
{assign var='sbd_key' value='sbd_'|cat:$sbd_unit}
{assign var='sbd_distance' value=$source_item.sbd_distance}

{if $sbd_unit == 'km'}
    {assign var='sbd_distance' value=$source_item.sbd_distance*1.609344}
{/if}
<span class="icon" style="padding: {if $text_dir == 'left'}0 2px 0 19px{else}0 19px 0 2px{/if};background: url('{$smarty.const.RL_PLUGINS_URL}search_by_distance/static/target.png') 0 3px no-repeat;" title="{$lang.sbd_distance}: {$sbd_distance|round:1} {$lang.$sbd_key}">{$sbd_distance|round:1} {$lang.$sbd_key_short}</span>

<!-- distance (search by distance) end -->
