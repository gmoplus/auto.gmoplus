<!-- faqs tpl -->

<div class="content-padding">
    {if empty($faqs)}
        {if !empty($all_faqs)}
            <ul class="news faqs">
                {foreach from=$all_faqs item='faqs'}
                    <li class="page mb-3">
                        <div>
                            <div class="date">{$faqs.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>
                            <a class="link-large"
                                title="{$faqs.title}"
                                href="{strip}
                                    {if $config.mod_rewrite}
                                        {pageUrl key='faqs' add_url=$faqs.Path}
                                    {else}
                                        {pageUrl key='faqs' vars="id=`$faqs.ID`"}
                                    {/if}
                                {/strip}"
                            >
                                <h4>{$faqs.title}</h4>
                                {rlHook name='newsPostCaption'}
                            </a>
                        </div>

                        <article>
                            {$faqs.content|regex_replace:"/(<style[^>]*>[^>]*<\\/style>)/mi":""|strip_tags:false|truncate:$config.faqs_page_content_length:"":false}{if $faqs.content|strlen > $config.faqs_page_content_length}...{/if}
                            {rlHook name='faqsPostContent'}
                        </article>
                    </li>
                {/foreach}
            </ul>

            <!-- paging block -->
            {paging calc=$pInfo.calc total=$all_faqs current=$pInfo.current per_page=$config.faqs_at_page}
            <!-- paging block end -->
        {else}
            <div class="info">{$lang.no_faqs}</div>
        {/if}
    {else}
        <div class="date">{$faqs.Date|date_format:$smarty.const.RL_DATE_FORMAT}</div>

        <article class="news">
            {$faqs.content}
        </article>

        <div class="ralign">
            <a title="{$lang.back_to_faqs}" href="{pageUrl key='faqs'}">
                {$lang.back_to_faqs}
            </a>
        </div>
    {/if}
</div>
<!-- faqs tpl end -->
