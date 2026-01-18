<!-- display label -->
{assign var='label_mode' value=false}
{if $featured_listing.Sub_status}
    {assign var='listing_label' value=$featured_listing}
{elseif $listing.Sub_status}
    {assign var='listing_label' value=$listing}
{elseif $listing_data.Sub_status}
    {assign var='listing_label' value=$listing_data}
    {assign var='label_mode' value=true}
{/if}
{if $listing_label.Sub_status_data}
    {addCSS file=$smarty.const.RL_PLUGINS_URL|cat:'listing_status/static/style.css'} 
{/if}

{assign var='labels_class' value=''}
{if $tpl_settings.name|strstr:'modern' || $tpl_settings.name|strstr:'simple' || $tpl_settings.name|strstr:'sun_cocktails'}
    {assign var='labels_class' value='lb_size-1'}
{elseif $tpl_settings.name|strstr:'escort_nova' && !$label_mode}
    {assign var='labels_class' value='lb_enova'}
{/if}


<div class="listing_labels {$labels_class}">{strip}
    {if $listing_label.Sub_status_data}
        {assign var='statuses' value=","|explode:$listing_label.Sub_status_data}
        {foreach from=$statuses item='item'}
            {assign var='data' value=$lb_statuses[$item]}

            {if $data}
                {getLabel assign="label" key=$item mode=$label_mode}

                {if $data.Watermark_type=='text'}
                    {if $label.name}
                        <div class="sub_status sub_status__text sub_status__position-{$data.Position}" data-name="{$label.name}"></div>
                    {/if}
                {else}
                    {if $label.img}
                        <div class="sub_status sub_status__img sub_status__position-{$data.Position}" data-name="{$label.name}">
                            <img style="background: transparent!important;" src="{$smarty.const.RL_FILES_URL}watermark/{$label.img}"/>
                        </div>
                    {else}
                        <div class="sub_status sub_status__text sub_status__position-{$data.Position}" data-name="{$label.name}"></div>
                    {/if}
                {/if}
            {/if}
        {/foreach}
    {/if}
{/strip}</div>

<!-- display label end -->
