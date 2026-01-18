{if $recent_posts}
    {if $block.Side == 'middle_left' || $block.Side == 'middle_right'}
        {assign var='box_class' value='col-md-3 col-md-6 mb-3'}
    {elseif $block.Side == 'left'}
        {assign var='box_class' value='col-md-3 col-lg-12 mb-3'}
    {else}
        {assign var='box_class' value='col-md-3 mb-4 mb-md-0'}
    {/if}

    <div class="wp-posts row">
        {foreach from=$recent_posts item='post'}
            <div class="wpb_last_posts {$box_class}">
                <a href="{$post.url}"><h4>{$post.title}</h4></a>
                <div class="d-flex flex-column flex-sm-row flex-md-column mt-2">
                    {if $post.img}
                        <div class="mb-2 mr-0 mr-sm-3 mr-md-0"
                             style="{if $config.wp_box_image_width}width:{$config.wp_box_image_width}px;{/if}{if $config.wp_box_image_height}height:{$config.wp_box_image_height}px;{/if}">
                            <a href="{$post.url}" class="d-block position-relative w-100" style="padding-bottom: 100%;">
                                <img style="object-fit: cover;" class="wp-post-img w-100 h-100 position-absolute" src="{$post.img}">
                            </a>
                        </div>
                    {/if}
                    <p class="wp-desc">{$post.excerpt} <span class="d-block text-right date">{$post.post_date|date_format:$smarty.const.RL_DATE_FORMAT}</span></p>
                </div>
            </div>
        {/foreach}
    </div>
{else}
    <span class="text-notice">{$lang.no_news}</span>
{/if}
