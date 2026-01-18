<!-- rss feed sample -->

<div style="padding: 10px 0 0 0;"><b>{$lang.rssfeed_feed_sample}</b></div>
<ul class="clear_list" style="max-width: 350px;width: 100%;padding: 5px 0 20px;">
    {foreach from=$rss_feed item='rss_item'}
        <li>
            {if $rss_item.title}
                <a target="_blank" href="{$rss_item.link}">{$rss_item.title}</a>
                <div>{$rss_item.description|strip_tags|truncate:'120':'...':true}</div>
            {/if}
        </li>
    {/foreach}
</ul>

<input type="hidden" name="validated" value="1"/>

<!-- rss feed sample END -->
