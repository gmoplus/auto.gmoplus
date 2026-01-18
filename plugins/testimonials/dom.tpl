<!-- dom tpl -->

{if $testimonials}
    <div class="testimonials-container">
    {foreach from=$testimonials item='testimonial' name='testimonialsF'}
        {include file=$smarty.const.RL_PLUGINS|cat:'testimonials/item.tpl'}
    {/foreach}
    </div>

    {paging calc=$countTestimonials total=$testimonials|@count current=$testimonials_page per_page=$config.testimonials_per_page} 
{/if}

<!-- dom tpl end -->
