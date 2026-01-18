<!-- testimonials box -->

{if $testimonial_box}
    {if $testimonials_long}
        <div class="row">
        {foreach from=$testimonial_box item='item' name='tName'}
            {assign var='read_more' value=false}
            {if $smarty.foreach.tName.last}
                {assign var='read_more' value=true}
            {/if}
            <div class="col-md-4{if !$smarty.foreach.tName.last} mb-4 mb-md-0{/if}">
                {include file=$smarty.const.RL_PLUGINS|cat:'testimonials/box.item.tpl' testimonial_item=$item}
            </div>
        {/foreach}
        </div>
    {else}
        {include file=$smarty.const.RL_PLUGINS|cat:'testimonials/box.item.tpl' testimonial_item=$testimonial_box read_more=true}
    {/if}
{else}
    <div class="info pb-4">{$lang.testimonials_no_testimonials} <a href="{pageUrl key='testimonials'}#add-testimonial">{$lang.testimonials_add_testimonial}</a></div>
{/if}

<script class="fl-js-dynamic">
{literal}

$(function() {
    var color = $('.testimonial-item div.hlight').css('background-color');
    $('.testimonial-item .testimonial-triangle').css(
        'border-' + (rlLangDir == 'rtl' ? 'top' : 'right') + '-color',
        color
    );
});

{/literal}
</script>

<!-- testimonials box end -->
