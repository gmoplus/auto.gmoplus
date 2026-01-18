<!-- xml feeds tpl -->

{if $formats}
    {if $feeds}
        <div class="list-table content-padding mb-4">
            <div class="header">
                <div>#</div>
                <div>{$lang.name}</div>
                <div>{$lang.xf_feed_url}</div>
                <div>{$lang.last_check}</div>
                <div>{$lang.status}</div>
                <div>{$lang.actions}</div>
            </div>

            {foreach from=$feeds item='feed' name='feedsLoop'}
                <div class="row" id="item_{$feed.ID}">
                    <div class="iteration no-flex">{$smarty.foreach.feedsLoop.iteration}</div>
                        <div class="table-cell" data-caption="{$lang.item}">
                            {$feed.Name}
                        </div>

                        <div class="table-cell" data-caption="{$lang.xf_feed_url}">
                            <a target="_blank" href="{$feed.Url}">{$feed.Url}</a>
                        </div>

                        <div class="table-cell" data-caption="{$lang.last_check}">
                            {if $feed.Lastrun}
                            <div class="value"><span class="text">
                                {$feed.Lastrun|date_format:$smarty.const.RL_DATE_FORMAT}
                            </span></div>
                            {else}
                                {$lang.not_available}
                            {/if}
                        </div>

                        <div class="table-cell" data-caption="{$lang.status}">
                            {$lang[$feed.Status]}
                        </div>

                        <div class="table-cell no-flex clearfix">
                            <span id="delete_{$feed.ID}" class="icon delete deleteXmlFeed" title="{$lang.delete}"></span>
                        </div>
                    </div>
            {/foreach}
        </div>
    {/if}

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_header.tpl' name=$lang.xf_add_feed}

    <form method="post" action="">
        <input type="hidden" name="submit" value="1" />

        <div class="submit-cell">
            <div class="name">{$lang.name}</div>
            <div class="field">
                <input class="w240" type="text" name="feed_name" value="{$smarty.post.feed_name}" />
            </div>
        </div>
        <div class="submit-cell">
            <div class="name">{$lang.xf_feed_url}</div>
            <div class="field">
                <input class="w350" type="text" name="feed_url" value="{$smarty.post.feed_url}" />
            </div>
        </div>
        <div class="submit-cell">
            <div class="name">{$lang.xf_format}</div>
            <div class="field">
                <select name="xml_format" class="w240">
                    <option value="0">{$lang.select}</option>
                    {foreach from=$formats item="format"}
                        <option {if $smarty.post.xml_format == $format.Key}selected="selected"{/if}
                            value="{$format.Key}">
                            {$format.Name}
                        </option>
                    {/foreach}
                </select>
            </div>
        </div>
        <div class="submit-cell buttons">
            <div class="name"></div>
            <div class="field">
                <input style="vertical-align:middle" type="submit" value="{$lang.add}" />
            </div>
        </div>
    </form>

    {include file='blocks'|cat:$smarty.const.RL_DS|cat:'fieldset_footer.tpl'}

    <script class="fl-js-dynamic">{literal}
    $(function() {
        $('.deleteXmlFeed').each(function() {
            // remake without xajax
            $(this).flModal({
                caption: '{/literal}{$lang.warning}{literal}',
                content: '{/literal}{phrase key='drop_confirm' db_check=true}{literal}',
                prompt : 'xajax_deleteXmlFeed('+ $(this).attr('id').split('_')[1] +')',
                width  : 'auto',
                height : 'auto'
            });
        });
    });
    {/literal}</script>
{/if}

<!-- xml feeds tpl end -->
