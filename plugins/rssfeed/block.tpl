<!-- rss feed block tpl -->

{if $rss_feed}
    <ul>
        {foreach from=$rss_feed item='feed_item' name='feedF'}
            <li {if !$smarty.foreach.feedF.last}style="padding: 0 0 10px;"{/if}>
                <a title="{$feed_item.title}"
                   href="{if !preg_match('/^http/', $feed_item.link)}http://{/if}{$feed_item.link}">{$feed_item.title}</a>&nbsp;<a
                        target="_blank" title="{$feed_item.title}" href="{$feed_item.link}"><img
                            style="width: 10px;height: 14px;background: url({$smarty.const.RL_PLUGINS_URL}rssfeed/static/gallery.png) 0 0 no-repeat;"
                            src="{$rlTplBase}img/blank.gif" alt=""/></a>
            </li>
        {/foreach}
    </ul>
{else}
    <div class="text-notice">{$lang.rssfeed_not_found}</div>
{/if}

<!-- rss feed block tpl end -->
