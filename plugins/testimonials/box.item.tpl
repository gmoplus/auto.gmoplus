<!-- testimonial item box -->
{if $block.Tpl}
    <p>{$testimonial_item.Testimonial|truncate:320:' ...':false|regex_replace:'/(https?\:\/\/[^\s\n\t]+)/':'<a href="$1">$1</a>'|nl2br}</p>

    <div class="d-flex flex-wrap">
        <span class="date flex-fill pt-2 pr-2 font-size-sm mr-auto">
            {$testimonial_item.Author}
        </span>

        {if $read_more}<a class="mx-auto pt-2" href="{pageUrl key='testimonials'}">{$lang.testimonials_read_more}</a>{/if}
    </div>
{else}
    <div class="testimonial-item">
        <div class="p-4 hlight d-flex">
            <svg viewBox="0 0 18 13" class="testimonials-quote flex-shrink-0 header-usernav-icon-fill mt-1 mr-3">
                <use xlink:href="#quote-icon"></use>
            </svg>
            <p>
                {$testimonial_item.Testimonial|truncate:320:' ...':false|regex_replace:'/(https?\:\/\/[^\s\n\t]+)/':'<a href="$1">$1</a>'|nl2br}
            </p>
        </div>

        <div class="testimonial-bottom position-relative d-flex flex-wrap">
            <div class="testimonial-triangle"></div>
        
            <span class="pt-1 author date font-size-sm flex-fill">
                {$testimonial_item.Author}
            </span> 
            
            {if $read_more}<a class="mx-auto pt-1" href="{pageUrl key='testimonials'}">{$lang.testimonials_read_more}</a>{/if}
        </div>
    </div>
{/if}
<!-- testimonial item box end -->
