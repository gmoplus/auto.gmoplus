<!-- testimonial item -->

<div class="testimonial-item pb-4">
    <div class="p-4 hlight d-flex">
        <svg viewBox="0 0 18 13" class="testimonials-quote flex-shrink-0 header-usernav-icon-fill mt-1 mr-3">
            <use xlink:href="#quote-icon"></use>
        </svg>
        <p>
            {$testimonial.Testimonial|regex_replace:'/(https?\:\/\/[^\s]+)/':'<a href="$1">$1</a>'|nl2br}
        </p>
    </div>

    <div class="testimonial-bottom position-relative d-flex pt-1">
        <div class="testimonial-triangle"></div>
    
        <span class="author flex-fill">
            {if $testimonial.ProfileLink}<a href="{$testimonial.ProfileLink}">{/if}
            {$testimonial.Author}
            {if $testimonial.ProfileLink}</a>{/if}
        </span> 
        <span class="date mr-3">
            <span>{$testimonial.Date|date_format:'%d %b.'}</span>
        </span>
    </div>
</div>

<!-- testimonial item end -->
