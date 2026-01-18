<!-- faqs block tpl -->
{if !empty($all_faqs_block)}
    <ul class="news faqs">
        {foreach from=$all_faqs_block item='faqs'}
            <li class="mb-3">
                <div>
                    <div class="date">
                        {$faqs.Date|date_format:$smarty.const.RL_DATE_FORMAT}
                    </div>
                    <a title="{$faqs.title}"
                        href="{strip}
                            {if $config.mod_rewrite}
                                {pageUrl key='faqs' add_url=$faqs.Path}
                            {else}
                                {pageUrl key='faqs' vars="id=`$faqs.ID`"}
                            {/if}
                        {/strip}"
                    >
                        {$faqs.title}
                    </a>
                </div>
                <article>
                    {$faqs.content|regex_replace:"/(<style[^>]*>[^>]*<\\/style>)/mi":""|strip_tags:false|truncate:$config.faqs_block_content_length:"":false}
                    {if $faqs.content|strlen > $config.faqs_block_content_length}...{/if}
                </article>
            </li>
        {/foreach}
    </ul>
    <div class="ralign">
        <a title="{$lang.view_all_faqs}" href="{pageUrl key='faqs'}">
            {$lang.view_all_faqs}
        </a>
    </div>
{else}
    {$lang.no_faqs}
{/if}
<!-- faqs block tpl end -->
